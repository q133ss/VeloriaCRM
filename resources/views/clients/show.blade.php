@extends('layouts.app')

@section('title', 'Клиент')

@section('content')
    <style>
        .client-show-page {
            --client-show-accent-soft: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.1);
            --client-show-border: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.12);
            --client-show-shadow: 0 24px 54px -36px rgba(37, 26, 84, 0.42);
        }

        .client-show-page .client-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--client-show-border);
            border-radius: 1.6rem;
            padding: 1.5rem;
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.14), transparent 34%),
                linear-gradient(140deg, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.06), rgba(var(--bs-info-rgb, 0, 207, 232), 0.05) 58%, rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.12));
            box-shadow: var(--client-show-shadow);
        }

        .client-show-page .client-hero::after {
            content: '';
            position: absolute;
            right: -3rem;
            bottom: -4rem;
            width: 12rem;
            height: 12rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08);
            filter: blur(12px);
        }

        .client-show-page .client-hero > * {
            position: relative;
            z-index: 1;
        }

        .client-show-page .client-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.72);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .client-show-page .client-eyebrow i {
            color: var(--bs-primary);
        }

        .client-show-page .hero-actions .btn {
            white-space: nowrap;
        }

        .client-show-page .surface-card {
            border: none;
            border-radius: 1.35rem;
            box-shadow: var(--client-show-shadow);
            background: color-mix(in srgb, var(--bs-card-bg) 96%, transparent);
        }

        .client-show-page .meta-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.8rem;
        }

        .client-show-page .meta-item {
            padding: 0.95rem 1rem;
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.03);
        }

        .client-show-page .meta-item span {
            display: block;
            margin-bottom: 0.25rem;
            color: var(--bs-secondary-color);
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .client-show-page .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .client-show-page .info-panel {
            padding: 1rem;
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.03);
        }

        .client-show-page .info-panel h6 {
            margin-bottom: 0.8rem;
        }

        .client-show-page .info-line {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin-bottom: 0.5rem;
        }

        .client-show-page .badge-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .client-show-page .badge-cloud .badge {
            border-radius: 999px;
            padding: 0.38rem 0.68rem;
        }

        .client-show-page .stack-card + .stack-card {
            margin-top: 1rem;
        }

        .client-show-page .risk-summary {
            padding: 0.95rem 1rem;
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.03);
        }

        .client-show-page .recommendations-shell details summary {
            cursor: pointer;
            list-style: none;
        }

        .client-show-page .recommendations-shell details summary::-webkit-details-marker {
            display: none;
        }

        .client-show-page .highlights-list li + li,
        .client-show-page #client-risk-signals li + li,
        .client-show-page #client-risk-suggestions li + li {
            margin-top: 0.45rem;
        }

        .client-show-page .analytics-note {
            color: var(--bs-secondary-color);
            font-size: 0.9rem;
        }

        .client-show-page details.surface-collapse {
            border-radius: 1.1rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.02);
        }

        .client-show-page details.surface-collapse > summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.25rem 0;
            cursor: pointer;
            list-style: none;
        }

        .client-show-page details.surface-collapse > summary::-webkit-details-marker {
            display: none;
        }

        .client-show-page details.surface-collapse > summary::after {
            content: 'Развернуть';
            color: var(--bs-secondary-color);
            font-size: 0.82rem;
            font-weight: 600;
        }

        .client-show-page details.surface-collapse[open] > summary::after {
            content: 'Свернуть';
        }

        .client-show-page .surface-collapse-body {
            padding-top: 1rem;
        }

        .client-show-page .client-lock-card {
            display: grid;
            gap: 1rem;
            align-items: center;
            padding: 1.15rem;
            border-radius: 1.2rem;
            border: 1px dashed rgba(var(--bs-primary-rgb, 255, 0, 252), 0.22);
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08), transparent 34%),
                rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.6);
        }

        .client-show-page .client-lock-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.4rem 0.8rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08);
            color: var(--bs-primary);
            font-size: 0.78rem;
            font-weight: 700;
        }

        .client-show-page .client-lock-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(180px, 0.8fr);
            gap: 1rem;
        }

        .client-show-page .client-lock-preview {
            display: grid;
            gap: 0.75rem;
            padding: 1rem;
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.03);
        }

        .client-show-page .client-lock-preview-pill {
            min-height: 2.8rem;
            border-radius: 0.9rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.05);
        }

        .client-show-page .soft-hidden {
            display: none;
        }

        html[data-bs-theme="dark"] .client-show-page .client-lock-card {
            background: rgba(20, 23, 34, 0.84);
        }

        html[data-bs-theme="dark"] .client-show-page .client-lock-preview,
        html[data-bs-theme="dark"] .client-show-page .client-lock-preview-pill {
            background: rgba(255, 255, 255, 0.03);
        }

        @media (max-width: 991.98px) {
            .client-show-page .meta-grid,
            .client-show-page .info-grid {
                grid-template-columns: 1fr;
            }

            .client-show-page .client-lock-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div id="client-view" class="client-show-page" data-client-id="{{ $clientId ?? '' }}">
        <div class="d-flex flex-column gap-4">
            <section class="client-hero">
                <div class="d-flex flex-column flex-xl-row align-items-xl-start justify-content-between gap-4">
                    <div class="d-flex flex-column gap-3">
                        <span class="client-eyebrow">
                            <i class="ri ri-user-heart-line"></i>
                            Карточка клиента
                        </span>
                        <div>
                            <h1 class="mb-2" id="client-name">Клиент</h1>
                            <p class="text-muted mb-0" id="client-subtitle">Загрузка информации...</p>
                        </div>
                    </div>
                    <div class="hero-actions d-flex flex-column flex-sm-row gap-2 align-self-start">
                        <button type="button" class="btn btn-outline-info" id="client-reminder-btn" disabled>
                            <i class="ri ri-mail-line me-1"></i>
                            Автонапоминание
                        </button>
                        <a href="#" class="btn btn-primary" id="client-edit-link" hidden>
                            <i class="ri ri-edit-line me-1"></i>
                            Редактировать
                        </a>
                        <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
                            <i class="ri ri-arrow-go-back-line me-1"></i>
                            К списку
                        </a>
                    </div>
                </div>
            </section>

            <div id="client-view-alerts"></div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="surface-card p-4 stack-card">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                            <h2 class="h5 mb-0">Контакты и профиль</h2>
                            <span class="badge bg-label-info" id="client-loyalty-badge">—</span>
                        </div>

                        <div class="meta-grid mb-3">
                            <div class="meta-item">
                                <span>Телефон</span>
                                <strong id="client-phone">—</strong>
                            </div>
                            <div class="meta-item">
                                <span>Email</span>
                                <strong id="client-email">—</strong>
                            </div>
                            <div class="meta-item">
                                <span>Последний визит</span>
                                <strong id="client-last-visit">—</strong>
                            </div>
                        </div>

                        <div class="info-grid">
                            <div class="info-panel">
                                <h6 class="text-muted text-uppercase small">Профиль</h6>
                                <div class="info-line">
                                    <i class="ri ri-cake-2-line text-muted"></i>
                                    <span>День рождения: <strong id="client-birthday">—</strong></span>
                                </div>
                                <span id="client-updated-at" class="soft-hidden">—</span>
                            </div>
                            <div class="info-panel">
                                <h6 class="text-muted text-uppercase small">Заметки для общения</h6>
                                <ul class="list-unstyled mb-0 highlights-list" id="client-highlights"></ul>
                            </div>
                        </div>
                    </div>

                    <div class="surface-card p-4 stack-card">
                        <details class="surface-collapse" id="client-personalization-details">
                            <summary>
                                <div>
                                    <h2 class="h5 mb-1">Персонализация</h2>
                                    <span class="analytics-note" id="client-personalization-hint">Показываем только дополнительные детали о клиенте.</span>
                                </div>
                            </summary>
                            <div class="surface-collapse-body">
                                <div class="info-grid">
                                    <div class="info-panel">
                                        <h6 class="text-muted text-uppercase small">Теги</h6>
                                        <div id="client-tags" class="badge-cloud"></div>
                                    </div>
                                    <div class="info-panel">
                                        <h6 class="text-muted text-uppercase small">Аллергии</h6>
                                        <div id="client-allergies" class="badge-cloud"></div>
                                    </div>
                                    <div class="info-panel">
                                        <h6 class="text-muted text-uppercase small">Предпочтения</h6>
                                        <div id="client-preferences" class="small"></div>
                                    </div>
                                    <div class="info-panel">
                                        <h6 class="text-muted text-uppercase small">Заметки</h6>
                                        <p class="mb-0" id="client-notes">—</p>
                                    </div>
                                </div>
                            </div>
                        </details>
                    </div>

                    <div class="surface-card p-4 stack-card">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                            <div>
                                <h2 class="h5 mb-0">Статистика по визитам</h2>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="client-analytics-btn" hidden>
                                    <i class="ri ri-bar-chart-line me-1"></i>
                                    Аналитика
                                </button>
                                <span class="badge bg-label-secondary">CRM</span>
                            </div>
                        </div>
                        <div id="client-statistics">
                            <p class="text-muted mb-0">Загрузка статистики...</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="surface-card p-4 stack-card">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                            <h2 class="h5 mb-0">Риск неявки</h2>
                            <span class="badge bg-label-secondary" id="client-risk-badge">—</span>
                        </div>
                        <div class="risk-summary mb-3" id="client-risk-score">Анализируем предыдущие записи клиента, чтобы подсказать, как снизить вероятность неявки.</div>
                        <div class="mb-3">
                            <h6 class="fw-semibold mb-2">Сигналы</h6>
                            <ul class="list-unstyled small mb-0" id="client-risk-signals"></ul>
                        </div>
                        <div>
                            <h6 class="fw-semibold mb-2">Что сделать</h6>
                            <ul class="list-unstyled small mb-0" id="client-risk-suggestions"></ul>
                        </div>
                    </div>

                    <div class="surface-card p-4 stack-card recommendations-shell">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                            <h2 class="h5 mb-0">Рекомендации ИИ</h2>
                            <span class="badge bg-label-secondary" id="client-ai-badge">ИИ</span>
                        </div>
                        <div class="client-lock-card" id="client-ai-lock" hidden>
                            <div class="client-lock-grid">
                                <div>
                                    <span class="client-lock-badge">
                                        <i class="ri ri-vip-crown-line"></i>
                                        {{ __('analytics.smart_lock.badge') }}
                                    </span>
                                    <h3 class="h5 mt-3 mb-2">{{ __('analytics.smart_lock.title') }}</h3>
                                    <p class="text-muted mb-3">{{ __('analytics.smart_lock.description') }}</p>
                                    <a href="{{ url('/subscription') }}" class="btn btn-primary">
                                        {{ __('analytics.smart_lock.cta') }}
                                    </a>
                                </div>
                                <div class="client-lock-preview" aria-hidden="true">
                                    <div class="client-lock-preview-pill"></div>
                                    <div class="client-lock-preview-pill"></div>
                                    <div class="client-lock-preview-pill"></div>
                                </div>
                            </div>
                        </div>
                        <details class="surface-collapse" id="client-ai-details">
                            <summary class="text-muted small">Показываем только то, что может помочь с удержанием и апсейлом.</summary>
                            <div class="surface-collapse-body">
                                <div id="client-ai-recommendations">
                                    <p class="text-muted mb-0">Загрузка...</p>
                                </div>
                            </div>
                        </details>
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
            const personalizationDetails = document.getElementById('client-personalization-details');
            const personalizationHint = document.getElementById('client-personalization-hint');
            const aiDetails = document.getElementById('client-ai-details');
            const aiLock = document.getElementById('client-ai-lock');
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

                const favoriteServices = Array.isArray(stats.favorite_services) ? stats.favorite_services : [];
                const recentServices = Array.isArray(stats.recent_services) ? stats.recent_services : [];

                const optionalPanels = [];
                if (favoriteServices.length) {
                    optionalPanels.push(`
                        <div class="info-panel">
                            <h6 class="fw-semibold small text-uppercase text-muted mb-2">Любимые услуги</h6>
                            <ul class="list-unstyled small mb-0">${favoriteServices.map(service => {
                                const count = service.count ?? 0;
                                const price = formatCurrency(service.average_price ?? 0);
                                return `<li><strong>${service.name || 'Услуга'}</strong> — ${count} виз., ср. чек ${price}</li>`;
                            }).join('')}</ul>
                        </div>
                    `);
                }
                if (recentServices.length) {
                    optionalPanels.push(`
                        <div class="info-panel">
                            <h6 class="fw-semibold small text-uppercase text-muted mb-2">Последние процедуры</h6>
                            <ul class="list-unstyled small mb-0">${recentServices.map(item => {
                                const price = item.price !== null && item.price !== undefined ? formatCurrency(item.price) : '—';
                                const performedAt = item.performed_at || '—';
                                return `<li><strong>${item.name || 'Услуга'}</strong> — ${price}, ${performedAt}</li>`;
                            }).join('')}</ul>
                        </div>
                    `);
                }

                statisticsContainer.innerHTML = `
                    <div class="meta-grid mb-3">
                        <div class="meta-item">
                            <span>Всего визитов</span>
                            <strong>${totalOrders}</strong>
                        </div>
                        <div class="meta-item">
                            <span>Средний чек</span>
                            <strong>${averageCheck}</strong>
                        </div>
                        <div class="meta-item">
                            <span>Удержание</span>
                            <strong>${retention}</strong>
                        </div>
                    </div>
                    <div class="info-grid">
                        <div class="info-panel">
                            <h6 class="fw-semibold small text-uppercase text-muted mb-2">Визиты</h6>
                            <ul class="list-unstyled small mb-0">
                                <li>Завершено: <strong>${completed}</strong></li>
                                <li>Отменено: <strong>${cancelled}</strong></li>
                                <li>Не пришёл: <strong>${noShow}</strong></li>
                                <li>Ближайший визит: <strong>${upcoming}</strong></li>
                                <li>Последний визит: <strong>${lastFromOrders}</strong></li>
                            </ul>
                        </div>
                        <div class="info-panel">
                            <h6 class="fw-semibold small text-uppercase text-muted mb-2">Финансы и ритм</h6>
                            <ul class="list-unstyled small mb-0">
                                <li>Lifetime Value: <strong>${lifetime}</strong></li>
                                <li>Выручка за 90 дней: <strong>${spend90}</strong></li>
                                <li>Средний интервал: <strong>${averageInterval}</strong></li>
                                <li>Средняя длительность: <strong>${averageDuration}</strong></li>
                            </ul>
                        </div>
                        ${optionalPanels.join('')}
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

                if (!clientMeta.has_elite_access) {
                    if (aiBadge) {
                        aiBadge.textContent = 'Elite';
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
                    block.className = 'info-panel mb-3';

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

                if (!clientMeta.has_elite_access) {
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
                if (!clientMeta.has_elite_access || analyticsLoading || analyticsLoaded) {
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
                if (!clientMeta.has_elite_access || recommendationsLoading || recommendationsLoaded) {
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

                const hasTags = Array.isArray(client.tags) && client.tags.length;
                const hasAllergies = Array.isArray(client.allergies) && client.allergies.length;
                const hasPreferences = Array.isArray(client.preferences)
                    ? client.preferences.length > 0
                    : (client.preferences && typeof client.preferences === 'object')
                        ? Object.keys(client.preferences).length > 0
                        : Boolean(client.preferences);
                const hasNotes = Boolean(client.notes);
                const hasPersonalization = hasTags || hasAllergies || hasPreferences || hasNotes;

                if (personalizationDetails) {
                    personalizationDetails.open = hasPersonalization;
                }
                if (personalizationHint) {
                    personalizationHint.textContent = hasPersonalization
                        ? 'Раскрыли блок, потому что у клиента уже есть дополнительные детали.'
                        : 'Блок можно не открывать, если карточка нужна только для контакта и визитов.';
                }
                if (aiDetails) {
                    aiDetails.open = false;
                }
                if (analyticsButton) {
                    analyticsButton.hidden = !clientMeta.has_elite_access;
                }
                if (aiDetails) {
                    aiDetails.hidden = !clientMeta.has_elite_access;
                }
                if (aiLock) {
                    aiLock.hidden = !!clientMeta.has_elite_access;
                }
                renderStatistics(clientMeta.statistics || null);
                renderRisk(clientMeta.risk || null);

                if (clientMeta.has_elite_access) {
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
                    if (!clientMeta.has_elite_access) {
                        return;
                    }
                    renderAnalyticsModal();
                    analyticsModal.show();
                    if (clientMeta.has_elite_access && !analyticsLoaded) {
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
