@extends('layouts.app')

@section('title', 'Новый клиент')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Добавление клиента</h4>
            <p class="text-muted mb-0">Заполните карточку, чтобы персонализировать коммуникации и напоминания.</p>
        </div>
        <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
            <i class="ri ri-arrow-go-back-line me-1"></i>
            К списку клиентов
        </a>
    </div>

    <div id="client-form-alerts"></div>

    <form id="client-form" class="card p-4" onsubmit="return false;">
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
                <textarea class="form-control" id="client-tags" name="tags" rows="3" placeholder="VIP, постоянный, парикмахер"></textarea>
                <div class="form-text">Разделяйте теги запятыми или переносом строки.</div>
                <div class="small mt-2" id="tag-suggestions"></div>
            </div>
            <div class="col-md-4">
                <label for="client-allergies" class="form-label">Аллергии</label>
                <textarea class="form-control" id="client-allergies" name="allergies" rows="3" placeholder="Пыльца, цитрусовые"></textarea>
                <div class="form-text">Укажите важные ограничения, чтобы избежать рисков.</div>
                <div class="small mt-2" id="allergy-suggestions"></div>
            </div>
            <div class="col-12">
                <label for="client-preferences" class="form-label">Предпочтения</label>
                <textarea class="form-control" id="client-preferences" name="preferences" rows="4" placeholder="Чай: зелёный\nМузыка: джаз"></textarea>
                <div class="form-text">Каждую пару «ключ: значение» указывайте с новой строки.</div>
                <div class="small mt-2" id="preference-suggestions"></div>
            </div>
            <div class="col-12">
                <label for="client-notes" class="form-label">Заметки</label>
                <textarea class="form-control" id="client-notes" name="notes" rows="4" placeholder="Любит утренние визиты, предпочитает натуральные оттенки."></textarea>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">Отмена</a>
            <button type="submit" class="btn btn-primary">Создать клиента</button>
        </div>
    </form>
@endsection

@section('scripts')
    @include('components.phone-mask-script')
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

            const tagsInput = document.getElementById('client-tags');
            const allergiesInput = document.getElementById('client-allergies');
            const preferencesInput = document.getElementById('client-preferences');

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
                    renderSuggestionBadges(tagSuggestions, result.tag_suggestions || [], value => appendToListInput(tagsInput, value));
                    renderSuggestionBadges(allergySuggestions, result.allergy_suggestions || [], value => appendToListInput(allergiesInput, value));
                    renderSuggestionBadges(preferenceSuggestions, result.preference_suggestions || [], value => appendPreference(preferencesInput, value));
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

            loadOptions();
        });
    </script>
@endsection
