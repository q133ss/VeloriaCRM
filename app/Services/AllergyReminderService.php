<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AllergyReminderService
{
    public const DEFAULT_MINUTES = 15;

    public function buildSettingsPayload(?Setting $settings, User $user, bool $available): array
    {
        $exclusions = $this->normalizeExclusions($settings?->allergy_reminder_exclusions, $user);

        return [
            'enabled' => $available ? (bool) ($settings?->allergy_reminder_enabled ?? false) : false,
            'available' => $available,
            'minutes' => (int) ($settings?->allergy_reminder_minutes ?: self::DEFAULT_MINUTES),
            'exclusions' => $exclusions,
            'required_plan' => 'pro',
            'upgrade_url' => url('/subscription'),
        ];
    }

    public function normalizeExclusions(null|array $value, User $user): array
    {
        $payload = is_array($value) ? $value : [];

        $allergies = collect(data_get($payload, 'allergies', []))
            ->map(fn ($item) => is_string($item) ? trim($item) : null)
            ->filter()
            ->unique(fn (string $item) => Str::lower($item))
            ->values()
            ->all();

        $allowedServiceIds = Service::query()
            ->where('user_id', $user->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $services = collect(data_get($payload, 'services', []))
            ->map(function ($item) {
                if (is_numeric($item)) {
                    return (int) $item;
                }

                return null;
            })
            ->filter(fn (?int $id) => $id !== null)
            ->filter(fn (int $id) => in_array($id, $allowedServiceIds, true))
            ->unique()
            ->values()
            ->all();

        return [
            'allergies' => $allergies,
            'services' => $services,
        ];
    }

    public function dispatchDueReminders(?Carbon $now = null, ?int $userId = null): array
    {
        $now = ($now ?: Carbon::now())->copy();
        $result = [
            'processed' => 0,
            'sent' => 0,
            'skipped' => 0,
            'items' => [],
        ];

        $settingsQuery = Setting::query()
            ->with('user')
            ->where('allergy_reminder_enabled', true);

        if ($userId !== null) {
            $settingsQuery->where('user_id', $userId);
        }

        /** @var EloquentCollection<int, Setting> $settingsCollection */
        $settingsCollection = $settingsQuery->get();

        foreach ($settingsCollection as $settings) {
            $user = $settings->user;

            if (! $user || ! $this->userHasProAccess($user)) {
                $result['skipped']++;
                $result['items'][] = [
                    'user_id' => $settings->user_id,
                    'status' => 'skipped',
                    'reason' => 'plan_unavailable',
                ];
                continue;
            }

            $minutes = max(1, (int) ($settings->allergy_reminder_minutes ?: self::DEFAULT_MINUTES));
            $windowStart = $now->copy()->addMinutes($minutes);
            $windowEnd = $windowStart->copy()->addMinute();
            $exclusions = $this->normalizeExclusions($settings->allergy_reminder_exclusions, $user);

            $orders = Order::query()
                ->with(['master'])
                ->where('master_id', $user->id)
                ->whereIn('status', ['new', 'confirmed'])
                ->whereNull('actual_started_at')
                ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
                ->orderBy('scheduled_at')
                ->get();

            foreach ($orders as $order) {
                $result['processed']++;

                $status = $this->dispatchForOrder($order, $user, $minutes, $exclusions);
                $result['items'][] = [
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'status' => $status,
                ];

                if ($status === 'sent') {
                    $result['sent']++;
                } else {
                    $result['skipped']++;
                }
            }
        }

        return $result;
    }

    protected function dispatchForOrder(Order $order, User $master, int $minutes, array $exclusions): string
    {
        if (! $order->scheduled_at) {
            return 'skipped_missing_schedule';
        }

        if (
            $order->allergy_reminder_sent_for
            && $order->allergy_reminder_sent_for->equalTo($order->scheduled_at)
        ) {
            return 'skipped_already_sent';
        }

        $clientProfile = Client::query()
            ->where('user_id', $order->client_id)
            ->first();

        $allergies = collect($clientProfile?->allergies ?? [])
            ->map(fn ($item) => is_string($item) ? trim($item) : null)
            ->filter()
            ->values();

        if ($allergies->isEmpty()) {
            return 'skipped_no_allergies';
        }

        $excludedAllergies = collect($exclusions['allergies'] ?? [])
            ->map(fn ($item) => Str::lower((string) $item))
            ->values();

        $hasExcludedAllergy = $allergies->contains(function (string $item) use ($excludedAllergies) {
            return $excludedAllergies->contains(Str::lower($item));
        });

        if ($hasExcludedAllergy) {
            return 'skipped_allergy_exception';
        }

        $serviceIds = collect($order->services ?? [])
            ->pluck('id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values();

        $excludedServiceIds = collect($exclusions['services'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($serviceIds->intersect($excludedServiceIds)->isNotEmpty()) {
            return 'skipped_service_exception';
        }

        $clientName = $clientProfile?->name ?: 'Клиент';
        $allergyText = $allergies->implode(', ');
        $timeLabel = $order->scheduled_at->format('H:i');

        app(NotificationService::class)->send(
            $master->id,
            'Проверьте аллергию перед записью',
            "{$clientName} записан(а) на {$timeLabel}. До визита осталось {$minutes} мин. Отмечены аллергии: {$allergyText}.",
            route('orders.show', ['order' => $order->id], false),
        );

        $order->forceFill([
            'allergy_reminder_sent_at' => Carbon::now(),
            'allergy_reminder_sent_for' => $order->scheduled_at->copy(),
        ])->save();

        return 'sent';
    }

    protected function userHasProAccess(User $user): bool
    {
        return $user->plans()
            ->whereIn('name', ['pro', 'Pro', 'PRO', 'elite', 'Elite', 'ELITE'])
            ->where(function ($query) {
                $query
                    ->whereNull('plan_user.ends_at')
                    ->orWhere('plan_user.ends_at', '>', Carbon::now());
            })
            ->exists();
    }

    public function availableServices(User $user): Collection
    {
        return Service::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
            ])
            ->values();
    }
}
