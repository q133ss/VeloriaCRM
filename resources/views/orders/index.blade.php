@extends('layouts.app')

@section('title', 'Записи')

@section('meta')
    @include('components.veloria-datetime-picker-styles')
    @include('components.booking-phrase-input-styles')
@endsection

@section('content')
    <div class="orders-page">
        <div class="d-flex flex-column gap-4">
            <section class="orders-hero">
                <div class="orders-hero__top">
                    <div>
                        <h1 class="orders-hero__title">Сегодня, <span id="orders-today-date">—</span></h1>
                        <p class="orders-hero__lead mb-0" id="orders-today-line">Смотрим расписание…</p>
                    </div>
                    <button
                        type="button"
                        class="btn btn-primary orders-hero__cta"
                        data-bs-toggle="modal"
                        data-bs-target="#quickCreateModal"
                    >
                        <i class="ri ri-add-line me-1"></i>
                        Новая запись
                    </button>
                </div>

                {{-- One line instead of choosing between a modal and a full-page form. --}}
                @include('components.booking-phrase-input')

                <div>
                    <button type="button" class="btn btn-primary btn-sm d-none" id="orders-phrase-open">
                        Проверить и создать
                    </button>
                </div>
            </section>

            <div id="orders-alerts"></div>

            <style>
        .orders-page {
            --orders-card-shadow: 0 20px 48px -34px rgba(37, 26, 84, 0.45);
            --orders-line: color-mix(in srgb, var(--bs-border-color) 70%, transparent);
        }

        .orders-hero {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            border: 1px solid rgba(var(--bs-primary-rgb, 255, 0, 252), 0.14);
            border-radius: 1.5rem;
            padding: 1.35rem 1.5rem;
            background: linear-gradient(135deg, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.07), rgba(var(--bs-primary-rgb, 255, 0, 252), 0.015) 60%, rgba(var(--bs-info-rgb, 0, 207, 232), 0.04));
            box-shadow: var(--orders-card-shadow);
        }

        .orders-hero__top {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .orders-hero__title {
            margin: 0 0 0.2rem;
            font-size: clamp(1.35rem, 2vw, 1.75rem);
            letter-spacing: -0.02em;
        }

        .orders-hero__lead {
            color: var(--bs-secondary-color);
        }

        .orders-hero__lead a {
            font-weight: 600;
        }

        .orders-hero__cta {
            white-space: nowrap;
        }

        /* The phrase line lives in the header, not in the calendar form:
           a grey block in a light header read as a placeholder. */
        .orders-hero .booking-phrase {
            margin-bottom: 0;
            padding: 0.55rem 0.7rem;
            border-radius: 1rem;
            border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.16);
            background: color-mix(in srgb, var(--bs-card-bg) 92%, transparent);
        }

        .orders-surface {
            border: none;
            border-radius: 1.35rem;
            box-shadow: var(--orders-card-shadow);
            background: color-mix(in srgb, var(--bs-card-bg) 94%, transparent);
        }

        .orders-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
        }

        .orders-tabs {
            display: inline-flex;
            gap: 0.2rem;
            padding: 0.25rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.07);
        }

        .orders-tab {
            border: 0;
            border-radius: 999px;
            padding: 0.45rem 0.95rem;
            background: transparent;
            color: var(--bs-secondary-color);
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .orders-tab.is-active {
            background: var(--bs-card-bg, #fff);
            color: var(--bs-primary);
            box-shadow: 0 8px 20px -14px rgba(0, 0, 0, 0.65);
        }

        .orders-toolbar__right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1 1 260px;
            justify-content: flex-end;
        }

        .orders-search {
            max-width: 260px;
        }

        /* Field and buttons to one height: the theme gives them three. */
        .orders-toolbar__right .orders-search {
            height: 2.625rem;
            padding-top: 0;
            padding-bottom: 0;
        }

        .orders-toolbar__right .btn {
            height: 2.625rem;
            display: inline-flex;
            align-items: center;
        }

        .orders-extra {
            display: none;
            gap: 0.75rem;
            padding: 0 1.25rem 1rem;
        }

        .orders-extra.is-visible {
            display: flex;
            flex-wrap: wrap;
        }

        .orders-extra > div {
            min-width: 200px;
        }

        .orders-reminder-note {
            display: none;
            margin: 0 1.25rem 1rem;
            border-radius: 1rem;
            padding: 0.8rem 1rem;
            background: rgba(var(--bs-warning-rgb, 255, 159, 67), 0.14);
            color: var(--bs-warning-text-emphasis, var(--bs-warning));
        }

        .orders-reminder-note.is-visible {
            display: block;
        }

        .orders-bulk-bar {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.75rem 1.25rem;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.05);
        }

        .orders-bulk-bar.is-visible {
            display: flex;
            flex-wrap: wrap;
        }

        .orders-bulk-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        /* Rows, not a table: on a phone the same data folds into a card
           instead of running off the right edge. */
        .orders-row,
        .orders-list-head {
            display: grid;
            grid-template-columns: 116px minmax(140px, 1.2fr) minmax(170px, 1.5fr) 132px 168px;
            align-items: center;
            gap: 0.9rem;
            padding: 0.85rem 1.25rem;
        }

        .orders-list.is-picking .orders-row,
        .orders-list.is-picking + .orders-list-head,
        .orders-list-head.is-picking {
            grid-template-columns: 26px 116px minmax(140px, 1.2fr) minmax(170px, 1.5fr) 132px 168px;
        }

        .orders-list-head {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            color: var(--bs-secondary-color);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--orders-line);
        }

        .orders-row {
            border-bottom: 1px solid var(--orders-line);
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .orders-row:hover {
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.03);
        }

        .orders-row.is-selected {
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.06);
        }

        /* The cells dim, never the row: opacity on the row makes a stacking
           context, and the dropdown inside it falls under its neighbours. */
        .orders-row.is-past .orders-row__when,
        .orders-row.is-past .orders-row__who,
        .orders-row.is-past .orders-row__what,
        .orders-row.is-past .orders-row__status {
            opacity: 0.72;
        }

        .orders-row__pick {
            display: none;
        }

        .orders-list.is-picking .orders-row__pick {
            display: block;
        }

        .orders-row__when strong {
            display: block;
            font-size: 1.05rem;
            line-height: 1.2;
        }

        .orders-row__when small,
        .orders-row__who small,
        .orders-row__what small {
            display: block;
            margin-top: 0.15rem;
            color: var(--bs-secondary-color);
        }

        .orders-row__who strong {
            display: block;
            font-size: 0.98rem;
        }

        .orders-row__what {
            min-width: 0;
        }

        .orders-row__what span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .orders-row__act {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.35rem;
        }

        .orders-row__act .btn {
            white-space: nowrap;
        }

        .orders-empty {
            padding: 3rem 1.25rem;
            text-align: center;
            color: var(--bs-secondary-color);
        }

        .orders-pagination {
            padding: 0.9rem 1.25rem;
            border-top: 1px solid var(--orders-line);
            background: transparent;
        }

        @media (max-width: 767.98px) {
            .orders-list-head {
                display: none;
            }

            .orders-row,
            .orders-list.is-picking .orders-row {
                grid-template-columns: 1fr auto;
                grid-template-areas:
                    'when status'
                    'who who'
                    'what what'
                    'act act';
                row-gap: 0.5rem;
                padding: 1rem 1.1rem;
            }

            .orders-list.is-picking .orders-row {
                grid-template-areas:
                    'pick status'
                    'when when'
                    'who who'
                    'what what'
                    'act act';
            }

            .orders-row__pick { grid-area: pick; }
            .orders-row__when { grid-area: when; }
            .orders-row__who { grid-area: who; }
            .orders-row__what { grid-area: what; }
            .orders-row__status { grid-area: status; justify-self: end; }
            .orders-row__act { grid-area: act; justify-content: stretch; }

            .orders-row__act .orders-row__primary {
                flex: 1;
            }

            .orders-toolbar,
            .orders-toolbar__right {
                justify-content: flex-start;
            }

            .orders-search {
                max-width: none;
            }

            .orders-tabs {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>

            <section class="card orders-surface" id="orders-card">
                <div class="orders-toolbar">
                    <div class="orders-tabs" role="group" aria-label="Период">
                        <button type="button" class="orders-tab is-active" data-period="today">Сегодня</button>
                        <button type="button" class="orders-tab" data-period="tomorrow">Завтра</button>
                        <button type="button" class="orders-tab" data-period="this_week">Неделя</button>
                        <button type="button" class="orders-tab" data-period="all">Все</button>
                    </div>

                    <div class="orders-toolbar__right">
                        <input
                            type="search"
                            class="form-control orders-search"
                            id="filter-search"
                            placeholder="Имя или телефон"
                            aria-label="Поиск клиента"
                        />
                        <button type="button" class="btn btn-outline-secondary" id="orders-more-filters">
                            <i class="ri ri-equalizer-line me-1"></i>
                            Ещё
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="orders-select-mode">
                            Выбрать несколько
                        </button>
                    </div>
                </div>

                <div class="orders-extra" id="orders-extra-filters">
                    <div>
                        <label for="filter-status" class="form-label">Статус</label>
                        <select class="form-select" id="filter-status"></select>
                    </div>
                    <div>
                        <label for="filter-period" class="form-label">Другой период</label>
                        <select class="form-select" id="filter-period"></select>
                    </div>
                    <div class="d-flex align-items-end">
                        <button type="button" id="filters-reset" class="btn btn-outline-secondary">Сбросить</button>
                    </div>
                </div>

                <div class="orders-reminder-note" id="orders-reminder-note"></div>

                <div class="orders-bulk-bar" id="orders-bulk-bar">
                    <label class="d-flex align-items-center gap-2 mb-0">
                        <input type="checkbox" class="form-check-input mt-0" id="select-all" />
                        <span class="text-muted">Выбрано <strong id="orders-bulk-selected">0</strong></span>
                    </label>
                    <div class="orders-bulk-actions">
                        <button type="button" class="btn btn-success btn-sm bulk-action-btn" data-action="confirm" disabled>
                            <i class="ri ri-check-double-line me-1"></i>
                            Подтвердить<span class="bulk-count"></span>
                        </button>
                        <button type="button" class="btn btn-info btn-sm text-white bulk-action-btn" data-action="remind" id="bulk-remind-btn" disabled>
                            <i class="ri ri-mail-line me-1"></i>
                            Напомнить<span class="bulk-count"></span>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm bulk-action-btn" data-action="cancel" disabled>
                            <i class="ri ri-close-circle-line me-1"></i>
                            Отменить<span class="bulk-count"></span>
                        </button>
                        <button type="button" class="btn btn-sm btn-text-secondary" id="orders-select-done">Готово</button>
                    </div>
                </div>

                <div class="orders-list-head" id="orders-list-head">
                    <span>Когда</span>
                    <span>Клиент</span>
                    <span>Услуги и сумма</span>
                    <span>Статус</span>
                    <span></span>
                </div>

                <div class="orders-list" id="orders-body">
                    <div class="orders-empty">Загружаем записи…</div>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 orders-pagination" id="orders-pagination">
                    <div class="text-muted small" id="orders-summary">Загрузка…</div>
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
    @include('components.booking-phrase-input-script')
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
                period: 'today',
                status: 'all',
                search: ''
            },
            page: 1,
            perPage: 12,
            reminderMessage: null,
            total: 0,
            picking: false,
        };

        const selectedOrders = new Set();
        // What each booking allows comes down with the list, so the row button
        // and the bulk bar answer the same question the same way.
        const orderActions = new Map();

        const ordersAlerts = document.getElementById('orders-alerts');
        const periodSelect = document.getElementById('filter-period');
        const statusSelect = document.getElementById('filter-status');
        const searchInput = document.getElementById('filter-search');
        const ordersBody = document.getElementById('orders-body');
        const ordersListHead = document.getElementById('orders-list-head');
        const ordersTotal = document.getElementById('orders-total');
        const ordersSummary = document.getElementById('orders-summary');
        const paginationList = document.getElementById('pagination-list');
        const selectAllCheckbox = document.getElementById('select-all');
        const bulkButtons = document.querySelectorAll('.bulk-action-btn');
        const bulkRemindBtn = document.getElementById('bulk-remind-btn');
        const ordersBulkBar = document.getElementById('orders-bulk-bar');
        const ordersBulkSelected = document.getElementById('orders-bulk-selected');
        const ordersReminderNote = document.getElementById('orders-reminder-note');
        const ordersTabs = document.querySelectorAll('.orders-tab');
        const ordersExtraFilters = document.getElementById('orders-extra-filters');
        const ordersMoreFilters = document.getElementById('orders-more-filters');
        const ordersSelectMode = document.getElementById('orders-select-mode');
        const ordersSelectDone = document.getElementById('orders-select-done');
        const ordersTodayDate = document.getElementById('orders-today-date');
        const ordersTodayLine = document.getElementById('orders-today-line');
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
        const quickScheduledAtInput = document.getElementById('quick_scheduled_at');
        const quickNoteInput = document.getElementById('quick_note');
        let quickLookupController = null;
        let quickLookupTimer = null;
        let quickRecentClients = [];
        let searchDebounce = null;

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

        function formatMoney(value) {
            if (value === null || value === undefined) {
                return '—';
            }

            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                maximumFractionDigits: 0,
            }).format(value);
        }

        function describeDay(date) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const day = new Date(date);
            day.setHours(0, 0, 0, 0);
            const diff = Math.round((day - today) / 86400000);

            if (diff === 0) return 'сегодня';
            if (diff === 1) return 'завтра';
            if (diff === -1) return 'вчера';

            return day.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
        }

        /**
         * One button per row: the one this booking needs now. The order
         * matters — while a visit is running, Finish beats a reminder.
         */
        function primaryActionFor(order) {
            const can = order.actions || {};

            if (can.can_confirm) {
                return { action: 'confirm', label: 'Подтвердить', cls: 'btn-primary' };
            }

            if (order.status === 'in_progress' && can.can_complete) {
                return { action: 'complete', label: 'Завершить', cls: 'btn-success' };
            }

            if (can.can_start) {
                return { action: 'start', label: 'Начать', cls: 'btn-primary' };
            }

            if (can.can_remind) {
                return { action: 'remind', label: 'Напомнить', cls: 'btn-outline-primary' };
            }

            return null;
        }

        function menuActionsFor(order, primary) {
            const can = order.actions || {};
            const items = [];

            if (can.can_confirm) items.push({ action: 'confirm', label: 'Подтвердить' });
            if (can.can_start) items.push({ action: 'start', label: 'Начать' });
            if (can.can_complete) items.push({ action: 'complete', label: 'Завершить' });
            if (can.can_remind) items.push({ action: 'remind', label: 'Напомнить' });
            if (can.can_mark_no_show) items.push({ action: 'no_show', label: 'Не пришёл', danger: true });
            if (can.can_cancel) items.push({ action: 'cancel', label: 'Отменить', danger: true });

            return items.filter(item => !primary || item.action !== primary.action);
        }

        /**
         * There is nothing to send a reminder with until its text is in the
         * settings, so the button goes out here instead of erroring on click.
         */
        function remindBlocked(action) {
            if (action !== 'remind' || state.reminderMessage) {
                return '';
            }

            return ' disabled title="Сначала добавьте текст напоминания в настройках"';
        }

        function renderOrders(orders) {
            ordersBody.innerHTML = '';
            selectedOrders.clear();
            orderActions.clear();
            selectAllCheckbox.checked = false;
            updateBulkButtons();

            if (!orders.length) {
                ordersBody.appendChild(buildEmptyState());
                return;
            }

            const now = new Date();

            orders.forEach(function (order) {
                orderActions.set(order.id, order.actions || {});

                const scheduled = order.scheduled_at ? new Date(order.scheduled_at) : null;
                const time = scheduled
                    ? scheduled.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
                    : '—';
                const dayLabel = scheduled ? describeDay(scheduled) : '';
                const serviceNames = (order.services || []).map(service => service.name).filter(Boolean);
                const primary = primaryActionFor(order);
                const menu = menuActionsFor(order, primary);

                const row = document.createElement('article');
                row.className = 'orders-row' + (scheduled && scheduled < now ? ' is-past' : '');
                row.setAttribute('data-id', order.id);

                const menuItems = menu.map(item => `
                    <li>
                        <button type="button" class="dropdown-item${item.danger ? ' text-danger' : ''} js-order-action" data-action="${item.action}"${remindBlocked(item.action)}>
                            ${item.label}
                        </button>
                    </li>
                `).join('');

                row.innerHTML = `
                    <div class="orders-row__pick">
                        <input type="checkbox" class="form-check-input order-checkbox" data-id="${order.id}" aria-label="Выбрать запись" />
                    </div>
                    <div class="orders-row__when">
                        <strong>${time}</strong>
                        <small>${dayLabel}</small>
                    </div>
                    <div class="orders-row__who">
                        <strong>${order.client?.name || 'Без имени'}</strong>
                        <small>${order.client?.phone || 'Без телефона'}</small>
                    </div>
                    <div class="orders-row__what">
                        <span>${serviceNames.length ? serviceNames.join(', ') : 'Услуга не выбрана'}</span>
                        <small>${formatMoney(order.total_price)}</small>
                    </div>
                    <div class="orders-row__status">
                        <span class="badge ${order.status_class}">${order.status_label}</span>
                    </div>
                    <div class="orders-row__act">
                        ${primary
                            ? `<button type="button" class="btn btn-sm ${primary.cls} orders-row__primary js-order-action" data-action="${primary.action}"${remindBlocked(primary.action)}>${primary.label}</button>`
                            : `<a href="/orders/${order.id}" class="btn btn-sm btn-outline-secondary orders-row__primary">Открыть</a>`}
                        <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-icon btn-text-secondary" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Другие действия">
                                <i class="ri ri-more-2-fill"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/orders/${order.id}">Открыть запись</a></li>
                                <li><a class="dropdown-item" href="/orders/${order.id}/edit">Редактировать</a></li>
                                ${menuItems ? '<li><hr class="dropdown-divider"></li>' + menuItems : ''}
                            </ul>
                        </div>
                    </div>
                `;

                const checkbox = row.querySelector('.order-checkbox');
                checkbox.addEventListener('change', function () {
                    if (this.checked) {
                        selectedOrders.add(order.id);
                    } else {
                        selectedOrders.delete(order.id);
                    }
                    row.classList.toggle('is-selected', this.checked);
                    updateBulkButtons();
                });

                row.querySelectorAll('.js-order-action').forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.stopPropagation();
                        runOrderAction(order.id, this.getAttribute('data-action'), button);
                    });
                });

                // A click anywhere in the row opens the booking: no aiming at icons.
                row.addEventListener('click', function (event) {
                    if (event.target.closest('button, a, input, label, .dropdown-menu')) {
                        return;
                    }

                    if (state.picking) {
                        checkbox.checked = !checkbox.checked;
                        checkbox.dispatchEvent(new Event('change'));
                        return;
                    }

                    window.location.href = `/orders/${order.id}`;
                });

                ordersBody.appendChild(row);
            });
        }

        function buildEmptyState() {
            const box = document.createElement('div');
            box.className = 'orders-empty';

            if (state.filters.period === 'today' && state.filters.status === 'all' && !state.filters.search) {
                box.innerHTML = `
                    <p class="mb-3">На сегодня записей нет.</p>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="orders-empty-week">Показать неделю</button>
                `;
                box.querySelector('#orders-empty-week').addEventListener('click', function () {
                    setPeriod('this_week');
                });

                return box;
            }

            box.innerHTML = `
                <p class="mb-3">Ничего не нашлось по этим условиям.</p>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="orders-empty-reset">Сбросить фильтры</button>
            `;
            box.querySelector('#orders-empty-reset').addEventListener('click', resetFilters);

            return box;
        }

        /**
         * One action on one booking. The moves differ, the behaviour does not:
         * the button locks for the request, the list is re-read after it.
         */
        async function runOrderAction(orderId, action, button) {
            const can = orderActions.get(orderId) || {};

            if (action === 'start' && can.start_needs_confirm) {
                // The start time disagrees with the booked one: that is settled
                // on its own screen, not silently from a list.
                window.location.href = `/orders/${orderId}/start-confirmation`;
                return;
            }

            if (action === 'cancel' && !confirm('Отменить эту запись?')) return;
            if (action === 'no_show' && !confirm('Отметить, что клиент не пришёл?')) return;

            const endpoints = {
                start: `/api/v1/orders/${orderId}/start`,
                complete: `/api/v1/orders/${orderId}/complete`,
                remind: `/api/v1/orders/${orderId}/remind`,
                cancel: `/api/v1/orders/${orderId}/cancel`,
                no_show: `/api/v1/orders/${orderId}/no-show`,
            };

            if (button) button.disabled = true;

            try {
                const response = action === 'confirm'
                    ? await fetch('/api/v1/orders/bulk', {
                        method: 'POST',
                        headers: authHeaders(),
                        credentials: 'include',
                        body: JSON.stringify({ action: 'confirm', orders: [orderId] }),
                    })
                    : await fetch(endpoints[action], {
                        method: 'POST',
                        headers: authHeaders(),
                        credentials: 'include',
                        body: JSON.stringify({}),
                    });

                const result = await response.json().catch(() => ({}));

                if (!response.ok) {
                    showAlert('danger', result.error?.message || 'Не удалось выполнить действие.');
                    return;
                }

                showAlert('success', result.message || 'Готово.');

                if (result.reminder_text) {
                    showAlert('info', '<strong>Текст напоминания:</strong><div class="mt-2 small">' + result.reminder_text.replace(/\n/g, '<br>') + '</div>', true);
                }

                loadOrders(state.page);
            } finally {
                if (button) button.disabled = false;
            }
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

            // «New client» fields next to an already chosen one left the master
            // guessing what to fill. While she is chosen, they are simply gone.
            [quickClientPhoneInput, quickClientNameInput].forEach(function (input) {
                const column = input?.closest('.col-md-6');
                if (column) {
                    column.classList.toggle('d-none', hasClient);
                }
            });

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

        function eligibleCount(permission) {
            let count = 0;
            selectedOrders.forEach(function (id) {
                if ((orderActions.get(id) || {})[permission]) {
                    count += 1;
                }
            });

            return count;
        }

        function updateBulkButtons() {
            const permissions = {
                confirm: 'can_confirm',
                remind: 'can_remind',
                cancel: 'can_cancel',
            };

            bulkButtons.forEach(function (btn) {
                const action = btn.getAttribute('data-action');
                const count = eligibleCount(permissions[action]);
                // The button goes out when no selected booking would take the
                // action: there was never anything to confirm twice.
                const blockedByTemplate = action === 'remind' && !state.reminderMessage;

                btn.disabled = count === 0 || blockedByTemplate;
                btn.title = blockedByTemplate
                    ? 'Сначала добавьте текст напоминания в настройках'
                    : (count === 0 ? 'Среди выбранных записей нет подходящих' : '');

                const label = btn.querySelector('.bulk-count');
                if (label) {
                    label.textContent = count ? ` (${count})` : '';
                }
            });

            if (ordersBulkBar) {
                ordersBulkBar.classList.toggle('is-visible', state.picking);
            }

            if (ordersBulkSelected) {
                ordersBulkSelected.textContent = String(selectedOrders.size);
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

        function renderTodaySummary(today) {
            if (!today || !ordersTodayLine) return;

            if (ordersTodayDate) {
                ordersTodayDate.textContent = today.date_label || '';
            }

            const parts = [];
            parts.push(today.total === 0
                ? 'записей нет'
                : today.total + ' ' + pluralOrders(today.total));

            if (today.next) {
                const when = today.next.is_today
                    ? ('в ' + today.next.time)
                    : (today.next.day_label + ', ' + today.next.time);
                const who = today.next.client_name || 'клиент';
                parts.push(`ближайшая — <a href="/orders/${today.next.id}">${who} ${when}</a>`);
            }

            ordersTodayLine.innerHTML = parts.join(' · ');
        }

        function pluralOrders(count) {
            const tail = count % 100;
            if (tail > 10 && tail < 20) return 'записей';
            switch (count % 10) {
                case 1: return 'запись';
                case 2:
                case 3:
                case 4: return 'записи';
                default: return 'записей';
            }
        }

        function syncTabs() {
            ordersTabs.forEach(function (tab) {
                tab.classList.toggle('is-active', tab.getAttribute('data-period') === state.filters.period);
            });
        }

        async function loadOrders(page = 1) {
            // The list is re-read right after an action, and clearing here wiped
            // the message saying the action went through.
            state.page = page;
            const params = new URLSearchParams({
                period: state.filters.period,
                status: state.filters.status,
                search: state.filters.search,
                page: state.page,
                per_page: state.perPage,
            });

            ordersBody.innerHTML = '<div class="orders-empty">Загружаем записи…</div>';

            const response = await fetch(`/api/v1/orders?${params.toString()}`, {
                headers: authHeaders(),
                credentials: 'include',
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                ordersBody.innerHTML = '<div class="orders-empty text-danger">Не удалось загрузить записи.</div>';
                showAlert('danger', error.error?.message || 'Произошла ошибка при загрузке списка.');
                return;
            }

            const data = await response.json();
            state.reminderMessage = data.meta.reminder_message || null;
            state.total = data.meta.pagination.total;

            renderOptions(periodSelect, data.meta.period_options, data.meta.filters.period);
            renderOptions(statusSelect, data.meta.status_options, data.meta.filters.status);

            if (document.activeElement !== searchInput) {
                searchInput.value = data.meta.filters.search || '';
            }

            if (ordersTotal) {
                ordersTotal.textContent = state.total;
            }

            renderTodaySummary(data.meta.today);
            syncTabs();

            if (!state.reminderMessage) {
                if (ordersReminderNote) {
                    ordersReminderNote.innerHTML = 'Чтобы отправлять напоминания, добавьте их текст в настройках. <a href="/settings" class="alert-link">Перейти в настройки</a>.';
                    ordersReminderNote.classList.add('is-visible');
                }
            } else if (ordersReminderNote) {
                ordersReminderNote.innerHTML = '';
                ordersReminderNote.classList.remove('is-visible');
            }

            renderOrders(data.data || []);
            renderPagination(data.meta);
        }

        function setPeriod(period) {
            state.filters.period = period;
            syncTabs();
            loadOrders(1);
        }

        function resetFilters() {
            state.filters = {
                period: 'today',
                status: 'all',
                search: ''
            };
            searchInput.value = '';
            loadOrders(1);
        }

        ordersTabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                setPeriod(this.getAttribute('data-period'));
            });
        });

        // Filters apply themselves: Apply looked like the only way to change
        // anything, and without pressing it the filter seemed broken.
        statusSelect.addEventListener('change', function () {
            state.filters.status = this.value;
            loadOrders(1);
        });

        periodSelect.addEventListener('change', function () {
            state.filters.period = this.value;
            syncTabs();
            loadOrders(1);
        });

        searchInput.addEventListener('input', function () {
            const value = this.value.trim();
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(function () {
                state.filters.search = value;
                loadOrders(1);
            }, 400);
        });

        document.getElementById('filters-reset').addEventListener('click', resetFilters);

        ordersMoreFilters.addEventListener('click', function () {
            const visible = ordersExtraFilters.classList.toggle('is-visible');
            this.classList.toggle('active', visible);
        });

        function setPicking(on) {
            state.picking = on;
            ordersBody.classList.toggle('is-picking', on);
            ordersListHead.classList.toggle('is-picking', on);
            ordersSelectMode.classList.toggle('active', on);

            if (!on) {
                selectedOrders.clear();
                selectAllCheckbox.checked = false;
                document.querySelectorAll('.order-checkbox').forEach(function (cb) {
                    cb.checked = false;
                    cb.closest('.orders-row')?.classList.remove('is-selected');
                });
            }

            updateBulkButtons();
        }

        ordersSelectMode.addEventListener('click', function () {
            setPicking(!state.picking);
        });

        ordersSelectDone.addEventListener('click', function () {
            setPicking(false);
        });

        selectAllCheckbox.addEventListener('change', function () {
            document.querySelectorAll('.order-checkbox').forEach(function (cb) {
                cb.checked = selectAllCheckbox.checked;
                const id = parseInt(cb.getAttribute('data-id'), 10);
                if (cb.checked) {
                    selectedOrders.add(id);
                } else {
                    selectedOrders.delete(id);
                }
                cb.closest('.orders-row')?.classList.toggle('is-selected', cb.checked);
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
                if (phrasePrefill) {
                    // The phrase already named her: a list of recent clients over
                    // a filled-in form is only in the way.
                    phrasePrefill = false;
                    return;
                }

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
                resetPhrase();
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

        let phrasePrefill = false;

        function openQuickModal() {
            const element = document.getElementById('quickCreateModal');
            if (!element || !window.bootstrap) return;
            bootstrap.Modal.getOrCreateInstance(element).show();
        }

        function resetPhrase() {
            if (window.BookingPhraseInput) {
                window.BookingPhraseInput.reset();
            }
            if (phraseOpenButton) {
                phraseOpenButton.classList.add('d-none');
            }
        }

        const phraseOpenButton = document.getElementById('orders-phrase-open');

        if (phraseOpenButton) {
            phraseOpenButton.addEventListener('click', function () {
                phrasePrefill = true;
                openQuickModal();
            });
        }

        /**
         * A parsed phrase lands in the quick form. Nothing is ever submitted
         * from here: the master sees the result and presses Create herself.
         */
        function applyPhraseToQuick(result) {
            const filled = (result && result.filled) || {};

            if (filled.client) {
                setQuickClientSelection(filled.client);
                if (quickClientSearchInput) {
                    quickClientSearchInput.value = filled.client.name || '';
                }
            } else if (filled.new_client) {
                setQuickClientSelection(null);

                if (quickClientNameInput && filled.new_client.name) {
                    quickClientNameInput.value = filled.new_client.name;
                }

                if (quickClientPhoneInput && filled.new_client.phone) {
                    quickClientPhoneInput.value = filled.new_client.phone;
                    quickClientPhoneInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }

            // The lookup opens its own suggestions on its own timer, over the
            // fields that were just filled in.
            clearQuickClientResults();
            clearQuickClientSuggestions();

            const wanted = (filled.services || []).map(service => String(service.id));
            document.querySelectorAll('.quick-service-checkbox').forEach(function (checkbox) {
                checkbox.checked = wanted.indexOf(checkbox.value) !== -1;
            });
            updateQuickSummary();

            if (filled.scheduled_at && quickScheduledAtInput) {
                if (window.VeloriaDateTimePicker) {
                    window.VeloriaDateTimePicker.setValue(quickScheduledAtInput, filled.scheduled_at);
                } else {
                    quickScheduledAtInput.value = filled.scheduled_at;
                }
            }

            if (filled.note && quickNoteInput && !quickNoteInput.value) {
                quickNoteInput.value = filled.note;
            }

            const choices = (result && result.choices) || [];

            if (!choices.length) {
                phrasePrefill = true;
                openQuickModal();
                return;
            }

            // The parser is asking something back: the chips stay on the page
            // rather than under the modal, and the form opens once answered.
            if (phraseOpenButton) {
                phraseOpenButton.classList.remove('d-none');
            }
        }

        function applyPhraseChoice(field, option) {
            if (field === 'client') {
                if (option.value === 'new') {
                    setQuickClientSelection(null);
                    if (quickClientNameInput && option.payload && option.payload.name) {
                        quickClientNameInput.value = option.payload.name;
                    }
                } else {
                    setQuickClientSelection(option.payload);
                    if (quickClientSearchInput) {
                        quickClientSearchInput.value = (option.payload && option.payload.name) || '';
                    }
                    clearQuickClientResults();
                }

                if (phraseOpenButton) {
                    phraseOpenButton.classList.remove('d-none');
                }

                return;
            }

            if (field === 'services') {
                const checkbox = document.querySelector('.quick-service-checkbox[value="' + option.value + '"]');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    updateQuickSummary();
                }

                return;
            }

            if (field === 'scheduled_at' && quickScheduledAtInput && quickScheduledAtInput.value) {
                const day = quickScheduledAtInput.value.slice(0, 10);
                if (window.VeloriaDateTimePicker) {
                    window.VeloriaDateTimePicker.setValue(quickScheduledAtInput, day + 'T' + option.value);
                }
            }
        }

        if (window.BookingPhraseInput) {
            window.BookingPhraseInput.init({
                authHeaders: authHeaders(),
                ensureOptions: loadQuickServices,
                getAnchorDate: function () {
                    return quickScheduledAtInput ? (quickScheduledAtInput.value || '').slice(0, 10) : '';
                },
                onParsed: applyPhraseToQuick,
                onChoice: applyPhraseChoice,
            });
        }

        updateBulkButtons();
        loadOrders();
    </script>
@endsection
