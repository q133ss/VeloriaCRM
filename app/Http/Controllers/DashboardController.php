<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use App\Services\ClientAttendanceService;
use App\Services\ClientIdentityService;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * The dashboard is a day sheet: who is coming today, who is slipping away, and
 * where the gaps are.
 *
 * It reads `orders`, the same table the calendar, the bookings list and analytics
 * read. It used to read `appointments`, which only the client self-booking portal
 * ever writes, so a master who booked her own clients saw an empty screen.
 * Revenue follows the analytics definition (order total, revenue statuses) rather
 * than the `payments` table, which only holds online YooKassa charges and is
 * empty for anyone taking cash.
 */
class DashboardController extends Controller
{
    /** Orders that count as money earned or promised. Matches AnalyticsController. */
    private const REVENUE_STATUSES = ['completed', 'in_progress', 'confirmed'];

    /** Orders that still occupy a slot in the day. */
    private const ACTIVE_STATUSES = ['new', 'confirmed', 'in_progress', 'completed'];

    /** How much past a client's own rhythm counts as overdue. */
    private const OVERDUE_FACTOR = 1.3;

    /** Days after a single visit before a client who never came back is flagged. */
    private const SINGLE_VISIT_OVERDUE_DAYS = 60;

    public function __construct(
        private readonly ScheduleService $scheduleService,
        private readonly ClientIdentityService $clientIdentity,
    ) {
    }

    public function show(Request $request): View
    {
        $user = $request->user() ?? Auth::guard('sanctum')->user();

        abort_unless($user, 403);

        Carbon::setLocale(app()->getLocale());

        $timezone = $user->timezone ?? config('app.timezone');
        $now = Carbon::now($timezone);
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();

        $setting = Setting::where('user_id', $user->id)->first();
        $clientCount = Client::where('user_id', $user->id)->count();
        $serviceCount = Service::where('user_id', $user->id)->count();
        $scheduleConfigured = $this->hasConfiguredSchedule($setting);

        // Eight weeks back covers the occupancy trend; a week forward covers the
        // free slots. Visit rhythms need far more history and are queried apart.
        $windowStart = $todayStart->copy()->subWeeks(8)->startOfWeek(Carbon::MONDAY);
        $windowEnd = $todayStart->copy()->addDays(7)->endOfDay();

        $orders = Order::with('client')
            ->where('master_id', $user->id)
            ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
            ->orderBy('scheduled_at')
            ->get();

        $cards = $this->clientIdentity->cardsForOrders($user->id, $orders);

        $todayOrders = $orders
            ->filter(fn (Order $order) => $this->isWithinDay($order->scheduled_at, $todayStart, $todayEnd))
            ->filter(fn (Order $order) => in_array($order->status, self::ACTIVE_STATUSES, true))
            ->values();

        $noShowCounts = $this->noShowCountsByClient($user->id);

        $schedule = $todayOrders->map(function (Order $order) use ($timezone, $cards, $noShowCounts) {
            $scheduledAt = $order->scheduled_at?->copy()->timezone($timezone);
            $card = $order->client_id ? $cards->get($order->client_id) : null;
            $services = collect($order->services ?? [])->pluck('name')->filter()->values();

            return [
                'id' => $order->id,
                'time' => $scheduledAt?->format('H:i') ?? '—',
                'client' => $card?->name ?: ($order->client?->name ?: '—'),
                'services' => $services->all(),
                'price' => (float) $order->total_price,
                'price_formatted' => $order->total_price > 0 ? $this->formatCurrency((float) $order->total_price) : null,
                'client_url' => $card ? route('clients.show', $card->id) : null,
                'note' => $card?->notes ?: $order->note,
                'indicator' => $this->buildIndicator($order, (int) ($noShowCounts[$order->client_id] ?? 0)),
            ];
        })->values();

        $expectedToday = $todayOrders
            ->filter(fn (Order $order) => in_array($order->status, self::REVENUE_STATUSES, true))
            ->sum(fn (Order $order) => (float) $order->total_price);

        return view('dashboard', [
            'today' => [
                'date_label' => Str::ucfirst($todayStart->locale(app()->getLocale())->isoFormat('D MMMM, dddd')),
                'count' => $todayOrders->count(),
                'expected_revenue' => $expectedToday,
                'expected_revenue_formatted' => $this->formatCurrency($expectedToday),
            ],
            'schedule' => $schedule,
            'dueClients' => $this->buildDueClients($user->id, $now, $timezone),
            'freeSlots' => $this->buildFreeSlots($setting, $orders, $todayStart, $timezone),
            'occupancy' => $this->buildOccupancy($setting, $orders, $todayStart, $timezone),
            'week' => $this->buildWeekSummary($orders, $todayStart, $todayEnd),
            'onboarding' => [
                'user_id' => $user->id,
                'schedule_configured' => $scheduleConfigured,
                'service_count' => $serviceCount,
                'client_count' => $clientCount,
                'completed_steps' => collect([
                    $scheduleConfigured,
                    $serviceCount > 0,
                    $clientCount > 0,
                ])->filter()->count(),
                'steps' => [
                    [
                        'key' => 'schedule',
                        'title' => 'График работы',
                        'description' => 'Укажите дни и часы, когда вы принимаете клиентов.',
                        'href' => route('settings') . '#settings-work',
                        'cta' => 'Заполнить график',
                        'completed' => $scheduleConfigured,
                    ],
                    [
                        'key' => 'services',
                        'title' => 'Услуги',
                        'description' => 'Добавьте процедуры и цены, чтобы можно было сразу оформлять запись.',
                        'href' => route('services.index'),
                        'cta' => 'Добавить услугу',
                        'completed' => $serviceCount > 0,
                    ],
                    [
                        'key' => 'clients',
                        'title' => 'Клиенты',
                        'description' => 'Сохраните первого клиента, чтобы не держать всё в заметках.',
                        'href' => route('clients.create'),
                        'cta' => 'Добавить клиента',
                        'completed' => $clientCount > 0,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Clients who have fallen out of their own rhythm.
     *
     * Every client has a personal cadence: lashes every three weeks, colour every
     * eight. A single fixed threshold, which is what analytics uses today, is
     * late for one and early for the other. The interval here is measured per
     * client from her own visits, so the list is short and every name on it is
     * genuinely overdue.
     */
    private function buildDueClients(int $masterId, CarbonInterface $now, string $timezone): Collection
    {
        $history = Order::query()
            ->where('master_id', $masterId)
            ->whereNotNull('client_id')
            ->whereIn('status', ['completed', 'in_progress', 'confirmed'])
            ->where('scheduled_at', '>=', $now->copy()->subDays(365))
            ->where('scheduled_at', '<=', $now)
            ->orderBy('scheduled_at')
            ->get(['id', 'client_id', 'scheduled_at', 'total_price']);

        if ($history->isEmpty()) {
            return collect();
        }

        // Anyone already on the books does not need chasing.
        $booked = Order::query()
            ->where('master_id', $masterId)
            ->whereNotNull('client_id')
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where('scheduled_at', '>', $now)
            ->pluck('client_id')
            ->unique()
            ->flip();

        $cards = Client::where('user_id', $masterId)
            ->whereIn('client_user_id', $history->pluck('client_id')->unique()->all())
            ->get()
            ->keyBy('client_user_id');

        return $history
            ->groupBy('client_id')
            ->reject(fn (Collection $visits, $clientId) => $booked->has($clientId))
            ->map(function (Collection $visits, $clientId) use ($now, $timezone, $cards) {
                $card = $cards->get($clientId);

                // Without a card there is no name to show and no way to open her.
                if (! $card) {
                    return null;
                }

                $dates = $visits->pluck('scheduled_at')->filter()->values();
                $lastVisit = $dates->last()?->copy()->timezone($timezone);

                if (! $lastVisit) {
                    return null;
                }

                $daysSince = (int) $lastVisit->copy()->startOfDay()->diffInDays($now->copy()->startOfDay());
                $interval = $this->averageInterval($dates);

                if ($interval === null) {
                    // One visit only. She came once and did not come back.
                    if ($daysSince < self::SINGLE_VISIT_OVERDUE_DAYS) {
                        return null;
                    }
                } elseif ($daysSince <= $interval * self::OVERDUE_FACTOR) {
                    return null;
                }

                $averageTicket = $visits->avg(fn (Order $order) => (float) $order->total_price) ?: 0.0;
                $overdueBy = $interval === null ? $daysSince : $daysSince - (int) round($interval);

                return [
                    'client_id' => $card->id,
                    'name' => $card->name,
                    'url' => route('clients.show', $card->id),
                    'phone' => $card->phone,
                    'visits' => $visits->count(),
                    'interval_days' => $interval === null ? null : (int) round($interval),
                    'days_since' => $daysSince,
                    'overdue_days' => max(0, $overdueBy),
                    'average_ticket' => $averageTicket,
                    // Rank by money at stake, not by lateness: losing a client with
                    // a big cheque matters more than one who is a week later.
                    'weight' => $averageTicket * max(1, $overdueBy),
                ];
            })
            ->filter()
            ->sortByDesc('weight')
            ->take(4)
            ->values();
    }

    /**
     * Mean gap between consecutive visits, or null when there is only one visit
     * and no gap to measure.
     *
     * @param  Collection<int, CarbonInterface>  $dates
     */
    private function averageInterval(Collection $dates): ?float
    {
        if ($dates->count() < 2) {
            return null;
        }

        $gaps = [];

        for ($i = 1; $i < $dates->count(); $i++) {
            $gaps[] = $dates[$i - 1]->diffInDays($dates[$i]);
        }

        $gaps = array_filter($gaps, fn (int $days) => $days > 0);

        return $gaps === [] ? null : array_sum($gaps) / count($gaps);
    }

    /**
     * Open slots over the next few days. An empty slot tomorrow is money that
     * quietly does not arrive, and it is only useful next to the list of clients
     * who are due, which is what the view puts beside it.
     */
    private function buildFreeSlots(?Setting $setting, Collection $orders, CarbonInterface $todayStart, string $timezone): array
    {
        if (! $setting) {
            return [];
        }

        $days = [];

        for ($offset = 1; $offset <= 3; $offset++) {
            $day = $todayStart->copy()->addDays($offset);
            $slots = collect($this->scheduleService->resolveSlotsForDate($setting, $day, $timezone));

            if ($slots->isEmpty()) {
                continue;
            }

            $taken = $orders
                ->filter(fn (Order $order) => $this->isWithinDay($order->scheduled_at, $day->copy()->startOfDay(), $day->copy()->endOfDay()))
                ->filter(fn (Order $order) => in_array($order->status, self::ACTIVE_STATUSES, true))
                ->map(fn (Order $order) => $order->scheduled_at?->copy()->timezone($timezone)->format('H:i'))
                ->filter();

            $free = $slots->diff($taken)->values();

            if ($free->isEmpty()) {
                continue;
            }

            $days[] = [
                'date' => $day->toDateString(),
                'label' => $day->locale(app()->getLocale())->isoFormat($offset === 1 ? '[завтра]' : 'dddd'),
                'free' => $free->take(3)->all(),
                'free_count' => $free->count(),
                'total' => $slots->count(),
            ];
        }

        return $days;
    }

    /**
     * Share of the working day that is booked, week by week.
     *
     * Occupancy is deliberately not revenue. A master feels revenue in her purse
     * anyway; what she cannot see is that she is drifting towards a thin month.
     * Occupancy sags two or three weeks before the money does.
     */
    private function buildOccupancy(?Setting $setting, Collection $orders, CarbonInterface $todayStart, string $timezone): array
    {
        if (! $setting) {
            return ['weeks' => [], 'current' => null, 'previous' => null];
        }

        $weeks = [];
        $firstWeekStart = $todayStart->copy()->subWeeks(7)->startOfWeek(Carbon::MONDAY);

        for ($index = 0; $index < 8; $index++) {
            $weekStart = $firstWeekStart->copy()->addWeeks($index);
            $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

            $capacity = 0;

            for ($day = 0; $day < 7; $day++) {
                $date = $weekStart->copy()->addDays($day);

                // The current week is only counted up to today, otherwise days
                // that have not happened yet drag the last point down.
                if ($date->greaterThan($todayStart)) {
                    break;
                }

                $capacity += count($this->scheduleService->resolveSlotsForDate($setting, $date, $timezone));
            }

            $booked = $orders
                ->filter(fn (Order $order) => $this->isWithinDay($order->scheduled_at, $weekStart->copy()->startOfDay(), $weekEnd))
                ->filter(fn (Order $order) => $order->scheduled_at && $order->scheduled_at->lessThanOrEqualTo($todayStart->copy()->endOfDay()))
                ->filter(fn (Order $order) => in_array($order->status, self::ACTIVE_STATUSES, true))
                ->count();

            $weeks[] = [
                'start' => $weekStart->toDateString(),
                'label' => $weekStart->locale(app()->getLocale())->isoFormat('D MMM'),
                'booked' => $booked,
                'capacity' => $capacity,
                'share' => $capacity > 0 ? min(100, (int) round(($booked / $capacity) * 100)) : null,
            ];
        }

        $measured = collect($weeks)->filter(fn (array $week) => $week['share'] !== null)->values();

        return [
            'weeks' => $weeks,
            'has_data' => $measured->count() >= 2,
            'current' => $measured->last()['share'] ?? null,
            'previous' => $measured->count() >= 2 ? $measured[$measured->count() - 2]['share'] : null,
        ];
    }

    /**
     * Money and people over the last seven days, on the same definition analytics
     * uses. A week rather than a day, because one quiet day says nothing.
     */
    private function buildWeekSummary(Collection $orders, CarbonInterface $todayStart, CarbonInterface $todayEnd): array
    {
        $weekStart = $todayStart->copy()->subDays(6);

        $weekOrders = $orders
            ->filter(fn (Order $order) => $this->isWithinDay($order->scheduled_at, $weekStart, $todayEnd))
            ->filter(fn (Order $order) => in_array($order->status, self::REVENUE_STATUSES, true));

        $revenue = (float) $weekOrders->sum(fn (Order $order) => (float) $order->total_price);
        $visits = $weekOrders->count();
        $clients = $weekOrders->pluck('client_id')->filter()->unique()->count();

        return [
            'has_data' => $revenue > 0 || $visits > 0,
            'revenue' => $revenue,
            'revenue_formatted' => $this->formatCurrency($revenue),
            'clients' => $clients,
            'visits' => $visits,
            'average_ticket_formatted' => $this->formatCurrency($visits > 0 ? $revenue / $visits : 0),
        ];
    }

    /**
     * How confident the master can be that this person turns up. Built from what
     * the booking actually records: whether it is confirmed, whether it has been
     * moved around, and whether she has failed to show before.
     */
    private function buildIndicator(Order $order, int $pastNoShows): array
    {
        if ($pastNoShows >= 2) {
            return ['type' => 'red', 'label' => __('dashboard.indicators.complex_visit')];
        }

        if ($pastNoShows === 1 || (int) ($order->reschedule_count ?? 0) >= 2) {
            return ['type' => 'yellow', 'label' => __('dashboard.indicators.no_show_risk')];
        }

        if ($order->status === 'new') {
            return ['type' => 'yellow', 'label' => __('dashboard.indicators.unconfirmed')];
        }

        return ['type' => 'green', 'label' => __('dashboard.indicators.high_attendance')];
    }

    /**
     * @return array<int, int>
     */
    /**
     * Only actual no-shows. A cancellation is a client who warned you, and
     * counting it here marked considerate people as unreliable.
     *
     * @return array<int, int>
     */
    private function noShowCountsByClient(int $masterId): array
    {
        return app(ClientAttendanceService::class)->noShowCountsFor(
            $masterId,
            Order::query()
                ->where('master_id', $masterId)
                ->whereNotNull('client_id')
                ->distinct()
                ->pluck('client_id')
                ->map(fn ($id) => (int) $id)
                ->all(),
        );
    }

    private function hasConfiguredSchedule(?Setting $setting): bool
    {
        if (! $setting) {
            return false;
        }

        $rules = $this->scheduleService->buildSettingsPayload($setting)['schedule_rules'] ?? [];

        $hasWeeklySlots = collect($rules['weekly'] ?? [])->contains(
            fn ($dayRules) => ! empty($dayRules['enabled']) && ! empty($dayRules['slots'])
        );

        return $hasWeeklySlots
            || ! empty($rules['cycle']['slots'] ?? [])
            || ! empty($rules['monthly']['dates'] ?? []);
    }

    private function formatCurrency(float $value): string
    {
        return __('dashboard.currency', [
            'amount' => number_format(max(0, $value), 0, '.', ' '),
        ]);
    }

    private function isWithinDay(?CarbonInterface $date, CarbonInterface $start, CarbonInterface $end): bool
    {
        return $date !== null && $date->betweenIncluded($start, $end);
    }
}
