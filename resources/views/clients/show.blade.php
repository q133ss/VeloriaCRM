@extends('layouts.app')

@section('title', 'Клиент')

@section('content')
    <div id="client-view" data-client-id="{{ $clientId ?? '' }}">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="mb-1" id="client-name">Клиент</h4>
                <p class="text-muted mb-0" id="client-subtitle">Загрузка информации...</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-info" id="client-reminder-btn" disabled>
                    <i class="ri ri-mail-line me-1"></i>
                    Автонапоминание
                </button>
                <a href="#" class="btn btn-outline-secondary" id="client-edit-link" hidden>
                    <i class="ri ri-edit-line me-1"></i>
                    Редактировать
                </a>
                <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
                    <i class="ri ri-arrow-go-back-line me-1"></i>
                    К списку
                </a>
            </div>
        </div>

        <div id="client-view-alerts"></div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Основная информация</h5>
                        <span class="badge bg-label-info" id="client-loyalty-badge">—</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase mb-2">Контакты</h6>
                                <p class="mb-1">
                                    <i class="ri ri-phone-line me-1"></i>
                                    <span id="client-phone">—</span>
                                </p>
                                <p class="mb-1">
                                    <i class="ri ri-mail-line me-1"></i>
                                    <span id="client-email">—</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase mb-2">Профиль</h6>
                                <p class="mb-1">День рождения: <span id="client-birthday">—</span></p>
                                <p class="mb-1">Последний визит: <span id="client-last-visit">—</span></p>
                                <p class="mb-0">Карточка обновлена: <span id="client-updated-at">—</span></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase mb-2">Теги</h6>
                                <div id="client-tags" class="d-flex flex-wrap gap-2"></div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase mb-2">Аллергии</h6>
                                <div id="client-allergies" class="d-flex flex-wrap gap-2"></div>
                            </div>
                            <div class="col-12">
                                <h6 class="text-muted text-uppercase mb-2">Предпочтения</h6>
                                <div id="client-preferences" class="small"></div>
                            </div>
                            <div class="col-12">
                                <h6 class="text-muted text-uppercase mb-2">Заметки</h6>
                                <p class="mb-0" id="client-notes">—</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Статистика по визитам</h5>
                        <span class="badge bg-label-secondary">CRM</span>
                    </div>
                    <div class="card-body" id="client-statistics">
                        <p class="text-muted mb-0">Загрузка статистики...</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Заметки для коммуникации</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Используйте карточку для персонализации сообщений и рекомендаций.</p>
                        <ul class="list-unstyled mb-0" id="client-highlights"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="clientReminderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reminder-title">Автонапоминание</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reminder-message" class="form-label">Текст напоминания</label>
                        <textarea class="form-control" id="reminder-message" rows="4"></textarea>
                        <div class="form-text">Текст загружается из настроек. При необходимости адаптируйте перед отправкой.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Канал связи</label>
                        <div id="reminder-channels" class="d-flex flex-column gap-2"></div>
                    </div>
                    <div id="reminder-errors" class="text-danger small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="reminder-send">Отправить</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('client-view');
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

            const alertsContainer = document.getElementById('client-view-alerts');
            const nameEl = document.getElementById('client-name');
            const subtitleEl = document.getElementById('client-subtitle');
            const phoneEl = document.getElementById('client-phone');
            const emailEl = document.getElementById('client-email');
            const birthdayEl = document.getElementById('client-birthday');
            const lastVisitEl = document.getElementById('client-last-visit');
            const updatedAtEl = document.getElementById('client-updated-at');
            const loyaltyBadge = document.getElementById('client-loyalty-badge');
            const tagsContainer = document.getElementById('client-tags');
            const allergiesContainer = document.getElementById('client-allergies');
            const preferencesContainer = document.getElementById('client-preferences');
            const notesEl = document.getElementById('client-notes');
            const highlightsList = document.getElementById('client-highlights');
            const statisticsContainer = document.getElementById('client-statistics');
            const editLink = document.getElementById('client-edit-link');
            const reminderButton = document.getElementById('client-reminder-btn');

            const reminderModalEl = document.getElementById('clientReminderModal');
            const reminderModal = new bootstrap.Modal(reminderModalEl);
            const reminderTitle = document.getElementById('reminder-title');
            const reminderMessageInput = document.getElementById('reminder-message');
            const reminderChannels = document.getElementById('reminder-channels');
            const reminderErrors = document.getElementById('reminder-errors');
            const reminderSendBtn = document.getElementById('reminder-send');

            let reminderMessageTemplate = '';
            let currentClient = null;

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

            function renderBadges(container, items, emptyText) {
                container.innerHTML = '';
                if (!Array.isArray(items) || !items.length) {
                    container.innerHTML = `<span class="text-muted">${emptyText}</span>`;
                    return;
                }
                items.forEach(function (item) {
                    if (typeof item !== 'string') {
                        return;
                    }
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-label-primary';
                    badge.textContent = item;
                    container.appendChild(badge);
                });
            }

            function renderPreferences(preferences) {
                if (!preferences) {
                    return '<p class="text-muted mb-0">Предпочтения не указаны.</p>';
                }
                if (Array.isArray(preferences)) {
                    if (!preferences.length) {
                        return '<p class="text-muted mb-0">Предпочтения не указаны.</p>';
                    }
                    const list = preferences
                        .filter(item => typeof item === 'string' && item.trim() !== '')
                        .map(item => `<li>${item}</li>`)
                        .join('');
                    if (!list) {
                        return '<p class="text-muted mb-0">Предпочтения не указаны.</p>';
                    }
                    return `<ul class="mb-0 ps-3">${list}</ul>`;
                }
                if (typeof preferences === 'object') {
                    const entries = Object.entries(preferences)
                        .filter(([key]) => key)
                        .map(([key, value]) => `<li><strong>${key}</strong>: ${value ?? ''}</li>`)
                        .join('');
                    if (!entries) {
                        return '<p class="text-muted mb-0">Предпочтения не указаны.</p>';
                    }
                    return `<ul class="mb-0 ps-3">${entries}</ul>`;
                }
                return '<p class="text-muted mb-0">Предпочтения не указаны.</p>';
            }

            function renderHighlights(client) {
                highlightsList.innerHTML = '';
                const highlights = [];
                if (client.loyalty_label) {
                    highlights.push(`Лояльность: ${client.loyalty_label}`);
                }
                if (Array.isArray(client.tags) && client.tags.length) {
                    highlights.push('Теги: ' + client.tags.join(', '));
                }
                if (client.birthday_formatted) {
                    highlights.push('День рождения: ' + client.birthday_formatted);
                }
                if (client.last_visit_at_formatted) {
                    highlights.push('Последний визит: ' + client.last_visit_at_formatted);
                }

                if (!highlights.length) {
                    highlightsList.innerHTML = '<li class="text-muted">Добавьте информацию, чтобы персонализировать коммуникации.</li>';
                    return;
                }

                highlights.forEach(function (item) {
                    const li = document.createElement('li');
                    li.textContent = item;
                    highlightsList.appendChild(li);
                });
            }

            function renderStatistics(stats) {
                if (!stats) {
                    statisticsContainer.innerHTML = '<p class="text-muted mb-0">Нет данных для отображения.</p>';
                    return;
                }
                const totalOrders = stats.total_orders ?? 0;
                const completed = stats.completed_orders ?? 0;
                const value = typeof stats.lifetime_value === 'number' ? stats.lifetime_value : 0;
                const upcoming = stats.upcoming_visit_formatted || '—';
                const lastFromOrders = stats.last_visit_from_orders_formatted || '—';

                statisticsContainer.innerHTML = `
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Всего визитов</dt>
                        <dd class="col-sm-6 text-sm-end">${totalOrders}</dd>
                        <dt class="col-sm-6">Завершено</dt>
                        <dd class="col-sm-6 text-sm-end">${completed}</dd>
                        <dt class="col-sm-6">Lifetime Value</dt>
                        <dd class="col-sm-6 text-sm-end">${value.toLocaleString('ru-RU', { style: 'currency', currency: 'RUB' })}</dd>
                        <dt class="col-sm-6">Ближайший визит</dt>
                        <dd class="col-sm-6 text-sm-end">${upcoming}</dd>
                        <dt class="col-sm-6">Последний визит</dt>
                        <dd class="col-sm-6 text-sm-end">${lastFromOrders}</dd>
                    </dl>
                `;
            }

            function renderReminderChannels(client) {
                reminderChannels.innerHTML = '';
                const channels = Array.isArray(client.available_channels) ? client.available_channels : [];
                if (!channels.length) {
                    reminderChannels.innerHTML = '<p class="text-muted mb-0">Добавьте телефон или email, чтобы выбрать канал связи.</p>';
                    reminderSendBtn.disabled = true;
                    return;
                }
                channels.forEach(function (channel, index) {
                    const id = `reminder-channel-${channel.key}-${client.id}`;
                    const wrapper = document.createElement('div');
                    wrapper.className = 'form-check';
                    wrapper.innerHTML = `
                        <input class="form-check-input" type="radio" name="reminder-channel" id="${id}" value="${channel.key}" ${index === 0 ? 'checked' : ''} />
                        <label class="form-check-label" for="${id}">${channel.label}</label>
                    `;
                    reminderChannels.appendChild(wrapper);
                });
                reminderSendBtn.disabled = false;
            }

            function fillClient(client, meta) {
                currentClient = client;
                reminderMessageTemplate = meta?.reminder_message || '';

                nameEl.textContent = client.name || 'Клиент';
                subtitleEl.textContent = client.created_at_formatted ? `Клиент создан ${client.created_at_formatted}` : 'Карточка клиента';
                phoneEl.textContent = client.phone || '—';
                emailEl.textContent = client.email || '—';
                birthdayEl.textContent = client.birthday_formatted || '—';
                lastVisitEl.textContent = client.last_visit_at_formatted || meta?.statistics?.last_visit_from_orders_formatted || '—';
                updatedAtEl.textContent = client.updated_at ? new Date(client.updated_at).toLocaleString('ru-RU') : '—';
                loyaltyBadge.textContent = client.loyalty_label || client.loyalty_level || 'Не задан';
                notesEl.textContent = client.notes || '—';

                renderBadges(tagsContainer, client.tags || [], 'Теги не указаны.');
                renderBadges(allergiesContainer, client.allergies || [], 'Нет данных.');
                preferencesContainer.innerHTML = renderPreferences(client.preferences);
                renderHighlights(client);
                renderStatistics(meta?.statistics || null);

                if (client.id) {
                    editLink.href = '/clients/' + client.id + '/edit';
                    editLink.hidden = false;
                    reminderButton.disabled = false;
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
                        showAlert('danger', result.error?.message || 'Не удалось загрузить данные клиента.');
                        subtitleEl.textContent = 'Ошибка загрузки данных.';
                        return;
                    }

                    fillClient(result.data, result.meta || {});
                } catch (error) {
                    console.error(error);
                    showAlert('danger', 'Произошла ошибка при загрузке клиента.');
                    subtitleEl.textContent = 'Ошибка загрузки данных.';
                }
            }

            function openReminderModal() {
                if (!currentClient) {
                    return;
                }

                reminderTitle.textContent = `Автонапоминание для ${currentClient.name || 'клиента'}`;
                reminderMessageInput.value = reminderMessageTemplate || '';
                reminderErrors.textContent = '';
                renderReminderChannels(currentClient);
                reminderModal.show();
            }

            reminderButton.addEventListener('click', openReminderModal);

            reminderSendBtn.addEventListener('click', async function () {
                reminderErrors.textContent = '';
                const message = reminderMessageInput.value.trim();
                const channelInput = reminderChannels.querySelector('input[name="reminder-channel"]:checked');

                if (!channelInput) {
                    reminderErrors.textContent = 'Выберите канал связи.';
                    return;
                }

                if (!message) {
                    reminderErrors.textContent = 'Добавьте текст напоминания.';
                    return;
                }

                try {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        await navigator.clipboard.writeText(message);
                        showAlert('info', 'Текст напоминания скопирован. Отправьте его клиенту через выбранный канал.');
                    } else {
                        showAlert('info', 'Скопируйте текст напоминания вручную и отправьте клиенту.');
                    }
                } catch (error) {
                    console.warn('Clipboard copy failed', error);
                    showAlert('info', 'Не удалось скопировать текст автоматически. Скопируйте вручную.');
                }

                reminderModal.hide();
            });

            loadClient();
        });
    </script>
@endsection
