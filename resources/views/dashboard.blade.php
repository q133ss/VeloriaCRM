@extends('layouts.app')

@section('title', 'Дашборд')

@section('meta')
    <style>
        .dashboard-section + .dashboard-section {
            margin-top: 2.5rem;
        }

        .dashboard-card-action {
            border-left: 3px solid transparent;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .dashboard-card-action:hover {
            border-color: var(--bs-primary);
            box-shadow: 0 0.75rem 1rem -0.75rem rgba(58, 53, 65, 0.5);
        }

        .dashboard-timeline {
            position: relative;
            padding-left: 1.5rem;
        }

        .dashboard-timeline::before {
            content: '';
            position: absolute;
            left: 0.6rem;
            top: 0.5rem;
            bottom: 0.5rem;
            width: 2px;
            border-radius: 999px;
            background: var(--bs-border-color, #e9ecef);
        }

        .dashboard-timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }

        .dashboard-timeline-item:last-child {
            padding-bottom: 0;
        }

        .dashboard-timeline-dot {
            position: absolute;
            left: -1.5rem;
            top: 0.1rem;
            width: 1.4rem;
            height: 1.4rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.75rem;
            box-shadow: 0 0 0 3px var(--bs-body-bg, #fff);
        }

        .dashboard-bar {
            position: relative;
            background: var(--bs-light, #f5f5f9);
            border-radius: 999px;
            overflow: hidden;
            height: 0.75rem;
        }

        .dashboard-bar-fill {
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            border-radius: inherit;
            background: var(--bs-primary);
        }

        .dashboard-bar-wrapper {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 0.75rem;
        }

        .dashboard-metric-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.875rem;
            border-radius: 999px;
            padding: 0.35rem 0.75rem;
            background: var(--bs-light, #f5f5f9);
        }

        .dashboard-indicator {
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.8125rem;
        }

        .dashboard-indicator[data-type="green"] {
            color: #0f5132;
            background: rgba(25, 135, 84, 0.12);
        }

        .dashboard-indicator[data-type="yellow"] {
            color: #664d03;
            background: rgba(255, 193, 7, 0.18);
        }

        .dashboard-indicator[data-type="red"] {
            color: #842029;
            background: rgba(220, 53, 69, 0.14);
        }

        @media (min-width: 1200px) {
            .dashboard-sticky-notes {
                position: sticky;
                top: 5.5rem;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $formatServices = static fn (array $services): string => collect($services)->filter()->implode(', ');
        $maxMarginValue = $marginData->max('value') ?? 0;
        $priorityStyles = [
            'urgent' => ['badge' => 'bg-label-danger', 'button' => 'btn-danger'],
            'high' => ['badge' => 'bg-label-primary', 'button' => 'btn-primary'],
            'normal' => ['badge' => 'bg-label-secondary', 'button' => 'btn-outline-primary'],
        ];
    @endphp

    <div class="dashboard-section">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-4">
            <div>
                <p class="text-uppercase text-muted fw-medium mb-1 small">Главный экран</p>
                <h4 class="mb-0">Фокус на сегодня</h4>
            </div>
            <div class="text-lg-end small text-muted">
                Обновлено {{ $updated_at->copy()->locale(app()->getLocale())->diffForHumans() }}
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-7 d-flex flex-column gap-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                            <div>
                                <h5 class="mb-1">Расписание на сегодня</h5>
                                <p class="text-muted mb-0">Следите за ключевыми визитами и сигналами от ИИ</p>
                            </div>
                            <button type="button" class="btn btn-primary" data-action="quick-book">Быстрая запись</button>
                        </div>

                        <div class="dashboard-timeline">
                            @forelse ($schedule as $appointment)
                                <div class="dashboard-timeline-item">
                                    <div class="dashboard-timeline-dot bg-primary-subtle text-primary fw-semibold">
                                        {{ $loop->iteration }}
                                    </div>
                                    <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 gap-sm-3">
                                        <div class="flex-grow-1">
                                            <div class="d-flex flex-column flex-sm-row flex-sm-wrap gap-2 align-items-sm-center">
                                                <span class="fw-semibold fs-6">{{ $appointment['time'] }}</span>
                                                <span class="fw-semibold">{{ $appointment['client'] }}</span>
                                                <span class="text-muted">{{ $formatServices($appointment['services']) }}</span>
                                            </div>
                                            @if (! empty($appointment['note']))
                                                <p class="mb-1 small text-muted mt-1">{{ $appointment['note'] }}</p>
                                            @endif
                                            <div class="d-flex flex-wrap gap-2">
                                                <span class="dashboard-indicator" data-type="{{ $appointment['indicator']['type'] }}">
                                                    {{ $appointment['indicator']['label'] }}
                                                </span>
                                                <button class="btn btn-sm btn-outline-secondary" type="button">Напомнить</button>
                                                <button class="btn btn-sm btn-outline-primary" type="button">Открыть карточку</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted text-center py-4">
                                    Сегодня нет запланированных визитов — самое время заняться привлечением клиентов.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                            <h5 class="mb-0">Сегодня в цифрах</h5>
                            <span class="dashboard-metric-pill">
                                Цель дня — <span class="fw-semibold">{{ $metrics['goal_formatted'] }}</span>
                            </span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <div class="border rounded-2 p-3 h-100">
                                    <p class="text-muted mb-1 small">Выручка</p>
                                    <h4 class="mb-1">{{ $metrics['revenue_formatted'] }}</h4>
                                    <p class="mb-0 small text-muted">Факт против цели</p>
                                    <div class="progress mt-2" style="height: 0.5rem;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $metrics['revenue_progress'] }}%;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="border rounded-2 p-3 h-100">
                                    <p class="text-muted mb-1 small">Клиенты сегодня</p>
                                    <h4 class="mb-1">{{ $metrics['clients_summary'] }}</h4>
                                    <p class="mb-0 small text-muted">Записано клиентов</p>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="border rounded-2 p-3 h-100">
                                    <p class="text-muted mb-1 small">Средний чек</p>
                                    <h4 class="mb-1">{{ $metrics['average_ticket_formatted'] }}</h4>
                                    <p class="mb-0 small text-muted">Чистая выручка за визит</p>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="border rounded-2 p-3 h-100">
                                    <p class="text-muted mb-1 small">Повторные визиты</p>
                                    <h4 class="mb-1">{{ $metrics['retention_rate_formatted'] }}</h4>
                                    <p class="mb-0 small text-muted">Доля клиентов, вернувшихся</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="card dashboard-sticky-notes">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div>
                                <h5 class="mb-1">Советы ИИ-ассистента</h5>
                                <p class="text-muted mb-0">Что можно сделать прямо сейчас</p>
                            </div>
                            <span class="badge bg-label-primary text-uppercase">В приоритете</span>
                        </div>
                        <div class="d-flex flex-column gap-3">
                            @forelse ($aiSuggestions as $suggestion)
                                @php
                                    $priority = $suggestion['priority'] ?? 'normal';
                                    $styles = $priorityStyles[$priority] ?? $priorityStyles['normal'];
                                    $actions = collect($suggestion['actions'] ?? []);
                                @endphp
                                <div class="border rounded-2 p-3 dashboard-card-action">
                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                        <p class="fw-semibold mb-0">{{ $suggestion['title'] }}</p>
                                        <span class="badge {{ $styles['badge'] }} text-uppercase">{{ \Illuminate\Support\Str::title($priority) }}</span>
                                    </div>
                                    <p class="text-muted mb-3">{{ $suggestion['description'] }}</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        @if ($actions->isNotEmpty())
                                            @foreach ($actions as $index => $action)
                                                <button class="btn btn-sm {{ $index === 0 ? $styles['button'] : 'btn-outline-secondary' }}" type="button">
                                                    {{ $action }}
                                                </button>
                                            @endforeach
                                        @else
                                            <button class="btn btn-sm {{ $styles['button'] }}" type="button">Перейти к клиентам</button>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted text-center py-4">
                                    Пока нет рекомендаций — данные обновятся после новых записей и платежей.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-4">
            <div>
                <p class="text-uppercase text-muted fw-medium mb-1 small">Аналитика роста</p>
                <h4 class="mb-0">Финансы и эффективность</h4>
            </div>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('analytics') }}">Открыть полную аналитику</a>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-7 d-flex flex-column gap-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                            <div>
                                <h5 class="mb-1">Маржа/час</h5>
                                <p class="text-muted mb-0">В какие дни работа приносит максимум</p>
                            </div>
                            @if ($marginInsight)
                                <span class="badge bg-label-success">Лучший день: {{ $marginInsight['label'] }} — {{ $marginInsight['display'] }}</span>
                            @else
                                <span class="badge bg-label-secondary">Недостаточно данных</span>
                            @endif
                        </div>
                        <div class="d-flex flex-column gap-3">
                            @forelse ($marginData as $item)
                                @php
                                    $ratio = $maxMarginValue > 0 ? ($item['value'] / $maxMarginValue) * 100 : 0;
                                @endphp
                                <div class="border rounded-2 p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-semibold">{{ $item['label'] }}</span>
                                        <span class="small text-muted">{{ $item['hours_display'] }}</span>
                                    </div>
                                    <div class="dashboard-bar-wrapper">
                                        <div class="dashboard-bar">
                                            <div class="dashboard-bar-fill" style="width: {{ number_format($ratio, 1, '.', '') }}%;"></div>
                                        </div>
                                        <span class="fw-semibold">{{ $item['display'] }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="d-flex justify-content-center text-muted">Недостаточно данных</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                            <div>
                                <h5 class="mb-1">Выручка за период</h5>
                                <p class="text-muted mb-0">Сравнение с прошлым периодом</p>
                            </div>
                            <span class="dashboard-metric-pill">
                                @if ($revenueDelta === null)
                                    Нет данных для сравнения
                                @else
                                    VS прошлый период: {{ ($revenueDelta > 0 ? '+' : '') . number_format($revenueDelta, 1, '.', '') }}%
                                @endif
                            </span>
                        </div>
                        <div class="d-flex flex-column gap-3">
                            @forelse ($revenueTrend as $item)
                                @php
                                    $delta = $item['previous'] > 0 ? (($item['current'] - $item['previous']) / max($item['previous'], 1)) * 100 : null;
                                @endphp
                                <div class="border rounded-2 p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-semibold">{{ $item['label'] }}</span>
                                        <span class="small text-muted">{{ number_format($item['current'], 0, '.', ' ') }} ₽</span>
                                    </div>
                                    <p class="small mb-0 text-muted">
                                        @if ($delta === null)
                                            Нет данных для сравнения
                                        @else
                                            {{ $delta >= 0 ? 'Рост' : 'Падение' }} {{ number_format(abs($delta), 1, '.', '') }}% vs прошлый период
                                        @endif
                                    </p>
                                </div>
                            @empty
                                <div class="d-flex justify-content-center text-muted">Недостаточно данных</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5 d-flex flex-column gap-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">Топ-3 маржинальных услуг</h5>
                        <ul class="list-unstyled mb-0">
                            @forelse ($topServices as $service)
                                <li class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <div class="fw-semibold">{{ $service['name'] }}</div>
                                        <div class="small text-muted">Средняя длительность: {{ $service['avg_duration'] }}</div>
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-semibold">{{ $service['margin_per_hour_formatted'] }}</span>
                                        <div class="small text-muted">₽/час</div>
                                    </div>
                                </li>
                            @empty
                                <li class="text-muted">Пока нет данных по услугам</li>
                            @endforelse
                        </ul>
                        <p class="small text-muted mt-3">
                            {{ $servicesInsight ?? 'Когда появятся новые продажи, мы подсветим самые выгодные услуги.' }}
                        </p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">Лучшие клиенты</h5>
                        <ul class="list-unstyled mb-0">
                            @forelse ($topClients as $client)
                                <li class="border rounded-2 p-3 mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold">{{ $client['name'] }}</span>
                                        <span class="badge bg-label-info">{{ $client['loyalty_badge'] }}</span>
                                    </div>
                                    <p class="small text-muted mb-1">LTV: {{ $client['total_spent_formatted'] }}</p>
                                    <p class="small text-muted mb-0">Последний визит: {{ $client['last_visit'] }}</p>
                                </li>
                            @empty
                                <li class="text-muted">Пока нет рекомендованных клиентов</li>
                            @endforelse
                        </ul>
                        <p class="small text-muted mt-3">Отмечаем тех, кто чаще рекомендует и возвращается.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section">
        <div class="card">
            <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
                <div>
                    <p class="text-uppercase text-muted fw-medium mb-1 small">Микро-обучение и тренды</p>
                    <h4 class="mb-2">Совет дня от Veloria</h4>
                    <p class="mb-0">{{ $dailyTip['text'] ?? 'Следите за обновлениями — скоро здесь появится персональный совет от Veloria.' }}</p>
                </div>
                <div class="text-lg-end">
                    <button class="btn btn-primary" type="button">Подробнее</button>
                    <p class="small text-muted mb-0 mt-2">Источник: {{ $dailyTip['source'] ?? 'Veloria AI ассистент' }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
