<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LandingStoreRequest;
use App\Http\Requests\LandingUpdateRequest;
use App\Models\Landing;
use App\Models\LandingRequest;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\SupportTicket;
use App\Services\Landing\LandingContent;
use App\Services\Landing\LandingImageStore;
use App\Services\Landing\TemplateRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LandingController extends Controller
{
    /**
     * Сколько сайтов держит бесплатный тариф.
     */
    private const FREE_LANDING_LIMIT = 1;

    /** Support-ticket category of an individual template request; the admin filters by it. */
    private const CUSTOM_DESIGN_CATEGORY = 'custom_design';

    public function index(): JsonResponse
    {
        $userId = $this->currentUserId();

        $landings = Landing::forUser($userId)
            ->withCount('requests')
            ->withMax('requests', 'created_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Landing $landing) => $this->transformLanding($landing))
            ->all();

        return response()->json([
            'data' => $landings,
            'meta' => [
                'can_create' => $this->canCreateLanding($userId),
                'free_limit' => self::FREE_LANDING_LIMIT,
            ],
        ]);
    }

    public function store(LandingStoreRequest $request): JsonResponse
    {
        $userId = $this->currentUserId();
        $this->ensureWithinLandingLimit($userId);

        $type = $request->input('type');
        $template = $request->input('landing') ?: $this->defaultTemplateForType($type);
        $slug = $this->generateSlug($request->input('slug'), $request->input('title'));

        $landing = Landing::create([
            'user_id' => $userId,
            'title' => $request->input('title'),
            'type' => $type,
            'landing' => $template,
            'slug' => $slug,
            'settings' => $request->input('settings'),
            'is_active' => $request->boolean('is_active', true),
            'views' => 0,
        ]);

        return response()->json([
            'message' => __('landings.notifications.created'),
            'data' => $this->transformLanding($landing),
        ], 201);
    }

    public function show(Landing $landing): JsonResponse
    {
        $this->ensureLandingBelongsToUser($landing);

        $landing->loadCount('requests')->loadMax('requests', 'created_at');

        return response()->json(['data' => $this->transformLanding($landing, includeRecentRequests: true)]);
    }

    public function update(LandingUpdateRequest $request, Landing $landing): JsonResponse
    {
        $this->ensureLandingBelongsToUser($landing);

        $payload = $request->only(['title', 'type', 'landing', 'settings']);

        if ($request->has('is_active')) {
            $payload['is_active'] = $request->boolean('is_active');
        }

        if ($request->has('type')) {
            $payload['landing'] = $request->input('landing') ?: $this->defaultTemplateForType($request->input('type'));
        }

        if ($request->has('slug')) {
            $payload['slug'] = $this->generateSlug($request->input('slug'), $payload['title'] ?? $landing->title, $landing->id);
        } elseif ($request->filled('title') && ! $request->has('slug')) {
            $payload['slug'] = $this->generateSlug($landing->slug, $payload['title'], $landing->id, allowExisting: true);
        }

        $landing->fill($payload);
        $landing->save();

        return response()->json([
            'message' => __('landings.notifications.updated'),
            'data' => $this->transformLanding($landing->fresh()->loadCount('requests')->loadMax('requests', 'created_at'), includeRecentRequests: true),
        ]);
    }

    /**
     * Click-editor save: merges the given content keys into the landing.
     * Keys are limited to what the landing's template lists in its manifest.
     */
    public function content(Request $request, Landing $landing, LandingContent $content): JsonResponse
    {
        $this->ensureLandingBelongsToUser($landing);

        $data = $request->validate([
            'changes' => ['required', 'array', 'min:1', 'max:30'],
            'changes.*.key' => ['required', 'string', 'max:64'],
            'changes.*.value' => ['nullable'],
        ]);

        return response()->json([
            'data' => ['values' => $content->apply($landing, $data['changes'])],
        ]);
    }

    /** Click-editor photo upload: replaces the photo in one slot of the template. */
    public function uploadImage(Request $request, Landing $landing, LandingContent $content, LandingImageStore $images): JsonResponse
    {
        $this->ensureLandingBelongsToUser($landing);

        $data = $request->validate([
            'key' => ['required', 'string', 'max:64'],
            'file' => ['required', 'file', 'max:8192', 'mimetypes:image/jpeg,image/png,image/webp', 'dimensions:max_width=9000,max_height=9000'],
        ], [
            'file.mimetypes' => __('landings.editor.errors.image_type'),
            'file.file' => __('landings.editor.errors.image_type'),
            'file.max' => __('landings.editor.errors.image_size'),
            'file.uploaded' => __('landings.editor.errors.image_size'),
            'file.dimensions' => __('landings.editor.errors.image_size'),
        ]);

        abort_unless(array_key_exists($data['key'], $content->imageDefaults($landing)), 422, __('landings.editor.errors.unknown_key'));

        $url = $images->store($landing, $data['key'], $request->file('file'));

        return response()->json(['data' => ['key' => $data['key'], 'url' => $url]]);
    }

    /** Puts the template's stock photo back in a slot. */
    public function resetImage(Landing $landing, string $key, LandingContent $content, LandingImageStore $images): JsonResponse
    {
        $this->ensureLandingBelongsToUser($landing);

        $defaults = $content->imageDefaults($landing);
        abort_unless(isset($defaults[$key]), 404);

        $images->remove($landing, $key);

        return response()->json(['data' => ['key' => $key, 'url' => asset($defaults[$key])]]);
    }

    public function destroy(Landing $landing, LandingImageStore $images): JsonResponse
    {
        $this->ensureLandingBelongsToUser($landing);

        $images->removeAll($landing);
        $landing->delete();

        return response()->json([
            'message' => __('landings.notifications.deleted'),
        ]);
    }

    public function options(): JsonResponse
    {
        $userId = $this->currentUserId();

        $services = Service::query()
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name', 'base_price', 'duration_min'])
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'price' => $service->base_price !== null ? (float) $service->base_price : null,
                'duration' => $service->duration_min !== null ? (int) $service->duration_min : null,
            ])
            ->all();

        $promotions = Promotion::query()
            ->forUser($userId)
            ->orderBy('name')
            ->get(['id', 'name', 'promo_code', 'percent', 'ends_at', 'service_id'])
            ->map(fn (Promotion $promotion) => [
                'id' => $promotion->id,
                'name' => $promotion->name,
                'promo_code' => $promotion->promo_code,
                'percent' => $promotion->percent,
                'ends_at' => optional($promotion->ends_at)->toDateString(),
                'service_id' => $promotion->service_id,
            ])
            ->all();

        return response()->json([
            'data' => [
                'services' => $services,
                'promotions' => $promotions,
                'custom_design' => $this->customDesignInfo(Auth::guard('sanctum')->user()),
                'can_create' => $this->canCreateLanding($userId),
            ],
        ]);
    }

    /**
     * «Нужен свой дизайн»: заявка на индивидуальный шаблон. Приходит в админку как обращение в поддержку.
     * Цена договорная; на Elite первый шаблон бесплатно (метка в тексте обращения, чтобы поддержка видела).
     */
    public function customDesign(Request $request): JsonResponse
    {
        $user = Auth::guard('sanctum')->user();
        abort_unless($user, 403);

        $data = $request->validate([
            'about' => ['required', 'string', 'min:5', 'max:500'],
            'style' => ['nullable', 'string', 'max:500'],
            'links' => ['nullable', 'string', 'max:500'],
            'contact' => ['required', 'string', 'min:3', 'max:120'],
        ]);

        $free = $this->customDesignInfo($user)['free_available'];

        $lines = [
            __('landings.custom.ticket_about', ['value' => $data['about']], 'ru'),
            __('landings.custom.ticket_style', ['value' => $data['style'] ?? '—'], 'ru'),
            __('landings.custom.ticket_links', ['value' => $data['links'] ?? '—'], 'ru'),
            __('landings.custom.ticket_contact', ['value' => $data['contact']], 'ru'),
            __('landings.custom.ticket_plan', ['value' => strtoupper($user->activePlanSlug())], 'ru'),
            $free ? __('landings.custom.ticket_free', [], 'ru') : __('landings.custom.ticket_paid', [], 'ru'),
        ];

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'subject' => __('landings.custom.ticket_subject', [], 'ru'),
            'status' => SupportTicket::STATUS_WAITING,
            'category' => self::CUSTOM_DESIGN_CATEGORY,
            'source' => 'landing_custom_design',
            'last_message_at' => now(),
        ]);

        $message = $ticket->messages()->create([
            'user_id' => $user->id,
            'sender_type' => 'user',
            'message' => implode("\n", $lines),
        ]);
        $ticket->touchLastMessageAt($message->created_at);

        return response()->json(['message' => __('landings.custom.sent')], 201);
    }

    /** @return array{plan: string, free_available: bool} */
    protected function customDesignInfo($user): array
    {
        $plan = $user->activePlanSlug();
        $already = SupportTicket::where('user_id', $user->id)->where('category', self::CUSTOM_DESIGN_CATEGORY)->exists();

        return [
            'plan' => $plan,
            'free_available' => $plan === 'elite' && ! $already,
        ];
    }


    protected function transformLanding(Landing $landing, bool $includeRecentRequests = false): array
    {
        $lastRequestAt = $landing->requests_max_created_at;
        if (is_string($lastRequestAt) && $lastRequestAt !== '') {
            $lastRequestAt = Carbon::parse($lastRequestAt);
        }

        $payload = [
            'id' => $landing->id,
            'title' => $landing->title,
            'type' => $landing->type,
            'landing' => $landing->landing,
            'slug' => $landing->slug,
            'settings' => $landing->settings,
            'is_active' => (bool) $landing->is_active,
            'views' => (int) $landing->views,
            'requests_count' => (int) ($landing->requests_count ?? $landing->requests()->count()),
            'last_request_at' => optional($lastRequestAt ?: $landing->requests()->max('created_at'))->toIso8601String(),
            'created_at' => optional($landing->created_at)->toIso8601String(),
            'updated_at' => optional($landing->updated_at)->toIso8601String(),
            'urls' => [
                'public' => url('/l/' . $landing->slug),
            ],
        ];

        if ($includeRecentRequests) {
            $payload['recent_requests'] = LandingRequest::query()
                ->where('landing_id', $landing->id)
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (LandingRequest $request) => [
                    'id' => $request->id,
                    'client_name' => $request->client_name,
                    'client_phone' => $request->client_phone,
                    'client_email' => $request->client_email,
                    'preferred_date' => optional($request->preferred_date)->toDateString(),
                    'message' => $request->message,
                    'status' => $request->status,
                    'service_name' => Arr::get($request->meta, 'service_name'),
                    'created_at' => optional($request->created_at)->toIso8601String(),
                ])
                ->values()
                ->all();
        }

        return $payload;
    }

    protected function generateSlug(?string $slug, ?string $title, ?int $ignoreId = null, bool $allowExisting = false): string
    {
        $base = Str::slug($slug ?: $title ?: Str::random(6));

        if ($allowExisting && $slug && $base === $slug) {
            return $slug;
        }

        if ($base === '') {
            $base = Str::random(6);
        }

        $candidate = $base;
        $suffix = 1;

        while (
            Landing::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    protected function defaultTemplateForType(string $type): string
    {
        return app(TemplateRegistry::class)->defaultTemplate($type);
    }

    protected function ensureLandingBelongsToUser(Landing $landing): void
    {
        if ($landing->user_id !== $this->currentUserId()) {
            abort(404);
        }
    }

    /**
     * Сайт мастера входит в бесплатный тариф: он клиенток приводит, а не
     * удерживает, и держать его за замком — значит закрывать вход в продукт.
     *
     * Поэтому ограничиваем количество, а не доступ. Упереться в лимит того,
     * чем уже пользуешься, понятнее, чем открыть раздел и получить 403 на
     * первой же кнопке. index() и options() отдают этот же флаг заранее, чтобы
     * список и визард не доводили до формы, которая гарантированно упадёт.
     */
    protected function canCreateLanding(int $userId): bool
    {
        return $this->userHasProAccess() || Landing::forUser($userId)->count() < self::FREE_LANDING_LIMIT;
    }

    protected function ensureWithinLandingLimit(int $userId): void
    {
        if ($this->canCreateLanding($userId)) {
            return;
        }

        abort(response()->json([
            'error' => [
                'code' => 'landing_limit_reached',
                'message' => __('landings.errors.free_limit', ['limit' => self::FREE_LANDING_LIMIT]),
            ],
        ], 403));
    }

    protected function currentUserId(): int
    {
        $userId = Auth::guard('sanctum')->id();

        if (! $userId) {
            abort(403);
        }

        return $userId;
    }

    protected function userHasProAccess(): bool
    {
        return (bool) Auth::guard('sanctum')->user()?->hasProAccess();
    }
}
