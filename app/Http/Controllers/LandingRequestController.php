<?php

namespace App\Http\Controllers;

use App\Http\Requests\LandingPublicRequest;
use App\Models\Client;
use App\Models\Landing;
use App\Models\LandingRequest;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class LandingRequestController extends Controller
{
    public function __invoke(LandingPublicRequest $request, string $slug): JsonResponse
    {
        $landing = Landing::query()->where('slug', $slug)->firstOrFail();

        if (! $landing->is_active) {
            abort(404);
        }

        $validated = $request->validated();
        $service = $this->resolveService($landing, $validated['service_id'] ?? null);

        if (! empty($validated['service_id']) && ! $service) {
            throw ValidationException::withMessages([
                'service_id' => __('landings.validation.service_exists'),
            ]);
        }

        $client = $this->resolveClient($landing, $validated);

        $lead = LandingRequest::query()->create([
            'landing_id' => $landing->id,
            'user_id' => $landing->user_id,
            'client_id' => $client?->id,
            'service_id' => $service?->id,
            'client_name' => $validated['client_name'],
            'client_phone' => $validated['client_phone'],
            'client_email' => $validated['client_email'] ?? null,
            'preferred_date' => ! empty($validated['preferred_date']) ? Carbon::parse($validated['preferred_date'])->toDateString() : null,
            'message' => $validated['message'] ?? null,
            'status' => 'new',
            'meta' => [
                'landing_title' => $landing->title,
                'service_name' => $service?->name,
                'source' => 'public_landing',
            ],
        ]);

        return response()->json([
            'message' => __('landings.public.request_saved'),
            'data' => [
                'id' => $lead->id,
                'service_name' => $service?->name,
            ],
        ], 201);
    }

    private function resolveService(Landing $landing, ?int $serviceId): ?Service
    {
        $serviceQuery = Service::query()->where('user_id', $landing->user_id);
        $settings = $landing->settings ?? [];

        $allowedIds = collect([])
            ->merge(data_get($settings, 'service_ids', []))
            ->merge(array_filter([data_get($settings, 'service_id')]))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($serviceId) {
            $query = (clone $serviceQuery)->whereKey($serviceId);
            if ($allowedIds->isNotEmpty()) {
                $query->whereIn('id', $allowedIds->all());
            }

            return $query->first();
        }

        if ($allowedIds->isNotEmpty()) {
            return (clone $serviceQuery)
                ->whereIn('id', $allowedIds->all())
                ->orderBy('name')
                ->first();
        }

        return (clone $serviceQuery)->orderBy('name')->first();
    }

    private function resolveClient(Landing $landing, array $validated): ?Client
    {
        $phone = trim((string) ($validated['client_phone'] ?? ''));
        $email = trim((string) ($validated['client_email'] ?? ''));

        if ($phone === '' && $email === '') {
            return null;
        }

        $client = Client::query()
            ->where('user_id', $landing->user_id)
            ->when($phone !== '', fn ($query) => $query->where('phone', $phone))
            ->when($phone === '' && $email !== '', fn ($query) => $query->where('email', $email))
            ->first();

        if ($client) {
            $client->forceFill([
                'name' => $validated['client_name'] ?: $client->name,
                'phone' => $phone !== '' ? $phone : $client->phone,
                'email' => $email !== '' ? $email : $client->email,
                'notes' => $client->notes,
            ])->save();

            return $client;
        }

        return Client::query()->create([
            'user_id' => $landing->user_id,
            'name' => $validated['client_name'],
            'phone' => $phone,
            'email' => $email !== '' ? $email : null,
        ]);
    }
}
