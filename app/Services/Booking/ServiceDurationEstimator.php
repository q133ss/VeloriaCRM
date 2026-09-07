<?php

namespace App\Services\Booking;

use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * How long this set of services really takes, measured rather than assumed.
 *
 * A price list says an hour; the clock says two hours twenty, every time. The
 * difference is what turns a full day into a day that runs late from noon.
 */
class ServiceDurationEstimator
{
    /** Below this the "usual" duration is one person's opinion, not a pattern. */
    public const MIN_SAMPLES = 3;

    /** A hint that agrees with the plan is not worth the space it takes. */
    private const MIN_DELTA_MINUTES = 15;
    private const MIN_DELTA_RATIO = 0.15;

    /**
     * `duration` is wall-clock between Start and Finish, so a visit where Finish
     * was pressed the next morning lands in the data as several hundred minutes.
     */
    private const MIN_PLAUSIBLE_MINUTES = 5;
    private const MAX_PLAUSIBLE_MINUTES = 600;
    private const MAX_PLAUSIBLE_RATIO = 4;

    /**
     * Every service set this master has measured, keyed by its signature.
     *
     * @return array<string, array{service_ids: array<int, int>, minutes: int, samples: int}>
     */
    public function estimatesFor(int $masterId): array
    {
        $orders = Order::query()
            ->where('master_id', $masterId)
            ->where('status', 'completed')
            ->whereNotNull('duration')
            ->get(['id', 'services', 'duration']);

        $grouped = [];

        foreach ($orders as $order) {
            $ids = $this->serviceIds($order->services ?? []);

            if ($ids === []) {
                continue;
            }

            $planned = $this->plannedMinutes($order->services ?? []);
            $measured = (int) $order->duration;

            if (! $this->plausible($measured, $planned)) {
                continue;
            }

            $grouped[$this->signature($ids)]['service_ids'] = $ids;
            $grouped[$this->signature($ids)]['planned'] = $planned;
            $grouped[$this->signature($ids)]['samples'][] = $measured;
        }

        $estimates = [];

        foreach ($grouped as $signature => $group) {
            $samples = collect($group['samples']);

            if ($samples->count() < self::MIN_SAMPLES) {
                continue;
            }

            $median = $this->roundToFive((float) $samples->median());

            if (! $this->worthSaying($median, $group['planned'])) {
                continue;
            }

            $estimates[$signature] = [
                'service_ids' => $group['service_ids'],
                'minutes' => $median,
                'samples' => $samples->count(),
            ];
        }

        return $estimates;
    }

    /**
     * @param  array<int, int>  $serviceIds
     * @return array{service_ids: array<int, int>, minutes: int, samples: int}|null
     */
    public function estimateForServices(int $masterId, array $serviceIds): ?array
    {
        $ids = $this->serviceIds(array_map(fn ($id) => ['id' => $id], $serviceIds));

        return $this->estimatesFor($masterId)[$this->signature($ids)] ?? null;
    }

    /**
     * The exact set, not its members: `services` is a JSON snapshot with no pivot
     * table, so a two-service booking cannot be split between them.
     *
     * @param  array<int, mixed>  $services
     * @return array<int, int>
     */
    private function serviceIds(array $services): array
    {
        $ids = collect($services)
            ->map(fn ($service) => (int) Arr::get($service, 'id'))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $ids;
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function signature(array $ids): string
    {
        return implode('-', $ids);
    }

    /**
     * @param  array<int, mixed>  $services
     */
    private function plannedMinutes(array $services): int
    {
        $planned = (int) collect($services)->sum(fn ($service) => (int) Arr::get($service, 'duration', 0));

        return $planned > 0 ? $planned : OrderDurationResolver::FALLBACK_MINUTES;
    }

    private function plausible(int $measured, int $planned): bool
    {
        if ($measured < self::MIN_PLAUSIBLE_MINUTES || $measured > self::MAX_PLAUSIBLE_MINUTES) {
            return false;
        }

        return $measured <= $planned * self::MAX_PLAUSIBLE_RATIO;
    }

    private function worthSaying(int $median, int $planned): bool
    {
        $delta = abs($median - $planned);

        return $delta >= self::MIN_DELTA_MINUTES && $delta >= $planned * self::MIN_DELTA_RATIO;
    }

    private function roundToFive(float $minutes): int
    {
        return (int) (round($minutes / 5) * 5);
    }
}
