@extends('layouts.app')

@section('title', __('dashboard.title'))

@section('meta')
    <style>
        .dashboard-shell {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .dashboard-hero {
            position: relative;
            overflow: hidden;
            border: 0;
            border-radius: 1.75rem;
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.32), transparent 35%),
                linear-gradient(135deg, rgba(18, 24, 57, 0.96), rgba(41, 47, 94, 0.92));
            color: #fff;
            box-shadow: 0 1.25rem 3rem -2rem rgba(17, 24, 39, 0.7);
        }

        .dashboard-hero::after {
            content: '';
            position: absolute;
            inset: auto -10% -45% auto;
            width: 18rem;
            height: 18rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            filter: blur(8px);
        }

        .dashboard-hero .card-body {
            position: relative;
            z-index: 1;
            padding: 1.75rem;
        }

        .dashboard-kicker {
            margin-bottom: 0.5rem;
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.72);
        }

        .dashboard-hero-title {
            margin-bottom: 0.5rem;
            font-size: clamp(1.75rem, 2vw, 2.35rem);
            line-height: 1.05;
            color: #fff;
        }

        .dashboard-hero-text {
            max-width: 42rem;
            margin-bottom: 1.25rem;
            color: rgba(255, 255, 255, 0.76);
        }

        .dashboard-hero .small,
        .dashboard-hero .dashboard-meta-pill span,
        .dashboard-hero .dashboard-meta-pill strong {
            color: inherit;
        }

        .dashboard-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .dashboard-meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 0.9rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            font-size: 0.92rem;
        }

        .dashboard-meta-pill strong {
            font-size: 1rem;
        }

        .dashboard-hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .dashboard-secondary-button {
            border-color: rgba(255, 255, 255, 0.16);
            background: rgba(255, 255, 255, 0.04);
            color: rgba(255, 255, 255, 0.82);
            box-shadow: none;
        }

        .dashboard-secondary-button:hover,
        .dashboard-secondary-button:focus,
        .dashboard-secondary-button:active {
            border-color: rgba(255, 255, 255, 0.28);
            background: rgba(255, 255, 255, 0.09);
            color: #fff;
        }

        .dashboard-panel,
        .dashboard-soft-card {
            border: 0;
            border-radius: 1.5rem;
            box-shadow: 0 1.25rem 2.5rem -2rem rgba(17, 24, 39, 0.35);
        }

        .dashboard-panel .card-body,
        .dashboard-soft-card .card-body {
            padding: 1.5rem;
        }

        .dashboard-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .dashboard-panel-header p {
            margin-bottom: 0.2rem;
        }

        .dashboard-section-label {
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--bs-secondary-color);
        }

        .dashboard-section-title {
            margin-bottom: 0;
            font-size: 1.2rem;
        }

        .dashboard-priority-card {
            height: 100%;
            background:
                linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.08), rgba(var(--bs-primary-rgb), 0.03)),
                var(--bs-card-bg);
        }

        .dashboard-priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.4rem 0.7rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .dashboard-priority-badge[data-priority="urgent"] {
            background: rgba(220, 53, 69, 0.16);
            color: #dc3545;
        }

        .dashboard-priority-badge[data-priority="high"] {
            background: rgba(var(--bs-primary-rgb), 0.16);
            color: var(--bs-primary);
        }

        .dashboard-priority-badge[data-priority="normal"] {
            background: rgba(var(--bs-secondary-rgb), 0.12);
            color: var(--bs-secondary-color);
        }

        .dashboard-secondary-list {
            display: grid;
            gap: 0.75rem;
            margin-top: 1.25rem;
        }

        .dashboard-secondary-item {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
            padding-top: 0.75rem;
            border-top: 1px solid rgba(var(--bs-border-color-rgb), 0.7);
        }

        .dashboard-secondary-dot {
            flex: 0 0 auto;
            width: 0.55rem;
            height: 0.55rem;
            margin-top: 0.45rem;
            border-radius: 999px;
            background: var(--bs-primary);
        }

        .dashboard-agenda {
            display: grid;
            gap: 1rem;
        }

        .dashboard-agenda-item {
            display: grid;
            grid-template-columns: 4.75rem minmax(0, 1fr);
            gap: 1rem;
            padding: 1rem;
            border-radius: 1.25rem;
            background: rgba(var(--bs-body-color-rgb), 0.025);
        }

        .dashboard-agenda-time {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 4.5rem;
            border-radius: 1rem;
            background: rgba(var(--bs-primary-rgb), 0.1);
            color: var(--bs-primary);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .dashboard-agenda-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .dashboard-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            background: rgba(var(--bs-secondary-rgb), 0.08);
            color: var(--bs-body-color);
            font-size: 0.8rem;
            font-weight: 500;
        }

        .dashboard-chip[data-type="green"] {
            color: #146c43;
            background: rgba(25, 135, 84, 0.14);
        }

        .dashboard-chip[data-type="yellow"] {
            color: #997404;
            background: rgba(255, 193, 7, 0.18);
        }

        .dashboard-chip[data-type="red"] {
            color: #b02a37;
            background: rgba(220, 53, 69, 0.16);
        }

        .dashboard-agenda-note {
            margin-top: 0.4rem;
            color: var(--bs-secondary-color);
        }

        .dashboard-agenda-empty {
            padding: 2rem 1.25rem;
            border-radius: 1.25rem;
            text-align: center;
            background: rgba(var(--bs-body-color-rgb), 0.025);
            color: var(--bs-secondary-color);
        }

        .dashboard-stat-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.9rem;
        }

        .dashboard-stat-card {
            padding: 1rem;
            border-radius: 1.2rem;
            background: rgba(var(--bs-body-color-rgb), 0.025);
        }

        .dashboard-stat-card p {
            margin-bottom: 0.35rem;
            color: var(--bs-secondary-color);
            font-size: 0.84rem;
        }

        .dashboard-stat-card h3 {
            margin-bottom: 0;
            font-size: 1.35rem;
        }

        .dashboard-insight-stack {
            display: grid;
            gap: 0.9rem;
        }

        .dashboard-revenue-list,
        .dashboard-top-list {
            display: grid;
            gap: 0.8rem;
        }

        .dashboard-revenue-row,
        .dashboard-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.9rem 1rem;
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb), 0.025);
        }

        .dashboard-trend {
            font-size: 0.82rem;
            color: var(--bs-secondary-color);
        }

        .dashboard-mini-note {
            padding: 1rem;
            border-radius: 1rem;
            background: rgba(var(--bs-primary-rgb), 0.08);
        }

        .dashboard-learning {
            border: 0;
            border-radius: 1.5rem;
            background:
                linear-gradient(135deg, rgba(255, 0, 153, 0.12), rgba(255, 255, 255, 0)),
                var(--bs-card-bg);
        }

        [data-bs-theme="dark"] .dashboard-soft-card .dashboard-secondary-button,
        [data-bs-theme="dark"] .dashboard-panel .dashboard-secondary-button {
            border-color: rgba(168, 139, 250, 0.24);
            background: rgba(168, 139, 250, 0.08);
            color: #d8ccff;
        }

        [data-bs-theme="dark"] .dashboard-soft-card .dashboard-secondary-button:hover,
        [data-bs-theme="dark"] .dashboard-panel .dashboard-secondary-button:hover,
        [data-bs-theme="dark"] .dashboard-soft-card .dashboard-secondary-button:focus,
        [data-bs-theme="dark"] .dashboard-panel .dashboard-secondary-button:focus,
        [data-bs-theme="dark"] .dashboard-soft-card .dashboard-secondary-button:active,
        [data-bs-theme="dark"] .dashboard-panel .dashboard-secondary-button:active {
            border-color: rgba(244, 114, 182, 0.36);
            background: rgba(244, 114, 182, 0.12);
            color: #fff;
        }

        [data-bs-theme="light"] .dashboard-hero {
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.18), transparent 28%),
                linear-gradient(135deg, #1e2a63, #4d238a 55%, #7f2dbd);
            box-shadow: 0 1.5rem 3rem -2rem rgba(49, 46, 129, 0.45);
        }

        [data-bs-theme="light"] .dashboard-hero::after {
            background: rgba(255, 255, 255, 0.14);
        }

        [data-bs-theme="light"] .dashboard-hero .dashboard-meta-pill {
            background: rgba(255, 255, 255, 0.16);
        }

        [data-bs-theme="light"] .dashboard-hero .btn-outline-light {
            border-color: rgba(255, 255, 255, 0.36);
            background: rgba(255, 255, 255, 0.06);
            color: #fff;
        }

        [data-bs-theme="light"] .dashboard-hero .btn-outline-light:hover {
            border-color: rgba(255, 255, 255, 0.56);
            background: rgba(255, 255, 255, 0.14);
            color: #fff;
        }

        [data-bs-theme="light"] .dashboard-soft-card .dashboard-secondary-button,
        [data-bs-theme="light"] .dashboard-panel .dashboard-secondary-button {
            border-color: rgba(99, 102, 241, 0.22);
            background: rgba(99, 102, 241, 0.04);
            color: #5b5fc7;
        }

        [data-bs-theme="light"] .dashboard-soft-card .dashboard-secondary-button:hover,
        [data-bs-theme="light"] .dashboard-panel .dashboard-secondary-button:hover,
        [data-bs-theme="light"] .dashboard-soft-card .dashboard-secondary-button:focus,
        [data-bs-theme="light"] .dashboard-panel .dashboard-secondary-button:focus,
        [data-bs-theme="light"] .dashboard-soft-card .dashboard-secondary-button:active,
        [data-bs-theme="light"] .dashboard-panel .dashboard-secondary-button:active {
            border-color: rgba(236, 72, 153, 0.28);
            background: rgba(236, 72, 153, 0.06);
            color: #8b2c6d;
        }

        @media (max-width: 991.98px) {
            .dashboard-hero .card-body,
            .dashboard-panel .card-body,
            .dashboard-soft-card .card-body {
                padding: 1.25rem;
            }
        }

        @media (max-width: 575.98px) {
            .dashboard-agenda-item {
                grid-template-columns: 1fr;
            }

            .dashboard-stat-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-panel-header {
                flex-direction: column;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $formatServices = static fn (array $services): string => collect($services)->filter()->implode(', ');
        $priorityLabels = trans('dashboard.sections.focus.ai.priority');
        $primarySuggestion = collect($aiSuggestions)->first();
        $secondarySuggestions = collect($aiSuggestions)->slice(1, 2);
        $todayCount = count($schedule);
        $topService = $topServices->first();
        $trendPreview = collect($revenueTrend)->take(3);
        $greetingText = $todayCount > 0
            ? __('dashboard.sections.focus.schedule.subtitle')
            : __('dashboard.sections.focus.schedule.empty');
    @endphp

    <div class="dashboard-shell">
        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="card dashboard-hero h-100">
                    <div class="card-body d-flex flex-column justify-content-between h-100">
                        <div>
                            <p class="dashboard-kicker">{{ __('dashboard.sections.focus.label') }}</p>
                            <h1 class="dashboard-hero-title">{{ __('dashboard.sections.focus.title') }}</h1>
                            <p class="dashboard-hero-text">{{ $greetingText }}</p>
                        </div>

                        <div class="dashboard-hero-meta">
                            <span class="dashboard-meta-pill">
                                <span>{{ __('dashboard.sections.focus.metrics.clients.label') }}</span>
                                <strong>{{ $metrics['clients_summary'] }}</strong>
                            </span>
                            <span class="dashboard-meta-pill">
                                <span>{{ __('dashboard.sections.focus.metrics.revenue.label') }}</span>
                                <strong>{{ $metrics['revenue_formatted'] }}</strong>
                            </span>
                            <span class="dashboard-meta-pill">
                                <span>{{ __('dashboard.sections.focus.metrics.forecast_pill', ['amount' => $metrics['forecast_profit_formatted']]) }}</span>
                            </span>
                        </div>

                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
                            <div class="small text-white-50">
                                {{ __('dashboard.sections.focus.updated', ['time' => $updated_at->copy()->locale(app()->getLocale())->diffForHumans()]) }}
                            </div>
                            <div class="dashboard-hero-actions">
                                <a href="{{ route('orders.create') }}" class="btn btn-primary">
                                    {{ __('dashboard.sections.focus.schedule.quick_book') }}
                                </a>
                                <a href="{{ route('calendar') }}" class="btn dashboard-secondary-button">
                                    {{ __('dashboard.sections.focus.schedule.title') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card dashboard-panel dashboard-priority-card h-100">
                    <div class="card-body">
                        <div class="dashboard-panel-header">
                            <div>
                                <p class="dashboard-section-label">{{ __('dashboard.sections.focus.ai.badge') }}</p>
                                <h2 class="dashboard-section-title">{{ __('dashboard.sections.focus.ai.title') }}</h2>
                            </div>
                            @if ($primarySuggestion)
                                @php $priority = $primarySuggestion['priority'] ?? 'normal'; @endphp
                                <span class="dashboard-priority-badge" data-priority="{{ $priority }}">
                                    {{ $priorityLabels[$priority] ?? \Illuminate\Support\Str::title($priority) }}
                                </span>
                            @endif
                        </div>

                        @if ($primarySuggestion)
                            <h3 class="h5 mb-2">{{ $primarySuggestion['title'] }}</h3>
                            <p class="text-muted mb-0">{{ $primarySuggestion['description'] }}</p>
                        @else
                            <h3 class="h5 mb-2">{{ __('dashboard.sections.focus.ai.title') }}</h3>
                            <p class="text-muted mb-0">{{ __('dashboard.sections.focus.ai.empty') }}</p>
                        @endif

                        <div class="dashboard-hero-actions mt-4">
                            <a href="{{ route('clients.index') }}" class="btn btn-primary">
                                {{ __('dashboard.sections.focus.ai.fallback_action') }}
                            </a>
                            <a href="{{ route('analytics') }}" class="btn dashboard-secondary-button">
                                {{ __('dashboard.sections.finance.cta') }}
                            </a>
                        </div>

                        @if ($secondarySuggestions->isNotEmpty())
                            <div class="dashboard-secondary-list">
                                @foreach ($secondarySuggestions as $suggestion)
                                    <div class="dashboard-secondary-item">
                                        <span class="dashboard-secondary-dot"></span>
                                        <div>
                                            <div class="fw-semibold">{{ $suggestion['title'] }}</div>
                                            <div class="small text-muted">{{ $suggestion['description'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-7">
                <div class="card dashboard-panel h-100">
                    <div class="card-body">
                        <div class="dashboard-panel-header">
                            <div>
                                <p class="dashboard-section-label">{{ __('dashboard.sections.focus.schedule.subtitle') }}</p>
                                <h2 class="dashboard-section-title">{{ __('dashboard.sections.focus.schedule.title') }}</h2>
                            </div>
                            <a href="{{ route('calendar') }}" class="btn dashboard-secondary-button btn-sm">
                                {{ __('dashboard.sections.focus.schedule.title') }}
                            </a>
                        </div>

                        <div class="dashboard-agenda">
                            @forelse ($schedule as $appointment)
                                <div class="dashboard-agenda-item">
                                    <div class="dashboard-agenda-time">{{ $appointment['time'] }}</div>
                                    <div>
                                        <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-start">
                                            <div>
                                                <div class="h5 mb-1">{{ $appointment['client'] }}</div>
                                                <div class="text-muted">{{ $formatServices($appointment['services']) ?: '-' }}</div>
                                            </div>
                                            <span class="dashboard-chip" data-type="{{ $appointment['indicator']['type'] }}">
                                                {{ $appointment['indicator']['label'] }}
                                            </span>
                                        </div>

                                        @if (! empty($appointment['note']))
                                            <div class="dashboard-agenda-note small">
                                                {{ \Illuminate\Support\Str::limit($appointment['note'], 110) }}
                                            </div>
                                        @endif

                                        <div class="dashboard-agenda-meta">
                                            <span class="dashboard-chip">
                                                {{ __('dashboard.sections.focus.metrics.clients.description') }}: {{ $appointment['history']['total_visits'] }}
                                            </span>
                                            @if (($appointment['history']['cancellations'] ?? 0) > 0)
                                                <span class="dashboard-chip">
                                                    Cancelled: {{ $appointment['history']['cancellations'] }}
                                                </span>
                                            @endif
                                            <a href="{{ route('clients.index') }}" class="dashboard-chip text-decoration-none">
                                                {{ __('dashboard.sections.focus.ai.fallback_action') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="dashboard-agenda-empty">
                                    {{ __('dashboard.sections.focus.schedule.empty') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="d-flex flex-column gap-4 h-100">
                    <div class="card dashboard-soft-card">
                        <div class="card-body">
                            <div class="dashboard-panel-header">
                                <div>
                                    <p class="dashboard-section-label">{{ __('dashboard.sections.focus.metrics.title') }}</p>
                                    <h2 class="dashboard-section-title">{{ __('dashboard.sections.focus.metrics.title') }}</h2>
                                </div>
                            </div>

                            <div class="dashboard-stat-grid">
                                <div class="dashboard-stat-card">
                                    <p>{{ __('dashboard.sections.focus.metrics.revenue.label') }}</p>
                                    <h3>{{ $metrics['revenue_formatted'] }}</h3>
                                </div>
                                <div class="dashboard-stat-card">
                                    <p>{{ __('dashboard.sections.focus.metrics.clients.label') }}</p>
                                    <h3>{{ $metrics['clients_summary'] }}</h3>
                                </div>
                                <div class="dashboard-stat-card">
                                    <p>{{ __('dashboard.sections.focus.metrics.avg_ticket.label') }}</p>
                                    <h3>{{ $metrics['average_ticket_formatted'] }}</h3>
                                </div>
                                <div class="dashboard-stat-card">
                                    <p>{{ __('dashboard.sections.focus.metrics.retention.label') }}</p>
                                    <h3>{{ $metrics['retention_rate_formatted'] }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card dashboard-soft-card flex-grow-1">
                        <div class="card-body">
                            <div class="dashboard-panel-header">
                                <div>
                                    <p class="dashboard-section-label">{{ __('dashboard.sections.finance.label') }}</p>
                                    <h2 class="dashboard-section-title">{{ __('dashboard.sections.finance.title') }}</h2>
                                </div>
                                <a href="{{ route('analytics') }}" class="btn dashboard-secondary-button btn-sm">
                                    {{ __('dashboard.sections.finance.cta') }}
                                </a>
                            </div>

                            <div class="dashboard-insight-stack">
                                <div class="dashboard-mini-note">
                                    <div class="small text-muted mb-1">{{ __('dashboard.sections.finance.margin.title') }}</div>
                                    <div class="fw-semibold">
                                        @if ($marginInsight)
                                            {{ __('dashboard.sections.finance.margin.best_day', ['day' => $marginInsight['label'], 'value' => $marginInsight['display']]) }}
                                        @else
                                            {{ __('dashboard.messages.not_enough_data') }}
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <div class="small text-muted mb-2">{{ __('dashboard.sections.finance.services.title') }}</div>
                                    <div class="dashboard-top-list">
                                        @forelse ($topServices->take(3) as $service)
                                            <div class="dashboard-top-row">
                                                <div>
                                                    <div class="fw-semibold">{{ $service['name'] }}</div>
                                                    <div class="small text-muted">
                                                        {{ __('dashboard.sections.finance.services.avg_duration', ['value' => $service['avg_duration']]) }}
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <div class="fw-semibold">{{ $service['margin_per_hour_formatted'] }}</div>
                                                    <div class="small text-muted">{{ __('dashboard.sections.finance.services.per_hour') }}</div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-muted">{{ __('dashboard.sections.finance.services.empty') }}</div>
                                        @endforelse
                                    </div>
                                </div>

                                <div>
                                    <div class="small text-muted mb-2">{{ __('dashboard.sections.finance.revenue.title') }}</div>
                                    <div class="dashboard-revenue-list">
                                        @forelse ($trendPreview as $item)
                                            @php
                                                $delta = $item['previous'] > 0 ? (($item['current'] - $item['previous']) / max($item['previous'], 1)) * 100 : null;
                                            @endphp
                                            <div class="dashboard-revenue-row">
                                                <div>
                                                    <div class="fw-semibold">{{ $item['label'] }}</div>
                                                    <div class="dashboard-trend">
                                                        @if ($delta === null)
                                                            {{ __('dashboard.messages.no_comparison') }}
                                                        @elseif ($delta >= 0)
                                                            {{ __('dashboard.sections.finance.revenue.growth', ['value' => number_format(abs($delta), 1, '.', '')]) }}
                                                        @else
                                                            {{ __('dashboard.sections.finance.revenue.decline', ['value' => number_format(abs($delta), 1, '.', '')]) }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="fw-semibold">{{ number_format($item['current'], 0, '.', ' ') }} ₽</div>
                                            </div>
                                        @empty
                                            <div class="text-muted">{{ __('dashboard.messages.not_enough_data') }}</div>
                                        @endforelse
                                    </div>
                                </div>

                                @if ($topService || $servicesInsight)
                                    <div class="small text-muted">
                                        {{ $servicesInsight ?? __('dashboard.sections.finance.services.empty_insight') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card dashboard-learning">
            <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <p class="dashboard-section-label">{{ __('dashboard.sections.learning.label') }}</p>
                    <h2 class="dashboard-section-title mb-2">{{ __('dashboard.sections.learning.title') }}</h2>
                    <p class="mb-0">{{ $dailyTip['text'] ?? __('dashboard.sections.learning.fallback') }}</p>
                </div>
                <div class="text-lg-end">
                    <a href="{{ route('learning') }}" class="btn btn-primary">
                        {{ __('dashboard.sections.learning.button') }}
                    </a>
                    <div class="small text-muted mt-2">
                        {{ __('dashboard.sections.learning.source', ['value' => $dailyTip['source'] ?? __('dashboard.sections.learning.default_source')]) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
