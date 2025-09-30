<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalendarDayRequest;
use App\Http\Requests\CalendarEventRequest;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    private const ISO_DAY_MAP = [
        1 => 'mon',
        2 => 'tue',
        3 => 'wed',
        4 => 'thu',
        5 => 'fri',
        6 => 'sat',
        7 => 'sun',
    ];

    public function events(CalendarEventRequest $request): JsonResponse
    {
        $userId = $this->currentUserId();
        $validated = $request->validated();

        $start = Carbon::parse($validated['start'])->startOfDay();
        $end = Carbon::parse($validated['end'])->endOfDay();

        $orders = Order::with('client')
            ->where('master_id', $userId)
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$start, $end])
            ->orderBy('scheduled_at')
            ->get();

        $events = $orders->map(fn (Order $order) => $this->mapOrderToEvent($order))->values();

        return response()->json([
            'data' => [
                'range' => [
                    'start' => $start->toDateString(),
                    'end' => $end->toDateString(),
                ],
                'events' => $events,
            ],
        ]);
    }

    public function day(CalendarDayRequest $request): JsonResponse
    {
        $userId = $this->currentUserId();
        $validated = $request->validated();
        $date = Carbon::parse($validated['date'])->startOfDay();

        $orders = Order::with('client')
            ->where('master_id', $userId)
            ->whereDate('scheduled_at', $date)
            ->orderBy('scheduled_at')
            ->get();

        $settings = $this->resolveUserSettings($userId);
        $weekdayKey = self::ISO_DAY_MAP[$date->isoWeekday()] ?? null;
        $workHours = $weekdayKey ? Arr::get($settings?->work_hours ?? [], $weekdayKey, []) : [];
        $isWorkingDay = $weekdayKey ? in_array($weekdayKey, $settings?->work_days ?? [], true) : false;

        $busySlots = $orders
            ->filter(fn (Order $order) => $order->scheduled_at !== null)
            ->map(fn (Order $order) => $order->scheduled_at->format('H:i'))
            ->unique()
            ->values();

        $availableSlots = $weekdayKey && $workHours
            ? collect($workHours)
                ->filter(fn ($slot) => ! $busySlots->contains($slot))
                ->values()
            : collect();

        return response()->json([
            'data' => [
                'date' => $date->toDateString(),
                'orders' => $orders->map(fn (Order $order) => $this->mapOrderToDetails($order))->values(),
                'available_slots' => $availableSlots,
                'is_working_day' => $isWorkingDay,
            ],
            'meta' => [
                'settings_notice' => $settings ? null : __('calendar.settings_missing'),
            ],
        ]);
    }

    private function mapOrderToEvent(Order $order): array
    {
        $start = $order->scheduled_at ? $order->scheduled_at->copy() : null;
        $duration = $order->duration ?? $order->duration_forecast ?? 60;
        $end = $start ? $start->copy()->addMinutes($duration) : null;

        $clientName = $order->client?->name;
        $title = $clientName ?: __('calendar.untitled_event');

        $serviceNames = collect($order->services ?? [])
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        return [
            'id' => $order->id,
            'title' => $title,
            'start' => $start?->toIso8601String(),
            'end' => $end?->toIso8601String(),
            'allDay' => false,
            'extendedProps' => [
                'status' => $order->status,
                'status_label' => $order->status_label,
                'client' => [
                    'id' => $order->client?->id,
                    'name' => $clientName,
                    'phone' => $order->client?->phone,
                ],
                'services' => $serviceNames,
                'scheduled_at_formatted' => $start?->format('d.m.Y H:i'),
            ],
        ];
    }

    private function mapOrderToDetails(Order $order): array
    {
        $services = collect($order->services ?? [])->map(function ($service) {
            $price = Arr::get($service, 'price');
            $duration = Arr::get($service, 'duration');

            return [
                'id' => Arr::get($service, 'id'),
                'name' => Arr::get($service, 'name'),
                'price' => is_numeric($price) ? (float) $price : null,
                'duration' => is_numeric($duration) ? (int) $duration : null,
            ];
        })->values();

        return [
            'id' => $order->id,
            'scheduled_at' => $order->scheduled_at?->toIso8601String(),
            'scheduled_at_formatted' => $order->scheduled_at?->format('H:i'),
            'status' => $order->status,
            'status_label' => $order->status_label,
            'note' => $order->note,
            'total_price' => $order->total_price !== null ? (float) $order->total_price : null,
            'services' => $services,
            'client' => [
                'id' => $order->client?->id,
                'name' => $order->client?->name ?? __('calendar.unnamed_client'),
                'phone' => $order->client?->phone,
                'email' => $order->client?->email,
            ],
        ];
    }

    private function currentUserId(): int
    {
        $userId = Auth::guard('sanctum')->id();

        if (! $userId) {
            abort(403);
        }

        return $userId;
    }

    private function resolveUserSettings(int $userId): ?Setting
    {
        return Setting::where('user_id', $userId)->first();
    }
}
