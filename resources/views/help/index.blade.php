@extends('layouts.app')

@section('title', __('help.title'))

@section('content')
    <style>
        .help-page {
            max-width: 1120px;
            margin: 0 auto;
            /* The instruction on how to write a ticket ran at 2.4:1 — the least
               readable text on a page whose only job is to be read. */
            --help-text: color-mix(in srgb, var(--bs-heading-color) 82%, transparent);
            --help-faint: color-mix(in srgb, var(--bs-heading-color) 74%, transparent);
            --help-line: color-mix(in srgb, var(--bs-border-color) 70%, transparent);
        }

        .help-page [hidden] {
            display: none !important;
        }

        .help-hero {
            border: 1px solid var(--help-line);
            border-radius: 1rem;
            background: rgba(var(--bs-primary-rgb), 0.04);
        }

        .help-section-card {
            border: 1px solid var(--help-line);
            border-radius: 1rem;
            background: var(--bs-card-bg);
            box-shadow: none;
        }

        .help-intro {
            max-width: 44rem;
        }

        .help-intro p,
        .help-section-card > .card-body > .mb-4 > p {
            color: var(--help-text);
        }

        .help-hero-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            align-items: flex-start;
        }

        .help-support-note {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.4rem 0.75rem;
            border-radius: 999px;
            background: color-mix(in srgb, var(--bs-heading-color) 6%, transparent);
            color: var(--help-text);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .help-support-note i {
            color: var(--help-faint);
        }

        /* Questions above the form: the section used to offer nothing but an
           empty textarea and a twelve-hour wait. */
        .help-faq details {
            border: 1px solid var(--help-line);
            border-radius: 0.75rem;
            background: var(--bs-card-bg);
        }

        .help-faq details + details {
            margin-top: 0.5rem;
        }

        .help-faq summary {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.9rem 1rem;
            list-style: none;
            cursor: pointer;
            font-weight: 600;
            color: var(--help-text);
        }

        .help-faq summary::-webkit-details-marker {
            display: none;
        }

        .help-faq summary:focus-visible {
            outline: 2px solid var(--bs-primary);
            outline-offset: -2px;
        }

        .help-faq summary i {
            flex: 0 0 auto;
            color: var(--help-faint);
            transition: transform 0.2s ease;
        }

        .help-faq details[open] summary i {
            transform: rotate(180deg);
        }

        .help-faq__answer {
            padding: 0 1rem 1rem;
            color: var(--help-text);
            font-size: 0.92rem;
        }

        .help-form-hint {
            border-radius: 0.75rem;
            padding: 0.75rem 0.9rem;
            background: color-mix(in srgb, var(--bs-heading-color) 4%, transparent);
            color: var(--help-text);
            font-size: 0.9rem;
        }

        .help-topic-chip {
            border-radius: 999px;
            border: 1px solid var(--help-line);
            color: var(--help-text);
            background: transparent;
        }

        .help-topic-chip:hover {
            border-color: color-mix(in srgb, var(--bs-heading-color) 40%, transparent);
            color: var(--bs-heading-color);
            background: color-mix(in srgb, var(--bs-heading-color) 5%, transparent);
        }

        .help-support-form .form-control,
        .help-support-form .form-select {
            border-radius: 0.75rem;
        }

        .help-support-form textarea.form-control {
            min-height: 8rem;
        }

        .help-field-hint {
            display: block;
            margin-top: 0.3rem;
            font-size: 0.82rem;
            color: var(--help-faint);
        }

        .help-support-tips {
            display: grid;
            gap: 0.4rem;
            padding-top: 0.25rem;
        }

        .help-support-tips li {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            color: var(--help-text);
        }

        .help-support-tips i {
            margin-top: 0.15rem;
            color: var(--help-faint);
        }

        /* The native control announced «Choose File / No file chosen» in the
           middle of a Russian form. */
        .help-file {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.6rem;
        }

        .help-file input[type="file"] {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .help-file__name {
            font-size: 0.88rem;
            color: var(--help-faint);
            word-break: break-all;
        }

        .help-file input[type="file"]:focus-visible + .help-file__button {
            outline: 2px solid var(--bs-primary);
            outline-offset: 2px;
        }

        .help-side-card {
            position: sticky;
            /* 1.5rem put the card under the fixed navbar. */
            top: 5.5rem;
        }

        .help-ticket-card .btn-icon {
            border-radius: 0.75rem;
        }

        .help-ticket-list .list-group-item {
            border-radius: 0.75rem;
            margin-bottom: 0.5rem;
            border: 1px solid var(--help-line);
            padding: 0.85rem 0.95rem;
            background: transparent;
        }

        /* The subject, the badge and the date used to fight for one row in a
           385px column. */
        .help-ticket-item {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            width: 100%;
            text-align: left;
        }

        .help-ticket-item__subject {
            font-weight: 600;
            color: var(--help-text);
        }

        .help-ticket-item__meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.82rem;
            color: var(--help-faint);
        }

        .help-empty-card {
            padding: 0.25rem 0;
            border: 0;
            background: transparent;
            color: var(--help-faint);
        }

        .help-ticket-bubble {
            max-width: min(100%, 34rem);
            border-radius: 1rem;
        }

        .help-ticket-bubble--support {
            background: color-mix(in srgb, var(--bs-heading-color) 7%, transparent);
            color: var(--bs-body-color);
        }

        .help-ticket-bubble--user {
            background: rgba(var(--bs-primary-rgb), 0.92);
            color: #fff;
        }

        @media (max-width: 1199.98px) {
            .help-side-card {
                position: static;
            }

            /* Once there is a conversation to read, it stops living below the
               form that starts a new one. */
            .help-page.has-tickets .help-tickets-col {
                order: -1;
            }
        }
    </style>

    <div class="help-page" id="help-page">
        <section class="help-hero mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-column flex-xl-row align-items-xl-start justify-content-between gap-4">
                    <div class="help-intro">
                        <h1 class="h4 mb-1">{{ __('help.title') }}</h1>
                        <p class="mb-0">{{ __('help.subtitle') }}</p>
                    </div>

                    <div class="help-hero-meta">
                        <div class="help-support-note" id="help-support-response-time">
                            <i class="ri ri-time-line"></i>
                            <span id="help-support-response-time-text">{{ __('help.support.response_time', ['hours' => config('help.support.response_time_hours', 12)]) }}</span>
                        </div>
                        <div class="help-support-note">
                            <i class="ri ri-mail-send-line"></i>
                            <span>{{ __('help.support.working_hours') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card help-section-card help-faq mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="mb-4">
                    <h2 class="h5 mb-2">{{ __('help.faq.title') }}</h2>
                    <p class="mb-0">{{ __('help.faq.subtitle') }}</p>
                </div>
                <div id="help-faq-list"></div>
            </div>
        </section>

        <div class="row g-4 align-items-start">
            <div class="col-12 col-xl-8">
                <div class="card help-section-card" id="help-support-panel">
                    <div class="card-body p-4 p-lg-5">
                        <div class="mb-4">
                            <h2 class="h5 mb-2">{{ __('help.support.title') }}</h2>
                            <p class="mb-0">{{ __('help.support.subtitle') }}</p>
                        </div>

                        <div class="help-form-hint mb-4">
                            Коротко опишите, что случилось, чего вы ожидали и, если нужно, приложите скриншот.
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <button type="button" class="btn btn-sm help-topic-chip" data-help-subject="Проблема с напоминаниями">Напоминания</button>
                            <button type="button" class="btn btn-sm help-topic-chip" data-help-subject="Вопрос по календарю">Календарь</button>
                            <button type="button" class="btn btn-sm help-topic-chip" data-help-subject="Не понимаю тариф">Тариф</button>
                        </div>

                        <div id="help-support-alert" class="alert d-none" role="alert"></div>

                        <form id="help-support-form" class="d-flex flex-column gap-3 help-support-form" enctype="multipart/form-data" novalidate>
                            <div>
                                <label for="help-subject" class="form-label">{{ __('help.support.form.subject_label') }}</label>
                                <input type="text" class="form-control" id="help-subject" name="subject" placeholder="{{ __('help.support.form.subject_placeholder') }}" />
                                <span class="invalid-feedback" data-error-for="subject"></span>
                            </div>
                            <div>
                                <label for="help-message" class="form-label">{{ __('help.support.form.message_label') }}</label>
                                <textarea class="form-control" id="help-message" name="message" rows="5" placeholder="{{ __('help.support.form.message_placeholder') }}"></textarea>
                                <span class="invalid-feedback" data-error-for="message"></span>
                                <small class="help-field-hint" id="help-message-hint"></small>
                            </div>
                            <div>
                                <label for="help-attachment" class="form-label">{{ __('help.support.form.attachment_label') }}</label>
                                <div class="help-file">
                                    <input type="file" id="help-attachment" name="attachment" />
                                    <label class="btn btn-outline-secondary btn-sm help-file__button" for="help-attachment">
                                        <i class="ri ri-attachment-2 me-1"></i>{{ __('help.support.form.attachment_choose') }}
                                    </label>
                                    <span class="help-file__name" data-file-name-for="help-attachment">{{ __('help.support.form.attachment_empty') }}</span>
                                    <button type="button" class="btn btn-text-secondary btn-sm" data-file-clear-for="help-attachment" hidden>{{ __('help.support.form.attachment_clear') }}</button>
                                </div>
                                <span class="invalid-feedback d-block" data-error-for="attachment"></span>
                                <small class="help-field-hint" id="help-attachment-hint"></small>
                            </div>
                            <ul id="help-support-tips" class="list-unstyled small mb-0 help-support-tips"></ul>
                            <div class="pt-2 d-flex flex-wrap align-items-center gap-3">
                                <button type="submit" class="btn btn-primary px-4" id="help-support-submit">
                                    <span class="spinner-border spinner-border-sm align-middle me-2 d-none" role="status" id="help-support-spinner"></span>
                                    {{ __('help.support.form.submit') }}
                                </button>
                                <small class="help-field-hint" id="help-contact-email"></small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4 help-tickets-col">
                <div class="d-flex flex-column gap-4 help-side-card">
                    <div class="card help-section-card help-ticket-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <h2 class="h5 mb-2">{{ __('help.tickets.title') }}</h2>
                                    <p class="help-field-hint mb-0">{{ __('help.tickets.subtitle') }}</p>
                                </div>
                                <button class="btn btn-icon btn-outline-secondary" type="button" id="help-refresh-tickets" title="{{ __('help.tickets.refresh') }}" aria-label="{{ __('help.tickets.refresh') }}">
                                    <i class="ri ri-refresh-line"></i>
                                </button>
                            </div>

                            <div id="help-tickets-alert" class="alert alert-danger d-none" role="alert"></div>
                            <div id="help-tickets-empty" class="help-empty-card d-none">
                                <div class="small">{{ __('help.tickets.empty') }}</div>
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
                        <div class="help-field-hint" id="helpTicketModalMeta"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('help.tickets.view') }}"></button>
                </div>
                <div class="modal-body">
                    <div id="help-ticket-messages" class="d-flex flex-column gap-3"></div>
                </div>
                <div class="modal-footer">
                    <form id="help-ticket-message-form" class="w-100" enctype="multipart/form-data" novalidate>
                        {{-- The answer to a reply used to be painted in the column
                             behind this dialog, where nobody could read it. --}}
                        <div id="help-reply-alert" class="alert d-none" role="alert"></div>
                        <div class="row g-3 align-items-end">
                            <div class="col-12">
                                <label for="help-reply-message" class="form-label">{{ __('help.support.form.message_label') }}</label>
                                <textarea class="form-control" id="help-reply-message" name="message" rows="3" placeholder="{{ __('help.support.form.message_placeholder') }}"></textarea>
                                <small class="help-field-hint" id="help-reply-hint"></small>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">{{ __('help.support.form.attachment_label') }}</label>
                                <div class="help-file">
                                    <input type="file" id="help-reply-attachment" name="attachment" />
                                    <label class="btn btn-outline-secondary btn-sm help-file__button" for="help-reply-attachment">
                                        <i class="ri ri-attachment-2 me-1"></i>{{ __('help.support.form.attachment_choose') }}
                                    </label>
                                    <span class="help-file__name" data-file-name-for="help-reply-attachment">{{ __('help.support.form.attachment_empty') }}</span>
                                    <button type="button" class="btn btn-text-secondary btn-sm" data-file-clear-for="help-reply-attachment" hidden>{{ __('help.support.form.attachment_clear') }}</button>
                                </div>
                            </div>
                            <div class="col-md-5 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary ms-auto" id="help-reply-submit">
                                    <span class="spinner-border spinner-border-sm align-middle me-2 d-none" role="status" id="help-reply-spinner"></span>
                                    {{ __('help.tickets.reply_submit') }}
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
            const page = document.getElementById('help-page');
            const responseTimeText = document.getElementById('help-support-response-time-text');
            const supportTipsList = document.getElementById('help-support-tips');
            const supportForm = document.getElementById('help-support-form');
            const supportAlert = document.getElementById('help-support-alert');
            const supportSubmit = document.getElementById('help-support-submit');
            const supportSpinner = document.getElementById('help-support-spinner');
            const supportAttachmentInput = document.getElementById('help-attachment');
            const supportSubjectInput = document.getElementById('help-subject');
            const supportMessageInput = document.getElementById('help-message');
            const supportTopicButtons = document.querySelectorAll('[data-help-subject]');
            const messageHint = document.getElementById('help-message-hint');
            const attachmentHint = document.getElementById('help-attachment-hint');
            const contactEmail = document.getElementById('help-contact-email');
            const faqList = document.getElementById('help-faq-list');
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
            const replyAlert = document.getElementById('help-reply-alert');
            const replyHint = document.getElementById('help-reply-hint');

            const translations = {
                alerts: {
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
                form: {
                    messageHint: @json(__('help.support.form.message_hint')),
                    attachmentHint: @json(__('help.support.form.attachment_hint')),
                    attachmentEmpty: @json(__('help.support.form.attachment_empty')),
                    contactEmail: @json(__('help.support.contact_email')),
                    success: @json(__('help.support.form.success')),
                    openConversation: @json(__('help.support.form.open_conversation')),
                    replyHint: @json(__('help.tickets.reply_hint')),
                    replySent: @json(__('help.tickets.reply_sent')),
                },
            };

            const statusStyles = {
                open: 'badge bg-label-primary',
                waiting: 'badge bg-label-warning',
                responded: 'badge bg-label-success',
                closed: 'badge bg-label-secondary',
            };

            let limits = { message_min: 10, reply_min: 3 };
            let attachmentRules = { max_mb: 10, extensions: ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt', 'csv'], accept: '' };

            function getCookie(name) {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) return parts.pop().split(';').shift();
                return null;
            }

            function authHeaders() {
                const headers = { Accept: 'application/json', 'Accept-Language': locale };
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

            /* ---------- alerts ---------- */

            function showAlert(element, message, type) {
                element.className = 'alert alert-' + type;
                element.textContent = message;
                element.hidden = false;
                element.classList.remove('d-none');
            }

            function showAlertHtml(element, html, type) {
                element.className = 'alert alert-' + type;
                element.innerHTML = html;
                element.classList.remove('d-none');
            }

            function hideAlert(element) {
                element.classList.add('d-none');
            }

            function clearFieldErrors(form) {
                form.querySelectorAll('[data-error-for]').forEach(function (node) {
                    node.textContent = '';
                });
                form.querySelectorAll('.is-invalid').forEach(function (node) {
                    node.classList.remove('is-invalid');
                });
            }

            /**
             * The page used to drop `error.fields` and print the wrapper text —
             * «Предоставленные данные недействительны» — for every mistake.
             */
            function applyFieldErrors(form, fields) {
                const names = Object.keys(fields || {});
                if (!names.length) return null;

                names.forEach(function (name) {
                    const slot = form.querySelector(`[data-error-for="${name}"]`);
                    const input = form.querySelector(`[name="${name}"]`);

                    if (input) input.classList.add('is-invalid');
                    if (slot) slot.textContent = fields[name][0];
                });

                return fields[names[0]][0];
            }

            /* ---------- rendering ---------- */

            function renderFaq(items) {
                faqList.innerHTML = '';

                (items || []).forEach(function (item) {
                    const details = document.createElement('details');
                    const link = item.link
                        ? `<a class="btn btn-sm btn-outline-primary mt-3" href="${escapeHtml(item.link.url)}">${escapeHtml(item.link.label)}</a>`
                        : '';

                    details.innerHTML = `
                        <summary>
                            <span>${escapeHtml(item.question)}</span>
                            <i class="ri ri-arrow-down-s-line"></i>
                        </summary>
                        <div class="help-faq__answer">
                            <div>${escapeHtml(item.answer)}</div>
                            ${link}
                        </div>`;
                    faqList.appendChild(details);
                });
            }

            function setSupportInfo(data) {
                if (responseTimeText && data.response_time_text) {
                    responseTimeText.textContent = data.response_time_text;
                }

                supportTipsList.innerHTML = '';

                (data.tips || []).forEach(function (tip) {
                    const li = document.createElement('li');
                    li.innerHTML = `<i class="ri ri-information-line"></i><span>${escapeHtml(tip)}</span>`;
                    supportTipsList.appendChild(li);
                });

                if (data.contact_email) {
                    contactEmail.innerHTML = translations.form.contactEmail.replace(
                        ':email',
                        `<a href="mailto:${escapeHtml(data.contact_email)}">${escapeHtml(data.contact_email)}</a>`
                    );
                }
            }

            function setRules(data) {
                limits = data.limits || limits;
                attachmentRules = data.attachment || attachmentRules;

                messageHint.textContent = translations.form.messageHint.replace(':min', limits.message_min);
                replyHint.textContent = translations.form.replyHint.replace(':min', limits.reply_min);
                attachmentHint.textContent = translations.form.attachmentHint
                    .replace(':size', attachmentRules.max_mb)
                    .replace(':formats', (attachmentRules.extensions || []).join(', '));

                if (attachmentRules.accept) {
                    supportAttachmentInput.setAttribute('accept', attachmentRules.accept);
                    ticketReplyAttachment.setAttribute('accept', attachmentRules.accept);
                }
            }

            function renderTickets(tickets) {
                ticketsList.innerHTML = '';
                page.classList.toggle('has-tickets', tickets.length > 0);

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
                    item.className = 'list-group-item list-group-item-action';
                    item.dataset.ticketId = ticket.id;
                    item.innerHTML = `
                        <span class="help-ticket-item">
                            <span class="help-ticket-item__subject">${escapeHtml(ticket.subject)}</span>
                            <span class="help-ticket-item__meta">
                                <span class="${badgeClass}">${escapeHtml(translations.statuses[ticket.status] || ticket.status)}</span>
                                <span>${ticket.last_message_at ? formatDate(ticket.last_message_at) : ''}</span>
                            </span>
                        </span>`;
                    item.addEventListener('click', function () {
                        loadTicket(ticket.id);
                    });
                    ticketsList.appendChild(item);
                });
            }

            function renderTicketConversation(ticket, options) {
                ticketModalLabel.textContent = ticket.subject;
                ticketModalMeta.textContent = `${translations.statuses[ticket.status] || ticket.status} · ${formatDate(ticket.updated_at)}`;
                ticketMessagesContainer.innerHTML = '';

                const messages = ticket.messages || [];

                if (!messages.length) {
                    const emptyState = document.createElement('div');
                    emptyState.className = 'help-field-hint';
                    emptyState.textContent = translations.messages.none;
                    ticketMessagesContainer.appendChild(emptyState);
                } else {
                    messages.forEach(function (message) {
                        const wrapper = document.createElement('div');
                        wrapper.className = `d-flex flex-column ${message.from_current_user ? 'align-items-end' : 'align-items-start'}`;
                        const bubble = document.createElement('div');
                        bubble.className = `p-3 help-ticket-bubble ${message.from_current_user ? 'help-ticket-bubble--user' : 'help-ticket-bubble--support'}`;
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
                resetFileInput(ticketReplyAttachment);

                if (!(options && options.keepAlert)) {
                    hideAlert(replyAlert);
                }

                ticketModal.show();
            }

            function setLoading(button, spinner, loading) {
                if (!button || !spinner) return;
                button.disabled = loading;
                spinner.classList.toggle('d-none', !loading);
            }

            /* ---------- file inputs ---------- */

            function resetFileInput(input) {
                input.value = '';
                updateFileName(input);
            }

            function updateFileName(input) {
                const name = document.querySelector(`[data-file-name-for="${input.id}"]`);
                const clear = document.querySelector(`[data-file-clear-for="${input.id}"]`);
                const file = input.files && input.files[0];

                if (name) name.textContent = file ? file.name : translations.form.attachmentEmpty;
                if (clear) clear.hidden = !file;
            }

            function validateFile(input, alertElement) {
                const file = input.files && input.files[0];
                if (!file) return true;

                const extension = file.name.split('.').pop().toLowerCase();

                if (!(attachmentRules.extensions || []).includes(extension)) {
                    showAlert(alertElement, translations.alerts.attachmentType
                        .replace(':formats', (attachmentRules.extensions || []).join(', ')), 'warning');
                    resetFileInput(input);
                    return false;
                }

                if (file.size > (attachmentRules.max_mb || 10) * 1024 * 1024) {
                    showAlert(alertElement, translations.alerts.attachmentTooLarge
                        .replace(':size', attachmentRules.max_mb), 'warning');
                    resetFileInput(input);
                    return false;
                }

                return true;
            }

            /* ---------- loading ---------- */

            function loadSupportInfo() {
                fetch('/api/v1/help/overview', { headers: authHeaders() })
                    .then(function (response) {
                        if (!response.ok) throw new Error('Failed');
                        return response.json();
                    })
                    .then(function (payload) {
                        const data = payload.data || {};
                        renderFaq(data.faq || []);
                        setSupportInfo(data.support || {});
                        setRules(data);
                    })
                    .catch(function () {
                        // Nothing to do: the page keeps the server-rendered
                        // response time and the built-in limits.
                    });
            }

            function loadTickets() {
                hideAlert(ticketsAlert);

                return fetch('/api/v1/help/tickets', { headers: authHeaders() })
                    .then(function (response) {
                        if (!response.ok) throw new Error('Failed');
                        return response.json();
                    })
                    .then(function (data) {
                        renderTickets(data.data || []);
                    })
                    .catch(function () {
                        showAlert(ticketsAlert, translations.alerts.ticketLoadError, 'danger');
                    });
            }

            function loadTicket(id, options) {
                ticketMessagesContainer.innerHTML = '<div class="help-field-hint">…</div>';
                return fetch('/api/v1/help/tickets/' + id, { headers: authHeaders() })
                    .then(function (response) {
                        if (!response.ok) throw new Error('Failed');
                        return response.json();
                    })
                    .then(function (data) {
                        renderTicketConversation(data.data || {}, options);
                        loadTickets();
                    })
                    .catch(function () {
                        showAlert(ticketsAlert, translations.alerts.ticketLoadError, 'danger');
                    });
            }

            /* ---------- wiring ---------- */

            [supportAttachmentInput, ticketReplyAttachment].forEach(function (input) {
                input.addEventListener('change', function () {
                    updateFileName(input);
                    validateFile(input, input === supportAttachmentInput ? supportAlert : replyAlert);
                });
            });

            document.querySelectorAll('[data-file-clear-for]').forEach(function (button) {
                button.addEventListener('click', function () {
                    resetFileInput(document.getElementById(button.dataset.fileClearFor));
                });
            });

            supportForm.addEventListener('submit', function (event) {
                event.preventDefault();
                hideAlert(supportAlert);
                clearFieldErrors(supportForm);
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
                                const first = applyFieldErrors(supportForm, data?.error?.fields);
                                showAlert(supportAlert, first || translations.alerts.ticketSubmitError, 'danger');
                                const invalid = supportForm.querySelector('.is-invalid');
                                if (invalid) invalid.focus();
                                throw new Error('Validation');
                            });
                        }
                        if (!response.ok) throw new Error('Failed');
                        return response.json();
                    })
                    .then(function (data) {
                        // The confirmation used to be covered by a dialog that
                        // opened on top of it; the conversation is one click away
                        // instead.
                        const id = data.data?.id;
                        const link = id
                            ? ` <a href="#" data-open-ticket="${id}">${escapeHtml(translations.form.openConversation)}</a>`
                            : '';

                        showAlertHtml(supportAlert, escapeHtml(data.message || translations.form.success) + link, 'success');
                        supportForm.reset();
                        resetFileInput(supportAttachmentInput);
                        clearFieldErrors(supportForm);
                        loadTickets();
                    })
                    .catch(function (error) {
                        if (error.message !== 'Validation') {
                            showAlert(supportAlert, translations.alerts.ticketSubmitError, 'danger');
                        }
                    })
                    .finally(function () {
                        setLoading(supportSubmit, supportSpinner, false);
                    });
            });

            supportAlert.addEventListener('click', function (event) {
                const trigger = event.target.closest('[data-open-ticket]');
                if (!trigger) return;

                event.preventDefault();
                loadTicket(trigger.dataset.openTicket);
            });

            ticketMessageForm.addEventListener('submit', function (event) {
                event.preventDefault();
                const ticketId = ticketMessageForm.dataset.ticketId;
                if (!ticketId) {
                    return;
                }

                hideAlert(replyAlert);
                setLoading(ticketReplySubmit, ticketReplySpinner, true);
                const formData = new FormData(ticketMessageForm);

                fetch(`/api/v1/help/tickets/${ticketId}/messages`, {
                    method: 'POST',
                    headers: authHeaders(),
                    body: formData,
                })
                    .then(function (response) {
                        if (response.status === 422) {
                            return response.json().then(function (data) {
                                const fields = data?.error?.fields || {};
                                const first = fields[Object.keys(fields)[0]]?.[0];
                                showAlert(replyAlert, first || translations.alerts.ticketSubmitError, 'danger');
                                ticketReplyMessage.classList.add('is-invalid');
                                ticketReplyMessage.focus();
                                throw new Error('Validation');
                            });
                        }
                        if (!response.ok) throw new Error('Failed');
                        return response.json();
                    })
                    .then(function (data) {
                        ticketReplyMessage.classList.remove('is-invalid');

                        if (data.data) {
                            renderTicketConversation(data.data, { keepAlert: true });
                        }

                        showAlert(replyAlert, translations.form.replySent, 'success');
                        loadTickets();
                    })
                    .catch(function (error) {
                        if (error.message !== 'Validation') {
                            showAlert(replyAlert, translations.alerts.ticketSubmitError, 'danger');
                        }
                    })
                    .finally(function () {
                        setLoading(ticketReplySubmit, ticketReplySpinner, false);
                    });
            });

            refreshTicketsButton.addEventListener('click', function () {
                loadTickets();
            });

            // A reply from support used to wait for a page reload or for someone
            // to notice the icon in the corner.
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible') loadTickets();
            });

            setInterval(function () {
                if (document.visibilityState === 'visible' && !ticketModalEl.classList.contains('show')) {
                    loadTickets();
                }
            }, 60000);

            supportTopicButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    supportSubjectInput.value = button.dataset.helpSubject || '';
                    supportMessageInput.focus();
                });
            });

            loadSupportInfo();
            loadTickets();
        });
    </script>
@endsection
