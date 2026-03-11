@extends('layouts.app')

@section('title', 'Новая запись')

@section('meta')
    @include('components.veloria-datetime-picker-styles')
@endsection

@section('content')
    <style>
        .order-create-page {
            --order-create-accent-soft: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.1);
            --order-create-border: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.12);
            --order-create-shadow: 0 24px 54px -36px rgba(37, 26, 84, 0.42);
        }

        .order-create-page .create-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--order-create-border);
            border-radius: 1.6rem;
            padding: 1.6rem;
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.14), transparent 34%),
                linear-gradient(140deg, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.06), rgba(var(--bs-info-rgb, 0, 207, 232), 0.05) 58%, rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.12));
            box-shadow: var(--order-create-shadow);
        }

        .order-create-page .create-hero::after {
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

        .order-create-page .create-hero > * {
            position: relative;
            z-index: 1;
        }

        .order-create-page .create-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            padding: 0.45rem 0.8rem;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.72);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .order-create-page .create-eyebrow i {
            color: var(--bs-primary);
        }

        .order-create-page .step-card,
        .order-create-page .summary-card {
            border: none;
            border-radius: 1.35rem;
            box-shadow: var(--order-create-shadow);
            background: color-mix(in srgb, var(--bs-card-bg) 96%, transparent);
        }

        .order-create-page .step-card {
            padding: 1.4rem;
        }

        .order-create-page .step-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            background: var(--order-create-accent-soft);
            color: var(--bs-primary);
            font-weight: 700;
        }

        .order-create-page .step-meta {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08);
            color: var(--bs-primary);
            font-size: 0.78rem;
            font-weight: 600;
        }

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

        .order-create-page .inline-hint {
            padding: 0.9rem 1rem;
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.03);
        }

        .order-create-page .selected-client-card {
            border: 1px solid rgba(var(--bs-primary-rgb, 255, 0, 252), 0.16);
            border-radius: 1rem;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.06);
        }

        .order-create-page .manual-client-card {
            border-radius: 1.1rem;
            border: 1px dashed rgba(var(--bs-primary-rgb, 255, 0, 252), 0.22);
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.02);
        }

        .order-create-page .manual-client-card.d-none {
            display: none !important;
        }

        .order-create-page .visit-meta-card {
            border-radius: 1rem;
            padding: 1rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.03);
        }

        .order-create-page .services-toolbar {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.85rem;
            align-items: end;
        }

        .order-create-page .services-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .order-create-page .service-card {
            display: block;
            border: 1px solid rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.08);
            border-radius: 1rem;
            padding: 0.95rem 1rem;
            transition: border-color .2s ease, transform .2s ease, box-shadow .2s ease, background-color .2s ease;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.72);
            cursor: pointer;
        }

        .order-create-page .service-card:hover {
            transform: translateY(-1px);
            border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.35);
            box-shadow: 0 0.8rem 1.6rem rgba(15, 23, 42, 0.12);
        }

        .order-create-page .service-card input {
            margin-top: 0.15rem;
        }

        .order-create-page .service-card input:checked + .service-card-body {
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.06);
            border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.38);
        }

        .order-create-page .service-card-body {
            display: block;
            border: 1px solid transparent;
            border-radius: 0.8rem;
            padding: 0.35rem;
        }

        .order-create-page .service-meta-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.3rem 0.65rem;
            border-radius: 999px;
            background: var(--order-create-accent-soft);
            color: var(--bs-primary);
            font-size: 0.78rem;
            font-weight: 700;
        }

        .order-create-page .service-duration {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: var(--bs-secondary-color);
        }

        .order-create-page .summary-card {
            position: sticky;
            top: 1.5rem;
            padding: 1.35rem;
        }

        .order-create-page .summary-kpis {
            display: grid;
            gap: 0.75rem;
        }

        .order-create-page .summary-kpi {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.85rem 0.95rem;
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.03);
        }

        .order-create-page .summary-kpi span {
            color: var(--bs-secondary-color);
        }

        .order-create-page .recommendations-shell {
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.02);
            padding: 1rem;
        }

        .order-create-page .recommendation-card {
            border: 1px solid rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.08);
            border-radius: 1rem;
            padding: 1rem;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.76);
        }

        .order-create-page .recommendation-card + .recommendation-card {
            margin-top: 0.85rem;
        }

        @media (max-width: 1199.98px) {
            .order-create-page .summary-card {
                position: static;
            }
        }

        @media (max-width: 991.98px) {
            .order-create-page .services-grid,
            .order-create-page .services-toolbar {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="order-create-page">
        <div class="d-flex flex-column gap-4">
            <section class="create-hero">
                <div class="d-flex flex-column flex-xl-row justify-content-between gap-4 align-items-xl-start">
                    <div class="d-flex flex-column gap-3">
                        <span class="create-eyebrow">
                            <i class="ri ri-sparkling-line"></i>
                            Создание записи по шагам
                        </span>
                        <div>
                            <h1 class="mb-2">Новая запись</h1>
                            <p class="text-muted mb-0 fs-6">Сначала выберите клиентку, потом дату и услуги. Всё второстепенное убрали в отдельные блоки ниже.</p>
                        </div>
                        <div class="small text-muted">Основной сценарий: выбрать клиентку, поставить время, отметить услуги и создать запись.</div>
                    </div>
                    <div class="d-flex flex-column flex-sm-row gap-2 align-self-stretch align-self-xl-start">
                        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">
                            <i class="ri ri-arrow-go-back-line me-1"></i>
                            К списку записей
                        </a>
                    </div>
                </div>
            </section>

            <div id="order-form-alerts"></div>

            <form id="order-form" onsubmit="return false;">
                <input type="hidden" id="client_id" name="client_id" />

                <div class="row g-4">
                    <div class="col-xl-8">
                        <div class="step-card mb-4">
                            <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3 mb-4">
                                <div class="d-flex gap-3">
                                    <span class="step-badge">1</span>
                                    <div>
                                        <h2 class="h5 mb-1">Клиентка</h2>
                                        <p class="text-muted mb-0">Сначала ищем существующую, только потом при необходимости добавляем новую вручную.</p>
                                    </div>
                                </div>
                                <span class="step-meta">Быстрый старт</span>
                            </div>

                            <div class="d-flex justify-content-start mb-3">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="toggle-manual-client">
                                    <i class="ri ri-user-add-line me-1"></i>
                                    Добавить новую клиентку
                                </button>
                            </div>

                            <div class="client-picker-layer mb-3">
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

                            <div class="inline-hint small text-muted mb-3">Поиск работает по имени и телефону. Сначала показываются недавние клиентки, чтобы не вводить всё заново.</div>
                            <div id="selected-client" class="selected-client-card d-none p-3 mb-3"></div>

                            <div class="manual-client-card p-3 d-none" id="manual-client-card">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <h3 class="h6 mb-1">Новая клиентка</h3>
                                        <p class="text-muted small mb-0">Поля открываются только если клиентки нет в поиске.</p>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-text-secondary" id="hide-manual-client">Скрыть</button>
                                </div>

                                <div class="row g-3">
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

                        <div class="step-card mb-4">
                            <div class="d-flex gap-3 mb-4">
                                <span class="step-badge">2</span>
                                <div>
                                    <h2 class="h5 mb-1">Визит</h2>
                                    <p class="text-muted mb-0">Выберите дату, а затем отметьте только те услуги, которые действительно входят в запись.</p>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-7 d-none">
                                    <div class="form-floating form-floating-outline">
                                        <input
                                            type="datetime-local"
                                            class="form-control"
                                            id="scheduled_at_legacy"
                                            name="scheduled_at_legacy"
                                            required
                                        />
                                        <label for="scheduled_at">Дата и время записи</label>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    @include('components.veloria-datetime-field', [
                                        'id' => 'scheduled_at',
                                        'name' => 'scheduled_at',
                                        'label' => 'Дата и время записи',
                                        'required' => true,
                                        'helper' => 'Нажмите на поле, чтобы открыть календарь, или выберите быстрый слот ниже.',
                                        'timeSlots' => ['09:00', '11:00', '13:00', '15:00', '18:00'],
                                    ])
                                </div>
                                <div class="col-md-5 d-flex align-items-center">
                                    <div class="small text-muted">Мастер: <span class="fw-semibold text-body">{{ auth()->user()?->name ?? 'Вы' }}</span>. Статус новой записи можно поменять в блоке `Дополнительно`.</div>
                                </div>
                            </div>

                            <div class="services-toolbar mb-3">
                                <div>
                                    <h3 class="h6 mb-1">Услуги</h3>
                                    <p class="text-muted small mb-0">Можно быстро отфильтровать список по названию и оставить только нужное.</p>
                                </div>
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="service-search" placeholder="Поиск услуги" />
                                    <label for="service-search">Поиск услуги</label>
                                </div>
                            </div>

                            <div id="services-container">
                                <p class="text-muted mb-0">Загрузка услуг...</p>
                            </div>
                        </div>

                        <div class="step-card mb-4">
                            <details>
                                <summary class="fw-semibold" style="cursor: pointer;">3. Дополнительно</summary>
                                <div class="row g-3 pt-3">
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

                    <div class="col-xl-4">
                        <div class="summary-card d-flex flex-column gap-4">
                            <div>
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                    <h2 class="h5 mb-0">Итог записи</h2>
                                    <span class="badge bg-label-primary">Готово к отправке</span>
                                </div>
                                <p class="text-muted small mb-0">Главные цифры и финальное действие остаются под рукой, но не мешают заполнению формы.</p>
                            </div>

                            <div class="summary-kpis">
                                <div class="summary-kpi">
                                    <span>Предварительная сумма</span>
                                    <strong id="summary-price">0 ₽</strong>
                                </div>
                                <div class="summary-kpi">
                                    <span>Прогноз времени</span>
                                    <strong id="summary-duration">0 мин</strong>
                                </div>
                            </div>

                            <div class="small text-muted" id="summary-selection-note">
                                Выберите клиентку, дату и хотя бы одну услугу, чтобы запись была полностью готова.
                            </div>

                            <div class="form-floating form-floating-outline d-none" id="custom-price-wrap">
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

                            <details class="recommendations-shell">
                                <summary class="d-flex align-items-start justify-content-between gap-3" style="cursor: pointer;">
                                    <span>
                                        <span class="h6 d-block mb-1">Подсказки по услугам</span>
                                        <span class="text-muted small">Дополнительные идеи для среднего чека. Не обязательны для заполнения.</span>
                                    </span>
                                    <span class="badge bg-label-primary">AI</span>
                                </summary>
                                <div id="recommendations-container" class="pt-3">
                                    <p class="text-muted mb-0">Загрузка...</p>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            </form>
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

        const alertsContainer = document.getElementById('order-form-alerts');
        const servicesContainer = document.getElementById('services-container');
        const recommendationsContainer = document.getElementById('recommendations-container');
        const summaryPrice = document.getElementById('summary-price');
        const summaryDuration = document.getElementById('summary-duration');
        const totalPriceInput = document.getElementById('total_price');
        const customPriceWrap = document.getElementById('custom-price-wrap');
        const statusSelect = document.getElementById('status');
        const scheduledAtInput = document.getElementById('scheduled_at');
        const serviceSearchInput = document.getElementById('service-search');
        const clientIdInput = document.getElementById('client_id');
        const clientSearchInput = document.getElementById('client_search');
        const clientPhoneInput = document.getElementById('client_phone');
        const clientNameInput = document.getElementById('client_name');
        const selectedClient = document.getElementById('selected-client');
        const clientResults = document.getElementById('client-results');
        const clientSuggestions = document.getElementById('client-suggestions');
        const summarySelectionNote = document.getElementById('summary-selection-note');
        const manualClientCard = document.getElementById('manual-client-card');
        const toggleManualClientButton = document.getElementById('toggle-manual-client');
        const hideManualClientButton = document.getElementById('hide-manual-client');

        let lookupController = null;
        let lookupTimer = null;
        let recentClients = [];
        let availableServices = [];

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

            const selected = new Set(
                Array.from(document.querySelectorAll('.service-checkbox:checked')).map(checkbox => String(checkbox.value))
            );

            const row = document.createElement('div');
            row.className = 'services-grid';

            services.forEach(service => {
                const col = document.createElement('div');
                const isChecked = selected.has(String(service.id));
                col.innerHTML = `
                    <label class="service-card w-100">
                        <input
                            type="checkbox"
                            class="form-check-input service-checkbox"
                            name="services[]"
                            value="${service.id}"
                            data-price="${service.price || 0}"
                            data-duration="${service.duration || 0}"
                            ${isChecked ? 'checked' : ''}
                        />
                        <span class="service-card-body d-block">
                            <span class="d-flex align-items-start justify-content-between gap-3">
                                <span>
                                    <span class="fw-semibold d-block">${service.name}</span>
                                    <small class="service-duration">~ ${service.duration || 0} мин</small>
                                </span>
                                <span class="service-meta-pill">${Number(service.price || 0).toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 })} ₽</span>
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

            updateSummary();
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
                wrapper.className = 'recommendation-card';

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

        function formatDateState(value) {
            if (!value) {
                return 'Не выбрана';
            }

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return value;
            }

            return new Intl.DateTimeFormat('ru-RU', {
                day: '2-digit',
                month: 'long',
                hour: '2-digit',
                minute: '2-digit',
            }).format(date);
        }

        function updateProgressState() {
            const hasExistingClient = Boolean(clientIdInput?.value);
            const manualName = clientNameInput?.value?.trim() || '';
            const manualPhone = clientPhoneInput?.value?.trim() || '';
            const clientLabel = hasExistingClient
                ? (clientNameInput?.value?.trim() || clientSearchInput?.value?.trim() || 'Клиентка выбрана')
                : (manualName || manualPhone || 'Не выбрана');
            const selectedCount = document.querySelectorAll('.service-checkbox:checked').length;

            const dateLabel = formatDateState(scheduledAtInput?.value || '');

            if (summarySelectionNote) {
                if (clientLabel === 'Не выбрана' || !scheduledAtInput?.value || selectedCount === 0) {
                    summarySelectionNote.textContent = 'Выберите клиентку, дату и хотя бы одну услугу, чтобы запись была полностью готова.';
                } else {
                    summarySelectionNote.textContent = `${clientLabel} • ${dateLabel} • ${selectedCount} усл.`;
                }
            }

            if (customPriceWrap) {
                customPriceWrap.classList.toggle('d-none', selectedCount === 0);
            }
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

            updateProgressState();
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

            updateProgressState();
        }

        function applyClientDraft(client) {
            setClientSelection(null);

            if (clientPhoneInput) {
                clientPhoneInput.value = client.phone || '';
            }

            if (clientNameInput) {
                clientNameInput.value = client.name || '';
            }

            clearClientSuggestions();
            clearClientResults();
            updateProgressState();
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
                    if (item.id) {
                        setClientSelection(item);
                    } else {
                        applyClientDraft(item);
                    }

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
                if (manualClientCard) {
                    manualClientCard.classList.remove('d-none');
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
                        applyClientDraft(item);
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

            if (window.VeloriaDateTimePicker) {
                window.VeloriaDateTimePicker.setValue(scheduledAtInput, `${dateParam}T${timePart}`);
            } else {
                scheduledAtInput.value = `${dateParam}T${timePart}`;
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
                recommendationsContainer.innerHTML = '<p class="text-danger mb-0">Не удалось загрузить подсказки.</p>';
                showFormAlert('danger', 'Не удалось загрузить данные для формы.');
                return;
            }

            const data = await response.json();
            recentClients = Array.isArray(data.recent_clients) ? data.recent_clients : [];
            availableServices = Array.isArray(data.services) ? data.services : [];
            renderServices(availableServices);
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
                                const target = input.type === 'hidden' && input.hasAttribute('data-veloria-datetime-value')
                                    ? input.closest('.veloria-datetime-field')
                                    : input.parentNode;

                                const displayInput = input.type === 'hidden'
                                    ? target?.querySelector('[data-veloria-datetime-display]')
                                    : null;

                                displayInput?.classList.add('is-invalid');
                                target?.appendChild(feedback);
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
                updateProgressState();
            });
        }

        if (clientNameInput) {
            clientNameInput.addEventListener('input', updateProgressState);
        }

        if (toggleManualClientButton) {
            toggleManualClientButton.addEventListener('click', function () {
                manualClientCard?.classList.remove('d-none');
                clientPhoneInput?.focus();
            });
        }

        if (hideManualClientButton) {
            hideManualClientButton.addEventListener('click', function () {
                manualClientCard?.classList.add('d-none');
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
                updateProgressState();
            });

            clientSearchInput.addEventListener('focus', function () {
                if (!this.value.trim()) {
                    renderClientResults(recentClients, 'Недавние клиентки');
                }
            });
        }

        if (scheduledAtInput) {
            scheduledAtInput.addEventListener('input', updateProgressState);
            scheduledAtInput.addEventListener('change', updateProgressState);
        }

        if (serviceSearchInput) {
            serviceSearchInput.addEventListener('input', function () {
                const query = this.value.trim().toLowerCase();
                const filteredServices = !query
                    ? availableServices
                    : availableServices.filter(service => (service.name || '').toLowerCase().includes(query));

                renderServices(filteredServices);
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
        updateProgressState();
        loadOptions();
    </script>
@endsection
