@extends('layouts.app')

@section('title', __('help.title'))

@section('content')
    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="d-flex flex-column gap-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
                            <div>
                                <h4 class="mb-1">{{ __('help.knowledge_base.title') }}</h4>
                                <p class="text-muted mb-0">{{ __('help.knowledge_base.subtitle') }}</p>
                            </div>
                            <span class="badge bg-label-info rounded-pill px-3 py-2" id="help-support-response-time"></span>
                        </div>
                        <div id="help-knowledge-alert" class="alert alert-danger d-none" role="alert"></div>
                        <div id="knowledge-base-list" class="row g-3"></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-1">{{ __('help.faq.title') }}</h4>
                        <p class="text-muted mb-3">{{ __('help.faq.subtitle') }}</p>
                        <div id="help-faq" class="accordion"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="d-flex flex-column gap-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-1">{{ __('help.support.title') }}</h4>
                        <p class="text-muted mb-4">{{ __('help.support.subtitle') }}</p>
                        <div id="help-support-alert" class="alert alert-success d-none" role="alert"></div>
                        <form id="help-support-form" class="d-flex flex-column gap-3" enctype="multipart/form-data">
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
                            <ul id="help-support-tips" class="list-unstyled small text-muted mb-0"></ul>
                            <button type="submit" class="btn btn-primary" id="help-support-submit">
                                <span class="spinner-border spinner-border-sm align-middle me-2 d-none" role="status" id="help-support-spinner"></span>
                                {{ __('help.support.form.submit') }}
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h4 class="mb-1">{{ __('help.tickets.title') }}</h4>
                                <p class="text-muted mb-0" id="help-tickets-subtitle"></p>
                            </div>
                            <button class="btn btn-icon btn-outline-secondary" type="button" id="help-refresh-tickets" title="Refresh">
                                <i class="ri ri-refresh-line"></i>
                            </button>
                        </div>
                        <div id="help-tickets-alert" class="alert alert-danger d-none" role="alert"></div>
                        <div id="help-tickets-empty" class="text-muted small d-none">{{ __('help.tickets.empty') }}</div>
                        <div id="help-tickets-list" class="list-group list-group-flush"></div>
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
                                    {{ __('help.support.form.submit') }}
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
            const knowledgeContainer = document.getElementById('knowledge-base-list');
            const knowledgeAlert = document.getElementById('help-knowledge-alert');
            const faqContainer = document.getElementById('help-faq');
            const responseTimeBadge = document.getElementById('help-support-response-time');
            const workingHoursHint = document.getElementById('help-support-working-hours');
            const supportTipsList = document.getElementById('help-support-tips');
            const supportForm = document.getElementById('help-support-form');
            const supportAlert = document.getElementById('help-support-alert');
            const supportSubmit = document.getElementById('help-support-submit');
            const supportSpinner = document.getElementById('help-support-spinner');
            const supportAttachmentInput = document.getElementById('help-attachment');
            const ticketsList = document.getElementById('help-tickets-list');
            const ticketsEmpty = document.getElementById('help-tickets-empty');
            const ticketsAlert = document.getElementById('help-tickets-alert');
            const refreshTicketsButton = document.getElementById('help-refresh-tickets');
            const ticketsSubtitle = document.getElementById('help-tickets-subtitle');
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
                knowledgeCta: @json(__('help.knowledge_base.cta')),
                ticketView: @json(__('help.tickets.view')),
                ticketsSubtitle: @json(__('help.subtitle')),
                messages: {
                    none: @json(__('help.tickets.messages.no_messages')),
                    fromSupport: @json(__('help.tickets.messages.from_support')),
                    fromYou: @json(__('help.tickets.messages.from_you')),
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

            ticketsSubtitle.textContent = translations.ticketsSubtitle;

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

            function renderKnowledgeBase(items) {
                knowledgeContainer.innerHTML = '';
                if (!items.length) {
                    knowledgeAlert.classList.remove('d-none');
                    knowledgeAlert.textContent = translations.alerts.loadError;
                    return;
                }
                knowledgeAlert.classList.add('d-none');
                items.forEach(function (item) {
                    const col = document.createElement('div');
                    col.className = 'col-12 col-md-6';
                    col.innerHTML = `
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex align-items-start gap-3">
                                <div class="avatar flex-shrink-0 bg-label-primary rounded"><i class="ri ${escapeHtml(item.icon)} icon-base p-2"></i></div>
                                <div>
                                    <h6 class="mb-1">${escapeHtml(item.title)}</h6>
                                    <p class="text-muted small mb-2">${escapeHtml(item.description)}</p>
                                    <a class="btn btn-sm btn-outline-primary" href="${escapeHtml(item.url)}" target="_blank" rel="noopener">${escapeHtml(translations.knowledgeCta)}</a>
                                </div>
                            </div>
                        </div>`;
                    knowledgeContainer.appendChild(col);
                });
            }

            function renderFaq(items) {
                faqContainer.innerHTML = '';
                if (!items.length) {
                    faqContainer.innerHTML = `<div class="text-muted small">${escapeHtml(translations.alerts.loadError)}</div>`;
                    return;
                }
                items.forEach(function (item, index) {
                    const id = `faq-item-${index}`;
                    const element = document.createElement('div');
                    element.className = 'accordion-item';
                    element.innerHTML = `
                        <h2 class="accordion-header" id="${id}-header">
                            <button class="accordion-button ${index !== 0 ? 'collapsed' : ''}" type="button" data-bs-toggle="collapse" data-bs-target="#${id}-body" aria-expanded="${index === 0}">
                                ${escapeHtml(item.question)}
                            </button>
                        </h2>
                        <div id="${id}-body" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" aria-labelledby="${id}-header" data-bs-parent="#help-faq">
                            <div class="accordion-body">${escapeHtml(item.answer)}</div>
                        </div>`;
                    faqContainer.appendChild(element);
                });
            }

            function setSupportInfo(data) {
                responseTimeBadge.textContent = data.response_time_text || '';
                workingHoursHint.textContent = data.working_hours || '';
                supportTipsList.innerHTML = '';
                (data.tips || []).forEach(function (tip) {
                    const li = document.createElement('li');
                    li.innerHTML = `<i class="ri ri-information-line me-2"></i>${escapeHtml(tip)}`;
                    supportTipsList.appendChild(li);
                });
            }

            function renderTickets(tickets) {
                ticketsList.innerHTML = '';
                if (!tickets.length) {
                    ticketsEmpty.classList.remove('d-none');
                    return;
                }
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
                        bubble.className = `p-3 rounded-3 shadow-sm ${message.from_current_user ? 'bg-primary text-white' : 'bg-light'}`;
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

            function loadOverview() {
                fetch('/api/v1/help/overview', { headers: authHeaders() })
                    .then(function (response) {
                        if (!response.ok) throw new Error('Failed');
                        return response.json();
                    })
                    .then(function (data) {
                        const payload = data.data || {};
                        renderKnowledgeBase(payload.knowledge_base || []);
                        renderFaq(payload.faqs || []);
                        setSupportInfo(payload.support || {});
                    })
                    .catch(function () {
                        knowledgeAlert.classList.remove('d-none');
                        knowledgeAlert.textContent = translations.alerts.loadError;
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
                ticketMessagesContainer.innerHTML = '<div class="text-muted small">Loading…</div>';
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

            loadOverview();
            loadTickets();
        });
    </script>
@endsection
