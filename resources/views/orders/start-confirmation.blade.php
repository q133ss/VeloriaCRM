@extends('layouts.app')

@section('title', 'Подтверждение начала процедуры')

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div id="alerts"></div>

            <div class="card mb-4" data-order-card hidden>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Запись <span data-order-code>#—</span></h4>
                        <p class="mb-0 text-muted">Пожалуйста, подтвердите, что процедура началась, либо отмените запись.</p>
                    </div>
                    <span class="badge" data-order-status>—</span>
                </div>
                <div class="card-body">
                    <dl class="row mb-4">
                        <dt class="col-sm-4 text-muted">Клиент</dt>
                        <dd class="col-sm-8" data-order-client>—</dd>

                        <dt class="col-sm-4 text-muted">Запланировано</dt>
                        <dd class="col-sm-8" data-order-scheduled>—</dd>

                        <dt class="col-sm-4 text-muted">Опоздание</dt>
                        <dd class="col-sm-8" data-order-late>—</dd>
                    </dl>

                    <div class="mb-4">
                        <label for="started_at" class="form-label">Фактическое начало</label>
                        <input type="datetime-local" class="form-control" id="started_at">
                        <div class="form-text">Значение сохранится в заказе при подтверждении начала.</div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-primary" data-action-confirm>
                            Подтвердить начало
                        </button>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#cancelCollapse" aria-expanded="false" aria-controls="cancelCollapse">
                            Отменить запись
                        </button>
                        <a class="btn btn-outline-secondary" href="/orders" data-back-link>Вернуться к списку</a>
                    </div>

                    <div class="collapse mt-4" id="cancelCollapse">
                        <div class="card card-body border border-danger-subtle">
                            <div class="mb-3">
                                <label for="cancel_reason" class="form-label">Причина отмены (необязательно)</label>
                                <textarea id="cancel_reason" class="form-control" rows="3" placeholder="Запись отменена, так как клиент не пришёл"></textarea>
                            </div>
                            <button type="button" class="btn btn-danger" data-action-cancel>Подтвердить отмену</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" data-loading-card>
                <div class="card-body text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3 mb-0 text-muted">Загружаем данные записи…</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        document.addEventListener('DOMContentLoaded', () => {
            const orderId = @json($orderId);
            const alerts = document.getElementById('alerts');
            const orderCard = document.querySelector('[data-order-card]');
            const loadingCard = document.querySelector('[data-loading-card]');
            const confirmButton = document.querySelector('[data-action-confirm]');
            const cancelButton = document.querySelector('[data-action-cancel]');
            const startedInput = document.getElementById('started_at');
            const cancelReasonInput = document.getElementById('cancel_reason');
            const orderCode = document.querySelector('[data-order-code]');
            const orderStatus = document.querySelector('[data-order-status]');
            const orderClient = document.querySelector('[data-order-client]');
            const orderScheduled = document.querySelector('[data-order-scheduled]');
            const orderLate = document.querySelector('[data-order-late]');
            const backLink = document.querySelector('[data-back-link]');

            let currentOrder = null;

            function authHeaders(extra = {}) {
                const token = (document.cookie.match(/(?:^|; )token=([^;]*)/) || [])[1];
                const headers = {
                    'Accept': 'application/json',
                    ...extra,
                };
                if (token) {
                    headers['Authorization'] = 'Bearer ' + token;
                }
                return headers;
            }

            function showAlert(type, message) {
                if (!alerts) return;
                const wrapper = document.createElement('div');
                wrapper.className = `alert alert-${type} alert-dismissible fade show`;
                wrapper.role = 'alert';
                wrapper.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
                `;
                alerts.appendChild(wrapper);
            }

            function clearAlerts() {
                if (!alerts) return;
                alerts.innerHTML = '';
            }

            function formatDateTime(isoString) {
                if (!isoString) return '—';
                const date = new Date(isoString);
                if (isNaN(date)) return '—';
                return date.toLocaleString('ru-RU', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                });
            }

            function isoToLocalInput(isoString) {
                if (!isoString) return '';
                const date = new Date(isoString);
                if (isNaN(date)) return '';
                const pad = (value) => String(value).padStart(2, '0');
                return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
            }

            function nowLocalInput() {
                return isoToLocalInput(new Date().toISOString());
            }

            function minutesLate(order) {
                if (!order?.scheduled_at) return null;
                const scheduledDate = new Date(order.scheduled_at);
                if (isNaN(scheduledDate)) return null;
                const diffMs = Date.now() - scheduledDate.getTime();
                const diffMinutes = Math.round(diffMs / 60000);
                return diffMinutes > 0 ? diffMinutes : 0;
            }

            function updateStatusBadge(order) {
                if (!orderStatus) return;
                orderStatus.className = 'badge ' + (order.status_class || 'bg-label-secondary');
                orderStatus.textContent = order.status_label || '—';
            }

            function renderOrder(order) {
                currentOrder = order;
                if (!order) {
                    return;
                }

                if (loadingCard) loadingCard.hidden = true;
                if (orderCard) orderCard.hidden = false;

                orderCode.textContent = `#${order.id}`;
                updateStatusBadge(order);
                orderClient.textContent = order.client?.name || 'Не указан';
                orderScheduled.textContent = formatDateTime(order.scheduled_at);

                const diff = minutesLate(order);
                orderLate.textContent = typeof diff === 'number'
                    ? `${diff} мин.`
                    : '—';

                const startedAt = order.actual_started_at || new Date().toISOString();
                startedInput.value = isoToLocalInput(startedAt) || nowLocalInput();

                if (order.status === 'cancelled') {
                    confirmButton.disabled = true;
                    cancelButton.disabled = true;
                }

                if (backLink) {
                    backLink.href = `/orders/${order.id}`;
                }
            }

            async function loadOrder() {
                try {
                    const response = await fetch(`/api/v1/orders/${orderId}`, {
                        headers: authHeaders(),
                        credentials: 'include',
                    });

                    if (!response.ok) {
                        throw new Error('Не удалось загрузить запись.');
                    }

                    const payload = await response.json();
                    renderOrder(payload.data);
                } catch (error) {
                    if (loadingCard) loadingCard.hidden = false;
                    if (orderCard) orderCard.hidden = true;
                    showAlert('danger', error.message || 'Не удалось загрузить запись.');
                }
            }

            async function confirmStart() {
                if (!startedInput.value) {
                    showAlert('warning', 'Укажите фактическое время начала.');
                    return;
                }

                const startedDate = new Date(startedInput.value);
                if (isNaN(startedDate)) {
                    showAlert('warning', 'Некорректный формат даты начала.');
                    return;
                }

                confirmButton.disabled = true;
                clearAlerts();

                try {
                    const response = await fetch(`/api/v1/orders/${orderId}/start`, {
                        method: 'POST',
                        headers: authHeaders({ 'Content-Type': 'application/json' }),
                        credentials: 'include',
                        body: JSON.stringify({
                            started_at: startedDate.toISOString(),
                        }),
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(payload.error?.message || 'Не удалось подтвердить начало.');
                    }

                    showAlert('success', payload.message || 'Начало процедуры зафиксировано.');
                    if (payload.data) {
                        renderOrder(payload.data);
                    } else {
                        await loadOrder();
                    }
                } catch (error) {
                    showAlert('danger', error.message || 'Не удалось подтвердить начало.');
                } finally {
                    confirmButton.disabled = false;
                }
            }

            async function cancelOrder() {
                confirmButton.disabled = true;
                cancelButton.disabled = true;
                clearAlerts();

                try {
                    const response = await fetch(`/api/v1/orders/${orderId}/cancel`, {
                        method: 'POST',
                        headers: authHeaders({ 'Content-Type': 'application/json' }),
                        credentials: 'include',
                        body: JSON.stringify({
                            reason: cancelReasonInput.value || null,
                        }),
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(payload.error?.message || 'Не удалось отменить запись.');
                    }

                    showAlert('success', payload.message || 'Запись отменена.');
                    if (payload.data) {
                        renderOrder(payload.data);
                    } else {
                        await loadOrder();
                    }
                } catch (error) {
                    showAlert('danger', error.message || 'Не удалось отменить запись.');
                } finally {
                    confirmButton.disabled = false;
                    cancelButton.disabled = false;
                }
            }

            if (confirmButton) {
                confirmButton.addEventListener('click', confirmStart);
            }

            if (cancelButton) {
                cancelButton.addEventListener('click', cancelOrder);
            }

            loadOrder();
        });
    </script>
@endpush
