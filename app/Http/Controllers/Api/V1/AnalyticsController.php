<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnalyticsRequest;
use App\Models\Client;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\Ai\AiGateway;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AnalyticsController extends Controller
{
    private const ORDER_REVENUE_STATUSES = ['completed', 'in_progress', 'confirmed'];
    private const PAYMENT_REVENUE_STATUSES = ['succeeded'];

    /**
     * A booking whose time has passed and which was neither cancelled nor
     * missed: the client came. The same rule decides «была / не была» on the
     * client list and in the outreach message, and the three must not disagree.
     */
    private const VISIT_STATUSES = ['completed', 'in_progress', 'confirmed'];

    /** Away this long, with nothing booked, and the client has slipped off. */
    private const SLEEPING_AFTER_DAYS = 60;

    public function __construct(
        private readonly AiGateway $ai,
    ) {
    }

    public function overview(AnalyticsRequest $request): JsonResponse
    {
        $userId = $this->currentUserId();
        $hasEliteAccess = $this->userHasEliteAccess();
        $activePlanSlug = $this->activePlanSlug();
        $validated = $request->validated();
        $requestedGrouping = $validated['grouping'] ?? null;
        $requestedSections = collect((array) $request->query('sections', []))
            ->filter(fn (mixed $section) => is_string($section) && $section !== '')
            ->values();
        $includeChurn = $requestedSections->contains('churn');
        $includeLtv = $requestedSections->contains('ltv');
        $includeTopClients = $requestedSections->contains('top_clients');

        $locale = app()->getLocale();
        $timezone = $request->user()?->timezone ?? config('app.timezone');

        $to = $this->resolveDate($validated['to'] ?? null, $timezone)?->endOfDay() ?? Carbon::now($timezone)->endOfDay();
        $from = $this->resolveDate($validated['from'] ?? null, $timezone)?->startOfDay() ?? $to->copy()->subDays(29)->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $periodDays = max(1, (int) $from->diffInDays($to) + 1);

        // Nobody should have to choose «по дням / по неделям» to read a chart:
        // a month of days is readable, a year of days is a hairbrush.
        $grouping = in_array($requestedGrouping, ['day', 'week', 'month'], true)
            ? $requestedGrouping
            : match (true) {
                $periodDays <= 45 => 'day',
                $periodDays <= 200 => 'week',
                default => 'month',
            };

        $compareTo = $this->resolveDate($validated['compare_to'] ?? null, $timezone)?->endOfDay();
        $compareFrom = $this->resolveDate($validated['compare_from'] ?? null, $timezone)?->startOfDay();

        if (! $compareFrom || ! $compareTo) {
            $compareTo = $from->copy()->subDay()->endOfDay();
            $compareFrom = $compareTo->copy()->subDays($periodDays - 1)->startOfDay();
        }

        [$orders, $payments, $clients, $services] = $this->loadData($userId);

        $revenueTransactions = $this->buildRevenueTransactions($orders, $payments);
        $currentRevenueTransactions = $revenueTransactions->filter(fn (array $tx) => $this->isWithinPeriod($tx['date'] ?? null, $from, $to) && $tx['is_revenue']);
        $previousRevenueTransactions = $revenueTransactions->filter(fn (array $tx) => $this->isWithinPeriod($tx['date'] ?? null, $compareFrom, $compareTo) && $tx['is_revenue']);

        // Bookings live in `orders`. They used to be counted from `appointments`,
        // a table only the client portal ever writes to, so for a master who
        // books everyone herself this page reported nought visits, nought
        // retention and an empty funnel next to a full month of takings.
        $currentBookings = $orders->filter(fn (Order $order) => $this->isWithinPeriod($order->scheduled_at, $from, $to));
        $currentVisits = $currentBookings->filter(fn (Order $order) => in_array($order->status, self::VISIT_STATUSES, true));
        $previousVisits = $orders
            ->filter(fn (Order $order) => $this->isWithinPeriod($order->scheduled_at, $compareFrom, $compareTo))
            ->filter(fn (Order $order) => in_array($order->status, self::VISIT_STATUSES, true));
        $pastVisits = $orders->filter(
            fn (Order $order) => in_array($order->status, self::VISIT_STATUSES, true)
                && $order->scheduled_at
                && $order->scheduled_at->lessThan($from),
        );

        $currentClients = $clients->filter(fn (Client $client) => $this->isWithinPeriod($client->created_at, $from, $to));

        $currentRevenue = $currentRevenueTransactions->sum('amount');
        $previousRevenue = $previousRevenueTransactions->sum('amount');
        $revenueDelta = $this->percentChange($currentRevenue, $previousRevenue);

        $servicesRevenue = $currentRevenueTransactions->sum('service_amount');
        $retailRevenue = $currentRevenueTransactions->sum('retail_amount');

        $avgTicketCurrent = $currentRevenueTransactions->isEmpty()
            ? 0.0
            : $currentRevenue / $currentRevenueTransactions->count();
        $avgTicketPrevious = $previousRevenueTransactions->isEmpty()
            ? 0.0
            : $previousRevenue / $previousRevenueTransactions->count();
        $avgTicketDelta = $this->percentChange($avgTicketCurrent, $avgTicketPrevious);

        $transactionsCurrent = $currentVisits->count();
        $transactionsPrevious = $previousVisits->count();
        $transactionsDelta = $this->percentChange($transactionsCurrent, $transactionsPrevious);

        $returnedBefore = $pastVisits->pluck('client_id')->filter()->unique()->flip();
        $servedClientsCurrent = $currentVisits->pluck('client_id')->filter()->unique();
        $returningClients = $servedClientsCurrent->filter(fn ($clientId) => $returnedBefore->has($clientId))->count();
        $retentionRate = $servedClientsCurrent->count() > 0
            ? round(($returningClients / $servedClientsCurrent->count()) * 100, 1)
            : 0.0;

        // One count of the client base, used by the card and by the breakdown.
        // The two used to disagree in plain sight: «Постоянные клиенты: 0» on
        // one card and «Постоянные 6» on the next.
        $segments = $this->buildClientSegments($clients, $orders, $from, $to);
        $loyalClients = $segments['distribution']['regular']['count'] ?? 0;
        $riskClients = $this->buildRiskClients($clients, $to);
        $churnRate = $clients->isEmpty()
            ? 0.0
            : round(($riskClients->count() / max(1, $clients->count())) * 100, 1);

        $ltv = $this->calculateLtv($revenueTransactions, $previousRevenueTransactions);
        $outcomes = $this->buildBookingOutcomes($currentBookings, $returningClients);
        $topClients = $this->resolveTopClients($currentRevenueTransactions);

        $serviceShare = $this->buildServiceShare($currentRevenueTransactions);
        $peakHours = $hasEliteAccess
            ? $this->buildPeakHoursInsight($currentRevenueTransactions, $locale, $timezone)
            : $this->lockedSmartInsightsPayload();
        $revenueTrend = $this->buildRevenueTrend(
            $currentRevenueTransactions,
            $previousRevenueTransactions,
            $from,
            $to,
            $compareFrom,
            $compareTo,
            $grouping,
            $locale
        );

        $financialInsights = $hasEliteAccess
            ? $this->buildFinancialInsights($serviceShare, $currentRevenue, $servicesRevenue, $avgTicketDelta)
            : [];
        $clientInsights = $hasEliteAccess
            ? $this->buildClientInsights($segments, $retentionRate, $riskClients, $ltv)
            : [];
        $persona = $hasEliteAccess
            ? $this->buildClientPersona($clients, $services, $revenueTransactions)
            : [];

        $aiInsights = $hasEliteAccess
            ? Cache::remember($this->insightsCacheKey($userId, $from, $to, $compareFrom, $compareTo), now()->addHours(6), function () use (
                $currentRevenue,
                $revenueDelta,
                $retentionRate,
                $avgTicketCurrent,
                $segments,
                $riskClients,
                $serviceShare,
                $ltv,
                $transactionsCurrent,
                $outcomes,
                $persona
            ) {
                return $this->generateAiInsights([
                    'revenue' => $currentRevenue,
                    'revenue_delta' => $revenueDelta,
                    'retention_rate' => $retentionRate,
                    'average_ticket' => $avgTicketCurrent,
                    'segments' => $segments,
                    'risk_clients' => $riskClients->take(20)->values()->toArray(),
                    'service_share' => $serviceShare,
                    'ltv' => $ltv,
                    'transactions' => $transactionsCurrent,
                    'funnel' => $outcomes,
                    'persona' => $persona,
                ]);
            })
            : $this->lockedSmartInsightsPayload();

        $clientsPayload = [
            'outcomes' => $outcomes,
            'segments' => $segments,
            'insights' => $clientInsights,
            'persona' => $persona,
        ];

        if ($includeChurn) {
            $clientsPayload['churn'] = [
                'rate' => $churnRate,
                'risk_clients' => $riskClients->values()->all(),
            ];
        }

        if ($includeLtv) {
            $clientsPayload['ltv'] = $ltv;
        }

        $dataPayload = [
            'summary' => [
                'revenue' => $this->formatMetric($currentRevenue, $previousRevenue, $revenueDelta),
                'services_revenue' => $this->formatMetric($servicesRevenue, null, null),
                'retail_revenue' => $this->formatMetric($retailRevenue, null, null),
                'average_ticket' => $this->formatMetric($avgTicketCurrent, $avgTicketPrevious, $avgTicketDelta),
                'clients' => [
                    'new' => $currentClients->count(),
                    'served' => $servedClientsCurrent->count(),
                    'returning' => $returningClients,
                    'loyal' => $loyalClients,
                ],
                'transactions' => $this->formatMetric($transactionsCurrent, $transactionsPrevious, $transactionsDelta),
                'retention_rate' => $this->formatMetric($retentionRate, null, null),
                'top_client' => $topClients[0] ?? null,
            ],
            'financial' => [
                'revenue_trend' => $revenueTrend,
                'service_share' => $serviceShare,
                'peak_hours' => $peakHours,
                'insights' => $financialInsights,
            ],
            'clients' => $clientsPayload,
            'ai' => $aiInsights,
        ];

        if ($includeTopClients) {
            $dataPayload['top_clients'] = $topClients;
        }

        return response()->json([
            'data' => [
                ...$dataPayload,
            ],
            'meta' => [
                'period' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                    'compare_from' => $compareFrom->toDateString(),
                    'compare_to' => $compareTo->toDateString(),
                    'grouping' => $grouping,
                ],
                'filters' => [
                    'groupings' => [
                        ['value' => 'day', 'label' => trans('analytics.grouping_options.day')],
                        ['value' => 'week', 'label' => trans('analytics.grouping_options.week')],
                        ['value' => 'month', 'label' => trans('analytics.grouping_options.month')],
                    ],
                    'presets' => $this->periodPresets($timezone, $from, $to),
                ],
                'exports' => [
                    'csv' => route('api.analytics.export', ['from' => $from->toDateString(), 'to' => $to->toDateString()]),
                ],
                'included_sections' => $requestedSections->all(),
                'access' => [
                    'smart_insights' => [
                        'available' => $hasEliteAccess,
                        'current_plan' => $activePlanSlug,
                        'required_plan' => 'elite',
                        'upgrade_url' => url('/subscription'),
                        'title' => trans('analytics.smart_lock.title'),
                        'description' => trans('analytics.smart_lock.description'),
                        'cta' => trans('analytics.smart_lock.cta'),
                        'badge' => trans('analytics.smart_lock.badge'),
                    ],
                ],
            ],
        ]);
    }

    protected function currentUserId(): int
    {
        $userId = Auth::guard('sanctum')->id();

        if (! $userId) {
            abort(403);
        }

        return (int) $userId;
    }

    /**
     * @return array{0: Collection<int, Order>, 1: Collection<int, Payment>, 2: Collection<int, Client>, 3: Collection<int, Service>}
     */
    /**
     * Ready-made ranges, so the usual question — «а как за месяц?» — is one
     * press rather than two date pickers and an Apply button.
     *
     * @return array<int, array{key: string, label: string, from: string, to: string, active: bool}>
     */
    protected function periodPresets(string $timezone, Carbon $from, Carbon $to): array
    {
        $today = Carbon::now($timezone)->endOfDay();

        $ranges = [
            'week' => 6,
            'month' => 29,
            'quarter' => 89,
            'year' => 364,
        ];

        $presets = [];

        foreach ($ranges as $key => $days) {
            $start = $today->copy()->subDays($days)->startOfDay();

            $presets[] = [
                'key' => $key,
                'label' => trans('analytics.filters.presets.' . $key),
                'from' => $start->toDateString(),
                'to' => $today->toDateString(),
                'active' => $from->toDateString() === $start->toDateString()
                    && $to->toDateString() === $today->toDateString(),
            ];
        }

        return $presets;
    }

    /**
     * Every booking of the period as a spreadsheet.
     *
     * The header used to carry a permanently disabled «Экспорт в Excel» button
     * whose url was hard-coded null. A file the master can hand to an
     * accountant is more use than a chart she cannot take with her, and a
     * semicolon-separated CSV with a byte order mark is what Excel opens
     * without an import wizard.
     */
    public function export(AnalyticsRequest $request): StreamedResponse
    {
        $userId = $this->currentUserId();
        $timezone = $request->user()?->timezone ?? config('app.timezone');
        $validated = $request->validated();

        $to = $this->resolveDate($validated['to'] ?? null, $timezone)?->endOfDay() ?? Carbon::now($timezone)->endOfDay();
        $from = $this->resolveDate($validated['from'] ?? null, $timezone)?->startOfDay() ?? $to->copy()->subDays(29)->startOfDay();

        $orders = Order::query()
            ->where('master_id', $userId)
            ->whereBetween('scheduled_at', [$from, $to])
            ->orderBy('scheduled_at')
            ->get();

        $cards = Client::where('user_id', $userId)->get()->keyBy('client_user_id');
        $statuses = trans('analytics.export.statuses');
        $filename = 'veloria-' . $from->toDateString() . '_' . $to->toDateString() . '.csv';

        return response()->streamDownload(function () use ($orders, $cards, $statuses, $timezone) {
            $handle = fopen('php://output', 'w');

            // Excel reads a CSV as the local codepage unless the file says
            // otherwise, and Cyrillic without this mark arrives as mojibake.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                trans('analytics.export.date'),
                trans('analytics.export.time'),
                trans('analytics.export.client'),
                trans('analytics.export.services'),
                trans('analytics.export.status'),
                trans('analytics.export.amount'),
            ], ';');

            foreach ($orders as $order) {
                $at = $order->scheduled_at?->copy()->timezone($timezone);

                fputcsv($handle, [
                    $at?->format('d.m.Y') ?? '',
                    $at?->format('H:i') ?? '',
                    $cards->get($order->client_id)?->name ?? '',
                    collect($order->services ?? [])->pluck('name')->filter()->implode(', '),
                    $statuses[$order->status] ?? $order->status,
                    number_format((float) $order->total_price, 2, ',', ''),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function loadData(int $userId): array
    {
        $orders = Order::where('master_id', $userId)->get();
        $payments = Payment::where('user_id', $userId)->get();
        $clients = Client::where('user_id', $userId)->get();
        $services = Service::where('user_id', $userId)->get()->keyBy('id');

        return [$orders, $payments, $clients, $services];
    }

    protected function resolveDate(mixed $value, string $timezone): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value, $timezone);
        } catch (Throwable) {
            return null;
        }
    }

    protected function isWithinPeriod(mixed $date, Carbon $from, Carbon $to): bool
    {
        if (! $date) {
            return false;
        }

        if (! $date instanceof Carbon) {
            try {
                $date = Carbon::parse($date);
            } catch (Throwable) {
                return false;
            }
        }

        return $date->betweenIncluded($from, $to);
    }

    protected function percentChange(float|int|null $current, float|int|null $previous): ?float
    {
        if ($previous === null) {
            return null;
        }

        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous == 0.0) {
            if ($current == 0.0) {
                return 0.0;
            }

            return 100.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    protected function userHasEliteAccess(): bool
    {
        $user = Auth::guard('sanctum')->user();

        if (! $user) {
            return false;
        }

        return $user->hasEliteAccess();
    }

    protected function activePlanSlug(): string
    {
        $user = Auth::guard('sanctum')->user();

        if (! $user) {
            return 'lite';
        }

        return $user->activePlanSlug();
    }

    protected function lockedSmartInsightsPayload(): array
    {
        return [
            'status' => 'locked',
        ];
    }

    protected function buildRevenueTransactions(Collection $orders, Collection $payments): Collection
    {
        $orderTransactions = $orders->map(function (Order $order) {
            $services = collect($order->services ?? [])->map(function ($service) {
                $name = Arr::get($service, 'name');
                $price = (float) Arr::get($service, 'price', 0);

                return [
                    'name' => $name,
                    'price' => $price,
                ];
            });

            return [
                'id' => 'order:' . $order->id,
                'source' => 'order',
                'client_key' => $order->client_id ? 'user:' . $order->client_id : null,
                'client_id' => $order->client_id,
                'client_type' => $order->client_id ? 'user' : null,
                'amount' => (float) $order->total_price,
                'service_amount' => $services->sum('price'),
                'retail_amount' => 0.0,
                'status' => $order->status,
                'is_revenue' => in_array($order->status, self::ORDER_REVENUE_STATUSES, true),
                'date' => $order->scheduled_at ?? $order->created_at,
                'services' => $services,
                'metadata' => [],
            ];
        });

        $paymentTransactions = $payments->map(function (Payment $payment) {
            $metadata = $payment->metadata ?? [];
            $type = strtolower((string) Arr::get($metadata, 'type'));
            $isRetail = in_array($type, ['retail', 'product', 'goods'], true);

            return [
                'id' => 'payment:' . $payment->id,
                'source' => 'payment',
                'client_key' => $payment->client_id ? 'client:' . $payment->client_id : null,
                'client_id' => $payment->client_id,
                'client_type' => $payment->client_id ? 'client' : null,
                'amount' => (float) $payment->amount,
                'service_amount' => $isRetail ? 0.0 : (float) $payment->amount,
                'retail_amount' => $isRetail ? (float) $payment->amount : 0.0,
                'status' => $payment->status,
                'is_revenue' => in_array(strtolower((string) $payment->status), self::PAYMENT_REVENUE_STATUSES, true),
                'date' => $payment->paid_at ?? $payment->created_at,
                'services' => collect([(string) Arr::get($metadata, 'service')])->filter()->map(fn ($name) => [
                    'name' => $name,
                    'price' => (float) $payment->amount,
                ]),
                'metadata' => $metadata,
            ];
        });

        // Both sides are lists of plain arrays. Left as Eloquent collections they
        // are not: merge() on one of those looks up getKey() on every item, so a
        // master with payments and no bookings crashed the whole page.
        return $orderTransactions->toBase()->merge($paymentTransactions->toBase());
    }

    /**
     * Who the master's clients are, in four groups that do not overlap.
     *
     * They used to be three overlapping questions — created recently, visited
     * recently, away for a while — printed under a column called «доля», so the
     * shares added up to 200%. Every client now falls into exactly one group,
     * and the numbers add up to the size of the book.
     *
     * @param  Collection<int, Client>  $clients
     * @param  Collection<int, Order>  $orders
     */
    protected function buildClientSegments(Collection $clients, Collection $orders, Carbon $from, Carbon $to): array
    {
        $sleepingThreshold = $to->copy()->subDays(self::SLEEPING_AFTER_DAYS);

        $visitsByAccount = $orders
            ->filter(fn (Order $order) => in_array($order->status, self::VISIT_STATUSES, true)
                && $order->scheduled_at
                && $order->scheduled_at->lessThanOrEqualTo($to))
            ->groupBy('client_id');

        $counts = ['regular' => 0, 'new' => 0, 'sleeping' => 0, 'never' => 0];

        foreach ($clients as $client) {
            $visits = $client->client_user_id
                ? ($visitsByAccount->get($client->client_user_id) ?? collect())
                : collect();

            if ($visits->isEmpty()) {
                $counts['never']++;

                continue;
            }

            $lastVisit = $visits->max('scheduled_at');

            if ($lastVisit && $lastVisit->lessThan($sleepingThreshold)) {
                $counts['sleeping']++;

                continue;
            }

            if ($visits->count() >= 2) {
                $counts['regular']++;

                continue;
            }

            $counts['new']++;
        }

        $total = max(1, $clients->count());

        $distribution = [];

        foreach (['regular', 'new', 'sleeping', 'never'] as $key) {
            $distribution[$key] = [
                'count' => $counts[$key],
                'share' => round(($counts[$key] / $total) * 100, 1),
                'label' => trans('analytics.segments.' . $key),
                'hint' => trans('analytics.segments.' . $key . '_hint'),
            ];
        }

        return [
            'total' => $clients->count(),
            'distribution' => $distribution,
        ];
    }

    protected function buildRiskClients(Collection $clients, Carbon $reference): Collection
    {
        $threshold = $reference->copy()->subDays(45);

        return $clients
            ->filter(fn (Client $client) => ! $client->last_visit_at || $client->last_visit_at->lessThan($threshold))
            ->sortBy(fn (Client $client) => $client->last_visit_at ?? CarbonImmutable::create(1900, 1, 1))
            ->take(8)
            ->map(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'loyalty_level' => $client->loyalty_level,
                'last_visit_at' => optional($client->last_visit_at)->toIso8601String(),
                'days_inactive' => $client->last_visit_at
                    ? $client->last_visit_at->diffInDays($reference)
                    : null,
            ]);
    }

    /**
     * What became of the bookings in the period.
     *
     * This used to be a «воронка продаж» whose first two stages were the same
     * thing — this product has no lead stage, a booking is made and then it
     * either happens or it does not. Four numbers a master can act on beat a
     * funnel shape borrowed from a sales team she does not have.
     *
     * @param  Collection<int, Order>  $bookings
     * @return array<int, array{key: string, label: string, count: int, share: float, tone: string}>
     */
    protected function buildBookingOutcomes(Collection $bookings, int $returningClients): array
    {
        $total = $bookings->count();
        $came = $bookings->filter(fn (Order $order) => in_array($order->status, self::VISIT_STATUSES, true))->count();
        $cancelled = $bookings->where('status', 'cancelled')->count();
        $missed = $bookings->where('status', 'no_show')->count();

        $share = fn (int $count) => $total > 0 ? round(($count / $total) * 100, 1) : 0.0;

        return [
            ['key' => 'total', 'label' => trans('analytics.outcomes.total'), 'count' => $total, 'share' => $total > 0 ? 100.0 : 0.0, 'tone' => 'neutral'],
            ['key' => 'came', 'label' => trans('analytics.outcomes.came'), 'count' => $came, 'share' => $share($came), 'tone' => 'good'],
            ['key' => 'returning', 'label' => trans('analytics.outcomes.returning'), 'count' => $returningClients, 'share' => $share($returningClients), 'tone' => 'good'],
            ['key' => 'cancelled', 'label' => trans('analytics.outcomes.cancelled'), 'count' => $cancelled, 'share' => $share($cancelled), 'tone' => 'muted'],
            ['key' => 'no_show', 'label' => trans('analytics.outcomes.no_show'), 'count' => $missed, 'share' => $share($missed), 'tone' => 'bad'],
        ];
    }

    protected function resolveTopClients(Collection $transactions): array
    {
        if ($transactions->isEmpty()) {
            return [];
        }

        $grouped = $transactions
            ->filter(fn (array $tx) => $tx['client_key'])
            ->groupBy('client_key')
            ->map(function (Collection $items) {
                return [
                    'amount' => round($items->sum('amount'), 2),
                    'transactions' => $items->count(),
                    'client_type' => $items->first()['client_type'] ?? null,
                    'client_id' => $items->first()['client_id'] ?? null,
                    'last_date' => $items->max(fn ($item) => $item['date'] instanceof Carbon ? $item['date'] : ($item['date'] ? Carbon::parse($item['date']) : null)),
                ];
            })
            ->sortByDesc('amount')
            ->take(5);

        $userIds = $grouped
            ->filter(fn ($data) => $data['client_type'] === 'user')
            ->map(fn ($data) => $data['client_id'])
            ->filter()
            ->unique();
        $clientIds = $grouped
            ->filter(fn ($data) => $data['client_type'] === 'client')
            ->map(fn ($data) => $data['client_id'])
            ->filter()
            ->unique();

        $userNames = $userIds->isEmpty()
            ? collect()
            : User::whereIn('id', $userIds)->get()->keyBy('id')->map->name;
        $clientNames = $clientIds->isEmpty()
            ? collect()
            : Client::whereIn('id', $clientIds)->get()->keyBy('id')->map->name;

        $unknown = trans('analytics.labels.unknown_client');

        return $grouped
            ->map(function (array $data, string $key) use ($userNames, $clientNames, $unknown) {
                $name = $data['client_type'] === 'user'
                    ? ($userNames[$data['client_id']] ?? $unknown)
                    : ($clientNames[$data['client_id']] ?? $unknown);

                return [
                    'client_key' => $key,
                    'name' => $name,
                    'amount' => $data['amount'],
                    'transactions' => $data['transactions'],
                    'last_purchase_at' => $data['last_date'] instanceof Carbon
                        ? $data['last_date']->toIso8601String()
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    protected function buildServiceShare(Collection $transactions): array
    {
        $totals = collect();

        $transactions->each(function (array $tx) use (&$totals) {
            collect($tx['services'])->each(function (array $service) use (&$totals) {
                $name = $service['name'] ?? null;
                $price = (float) ($service['price'] ?? 0);
                if (! $name) {
                    return;
                }
                $totals[$name] = ($totals[$name] ?? 0) + ($price > 0 ? $price : 1);
            });
        });

        if ($totals->isEmpty()) {
            return [
                'labels' => [],
                'values' => [],
            ];
        }

        $sorted = $totals->sortDesc();
        $top = $sorted->take(6);
        if ($sorted->count() > 6) {
            $top = $top->merge([
                trans('analytics.labels.other') => $sorted->slice(6)->sum(),
            ]);
        }

        return [
            'labels' => $top->keys()->values()->all(),
            'values' => $top->values()->map(fn ($value) => round((float) $value, 2))->all(),
        ];
    }

    protected function buildRevenueTrend(
        Collection $current,
        Collection $previous,
        Carbon $from,
        Carbon $to,
        Carbon $compareFrom,
        Carbon $compareTo,
        string $grouping,
        string $locale
    ): array {
        $segments = $this->segmentPeriod($from, $to, $grouping, $locale);
        $currentSeries = [];
        $previousSeries = [];
        $labels = [];

        foreach ($segments as $segment) {
            $labels[] = $segment['label'];

            $currentSeries[] = round($current
                ->filter(fn (array $tx) => $this->isWithinPeriod($tx['date'], $segment['start'], $segment['end']))
                ->sum('amount'), 2);

            $previousStart = $compareFrom
                ->copy()
                ->addDays($segment['offset'])
                ->startOfDay();

            $previousEnd = $previousStart
                ->copy()
                ->addDays($segment['span'])
                ->endOfDay();
            if ($previousEnd->greaterThan($compareTo)) {
                $previousEnd = $compareTo->copy();
            }

            $previousSeries[] = round($previous
                ->filter(fn (array $tx) => $this->isWithinPeriod($tx['date'], $previousStart, $previousEnd))
                ->sum('amount'), 2);
        }

        return [
            'labels' => $labels,
            'current' => $currentSeries,
            'previous' => $previousSeries,
            'current_total' => round(array_sum($currentSeries), 2),
            'previous_total' => round(array_sum($previousSeries), 2),
        ];
    }

    protected function segmentPeriod(Carbon $from, Carbon $to, string $grouping, string $locale): array
    {
        $segments = [];
        $cursor = $from->copy();
        $guard = 0;

        while ($cursor->lte($to) && $guard < 400) {
            $guard++;
            $rangeStart = match ($grouping) {
                'week' => $cursor->copy()->startOfWeek(),
                'month' => $cursor->copy()->startOfMonth(),
                default => $cursor->copy()->startOfDay(),
            };
            if ($rangeStart->lt($from)) {
                $rangeStart = $from->copy();
            }

            $rangeEnd = match ($grouping) {
                'week' => $cursor->copy()->endOfWeek(),
                'month' => $cursor->copy()->endOfMonth(),
                default => $cursor->copy()->endOfDay(),
            };
            if ($rangeEnd->gt($to)) {
                $rangeEnd = $to->copy();
            }

            $label = match ($grouping) {
                'week' => $rangeStart->copy()->locale($locale)->translatedFormat('d M') . ' - ' . $rangeEnd->copy()->locale($locale)->translatedFormat('d M'),
                'month' => $rangeStart->copy()->locale($locale)->translatedFormat('F Y'),
                default => $rangeStart->copy()->locale($locale)->translatedFormat('d M'),
            };

            $segments[] = [
                'label' => $label,
                'start' => $rangeStart->copy(),
                'end' => $rangeEnd->copy(),
                // Carbon 3 signs its diffs: `$a->diffInDays($b)` is `$b - $a`,
                // so the old `$rangeStart->diffInDays($from)` was negative and
                // every «прошлый период» bucket was looked up before the compare
                // window even began — which is why that line was flat at zero.
                'offset' => (int) $from->diffInDays($rangeStart),
                'span' => (int) $rangeStart->diffInDays($rangeEnd),
            ];

            $cursor = $rangeEnd->copy()->addDay()->startOfDay();
        }

        return $segments;
    }

    protected function buildPeakHoursInsight(Collection $transactions, string $locale, string $timezone): array
    {
        $slots = collect([
            ['key' => 'morning', 'label' => trans('analytics.peak_hours.slots.morning'), 'start' => 8, 'end' => 12],
            ['key' => 'midday', 'label' => trans('analytics.peak_hours.slots.midday'), 'start' => 12, 'end' => 15],
            ['key' => 'afternoon', 'label' => trans('analytics.peak_hours.slots.afternoon'), 'start' => 15, 'end' => 18],
            ['key' => 'evening', 'label' => trans('analytics.peak_hours.slots.evening'), 'start' => 18, 'end' => 23],
        ])->values();

        $days = collect(range(0, 6))->map(function (int $index) use ($locale, $timezone) {
            $date = CarbonImmutable::now($timezone)->startOfWeek()->addDays($index)->locale($locale);

            return [
                'index' => $index,
                'label' => $date->translatedFormat('dd'),
                'full_label' => $date->translatedFormat('dddd'),
            ];
        })->values();

        $cells = collect();

        foreach ($days as $day) {
            foreach ($slots as $slot) {
                $cells->push([
                    'day_index' => $day['index'],
                    'day_label' => $day['full_label'],
                    'slot_key' => $slot['key'],
                    'slot_label' => $slot['label'],
                    'revenue' => 0.0,
                    'transactions' => 0,
                    'services' => [],
                    'pairs' => [],
                ]);
            }
        }

        $transactions->each(function (array $transaction) use (&$cells, $slots, $timezone) {
            $date = $transaction['date'] ?? null;

            if (! $date) {
                return;
            }

            try {
                $date = $date instanceof Carbon
                    ? $date->copy()->setTimezone($timezone)
                    : Carbon::parse($date, $timezone);
            } catch (Throwable) {
                return;
            }

            $slot = $slots->first(function (array $candidate) use ($date) {
                $hour = (int) $date->format('G');

                return $hour >= $candidate['start'] && $hour < $candidate['end'];
            });

            if (! $slot) {
                return;
            }

            $cellIndex = $cells->search(
                fn (array $cell) => $cell['day_index'] === ($date->dayOfWeekIso - 1) && $cell['slot_key'] === $slot['key']
            );

            if ($cellIndex === false) {
                return;
            }

            $cell = $cells->get($cellIndex);
            $cell['revenue'] += (float) ($transaction['amount'] ?? 0);
            $cell['transactions']++;

            $serviceNames = collect($transaction['services'] ?? [])
                ->pluck('name')
                ->filter(fn (mixed $name) => is_string($name) && $name !== '')
                ->values();

            foreach ($serviceNames as $serviceName) {
                $cell['services'][$serviceName] = ($cell['services'][$serviceName] ?? 0) + 1;
            }

            $uniqueServices = $serviceNames->unique()->values();
            if ($uniqueServices->count() > 1) {
                for ($left = 0; $left < $uniqueServices->count() - 1; $left++) {
                    for ($right = $left + 1; $right < $uniqueServices->count(); $right++) {
                        $pair = [$uniqueServices[$left], $uniqueServices[$right]];
                        sort($pair);
                        $pairKey = implode(' + ', $pair);
                        $cell['pairs'][$pairKey] = ($cell['pairs'][$pairKey] ?? 0) + 1;
                    }
                }
            }

            $cells->put($cellIndex, $cell);
        });

        $activeCells = $cells
            ->filter(fn (array $cell) => $cell['transactions'] > 0)
            ->values();

        if ($activeCells->count() < 3) {
            return [
                'status' => 'empty',
                'title' => trans('analytics.peak_hours.empty_title'),
                'description' => trans('analytics.peak_hours.empty_description'),
            ];
        }

        $maxRevenue = max(1, (float) $activeCells->max('revenue'));
        $topCell = $activeCells
            ->sortByDesc(fn (array $cell) => ($cell['revenue'] * 1000) + $cell['transactions'])
            ->first();

        $overallAverageTicket = $activeCells->sum('revenue') / max(1, $activeCells->sum('transactions'));
        $highlightAverageTicket = $topCell['revenue'] / max(1, $topCell['transactions']);
        $topServices = collect($topCell['services'])->sortDesc()->take(3);
        $topPair = collect($topCell['pairs'])->sortDesc()->keys()->first();

        $drivers = [];

        if ($topServices->isNotEmpty()) {
            $leadService = (string) $topServices->keys()->first();
            $drivers[] = [
                'title' => trans('analytics.peak_hours.drivers.top_service_title'),
                'body' => trans('analytics.peak_hours.drivers.top_service_body', [
                    'service' => $leadService,
                ]),
            ];
        }

        if ($highlightAverageTicket >= $overallAverageTicket * 1.1) {
            $drivers[] = [
                'title' => trans('analytics.peak_hours.drivers.average_ticket_title'),
                'body' => trans('analytics.peak_hours.drivers.average_ticket_body'),
            ];
        }

        if ($topPair) {
            $drivers[] = [
                'title' => trans('analytics.peak_hours.drivers.bundle_title'),
                'body' => trans('analytics.peak_hours.drivers.bundle_body', [
                    'pair' => $topPair,
                ]),
            ];
        } elseif ($topServices->count() > 1) {
            $secondaryService = (string) $topServices->keys()->skip(1)->first();
            $drivers[] = [
                'title' => trans('analytics.peak_hours.drivers.follow_up_title'),
                'body' => trans('analytics.peak_hours.drivers.follow_up_body', [
                    'service' => $secondaryService,
                ]),
            ];
        }

        $actionBody = $topPair
            ? trans('analytics.peak_hours.action_body_pair', [
                'pair' => $topPair,
                'day' => mb_strtolower((string) $topCell['day_label']),
                'slot' => $topCell['slot_label'],
            ])
            : trans('analytics.peak_hours.action_body_service', [
                'service' => (string) ($topServices->keys()->first() ?? trans('analytics.peak_hours.fallback_service')),
                'day' => mb_strtolower((string) $topCell['day_label']),
                'slot' => $topCell['slot_label'],
            ]);

        return [
            'status' => 'ready',
            'title' => trans('analytics.peak_hours.title'),
            'description' => trans('analytics.peak_hours.description'),
            'headline' => trans('analytics.peak_hours.headline', [
                'day' => mb_strtolower((string) $topCell['day_label']),
                'slot' => $topCell['slot_label'],
            ]),
            'summary' => trans('analytics.peak_hours.summary', [
                'count' => $topCell['transactions'],
            ]),
            'highlight' => [
                'day_label' => $topCell['day_label'],
                'slot_label' => $topCell['slot_label'],
                'revenue' => round((float) $topCell['revenue'], 2),
                'transactions' => (int) $topCell['transactions'],
                'average_ticket' => round($highlightAverageTicket, 2),
            ],
            'days' => $days->all(),
            'slots' => $slots->map(fn (array $slot) => Arr::only($slot, ['key', 'label']))->all(),
            'cells' => $cells->map(function (array $cell) use ($maxRevenue) {
                return [
                    'day_index' => $cell['day_index'],
                    'slot_key' => $cell['slot_key'],
                    'revenue' => round((float) $cell['revenue'], 2),
                    'transactions' => (int) $cell['transactions'],
                    'average_ticket' => $cell['transactions'] > 0
                        ? round($cell['revenue'] / $cell['transactions'], 2)
                        : 0.0,
                    'intensity' => $cell['transactions'] > 0
                        ? round(min(1, $cell['revenue'] / $maxRevenue), 3)
                        : 0.0,
                ];
            })->values()->all(),
            'top_services' => $topServices->map(fn (int $count, string $name) => [
                'name' => $name,
                'count' => $count,
            ])->values()->all(),
            'drivers' => $drivers,
            'action' => [
                'title' => trans('analytics.peak_hours.action_title'),
                'body' => $actionBody,
            ],
        ];
    }

    protected function buildFinancialInsights(array $serviceShare, float $revenue, float $serviceRevenue, ?float $avgTicketDelta): array
    {
        $insights = [];
        $topServiceName = $serviceShare['labels'][0] ?? null;
        $topServiceShare = $serviceShare['values'][0] ?? null;

        if ($topServiceName !== null && $topServiceShare !== null) {
            $insights[] = [
                'title' => trans('analytics.insights.revenue_driver'),
                'body' => trans('analytics.insights.revenue_driver_text', [
                    'service' => $topServiceName,
                    'share' => round($topServiceShare, 1),
                ]),
            ];
        }

        $insights[] = [
            'title' => trans('analytics.insights.revenue_total'),
            'body' => trans('analytics.insights.revenue_total_text', [
                'revenue' => number_format($revenue, 0, '.', ' '),
                'services' => number_format($serviceRevenue, 0, '.', ' '),
            ]),
        ];

        if ($avgTicketDelta !== null) {
            $insights[] = [
                'title' => trans('analytics.insights.average_ticket'),
                'body' => trans('analytics.insights.average_ticket_text', [
                    'delta' => $avgTicketDelta,
                ]),
            ];
        }

        return $insights;
    }

    protected function buildClientInsights(array $segments, float $retentionRate, Collection $riskClients, array $ltv): array
    {
        $insights = [];

        $activeShare = $segments['distribution']['regular']['share'] ?? 0;
        $sleepingCount = $segments['distribution']['sleeping']['count'] ?? 0;

        $insights[] = [
            'title' => trans('analytics.insights.retention'),
            'body' => trans('analytics.insights.retention_text', [
                'retention' => $retentionRate,
                'active' => $activeShare,
            ]),
        ];

        $insights[] = [
            'title' => trans('analytics.insights.churn'),
            'body' => trans('analytics.insights.churn_text', [
                'risk' => $riskClients->count(),
                'sleeping' => $sleepingCount,
            ]),
        ];

        $ltvValue = $ltv['value'] ?? 0;
        $insights[] = [
            'title' => trans('analytics.insights.ltv'),
            'body' => trans('analytics.insights.ltv_text', [
                'ltv' => number_format($ltvValue, 0, '.', ' '),
            ]),
        ];

        return $insights;
    }

    protected function buildClientPersona(Collection $clients, Collection $services, Collection $transactions): array
    {
        if ($clients->isEmpty()) {
            return [];
        }

        $ages = $clients->filter(fn (Client $client) => $client->birthday)->map(fn (Client $client) => $client->birthday->age);
        $avgAge = $ages->isNotEmpty() ? (int) round($ages->average()) : null;
        $ageGroup = $avgAge ? $this->ageGroup($avgAge) : null;

        $loyalty = $clients->groupBy(fn (Client $client) => $client->loyalty_level ?? 'none')->map->count();
        $topLoyalty = $loyalty->sortDesc()->keys()->first();

        $tags = $clients
            ->flatMap(fn (Client $client) => collect($client->tags ?? [])->map(fn ($tag) => Str::lower((string) $tag)))
            ->filter();
        $popularTags = $tags->isEmpty()
            ? []
            : $tags->countBy()->sortDesc()->take(3)->map(fn ($count, $tag) => ['tag' => $tag, 'count' => $count])->values()->all();

        $serviceCounts = collect();
        $transactions->each(function (array $tx) use (&$serviceCounts) {
            collect($tx['services'])->each(function (array $service) use (&$serviceCounts) {
                if ($service['name'] ?? null) {
                    $serviceCounts[$service['name']] = ($serviceCounts[$service['name']] ?? 0) + 1;
                }
            });
        });

        if ($serviceCounts->isEmpty()) {
            $serviceCounts = $services->pluck('name')->countBy();
        }

        $favoriteService = $serviceCounts->sortDesc()->keys()->first();

        return array_filter([
            'avg_age' => $avgAge,
            'age_group' => $ageGroup,
            'top_loyalty' => $topLoyalty,
            'popular_tags' => $popularTags,
            'favorite_service' => $favoriteService,
        ], fn ($value) => $value !== null && $value !== []);
    }

    protected function ageGroup(int $age): string
    {
        return match (true) {
            $age < 25 => '18-24',
            $age < 35 => '25-34',
            $age < 45 => '35-44',
            $age < 55 => '45-54',
            default => '55+',
        };
    }

    protected function calculateLtv(Collection $transactions, Collection $previousTransactions): array
    {
        $byClient = $transactions
            ->filter(fn (array $tx) => $tx['client_key'])
            ->groupBy('client_key')
            ->map(fn (Collection $items) => $items->sum('amount'));

        $previousByClient = $previousTransactions
            ->filter(fn (array $tx) => $tx['client_key'])
            ->groupBy('client_key')
            ->map(fn (Collection $items) => $items->sum('amount'));

        $value = $byClient->isEmpty()
            ? 0.0
            : round($byClient->sum() / max(1, $byClient->count()), 2);
        $previousValue = $previousByClient->isEmpty()
            ? 0.0
            : round($previousByClient->sum() / max(1, $previousByClient->count()), 2);

        return [
            'value' => $value,
            'previous' => $previousValue,
            'delta' => $this->percentChange($value, $previousValue),
            'insight' => trans('analytics.ltv.insight_default'),
        ];
    }

    protected function formatMetric(float|int $current, float|int|null $previous, ?float $delta): array
    {
        return [
            'current' => round((float) $current, 2),
            'previous' => $previous !== null ? round((float) $previous, 2) : null,
            'delta' => $delta,
            'trend' => $delta === null
                ? null
                : ($delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat')),
        ];
    }

    protected function insightsCacheKey(int $userId, Carbon $from, Carbon $to, Carbon $compareFrom, Carbon $compareTo): string
    {
        return 'analytics:ai:' . $userId . ':' . sha1(
            $from->toIso8601String() . '|' .
            $to->toIso8601String() . '|' .
            $compareFrom->toIso8601String() . '|' .
            $compareTo->toIso8601String()
        );
    }

    protected function generateAiInsights(array $context): array
    {
        $fallback = $this->fallbackAiInsights($context);

        if (! $this->aiEnabled()) {
            return $fallback;
        }

        $prompt = <<<'PROMPT'
Ты — аналитик CRM для индустрии красоты. На основе переданных метрик подготовь краткое резюме, прогноз выручки на следующий период и три практических рекомендации. Используй цифры из контекста. Форматируй ответ в JSON.
PROMPT;

        $schema = [
            'name' => 'analytics_insights',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'summary' => ['type' => 'string'],
                    'forecast' => [
                        'type' => 'object',
                        'properties' => [
                            'revenue' => ['type' => 'number'],
                            'delta' => ['type' => 'number'],
                            'confidence' => ['type' => 'number'],
                            'comment' => ['type' => 'string'],
                        ],
                        'required' => ['revenue', 'delta', 'confidence'],
                    ],
                    'recommendations' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'description' => ['type' => 'string'],
                                'category' => ['type' => 'string'],
                            ],
                            'required' => ['title', 'description'],
                        ],
                        'minItems' => 1,
                        'maxItems' => 5,
                    ],
                ],
                'required' => ['summary', 'forecast', 'recommendations'],
            ],
        ];

        try {
            $decoded = $this->ai->json('analytics_insights', $prompt, $context, $schema, [
                'max_tokens' => 600,
            ]);

            if ($decoded === null) {
                return $fallback;
            }

            return [
                'summary' => (string) Arr::get($decoded, 'summary', $fallback['summary']),
                'forecast' => [
                    'revenue' => (float) Arr::get($decoded, 'forecast.revenue', $fallback['forecast']['revenue']),
                    'delta' => (float) Arr::get($decoded, 'forecast.delta', $fallback['forecast']['delta']),
                    'confidence' => (float) Arr::get($decoded, 'forecast.confidence', $fallback['forecast']['confidence']),
                    'comment' => (string) Arr::get($decoded, 'forecast.comment', $fallback['forecast']['comment']),
                ],
                'recommendations' => collect(Arr::get($decoded, 'recommendations', []))
                    ->take(5)
                    ->map(function ($item) {
                        return [
                            'title' => (string) Arr::get($item, 'title'),
                            'description' => (string) Arr::get($item, 'description'),
                            'category' => (string) Arr::get($item, 'category', 'general'),
                        ];
                    })
                    ->values()
                    ->all(),
            ];
        } catch (Throwable $exception) {
            Log::warning('Failed to generate analytics AI insights.', [
                'exception' => $exception->getMessage(),
            ]);
        }

        return $fallback;
    }

    protected function fallbackAiInsights(array $context): array
    {
        $revenue = (float) ($context['revenue'] ?? 0);
        $delta = (float) ($context['revenue_delta'] ?? 0);
        $retention = (float) ($context['retention_rate'] ?? 0);
        $averageTicket = (float) ($context['average_ticket'] ?? 0);
        $riskClients = collect($context['risk_clients'] ?? []);
        $serviceShare = $context['service_share']['labels'][0] ?? null;
        $transactions = (int) ($context['transactions'] ?? 0);

        $forecastDelta = $delta !== 0.0 ? $delta * 0.6 : ($retention - 50) / 5;
        $forecastRevenue = max(0, round($revenue * (1 + ($forecastDelta / 100)), 2));
        $confidence = round(min(0.92, max(0.35, 0.55 + ($retention / 200))), 2);

        $summaryParts = [];
        $summaryParts[] = trans('analytics.ai.summary_revenue', [
            'revenue' => number_format($revenue, 0, '.', ' '),
            'delta' => round($delta, 1),
        ]);
        $summaryParts[] = trans('analytics.ai.summary_retention', [
            'retention' => round($retention, 1),
        ]);
        if ($serviceShare) {
            $summaryParts[] = trans('analytics.ai.summary_service', ['service' => $serviceShare]);
        }

        $recommendations = [];
        if ($riskClients->isNotEmpty()) {
            $recommendations[] = [
                'title' => trans('analytics.ai.recommendations.risk.title'),
                'description' => trans('analytics.ai.recommendations.risk.description', [
                    'count' => $riskClients->count(),
                ]),
                'category' => 'retention',
            ];
        }

        if ($transactions > 0) {
            $recommendations[] = [
                'title' => trans('analytics.ai.recommendations.upsell.title'),
                'description' => trans('analytics.ai.recommendations.upsell.description', [
                    'service' => $serviceShare ?? trans('analytics.ai.recommendations.upsell.fallback_service'),
                ]),
                'category' => 'upsell',
            ];
        }

        $recommendations[] = [
            'title' => trans('analytics.ai.recommendations.nurture.title'),
            'description' => trans('analytics.ai.recommendations.nurture.description', [
                'ticket' => number_format($averageTicket, 0, '.', ' '),
            ]),
            'category' => 'retention',
        ];

        return [
            'summary' => implode(' ', $summaryParts),
            'forecast' => [
                'revenue' => $forecastRevenue,
                'delta' => round($forecastDelta, 1),
                'confidence' => $confidence,
                'comment' => trans('analytics.ai.forecast_comment'),
            ],
            'recommendations' => $recommendations,
        ];
    }

    protected function aiEnabled(): bool
    {
        return $this->ai->enabled('analytics_insights');
    }
}
