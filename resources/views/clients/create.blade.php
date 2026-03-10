@extends('layouts.app')

@section('title', 'Новый клиент')

@section('content')
    <style>
        .client-create-page {
            --client-create-accent-soft: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.1);
            --client-create-border: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.12);
            --client-create-shadow: 0 24px 54px -36px rgba(37, 26, 84, 0.42);
        }

        .client-create-page .create-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--client-create-border);
            border-radius: 1.6rem;
            padding: 1.6rem;
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.14), transparent 34%),
                linear-gradient(140deg, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.06), rgba(var(--bs-info-rgb, 0, 207, 232), 0.05) 58%, rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.12));
            box-shadow: var(--client-create-shadow);
        }

        .client-create-page .create-hero::after {
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

        .client-create-page .create-hero > * {
            position: relative;
            z-index: 1;
        }

        .client-create-page .create-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.72);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .client-create-page .create-eyebrow i {
            color: var(--bs-primary);
        }

        .client-create-page .hero-status {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.55rem 0.85rem;
            border-radius: 999px;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.68);
            font-weight: 600;
        }

        .client-create-page .create-shell,
        .client-create-page .summary-card {
            border: none;
            border-radius: 1.35rem;
            box-shadow: var(--client-create-shadow);
            background: color-mix(in srgb, var(--bs-card-bg) 96%, transparent);
        }

        .client-create-page .step-card {
            padding: 1.4rem;
            border-radius: 1.2rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.02);
        }

        .client-create-page .step-card + .step-card {
            margin-top: 1rem;
        }

        .client-create-page .step-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            background: var(--client-create-accent-soft);
            color: var(--bs-primary);
            font-weight: 700;
        }

        .client-create-page .section-intro {
            padding: 0.9rem 1rem;
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.03);
        }

        .client-create-page .suggestion-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .client-create-page .suggestion-group .badge {
            border-radius: 999px;
            padding: 0.38rem 0.7rem;
        }

        .client-create-page .chip-input-shell {
            display: grid;
            gap: 0.55rem;
        }

        .client-create-page .chip-textarea {
            display: none;
        }

        .client-create-page .tagify {
            --tags-border-color: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.12);
            --tags-hover-border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.28);
            --tags-focus-border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.44);
            --tag-bg: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.1);
            --tag-hover: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.16);
            --tag-text-color: var(--bs-emphasis-color);
            min-height: calc(3.25rem + 2px);
            padding: 0.5rem 0.75rem;
            border-radius: 1rem;
            background: var(--bs-body-bg);
        }

        .client-create-page .tagify__input {
            margin: 0.25rem 0;
        }

        .client-create-page .suggestion-chip {
            border: 0;
            border-radius: 999px;
            padding: 0.42rem 0.8rem;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.1);
            color: var(--bs-emphasis-color);
            font-weight: 600;
        }

        .client-create-page .preferences-card {
            display: grid;
            gap: 0.85rem;
            margin-top: 0.35rem;
            padding: 1rem;
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.03);
        }

        .client-create-page .preferences-hint {
            color: var(--bs-secondary-color);
            font-size: 0.92rem;
        }

        .client-create-page .preferences-list {
            display: grid;
            gap: 0.75rem;
        }

        .client-create-page .preference-row {
            display: grid;
            grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.35fr) auto;
            gap: 0.75rem;
            align-items: start;
        }

        .client-create-page .preference-row .btn {
            height: calc(3.25rem + 2px);
            border-radius: 1rem;
        }

        .client-create-page .preference-empty {
            padding: 0.95rem 1rem;
            border: 1px dashed rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.14);
            border-radius: 1rem;
            color: var(--bs-secondary-color);
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.02);
        }

        html[data-bs-theme="dark"] .client-create-page .preferences-card,
        html[data-bs-theme="dark"] .client-create-page .preference-empty {
            background: rgba(255, 255, 255, 0.04);
        }

        .client-create-page .summary-card {
            position: sticky;
            top: 1.5rem;
            padding: 1.35rem;
        }

        .client-create-page .summary-kpis {
            display: grid;
            gap: 0.75rem;
        }

        .client-create-page .summary-kpi {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.85rem 0.95rem;
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.03);
        }

        .client-create-page .summary-hint {
            padding: 0.95rem 1rem;
            border-radius: 1rem;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.06);
        }

        .client-create-page .step-card details {
            display: grid;
            gap: 1rem;
        }

        .client-create-page .step-card summary {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            list-style: none;
            font-weight: 700;
        }

        .client-create-page .step-card summary::-webkit-details-marker {
            display: none;
        }

        .client-create-page .step-card summary::before {
            content: '';
            width: 0.65rem;
            height: 0.65rem;
            border-right: 2px solid currentColor;
            border-bottom: 2px solid currentColor;
            transform: rotate(-45deg);
            transition: transform 0.2s ease;
            opacity: 0.7;
            margin-left: 0.2rem;
        }

        .client-create-page .step-card details[open] summary::before {
            transform: rotate(45deg);
        }

        .client-create-page .profile-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) repeat(2, minmax(0, 1fr));
            gap: 1rem;
            align-items: start;
        }

        .client-create-page .profile-grid .profile-email-block {
            display: grid;
            gap: 0.45rem;
        }

        .client-create-page .profile-grid .form-text {
            margin-top: 0;
            padding-left: 0.1rem;
        }

        @media (max-width: 1199.98px) {
            .client-create-page .summary-card {
                position: static;
            }

            .client-create-page .profile-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .client-create-page .profile-grid .profile-email-block {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 767.98px) {
            .client-create-page .profile-grid {
                grid-template-columns: 1fr;
            }

            .client-create-page .preference-row {
                grid-template-columns: 1fr;
            }

            .client-create-page .preference-row .btn {
                height: auto;
            }
        }
    </style>

    <div class="client-create-page">
        <div class="d-flex flex-column gap-4">
            <section class="create-hero">
                <div class="d-flex flex-column flex-xl-row justify-content-between gap-4 align-items-xl-start">
                    <div class="d-flex flex-column gap-3">
                        <span class="create-eyebrow">
                            <i class="ri ri-user-heart-line"></i>
                            Лёгкое добавление клиента
                        </span>
                        <div>
                            <h1 class="mb-2">Добавление клиента</h1>
                            <p class="text-muted mb-0 fs-6">Начните с базовых данных. Всё, что не нужно для первого контакта, убрано в дополнительные блоки ниже.</p>
                        </div>
                        <div class="hero-status" id="hero-client-status">Сначала укажите имя и телефон</div>
                    </div>
                    <div class="d-flex flex-column flex-sm-row gap-2 align-self-start">
                        <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
                            <i class="ri ri-arrow-go-back-line me-1"></i>
                            К списку клиентов
                        </a>
                    </div>
                </div>
            </section>

            <div id="client-form-alerts"></div>

            <form id="client-form" class="create-shell p-4" onsubmit="return false;">
                <div class="row g-4">
                    <div class="col-xl-8">
                        <div class="step-card">
                            <div class="d-flex gap-3 mb-4">
                                <span class="step-badge">1</span>
                                <div>
                                    <h2 class="h5 mb-1">Основные данные</h2>
                                    <p class="text-muted mb-0">Только то, что действительно нужно, чтобы создать клиента и не потерять контакт.</p>
                                </div>
                            </div>

                            <div class="section-intro small text-muted mb-3">Имя и телефон обязательны. Остальные поля можно заполнить позже, когда клиент уже появится в базе.</div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="client-name" name="name" placeholder="Имя" required />
                                        <label for="client-name">Имя клиента</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="client-phone" name="phone" placeholder="+7(999)999-99-99" data-phone-mask required />
                                        <label for="client-phone">Телефон</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="step-card">
                            <details>
                                <summary class="fw-semibold" style="cursor: pointer;">2. Профиль клиента</summary>
                                <div class="profile-grid pt-2">
                                    <div class="profile-email-block">
                                        <div class="form-floating form-floating-outline">
                                            <input type="email" class="form-control" id="client-email" name="email" placeholder="email@example.com" />
                                            <label for="client-email">Email</label>
                                        </div>
                                        <div class="form-text">Если email не нужен сейчас, оставьте поле пустым.</div>
                                    </div>
                                    <div>
                                        <div class="form-floating form-floating-outline">
                                            <input type="date" class="form-control" id="client-birthday" name="birthday" />
                                            <label for="client-birthday">День рождения</label>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="form-floating form-floating-outline">
                                            <input type="datetime-local" class="form-control" id="client-last-visit" name="last_visit_at" />
                                            <label for="client-last-visit">Последний визит</label>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="form-floating form-floating-outline">
                                            <select class="form-select" id="client-loyalty" name="loyalty_level"></select>
                                            <label for="client-loyalty">Статус клиента</label>
                                        </div>
                                        <div class="form-text">Если не уверены, оставьте «Не задан». Этот статус нужен только для вашего удобства.</div>
                                    </div>
                                </div>
                            </details>
                        </div>

                        <div class="step-card">
                            <details>
                                <summary class="fw-semibold" style="cursor: pointer;">3. Персонализация</summary>
                                <div class="row g-3 pt-3">
                                    <div class="col-md-6">
                                        <label for="client-tags" class="form-label">Теги</label>
                                        <textarea class="form-control" id="client-tags" name="tags" rows="3" placeholder="VIP, постоянный, парикмахер"></textarea>
                                        <div class="form-text">Разделяйте теги запятыми или переносом строки.</div>
                                        <div class="small mt-2 suggestion-group" id="tag-suggestions"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="client-allergies" class="form-label">Аллергии</label>
                                        <textarea class="form-control" id="client-allergies" name="allergies" rows="3" placeholder="Пыльца, цитрусовые"></textarea>
                                        <div class="form-text">Укажите важные ограничения, чтобы избежать рисков.</div>
                                        <div class="small mt-2 suggestion-group" id="allergy-suggestions"></div>
                                    </div>
                                    <div class="col-12">
                                        <label for="client-preferences" class="form-label">Предпочтения</label>
                                        <textarea class="form-control" id="client-preferences" name="preferences" rows="4" placeholder="Чай: зелёный
Музыка: джаз"></textarea>
                                        <div class="form-text">Каждую пару «ключ: значение» указывайте с новой строки.</div>
                                        <div class="small mt-2 suggestion-group" id="preference-suggestions"></div>
                                    </div>
                                    <div class="col-12">
                                        <label for="client-notes" class="form-label">Заметки</label>
                                        <textarea class="form-control" id="client-notes" name="notes" rows="4" placeholder="Любит утренние визиты, предпочитает натуральные оттенки."></textarea>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="summary-card d-flex flex-column gap-4">
                            <div>
                                <h2 class="h5 mb-2">Итог</h2>
                                <p class="text-muted small mb-0">Карточка создаётся даже с минимальным набором данных. Остальное можно дополнять позже без давления на пользователя.</p>
                            </div>

                            <div class="summary-kpis">
                                <div class="summary-kpi">
                                    <span>Имя</span>
                                    <strong id="summary-name">Не указано</strong>
                                </div>
                                <div class="summary-kpi">
                                    <span>Телефон</span>
                                    <strong id="summary-phone">Не указан</strong>
                                </div>
                                <div class="summary-kpi">
                                    <span>Email</span>
                                    <strong id="summary-email">Не указан</strong>
                                </div>
                            </div>

                            <div class="summary-hint small" id="summary-hint">
                                Для создания клиента достаточно имени и телефона.
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">Отмена</a>
                                <button type="submit" class="btn btn-primary">Создать клиента</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    @include('components.phone-mask-script')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/tagify/tagify.css') }}" />
    <script src="{{ asset('assets/vendor/libs/tagify/tagify.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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

            const form = document.getElementById('client-form');
            const alertsContainer = document.getElementById('client-form-alerts');
            const loyaltySelect = document.getElementById('client-loyalty');
            const tagSuggestions = document.getElementById('tag-suggestions');
            const allergySuggestions = document.getElementById('allergy-suggestions');
            const preferenceSuggestions = document.getElementById('preference-suggestions');
            const heroClientStatus = document.getElementById('hero-client-status');
            const summaryName = document.getElementById('summary-name');
            const summaryPhone = document.getElementById('summary-phone');
            const summaryEmail = document.getElementById('summary-email');
            const summaryHint = document.getElementById('summary-hint');

            const tagsInput = document.getElementById('client-tags');
            const allergiesInput = document.getElementById('client-allergies');
            const preferencesInput = document.getElementById('client-preferences');
            let tagsTagify = null;
            let allergiesTagify = null;
            const nameInput = document.getElementById('client-name');
            const phoneInput = document.getElementById('client-phone');
            const emailInput = document.getElementById('client-email');
            tagsTagify = createTagifyField(tagsInput, 'Например: любит утренние записи');
            allergiesTagify = createTagifyField(allergiesInput, 'Например: латекс');
            const preferencesEditor = setupPreferencesEditor(preferencesInput);

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

            function clearFieldErrors() {
                form.querySelectorAll('.is-invalid').forEach(element => element.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(element => element.remove());
            }

            function attachFieldErrors(fields) {
                Object.keys(fields).forEach(function (key) {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (!input) return;
                    input.classList.add('is-invalid');
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = Array.isArray(fields[key]) ? fields[key][0] : fields[key];
                    if (input.parentElement && input.parentElement.classList.contains('form-floating')) {
                        input.parentElement.appendChild(feedback);
                    } else {
                        input.insertAdjacentElement('afterend', feedback);
                    }
                });
            }

            function renderSelectOptions(select, options) {
                select.innerHTML = '';
                Object.keys(options || {}).forEach(function (key) {
                    const option = document.createElement('option');
                    option.value = key;
                    option.textContent = options[key];
                    select.appendChild(option);
                });
            }

            function renderSuggestionBadges(container, items, handler) {
                container.innerHTML = '';
                if (!Array.isArray(items) || !items.length) {
                    container.innerHTML = '<span class="text-muted">Подсказок пока нет.</span>';
                    return;
                }

                container.classList.add('d-flex', 'flex-wrap', 'gap-2');
                items.forEach(function (item) {
                    if (typeof item !== 'string' || item.trim() === '') {
                        return;
                    }
                    const badge = document.createElement('button');
                    badge.type = 'button';
                    badge.className = 'suggestion-chip';
                    badge.textContent = item;
                    badge.addEventListener('click', () => handler(item));
                    container.appendChild(badge);
                });
            }

            function parseList(value) {
                if (!value) {
                    return [];
                }
                return value
                    .split(/[,\n]+/)
                    .map(item => item.trim())
                    .filter(Boolean);
            }

            function parsePreferences(value) {
                if (!value) {
                    return {};
                }
                const lines = value.split(/\n+/).map(line => line.trim()).filter(Boolean);
                if (!lines.length) {
                    return {};
                }
                const result = {};
                lines.forEach(line => {
                    const parts = line.split(':');
                    if (parts.length >= 2) {
                        const key = parts.shift().trim();
                        const val = parts.join(':').trim();
                        if (key) {
                            result[key] = val || '';
                        }
                    } else {
                        result[line] = '';
                    }
                });
                return result;
            }

            function appendToListInput(input, value) {
                const list = parseList(input.value);
                if (!list.includes(value)) {
                    list.push(value);
                }
                input.value = list.join(', ');
            }

            function appendPreference(input, key) {
                const current = input.value.trim();
                const line = key.includes(':') ? key : key + ': ';
                input.value = current ? current + '\n' + line : line;
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            }

            function createTagifyField(textarea, placeholder) {
                if (!textarea || typeof Tagify === 'undefined') {
                    return null;
                }

                textarea.classList.add('chip-textarea');

                const shell = document.createElement('div');
                shell.className = 'chip-input-shell';
                textarea.insertAdjacentElement('afterend', shell);
                shell.appendChild(textarea);

                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                input.placeholder = placeholder;
                shell.appendChild(input);

                const tagify = new Tagify(input, {
                    duplicates: false,
                    editTags: 1,
                    trim: true,
                    originalInputValueFormat: values => values.map(item => item.value).join(', '),
                    dropdown: {
                        enabled: 0,
                        maxItems: 12,
                        closeOnSelect: false,
                    },
                });

                const initialValues = parseList(textarea.value);
                if (initialValues.length) {
                    tagify.addTags(initialValues);
                }

                return tagify;
            }

            function setTagifySuggestions(tagify, items) {
                if (!tagify) return;
                tagify.settings.whitelist = Array.isArray(items) ? items : [];
            }

            function addTagValue(tagify, value) {
                if (!tagify || !value) return;
                const existing = tagify.value.map(item => item.value);
                if (!existing.includes(value)) {
                    tagify.addTags([value]);
                }
            }

            function readTagifyValues(tagify, fallbackTextarea) {
                if (!tagify) {
                    return parseList(fallbackTextarea?.value || '');
                }

                return tagify.value.map(item => item.value).filter(Boolean);
            }

            function setupPreferencesEditor(textarea) {
                if (!textarea) {
                    return null;
                }

                textarea.classList.add('chip-textarea');

                const card = document.createElement('div');
                card.className = 'preferences-card';
                card.innerHTML = `
                    <div class="preferences-hint">Лучше записать 2-4 детали, которые помогут быстро вспомнить человека перед визитом.</div>
                    <div class="preferences-list"></div>
                    <div class="preference-empty">Пока ничего не добавлено. Это можно заполнить позже.</div>
                    <button type="button" class="btn btn-outline-primary align-self-start">
                        <i class="ri ri-add-line me-1"></i>
                        Добавить предпочтение
                    </button>
                `;
                textarea.insertAdjacentElement('afterend', card);
                const legacyHint = card.nextElementSibling;
                if (legacyHint && legacyHint.classList.contains('form-text')) {
                    legacyHint.hidden = true;
                }

                const list = card.querySelector('.preferences-list');
                const empty = card.querySelector('.preference-empty');
                const addButton = card.querySelector('button');

                function sync() {
                    const result = {};
                    list.querySelectorAll('[data-preference-row]').forEach(function (row) {
                        const key = row.querySelector('[data-preference-key]')?.value?.trim() || '';
                        const value = row.querySelector('[data-preference-value]')?.value?.trim() || '';
                        if (key) {
                            result[key] = value;
                        }
                    });

                    textarea.value = Object.entries(result)
                        .map(([key, value]) => `${key}: ${value}`)
                        .join('\n');

                    empty.hidden = list.children.length > 0;
                }

                function addRow(key = '', value = '') {
                    const row = document.createElement('div');
                    row.className = 'preference-row';
                    row.setAttribute('data-preference-row', 'true');
                    row.innerHTML = `
                        <div class="form-floating form-floating-outline">
                            <input type="text" class="form-control" data-preference-key placeholder="Например: Напиток" value="${escapeHtml(key)}" />
                            <label>Что важно</label>
                        </div>
                        <div class="form-floating form-floating-outline">
                            <input type="text" class="form-control" data-preference-value placeholder="Например: Кофе без сахара" value="${escapeHtml(value)}" />
                            <label>Значение</label>
                        </div>
                        <button type="button" class="btn btn-outline-secondary" data-remove-preference>
                            <i class="ri ri-delete-bin-line"></i>
                        </button>
                    `;

                    row.querySelectorAll('input').forEach(function (input) {
                        input.addEventListener('input', sync);
                        input.addEventListener('change', sync);
                    });

                    row.querySelector('[data-remove-preference]').addEventListener('click', function () {
                        row.remove();
                        sync();
                    });

                    list.appendChild(row);
                    sync();
                    return row;
                }

                function populate(preferences) {
                    list.innerHTML = '';
                    const entries = preferences && typeof preferences === 'object'
                        ? Object.entries(preferences).filter(([key]) => key && String(key).trim() !== '')
                        : [];

                    if (!entries.length) {
                        sync();
                        return;
                    }

                    entries.forEach(function ([key, value]) {
                        addRow(key, value || '');
                    });
                }

                function appendSuggested(key) {
                    const normalizedKey = (key || '').replace(/:$/, '').trim();
                    if (!normalizedKey) return;

                    const existing = Array.from(list.querySelectorAll('[data-preference-key]')).find(function (input) {
                        return input.value.trim().toLowerCase() === normalizedKey.toLowerCase();
                    });

                    if (existing) {
                        existing.closest('[data-preference-row]')?.querySelector('[data-preference-value]')?.focus();
                        return;
                    }

                    const row = addRow(normalizedKey, '');
                    row.querySelector('[data-preference-value]')?.focus();
                }

                addButton.addEventListener('click', function () {
                    const row = addRow('', '');
                    row.querySelector('[data-preference-key]')?.focus();
                });

                populate(parsePreferences(textarea.value));

                return {
                    sync,
                    populate,
                    appendSuggested,
                };
            }

            function updateClientSummary() {
                const name = nameInput?.value?.trim() || '';
                const phone = phoneInput?.value?.trim() || '';
                const email = emailInput?.value?.trim() || '';

                if (summaryName) {
                    summaryName.textContent = name || 'Не указано';
                }

                if (summaryPhone) {
                    summaryPhone.textContent = phone || 'Не указан';
                }

                if (summaryEmail) {
                    summaryEmail.textContent = email || 'Не указан';
                }

                if (heroClientStatus) {
                    if (name && phone) {
                        heroClientStatus.textContent = `${name} • можно создавать карточку`;
                    } else if (name || phone) {
                        heroClientStatus.textContent = 'Заполните ещё одно обязательное поле';
                    } else {
                        heroClientStatus.textContent = 'Сначала укажите имя и телефон';
                    }
                }

                if (summaryHint) {
                    if (name && phone) {
                        summaryHint.textContent = 'База готова. Можно создавать клиента и заполнять остальное позже.';
                    } else {
                        summaryHint.textContent = 'Для создания клиента достаточно имени и телефона.';
                    }
                }
            }

            async function loadOptions() {
                try {
                    const response = await fetch('/api/v1/clients/options', {
                        headers: authHeaders(),
                        credentials: 'include',
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        showFormAlert('danger', result.error?.message || 'Не удалось загрузить подсказки.');
                        renderSelectOptions(loyaltySelect, { '': 'Не задан' });
                        return;
                    }

                    renderSelectOptions(loyaltySelect, Object.assign({ '': 'Не задан' }, result.loyalty_levels || {}));
                    setTagifySuggestions(tagsTagify, result.tag_suggestions || []);
                    renderSuggestionBadges(tagSuggestions, result.tag_suggestions || [], value => addTagValue(tagsTagify, value));
                    setTagifySuggestions(allergiesTagify, result.allergy_suggestions || []);
                    renderSuggestionBadges(allergySuggestions, result.allergy_suggestions || [], value => addTagValue(allergiesTagify, value));
                    renderSuggestionBadges(preferenceSuggestions, result.preference_suggestions || [], value => preferencesEditor?.appendSuggested(value));
                } catch (error) {
                    console.error(error);
                    showFormAlert('danger', 'Не удалось загрузить данные для формы.');
                    renderSelectOptions(loyaltySelect, { '': 'Не задан' });
                }
            }

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                clearFormAlerts();
                clearFieldErrors();
                preferencesEditor?.sync();

                const payload = {
                    name: form.name.value.trim(),
                    phone: form.phone.value.trim(),
                    email: form.email.value.trim() || null,
                    birthday: form.birthday.value || null,
                    last_visit_at: form.last_visit_at.value || null,
                    loyalty_level: form.loyalty_level.value || null,
                    notes: form.notes.value.trim() || null,
                    tags: readTagifyValues(tagsTagify, form.tags),
                    allergies: readTagifyValues(allergiesTagify, form.allergies),
                    preferences: parsePreferences(form.preferences.value),
                };

                if (!payload.tags.length) payload.tags = null;
                if (!payload.allergies.length) payload.allergies = null;
                if (payload.preferences && !Object.keys(payload.preferences).length) payload.preferences = null;

                try {
                    const response = await fetch('/api/v1/clients', {
                        method: 'POST',
                        headers: authHeaders(),
                        credentials: 'include',
                        body: JSON.stringify(payload),
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const fields = result.error?.fields || {};
                        if (Object.keys(fields).length) {
                            attachFieldErrors(fields);
                        }
                        showFormAlert('danger', result.error?.message || 'Не удалось создать клиента.');
                        return;
                    }

                    showFormAlert('success', result.message || 'Клиент успешно создан. Перенаправляем...');
                    setTimeout(() => {
                        if (result.data?.id) {
                            window.location.href = '/clients/' + result.data.id;
                        } else {
                            window.location.href = '{{ route('clients.index') }}';
                        }
                    }, 1200);
                } catch (error) {
                    console.error(error);
                    showFormAlert('danger', 'Произошла ошибка при сохранении клиента.');
                }
            });

            [nameInput, phoneInput, emailInput].forEach(function (input) {
                if (!input) return;
                input.addEventListener('input', updateClientSummary);
                input.addEventListener('change', updateClientSummary);
            });

            updateClientSummary();
            loadOptions();
        });
    </script>
@endsection
