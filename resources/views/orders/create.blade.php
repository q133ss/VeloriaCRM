@extends('layouts.app')

@section('title', 'Новая запись')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Создание записи</h4>
            <p class="text-muted mb-0">Укажите клиента, время и необходимые услуги.</p>
        </div>
        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">
            <i class="ri ri-arrow-go-back-line me-1"></i>
            Вернуться к списку
        </a>
    </div>

    <div id="order-form-alerts"></div>

    <form id="order-form" class="card p-4" onsubmit="return false;">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input
                        type="text"
                        class="form-control"
                        id="master_name"
                        value="{{ auth()->user()?->name ?? 'Вы' }}"
                        readonly
                    />
                    <label for="master_name">Мастер</label>
                </div>
            </div>
            <div class="col-md-4">
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
                    <label for="client_phone">Телефон клиента</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input
                        type="text"
                        class="form-control"
                        id="client_name"
                        name="client_name"
                        placeholder="Имя клиента"
                    />
                    <label for="client_name">Имя клиента</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input
                        type="email"
                        class="form-control"
                        id="client_email"
                        name="client_email"
                        placeholder="email@example.com"
                    />
                    <label for="client_email">Email клиента</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input
                        type="datetime-local"
                        class="form-control"
                        id="scheduled_at"
                        name="scheduled_at"
                        required
                    />
                    <label for="scheduled_at">Запланированная дата и время</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <select class="form-select" id="status" name="status" required></select>
                    <label for="status">Статус</label>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Выбранные услуги</h5>
                        <small class="text-muted">Отметьте, что войдёт в заказ</small>
                    </div>
                    <div class="card-body" id="services-container">
                        <p class="text-muted mb-0">Загрузка услуг...</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Рекомендации ИИ</h5>
                        <span class="badge bg-label-primary">ИИ</span>
                    </div>
                    <div class="card-body" id="recommendations-container">
                        <p class="text-muted mb-0">Загрузка...</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-floating form-floating-outline mb-4">
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        class="form-control"
                        id="total_price"
                        name="total_price"
                    />
                    <label for="total_price">Итоговая сумма (₽)</label>
                </div>
                <div class="card">
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-6">Предварительная сумма</dt>
                            <dd class="col-6 text-end" id="summary-price">0 ₽</dd>
                            <dt class="col-6">Прогноз времени</dt>
                            <dd class="col-6 text-end" id="summary-duration">0 мин</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="form-floating form-floating-outline h-100">
                    <textarea class="form-control" id="note" name="note" style="height: 160px"></textarea>
                    <label for="note">Заметка для мастера</label>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">Отмена</a>
            <button type="submit" class="btn btn-primary">Создать запись</button>
        </div>
    </form>
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

        let servicesMap = new Map();

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
            servicesMap = new Map(services.map(service => [service.id, service]));

            if (!services.length) {
                servicesContainer.innerHTML = '<p class="text-muted mb-0">Услуги ещё не созданы. Добавьте их в разделе «Услуги».</p>';
                return;
            }

            const row = document.createElement('div');
            row.className = 'row g-3';

            services.forEach(service => {
                const col = document.createElement('div');
                col.className = 'col-md-6';
                col.innerHTML = `
                    <div class="form-check custom-option custom-option-basic">
                        <label class="form-check-label custom-option-content" for="service-${service.id}">
                            <input
                                type="checkbox"
                                class="form-check-input service-checkbox"
                                id="service-${service.id}"
                                name="services[]"
                                value="${service.id}"
                                data-price="${service.price || 0}"
                                data-duration="${service.duration || 0}"
                            />
                            <span class="custom-option-body">
                                <span class="custom-option-title d-flex align-items-center justify-content-between">
                                    <span>${service.name}</span>
                                    <span class="badge bg-label-primary">${Number(service.price || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₽</span>
                                </span>
                                <small class="text-muted">Длительность: ${service.duration || 0} мин</small>
                            </span>
                        </label>
                    </div>
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
                } else {
                    showFormAlert('info', 'Добавьте услугу вручную — она пока не найдена в списке.');
                }
            });
        }

        function renderRecommendations(recommendations) {
            if (!Array.isArray(recommendations) || !recommendations.length) {
                recommendationsContainer.innerHTML = '<p class="text-muted">Пока нет услуг для предложения.</p>';
                return;
            }

            recommendationsContainer.innerHTML = '';
            recommendations.forEach(item => {
                const wrapper = document.createElement('div');
                wrapper.className = 'mb-4';

                const service = item.service || {};
                const title = service.name || item.title || 'Рекомендация';
                const price = typeof service.price === 'number' ? service.price : null;
                const duration = typeof service.duration === 'number' ? service.duration : null;
                const insight = item.insight || 'Персонализированная рекомендация ИИ.';
                const action = item.action ? `<p class="small mb-0">${item.action}</p>` : '';

                let meta = '';
                if (price !== null || duration !== null) {
                    const parts = [];
                    if (price !== null) {
                        parts.push(`${formatCurrency(price)} ₽`);
                    }
                    if (duration !== null) {
                        parts.push(`${duration} мин`);
                    }
                    if (parts.length) {
                        meta = `<p class="small text-muted mb-2">${parts.join(' · ')}</p>`;
                    }
                }

                let confidenceBlock = '';
                if (typeof item.confidence === 'number' && !Number.isNaN(item.confidence)) {
                    const normalized = Math.min(1, Math.max(0, item.confidence));
                    const percent = Math.round(normalized * 100);
                    confidenceBlock = `<span class="badge bg-label-info align-self-start">${percent}%</span>`;
                }

                wrapper.innerHTML = `
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div class="flex-grow-1">
                            <strong>${title}</strong>
                            ${meta}
                            <p class="text-muted small mb-1">${insight}</p>
                            ${action}
                        </div>
                        ${confidenceBlock}
                    </div>
                `;

                if (service.id) {
                    const addButton = document.createElement('button');
                    addButton.type = 'button';
                    addButton.className = 'btn btn-sm btn-outline-primary mt-2';
                    addButton.textContent = 'Добавить в заказ';
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

        async function loadOptions() {
            servicesContainer.innerHTML = '<p class="text-muted mb-0">Загрузка услуг...</p>';
            recommendationsContainer.innerHTML = '<p class="text-muted mb-0">Загрузка...</p>';

            const response = await fetch('/api/v1/orders/options', {
                headers: authHeaders(),
                credentials: 'include',
            });

            if (!response.ok) {
                servicesContainer.innerHTML = '<p class="text-danger mb-0">Не удалось загрузить услуги.</p>';
                recommendationsContainer.innerHTML = '<p class="text-danger mb-0">Не удалось загрузить рекомендации.</p>';
                showFormAlert('danger', 'Не удалось загрузить данные для формы.');
                return;
            }

            const data = await response.json();
            renderServices(data.services || []);
            renderRecommendations(data.recommended_services || []);
            renderStatuses(data.status_options || {});
        }

        document.getElementById('order-form').addEventListener('submit', async function (event) {
            event.preventDefault();
            clearFormAlerts();
            const form = event.target;

            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

            const payload = {
                client_phone: form.client_phone.value,
                client_name: form.client_name.value,
                client_email: form.client_email.value,
                scheduled_at: form.scheduled_at.value,
                services: Array.from(document.querySelectorAll('.service-checkbox:checked')).map(cb => Number(cb.value)),
                note: form.note.value,
                total_price: form.total_price.value ? Number(form.total_price.value) : null,
                status: form.status.value,
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
                }, 800);
            }
        });

        loadOptions();
    </script>
@endsection
