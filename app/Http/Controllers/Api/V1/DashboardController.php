<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsDaily;
use App\Models\Client;
use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
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

    public function show(): JsonResponse
    {
        $userId = $this->currentUserId();
        $today = Carbon::today();

        $ordersToday = Order::with('client')
            ->where('master_id', $userId)
            ->whereDate('scheduled_at', $today)
            ->orderBy('scheduled_at')
            ->get();

        $settings = Setting::where('user_id', $userId)->first();

        $schedule = $this->buildScheduleBlock($ordersToday, $settings, $today);
        $metrics = $this->buildTodayMetrics($ordersToday, $settings, $userId, $today);
        $finance = $this->buildFinanceBlock($userId);
        $aiTips = $this->buildAiTips($userId, $ordersToday, $settings, $today, $finance);
        $learning = $this->buildLearningBlock($userId);

        return response()->json([
            'date' => $today->toDateString(),
            'schedule' => $schedule,
            'metrics' => $metrics,
            'ai_tips' => $aiTips,
            'finance' => $finance,
            'learning' => $learning,
        ]);
    }

    private function buildScheduleBlock(Collection $orders, ?Setting $settings, Carbon $date): array
    {
        $weekdayKey = self::ISO_DAY_MAP[$date->isoWeekday()] ?? null;
        $workHours = $weekdayKey ? Arr::get($settings?->work_hours ?? [], $weekdayKey, []) : [];
        $isWorkingDay = $weekdayKey ? in_array($weekdayKey, $settings?->work_days ?? [], true) : false;

        $items = $orders->map(function (Order $order) {
            $start = $order->scheduled_at ? $order->scheduled_at->copy() : null;
            $duration = $order->duration
                ?? $order->duration_forecast
                ?? collect($order->services ?? [])->sum(fn ($service) => (int) Arr::get($service, 'duration', 0));

            if (! $duration && $start) {
                $duration = 60;
            }

            $end = $start ? $start->copy()->addMinutes($duration) : null;

            $services = collect($order->services ?? [])
                ->map(fn ($service) => [
                    'id' => Arr::get($service, 'id'),
                    'name' => Arr::get($service, 'name'),
                    'price' => $this->toFloat(Arr::get($service, 'price')),
                    'duration' => Arr::get($service, 'duration'),
                ])
                ->values();

            $indicator = $this->buildOrderIndicator($order);

            return [
                'id' => $order->id,
                'time' => $start?->format('H:i'),
                'end_time' => $end?->format('H:i'),
                'duration' => $duration,
                'client' => [
                    'id' => $order->client?->id,
                    'name' => $order->client?->name,
                    'phone' => $order->client?->phone,
                ],
                'services' => $services,
                'total_price' => $order->total_price !== null ? (float) $order->total_price : null,
                'status' => $order->status,
                'status_label' => $order->status_label,
                'indicator' => $indicator,
                'badges' => $this->buildOrderBadges($order, $indicator['level']),
            ];
        })->values();

        $freeSlots = $this->buildFreeSlots($orders, $workHours, $date);

        return [
            'date' => $date->toDateString(),
            'is_working_day' => $isWorkingDay,
            'work_hours' => $workHours,
            'items' => $items,
            'free_slots' => $freeSlots,
        ];
    }

    private function buildOrderIndicator(Order $order): array
    {
        $complexity = (int) ($order->complexity_level ?? 0);
        $reschedules = (int) ($order->reschedule_count ?? 0);
        $reminderSent = (bool) ($order->is_reminder_sent ?? false);
        $status = $order->status;

        if (in_array($status, ['cancelled', 'no_show'], true)) {
            return [
                'level' => 'critical',
                'icon' => '🔴',
                'label' => 'Сложный визит',
                'reason' => $status === 'cancelled'
                    ? 'Запись отменена, клиенту стоит предложить другую дату.'
                    : 'Визит сорвался, стоит связаться с клиентом.',
            ];
        }

        if ($complexity >= 4) {
            return [
                'level' => 'critical',
                'icon' => '🔴',
                'label' => 'Сложный визит',
                'reason' => 'Высокая сложность услуги, подготовьте материалы заранее.',
            ];
        }

        if ($reschedules > 0 || ! $reminderSent) {
            return [
                'level' => 'risk',
                'icon' => '🟡',
                'label' => 'Риск неявки',
                'reason' => $reschedules > 0
                    ? 'Клиент переносил запись, напомните дополнительно.'
                    : 'Напоминание ещё не отправлено, стоит связаться.',
            ];
        }

        return [
            'level' => 'high',
            'icon' => '🟢',
            'label' => 'Высокая явка',
            'reason' => 'Клиент обычно приходит вовремя, всё под контролем.',
        ];
    }

    private function buildOrderBadges(Order $order, string $indicatorLevel): array
    {
        $badges = [];

        if ($indicatorLevel === 'risk') {
            $badges[] = 'Рекомендовано двойное напоминание';
        }

        if ($indicatorLevel === 'critical') {
            $badges[] = 'Подготовьте дополнительные материалы';
        }

        if ($order->prepaid_amount && $order->prepaid_amount > 0) {
            $badges[] = 'Есть предоплата';
        }

        if ($order->recommended_services && count($order->recommended_services) > 0) {
            $badges[] = 'Есть апсейл от ИИ';
        }

        return $badges;
    }

    private function buildFreeSlots(Collection $orders, array $workHours, Carbon $date): array
    {
        if (! $workHours) {
            return [];
        }

        $occupied = $orders
            ->filter(fn (Order $order) => $order->scheduled_at)
            ->map(fn (Order $order) => $order->scheduled_at->copy()->format('H:i'))
            ->all();

        return collect($workHours)
            ->filter(fn ($slot) => ! in_array($slot, $occupied, true))
            ->map(fn ($slot) => [
                'time' => $slot,
                'date_time' => $date->copy()->setTimeFromTimeString($slot)->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function buildTodayMetrics(Collection $orders, ?Setting $settings, int $userId, Carbon $date): array
    {
        $effectiveOrders = $orders->filter(fn (Order $order) => ! in_array($order->status, ['cancelled', 'no_show'], true));
        $revenue = $effectiveOrders->sum(fn (Order $order) => $order->total_price !== null ? (float) $order->total_price : 0.0);
        $clientsCount = $effectiveOrders->count();

        $workHours = [];
        $weekdayKey = self::ISO_DAY_MAP[$date->isoWeekday()] ?? null;
        if ($weekdayKey) {
            $workHours = Arr::get($settings?->work_hours ?? [], $weekdayKey, []);
        }

        $capacity = is_array($workHours) ? count($workHours) : 0;

        $analytics = $this->loadDailyAnalytics($userId, 14);
        $planRevenue = $analytics->avg('revenue') ?: null;

        if ($planRevenue === null) {
            $planRevenue = $revenue;
        }

        $averageCheck = $clientsCount > 0 ? $revenue / $clientsCount : null;
        $historicalAverageCheck = $analytics->avg(fn ($row) => $row['average_check'] ?? null);

        return [
            'revenue' => [
                'target' => round($planRevenue, 2),
                'actual' => round($revenue, 2),
                'delta' => $planRevenue > 0 ? round((($revenue - $planRevenue) / $planRevenue) * 100, 1) : null,
            ],
            'clients' => [
                'booked' => $clientsCount,
                'capacity' => $capacity,
            ],
            'average_check' => [
                'value' => $averageCheck !== null ? round($averageCheck, 2) : null,
                'baseline' => $historicalAverageCheck !== null ? round($historicalAverageCheck, 2) : null,
            ],
        ];
    }

    private function buildFinanceBlock(int $userId): array
    {
        $analytics = $this->loadDailyAnalytics($userId, 14);
        $weekdays = $analytics
            ->groupBy(fn ($row) => CarbonImmutable::parse($row['date'])->dayOfWeek)
            ->map(fn (Collection $rows) => $rows->avg('margin_per_hour'))
            ->filter()
            ->map(fn ($value, $weekday) => [
                'weekday' => $this->weekdayLabel((int) $weekday),
                'value' => round((float) $value, 2),
            ])
            ->values();

        $bestDay = $weekdays->sortByDesc('value')->first();
        $averageMargin = $weekdays->avg('value');

        $revenueTrend = $analytics
            ->sortBy('date')
            ->map(fn ($row) => [
                'date' => $row['date'],
                'revenue' => round((float) $row['revenue'], 2),
            ])
            ->values();

        $recent = $analytics->filter(fn ($row) => CarbonImmutable::parse($row['date'])->greaterThanOrEqualTo(now()->subDays(6)));
        $previous = $analytics->filter(function ($row) {
            $date = CarbonImmutable::parse($row['date']);
            return $date->betweenIncluded(now()->subDays(13), now()->subDays(7));
        });

        $recentSum = $recent->sum('revenue');
        $previousSum = $previous->sum('revenue');
        $revenueDelta = $previousSum > 0
            ? round((($recentSum - $previousSum) / $previousSum) * 100, 1)
            : null;

        $topServices = $this->resolveTopServices($userId);
        $topClients = $this->resolveTopClients($userId);

        return [
            'margin_per_hour' => [
                'chart' => $weekdays,
                'insight' => $bestDay && $averageMargin
                    ? sprintf('В %s маржа/час на %.0f%% выше средней. Перенесите часть клиентов на этот день?',
                        mb_strtolower($bestDay['weekday']),
                        $averageMargin > 0 ? (($bestDay['value'] - $averageMargin) / $averageMargin) * 100 : 0)
                    : null,
            ],
            'revenue_trend' => [
                'points' => $revenueTrend,
                'comparison' => [
                    'current' => round((float) $recentSum, 2),
                    'previous' => round((float) $previousSum, 2),
                    'delta' => $revenueDelta,
                ],
            ],
            'top_services' => $topServices,
            'top_clients' => $topClients,
        ];
    }

    private function buildAiTips(int $userId, Collection $ordersToday, ?Setting $settings, Carbon $date, array $finance): array
    {
        $tips = [];

        $freeSlotsTomorrow = $this->buildFreeSlots(
            $this->fetchOrdersForDate($userId, $date->copy()->addDay()),
            $this->resolveWorkHoursForDate($settings, $date->copy()->addDay()),
            $date->copy()->addDay()
        );

        if ($freeSlotsTomorrow) {
            $clientForUpsell = Client::where('user_id', $userId)
                ->orderByDesc('last_visit_at')
                ->first();

            $tips[] = [
                'title' => 'Заполните свободные окна',
                'message' => sprintf(
                    'Завтра есть %d свободных слотов. Предложите %s коррекцию или экспресс-услугу.',
                    count($freeSlotsTomorrow),
                    $clientForUpsell?->name ?? 'постоянным клиентам'
                ),
                'action' => 'Отправить предложение',
            ];
        }

        $riskOrder = $ordersToday->first(function (Order $order) {
            return $order->reschedule_count > 0 || ! $order->is_reminder_sent;
        });

        if ($riskOrder) {
            $tips[] = [
                'title' => 'Риск неявки',
                'message' => sprintf(
                    'Клиент %s переносил визит. Напомните ему повторно, чтобы подтвердить запись.',
                    $riskOrder->client?->name ?? 'клиент'
                ),
                'action' => 'Отправить напоминание',
            ];
        }

        $complexOrder = $ordersToday->first(fn (Order $order) => ($order->complexity_level ?? 0) >= 4);
        if ($complexOrder) {
            $tips[] = [
                'title' => 'Подготовьтесь к сложному визиту',
                'message' => sprintf(
                    'Визит %s отмечен как сложный. Проверьте материалы и добавьте запас по времени.',
                    $complexOrder->client?->name ?? 'клиента'
                ),
                'action' => 'Открыть чек-лист',
            ];
        }

        if (! $tips) {
            $tips[] = [
                'title' => 'День под контролем',
                'message' => 'Все клиенты подтвердили записи. Используйте время для подготовки апсейлов и обучения.',
                'action' => null,
            ];
        }

        return $tips;
    }

    private function buildLearningBlock(int $userId): array
    {
        $orders = Order::where('master_id', $userId)
            ->whereBetween('scheduled_at', [now()->subDays(14), now()])
            ->get();

        if ($orders->isEmpty()) {
            return [
                'tip' => 'Добавьте клиентов в расписание, чтобы получать персональные инсайты и подсказки по трендам.',
                'action' => null,
            ];
        }

        $services = $this->groupServicesStats($orders);
        $recent = $services->sortByDesc('recent_revenue')->first();
        $previous = $services->sortByDesc('previous_revenue')->first();

        if ($recent && $recent['recent_revenue'] > 0) {
            $growth = $recent['previous_revenue'] > 0
                ? round((($recent['recent_revenue'] - $recent['previous_revenue']) / $recent['previous_revenue']) * 100, 0)
                : null;

            if ($growth !== null && $growth > 0) {
                return [
                    'tip' => sprintf(
                        'Спрос на «%s» вырос на %d%% за последние 2 недели. Упомяните услугу в соцсетях или рассылке.',
                        $recent['name'],
                        $growth
                    ),
                    'action' => 'Подробнее',
                ];
            }
        }

        if ($previous && $previous['previous_revenue'] > 0 && $previous['recent_revenue'] < $previous['previous_revenue']) {
            $drop = round((($previous['previous_revenue'] - $previous['recent_revenue']) / $previous['previous_revenue']) * 100, 0);

            return [
                'tip' => sprintf(
                    'Спрос на «%s» снизился на %d%%. Подумайте о спецпредложении или апсейле.',
                    $previous['name'],
                    $drop
                ),
                'action' => 'Подробнее',
            ];
        }

        return [
            'tip' => 'Проанализируйте популярные услуги за последние недели и подготовьте апсейл-сценарии.',
            'action' => 'Подробнее',
        ];
    }

    private function resolveTopServices(int $userId): array
    {
        $orders = Order::where('master_id', $userId)
            ->whereBetween('scheduled_at', [now()->subDays(60), now()])
            ->get();

        if ($orders->isEmpty()) {
            return [];
        }

        $services = $this->groupServicesStats($orders)
            ->map(fn ($row) => [
                'service_id' => $row['id'],
                'name' => $row['name'],
                'margin_per_hour' => $row['hours'] > 0 ? round($row['margin'] / $row['hours'], 2) : null,
                'visits' => $row['visits'],
            ])
            ->filter(fn ($row) => $row['margin_per_hour'] !== null)
            ->sortByDesc('margin_per_hour')
            ->take(3)
            ->values()
            ->all();

        return $services;
    }

    private function resolveTopClients(int $userId): array
    {
        $orders = Order::with('client')
            ->where('master_id', $userId)
            ->whereBetween('scheduled_at', [now()->subDays(120), now()])
            ->get();

        if ($orders->isEmpty()) {
            return [];
        }

        $stats = $orders->groupBy('client_id')->map(function (Collection $clientOrders) {
            /** @var Order|null $sample */
            $sample = $clientOrders->first();
            $client = $sample?->client;

            $revenue = $clientOrders
                ->filter(fn (Order $order) => ! in_array($order->status, ['cancelled', 'no_show'], true))
                ->sum(fn (Order $order) => $order->total_price !== null ? (float) $order->total_price : 0.0);

            $visits = $clientOrders->count();
            $noShows = $clientOrders->where('status', 'no_show')->count();

            return [
                'client_id' => $client?->id,
                'name' => $client?->name,
                'ltv' => round($revenue, 2),
                'visits' => $visits,
                'loyalty' => $noShows > 0 ? 'На контроле' : 'Лояльный',
            ];
        });

        return $stats
            ->filter(fn ($row) => $row['name'])
            ->sortByDesc('ltv')
            ->take(5)
            ->values()
            ->all();
    }

    private function groupServicesStats(Collection $orders): Collection
    {
        $serviceLookup = Service::whereIn('id', $orders
            ->flatMap(fn (Order $order) => collect($order->services ?? [])->pluck('id')->filter())
            ->unique()
            ->values()
        )->get()->keyBy('id');

        $now = now();
        $splitPoint = $now->copy()->subDays(7);

        return $orders->flatMap(function (Order $order) use ($serviceLookup) {
            $scheduledAt = $order->scheduled_at;
            $services = collect($order->services ?? []);

            if ($services->isEmpty()) {
                return [];
            }

            return $services->map(function ($service) use ($serviceLookup, $order, $scheduledAt) {
                $serviceId = Arr::get($service, 'id');
                $serviceModel = $serviceId ? $serviceLookup->get($serviceId) : null;
                $price = $this->toFloat(Arr::get($service, 'price')) ?? 0.0;
                $cost = $serviceModel?->cost ?? 0.0;
                $duration = (int) (Arr::get($service, 'duration') ?? $serviceModel?->duration_min ?? 60);

                return [
                    'id' => $serviceId,
                    'name' => Arr::get($service, 'name') ?? $serviceModel?->name,
                    'margin' => max($price - $cost, 0),
                    'revenue' => $price,
                    'hours' => $duration > 0 ? $duration / 60 : 1,
                    'scheduled_at' => $scheduledAt,
                    'order_status' => $order->status,
                ];
            });
        })
            ->groupBy('id')
            ->map(function (Collection $serviceRows) use ($splitPoint) {
                $recent = $serviceRows->filter(fn ($row) => $row['scheduled_at'] && $row['scheduled_at']->greaterThanOrEqualTo($splitPoint));
                $previous = $serviceRows->filter(fn ($row) => $row['scheduled_at'] && $row['scheduled_at']->lessThan($splitPoint));

                return [
                    'id' => $serviceRows->first()['id'],
                    'name' => $serviceRows->first()['name'],
                    'margin' => $serviceRows->sum('margin'),
                    'revenue' => $serviceRows->sum('revenue'),
                    'hours' => $serviceRows->sum('hours'),
                    'visits' => $serviceRows->count(),
                    'recent_revenue' => $recent->sum('revenue'),
                    'previous_revenue' => $previous->sum('revenue'),
                ];
            });
    }

    private function loadDailyAnalytics(int $userId, int $days): Collection
    {
        $end = CarbonImmutable::today();
        $start = $end->subDays($days - 1);

        $records = AnalyticsDaily::where('user_id', $userId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get()
            ->keyBy(fn (AnalyticsDaily $row) => $row->date->toDateString());

        $orders = Order::where('master_id', $userId)
            ->whereBetween('scheduled_at', [$start->startOfDay(), $end->endOfDay()])
            ->get();

        $serviceLookup = Service::where('user_id', $userId)->get()->keyBy('id');

        $daysRange = Collection::times($days, fn ($i) => $start->addDays($i));

        return $daysRange->map(function (CarbonImmutable $day) use ($records, $orders, $serviceLookup) {
            $key = $day->toDateString();
            $record = $records->get($key);

            if ($record) {
                return [
                    'date' => $key,
                    'revenue' => (float) $record->revenue,
                    'hours_booked' => (float) $record->hours_booked,
                    'margin_per_hour' => (float) $record->margin_per_hour,
                    'average_check' => $record->hours_booked > 0 ? (float) $record->revenue / (float) $record->hours_booked : null,
                ];
            }

            $dayOrders = $orders->filter(fn (Order $order) => $order->scheduled_at && $order->scheduled_at->isSameDay($day));
            $revenue = $dayOrders
                ->filter(fn (Order $order) => ! in_array($order->status, ['cancelled', 'no_show'], true))
                ->sum(fn (Order $order) => $order->total_price !== null ? (float) $order->total_price : 0.0);

            $hours = $dayOrders->sum(function (Order $order) {
                if ($order->duration) {
                    return $order->duration / 60;
                }

                $services = collect($order->services ?? []);
                $total = $services->sum(fn ($service) => (int) Arr::get($service, 'duration', 0));

                return $total > 0 ? $total / 60 : 1;
            });

            $margin = $dayOrders->sum(function (Order $order) use ($serviceLookup) {
                $services = collect($order->services ?? []);

                return $services->sum(function ($service) use ($serviceLookup) {
                    $serviceId = Arr::get($service, 'id');
                    $price = $this->toFloat(Arr::get($service, 'price')) ?? 0.0;
                    $model = $serviceId ? $serviceLookup->get($serviceId) : null;
                    $cost = $model?->cost ?? 0.0;

                    return max($price - $cost, 0);
                });
            });

            return [
                'date' => $key,
                'revenue' => $revenue,
                'hours_booked' => $hours,
                'margin_per_hour' => $hours > 0 ? $margin / $hours : 0.0,
                'average_check' => $dayOrders->count() > 0 ? $revenue / $dayOrders->count() : null,
            ];
        })->values();
    }

    private function fetchOrdersForDate(int $userId, Carbon $date): Collection
    {
        return Order::where('master_id', $userId)
            ->whereDate('scheduled_at', $date)
            ->get();
    }

    private function resolveWorkHoursForDate(?Setting $settings, Carbon $date): array
    {
        $weekdayKey = self::ISO_DAY_MAP[$date->isoWeekday()] ?? null;

        if (! $weekdayKey) {
            return [];
        }

        return Arr::get($settings?->work_hours ?? [], $weekdayKey, []);
    }

    private function weekdayLabel(int $weekday): string
    {
        $labels = [
            0 => 'Воскресенье',
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
        ];

        return $labels[$weekday] ?? 'День';
    }

    private function toFloat($value): ?float
    {
        if ($value === null) {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function currentUserId(): int
    {
        $userId = Auth::guard('sanctum')->id();

        if (! $userId) {
            abort(403);
        }

        return $userId;
    }
}
