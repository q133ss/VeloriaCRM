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
        $todayAppointments = [
            [
                'time' => '09:00',
                'client' => 'Мария Петрова',
                'service' => 'Наращивание ресниц',
                'note' => 'Любит классический изгиб, попросила напомнить про уход',
                'indicator' => ['type' => 'green', 'label' => '🟢 Высокая явка'],
            ],
            [
                'time' => '11:30',
                'client' => 'Анна Смирнова',
                'service' => 'Ламинирование бровей',
                'note' => 'В прошлый раз опаздывала на 15 минут',
                'indicator' => ['type' => 'yellow', 'label' => '🟡 Риск неявки'],
            ],
            [
                'time' => '14:00',
                'client' => 'Ольга Иванова',
                'service' => 'Чистка + маска «стеклянная кожа»',
                'note' => 'Завтра День рождения, ждет рекомендации по подарку',
                'indicator' => ['type' => 'green', 'label' => '🟢 Высокая явка'],
            ],
            [
                'time' => '16:30',
                'client' => 'Елена Котова',
                'service' => 'Коррекция бровей и окрашивание',
                'note' => 'Просила подготовить новую палитру оттенков',
                'indicator' => ['type' => 'red', 'label' => '🔴 Сложный визит'],
            ],
        ];
    @endphp

    <div class="dashboard-section">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-4">
            <div>
                <p class="text-uppercase text-muted fw-medium mb-1 small">Главный экран</p>
                <h4 class="mb-0">Фокус на сегодня</h4>
            </div>
            <div class="text-lg-end small text-muted">
                Обновлено <span id="dashboard-updated-at">только что</span>
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
                            @foreach ($todayAppointments as $appointment)
                                <div class="dashboard-timeline-item">
                                    <div class="dashboard-timeline-dot bg-primary-subtle text-primary fw-semibold">
                                        {{ $loop->iteration }}
                                    </div>
                                    <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 gap-sm-3">
                                        <div class="flex-grow-1">
                                            <div class="d-flex flex-column flex-sm-row flex-sm-wrap gap-2 align-items-sm-center">
                                                <span class="fw-semibold fs-6">{{ $appointment['time'] }}</span>
                                                <span class="fw-semibold">{{ $appointment['client'] }}</span>
                                                <span class="text-muted">{{ $appointment['service'] }}</span>
                                            </div>
                                            <p class="mb-1 small text-muted mt-1">{{ $appointment['note'] }}</p>
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
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                            <h5 class="mb-0">Сегодня в цифрах</h5>
                            <span class="dashboard-metric-pill">
                                Цель дня — <span class="fw-semibold" data-dashboard-goal>8 000 ₽</span>
                            </span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <div class="border rounded-2 p-3 h-100">
                                    <p class="text-muted mb-1 small">Выручка</p>
                                    <h4 class="mb-1" data-dashboard-revenue>—</h4>
                                    <p class="mb-0 small text-muted">Факт против цели</p>
                                    <div class="progress mt-2" style="height: 0.5rem;">
                                        <div class="progress-bar" role="progressbar" style="width: 0%;" data-dashboard-revenue-progress></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="border rounded-2 p-3 h-100">
                                    <p class="text-muted mb-1 small">Клиенты сегодня</p>
                                    <h4 class="mb-1" data-dashboard-clients>—</h4>
                                    <p class="mb-0 small text-muted">Записано клиентов</p>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="border rounded-2 p-3 h-100">
                                    <p class="text-muted mb-1 small">Средний чек</p>
                                    <h4 class="mb-1" data-dashboard-average>—</h4>
                                    <p class="mb-0 small text-muted">Чистая выручка за визит</p>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="border rounded-2 p-3 h-100">
                                    <p class="text-muted mb-1 small">Повторные визиты</p>
                                    <h4 class="mb-1" data-dashboard-retention>—</h4>
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
                        <div class="d-flex flex-column gap-3" data-dashboard-ai-suggestions>
                            <div class="border rounded-2 p-3 dashboard-card-action">
                                <p class="fw-semibold mb-2">У вас 2 свободных слота завтра.</p>
                                <p class="text-muted mb-3">Предложите Марии запись на коррекцию ресниц.</p>
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-sm btn-primary" type="button">Отправить предложение</button>
                                    <button class="btn btn-sm btn-outline-secondary" type="button">Позвонить</button>
                                </div>
                            </div>
                            <div class="border rounded-2 p-3 dashboard-card-action">
                                <p class="fw-semibold mb-2">Клиентка Анна — в группе риска по неявке.</p>
                                <p class="text-muted mb-3">Напомните ей двойным сообщением в чат и WhatsApp.</p>
                                <button class="btn btn-sm btn-warning" type="button">Отправить напоминание</button>
                            </div>
                            <div class="border rounded-2 p-3 dashboard-card-action">
                                <p class="fw-semibold mb-2">Завтра у Ольги День рождения.</p>
                                <p class="text-muted mb-3">Предложите подарок-пробник для ухода за кожей.</p>
                                <button class="btn btn-sm btn-outline-primary" type="button">Создать предложение</button>
                            </div>
                            <div class="border rounded-2 p-3 dashboard-card-action">
                                <p class="fw-semibold mb-2">Следующий визит у Елены — сложный.</p>
                                <p class="text-muted mb-3">Подготовьте дополнительные материалы и уточните пожелания заранее.</p>
                                <button class="btn btn-sm btn-outline-secondary" type="button">Подготовить чек-лист</button>
                            </div>
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
                            <span class="badge bg-label-success" data-dashboard-margin-insight>ИИ: В пятницу маржа выше на 25%.</span>
                        </div>
                        <div class="d-flex flex-column gap-3" data-dashboard-margin-list>
                            <div class="d-flex justify-content-center text-muted">Загрузка…</div>
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
                            <span class="dashboard-metric-pill" data-dashboard-revenue-delta>—</span>
                        </div>
                        <div class="d-flex flex-column gap-3" data-dashboard-revenue-trend>
                            <div class="d-flex justify-content-center text-muted">Загрузка…</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5 d-flex flex-column gap-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">Топ-3 маржинальных услуг</h5>
                        <ul class="list-unstyled mb-0" data-dashboard-services>
                            <li class="text-muted">Данные загружаются…</li>
                        </ul>
                        <p class="small text-muted mt-3" data-dashboard-services-insight>
                            ИИ: Наращивание ресниц приносит 1500 ₽/час, ламинирование бровей — 1200 ₽/час.
                        </p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">Лучшие клиенты</h5>
                        <ul class="list-unstyled mb-0" data-dashboard-clients-top>
                            <li class="text-muted">Данные загружаются…</li>
                        </ul>
                        <p class="small text-muted mt-3">Отмечаем тех, кто чаще рекомендует и оставляет отзывы.</p>
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
                    <p class="mb-0" data-dashboard-tip>
                        На этой неделе запрос на «эффект стеклянной кожи» вырос на 40%. Упомяните его в сторис и предложите пробный набор.
                    </p>
                </div>
                <div class="text-lg-end">
                    <button class="btn btn-primary" type="button">Подробнее</button>
                    <p class="small text-muted mb-0 mt-2" data-dashboard-tip-source>Источник: трендовые запросы клиентов Veloria</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var revenueEl = document.querySelector('[data-dashboard-revenue]');
            if (!revenueEl) return;

            var goal = 8000;
            var marginList = document.querySelector('[data-dashboard-margin-list]');
            var revenueTrendEl = document.querySelector('[data-dashboard-revenue-trend]');
            var servicesEl = document.querySelector('[data-dashboard-services]');
            var topClientsEl = document.querySelector('[data-dashboard-clients-top]');
            var revenueProgressEl = document.querySelector('[data-dashboard-revenue-progress]');
            var clientsEl = document.querySelector('[data-dashboard-clients]');
            var averageEl = document.querySelector('[data-dashboard-average]');
            var retentionEl = document.querySelector('[data-dashboard-retention]');
            var revenueDeltaEl = document.querySelector('[data-dashboard-revenue-delta]');
            var goalEl = document.querySelector('[data-dashboard-goal]');
            var marginInsightEl = document.querySelector('[data-dashboard-margin-insight]');

            if (goalEl) {
                goalEl.textContent = new Intl.NumberFormat('ru-RU').format(goal) + ' ₽';
            }

            function getCookie(name) {
                var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                return match ? decodeURIComponent(match[1]) : null;
            }

            function formatCurrency(value) {
                return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(value);
            }

            function formatDelta(delta) {
                if (delta === null || isNaN(delta)) return '—';
                var sign = delta > 0 ? '+' : '';
                var emoji = delta > 0 ? '✅' : (delta < 0 ? '⚠️' : '➖');
                return emoji + ' ' + sign + delta.toFixed(1) + '%';
            }

            function renderMargin(items) {
                if (!marginList) return;
                marginList.innerHTML = '';
                if (!items.length) {
                    marginList.innerHTML = '<div class="d-flex justify-content-center text-muted">Недостаточно данных</div>';
                    return;
                }

                var maxValue = Math.max.apply(null, items.map(function (item) { return item.value; }));
                items.forEach(function (item) {
                    var wrapper = document.createElement('div');
                    wrapper.className = 'border rounded-2 p-3';
                    wrapper.innerHTML = '
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold">' + item.label + '</span>
                            <span class="small text-muted">' + item.duration + '</span>
                        </div>
                        <div class="dashboard-bar-wrapper">
                            <div class="dashboard-bar">
                                <div class="dashboard-bar-fill" style="width: ' + (maxValue > 0 ? (item.value / maxValue * 100).toFixed(1) : 0) + '%"></div>
                            </div>
                            <span class="fw-semibold">' + item.display + '</span>
                        </div>
                    ';
                    marginList.appendChild(wrapper);
                });
            }

            function renderRevenueTrend(data) {
                if (!revenueTrendEl) return;
                revenueTrendEl.innerHTML = '';
                if (!data.labels || !data.labels.length) {
                    revenueTrendEl.innerHTML = '<div class="d-flex justify-content-center text-muted">Недостаточно данных</div>';
                    return;
                }

                data.labels.forEach(function (label, index) {
                    var card = document.createElement('div');
                    card.className = 'border rounded-2 p-3';
                    var current = data.current[index] || 0;
                    var previous = data.previous[index] || 0;
                    var delta = previous === 0 ? null : ((current - previous) / Math.max(previous, 1)) * 100;
                    card.innerHTML = '
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold">' + label + '</span>
                            <span class="small text-muted">' + formatCurrency(current) + '</span>
                        </div>
                        <p class="small mb-0 text-muted">' + (delta === null ? 'Нет данных для сравнения' : (delta >= 0 ? 'Рост ' : 'Падение ') + Math.abs(delta).toFixed(1) + '% vs прошлый период') + '</p>
                    ';
                    revenueTrendEl.appendChild(card);
                });
            }

            function renderServices(services) {
                if (!servicesEl) return;
                servicesEl.innerHTML = '';
                if (!services.length) {
                    servicesEl.innerHTML = '<li class="text-muted">Данных пока нет</li>';
                    return;
                }

                services.slice(0, 3).forEach(function (service, index) {
                    var li = document.createElement('li');
                    li.className = 'd-flex justify-content-between align-items-start mb-3';
                    var name = service.name || service.title || service.label || ('Услуга #' + (index + 1));
                    var marginValue = service.margin_per_hour || service.value || service.amount || 0;
                    var duration = service.duration || service.default_duration || '60 мин';
                    li.innerHTML = '
                        <div>
                            <div class="fw-semibold">' + name + '</div>
                            <div class="small text-muted">' + duration + '</div>
                        </div>
                        <div class="text-end">
                            <span class="fw-semibold">' + formatCurrency(marginValue) + '</span>
                            <div class="small text-muted">₽/час</div>
                        </div>
                    ';
                    servicesEl.appendChild(li);
                });
            }

            function renderClients(clients) {
                if (!topClientsEl) return;
                topClientsEl.innerHTML = '';
                if (!clients.length) {
                    topClientsEl.innerHTML = '<li class="text-muted">Пока нет рекомендованных клиентов</li>';
                    return;
                }

                clients.slice(0, 5).forEach(function (client) {
                    var li = document.createElement('li');
                    li.className = 'border rounded-2 p-3 mb-2';
                    var loyalty = client.loyalty_level ? client.loyalty_level.toUpperCase() : 'LTV';
                    var lastVisit = client.last_purchase_at ? new Date(client.last_purchase_at).toLocaleDateString('ru-RU') : (client.last_visit || client.last_visited_at || '—');
                    li.innerHTML = '
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold">' + client.name + '</span>
                            <span class="badge bg-label-info">' + loyalty + '</span>
                        </div>
                        <p class="small text-muted mb-1">LTV: ' + formatCurrency(client.total_spent || client.amount || client.ltv || 0) + '</p>
                        <p class="small text-muted mb-0">Последний визит: ' + lastVisit + '</p>
                    ';
                    topClientsEl.appendChild(li);
                });
            }

            var token = getCookie('token');
            var headers = { 'Accept': 'application/json' };
            if (token) headers['Authorization'] = 'Bearer ' + token;

            fetch('/api/v1/analytics/overview', { headers: headers })
                .then(function (response) {
                    if (!response.ok) throw new Error('Ошибка загрузки данных');
                    return response.json();
                })
                .then(function (payload) {
                    var summary = payload.data && payload.data.summary ? payload.data.summary : {};
                    var financial = payload.data && payload.data.financial ? payload.data.financial : {};
                    var topClients = payload.data && payload.data.top_clients ? payload.data.top_clients : [];
                    var trend = financial.revenue_trend || {};
                    trend.labels = Array.isArray(trend.labels) ? trend.labels : [];
                    trend.current = Array.isArray(trend.current) ? trend.current : [];
                    trend.previous = Array.isArray(trend.previous) ? trend.previous : [];
                    var services = (financial.service_share && (financial.service_share.items || financial.service_share.data)) || [];

                    var currentRevenue = summary.revenue ? summary.revenue.current || 0 : 0;
                    var revenueDelta = summary.revenue ? summary.revenue.delta : null;
                    var transactions = summary.transactions ? summary.transactions.current || 0 : 0;
                    var clientsTarget = 5;

                    revenueEl.textContent = formatCurrency(currentRevenue);
                    if (revenueProgressEl) {
                        var progress = Math.min(100, Math.round((currentRevenue / goal) * 100));
                        revenueProgressEl.style.width = progress + '%';
                    }

                    if (clientsEl) {
                        clientsEl.textContent = transactions + ' из ' + clientsTarget;
                    }

                    if (averageEl && summary.average_ticket) {
                        averageEl.textContent = formatCurrency(summary.average_ticket.current || 0);
                    }

                    if (retentionEl && summary.retention_rate) {
                        retentionEl.textContent = (summary.retention_rate.current || 0).toFixed(1) + '%';
                    }

                    if (revenueDeltaEl) {
                        revenueDeltaEl.textContent = 'VS прошлый период: ' + formatDelta(revenueDelta);
                    }

                    renderRevenueTrend(trend);

                    var marginItems = [];
                    if (trend.labels && trend.labels.length) {
                        var hoursPerDay = 6;
                        var labelsSlice = trend.labels.slice(-7);
                        var currentSlice = trend.current.slice(-7);
                        var total = labelsSlice.map(function (label, idx) {
                            var value = currentSlice[idx] || 0;
                            return { label: label, value: value / hoursPerDay };
                        });
                        marginItems = total.map(function (item) {
                            return {
                                label: item.label,
                                value: Math.round(item.value),
                                display: formatCurrency(item.value),
                                duration: hoursPerDay + ' ч в работе',
                            };
                        });
                    }
                    renderMargin(marginItems);

                    if (marginInsightEl && marginItems.length) {
                        var best = marginItems.slice().sort(function (a, b) { return b.value - a.value; })[0];
                        marginInsightEl.textContent = 'ИИ: ' + best.label + ' приносит больше всего — ' + best.display + '. Перенесем туда ключевых клиентов?';
                    }

                    renderServices(services);
                    renderClients(topClients || []);
                })
                .catch(function () {
                    if (marginList) {
                        marginList.innerHTML = '<div class="d-flex justify-content-center text-muted">Не удалось загрузить данные</div>';
                    }
                    if (revenueTrendEl) {
                        revenueTrendEl.innerHTML = '<div class="d-flex justify-content-center text-muted">Не удалось загрузить данные</div>';
                    }
                    if (servicesEl) {
                        servicesEl.innerHTML = '<li class="text-muted">Не удалось загрузить данные</li>';
                    }
                    if (revenueDeltaEl) {
                        revenueDeltaEl.textContent = 'Нет данных для сравнения';
                    }
                });
        });
    </script>
@endsection
