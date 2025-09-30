@extends('layouts.app')

@section('title', 'Записи')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Записи</h4>
            <p class="text-muted mb-0">Управляйте расписанием, подтверждайте визиты и напоминания клиентам.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickCreateModal">
                <i class="ri ri-flashlight-line me-1"></i>
                Быстрое создание
            </button>
            <a href="{{ route('orders.create') }}" class="btn btn-primary">
                <i class="ri ri-add-line me-1"></i>
                Новая запись
            </a>
        </div>
    </div>

    <div id="orders-alerts"></div>

    <div class="card mb-4">
        <div class="card-body">
            <form id="filters-form" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="filter-period" class="form-label">Период</label>
                    <select class="form-select" id="filter-period" name="period"></select>
                </div>
                <div class="col-md-3">
                    <label for="filter-status" class="form-label">Статус</label>
                    <select class="form-select" id="filter-status" name="status"></select>
                </div>
                <div class="col-md-4">
                    <label for="filter-search" class="form-label">Быстрый поиск</label>
                    <input
                        type="text"
                        class="form-control"
                        id="filter-search"
                        name="search"
                        placeholder="Имя или телефон клиента"
                    />
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">Применить</button>
                    <button type="button" id="filters-reset" class="btn btn-outline-secondary flex-fill">Сбросить</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card" id="orders-card">
        <div class="card-header d-flex flex-column flex-md-row gap-2 gap-md-3 align-items-md-center justify-content-md-between">
            <div class="d-flex align-items-center gap-2">
                <h5 class="mb-0">Список записей</h5>
                <span class="badge bg-label-secondary" id="orders-total">0</span>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-success btn-sm bulk-action-btn" data-action="confirm">
                    <i class="ri ri-check-double-line me-1"></i>
                    Подтвердить выбранные
                </button>
                <button
                    type="button"
                    class="btn btn-info btn-sm text-white bulk-action-btn"
                    data-action="remind"
                    id="bulk-remind-btn"
                >
                    <i class="ri ri-mail-line me-1"></i>
                    Напомнить о записи
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm bulk-action-btn" data-action="cancel">
                    <i class="ri ri-close-circle-line me-1"></i>
                    Отменить
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" class="form-check-input" id="select-all" />
                        </th>
                        <th>Дата / Время</th>
                        <th>Клиент 📞</th>
                        <th>Услуги</th>
                        <th>Статус</th>
                        <th class="text-end">Сумма</th>
                        <th class="text-end">Действия</th>
                    </tr>
                </thead>
                <tbody id="orders-body">
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">Загрузка данных...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center" id="orders-pagination">
            <div class="text-muted small" id="orders-summary">Показано 0 из 0</div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="pagination-list"></ul>
            </nav>
        </div>
    </div>

    <div class="modal fade" id="quickCreateModal" tabindex="-1" aria-labelledby="quickCreateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickCreateModalLabel">Быстрое создание записи</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="quick-create-form" onsubmit="return false;">
                    <div class="modal-body">
                        <p class="text-muted">Укажите телефон клиента и время визита. Если клиента нет в базе, мы создадим его автоматически.</p>
                        <input type="hidden" id="quick_master_name" value="{{ auth()->user()?->name ?? 'Вы' }}" />
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="datetime-local" class="form-control" id="quick_scheduled_at" name="scheduled_at" required />
                                    <label for="quick_scheduled_at">Дата и время</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="quick_client_phone"
                                        name="client_phone"
                                        placeholder="+7(999)999-99-99"
                                        data-phone-mask
                                        required
                                    />
                                    <label for="quick_client_phone">Телефон клиента</label>
                                </div>
                                <div id="quick-client-suggestions" class="list-group list-group-flush border rounded-3 shadow-sm mt-2 d-none"></div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="quick_client_name" name="client_name" placeholder="Имя" />
                                    <label for="quick_client_name">Имя клиента</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Услуги</label>
                                <div class="row g-2" id="quick-services-container">
                                    <div class="col-12 text-muted">Загрузка услуг...</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                                    <span>Предварительная сумма</span>
                                    <span id="quick-services-summary">0 ₽</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating form-floating-outline">
                                    <textarea class="form-control" id="quick_note" name="note" style="height: 120px"></textarea>
                                    <label for="quick_note">Комментарий</label>
                                </div>
                            </div>
                        </div>
                        <div id="quick-create-errors" class="mt-3"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отменить</button>
                        <button type="submit" class="btn btn-primary">Создать</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('components.phone-mask-script')
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
        const quickForm = document.getElementById('quick-create-form');
        const quickServicesContainer = document.getElementById('quick-services-container');
        const quickServicesSummary = document.getElementById('quick-services-summary');
        const quickClientPhoneInput = document.getElementById('quick_client_phone');
        const quickClientNameInput = document.getElementById('quick_client_name');
        const quickClientSuggestions = document.getElementById('quick-client-suggestions');
        let quickLookupController = null;
        let quickLookupTimer = null;

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
                emptyRow.innerHTML = '<td colspan="7" class="text-center py-5 text-muted">Записей пока нет.</td>';
                ordersBody.appendChild(emptyRow);
                return;
            }

            orders.forEach(function (order) {
                const tr = document.createElement('tr');
                const serviceNames = (order.services || []).map(service => service.name).filter(Boolean);
                const servicesPreview = serviceNames.slice(0, 2).map(name => `<span>${name}</span>`).join('');
                const extraServices = serviceNames.length > 2 ? `<span class="text-muted small">+ ещё ${serviceNames.length - 2}</span>` : '';
                const totalPrice = order.total_price !== null && order.total_price !== undefined
                    ? new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(order.total_price)
                    : '—';

                tr.innerHTML = `
                    <td>
                        <input type="checkbox" class="form-check-input order-checkbox" data-id="${order.id}" />
                    </td>
                    <td>
                        <div class="fw-medium">${order.scheduled_at_formatted || '—'}</div>
                        <small class="text-muted">${order.master?.name || ''}</small>
                    </td>
                    <td>
                        <div class="fw-medium">${order.client?.name || 'Без имени'}</div>
                        <small class="text-muted">${order.client?.phone || '—'}</small>
                    </td>
                    <td>
                        ${serviceNames.length ? `<div class="d-flex flex-column">${servicesPreview}${extraServices}</div>` : '<span class="text-muted">Не выбраны</span>'}
                    </td>
                    <td>
                        <span class="badge ${order.status_class}">${order.status_label}</span>
                    </td>
                    <td class="text-end">${totalPrice}</td>
                    <td class="text-end">
                        <div class="btn-group" role="group">
                            <a href="/orders/${order.id}" class="btn btn-sm btn-icon btn-text-secondary" title="Просмотр">
                                <i class="ri ri-eye-line"></i>
                            </a>
                            <a href="/orders/${order.id}/edit" class="btn btn-sm btn-icon btn-text-secondary" title="Редактировать">
                                <i class="ri ri-edit-line"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-icon btn-text-secondary text-danger js-cancel-single" data-order-id="${order.id}" title="Отменить">
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
                    if (quickClientPhoneInput) {
                        quickClientPhoneInput.value = item.phone || '';
                        quickClientPhoneInput.dispatchEvent(new Event('input', { bubbles: true }));
                        quickClientPhoneInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    if (quickClientNameInput) {
                        quickClientNameInput.value = item.name || '';
                    }

                    clearQuickClientSuggestions();
                    lookupQuickClient(item.phone || '');
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
            } catch (error) {
                if (error?.name === 'AbortError') {
                    return;
                }

                quickServicesContainer.innerHTML = '<div class="col-12 text-danger">Не удалось загрузить услуги.</div>';
            }
        }

        async function lookupQuickClient(phone) {
            if (!quickClientPhoneInput) {
                return;
            }

            const value = (phone || '').toString().trim();
            const digits = value.replace(/[^0-9]+/g, '');

            if (!value || !digits.length) {
                clearQuickClientSuggestions();
                return;
            }

            if (digits.length < 3) {
                clearQuickClientSuggestions();
                return;
            }

            if (quickLookupController) {
                quickLookupController.abort();
            }

            quickLookupController = new AbortController();

            try {
                const params = new URLSearchParams({ client_phone: value });
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

                if (Array.isArray(data.suggestions)) {
                    renderQuickClientSuggestions(data.suggestions);
                } else {
                    clearQuickClientSuggestions();
                }

                if (data.client && quickClientNameInput && !quickClientNameInput.matches(':focus')) {
                    quickClientNameInput.value = data.client.name || '';
                }
            } catch (error) {
                if (error?.name === 'AbortError') {
                    return;
                }

                clearQuickClientSuggestions();
            }
        }

        function updateBulkButtons() {
            const hasSelection = selectedOrders.size > 0;
            bulkButtons.forEach(btn => {
                btn.disabled = !hasSelection;
            });
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

            ordersBody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">Загрузка данных...</td></tr>';

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

            if (!state.reminderMessage) {
                showAlert('warning', 'Добавьте текст автонапоминания в настройках, чтобы отправлять напоминания. <a href="/settings" class="alert-link">Перейти в настройки</a>.', true);
                bulkRemindBtn.disabled = true;
            } else {
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

                quickLookupTimer = setTimeout(() => lookupQuickClient(value), 400);
            });

            quickClientPhoneInput.addEventListener('blur', function () {
                const value = this.value.trim();
                if (value) {
                    lookupQuickClient(value);
                } else {
                    clearQuickClientSuggestions();
                }
            });
        }

        const quickModalElement = document.getElementById('quickCreateModal');
        if (quickModalElement) {
            quickModalElement.addEventListener('shown.bs.modal', () => {
                if (quickClientPhoneInput && quickClientPhoneInput.value.trim()) {
                    lookupQuickClient(quickClientPhoneInput.value.trim());
                }
            });

            quickModalElement.addEventListener('hidden.bs.modal', () => {
                if (quickForm) {
                    quickForm.reset();
                    updateQuickSummary();
                }
                clearQuickClientSuggestions();
            });
        }

        document.addEventListener('click', function (event) {
            if (!quickClientSuggestions || quickClientSuggestions.classList.contains('d-none')) {
                return;
            }

            if (event.target === quickClientPhoneInput) {
                return;
            }

            if (quickClientSuggestions.contains(event.target)) {
                return;
            }

            clearQuickClientSuggestions();
        });

        loadQuickServices();

        if (quickForm) {
            quickForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                const form = event.target;
                const errorsContainer = document.getElementById('quick-create-errors');
                errorsContainer.innerHTML = '';

                const payload = {
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

        loadOrders();
    </script>
@endsection
