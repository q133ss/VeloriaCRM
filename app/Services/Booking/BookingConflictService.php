<?php

namespace App\Services\Booking;

use App\Models\Appointment;
use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class BookingConflictService
{
    public function detectConflict(
        int $masterId,
        Carbon $startsAt,
        int $durationMinutes,
        ?int $ignoreOrderId = null,
    ): ?array {
        $durationMinutes = max(1, $durationMinutes);
        $endsAt = $startsAt->copy()->addMinutes($durationMinutes);
        $dayStart = $startsAt->copy()->startOfDay();
        $dayEnd = $startsAt->copy()->endOfDay();

        $orders = Order::query()
            ->with('client')
            ->where('master_id', $masterId)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->whereBetween('scheduled_at', [$dayStart, $dayEnd])
            ->when($ignoreOrderId, fn ($query) => $query->whereKeyNot($ignoreOrderId))
            ->get();

        foreach ($orders as $order) {
            $orderStart = $order->scheduled_at?->copy();

            if (! $orderStart) {
                continue;
            }

            $orderEnd = $orderStart->copy()->addMinutes($this->resolveOrderDuration($order));

            if ($startsAt->lt($orderEnd) && $endsAt->gt($orderStart)) {
                return [
                    'type' => 'order',
                    'id' => $order->id,
                    'starts_at' => $orderStart,
                    'ends_at' => $orderEnd,
                    'client_name' => $order->client?->name,
                ];
            }
        }

        $appointments = Appointment::query()
            ->where('user_id', $masterId)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('starts_at', [$dayStart, $dayEnd])
            ->get();

        foreach ($appointments as $appointment) {
            if (! $appointment->starts_at || ! $appointment->ends_at) {
                continue;
            }

            if ($startsAt->lt($appointment->ends_at) && $endsAt->gt($appointment->starts_at)) {
                return [
                    'type' => 'appointment',
                    'id' => $appointment->id,
                    'starts_at' => $appointment->starts_at->copy(),
                    'ends_at' => $appointment->ends_at->copy(),
                    'client_name' => null,
                ];
            }
        }

        return null;
    }

    public function resolveOrderDuration(Order $order): int
    {
        $servicesDuration = collect($order->services ?? [])
            ->sum(fn ($item) => (int) Arr::get($item, 'duration', 0));

        return max(1, $servicesDuration ?: (int) ($order->duration_forecast ?: $order->duration ?: 60));
    }
}
