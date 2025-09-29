@extends('layouts.app')

@section('title', 'Клиенты')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Клиенты</h4>
            <p class="text-muted mb-0">Ведите базу клиентов, отслеживайте визиты и отправляйте напоминания.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('clients.create') }}" class="btn btn-primary">
                <i class="ri ri-user-add-line me-1"></i>
                Добавить клиента
            </a>
        </div>
    </div>

    <div id="clients-alerts"></div>

    <div class="card">
        <div class="card-header d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
            <div class="d-flex align-items-center gap-2">
                <h5 class="mb-0">Мои клиенты</h5>
                <span class="badge bg-label-secondary" id="clients-total">0</span>
            </div>
            <form id="filters-form" class="row g-2 align-items-end">
                <div class="col-md-6 col-lg-4">
                    <label for="filter-search" class="form-label">Поиск</label>
                    <div class="position-relative">
                        <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted">
                            <i class="ri ri-search-line"></i>
                        </span>
                        <input
                            type="text"
                            class="form-control ps-5"
                            id="filter-search"
                            name="search"
                            placeholder="Имя, телефон или email"
                        />
                    </div>
                </div>
                <div class="col-md-4 col-lg-3">
                    <label for="filter-loyalty" class="form-label">Лояльность</label>
                    <select class="form-select" id="filter-loyalty" name="loyalty"></select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">Применить</button>
                    <button type="button" class="btn btn-outline-secondary flex-fill" id="filters-reset">Сбросить</button>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Клиент</th>
                        <th>Контакты</th>
                        <th>Последний визит</th>
                        <th>Лояльность</th>
                        <th class="text-end">Действия</th>
                    </tr>
                </thead>
                <tbody id="clients-body">
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">Загрузка данных...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2" id="clients-pagination">
            <div class="text-muted small" id="clients-summary">Показано 0 из 0</div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="pagination-list"></ul>
            </nav>
        </div>
    </div>

    <div class="modal fade" id="clientQuickViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quick-view-name">Клиент</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase mb-2">Контакты</h6>
                            <p class="mb-1">
                                <i class="ri ri-phone-line me-1"></i>
                                <span id="quick-view-phone">—</span>
                            </p>
                            <p class="mb-0">
                                <i class="ri ri-mail-line me-1"></i>
                                <span id="quick-view-email">—</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase mb-2">Профиль</h6>
                            <p class="mb-1">День рождения: <span id="quick-view-birthday">—</span></p>
                            <p class="mb-1">Последний визит: <span id="quick-view-last-visit">—</span></p>
                            <p class="mb-0">Лояльность: <span id="quick-view-loyalty">Не задан</span></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase mb-2">Теги</h6>
                            <div id="quick-view-tags" class="d-flex flex-wrap gap-2"></div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase mb-2">Аллергии</h6>
                            <div id="quick-view-allergies" class="d-flex flex-wrap gap-2"></div>
                        </div>
                        <div class="col-12">
                            <h6 class="text-muted text-uppercase mb-2">Предпочтения</h6>
                            <div id="quick-view-preferences" class="small"></div>
                        </div>
                        <div class="col-12">
                            <h6 class="text-muted text-uppercase mb-2">Заметки</h6>
                            <p class="mb-0" id="quick-view-notes">—</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-outline-primary" id="quick-view-link">Подробнее</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
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
                        <div class="form-text">Текст берётся из настроек. При необходимости отредактируйте перед отправкой.</div>
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
                    search: '',
                    loyalty: '',
                },
                page: 1,
                perPage: 10,
                reminderMessage: '',
                clients: [],
                loyaltyOptions: {},
            };

            const alertsContainer = document.getElementById('clients-alerts');
            const clientsBody = document.getElementById('clients-body');
            const clientsTotal = document.getElementById('clients-total');
            const clientsSummary = document.getElementById('clients-summary');
            const paginationList = document.getElementById('pagination-list');
            const filtersForm = document.getElementById('filters-form');
            const searchInput = document.getElementById('filter-search');
            const loyaltySelect = document.getElementById('filter-loyalty');
            const resetButton = document.getElementById('filters-reset');

            const quickViewModalEl = document.getElementById('clientQuickViewModal');
            const quickViewModal = new bootstrap.Modal(quickViewModalEl);
            const quickViewName = document.getElementById('quick-view-name');
            const quickViewPhone = document.getElementById('quick-view-phone');
            const quickViewEmail = document.getElementById('quick-view-email');
            const quickViewBirthday = document.getElementById('quick-view-birthday');
            const quickViewLastVisit = document.getElementById('quick-view-last-visit');
            const quickViewLoyalty = document.getElementById('quick-view-loyalty');
            const quickViewTags = document.getElementById('quick-view-tags');
            const quickViewAllergies = document.getElementById('quick-view-allergies');
            const quickViewPreferences = document.getElementById('quick-view-preferences');
            const quickViewNotes = document.getElementById('quick-view-notes');
            const quickViewLink = document.getElementById('quick-view-link');

            const reminderModalEl = document.getElementById('clientReminderModal');
            const reminderModal = new bootstrap.Modal(reminderModalEl);
            const reminderTitle = document.getElementById('reminder-title');
            const reminderMessageInput = document.getElementById('reminder-message');
            const reminderChannels = document.getElementById('reminder-channels');
            const reminderErrors = document.getElementById('reminder-errors');
            const reminderSendBtn = document.getElementById('reminder-send');

            let reminderClientId = null;

            function showAlert(type, message, sticky = false) {
                const wrapper = document.createElement('div');
                wrapper.className = 'alert alert-' + type + ' alert-dismissible fade show';
                wrapper.setAttribute('role', 'alert');
                wrapper.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                alertsContainer.appendChild(wrapper);
                if (!sticky) {
                    setTimeout(() => {
                        wrapper.classList.remove('show');
                        wrapper.addEventListener('transitionend', () => wrapper.remove());
                    }, 5000);
                }
            }

            function clearAlerts() {
                alertsContainer.innerHTML = '';
            }

            function renderSelectOptions(select, options, selected) {
                select.innerHTML = '';
                Object.keys(options).forEach(function (key) {
                    const option = document.createElement('option');
                    option.value = key;
                    option.textContent = options[key];
                    if ((selected ?? '') === key) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            }

            function renderEmptyState() {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="5" class="text-center py-5 text-muted">Клиентов пока нет.</td>';
                clientsBody.innerHTML = '';
                clientsBody.appendChild(row);
            }

            function formatList(items, emptyPlaceholder = '—') {
                if (!Array.isArray(items) || !items.length) {
                    return emptyPlaceholder;
                }
                return items.join(', ');
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

            function formatPreferences(preferences) {
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
                        .filter(([key, value]) => key && value !== null && value !== undefined)
                        .map(([key, value]) => `<li><strong>${key}</strong>: ${value}</li>`)
                        .join('');

                    if (!entries) {
                        return '<p class="text-muted mb-0">Предпочтения не указаны.</p>';
                    }

                    return `<ul class="mb-0 ps-3">${entries}</ul>`;
                }

                return '<p class="text-muted mb-0">Предпочтения не указаны.</p>';
            }

            function renderClients(clients) {
                clientsBody.innerHTML = '';

                if (!Array.isArray(clients) || !clients.length) {
                    renderEmptyState();
                    return;
                }

                clients.forEach(function (client) {
                    const tr = document.createElement('tr');
                    const loyalty = client.loyalty_label || client.loyalty_level || 'Не задан';
                    const lastVisit = client.last_visit_at_formatted || '—';
                    const tagsPreview = Array.isArray(client.tags) ? client.tags.slice(0, 2) : [];
                    const tagsExtra = Array.isArray(client.tags) && client.tags.length > 2
                        ? `<span class="text-muted small">+ ещё ${client.tags.length - 2}</span>`
                        : '';

                    tr.innerHTML = `
                        <td>
                            <div class="fw-semibold">${client.name || 'Без имени'}</div>
                            <div class="small text-muted">${tagsPreview.map(tag => `<span class="badge bg-label-primary me-1">${tag}</span>`).join(' ')} ${tagsExtra}</div>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <span>${client.phone ? `<i class=\"ri ri-phone-line me-1 text-muted\"></i>${client.phone}` : '—'}</span>
                                <span>${client.email ? `<i class=\"ri ri-mail-line me-1 text-muted\"></i>${client.email}` : ''}</span>
                            </div>
                        </td>
                        <td>${lastVisit}</td>
                        <td>
                            <span class="badge bg-label-info">${loyalty}</span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary js-client-quick-view" data-client-id="${client.id}">
                                    Быстрый просмотр
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info js-client-reminder" data-client-id="${client.id}">
                                    Автонапоминание
                                </button>
                                <a href="/clients/${client.id}" class="btn btn-sm btn-outline-primary">Подробнее</a>
                                <a href="/clients/${client.id}/edit" class="btn btn-sm btn-outline-secondary">Изменить</a>
                                <button type="button" class="btn btn-sm btn-outline-danger js-client-delete" data-client-id="${client.id}" data-client-name="${client.name}">
                                    Удалить
                                </button>
                            </div>
                        </td>
                    `;

                    clientsBody.appendChild(tr);
                });

                clientsBody.querySelectorAll('.js-client-quick-view').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const clientId = Number(this.getAttribute('data-client-id'));
                        openQuickView(clientId);
                    });
                });

                clientsBody.querySelectorAll('.js-client-reminder').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const clientId = Number(this.getAttribute('data-client-id'));
                        openReminderModal(clientId);
                    });
                });

                clientsBody.querySelectorAll('.js-client-delete').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const clientId = Number(this.getAttribute('data-client-id'));
                        const clientName = this.getAttribute('data-client-name') || 'клиента';
                        deleteClient(clientId, clientName);
                    });
                });
            }

            function renderPagination(meta) {
                paginationList.innerHTML = '';

                if (!meta || !meta.pagination) {
                    return;
                }

                const pagination = meta.pagination;
                const totalPages = pagination.last_page || 1;
                const current = pagination.current_page || 1;

                const prevItem = document.createElement('li');
                prevItem.className = 'page-item' + (current <= 1 ? ' disabled' : '');
                prevItem.innerHTML = '<a class="page-link" href="#" aria-label="Назад">«</a>';
                prevItem.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (current > 1) {
                        loadClients(current - 1);
                    }
                });
                paginationList.appendChild(prevItem);

                for (let page = 1; page <= totalPages; page++) {
                    if (totalPages > 6 && page > 3 && page < totalPages - 2 && Math.abs(page - current) > 1) {
                        if (page < current && !paginationList.querySelector('.page-item.dots-before')) {
                            const dots = document.createElement('li');
                            dots.className = 'page-item disabled dots-before';
                            dots.innerHTML = '<span class="page-link">...</span>';
                            paginationList.appendChild(dots);
                        }
                        if (page > current && !paginationList.querySelector('.page-item.dots-after')) {
                            const dots = document.createElement('li');
                            dots.className = 'page-item disabled dots-after';
                            dots.innerHTML = '<span class="page-link">...</span>';
                            paginationList.appendChild(dots);
                        }
                        continue;
                    }

                    const item = document.createElement('li');
                    item.className = 'page-item' + (page === current ? ' active' : '');
                    item.innerHTML = `<a class="page-link" href="#">${page}</a>`;
                    item.addEventListener('click', function (e) {
                        e.preventDefault();
                        loadClients(page);
                    });
                    paginationList.appendChild(item);
                }

                const nextItem = document.createElement('li');
                nextItem.className = 'page-item' + (current >= totalPages ? ' disabled' : '');
                nextItem.innerHTML = '<a class="page-link" href="#" aria-label="Вперёд">»</a>';
                nextItem.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (current < totalPages) {
                        loadClients(current + 1);
                    }
                });
                paginationList.appendChild(nextItem);

                const shownCount = (current - 1) * state.perPage + (state.clients.length || 0);
                const total = pagination.total || 0;
                clientsSummary.textContent = `Показано ${Math.min(shownCount, total)} из ${total}`;
                clientsTotal.textContent = total;
            }

            async function loadClients(page = 1) {
                clearAlerts();
                clientsBody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">Загрузка данных...</td></tr>';

                state.page = page;
                const params = new URLSearchParams({
                    page: String(page),
                    per_page: String(state.perPage),
                });

                if (state.filters.search) {
                    params.append('search', state.filters.search);
                }

                if (state.filters.loyalty) {
                    params.append('loyalty', state.filters.loyalty);
                }

                try {
                    const response = await fetch('/api/v1/clients?' + params.toString(), {
                        headers: authHeaders(),
                        credentials: 'include',
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        clientsBody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-danger">Не удалось загрузить клиентов.</td></tr>';
                        const message = result.error?.message || 'Произошла ошибка при загрузке клиентов.';
                        showAlert('danger', message, true);
                        return;
                    }

                    state.clients = Array.isArray(result.data) ? result.data : [];
                    state.reminderMessage = result.meta?.reminder_message || '';

                    if (result.meta?.loyalty_options) {
                        state.loyaltyOptions = result.meta.loyalty_options;
                        renderSelectOptions(loyaltySelect, state.loyaltyOptions, state.filters.loyalty);
                    } else if (!loyaltySelect.options.length) {
                        renderSelectOptions(loyaltySelect, { '': 'Все уровни' }, state.filters.loyalty);
                    }

                    renderClients(state.clients);
                    renderPagination(result.meta);
                } catch (error) {
                    console.error(error);
                    clientsBody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-danger">Не удалось загрузить клиентов.</td></tr>';
                    showAlert('danger', 'Произошла непредвиденная ошибка.', true);
                }
            }

            function findClient(id) {
                return state.clients.find(client => Number(client.id) === Number(id));
            }

            function openQuickView(clientId) {
                const client = findClient(clientId);
                if (!client) {
                    showAlert('warning', 'Клиент не найден.');
                    return;
                }

                quickViewName.textContent = client.name || 'Клиент';
                quickViewPhone.textContent = client.phone || '—';
                quickViewEmail.textContent = client.email || '—';
                quickViewBirthday.textContent = client.birthday_formatted || '—';
                quickViewLastVisit.textContent = client.last_visit_at_formatted || '—';
                quickViewLoyalty.textContent = client.loyalty_label || client.loyalty_level || 'Не задан';
                quickViewNotes.textContent = client.notes || '—';
                quickViewLink.setAttribute('href', '/clients/' + client.id);

                renderBadges(quickViewTags, client.tags || [], 'Теги не указаны.');
                renderBadges(quickViewAllergies, client.allergies || [], 'Нет информации.');
                quickViewPreferences.innerHTML = formatPreferences(client.preferences);

                quickViewModal.show();
            }

            function renderReminderChannelsForClient(client) {
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

            function openReminderModal(clientId) {
                const client = findClient(clientId);
                if (!client) {
                    showAlert('warning', 'Клиент не найден.');
                    return;
                }

                reminderClientId = clientId;
                reminderTitle.textContent = `Автонапоминание для ${client.name || 'клиента'}`;
                reminderMessageInput.value = state.reminderMessage || '';
                reminderErrors.textContent = '';
                renderReminderChannelsForClient(client);
                reminderModal.show();
            }

            async function deleteClient(clientId, clientName) {
                if (!confirm(`Удалить ${clientName}?`)) {
                    return;
                }

                try {
                    const response = await fetch('/api/v1/clients/' + clientId, {
                        method: 'DELETE',
                        headers: authHeaders(),
                        credentials: 'include',
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const message = result.error?.message || 'Не удалось удалить клиента.';
                        showAlert('danger', message, true);
                        return;
                    }

                    showAlert('success', 'Клиент удалён.');
                    await loadClients(state.page);
                } catch (error) {
                    console.error(error);
                    showAlert('danger', 'Произошла ошибка при удалении клиента.', true);
                }
            }

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
                        showAlert('info', 'Текст напоминания скопирован в буфер обмена. Отправьте его через выбранный канал.');
                    } else {
                        showAlert('info', 'Скопируйте текст напоминания вручную и отправьте клиенту.');
                    }
                } catch (error) {
                    console.warn('Clipboard copy failed', error);
                    showAlert('info', 'Не удалось автоматически скопировать текст. Скопируйте его вручную.');
                }

                reminderModal.hide();
            });

            filtersForm.addEventListener('submit', function (event) {
                event.preventDefault();
                state.filters.search = searchInput.value.trim();
                state.filters.loyalty = loyaltySelect.value || '';
                loadClients(1);
            });

            resetButton.addEventListener('click', function () {
                searchInput.value = '';
                loyaltySelect.value = '';
                state.filters.search = '';
                state.filters.loyalty = '';
                loadClients(1);
            });

            loadClients();
        });
    </script>
@endsection
