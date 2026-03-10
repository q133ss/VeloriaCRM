@extends('layouts.app')

@section('title', 'Backoffice Support')

@section('content')
    @include('admin.partials.styles')

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-shell">
            <section class="admin-hero">
                <h1>Поддержка</h1>
                <p>Очередь поддержки собрана в одну спокойную рабочую зону: приоритет, ответственный, контекст клиента и быстрый ответ без отдельного CRM-хаоса.</p>
            </section>

            @include('admin.partials.nav')

            <div class="admin-two-column">
                <section class="admin-panel">
                    <div class="admin-panel-body admin-stack">
                        <div class="admin-toolbar">
                            <select class="form-select" id="admin-ticket-status-filter">
                                <option value="all">Все статусы</option>
                                <option value="waiting">Waiting</option>
                                <option value="open">Open</option>
                                <option value="responded">Responded</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div id="admin-ticket-list" class="admin-list"></div>
                    </div>
                </section>

                <section class="admin-panel soft">
                    <div class="admin-panel-body">
                        <div id="admin-ticket-detail" class="admin-empty">Выберите тикет, чтобы назначить ответственного, обновить статус и ответить клиенту.</div>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterEl = document.getElementById('admin-ticket-status-filter');
            const listEl = document.getElementById('admin-ticket-list');
            const detailEl = document.getElementById('admin-ticket-detail');
            let tickets = [];
            let operators = [];
            let selectedTicketId = null;

            const formatDate = (value) => value ? new Date(value).toLocaleString() : 'Нет данных';

            const renderList = () => {
                if (!tickets.length) {
                    listEl.innerHTML = '<div class="admin-empty">Очередь по этим условиям пуста.</div>';
                    return;
                }

                listEl.innerHTML = tickets.map(function (ticket) {
                    const active = ticket.id === selectedTicketId ? 'is-active' : '';
                    const priorityClass = ticket.priority === 'urgent' ? 'danger' : (ticket.priority === 'high' ? 'warning' : '');
                    return `<article class="admin-row is-clickable ${active}" data-ticket-id="${ticket.id}"><div><div class="admin-row-title">${ticket.subject}</div><div class="admin-row-meta">${ticket.user?.name || 'Без имени'} • ${ticket.last_message_preview || 'Без сообщений'}</div></div><div class="d-flex flex-column align-items-end gap-2"><span class="admin-chip ${priorityClass}">${ticket.priority_label}</span><span class="admin-chip">${ticket.status_label}</span></div></article>`;
                }).join('');

                listEl.querySelectorAll('[data-ticket-id]').forEach(function (node) {
                    node.addEventListener('click', function () {
                        loadTicket(Number(node.getAttribute('data-ticket-id')));
                    });
                });
            };

            const renderTicket = (ticket) => {
                detailEl.innerHTML = `
                    <div class="admin-stack">
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div>
                                <h3 class="mb-1">${ticket.subject}</h3>
                                <p class="text-muted mb-0">${ticket.user?.name || 'Без имени'}${ticket.user?.email ? ' • ' + ticket.user.email : ''}${ticket.user?.phone ? ' • ' + ticket.user.phone : ''}</p>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <span class="admin-chip">${ticket.status_label}</span>
                                <span class="admin-chip ${ticket.priority === 'urgent' ? 'danger' : (ticket.priority === 'high' ? 'warning' : '')}">${ticket.priority_label}</span>
                            </div>
                        </div>
                        <div class="admin-detail-grid">
                            <div class="admin-detail-card"><div class="admin-metric-label">Категория</div><div class="admin-row-title">${ticket.category || 'Не выбрана'}</div></div>
                            <div class="admin-detail-card"><div class="admin-metric-label">Ответственный</div><div class="admin-row-title">${ticket.assignee ? ticket.assignee.name : 'Не назначен'}</div></div>
                            <div class="admin-detail-card"><div class="admin-metric-label">Создан</div><div class="admin-row-title">${formatDate(ticket.created_at)}</div></div>
                            <div class="admin-detail-card"><div class="admin-metric-label">Первый ответ</div><div class="admin-row-title">${formatDate(ticket.first_responded_at)}</div></div>
                        </div>
                        <form id="admin-ticket-meta-form" class="admin-stack">
                            <div class="admin-detail-grid">
                                <div>
                                    <label class="form-label" for="admin-ticket-status">Статус</label>
                                    <select class="form-select" id="admin-ticket-status" name="status">${['open', 'waiting', 'responded', 'closed'].map(function (status) { return `<option value="${status}" ${ticket.status === status ? 'selected' : ''}>${status}</option>`; }).join('')}</select>
                                </div>
                                <div>
                                    <label class="form-label" for="admin-ticket-priority">Приоритет</label>
                                    <select class="form-select" id="admin-ticket-priority" name="priority">${['low', 'normal', 'high', 'urgent'].map(function (priority) { return `<option value="${priority}" ${ticket.priority === priority ? 'selected' : ''}>${priority}</option>`; }).join('')}</select>
                                </div>
                                <div>
                                    <label class="form-label" for="admin-ticket-assignee">Ответственный</label>
                                    <select class="form-select" id="admin-ticket-assignee" name="assigned_to"><option value="">Не назначен</option>${operators.map(function (operator) { return `<option value="${operator.id}" ${ticket.assignee?.id === operator.id ? 'selected' : ''}>${operator.name}</option>`; }).join('')}</select>
                                </div>
                            </div>
                            <div>
                                <label class="form-label" for="admin-ticket-category">Категория</label>
                                <input class="form-control" id="admin-ticket-category" name="category" value="${ticket.category || ''}" placeholder="billing, onboarding, bug, ai">
                            </div>
                            <button class="btn btn-outline-primary align-self-start" type="submit">Обновить карточку</button>
                        </form>
                        <div>
                            <div class="admin-metric-label mb-2">Переписка</div>
                            <div class="admin-messages">
                                ${(ticket.messages || []).length ? ticket.messages.map(function (message) {
                                    const cssClass = message.sender_type === 'support' ? 'support' : '';
                                    return `<article class="admin-message ${cssClass}"><div class="d-flex justify-content-between gap-3 mb-2"><strong>${message.sender_type === 'support' ? 'Поддержка' : 'Клиент'}</strong><span class="admin-row-meta">${formatDate(message.created_at)}</span></div><div>${message.body || ''}</div></article>`;
                                }).join('') : '<div class="admin-empty">Сообщений ещё нет.</div>'}
                            </div>
                        </div>
                        <form id="admin-ticket-reply-form" class="admin-stack">
                            <div>
                                <label class="form-label" for="admin-ticket-reply">Ответ</label>
                                <textarea class="form-control" rows="4" id="admin-ticket-reply" placeholder="Коротко и спокойно объясните следующий шаг клиенту"></textarea>
                            </div>
                            <button class="btn btn-primary align-self-start" type="submit">Отправить ответ</button>
                        </form>
                    </div>
                `;

                document.getElementById('admin-ticket-meta-form').addEventListener('submit', async function (event) {
                    event.preventDefault();
                    const payload = {
                        status: document.getElementById('admin-ticket-status').value,
                        priority: document.getElementById('admin-ticket-priority').value,
                        assigned_to: document.getElementById('admin-ticket-assignee').value || null,
                        category: document.getElementById('admin-ticket-category').value || null
                    };

                    const response = await fetch(`/api/v1/admin/support/tickets/${ticket.id}`, {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify(payload)
                    });

                    if (!response.ok) {
                        alert('Не удалось обновить тикет.');
                        return;
                    }

                    await loadTickets();
                    await loadTicket(ticket.id);
                });

                document.getElementById('admin-ticket-reply-form').addEventListener('submit', async function (event) {
                    event.preventDefault();
                    const message = document.getElementById('admin-ticket-reply').value.trim();
                    if (!message) return;

                    const response = await fetch(`/api/v1/admin/support/tickets/${ticket.id}/reply`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ message })
                    });

                    if (!response.ok) {
                        alert('Не удалось отправить ответ.');
                        return;
                    }

                    await loadTickets();
                    await loadTicket(ticket.id);
                });
            };

            const loadTickets = async () => {
                const query = new URLSearchParams({ status: filterEl.value });
                const response = await fetch(`/api/v1/admin/support/tickets?${query.toString()}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!response.ok) {
                    listEl.innerHTML = '<div class="admin-empty">Не удалось загрузить очередь поддержки.</div>';
                    return;
                }

                const payload = await response.json();
                tickets = Array.isArray(payload.data) ? payload.data : [];
                operators = Array.isArray(payload.operators) ? payload.operators : [];

                if (!selectedTicketId && tickets[0]) selectedTicketId = tickets[0].id;
                if (selectedTicketId && !tickets.some(function (ticket) { return ticket.id === selectedTicketId; })) {
                    selectedTicketId = tickets[0] ? tickets[0].id : null;
                }

                renderList();

                if (selectedTicketId) {
                    await loadTicket(selectedTicketId, false);
                } else {
                    detailEl.innerHTML = '<div class="admin-empty">Очередь по этим условиям пуста.</div>';
                }
            };

            const loadTicket = async (ticketId, rerenderList = true) => {
                selectedTicketId = ticketId;
                if (rerenderList) renderList();

                const response = await fetch(`/api/v1/admin/support/tickets/${ticketId}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!response.ok) {
                    detailEl.innerHTML = '<div class="admin-empty">Не удалось загрузить тикет.</div>';
                    return;
                }

                const payload = await response.json();
                renderTicket(payload.data);
            };

            filterEl.addEventListener('change', loadTickets);
            loadTickets();
        });
    </script>
@endsection
