<?php

namespace App\Services\Booking;

use App\Models\Appointment;
use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class AvailabilityService
{
    /**
     * @return array<int, string> list of HH:MM slots
     */
    public function availableSlotsForDate(
        int $masterId,
        Service $service,
        string $date,
        ?Setting $setting = null,
        ?string $timezone = null,
    ): array {
        $timezone = $timezone ?: config('app.timezone');
        $setting = $setting ?: Setting::query()->where('user_id', $masterId)->first();

        $day = Carbon::parse($date, $timezone)->startOfDay();
        $dayKey = strtolower($day->format('D'));
        $serviceDuration = (int) ($service->duration_min ?? 60);

        $workHours = $setting?->work_hours ?? [];
        $slots = collect(is_array($workHours) ? Arr::get($workHours, $dayKey, []) : [])
            ->filter(fn ($slot) => is_string($slot) && preg_match('/^\\d{2}:\\d{2}$/', $slot))
            ->values();

        if ($slots->isEmpty()) {
            return [];
        }

        $startDb = $day->copy()->timezone(config('app.timezone'));
        $endDb = $day->copy()->endOfDay()->timezone(config('app.timezone'));

        $busy = collect();

        $appointments = Appointment::query()
            ->where('user_id', $masterId)
            ->whereBetween('starts_at', [$startDb, $endDb])
            ->where('status', '!=', 'cancelled')
            ->get(['starts_at', 'ends_at']);

        foreach ($appointments as $appt) {
            if ($appt->starts_at && $appt->ends_at) {
                $busy->push([
                    'start' => $appt->starts_at->copy()->timezone($timezone),
                    'end' => $appt->ends_at->copy()->timezone($timezone),
                ]);
            }
        }

        $orders = Order::query()
            ->where('master_id', $masterId)
            ->whereBetween('scheduled_at', [$startDb, $endDb])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->get(['scheduled_at', 'services']);

        foreach ($orders as $order) {
            $start = $order->scheduled_at?->copy()->timezone($timezone);
            if (! $start) {
                continue;
            }

            $duration = collect($order->services ?? [])
                ->sum(fn ($item) => (int) Arr::get($item, 'duration', 0));
            $duration = $duration > 0 ? $duration : 60;

            $busy->push([
                'start' => $start,
                'end' => $start->copy()->addMinutes($duration),
            ]);
        }

        $now = Carbon::now($timezone);

        return $slots->filter(function (string $slot) use ($day, $serviceDuration, $busy, $now) {
            $candidateStart = Carbon::createFromFormat('Y-m-d H:i', $day->format('Y-m-d') . ' ' . $slot, $day->getTimezone());

            if ($candidateStart->lessThanOrEqualTo($now->copy()->addMinutes(5))) {
                return false;
            }

            $candidateEnd = $candidateStart->copy()->addMinutes($serviceDuration);

            foreach ($busy as $interval) {
                if ($candidateStart->lt($interval['end']) && $candidateEnd->gt($interval['start'])) {
                    return false;
                }
            }

            return true;
        })->values()->all();
    }
}

