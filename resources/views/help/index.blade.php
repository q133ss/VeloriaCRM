@extends('layouts.app')

@section('title', __('help.title'))

@section('content')
    <style>
        .help-page {
            max-width: 1120px;
            margin: 0 auto;
            --help-border: rgba(var(--bs-primary-rgb), 0.12);
            --help-shadow: 0 24px 54px -36px rgba(37, 26, 84, 0.42);
        }

        .help-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--help-border);
            border-radius: 1.5rem;
            box-shadow: var(--help-shadow);
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.14), transparent 34%),
                linear-gradient(140deg, rgba(var(--bs-primary-rgb), 0.06), rgba(var(--bs-info-rgb, 0, 207, 232), 0.05) 58%, rgba(var(--bs-body-bg-rgb), 0.12));
        }

        .help-hero::after {
            content: '';
            position: absolute;
            right: -3rem;
            bottom: -4rem;
            width: 12rem;
            height: 12rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb), 0.08);
            filter: blur(12px);
        }

        .help-hero > * {
            position: relative;
            z-index: 1;
        }

        .help-section-card {
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
            border-radius: 1.25rem;
            background: rgba(var(--bs-body-bg-rgb), 0.98);
            box-shadow: none;
        }

        .help-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: rgba(var(--bs-body-bg-rgb), 0.72);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .help-intro {
            max-width: 44rem;
        }

        .help-hero-meta {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            align-items: flex-start;
        }

        .help-support-note {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.55rem 0.8rem;
            border-radius: 999px;
            background: rgba(var(--bs-body-color-rgb), 0.06);
            color: rgba(var(--bs-body-color-rgb), 0.78);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .help-support-note i {
            color: rgba(var(--bs-body-color-rgb), 0.55);
        }

        .help-form-hint {
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb), 0.04);
        }

        .help-form-hint p:last-child {
            margin-bottom: 0;
        }

        .help-topic-chip {
            border-radius: 999px;
            border-color: rgba(var(--bs-body-color-rgb), 0.12);
            color: rgba(var(--bs-body-color-rgb), 0.7);
        }

        .help-support-form .form-control,
        .help-support-form .form-select {
            border-radius: 0.95rem;
        }

        .help-support-form textarea.form-control {
            min-height: 8rem;
        }

        .help-support-tips {
            display: grid;
            gap: 0.5rem;
            padding-top: 0.25rem;
        }

        .help-support-tips li {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            color: rgba(var(--bs-body-color-rgb), 0.62);
        }

        .help-support-tips i {
            margin-top: 0.1rem;
            color: rgba(var(--bs-body-color-rgb), 0.45);
        }

        .help-side-card {
            position: sticky;
            top: 1.5rem;
        }

        .help-ticket-card .btn-icon {
            border-radius: 0.85rem;
        }

        .help-ticket-list .list-group-item {
            border-radius: 0.9rem;
            margin-bottom: 0.75rem;
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
            padding: 0.9rem 1rem;
            background: transparent;
        }

        .help-empty-card {
            padding: 0.25rem 0;
            border: 0;
            background: transparent;
        }

        .help-empty-icon {
            display: none;
        }

        .help-ticket-bubble {
            max-width: min(100%, 34rem);
            border-radius: 1rem;
        }

        .help-ticket-bubble--support {
            background: rgba(var(--bs-body-color-rgb), 0.06);
            color: var(--bs-body-color);
        }

        .help-ticket-bubble--user {
            background: rgba(var(--bs-primary-rgb), 0.92);
            color: #fff;
        }

        html[data-bs-theme="dark"] .help-section-card {
            background: rgba(18, 24, 38, 0.92);
        }

        html[data-bs-theme="dark"] .help-eyebrow,
        html[data-bs-theme="dark"] .help-support-note,
        html[data-bs-theme="dark"] .help-form-hint {
            background: rgba(255, 255, 255, 0.05);
        }

        html[data-bs-theme="dark"] .help-ticket-bubble--support {
            background: rgba(255, 255, 255, 0.07);
        }

        @media (max-width: 767.98px) {
            .help-hero-meta {
                align-items: stretch;
            }
        }

        @media (max-width: 1199.98px) {
            .help-side-card {
                position: static;
            }
        }
    </style>

    <div class="help-page">
        <section class="help-hero mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-column flex-xl-row align-items-xl-start justify-content-between gap-4">
                    <div class="d-flex flex-column gap-3 help-intro">
                        <span class="help-eyebrow">
                            <i class="ri ri-lifebuoy-line text-primary"></i>
                            Veloria Help
                        </span>
                        <div>
                            <h4 class="mb-1">{{ __('help.title') }}</h4>
                            <p class="text-muted mb-0">Опишите ситуацию, и мы ответим здесь. Ничего искать не нужно.</p>
                        </div>
                    </div>

                    <div class="help-hero-meta">
                        <div class="help-support-note" id="help-support-response-time">
                            <i class="ri ri-time-line"></i>
                            <span id="help-support-response-time-text">Обычно отвечаем в рабочие часы</span>
                        </div>
                        <div class="help-support-note">
                            <i class="ri ri-mail-send-line"></i>
                            <span>Ответим в приложении и на почту</span>
                        </div>
                        <div class="help-support-note">
                            <i class="ri ri-calendar-schedule-line"></i>
                            <span id="help-support-working-hours-chip">Будни с 09:00 до 21:00</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="row g-4 align-items-start">
            <div class="col-12 col-xl-8">
                <div class="card help-section-card" id="help-support-panel">
                    <div class="card-body p-4 p-lg-5">
                        <div class="mb-4">
                            <h4 class="mb-2">{{ __('help.support.title') }}</h4>
                            <p class="text-muted mb-0">{{ __('help.support.subtitle') }}</p>
                        </div>

                        <div class="help-form-hint p-3 mb-4">
                            <div class="small text-muted">
                                Коротко опишите, что случилось, чего вы ожидали и, если нужно, приложите скриншот.
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <button type="button" class="btn btn-sm btn-outline-secondary help-topic-chip" data-help-subject="Проблема с напоминаниями">Напоминания</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary help-topic-chip" data-help-subject="Вопрос по календарю">Календарь</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary help-topic-chip" data-help-subject="Не понимаю тариф">Тариф</button>
                        </div>

                        <div id="help-support-alert" class="alert alert-success d-none" role="alert"></div>
                        <form id="help-support-form" class="d-flex flex-column gap-3 help-support-form" enctype="multipart/form-data">
                            <div>
                                <label for="help-subject" class="form-label">{{ __('help.support.form.subject_label') }}</label>
                                <input type="text" class="form-control" id="help-subject" name="subject" placeholder="{{ __('help.support.form.subject_placeholder') }}" required />
                            </div>
                            <div>
                                <label for="help-message" class="form-label">{{ __('help.support.form.message_label') }}</label>
                                <textarea class="form-control" id="help-message" name="message" rows="5" placeholder="{{ __('help.support.form.message_placeholder') }}" required></textarea>
                            </div>
                            <div>
                                <label for="help-attachment" class="form-label">{{ __('help.support.form.attachment_label') }}</label>
                                <input type="file" class="form-control" id="help-attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt,.csv" />
                                <div class="form-text" id="help-support-working-hours"></div>
                            </div>
                            <ul id="help-support-tips" class="list-unstyled small text-muted mb-0 help-support-tips"></ul>
                            <div class="pt-2">
                                <button type="submit" class="btn btn-primary px-4" id="help-support-submit">
                                    <span class="spinner-border spinner-border-sm align-middle me-2 d-none" role="status" id="help-support-spinner"></span>
                                    Отправить сообщение
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="d-flex flex-column gap-4 help-side-card">
                    <div class="card help-section-card help-ticket-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <h5 class="mb-2">{{ __('help.tickets.title') }}</h5>
                                    <p class="text-muted small mb-0">Здесь появятся ваши сообщения и ответы команды.</p>
                                </div>
                                <button class="btn btn-icon btn-outline-secondary" type="button" id="help-refresh-tickets" title="Обновить список">
                                    <i class="ri ri-refresh-line"></i>
                                </button>
                            </div>

                            <div id="help-tickets-alert" class="alert alert-danger d-none" role="alert"></div>
                            <div id="help-tickets-empty" class="help-empty-card d-none">
                                <div class="text-muted small">
                                    Пока обращений нет. Когда вы напишете в поддержку, переписка появится здесь.
                                </div>
                            </div>
                            <div id="help-tickets-list" class="list-group list-group-flush help-ticket-list"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="helpTicketModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="helpTicketModalLabel"></h5>
                        <div class="text-muted small" id="helpTicketModalMeta"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="help-ticket-messages" class="d-flex flex-column gap-3"></div>
                </div>
                <div class="modal-footer">
                    <form id="help-ticket-message-form" class="w-100" enctype="multipart/form-data">
                        <div class="row g-3 align-items-end">
                            <div class="col-12">
                                <label for="help-reply-message" class="form-label">{{ __('help.support.form.message_label') }}</label>
                                <textarea class="form-control" id="help-reply-message" name="message" rows="3" placeholder="{{ __('help.support.form.message_placeholder') }}" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="help-reply-attachment" class="form-label">{{ __('help.support.form.attachment_label') }}</label>
                                <input type="file" class="form-control" id="help-reply-attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt,.csv" />
                            </div>
                            <div class="col-md-6 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary ms-auto" id="help-reply-submit">
                                    <span class="spinner-border spinner-border-sm align-middle me-2 d-none" role="status" id="help-reply-spinner"></span>
                                    Отправить ответ
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const locale = document.documentElement.lang || 'ru';
            const responseTimeBadge = document.getElementById('help-support-response-time');
            const responseTimeText = document.getElementById('help-support-response-time-text');
            const workingHoursHint = document.getElementById('help-support-working-hours');
            const workingHoursChip = document.getElementById('help-support-working-hours-chip');
            const supportTipsList = document.getElementById('help-support-tips');
            const supportForm = document.getElementById('help-support-form');
            const supportAlert = document.getElementById('help-support-alert');
            const supportSubmit = document.getElementById('help-support-submit');
            const supportSpinner = document.getElementById('help-support-spinner');
            const supportAttachmentInput = document.getElementById('help-attachment');
            const supportSubjectInput = document.getElementById('help-subject');
            const supportMessageInput = document.getElementById('help-message');
            const supportTopicButtons = document.querySelectorAll('[data-help-subject]');
            const ticketsList = document.getElementById('help-tickets-list');
            const ticketsEmpty = document.getElementById('help-tickets-empty');
            const ticketsAlert = document.getElementById('help-tickets-alert');
            const refreshTicketsButton = document.getElementById('help-refresh-tickets');
            const ticketModalEl = document.getElementById('helpTicketModal');
            const ticketModal = new bootstrap.Modal(ticketModalEl);
            const ticketModalLabel = document.getElementById('helpTicketModalLabel');
            const ticketModalMeta = document.getElementById('helpTicketModalMeta');
            const ticketMessagesContainer = document.getElementById('help-ticket-messages');
            const ticketMessageForm = document.getElementById('help-ticket-message-form');
            const ticketReplySpinner = document.getElementById('help-reply-spinner');
            const ticketReplySubmit = document.getElementById('help-reply-submit');
            const ticketReplyAttachment = document.getElementById('help-reply-attachment');
            const ticketReplyMessage = document.getElementById('help-reply-message');

            const translations = {
                alerts: {
                    loadError: @json(__('help.alerts.load_error')),
                    ticketLoadError: @json(__('help.alerts.ticket_load_error')),
                    ticketSubmitError: @json(__('help.alerts.ticket_submit_error')),
                    attachmentTooLarge: @json(__('help.alerts.attachment_too_large')),
                    attachmentType: @json(__('help.alerts.attachment_type')),
                },
                messages: {
                    none: @json(__('help.tickets.messages.no_messages')),
                },
                statuses: {
                    open: @json(__('help.tickets.statuses.open')),
                    waiting: @json(__('help.tickets.statuses.waiting')),
                    responded: @json(__('help.tickets.statuses.responded')),
                    closed: @json(__('help.tickets.statuses.closed')),
                },
            };

            const statusStyles = {
                open: 'badge bg-label-primary',
                waiting: 'badge bg-label-warning',
                responded: 'badge bg-label-success',
                closed: 'badge bg-label-secondary',
            };

            function getCookie(name) {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) return parts.pop().split(';').shift();
                return null;
            }

            function authHeaders() {
                const headers = { Accept: 'application/json' };
                const token = getCookie('token');
                if (token) {
                    headers.Authorization = 'Bearer ' + token;
                }
                return headers;
            }

            function escapeHtml(value) {
                if (value === null || value === undefined) {
                    return '';
                }

                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function formatDate(value) {
                if (!value) return '';
                try {
                    const date = new Date(value);
                    return new Intl.DateTimeFormat(locale, {
                        dateStyle: 'medium',
                        timeStyle: 'short',
                    }).format(date);
                } catch (e) {
                    return value;
                }
            }

            function setSupportInfo(data) {
                if (responseTimeText || responseTimeBadge) {
                    (responseTimeText || responseTimeBadge).textContent = data.response_time_text || 'Обычно отвечаем в рабочие часы';
                }
                workingHoursHint.textContent = data.working_hours || '';
                if (workingHoursChip) {
                    workingHoursChip.textContent = data.working_hours || 'Будни с 09:00 до 21:00';
                }
                supportTipsList.innerHTML = '';

                (data.tips || []).forEach(function (tip) {
                    const li = document.createElement('li');
                    li.innerHTML = `<i class="ri ri-information-line"></i><span>${escapeHtml(tip)}</span>`;
                    supportTipsList.appendChild(li);
                });
            }

            function renderTickets(tickets) {
                ticketsList.innerHTML = '';

                if (!tickets.length) {
                    ticketsList.classList.add('d-none');
                    ticketsEmpty.classList.remove('d-none');
                    return;
                }

                ticketsList.classList.remove('d-none');
                ticketsEmpty.classList.add('d-none');

                tickets.forEach(function (ticket) {
                    const badgeClass = statusStyles[ticket.status] || 'badge bg-label-secondary';
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-start gap-3';
                    item.dataset.ticketId = ticket.id;
                    item.innerHTML = `
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">${escapeHtml(ticket.subject)}</span>
                                <span class="${badgeClass}">${escapeHtml(translations.statuses[ticket.status] || ticket.status)}</span>
                            </div>
                            <div class="text-muted small">${ticket.last_message_at ? formatDate(ticket.last_message_at) : ''}</div>
                        </div>
                        <i class="ri ri-arrow-right-s-line"></i>`;
                    item.addEventListener('click', function () {
                        loadTicket(ticket.id);
                    });
                    ticketsList.appendChild(item);
                });
            }

            function renderTicketConversation(ticket) {
                ticketModalLabel.textContent = ticket.subject;
                ticketModalMeta.textContent = `${escapeHtml(translations.statuses[ticket.status] || ticket.status)} · ${formatDate(ticket.updated_at)}`;
                ticketMessagesContainer.innerHTML = '';

                const messages = ticket.messages || [];

                if (!messages.length) {
                    const emptyState = document.createElement('div');
                    emptyState.className = 'text-muted small';
                    emptyState.textContent = translations.messages.none;
                    ticketMessagesContainer.appendChild(emptyState);
                } else {
                    messages.forEach(function (message) {
                        const wrapper = document.createElement('div');
                        wrapper.className = `d-flex flex-column ${message.from_current_user ? 'align-items-end' : 'align-items-start'}`;
                        const bubble = document.createElement('div');
                        bubble.className = `p-3 shadow-sm help-ticket-bubble ${message.from_current_user ? 'help-ticket-bubble--user' : 'help-ticket-bubble--support'}`;
                        bubble.innerHTML = `
                            <div class="small fw-semibold mb-1">${escapeHtml(message.sender_label)}</div>
                            <div class="mb-2">${message.body ? escapeHtml(message.body) : ''}</div>
                            <div class="d-flex flex-column gap-1 small">
                                ${message.attachment_url ? `<a class="${message.from_current_user ? 'link-light' : ''}" href="${escapeHtml(message.attachment_url)}" target="_blank" rel="noopener">${escapeHtml(message.attachment_name || 'attachment')}</a>` : ''}
                                <span class="opacity-75">${escapeHtml(message.created_at_human || formatDate(message.created_at))}</span>
                            </div>`;
                        wrapper.appendChild(bubble);
                        ticketMessagesContainer.appendChild(wrapper);
                    });
                }

                ticketMessageForm.dataset.ticketId = ticket.id;
                ticketReplyMessage.value = '';
                ticketReplyAttachment.value = '';
                ticketModal.show();
            }

            function setLoading(button, spinner, loading) {
                if (!button || !spinner) return;
                button.disabled = loading;
                spinner.classList.toggle('d-none', !loading);
            }

            function showSupportAlert(message, type) {
                supportAlert.className = 'alert alert-' + type;
                supportAlert.textContent = message;
                supportAlert.classList.remove('d-none');
            }

            function loadSupportInfo() {
                fetch('/api/v1/help/overview', { headers: authHeaders() })
                    .then(function (response) {
                        if (!response.ok) throw new Error('Failed');
                        return response.json();
                    })
                    .then(function (data) {
                        setSupportInfo(data.data?.support || {});
                    })
                    .catch(function () {
                        if (responseTimeText || responseTimeBadge) {
                            (responseTimeText || responseTimeBadge).textContent = 'Обычно отвечаем в рабочие часы';
                        }
                    });
            }

            function loadTickets() {
                ticketsAlert.classList.add('d-none');
                fetch('/api/v1/help/tickets', { headers: authHeaders() })
                    .then(function (response) {
                        if (!response.ok) throw new Error('Failed');
                        return response.json();
                    })
                    .then(function (data) {
                        renderTickets(data.data || []);
                    })
                    .catch(function () {
                        ticketsAlert.classList.remove('d-none');
                        ticketsAlert.textContent = translations.alerts.ticketLoadError;
                    });
            }

            function loadTicket(id) {
                ticketMessagesContainer.innerHTML = '<div class="text-muted small">Загрузка...</div>';
                fetch('/api/v1/help/tickets/' + id, { headers: authHeaders() })
                    .then(function (response) {
                        if (!response.ok) throw new Error('Failed');
                        return response.json();
                    })
                    .then(function (data) {
                        renderTicketConversation(data.data || {});
                        loadTickets();
                    })
                    .catch(function () {
                        ticketsAlert.classList.remove('d-none');
                        ticketsAlert.textContent = translations.alerts.ticketLoadError;
                    });
            }

            const allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt', 'csv'];

            supportAttachmentInput.addEventListener('change', function () {
                const file = supportAttachmentInput.files[0];
                if (!file) return;
                const extension = file.name.split('.').pop().toLowerCase();
                if (!allowedExtensions.includes(extension)) {
                    showSupportAlert(translations.alerts.attachmentType, 'warning');
                    supportAttachmentInput.value = '';
                    return;
                }
                if (file.size > 10 * 1024 * 1024) {
                    showSupportAlert(translations.alerts.attachmentTooLarge, 'warning');
                    supportAttachmentInput.value = '';
                }
            });

            ticketReplyAttachment.addEventListener('change', function () {
                const file = ticketReplyAttachment.files[0];
                if (!file) return;
                const extension = file.name.split('.').pop().toLowerCase();
                if (!allowedExtensions.includes(extension)) {
                    ticketReplyAttachment.value = '';
                    ticketsAlert.classList.remove('d-none');
                    ticketsAlert.textContent = translations.alerts.attachmentType;
                    return;
                }
                if (file.size > 10 * 1024 * 1024) {
                    ticketReplyAttachment.value = '';
                    ticketsAlert.classList.remove('d-none');
                    ticketsAlert.textContent = translations.alerts.attachmentTooLarge;
                }
            });

            supportForm.addEventListener('submit', function (event) {
                event.preventDefault();
                supportAlert.classList.add('d-none');
                setLoading(supportSubmit, supportSpinner, true);

                const formData = new FormData(supportForm);
                fetch('/api/v1/help/tickets', {
                    method: 'POST',
                    headers: authHeaders(),
                    body: formData,
                })
                    .then(function (response) {
                        if (response.status === 422) {
                            return response.json().then(function (data) {
                                const message = data?.error?.message || translations.alerts.ticketSubmitError;
                                showSupportAlert(message, 'danger');
                                throw new Error('Validation');
                            });
                        }
                        if (!response.ok) throw new Error('Failed');
                        return response.json();
                    })
                    .then(function (data) {
                        showSupportAlert(data.message || '{{ __('help.support.form.success') }}', 'success');
                        supportForm.reset();
                        loadTickets();
                        if (data.data?.id) {
                            loadTicket(data.data.id);
                        }
                    })
                    .catch(function (error) {
                        if (error.message !== 'Validation') {
                            showSupportAlert(translations.alerts.ticketSubmitError, 'danger');
                        }
                    })
                    .finally(function () {
                        setLoading(supportSubmit, supportSpinner, false);
                    });
            });

            ticketMessageForm.addEventListener('submit', function (event) {
                event.preventDefault();
                const ticketId = ticketMessageForm.dataset.ticketId;
                if (!ticketId) {
                    return;
                }
                setLoading(ticketReplySubmit, ticketReplySpinner, true);
                const formData = new FormData(ticketMessageForm);
                fetch(`/api/v1/help/tickets/${ticketId}/messages`, {
                    method: 'POST',
                    headers: authHeaders(),
                    body: formData,
                })
                    .then(function (response) {
                        if (response.status === 422) {
                            return response.json().then(function () {
                                ticketsAlert.classList.remove('d-none');
                                ticketsAlert.textContent = translations.alerts.ticketSubmitError;
                                throw new Error('Validation');
                            });
                        }
                        if (!response.ok) throw new Error('Failed');
                        return response.json();
                    })
                    .then(function (data) {
                        if (data.data) {
                            renderTicketConversation(data.data);
                        }
                        loadTickets();
                    })
                    .catch(function (error) {
                        if (error.message !== 'Validation') {
                            ticketsAlert.classList.remove('d-none');
                            ticketsAlert.textContent = translations.alerts.ticketSubmitError;
                        }
                    })
                    .finally(function () {
                        setLoading(ticketReplySubmit, ticketReplySpinner, false);
                    });
            });

            refreshTicketsButton.addEventListener('click', function () {
                loadTickets();
            });

            supportTopicButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    supportSubjectInput.value = button.dataset.helpSubject || '';
                    if (supportMessageInput && !supportMessageInput.value.trim()) {
                        supportMessageInput.focus();
                    } else {
                        supportSubjectInput.focus();
                    }
                    supportSubjectInput.dispatchEvent(new Event('input', { bubbles: true }));
                });
            });

            loadSupportInfo();
            loadTickets();
        });
    </script>
@endsection
