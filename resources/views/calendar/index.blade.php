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
            --calendar-accent-soft: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.12);
            --calendar-success-soft: rgba(var(--bs-success-rgb, 40, 199, 111), 0.12);
            --calendar-card-shadow: 0 20px 48px -34px rgba(37, 26, 84, 0.5);
        }

        .calendar-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(var(--bs-primary-rgb, 255, 0, 252), 0.14);
            border-radius: 1.5rem;
            padding: 1.5rem;
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.16), transparent 36%),
                linear-gradient(135deg, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08), rgba(var(--bs-primary-rgb, 255, 0, 252), 0.02) 52%, rgba(var(--bs-success-rgb, 40, 199, 111), 0.06));
            box-shadow: var(--calendar-card-shadow);
        }

        .calendar-hero::after {
            content: '';
            position: absolute;
            right: -3rem;
            bottom: -4rem;
            width: 12rem;
            height: 12rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08);
            filter: blur(8px);
        }

        .calendar-hero__content,
        .calendar-hero__actions {
            position: relative;
            z-index: 1;
        }

        .calendar-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.68);
            color: var(--bs-body-color);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .calendar-eyebrow i {
            color: var(--bs-primary);
        }

        .calendar-hero__title {
            font-size: clamp(1.85rem, 2.6vw, 2.65rem);
            line-height: 1.05;
            letter-spacing: -0.03em;
        }

        .calendar-overview {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.9rem;
        }

        .calendar-overview-card {
            border: 1px solid rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08);
            border-radius: 1.05rem;
            padding: 1rem 1.05rem;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.76);
            backdrop-filter: blur(6px);
        }

        .calendar-overview-card span {
            display: block;
            color: var(--bs-secondary-color);
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.45rem;
        }

        .calendar-overview-card strong {
            display: block;
            font-size: 1rem;
            line-height: 1.3;
        }

        .calendar-surface {
            border: none;
            border-radius: 1.4rem;
            background: color-mix(in srgb, var(--bs-card-bg) 92%, transparent);
            box-shadow: var(--calendar-card-shadow);
        }

        .calendar-panel-header {
            padding: 1.15rem 1.25rem 0;
        }

        .calendar-card-body {
            display: flex;
            padding: 0 1.1rem 1.1rem;
        }

        .calendar-toolbar-note {
            color: var(--bs-secondary-color);
            font-size: 0.9rem;
        }

        .calendar-segmented {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            padding: 0.35rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 999px;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.68);
        }

        .calendar-segmented .btn {
            border: none;
            border-radius: 999px;
            color: var(--bs-secondary-color);
            font-weight: 700;
            padding-inline: 1rem;
            box-shadow: none !important;
        }

        .calendar-segmented .btn.btn-primary {
            color: #fff;
        }

        .calendar-soft-btn {
            border: 1px solid var(--bs-border-color);
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.7);
            color: var(--bs-body-color);
        }

        .calendar-soft-btn:hover,
        .calendar-soft-btn:focus {
            border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.28);
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.06);
            color: var(--bs-primary);
        }

        #crm-calendar {
            flex: 1 1 auto;
            min-height: 680px;
            height: 100%;
        }

        #crm-calendar .fc {
            height: 100%;
        }

        #crm-calendar .fc .fc-view-harness {
            min-height: 600px;
            height: 100%;
        }

        #crm-calendar .fc .fc-highlight {
            background-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.12);
        }

        #crm-calendar .fc .fc-daygrid-day.fc-day-today {
            background: linear-gradient(180deg, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.1), rgba(var(--bs-primary-rgb, 255, 0, 252), 0.03));
        }

        #crm-calendar .fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
            width: 2rem;
            height: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.12);
            color: var(--bs-primary);
            font-weight: 700;
        }

        #crm-calendar .fc-theme-standard td,
        #crm-calendar .fc-theme-standard th {
            border-color: rgba(var(--bs-border-color-rgb, 160, 169, 192), 0.55);
        }

        #crm-calendar .fc .fc-scrollgrid,
        #crm-calendar .fc-theme-standard .fc-scrollgrid {
            border-color: rgba(var(--bs-border-color-rgb, 160, 169, 192), 0.55);
            border-radius: 1rem;
            overflow: hidden;
        }

        #crm-calendar .fc .fc-col-header,
        #crm-calendar .fc .fc-col-header-cell,
        #crm-calendar .fc .fc-timegrid-axis,
        #crm-calendar .fc .fc-list-table thead tr {
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.68);
        }

        #crm-calendar .fc .fc-col-header-cell-cushion,
        #crm-calendar .fc .fc-timegrid-axis-cushion {
            color: var(--bs-secondary-color);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding-block: 0.75rem;
        }

        #crm-calendar .fc .fc-daygrid-day-frame {
            min-height: 8rem;
            padding: 0.35rem;
        }

        #crm-calendar .fc .fc-daygrid-day-top {
            justify-content: flex-end;
            padding: 0.15rem 0.2rem 0;
        }

        #crm-calendar .fc .fc-daygrid-day-number {
            color: var(--bs-secondary-color);
            font-weight: 600;
            padding: 0.2rem;
        }

        #crm-calendar .fc .fc-daygrid-event,
        #crm-calendar .fc .fc-timegrid-event {
            border: none;
            border-radius: 0.85rem;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.12);
            color: var(--bs-body-color);
            box-shadow: none;
            padding: 0.15rem 0.25rem;
        }

        #crm-calendar .fc .fc-daygrid-event:hover,
        #crm-calendar .fc .fc-timegrid-event:hover {
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.18);
        }

        #crm-calendar .fc .fc-event-main {
            font-weight: 600;
        }

        #crm-calendar .fc .fc-event-time {
            color: var(--bs-primary);
            font-weight: 700;
        }

        .calendar-day-card {
            position: sticky;
            top: 5.75rem;
        }

        .calendar-day-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .calendar-day-metric {
            border-radius: 1rem;
            padding: 0.9rem;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.68);
            border: 1px solid rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08);
        }

        .calendar-day-metric span {
            display: block;
            color: var(--bs-secondary-color);
            font-size: 0.74rem;
            margin-bottom: 0.35rem;
        }

        .calendar-day-metric strong {
            display: block;
            font-size: 1.1rem;
            line-height: 1.15;
        }

        .calendar-day-status {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            padding: 0.45rem 0.8rem;
            font-size: 0.82rem;
            font-weight: 700;
            background: rgba(var(--bs-secondary-color-rgb, 130, 134, 158), 0.12);
            color: var(--bs-secondary-color);
        }

        .calendar-day-status.is-primary {
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.09);
            color: var(--bs-primary);
        }

        .calendar-day-status.is-success {
            background: rgba(var(--bs-success-rgb, 40, 199, 111), 0.12);
            color: var(--bs-success);
        }

        .calendar-section-card {
            border: 1px solid rgba(var(--bs-border-color-rgb, 160, 169, 192), 0.55);
            border-radius: 1.1rem;
            padding: 1rem;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.52);
        }

        .calendar-slot-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: var(--calendar-success-soft);
            color: var(--bs-success);
            font-weight: 700;
            font-size: 0.82rem;
        }

        .calendar-order-card {
            border: 1px solid rgba(var(--bs-border-color-rgb, 160, 169, 192), 0.55);
            border-radius: 1rem;
            padding: 1rem 1.05rem;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.78);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .calendar-order-card:hover {
            transform: translateY(-1px);
            border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.22);
            box-shadow: 0 18px 35px -30px rgba(37, 26, 84, 0.58);
        }

        .calendar-order-card .calendar-order-meta {
            color: var(--bs-secondary-color);
        }

        .calendar-match-card {
            border: 1px solid rgba(var(--bs-primary-rgb, 255, 0, 252), 0.12);
            border-radius: 1rem;
            padding: 1rem;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.04);
        }

        .calendar-match-reasons span {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.3rem 0.6rem;
            background: rgba(var(--bs-body-color-rgb, 88, 96, 116), 0.08);
            color: var(--bs-secondary-color);
            font-size: 0.74rem;
            font-weight: 600;
        }

        .calendar-order-card .calendar-order-services span {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            background-color: var(--calendar-accent-soft);
            color: var(--bs-primary-color);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .calendar-order-card .calendar-order-services span i {
            font-size: 0.85rem;
        }


        .calendar-create-modal .modal-content {
            border: none;
            border-radius: 1.4rem;
            overflow: hidden;
            box-shadow: 0 30px 80px -45px rgba(37, 26, 84, 0.65);
        }

        .calendar-create-modal .modal-header,
        .calendar-create-modal .modal-footer {
            border-color: rgba(var(--bs-border-color-rgb, 160, 169, 192), 0.5);
        }

        .calendar-create-modal .modal-body {
            background: color-mix(in srgb, var(--bs-card-bg) 94%, transparent);
        }

        .calendar-modal-search-layer {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .calendar-modal-results,
        .calendar-modal-suggestions {
            position: static;
            z-index: 1;
            max-height: 260px;
            overflow-y: auto;
            margin: 0;
            border: 1px solid rgba(var(--bs-border-color-rgb, 160, 169, 192), 0.55);
            border-radius: 1rem;
            background: var(--bs-body-bg);
            box-shadow: 0 20px 40px -30px rgba(37, 26, 84, 0.4);
        }

        .calendar-modal-services {
            max-height: 320px;
            overflow-y: auto;
            padding-right: 0.15rem;
        }

        .calendar-modal-service {
            border: 1px solid rgba(var(--bs-border-color-rgb, 160, 169, 192), 0.55);
            border-radius: 1rem;
            padding: 0.9rem 1rem;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.78);
            transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .calendar-modal-service:hover {
            transform: translateY(-1px);
            border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.2);
            box-shadow: 0 16px 32px -28px rgba(37, 26, 84, 0.45);
        }

        .calendar-modal-service input {
            margin-top: 0.1rem;
        }

        .calendar-modal-summary {
            border: 1px solid rgba(var(--bs-primary-rgb, 255, 0, 252), 0.12);
            border-radius: 1rem;
            padding: 1rem;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.06);
        }

        .calendar-modal-summary strong {
            font-size: 1.05rem;
        }

        @media (max-width: 1199.98px) {
            .calendar-day-card {
                position: static;
            }
        }

        @media (max-width: 991.98px) {
            .calendar-hero,
            .calendar-surface {
                border-radius: 1.2rem;
            }

            .calendar-overview,
            .calendar-day-summary-grid {
                grid-template-columns: 1fr;
            }

            #crm-calendar {
                min-height: 560px;
            }

            #crm-calendar .fc .fc-view-harness {
                min-height: 520px;
            }

            #crm-calendar .fc .fc-daygrid-day-frame {
                min-height: 6.5rem;
            }
        }

        @media (max-width: 575.98px) {
            .calendar-hero {
                padding: 1.15rem;
            }

            .calendar-panel-header {
                padding: 1rem 1rem 0;
            }

            .calendar-card-body {
                padding: 0 0.75rem 0.75rem;
            }

            .calendar-segmented {
                width: 100%;
                justify-content: space-between;
            }

            .calendar-segmented .btn {
                flex: 1 1 calc(50% - 0.25rem);
                padding-inline: 0.75rem;
            }

            #crm-calendar {
                min-height: 520px;
            }
        }
    </style>
@endsection

@section('content')
    <div class="calendar-page">
        <section class="calendar-hero mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-12 col-xl-7">
                    <div class="calendar-hero__content">
                        <div class="calendar-eyebrow mb-3">
                            <i class="ri ri-sparkling-2-line"></i>
                            {{ __('calendar.page.title') }}
                        </div>
                        <h1 class="calendar-hero__title mb-2">{{ __('calendar.page.title') }}</h1>
                        <p class="text-muted mb-4 fs-5">{{ __('calendar.page.subtitle') }}</p>

                        <div class="calendar-overview">
                            <div class="calendar-overview-card">
                                <span>Период</span>
                                <strong id="calendar-range-label">-</strong>
                            </div>
                            <div class="calendar-overview-card">
                                <span>Выбранный день</span>
                                <strong id="calendar-selected-date-label">-</strong>
                            </div>
                            <div class="calendar-overview-card">
                                <span>Записей в периоде</span>
                                <strong id="calendar-visible-events-count">0</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-5">
                    <div class="calendar-hero__actions d-flex flex-column gap-3">
                        <div class="d-flex flex-wrap justify-content-xl-end gap-2">
                            <button type="button" class="btn calendar-soft-btn" data-calendar-nav="prev" aria-label="{{ __('calendar.actions.previous') }}">
                                <i class="ri ri-arrow-left-s-line"></i>
                            </button>
                            <button type="button" class="btn calendar-soft-btn" data-calendar-nav="next" aria-label="{{ __('calendar.actions.next') }}">
                                <i class="ri ri-arrow-right-s-line"></i>
                            </button>
                            <button type="button" class="btn calendar-soft-btn" id="calendar-refresh" aria-label="{{ __('calendar.actions.refresh') }}">
                                <i class="ri ri-refresh-line"></i>
                            </button>
                            <button type="button" class="btn btn-primary" id="calendar-today">
                                <i class="ri ri-calendar-event-line me-1"></i>
                                {{ __('calendar.actions.today') }}
                            </button>
                        </div>
                        <div class="d-flex flex-wrap justify-content-xl-end gap-2">
                            <span class="badge rounded-pill bg-label-primary px-3 py-2" id="calendar-active-view-label">{{ __('calendar.views.month') }}</span>
                            <span class="badge rounded-pill bg-label-secondary px-3 py-2">Нажмите на день, чтобы увидеть детали</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div id="calendar-events-error" class="alert alert-danger d-none mb-4" role="alert">
            {{ __('calendar.alerts.events_load_failed') }}
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-12 col-xl-8">
                <div class="card calendar-surface h-100">
                    <div class="calendar-panel-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                        <div class="calendar-segmented mb-2" role="group" aria-label="{{ __('calendar.views.month') }}">
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
                    <div class="calendar-card-body">
                        <div id="crm-calendar"></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card calendar-surface calendar-day-card">
                    <div class="card-body p-4">
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                                <div>
                                    <div class="text-uppercase text-muted small fw-semibold mb-2">{{ __('calendar.day.panel_title') }}</div>
                                    <h4 class="mb-1" id="calendar-day-title">-</h4>
                                    <p class="text-muted mb-0" id="calendar-day-summary">{{ $calendarDayTranslations['subtitle']['zero'] }}</p>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-primary" id="calendar-open-waitlist" disabled>
                                        <i class="ri ri-timer-flash-line me-1"></i>
                                        Умный waitlist
                                    </button>
                                    <button type="button" class="btn btn-primary" id="calendar-create-order" disabled>
                                        <i class="ri ri-add-line me-1"></i>
                                        {{ __('calendar.actions.create_order') }}
                                    </button>
                                </div>
                            </div>

                            <div class="calendar-day-summary-grid">
                                <div class="calendar-day-metric">
                                    <span>Записи</span>
                                    <strong id="calendar-day-orders-count">0</strong>
                                </div>
                                <div class="calendar-day-metric">
                                    <span>Свободно</span>
                                    <strong id="calendar-day-slots-count">0</strong>
                                </div>
                                <div class="calendar-day-metric">
                                    <span>Статус</span>
                                    <strong id="calendar-day-status-text">-</strong>
                                </div>
                            </div>

                            <div class="calendar-day-status" id="calendar-day-status-badge">
                                <i class="ri ri-time-line"></i>
                                <span id="calendar-day-status-label">Выберите день в календаре</span>
                            </div>

                            <div id="calendar-day-loading" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">{{ $calendarDayTranslations['loading'] }}</span>
                                </div>
                            </div>
                            <div id="calendar-day-error" class="alert alert-danger d-none mb-0" role="alert">
                                {{ __('calendar.alerts.day_load_failed') }}
                            </div>
                            <div id="calendar-day-settings" class="alert alert-warning d-none mb-0" role="alert"></div>
                            <div id="calendar-day-non-working" class="alert alert-info d-none mb-0" role="alert">
                                <div class="fw-semibold mb-1">{{ __('calendar.day.non_working_day') }}</div>
                                <div class="mb-0">{{ __('calendar.day.non_working_day_description') }}</div>
                            </div>

                            <div id="calendar-day-content" class="d-none">
                                <div class="calendar-section-card mb-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="mb-0">{{ __('calendar.day.free_slots_title') }}</h6>
                                        <span class="badge bg-label-primary" id="calendar-day-slots-badge">0</span>
                                    </div>
                                    <p class="text-muted small mb-3" id="calendar-day-slots-hint">{{ __('calendar.day.free_slots_hint') }}</p>
                                    <div id="calendar-day-slots" class="d-flex flex-wrap gap-2"></div>
                                    <div id="calendar-day-slots-empty" class="text-muted small d-none">
                                        {{ __('calendar.day.free_slots_empty') }}
                                    </div>
                                </div>

                                <div class="calendar-section-card">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h6 class="mb-0">{{ __('calendar.day.orders_title') }}</h6>
                                        <span class="badge bg-label-secondary" id="calendar-day-orders-badge">0</span>
                                    </div>
                                    <div id="calendar-day-orders" class="d-flex flex-column gap-3"></div>
                                    <div id="calendar-day-orders-empty" class="text-muted small d-none">
                                        {{ __('calendar.day.orders_empty') }}
                                    </div>
                                </div>

                                <div class="calendar-section-card mt-3">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h6 class="mb-0">Умный лист ожидания</h6>
                                        <span class="badge bg-label-warning" id="calendar-day-waitlist-badge">0</span>
                                    </div>
                                    <p class="text-muted small mb-3">Клиенты, которым выбранный день подходит лучше всего.</p>
                                    <div id="calendar-day-waitlist" class="d-flex flex-column gap-3"></div>
                                    <div id="calendar-day-waitlist-empty" class="text-muted small d-none">
                                        Пока нет подходящих клиентов в листе ожидания.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <div class="modal fade calendar-create-modal" id="calendar-create-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header px-4 py-3">
                        <div>
                            <h5 class="modal-title mb-1">Новая запись</h5>
                            <p class="text-muted mb-0 small">Создайте запись, не покидая календарь.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="calendar-create-form">
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

                                        <div class="col-md-6 d-none">
                                            <div class="form-floating form-floating-outline">
                                                <input
                                                    type="datetime-local"
                                                    class="form-control"
                                                    id="calendar-create-scheduled-at-legacy"
                                                    name="scheduled_at_legacy"
                                                    required
                                                />
                                                <label for="calendar-create-scheduled-at">Дата и время</label>
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
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted">Предварительная сумма</span>
                                            <strong id="calendar-create-summary-price">0 ₽</strong>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted">Прогноз времени</span>
                                            <strong id="calendar-create-summary-duration">0 мин</strong>
                                        </div>
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
                            <h5 class="modal-title mb-1">Умный лист ожидания</h5>
                            <p class="text-muted mb-0 small">Добавьте клиента, чтобы быстро закрывать отмены и свободные окна.</p>
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

@endsection

@section('scripts')
    @include('components.phone-mask-script')
    @include('components.veloria-datetime-picker-script')
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
            const daySettingsEl = document.getElementById('calendar-day-settings');
            const dayNonWorkingEl = document.getElementById('calendar-day-non-working');
            const daySlotsCountEl = document.getElementById('calendar-day-slots-count');
            const daySlotsBadgeEl = document.getElementById('calendar-day-slots-badge');
            const daySlotsEl = document.getElementById('calendar-day-slots');
            const daySlotsHintEl = document.getElementById('calendar-day-slots-hint');
            const daySlotsEmptyEl = document.getElementById('calendar-day-slots-empty');
            const dayOrdersEl = document.getElementById('calendar-day-orders');
            const dayOrdersEmptyEl = document.getElementById('calendar-day-orders-empty');
            const dayOrdersCountEl = document.getElementById('calendar-day-orders-count');
            const dayOrdersBadgeEl = document.getElementById('calendar-day-orders-badge');
            const dayWaitlistEl = document.getElementById('calendar-day-waitlist');
            const dayWaitlistEmptyEl = document.getElementById('calendar-day-waitlist-empty');
            const dayWaitlistBadgeEl = document.getElementById('calendar-day-waitlist-badge');
            const dayStatusTextEl = document.getElementById('calendar-day-status-text');
            const dayStatusBadgeEl = document.getElementById('calendar-day-status-badge');
            const dayStatusLabelEl = document.getElementById('calendar-day-status-label');
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
            const createOrderSummaryDurationEl = document.getElementById('calendar-create-summary-duration');
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

            function setActiveViewButton(viewName) {
                viewButtons.forEach(function (btn) {
                    const matches = btn.getAttribute('data-calendar-view') === viewName;
                    btn.classList.toggle('btn-primary', matches);
                    btn.classList.toggle('text-white', matches);
                    btn.classList.toggle('btn-outline-secondary', !matches);
                });

                updateActiveViewLabel(viewName);
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

                if (createOrderSummaryDurationEl) {
                    createOrderSummaryDurationEl.textContent = totalDuration + ' мин';
                }

                if (createOrderServicesCountEl) {
                    createOrderServicesCountEl.textContent = String(selectedServices);
                }

                if (createOrderTotalPriceEl && !createOrderTotalPriceEl.dataset.userEdited) {
                    createOrderTotalPriceEl.value = totalPrice ? totalPrice.toFixed(2) : '';
                }
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
                renderCreateServices(data.services || []);
                renderCreateStatuses(data.status_options || {});
                renderCreateClientResults(createOrderRecentClients, 'Недавние клиентки');
                createOrderOptionsLoaded = true;
            }

            function resetCreateOrderForm(dateStr) {
                if (!createOrderForm) return;

                createOrderForm.reset();
                clearCreateAlerts();
                clearCreateClientResults();
                clearCreateClientSuggestions();
                setCreateClientSelection(null);

                if (createOrderTotalPriceEl) {
                    delete createOrderTotalPriceEl.dataset.userEdited;
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

                renderCreateClientResults(createOrderRecentClients, 'Недавние клиентки');
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
                    showWaitlistAlert('danger', 'Не удалось загрузить данные для waitlist.');
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

            function renderWaitlistMatches(matches) {
                if (!dayWaitlistEl) return;

                dayWaitlistEl.innerHTML = '';
                const items = Array.isArray(matches) ? matches : [];

                if (dayWaitlistBadgeEl) {
                    dayWaitlistBadgeEl.textContent = String(items.length);
                }

                toggle(dayWaitlistEmptyEl, items.length === 0);

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
                            <span class="badge bg-label-primary">Score ${match.match_score || 0}</span>
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
                    bookBtn.addEventListener('click', async function () {
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
                            const time = lastDayAvailableSlots[0] || (match.preferred_time_windows && match.preferred_time_windows[0] ? match.preferred_time_windows[0].start : '10:00');
                            if (window.VeloriaDateTimePicker) {
                                window.VeloriaDateTimePicker.setValue(createOrderScheduledAtEl, targetDate + 'T' + (time || '10:00'));
                            } else {
                                createOrderScheduledAtEl.value = targetDate + 'T' + (time || '10:00');
                            }
                        }

                        if (match.service && match.service.id) {
                            document.querySelectorAll('.calendar-create-service-checkbox').forEach(function (checkbox) {
                                checkbox.checked = Number(checkbox.value) === Number(match.service.id);
                            });
                            updateCreateSummary();
                        }
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


            function updateDayStatus(state, label) {
                if (dayStatusTextEl) {
                    dayStatusTextEl.textContent = label || '-';
                }

                if (dayStatusLabelEl) {
                    dayStatusLabelEl.textContent = label || 'Выберите день в календаре';
                }

                if (dayStatusBadgeEl) {
                    dayStatusBadgeEl.classList.remove('is-primary', 'is-success');
                    if (state === 'primary') {
                        dayStatusBadgeEl.classList.add('is-primary');
                    } else if (state === 'success') {
                        dayStatusBadgeEl.classList.add('is-success');
                    }
                }
            }

            function setDayLoading(isLoading) {
                toggle(dayLoadingEl, isLoading);
                toggle(dayContentEl, !isLoading);
                if (isLoading) {
                    toggle(daySettingsEl, false);
                    toggle(dayNonWorkingEl, false);
                    updateDayStatus('primary', translations.day.loading || 'Загружаем данные дня...');
                }
            }

            function setDayError(hasError) {
                toggle(dayErrorEl, hasError);
                if (hasError) {
                    setDayLoading(false);
                    toggle(dayContentEl, false);
                    toggle(daySettingsEl, false);
                    toggle(dayNonWorkingEl, false);
                    updateDayStatus(null, translations.alerts.day_load_failed || 'Не удалось получить данные по дню.');
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
                if (daySettingsEl) {
                    daySettingsEl.textContent = settingsNotice || '';
                    toggle(daySettingsEl, Boolean(settingsNotice));
                }

                if (daySlotsHintEl) {
                    toggle(daySlotsHintEl, !settingsNotice);
                }

                const slotsText = settingsNotice ? '-' : String(availableSlots.length);
                if (daySlotsCountEl) {
                    daySlotsCountEl.textContent = slotsText;
                }
                if (daySlotsBadgeEl) {
                    daySlotsBadgeEl.textContent = slotsText;
                }

                if (dayOrdersCountEl) {
                    dayOrdersCountEl.textContent = String(orders.length);
                }
                if (dayOrdersBadgeEl) {
                    dayOrdersBadgeEl.textContent = String(orders.length);
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

                if (settingsNotice) {
                    updateDayStatus('primary', settingsNotice);
                } else if (payload.is_working_day === false) {
                    updateDayStatus(null, translations.day.non_working_day || 'Выходной день');
                } else if (orders.length > 0) {
                    updateDayStatus('success', pluralize(translations.day.subtitle, orders.length));
                } else {
                    updateDayStatus('primary', translations.day.free_slots_title || 'Свободные слоты');
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
                expandRows: true,
                dayMaxEvents: 3,
                height: '100%',
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
                        showCreateAlert('danger', (result.error && result.error.message) || 'Не удалось создать запись.');
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
                        showWaitlistAlert('danger', (result.error && result.error.message) || 'Не удалось добавить клиента в waitlist.');
                        if (waitlistSubmitEl) {
                            waitlistSubmitEl.disabled = false;
                        }
                        return;
                    }

                    if (waitlistModal) {
                        waitlistModal.hide();
                    }

                    showPageFeedback('success', result.message || 'Клиент добавлен в waitlist.');
                    if (selectedDate) {
                        loadWaitlistMatches(selectedDate, lastDayAvailableSlots[0] || null);
                    }

                    if (waitlistSubmitEl) {
                        waitlistSubmitEl.disabled = false;
                    }
                });
            }

            updateSelectedDatePreview(new Date().toISOString().slice(0, 10));
            updateDayStatus(null, 'Выберите день в календаре');
        });
    </script>
@endsection
