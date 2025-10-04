@extends('layouts.app')

@section('title', __('calendar.page.title'))

@php
    $calendarDayTranslations = trans('calendar.day');
    $calendarActions = trans('calendar.actions');
    $calendarViews = trans('calendar.views');
    $statusBadges = [
        'draft' => 'bg-label-secondary',
        'pending' => 'bg-label-warning',
        'waiting' => 'bg-label-warning',
        'scheduled' => 'bg-label-info',
        'confirmed' => 'bg-label-primary',
        'processing' => 'bg-label-info',
        'in_progress' => 'bg-label-info',
        'completed' => 'bg-label-success',
        'done' => 'bg-label-success',
        'cancelled' => 'bg-label-danger',
        'canceled' => 'bg-label-danger',
        'no_show' => 'bg-label-dark',
    ];
@endphp

@section('meta')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css">
    <style>
        .calendar-card-body {
            display: flex;
        }

        #crm-calendar {
            flex: 1 1 auto;
            min-height: 640px;
            height: 100%;
        }

        #crm-calendar .fc .fc-toolbar-title {
            font-size: 1.15rem;
        }

        #crm-calendar .fc {
            height: 100%;
        }

        #crm-calendar .fc .fc-view-harness {
            min-height: 560px;
            height: 100%;
        }

        #crm-calendar .fc .fc-highlight {
            background-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.12);
        }

        #crm-calendar .fc .fc-daygrid-day.fc-day-today {
            background-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08);
        }

        #crm-calendar .fc-theme-standard td,
        #crm-calendar .fc-theme-standard th {
            border-color: var(--bs-border-color);
        }

        #crm-calendar .fc .fc-daygrid-event {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #crm-calendar .fc .fc-daygrid-day-events {
            overflow: hidden;
        }

        #crm-calendar .fc .fc-daygrid-more-link,
        #crm-calendar .fc .calendar-more-placeholder {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            padding: 0;
            border-radius: 50%;
            background: transparent;
            color: var(--bs-secondary-color);
            font-weight: 600;
            pointer-events: none;
            cursor: default;
            text-decoration: none;
        }

        #crm-calendar .fc .fc-daygrid-more-link:focus,
        #crm-calendar .fc .fc-daygrid-more-link:active {
            outline: none;
        }

        #crm-calendar .fc .fc-popover {
            background-color: var(--bs-card-bg);
            border-color: var(--bs-border-color);
            color: var(--bs-body-color);
            box-shadow: 0 1.25rem 2.5rem -1.25rem rgba(15, 15, 15, 0.45);
        }

        #crm-calendar .fc .fc-popover .fc-popover-header,
        #crm-calendar .fc .fc-popover .fc-popover-body {
            background-color: var(--bs-card-bg);
            color: var(--bs-body-color);
        }

        #crm-calendar .fc .fc-popover .fc-popover-header {
            border-bottom-color: var(--bs-border-color);
        }

        #crm-calendar .fc .fc-popover .fc-popover-body .fc-event {
            color: inherit;
        }

        #crm-calendar .fc .fc-col-header,
        #crm-calendar .fc .fc-col-header-cell,
        #crm-calendar .fc .fc-timegrid-axis,
        #crm-calendar .fc .fc-list-table thead tr {
            background-color: var(--bs-card-bg);
        }

        #crm-calendar .fc .fc-col-header-cell-cushion,
        #crm-calendar .fc .fc-timegrid-axis-cushion {
            color: var(--bs-body-color);
        }

        .calendar-order-card {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .calendar-order-card:hover {
            border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.4);
            box-shadow: 0 0.75rem 1.25rem -0.75rem rgba(58, 53, 65, 0.45);
        }

        .calendar-order-card .calendar-order-meta {
            color: var(--bs-secondary-color);
        }

        .calendar-order-card .calendar-order-services span {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            background-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.12);
            color: var(--bs-primary-color);
            font-size: 0.75rem;
        }

        .calendar-order-card .calendar-order-services span i {
            font-size: 0.85rem;
        }

        @media (max-width: 991.98px) {
            #crm-calendar {
                min-height: 520px;
            }

            #crm-calendar .fc .fc-view-harness {
                min-height: 480px;
            }
        }
    </style>
@endsection

@section('content')
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div class="d-flex flex-column">
            <h4 class="mb-1">{{ __('calendar.page.title') }}</h4>
            <span class="text-muted small" id="calendar-range-label"></span>
            <p class="text-muted mb-0">{{ __('calendar.page.subtitle') }}</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="btn-group" role="group" aria-label="{{ __('calendar.page.title') }}">
                <button type="button" class="btn btn-outline-secondary" data-calendar-nav="prev" aria-label="{{ __('calendar.actions.previous') }}">
                    <i class="ri ri-arrow-left-s-line"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" data-calendar-nav="next" aria-label="{{ __('calendar.actions.next') }}">
                    <i class="ri ri-arrow-right-s-line"></i>
                </button>
            </div>
            <button type="button" class="btn btn-light border" id="calendar-refresh">
                <i class="ri ri-refresh-line me-1"></i>
                {{ __('calendar.actions.refresh') }}
            </button>
            <button type="button" class="btn btn-primary" id="calendar-today">
                <i class="ri ri-calendar-event-line me-1"></i>
                {{ __('calendar.actions.today') }}
            </button>
        </div>
    </div>

    <div id="calendar-events-error" class="alert alert-danger d-none" role="alert">
        {{ __('calendar.alerts.events_load_failed') }}
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="btn-group" role="group" aria-label="{{ __('calendar.views.month') }}">
                            <button type="button" class="btn btn-outline-secondary" data-calendar-view="dayGridMonth">
                                {{ __('calendar.views.month') }}
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-calendar-view="timeGridWeek">
                                {{ __('calendar.views.week') }}
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-calendar-view="timeGridDay">
                                {{ __('calendar.views.day') }}
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-calendar-view="listWeek">
                                {{ __('calendar.views.list') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0 calendar-card-body">
                    <div id="crm-calendar"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <div class="text-uppercase text-muted small fw-semibold">{{ __('calendar.day.panel_title') }}</div>
                        <h5 class="mb-1" id="calendar-day-title">—</h5>
                        <p class="text-muted mb-0" id="calendar-day-summary">{{ $calendarDayTranslations['subtitle']['zero'] }}</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" id="calendar-create-order" disabled>
                        <i class="ri ri-add-line me-1"></i>
                        {{ __('calendar.actions.create_order') }}
                    </button>
                </div>
                <div class="card-body">
                    <div id="calendar-day-loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">{{ $calendarDayTranslations['loading'] }}</span>
                        </div>
                    </div>
                    <div id="calendar-day-error" class="alert alert-danger d-none" role="alert">
                        {{ __('calendar.alerts.day_load_failed') }}
                    </div>
                    <div id="calendar-day-settings" class="alert alert-warning d-none" role="alert"></div>
                    <div id="calendar-day-non-working" class="alert alert-info d-none" role="alert">
                        <div class="fw-semibold mb-1">{{ __('calendar.day.non_working_day') }}</div>
                        <div class="mb-0">{{ __('calendar.day.non_working_day_description') }}</div>
                    </div>
                    <div id="calendar-day-content" class="d-none">
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="mb-0">{{ __('calendar.day.free_slots_title') }}</h6>
                                <span class="badge bg-label-primary" id="calendar-day-slots-count">0</span>
                            </div>
                            <p class="text-muted small mb-2" id="calendar-day-slots-hint">{{ __('calendar.day.free_slots_hint') }}</p>
                            <div id="calendar-day-slots" class="d-flex flex-wrap gap-2"></div>
                            <div id="calendar-day-slots-empty" class="text-muted small d-none">
                                {{ __('calendar.day.free_slots_empty') }}
                            </div>
                        </div>
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="mb-0">{{ __('calendar.day.orders_title') }}</h6>
                            </div>
                            <div id="calendar-day-orders" class="d-flex flex-column gap-3"></div>
                            <div id="calendar-day-orders-empty" class="text-muted small d-none">
                                {{ __('calendar.day.orders_empty') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales-all.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const locale = '{{ str_replace('_', '-', app()->getLocale()) }}';

            const translations = {
                settingsMissing: @json(__('calendar.settings_missing')),
                alerts: @json(trans('calendar.alerts')),
                day: @json($calendarDayTranslations),
                actions: @json($calendarActions),
                views: @json($calendarViews),
                labels: @json(trans('calendar.labels')),
                noEvents: @json(__('calendar.no_events')),
                unnamedClient: @json(__('calendar.unnamed_client')),
            };

            const statusBadges = @json($statusBadges);
            const buttonText = {
                today: translations.actions.today || 'Today',
                month: translations.views.month || 'Month',
                week: translations.views.week || 'Week',
                day: translations.views.day || 'Day',
                list: translations.views.list || 'Agenda',
            };

            const allDayText = (translations.labels && translations.labels.all_day)
                ? translations.labels.all_day
                : 'All day';

            const pluralRules = new Intl.PluralRules(locale);
            const eventsErrorEl = document.getElementById('calendar-events-error');
            const rangeLabelEl = document.getElementById('calendar-range-label');
            const calendarEl = document.getElementById('crm-calendar');
            const dayTitleEl = document.getElementById('calendar-day-title');
            const daySummaryEl = document.getElementById('calendar-day-summary');
            const dayLoadingEl = document.getElementById('calendar-day-loading');
            const dayErrorEl = document.getElementById('calendar-day-error');
            const dayContentEl = document.getElementById('calendar-day-content');
            const daySettingsEl = document.getElementById('calendar-day-settings');
            const dayNonWorkingEl = document.getElementById('calendar-day-non-working');
            const daySlotsCountEl = document.getElementById('calendar-day-slots-count');
            const daySlotsEl = document.getElementById('calendar-day-slots');
            const daySlotsHintEl = document.getElementById('calendar-day-slots-hint');
            const daySlotsEmptyEl = document.getElementById('calendar-day-slots-empty');
            const dayOrdersEl = document.getElementById('calendar-day-orders');
            const dayOrdersEmptyEl = document.getElementById('calendar-day-orders-empty');
            const createOrderBtn = document.getElementById('calendar-create-order');
            const refreshBtn = document.getElementById('calendar-refresh');
            const todayBtn = document.getElementById('calendar-today');
            const navButtons = document.querySelectorAll('[data-calendar-nav]');
            const viewButtons = document.querySelectorAll('[data-calendar-view]');
            let selectedDate = null;

            function toggle(el, show) {
                if (!el) return;
                el.classList.toggle('d-none', !show);
            }

            function setActiveViewButton(viewName) {
                viewButtons.forEach(function (btn) {
                    const matches = btn.getAttribute('data-calendar-view') === viewName;
                    btn.classList.toggle('btn-primary', matches);
                    btn.classList.toggle('text-white', matches);
                    btn.classList.toggle('btn-outline-secondary', !matches);
                });
            }

            function getCookie(name) {
                const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                return match ? decodeURIComponent(match[1]) : null;
            }

            const authHeaders = (function () {
                const headers = { 'Accept': 'application/json' };
                const token = getCookie('token');
                if (token) {
                    headers['Authorization'] = 'Bearer ' + token;
                }
                return headers;
            })();

            function pluralize(map, count) {
                if (!map) {
                    return String(count);
                }
                if (count === 0 && map.zero) {
                    return map.zero;
                }
                const key = pluralRules.select(count);
                const template = map[key] || map.other || '';
                return template.replace(':count', count);
            }

            function formatDateLabel(dateStr) {
                if (!dateStr) return '—';
                const date = new Date(dateStr + 'T00:00:00');
                if (Number.isNaN(date.getTime())) return dateStr;
                const formatted = new Intl.DateTimeFormat(locale, {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                }).format(date);
                return formatted.charAt(0).toUpperCase() + formatted.slice(1);
            }

            function showEventsError(show) {
                toggle(eventsErrorEl, show);
            }

            function setDayLoading(isLoading) {
                toggle(dayLoadingEl, isLoading);
                toggle(dayContentEl, !isLoading);
                if (isLoading) {
                    toggle(daySettingsEl, false);
                    toggle(dayNonWorkingEl, false);
                }
            }

            function setDayError(hasError) {
                toggle(dayErrorEl, hasError);
                if (hasError) {
                    setDayLoading(false);
                    toggle(dayContentEl, false);
                    toggle(daySettingsEl, false);
                    toggle(dayNonWorkingEl, false);
                    if (daySummaryEl && translations.alerts && translations.alerts.day_load_failed) {
                        daySummaryEl.textContent = translations.alerts.day_load_failed;
                    }
                }
            }

            function updateCreateButton(dateStr) {
                if (!createOrderBtn) return;
                createOrderBtn.dataset.date = dateStr || '';
                createOrderBtn.disabled = !dateStr;
            }

            function renderOrderCard(order) {
                const wrapper = document.createElement('div');
                wrapper.className = 'calendar-order-card';

                const header = document.createElement('div');
                header.className = 'd-flex flex-wrap align-items-center justify-content-between gap-2';

                const info = document.createElement('div');
                info.className = 'd-flex flex-wrap align-items-center gap-2';

                const timeBadge = document.createElement('span');
                timeBadge.className = 'badge bg-label-primary';
                timeBadge.textContent = order.scheduled_at_formatted || translations.day.order_time_undetermined;
                info.appendChild(timeBadge);

                const clientName = (order.client && order.client.name) || translations.unnamedClient;
                const clientEl = document.createElement('span');
                clientEl.className = 'fw-semibold';
                clientEl.textContent = clientName;
                info.appendChild(clientEl);

                header.appendChild(info);

                if (order.status_label || order.status) {
                    const statusEl = document.createElement('span');
                    const badgeClass = statusBadges[order.status] || 'bg-label-secondary';
                    statusEl.className = 'badge ' + badgeClass;
                    statusEl.textContent = order.status_label || order.status;
                    header.appendChild(statusEl);
                }

                wrapper.appendChild(header);

                if (order.client && (order.client.phone || order.client.email)) {
                    const contacts = document.createElement('div');
                    contacts.className = 'calendar-order-meta small mt-2';
                    const parts = [];
                    if (order.client.phone) parts.push(order.client.phone);
                    if (order.client.email) parts.push(order.client.email);
                    if (translations.day.contacts_label) {
                        const labelSpan = document.createElement('span');
                        labelSpan.className = 'fw-semibold';
                        labelSpan.textContent = translations.day.contacts_label + ':';
                        contacts.appendChild(labelSpan);
                        contacts.appendChild(document.createTextNode(' '));
                    }
                    contacts.appendChild(document.createTextNode(parts.join(' • ')));
                    wrapper.appendChild(contacts);
                }

                if (order.services && Array.isArray(order.services) && order.services.length) {
                    const servicesWrap = document.createElement('div');
                    servicesWrap.className = 'calendar-order-services d-flex flex-wrap gap-2 mt-3';
                    order.services.forEach(function (service) {
                        if (!service || !service.name) return;
                        const pill = document.createElement('span');
                        const icon = document.createElement('i');
                        icon.className = 'ri ri-scissors-2-line';
                        pill.appendChild(icon);
                        const text = document.createElement('span');
                        text.textContent = service.name;
                        pill.appendChild(text);
                        servicesWrap.appendChild(pill);
                    });
                    wrapper.appendChild(servicesWrap);
                }

                if (order.note) {
                    const note = document.createElement('div');
                    note.className = 'calendar-order-meta small mt-3';
                    if (translations.day.note_label) {
                        const noteLabel = document.createElement('span');
                        noteLabel.className = 'fw-semibold';
                        noteLabel.textContent = translations.day.note_label + ':';
                        note.appendChild(noteLabel);
                        note.appendChild(document.createTextNode(' '));
                    }
                    note.appendChild(document.createTextNode(order.note));
                    wrapper.appendChild(note);
                }

                const footer = document.createElement('div');
                footer.className = 'd-flex justify-content-end mt-3';

                const openBtn = document.createElement('a');
                openBtn.className = 'btn btn-sm btn-outline-primary';
                openBtn.href = '/orders/' + order.id;
                openBtn.textContent = translations.actions.open_order;
                footer.appendChild(openBtn);

                wrapper.appendChild(footer);

                return wrapper;
            }

            function renderDayDetails(payload, meta) {
                const dateStr = payload.date;
                dayTitleEl.textContent = formatDateLabel(dateStr);

                const orders = Array.isArray(payload.orders) ? payload.orders : [];
                daySummaryEl.textContent = pluralize(translations.day.subtitle, orders.length);

                const availableSlots = Array.isArray(payload.available_slots) ? payload.available_slots : [];
                daySlotsEl.innerHTML = '';
                if (availableSlots.length) {
                    availableSlots.forEach(function (slot) {
                        const slotBadge = document.createElement('span');
                        slotBadge.className = 'badge rounded-pill bg-label-success';
                        slotBadge.textContent = slot;
                        daySlotsEl.appendChild(slotBadge);
                    });
                }

                const settingsNotice = meta && meta.settings_notice ? meta.settings_notice : null;
                if (daySettingsEl) {
                    daySettingsEl.textContent = settingsNotice || '';
                    toggle(daySettingsEl, Boolean(settingsNotice));
                }

                if (daySlotsHintEl) {
                    toggle(daySlotsHintEl, !settingsNotice);
                }

                if (daySlotsCountEl) {
                    daySlotsCountEl.textContent = settingsNotice ? '—' : availableSlots.length;
                }

                toggle(daySlotsEmptyEl, !settingsNotice && availableSlots.length === 0);

                toggle(dayNonWorkingEl, payload.is_working_day === false);

                dayOrdersEl.innerHTML = '';
                if (orders.length) {
                    orders.forEach(function (order) {
                        dayOrdersEl.appendChild(renderOrderCard(order));
                    });
                }
                toggle(dayOrdersEmptyEl, orders.length === 0);

                setDayLoading(false);
            }

            function loadDayDetails(dateStr, options) {
                if (!dateStr) return;
                const force = options && options.force;
                if (!force && selectedDate === dateStr && !dayContentEl.classList.contains('d-none')) {
                    return;
                }

                selectedDate = dateStr;
                updateCreateButton(dateStr);
                if (dayTitleEl) {
                    dayTitleEl.textContent = formatDateLabel(dateStr);
                }
                if (daySummaryEl && translations.day && translations.day.loading) {
                    daySummaryEl.textContent = translations.day.loading;
                }
                setDayLoading(true);
                setDayError(false);

                const url = new URL('/api/v1/calendar/day', window.location.origin);
                url.searchParams.set('date', dateStr);

                fetch(url.toString(), { headers: authHeaders })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Failed');
                        }
                        return response.json();
                    })
                    .then(function (json) {
                        const payload = json && json.data ? json.data : {};
                        const meta = json ? json.meta : {};
                        renderDayDetails(payload, meta);
                    })
                    .catch(function () {
                        setDayError(true);
                    });
            }

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: false,
                locale: locale,
                firstDay: 1,
                selectable: true,
                selectMirror: true,
                expandRows: true,
                dayMaxEvents: 4,
                dayMaxEventRows: 4,
                eventMaxStack: 4,
                height: '100%',
                allDayText: allDayText,
                buttonText: buttonText,
                noEventsContent: function () {
                    return { html: translations.noEvents };
                },
                moreLinkContent: function () {
                    return '…';
                },
                moreLinkDidMount: function (args) {
                    if (!args.el) {
                        return;
                    }
                    args.el.classList.add('calendar-more-placeholder');
                    args.el.setAttribute('aria-hidden', 'true');
                    args.el.setAttribute('tabindex', '-1');
                },
                moreLinkClick: function (info) {
                    if (info && info.jsEvent) {
                        info.jsEvent.preventDefault();
                        info.jsEvent.stopPropagation();
                    }
                    return null;
                },
                eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                events: function (fetchInfo, successCallback, failureCallback) {
                    showEventsError(false);
                    const start = fetchInfo.start.toISOString().slice(0, 10);
                    const end = fetchInfo.end.toISOString().slice(0, 10);
                    const url = new URL('/api/v1/calendar/events', window.location.origin);
                    url.searchParams.set('start', start);
                    url.searchParams.set('end', end);

                    fetch(url.toString(), { headers: authHeaders })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('Failed');
                            }
                            return response.json();
                        })
                        .then(function (json) {
                            const events = (json && json.data && json.data.events) ? json.data.events : [];
                            successCallback(events);
                        })
                        .catch(function () {
                            showEventsError(true);
                            if (failureCallback) {
                                failureCallback();
                            }
                        });
                },
                select: function (selectionInfo) {
                    const dateStr = selectionInfo.startStr ? selectionInfo.startStr.slice(0, 10) : null;
                    if (dateStr) {
                        loadDayDetails(dateStr);
                    }
                },
                dateClick: function (info) {
                    calendar.select(info.date);
                },
                datesSet: function () {
                    if (rangeLabelEl) {
                        rangeLabelEl.textContent = calendar.view.title;
                    }
                    setActiveViewButton(calendar.view.type);
                },
                eventClick: function (info) {
                    info.jsEvent.preventDefault();
                    if (info.event.start) {
                        calendar.select(info.event.start);
                    }
                    if (info.jsEvent.metaKey || info.jsEvent.ctrlKey) {
                        window.open('/orders/' + info.event.id, '_blank');
                    }
                },
                eventDidMount: function (info) {
                    const parts = [];
                    if (info.event.extendedProps && info.event.extendedProps.scheduled_at_formatted) {
                        parts.push(info.event.extendedProps.scheduled_at_formatted);
                    }
                    if (info.event.extendedProps && info.event.extendedProps.client && info.event.extendedProps.client.name) {
                        parts.push(info.event.extendedProps.client.name);
                    }
                    if (info.event.extendedProps && Array.isArray(info.event.extendedProps.services) && info.event.extendedProps.services.length) {
                        parts.push(info.event.extendedProps.services.join(', '));
                    }
                    if (parts.length) {
                        info.el.setAttribute('title', parts.join(' • '));
                    }
                }
            });

            calendar.render();
            calendar.select(new Date());

            navButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const action = btn.getAttribute('data-calendar-nav');
                    if (action === 'prev') {
                        calendar.prev();
                    } else if (action === 'next') {
                        calendar.next();
                    }
                });
            });

            viewButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const view = btn.getAttribute('data-calendar-view');
                    if (view) {
                        calendar.changeView(view);
                    }
                });
            });

            if (refreshBtn) {
                refreshBtn.addEventListener('click', function () {
                    calendar.refetchEvents();
                    if (selectedDate) {
                        loadDayDetails(selectedDate, { force: true });
                    }
                });
            }

            if (todayBtn) {
                todayBtn.addEventListener('click', function () {
                    calendar.today();
                    calendar.select(new Date());
                });
            }

            if (createOrderBtn) {
                createOrderBtn.addEventListener('click', function () {
                    const date = createOrderBtn.dataset.date;
                    if (!date) return;
                    const url = new URL('/orders/create', window.location.origin);
                    url.searchParams.set('date', date);
                    window.location.href = url.toString();
                });
            }
        });
    </script>
@endsection
