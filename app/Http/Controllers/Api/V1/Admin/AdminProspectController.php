<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prospect;
use App\Models\Service;
use App\Services\Admin\AdminAuditService;
use App\Services\Prospects\ProspectBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Accounts built in advance for cold outreach. The scraper (VeloriaBomber)
 * posts a group here with a token limited to this controller; the admin page
 * lists the results and accepts the same payload packed in a zip.
 */
class AdminProspectController extends Controller
{
    /** The scraper's token may call these routes and nothing else in the backoffice. */
    public const TOKEN_ABILITY = 'prospects';

    public const TOKEN_NAME = 'prospects-scraper';

    public function __construct(
        private readonly ProspectBuilder $builder,
        private readonly AdminAuditService $auditService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $prospects = Prospect::query()
            ->with(['user', 'landing'])
            ->when($search !== '', fn ($q) => $q->where(fn ($b) => $b
                ->where('title', 'like', '%' . $search . '%')
                ->orWhere('external_id', 'like', '%' . $search . '%')))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->limit(200)
            ->get();

        return response()->json([
            'data' => $prospects->map(fn (Prospect $p) => $this->transform($p))->all(),
        ]);
    }

    public function show(Request $request, string $channel, string $externalId): JsonResponse
    {
        $prospect = Prospect::query()
            ->where('channel', $channel)
            ->where('external_id', Str::lower($externalId))
            ->firstOrFail();

        return response()->json(['data' => $this->transform($prospect)]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request->all());

        @set_time_limit(180);
        $prospect = $this->builder->build($payload, $request->user());
        $this->audit($request, $prospect, 'admin.prospect_built');

        return response()->json(['data' => $this->transform($prospect)], 201);
    }

    /** The same payload as store(), as prospect.json plus the photos it names, in a zip. */
    public function import(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:zip', 'max:51200']]);

        $dir = storage_path('app/prospect-import/' . Str::random(16));
        File::ensureDirectoryExists($dir);

        try {
            $zip = new \ZipArchive();

            if ($zip->open($request->file('file')->getRealPath()) !== true) {
                throw ValidationException::withMessages(['file' => 'Не получилось открыть архив.']);
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);

                // Only the json and pictures, and never outside the folder.
                if (str_contains($name, '..') || str_starts_with($name, '/') || ! preg_match('/\.(json|jpe?g|png|webp)$/i', $name)) {
                    continue;
                }

                $zip->extractTo($dir, $name);
            }

            $zip->close();

            $json = collect(File::allFiles($dir))->first(fn ($f) => $f->getFilename() === 'prospect.json');

            if (! $json) {
                throw ValidationException::withMessages(['file' => 'В архиве нет prospect.json.']);
            }

            $data = json_decode((string) file_get_contents($json->getRealPath()), true);

            if (! is_array($data)) {
                throw ValidationException::withMessages(['file' => 'prospect.json не читается как JSON.']);
            }

            $payload = $this->validatePayload($data);

            @set_time_limit(180);
            $prospect = $this->builder->build($payload, $request->user(), $json->getPath());
            $this->audit($request, $prospect, 'admin.prospect_imported');

            return response()->json(['data' => $this->transform($prospect)], 201);
        } finally {
            File::deleteDirectory($dir);
        }
    }

    public function update(Request $request, Prospect $prospect): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([Prospect::STATUS_BUILT, Prospect::STATUS_SENT])],
        ]);

        abort_unless($prospect->isPending(), 422, 'Аккаунт уже забран или удалён.');

        $prospect->status = $data['status'];
        $prospect->sent_at = $data['status'] === Prospect::STATUS_SENT ? ($prospect->sent_at ?? now()) : null;
        $prospect->save();

        return response()->json(['data' => $this->transform($prospect)]);
    }

    public function destroy(Request $request, Prospect $prospect): JsonResponse
    {
        abort_unless($prospect->isPending() || ! $prospect->user_id, 422, 'Мастер уже забрал аккаунт: удаляйте его как обычного пользователя.');

        $this->audit($request, $prospect, 'admin.prospect_deleted');
        $this->builder->destroy($prospect);
        $prospect->delete();

        return response()->json(['message' => 'Заготовка удалена.']);
    }

    /** A token for the scraper; issuing a new one revokes the old. Shown once. */
    public function token(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->tokens()->where('name', self::TOKEN_NAME)->delete();

        $token = $user->createToken(self::TOKEN_NAME, [self::TOKEN_ABILITY])->plainTextToken;

        $this->auditService->log(
            userId: $user->id,
            actorId: $user->id,
            action: 'admin.prospect_token_issued',
            subject: $user,
            request: $request,
        );

        return response()->json(['data' => ['token' => $token]]);
    }

    private function validatePayload(array $input): array
    {
        $validator = validator($input, [
            'channel' => ['nullable', 'string', Rule::in(['vk'])],
            'external_id' => ['required', 'string', 'max:128', 'regex:/^[A-Za-z0-9_.\-]+$/'],
            'url' => ['nullable', 'url', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:64'],
            'booking' => ['nullable', 'array'],
            'booking.service' => ['nullable', 'string', 'max:64'],
            'booking.url' => ['nullable', 'string', 'max:500'],
            'services' => ['nullable', 'array', 'max:200'],
            'services.*.name' => ['nullable', 'string', 'max:500'],
            'services.*.price' => ['nullable'],
            'services.*.duration' => ['nullable'],
            'services.*.category' => ['nullable', 'string', 'max:120'],
            'texts' => ['nullable', 'array', 'max:20'],
            'texts.*' => ['nullable', 'string', 'max:20000'],
            'avatar' => ['nullable', 'string', 'max:2000'],
            'cover' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:40'],
            'photos.*' => ['nullable', 'string', 'max:2000'],
            'template' => ['nullable', 'string', 'max:64'],
        ]);

        return $validator->validate() + ['channel' => 'vk'];
    }

    private function audit(Request $request, Prospect $prospect, string $action): void
    {
        $this->auditService->log(
            userId: $prospect->user_id ?? $request->user()->id,
            actorId: $request->user()?->id,
            action: $action,
            subject: $prospect,
            request: $request,
            meta: ['external_id' => $prospect->external_id, 'title' => $prospect->title],
        );
    }

    private function transform(Prospect $prospect): array
    {
        $landing = $prospect->landing;
        $pending = $prospect->isPending();

        return [
            'id' => $prospect->id,
            'channel' => $prospect->channel,
            'external_id' => $prospect->external_id,
            'url' => $prospect->url,
            'title' => $prospect->title,
            'booking_service' => $prospect->booking_service,
            'status' => $prospect->status,
            'services_source' => $prospect->services_source,
            'services_count' => $prospect->user_id ? Service::query()->where('user_id', $prospect->user_id)->count() : 0,
            'landing_url' => $landing ? route('landings.public', $landing->slug) : null,
            'editor_url' => $landing ? route('landings.public', ['slug' => $landing->slug, 'edit' => 1]) : null,
            'login' => $pending ? $prospect->user?->email : null,
            'password' => $pending ? $prospect->password : null,
            'claim_url' => $pending && $prospect->claim_token ? route('prospects.claim', $prospect->claim_token) : null,
            'created_at' => optional($prospect->created_at)->toIso8601String(),
            'sent_at' => optional($prospect->sent_at)->toIso8601String(),
            'claimed_at' => optional($prospect->claimed_at)->toIso8601String(),
            'expires_at' => $pending ? optional($prospect->created_at?->copy()->addDays((int) config('prospects.ttl_days')))->toIso8601String() : null,
        ];
    }
}
