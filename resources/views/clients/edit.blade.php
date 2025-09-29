@extends('layouts.app')

@section('title', 'Редактирование клиента')

@section('content')
    <div id="client-edit" data-client-id="{{ $clientId ?? '' }}">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="mb-1" id="client-edit-title">Редактирование клиента</h4>
                <p class="text-muted mb-0" id="client-edit-subtitle">Загрузка данных...</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
                    <i class="ri ri-arrow-go-back-line me-1"></i>
                    К списку клиентов
                </a>
                <a href="#" class="btn btn-primary" id="client-view-link" hidden>
                    <i class="ri ri-user-line me-1"></i>
                    Открыть карточку
                </a>
            </div>
        </div>

        <div id="client-edit-alerts"></div>

        <form id="client-edit-form" class="card p-4" onsubmit="return false;" hidden>
            <div class="row g-4">
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
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input type="email" class="form-control" id="client-email" name="email" placeholder="email@example.com" />
                        <label for="client-email">Email</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <input type="date" class="form-control" id="client-birthday" name="birthday" />
                        <label for="client-birthday">День рождения</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <input type="datetime-local" class="form-control" id="client-last-visit" name="last_visit_at" />
                        <label for="client-last-visit">Последний визит</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <select class="form-select" id="client-loyalty" name="loyalty_level"></select>
                        <label for="client-loyalty">Уровень лояльности</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="client-tags" class="form-label">Теги</label>
                    <textarea class="form-control" id="client-tags" name="tags" rows="3"></textarea>
                    <div class="form-text">Разделяйте теги запятыми или переносом строки.</div>
                    <div class="small mt-2" id="tag-suggestions"></div>
                </div>
                <div class="col-md-4">
                    <label for="client-allergies" class="form-label">Аллергии</label>
                    <textarea class="form-control" id="client-allergies" name="allergies" rows="3"></textarea>
                    <div class="form-text">Укажите важные ограничения, чтобы избежать рисков.</div>
                    <div class="small mt-2" id="allergy-suggestions"></div>
                </div>
                <div class="col-12">
                    <label for="client-preferences" class="form-label">Предпочтения</label>
                    <textarea class="form-control" id="client-preferences" name="preferences" rows="4"></textarea>
                    <div class="form-text">Каждую пару «ключ: значение» указывайте с новой строки.</div>
                    <div class="small mt-2" id="preference-suggestions"></div>
                </div>
                <div class="col-12">
                    <label for="client-notes" class="form-label">Заметки</label>
                    <textarea class="form-control" id="client-notes" name="notes" rows="4"></textarea>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">Отмена</a>
                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    @include('components.phone-mask-script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('client-edit');
            const clientId = Number(container?.getAttribute('data-client-id'));

            if (!clientId) {
                return;
            }

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

            const form = document.getElementById('client-edit-form');
            const alertsContainer = document.getElementById('client-edit-alerts');
            const loyaltySelect = document.getElementById('client-loyalty');
            const tagSuggestions = document.getElementById('tag-suggestions');
            const allergySuggestions = document.getElementById('allergy-suggestions');
            const preferenceSuggestions = document.getElementById('preference-suggestions');
            const viewLink = document.getElementById('client-view-link');
            const title = document.getElementById('client-edit-title');
            const subtitle = document.getElementById('client-edit-subtitle');

            const tagsInput = document.getElementById('client-tags');
            const allergiesInput = document.getElementById('client-allergies');
            const preferencesInput = document.getElementById('client-preferences');

            function showAlert(type, message) {
                const alert = document.createElement('div');
                alert.className = 'alert alert-' + type + ' alert-dismissible fade show';
                alert.setAttribute('role', 'alert');
                alert.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                alertsContainer.appendChild(alert);
            }

            function clearAlerts() {
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
                    badge.className = 'badge bg-label-primary border-0';
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

            function formatListForInput(items) {
                if (!Array.isArray(items)) {
                    return '';
                }
                return items.join(', ');
            }

            function formatPreferencesForInput(preferences) {
                if (!preferences) {
                    return '';
                }
                if (Array.isArray(preferences)) {
                    return preferences.join('\n');
                }
                if (typeof preferences === 'object') {
                    return Object.entries(preferences)
                        .map(([key, value]) => `${key}: ${value ?? ''}`)
                        .join('\n');
                }
                return '';
            }

            async function loadOptions() {
                try {
                    const response = await fetch('/api/v1/clients/options', {
                        headers: authHeaders(),
                        credentials: 'include',
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        showAlert('danger', result.error?.message || 'Не удалось загрузить подсказки.');
                        renderSelectOptions(loyaltySelect, { '': 'Не задан' });
                        return result;
                    }

                    renderSelectOptions(loyaltySelect, Object.assign({ '': 'Не задан' }, result.loyalty_levels || {}));
                    renderSuggestionBadges(tagSuggestions, result.tag_suggestions || [], value => appendToListInput(tagsInput, value));
                    renderSuggestionBadges(allergySuggestions, result.allergy_suggestions || [], value => appendToListInput(allergiesInput, value));
                    renderSuggestionBadges(preferenceSuggestions, result.preference_suggestions || [], value => appendPreference(preferencesInput, value));

                    return result;
                } catch (error) {
                    console.error(error);
                    showAlert('danger', 'Не удалось загрузить данные для формы.');
                    renderSelectOptions(loyaltySelect, { '': 'Не задан' });
                    return {};
                }
            }

            async function loadClient() {
                try {
                    const response = await fetch('/api/v1/clients/' + clientId, {
                        headers: authHeaders(),
                        credentials: 'include',
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        showAlert('danger', result.error?.message || 'Не удалось загрузить клиента.');
                        subtitle.textContent = 'Ошибка загрузки данных.';
                        return null;
                    }

                    return result;
                } catch (error) {
                    console.error(error);
                    showAlert('danger', 'Произошла ошибка при загрузке клиента.');
                    subtitle.textContent = 'Ошибка загрузки данных.';
                    return null;
                }
            }

            function fillForm(client) {
                form.name.value = client.name || '';
                form.phone.value = client.phone || '';
                form.email.value = client.email || '';
                form.birthday.value = client.birthday || '';
                form.last_visit_at.value = client.last_visit_at_local || '';
                form.loyalty_level.value = client.loyalty_level || '';
                form.tags.value = formatListForInput(client.tags || []);
                form.allergies.value = formatListForInput(client.allergies || []);
                form.preferences.value = formatPreferencesForInput(client.preferences);
                form.notes.value = client.notes || '';
            }

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                clearAlerts();
                clearFieldErrors();

                const payload = {
                    name: form.name.value.trim(),
                    phone: form.phone.value.trim(),
                    email: form.email.value.trim() || null,
                    birthday: form.birthday.value || null,
                    last_visit_at: form.last_visit_at.value || null,
                    loyalty_level: form.loyalty_level.value || null,
                    notes: form.notes.value.trim() || null,
                    tags: parseList(form.tags.value),
                    allergies: parseList(form.allergies.value),
                    preferences: parsePreferences(form.preferences.value),
                };

                if (!payload.tags.length) payload.tags = null;
                if (!payload.allergies.length) payload.allergies = null;
                if (payload.preferences && !Object.keys(payload.preferences).length) payload.preferences = null;

                try {
                    const response = await fetch('/api/v1/clients/' + clientId, {
                        method: 'PATCH',
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
                        showAlert('danger', result.error?.message || 'Не удалось обновить клиента.');
                        return;
                    }

                    showAlert('success', result.message || 'Данные клиента обновлены.');
                } catch (error) {
                    console.error(error);
                    showAlert('danger', 'Произошла ошибка при сохранении клиента.');
                }
            });

            (async function initialize() {
                subtitle.textContent = 'Загрузка данных...';
                const [options, clientResponse] = await Promise.all([loadOptions(), loadClient()]);

                if (!clientResponse || !clientResponse.data) {
                    return;
                }

                const client = clientResponse.data;
                const loyaltyLevels = options?.loyalty_levels || {};

                if (loyaltyLevels && !Object.prototype.hasOwnProperty.call(loyaltyLevels, client.loyalty_level || '')) {
                    renderSelectOptions(loyaltySelect, Object.assign({ '': 'Не задан' }, loyaltyLevels));
                }

                title.textContent = client.name ? `Редактирование: ${client.name}` : 'Редактирование клиента';
                subtitle.textContent = client.created_at_formatted ? `Клиент создан ${client.created_at_formatted}` : 'Карточка клиента';
                viewLink.href = '/clients/' + client.id;
                viewLink.hidden = false;

                fillForm(client);
                form.hidden = false;
            })();
        });
    </script>
@endsection
