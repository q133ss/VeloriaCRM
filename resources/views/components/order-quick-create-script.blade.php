<script>
    (function () {
        const quickModalElement = document.getElementById('quickCreateModal');
        const quickForm = document.getElementById('quick-create-form');

        if (!quickModalElement || !quickForm) {
            return;
        }

        const quickAlerts = document.getElementById('quick-create-alerts');
        const quickServicesContainer = document.getElementById('quick-services-container');
        const quickServicesSummary = document.getElementById('quick-services-summary');
        const quickClientIdInput = document.getElementById('quick_client_id');
        const quickClientSearchInput = document.getElementById('quick_client_search');
        const quickClientPhoneInput = document.getElementById('quick_client_phone');
        const quickClientNameInput = document.getElementById('quick_client_name');
        const quickSelectedClient = document.getElementById('quick-selected-client');
        const quickClientResults = document.getElementById('quick-client-results');
        const quickClientSuggestions = document.getElementById('quick-client-suggestions');
        const quickCreateErrors = document.getElementById('quick-create-errors');
        let quickLookupController = null;
        let quickLookupTimer = null;
        let quickRecentClients = [];

        function getCookie(name) {
            const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
            return match ? decodeURIComponent(match[2]) : null;
        }

        function authHeaders(extra = {}) {
            if (typeof window.authHeaders === 'function') {
                return window.authHeaders(extra);
            }

            const token = getCookie('token');
            const headers = Object.assign({ Accept: 'application/json', 'Content-Type': 'application/json' }, extra);

            if (token) {
                headers.Authorization = 'Bearer ' + token;
            }

            return headers;
        }

        function showQuickAlert(type, message) {
            if (!quickAlerts) {
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'alert alert-' + type + ' alert-dismissible fade show';
            wrapper.setAttribute('role', 'alert');
            wrapper.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            quickAlerts.appendChild(wrapper);

            setTimeout(() => {
                wrapper.classList.remove('show');
                wrapper.addEventListener('transitionend', () => wrapper.remove(), { once: true });
            }, 5000);
        }

        function formatQuickCurrency(value) {
            return value.toLocaleString('ru-RU', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' ₽';
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
            quickClientResults.innerHTML = '';
            quickClientResults.classList.add('d-none');
        }

        function clearQuickClientSuggestions() {
            quickClientSuggestions.innerHTML = '';
            quickClientSuggestions.classList.add('d-none');
        }

        function setQuickClientSelection(client) {
            const hasClient = Boolean(client && client.id);

            quickClientIdInput.value = hasClient ? client.id : '';

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

            quickClientPhoneInput.required = !hasClient;
            quickClientPhoneInput.readOnly = hasClient;
            quickClientPhoneInput.value = hasClient ? (client.phone || '') : '';

            quickClientNameInput.readOnly = hasClient;
            quickClientNameInput.value = hasClient ? (client.name || '') : '';

            clearQuickClientSuggestions();

            if (hasClient) {
                clearQuickClientResults();
            } else if (quickClientSearchInput.value.trim() === '') {
                renderQuickClientResults(quickRecentClients, 'Недавние клиенты');
            } else {
                clearQuickClientResults();
            }
        }

        function applyQuickClientDraft(client) {
            setQuickClientSelection(null);
            quickClientPhoneInput.value = client.phone || '';
            quickClientNameInput.value = client.name || '';
            clearQuickClientSuggestions();
            clearQuickClientResults();
        }

        function renderQuickClientResults(items, title = 'Клиенты') {
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

            items.forEach((item) => {
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

                    quickClientSearchInput.value = item.name || item.phone || '';
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
                quickClientSearchInput.value = '';
                quickClientPhoneInput.focus();
            });
            quickClientResults.appendChild(createButton);

            quickClientResults.classList.remove('d-none');
        }

        function renderQuickClientSuggestions(suggestions) {
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

            suggestions.forEach((item) => {
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
                        quickClientSearchInput.value = item.name || item.phone || '';
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
            let totalPrice = 0;

            quickForm.querySelectorAll('.quick-service-checkbox:checked').forEach((checkbox) => {
                totalPrice += Number(checkbox.getAttribute('data-price') || 0);
            });

            quickServicesSummary.textContent = formatQuickCurrency(totalPrice);
        }

        function renderQuickServices(services) {
            quickServicesContainer.innerHTML = '';

            if (!Array.isArray(services) || !services.length) {
                const empty = document.createElement('div');
                empty.className = 'col-12 text-muted';
                empty.textContent = 'Услуги ещё не добавлены.';
                quickServicesContainer.appendChild(empty);
                updateQuickSummary();
                return;
            }

            services.forEach((service) => {
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
                                    <span class="badge bg-label-primary">${Number(service.price || 0).toLocaleString('ru-RU', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    })} ₽</span>
                                </span>
                                <small class="text-muted">~ ${service.duration || 0} мин</small>
                            </span>
                        </label>
                    </div>
                `;
                quickServicesContainer.appendChild(col);
            });

            quickServicesContainer.querySelectorAll('.quick-service-checkbox').forEach((checkbox) => {
                checkbox.addEventListener('change', updateQuickSummary);
            });

            updateQuickSummary();
        }

        async function loadQuickServices() {
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

                if (quickClientSearchInput.value.trim() === '') {
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
            const value = String(query || '').trim();

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
                const params = new URLSearchParams(mode === 'phone'
                    ? { client_phone: value }
                    : { client_search: value });

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

                if (mode === 'phone' && data.client && !quickClientNameInput.matches(':focus')) {
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

        quickClientPhoneInput.addEventListener('input', function () {
            if (quickClientIdInput.value) {
                return;
            }

            const value = this.value.trim();
            const digits = value.replace(/[^0-9]+/g, '');

            if (quickLookupTimer) {
                clearTimeout(quickLookupTimer);
            }

            if (!value) {
                if (!quickClientNameInput.matches(':focus')) {
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
            if (quickClientIdInput.value) {
                return;
            }

            const value = this.value.trim();

            if (value) {
                lookupQuickClient(value, 'phone');
            } else {
                clearQuickClientSuggestions();
            }
        });

        quickClientSearchInput.addEventListener('input', function () {
            const value = this.value.trim();

            if (quickLookupTimer) {
                clearTimeout(quickLookupTimer);
            }

            if (!value) {
                if (quickClientIdInput.value) {
                    setQuickClientSelection(null);
                }

                renderQuickClientResults(quickRecentClients, 'Недавние клиенты');
                return;
            }

            if (quickClientIdInput.value) {
                setQuickClientSelection(null);
            }

            quickLookupTimer = setTimeout(() => lookupQuickClient(value, 'search'), 250);
        });

        quickClientSearchInput.addEventListener('focus', function () {
            if (!this.value.trim()) {
                renderQuickClientResults(quickRecentClients, 'Недавние клиенты');
            }
        });

        quickModalElement.addEventListener('shown.bs.modal', () => {
            if (!quickForm.scheduled_at.value && window.VeloriaDateTimePicker) {
                const now = new Date();
                now.setHours(10, 0, 0, 0);
                window.VeloriaDateTimePicker.setValue(quickForm.scheduled_at, [
                    now.getFullYear(),
                    String(now.getMonth() + 1).padStart(2, '0'),
                    String(now.getDate()).padStart(2, '0')
                ].join('-') + 'T10:00');
            }

            if (!quickClientSearchInput.value.trim()) {
                renderQuickClientResults(quickRecentClients, 'Недавние клиенты');
            } else if (quickClientSearchInput.value.trim()) {
                lookupQuickClient(quickClientSearchInput.value.trim(), 'search');
            } else if (quickClientPhoneInput.value.trim()) {
                lookupQuickClient(quickClientPhoneInput.value.trim(), 'phone');
            }
        });

        quickModalElement.addEventListener('hidden.bs.modal', () => {
            quickForm.reset();
            quickCreateErrors.innerHTML = '';
            updateQuickSummary();
            window.VeloriaDateTimePicker?.sync(quickForm.scheduled_at);
            setQuickClientSelection(null);
            clearQuickClientSuggestions();
            clearQuickClientResults();
        });

        document.addEventListener('click', function (event) {
            if (
                !quickClientSuggestions.classList.contains('d-none') &&
                event.target !== quickClientPhoneInput &&
                !quickClientSuggestions.contains(event.target)
            ) {
                clearQuickClientSuggestions();
            }

            if (
                !quickClientResults.classList.contains('d-none') &&
                event.target !== quickClientSearchInput &&
                !quickClientResults.contains(event.target)
            ) {
                clearQuickClientResults();
            }
        });

        quickForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            quickCreateErrors.innerHTML = '';

            const payload = {
                client_id: this.client_id.value ? Number(this.client_id.value) : null,
                client_phone: this.client_phone.value.trim(),
                client_name: this.client_name.value.trim(),
                scheduled_at: this.scheduled_at.value,
                note: this.note.value,
            };

            const selectedServices = Array.from(this.querySelectorAll('.quick-service-checkbox:checked'));
            const services = selectedServices.map((checkbox) => Number(checkbox.value));
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

                    Object.keys(fields).forEach((key) => {
                        const li = document.createElement('li');
                        li.textContent = fields[key][0];
                        list.appendChild(li);
                    });

                    quickCreateErrors.appendChild(list);
                } else {
                    quickCreateErrors.innerHTML = '<div class="text-danger">' + (result.error?.message || 'Не удалось создать запись.') + '</div>';
                }

                return;
            }

            const modal = bootstrap.Modal.getInstance(quickModalElement);
            if (modal) {
                modal.hide();
            }

            showQuickAlert('success', result.message || 'Запись создана.');

            if (result.data?.id) {
                window.location.href = `/orders/${result.data.id}`;
                return;
            }

            window.location.href = '/orders';
        });

        updateQuickSummary();
        loadQuickServices();
    })();
</script>
