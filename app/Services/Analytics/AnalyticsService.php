<?php

namespace App\Services\Analytics;

use App\Models\Client;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AnalyticsService
{
    public function __construct(private readonly CacheRepository $cache)
    {
    }

    public function build(int $userId, Carbon $start, Carbon $end, string $groupBy, string $compareTo): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $comparison = $this->resolveComparisonRange($start, $end, $compareTo);

        $orders = Order::with('client')
            ->where('master_id', $userId)
            ->whereBetween('scheduled_at', [$start, $end])
            ->get();

        $comparisonOrders = collect();
        if ($comparison) {
            $comparisonOrders = Order::with('client')
                ->where('master_id', $userId)
                ->whereBetween('scheduled_at', [$comparison['start'], $comparison['end']])
                ->get();
        }

        $services = Service::where('user_id', $userId)->get()->keyBy('id');
        $clients = Client::where('user_id', $userId)->get()->keyBy('id');

        $currentMetrics = $this->buildMetrics($orders, $services, $clients, $start, $end, $groupBy);
        $previousMetrics = $comparison
            ? $this->buildMetrics($comparisonOrders, $services, $clients, $comparison['start'], $comparison['end'], $groupBy)
            : $this->blankMetrics($groupBy, $start, $end);

        $insights = $this->cache->remember(
            $this->cacheKey($userId, $start, $end, $compareTo),
            now()->addHours(6),
            fn () => $this->buildInsights($currentMetrics, $previousMetrics, $services, $clients, $compareTo)
        );

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'compare_to' => $compareTo,
                'comparison_range' => $comparison ? [
                    'start' => $comparison['start']->toDateString(),
                    'end' => $comparison['end']->toDateString(),
                ] : null,
                'group_by' => $groupBy,
            ],
            'summary' => $this->buildSummaryCards($currentMetrics, $previousMetrics),
            'finance' => [
                'revenue_timeline' => $currentMetrics['revenue_timeline'],
                'revenue_sources' => $currentMetrics['revenue_sources'],
                'average_ticket_trend' => $currentMetrics['average_ticket_trend'],
                'insights' => $insights['finance'],
            ],
            'clients' => [
                'funnel' => $currentMetrics['funnel'],
                'segments' => $currentMetrics['segments'],
                'churn' => $currentMetrics['churn'],
                'ltv' => $currentMetrics['ltv'],
                'insights' => $insights['clients'],
            ],
            'ai' => $insights['ai'],
        ];
    }

    protected function buildMetrics(Collection $orders, Collection $services, Collection $clients, Carbon $start, Carbon $end, string $groupBy): array
    {
        $completed = $orders->filter(fn (Order $order) => $order->status === 'completed');
        $currency = trans('analytics.currency');

        $revenueDistribution = $this->splitRevenueByType($completed);

        $totalRevenue = $revenueDistribution['total'];
        $serviceRevenue = $revenueDistribution['services'];
        $retailRevenue = $revenueDistribution['retail'];

        $ordersCount = $orders->count();
        $completedCount = $completed->count();

        $averageCheck = $completedCount > 0 ? round($totalRevenue / $completedCount, 2) : 0;

        $uniqueClients = $orders->pluck('client_id')->filter()->unique();
        $firstVisits = $this->loadFirstVisitDates($orders, $start);

        $newClients = $uniqueClients->filter(fn ($id) => isset($firstVisits[$id]) && $firstVisits[$id]->betweenIncluded($start, $end));
        $returningClients = $uniqueClients->diff($newClients);

        $loyalClients = $this->resolveLoyalClients($orders);

        $retentionBase = max(1, $uniqueClients->count());
        $retentionRate = round(($loyalClients->count() / $retentionBase) * 100, 1);

        $topClientData = $this->resolveTopClient($completed);

        $revenueTimeline = $this->buildRevenueTimeline($completed, $start, $end, $groupBy);
        $averageTicketTrend = $this->buildAverageTicketTrend($completed, $start, $end, $groupBy);
        $revenueByService = $this->buildRevenueByService($completed, $services);

        $funnel = $this->buildFunnel($orders, $uniqueClients, $loyalClients);
        $segments = $this->buildSegments($clients, $orders, $start, $end);
        $churn = $this->buildChurn($orders, $clients, $start);
        $ltv = $this->buildLtv($orders, $services, $loyalClients);

        return [
            'currency' => $currency,
            'totals' => [
                'revenue_total' => $totalRevenue,
                'revenue_services' => $serviceRevenue,
                'revenue_retail' => $retailRevenue,
                'average_check' => $averageCheck,
                'clients_total' => $uniqueClients->count(),
                'clients_new' => $newClients->count(),
                'clients_returning' => $returningClients->count(),
                'clients_loyal' => $loyalClients->count(),
                'transactions' => $ordersCount,
                'retention_rate' => $retentionRate,
                'top_client' => $topClientData,
            ],
            'revenue_timeline' => $revenueTimeline,
            'average_ticket_trend' => $averageTicketTrend,
            'revenue_sources' => $revenueByService,
            'funnel' => $funnel,
            'segments' => $segments,
            'churn' => $churn,
            'ltv' => $ltv,
        ];
    }

    protected function blankMetrics(string $groupBy, Carbon $start, Carbon $end): array
    {
        $timeline = [
            'labels' => [],
            'series' => [],
            'meta' => [
                'group_by' => $groupBy,
            ],
        ];

        return [
            'currency' => trans('analytics.currency'),
            'totals' => [
                'revenue_total' => 0,
                'revenue_services' => 0,
                'revenue_retail' => 0,
                'average_check' => 0,
                'clients_total' => 0,
                'clients_new' => 0,
                'clients_returning' => 0,
                'clients_loyal' => 0,
                'transactions' => 0,
                'retention_rate' => 0,
                'top_client' => null,
            ],
            'revenue_timeline' => $timeline,
            'average_ticket_trend' => $timeline,
            'revenue_sources' => [
                'labels' => [],
                'series' => [],
            ],
            'funnel' => [
                'stages' => [],
                'conversion' => 0,
            ],
            'segments' => [
                'new' => 0,
                'loyal' => 0,
                'inactive' => 0,
            ],
            'churn' => [
                'rate' => 0,
                'at_risk' => [],
            ],
            'ltv' => [
                'average' => 0,
                'top_services' => [],
            ],
        ];
    }

    protected function buildSummaryCards(array $current, array $previous): array
    {
        $totals = $current['totals'];
        $prev = $previous['totals'];

        return [
            $this->summaryCard('revenue', $totals['revenue_total'], $prev['revenue_total'], $current['currency'], [
                'services' => $totals['revenue_services'],
                'retail' => $totals['revenue_retail'],
            ]),
            $this->summaryCard('average_check', $totals['average_check'], $prev['average_check'], $current['currency']),
            $this->summaryCard('clients', $totals['clients_total'], $prev['clients_total'], '' , [
                'new' => $totals['clients_new'],
                'returning' => $totals['clients_returning'],
                'loyal' => $totals['clients_loyal'],
            ]),
            $this->summaryCard('transactions', $totals['transactions'], $prev['transactions']),
            $this->summaryCard('retention_rate', $totals['retention_rate'], $prev['retention_rate'], '%'),
            $this->summaryCard('top_client', $totals['top_client']['value'] ?? 0, $prev['top_client']['value'] ?? 0, $current['currency'], $totals['top_client']),
        ];
    }

    protected function summaryCard(string $key, $currentValue, $previousValue, string $suffix = '', $extra = null): array
    {
        $change = $this->calculateChange($currentValue, $previousValue);

        return [
            'key' => $key,
            'label' => trans("analytics.summary.{$key}"),
            'value' => round((float) $currentValue, 2),
            'suffix' => $suffix,
            'change' => $change,
            'breakdown' => $this->formatBreakdown($key, $extra, $suffix),
        ];
    }

    protected function formatBreakdown(string $key, $data, string $suffix): ?array
    {
        if ($data === null) {
            return null;
        }

        if ($key === 'top_client' && is_array($data)) {
            if (empty($data['client'])) {
                return null;
            }

            return [
                'client' => $data['client'],
                'value' => round((float) ($data['value'] ?? 0), 2),
                'suffix' => $suffix,
            ];
        }

        if (! is_array($data)) {
            return null;
        }

        $total = array_sum(array_map('floatval', $data));
        $formatted = [];

        foreach ($data as $subKey => $value) {
            $formatted[] = [
                'key' => $subKey,
                'label' => trans("analytics.summary_breakdown.{$subKey}"),
                'value' => round((float) $value, 2),
                'suffix' => $suffix,
                'share' => $total > 0 ? round(((float) $value / $total) * 100, 1) : 0,
            ];
        }

        return $formatted;
    }

    protected function calculateChange($current, $previous): array
    {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous == 0 && $current == 0) {
            return [
                'direction' => 'equal',
                'percentage' => 0,
                'absolute' => 0,
            ];
        }

        if ($previous == 0) {
            return [
                'direction' => 'up',
                'percentage' => 100,
                'absolute' => round($current, 2),
            ];
        }

        $diff = $current - $previous;
        $direction = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'equal');
        $percentage = round(($diff / $previous) * 100, 1);

        return [
            'direction' => $direction,
            'percentage' => $percentage,
            'absolute' => round($diff, 2),
        ];
    }

    protected function splitRevenueByType(Collection $orders): array
    {
        $total = 0;
        $serviceRevenue = 0;
        $retailRevenue = 0;

        foreach ($orders as $order) {
            $total += (float) $order->total_price;
            $items = collect($order->services ?? []);

            if ($items->isEmpty()) {
                $serviceRevenue += (float) $order->total_price;
                continue;
            }

            $serviceSum = $items->filter(fn ($item) => ($item['type'] ?? 'service') !== 'product')
                ->sum(fn ($item) => (float) ($item['price'] ?? 0));
            $productSum = $items->filter(fn ($item) => ($item['type'] ?? 'service') === 'product')
                ->sum(fn ($item) => (float) ($item['price'] ?? 0));

            $itemsTotal = $serviceSum + $productSum;

            if ($itemsTotal <= 0) {
                $serviceRevenue += (float) $order->total_price;
                continue;
            }

            $ratio = (float) $order->total_price / $itemsTotal;
            $serviceRevenue += $serviceSum * $ratio;
            $retailRevenue += $productSum * $ratio;
        }

        return [
            'total' => round($total, 2),
            'services' => round($serviceRevenue, 2),
            'retail' => round($retailRevenue, 2),
        ];
    }

    protected function loadFirstVisitDates(Collection $orders, Carbon $start): array
    {
        $clientIds = $orders->pluck('client_id')->filter()->unique();

        if ($clientIds->isEmpty()) {
            return [];
        }

        $records = Order::selectRaw('client_id, MIN(scheduled_at) as first_visit')
            ->whereIn('client_id', $clientIds)
            ->groupBy('client_id')
            ->pluck('first_visit', 'client_id');

        $result = [];
        foreach ($records as $clientId => $date) {
            $result[$clientId] = Carbon::parse($date)->startOfDay();
        }

        return $result;
    }

    protected function resolveLoyalClients(Collection $orders): Collection
    {
        $counts = $orders->groupBy('client_id')->map->count();

        return $counts->filter(fn ($count) => $count >= 2)->keys();
    }

    protected function resolveTopClient(Collection $orders): ?array
    {
        if ($orders->isEmpty()) {
            return null;
        }

        $totals = $orders->groupBy('client_id')->map(function (Collection $clientOrders) {
            return $clientOrders->sum(fn (Order $order) => (float) $order->total_price);
        });

        $topClientId = $totals->sortDesc()->keys()->first();
        $topValue = $totals[$topClientId];

        /** @var Order|null $order */
        $order = $orders->firstWhere('client_id', $topClientId);
        $name = $order && $order->client ? $order->client->name : trans('analytics.labels.unknown_client');

        return [
            'client_id' => $topClientId,
            'client' => $name,
            'value' => round((float) $topValue, 2),
        ];
    }

    protected function buildRevenueTimeline(Collection $orders, Carbon $start, Carbon $end, string $groupBy): array
    {
        $groups = [];
        $period = $this->iteratePeriod($start, $end, $groupBy);

        foreach ($period as $bucket) {
            $groups[$bucket['label']] = 0;
        }

        foreach ($orders as $order) {
            $label = $this->resolveLabelForDate($order->scheduled_at, $groupBy);
            if (array_key_exists($label, $groups)) {
                $groups[$label] += (float) $order->total_price;
            }
        }

        return [
            'labels' => array_keys($groups),
            'series' => array_values(array_map(fn ($value) => round($value, 2), $groups)),
            'meta' => [
                'group_by' => $groupBy,
            ],
        ];
    }

    protected function buildAverageTicketTrend(Collection $orders, Carbon $start, Carbon $end, string $groupBy): array
    {
        $buckets = [];
        $period = $this->iteratePeriod($start, $end, $groupBy);

        foreach ($period as $bucket) {
            $buckets[$bucket['label']] = [
                'sum' => 0,
                'count' => 0,
            ];
        }

        foreach ($orders as $order) {
            $label = $this->resolveLabelForDate($order->scheduled_at, $groupBy);
            if (! isset($buckets[$label])) {
                continue;
            }

            $buckets[$label]['sum'] += (float) $order->total_price;
            $buckets[$label]['count'] += 1;
        }

        $series = [];
        foreach ($buckets as $label => $data) {
            $series[$label] = $data['count'] > 0 ? round($data['sum'] / $data['count'], 2) : 0;
        }

        return [
            'labels' => array_keys($series),
            'series' => array_values($series),
            'meta' => [
                'group_by' => $groupBy,
            ],
        ];
    }

    protected function buildRevenueByService(Collection $orders, Collection $services): array
    {
        $totals = [];
        foreach ($orders as $order) {
            $items = collect($order->services ?? []);

            if ($items->isEmpty()) {
                $label = trans('analytics.labels.other_services');
                $totals[$label] = ($totals[$label] ?? 0) + (float) $order->total_price;
                continue;
            }

            foreach ($items as $item) {
                $label = $item['name'] ?? ($services[$item['id']]->name ?? trans('analytics.labels.unknown_service'));
                $totals[$label] = ($totals[$label] ?? 0) + (float) ($item['price'] ?? 0);
            }
        }

        arsort($totals);

        return [
            'labels' => array_keys($totals),
            'series' => array_map(fn ($value) => round((float) $value, 2), array_values($totals)),
        ];
    }

    protected function buildFunnel(Collection $orders, Collection $uniqueClients, Collection $loyalClients): array
    {
        $createdClients = $uniqueClients->count();
        $booked = $orders->filter(fn (Order $order) => in_array($order->status, ['new', 'confirmed', 'in_progress', 'completed']))->count();
        $completed = $orders->filter(fn (Order $order) => $order->status === 'completed')->count();
        $repeat = $loyalClients->count();

        $stages = [
            [
                'key' => 'lead',
                'label' => trans('analytics.funnel.lead'),
                'value' => $createdClients,
            ],
            [
                'key' => 'booked',
                'label' => trans('analytics.funnel.booked'),
                'value' => $booked,
            ],
            [
                'key' => 'completed',
                'label' => trans('analytics.funnel.completed'),
                'value' => $completed,
            ],
            [
                'key' => 'repeat',
                'label' => trans('analytics.funnel.repeat'),
                'value' => $repeat,
            ],
        ];

        $prevValue = null;
        foreach ($stages as &$stage) {
            $value = $stage['value'];
            $stage['conversion'] = $prevValue && $prevValue > 0
                ? round(($value / $prevValue) * 100, 1)
                : 100;
            $prevValue = $value;
        }
        unset($stage);

        $overallConversion = $createdClients > 0 ? round(($repeat / $createdClients) * 100, 1) : 0;

        return [
            'stages' => $stages,
            'conversion' => $overallConversion,
        ];
    }

    protected function buildSegments(Collection $clients, Collection $orders, Carbon $start, Carbon $end): array
    {
        $now = Carbon::now();

        if ($clients->isEmpty()) {
            $clientData = $orders->groupBy('client_id')->map(function (Collection $group) {
                $last = $group->max(fn (Order $order) => $order->scheduled_at);

                return [
                    'last_visit_at' => $last,
                ];
            });
        } else {
            $clientData = $clients->map(function (Client $client) {
                return [
                    'last_visit_at' => $client->last_visit_at,
                ];
            });
        }

        $segments = [
            'new' => 0,
            'loyal' => 0,
            'inactive' => 0,
        ];

        foreach ($clientData as $client) {
            $lastVisit = $client['last_visit_at'] ?? null;
            $lastVisit = $lastVisit ? Carbon::parse($lastVisit) : null;

            if ($lastVisit === null || $lastVisit->betweenIncluded($start, $end)) {
                $segments['new'] += 1;
                continue;
            }

            if ($lastVisit->greaterThan($now->copy()->subDays(45))) {
                $segments['loyal'] += 1;
            } else {
                $segments['inactive'] += 1;
            }
        }

        return $segments;
    }

    protected function buildChurn(Collection $orders, Collection $clients, Carbon $start): array
    {
        $lastVisits = $orders->groupBy('client_id')->map(function (Collection $clientOrders) {
            /** @var Order|null $latest */
            $latest = $clientOrders->sortByDesc('scheduled_at')->first();

            return $latest ? Carbon::parse($latest->scheduled_at) : null;
        });

        $atRisk = [];
        $churned = 0;
        $threshold = $start->copy()->subDays(45);

        foreach ($lastVisits as $clientId => $lastVisit) {
            if (! $lastVisit) {
                continue;
            }

            if ($lastVisit->lessThan($threshold)) {
                $churned += 1;

                $clientName = $this->resolveClientName($clientId, $orders, $clients);
                $atRisk[] = [
                    'client_id' => $clientId,
                    'client' => $clientName,
                    'last_visit' => $lastVisit->toDateString(),
                    'days_inactive' => $lastVisit->diffInDays($start),
                ];
            }
        }

        $totalClients = max(1, $lastVisits->count());
        $churnRate = round(($churned / $totalClients) * 100, 1);

        usort($atRisk, fn ($a, $b) => $b['days_inactive'] <=> $a['days_inactive']);
        $atRisk = array_slice($atRisk, 0, 10);

        return [
            'rate' => $churnRate,
            'at_risk' => $atRisk,
        ];
    }

    protected function buildLtv(Collection $orders, Collection $services, Collection $loyalClients): array
    {
        if ($orders->isEmpty()) {
            return [
                'average' => 0,
                'top_services' => [],
            ];
        }

        $revenueByClient = $orders->groupBy('client_id')->map(fn (Collection $clientOrders) => $clientOrders->sum('total_price'));
        $average = round($revenueByClient->average(), 2);

        $topServices = $this->buildRevenueByService($orders, $services);
        $topServicesList = [];

        foreach ($topServices['labels'] as $index => $label) {
            $topServicesList[] = [
                'service' => $label,
                'value' => $topServices['series'][$index] ?? 0,
            ];
        }

        $topServicesList = array_slice($topServicesList, 0, 5);

        return [
            'average' => $average,
            'top_services' => $topServicesList,
            'loyal_clients' => $loyalClients->count(),
        ];
    }

    protected function buildInsights(array $current, array $previous, Collection $services, Collection $clients, string $compareTo): array
    {
        $currency = trans('analytics.currency');
        $financeInsights = [];
        $clientInsights = [];

        $totalRevenue = $current['totals']['revenue_total'];
        $serviceRevenue = $current['totals']['revenue_services'];
        $retailRevenue = $current['totals']['revenue_retail'];
        $averageCheck = $current['totals']['average_check'];
        $previousAverageCheck = $previous['totals']['average_check'];

        if ($totalRevenue > 0) {
            $serviceShare = $totalRevenue > 0 ? round(($serviceRevenue / $totalRevenue) * 100, 1) : 0;
            $financeInsights[] = trans('analytics.insights.service_share', [
                'service_share' => $serviceShare,
                'time_share' => 15,
            ]);
        }

        if ($averageCheck > $previousAverageCheck) {
            $growth = $this->calculateChange($averageCheck, $previousAverageCheck);
            $financeInsights[] = trans('analytics.insights.average_ticket_growth', [
                'growth' => $growth['percentage'],
                'currency' => $currency,
            ]);
        }

        $ltv = $current['ltv']['average'];
        if ($ltv > 0) {
            $clientInsights[] = trans('analytics.insights.ltv_high', [
                'ltv' => $ltv,
                'currency' => $currency,
            ]);
        }

        $churnRate = $current['churn']['rate'];
        if ($churnRate > 0) {
            $clientInsights[] = trans('analytics.insights.churn_warning', [
                'rate' => $churnRate,
            ]);
        }

        $atRisk = $current['churn']['at_risk'];
        if (! empty($atRisk)) {
            $clientInsights[] = trans('analytics.insights.birthday_offer', [
                'count' => count($atRisk),
            ]);
        }

        $forecast = $this->forecastRevenue($current['revenue_timeline']);

        $associations = $this->detectServiceAssociations($current['revenue_sources']);

        $pricing = $this->pricingRecommendation($services, $current['revenue_sources']);

        return [
            'finance' => $financeInsights,
            'clients' => $clientInsights,
            'ai' => [
                'forecast' => $forecast,
                'recommendations' => $clientInsights,
                'associations' => $associations,
                'pricing' => $pricing,
                'compare_to' => $compareTo,
            ],
        ];
    }

    protected function forecastRevenue(array $timeline): array
    {
        $series = $timeline['series'] ?? [];
        if (empty($series)) {
            return [
                'value' => 0,
                'currency' => trans('analytics.currency'),
                'confidence' => 0,
            ];
        }

        $days = count($series);
        $average = array_sum($series) / max(1, $days);
        $forecastValue = round($average * $days * 1.05, 2);

        return [
            'value' => $forecastValue,
            'currency' => trans('analytics.currency'),
            'confidence' => 72,
            'comment' => trans('analytics.insights.forecast_comment'),
        ];
    }

    protected function detectServiceAssociations(array $revenueSources): array
    {
        $labels = $revenueSources['labels'] ?? [];
        $series = $revenueSources['series'] ?? [];

        $associations = [];
        foreach ($labels as $index => $label) {
            if (($series[$index] ?? 0) <= 0) {
                continue;
            }

            if (Str::contains(mb_strtolower($label), ['маникюр', 'manicure'])) {
                $associations[] = trans('analytics.insights.manicure_to_pedicure');
            }
        }

        if (empty($associations)) {
            $associations[] = trans('analytics.insights.default_association');
        }

        return array_values(array_unique($associations));
    }

    protected function pricingRecommendation(Collection $services, array $revenueSources): array
    {
        if ($services->isEmpty()) {
            return [];
        }

        $recommendations = [];
        foreach ($services as $service) {
            if ($service->cost <= 0) {
                continue;
            }

            $margin = $service->base_price - $service->cost;
            $marginRate = $service->base_price > 0 ? round(($margin / $service->base_price) * 100, 1) : 0;

            if ($marginRate < 30) {
                $recommendations[] = trans('analytics.insights.pricing_focus', [
                    'service' => $service->name,
                ]);
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = trans('analytics.insights.pricing_balance');
        }

        return array_slice(array_values(array_unique($recommendations)), 0, 3);
    }

    protected function iteratePeriod(Carbon $start, Carbon $end, string $groupBy): array
    {
        $buckets = [];
        $current = $start->copy();

        while ($current <= $end) {
            $label = $this->resolveLabelForDate($current, $groupBy);
            $buckets[] = [
                'label' => $label,
            ];

            $current = match ($groupBy) {
                'week' => $current->copy()->addWeek(),
                'month' => $current->copy()->addMonth()->startOfMonth(),
                default => $current->copy()->addDay(),
            };
        }

        return $buckets;
    }

    protected function resolveLabelForDate(Carbon $date, string $groupBy): string
    {
        return match ($groupBy) {
            'week' => 'W' . $date->weekOfYear . ' ' . $date->format('Y'),
            'month' => $date->translatedFormat('F Y'),
            default => $date->format('d.m'),
        };
    }

    protected function resolveClientName(int $clientId, Collection $orders, Collection $clients): string
    {
        $order = $orders->firstWhere('client_id', $clientId);
        if ($order && $order->client) {
            return $order->client->name ?? trans('analytics.labels.unknown_client');
        }

        $client = $clients->get($clientId);
        if ($client) {
            return $client->name ?? trans('analytics.labels.unknown_client');
        }

        return trans('analytics.labels.unknown_client');
    }

    protected function resolveComparisonRange(Carbon $start, Carbon $end, string $compareTo): ?array
    {
        $duration = $start->diffInDays($end) + 1;

        return match ($compareTo) {
            'previous_year' => [
                'start' => $start->copy()->subYear(),
                'end' => $end->copy()->subYear(),
            ],
            'none' => null,
            default => [
                'start' => $start->copy()->subDays($duration)->startOfDay(),
                'end' => $start->copy()->subDay()->endOfDay(),
            ],
        };
    }

    protected function cacheKey(int $userId, Carbon $start, Carbon $end, string $compareTo): string
    {
        return sprintf('analytics:insights:%d:%s:%s:%s', $userId, $start->toDateString(), $end->toDateString(), $compareTo);
    }
}
