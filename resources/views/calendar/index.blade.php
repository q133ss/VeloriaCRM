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
    @include('components.veloria-datetime-picker-styles')
    <style>
        .calendar-page {
            --cal-border: rgba(var(--bs-border-color-rgb, 160, 169, 192), 0.42);
            --cal-accent: rgba(var(--bs-primary-rgb, 255, 0, 252), 1);
            --cal-accent-soft: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.1);
            --cal-quiet: rgba(var(--bs-body-color-rgb, 88, 96, 116), 0.045);
            --cal-radius: 0.65rem;
        }

        .calendar-panel {
            border: 1px solid var(--cal-border);
            border-radius: var(--cal-radius);
            background: var(--bs-card-bg);
        }

        /* --- Toolbar: month, navigation, one primary action --- */

        .calendar-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }

        .calendar-toolbar__month {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .calendar-toolbar__nav {
            display: inline-flex;
            gap: 0.35rem;
        }

        .calendar-toolbar__spacer {
            flex: 1 1 auto;
        }

        .calendar-icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            padding: 0;
            border: 1px solid var(--cal-border);
            border-radius: 0.5rem;
            background: transparent;
            color: var(--bs-body-color);
            font-size: 1.15rem;
            line-height: 1;
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }

        .calendar-icon-btn:hover {
            background: var(--cal-quiet);
            color: var(--bs-body-color);
        }

        .calendar-icon-btn:focus-visible,
        .calendar-ghost-btn:focus-visible,
        .calendar-views button:focus-visible {
            outline: 2px solid var(--cal-accent);
            outline-offset: 2px;
        }

        .calendar-ghost-btn {
            border: 1px solid var(--cal-border);
            border-radius: 0.5rem;
            background: transparent;
            color: var(--bs-body-color);
            font-weight: 600;
            padding: 0.4rem 0.85rem;
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }

        .calendar-ghost-btn:hover {
            background: var(--cal-quiet);
            color: var(--bs-body-color);
        }

        /* --- View tabs --- */

        .calendar-views {
            display: inline-flex;
            gap: 0.15rem;
            padding: 0.25rem;
            margin: 0.85rem 0.85rem 0;
            border-radius: 0.5rem;
            background: var(--cal-quiet);
        }

        .calendar-views button {
            border: none;
            border-radius: 0.4rem;
            background: transparent;
            color: var(--bs-body-color);
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.35rem 0.9rem;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .calendar-views button:hover {
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.7);
        }

        .calendar-views button.is-active {
            background: var(--bs-card-bg);
            color: var(--cal-accent);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
        }

        /* --- FullCalendar --- */

        #crm-calendar {
            --fc-today-bg-color: transparent;
            --fc-highlight-color: var(--cal-accent-soft);
            --fc-border-color: var(--cal-border);
            /* FullCalendar hardcodes these to white/grey, which breaks the dark theme. */
            --fc-page-bg-color: var(--bs-card-bg);
            --fc-neutral-bg-color: var(--cal-quiet);
            --fc-list-event-hover-bg-color: var(--cal-quiet);
            padding: 0.85rem;
        }

        #crm-calendar td,
        #crm-calendar th,
        #crm-calendar .fc-scrollgrid {
            border-color: var(--cal-border);
        }

        #crm-calendar .fc-scrollgrid {
            border-radius: 0.5rem;
            overflow: hidden;
        }

        #crm-calendar .fc-col-header-cell-cushion,
        #crm-calendar .fc-timegrid-axis-cushion {
            color: var(--bs-secondary-color);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding-block: 0.6rem;
        }

        #crm-calendar .fc-daygrid-day-frame {
            min-height: 5.5rem;
            padding: 0.3rem;
        }

        /* The dot eats width the client name needs in a ~100px month cell. */
        #crm-calendar .fc-daygrid-event-dot {
            display: none;
        }

        #crm-calendar .fc-daygrid-day-number {
            color: var(--bs-secondary-color);
            font-weight: 600;
            padding: 0.2rem 0.3rem;
        }

        /* Today is a quiet marker; the day you picked is the loud one. */
        #crm-calendar .fc-daygrid-day.fc-day-today {
            background: transparent;
        }

        #crm-calendar .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.7rem;
            height: 1.7rem;
            padding: 0;
            border: 1px solid var(--cal-accent);
            border-radius: 999px;
            color: var(--cal-accent);
        }

        #crm-calendar .fc-daygrid-day:has(.fc-highlight),
        #crm-calendar .fc-timegrid-col:has(.fc-highlight) {
            box-shadow: inset 0 0 0 2px var(--cal-accent);
        }

        #crm-calendar .fc-highlight {
            background: var(--cal-accent-soft);
        }

        #crm-calendar .fc-daygrid-event,
        #crm-calendar .fc-timegrid-event {
            border: none;
            border-radius: 0.35rem;
            background: var(--cal-accent-soft);
            color: var(--bs-body-color);
            box-shadow: none;
            padding: 0.1rem 0.3rem;
            font-size: 0.75rem;
        }

        #crm-calendar .fc-daygrid-event:hover,
        #crm-calendar .fc-timegrid-event:hover {
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.18);
        }

        #crm-calendar .fc-event-title,
        #crm-calendar .fc-event-time {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #crm-calendar .fc-event-time {
            flex: 0 0 auto;
            color: var(--cal-accent);
            font-weight: 700;
        }

        /* --- Day panel --- */

        .calendar-day {
            position: sticky;
            top: 5.75rem;
            padding: 1.25rem;
        }

        .calendar-day__title {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0 0 0.15rem;
        }

        .calendar-day__summary {
            color: var(--bs-secondary-color);
            font-size: 0.875rem;
            margin: 0;
        }

        .calendar-day__block {
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--cal-border);
        }

        .calendar-day__block h3 {
            display: flex;
            align-items: baseline;
            gap: 0.4rem;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--bs-secondary-color);
            margin-bottom: 0.75rem;
        }

        .calendar-notice {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 0.5rem;
            border: 1px solid var(--cal-border);
            border-left: 2px solid var(--bs-secondary-color);
            border-radius: 0.4rem;
            padding: 0.7rem 0.85rem;
            background: var(--cal-quiet);
            color: var(--bs-secondary-color);
            font-size: 0.875rem;
        }

        .calendar-empty {
            padding: 1.5rem 0;
            text-align: center;
            color: var(--bs-secondary-color);
        }

        .calendar-gap-card {
            border: 1px solid var(--cal-border);
            border-left: 2px solid var(--cal-accent);
            border-radius: 0.5rem;
            padding: 0.75rem 0.85rem;
        }

        .calendar-gap-meta {
            color: var(--bs-secondary-color);
            font-size: 0.85rem;
        }

        .calendar-gap-candidate {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem 0.75rem;
            margin-top: 0.65rem;
            padding-top: 0.65rem;
            border-top: 1px solid var(--cal-border);
        }

        .calendar-gap-candidate > div:first-child {
            flex: 1 1 9rem;
            min-width: 0;
        }

        /* Start is not an ordinary action: it puts a clock in the header and the
           measured duration depends on it being pressed at the right moment. */
        .calendar-timer-btn,
        .calendar-stop-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border-radius: 0.5rem;
            font-weight: 600;
            padding: 0.35rem 0.8rem;
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }

        .calendar-timer-btn {
            border: 1px solid rgba(var(--bs-success-rgb, 40, 199, 111), 0.5);
            background: rgba(var(--bs-success-rgb, 40, 199, 111), 0.12);
            color: var(--bs-body-color);
        }

        .calendar-timer-btn:hover {
            border-color: rgb(var(--bs-success-rgb, 40, 199, 111));
            background: rgba(var(--bs-success-rgb, 40, 199, 111), 0.2);
            color: var(--bs-body-color);
        }

        .calendar-stop-btn {
            border: 1px solid rgba(var(--bs-danger-rgb, 255, 62, 29), 0.45);
            background: rgba(var(--bs-danger-rgb, 255, 62, 29), 0.1);
            color: var(--bs-body-color);
        }

        .calendar-stop-btn:hover {
            border-color: rgb(var(--bs-danger-rgb, 255, 62, 29));
            background: rgba(var(--bs-danger-rgb, 255, 62, 29), 0.18);
            color: var(--bs-body-color);
        }

        .calendar-action-icon {
            flex: 0 0 auto;
            width: 0.6rem;
            height: 0.6rem;
        }

        .calendar-action-icon--play {
            background: rgb(var(--bs-success-rgb, 40, 199, 111));
            clip-path: polygon(0 0, 100% 50%, 0 100%);
        }

        .calendar-action-icon--stop {
            background: rgb(var(--bs-danger-rgb, 255, 62, 29));
            border-radius: 1px;
        }

        .calendar-duration-hint {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 0.5rem;
            color: var(--bs-secondary-color);
            font-size: 0.85rem;
        }

        .calendar-order-attention {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 0.5rem;
            margin-top: 0.5rem;
            color: var(--bs-secondary-color);
            font-size: 0.85rem;
        }

        .calendar-slot-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.3rem 0.6rem;
            border: 1px solid var(--cal-border);
            border-radius: 0.35rem;
            background: transparent;
            color: var(--bs-body-color);
            font-size: 0.85rem;
            font-variant-numeric: tabular-nums;
        }

        .calendar-order-card {
            border: 1px solid var(--cal-border);
            border-radius: 0.5rem;
            padding: 0.85rem;
            background: transparent;
            transition: border-color 0.15s ease;
        }

        .calendar-order-card:hover {
            border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.35);
        }

        .calendar-order-card .calendar-order-meta {
            color: var(--bs-secondary-color);
            font-size: 0.85rem;
        }

        .calendar-order-card .calendar-order-services span {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.5rem;
            border-radius: 0.3rem;
            background: var(--cal-quiet);
            color: var(--bs-body-color);
            font-size: 0.75rem;
        }

        .calendar-order-card .calendar-order-services span i {
            font-size: 0.85rem;
        }

        .calendar-match-card {
            border: 1px solid var(--cal-border);
            border-radius: 0.5rem;
            padding: 0.85rem;
        }

        .calendar-match-reasons span {
            display: inline-flex;
            align-items: center;
            border-radius: 0.3rem;
            padding: 0.2rem 0.45rem;
            background: var(--cal-quiet);
            color: var(--bs-secondary-color);
            font-size: 0.72rem;
            font-weight: 600;
        }

        /* --- Modals --- */

        .calendar-create-modal .modal-content {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.75rem;
        }

        /* Bootstrap's scrollable modal expects header, body and footer to be
           direct children of .modal-content. Here a <form> wraps body and footer,
           and being a plain block it grows to its content: the body never gets a
           height to scroll inside, so on a phone half the form and the create
           button used to sit below the edge of the screen with nothing to scroll. */
        .calendar-create-modal .modal-content > form {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
        }

        .calendar-create-modal .modal-body {
            flex: 1 1 auto;
            overflow-y: auto;
            min-height: 0;
        }

        .calendar-create-modal .modal-footer {
            position: sticky;
            bottom: 0;
            z-index: 3;
            background: var(--bs-modal-bg, var(--bs-body-bg));
            border-top: 1px solid var(--bs-border-color);
        }

        .calendar-modal-search-layer {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        /* Floating, not in the flow: a list of recent clients that pushes the
           form down also pushes the submit button out of the window. */
        .calendar-modal-results,
        .calendar-modal-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 5;
            margin-top: 0.35rem;
            max-height: 260px;
            overflow-y: auto;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.5rem;
            background: var(--bs-body-bg);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15);
        }

        /* No inner scroll: the list used to clip a service in half behind an
           invisible scrollbar. The modal body scrolls for it now. */
        .calendar-modal-services {
            padding-right: 0.15rem;
        }

        .calendar-modal-service {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.5rem;
            padding: 0.75rem 0.9rem;
            transition: border-color 0.15s ease;
        }

        .calendar-modal-service:hover {
            border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.35);
        }

        .calendar-modal-service input {
            margin-top: 0.1rem;
        }

        .calendar-modal-summary {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.5rem;
            padding: 0.85rem;
        }

        /* --- Responsive --- */

        @media (max-width: 1199.98px) {
            .calendar-day {
                position: static;
            }
        }

        @media (max-width: 991.98px) {
            #crm-calendar .fc-daygrid-day-frame {
                min-height: 5rem;
            }
        }

        @media (max-width: 575.98px) {
            .calendar-toolbar__month {
                font-size: 1.2rem;
                width: 100%;
                order: -1;
            }

            .calendar-toolbar__spacer {
                display: none;
            }

            .calendar-toolbar .btn-primary {
                width: 100%;
            }

            /* Week and agenda views are unreadable at 390px — month and day only. */
            .calendar-views__wide {
                display: none;
            }

            #crm-calendar {
                padding: 0.5rem;
            }

            #crm-calendar .fc-daygrid-day-frame {
                min-height: 3.4rem;
            }

            .calendar-day {
                padding: 1rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="calendar-page">
        <div class="calendar-toolbar">
            <h1 class="calendar-toolbar__month" id="calendar-range-label">-</h1>
            <div class="calendar-toolbar__nav">
                <button type="button" class="calendar-icon-btn" data-calendar-nav="prev" aria-label="{{ __('calendar.actions.previous') }}">
                    <i class="ri ri-arrow-left-s-line"></i>
                </button>
                <button type="button" class="calendar-icon-btn" data-calendar-nav="next" aria-label="{{ __('calendar.actions.next') }}">
                    <i class="ri ri-arrow-right-s-line"></i>
                </button>
            </div>
            <button type="button" class="btn calendar-ghost-btn" id="calendar-today">{{ __('calendar.actions.today') }}</button>
            <div class="calendar-toolbar__spacer"></div>
            <button type="button" class="btn btn-primary" id="calendar-create-order" disabled>
                <i class="ri ri-add-line me-1"></i>{{ __('calendar.actions.create_order') }}
            </button>
        </div>

        <div id="calendar-events-error" class="alert alert-danger d-none mb-4" role="alert">
            {{ __('calendar.alerts.events_load_failed') }}
        </div>

        <div class="row g-3 align-items-start">
            <div class="col-12 col-xl-8">
                <div class="calendar-panel">
                    <div class="calendar-views" role="group" aria-label="{{ __('calendar.page.title') }}">
                        <button type="button" data-calendar-view="dayGridMonth">{{ __('calendar.views.month') }}</button>
                        <button type="button" class="calendar-views__wide" data-calendar-view="timeGridWeek">{{ __('calendar.views.week') }}</button>
                        <button type="button" data-calendar-view="timeGridDay">{{ __('calendar.views.day') }}</button>
                        <button type="button" class="calendar-views__wide" data-calendar-view="listWeek">{{ __('calendar.views.list') }}</button>
                    </div>
                    <div id="crm-calendar"></div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <aside class="calendar-panel calendar-day">
                    <h2 class="calendar-day__title" id="calendar-day-title">-</h2>
                    <p class="calendar-day__summary mb-3" id="calendar-day-summary">{{ $calendarDayTranslations['subtitle']['zero'] }}</p>

                    <div id="calendar-day-loading" class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">{{ $calendarDayTranslations['loading'] }}</span>
                        </div>
                    </div>

                    <div id="calendar-day-error" class="alert alert-danger d-none mb-0" role="alert">
                        {{ __('calendar.alerts.day_load_failed') }}
                    </div>

                    <div id="calendar-day-notice" class="calendar-notice d-none mb-3">
                        <span id="calendar-day-notice-text"></span>
                        <a href="{{ url('/settings') }}" id="calendar-day-notice-action" class="fw-semibold d-none">{{ __('calendar.actions.setup_schedule') }}</a>
                    </div>

                    <div id="calendar-day-content" class="d-none">
                        <div id="calendar-day-orders" class="calendar-day__orders d-flex flex-column gap-2"></div>

                        <div id="calendar-day-orders-empty" class="calendar-empty d-none">
                            <button type="button" class="btn btn-sm calendar-ghost-btn" data-calendar-create>
                                {{ __('calendar.actions.create_order') }}
                            </button>
                        </div>

                        <section id="calendar-day-gaps-section" class="calendar-day__block d-none">
                            <h3>{{ __('calendar.day.gaps_title') }} <span id="calendar-day-gaps-badge" class="fw-normal"></span></h3>
                            <div id="calendar-day-gaps" class="d-flex flex-column gap-2"></div>
                        </section>

                        <section id="calendar-day-slots-section" class="calendar-day__block d-none">
                            <h3>{{ __('calendar.day.free_slots_title') }} <span id="calendar-day-slots-badge" class="fw-normal"></span></h3>
                            <div id="calendar-day-slots" class="d-flex flex-wrap gap-2"></div>
                        </section>

                        <section id="calendar-day-waitlist-section" class="calendar-day__block d-none">
                            <h3>{{ __('calendar.day.waitlist_title') }} <span id="calendar-day-waitlist-badge" class="fw-normal">0</span></h3>
                            <div id="calendar-day-waitlist" class="d-flex flex-column gap-2"></div>
                        </section>

                        <div class="calendar-day__block">
                            <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" id="calendar-open-waitlist" disabled>
                                {{ __('calendar.actions.add_to_waitlist') }}
                            </button>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

        <div class="modal fade calendar-create-modal" id="calendar-create-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header px-4 py-3">
                        <div>
                            <h5 class="modal-title mb-1">Новая запись</h5>
                            <p class="text-muted mb-0 small">Календарь остаётся открытым — заполните и вернётесь на место.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    {{-- Browser-native validation speaks English in a Russian UI;
                         the server's messages are already written for the master. --}}
                    <form id="calendar-create-form" novalidate>
                        <div class="modal-body p-4">
                            <div id="calendar-create-alerts" class="mb-3"></div>
                            <input type="hidden" id="calendar-create-client-id" name="client_id" />
                            <input type="hidden" id="calendar-create-waitlist-entry-id" name="waitlist_entry_id" />

                            <div class="row g-4">
                                <div class="col-lg-7">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="calendar-modal-search-layer">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        id="calendar-create-client-search"
                                                        placeholder="Анна или +7..."
                                                        autocomplete="off"
                                                    />
                                                    <label for="calendar-create-client-search">Найти клиентку</label>
                                                </div>
                                                <div id="calendar-create-client-results" class="calendar-modal-results list-group d-none"></div>
                                            </div>
                                            <div id="calendar-create-selected-client" class="alert alert-primary d-none mt-3 mb-0"></div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="calendar-modal-search-layer">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        id="calendar-create-client-phone"
                                                        name="client_phone"
                                                        placeholder="+7(999)999-99-99"
                                                        data-phone-mask
                                                        required
                                                    />
                                                    <label for="calendar-create-client-phone">Телефон</label>
                                                </div>
                                                <div id="calendar-create-client-suggestions" class="calendar-modal-suggestions list-group d-none"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating form-floating-outline">
                                                <input
                                                    type="text"
                                                    class="form-control"
                                                    id="calendar-create-client-name"
                                                    name="client_name"
                                                    placeholder="Имя клиентки"
                                                />
                                                <label for="calendar-create-client-name">Имя клиентки</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            @include('components.veloria-datetime-field', [
                                                'id' => 'calendar-create-scheduled-at',
                                                'name' => 'scheduled_at',
                                                'label' => 'Дата и время',
                                                'required' => true,
                                                'helper' => 'Сначала выберите день, затем время. Для быстрого сценария используйте готовые слоты ниже.',
                                                'timeSlots' => ['09:00', '10:00', '12:00', '15:00', '18:00'],
                                            ])
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating form-floating-outline">
                                                <select class="form-select" id="calendar-create-status" name="status" required></select>
                                                <label for="calendar-create-status">Статус</label>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-floating form-floating-outline">
                                                <textarea class="form-control" id="calendar-create-note" name="note" style="height: 120px"></textarea>
                                                <label for="calendar-create-note">Комментарий для мастера</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-5">
                                    <div class="calendar-modal-summary mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted">Предварительная сумма</span>
                                            <strong id="calendar-create-summary-price">0 ₽</strong>
                                        </div>
                                    </div>

                                    <div class="form-floating form-floating-outline mb-1">
                                        <input
                                            type="number"
                                            min="5"
                                            max="720"
                                            step="5"
                                            class="form-control"
                                            id="calendar-create-duration"
                                            name="duration_forecast"
                                        />
                                        <label for="calendar-create-duration">{{ __('calendar.day.duration_label') }}</label>
                                    </div>

                                    {{-- Appears only when the measured time really differs from the price list. --}}
                                    <div id="calendar-create-duration-hint" class="calendar-duration-hint d-none mb-3">
                                        <span id="calendar-create-duration-hint-text"></span>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" id="calendar-create-duration-apply">
                                            {{ __('calendar.day.duration_apply') }}
                                        </button>
                                    </div>

                                    <div class="form-floating form-floating-outline mb-3">
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="form-control"
                                            id="calendar-create-total-price"
                                            name="total_price"
                                        />
                                        <label for="calendar-create-total-price">Своя сумма, если нужно</label>
                                    </div>

                                    <div>
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <h6 class="mb-0">Услуги</h6>
                                            <span class="badge bg-label-primary" id="calendar-create-services-count">0</span>
                                        </div>
                                        <div id="calendar-create-services" class="calendar-modal-services d-flex flex-column gap-2">
                                            <p class="text-muted mb-0">Загрузка услуг...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer px-4 py-3">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                            <button type="submit" class="btn btn-primary" id="calendar-create-submit">Создать запись</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade calendar-create-modal" id="calendar-waitlist-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header px-4 py-3">
                        <div>
                            <h5 class="modal-title mb-1">Лист ожидания</h5>
                            <p class="text-muted mb-0 small">Если день занят, запишите клиента сюда — предложим ему освободившееся время.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="calendar-waitlist-form">
                        <div class="modal-body p-4">
                            <div id="calendar-waitlist-alerts" class="mb-3"></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="calendar-waitlist-client-name" placeholder="Имя" />
                                        <label for="calendar-waitlist-client-name">Имя клиентки</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="calendar-waitlist-client-phone" placeholder="+7..." data-phone-mask required />
                                        <label for="calendar-waitlist-client-phone">Телефон</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating form-floating-outline">
                                        <input type="email" class="form-control" id="calendar-waitlist-client-email" placeholder="email@example.com" />
                                        <label for="calendar-waitlist-client-email">Email, если есть</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating form-floating-outline">
                                        <select class="form-select" id="calendar-waitlist-service" required></select>
                                        <label for="calendar-waitlist-service">Услуга</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating form-floating-outline">
                                        <input type="date" class="form-control" id="calendar-waitlist-date" required />
                                        <label for="calendar-waitlist-date">Нужная дата</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-floating form-floating-outline">
                                        <input type="time" class="form-control" id="calendar-waitlist-time-start" />
                                        <label for="calendar-waitlist-time-start">С</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-floating form-floating-outline">
                                        <input type="time" class="form-control" id="calendar-waitlist-time-end" />
                                        <label for="calendar-waitlist-time-end">До</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating form-floating-outline">
                                        <input type="number" min="0" max="14" class="form-control" id="calendar-waitlist-flexibility" value="0" />
                                        <label for="calendar-waitlist-flexibility">Гибкость по дням</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating form-floating-outline">
                                        <input type="number" min="0" max="5" class="form-control" id="calendar-waitlist-priority" value="0" />
                                        <label for="calendar-waitlist-priority">Ручной приоритет</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating form-floating-outline">
                                        <textarea class="form-control" id="calendar-waitlist-notes" style="height: 110px"></textarea>
                                        <label for="calendar-waitlist-notes">Комментарий</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer px-4 py-3">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                            <button type="submit" class="btn btn-primary" id="calendar-waitlist-submit">Добавить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @include('components.message-sheet')
@endsection

@section('scripts')
    @include('components.phone-mask-script')
    @include('components.veloria-datetime-picker-script')
    @include('components.message-sheet-script')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales-all.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const locale = '{{ str_replace('_', '-', app()->getLocale()) }}';
            document.documentElement.setAttribute('lang', locale);

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

            const gapValueLabel = @json(__('calendar.day.gap_value'));
            const gapBookLabel = @json(__('calendar.day.gap_book'));
            const gapWriteLabel = @json(__('calendar.day.gap_write'));
            const attentionConfirmLabel = @json(__('calendar.day.attention.confirm'));
            const reminderTemplate = @json(__('calendar.day.attention.reminder_text'));
            const startConfirmTemplate = @json(__('calendar.day.actions.start_confirm'));
            const durationHintTemplate = @json(__('calendar.day.duration_hint'));
            const orderActionLabels = {
                start: @json(__('calendar.day.actions.start')),
                complete: @json(__('calendar.day.actions.complete')),
                noShow: @json(__('calendar.day.actions.no_show')),
            };
            const moneyFormatter = new Intl.NumberFormat(locale, { maximumFractionDigits: 0 });

            /**
             * "понедельник, 7 сентября" — a date to read inside a sentence, not
             * the panel heading with a year and a trailing "г.".
             */
            function formatGapDay(dateStr) {
                if (!dateStr) return '';

                const date = new Date(dateStr + 'T00:00:00');

                if (Number.isNaN(date.getTime())) return dateStr;

                return new Intl.DateTimeFormat(locale, {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                }).format(date);
            }

            function currentTimeLabel() {
                const now = new Date();

                return String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
            }

            function formatMoney(value) {
                return moneyFormatter.format(Number(value) || 0);
            }

            const pluralRules = new Intl.PluralRules(locale);
            const eventsErrorEl = document.getElementById('calendar-events-error');
            const rangeLabelEl = document.getElementById('calendar-range-label');
            const selectedDateLabelEl = document.getElementById('calendar-selected-date-label');
            const visibleEventsCountEl = document.getElementById('calendar-visible-events-count');
            const activeViewLabelEl = document.getElementById('calendar-active-view-label');
            const calendarEl = document.getElementById('crm-calendar');
            const dayTitleEl = document.getElementById('calendar-day-title');
            const daySummaryEl = document.getElementById('calendar-day-summary');
            const dayLoadingEl = document.getElementById('calendar-day-loading');
            const dayErrorEl = document.getElementById('calendar-day-error');
            const dayContentEl = document.getElementById('calendar-day-content');
            const dayNoticeEl = document.getElementById('calendar-day-notice');
            const dayNoticeTextEl = document.getElementById('calendar-day-notice-text');
            const dayNoticeActionEl = document.getElementById('calendar-day-notice-action');
            const dayGapsSectionEl = document.getElementById('calendar-day-gaps-section');
            const dayGapsEl = document.getElementById('calendar-day-gaps');
            const dayGapsBadgeEl = document.getElementById('calendar-day-gaps-badge');
            const daySlotsSectionEl = document.getElementById('calendar-day-slots-section');
            const daySlotsBadgeEl = document.getElementById('calendar-day-slots-badge');
            const daySlotsEl = document.getElementById('calendar-day-slots');
            const dayOrdersEl = document.getElementById('calendar-day-orders');
            const dayOrdersEmptyEl = document.getElementById('calendar-day-orders-empty');
            const dayWaitlistSectionEl = document.getElementById('calendar-day-waitlist-section');
            const dayWaitlistEl = document.getElementById('calendar-day-waitlist');
            const dayWaitlistBadgeEl = document.getElementById('calendar-day-waitlist-badge');
            const createOrderBtn = document.getElementById('calendar-create-order');
            const openWaitlistBtn = document.getElementById('calendar-open-waitlist');
            const createOrderModalEl = document.getElementById('calendar-create-modal');
            const createOrderForm = document.getElementById('calendar-create-form');
            const createOrderAlertsEl = document.getElementById('calendar-create-alerts');
            const createOrderClientIdEl = document.getElementById('calendar-create-client-id');
            const createOrderWaitlistEntryIdEl = document.getElementById('calendar-create-waitlist-entry-id');
            const createOrderClientSearchEl = document.getElementById('calendar-create-client-search');
            const createOrderClientResultsEl = document.getElementById('calendar-create-client-results');
            const createOrderClientSuggestionsEl = document.getElementById('calendar-create-client-suggestions');
            const createOrderSelectedClientEl = document.getElementById('calendar-create-selected-client');
            const createOrderClientPhoneEl = document.getElementById('calendar-create-client-phone');
            const createOrderClientNameEl = document.getElementById('calendar-create-client-name');
            const createOrderScheduledAtEl = document.getElementById('calendar-create-scheduled-at');
            const createOrderStatusEl = document.getElementById('calendar-create-status');
            const createOrderNoteEl = document.getElementById('calendar-create-note');
            const createOrderTotalPriceEl = document.getElementById('calendar-create-total-price');
            const createOrderServicesEl = document.getElementById('calendar-create-services');
            const createOrderServicesCountEl = document.getElementById('calendar-create-services-count');
            const createOrderSummaryPriceEl = document.getElementById('calendar-create-summary-price');
            const createOrderDurationEl = document.getElementById('calendar-create-duration');
            const createOrderDurationHintEl = document.getElementById('calendar-create-duration-hint');
            const createOrderDurationHintTextEl = document.getElementById('calendar-create-duration-hint-text');
            const createOrderDurationApplyEl = document.getElementById('calendar-create-duration-apply');
            let durationEstimates = [];
            const createOrderSubmitEl = document.getElementById('calendar-create-submit');
            const waitlistModalEl = document.getElementById('calendar-waitlist-modal');
            const waitlistForm = document.getElementById('calendar-waitlist-form');
            const waitlistAlertsEl = document.getElementById('calendar-waitlist-alerts');
            const waitlistClientNameEl = document.getElementById('calendar-waitlist-client-name');
            const waitlistClientPhoneEl = document.getElementById('calendar-waitlist-client-phone');
            const waitlistClientEmailEl = document.getElementById('calendar-waitlist-client-email');
            const waitlistServiceEl = document.getElementById('calendar-waitlist-service');
            const waitlistDateEl = document.getElementById('calendar-waitlist-date');
            const waitlistTimeStartEl = document.getElementById('calendar-waitlist-time-start');
            const waitlistTimeEndEl = document.getElementById('calendar-waitlist-time-end');
            const waitlistFlexibilityEl = document.getElementById('calendar-waitlist-flexibility');
            const waitlistPriorityEl = document.getElementById('calendar-waitlist-priority');
            const waitlistNotesEl = document.getElementById('calendar-waitlist-notes');
            const waitlistSubmitEl = document.getElementById('calendar-waitlist-submit');
            const refreshBtn = document.getElementById('calendar-refresh');
            const todayBtn = document.getElementById('calendar-today');
            const navButtons = document.querySelectorAll('[data-calendar-nav]');
            const viewButtons = document.querySelectorAll('[data-calendar-view]');
            const createOrderModal = (typeof bootstrap !== 'undefined' && createOrderModalEl)
                ? new bootstrap.Modal(createOrderModalEl)
                : null;
            const waitlistModal = (typeof bootstrap !== 'undefined' && waitlistModalEl)
                ? new bootstrap.Modal(waitlistModalEl)
                : null;

            let selectedDate = null;
            let dayHasGaps = false;
            let lastDayAvailableSlots = [];
            let waitlistOptionsLoaded = false;

            function toggle(el, show) {
                if (!el) return;
                el.classList.toggle('d-none', !show);
            }

            function viewLabel(viewName) {
                if (viewName === 'timeGridWeek') return translations.views.week || 'Week';
                if (viewName === 'timeGridDay') return translations.views.day || 'Day';
                if (viewName === 'listWeek') return translations.views.list || 'List';
                return translations.views.month || 'Month';
            }

            function updateActiveViewLabel(viewName) {
                if (!activeViewLabelEl) return;
                activeViewLabelEl.textContent = viewLabel(viewName);
            }

            // Month/agenda size to their content; the hour grids would otherwise render
            // all 24 hours as one 1200px-tall page section instead of scrolling.
            let viewSizing = null;
            function applyViewSizing(viewName) {
                const timed = viewName === 'timeGridWeek' || viewName === 'timeGridDay';
                const mode = timed ? 'timed' : 'auto';
                if (mode === viewSizing || typeof calendar === 'undefined') return;
                viewSizing = mode;
                calendar.setOption('expandRows', timed);
                calendar.setOption('height', timed ? 700 : 'auto');
                if (timed) {
                    // Resizing resets the scroller, so land on working hours, not midnight.
                    requestAnimationFrame(function () { calendar.scrollToTime('08:00:00'); });
                }
            }

            function setActiveViewButton(viewName) {
                viewButtons.forEach(function (btn) {
                    const matches = btn.getAttribute('data-calendar-view') === viewName;
                    btn.classList.toggle('is-active', matches);
                    btn.setAttribute('aria-pressed', matches ? 'true' : 'false');
                });

                updateActiveViewLabel(viewName);
                applyViewSizing(viewName);
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
                if (!dateStr) return '-';
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

            function updateSelectedDatePreview(dateStr) {
                if (!selectedDateLabelEl) return;
                selectedDateLabelEl.textContent = formatDateLabel(dateStr);
            }

            function updateVisibleEventsCount(count) {
                if (!visibleEventsCountEl) return;
                visibleEventsCountEl.textContent = String(count || 0);
            }


            function showPageFeedback(type, message) {
                if (!eventsErrorEl) return;

                eventsErrorEl.className = 'alert alert-' + type + ' mb-4';
                eventsErrorEl.textContent = message || '';
                eventsErrorEl.classList.remove('d-none');

                if (type !== 'danger') {
                    window.setTimeout(function () {
                        eventsErrorEl.className = 'alert alert-danger d-none mb-4';
                        eventsErrorEl.textContent = '';
                    }, 2600);
                }
            }

            function clearCreateAlerts() {
                if (!createOrderAlertsEl) return;
                createOrderAlertsEl.innerHTML = '';
            }

            function showCreateAlert(type, message) {
                if (!createOrderAlertsEl) return;
                const alert = document.createElement('div');
                alert.className = 'alert alert-' + type + ' mb-0';
                alert.setAttribute('role', 'alert');
                alert.textContent = message;
                createOrderAlertsEl.innerHTML = '';
                createOrderAlertsEl.appendChild(alert);
            }

            // Where a field named by the API lives on screen. Anything the server
            // can complain about needs an entry, or the master gets the summary
            // line and no idea which control to fix.
            const createFieldAnchors = {
                client_id: 'calendar-create-client-search',
                client_phone: 'calendar-create-client-phone',
                client_name: 'calendar-create-client-name',
                client_email: 'calendar-create-client-name',
                scheduled_at: 'calendar-create-scheduled-at_display',
                services: 'calendar-create-services',
                note: 'calendar-create-note',
                total_price: 'calendar-create-total-price',
                duration_forecast: 'calendar-create-duration',
                status: 'calendar-create-status',
            };

            function clearCreateFieldErrors() {
                document.querySelectorAll('#calendar-create-modal .is-invalid').forEach(function (el) {
                    el.classList.remove('is-invalid');
                });
                document.querySelectorAll('[data-create-field-error]').forEach(function (el) {
                    el.remove();
                });
            }

            function showCreateFieldErrors(fields) {
                clearCreateFieldErrors();

                let first = null;

                Object.keys(fields || {}).forEach(function (key) {
                    // Laravel reports item failures as `services.0`; the master
                    // only cares that it was the services block.
                    const anchorId = createFieldAnchors[key] || createFieldAnchors[key.split('.')[0]];
                    const anchor = anchorId ? document.getElementById(anchorId) : null;
                    if (!anchor) return;

                    const messages = Array.isArray(fields[key]) ? fields[key] : [fields[key]];
                    const text = messages.filter(Boolean).join(' ');
                    if (!text) return;

                    anchor.classList.add('is-invalid');

                    const note = document.createElement('div');
                    note.className = 'invalid-feedback d-block';
                    note.setAttribute('data-create-field-error', '');
                    note.textContent = text;

                    const holder = anchor.closest('.form-floating, .veloria-datetime-field') || anchor;
                    holder.insertAdjacentElement('afterend', note);

                    if (!first) {
                        first = anchor;
                    }
                });

                if (first) {
                    first.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }

                return Boolean(first);
            }

            function clearWaitlistAlerts() {
                if (!waitlistAlertsEl) return;
                waitlistAlertsEl.innerHTML = '';
            }

            function showWaitlistAlert(type, message) {
                if (!waitlistAlertsEl) return;
                const alert = document.createElement('div');
                alert.className = 'alert alert-' + type + ' mb-0';
                alert.setAttribute('role', 'alert');
                alert.textContent = message;
                waitlistAlertsEl.innerHTML = '';
                waitlistAlertsEl.appendChild(alert);
            }

            function formatCreatePhone(phone) {
                const digits = (phone || '').replace(/\D/g, '');

                if (!digits.length) {
                    return '';
                }

                let normalized = digits;

                if (normalized.length === 10) {
                    normalized = '7' + normalized;
                }

                if (normalized.length !== 11) {
                    return phone || '';
                }

                const country = normalized[0];
                const city = normalized.slice(1, 4);
                const first = normalized.slice(4, 7);
                const second = normalized.slice(7, 9);
                const third = normalized.slice(9, 11);

                return '+' + country + ' (' + city + ') ' + first + '-' + second + '-' + third;
            }

            function renderCreateStatuses(statuses) {
                if (!createOrderStatusEl) return;
                createOrderStatusEl.innerHTML = '';

                Object.keys(statuses || {}).forEach(function (key) {
                    const option = document.createElement('option');
                    option.value = key;
                    option.textContent = statuses[key];
                    if (key === 'new') {
                        option.selected = true;
                    }
                    createOrderStatusEl.appendChild(option);
                });
            }

            function renderWaitlistServices(services) {
                if (!waitlistServiceEl) return;
                waitlistServiceEl.innerHTML = '';

                (services || []).forEach(function (service, index) {
                    const option = document.createElement('option');
                    option.value = String(service.id);
                    option.textContent = service.name + ' · ' + (service.duration || 0) + ' мин';
                    option.selected = index === 0;
                    waitlistServiceEl.appendChild(option);
                });
            }

            function updateCreateSummary() {
                let totalPrice = 0;
                let totalDuration = 0;
                let selectedServices = 0;

                document.querySelectorAll('.calendar-create-service-checkbox:checked').forEach(function (checkbox) {
                    totalPrice += Number(checkbox.getAttribute('data-price') || 0);
                    totalDuration += Number(checkbox.getAttribute('data-duration') || 0);
                    selectedServices += 1;
                });

                if (createOrderSummaryPriceEl) {
                    createOrderSummaryPriceEl.textContent = totalPrice.toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + ' ₽';
                }

                if (createOrderDurationEl && !createOrderDurationEl.dataset.userEdited) {
                    createOrderDurationEl.value = totalDuration || '';
                }

                updateDurationHint(totalDuration);

                if (createOrderServicesCountEl) {
                    createOrderServicesCountEl.textContent = String(selectedServices);
                }

                if (createOrderTotalPriceEl && !createOrderTotalPriceEl.dataset.userEdited) {
                    createOrderTotalPriceEl.value = totalPrice ? totalPrice.toFixed(2) : '';
                }
            }

            /**
             * Say what this set of services really takes, when the measured time
             * disagrees with the price list. The field is never changed on its own:
             * the master decides, the button just saves her the arithmetic.
             */
            function updateDurationHint(plannedDuration) {
                if (!createOrderDurationHintEl) return;

                const selected = Array.from(document.querySelectorAll('.calendar-create-service-checkbox:checked'))
                    .map(function (checkbox) { return Number(checkbox.value); })
                    .sort(function (a, b) { return a - b; })
                    .join('-');

                const estimate = durationEstimates.find(function (item) {
                    return (item.service_ids || []).slice().sort(function (a, b) { return a - b; }).join('-') === selected;
                });

                if (!selected || !estimate || estimate.minutes === plannedDuration) {
                    toggle(createOrderDurationHintEl, false);
                    return;
                }

                createOrderDurationHintTextEl.textContent = durationHintTemplate
                    .replace(':duration', humanMinutes(estimate.minutes))
                    .replace(':count', estimate.samples);
                createOrderDurationApplyEl.dataset.minutes = String(estimate.minutes);
                toggle(createOrderDurationHintEl, true);
            }

            function humanMinutes(minutes) {
                const hours = Math.floor(minutes / 60);
                const rest = minutes % 60;

                if (!hours) return rest + ' мин';

                return rest ? hours + ' ч ' + rest + ' мин' : hours + ' ч';
            }

            function renderCreateServices(services) {
                if (!createOrderServicesEl) return;

                if (!Array.isArray(services) || !services.length) {
                    createOrderServicesEl.innerHTML = '<p class="text-muted mb-0">Услуги еще не добавлены.</p>';
                    return;
                }

                createOrderServicesEl.innerHTML = '';

                services.forEach(function (service) {
                    const label = document.createElement('label');
                    label.className = 'calendar-modal-service d-flex align-items-start gap-3';
                    label.innerHTML = `
                        <input
                            type="checkbox"
                            class="form-check-input calendar-create-service-checkbox"
                            value="${service.id}"
                            data-price="${service.price || 0}"
                            data-duration="${service.duration || 0}"
                        />
                        <span class="flex-grow-1">
                            <span class="fw-semibold d-block">${service.name}</span>
                            <span class="small text-muted">~ ${service.duration || 0} мин</span>
                        </span>
                        <span class="badge bg-label-primary">${Number(service.price || 0).toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 })} ₽</span>
                    `;

                    createOrderServicesEl.appendChild(label);
                });

                document.querySelectorAll('.calendar-create-service-checkbox').forEach(function (checkbox) {
                    checkbox.addEventListener('change', updateCreateSummary);
                });

                updateCreateSummary();
            }

            function clearCreateClientResults() {
                if (!createOrderClientResultsEl) return;
                createOrderClientResultsEl.innerHTML = '';
                createOrderClientResultsEl.classList.add('d-none');
            }

            function clearCreateClientSuggestions() {
                if (!createOrderClientSuggestionsEl) return;
                createOrderClientSuggestionsEl.innerHTML = '';
                createOrderClientSuggestionsEl.classList.add('d-none');
            }

            function setCreateClientSelection(client) {
                const hasClient = Boolean(client && client.id);

                if (createOrderClientIdEl) {
                    createOrderClientIdEl.value = hasClient ? client.id : '';
                }

                if (createOrderSelectedClientEl) {
                    if (hasClient) {
                        createOrderSelectedClientEl.innerHTML = `
                            <div>
                                <div class="fw-semibold">Выбрана клиентка: ${client.name || 'Без имени'}</div>
                                <div class="small">${formatCreatePhone(client.phone || '') || 'Без телефона'}</div>
                            </div>
                        `;
                        createOrderSelectedClientEl.classList.remove('d-none');
                    } else {
                        createOrderSelectedClientEl.innerHTML = '';
                        createOrderSelectedClientEl.classList.add('d-none');
                    }
                }

                if (createOrderClientPhoneEl) {
                    createOrderClientPhoneEl.readOnly = hasClient;
                    createOrderClientPhoneEl.required = !hasClient;
                    createOrderClientPhoneEl.value = hasClient ? (client.phone || '') : '';
                }

                if (createOrderClientNameEl) {
                    createOrderClientNameEl.readOnly = hasClient;
                    createOrderClientNameEl.value = hasClient ? (client.name || '') : '';
                }

                clearCreateClientSuggestions();
            }

            function renderCreateClientResults(items, title) {
                if (!createOrderClientResultsEl) return;

                createOrderClientResultsEl.innerHTML = '';

                if (!Array.isArray(items) || !items.length) {
                    createOrderClientResultsEl.classList.add('d-none');
                    return;
                }

                const header = document.createElement('div');
                header.className = 'list-group-item small text-muted';
                header.textContent = title;
                createOrderClientResultsEl.appendChild(header);

                items.forEach(function (item) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'list-group-item list-group-item-action d-flex align-items-start justify-content-between gap-2';
                    button.innerHTML = `
                        <div class="d-flex flex-column text-start">
                            <span class="fw-medium">${item.name || 'Без имени'}</span>
                            <span class="small text-muted">${formatCreatePhone(item.phone || '') || 'Без телефона'}</span>
                        </div>
                        <span class="small text-muted">${item.last_visit_at_formatted || ''}</span>
                    `;
                    button.addEventListener('click', function () {
                        setCreateClientSelection(item);
                        if (createOrderClientSearchEl) {
                            createOrderClientSearchEl.value = item.name || item.phone || '';
                        }
                        clearCreateClientResults();
                    });
                    createOrderClientResultsEl.appendChild(button);
                });

                const createButton = document.createElement('button');
                createButton.type = 'button';
                createButton.className = 'list-group-item list-group-item-action d-flex align-items-center justify-content-between gap-2 text-primary';
                createButton.innerHTML = `
                    <span class="fw-medium">Создать нового клиента</span>
                    <i class="ri ri-user-add-line"></i>
                `;
                createButton.addEventListener('click', function () {
                    setCreateClientSelection(null);
                    clearCreateClientResults();

                    if (createOrderClientSearchEl) {
                        createOrderClientSearchEl.value = '';
                    }

                    if (createOrderClientPhoneEl) {
                        createOrderClientPhoneEl.focus();
                    }
                });
                createOrderClientResultsEl.appendChild(createButton);

                createOrderClientResultsEl.classList.remove('d-none');
            }

            function renderCreateClientSuggestions(items) {
                if (!createOrderClientSuggestionsEl) return;

                createOrderClientSuggestionsEl.innerHTML = '';

                if (!Array.isArray(items) || !items.length) {
                    createOrderClientSuggestionsEl.classList.add('d-none');
                    return;
                }

                const header = document.createElement('div');
                header.className = 'list-group-item small text-muted';
                header.textContent = 'Похожие клиентки';
                createOrderClientSuggestionsEl.appendChild(header);

                items.forEach(function (item) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'list-group-item list-group-item-action';
                    button.innerHTML = `
                        <span class="fw-medium d-block">${item.name || 'Без имени'}</span>
                        <span class="small text-muted">${formatCreatePhone(item.phone || '')}</span>
                    `;
                    button.addEventListener('click', function () {
                        if (item.id) {
                            setCreateClientSelection(item);
                            if (createOrderClientSearchEl) {
                                createOrderClientSearchEl.value = item.name || item.phone || '';
                            }
                        }
                        clearCreateClientSuggestions();
                    });
                    createOrderClientSuggestionsEl.appendChild(button);
                });

                createOrderClientSuggestionsEl.classList.remove('d-none');
            }

            let createOrderLookupController = null;
            let createOrderLookupTimer = null;
            let createOrderRecentClients = [];
            let createOrderOptionsLoaded = false;

            async function lookupCreateClient(query, mode) {
                const value = (query || '').trim();

                if (!value) {
                    clearCreateClientSuggestions();
                    if (mode === 'search') {
                        renderCreateClientResults(createOrderRecentClients, 'Недавние клиентки');
                    }
                    return;
                }

                if (mode === 'phone' && value.replace(/[^0-9]+/g, '').length < 3) {
                    clearCreateClientSuggestions();
                    return;
                }

                if (mode === 'search' && value.length < 2) {
                    renderCreateClientResults(createOrderRecentClients, 'Недавние клиентки');
                    return;
                }

                if (createOrderLookupController) {
                    createOrderLookupController.abort();
                }

                createOrderLookupController = new AbortController();

                try {
                    const params = new URLSearchParams(mode === 'phone' ? { client_phone: value } : { client_search: value });
                    const response = await fetch('/api/v1/orders/options?' + params.toString(), {
                        headers: authHeaders,
                        signal: createOrderLookupController.signal,
                    });

                    if (!response.ok) {
                        clearCreateClientSuggestions();
                        clearCreateClientResults();
                        return;
                    }

                    const data = await response.json();

                    if (mode === 'search') {
                        renderCreateClientResults(Array.isArray(data.suggestions) ? data.suggestions : [], 'Найденные клиентки');
                        clearCreateClientSuggestions();
                    } else {
                        renderCreateClientSuggestions(Array.isArray(data.suggestions) ? data.suggestions : []);
                    }

                    if (mode === 'phone' && data.client && createOrderClientNameEl && !createOrderClientNameEl.matches(':focus')) {
                        createOrderClientNameEl.value = data.client.name || '';
                    }
                } catch (error) {
                    if (error && error.name === 'AbortError') return;
                }
            }

            async function loadCreateOrderOptions() {
                if (createOrderOptionsLoaded) return;

                const response = await fetch('/api/v1/orders/options', {
                    headers: authHeaders,
                });

                if (!response.ok) {
                    showCreateAlert('danger', 'Не удалось загрузить данные для формы.');
                    return;
                }

                const data = await response.json();
                createOrderRecentClients = Array.isArray(data.recent_clients) ? data.recent_clients : [];
                durationEstimates = Array.isArray(data.duration_estimates) ? data.duration_estimates : [];
                renderCreateServices(data.services || []);
                renderCreateStatuses(data.status_options || {});
                createOrderOptionsLoaded = true;
            }

            function resetCreateOrderForm(dateStr) {
                if (!createOrderForm) return;

                createOrderForm.reset();
                clearCreateAlerts();
                clearCreateFieldErrors();
                clearCreateClientResults();
                clearCreateClientSuggestions();
                setCreateClientSelection(null);

                if (createOrderTotalPriceEl) {
                    delete createOrderTotalPriceEl.dataset.userEdited;
                }

                if (createOrderDurationEl) {
                    createOrderDurationEl.value = '';
                    delete createOrderDurationEl.dataset.userEdited;
                }

                if (createOrderWaitlistEntryIdEl) {
                    createOrderWaitlistEntryIdEl.value = '';
                }

                if (createOrderScheduledAtEl) {
                    const timePart = '10:00';
                    if (window.VeloriaDateTimePicker) {
                        window.VeloriaDateTimePicker.setValue(createOrderScheduledAtEl, (dateStr || new Date().toISOString().slice(0, 10)) + 'T' + timePart);
                    } else {
                        createOrderScheduledAtEl.value = (dateStr || new Date().toISOString().slice(0, 10)) + 'T' + timePart;
                    }
                }

                document.querySelectorAll('.calendar-create-service-checkbox').forEach(function (checkbox) {
                    checkbox.checked = false;
                });

                // Recent clients wait for the search field to be focused. Opened
                // with the form, the list only hides the fields underneath it.
                clearCreateClientResults();
                updateCreateSummary();
            }

            async function openCreateOrderModal(dateStr) {
                await loadCreateOrderOptions();
                resetCreateOrderForm(dateStr);
                if (createOrderModal) {
                    createOrderModal.show();
                }
            }

            async function loadWaitlistOptions() {
                if (waitlistOptionsLoaded) return;

                const response = await fetch('/api/v1/waitlist/options', {
                    headers: authHeaders,
                });

                if (!response.ok) {
                    showWaitlistAlert('danger', 'Не удалось загрузить данные листа ожидания.');
                    return;
                }

                const data = await response.json();
                renderWaitlistServices(data.services || []);
                waitlistOptionsLoaded = true;
            }

            function resetWaitlistForm(dateStr) {
                if (!waitlistForm) return;
                waitlistForm.reset();
                clearWaitlistAlerts();

                if (waitlistDateEl) {
                    waitlistDateEl.value = dateStr || new Date().toISOString().slice(0, 10);
                }

                if (waitlistFlexibilityEl) {
                    waitlistFlexibilityEl.value = '0';
                }

                if (waitlistPriorityEl) {
                    waitlistPriorityEl.value = '0';
                }
            }

            async function openWaitlistModal(dateStr) {
                await loadWaitlistOptions();
                resetWaitlistForm(dateStr);
                if (waitlistModal) {
                    waitlistModal.show();
                }
            }

            /**
             * Open the create form pre-filled from a waiting-list entry.
             * `preferredTime` lets a gap card offer its own window instead of the
             * first free anchor of the day.
             */
            async function bookWaitlistMatch(match, preferredTime) {
                const targetDate = selectedDate || new Date().toISOString().slice(0, 10);
                await openCreateOrderModal(targetDate);

                if (createOrderWaitlistEntryIdEl) {
                    createOrderWaitlistEntryIdEl.value = match.id;
                }

                if (createOrderClientIdEl) {
                    createOrderClientIdEl.value = '';
                }

                if (createOrderClientSearchEl) {
                    createOrderClientSearchEl.value = '';
                }

                if (createOrderClientNameEl) {
                    createOrderClientNameEl.value = match.client && match.client.name ? match.client.name : '';
                }

                if (createOrderClientPhoneEl) {
                    createOrderClientPhoneEl.value = match.client && match.client.phone ? match.client.phone : '';
                }

                if (createOrderNoteEl && match.notes) {
                    createOrderNoteEl.value = match.notes;
                }

                if (createOrderScheduledAtEl) {
                    const windowStart = match.preferred_time_windows && match.preferred_time_windows[0]
                        ? match.preferred_time_windows[0].start
                        : null;
                    const time = preferredTime || lastDayAvailableSlots[0] || windowStart || '10:00';

                    if (window.VeloriaDateTimePicker) {
                        window.VeloriaDateTimePicker.setValue(createOrderScheduledAtEl, targetDate + 'T' + time);
                    } else {
                        createOrderScheduledAtEl.value = targetDate + 'T' + time;
                    }
                }

                if (match.service && match.service.id) {
                    document.querySelectorAll('.calendar-create-service-checkbox').forEach(function (checkbox) {
                        checkbox.checked = Number(checkbox.value) === Number(match.service.id);
                    });
                    updateCreateSummary();
                }
            }

            function renderWaitlistMatches(matches) {
                if (!dayWaitlistEl) return;

                dayWaitlistEl.innerHTML = '';
                const items = Array.isArray(matches) ? matches : [];

                if (dayWaitlistBadgeEl) {
                    dayWaitlistBadgeEl.textContent = String(items.length);
                }

                // The gap cards already name these people next to the window they
                // fit. Listing them again below is the same client twice.
                toggle(dayWaitlistSectionEl, items.length > 0 && !dayHasGaps);

                items.forEach(function (match) {
                    const card = document.createElement('div');
                    card.className = 'calendar-match-card';

                    const reasons = Array.isArray(match.match_reasons) ? match.match_reasons : [];
                    const serviceName = match.service && match.service.name ? match.service.name : 'Услуга не указана';

                    card.innerHTML = `
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <div class="fw-semibold">${match.client && match.client.name ? match.client.name : 'Без имени'}</div>
                                <div class="small text-muted">${formatCreatePhone(match.client && match.client.phone ? match.client.phone : '') || 'Без телефона'}</div>
                                <div class="small mt-2">${serviceName}</div>
                            </div>
                        </div>
                    `;

                    if (reasons.length) {
                        const reasonsWrap = document.createElement('div');
                        reasonsWrap.className = 'calendar-match-reasons d-flex flex-wrap gap-2 mt-3';
                        reasons.forEach(function (reason) {
                            const pill = document.createElement('span');
                            pill.textContent = reason;
                            reasonsWrap.appendChild(pill);
                        });
                        card.appendChild(reasonsWrap);
                    }

                    if (match.notes) {
                        const note = document.createElement('div');
                        note.className = 'small text-muted mt-3';
                        note.textContent = match.notes;
                        card.appendChild(note);
                    }

                    const actionRow = document.createElement('div');
                    actionRow.className = 'd-flex justify-content-end mt-3';

                    const bookBtn = document.createElement('button');
                    bookBtn.type = 'button';
                    bookBtn.className = 'btn btn-sm btn-outline-primary';
                    bookBtn.textContent = 'Записать';
                    bookBtn.addEventListener('click', function () {
                        bookWaitlistMatch(match);
                    });
                    actionRow.appendChild(bookBtn);
                    card.appendChild(actionRow);
                    dayWaitlistEl.appendChild(card);
                });
            }

            async function loadWaitlistMatches(dateStr, timeStr) {
                if (!dateStr) {
                    renderWaitlistMatches([]);
                    return;
                }

                const url = new URL('/api/v1/waitlist/matches', window.location.origin);
                url.searchParams.set('date', dateStr);
                if (timeStr) {
                    url.searchParams.set('time', timeStr);
                }

                const response = await fetch(url.toString(), { headers: authHeaders });
                if (!response.ok) {
                    renderWaitlistMatches([]);
                    return;
                }

                const json = await response.json().catch(function () { return {}; });
                renderWaitlistMatches(json && json.data ? json.data.matches : []);
            }


            // A single notice line. The old panel rendered the same message in three
            // places at once (metric, pill and alert), so it is deliberately one node now.
            function setDayNotice(text, withAction) {
                if (!dayNoticeEl) return;
                if (dayNoticeTextEl) {
                    dayNoticeTextEl.textContent = text || '';
                }
                toggle(dayNoticeActionEl, Boolean(text) && Boolean(withAction));
                toggle(dayNoticeEl, Boolean(text));
            }

            function setDayLoading(isLoading) {
                toggle(dayLoadingEl, isLoading);
                toggle(dayContentEl, !isLoading);
                if (isLoading) {
                    setDayNotice('', false);
                    toggle(dayGapsSectionEl, false);
                }
            }

            function setDayError(hasError) {
                toggle(dayErrorEl, hasError);
                if (hasError) {
                    setDayLoading(false);
                    toggle(dayContentEl, false);
                    setDayNotice('', false);
                    if (daySummaryEl && translations.alerts && translations.alerts.day_load_failed) {
                        daySummaryEl.textContent = translations.alerts.day_load_failed;
                    }
                }
            }

            function updateCreateButton(dateStr) {
                if (!createOrderBtn) return;
                createOrderBtn.dataset.date = dateStr || '';
                createOrderBtn.disabled = !dateStr;
                if (openWaitlistBtn) {
                    openWaitlistBtn.dataset.date = dateStr || '';
                    openWaitlistBtn.disabled = !dateStr;
                }
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

                if (order.attention && order.attention.text) {
                    const attention = document.createElement('div');
                    attention.className = 'calendar-order-attention';

                    const text = document.createElement('span');
                    text.textContent = order.attention.text;
                    attention.appendChild(text);

                    const confirmBtn = document.createElement('button');
                    confirmBtn.type = 'button';
                    confirmBtn.className = 'btn btn-sm btn-link p-0 text-decoration-none';
                    confirmBtn.textContent = (order.attention.action && order.attention.action.label) || attentionConfirmLabel;
                    confirmBtn.addEventListener('click', function () {
                        openReminderSheet(order);
                    });
                    attention.appendChild(confirmBtn);

                    wrapper.appendChild(attention);
                }

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
                footer.className = 'd-flex flex-wrap justify-content-end gap-2 mt-3';

                // Start and finish are what produce a measured duration; until now
                // they lived only on the order page, so almost nobody pressed them.
                if (order.can_start) {
                    const startBtn = orderActionButton(order, 'start', orderActionLabels.start, 'calendar-timer-btn');
                    startBtn.prepend(actionIcon('play'));
                    // Starting far from the booked time records a duration that never
                    // happened, so that case asks first. Around the appointed minute
                    // a confirmation would only be in the way.
                    startBtn.dataset.confirm = order.start_needs_confirm
                        ? startConfirmTemplate
                            .replace(':time', order.scheduled_at_formatted || '')
                            .replace(':now', currentTimeLabel())
                        : '';
                    footer.appendChild(startBtn);
                }

                if (order.can_complete) {
                    // The stop square belongs to a timer that is actually running.
                    // On a booking nobody started, Finish is just bookkeeping.
                    const running = order.status === 'in_progress';
                    const completeBtn = orderActionButton(
                        order,
                        'complete',
                        orderActionLabels.complete,
                        running ? 'calendar-stop-btn' : 'calendar-ghost-btn',
                    );

                    if (running) {
                        completeBtn.prepend(actionIcon('stop'));
                    }

                    footer.appendChild(completeBtn);
                }

                if (order.can_mark_no_show) {
                    footer.appendChild(orderActionButton(order, 'no-show', orderActionLabels.noShow, 'calendar-ghost-btn'));
                }

                const openBtn = document.createElement('a');
                openBtn.className = 'btn btn-sm btn-outline-primary';
                openBtn.href = '/orders/' + order.id;
                openBtn.textContent = translations.actions.open_order;
                footer.appendChild(openBtn);

                wrapper.appendChild(footer);

                return wrapper;
            }

            // The same play/stop marks the header timer uses, so the two read as
            // one control rather than as two unrelated buttons.
            function actionIcon(kind) {
                const icon = document.createElement('span');
                icon.className = 'calendar-action-icon calendar-action-icon--' + kind;
                icon.setAttribute('aria-hidden', 'true');

                return icon;
            }

            function orderActionButton(order, action, label, styleClass) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm ' + styleClass;
                button.textContent = label;

                button.addEventListener('click', async function () {
                    if (button.dataset.confirm && !window.confirm(button.dataset.confirm)) {
                        return;
                    }

                    button.disabled = true;

                    try {
                        const response = await fetch('/api/v1/orders/' + order.id + '/' + action, {
                            method: 'POST',
                            headers: Object.assign({}, authHeaders, { 'Content-Type': 'application/json' }),
                        });

                        const json = await response.json().catch(function () { return {}; });

                        if (!response.ok) {
                            showPageFeedback('danger', (json.error && json.error.message) || translations.alerts.day_load_failed);
                            button.disabled = false;
                            return;
                        }

                        showPageFeedback('success', json.message || '');
                        calendar.refetchEvents();
                        if (selectedDate) {
                            loadDayDetails(selectedDate, { force: true });
                        }

                        // Starting or finishing a visit changes what the header
                        // timer should show, and it polls only once a minute.
                        if (window.veloriaActiveTimer) {
                            window.veloriaActiveTimer.refresh();
                        }
                    } catch (error) {
                        showPageFeedback('danger', translations.alerts.day_load_failed);
                        button.disabled = false;
                    }
                });

                return button;
            }

            // An unsold stretch between two bookings, with the people who fit it.
            /**
             * Ask a client who has missed appointments before to confirm.
             * Deterministic text, no generation, no quota — it says one thing and
             * the master can edit it before it goes anywhere.
             */
            function openReminderSheet(order) {
                if (!window.veloriaMessage || !order.client || !order.client.id) return;

                const name = order.client.name || translations.unnamedClient;
                const when = formatDateLabel(selectedDate);
                const time = order.scheduled_at_formatted || '';

                const text = reminderTemplate.replace(':name', name)
                    .replace(':date', when.toLowerCase())
                    .replace(':time', time);

                window.veloriaMessage.open({
                    clientId: order.client.card_id || order.client.id,
                    clientName: name,
                    clientPhone: order.client.phone || '',
                    channels: order.client.channels || [],
                    text: text,
                });
            }

            function renderGapCard(gap) {
                const card = document.createElement('div');
                card.className = 'calendar-gap-card';

                const head = document.createElement('div');
                head.className = 'd-flex flex-wrap align-items-baseline justify-content-between gap-2';

                const when = document.createElement('strong');
                when.textContent = gap.start + ' – ' + gap.end;
                head.appendChild(when);

                const meta = document.createElement('span');
                meta.className = 'calendar-gap-meta';
                meta.textContent = gap.label || '';
                if (gap.estimated_value) {
                    meta.textContent += ' · ' + gapValueLabel.replace(':sum', formatMoney(gap.estimated_value));
                }
                head.appendChild(meta);
                card.appendChild(head);

                const candidates = Array.isArray(gap.candidates) ? gap.candidates : [];

                candidates.forEach(function (match) {
                    const row = document.createElement('div');
                    row.className = 'calendar-gap-candidate';

                    const who = document.createElement('div');
                    const name = document.createElement('span');
                    name.className = 'fw-semibold';
                    name.textContent = (match.client && match.client.name) || translations.unnamedClient;
                    who.appendChild(name);

                    const reason = (match.match_reasons || [])[0];
                    const service = match.service && match.service.name;
                    const detail = [service, reason].filter(Boolean).join(' · ');

                    if (detail) {
                        const sub = document.createElement('div');
                        sub.className = 'calendar-order-meta small';
                        sub.textContent = detail;
                        who.appendChild(sub);
                    }

                    row.appendChild(who);

                    const buttons = document.createElement('div');
                    buttons.className = 'd-flex gap-2';

                    const writeBtn = document.createElement('button');
                    writeBtn.type = 'button';
                    writeBtn.className = 'btn btn-sm btn-link p-0 text-decoration-none';
                    writeBtn.textContent = gapWriteLabel;
                    writeBtn.addEventListener('click', function () {
                        openGapOffer(match, gap);
                    });
                    buttons.appendChild(writeBtn);

                    const bookBtn = document.createElement('button');
                    bookBtn.type = 'button';
                    bookBtn.className = 'btn btn-sm calendar-ghost-btn';
                    bookBtn.textContent = gapBookLabel;
                    bookBtn.addEventListener('click', function () {
                        bookWaitlistMatch(match, gap.slots && gap.slots.length ? gap.slots[0] : gap.start);
                    });
                    buttons.appendChild(bookBtn);

                    row.appendChild(buttons);
                    card.appendChild(row);
                });

                return card;
            }

            /**
             * Offer this window to this client. The wording is the one thing here
             * a model does better than a template, so it is the one thing it does.
             */
            function openGapOffer(match, gap) {
                if (!window.veloriaMessage || !match.client || !match.client.id) return;

                window.veloriaMessage.open({
                    clientId: match.client.id,
                    clientName: match.client.name || translations.unnamedClient,
                    clientPhone: match.client.phone || '',
                    channels: match.client.channels || [],
                    draft: {
                        intent: 'gap_offer',
                        free_day: formatGapDay(selectedDate),
                        free_slots: (gap.slots || []).slice(0, 3),
                        gap_start: gap.start,
                        gap_end: gap.end,
                        waitlist_entry_id: match.id,
                    },
                });
            }

            function renderGaps(gaps) {
                const items = Array.isArray(gaps) ? gaps : [];
                dayHasGaps = items.length > 0;

                if (!dayGapsEl) return;

                dayGapsEl.innerHTML = '';

                items.forEach(function (gap) {
                    dayGapsEl.appendChild(renderGapCard(gap));
                });

                if (dayGapsBadgeEl) {
                    dayGapsBadgeEl.textContent = items.length ? String(items.length) : '';
                }

                toggle(dayGapsSectionEl, items.length > 0);
            }

            function renderDayDetails(payload, meta) {
                const dateStr = payload.date;
                if (dayTitleEl) {
                    dayTitleEl.textContent = formatDateLabel(dateStr);
                }
                updateSelectedDatePreview(dateStr);

                const orders = Array.isArray(payload.orders) ? payload.orders : [];
                if (daySummaryEl) {
                    daySummaryEl.textContent = pluralize(translations.day.subtitle, orders.length);
                }

                const availableSlots = Array.isArray(payload.available_slots) ? payload.available_slots : [];
                lastDayAvailableSlots = availableSlots.slice();
                daySlotsEl.innerHTML = '';
                if (availableSlots.length) {
                    availableSlots.forEach(function (slot) {
                        const slotBadge = document.createElement('span');
                        slotBadge.className = 'calendar-slot-pill';
                        slotBadge.textContent = slot;
                        daySlotsEl.appendChild(slotBadge);
                    });
                }

                const settingsNotice = meta && meta.settings_notice ? meta.settings_notice : null;

                if (daySlotsBadgeEl) {
                    daySlotsBadgeEl.textContent = availableSlots.length ? String(availableSlots.length) : '';
                }

                renderGaps(payload.gaps);

                // Free time only makes sense once a schedule exists and something is left.
                toggle(daySlotsSectionEl, !settingsNotice && availableSlots.length > 0);

                dayOrdersEl.innerHTML = '';
                if (orders.length) {
                    orders.forEach(function (order) {
                        dayOrdersEl.appendChild(renderOrderCard(order));
                    });
                }
                toggle(dayOrdersEmptyEl, orders.length === 0);

                if (settingsNotice) {
                    setDayNotice(settingsNotice, true);
                } else if (payload.is_working_day === false && orders.length === 0) {
                    // is_working_day is derived from the schedule alone, so a booked day can
                    // still come back false. Never call a day with bookings a day off.
                    setDayNotice(translations.day.non_working_day_description || '', false);
                } else {
                    setDayNotice('', false);
                }

                loadWaitlistMatches(dateStr, availableSlots[0] || null);
                setDayLoading(false);
            }

            function loadDayDetails(dateStr, options) {
                if (!dateStr) return;
                const force = options && options.force;
                if (!force && selectedDate === dateStr && !dayContentEl.classList.contains('d-none')) {
                    return;
                }

                selectedDate = dateStr;
                updateSelectedDatePreview(dateStr);
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
                expandRows: false,
                dayMaxEvents: 3,
                height: 'auto',
                scrollTime: '08:00:00',
                allDayText: allDayText,
                buttonText: buttonText,
                noEventsContent: function () {
                    return { html: translations.noEvents };
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
                            updateVisibleEventsCount(events.length);
                            successCallback(events);
                        })
                        .catch(function () {
                            showEventsError(true);
                            updateVisibleEventsCount(0);
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
                        const title = calendar.view.title || '';
                        rangeLabelEl.textContent = title.charAt(0).toUpperCase() + title.slice(1);
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

                    // A month cell is ~100px wide: "Ирина Кравцова" truncates to "Ири…".
                    // Show the first name only; the tooltip above keeps the full record.
                    if (info.view.type === 'dayGridMonth') {
                        const titleEl = info.el.querySelector('.fc-event-title');
                        if (titleEl) {
                            const firstName = titleEl.textContent.trim().split(/\s+/)[0];
                            if (firstName) {
                                titleEl.textContent = firstName;
                            }
                        }
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

            if (createOrderDurationEl) {
                createOrderDurationEl.addEventListener('input', function () {
                    this.dataset.userEdited = this.value ? '1' : '';
                });
            }

            if (createOrderDurationApplyEl) {
                createOrderDurationApplyEl.addEventListener('click', function () {
                    if (!createOrderDurationEl) return;
                    createOrderDurationEl.value = this.dataset.minutes || '';
                    createOrderDurationEl.dataset.userEdited = '1';
                    toggle(createOrderDurationHintEl, false);
                });
            }

            if (createOrderClientPhoneEl) {
                createOrderClientPhoneEl.addEventListener('input', function () {
                    if (createOrderClientIdEl && createOrderClientIdEl.value) {
                        return;
                    }

                    const value = this.value.trim();

                    if (createOrderLookupTimer) {
                        clearTimeout(createOrderLookupTimer);
                    }

                    if (!value) {
                        if (createOrderClientNameEl && !createOrderClientNameEl.matches(':focus')) {
                            createOrderClientNameEl.value = '';
                        }
                        clearCreateClientSuggestions();
                        return;
                    }

                    createOrderLookupTimer = setTimeout(function () {
                        lookupCreateClient(value, 'phone');
                    }, 350);
                });
            }

            if (createOrderClientSearchEl) {
                createOrderClientSearchEl.addEventListener('input', function () {
                    const value = this.value.trim();

                    if (createOrderLookupTimer) {
                        clearTimeout(createOrderLookupTimer);
                    }

                    if (!value) {
                        if (createOrderClientIdEl && createOrderClientIdEl.value) {
                            setCreateClientSelection(null);
                        }
                        renderCreateClientResults(createOrderRecentClients, 'Недавние клиентки');
                        return;
                    }

                    if (createOrderClientIdEl && createOrderClientIdEl.value) {
                        setCreateClientSelection(null);
                    }

                    createOrderLookupTimer = setTimeout(function () {
                        lookupCreateClient(value, 'search');
                    }, 250);
                });

                createOrderClientSearchEl.addEventListener('focus', function () {
                    if (!this.value.trim()) {
                        renderCreateClientResults(createOrderRecentClients, 'Недавние клиентки');
                    }
                });
            }

            if (createOrderTotalPriceEl) {
                createOrderTotalPriceEl.addEventListener('input', function () {
                    this.dataset.userEdited = this.value ? '1' : '';
                });
            }

            if (createOrderForm) {
                createOrderForm.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    clearCreateAlerts();
                    clearCreateFieldErrors();

                    if (createOrderSubmitEl) {
                        createOrderSubmitEl.disabled = true;
                    }

                    const payload = {
                        client_id: createOrderClientIdEl && createOrderClientIdEl.value ? Number(createOrderClientIdEl.value) : null,
                        waitlist_entry_id: createOrderWaitlistEntryIdEl && createOrderWaitlistEntryIdEl.value ? Number(createOrderWaitlistEntryIdEl.value) : null,
                        client_phone: createOrderClientPhoneEl ? createOrderClientPhoneEl.value : '',
                        client_name: createOrderClientNameEl ? createOrderClientNameEl.value : '',
                        scheduled_at: createOrderScheduledAtEl ? createOrderScheduledAtEl.value : '',
                        services: Array.from(document.querySelectorAll('.calendar-create-service-checkbox:checked')).map(function (checkbox) {
                            return Number(checkbox.value);
                        }),
                        note: createOrderNoteEl ? createOrderNoteEl.value : '',
                        total_price: createOrderTotalPriceEl && createOrderTotalPriceEl.value ? Number(createOrderTotalPriceEl.value) : null,
                        duration_forecast: createOrderDurationEl && createOrderDurationEl.value ? Number(createOrderDurationEl.value) : null,
                        status: createOrderStatusEl && createOrderStatusEl.value ? createOrderStatusEl.value : 'new',
                    };

                    const response = await fetch('/api/v1/orders', {
                        method: 'POST',
                        headers: Object.assign({}, authHeaders, { 'Content-Type': 'application/json' }),
                        body: JSON.stringify(payload),
                    });

                    const result = await response.json().catch(function () {
                        return {};
                    });

                    if (!response.ok) {
                        // Two envelopes reach us from the same endpoint: BaseRequest
                        // wraps its failures as error.fields, while the ones thrown
                        // inside the controller — the booking conflict above all —
                        // arrive as Laravel's plain errors bag.
                        const fields = (result.error && result.error.fields) || result.errors || {};
                        const message = (result.error && result.error.message)
                            || result.message
                            || 'Не удалось создать запись.';

                        // A message under the field it belongs to says everything
                        // the banner would, so the banner is for what has no field.
                        if (!showCreateFieldErrors(fields)) {
                            showCreateAlert('danger', message);
                        }

                        if (createOrderSubmitEl) {
                            createOrderSubmitEl.disabled = false;
                        }
                        return;
                    }

                    if (createOrderModal) {
                        createOrderModal.hide();
                    }

                    showPageFeedback('success', result.message || 'Запись создана.');
                    calendar.refetchEvents();

                    if (createOrderScheduledAtEl && createOrderScheduledAtEl.value) {
                        const createdDate = createOrderScheduledAtEl.value.slice(0, 10);
                        selectedDate = createdDate;
                        updateSelectedDatePreview(createdDate);
                        loadDayDetails(createdDate, { force: true });
                        calendar.gotoDate(createdDate);
                        calendar.select(createdDate);
                    } else if (selectedDate) {
                        loadDayDetails(selectedDate, { force: true });
                    }

                    if (createOrderSubmitEl) {
                        createOrderSubmitEl.disabled = false;
                    }
                });
            }

            document.addEventListener('click', function (event) {
                if (
                    createOrderClientSuggestionsEl &&
                    !createOrderClientSuggestionsEl.classList.contains('d-none') &&
                    event.target !== createOrderClientPhoneEl &&
                    !createOrderClientSuggestionsEl.contains(event.target)
                ) {
                    clearCreateClientSuggestions();
                }

                if (
                    createOrderClientResultsEl &&
                    !createOrderClientResultsEl.classList.contains('d-none') &&
                    event.target !== createOrderClientSearchEl &&
                    !createOrderClientResultsEl.contains(event.target)
                ) {
                    clearCreateClientResults();
                }
            });

            if (createOrderBtn) {
                createOrderBtn.addEventListener('click', function () {
                    const date = createOrderBtn.dataset.date;
                    if (!date) return;
                    openCreateOrderModal(date);
                });
            }

            document.querySelectorAll('[data-calendar-create]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!selectedDate) return;
                    openCreateOrderModal(selectedDate);
                });
            });

            if (openWaitlistBtn) {
                openWaitlistBtn.addEventListener('click', function () {
                    const date = openWaitlistBtn.dataset.date;
                    if (!date) return;
                    openWaitlistModal(date);
                });
            }

            if (waitlistForm) {
                waitlistForm.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    clearWaitlistAlerts();

                    if (waitlistSubmitEl) {
                        waitlistSubmitEl.disabled = true;
                    }

                    const payload = {
                        client_name: waitlistClientNameEl ? waitlistClientNameEl.value.trim() : '',
                        client_phone: waitlistClientPhoneEl ? waitlistClientPhoneEl.value.trim() : '',
                        client_email: waitlistClientEmailEl ? waitlistClientEmailEl.value.trim() : '',
                        service_id: waitlistServiceEl && waitlistServiceEl.value ? Number(waitlistServiceEl.value) : null,
                        preferred_dates: waitlistDateEl && waitlistDateEl.value ? [waitlistDateEl.value] : [],
                        preferred_time_windows: (waitlistTimeStartEl && waitlistTimeStartEl.value && waitlistTimeEndEl && waitlistTimeEndEl.value)
                            ? [{ start: waitlistTimeStartEl.value, end: waitlistTimeEndEl.value }]
                            : [],
                        flexibility_days: waitlistFlexibilityEl && waitlistFlexibilityEl.value ? Number(waitlistFlexibilityEl.value) : 0,
                        priority_manual: waitlistPriorityEl && waitlistPriorityEl.value ? Number(waitlistPriorityEl.value) : 0,
                        notes: waitlistNotesEl ? waitlistNotesEl.value.trim() : '',
                        source: 'manual',
                    };

                    const response = await fetch('/api/v1/waitlist', {
                        method: 'POST',
                        headers: Object.assign({}, authHeaders, { 'Content-Type': 'application/json' }),
                        body: JSON.stringify(payload),
                    });

                    const result = await response.json().catch(function () {
                        return {};
                    });

                    if (!response.ok) {
                        showWaitlistAlert('danger', (result.error && result.error.message) || 'Не удалось добавить клиента в лист ожидания.');
                        if (waitlistSubmitEl) {
                            waitlistSubmitEl.disabled = false;
                        }
                        return;
                    }

                    if (waitlistModal) {
                        waitlistModal.hide();
                    }

                    showPageFeedback('success', result.message || 'Клиент добавлен в лист ожидания.');
                    if (selectedDate) {
                        loadWaitlistMatches(selectedDate, lastDayAvailableSlots[0] || null);
                    }

                    if (waitlistSubmitEl) {
                        waitlistSubmitEl.disabled = false;
                    }
                });
            }

            // Something changed a booking elsewhere — the header timer, most likely.
            document.addEventListener('veloria:order-changed', function () {
                calendar.refetchEvents();

                if (selectedDate) {
                    loadDayDetails(selectedDate, { force: true });
                }
            });

            updateSelectedDatePreview(new Date().toISOString().slice(0, 10));
        });
    </script>
@endsection
