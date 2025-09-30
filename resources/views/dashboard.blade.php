@extends('layouts.app')

@section('title', 'Дашборд')

@section('content')
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Сегодня в VeloriaCRM</h4>
            <p class="text-muted mb-0">Следите за расписанием, показателями и рекомендациями ассистента в одном экране.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('orders.create') }}" class="btn btn-primary">
                <i class="ri ri-flashlight-line me-1"></i>
                Быстрая запись
            </a>
            <a href="#finance" class="btn btn-outline-secondary">
                <i class="ri ri-line-chart-line me-1"></i>
                Финансы и эффективность
            </a>
        </div>
    </div>

    <div id="dashboard-alerts" class="mb-3"></div>

    <div class="row gy-4">
        <div class="col-12 col-xl-8 d-flex flex-column gap-4">
            <div class="card h-100">
                <div class="card-header d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
                    <div>
                        <h5 class="mb-0">Расписание на сегодня</h5>
                        <small class="text-muted" id="dashboard-schedule-meta">—</small>
                    </div>
                    <span class="badge bg-label-secondary" id="dashboard-workday-badge">Загрузка...</span>
                </div>
                <div class="card-body">
                    <div id="dashboard-schedule" class="d-flex flex-column gap-4">
                        <p class="text-muted text-center mb-0">Загружаем расписание...</p>
                    </div>
                    <div class="mt-4" id="dashboard-free-slots"></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
                    <h5 class="mb-0">Сегодня в цифрах</h5>
                    <small class="text-muted">Актуализируется автоматически</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="text-muted">Выручка</span>
                                    <span class="badge bg-label-secondary" id="dashboard-revenue-delta">—</span>
                                </div>
                                <h4 class="mt-2 mb-1" id="dashboard-revenue-actual">—</h4>
                                <p class="text-muted mb-0 small" id="dashboard-revenue-target">Цель: —</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <span class="text-muted">Клиенты</span>
                                <h4 class="mt-2 mb-1" id="dashboard-clients-count">—</h4>
                                <p class="text-muted mb-0 small">Записано / доступно</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <span class="text-muted">Средний чек</span>
                                <h4 class="mt-2 mb-1" id="dashboard-average-check">—</h4>
                                <p class="text-muted mb-0 small" id="dashboard-average-baseline">База: —</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Советы ИИ-ассистента</h5>
                </div>
                <div class="card-body">
                    <div id="dashboard-ai-tips" class="d-flex flex-column gap-3">
                        <p class="text-muted mb-0">Анализируем данные...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="finance" class="mt-5">
        <div class="row gy-4">
            <div class="col-12 col-xl-8 d-flex flex-column gap-4">
                <div class="card h-100">
                    <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
                        <div>
                            <h5 class="mb-0">Маржа и доходность</h5>
                            <small class="text-muted">Динамика по дням недели</small>
                        </div>
                        <span class="text-muted small" id="dashboard-margin-insight">—</span>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush" id="dashboard-margin-list">
                            <li class="list-group-item text-muted">Нет данных для анализа.</li>
                        </ul>
                    </div>
                </div>

                <div class="card h-100">
                    <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
                        <div>
                            <h5 class="mb-0">Выручка за период</h5>
                            <small class="text-muted">Последние 2 недели</small>
                        </div>
                        <div class="text-muted small" id="dashboard-revenue-comparison">—</div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-2" id="dashboard-revenue-trend">
                            <p class="text-muted mb-0">История загрузится через секунду...</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4 d-flex flex-column gap-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Топ-услуги по марже</h5>
                    </div>
                    <div class="card-body">
                        <div id="dashboard-top-services" class="d-flex flex-column gap-3">
                            <p class="text-muted mb-0">Данных пока нет.</p>
                        </div>
                    </div>
                </div>

                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Лучшие клиенты</h5>
                    </div>
                    <div class="card-body">
                        <div id="dashboard-top-clients" class="d-flex flex-column gap-3">
                            <p class="text-muted mb-0">Загрузим, как только появятся визиты.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
            <div>
                <h5 class="mb-0">Микро-обучение и тренды</h5>
                <small class="text-muted">Один совет в день для роста бизнеса</small>
            </div>
        </div>
        <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <p class="mb-0" id="dashboard-learning-tip">Следим за трендами...</p>
            <a href="#" class="btn btn-outline-primary" id="dashboard-learning-action" hidden>Подробнее</a>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function getCookie(name) {
                var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
                return match ? decodeURIComponent(match[2]) : null;
            }

            function authHeaders(extra) {
                if (extra === void 0) { extra = {}; }
                var token = getCookie('token');
                var headers = Object.assign({ 'Accept': 'application/json' }, extra || {});
                if (token) {
                    headers['Authorization'] = 'Bearer ' + token;
                }
                return headers;
            }

            var alertsContainer = document.getElementById('dashboard-alerts');
            var scheduleContainer = document.getElementById('dashboard-schedule');
            var scheduleMeta = document.getElementById('dashboard-schedule-meta');
            var workdayBadge = document.getElementById('dashboard-workday-badge');
            var freeSlotsContainer = document.getElementById('dashboard-free-slots');
            var revenueActual = document.getElementById('dashboard-revenue-actual');
            var revenueTarget = document.getElementById('dashboard-revenue-target');
            var revenueDelta = document.getElementById('dashboard-revenue-delta');
            var clientsCount = document.getElementById('dashboard-clients-count');
            var averageCheck = document.getElementById('dashboard-average-check');
            var averageBaseline = document.getElementById('dashboard-average-baseline');
            var tipsContainer = document.getElementById('dashboard-ai-tips');
            var marginList = document.getElementById('dashboard-margin-list');
            var marginInsight = document.getElementById('dashboard-margin-insight');
            var revenueTrendContainer = document.getElementById('dashboard-revenue-trend');
            var revenueComparison = document.getElementById('dashboard-revenue-comparison');
            var topServicesContainer = document.getElementById('dashboard-top-services');
            var topClientsContainer = document.getElementById('dashboard-top-clients');
            var learningTip = document.getElementById('dashboard-learning-tip');
            var learningAction = document.getElementById('dashboard-learning-action');

            function formatCurrency(value) {
                if (value === null || value === undefined || isNaN(value)) {
                    return '—';
                }
                return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(value);
            }

            function formatNumber(value) {
                if (value === null || value === undefined || isNaN(value)) {
                    return '—';
                }
                return new Intl.NumberFormat('ru-RU').format(value);
            }

            function formatPercent(value) {
                if (value === null || value === undefined || isNaN(value)) {
                    return '—';
                }
                return (value > 0 ? '+' : '') + value.toFixed(1) + '%';
            }

            function clearAlerts() {
                alertsContainer.innerHTML = '';
            }

            function showAlert(type, message) {
                var wrapper = document.createElement('div');
                wrapper.className = 'alert alert-' + type + ' alert-dismissible fade show';
                wrapper.setAttribute('role', 'alert');
                wrapper.innerHTML = '\n                    ' + message + '\n                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>\n                ';
                alertsContainer.appendChild(wrapper);
            }

            function renderSchedule(schedule) {
                if (!schedule) {
                    scheduleContainer.innerHTML = '<p class="text-muted text-center mb-0">Нет данных по расписанию.</p>';
                    return;
                }

                scheduleMeta.textContent = new Date(schedule.date).toLocaleDateString('ru-RU', { weekday: 'long', day: 'numeric', month: 'long' });

                if (schedule.is_working_day) {
                    if (Array.isArray(schedule.work_hours) && schedule.work_hours.length) {
                        workdayBadge.textContent = 'Рабочий день: ' + schedule.work_hours[0] + ' — ' + schedule.work_hours[schedule.work_hours.length - 1];
                        workdayBadge.className = 'badge bg-label-primary';
                    } else {
                        workdayBadge.textContent = 'Гибкий график';
                        workdayBadge.className = 'badge bg-label-info';
                    }
                } else {
                    workdayBadge.textContent = 'Выходной день';
                    workdayBadge.className = 'badge bg-label-warning';
                }

                var items = Array.isArray(schedule.items) ? schedule.items : [];
                if (!items.length) {
                    scheduleContainer.innerHTML = '<p class="text-muted text-center mb-0">На сегодня записей нет. Воспользуйтесь быстрым бронированием.</p>';
                } else {
                    scheduleContainer.innerHTML = '';
                    items.forEach(function (item) {
                        var wrapper = document.createElement('div');
                        wrapper.className = 'position-relative ps-4 border-start border-2 border-light pb-4';
                        var indicator = item.indicator || {};
                        var indicatorIcon = indicator.icon || '•';
                        var indicatorLabel = indicator.label || 'Без статуса';
                        var indicatorLevel = indicator.level || 'secondary';
                        var indicatorReason = indicator.reason || '';
                        var clientName = item.client && item.client.name ? item.client.name : 'Клиент не указан';
                        var badges = '';
                        if (Array.isArray(item.badges)) {
                            badges = item.badges.map(function (badge) {
                                return '<span class="badge bg-label-secondary">' + badge + '</span>';
                            }).join(' ');
                        }

                        var services = '';
                        if (Array.isArray(item.services) && item.services.length) {
                            services = item.services.map(function (service) {
                                return '<li>' + (service.name || 'Услуга') + (service.duration ? ' · ' + service.duration + ' мин' : '') + '</li>';
                            }).join('');
                            services = '<ul class="mb-0 ps-3">' + services + '</ul>';
                        }

                        wrapper.innerHTML = '\n                            <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-body shadow-sm">' + indicatorIcon + '</span>\n                            <div class="card border-0 shadow-none bg-body">\n                                <div class="card-body p-4">\n                                    <div class="d-flex flex-column flex-md-row justify-content-between gap-3">\n                                        <div>\n                                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">\n                                                <h6 class="mb-0">' + (item.time || '—') + (item.end_time ? ' — ' + item.end_time : '') + '</h6>\n                                                <span class="badge bg-label-' + (indicatorClass(indicatorLevel)) + '">' + indicatorLabel + '</span>\n                                            </div>\n                                            <p class="mb-2 text-muted">' + clientName + '</p>\n                                            ' + services + '\n                                            <div class="d-flex flex-wrap gap-2 mt-2">' + badges + '</div>\n                                        </div>\n                                        <div class="text-md-end">\n                                            <p class="mb-1 fw-semibold">' + formatCurrency(item.total_price) + '</p>\n                                            <small class="text-muted">' + indicatorReason + '</small>\n                                        </div>\n                                    </div>\n                                </div>\n                            </div>\n                        ';

                        scheduleContainer.appendChild(wrapper);
                    });
                }

                var freeSlots = Array.isArray(schedule.free_slots) ? schedule.free_slots : [];
                if (freeSlots.length) {
                    freeSlotsContainer.innerHTML = '<div class="alert alert-info mb-0">Свободные окна: ' + freeSlots.map(function (slot) { return '<span class="badge bg-label-primary me-1">' + slot.time + '</span>'; }).join('') + '</div>';
                } else {
                    freeSlotsContainer.innerHTML = '';
                }
            }

            function indicatorClass(level) {
                switch (level) {
                    case 'high':
                        return 'success';
                    case 'risk':
                        return 'warning';
                    case 'critical':
                        return 'danger';
                    default:
                        return 'secondary';
                }
            }

            function renderTodayMetrics(metrics) {
                if (!metrics) {
                    revenueActual.textContent = '—';
                    revenueTarget.textContent = 'Цель: —';
                    revenueDelta.textContent = '—';
                    clientsCount.textContent = '—';
                    averageCheck.textContent = '—';
                    averageBaseline.textContent = 'База: —';
                    return;
                }

                var revenue = metrics.revenue || {};
                revenueActual.textContent = formatCurrency(revenue.actual);
                revenueTarget.textContent = 'Цель: ' + formatCurrency(revenue.target);
                if (revenue.delta === null || revenue.delta === undefined || isNaN(revenue.delta)) {
                    revenueDelta.textContent = '—';
                    revenueDelta.className = 'badge bg-label-secondary';
                } else {
                    revenueDelta.textContent = formatPercent(revenue.delta);
                    revenueDelta.className = 'badge ' + (revenue.delta >= 0 ? 'bg-label-success' : 'bg-label-danger');
                }

                var clients = metrics.clients || {};
                var booked = clients.booked || 0;
                var capacity = clients.capacity || 0;
                clientsCount.textContent = booked + ' / ' + capacity;

                var avg = metrics.average_check || {};
                averageCheck.textContent = formatCurrency(avg.value);
                averageBaseline.textContent = 'База: ' + formatCurrency(avg.baseline);
            }

            function renderTips(tips) {
                tipsContainer.innerHTML = '';
                if (!Array.isArray(tips) || !tips.length) {
                    tipsContainer.innerHTML = '<p class="text-muted mb-0">Подсказок пока нет.</p>';
                    return;
                }

                tips.forEach(function (tip) {
                    var card = document.createElement('div');
                    card.className = 'border rounded-3 p-3';
                    card.innerHTML = '\n                        <h6 class="mb-2">' + (tip.title || 'Совет') + '</h6>\n                        <p class="mb-3 text-muted">' + (tip.message || '') + '</p>\n                    ';
                    if (tip.action) {
                        var btn = document.createElement('button');
                        btn.className = 'btn btn-sm btn-primary';
                        btn.type = 'button';
                        btn.textContent = tip.action;
                        card.appendChild(btn);
                    }
                    tipsContainer.appendChild(card);
                });
            }

            function renderMargin(data) {
                marginList.innerHTML = '';
                if (!Array.isArray(data) || !data.length) {
                    marginList.innerHTML = '<li class="list-group-item text-muted">Недостаточно данных.</li>';
                    marginInsight.textContent = '—';
                    return;
                }

                data.forEach(function (row) {
                    var item = document.createElement('li');
                    item.className = 'list-group-item d-flex justify-content-between align-items-center';
                    item.innerHTML = '\n                        <span>' + (row.weekday || 'День') + '</span>\n                        <span class="fw-semibold">' + formatCurrency(row.value) + ' / ч</span>\n                    ';
                    marginList.appendChild(item);
                });
            }

            function renderRevenueTrend(points, comparison) {
                revenueTrendContainer.innerHTML = '';
                if (!Array.isArray(points) || !points.length) {
                    revenueTrendContainer.innerHTML = '<p class="text-muted mb-0">Недостаточно истории для графика.</p>';
                } else {
                    points.forEach(function (point) {
                        var row = document.createElement('div');
                        row.className = 'd-flex justify-content-between align-items-center border rounded-3 px-3 py-2';
                        var date = new Date(point.date + 'T00:00:00');
                        row.innerHTML = '\n                            <span>' + date.toLocaleDateString('ru-RU', { day: '2-digit', month: 'short' }) + '</span>\n                            <span class="fw-semibold">' + formatCurrency(point.revenue) + '</span>\n                        ';
                        revenueTrendContainer.appendChild(row);
                    });
                }

                if (!comparison) {
                    revenueComparison.textContent = '—';
                    return;
                }

                var current = formatCurrency(comparison.current);
                var previous = formatCurrency(comparison.previous);
                var delta = comparison.delta;
                var deltaText = (delta === null || delta === undefined || isNaN(delta)) ? '—' : formatPercent(delta);
                revenueComparison.textContent = 'Текущий период: ' + current + ' · Прошлый: ' + previous + ' (' + deltaText + ')';
            }

            function renderTopServices(items) {
                topServicesContainer.innerHTML = '';
                if (!Array.isArray(items) || !items.length) {
                    topServicesContainer.innerHTML = '<p class="text-muted mb-0">Добавьте заказы, чтобы увидеть лидеров.</p>';
                    return;
                }

                items.forEach(function (item) {
                    var block = document.createElement('div');
                    block.className = 'border rounded-3 p-3';
                    block.innerHTML = '\n                        <h6 class="mb-1">' + (item.name || 'Услуга') + '</h6>\n                        <p class="text-muted mb-0">' + formatCurrency(item.margin_per_hour) + ' / ч · визитов: ' + (item.visits || 0) + '</p>\n                    ';
                    topServicesContainer.appendChild(block);
                });
            }

            function renderTopClients(items) {
                topClientsContainer.innerHTML = '';
                if (!Array.isArray(items) || !items.length) {
                    topClientsContainer.innerHTML = '<p class="text-muted mb-0">Статистика появится после первых визитов.</p>';
                    return;
                }

                items.forEach(function (item) {
                    var block = document.createElement('div');
                    block.className = 'border rounded-3 p-3';
                    block.innerHTML = '\n                        <div class="d-flex justify-content-between align-items-center">\n                            <div>\n                                <h6 class="mb-1">' + (item.name || 'Клиент') + '</h6>\n                                <span class="badge bg-label-' + (item.loyalty === 'Лояльный' ? 'success' : 'warning') + '">' + (item.loyalty || '—') + '</span>\n                            </div>\n                            <div class="text-end">\n                                <p class="fw-semibold mb-0">' + formatCurrency(item.ltv) + '</p>\n                                <small class="text-muted">Визитов: ' + (item.visits || 0) + '</small>\n                            </div>\n                        </div>\n                    ';
                    topClientsContainer.appendChild(block);
                });
            }

            function renderLearning(block) {
                if (!block) {
                    learningTip.textContent = 'Данные отсутствуют.';
                    learningAction.hidden = true;
                    return;
                }

                learningTip.textContent = block.tip || 'Держите руку на пульсе трендов.';
                if (block.action) {
                    learningAction.textContent = block.action;
                    learningAction.hidden = false;
                } else {
                    learningAction.hidden = true;
                }
            }

            function loadDashboard() {
                clearAlerts();
                fetch('/api/v1/dashboard', {
                    headers: authHeaders()
                }).then(function (response) {
                    if (!response.ok) {
                        throw new Error('Ошибка загрузки: ' + response.status);
                    }
                    return response.json();
                }).then(function (data) {
                    renderSchedule(data.schedule);
                    renderTodayMetrics(data.metrics);
                    renderTips(data.ai_tips);

                    var finance = data.finance || {};
                    var marginBlock = finance.margin_per_hour || {};
                    renderMargin(marginBlock.chart);
                    marginInsight.textContent = marginBlock.insight || '—';

                    var revenueBlock = finance.revenue_trend || {};
                    renderRevenueTrend(revenueBlock.points, revenueBlock.comparison);

                    renderTopServices(finance.top_services);
                    renderTopClients(finance.top_clients);
                    renderLearning(data.learning);
                }).catch(function (error) {
                    showAlert('danger', 'Не удалось загрузить дашборд: ' + error.message);
                    renderSchedule(null);
                    renderTodayMetrics(null);
                    renderTips([]);
                    renderMargin([]);
                    renderRevenueTrend([], null);
                    renderTopServices([]);
                    renderTopClients([]);
                    renderLearning(null);
                });
            }

            loadDashboard();
        });
    </script>
@endsection
