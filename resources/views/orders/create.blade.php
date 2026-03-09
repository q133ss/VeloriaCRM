@extends('layouts.app')

@section('title', 'Новая запись')

@section('content')
    <style>
        .order-create-page .client-picker-layer {
            position: relative;
        }

        .order-create-page #client-results,
        .order-create-page #client-suggestions {
            position: absolute;
            top: calc(100% + 0.5rem);
            left: 0;
            right: 0;
            z-index: 40;
            max-height: 280px;
            overflow-y: auto;
            margin-top: 0;
            background: var(--bs-paper-bg, var(--bs-body-bg));
            box-shadow: 0 1rem 2rem rgba(15, 23, 42, 0.28);
        }

        .order-create-page .summary-card {
            position: sticky;
            top: 1.5rem;
        }

        .order-create-page .service-card {
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            padding: 1rem;
            transition: border-color .2s ease, transform .2s ease, box-shadow .2s ease;
        }

        .order-create-page .service-card:hover {
            transform: translateY(-1px);
            border-color: rgba(var(--bs-primary-rgb), 0.45);
            box-shadow: 0 0.75rem 1.5rem rgba(15, 23, 42, 0.16);
        }

        .order-create-page .service-card input:checked + .service-card-body {
            border-color: rgba(var(--bs-primary-rgb), 0.7);
        }

        .order-create-page .service-card-body {
            display: block;
            border: 1px solid transparent;
            border-radius: .8rem;
            padding: .15rem;
        }
    </style>

    <div class="order-create-page">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="mb-1">Новая запись</h4>
                <p class="text-muted mb-0">Сначала выберите клиентку, потом время и услуги. Всё лишнее спрятано ниже.</p>
            </div>
            <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">
                <i class="ri ri-arrow-go-back-line me-1"></i>
                К списку записей
            </a>
        </div>

        <div id="order-form-alerts"></div>

        <form id="order-form" onsubmit="return false;">
            <input type="hidden" id="client_id" name="client_id" />

            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <h5 class="mb-1">1. Клиентка</h5>
                                    <p class="text-muted mb-0">Найдите из недавних или добавьте новую.</p>
                                </div>
                                <span class="badge bg-label-primary">Быстро</span>
                            </div>

                            <div class="client-picker-layer">
                                <div class="form-floating form-floating-outline">
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="client_search"
                                        placeholder="Анна, +7..., Иван"
                                        autocomplete="off"
                                    />
                                    <label for="client_search">Найти существующую клиентку</label>
                                </div>
                                <div id="client-results" class="list-group list-group-flush border rounded-3 d-none"></div>
                            </div>

                            <div class="form-text mt-2">Поиск по имени и телефону. Сначала показываются недавние клиентки.</div>
                            <div id="selected-client" class="alert alert-primary d-none mt-3 mb-0"></div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <div class="client-picker-layer">
                                        <div class="form-floating form-floating-outline">
                                            <input
                                                type="text"
                                                class="form-control"
                                                id="client_phone"
                                                name="client_phone"
                                                placeholder="+7(999)999-99-99"
                                                data-phone-mask
                                                required
                                            />
                                            <label for="client_phone">Телефон новой клиентки</label>
                                        </div>
                                        <div id="client-suggestions" class="list-group list-group-flush border rounded-3 d-none"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating form-floating-outline">
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="client_name"
                                            name="client_name"
                                            placeholder="Имя клиентки"
                                        />
                                        <label for="client_name">Имя клиентки</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <h5 class="mb-1">2. Визит</h5>
                                    <p class="text-muted mb-0">Только дата и набор услуг.</p>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="form-floating form-floating-outline">
                                        <input
                                            type="datetime-local"
                                            class="form-control"
                                            id="scheduled_at"
                                            name="scheduled_at"
                                            required
                                        />
                                        <label for="scheduled_at">Дата и время записи</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="rounded-4 border p-3 h-100">
                                        <div class="small text-muted mb-1">Мастер</div>
                                        <div class="fw-semibold">{{ auth()->user()?->name ?? 'Вы' }}</div>
                                        <div class="small text-muted mt-2">Статус новой записи будет выставлен автоматически.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h6 class="mb-1">Услуги</h6>
                                    <p class="text-muted small mb-0">Отметьте всё, что входит в визит.</p>
                                </div>
                            </div>

                            <div id="services-container">
                                <p class="text-muted mb-0">Загрузка услуг...</p>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <details>
                                <summary class="fw-semibold mb-3" style="cursor: pointer;">Дополнительно</summary>
                                <div class="row g-3 pt-2">
                                    <div class="col-md-6">
                                        <div class="form-floating form-floating-outline">
                                            <input
                                                type="email"
                                                class="form-control"
                                                id="client_email"
                                                name="client_email"
                                                placeholder="email@example.com"
                                            />
                                            <label for="client_email">Email</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating form-floating-outline">
                                            <select class="form-select" id="status" name="status" required></select>
                                            <label for="status">Статус</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating form-floating-outline">
                                            <textarea class="form-control" id="note" name="note" style="height: 140px"></textarea>
                                            <label for="note">Комментарий для мастера</label>
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="summary-card d-flex flex-column gap-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h5 class="mb-3">Итог</h5>

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Предварительная сумма</span>
                                    <strong id="summary-price">0 ₽</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted">Прогноз времени</span>
                                    <strong id="summary-duration">0 мин</strong>
                                </div>

                                <div class="form-floating form-floating-outline mb-3">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="form-control"
                                        id="total_price"
                                        name="total_price"
                                    />
                                    <label for="total_price">Своя сумма, если нужно</label>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">Создать запись</button>
                                    <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">Отмена</a>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="mb-1">Подсказки по услугам</h5>
                                    <p class="text-muted small mb-0">Можно быстро добавить в запись.</p>
                                </div>
                                <span class="badge bg-label-primary">AI</span>
                            </div>
                            <div class="card-body p-4" id="recommendations-container">
                                <p class="text-muted mb-0">Загрузка...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
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

        const alertsContainer = document.getElementById('order-form-alerts');
        const servicesContainer = document.getElementById('services-container');
        const recommendationsContainer = document.getElementById('recommendations-container');
        const summaryPrice = document.getElementById('summary-price');
        const summaryDuration = document.getElementById('summary-duration');
        const totalPriceInput = document.getElementById('total_price');
        const statusSelect = document.getElementById('status');
        const scheduledAtInput = document.getElementById('scheduled_at');
        const clientIdInput = document.getElementById('client_id');
        const clientSearchInput = document.getElementById('client_search');
        const clientPhoneInput = document.getElementById('client_phone');
        const clientNameInput = document.getElementById('client_name');
        const selectedClient = document.getElementById('selected-client');
        const clientResults = document.getElementById('client-results');
        const clientSuggestions = document.getElementById('client-suggestions');

        let lookupController = null;
        let lookupTimer = null;
        let recentClients = [];

        function showFormAlert(type, message) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-' + type + ' alert-dismissible fade show';
            alert.setAttribute('role', 'alert');
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            alertsContainer.appendChild(alert);
        }

        function clearFormAlerts() {
            alertsContainer.innerHTML = '';
        }

        function renderServices(services) {
            if (!services.length) {
                servicesContainer.innerHTML = '<p class="text-muted mb-0">Услуги ещё не добавлены.</p>';
                return;
            }

            const row = document.createElement('div');
            row.className = 'row g-3';

            services.forEach(service => {
                const col = document.createElement('div');
                col.className = 'col-md-6';
                col.innerHTML = `
                    <label class="service-card w-100">
                        <input
                            type="checkbox"
                            class="form-check-input service-checkbox"
                            name="services[]"
                            value="${service.id}"
                            data-price="${service.price || 0}"
                            data-duration="${service.duration || 0}"
                        />
                        <span class="service-card-body d-block">
                            <span class="d-flex align-items-start justify-content-between gap-3">
                                <span>
                                    <span class="fw-semibold d-block">${service.name}</span>
                                    <small class="text-muted">~ ${service.duration || 0} мин</small>
                                </span>
                                <span class="badge bg-label-primary">${Number(service.price || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₽</span>
                            </span>
                        </span>
                    </label>
                `;
                row.appendChild(col);
            });

            servicesContainer.innerHTML = '';
            servicesContainer.appendChild(row);

            document.querySelectorAll('.service-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateSummary);
            });
        }

        function formatCurrency(value) {
            return value.toLocaleString('ru-RU', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            });
        }

        function attachServiceHandler(button, serviceId) {
            if (!button || !serviceId) {
                return;
            }

            button.addEventListener('click', () => {
                const checkbox = document.querySelector(`.service-checkbox[value="${serviceId}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    checkbox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        }

        function renderRecommendations(recommendations) {
            if (!Array.isArray(recommendations) || !recommendations.length) {
                recommendationsContainer.innerHTML = '<p class="text-muted mb-0">Пока подсказок нет.</p>';
                return;
            }

            recommendationsContainer.innerHTML = '';

            recommendations.forEach(item => {
                const wrapper = document.createElement('div');
                wrapper.className = 'border rounded-4 p-3 mb-3';

                const service = item.service || {};
                const title = service.name || item.title || 'Рекомендация';
                const price = typeof service.price === 'number' ? service.price : null;
                const duration = typeof service.duration === 'number' ? service.duration : null;
                const insight = item.insight || 'Персональная подсказка по этой клиентке.';

                const meta = [];
                if (price !== null) meta.push(`${formatCurrency(price)} ₽`);
                if (duration !== null) meta.push(`${duration} мин`);

                wrapper.innerHTML = `
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <div class="fw-semibold">${title}</div>
                            ${meta.length ? `<div class="small text-muted mb-2">${meta.join(' • ')}</div>` : ''}
                            <div class="small text-muted">${insight}</div>
                        </div>
                        ${typeof item.confidence === 'number' ? `<span class="badge bg-label-info">${Math.round(Math.min(1, Math.max(0, item.confidence)) * 100)}%</span>` : ''}
                    </div>
                `;

                if (service.id) {
                    const addButton = document.createElement('button');
                    addButton.type = 'button';
                    addButton.className = 'btn btn-sm btn-outline-primary mt-3';
                    addButton.textContent = 'Добавить';
                    attachServiceHandler(addButton, service.id);
                    wrapper.appendChild(addButton);
                }

                recommendationsContainer.appendChild(wrapper);
            });
        }

        function renderStatuses(statuses) {
            statusSelect.innerHTML = '';
            Object.keys(statuses).forEach(key => {
                const option = document.createElement('option');
                option.value = key;
                option.textContent = statuses[key];
                if (key === 'new') option.selected = true;
                statusSelect.appendChild(option);
            });
        }

        function updateSummary() {
            let totalPrice = 0;
            let totalDuration = 0;

            document.querySelectorAll('.service-checkbox:checked').forEach(checkbox => {
                totalPrice += Number(checkbox.getAttribute('data-price') || 0);
                totalDuration += Number(checkbox.getAttribute('data-duration') || 0);
            });

            summaryPrice.textContent = `${totalPrice.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₽`;
            summaryDuration.textContent = `${totalDuration} мин`;

            if (!totalPriceInput.value) {
                totalPriceInput.value = totalPrice ? totalPrice.toFixed(2) : '';
            }
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

        function clearClientSuggestions() {
            if (!clientSuggestions) {
                return;
            }

            clientSuggestions.innerHTML = '';
            clientSuggestions.classList.add('d-none');
        }

        function clearClientResults() {
            if (!clientResults) {
                return;
            }

            clientResults.innerHTML = '';
            clientResults.classList.add('d-none');
        }

        function setClientSelection(client) {
            const hasClient = Boolean(client && client.id);

            if (clientIdInput) {
                clientIdInput.value = hasClient ? client.id : '';
            }

            if (selectedClient) {
                if (hasClient) {
                    selectedClient.innerHTML = `
                        <div>
                            <div class="fw-semibold">Выбрана клиентка: ${client.name || 'Без имени'}</div>
                            <div class="small">${formatSuggestionPhone(client.phone || '') || 'Без телефона'}</div>
                            ${client.last_visit_at_formatted ? `<div class="small mt-1 opacity-75">Последний визит: ${client.last_visit_at_formatted}</div>` : ''}
                        </div>
                    `;
                    selectedClient.classList.remove('d-none');
                } else {
                    selectedClient.innerHTML = '';
                    selectedClient.classList.add('d-none');
                }
            }

            if (clientPhoneInput) {
                clientPhoneInput.required = !hasClient;
                clientPhoneInput.readOnly = hasClient;
                clientPhoneInput.value = hasClient ? (client.phone || '') : '';
            }

            if (clientNameInput) {
                clientNameInput.readOnly = hasClient;
                clientNameInput.value = hasClient ? (client.name || '') : '';
            }

            clearClientSuggestions();

            if (hasClient) {
                clearClientResults();
            } else if (clientSearchInput && clientSearchInput.value.trim() === '') {
                renderClientResults(recentClients, 'Недавние клиентки');
            } else {
                clearClientResults();
            }
        }

        function renderClientResults(items, title = 'Клиентки') {
            if (!clientResults) {
                return;
            }

            clientResults.innerHTML = '';

            if (!Array.isArray(items) || !items.length) {
                clientResults.classList.add('d-none');
                return;
            }

            const header = document.createElement('div');
            header.className = 'list-group-item small text-muted';
            header.textContent = title;
            header.tabIndex = -1;
            clientResults.appendChild(header);

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
                    setClientSelection(item);
                    if (clientSearchInput) {
                        clientSearchInput.value = item.name || item.phone || '';
                    }
                });
                clientResults.appendChild(button);
            });

            const createButton = document.createElement('button');
            createButton.type = 'button';
            createButton.className = 'list-group-item list-group-item-action d-flex align-items-center justify-content-between gap-2 text-primary';
            createButton.innerHTML = `
                <span class="fw-medium">Добавить новую клиентку</span>
                <i class="ri ri-user-add-line"></i>
            `;
            createButton.addEventListener('click', () => {
                setClientSelection(null);
                clearClientResults();
                if (clientSearchInput) {
                    clientSearchInput.value = '';
                }
                if (clientPhoneInput) {
                    clientPhoneInput.focus();
                }
            });
            clientResults.appendChild(createButton);

            clientResults.classList.remove('d-none');
        }

        function renderClientSuggestions(suggestions) {
            if (!clientSuggestions) {
                return;
            }

            clientSuggestions.innerHTML = '';

            if (!Array.isArray(suggestions) || !suggestions.length) {
                clientSuggestions.classList.add('d-none');
                return;
            }

            const header = document.createElement('div');
            header.className = 'list-group-item small text-muted';
            header.textContent = 'Похожие клиентки';
            header.tabIndex = -1;
            clientSuggestions.appendChild(header);

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
                        setClientSelection(item);
                        if (clientSearchInput) {
                            clientSearchInput.value = item.name || item.phone || '';
                        }
                    } else {
                        if (clientPhoneInput) {
                            clientPhoneInput.value = item.phone || '';
                            clientPhoneInput.dispatchEvent(new Event('input', { bubbles: true }));
                            clientPhoneInput.dispatchEvent(new Event('change', { bubbles: true }));
                        }

                        if (clientNameInput && !clientNameInput.matches(':focus')) {
                            clientNameInput.value = item.name || '';
                        }
                    }

                    clearClientSuggestions();
                });

                clientSuggestions.appendChild(button);
            });

            clientSuggestions.classList.remove('d-none');
        }

        async function lookupClient(query, mode = 'search') {
            const value = (query || '').toString().trim();

            if (!value) {
                clearClientSuggestions();
                if (mode === 'search') {
                    renderClientResults(recentClients, 'Недавние клиентки');
                }
                return;
            }

            if (mode === 'phone' && value.replace(/[^0-9]+/g, '').length < 3) {
                clearClientSuggestions();
                return;
            }

            if (mode === 'search' && value.length < 2) {
                renderClientResults(recentClients, 'Недавние клиентки');
                return;
            }

            if (lookupController) {
                lookupController.abort();
            }

            lookupController = new AbortController();

            try {
                const params = new URLSearchParams(
                    mode === 'phone'
                        ? { client_phone: value }
                        : { client_search: value }
                );
                const response = await fetch(`/api/v1/orders/options?${params.toString()}`, {
                    headers: authHeaders(),
                    credentials: 'include',
                    signal: lookupController.signal,
                });

                if (!response.ok) {
                    clearClientSuggestions();
                    clearClientResults();
                    return;
                }

                const data = await response.json();

                if (mode === 'search') {
                    renderClientResults(Array.isArray(data.suggestions) ? data.suggestions : [], 'Найденные клиентки');
                    clearClientSuggestions();
                } else if (Array.isArray(data.suggestions)) {
                    renderClientSuggestions(data.suggestions);
                } else {
                    clearClientSuggestions();
                }

                if (mode === 'phone' && data.client && clientNameInput && !clientNameInput.matches(':focus')) {
                    clientNameInput.value = data.client.name || '';
                }
            } catch (error) {
                if (error?.name === 'AbortError') {
                    return;
                }

                clearClientSuggestions();
                clearClientResults();
            }
        }

        function applyDateFromUrl() {
            if (!scheduledAtInput) {
                return;
            }

            const params = new URLSearchParams(window.location.search);
            const dateParam = params.get('date');

            if (!dateParam || !/^\d{4}-\d{2}-\d{2}$/.test(dateParam)) {
                return;
            }

            const currentValue = scheduledAtInput.value || '';
            const timePart = currentValue.includes('T') ? currentValue.split('T')[1] : '00:00';

            scheduledAtInput.value = `${dateParam}T${timePart}`;
        }

        async function loadOptions() {
            servicesContainer.innerHTML = '<p class="text-muted mb-0">Загрузка услуг...</p>';
            recommendationsContainer.innerHTML = '<p class="text-muted mb-0">Загрузка...</p>';

            const response = await fetch('/api/v1/orders/options', {
                headers: authHeaders(),
                credentials: 'include',
            });

            if (!response.ok) {
                servicesContainer.innerHTML = '<p class="text-danger mb-0">Не удалось загрузить услуги.</p>';
                recommendationsContainer.innerHTML = '<p class="text-danger mb-0">Не удалось загрузить подсказки.</p>';
                showFormAlert('danger', 'Не удалось загрузить данные для формы.');
                return;
            }

            const data = await response.json();
            recentClients = Array.isArray(data.recent_clients) ? data.recent_clients : [];
            renderServices(data.services || []);
            renderRecommendations(data.recommended_services || []);
            renderStatuses(data.status_options || {});
            renderClientResults(recentClients, 'Недавние клиентки');
            updateSummary();
        }

        document.getElementById('order-form').addEventListener('submit', async function (event) {
            event.preventDefault();
            clearFormAlerts();
            const form = event.target;

            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

            const payload = {
                client_id: form.client_id.value ? Number(form.client_id.value) : null,
                client_phone: form.client_phone.value,
                client_name: form.client_name.value,
                client_email: form.client_email.value,
                scheduled_at: form.scheduled_at.value,
                services: Array.from(document.querySelectorAll('.service-checkbox:checked')).map(cb => Number(cb.value)),
                note: form.note.value,
                total_price: form.total_price.value ? Number(form.total_price.value) : null,
                status: form.status.value || 'new',
            };

            const response = await fetch('/api/v1/orders', {
                method: 'POST',
                headers: authHeaders(),
                credentials: 'include',
                body: JSON.stringify(payload),
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                const fields = result.error?.fields || {};
                if (Object.keys(fields).length) {
                    Object.keys(fields).forEach(key => {
                        const fieldName = key.replace(/\.(\w+)/g, '[$1]');
                        const input = form.querySelector(`[name="${fieldName}"]`);
                        if (input) {
                            input.classList.add('is-invalid');
                            const feedback = document.createElement('div');
                            feedback.className = 'invalid-feedback';
                            feedback.textContent = fields[key][0];
                            if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('invalid-feedback')) {
                                input.parentNode.appendChild(feedback);
                            }
                        }
                    });
                } else {
                    showFormAlert('danger', result.error?.message || 'Не удалось создать запись.');
                }
                return;
            }

            showFormAlert('success', result.message || 'Запись создана. Перенаправляем...');
            if (result.data?.id) {
                setTimeout(() => {
                    window.location.href = `/orders/${result.data.id}`;
                }, 700);
            }
        });

        if (clientPhoneInput) {
            clientPhoneInput.addEventListener('input', function () {
                if (clientIdInput && clientIdInput.value) {
                    return;
                }

                const value = this.value.trim();
                const digits = value.replace(/[^0-9]+/g, '');

                if (lookupTimer) {
                    clearTimeout(lookupTimer);
                }

                if (!value) {
                    if (clientNameInput && !clientNameInput.matches(':focus')) {
                        clientNameInput.value = '';
                    }
                    clearClientSuggestions();
                    return;
                }

                if (digits.length < 3) {
                    clearClientSuggestions();
                    return;
                }

                lookupTimer = setTimeout(() => lookupClient(value, 'phone'), 400);
            });
        }

        if (clientSearchInput) {
            clientSearchInput.addEventListener('input', function () {
                const value = this.value.trim();

                if (lookupTimer) {
                    clearTimeout(lookupTimer);
                }

                if (!value) {
                    if (clientIdInput && clientIdInput.value) {
                        setClientSelection(null);
                    }
                    renderClientResults(recentClients, 'Недавние клиентки');
                    return;
                }

                if (clientIdInput && clientIdInput.value) {
                    setClientSelection(null);
                }

                lookupTimer = setTimeout(() => lookupClient(value, 'search'), 250);
            });

            clientSearchInput.addEventListener('focus', function () {
                if (!this.value.trim()) {
                    renderClientResults(recentClients, 'Недавние клиентки');
                }
            });
        }

        document.addEventListener('click', function (event) {
            if (
                clientSuggestions &&
                !clientSuggestions.classList.contains('d-none') &&
                event.target !== clientPhoneInput &&
                !clientSuggestions.contains(event.target)
            ) {
                clearClientSuggestions();
            }

            if (
                clientResults &&
                !clientResults.classList.contains('d-none') &&
                event.target !== clientSearchInput &&
                !clientResults.contains(event.target)
            ) {
                clearClientResults();
            }
        });

        applyDateFromUrl();
        loadOptions();
    </script>
@endsection
