@extends('layouts.app')

@section('title', 'Клиент')

@section('content')
    <div id="client-view" data-client-id="{{ $clientId ?? '' }}">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="mb-1" id="client-name">Клиент</h4>
                <p class="text-muted mb-0" id="client-subtitle">Загрузка информации...</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary" id="client-analytics-btn">
                    <i class="ri ri-bar-chart-line me-1"></i>
                    Аналитика клиента
                </button>
                <button type="button" class="btn btn-outline-info" id="client-reminder-btn" disabled>
                    <i class="ri ri-mail-line me-1"></i>
                    Автонапоминание
                </button>
                <a href="#" class="btn btn-outline-secondary" id="client-edit-link" hidden>
                    <i class="ri ri-edit-line me-1"></i>
                    Редактировать
                </a>
                <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
                    <i class="ri ri-arrow-go-back-line me-1"></i>
                    К списку
                </a>
            </div>
        </div>

        <div id="client-view-alerts"></div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Основная информация</h5>
                        <span class="badge bg-label-info" id="client-loyalty-badge">—</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase mb-2">Контакты</h6>
                                <p class="mb-1">
                                    <i class="ri ri-phone-line me-1"></i>
                                    <span id="client-phone">—</span>
                                </p>
                                <p class="mb-1">
                                    <i class="ri ri-mail-line me-1"></i>
                                    <span id="client-email">—</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase mb-2">Профиль</h6>
                                <p class="mb-1">День рождения: <span id="client-birthday">—</span></p>
                                <p class="mb-1">Последний визит: <span id="client-last-visit">—</span></p>
                                <p class="mb-0">Карточка обновлена: <span id="client-updated-at">—</span></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase mb-2">Теги</h6>
                                <div id="client-tags" class="d-flex flex-wrap gap-2"></div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase mb-2">Аллергии</h6>
                                <div id="client-allergies" class="d-flex flex-wrap gap-2"></div>
                            </div>
                            <div class="col-12">
                                <h6 class="text-muted text-uppercase mb-2">Предпочтения</h6>
                                <div id="client-preferences" class="small"></div>
                            </div>
                            <div class="col-12">
                                <h6 class="text-muted text-uppercase mb-2">Заметки</h6>
                                <p class="mb-0" id="client-notes">—</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Статистика по визитам</h5>
                        <span class="badge bg-label-secondary">CRM</span>
                    </div>
                    <div class="card-body" id="client-statistics">
                        <p class="text-muted mb-0">Загрузка статистики...</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Риски неявки</h5>
                        <span class="badge bg-label-secondary" id="client-risk-badge">—</span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3" id="client-risk-score">Анализируем предыдущие записи клиента, чтобы подсказать, как снизить вероятность неявки.</p>
                        <div class="mb-3">
                            <h6 class="fw-semibold mb-2">Сигналы</h6>
                            <ul class="list-unstyled small mb-0" id="client-risk-signals"></ul>
                        </div>
                        <div>
                            <h6 class="fw-semibold mb-2">Что сделать</h6>
                            <ul class="list-unstyled small mb-0" id="client-risk-suggestions"></ul>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Рекомендации ИИ</h5>
                        <span class="badge bg-label-secondary" id="client-ai-badge">ИИ</span>
                    </div>
                    <div class="card-body" id="client-ai-recommendations">
                        <p class="text-muted mb-0">Загрузка...</p>
                    </div>
                </div>

                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Заметки для коммуникации</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Используйте карточку для персонализации сообщений и рекомендаций.</p>
                        <ul class="list-unstyled mb-0" id="client-highlights"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="clientAnalyticsModal" tabindex="-1" aria-labelledby="clientAnalyticsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clientAnalyticsModalLabel">Аналитика клиента</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="client-analytics-content">
                    <p class="text-muted mb-0">Загрузка...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="clientReminderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reminder-title">Автонапоминание</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reminder-message" class="form-label">Текст напоминания</label>
                        <textarea class="form-control" id="reminder-message" rows="4"></textarea>
                        <div class="form-text">Текст загружается из настроек. При необходимости адаптируйте перед отправкой.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Канал связи</label>
                        <div id="reminder-channels" class="d-flex flex-column gap-2"></div>
                    </div>
                    <div id="reminder-errors" class="text-danger small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="reminder-send">Отправить</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('client-view');
            const clientId = Number(container?.getAttribute('data-client-id'));

            if (!clientId) {
                return;
            }

            function getCookie(name) {
                var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
                return match ? decodeURIComponent(match[2]) : null;
            }

            function authHeaders(extra = {}) {
                var token = getCookie('token');
                var headers = Object.assign({ 'Accept': 'application/json', 'Content-Type': 'application/json' }, extra);
                if (token) headers['Authorization'] = 'Bearer ' + token;
                return headers;
            }

            const alertsContainer = document.getElementById('client-view-alerts');
            const nameEl = document.getElementById('client-name');
            const subtitleEl = document.getElementById('client-subtitle');
            const phoneEl = document.getElementById('client-phone');
            const emailEl = document.getElementById('client-email');
            const birthdayEl = document.getElementById('client-birthday');
            const lastVisitEl = document.getElementById('client-last-visit');
            const updatedAtEl = document.getElementById('client-updated-at');
            const loyaltyBadge = document.getElementById('client-loyalty-badge');
            const tagsContainer = document.getElementById('client-tags');
            const allergiesContainer = document.getElementById('client-allergies');
            const preferencesContainer = document.getElementById('client-preferences');
            const notesEl = document.getElementById('client-notes');
            const highlightsList = document.getElementById('client-highlights');
            const statisticsContainer = document.getElementById('client-statistics');
            const editLink = document.getElementById('client-edit-link');
            const reminderButton = document.getElementById('client-reminder-btn');
            const analyticsButton = document.getElementById('client-analytics-btn');
            const analyticsModalEl = document.getElementById('clientAnalyticsModal');
            const analyticsModal = analyticsModalEl ? new bootstrap.Modal(analyticsModalEl) : null;
            const analyticsContent = document.getElementById('client-analytics-content');
            const recommendationsContainer = document.getElementById('client-ai-recommendations');
            const aiBadge = document.getElementById('client-ai-badge');
            const riskBadge = document.getElementById('client-risk-badge');
            const riskScoreEl = document.getElementById('client-risk-score');
            const riskSignalsList = document.getElementById('client-risk-signals');
            const riskSuggestionsList = document.getElementById('client-risk-suggestions');

            const reminderModalEl = document.getElementById('clientReminderModal');
            const reminderModal = reminderModalEl ? new bootstrap.Modal(reminderModalEl) : null;
            const reminderTitle = document.getElementById('reminder-title');
            const reminderMessageInput = document.getElementById('reminder-message');
            const reminderChannels = document.getElementById('reminder-channels');
            const reminderErrors = document.getElementById('reminder-errors');
            const reminderSendBtn = document.getElementById('reminder-send');

            let reminderMessageTemplate = '';
            let currentClient = null;
            let clientMeta = {};
            let analyticsData = null;
            let analyticsLoaded = false;
            let analyticsLoading = false;
            let analyticsError = null;
            let recommendationsLoaded = false;
            let recommendationsLoading = false;
            let recommendationsError = null;

            function showAlert(type, message) {
                const alert = document.createElement('div');
                alert.className = 'alert alert-' + type + ' alert-dismissible fade show';
                alert.setAttribute('role', 'alert');
                alert.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                alertsContainer.appendChild(alert);
            }

            function renderBadges(container, items, emptyText) {
                container.innerHTML = '';
                if (!Array.isArray(items) || !items.length) {
                    container.innerHTML = `<span class="text-muted">${emptyText}</span>`;
                    return;
                }
                items.forEach(function (item) {
                    if (typeof item !== 'string') {
                        return;
                    }
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-label-primary';
                    badge.textContent = item;
                    container.appendChild(badge);
                });
            }

            function renderPreferences(preferences) {
                if (!preferences) {
                    return '<p class="text-muted mb-0">Предпочтения не указаны.</p>';
                }
                if (Array.isArray(preferences)) {
                    if (!preferences.length) {
                        return '<p class="text-muted mb-0">Предпочтения не указаны.</p>';
                    }
                    const list = preferences
                        .filter(item => typeof item === 'string' && item.trim() !== '')
                        .map(item => `<li>${item}</li>`)
                        .join('');
                    if (!list) {
                        return '<p class="text-muted mb-0">Предпочтения не указаны.</p>';
                    }
                    return `<ul class="mb-0 ps-3">${list}</ul>`;
                }
                if (typeof preferences === 'object') {
                    const entries = Object.entries(preferences)
                        .filter(([key]) => key)
                        .map(([key, value]) => `<li><strong>${key}</strong>: ${value ?? ''}</li>`)
                        .join('');
                    if (!entries) {
                        return '<p class="text-muted mb-0">Предпочтения не указаны.</p>';
                    }
                    return `<ul class="mb-0 ps-3">${entries}</ul>`;
                }
                return '<p class="text-muted mb-0">Предпочтения не указаны.</p>';
            }

            function formatCurrency(value) {
                if (value === null || value === undefined) {
                    return '—';
                }
                const number = Number(value);
                if (Number.isNaN(number)) {
                    return '—';
                }
                return number.toLocaleString('ru-RU', { style: 'currency', currency: 'RUB' });
            }

            function formatDate(value) {
                if (!value) {
                    return '—';
                }
                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return '—';
                }
                return date.toLocaleDateString('ru-RU');
            }

            function renderHighlights(client, stats = null, risk = null) {
                highlightsList.innerHTML = '';
                const highlights = [];
                if (client.loyalty_label) {
                    highlights.push(`Лояльность: ${client.loyalty_label}`);
                }
                if (Array.isArray(client.tags) && client.tags.length) {
                    highlights.push('Теги: ' + client.tags.join(', '));
                }
                if (client.birthday_formatted) {
                    highlights.push('День рождения: ' + client.birthday_formatted);
                }
                if (client.last_visit_at_formatted) {
                    highlights.push('Последний визит: ' + client.last_visit_at_formatted);
                }
                if (stats && typeof stats.average_check === 'number') {
                    highlights.push('Средний чек: ' + formatCurrency(stats.average_check));
                }
                if (stats && typeof stats.retention_score === 'number') {
                    highlights.push('Индекс удержания: ' + Math.round(stats.retention_score) + '%');
                }
                if (risk && risk.label) {
                    highlights.push('Риск неявки: ' + risk.label);
                }

                if (!highlights.length) {
                    highlightsList.innerHTML = '<li class="text-muted">Добавьте информацию, чтобы персонализировать коммуникации.</li>';
                    return;
                }

                highlights.forEach(function (item) {
                    const li = document.createElement('li');
                    li.textContent = item;
                    highlightsList.appendChild(li);
                });
            }

            function renderStatistics(stats) {
                if (!stats) {
                    statisticsContainer.innerHTML = '<p class="text-muted mb-0">Нет данных для отображения.</p>';
                    return;
                }

                const totalOrders = stats.total_orders ?? 0;
                const completed = stats.completed_orders ?? 0;
                const cancelled = stats.cancelled_orders ?? 0;
                const noShow = stats.no_show_orders ?? 0;
                const upcoming = stats.upcoming_visit_formatted || '—';
                const lastFromOrders = stats.last_visit_from_orders_formatted || '—';
                const lifetime = formatCurrency(stats.lifetime_value ?? 0);
                const averageCheck = formatCurrency(stats.average_check ?? 0);
                const spend90 = formatCurrency(stats.spend_last_90_days ?? 0);
                const averageInterval = stats.average_interval_days ? `${stats.average_interval_days} дн.` : '—';
                const averageDuration = stats.average_duration ? `${stats.average_duration} мин` : '—';
                const retention = typeof stats.retention_score === 'number' ? `${Math.round(stats.retention_score)}%` : '—';

                let favoritesHtml = '<p class="text-muted small mb-0">Добавьте завершённые визиты, чтобы увидеть любимые услуги.</p>';
                if (Array.isArray(stats.favorite_services) && stats.favorite_services.length) {
                    favoritesHtml = '<ul class="list-unstyled small mb-0">' + stats.favorite_services.map(service => {
                        const count = service.count ?? 0;
                        const price = formatCurrency(service.average_price ?? 0);
                        return `<li><strong>${service.name || 'Услуга'}</strong> — ${count} виз., ср. чек ${price}</li>`;
                    }).join('') + '</ul>';
                }

                let recentHtml = '<p class="text-muted small mb-0">История процедур появится после визитов.</p>';
                if (Array.isArray(stats.recent_services) && stats.recent_services.length) {
                    recentHtml = '<ul class="list-unstyled small mb-0">' + stats.recent_services.map(item => {
                        const price = item.price !== null && item.price !== undefined ? formatCurrency(item.price) : '—';
                        const performedAt = item.performed_at || '—';
                        return `<li><strong>${item.name || 'Услуга'}</strong> — ${price}, ${performedAt}</li>`;
                    }).join('') + '</ul>';
                }

                statisticsContainer.innerHTML = `
                    <div class="d-flex flex-column gap-3">
                        <div>
                            <dl class="row mb-0 small">
                                <dt class="col-6">Всего визитов</dt>
                                <dd class="col-6 text-end">${totalOrders}</dd>
                                <dt class="col-6">Завершено</dt>
                                <dd class="col-6 text-end">${completed}</dd>
                                <dt class="col-6">Отменено</dt>
                                <dd class="col-6 text-end">${cancelled}</dd>
                                <dt class="col-6">Не явился</dt>
                                <dd class="col-6 text-end">${noShow}</dd>
                                <dt class="col-6">Ближайший визит</dt>
                                <dd class="col-6 text-end">${upcoming}</dd>
                                <dt class="col-6">Последний визит</dt>
                                <dd class="col-6 text-end">${lastFromOrders}</dd>
                            </dl>
                        </div>
                        <div>
                            <h6 class="fw-semibold small text-uppercase text-muted mb-2">Финансы</h6>
                            <ul class="list-unstyled small mb-0">
                                <li>Lifetime Value: <strong>${lifetime}</strong></li>
                                <li>Средний чек: <strong>${averageCheck}</strong></li>
                                <li>Выручка за 90 дней: <strong>${spend90}</strong></li>
                            </ul>
                        </div>
                        <div>
                            <h6 class="fw-semibold small text-uppercase text-muted mb-2">Поведение</h6>
                            <ul class="list-unstyled small mb-0">
                                <li>Средний интервал: <strong>${averageInterval}</strong></li>
                                <li>Средняя длительность: <strong>${averageDuration}</strong></li>
                                <li>Индекс удержания: <strong>${retention}</strong></li>
                            </ul>
                        </div>
                        <div>
                            <h6 class="fw-semibold small text-uppercase text-muted mb-2">Любимые услуги</h6>
                            ${favoritesHtml}
                        </div>
                        <div>
                            <h6 class="fw-semibold small text-uppercase text-muted mb-2">Последние процедуры</h6>
                            ${recentHtml}
                        </div>
                    </div>
                `;
            }

            function renderRisk(risk) {
                if (!riskBadge || !riskScoreEl || !riskSignalsList || !riskSuggestionsList) {
                    return;
                }

                riskSignalsList.innerHTML = '';
                riskSuggestionsList.innerHTML = '';

                if (!risk) {
                    riskBadge.className = 'badge bg-label-secondary';
                    riskBadge.textContent = '—';
                    riskScoreEl.textContent = 'Недостаточно данных для расчёта риска. Сохраняйте историю визитов.';
                    riskSignalsList.innerHTML = '<li class="text-muted">Добавьте завершённые визиты, чтобы увидеть сигналы риска.</li>';
                    riskSuggestionsList.innerHTML = '<li class="text-muted">Настройте автонапоминания и уточняйте причины отмен.</li>';
                    return;
                }

                const levelClass = {
                    low: 'bg-label-success',
                    medium: 'bg-label-warning',
                    high: 'bg-label-danger',
                }[risk.level] || 'bg-label-secondary';

                riskBadge.className = 'badge ' + levelClass;
                riskBadge.textContent = risk.label || '—';

                if (typeof risk.score === 'number') {
                    riskScoreEl.textContent = `Оценка риска: ${Math.round(risk.score)} из 100.`;
                } else {
                    riskScoreEl.textContent = 'Оценка риска доступна на основе истории визитов.';
                }

                const signals = Array.isArray(risk.signals) && risk.signals.length
                    ? risk.signals
                    : ['Система не обнаружила тревожных сигналов. Продолжайте поддерживать контакт.'];
                signals.forEach(function (signal) {
                    const li = document.createElement('li');
                    li.textContent = signal;
                    riskSignalsList.appendChild(li);
                });

                const suggestions = Array.isArray(risk.suggestions) && risk.suggestions.length
                    ? risk.suggestions
                    : ['Запланируйте напоминание и уточните ожидания клиента.'];
                suggestions.forEach(function (suggestion) {
                    const li = document.createElement('li');
                    li.textContent = suggestion;
                    riskSuggestionsList.appendChild(li);
                });
            }

            function renderRecommendations(recommendations) {
                if (!recommendationsContainer) {
                    return;
                }

                if (!clientMeta.has_pro_access) {
                    if (aiBadge) {
                        aiBadge.textContent = 'PRO';
                        aiBadge.className = 'badge bg-label-secondary';
                    }
                    recommendationsContainer.innerHTML = '<p class="text-muted mb-0">Доступно только в тарифах PRO и Elite.</p>';
                    return;
                }

                if (aiBadge) {
                    aiBadge.textContent = 'ИИ';
                    aiBadge.className = 'badge bg-label-primary';
                }

                if (recommendationsLoading) {
                    recommendationsContainer.innerHTML = '<p class="text-muted mb-0">Рекомендации загружаются...</p>';
                    return;
                }

                if (recommendationsError) {
                    recommendationsContainer.innerHTML = `<p class="text-danger mb-0">${recommendationsError}</p>`;
                    return;
                }

                if (!Array.isArray(recommendations) || !recommendations.length) {
                    recommendationsContainer.innerHTML = '<p class="text-muted mb-0">Рекомендаций пока нет.</p>';
                    return;
                }

                recommendationsContainer.innerHTML = '';
                recommendations.forEach(function (item) {
                    const block = document.createElement('div');
                    block.className = 'mb-3';

                    const service = item.service || {};
                    const title = item.title || service.name || 'Рекомендация';
                    const price = typeof service.price === 'number' ? formatCurrency(service.price) : null;
                    const duration = typeof service.duration === 'number' ? `${service.duration} мин` : null;
                    const metaParts = [];
                    if (price) metaParts.push(price);
                    if (duration) metaParts.push(duration);
                    const metaLine = metaParts.length ? `<p class="small text-muted mb-2">${metaParts.join(' · ')}</p>` : '';

                    const insight = item.insight || 'Персонализированная рекомендация.';
                    const action = item.action ? `<p class="small mb-0">${item.action}</p>` : '';
                    const confidence = typeof item.confidence === 'number' && !Number.isNaN(item.confidence)
                        ? Math.round(Math.min(1, Math.max(0, item.confidence)) * 100)
                        : null;

                    block.innerHTML = `
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div class="flex-grow-1">
                                <strong>${title}</strong>
                                ${metaLine}
                                <p class="text-muted small mb-1">${insight}</p>
                                ${action}
                            </div>
                            ${confidence !== null ? `<span class="badge bg-label-info align-self-start">${confidence}%</span>` : ''}
                        </div>
                    `;
                    recommendationsContainer.appendChild(block);
                });
            }

            function renderAnalytics(data) {
                if (!analyticsContent) {
                    return;
                }

                const metrics = data.metrics || {};
                const insights = data.insights || {};
                const favorites = Array.isArray(metrics.favorite_services) ? metrics.favorite_services : [];

                let html = '';

                if (insights.summary) {
                    html += `<div class="mb-3"><h6 class="fw-semibold mb-1">Краткий вывод</h6><p class="mb-0">${insights.summary}</p></div>`;
                }

                if (Array.isArray(insights.risk_flags) && insights.risk_flags.length) {
                    html += '<div class="mb-3"><h6 class="fw-semibold mb-1">Зоны внимания</h6><ul class="small mb-0">';
                    insights.risk_flags.forEach(flag => {
                        html += `<li>${flag}</li>`;
                    });
                    html += '</ul></div>';
                }

                if (Array.isArray(insights.recommendations) && insights.recommendations.length) {
                    html += '<div class="mb-3"><h6 class="fw-semibold mb-1">Что сделать</h6><ul class="small mb-0">';
                    insights.recommendations.forEach(rec => {
                        html += `<li><strong>${rec.title}:</strong> ${rec.action}</li>`;
                    });
                    html += '</ul></div>';
                }

                html += '<div><h6 class="fw-semibold mb-2">Ключевые метрики</h6><ul class="list-unstyled small mb-0">';
                html += `<li>Всего визитов: <strong>${metrics.total_visits ?? 0}</strong></li>`;
                html += `<li>Завершено: <strong>${metrics.completed_visits ?? 0}</strong></li>`;
                html += `<li>Предстоящие: <strong>${metrics.upcoming_visits ?? 0}</strong></li>`;
                html += `<li>Отмены: <strong>${metrics.cancelled_visits ?? 0}</strong></li>`;
                html += `<li>Не пришёл: <strong>${metrics.no_show_visits ?? 0}</strong></li>`;
                html += `<li>Средний чек: <strong>${formatCurrency(metrics.average_check)}</strong></li>`;
                if (metrics.average_visit_interval_days) {
                    html += `<li>Средний интервал: <strong>${metrics.average_visit_interval_days} дн.</strong></li>`;
                }
                if (metrics.last_visit_at) {
                    html += `<li>Последний визит: <strong>${formatDate(metrics.last_visit_at)}</strong></li>`;
                }
                if (metrics.next_visit_at) {
                    html += `<li>Следующий визит: <strong>${formatDate(metrics.next_visit_at)}</strong></li>`;
                }
                html += `<li>Выручка за всё время: <strong>${formatCurrency(metrics.lifetime_value)}</strong></li>`;
                if (favorites.length) {
                    html += '<li class="mt-2">Любимые услуги:<ul class="small ps-3 mb-0">';
                    favorites.forEach(service => {
                        html += `<li>${service.name || 'Услуга'} — ${service.count || 0} виз., ср. чек ${formatCurrency(service.average_price)}</li>`;
                    });
                    html += '</ul></li>';
                }
                html += '</ul></div>';

                analyticsContent.innerHTML = html;
            }

            function renderAnalyticsModal() {
                if (!analyticsContent) {
                    return;
                }

                if (!clientMeta.has_pro_access) {
                    analyticsContent.innerHTML = '<p class="text-muted mb-0">Аналитика доступна только в тарифах PRO и Elite.</p>';
                    return;
                }

                if (analyticsLoading) {
                    analyticsContent.innerHTML = '<p class="text-muted mb-0">Аналитика загружается...</p>';
                    return;
                }

                if (analyticsError) {
                    analyticsContent.innerHTML = `<p class="text-danger mb-0">${analyticsError}</p>`;
                    return;
                }

                if (analyticsLoaded && analyticsData) {
                    renderAnalytics(analyticsData);
                    return;
                }

                analyticsContent.innerHTML = '<p class="text-muted mb-0">Запросите аналитику клиента.</p>';
            }

            async function loadAnalytics() {
                if (!clientMeta.has_pro_access || analyticsLoading || analyticsLoaded) {
                    renderAnalyticsModal();
                    return;
                }

                analyticsLoading = true;
                analyticsError = null;
                analyticsData = null;
                renderAnalyticsModal();

                try {
                    const response = await fetch(`/api/v1/clients/${clientId}/analytics`, {
                        headers: authHeaders(),
                        credentials: 'include',
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        analyticsError = result.error?.message || 'Не удалось получить аналитику.';
                    } else {
                        analyticsData = result;
                        analyticsLoaded = true;
                    }
                } catch (error) {
                    console.error(error);
                    analyticsError = 'Не удалось получить аналитику.';
                } finally {
                    analyticsLoading = false;
                    renderAnalyticsModal();
                }
            }

            async function loadRecommendations() {
                if (!clientMeta.has_pro_access || recommendationsLoading || recommendationsLoaded) {
                    return;
                }

                recommendationsLoading = true;
                recommendationsError = null;
                renderRecommendations([]);

                try {
                    const response = await fetch(`/api/v1/clients/${clientId}/recommendations`, {
                        headers: authHeaders(),
                        credentials: 'include',
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        recommendationsError = result.error?.message || 'Не удалось получить рекомендации.';
                    } else {
                        recommendationsLoaded = true;
                        recommendationsLoading = false;
                        recommendationsError = null;
                        renderRecommendations(result.recommendations || []);
                        return;
                    }
                } catch (error) {
                    console.error(error);
                    recommendationsError = 'Не удалось получить рекомендации.';
                }

                recommendationsLoading = false;
                renderRecommendations([]);
            }

            function renderReminderChannels(client) {
                reminderChannels.innerHTML = '';
                const channels = Array.isArray(client.available_channels) ? client.available_channels : [];
                if (!channels.length) {
                    reminderChannels.innerHTML = '<p class="text-muted mb-0">Добавьте телефон или email, чтобы выбрать канал связи.</p>';
                    reminderSendBtn.disabled = true;
                    return;
                }
                channels.forEach(function (channel, index) {
                    const id = `reminder-channel-${channel.key}-${client.id}`;
                    const wrapper = document.createElement('div');
                    wrapper.className = 'form-check';
                    wrapper.innerHTML = `
                        <input class="form-check-input" type="radio" name="reminder-channel" id="${id}" value="${channel.key}" ${index === 0 ? 'checked' : ''} />
                        <label class="form-check-label" for="${id}">${channel.label}</label>
                    `;
                    reminderChannels.appendChild(wrapper);
                });
                reminderSendBtn.disabled = false;
            }

            function fillClient(client, meta) {
                currentClient = client;
                clientMeta = meta || {};
                reminderMessageTemplate = clientMeta.reminder_message || '';
                analyticsData = null;
                analyticsLoaded = false;
                analyticsLoading = false;
                analyticsError = null;
                recommendationsLoaded = false;
                recommendationsLoading = false;
                recommendationsError = null;

                nameEl.textContent = client.name || 'Клиент';
                subtitleEl.textContent = client.created_at_formatted ? `Клиент создан ${client.created_at_formatted}` : 'Карточка клиента';
                phoneEl.textContent = client.phone || '—';
                emailEl.textContent = client.email || '—';
                birthdayEl.textContent = client.birthday_formatted || '—';
                lastVisitEl.textContent = client.last_visit_at_formatted || clientMeta.statistics?.last_visit_from_orders_formatted || '—';
                updatedAtEl.textContent = client.updated_at ? new Date(client.updated_at).toLocaleString('ru-RU') : '—';
                loyaltyBadge.textContent = client.loyalty_label || client.loyalty_level || 'Не задан';
                notesEl.textContent = client.notes || '—';

                renderBadges(tagsContainer, client.tags || [], 'Теги не указаны.');
                renderBadges(allergiesContainer, client.allergies || [], 'Нет данных.');
                preferencesContainer.innerHTML = renderPreferences(client.preferences);
                renderHighlights(client, clientMeta.statistics || null, clientMeta.risk || null);
                renderStatistics(clientMeta.statistics || null);
                renderRisk(clientMeta.risk || null);

                if (clientMeta.has_pro_access) {
                    loadRecommendations();
                } else {
                    renderRecommendations([]);
                }

                if (client.id) {
                    editLink.href = '/clients/' + client.id + '/edit';
                    editLink.hidden = false;
                    reminderButton.disabled = false;
                }
            }

            async function loadClient() {
                try {
                    const response = await fetch('/api/v1/clients/' + clientId, {
                        headers: authHeaders(),
                        credentials: 'include',
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        showAlert('danger', result.error?.message || 'Не удалось загрузить данные клиента.');
                        subtitleEl.textContent = 'Ошибка загрузки данных.';
                        return;
                    }

                    fillClient(result.data, result.meta || {});
                } catch (error) {
                    console.error(error);
                    showAlert('danger', 'Произошла ошибка при загрузке клиента.');
                    subtitleEl.textContent = 'Ошибка загрузки данных.';
                }
            }

            function openReminderModal() {
                if (!currentClient || !reminderModal) {
                    return;
                }

                reminderTitle.textContent = `Автонапоминание для ${currentClient.name || 'клиента'}`;
                reminderMessageInput.value = reminderMessageTemplate || '';
                reminderErrors.textContent = '';
                renderReminderChannels(currentClient);
                reminderModal.show();
            }

            if (analyticsButton && analyticsModal) {
                analyticsButton.addEventListener('click', function () {
                    renderAnalyticsModal();
                    analyticsModal.show();
                    if (clientMeta.has_pro_access && !analyticsLoaded) {
                        loadAnalytics();
                    }
                });
            }

            if (reminderButton) {
                reminderButton.addEventListener('click', openReminderModal);
            }

            if (reminderSendBtn) {
                reminderSendBtn.addEventListener('click', async function () {
                    reminderErrors.textContent = '';
                    const message = reminderMessageInput.value.trim();
                    const channelInput = reminderChannels.querySelector('input[name="reminder-channel"]:checked');

                    if (!channelInput) {
                        reminderErrors.textContent = 'Выберите канал связи.';
                        return;
                    }

                    if (!message) {
                        reminderErrors.textContent = 'Добавьте текст напоминания.';
                        return;
                    }

                    try {
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            await navigator.clipboard.writeText(message);
                            showAlert('info', 'Текст напоминания скопирован. Отправьте его клиенту через выбранный канал.');
                        } else {
                            showAlert('info', 'Скопируйте текст напоминания вручную и отправьте клиенту.');
                        }
                    } catch (error) {
                        console.warn('Clipboard copy failed', error);
                        showAlert('info', 'Не удалось скопировать текст автоматически. Скопируйте вручную.');
                    }

                    if (reminderModal) {
                        reminderModal.hide();
                    }
                });
            }

            renderRisk(null);
            loadClient();
        });
    </script>
@endsection
