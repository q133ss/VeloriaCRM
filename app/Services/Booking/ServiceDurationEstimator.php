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
        $estimates = [];

        foreach ($this->measurements($masterId) as $signature => $group) {
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
     * The same measurement, per single service, for the price list.
     *
     * Only visits booked on their own count: `services` is a JSON snapshot with
     * no pivot table, so a two-hour appointment for a cut and a blow-dry cannot
     * be divided between them. And unlike the booking form, nothing is filtered
     * out here for agreeing with the plan — the price list is where the plan is
     * set, so it is the caller that compares the two.
     *
     * @return array<int, array{minutes: int, samples: int}> keyed by service id
     */
    public function perServiceFor(int $masterId): array
    {
        $measured = [];

        foreach ($this->measurements($masterId) as $group) {
            if (count($group['service_ids']) !== 1) {
                continue;
            }

            $samples = collect($group['samples']);

            if ($samples->count() < self::MIN_SAMPLES) {
                continue;
            }

            $measured[$group['service_ids'][0]] = [
                'minutes' => $this->roundToFive((float) $samples->median()),
                'samples' => $samples->count(),
            ];
        }

        return $measured;
    }

    /**
     * A difference worth acting on: a quarter of an hour, and at least a sixth
     * of the plan. Anything smaller is noise a master should not be nagged about.
     */
    public function worthSaying(int $median, int $planned): bool
    {
        $delta = abs($median - $planned);

        return $delta >= self::MIN_DELTA_MINUTES && $delta >= $planned * self::MIN_DELTA_RATIO;
    }

    /**
     * Wall-clock durations of finished visits, grouped by the exact set booked.
     *
     * @return array<string, array{service_ids: array<int, int>, planned: int, samples: array<int, int>}>
     */
    private function measurements(int $masterId): array
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

            $signature = $this->signature($ids);
            $grouped[$signature]['service_ids'] = $ids;
            $grouped[$signature]['planned'] = $planned;
            $grouped[$signature]['samples'][] = $measured;
        }

        return $grouped;
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

    private function roundToFive(float $minutes): int
    {
        return (int) (round($minutes / 5) * 5);
    }
}
