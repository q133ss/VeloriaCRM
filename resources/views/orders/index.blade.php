@extends('layouts.app')

@section('title', 'Записи')

@section('meta')
    @include('components.veloria-datetime-picker-styles')
@endsection

@section('content')
    <div class="orders-page">
        <div class="d-flex flex-column gap-4">
            <section class="orders-hero">
                <div class="d-flex flex-column flex-xl-row gap-4 justify-content-between align-items-xl-start">
                    <div class="orders-hero__content d-flex flex-column gap-3">
                        <span class="orders-eyebrow">
                            <i class="ri ri-calendar-check-line"></i>
                            Записи под контролем
                        </span>
                        <div>
                            <h1 class="orders-hero__title mb-2">Записи</h1>
                            <p class="text-muted mb-0 fs-6">
                                Один экран для расписания, статусов и быстрых действий без лишнего визуального шума.
                            </p>
                        </div>
                        <div class="orders-overview">
                            <div class="orders-overview-card">
                                <span>Всего записей</span>
                                <strong id="orders-hero-total">0</strong>
                            </div>
                            <div class="orders-overview-card">
                                <span>Период</span>
                                <strong id="orders-hero-period">Все записи</strong>
                            </div>
                            <div class="orders-overview-card">
                                <span>Выбрано</span>
                                <strong><span id="orders-selected-count">0</span> для действий</strong>
                            </div>
                        </div>
                    </div>

                    <div class="orders-hero__actions d-flex flex-column flex-sm-row gap-2 align-self-start">
                        <button class="btn orders-soft-btn" data-bs-toggle="modal" data-bs-target="#quickCreateModal">
                            <i class="ri ri-flashlight-line me-1"></i>
                            Быстрое создание
                        </button>
                        <a href="{{ route('orders.create') }}" class="btn btn-primary">
                            <i class="ri ri-add-line me-1"></i>
                            Новая запись
                        </a>
                    </div>
                </div>
            </section>

            <div id="orders-alerts"></div>

            <style>
        .orders-page {
            --orders-accent-soft: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.12);
            --orders-card-shadow: 0 20px 48px -34px rgba(37, 26, 84, 0.45);
        }

        .orders-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(var(--bs-primary-rgb, 255, 0, 252), 0.14);
            border-radius: 1.5rem;
            padding: 1.5rem;
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.15), transparent 34%),
                linear-gradient(135deg, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08), rgba(var(--bs-primary-rgb, 255, 0, 252), 0.02) 52%, rgba(var(--bs-info-rgb, 0, 207, 232), 0.05));
            box-shadow: var(--orders-card-shadow);
        }

        .orders-hero::after {
            content: '';
            position: absolute;
            right: -3rem;
            bottom: -4rem;
            width: 12rem;
            height: 12rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08);
            filter: blur(8px);
        }

        .orders-hero__content,
        .orders-hero__actions {
            position: relative;
            z-index: 1;
        }

        .orders-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.68);
            color: var(--bs-body-color);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .orders-eyebrow i {
            color: var(--bs-primary);
        }

        .orders-hero__title {
            font-size: clamp(1.85rem, 2.6vw, 2.6rem);
            line-height: 1.05;
            letter-spacing: -0.03em;
        }

        .orders-overview {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.9rem;
        }

        .orders-overview-card {
            border: 1px solid rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08);
            border-radius: 1.05rem;
            padding: 1rem 1.05rem;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.76);
            backdrop-filter: blur(6px);
        }

        .orders-overview-card span {
            display: block;
            color: var(--bs-secondary-color);
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.45rem;
        }

        .orders-overview-card strong {
            display: block;
            font-size: 1rem;
            line-height: 1.3;
        }

        .orders-soft-btn {
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.72);
            border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.18);
        }

        .orders-soft-btn:hover,
        .orders-soft-btn:focus {
            border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.35);
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.06);
            color: var(--bs-primary);
        }

        .orders-hero__actions .btn {
            white-space: nowrap;
        }

        .orders-surface {
            border: none;
            border-radius: 1.35rem;
            box-shadow: var(--orders-card-shadow);
            background: color-mix(in srgb, var(--bs-card-bg) 94%, transparent);
        }

        .orders-filters-grid {
            display: grid;
            grid-template-columns: 1.1fr 1fr 1.4fr auto;
            gap: 0.85rem;
        }

        .orders-bulk-bar {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.25rem 0;
        }

        .orders-bulk-bar.is-visible {
            display: flex;
        }

        .orders-bulk-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .orders-table-wrap {
            padding: 0 1rem 1rem;
        }

        .orders-table {
            margin-bottom: 0;
        }

        .orders-table thead th {
            color: var(--bs-secondary-color);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom-width: 1px;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.68);
        }

        .orders-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .orders-table tbody tr:hover {
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.03);
        }

        .orders-date-cell strong,
        .orders-client-cell strong {
            display: block;
            font-size: 0.96rem;
        }

        .orders-client-cell small,
        .orders-date-cell small {
            display: block;
            margin-top: 0.18rem;
        }

        .orders-services {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
        }

        .orders-service-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.28rem 0.6rem;
            border-radius: 999px;
            background: var(--orders-accent-soft);
            color: var(--bs-primary);
            font-size: 0.76rem;
            font-weight: 600;
        }

        .orders-empty {
            padding: 3.5rem 1rem !important;
        }

        .orders-pagination {
            padding: 0 1.25rem 1.1rem;
            border-top: none;
            background: transparent;
        }

        .orders-reminder-note {
            display: none;
            border-radius: 1rem;
            padding: 0.85rem 1rem;
            background: rgba(var(--bs-warning-rgb, 255, 159, 67), 0.14);
            color: var(--bs-warning-text-emphasis, var(--bs-warning));
        }

        .orders-reminder-note.is-visible {
            display: block;
        }

        @media (max-width: 991.98px) {
            .orders-overview,
            .orders-filters-grid {
                grid-template-columns: 1fr;
            }

            .orders-bulk-bar {
                flex-direction: column;
                align-items: flex-start;
            }
        }

    </style>

            <section class="card orders-surface">
                <div class="card-body p-4 d-flex flex-column gap-3">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
                        <div>
                            <h2 class="h5 mb-1">Фильтры и поиск</h2>
                            <p class="text-muted mb-0">Оставили только те параметры, которые помогают быстро найти нужную запись.</p>
                        </div>
                        <div class="orders-reminder-note" id="orders-reminder-note"></div>
                    </div>

                    <form id="filters-form" class="orders-filters-grid align-items-end">
                        <div>
                            <label for="filter-period" class="form-label">Период</label>
                            <select class="form-select" id="filter-period" name="period"></select>
                        </div>
                        <div>
                            <label for="filter-status" class="form-label">Статус</label>
                            <select class="form-select" id="filter-status" name="status"></select>
                        </div>
                        <div>
                            <label for="filter-search" class="form-label">Поиск клиента</label>
                            <input
                                type="text"
                                class="form-control"
                                id="filter-search"
                                name="search"
                                placeholder="Имя или телефон клиента"
                            />
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">Применить</button>
                            <button type="button" id="filters-reset" class="btn btn-outline-secondary flex-fill">Сбросить</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="card orders-surface" id="orders-card">
                <div class="card-body p-0">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 px-4 pt-4">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h2 class="h5 mb-0">Список записей</h2>
                                <span class="badge bg-label-secondary" id="orders-total">0</span>
                            </div>
                            <p class="text-muted mb-0">Клиент, время и статус читаются первыми. Остальные действия доступны без перегрузки экрана.</p>
                        </div>
                    </div>

                    <div class="orders-bulk-bar" id="orders-bulk-bar">
                        <div class="text-muted">
                            Выбрано <strong id="orders-bulk-selected">0</strong> записей
                        </div>
                        <div class="orders-bulk-actions">
                            <button type="button" class="btn btn-success btn-sm bulk-action-btn" data-action="confirm" disabled>
                                <i class="ri ri-check-double-line me-1"></i>
                                Подтвердить
                            </button>
                            <button type="button" class="btn btn-info btn-sm text-white bulk-action-btn" data-action="remind" id="bulk-remind-btn" disabled>
                                <i class="ri ri-mail-line me-1"></i>
                                Напомнить
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm bulk-action-btn" data-action="cancel" disabled>
                                <i class="ri ri-close-circle-line me-1"></i>
                                Отменить
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive orders-table-wrap">
                        <table class="table table-hover mb-0 align-middle orders-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" class="form-check-input" id="select-all" />
                                    </th>
                                    <th>Дата и мастер</th>
                                    <th>Клиент</th>
                                    <th>Услуги</th>
                                    <th>Статус</th>
                                    <th class="text-end">Сумма</th>
                                    <th class="text-end">Действия</th>
                                </tr>
                            </thead>
                            <tbody id="orders-body">
                                <tr>
                                    <td colspan="7" class="text-center text-muted orders-empty">Загрузка данных...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 orders-pagination" id="orders-pagination">
                    <div class="text-muted small" id="orders-summary">Загрузка...</div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="pagination-list"></ul>
                    </nav>
                </div>
            </section>

            @include('components.order-quick-create-modal')
        </div>
    </div>
@endsection

@section('scripts')
    @include('components.phone-mask-script')
    @include('components.veloria-datetime-picker-script')
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

        const state = {
            filters: {
                period: 'this_week',
                status: 'all',
                search: ''
            },
            page: 1,
            perPage: 12,
            reminderMessage: null,
            total: 0,
        };

        const selectedOrders = new Set();

        const ordersAlerts = document.getElementById('orders-alerts');
        const periodSelect = document.getElementById('filter-period');
        const statusSelect = document.getElementById('filter-status');
        const searchInput = document.getElementById('filter-search');
        const ordersBody = document.getElementById('orders-body');
        const ordersTotal = document.getElementById('orders-total');
        const ordersSummary = document.getElementById('orders-summary');
        const paginationList = document.getElementById('pagination-list');
        const selectAllCheckbox = document.getElementById('select-all');
        const bulkButtons = document.querySelectorAll('.bulk-action-btn');
        const bulkRemindBtn = document.getElementById('bulk-remind-btn');
        const ordersHeroTotal = document.getElementById('orders-hero-total');
        const ordersHeroPeriod = document.getElementById('orders-hero-period');
        const ordersSelectedCount = document.getElementById('orders-selected-count');
        const ordersBulkBar = document.getElementById('orders-bulk-bar');
        const ordersBulkSelected = document.getElementById('orders-bulk-selected');
        const ordersReminderNote = document.getElementById('orders-reminder-note');
        const quickForm = document.getElementById('quick-create-form');
        const quickServicesContainer = document.getElementById('quick-services-container');
        const quickServicesSummary = document.getElementById('quick-services-summary');
        const quickClientIdInput = document.getElementById('quick_client_id');
        const quickClientSearchInput = document.getElementById('quick_client_search');
        const quickClientPhoneInput = document.getElementById('quick_client_phone');
        const quickClientNameInput = document.getElementById('quick_client_name');
        const quickSelectedClient = document.getElementById('quick-selected-client');
        const quickClientResults = document.getElementById('quick-client-results');
        const quickClientSuggestions = document.getElementById('quick-client-suggestions');
        let quickLookupController = null;
        let quickLookupTimer = null;
        let quickRecentClients = [];

        function showAlert(type, message, sticky = false) {
            const wrapper = document.createElement('div');
            wrapper.className = 'alert alert-' + type + ' alert-dismissible fade show';
            wrapper.setAttribute('role', 'alert');
            wrapper.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            ordersAlerts.appendChild(wrapper);
            if (!sticky) {
                setTimeout(() => {
                    wrapper.classList.remove('show');
                    wrapper.addEventListener('transitionend', () => wrapper.remove());
                }, 5000);
            }
        }

        function clearAlerts() {
            ordersAlerts.innerHTML = '';
        }

        function renderOptions(selectElement, options, selected) {
            selectElement.innerHTML = '';
            Object.keys(options).forEach(function (key) {
                const option = document.createElement('option');
                option.value = key;
                option.textContent = options[key];
                if (selected === key) {
                    option.selected = true;
                }
                selectElement.appendChild(option);
            });
        }

        function renderOrders(orders) {
            ordersBody.innerHTML = '';
            selectedOrders.clear();
            selectAllCheckbox.checked = false;
            updateBulkButtons();

            if (!orders.length) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = '<td colspan="7" class="text-center text-muted orders-empty">Записей пока нет.</td>';
                ordersBody.appendChild(emptyRow);
                return;
            }

            orders.forEach(function (order) {
                const tr = document.createElement('tr');
                const serviceNames = (order.services || []).map(service => service.name).filter(Boolean);
                const servicesPreview = serviceNames
                    .slice(0, 2)
                    .map(name => `<span class="orders-service-pill">${name}</span>`)
                    .join('');
                const extraServices = serviceNames.length > 2
                    ? `<span class="text-muted small">+ еще ${serviceNames.length - 2}</span>`
                    : '';
                const totalPrice = order.total_price !== null && order.total_price !== undefined
                    ? new Intl.NumberFormat('ru-RU', {
                        style: 'currency',
                        currency: 'RUB',
                        maximumFractionDigits: 0
                    }).format(order.total_price)
                    : '—';

                tr.innerHTML = `
                    <td>
                        <input type="checkbox" class="form-check-input order-checkbox" data-id="${order.id}" />
                    </td>
                    <td>
                        <div class="orders-date-cell">
                            <strong>${order.scheduled_at_formatted || '—'}</strong>
                            <small class="text-muted">${order.master?.name || 'Без мастера'}</small>
                        </div>
                    </td>
                    <td>
                        <div class="orders-client-cell">
                            <strong>${order.client?.name || 'Без имени'}</strong>
                            <small class="text-muted">${order.client?.phone || 'Без телефона'}</small>
                        </div>
                    </td>
                    <td>
                        ${serviceNames.length
                            ? `<div class="orders-services">${servicesPreview}</div>${extraServices ? `<div class="mt-1">${extraServices}</div>` : ''}`
                            : '<span class="text-muted">Услуги не выбраны</span>'}
                    </td>
                    <td>
                        <span class="badge ${order.status_class}">${order.status_label}</span>
                    </td>
                    <td class="text-end fw-semibold">${totalPrice}</td>
                    <td class="text-end">
                        <div class="btn-group" role="group">
                            <a href="/orders/${order.id}" class="btn btn-sm btn-icon btn-text-secondary" title="Открыть запись">
                                <i class="ri ri-eye-line"></i>
                            </a>
                            <a href="/orders/${order.id}/edit" class="btn btn-sm btn-icon btn-text-secondary" title="Редактировать">
                                <i class="ri ri-edit-line"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-icon btn-text-secondary text-danger js-cancel-single" data-order-id="${order.id}" title="Отменить запись">
                                <i class="ri ri-close-circle-line"></i>
                            </button>
                        </div>
                    </td>
                `;

                const checkbox = tr.querySelector('.order-checkbox');
                checkbox.addEventListener('change', function () {
                    if (this.checked) {
                        selectedOrders.add(order.id);
                    } else {
                        selectedOrders.delete(order.id);
                    }
                    updateBulkButtons();
                });

                const cancelButton = tr.querySelector('.js-cancel-single');
                cancelButton.addEventListener('click', function () {
                    const orderId = this.getAttribute('data-order-id');
                    if (!orderId) return;
                    if (!confirm('Вы уверены, что хотите отменить эту запись?')) return;
                    cancelOrder(orderId);
                });

                ordersBody.appendChild(tr);
            });
        }

        function formatQuickCurrency(value) {
            return `${value.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₽`;
        }

        function formatSuggestionPhone(phone) {
            const digits = (phone || '').replace(/\D/g, '');

            if (!digits.length) {
                return '';
            }

            let normalized = digits;

            if (normalized.length === 10) {
                normalized = '7' + normalized;
            }

            if (normalized.length !== 11) {
                return phone || '';
            }

            const country = normalized[0];
            const city = normalized.slice(1, 4);
            const first = normalized.slice(4, 7);
            const second = normalized.slice(7, 9);
            const third = normalized.slice(9, 11);

            return `+${country} (${city}) ${first}-${second}-${third}`;
        }

        function clearQuickClientResults() {
            if (!quickClientResults) {
                return;
            }

            quickClientResults.innerHTML = '';
            quickClientResults.classList.add('d-none');
        }

        function setQuickClientSelection(client) {
            const hasClient = Boolean(client && client.id);

            if (quickClientIdInput) {
                quickClientIdInput.value = hasClient ? client.id : '';
            }

            if (quickSelectedClient) {
                if (hasClient) {
                    const phone = formatSuggestionPhone(client.phone || '');
                    const lastVisit = client.last_visit_at_formatted
                        ? `<div class="small mt-1 opacity-75">Последний визит: ${client.last_visit_at_formatted}</div>`
                        : '';

                    quickSelectedClient.innerHTML = `
                        <div>
                            <div class="fw-semibold">Выбран клиент: ${client.name || 'Без имени'}</div>
                            <div class="small">${phone || 'Без телефона'}</div>
                            ${lastVisit}
                        </div>
                    `;
                    quickSelectedClient.classList.remove('d-none');
                } else {
                    quickSelectedClient.innerHTML = '';
                    quickSelectedClient.classList.add('d-none');
                }
            }

            if (quickClientPhoneInput) {
                quickClientPhoneInput.required = !hasClient;
                quickClientPhoneInput.readOnly = hasClient;
                quickClientPhoneInput.value = hasClient ? (client.phone || '') : '';
            }

            if (quickClientNameInput) {
                quickClientNameInput.readOnly = hasClient;
                quickClientNameInput.value = hasClient ? (client.name || '') : '';
            }

            clearQuickClientSuggestions();

            if (hasClient) {
                clearQuickClientResults();
            } else if (quickClientSearchInput && quickClientSearchInput.value.trim() === '') {
                renderQuickClientResults(quickRecentClients, 'Недавние клиенты');
            } else {
                clearQuickClientResults();
            }
        }

        function applyQuickClientDraft(client) {
            setQuickClientSelection(null);

            if (quickClientPhoneInput) {
                quickClientPhoneInput.value = client.phone || '';
            }

            if (quickClientNameInput) {
                quickClientNameInput.value = client.name || '';
            }

            clearQuickClientSuggestions();
            clearQuickClientResults();
        }

        function renderQuickClientResults(items, title = 'Клиенты') {
            if (!quickClientResults) {
                return;
            }

            quickClientResults.innerHTML = '';

            if (!Array.isArray(items) || !items.length) {
                quickClientResults.classList.add('d-none');
                return;
            }

            const header = document.createElement('div');
            header.className = 'list-group-item small text-muted';
            header.textContent = title;
            header.tabIndex = -1;
            quickClientResults.appendChild(header);

            items.forEach(item => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action d-flex align-items-start justify-content-between gap-2';
                button.innerHTML = `
                    <div class="d-flex flex-column text-start">
                        <span class="fw-medium">${item.name || 'Без имени'}</span>
                        <span class="small text-muted">${formatSuggestionPhone(item.phone || '') || 'Без телефона'}</span>
                    </div>
                    <span class="small text-muted text-end">${item.last_visit_at_formatted || ''}</span>
                `;
                button.addEventListener('click', () => {
                    if (item.id) {
                        setQuickClientSelection(item);
                    } else {
                        applyQuickClientDraft(item);
                    }

                    if (quickClientSearchInput) {
                        quickClientSearchInput.value = item.name || item.phone || '';
                    }
                });
                quickClientResults.appendChild(button);
            });

            const createButton = document.createElement('button');
            createButton.type = 'button';
            createButton.className = 'list-group-item list-group-item-action d-flex align-items-center justify-content-between gap-2 text-primary';
            createButton.innerHTML = `
                <span class="fw-medium">Добавить нового клиента</span>
                <i class="ri ri-user-add-line"></i>
            `;
            createButton.addEventListener('click', () => {
                setQuickClientSelection(null);
                clearQuickClientResults();
                if (quickClientSearchInput) {
                    quickClientSearchInput.value = '';
                }
                if (quickClientPhoneInput) {
                    quickClientPhoneInput.focus();
                }
            });
            quickClientResults.appendChild(createButton);

            quickClientResults.classList.remove('d-none');
        }

        function clearQuickClientSuggestions() {
            if (!quickClientSuggestions) {
                return;
            }

            quickClientSuggestions.innerHTML = '';
            quickClientSuggestions.classList.add('d-none');
        }

        function renderQuickClientSuggestions(suggestions) {
            if (!quickClientSuggestions) {
                return;
            }

            quickClientSuggestions.innerHTML = '';

            if (!Array.isArray(suggestions) || !suggestions.length) {
                quickClientSuggestions.classList.add('d-none');
                return;
            }

            const header = document.createElement('div');
            header.className = 'list-group-item small text-muted';
            header.textContent = 'Существующие клиенты';
            header.tabIndex = -1;
            quickClientSuggestions.appendChild(header);

            suggestions.forEach(item => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action d-flex flex-column align-items-start';
                button.innerHTML = `
                    <span class="fw-medium">${item.name || 'Без имени'}</span>
                    <span class="small text-muted">${formatSuggestionPhone(item.phone)}</span>
                `;
                button.addEventListener('click', () => {
                    if (item.id) {
                        setQuickClientSelection(item);
                        if (quickClientSearchInput) {
                            quickClientSearchInput.value = item.name || item.phone || '';
                        }
                    } else {
                        applyQuickClientDraft(item);
                    }

                    clearQuickClientSuggestions();
                    clearQuickClientResults();
                });

                quickClientSuggestions.appendChild(button);
            });

            quickClientSuggestions.classList.remove('d-none');
        }

        function updateQuickSummary() {
            if (!quickForm || !quickServicesSummary) {
                return;
            }

            let totalPrice = 0;
            quickForm.querySelectorAll('.quick-service-checkbox:checked').forEach(checkbox => {
                totalPrice += Number(checkbox.getAttribute('data-price') || 0);
            });

            quickServicesSummary.textContent = formatQuickCurrency(totalPrice);
        }

        function renderQuickServices(services) {
            if (!quickServicesContainer) {
                return;
            }

            quickServicesContainer.innerHTML = '';

            if (!Array.isArray(services) || !services.length) {
                const empty = document.createElement('div');
                empty.className = 'col-12 text-muted';
                empty.textContent = 'Услуги ещё не добавлены.';
                quickServicesContainer.appendChild(empty);
                updateQuickSummary();
                return;
            }

            services.forEach(service => {
                const col = document.createElement('div');
                col.className = 'col-md-6';
                col.innerHTML = `
                    <div class="form-check custom-option custom-option-basic">
                        <label class="form-check-label custom-option-content w-100" for="quick-service-${service.id}">
                            <input
                                type="checkbox"
                                class="form-check-input quick-service-checkbox"
                                id="quick-service-${service.id}"
                                value="${service.id}"
                                data-price="${service.price || 0}"
                                data-duration="${service.duration || 0}"
                            />
                            <span class="custom-option-body">
                                <span class="custom-option-title d-flex justify-content-between align-items-center">
                                    <span>${service.name}</span>
                                    <span class="badge bg-label-primary">${Number(service.price || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₽</span>
                                </span>
                                <small class="text-muted">~ ${service.duration || 0} мин</small>
                            </span>
                        </label>
                    </div>
                `;
                quickServicesContainer.appendChild(col);
            });

            quickServicesContainer.querySelectorAll('.quick-service-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateQuickSummary);
            });

            updateQuickSummary();
        }

        async function loadQuickServices() {
            if (!quickServicesContainer) {
                return;
            }

            quickServicesContainer.innerHTML = '<div class="col-12 text-muted">Загрузка услуг...</div>';

            try {
                const response = await fetch('/api/v1/orders/options', {
                    headers: authHeaders(),
                    credentials: 'include',
                });

                if (!response.ok) {
                    quickServicesContainer.innerHTML = '<div class="col-12 text-danger">Не удалось загрузить услуги.</div>';
                    return;
                }

                const data = await response.json();
                renderQuickServices(data.services || []);
                quickRecentClients = Array.isArray(data.recent_clients) ? data.recent_clients : [];

                if (quickClientSearchInput && quickClientSearchInput.value.trim() === '') {
                    renderQuickClientResults(quickRecentClients, 'Недавние клиенты');
                }
            } catch (error) {
                if (error?.name === 'AbortError') {
                    return;
                }

                quickServicesContainer.innerHTML = '<div class="col-12 text-danger">Не удалось загрузить услуги.</div>';
            }
        }

        async function lookupQuickClient(query, mode = 'search') {
            if (!quickClientPhoneInput && !quickClientSearchInput) {
                return;
            }

            const value = (query || '').toString().trim();

            if (!value) {
                clearQuickClientSuggestions();
                if (mode === 'search') {
                    renderQuickClientResults(quickRecentClients, 'Недавние клиенты');
                }
                return;
            }

            if (mode === 'phone' && value.replace(/[^0-9]+/g, '').length < 3) {
                clearQuickClientSuggestions();
                return;
            }

            if (mode === 'search' && value.length < 2) {
                renderQuickClientResults(quickRecentClients, 'Недавние клиенты');
                return;
            }

            if (quickLookupController) {
                quickLookupController.abort();
            }

            quickLookupController = new AbortController();

            try {
                const params = new URLSearchParams(
                    mode === 'phone'
                        ? { client_phone: value }
                        : { client_search: value }
                );
                const response = await fetch(`/api/v1/orders/options?${params.toString()}`, {
                    headers: authHeaders(),
                    credentials: 'include',
                    signal: quickLookupController.signal,
                });

                if (!response.ok) {
                    clearQuickClientSuggestions();
                    return;
                }

                const data = await response.json();

                if (mode === 'search') {
                    renderQuickClientResults(Array.isArray(data.suggestions) ? data.suggestions : [], 'Найденные клиенты');
                    clearQuickClientSuggestions();
                } else if (Array.isArray(data.suggestions)) {
                    renderQuickClientSuggestions(data.suggestions);
                } else {
                    clearQuickClientSuggestions();
                }

                if (mode === 'phone' && data.client && quickClientNameInput && !quickClientNameInput.matches(':focus')) {
                    quickClientNameInput.value = data.client.name || '';
                }
            } catch (error) {
                if (error?.name === 'AbortError') {
                    return;
                }

                if (mode === 'search') {
                    clearQuickClientResults();
                } else {
                    clearQuickClientSuggestions();
                }
            }
        }

        function updateBulkButtons() {
            const hasSelection = selectedOrders.size > 0;
            bulkButtons.forEach(btn => {
                btn.disabled = !hasSelection;
            });

            if (ordersBulkBar) {
                ordersBulkBar.classList.toggle('is-visible', hasSelection);
            }

            if (ordersBulkSelected) {
                ordersBulkSelected.textContent = String(selectedOrders.size);
            }

            if (ordersSelectedCount) {
                ordersSelectedCount.textContent = String(selectedOrders.size);
            }
        }

        function renderPagination(meta) {
            paginationList.innerHTML = '';
            const pagination = meta.pagination;

            const prevItem = document.createElement('li');
            prevItem.className = 'page-item' + (pagination.current_page <= 1 ? ' disabled' : '');
            prevItem.innerHTML = `<a class="page-link" href="#" aria-label="Назад">«</a>`;
            prevItem.addEventListener('click', function (e) {
                e.preventDefault();
                if (pagination.current_page > 1) {
                    loadOrders(pagination.current_page - 1);
                }
            });
            paginationList.appendChild(prevItem);

            const totalPages = pagination.last_page;
            for (let page = 1; page <= totalPages; page++) {
                if (page > 3 && page < totalPages - 1 && Math.abs(page - pagination.current_page) > 1) {
                    if (!paginationList.querySelector('li.dots-before') && page < pagination.current_page) {
                        const dots = document.createElement('li');
                        dots.className = 'page-item disabled dots-before';
                        dots.innerHTML = '<span class="page-link">...</span>';
                        paginationList.appendChild(dots);
                    }
                    if (!paginationList.querySelector('li.dots-after') && page > pagination.current_page) {
                        const dots = document.createElement('li');
                        dots.className = 'page-item disabled dots-after';
                        dots.innerHTML = '<span class="page-link">...</span>';
                        paginationList.appendChild(dots);
                    }
                    continue;
                }

                const item = document.createElement('li');
                item.className = 'page-item' + (page === pagination.current_page ? ' active' : '');
                item.innerHTML = `<a class="page-link" href="#">${page}</a>`;
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    loadOrders(page);
                });
                paginationList.appendChild(item);
            }

            const nextItem = document.createElement('li');
            nextItem.className = 'page-item' + (pagination.current_page >= totalPages ? ' disabled' : '');
            nextItem.innerHTML = `<a class="page-link" href="#" aria-label="Вперёд">»</a>`;
            nextItem.addEventListener('click', function (e) {
                e.preventDefault();
                if (pagination.current_page < totalPages) {
                    loadOrders(pagination.current_page + 1);
                }
            });
            paginationList.appendChild(nextItem);

            ordersSummary.textContent = `Показано ${Math.min(pagination.current_page * pagination.per_page, pagination.total)} из ${pagination.total}`;
        }

        async function loadOrders(page = 1) {
            clearAlerts();
            state.page = page;
            const params = new URLSearchParams({
                period: state.filters.period,
                status: state.filters.status,
                search: state.filters.search,
                page: state.page,
                per_page: state.perPage,
            });

            ordersBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted orders-empty">Загрузка данных...</td></tr>';

            const response = await fetch(`/api/v1/orders?${params.toString()}`, {
                headers: authHeaders(),
                credentials: 'include',
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                ordersBody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-danger">Не удалось загрузить записи.</td></tr>';
                showAlert('danger', error.error?.message || 'Произошла ошибка при загрузке списка.');
                return;
            }

            const data = await response.json();
            state.reminderMessage = data.meta.reminder_message || null;
            state.total = data.meta.pagination.total;

            renderOptions(periodSelect, data.meta.period_options, data.meta.filters.period);
            renderOptions(statusSelect, data.meta.status_options, data.meta.filters.status);
            searchInput.value = data.meta.filters.search || '';

            ordersTotal.textContent = state.total;

            if (ordersHeroTotal) {
                ordersHeroTotal.textContent = String(state.total);
            }

            if (ordersHeroPeriod) {
                const currentPeriodLabel = periodSelect.options[periodSelect.selectedIndex]?.textContent || 'Текущий период';
                ordersHeroPeriod.textContent = currentPeriodLabel;
            }

            if (!state.reminderMessage) {
                if (ordersReminderNote) {
                    ordersReminderNote.innerHTML = 'Добавьте текст автонапоминания в настройках, чтобы отправлять сообщения. <a href="/settings" class="alert-link">Перейти в настройки</a>.';
                    ordersReminderNote.classList.add('is-visible');
                }
                bulkRemindBtn.disabled = true;
            } else {
                if (ordersReminderNote) {
                    ordersReminderNote.innerHTML = '';
                    ordersReminderNote.classList.remove('is-visible');
                }
                bulkRemindBtn.disabled = false;
            }

            renderOrders(data.data || []);
            renderPagination(data.meta);
        }

        function resetFilters() {
            state.filters = {
                period: 'this_week',
                status: 'all',
                search: ''
            };
            loadOrders(1);
        }

        document.getElementById('filters-form').addEventListener('submit', function (event) {
            event.preventDefault();
            state.filters.period = periodSelect.value;
            state.filters.status = statusSelect.value;
            state.filters.search = searchInput.value.trim();
            loadOrders(1);
        });

        document.getElementById('filters-reset').addEventListener('click', function () {
            resetFilters();
        });

        selectAllCheckbox.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = selectAllCheckbox.checked;
                if (cb.checked) {
                    selectedOrders.add(parseInt(cb.getAttribute('data-id'), 10));
                } else {
                    selectedOrders.delete(parseInt(cb.getAttribute('data-id'), 10));
                }
            });
            updateBulkButtons();
        });

        bulkButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const action = this.getAttribute('data-action');
                if (!selectedOrders.size) {
                    showAlert('warning', 'Выберите хотя бы одну запись.');
                    return;
                }
                if (action === 'cancel' && !confirm('Отменить выбранные записи?')) {
                    return;
                }
                submitBulkAction(action);
            });
        });

        async function submitBulkAction(action) {
            const payload = {
                action: action,
                orders: Array.from(selectedOrders),
            };

            const response = await fetch('/api/v1/orders/bulk', {
                method: 'POST',
                headers: authHeaders(),
                credentials: 'include',
                body: JSON.stringify(payload),
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                showAlert('danger', result.error?.message || 'Не удалось выполнить действие.');
                return;
            }

            showAlert('success', result.message || 'Действие выполнено.');
            if (result.reminder_text) {
                showAlert('info', '<strong>Текст автонапоминания:</strong><div class="mt-2 small">' + result.reminder_text.replace(/\n/g, '<br>') + '</div>', true);
            }

            loadOrders(state.page);
        }

        async function cancelOrder(orderId) {
            const response = await fetch(`/api/v1/orders/${orderId}/cancel`, {
                method: 'POST',
                headers: authHeaders(),
                credentials: 'include',
                body: JSON.stringify({}),
            });

            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                showAlert('danger', result.error?.message || 'Не удалось отменить запись.');
                return;
            }

            showAlert('success', result.message || 'Запись отменена.');
            loadOrders(state.page);
        }

        if (quickClientPhoneInput) {
            quickClientPhoneInput.addEventListener('input', function () {
                if (quickClientIdInput && quickClientIdInput.value) {
                    return;
                }

                const value = this.value.trim();
                const digits = value.replace(/[^0-9]+/g, '');

                if (quickLookupTimer) {
                    clearTimeout(quickLookupTimer);
                }

                if (!value) {
                    if (quickClientNameInput && !quickClientNameInput.matches(':focus')) {
                        quickClientNameInput.value = '';
                    }
                    clearQuickClientSuggestions();
                    return;
                }

                if (digits.length < 3) {
                    clearQuickClientSuggestions();
                    return;
                }

                quickLookupTimer = setTimeout(() => lookupQuickClient(value, 'phone'), 400);
            });

            quickClientPhoneInput.addEventListener('blur', function () {
                if (quickClientIdInput && quickClientIdInput.value) {
                    return;
                }

                const value = this.value.trim();
                if (value) {
                    lookupQuickClient(value, 'phone');
                } else {
                    clearQuickClientSuggestions();
                }
            });
        }

        if (quickClientSearchInput) {
            quickClientSearchInput.addEventListener('input', function () {
                const value = this.value.trim();

                if (quickLookupTimer) {
                    clearTimeout(quickLookupTimer);
                }

                if (!value) {
                    if (quickClientIdInput && quickClientIdInput.value) {
                        setQuickClientSelection(null);
                    }
                    renderQuickClientResults(quickRecentClients, 'Недавние клиенты');
                    return;
                }

                if (quickClientIdInput && quickClientIdInput.value) {
                    setQuickClientSelection(null);
                }

                quickLookupTimer = setTimeout(() => lookupQuickClient(value, 'search'), 250);
            });

            quickClientSearchInput.addEventListener('focus', function () {
                if (!this.value.trim()) {
                    renderQuickClientResults(quickRecentClients, 'Недавние клиенты');
                }
            });
        }

        const quickModalElement = document.getElementById('quickCreateModal');
        if (quickModalElement) {
            quickModalElement.addEventListener('shown.bs.modal', () => {
                if (quickClientSearchInput && !quickClientSearchInput.value.trim()) {
                    renderQuickClientResults(quickRecentClients, 'Недавние клиенты');
                } else if (quickClientSearchInput && quickClientSearchInput.value.trim()) {
                    lookupQuickClient(quickClientSearchInput.value.trim(), 'search');
                } else if (quickClientPhoneInput && quickClientPhoneInput.value.trim()) {
                    lookupQuickClient(quickClientPhoneInput.value.trim(), 'phone');
                }
            });

            quickModalElement.addEventListener('hidden.bs.modal', () => {
                if (quickForm) {
                    quickForm.reset();
                    updateQuickSummary();
                }
                setQuickClientSelection(null);
                clearQuickClientSuggestions();
                clearQuickClientResults();
            });
        }

        document.addEventListener('click', function (event) {
            if (
                quickClientSuggestions &&
                !quickClientSuggestions.classList.contains('d-none') &&
                event.target !== quickClientPhoneInput &&
                !quickClientSuggestions.contains(event.target)
            ) {
                clearQuickClientSuggestions();
            }

            if (
                quickClientResults &&
                !quickClientResults.classList.contains('d-none') &&
                event.target !== quickClientSearchInput &&
                !quickClientResults.contains(event.target)
            ) {
                clearQuickClientResults();
            }
        });

        loadQuickServices();

        if (quickForm) {
            quickForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                const form = event.target;
                const errorsContainer = document.getElementById('quick-create-errors');
                errorsContainer.innerHTML = '';

                const payload = {
                    client_id: form.client_id.value ? Number(form.client_id.value) : null,
                    client_phone: form.client_phone.value.trim(),
                    client_name: form.client_name.value.trim(),
                    scheduled_at: form.scheduled_at.value,
                    note: form.note.value,
                };

                const selectedServices = Array.from(form.querySelectorAll('.quick-service-checkbox:checked'));
                const services = selectedServices.map(cb => Number(cb.value));
                const totalPrice = selectedServices.reduce((sum, checkbox) => {
                    return sum + Number(checkbox.getAttribute('data-price') || 0);
                }, 0);

                payload.services = services;
                payload.total_price = services.length ? Number(totalPrice.toFixed(2)) : null;

                const response = await fetch('/api/v1/orders/quick-create', {
                    method: 'POST',
                    headers: authHeaders(),
                    credentials: 'include',
                    body: JSON.stringify(payload),
                });

                const result = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const fields = result.error?.fields || {};
                    if (Object.keys(fields).length) {
                        const list = document.createElement('ul');
                        list.className = 'text-danger mb-0';
                        Object.keys(fields).forEach(key => {
                            const li = document.createElement('li');
                            li.textContent = fields[key][0];
                            list.appendChild(li);
                        });
                        errorsContainer.appendChild(list);
                    } else {
                        errorsContainer.innerHTML = '<div class="text-danger">' + (result.error?.message || 'Не удалось создать запись.') + '</div>';
                    }
                    return;
                }

                const modal = bootstrap.Modal.getInstance(document.getElementById('quickCreateModal'));
                if (modal) {
                    modal.hide();
                }
                showAlert('success', result.message || 'Запись создана.');
                if (result.data?.id) {
                    window.location.href = `/orders/${result.data.id}`;
                } else {
                    loadOrders(1);
                }
            });
        }

        updateBulkButtons();
        loadOrders();
    </script>
@endsection
