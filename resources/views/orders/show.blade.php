@extends('layouts.app')

@section('title', 'Запись')

@section('meta')
    <style>
        /* Order header actions: keep title full-width and move actions below it. */
        #order-action-buttons .btn {
            white-space: nowrap;
        }

        /* Order history timeline: prevent overly tall items and align content neatly. */
        #order-history.timeline {
            margin-bottom: 0;
        }

        #order-history .timeline-item {
            align-items: flex-start;
        }

        #order-history .timeline-event {
            min-height: auto !important;
            padding-top: 0 !important;
        }

        #order-history .timeline-header {
            align-items: flex-start !important;
            flex-wrap: wrap;
            gap: 0.5rem 1rem;
        }

        #order-history .timeline-header small {
            white-space: nowrap;
        }

        .timeline-indicator{
            box-shadow: 0 0 0 1px var(--bs-body-bg)!important;
            top: 5px!important;
        }
    </style>
@endsection

@section('content')
    <div id="order-view" data-order-id="{{ $orderId ?? '' }}">
        <div class="mb-4">
            <div class="d-flex flex-column gap-2">
                <div>
                    <h4 class="mb-1" id="order-title">Запись</h4>
                    <p class="text-muted mb-0" id="order-subtitle">Загрузка данных...</p>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2" id="order-action-buttons" hidden>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="#" class="btn btn-outline-primary" id="action-edit">
                            <i class="ri ri-edit-line me-1"></i>
                            Редактировать
                        </a>
                        <button type="button" class="btn btn-outline-secondary" id="action-analytics" data-bs-toggle="modal" data-bs-target="#analyticsModal">
                            <i class="ri ri-bar-chart-line me-1"></i>
                            Аналитика клиента
                        </button>
                    </div>

                    <div class="w-100"></div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success" id="action-start">
                            <i class="ri ri-play-line me-1"></i>
                            Начать работу
                        </button>
                        <button type="button" class="btn btn-success" id="action-complete">
                            <i class="ri ri-check-line me-1"></i>
                            Завершить
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="action-reschedule" data-bs-toggle="modal" data-bs-target="#rescheduleModal">
                            <i class="ri ri-calendar-line me-1"></i>
                            Перенести
                        </button>
                        <button type="button" class="btn btn-outline-info" id="action-remind">
                            <i class="ri ri-mail-line me-1"></i>
                            Напомнить
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="action-cancel">
                            <i class="ri ri-close-line me-1"></i>
                            Отменить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="order-alerts"></div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Основное</h5>
                        <span class="badge" id="order-status">—</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6 class="text-muted">Клиент</h6>
                                <p class="mb-1" id="order-client-name">—</p>
                                <p class="mb-1">
                                    <i class="ri ri-phone-line me-1"></i>
                                    <span id="order-client-phone">—</span>
                                </p>
                                <p class="mb-0">
                                    <i class="ri ri-mail-line me-1"></i>
                                    <span id="order-client-email">—</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Финансы</h6>
                                <p class="mb-1">Итоговая сумма: <strong id="order-total">—</strong></p>
                                <p class="mb-0">Источник: <span id="order-source">manual</span></p>
                            </div>
                            <div class="col-12">
                                <h6 class="text-muted">Услуги</h6>
                                <div id="order-services" class="row g-3"></div>
                            </div>
                            <div class="col-12">
                                <h6 class="text-muted">Заметка мастера</h6>
                                <p class="mb-0" id="order-note">—</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">История</h5>
                    </div>
                    <div class="card-body">
                        <ul class="timeline" id="order-history">
                            <li class="timeline-item">
                                <span class="timeline-indicator"><i class="ri ri-time-line"></i></span>
                                <div class="timeline-event">
                                    <div class="timeline-header">
                                        <h6 class="mb-0">Загрузка...</h6>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Время</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-6">Запланировано</dt>
                            <dd class="col-6 text-end" id="order-scheduled">—</dd>
                            <dt class="col-6">Фактическое начало</dt>
                            <dd class="col-6 text-end" id="order-started">—</dd>
                            <dt class="col-6">Фактическое окончание</dt>
                            <dd class="col-6 text-end" id="order-finished">—</dd>
                            <dt class="col-6">Длительность</dt>
                            <dd class="col-6 text-end" id="order-duration">—</dd>
                            <dt class="col-6">Прогноз</dt>
                            <dd class="col-6 text-end" id="order-forecast">—</dd>
                        </dl>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Рекомендации ИИ</h5>
                        <span class="badge bg-label-secondary" id="ai-recommendations-badge">ИИ</span>
                    </div>
                    <div class="card-body" id="order-ai-recommendations">
                        <p class="text-muted mb-0">Загрузка...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rescheduleModal" tabindex="-1" aria-labelledby="rescheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rescheduleModalLabel">Перенос записи</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="reschedule-form" onsubmit="return false;">
                    <div class="modal-body">
                        <div class="form-floating form-floating-outline mb-3">
                            <input type="datetime-local" class="form-control" id="reschedule_at" name="scheduled_at" required />
                            <label for="reschedule_at">Новая дата и время</label>
                        </div>
                        <div id="reschedule-errors"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Перенести</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="analyticsModal" tabindex="-1" aria-labelledby="analyticsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="analyticsModalLabel">Аналитика клиента</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="analytics-content">
                    <p class="text-muted">Загрузка...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
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

        const viewContainer = document.getElementById('order-view');
        const orderId = viewContainer.getAttribute('data-order-id');
        const alertsContainer = document.getElementById('order-alerts');
        const actionButtons = document.getElementById('order-action-buttons');
        const rescheduleForm = document.getElementById('reschedule-form');
        const rescheduleErrors = document.getElementById('reschedule-errors');
        const analyticsContent = document.getElementById('analytics-content');
        const aiBadge = document.getElementById('ai-recommendations-badge');

        const actionEdit = document.getElementById('action-edit');
        const actionStart = document.getElementById('action-start');
        const actionComplete = document.getElementById('action-complete');
        const actionReschedule = document.getElementById('action-reschedule');
        const actionRemind = document.getElementById('action-remind');
        const actionCancel = document.getElementById('action-cancel');
        const actionAnalytics = document.getElementById('action-analytics');

        let currentOrder = null;
        let meta = { has_pro_access: false, reminder_message: null };
        let analyticsData = null;
        let analyticsLoaded = false;
        let analyticsLoading = false;
        let analyticsError = null;

        function showAlert(type, message, sticky = false) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-' + type + ' alert-dismissible fade show';
            alert.setAttribute('role', 'alert');
            alert.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
            alertsContainer.appendChild(alert);
            if (!sticky) {
                setTimeout(() => {
                    alert.classList.remove('show');
                    alert.addEventListener('transitionend', () => alert.remove());
                }, 5000);
            }
        }

        function formatDateTime(value) {
            if (!value) return '—';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return '—';
            return date.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        }

        function formatDate(value) {
            if (!value) return '—';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return '—';
            return date.toLocaleDateString('ru-RU');
        }

        function formatCurrency(value) {
            if (value === null || value === undefined) return '—';
            const number = Number(value);
            if (Number.isNaN(number)) return '—';
            return number.toLocaleString('ru-RU', { style: 'currency', currency: 'RUB' });
        }

        function isoToLocalInput(value) {
            if (!value) return '';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return '';
            date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
            return date.toISOString().slice(0, 16);
        }

        function renderServices(services) {
            const container = document.getElementById('order-services');
            container.innerHTML = '';
            if (!services.length) {
                container.innerHTML = '<div class="col-12 text-muted">Услуги не выбраны.</div>';
                return;
            }
            services.forEach(service => {
                const col = document.createElement('div');
                col.className = 'col-md-6';
                col.innerHTML = `
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-medium">${service.name}</span>
                            <span class="badge bg-label-primary">${service.price !== null && service.price !== undefined ? Number(service.price).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₽' : '—'}</span>
                        </div>
                        <small class="text-muted">Длительность: ${(service.duration || 0)} мин</small>
                    </div>
                `;
                container.appendChild(col);
            });
        }

        function renderHistory(history) {
            const list = document.getElementById('order-history');
            list.innerHTML = '';
            if (!history.length) {
                list.innerHTML = '<li class="timeline-item"><span class="timeline-indicator"><i class="ri ri-time-line"></i></span><div class="timeline-event"><div class="timeline-header"><h6 class="mb-0">История пуста</h6></div></div></li>';
                return;
            }

            history.forEach(event => {
                const item = document.createElement('li');
                item.className = 'timeline-item';
                item.innerHTML = `
                    <span class="timeline-indicator"><i class="ri ri-checkbox-circle-line"></i></span>
                    <div class="timeline-event">
                        <div class="timeline-header d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <h6 class="mb-0">${event.label}</h6>
                            <small class="text-muted">${event.time || '—'}</small>
                        </div>
                        <p class="mb-0">${event.description || ''}</p>
                    </div>
                `;
                list.appendChild(item);
            });
        }

        function renderRecommendations(recommendations) {
            const container = document.getElementById('order-ai-recommendations');
            container.innerHTML = '';
            if (!meta.has_pro_access) {
                if (aiBadge) {
                    aiBadge.textContent = 'PRO';
                    aiBadge.className = 'badge bg-label-secondary';
                }
                container.innerHTML = '<p class="text-muted mb-0">Доступно только в тарифах PRO и Elite.</p>';
                return;
            }
            if (aiBadge) {
                aiBadge.textContent = 'ИИ';
                aiBadge.className = 'badge bg-label-primary';
            }
            if (!Array.isArray(recommendations) || !recommendations.length) {
                container.innerHTML = '<p class="text-muted mb-0">Рекомендаций пока нет.</p>';
                return;
            }
            recommendations.forEach(item => {
                const block = document.createElement('div');
                block.className = 'mb-3';

                const service = item.service || {};
                const title = service.name || item.title || 'Рекомендация';
                const price = typeof service.price === 'number' ? service.price : null;
                const duration = typeof service.duration === 'number' ? service.duration : null;

                let confidence = null;
                if (typeof item.confidence === 'number' && !Number.isNaN(item.confidence)) {
                    const normalized = Math.min(1, Math.max(0, item.confidence));
                    confidence = Math.round(normalized * 100);
                }

                const metaParts = [];
                if (price !== null) {
                    metaParts.push(`${price.toLocaleString('ru-RU')} ₽`);
                }
                if (duration !== null) {
                    metaParts.push(`${duration} мин`);
                }
                const meta = metaParts.length ? `<p class="small text-muted mb-2">${metaParts.join(' · ')}</p>` : '';

                const insight = item.insight || 'Персонализированная рекомендация ИИ.';
                const action = item.action ? `<p class="small mb-0">${item.action}</p>` : '';

                block.innerHTML = `
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div class="flex-grow-1">
                            <strong>${title}</strong>
                            ${meta}
                            <p class="text-muted small mb-1">${insight}</p>
                            ${action}
                        </div>
                        ${confidence !== null ? `<span class="badge bg-label-info align-self-start">${confidence}%</span>` : ''}
                    </div>
                `;
                container.appendChild(block);
            });
        }

        function renderAnalytics(data) {
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
            if (metrics.loyalty_level) {
                html += `<li class="mt-2">Уровень лояльности: <strong>${metrics.loyalty_level}</strong></li>`;
            }
            html += '</ul></div>';

            analyticsContent.innerHTML = html;
        }

        function renderAnalyticsModal() {
            if (!meta.has_pro_access) {
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
            if (!meta.has_pro_access) {
                renderAnalyticsModal();
                return;
            }

            if (analyticsLoading || analyticsLoaded) {
                renderAnalyticsModal();
                return;
            }

            analyticsLoading = true;
            analyticsError = null;
            analyticsData = null;
            renderAnalyticsModal();

            try {
                const response = await fetch(`/api/v1/orders/${orderId}/analytics`, {
                    headers: authHeaders(),
                    credentials: 'include',
                });

                if (!response.ok) {
                    const error = await response.json().catch(() => ({}));
                    analyticsError = error.error?.message || 'Не удалось получить аналитику.';
                    return;
                }

                analyticsData = await response.json();
                analyticsLoaded = true;
            } catch (error) {
                analyticsError = 'Не удалось получить аналитику.';
            } finally {
                analyticsLoading = false;

                if (analyticsError) {
                    analyticsContent.innerHTML = `<p class="text-danger mb-0">${analyticsError}</p>`;
                } else {
                    renderAnalyticsModal();
                }
            }
        }

        function renderOrder(order) {
            document.getElementById('order-title').textContent = order.client?.name ? `Запись: ${order.client.name}` : 'Запись';
            document.getElementById('order-subtitle').textContent = order.scheduled_at_formatted ? `Назначено на ${order.scheduled_at_formatted}` : 'Дата не указана';

            const statusBadge = document.getElementById('order-status');
            statusBadge.className = 'badge ' + (order.status_class || 'bg-label-secondary');
            statusBadge.textContent = order.status_label || '—';

            document.getElementById('order-client-name').textContent = order.client?.name || 'Не указан';
            document.getElementById('order-client-phone').textContent = order.client?.phone || '—';
            document.getElementById('order-client-email').textContent = order.client?.email || '—';
            document.getElementById('order-source').textContent = order.source || 'manual';
            document.getElementById('order-note').textContent = order.note || 'Нет заметок';

            document.getElementById('order-total').textContent = order.total_price !== null && order.total_price !== undefined
                ? Number(order.total_price).toLocaleString('ru-RU', { style: 'currency', currency: 'RUB' })
                : '—';

            document.getElementById('order-scheduled').textContent = formatDateTime(order.scheduled_at);
            document.getElementById('order-started').textContent = formatDateTime(order.actual_started_at);
            document.getElementById('order-finished').textContent = formatDateTime(order.actual_finished_at);
            document.getElementById('order-duration').textContent = order.duration ? `${order.duration} мин` : '—';
            document.getElementById('order-forecast').textContent = order.duration_forecast ? `${order.duration_forecast} мин` : '—';

            renderServices(order.services || []);
            renderHistory(order.history || []);
            renderRecommendations(order.recommended_services || []);
            renderAnalyticsModal();

            actionEdit.href = `/orders/${order.id}/edit`;
            actionButtons.hidden = false;

            const canStartNow = !!order.actions?.can_start_now;
            actionStart.hidden = !canStartNow;
            actionStart.disabled = !canStartNow;
            actionStart.dataset.warning = order.actions?.start_warning ? '1' : '0';

            const canComplete = !!order.actions?.can_complete;
            actionComplete.hidden = !canComplete;
            actionComplete.disabled = !canComplete;

            const canReschedule = !!order.actions?.can_reschedule;
            actionReschedule.hidden = !canReschedule;
            actionReschedule.disabled = !canReschedule;

            const canCancel = !!order.actions?.can_cancel;
            actionCancel.hidden = !canCancel;
            actionCancel.disabled = !canCancel;

            const isFinished = ['completed', 'cancelled'].includes(order.status);
            const canRemind = !!meta.reminder_message && !isFinished && !order.is_reminder_sent;
            actionRemind.hidden = !canRemind;
            actionRemind.disabled = !canRemind;

            const canSeeAnalytics = !!meta.has_pro_access && !!order.client?.id;
            actionAnalytics.hidden = !canSeeAnalytics;
            actionAnalytics.disabled = !canSeeAnalytics;
        }

        async function loadOrder() {
            const response = await fetch(`/api/v1/orders/${orderId}`, {
                headers: authHeaders(),
                credentials: 'include',
            });

            if (!response.ok) {
                showAlert('danger', 'Не удалось загрузить запись.');
                return;
            }

            const data = await response.json();
            currentOrder = data.data;
            meta = data.meta || meta;
            if (!meta.reminder_message) {
                showAlert('warning', 'Добавьте текст автонапоминания в настройках, чтобы отправлять напоминания. <a href="/settings" class="alert-link">Перейти в настройки</a>.', true);
            }
            renderOrder(currentOrder);
        }

        async function performAction(url, method = 'POST', body = {}) {
            const response = await fetch(url, {
                method: method,
                headers: authHeaders(),
                credentials: 'include',
                body: method === 'GET' ? null : JSON.stringify(body),
            });

            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                showAlert('danger', result.error?.message || 'Не удалось выполнить действие.');
                return null;
            }

            if (result.reminder_text) {
                showAlert('info', '<strong>Текст автонапоминания:</strong><div class="mt-2 small">' + result.reminder_text.replace(/\n/g, '<br>') + '</div>', true);
            }

            showAlert('success', result.message || 'Действие выполнено.');
            if (result.data) {
                currentOrder = result.data;
                renderOrder(currentOrder);
            } else {
                loadOrder();
            }
            return result;
        }

        actionStart.addEventListener('click', function () {
            if (this.disabled) return;
            if (this.dataset.warning === '1') {
                if (!confirm('До записи остаётся достаточно времени. Вы уверены, что хотите начать работу сейчас?')) {
                    return;
                }
            }
            performAction(`/api/v1/orders/${orderId}/start`);
        });

        actionComplete.addEventListener('click', function () {
            if (this.disabled) return;
            performAction(`/api/v1/orders/${orderId}/complete`);
        });

        actionReschedule.addEventListener('click', function () {
            rescheduleErrors.innerHTML = '';
            const input = document.getElementById('reschedule_at');
            input.value = isoToLocalInput(currentOrder?.scheduled_at);
        });

        rescheduleForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            rescheduleErrors.innerHTML = '';
            const input = rescheduleForm.scheduled_at.value;
            const result = await performAction(`/api/v1/orders/${orderId}/reschedule`, 'POST', { scheduled_at: input });
            if (result) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('rescheduleModal'));
                if (modal) modal.hide();
            } else {
                rescheduleErrors.innerHTML = '<div class="text-danger">Не удалось перенести запись.</div>';
            }
        });

        actionRemind.addEventListener('click', function () {
            if (this.disabled) return;
            performAction(`/api/v1/orders/${orderId}/remind`);
        });

        actionCancel.addEventListener('click', function () {
            if (this.disabled) return;
            if (!confirm('Вы уверены, что хотите отменить запись?')) return;
            performAction(`/api/v1/orders/${orderId}/cancel`, 'POST', {});
        });

        actionAnalytics.addEventListener('click', function () {
            if (!meta.has_pro_access) {
                renderAnalyticsModal();
                return;
            }

            if (analyticsLoaded) {
                renderAnalyticsModal();
                return;
            }

            loadAnalytics();
        });

        loadOrder();
    </script>
@endsection
