<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LandingStoreRequest;
use App\Http\Requests\LandingUpdateRequest;
use App\Models\Landing;
use App\Models\LandingRequest;
use App\Models\Promotion;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LandingController extends Controller
{
    public function index(): JsonResponse
    {
        $this->ensureProAccess();

        $userId = $this->currentUserId();

        $landings = Landing::forUser($userId)
            ->withCount('requests')
            ->withMax('requests', 'created_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Landing $landing) => $this->transformLanding($landing))
            ->all();

        return response()->json(['data' => $landings]);
    }

    public function store(LandingStoreRequest $request): JsonResponse
    {
        $this->ensureProAccess();

        $userId = $this->currentUserId();
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
        $this->ensureProAccess();
        $this->ensureLandingBelongsToUser($landing);

        $landing->loadCount('requests')->loadMax('requests', 'created_at');

        return response()->json(['data' => $this->transformLanding($landing, includeRecentRequests: true)]);
    }

    public function update(LandingUpdateRequest $request, Landing $landing): JsonResponse
    {
        $this->ensureProAccess();
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

    public function destroy(Landing $landing): JsonResponse
    {
        $this->ensureProAccess();
        $this->ensureLandingBelongsToUser($landing);

        $landing->delete();

        return response()->json([
            'message' => __('landings.notifications.deleted'),
        ]);
    }

    public function options(): JsonResponse
    {
        $this->ensureProAccess();

        $userId = $this->currentUserId();

        $services = Service::query()
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
            ])
            ->all();

        $promotions = Promotion::query()
            ->forUser($userId)
            ->orderBy('name')
            ->get(['id', 'name', 'promo_code'])
            ->map(fn (Promotion $promotion) => [
                'id' => $promotion->id,
                'name' => $promotion->name,
                'promo_code' => $promotion->promo_code,
            ])
            ->all();

        return response()->json([
            'data' => [
                'services' => $services,
                'promotions' => $promotions,
            ],
        ]);
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
        $templates = [
            'general' => 'landings.templates.general',
            'promotion' => 'landings.templates.promotion',
            'service' => 'landings.templates.service',
            'seasonal' => 'landings.templates.seasonal',
            'consultation' => 'landings.templates.consultation',
        ];

        return $templates[$type] ?? 'landings.templates.general';
    }

    protected function ensureLandingBelongsToUser(Landing $landing): void
    {
        if ($landing->user_id !== $this->currentUserId()) {
            abort(404);
        }
    }

    protected function ensureProAccess(): void
    {
        if (! $this->userHasProAccess()) {
            abort(response()->json([
                'error' => [
                    'code' => 'plan_required',
                    'message' => __('landings.errors.plan_required'),
                ],
            ], 403));
        }
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
        $user = Auth::guard('sanctum')->user();

        if (! $user) {
            return false;
        }

        return $user->plans()
            ->whereIn('name', ['pro', 'Pro', 'PRO', 'elite', 'Elite', 'ELITE'])
            ->where(function ($query) {
                $query
                    ->whereNull('plan_user.ends_at')
                    ->orWhere('plan_user.ends_at', '>', Carbon::now());
            })
            ->exists();
    }
}
