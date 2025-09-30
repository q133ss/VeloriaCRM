<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Setting;
use App\Services\DashboardAiService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardAiService $aiService,
    ) {
    }

    public function show(Request $request): View
    {
        $user = $request->user() ?? Auth::user();

        abort_unless($user, 403);

        $timezone = $user->timezone ?? config('app.timezone');
        $now = Carbon::now($timezone);
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $tomorrowStart = $todayStart->copy()->addDay();
        $tomorrowEnd = $tomorrowStart->copy()->endOfDay();
        $rangeStart = $todayStart->copy()->subDays(30);
        $rangeEnd = $tomorrowEnd->copy()->addDays(6);

        $appointments = Appointment::with('client')
            ->where('user_id', $user->id)
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd])
            ->orderBy('starts_at')
            ->get();

        $serviceIds = $appointments
            ->flatMap(fn (Appointment $appointment) => $appointment->service_ids ?? [])
            ->filter()
            ->unique()
            ->all();

        $services = $serviceIds
            ? Service::where('user_id', $user->id)->whereIn('id', $serviceIds)->get()->keyBy('id')
            : collect();

        $setting = Setting::where('user_id', $user->id)->first();

        $appointmentsToday = $appointments->filter(fn (Appointment $appt) => $this->isWithinDay($appt->starts_at, $todayStart, $todayEnd));
        $appointmentsTomorrow = $appointments->filter(fn (Appointment $appt) => $this->isWithinDay($appt->starts_at, $tomorrowStart, $tomorrowEnd));
        $pastAppointments = $appointments->filter(fn (Appointment $appt) => $appt->starts_at && $appt->starts_at->lt($todayStart));

        $todaySchedule = $appointmentsToday->map(function (Appointment $appointment) use ($services, $timezone, $pastAppointments) {
            $startsAt = $appointment->starts_at?->copy()->timezone($timezone);
            $serviceData = $this->resolveServiceData($services, $appointment->service_ids ?? []);
            $history = $pastAppointments->where('client_id', $appointment->client_id);
            $cancellations = $history->whereIn('status', ['cancelled', 'no_show'])->count();
            $indicator = $this->buildIndicator($appointment, $cancellations);

            return [
                'id' => $appointment->id,
                'time' => $startsAt?->format('H:i') ?? '—',
                'client' => $appointment->client?->name ?? '—',
                'services' => $serviceData['names'],
                'note' => $appointment->client?->notes,
                'indicator' => $indicator,
                'status' => $appointment->status,
                'risk_score' => (float) ($appointment->risk_no_show ?? 0),
                'fit_score' => (float) ($appointment->fit_score ?? 0),
                'history' => [
                    'total_visits' => $history->count(),
                    'cancellations' => $cancellations,
                ],
            ];
        })->values();

        $todayExpectedRevenue = $appointmentsToday->sum(fn (Appointment $appt) => $this->resolveServiceData($services, $appt->service_ids ?? [])['price']);
        $todayPayments = Payment::where('user_id', $user->id)
            ->whereBetween('paid_at', [$todayStart, $todayEnd])
            ->get();
        $todayRevenue = $todayPayments->sum('amount');
        $todayPaymentsCount = $todayPayments->count();

        $bookedClients = $appointmentsToday->pluck('client_id')->filter()->unique()->count();
        $capacity = $this->resolveCapacity($setting, $todayStart);

        $avgTicket = $todayPaymentsCount > 0
            ? $todayRevenue / $todayPaymentsCount
            : ($bookedClients > 0 ? $todayExpectedRevenue / max(1, $bookedClients) : 0);

        $retainedClients = $appointmentsToday
            ->pluck('client_id')
            ->filter()
            ->unique()
            ->filter(fn ($clientId) => $pastAppointments->where('client_id', $clientId)->where('status', 'completed')->isNotEmpty())
            ->count();
        $retentionRate = $bookedClients > 0 ? round(($retainedClients / $bookedClients) * 100, 1) : 0.0;

        $goal = $todayExpectedRevenue > 0 ? $todayExpectedRevenue : $this->resolveAverageRevenue($user->id, $todayStart, $timezone);
        $goal = $goal > 0 ? $goal : 0;
        $progress = $goal > 0 ? min(100, round(($todayRevenue / $goal) * 100)) : 0;

        $metrics = [
            'goal' => $goal,
            'goal_formatted' => $this->formatCurrency($goal),
            'revenue' => $todayRevenue,
            'revenue_formatted' => $this->formatCurrency($todayRevenue),
            'revenue_progress' => $progress,
            'clients_summary' => $capacity > 0
                ? sprintf('%d из %d', $appointmentsToday->count(), $capacity)
                : sprintf('%d', $appointmentsToday->count()),
            'average_ticket' => $avgTicket,
            'average_ticket_formatted' => $this->formatCurrency($avgTicket),
            'retention_rate' => $retentionRate,
            'retention_rate_formatted' => number_format($retentionRate, 1, '.', '') . '%',
        ];

        $marginData = $this->buildMarginData($appointments, $services, $todayStart, $timezone);
        $revenueTrend = $this->buildRevenueTrend($user->id, $todayStart, $todayEnd, $timezone);
        $revenueDelta = $this->percentChange(array_sum(Arr::pluck($revenueTrend, 'current')), array_sum(Arr::pluck($revenueTrend, 'previous')));

        $serviceStats = $this->buildServiceStats($appointments, $services);
        $topServices = $serviceStats->sortByDesc('margin_per_hour')->take(3)->values();
        $servicesInsight = $this->buildServiceInsight($topServices);

        $topClients = $this->resolveTopClients($user->id, $appointments, $timezone);

        $aiContext = [
            'date' => $todayStart->toDateString(),
            'timezone' => $timezone,
            'metrics' => [
                'revenue_today' => round($todayRevenue, 2),
                'goal' => round($goal, 2),
                'clients_booked' => $appointmentsToday->count(),
                'clients_capacity' => $capacity,
                'average_ticket' => round($avgTicket, 2),
                'retention_rate' => $retentionRate,
            ],
            'appointments' => $todaySchedule->map(function (array $item) use ($appointments, $timezone) {
                $clientId = $appointments
                    ->firstWhere('id', $item['id'])
                    ?->client_id;
                $upcoming = $appointments
                    ->filter(fn (Appointment $appt) => $appt->client_id === $clientId && $appt->starts_at && $appt->starts_at->isFuture())
                    ->count();

                return array_merge($item, [
                    'upcoming_visits' => $upcoming,
                ]);
            })->all(),
            'signals' => $this->buildSignals($user->id, $setting, $appointmentsTomorrow, $timezone, $appointments, $todayStart, $tomorrowStart),
            'top_services' => $topServices->map(fn (array $service) => Arr::only($service, ['id', 'name', 'margin_per_hour']))->all(),
        ];

        $aiSuggestions = $this->aiService->suggestions($user->id, $todayStart, $aiContext);
        $dailyTip = $this->aiService->dailyTip($user->id, $todayStart, [
            'metrics' => $aiContext['metrics'],
            'top_services' => $aiContext['top_services'],
            'signals' => $aiContext['signals'],
            'revenue_trend' => $revenueTrend,
        ]);

        return view('dashboard', [
            'updated_at' => $now,
            'timezone' => $timezone,
            'schedule' => $todaySchedule,
            'metrics' => $metrics,
            'aiSuggestions' => $aiSuggestions,
            'marginData' => $marginData,
            'marginInsight' => $marginData->isNotEmpty() ? $marginData->sortByDesc('value')->first() : null,
            'revenueTrend' => $revenueTrend,
            'revenueDelta' => $revenueDelta,
            'topServices' => $topServices,
            'servicesInsight' => $servicesInsight,
            'topClients' => $topClients,
            'dailyTip' => $dailyTip,
        ]);
    }

    protected function resolveCapacity(?Setting $setting, CarbonInterface $day): int
    {
        if (! $setting) {
            return 0;
        }

        $workHours = $setting->work_hours ?? [];
        if (! is_array($workHours)) {
            return 0;
        }

        $dayKey = strtolower($day->format('D'));
        $slots = $workHours[$dayKey] ?? [];

        return is_array($slots) ? count($slots) : 0;
    }

    protected function resolveAverageRevenue(int $userId, CarbonInterface $today, string $timezone): float
    {
        $rangeStart = $today->copy()->subDays(14);
        $payments = Payment::where('user_id', $userId)
            ->whereBetween('paid_at', [$rangeStart, $today])
            ->get();

        if ($payments->isEmpty()) {
            return 0.0;
        }

        $daily = $payments
            ->groupBy(fn (Payment $payment) => $payment->paid_at?->copy()->timezone($timezone)->toDateString())
            ->map(fn (Collection $group) => $group->sum('amount'));

        return round($daily->avg() ?? 0.0, 2);
    }

    protected function resolveServiceData(Collection $services, array $serviceIds): array
    {
        $selected = collect($serviceIds)
            ->map(fn ($id) => $services->get($id))
            ->filter();

        $price = $selected->sum(fn ($service) => (float) ($service->base_price ?? 0));
        $cost = $selected->sum(fn ($service) => (float) ($service->cost ?? 0));
        $duration = $selected->sum(fn ($service) => (int) ($service->duration_min ?? 60));

        return [
            'names' => $selected->pluck('name')->values()->all(),
            'price' => $price,
            'cost' => $cost,
            'duration' => $duration,
            'margin' => $price - $cost,
        ];
    }

    protected function buildIndicator(Appointment $appointment, int $cancellations): array
    {
        $risk = (float) ($appointment->risk_no_show ?? 0);
        $fit = (float) ($appointment->fit_score ?? 0);

        if ($cancellations >= 2 || $fit <= 0.5) {
            return ['type' => 'red', 'label' => '🔴 Сложный визит'];
        }

        if ($risk >= 0.3) {
            return ['type' => 'yellow', 'label' => '🟡 Риск неявки'];
        }

        return ['type' => 'green', 'label' => '🟢 Высокая явка'];
    }

    protected function buildMarginData(Collection $appointments, Collection $services, CarbonInterface $todayStart, string $timezone): Collection
    {
        $start = $todayStart->copy()->subDays(6);
        $days = collect();

        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i);
            $label = $day->locale(app()->getLocale())->translatedFormat('d MMM, EEE');
            $days->put($day->toDateString(), [
                'label' => Str::ucfirst($label),
                'margin' => 0.0,
                'hours' => 0.0,
            ]);
        }

        $appointments
            ->filter(fn (Appointment $appt) => $appt->starts_at && $appt->starts_at->between($start, $todayStart->copy()->endOfDay()))
            ->each(function (Appointment $appointment) use (&$days, $services, $timezone) {
                $date = $appointment->starts_at?->copy()->timezone($timezone)->toDateString();
                if (! $date || ! $days->has($date)) {
                    return;
                }

                $serviceData = $this->resolveServiceData($services, $appointment->service_ids ?? []);
                $hours = max(0.5, $serviceData['duration'] / 60);

                $days[$date]['margin'] += $serviceData['margin'];
                $days[$date]['hours'] += $hours;
            });

        return $days->map(function (array $item) {
            $value = $item['hours'] > 0 ? $item['margin'] / $item['hours'] : 0.0;

            return [
                'label' => $item['label'],
                'value' => round($value, 2),
                'display' => $this->formatCurrency($value),
                'hours_display' => $this->formatHours($item['hours']),
            ];
        })->values();
    }

    protected function buildRevenueTrend(int $userId, CarbonInterface $todayStart, CarbonInterface $todayEnd, string $timezone): array
    {
        $periodDays = 7;
        $currentStart = $todayStart->copy()->subDays($periodDays - 1);
        $previousEnd = $currentStart->copy()->subDay();
        $previousStart = $previousEnd->copy()->subDays($periodDays - 1);

        $payments = Payment::where('user_id', $userId)
            ->whereBetween('paid_at', [$previousStart, $todayEnd])
            ->get();

        $currentDays = collect();
        $previousDays = collect();

        for ($i = $periodDays - 1; $i >= 0; $i--) {
            $day = $currentStart->copy()->addDays($i);
            $label = Str::ucfirst($day->locale(app()->getLocale())->translatedFormat('d MMM'));
            $currentDays->push([
                'date' => $day->toDateString(),
                'label' => $label,
                'current' => 0.0,
                'previous' => 0.0,
            ]);
        }

        $currentGrouped = $payments
            ->filter(fn (Payment $payment) => $payment->paid_at && $payment->paid_at->between($currentStart, $todayEnd))
            ->groupBy(fn (Payment $payment) => $payment->paid_at?->copy()->timezone($timezone)->toDateString());

        $previousGrouped = $payments
            ->filter(fn (Payment $payment) => $payment->paid_at && $payment->paid_at->between($previousStart, $previousEnd))
            ->groupBy(fn (Payment $payment) => $payment->paid_at?->copy()->timezone($timezone)->toDateString());

        $currentDays = $currentDays->map(function (array $item) use ($currentGrouped, $previousGrouped) {
            $date = $item['date'];
            $previousDate = Carbon::parse($date)->subDays(7)->toDateString();

            return [
                'label' => $item['label'],
                'current' => round(($currentGrouped[$date] ?? collect())->sum('amount'), 2),
                'previous' => round(($previousGrouped[$previousDate] ?? collect())->sum('amount'), 2),
            ];
        });

        return $currentDays->values()->all();
    }

    protected function buildServiceStats(Collection $appointments, Collection $services): Collection
    {
        $stats = collect();

        $appointments->each(function (Appointment $appointment) use (&$stats, $services) {
            $serviceIds = $appointment->service_ids ?? [];
            foreach ($serviceIds as $serviceId) {
                $service = $services->get($serviceId);
                if (! $service) {
                    continue;
                }

                $entry = $stats->get($serviceId, [
                    'id' => $serviceId,
                    'name' => $service->name,
                    'total_margin' => 0.0,
                    'total_duration' => 0,
                    'count' => 0,
                ]);

                $entry['total_margin'] += (float) ($service->base_price ?? 0) - (float) ($service->cost ?? 0);
                $entry['total_duration'] += (int) ($service->duration_min ?? 60);
                $entry['count']++;

                $stats->put($serviceId, $entry);
            }
        });

        return $stats->map(function (array $item) {
            $hours = max(0.5, $item['total_duration'] / 60);
            $marginPerHour = $hours > 0 ? $item['total_margin'] / $hours : 0;

            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'margin_per_hour' => round($marginPerHour, 2),
                'margin_per_hour_formatted' => $this->formatCurrency($marginPerHour),
                'avg_duration' => $this->formatHours($item['total_duration'] / max(1, $item['count'])),
            ];
        });
    }

    protected function buildServiceInsight(Collection $services): ?string
    {
        if ($services->isEmpty()) {
            return null;
        }

        $top = $services->take(2)->values();
        $first = $top[0];
        $second = $top[1] ?? null;

        if ($second) {
            return sprintf(
                'ИИ: %s приносит %s за час, следом %s — %s.',
                $first['name'],
                $first['margin_per_hour_formatted'],
                $second['name'],
                $second['margin_per_hour_formatted'],
            );
        }

        return sprintf('ИИ: самая высокая маржа сейчас у услуги «%s» — %s в час.', $first['name'], $first['margin_per_hour_formatted']);
    }

    protected function resolveTopClients(int $userId, Collection $appointments, string $timezone): Collection
    {
        $clients = Client::where('user_id', $userId)->get()->keyBy('id');
        $payments = Payment::where('user_id', $userId)->get();

        $scores = collect();

        $clients->each(function (Client $client) use (&$scores, $payments, $appointments, $timezone) {
            $clientPayments = $payments->where('client_id', $client->id);
            $totalSpent = $clientPayments->sum('amount');
            $visits = $appointments->where('client_id', $client->id);
            $loyalty = $client->loyalty_level ?? 'new';
            $loyaltyWeight = match ($loyalty) {
                'ambassador' => 4.5,
                'vip' => 4.0,
                'platinum' => 3.5,
                'gold' => 3.0,
                'silver' => 2.0,
                'bronze' => 1.5,
                default => 1.2,
            };

            $score = ($totalSpent * $loyaltyWeight) + ($visits->count() * 50);
            $lastVisit = $visits->sortByDesc(fn (Appointment $appt) => $appt->starts_at)->first()?->starts_at
                ?->copy()->timezone($timezone)
                ->locale(app()->getLocale())
                ->translatedFormat('d MMMM');

            $scores->push([
                'name' => $client->name,
                'loyalty_level' => $client->loyalty_level,
                'loyalty_badge' => strtoupper($client->loyalty_level ?? 'LTV'),
                'total_spent' => $totalSpent,
                'total_spent_formatted' => $this->formatCurrency($totalSpent),
                'last_visit' => $lastVisit ?? '—',
                'score' => $score,
            ]);
        });

        return $scores->sortByDesc('score')->take(5)->values();
    }

    protected function buildSignals(int $userId, ?Setting $setting, Collection $appointmentsTomorrow, string $timezone, Collection $allAppointments, CarbonInterface $todayStart, CarbonInterface $tomorrowStart): array
    {
        $freeSlots = collect();
        if ($setting) {
            $workHours = $setting->work_hours ?? [];
            $dayKey = strtolower($tomorrowStart->format('D'));
            $slots = collect($workHours[$dayKey] ?? []);
            $booked = $appointmentsTomorrow
                ->map(fn (Appointment $appt) => $appt->starts_at?->copy()->timezone($timezone)->format('H:i'))
                ->filter();
            $freeSlots = $slots->diff($booked)->values();
        }

        $riskClients = $allAppointments
            ->filter(fn (Appointment $appt) => $appt->starts_at && $this->isWithinDay($appt->starts_at, $todayStart, $todayStart->copy()->endOfDay()))
            ->filter(fn (Appointment $appt) => (float) ($appt->risk_no_show ?? 0) >= 0.3)
            ->map(fn (Appointment $appt) => [
                'id' => $appt->client_id,
                'name' => $appt->client?->name,
            ])
            ->unique('id')
            ->values();

        $complexVisits = $allAppointments
            ->filter(fn (Appointment $appt) => $appt->starts_at && $this->isWithinDay($appt->starts_at, $todayStart, $todayStart->copy()->endOfDay()))
            ->filter(fn (Appointment $appt) => $this->buildIndicator($appt, 0)['type'] === 'red')
            ->map(fn (Appointment $appt) => [
                'client' => $appt->client?->name,
                'time' => $appt->starts_at?->copy()->timezone($timezone)->format('H:i'),
            ])
            ->values();

        $birthdays = Client::where('user_id', $userId)
            ->whereNotNull('birthday')
            ->get()
            ->filter(function (Client $client) use ($tomorrowStart) {
                return $client->birthday?->isSameAs('m-d', $tomorrowStart);
            })
            ->map(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
            ])
            ->values();

        return [
            'free_slots_tomorrow' => $freeSlots->all(),
            'high_risk_clients' => $riskClients->all(),
            'complex_visits' => $complexVisits->all(),
            'birthdays_tomorrow' => $birthdays->all(),
        ];
    }

    protected function formatCurrency(float $value): string
    {
        return number_format(max(0, $value), 0, '.', ' ') . ' ₽';
    }

    protected function formatHours(float $hours): string
    {
        $totalMinutes = (int) round($hours * 60);
        $h = intdiv($totalMinutes, 60);
        $m = $totalMinutes % 60;

        if ($h > 0 && $m > 0) {
            return sprintf('%d ч %d мин', $h, $m);
        }

        if ($h > 0) {
            return sprintf('%d ч', $h);
        }

        return sprintf('%d мин', $m);
    }

    protected function percentChange(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    protected function isWithinDay(?CarbonInterface $date, CarbonInterface $start, CarbonInterface $end): bool
    {
        if (! $date) {
            return false;
        }

        return $date->betweenIncluded($start, $end);
    }
}
