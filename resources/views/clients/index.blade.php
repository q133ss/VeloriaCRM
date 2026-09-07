@extends('layouts.app')

@section('title', 'Клиенты')

@section('content')
    <style>
        .clients-page {
            --clients-card-shadow: 0 20px 48px -34px rgba(37, 26, 84, 0.45);
            --clients-line: color-mix(in srgb, var(--bs-border-color) 70%, transparent);
        }

        .clients-hero {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            border: 1px solid rgba(var(--bs-primary-rgb, 255, 0, 252), 0.14);
            border-radius: 1.5rem;
            padding: 1.35rem 1.5rem;
            background: linear-gradient(135deg, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.07), rgba(var(--bs-primary-rgb, 255, 0, 252), 0.015) 60%, rgba(var(--bs-info-rgb, 0, 207, 232), 0.04));
            box-shadow: var(--clients-card-shadow);
        }

        .clients-hero__top {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .clients-hero__title {
            margin: 0 0 0.2rem;
            font-size: clamp(1.35rem, 2vw, 1.75rem);
            letter-spacing: -0.02em;
        }

        .clients-hero__lead {
            color: var(--bs-secondary-color);
        }

        .clients-hero__cta {
            white-space: nowrap;
        }

        .clients-surface {
            border: none;
            border-radius: 1.35rem;
            box-shadow: var(--clients-card-shadow);
            background: color-mix(in srgb, var(--bs-card-bg) 94%, transparent);
        }

        .clients-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
        }

        .clients-tabs {
            display: inline-flex;
            gap: 0.2rem;
            padding: 0.25rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.07);
        }

        .clients-tab {
            border: 0;
            border-radius: 999px;
            padding: 0.45rem 0.95rem;
            background: transparent;
            color: var(--bs-secondary-color);
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .clients-tab.is-active {
            background: var(--bs-card-bg, #fff);
            color: var(--bs-primary);
            box-shadow: 0 8px 20px -14px rgba(0, 0, 0, 0.65);
        }

        .clients-tab__count {
            margin-left: 0.3rem;
            opacity: 0.65;
            font-weight: 600;
        }

        .clients-toolbar__right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1 1 240px;
            justify-content: flex-end;
        }

        /* Field and buttons to one height: the theme gives them three. */
        .clients-toolbar__right .clients-search {
            max-width: 280px;
            height: 2.625rem;
            padding-top: 0;
            padding-bottom: 0;
        }

        /* Rows, not a table: on a phone the same data folds into a card
           instead of running off the right edge. */
        .clients-row,
        .clients-list-head {
            display: grid;
            grid-template-columns: minmax(150px, 1.2fr) minmax(160px, 1.1fr) minmax(150px, 1fr) minmax(150px, 1fr) 168px;
            align-items: center;
            gap: 0.9rem;
            padding: 0.85rem 1.25rem;
        }

        .clients-list-head {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            color: var(--bs-secondary-color);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--clients-line);
        }

        .clients-row {
            border-bottom: 1px solid var(--clients-line);
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .clients-row:hover {
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.03);
        }

        .clients-row__who strong {
            display: block;
            font-size: 0.98rem;
        }

        .clients-row__tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
            margin-top: 0.25rem;
        }

        .clients-tag {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.1);
            color: var(--bs-primary);
            font-size: 0.72rem;
            font-weight: 700;
        }

        .clients-row__contacts,
        .clients-row__history,
        .clients-row__next {
            min-width: 0;
        }

        .clients-row__contacts a {
            color: var(--bs-body-color);
        }

        .clients-row__contacts a:hover {
            color: var(--bs-primary);
        }

        .clients-row small,
        .clients-row__muted {
            display: block;
            margin-top: 0.15rem;
            color: var(--bs-secondary-color);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .clients-row__away {
            color: var(--bs-warning-text-emphasis, var(--bs-warning));
            font-weight: 600;
        }

        .clients-row__soon {
            font-weight: 600;
        }

        .clients-row__act {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.35rem;
        }

        .clients-row__act .btn {
            white-space: nowrap;
        }

        .clients-empty {
            padding: 3rem 1.25rem;
            text-align: center;
            color: var(--bs-secondary-color);
        }

        .clients-pagination {
            padding: 0.9rem 1.25rem;
            border-top: 1px solid var(--clients-line);
            background: transparent;
        }

        @media (max-width: 767.98px) {
            .clients-list-head {
                display: none;
            }

            .clients-row {
                grid-template-columns: 1fr;
                grid-template-areas:
                    'who'
                    'history'
                    'next'
                    'contacts'
                    'act';
                row-gap: 0.45rem;
                padding: 1rem 1.1rem;
            }

            .clients-row__who { grid-area: who; }
            .clients-row__history { grid-area: history; }
            .clients-row__next { grid-area: next; }
            .clients-row__contacts { grid-area: contacts; }
            .clients-row__act { grid-area: act; justify-content: stretch; margin-top: 0.35rem; }

            .clients-row__act .clients-row__primary {
                flex: 1;
            }

            .clients-toolbar,
            .clients-toolbar__right {
                justify-content: flex-start;
            }

            .clients-toolbar__right .clients-search {
                max-width: none;
            }

            /* Four tabs do not fit one line on a 390px screen. They wrap onto
               two rather than hide behind a sideways scroll nobody discovers. */
            .clients-tabs {
                width: 100%;
                flex-wrap: wrap;
                border-radius: 1.1rem;
            }

            .clients-tab {
                padding: 0.45rem 0.7rem;
                font-size: 0.85rem;
            }
        }
    </style>

    <div class="clients-page d-flex flex-column gap-4">
        <section class="clients-hero">
            <div class="clients-hero__top">
                <div>
                    <h1 class="clients-hero__title">Клиенты</h1>
                    <p class="clients-hero__lead mb-0" id="clients-lead">Загрузка...</p>
                </div>
                {{-- One way in. A card is also created by itself the first time
                     someone is booked, so adding one by hand is the rare case. --}}
                <button type="button" class="btn btn-primary clients-hero__cta" data-bs-toggle="modal" data-bs-target="#quickClientModal">
                    <i class="ri ri-user-add-line me-1"></i>
                    Добавить клиента
                </button>
            </div>
        </section>

        <div id="clients-alerts"></div>

        <div class="card clients-surface">
            <div class="clients-toolbar">
                <div class="clients-tabs" id="clients-tabs">
                    <button type="button" class="clients-tab is-active" data-group="all">Все<span class="clients-tab__count" data-count="all"></span></button>
                    <button type="button" class="clients-tab" data-group="upcoming">Придут<span class="clients-tab__count" data-count="upcoming"></span></button>
                    <button type="button" class="clients-tab" data-group="sleeping">Давно не были<span class="clients-tab__count" data-count="sleeping"></span></button>
                    <button type="button" class="clients-tab" data-group="new">Новые<span class="clients-tab__count" data-count="new"></span></button>
                </div>
                <div class="clients-toolbar__right">
                    <input
                        type="search"
                        class="form-control clients-search"
                        id="filter-search"
                        placeholder="Имя, телефон, тег"
                        autocomplete="off"
                    />
                </div>
            </div>

            <div class="clients-list-head">
                <span>Клиент</span>
                <span>Контакты</span>
                <span>Была</span>
                <span>Придёт</span>
                <span></span>
            </div>

            <div class="clients-list" id="clients-body">
                <div class="clients-empty">Загрузка...</div>
            </div>

            <div class="card-footer d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 clients-pagination" id="clients-pagination">
                <div class="text-muted small" id="clients-summary"></div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="pagination-list"></ul>
                </nav>
            </div>
        </div>
    </div>

    <div class="modal fade" id="quickClientModal" tabindex="-1" aria-labelledby="quickClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickClientModalLabel">Новый клиент</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form id="quick-client-form" onsubmit="return false;">
                    <div class="modal-body">
                        <p class="text-muted">Имя и телефон — остальное допишется само, когда она придёт.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="quick_client_name" name="name" placeholder="Имя" required />
                                    <label for="quick_client_name">Имя клиента</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="quick_client_phone" name="phone" placeholder="+7(999)999-99-99" data-phone-mask required />
                                    <label for="quick_client_phone">Телефон</label>
                                </div>
                            </div>
                        </div>
                        <div id="quick-client-errors" class="mt-3"></div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        {{-- The full form is not a second route, it is this one continued. --}}
                        <a href="{{ route('clients.create') }}" class="btn btn-text-secondary">Все поля</a>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                            <button type="submit" class="btn btn-primary" id="quick-client-submit">Создать</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="clientDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Удалить карточку?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                {{-- The old confirm() said «Удалить Анна Лебедева?» and nothing
                     about what that costs. It costs the notes, and only those. --}}
                <div class="modal-body">
                    <p class="mb-2" id="delete-name">Клиент</p>
                    <p class="text-muted mb-0" id="delete-consequences"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Оставить</button>
                    <button type="button" class="btn btn-danger" id="delete-confirm">Удалить карточку</button>
                </div>
            </div>
        </div>
    </div>

    @include('components.message-sheet-styles')
    @include('components.message-sheet')
@endsection

@section('scripts')
    @include('components.phone-mask-script')
    @include('components.message-sheet-script')
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

            // Names, tags and notes are typed by people and rendered as markup.
            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, function (character) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
                });
            }

            function plural(count, one, few, many) {
                const mod100 = count % 100;
                if (mod100 >= 11 && mod100 <= 14) return many;
                const mod10 = count % 10;
                if (mod10 === 1) return one;
                if (mod10 >= 2 && mod10 <= 4) return few;
                return many;
            }

            const state = {
                group: 'all',
                search: '',
                page: 1,
                perPage: 12,
                clients: [],
                groups: {},
            };

            const alertsContainer = document.getElementById('clients-alerts');
            const clientsBody = document.getElementById('clients-body');
            const clientsLead = document.getElementById('clients-lead');
            const clientsSummary = document.getElementById('clients-summary');
            const paginationList = document.getElementById('pagination-list');
            const searchInput = document.getElementById('filter-search');
            const tabsContainer = document.getElementById('clients-tabs');

            const quickClientModalEl = document.getElementById('quickClientModal');
            const quickClientForm = document.getElementById('quick-client-form');
            const quickClientSubmit = document.getElementById('quick-client-submit');
            const quickClientErrors = document.getElementById('quick-client-errors');
            const quickClientNameInput = document.getElementById('quick_client_name');
            const quickClientSubmitOriginal = quickClientSubmit ? quickClientSubmit.innerHTML : '';

            const deleteModalEl = document.getElementById('clientDeleteModal');
            const deleteModal = new bootstrap.Modal(deleteModalEl);
            const deleteName = document.getElementById('delete-name');
            const deleteConsequences = document.getElementById('delete-consequences');
            const deleteConfirm = document.getElementById('delete-confirm');
            let deleteTarget = null;

            function showAlert(type, message, sticky = false) {
                const wrapper = document.createElement('div');
                wrapper.className = 'alert alert-' + type + ' alert-dismissible fade show';
                wrapper.setAttribute('role', 'alert');
                wrapper.innerHTML = `
                    ${escapeHtml(message)}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
                `;
                alertsContainer.appendChild(wrapper);
                if (!sticky) {
                    setTimeout(() => {
                        wrapper.classList.remove('show');
                        wrapper.addEventListener('transitionend', () => wrapper.remove());
                    }, 5000);
                }
            }

            function findClient(id) {
                return state.clients.find(client => Number(client.id) === Number(id));
            }

            function bookUrl(client) {
                const params = new URLSearchParams();
                if (client.account_id) params.set('client_id', client.account_id);
                if (client.name) params.set('client_name', client.name);
                if (client.phone) params.set('client_phone', client.phone);
                return '/orders/create?' + params.toString();
            }

            // One rule, and it fits in a sentence: if she has slipped away, write
            // to her; otherwise the useful thing is to put her in the calendar.
            function primaryFor(client) {
                return client.group === 'sleeping'
                    ? { action: 'message', label: 'Написать', icon: 'ri-chat-1-line' }
                    : { action: 'book', label: 'Записать', icon: 'ri-calendar-check-line' };
            }

            function openMessage(client, withDraft) {
                if (!window.veloriaMessage) {
                    return;
                }

                window.veloriaMessage.open({
                    clientId: client.id,
                    clientName: client.name || 'Клиент',
                    clientPhone: client.phone || '',
                    channels: client.channels || [],
                    // The wording is the part a master puts off, and it is worth
                    // generating only when there is something to say: come back.
                    draft: withDraft ? { intent: 'return' } : null,
                });
            }

            function menuFor(client, primary) {
                const items = [];

                if (client.phone) {
                    items.push(`<a class="dropdown-item" href="tel:${escapeHtml(client.phone)}"><i class="ri ri-phone-line me-2"></i>Позвонить</a>`);
                }

                if (primary.action === 'message') {
                    items.push(`<a class="dropdown-item" href="${escapeHtml(bookUrl(client))}"><i class="ri ri-calendar-check-line me-2"></i>Записать</a>`);
                } else {
                    items.push(`<button type="button" class="dropdown-item js-client-action" data-action="message"><i class="ri ri-chat-1-line me-2"></i>Написать</button>`);
                }

                if (client.next && client.next.order_id) {
                    items.push(`<a class="dropdown-item" href="/orders/${client.next.order_id}"><i class="ri ri-calendar-event-line me-2"></i>Открыть запись</a>`);
                }

                items.push(`<a class="dropdown-item" href="/clients/${client.id}"><i class="ri ri-user-line me-2"></i>Карточка клиента</a>`);
                items.push(`<a class="dropdown-item" href="/clients/${client.id}/edit"><i class="ri ri-edit-line me-2"></i>Редактировать</a>`);
                items.push('<div class="dropdown-divider"></div>');
                items.push(`<button type="button" class="dropdown-item text-danger js-client-action" data-action="delete"><i class="ri ri-delete-bin-line me-2"></i>Удалить карточку</button>`);

                return items.join('');
            }

            function renderClients(clients) {
                clientsBody.innerHTML = '';

                if (!clients.length) {
                    clientsBody.appendChild(buildEmptyState());
                    return;
                }

                clients.forEach(function (client) {
                    const primary = primaryFor(client);
                    const tags = (Array.isArray(client.tags) ? client.tags : []).slice(0, 3);
                    const row = document.createElement('article');
                    row.className = 'clients-row';
                    row.dataset.id = client.id;

                    const historyExtra = [client.visits_text, client.no_shows_text].filter(Boolean).join(' · ');
                    const awayClass = client.group === 'sleeping' ? ' clients-row__away' : '';
                    const soonClass = client.next && client.next.at ? ' clients-row__soon' : '';

                    row.innerHTML = `
                        <div class="clients-row__who">
                            <strong>${escapeHtml(client.name || 'Без имени')}</strong>
                            ${tags.length ? `<div class="clients-row__tags">${tags.map(tag => `<span class="clients-tag">${escapeHtml(tag)}</span>`).join('')}</div>` : ''}
                        </div>
                        <div class="clients-row__contacts">
                            ${client.phone
                                ? `<a href="tel:${escapeHtml(client.phone)}" class="js-stop">${escapeHtml(client.phone)}</a>`
                                : '<span class="text-muted">Без телефона</span>'}
                            ${client.email ? `<small>${escapeHtml(client.email)}</small>` : ''}
                        </div>
                        <div class="clients-row__history">
                            <span class="${awayClass.trim()}">${escapeHtml(client.last_visit ? client.last_visit.text : '')}</span>
                            ${historyExtra ? `<small>${escapeHtml(historyExtra)}</small>` : ''}
                        </div>
                        <div class="clients-row__next">
                            <span class="${soonClass.trim()}">${escapeHtml(client.next ? client.next.text : '')}</span>
                        </div>
                        <div class="clients-row__act">
                            ${primary.action === 'book'
                                ? `<a href="${escapeHtml(bookUrl(client))}" class="btn btn-sm btn-primary clients-row__primary js-stop"><i class="ri ${primary.icon} me-1"></i>${primary.label}</a>`
                                : `<button type="button" class="btn btn-sm btn-primary clients-row__primary js-client-action" data-action="message"><i class="ri ${primary.icon} me-1"></i>${primary.label}</button>`}
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-icon btn-text-secondary" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="Ещё">
                                    <i class="ri ri-more-2-line"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">${menuFor(client, primary)}</div>
                            </div>
                        </div>
                    `;

                    row.addEventListener('click', function (event) {
                        if (event.target.closest('.js-stop, .dropdown, a, button')) {
                            return;
                        }
                        window.location.href = '/clients/' + client.id;
                    });

                    row.querySelectorAll('.js-client-action').forEach(function (button) {
                        button.addEventListener('click', function (event) {
                            event.stopPropagation();
                            runClientAction(client, this.dataset.action);
                        });
                    });

                    clientsBody.appendChild(row);
                });
            }

            function buildEmptyState() {
                const box = document.createElement('div');
                box.className = 'clients-empty';

                if (state.search) {
                    box.innerHTML = `Никого не нашли по запросу «${escapeHtml(state.search)}». Попробуйте часть имени или последние цифры телефона.`;
                    return box;
                }

                box.textContent = {
                    upcoming: 'Ни у кого нет предстоящей записи.',
                    sleeping: 'Никто не пропал: все были у вас меньше полутора месяцев назад.',
                    new: 'Новых карточек нет — все, кто у вас есть, уже приходили или записаны.',
                    all: 'Клиентов пока нет. Карточка создаётся сама, как только вы кого-нибудь запишете.',
                }[state.group] || 'Здесь пока пусто.';

                return box;
            }

            function runClientAction(client, action) {
                if (action === 'message') {
                    openMessage(client, client.group === 'sleeping');
                    return;
                }

                if (action === 'delete') {
                    askToDelete(client);
                }
            }

            function askToDelete(client) {
                deleteTarget = client;
                deleteName.textContent = client.name || 'Клиент';

                const kept = [];
                if (client.visits > 0) {
                    kept.push(client.visits + ' ' + plural(client.visits, 'визит', 'визита', 'визитов'));
                }
                if (client.next && client.next.order_id) {
                    kept.push('будущая запись');
                }

                deleteConsequences.textContent = 'Заметки, аллергии, предпочтения и теги пропадут без возврата.'
                    + (kept.length ? ' ' + kept.join(' и ') + ' останутся в календаре и в аналитике.' : '');

                deleteModal.show();
            }

            function renderLead(meta) {
                const groups = meta.groups || {};
                const total = groups.all || 0;
                const parts = [total + ' ' + plural(total, 'клиент', 'клиента', 'клиентов')];

                if (groups.upcoming) {
                    parts.push(groups.upcoming + ' ' + plural(groups.upcoming, 'придёт', 'придут', 'придут'));
                }

                if (groups.sleeping) {
                    parts.push(groups.sleeping + ' ' + plural(groups.sleeping, 'давно не была', 'давно не были', 'давно не были'));
                }

                clientsLead.textContent = total ? parts.join(' · ') : 'Пока никого — записи заводят карточки сами.';

                Object.keys(groups).forEach(function (key) {
                    const badge = tabsContainer.querySelector(`[data-count="${key}"]`);
                    if (badge) {
                        badge.textContent = groups[key] ? ' ' + groups[key] : '';
                    }
                });
            }

            function renderPagination(meta) {
                paginationList.innerHTML = '';

                const pagination = (meta && meta.pagination) || {};
                const totalPages = pagination.last_page || 1;
                const current = pagination.current_page || 1;
                const total = pagination.total || 0;

                clientsSummary.textContent = total
                    ? `Показано ${state.clients.length} из ${total}`
                    : '';

                if (totalPages < 2) {
                    return;
                }

                const step = function (label, page, disabled) {
                    const item = document.createElement('li');
                    item.className = 'page-item' + (disabled ? ' disabled' : '');
                    item.innerHTML = `<a class="page-link" href="#">${label}</a>`;
                    item.addEventListener('click', function (event) {
                        event.preventDefault();
                        if (!disabled) loadClients(page);
                    });
                    paginationList.appendChild(item);
                };

                step('«', current - 1, current <= 1);

                for (let page = 1; page <= totalPages; page++) {
                    if (totalPages > 6 && page > 2 && page < totalPages - 1 && Math.abs(page - current) > 1) {
                        continue;
                    }

                    const item = document.createElement('li');
                    item.className = 'page-item' + (page === current ? ' active' : '');
                    item.innerHTML = `<a class="page-link" href="#">${page}</a>`;
                    item.addEventListener('click', function (event) {
                        event.preventDefault();
                        loadClients(page);
                    });
                    paginationList.appendChild(item);
                }

                step('»', current + 1, current >= totalPages);
            }

            function syncTabs() {
                tabsContainer.querySelectorAll('.clients-tab').forEach(function (tab) {
                    tab.classList.toggle('is-active', tab.dataset.group === state.group);
                });
            }

            async function loadClients(page = 1) {
                state.page = page;
                clientsBody.innerHTML = '<div class="clients-empty">Загрузка...</div>';

                const params = new URLSearchParams({
                    page: String(page),
                    per_page: String(state.perPage),
                    group: state.group,
                });

                if (state.search) {
                    params.append('search', state.search);
                }

                try {
                    const response = await fetch('/api/v1/clients?' + params.toString(), {
                        headers: authHeaders(),
                        credentials: 'include',
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        clientsBody.innerHTML = '<div class="clients-empty text-danger">Не удалось загрузить клиентов.</div>';
                        showAlert('danger', result.error?.message || 'Произошла ошибка при загрузке клиентов.', true);
                        return;
                    }

                    state.clients = Array.isArray(result.data) ? result.data : [];
                    state.groups = result.meta?.groups || {};

                    renderClients(state.clients);
                    renderLead(result.meta || {});
                    renderPagination(result.meta || {});
                } catch (error) {
                    console.error(error);
                    clientsBody.innerHTML = '<div class="clients-empty text-danger">Не удалось загрузить клиентов.</div>';
                    showAlert('danger', 'Произошла непредвиденная ошибка.', true);
                }
            }

            deleteConfirm.addEventListener('click', async function () {
                if (!deleteTarget) {
                    return;
                }

                const client = deleteTarget;
                deleteConfirm.disabled = true;

                try {
                    const response = await fetch('/api/v1/clients/' + client.id, {
                        method: 'DELETE',
                        headers: authHeaders(),
                        credentials: 'include',
                    });

                    const result = await response.json().catch(() => ({}));
                    deleteModal.hide();

                    if (!response.ok) {
                        showAlert('danger', result.error?.message || 'Не удалось удалить карточку.', true);
                        return;
                    }

                    showAlert('success', 'Карточка удалена.');
                    await loadClients(state.page);
                } catch (error) {
                    console.error(error);
                    deleteModal.hide();
                    showAlert('danger', 'Произошла ошибка при удалении.', true);
                } finally {
                    deleteConfirm.disabled = false;
                    deleteTarget = null;
                }
            });

            tabsContainer.addEventListener('click', function (event) {
                const tab = event.target.closest('.clients-tab');
                if (!tab || tab.dataset.group === state.group) {
                    return;
                }

                state.group = tab.dataset.group;
                syncTabs();
                loadClients(1);
            });

            // The filter applies itself. «Применить» was one more press between
            // typing a name and seeing it.
            let searchTimer = null;
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    const value = searchInput.value.trim();
                    if (value === state.search) {
                        return;
                    }
                    state.search = value;
                    loadClients(1);
                }, 400);
            });

            function setQuickClientLoading(isLoading) {
                if (!quickClientSubmit) {
                    return;
                }

                quickClientSubmit.disabled = isLoading;
                quickClientSubmit.innerHTML = isLoading
                    ? '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Создание...'
                    : quickClientSubmitOriginal;
            }

            function clearQuickClientErrors() {
                if (quickClientErrors) {
                    quickClientErrors.innerHTML = '';
                }

                quickClientForm?.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                quickClientForm?.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
            }

            function attachQuickClientErrors(fields) {
                Object.keys(fields || {}).forEach(function (key) {
                    const input = quickClientForm.querySelector(`[name="${key}"]`);
                    if (!input) {
                        return;
                    }

                    input.classList.add('is-invalid');
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = Array.isArray(fields[key]) ? fields[key][0] : fields[key];

                    if (input.parentElement?.classList.contains('form-floating')) {
                        input.parentElement.appendChild(feedback);
                    } else {
                        input.insertAdjacentElement('afterend', feedback);
                    }
                });
            }

            quickClientForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                clearQuickClientErrors();
                setQuickClientLoading(true);

                const formData = new FormData(quickClientForm);
                const payload = {
                    name: (formData.get('name') || '').toString().trim(),
                    phone: (formData.get('phone') || '').toString().trim(),
                };

                try {
                    const response = await fetch('/api/v1/clients', {
                        method: 'POST',
                        headers: authHeaders(),
                        credentials: 'include',
                        body: JSON.stringify(payload),
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        attachQuickClientErrors(result.error?.fields || result.errors || {});
                        if (quickClientErrors) {
                            quickClientErrors.innerHTML = `<div class="text-danger">${escapeHtml(result.error?.message || result.message || 'Не удалось создать клиента.')}</div>`;
                        }
                        return;
                    }

                    bootstrap.Modal.getInstance(quickClientModalEl)?.hide();
                    showAlert('success', result.message || 'Клиент создан.');
                    await loadClients(1);
                } catch (error) {
                    console.error(error);
                    if (quickClientErrors) {
                        quickClientErrors.innerHTML = '<div class="text-danger">Произошла ошибка. Попробуйте ещё раз.</div>';
                    }
                } finally {
                    setQuickClientLoading(false);
                }
            });

            quickClientModalEl.addEventListener('shown.bs.modal', function () {
                clearQuickClientErrors();
                setQuickClientLoading(false);
                quickClientNameInput?.focus();
            });

            quickClientModalEl.addEventListener('hidden.bs.modal', function () {
                quickClientForm.reset();
                clearQuickClientErrors();
                setQuickClientLoading(false);
            });

            syncTabs();
            loadClients();
        });
    </script>
@endsection
