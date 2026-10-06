@extends('layouts.app')

@php
    $commonTimezones = [
        'Europe/Kaliningrad' => 'Калининград (MSK−1)',
        'Europe/Moscow' => 'Москва (MSK)',
        'Europe/Samara' => 'Самара (MSK+1)',
        'Asia/Yekaterinburg' => 'Екатеринбург (MSK+2)',
        'Asia/Omsk' => 'Омск (MSK+3)',
        'Asia/Krasnoyarsk' => 'Красноярск (MSK+4)',
        'Asia/Irkutsk' => 'Иркутск (MSK+5)',
        'Asia/Yakutsk' => 'Якутск (MSK+6)',
        'Asia/Vladivostok' => 'Владивосток (MSK+7)',
        'Asia/Magadan' => 'Магадан (MSK+8)',
        'Asia/Kamchatka' => 'Камчатка (MSK+9)',
        'Europe/Minsk' => 'Минск',
        'Europe/Kyiv' => 'Киев',
        'Asia/Almaty' => 'Алматы',
        'Asia/Tbilisi' => 'Тбилиси',
    ];
@endphp

@section('content')
    <style>
        .settings-anchor-nav .nav-link {
            border-radius: 999px;
            white-space: nowrap;
            position: relative;
        }

        /* A tab with edits that are not saved yet. */
        .settings-tab-dot {
            display: inline-block;
            width: 0.5rem;
            height: 0.5rem;
            margin-left: 0.5rem;
            border-radius: 50%;
            background: var(--bs-warning);
        }

        .settings-tab-dot[hidden] {
            display: none;
        }

        .settings-page {
            --settings-text: color-mix(in srgb, var(--bs-heading-color) 82%, transparent);
            --settings-faint: color-mix(in srgb, var(--bs-heading-color) 74%, transparent);
        }

        /* The label of a field used to run at 2.3:1 — the palest text on a page
           made of fields. */
        .settings-page .form-floating > label,
        .settings-page .form-label {
            color: var(--settings-text);
        }

        /* A switch that cannot be switched should not offer a hand cursor. */
        .settings-page .form-check-input:disabled,
        .settings-page .form-check-input:disabled ~ .form-check-label {
            cursor: not-allowed;
        }

        /* The locked Pro and Elite cards used to take half of the notifications
           block with decorative skeleton bars. */
        .settings-feature-lock .elite-lock-preview {
            display: none;
        }

        .settings-feature-lock .elite-lock-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        /* The only button of a six-screen form now travels with the reader,
           and answers where it stands. */
        .settings-actionbar {
            position: sticky;
            bottom: 0;
            z-index: 5;
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1.5rem;
            margin-bottom: 0;
            padding: 0.85rem 1.1rem;
            box-shadow: 0 -10px 26px -22px rgba(0, 0, 0, 0.75);
        }

        /* «Открыть поля» broke across two lines in its own button. */
        #toggle-password-fields {
            white-space: nowrap;
        }

        /* An empty message container still spent its bottom margin. */
        #form-messages:empty {
            display: none;
        }

        /* On a phone the four sections took four full rows of their own. */
        @media (max-width: 767.98px) {
            .settings-anchor-nav .nav {
                flex-direction: row;
                flex-wrap: nowrap;
                overflow-x: auto;
                gap: 0.5rem;
            }

            .settings-anchor-nav .nav-link {
                white-space: nowrap;
            }
        }

        .settings-actionbar__state {
            flex: 1 1 12rem;
            min-width: 0;
            font-size: 0.9rem;
            color: var(--settings-faint);
        }

        .settings-actionbar__state.is-dirty {
            color: var(--bs-warning-text-emphasis, #8a5a00);
            font-weight: 600;
        }

        .settings-actionbar__state.is-ok {
            color: var(--bs-success-text-emphasis, var(--bs-success));
            font-weight: 600;
        }

        .settings-actionbar__state.is-bad {
            color: var(--bs-danger-text-emphasis, var(--bs-danger));
            font-weight: 600;
        }

        .settings-slot-error {
            display: block;
            margin-top: 0.35rem;
            font-size: 0.82rem;
            color: var(--bs-danger-text-emphasis, var(--bs-danger));
        }

        .settings-card {
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
        }

        .settings-section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .settings-meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.75rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb), 0.08);
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--bs-body-color);
        }

        .settings-compact-note {
            padding: 0.9rem 1rem;
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb), 0.04);
            height: 100%;
        }

        .settings-password-toggle {
            border: 1px dashed rgba(var(--bs-body-color-rgb), 0.14);
            border-radius: 1rem;
        }

        .settings-password-fields {
            display: none;
        }

        .settings-password-fields.is-visible {
            display: flex;
        }

        .settings-danger-card {
            border: 1px solid rgba(var(--bs-danger-rgb), 0.18);
        }

        .settings-work-table th,
        .settings-work-table td {
            vertical-align: middle;
        }

        .settings-summary-card {
            position: sticky;
            /* The card was already sticky, but at 1.5rem it travelled under the
               fixed navbar. */
            top: 5.5rem;
        }

        .settings-feature-card {
            border: 1px solid rgba(var(--bs-primary-rgb), 0.14);
            border-radius: 1.25rem;
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.12), transparent 32%),
                rgba(var(--bs-primary-rgb), 0.04);
        }

        .settings-feature-list {
            display: grid;
            gap: 0.75rem;
        }

        .settings-feature-list-item {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
        }

        .settings-feature-lock {
            border: 1px dashed rgba(var(--bs-warning-rgb), 0.36);
            border-radius: 1.25rem;
            background: rgba(var(--bs-warning-rgb), 0.08);
        }

        .allergy-reminder-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.95fr);
            gap: 1.25rem;
            align-items: start;
        }

        .allergy-reminder-copy {
            display: grid;
            gap: 1rem;
        }

        .allergy-reminder-controls {
            display: grid;
            gap: 1rem;
        }

        .allergy-reminder-panel {
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
            border-radius: 1rem;
            background: rgba(var(--bs-body-bg-rgb), 0.34);
            padding: 1rem;
        }

        .allergy-reminder-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .allergy-reminder-toggle-copy {
            display: grid;
            gap: 0.2rem;
        }

        .allergy-reminder-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.45rem;
        }

        .allergy-reminder-help {
            font-size: 0.8125rem;
            color: var(--settings-faint);
            margin-top: 0.45rem;
        }

        .allergy-reminder-select {
            min-height: 10rem;
        }

        .allergy-reminder-note {
            border-radius: 0.9rem;
            padding: 0.85rem 1rem;
            background: rgba(var(--bs-primary-rgb), 0.08);
            font-size: 0.875rem;
        }

        .schedule-mode-switch {
            display: inline-flex;
            padding: 0.25rem;
            gap: 0.25rem;
            border-radius: 999px;
            background: rgba(var(--bs-body-color-rgb), 0.05);
        }

        .schedule-mode-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1.1rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--settings-faint);
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .schedule-mode-pill.is-active {
            background: var(--bs-primary);
            color: #fff;
        }

        .schedule-panel {
            display: none;
            padding: 1.25rem;
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb), 0.02);
        }

        .schedule-panel.is-active {
            display: block;
        }

        .schedule-help {
            flex: 0 0 auto;
            font-size: 0.8125rem;
            color: var(--settings-faint);
        }

        .wd-step {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex: 0 0 auto;
        }

        .wd-step .form-select {
            width: auto;
        }

        .wd-row {
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
        }

        .wd-row:last-child {
            border-bottom: 0;
        }

        .wd-row__main {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem 1rem;
        }

        .wd-row__day {
            flex: 0 0 11rem;
            margin: 0;
        }

        .wd-simple,
        .wd-manual {
            display: none;
            align-items: center;
            gap: 0.5rem;
        }

        .wd-manual {
            flex: 1 1 16rem;
        }

        .wd-simple .form-select {
            width: 6.5rem;
        }

        .wd-breaks {
            display: none;
            flex-direction: column;
            gap: 0.4rem;
            margin: 0.5rem 0 0 12rem;
        }

        .wd-break {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .wd-break-remove {
            padding-inline: 0.5rem;
            font-size: 1.25rem;
            line-height: 1;
            text-decoration: none;
        }

        .mday .wd-breaks,
        .mday .wd-row__foot {
            margin-left: 0;
        }

        .wd-break .form-select {
            width: 5.75rem;
        }

        .wd-row__foot {
            margin-left: 12rem;
        }

        .wd-row__foot:empty,
        .wd-row.is-off .wd-row__foot {
            display: none;
        }

        .wd-row.is-on:not(.is-manual) .wd-simple,
        .wd-row.is-on.is-manual .wd-manual {
            display: flex;
        }

        .wd-row.is-on:not(.is-manual) .wd-breaks:not(:empty) {
            display: flex;
        }

        .wd-row.is-on .wd-off {
            display: none;
        }

        .cycle-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .cycle-grid .form-label {
            font-size: 0.875rem;
        }

        .schedule-calendar-legend {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.8125rem;
            color: var(--settings-faint);
        }

        .schedule-dot {
            display: inline-block;
            width: 0.7rem;
            height: 0.7rem;
            margin-right: 0.4rem;
            border-radius: 50%;
            background: rgba(var(--bs-body-color-rgb), 0.12);
            vertical-align: middle;
        }

        .schedule-dot.is-work {
            background: var(--bs-primary);
        }

        .schedule-dot.is-override {
            position: relative;
        }

        .schedule-dot.is-override::after {
            content: '';
            position: absolute;
            inset: -3px;
            border: 1.5px solid var(--bs-body-color);
            border-radius: 50%;
            opacity: 0.55;
        }

        .mcal {
            max-width: 26rem;
        }

        .mcal__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        /* "capitalize" turned «2026 г.» into «2026 Г.»: only the first letter goes up. */
        .mcal__title {
            display: inline-block;
        }

        .mcal__title::first-letter {
            text-transform: uppercase;
        }

        .mcal__grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.3rem;
            text-align: center;
        }

        .mcal__dow {
            font-size: 0.72rem;
            color: var(--settings-faint);
            text-transform: uppercase;
        }

        .mcal-day {
            aspect-ratio: 1;
            padding: 0;
            border: 1px solid transparent;
            border-radius: 0.6rem;
            background: transparent;
            color: var(--bs-body-color);
            font-size: 0.875rem;
            cursor: pointer;
        }

        .mcal-day:hover {
            background: rgba(var(--bs-primary-rgb), 0.12);
        }

        .mcal-day.is-today {
            border-color: rgba(var(--bs-body-color-rgb), 0.45);
        }

        .mcal-day.is-work {
            background: var(--bs-primary);
            color: #fff;
            font-weight: 600;
        }

        .mcal-day.is-active {
            outline: 2px solid var(--bs-body-color);
            outline-offset: 1px;
        }

        /* A date picked by hand, whether its value matches the rule or not. */
        .mcal-day.is-override::after {
            content: '';
            display: block;
            width: 0.3rem;
            height: 0.3rem;
            margin: 0.15rem auto 0;
            border-radius: 50%;
            background: currentColor;
            opacity: 0.6;
        }

        .mday-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .mday-chip {
            padding: 0.3rem 0.8rem;
            border: 1px solid transparent;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb), 0.1);
            color: var(--bs-body-color);
            font-size: 0.875rem;
        }

        .mday-chip:hover {
            background: rgba(var(--bs-primary-rgb), 0.18);
        }

        .mday-chip.is-active {
            border-color: var(--bs-body-color);
        }

        .mday {
            margin-top: 1.25rem;
            padding: 1rem 1.25rem;
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.12);
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb), 0.03);
        }

        .holiday-form {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 0.75rem 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.1);
            border-radius: 1rem;
        }

        .holiday-form[hidden] {
            display: none;
        }

        .holiday-form > div:not(.holiday-form__actions) {
            flex: 1 1 11rem;
        }

        .holiday-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .holiday-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.4rem 0.3rem 0.8rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb), 0.1);
            font-size: 0.875rem;
        }

        .holiday-chip button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            padding: 0;
            border: 0;
            border-radius: 50%;
            background: transparent;
            color: inherit;
            font-size: 1.1rem;
            line-height: 1;
        }

        .holiday-chip button:hover {
            background: rgba(var(--bs-body-color-rgb), 0.12);
        }

        .wd-manual-toggle {
            display: none;
        }

        .wd-row.is-manual .wd-manual-toggle {
            display: inline;
        }

        .wd-simple {
            flex-wrap: wrap;
        }

        @media (max-width: 575.98px) {
            .wd-row__day {
                flex-basis: 100%;
            }

            .wd-simple .form-select {
                width: 6rem;
            }

            .wd-break {
                gap: 0.3rem;
            }

            .wd-break .form-select {
                width: 4.9rem;
                padding-left: 0.5rem;
                padding-right: 1.6rem;
                background-position: right 0.4rem center;
            }

            .wd-break > .small {
                font-size: 0.72rem;
            }

            .wd-breaks,
            .wd-row__foot {
                margin-left: 0;
            }

            .cycle-grid {
                grid-template-columns: 1fr;
            }
        }

        html[data-bs-theme="dark"] .settings-feature-card {
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.18), transparent 34%),
                rgba(var(--bs-primary-rgb), 0.08);
        }

        html[data-bs-theme="dark"] .settings-feature-lock {
            background: rgba(var(--bs-warning-rgb), 0.12);
        }

        html[data-bs-theme="dark"] .allergy-reminder-panel {
            background: rgba(var(--bs-body-bg-rgb), 0.2);
            border-color: rgba(255, 255, 255, 0.08);
        }

        html[data-bs-theme="dark"] .allergy-reminder-note {
            background: rgba(var(--bs-primary-rgb), 0.14);
        }

        html[data-bs-theme="dark"] .schedule-panel {
            background: rgba(var(--bs-primary-rgb), 0.1);
        }

        @media (max-width: 991.98px) {
            .allergy-reminder-layout {
                grid-template-columns: 1fr;
            }

            .allergy-reminder-toggle {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

    <div class="row g-6 settings-page">
        <div class="col-12">
            <h3 class="mb-4">{{ __('menu.settings') }}</h3>
            <div class="nav-align-top settings-anchor-nav">
                {{-- Tabs, not anchors: one section on screen at a time; the hash keeps the tab on reload. --}}
                <ul class="nav nav-pills flex-row flex-nowrap overflow-auto mb-0 gap-2" role="tablist" id="settings-tabs">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" href="#settings-account" role="tab" data-settings-tab="settings-account" aria-selected="true"><i class="icon-base ri ri-group-line icon-sm me-2"></i>{{ __('settings.nav_account') }}<span class="settings-tab-dot" hidden></span></a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" href="#settings-notifications" role="tab" data-settings-tab="settings-notifications" aria-selected="false"><i class="icon-base ri ri-notification-4-line icon-sm me-2"></i>{{ __('settings.nav_notifications') }}<span class="settings-tab-dot" hidden></span></a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" href="#settings-work" role="tab" data-settings-tab="settings-work" aria-selected="false"><i class="icon-base ri ri-calendar-line icon-sm me-2"></i>{{ __('settings.work_settings') }}<span class="settings-tab-dot" hidden></span></a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" href="#settings-location" role="tab" data-settings-tab="settings-location" aria-selected="false"><i class="icon-base ri ri-map-pin-line icon-sm me-2"></i>{{ __('settings.address') }}<span class="settings-tab-dot" hidden></span></a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" href="#settings-branding" role="tab" data-settings-tab="settings-branding" aria-selected="false"><i class="icon-base ri ri-smartphone-line icon-sm me-2"></i>Приложение<span class="settings-tab-dot" hidden></span></a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-xl-8">
            <form id="settings-form" onsubmit="return false">
                <div id="form-messages" class="mb-6"></div>

                <div class="card mb-6 settings-card" id="settings-account">
                    <div class="card-body p-5 p-lg-6">
                        <div class="settings-section-title">
                            <div>
                                <h5 class="mb-1">Профиль аккаунта</h5>
                                <p class="text-muted mb-0">Только основные данные, которые нужны для связи с вами и работы системы.</p>
                            </div>
                            <span class="settings-meta-chip"><i class="icon-base ri ri-shield-user-line"></i> Основное</span>
                        </div>

                        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-6 mb-6">
                            <div class="avatar w-px-100 h-px-100 rounded-4 overflow-hidden" id="uploadedAvatar">
                                <img alt="user-avatar" class="w-100 h-100 d-none" id="uploadedAvatarImg" />
                                <span class="avatar-initial w-100 h-100 rounded-4 bg-primary text-white fw-semibold d-flex align-items-center justify-content-center fs-2" id="uploadedAvatarInitials">?</span>
                            </div>
                            <div class="button-wrapper">
                                <label for="upload" class="btn btn-primary me-3 mb-3" tabindex="0">
                                    <span class="d-none d-sm-block">{{ __('settings.upload_photo') }}</span>
                                    <i class="icon-base ri ri-upload-2-line d-block d-sm-none"></i>
                                    <input type="file" id="upload" class="account-file-input" hidden accept="image/png, image/jpeg" />
                                </label>
                                <button type="button" class="btn btn-outline-danger account-image-reset mb-3">
                                    <i class="icon-base ri ri-delete-bin-line d-block d-sm-none"></i>
                                    <span class="d-none d-sm-block">Удалить фото</span>
                                </button>
                                <div class="text-muted small">{{ __('settings.allowed_formats') }}</div>
                            </div>
                        </div>

                        <div class="row g-5">
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="name" name="name" />
                                    <label for="name">{{ __('settings.name') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="email" class="form-control" id="email" name="email" />
                                    <label for="email">{{ __('settings.email') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="phone" name="phone" data-phone-mask placeholder="+7(999)999-99-99" />
                                    <label for="phone">{{ __('settings.phone') }}</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating form-floating-outline">
                                    {{-- 419 zones in one flat list; the eleven Russian ones come first. --}}
                                    <select id="timezone" name="timezone" class="form-select">
                                        <optgroup label="Россия и соседние страны">
                                            @foreach($commonTimezones as $tz => $label)
                                                <option value="{{ $tz }}">{{ $label }}</option>
                                            @endforeach
                                        </optgroup>
                                        <optgroup label="Все часовые пояса">
                                            @foreach(timezone_identifiers_list() as $tz)
                                                <option value="{{ $tz }}">{{ $tz }}</option>
                                            @endforeach
                                        </optgroup>
                                    </select>
                                    <label for="timezone">{{ __('settings.timezone') }}</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating form-floating-outline">
                                    <select id="time_format" name="time_format" class="form-select">
                                        <option value="24h">24h</option>
                                        <option value="12h">12h</option>
                                    </select>
                                    <label for="time_format">{{ __('settings.time_format') }}</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="settings-password-toggle p-4">
                                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                                        <div>
                                            <h6 class="mb-1">Сменить пароль</h6>
                                            <p class="text-muted mb-0">Поля ниже нужны только если вы действительно хотите поменять пароль.</p>
                                        </div>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="toggle-password-fields">Открыть поля</button>
                                    </div>
                                    <div class="row g-4 mt-1 settings-password-fields" id="settings-password-fields">
                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="password" class="form-control" id="current_password" name="current_password" />
                                                <label for="current_password">{{ __('settings.current_password') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="password" class="form-control" id="new_password" name="new_password" />
                                                <label for="new_password">{{ __('settings.new_password') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" />
                                                <label for="new_password_confirmation">{{ __('settings.password_confirmation') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-6 settings-card" id="settings-notifications">
                    <div class="card-body p-5 p-lg-6">
                        <div class="settings-section-title">
                            <div>
                                <h5 class="mb-1">Уведомления</h5>
                                <p class="text-muted mb-0">Выберите каналы, по которым хотите получать события о записях и сообщениях.</p>
                            </div>
                            <span class="settings-meta-chip"><i class="icon-base ri ri-mail-open-line"></i> Каналы</span>
                        </div>

                        <div class="row g-5">
                            <div class="col-md-4">
                                <div class="settings-compact-note">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="notif-email" />
                                        <label class="form-check-label fw-semibold" for="notif-email">{{ __('settings.email_notifications') }}</label>
                                    </div>
                                    <small class="text-muted d-block">Письма будут приходить на email из профиля.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="settings-compact-note">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="notif-telegram" />
                                        <label class="form-check-label fw-semibold" for="notif-telegram">{{ __('settings.telegram_notifications') }}</label>
                                    </div>
                                    <small class="text-muted d-block">Канал доступен после подключения Telegram в интеграциях.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="settings-compact-note">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="notif-sms" />
                                        <label class="form-check-label fw-semibold" for="notif-sms">{{ __('settings.sms_notifications') }}</label>
                                    </div>
                                    <small class="text-muted d-block">Нужен телефон и подключённый SMS-провайдер.</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating form-floating-outline">
                                    <textarea class="form-control" id="reminder_message" name="reminder_message" style="height: 140px"></textarea>
                                    <label for="reminder_message">{{ __('settings.reminder_message') }}</label>
                                </div>
                                <small class="text-muted">{{ __('settings.reminder_message_hint') }}</small>
                            </div>
                            <div class="col-12">
                                <div class="settings-feature-card p-4 d-none" id="allergy-reminders-pro">
                                    <div class="allergy-reminder-layout">
                                        <div class="allergy-reminder-copy">
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                <span class="badge bg-label-primary">Pro / Elite</span>
                                                <span class="badge bg-label-secondary">Безопасность</span>
                                            </div>
                                            <h6 class="mb-2">Автонапоминания об аллергии</h6>
                                            <p class="text-muted mb-0">Перед визитом мастер получит отдельное уведомление, если в карточке клиента указаны аллергии. Настройку можно оставить совсем простой: включить напоминание и выбрать время.</p>
                                            <div class="settings-feature-list small">
                                                <div class="settings-feature-list-item">
                                                    <i class="ri ri-check-line text-primary mt-1"></i>
                                                    <span>Напоминание приходит только по клиентам с заполненным блоком аллергий.</span>
                                                </div>
                                                <div class="settings-feature-list-item">
                                                    <i class="ri ri-check-line text-primary mt-1"></i>
                                                    <span>Исключения помогают не дублировать контроль там, где вы уже работаете по отдельному протоколу.</span>
                                                </div>
                                            </div>
                                            <div class="allergy-reminder-note">
                                                Если исключения не нужны, оставьте поля ниже пустыми. Напоминание всё равно будет работать.
                                            </div>
                                        </div>
                                        <div class="allergy-reminder-controls">
                                            <div class="allergy-reminder-panel">
                                                <div class="allergy-reminder-toggle">
                                                    <div class="allergy-reminder-toggle-copy">
                                                        <strong>Напоминать мастеру перед записью</strong>
                                                        <span class="text-muted small">Включите один раз, дальше напоминания будут приходить автоматически.</span>
                                                    </div>
                                                    <div class="form-check form-switch m-0">
                                                        <input class="form-check-input" type="checkbox" id="allergy_reminder_enabled" name="allergy_reminder_enabled" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="allergy-reminder-panel">
                                                <label class="allergy-reminder-label" for="allergy_reminder_minutes">Когда прислать напоминание</label>
                                                <div class="input-group">
                                                    <input type="number" min="1" max="1440" class="form-control" id="allergy_reminder_minutes" name="allergy_reminder_minutes" value="15" />
                                                    <span class="input-group-text">минут до записи</span>
                                                </div>
                                                <div class="allergy-reminder-help">Обычно достаточно 10-15 минут, чтобы мастер успел обратить внимание перед началом процедуры.</div>
                                            </div>
                                            <div class="allergy-reminder-panel">
                                                <label class="allergy-reminder-label" for="allergy_reminder_service_exclusions">Какие услуги не учитывать</label>
                                                <select class="form-select allergy-reminder-select" id="allergy_reminder_service_exclusions" name="allergy_reminder_service_exclusions" multiple size="5"></select>
                                                <div class="allergy-reminder-help">Выберите только те услуги, где отдельное напоминание не нужно. Поле можно не заполнять.</div>
                                            </div>
                                            <div class="allergy-reminder-panel">
                                                <label class="allergy-reminder-label" for="allergy_reminder_allergy_exclusions">Какие аллергии пропускать</label>
                                                <textarea class="form-control" id="allergy_reminder_allergy_exclusions" name="allergy_reminder_allergy_exclusions" rows="3" placeholder="Например: пыльца, латекс"></textarea>
                                                <div class="allergy-reminder-help">Укажите через запятую или с новой строки. Если такой пункт есть у клиента, напоминание не придёт.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 d-none" id="allergy-reminders-locked-shared">
                                @include('components.elite-lock-card', [
                                    'wrapperClass' => 'settings-feature-lock p-4',
                                    'badge' => 'Pro / Elite',
                                    'title' => 'Автонапоминания об аллергии',
                                    'description' => 'Система предупредит мастера перед записью, если у клиента есть отмеченные аллергии. Функция доступна на тарифах Pro и Elite.',
                                    'cta' => 'Открыть тарифы',
                                    'buttonClass' => 'btn btn-outline-primary',
                                ])
                            </div>
                            <div class="col-12">
                                <div class="settings-feature-card p-4 d-none" id="daily-post-ideas-elite">
                                    <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
                                        <div class="me-lg-4">
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                <span class="badge bg-label-primary">Elite</span>
                                                <span class="badge bg-label-secondary">AI-контент</span>
                                            </div>
                                            <h6 class="mb-2">Ежедневные идеи для постов</h6>
                                            <p class="text-muted mb-3">Система будет присылать короткие идеи для Telegram или публикаций на платформе, чтобы не искать темы каждый день вручную.</p>
                                            <div class="settings-feature-list small">
                                                <div class="settings-feature-list-item">
                                                    <i class="ri ri-check-line text-primary mt-1"></i>
                                                    <span>Подсказки приходят каждый день и помогают быстро выбрать тему поста.</span>
                                                </div>
                                                <div class="settings-feature-list-item">
                                                    <i class="ri ri-check-line text-primary mt-1"></i>
                                                    <span>Идеи подходят и для Telegram, и для публикаций внутри платформы.</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="settings-compact-note flex-grow-1">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" id="daily_post_ideas_enabled" name="daily_post_ideas_enabled" />
                                                <label class="form-check-label fw-semibold" for="daily_post_ideas_enabled">Получать идеи каждый день</label>
                                            </div>
                                            <small class="text-muted d-block">Можно отключить в любой момент. Пока это работает как ежедневная AI-подборка внутри Elite.</small>
                                            <div class="mt-4">
                                                <label for="daily_post_ideas_channel" class="form-label fw-semibold">Куда готовить идеи</label>
                                                <select class="form-select" id="daily_post_ideas_channel" name="daily_post_ideas_channel">
                                                    <option value="both">Telegram и платформа</option>
                                                    <option value="telegram">Только Telegram</option>
                                                    <option value="platform">Только платформа</option>
                                                </select>
                                            </div>
                                            <div class="mt-3">
                                                <label for="daily_post_ideas_preferences" class="form-label fw-semibold">Темы и пожелания для ИИ</label>
                                                <textarea class="form-control" id="daily_post_ideas_preferences" name="daily_post_ideas_preferences" rows="4" placeholder="Например: идеи про уход за волосами, сезонные процедуры, мягкий экспертный тон, короткие тексты с CTA на запись."></textarea>
                                                <small class="text-muted d-block mt-2">Опишите темы, формат, тон и акценты. Эти вводные будут использоваться для генерации ежедневных идей.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 d-none" id="daily-post-ideas-locked-shared">
                                @include('components.elite-lock-card', [
                                    'wrapperClass' => 'settings-feature-lock p-4',
                                    'title' => 'Ежедневные идеи для постов',
                                    'description' => 'Автоматические идеи для Telegram и публикаций на платформе доступны только на тарифе Elite.',
                                    'cta' => 'Перейти на Elite',
                                    'buttonClass' => 'btn btn-outline-primary',
                                ])
                            </div>
                            <div class="col-12">
                                <div class="alert alert-primary mt-1 mb-0">
                                    {{ __('integrations.moved_notice') }}
                                    <a href="{{ route('integrations') }}" class="alert-link">{{ __('menu.integrations') }}</a>.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-6 settings-card" id="settings-work">
                    <div class="card-body p-5 p-lg-6">
                        <div class="settings-section-title">
                            <div>
                                <h5 class="mb-1">{{ __('settings.work_settings') }}</h5>
                                <p class="text-muted mb-0">Этот график влияет на свободные слоты в календаре и расчёт доступного времени.</p>
                            </div>
                            <span class="settings-meta-chip"><i class="icon-base ri ri-time-line"></i> Расписание</span>
                        </div>

                        {{-- The two radios stay as the state holder for which rule generates the calendar below; a picked date always overrides whichever one is active. --}}
                        <div class="schedule-mode-switch mb-4" role="radiogroup" aria-label="{{ __('settings.schedule_mode_label') }}">
                            <label class="schedule-mode-pill" for="schedule-mode-weekly" data-schedule-card="weekly">
                                <input class="visually-hidden" type="radio" name="schedule_mode" id="schedule-mode-weekly" value="weekly" checked />
                                <span>{{ __('settings.schedule_mode_weekly') }}</span>
                            </label>
                            <label class="schedule-mode-pill" for="schedule-mode-cycle" data-schedule-card="cycle">
                                <input class="visually-hidden" type="radio" name="schedule_mode" id="schedule-mode-cycle" value="cycle" />
                                <span>{{ __('settings.schedule_mode_cycle') }}</span>
                            </label>
                        </div>

                        <template id="wd-break-template">
                            <div class="wd-break">
                                <span class="small text-muted">{{ __('settings.schedule_break_label') }}</span>
                                <select class="form-select form-select-sm wd-bs" aria-label="{{ __('settings.schedule_break_label') }} {{ __('settings.from') }}"></select>
                                <span class="wd-dash">—</span>
                                <select class="form-select form-select-sm wd-be" aria-label="{{ __('settings.schedule_break_label') }} {{ __('settings.to') }}"></select>
                                <button type="button" class="btn btn-link btn-sm text-muted wd-break-remove" aria-label="{{ __('settings.schedule_break_remove') }}" title="{{ __('settings.schedule_break_remove') }}">&times;</button>
                            </div>
                        </template>

                        {{-- Shared by the weekly and the shift schedule; the custom-month dates carry their own times. --}}
                        <div class="wd-step mb-4" id="schedule-step-wrap">
                            <label for="weekly-step" class="small text-muted">{{ __('settings.schedule_step') }}</label>
                            <select class="form-select form-select-sm" id="weekly-step">
                                @foreach([15, 30, 45, 60, 90, 120] as $minutes)
                                    <option value="{{ $minutes }}">{{ __('settings.schedule_step_option', ['minutes' => $minutes]) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <details class="settings-password-toggle p-4 mb-5" id="schedule-rule-details">
                            <summary class="fw-semibold cursor-pointer">{{ __('settings.schedule_rule_summary') }}</summary>
                            <div class="pt-4">
                                <div class="schedule-panel is-active" data-schedule-panel="weekly">
                                    <p class="text-muted mb-4">{{ __('settings.schedule_simple_hint') }}</p>

                                    <div class="wd-list">
                                        @foreach(['mon','tue','wed','thu','fri','sat','sun'] as $day)
                                        <div class="wd-row" data-day-row="{{ $day }}">
                                            <div class="wd-row__main">
                                                <div class="form-check form-switch wd-row__day">
                                                    <input class="form-check-input weekly-day-check" type="checkbox" id="weekly-day-{{ $day }}" data-day="{{ $day }}" />
                                                    <label class="form-check-label" for="weekly-day-{{ $day }}">{{ __('settings.day_' . $day) }}</label>
                                                </div>
                                                <span class="wd-off text-muted">{{ __('settings.schedule_day_off') }}</span>
                                                <div class="wd-simple">
                                                    <select class="form-select wd-from" data-day="{{ $day }}" aria-label="{{ __('settings.from') }}"></select>
                                                    <span class="wd-dash">—</span>
                                                    <select class="form-select wd-to" data-day="{{ $day }}" aria-label="{{ __('settings.to') }}"></select>
                                                    <button type="button" class="btn btn-link btn-sm wd-break-add" data-day="{{ $day }}">{{ __('settings.schedule_break_add') }}</button>
                                                </div>
                                                <div class="wd-manual">
                                                    <input type="text" class="form-control weekly-day-slots" data-day="{{ $day }}" placeholder="09:00, 10:00, 15:30" />
                                                </div>
                                            </div>
                                            <div class="wd-breaks" data-day="{{ $day }}"></div>
                                            <div class="wd-row__foot">
                                                <span class="settings-slot-error" data-slot-error="{{ $day }}" hidden></span>
                                                <button type="button" class="btn btn-link btn-sm p-0 wd-manual-toggle" data-day="{{ $day }}"></button>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>

                                    <div class="d-flex flex-wrap gap-3 mt-4">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="weekly-copy-all">{{ __('settings.schedule_copy_all') }}</button>
                                        <button type="button" class="btn btn-link btn-sm px-0" id="weekly-manual-all">{{ __('settings.schedule_manual_on') }}</button>
                                    </div>
                                </div>

                                <div class="schedule-panel" data-schedule-panel="cycle">
                                    <p class="text-muted mb-4">{{ __('settings.schedule_cycle_intro') }}</p>

                                    <div class="cycle-grid">
                                        <div>
                                            <label for="cycle_work_days" class="form-label">{{ __('settings.schedule_cycle_q_work') }}</label>
                                            <input type="number" min="1" max="31" class="form-control" id="cycle_work_days" />
                                        </div>
                                        <div>
                                            <label for="cycle_rest_days" class="form-label">{{ __('settings.schedule_cycle_q_rest') }}</label>
                                            <input type="number" min="1" max="31" class="form-control" id="cycle_rest_days" />
                                        </div>
                                        <div>
                                            <label for="cycle_anchor_date" class="form-label">{{ __('settings.schedule_cycle_q_anchor') }}</label>
                                            <input type="date" class="form-control" id="cycle_anchor_date" />
                                        </div>
                                    </div>

                                    <div class="wd-list mt-4">
                                        <div class="wd-row is-on" data-day-row="cycle">
                                            <div class="wd-row__main">
                                                <span class="wd-row__day fw-semibold">{{ __('settings.work_hours') }}</span>
                                                <div class="wd-simple">
                                                    <select class="form-select wd-from" data-day="cycle" aria-label="{{ __('settings.from') }}"></select>
                                                    <span class="wd-dash">—</span>
                                                    <select class="form-select wd-to" data-day="cycle" aria-label="{{ __('settings.to') }}"></select>
                                                    <button type="button" class="btn btn-link btn-sm wd-break-add" data-day="cycle">{{ __('settings.schedule_break_add') }}</button>
                                                </div>
                                                <div class="wd-manual">
                                                    <input type="text" class="form-control weekly-day-slots" id="cycle_slots" data-day="cycle" placeholder="09:00, 10:00, 15:30" />
                                                </div>
                                            </div>
                                            <div class="wd-breaks" data-day="cycle"></div>
                                            <div class="wd-row__foot">
                                                <span class="settings-slot-error" data-slot-error="cycle" hidden></span>
                                                <button type="button" class="btn btn-link btn-sm p-0 wd-manual-toggle" data-day="cycle"></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <div class="schedule-calendar-block mb-5">
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-3">
                                <div>
                                    <h6 class="mb-1">{{ __('settings.schedule_calendar_title') }}</h6>
                                    <p class="text-muted small mb-0">{{ __('settings.schedule_calendar_hint') }}</p>
                                </div>
                                <span class="schedule-calendar-legend">
                                    <span><i class="schedule-dot is-work"></i>{{ __('settings.schedule_calendar_legend_work') }}</span>
                                    <span><i class="schedule-dot"></i>{{ __('settings.schedule_calendar_legend_off') }}</span>
                                    <span><i class="schedule-dot is-override"></i>{{ __('settings.schedule_calendar_legend_override') }}</span>
                                </span>
                            </div>

                            <div class="mcal">
                                <div class="mcal__head">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="mcal-prev" aria-label="{{ __('settings.schedule_monthly_prev') }}">&lsaquo;</button>
                                    <span class="fw-semibold mcal__title" id="mcal-title"></span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="mcal-next" aria-label="{{ __('settings.schedule_monthly_next') }}">&rsaquo;</button>
                                </div>
                                <div class="mcal__grid" id="mcal-grid"></div>
                                <div class="small text-muted mt-3" id="mcal-summary"></div>
                                <div class="mday-chips" id="mday-chips"></div>
                            </div>

                            <div class="mday" id="mday" hidden>
                                <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                                    <span class="fw-semibold" id="mday-title"></span>
                                    <button type="button" class="btn btn-link btn-sm p-0" id="mday-close">{{ __('settings.schedule_monthly_done') }}</button>
                                </div>
                                <div class="wd-list">
                                    <div class="wd-row" data-day-row="mday">
                                        <div class="wd-row__main">
                                            <div class="form-check form-switch wd-row__day">
                                                <input class="form-check-input" type="checkbox" id="mday-work-toggle" />
                                                <label class="form-check-label" for="mday-work-toggle">{{ __('settings.schedule_mday_work_label') }}</label>
                                            </div>
                                            <span class="wd-off text-muted">{{ __('settings.schedule_day_off') }}</span>
                                            <div class="wd-simple">
                                                <select class="form-select wd-from" data-day="mday" aria-label="{{ __('settings.from') }}"></select>
                                                <span class="wd-dash">—</span>
                                                <select class="form-select wd-to" data-day="mday" aria-label="{{ __('settings.to') }}"></select>
                                                <button type="button" class="btn btn-link btn-sm wd-break-add" data-day="mday">{{ __('settings.schedule_break_add') }}</button>
                                            </div>
                                            <div class="wd-manual">
                                                <input type="text" class="form-control weekly-day-slots" data-day="mday" placeholder="09:00, 10:00, 15:30" />
                                            </div>
                                        </div>
                                        <div class="wd-breaks" data-day="mday"></div>
                                        <div class="wd-row__foot">
                                            <span class="settings-slot-error" data-slot-error="mday" hidden></span>
                                            <button type="button" class="btn btn-link btn-sm p-0 wd-manual-toggle" data-day="mday"></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="mday-reset" hidden>{{ __('settings.schedule_reset_to_rule') }}</button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5" id="holidays-block">
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-3">
                                <div>
                                    <h6 class="mb-1">{{ __('settings.holidays_title') }}</h6>
                                    <p class="text-muted small mb-0">{{ __('settings.holidays_hint') }}</p>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="holiday-open">{{ __('settings.holidays_add') }}</button>
                            </div>
                            <div class="holiday-form" id="holiday-form" hidden>
                                <div>
                                    <label for="holiday-from" class="form-label small">{{ __('settings.holidays_from') }}</label>
                                    <input type="date" class="form-control" id="holiday-from" />
                                </div>
                                <div>
                                    <label for="holiday-to" class="form-label small">{{ __('settings.holidays_to') }}</label>
                                    <input type="date" class="form-control" id="holiday-to" />
                                </div>
                                <div class="holiday-form__actions">
                                    <button type="button" class="btn btn-primary" id="holiday-add">{{ __('settings.add') }}</button>
                                </div>
                            </div>
                            <div class="holiday-chips" id="holiday-chips"></div>
                            <p class="text-muted small mb-0" id="holiday-empty">{{ __('settings.holidays_empty') }}</p>
                        </div>
                    </div>
                </div>

                <div class="card mb-6 settings-card" id="settings-location">
                    <div class="card-body p-5 p-lg-6">
                        <div class="settings-section-title">
                            <div>
                                <h5 class="mb-1">Локация</h5>
                                <p class="text-muted mb-0">Адрес видят клиенты. Координаты можно заполнять только при необходимости.</p>
                            </div>
                            <span class="settings-meta-chip"><i class="icon-base ri ri-map-pin-line"></i> Адрес</span>
                        </div>

                        <div class="row g-5">
                            <div class="col-12">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="address" name="address" />
                                    <label for="address">{{ __('settings.address') }}</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <details class="settings-password-toggle p-4">
                                    <summary class="fw-semibold cursor-pointer">Координаты карты</summary>
                                    <p class="text-muted small mt-2 mb-4">Нужны только если хотите вручную уточнить точку на карте. Обычно достаточно адреса.</p>
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" class="form-control" id="map_lat" name="map_point[lat]" />
                                                <label for="map_lat">Широта</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" class="form-control" id="map_lng" name="map_point[lng]" />
                                                <label for="map_lng">Долгота</label>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-6 settings-card" id="settings-branding">
                    <div class="card-body p-5 p-lg-6">
                        <div class="settings-section-title">
                            <div>
                                <h5 class="mb-1">Клиентское приложение</h5>
                                <p class="text-muted mb-0">Логотип, цвета и название, которые увидят ваши клиенты в мобильном приложении Veloria Client.</p>
                            </div>
                            <span class="settings-meta-chip"><i class="icon-base ri ri-smartphone-line"></i> Брендинг</span>
                        </div>

                        <div class="row g-4">
                            <div class="col-12">
                                <div class="settings-feature-card p-4 d-none" id="branding-pro">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                        <span class="badge bg-label-primary">Pro / Elite</span>
                                        <span class="badge bg-label-secondary">Мобильное приложение</span>
                                    </div>
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" class="form-control" id="branding_app_display_name" name="branding_app_display_name" maxlength="60" placeholder="Veloria Client" />
                                                <label for="branding_app_display_name">Название в приложении</label>
                                            </div>
                                            <small class="text-muted">Показывается клиенту вместо «Veloria». Оставьте пустым, чтобы использовать имя по умолчанию.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating form-floating-outline">
                                                <input type="url" class="form-control" id="branding_logo_url" name="branding_logo_url" placeholder="https://..." />
                                                <label for="branding_logo_url">Ссылка на логотип</label>
                                            </div>
                                            <small class="text-muted">Прямая ссылка на квадратное изображение (PNG или JPG).</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold" for="branding_primary_color">Основной цвет</label>
                                            <div class="input-group">
                                                <span class="input-group-text p-1">
                                                    <input type="color" class="form-control form-control-color" id="branding_primary_color_picker" value="#ff00fc" />
                                                </span>
                                                <input type="text" class="form-control" id="branding_primary_color" name="branding_primary_color" placeholder="#FF00FC" maxlength="7" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold" for="branding_secondary_color">Дополнительный цвет</label>
                                            <div class="input-group">
                                                <span class="input-group-text p-1">
                                                    <input type="color" class="form-control form-control-color" id="branding_secondary_color_picker" value="#ff62fb" />
                                                </span>
                                                <input type="text" class="form-control" id="branding_secondary_color" name="branding_secondary_color" placeholder="#FF62FB" maxlength="7" />
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-3">Изменения появятся в приложении клиента после следующего входа — обновлять и переустанавливать приложение не нужно.</small>
                                </div>
                            </div>
                            <div class="col-12 d-none" id="branding-locked-shared">
                                @include('components.elite-lock-card', [
                                    'wrapperClass' => 'settings-feature-lock p-4',
                                    'badge' => 'Pro / Elite',
                                    'title' => 'Брендинг клиентского приложения',
                                    'description' => 'Свой логотип, цвета и название в мобильном приложении для клиентов доступны на тарифах Pro и Elite.',
                                    'cta' => 'Открыть тарифы',
                                    'buttonClass' => 'btn btn-outline-primary',
                                ])
                            </div>
                        </div>
                    </div>
                </div>

                {{-- «Сбросить» was a native type="reset": it emptied the name, the
                     email and the phone and set the timezone to Africa/Abidjan,
                     without asking, because the values are filled in by script and
                     the HTML defaults behind them are blank. --}}
                <div class="settings-actionbar card" id="settings-actionbar">
                    <div class="settings-actionbar__state" id="settings-state">Все изменения сохранены</div>
                    <button type="button" class="btn btn-text-secondary" id="settings-cancel" hidden>Отменить изменения</button>
                    <button type="submit" class="btn btn-primary" id="settings-save">{{ __('settings.save_changes') }}</button>
                </div>
            </form>
        </div>

        {{-- Account deletion used to sit on the first screen, beside the tips. --}}
        <div class="col-xl-8" data-settings-extra="settings-account">
            <div class="card settings-danger-card">
                <div class="card-body p-5">
                    <h5 class="mb-2">{{ __('settings.delete_account_title') }}</h5>
                    <p class="text-muted mb-4">Эта секция скрыта, чтобы не мешать обычной работе с настройками.</p>
                    <details>
                        <summary class="fw-semibold text-danger cursor-pointer">{{ __('settings.delete_account') }}</summary>
                        <div class="pt-4">
                            <div class="alert alert-warning">
                                <h6 class="alert-heading mb-1">{{ __('settings.delete_account') }}</h6>
                                <p class="mb-0">{{ __('settings.delete_account_warning') }}</p>
                            </div>
                            <form id="delete-form" onsubmit="return false">
                                <div class="mb-4">
                                    <input type="password" class="form-control" name="password" placeholder="{{ __('settings.current_password') }}" />
                                </div>
                                <button type="submit" class="btn btn-danger">{{ __('settings.delete') }}</button>
                            </form>
                        </div>
                    </details>
                </div>
            </div>
        </div>
    </div>
    <script>
    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    }
    function authHeaders(extra = {}) {
        var token = getCookie('token');
        var headers = Object.assign({ 'Accept': 'application/json', 'Accept-Language': document.documentElement.lang }, extra);
        if (token) headers['Authorization'] = 'Bearer ' + token;
        return headers;
    }
    /* ---------- days off: a list of dates, shown as ranges ---------- */

    let holidayDates = [];
    const isoParts = iso => String(iso).split('-').map(Number);
    const isoFromUtcDay = day => new Date(day * 86400000).toISOString().slice(0, 10);
    const utcDayFromIso = iso => { const [y, m, d] = isoParts(iso); return Math.round(Date.UTC(y, m - 1, d) / 86400000); };
    function formatShortDate(iso, withYear) {
        const [y, m, d] = isoParts(iso);
        return new Intl.DateTimeFormat(document.documentElement.lang || 'ru', {
            day: 'numeric', month: 'short', ...(withYear ? { year: 'numeric' } : {}),
        }).format(new Date(y, m - 1, d));
    }
    function setHolidays(dates) {
        holidayDates = Array.from(new Set((dates || []).filter(date => /^\d{4}-\d{2}-\d{2}$/.test(date)))).sort();
        renderHolidays();
    }
    function touchSettingsForm() {
        document.getElementById('holiday-chips').dispatchEvent(new Event('change', { bubbles: true }));
    }
    function renderHolidays() {
        const chips = document.getElementById('holiday-chips');
        chips.innerHTML = '';
        const ranges = [];
        holidayDates.forEach(date => {
            const last = ranges[ranges.length - 1];
            if (last && utcDayFromIso(date) - utcDayFromIso(last.end) === 1) last.end = date;
            else ranges.push({ start: date, end: date });
        });
        const thisYear = String(new Date().getFullYear());
        ranges.forEach(range => {
            const withYear = range.start.slice(0, 4) !== thisYear || range.end.slice(0, 4) !== thisYear;
            const label = range.start === range.end
                ? formatShortDate(range.start, withYear)
                : formatShortDate(range.start, withYear) + ' – ' + formatShortDate(range.end, withYear);
            const chip = document.createElement('span');
            chip.className = 'holiday-chip';
            const text = document.createElement('span');
            text.textContent = label;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.innerHTML = '&times;';
            remove.setAttribute('aria-label', @json(__('settings.holidays_remove')) + ' ' + label);
            remove.addEventListener('click', () => {
                const from = utcDayFromIso(range.start);
                const to = utcDayFromIso(range.end);
                holidayDates = holidayDates.filter(date => { const day = utcDayFromIso(date); return day < from || day > to; });
                renderHolidays();
                touchSettingsForm();
            });
            chip.append(text, remove);
            chips.appendChild(chip);
        });
        document.getElementById('holiday-empty').hidden = ranges.length > 0;
    }
    function initHolidays() {
        const form = document.getElementById('holiday-form');
        const from = document.getElementById('holiday-from');
        const to = document.getElementById('holiday-to');

        document.getElementById('holiday-open').addEventListener('click', () => {
            form.hidden = !form.hidden;
            if (!form.hidden) from.focus();
        });
        document.getElementById('holiday-add').addEventListener('click', () => {
            if (!from.value) { from.classList.add('is-invalid'); from.focus(); return; }
            from.classList.remove('is-invalid');
            let start = utcDayFromIso(from.value);
            let end = to.value ? utcDayFromIso(to.value) : start;
            if (end < start) [start, end] = [end, start];
            end = Math.min(end, start + 365); // a year at most in one go
            const added = [];
            for (let day = start; day <= end; day++) added.push(isoFromUtcDay(day));
            setHolidays([...holidayDates, ...added]);
            from.value = '';
            to.value = '';
            form.hidden = true;
            touchSettingsForm();
        });
    }

    const scheduleDays = ['mon','tue','wed','thu','fri','sat','sun'];
    function parseSlots(value) {
        return Array.from(new Set(String(value || '')
            .split(/[\n,;]+/)
            .map(item => item.trim())
            .filter(item => /^\d{2}:\d{2}$/.test(item)))).sort();
    }
    function slotsToInput(slots) {
        return Array.isArray(slots) ? slots.join(', ') : '';
    }
    function toggleSchedulePanels(mode) {
        document.querySelectorAll('[data-schedule-panel]').forEach(panel => {
            panel.classList.toggle('is-active', panel.dataset.schedulePanel === mode);
        });
        document.querySelectorAll('[data-schedule-card]').forEach(card => {
            card.classList.toggle('is-active', card.dataset.scheduleCard === mode);
        });
        // The calendar always shows the chosen rule's fill, so switching the rule
        // (weekly <-> shift) must repaint it.
        renderMonthlyCalendar();
    }
    function getSelectedScheduleMode() {
        return document.querySelector('input[name="schedule_mode"]:checked')?.value || 'weekly';
    }
    /* ---------- weekly schedule: «from — to» instead of a typed slot list ----------
       The hidden text input .weekly-day-slots stays the single source of truth
       (collect / validate / dirty-check all read it); the selects only write to it. */

    const WD_DEFAULT = { from: '10:00', to: '19:00', step: 60, breakStart: '13:00', breakEnd: '14:00' };
    const wdText = {
        range: @json(__('settings.schedule_error_range')),
        breakRange: @json(__('settings.schedule_error_break')),
        empty: 'Включённый день без часов не сохранится — укажите время.',
        manualOff: @json(__('settings.schedule_manual_off')),
        manualConfirm: @json(__('settings.schedule_manual_confirm')),
    };

    function timeToMin(value) {
        const match = /^(\d{2}):(\d{2})$/.exec(value || '');
        return match ? Number(match[1]) * 60 + Number(match[2]) : null;
    }
    function minToTime(minutes) {
        return String(Math.floor(minutes / 60)).padStart(2, '0') + ':' + String(minutes % 60).padStart(2, '0');
    }
    function buildSlots(from, to, step, breaks = []) {
        const slots = [];
        if (from === null || to === null || !(step > 0)) return slots;
        for (let m = from; m + step <= to; m += step) {
            const inBreak = breaks.some(item => m < item.end && m + step > item.start);
            if (!inBreak) slots.push(minToTime(m));
        }
        return slots;
    }
    // Reverse of buildSlots: a day is "regular" when one from/to (+ some breaks) rebuilds exactly its slots.
    function inferDay(slots, step) {
        const mins = (slots || []).map(timeToMin);
        if (!mins.length || mins.some(m => m === null)) return null;
        mins.sort((a, b) => a - b);
        const breaks = [];
        for (let i = 0; i < mins.length - 1; i++) {
            const gap = mins[i + 1] - mins[i];
            if (gap === step) continue;
            if (gap < step) return null;
            breaks.push({ start: mins[i] + step, end: mins[i + 1] });
        }
        const from = mins[0];
        const to = mins[mins.length - 1] + step;
        const rebuilt = buildSlots(from, to, step, breaks);
        if (rebuilt.join() !== mins.map(minToTime).join()) return null;
        return { from, to, breaks };
    }
    function inferStep(weekly, cycle = {}) {
        let best = null;
        const lists = [...scheduleDays.map(day => weekly?.[day]?.slots || []), cycle?.slots || []];
        lists.forEach(list => {
            const mins = list.map(timeToMin).filter(m => m !== null).sort((a, b) => a - b);
            for (let i = 0; i < mins.length - 1; i++) {
                const gap = mins[i + 1] - mins[i];
                if (gap > 0 && (best === null || gap < best)) best = gap;
            }
        });
        return best || WD_DEFAULT.step;
    }

    const wdRow = day => document.querySelector(`[data-day-row="${day}"]`);
    const wdField = (day, selector) => wdRow(day).querySelector(selector);
    function wdEnsureOption(select, value) {
        if (value && ![...select.options].some(option => option.value === value)) {
            const option = new Option(value, value);
            const index = [...select.options].findIndex(item => item.value > value);
            select.add(option, index === -1 ? null : select.options[index]);
        }
    }
    function wdSetTime(select, minutes) {
        const value = minToTime(minutes);
        wdEnsureOption(select, value);
        select.value = value;
    }
    function wdFillSelect(select) {
        select.innerHTML = '';
        for (let m = 6 * 60; m <= 23 * 60 + 30; m += 30) {
            select.add(new Option(minToTime(m), minToTime(m)));
        }
    }
    function wdFillTimeOptions() {
        document.querySelectorAll('.wd-from, .wd-to, .wd-bs, .wd-be').forEach(wdFillSelect);
    }
    function wdStep() {
        return parseInt(document.getElementById('weekly-step').value, 10) || WD_DEFAULT.step;
    }
    function wdAddBreak(day, start, end) {
        const node = document.getElementById('wd-break-template').content.firstElementChild.cloneNode(true);
        node.querySelectorAll('select, button').forEach(element => { element.dataset.day = day; });
        node.querySelectorAll('select').forEach(wdFillSelect);
        wdSetTime(node.querySelector('.wd-bs'), start);
        wdSetTime(node.querySelector('.wd-be'), end);
        wdRow(day).querySelector('.wd-breaks').appendChild(node);
    }
    function wdRead(day) {
        return {
            from: timeToMin(wdField(day, '.wd-from').value),
            to: timeToMin(wdField(day, '.wd-to').value),
            breaks: [...wdRow(day).querySelectorAll('.wd-break')].map(node => ({
                start: timeToMin(node.querySelector('.wd-bs').value),
                end: timeToMin(node.querySelector('.wd-be').value),
            })),
        };
    }
    function wdWrite(day, values) {
        wdSetTime(wdField(day, '.wd-from'), values.from);
        wdSetTime(wdField(day, '.wd-to'), values.to);
        wdRow(day).querySelector('.wd-breaks').innerHTML = '';
        (values.breaks || []).forEach(item => wdAddBreak(day, item.start, item.end));
    }
    function wdDefaults() {
        return { from: timeToMin(WD_DEFAULT.from), to: timeToMin(WD_DEFAULT.to), breaks: [] };
    }
    function wdSetState(day, { enabled, manual }) {
        const row = wdRow(day);
        row.classList.toggle('is-on', enabled);
        row.classList.toggle('is-off', !enabled);
        row.classList.toggle('is-manual', Boolean(manual));
        const toggle = wdField(day, '.wd-manual-toggle');
        toggle.textContent = manual ? wdText.manualOff : '';
    }
    function wdDayError(day) {
        const input = wdField(day, '.weekly-day-slots');
        if (wdRow(day).classList.contains('is-manual')) {
            return parseSlots(input.value).length ? '' : wdText.empty;
        }
        const { from, to, breaks } = wdRead(day);
        if (from === null || to === null || to <= from) return wdText.range;
        if (breaks.some(item => item.start === null || item.end === null || item.end <= item.start || item.start < from || item.end > to)) {
            return wdText.breakRange;
        }
        return buildSlots(from, to, wdStep(), breaks).length ? '' : wdText.range;
    }
    function wdShowError(day, message) {
        const error = document.querySelector(`[data-slot-error="${day}"]`);
        error.textContent = message;
        error.hidden = !message;
        const row = wdRow(day);
        row.querySelectorAll('.wd-from, .wd-to, .wd-bs, .wd-be, .weekly-day-slots')
            .forEach(field => field.classList.toggle('is-invalid', Boolean(message)));
    }
    // selects -> hidden input
    function wdSyncDay(day) {
        const row = wdRow(day);
        if (row.classList.contains('is-manual')) return;
        const { from, to, breaks } = wdRead(day);
        const slots = buildSlots(from, to, wdStep(), breaks);
        wdField(day, '.weekly-day-slots').value = slotsToInput(slots);
    }
    function wdNotify(day) {
        wdField(day, '.weekly-day-slots').dispatchEvent(new Event('input', { bubbles: true }));
    }
    function wdFirstRegularDay(except = null) {
        return scheduleDays.find(day => day !== except
            && document.getElementById('weekly-day-' + day).checked
            && !wdRow(day).classList.contains('is-manual'));
    }
    // Also fills the time options of the shift schedule row, so it must run before populateCycleSchedule.
    function populateWeeklySchedule(weekly = {}, cycle = {}) {
        wdFillTimeOptions();
        const step = inferStep(weekly, cycle);
        const stepSelect = document.getElementById('weekly-step');
        wdEnsureOption(stepSelect, String(step));
        stepSelect.value = String(step);

        scheduleDays.forEach(day => {
            const check = document.getElementById('weekly-day-' + day);
            const slotsInput = wdField(day, '.weekly-day-slots');
            const dayRules = weekly?.[day] || {};
            const slots = Array.isArray(dayRules.slots) ? dayRules.slots : [];
            const enabled = Boolean(dayRules.enabled) || slots.length > 0;
            const inferred = inferDay(slots, step);

            check.checked = enabled;
            wdWrite(day, inferred || wdDefaults());
            // Slots that one «from — to» cannot rebuild stay editable as text, not silently rewritten.
            const manual = enabled && slots.length > 0 && !inferred;
            slotsInput.value = manual ? slotsToInput(slots) : '';
            wdSetState(day, { enabled, manual });
            wdSyncDay(day);
            wdShowError(day, '');
        });
    }
    function wdFirstField(day) {
        return wdRow(day).classList.contains('is-manual')
            ? wdField(day, '.weekly-day-slots')
            : wdField(day, '.wd-from');
    }
    // The shift-schedule row has no day switch: it is always on. The calendar-date
    // row has its own switch instead of the weekday checkboxes.
    function wdIsOn(day) {
        if (day === 'cycle') return true;
        if (day === 'mday') return document.getElementById('mday-work-toggle').checked;
        return document.getElementById('weekly-day-' + day).checked;
    }
    function initWeeklyScheduleControls() {
        document.querySelectorAll('.wd-list').forEach(bindWeeklyList);

        // The calendar below always shows the active rule's fill, so any change to
        // the rule itself must repaint it.
        ['cycle_work_days', 'cycle_rest_days', 'cycle_anchor_date'].forEach(id => {
            document.getElementById(id).addEventListener('input', renderMonthlyCalendar);
        });

        document.getElementById('weekly-step').addEventListener('change', () => {
            [...scheduleDays, 'cycle', 'mday'].forEach(day => {
                wdSyncDay(day);
                if (wdIsOn(day)) wdShowError(day, wdDayError(day));
            });
            // Re-generated hours of the day being edited go back into its date.
            if (!document.getElementById('mday').hidden) wdNotify('mday');
            renderMonthlyCalendar();
        });

        document.getElementById('weekly-copy-all').addEventListener('click', () => {
            const source = wdFirstRegularDay();
            if (!source) return;
            const values = wdRead(source);
            scheduleDays.forEach(day => {
                if (day === source || !document.getElementById('weekly-day-' + day).checked) return;
                wdWrite(day, values);
                wdSetState(day, { enabled: true, manual: false });
                wdSyncDay(day);
                wdShowError(day, '');
            });
            wdNotify(source);
            renderMonthlyCalendar();
        });

        document.getElementById('weekly-manual-all').addEventListener('click', () => {
            scheduleDays.forEach(day => {
                if (!document.getElementById('weekly-day-' + day).checked) return;
                wdSyncDay(day);
                wdSetState(day, { enabled: true, manual: true });
            });
            renderMonthlyCalendar();
        });

    }
    function bindWeeklyList(list) {
        list.addEventListener('change', event => {
            const day = event.target.dataset?.day;
            if (!day) return;
            if (event.target.classList.contains('weekly-day-check')) {
                const enabled = event.target.checked;
                if (enabled) {
                    const source = wdFirstRegularDay(day);
                    wdWrite(day, source ? wdRead(source) : wdDefaults());
                    wdSetState(day, { enabled: true, manual: false });
                } else {
                    wdSetState(day, { enabled: false, manual: false });
                    wdShowError(day, '');
                }
            }
            wdSyncDay(day);
            if (wdIsOn(day)) wdShowError(day, wdDayError(day));
            renderMonthlyCalendar();
        });

        list.addEventListener('click', event => {
            const button = event.target.closest('button[data-day]');
            if (!button) return;
            const day = button.dataset.day;
            const row = wdRow(day);

            if (button.classList.contains('wd-break-add')) {
                const { from, to, breaks } = wdRead(day);
                // The first break defaults to lunch, the next ones go an hour after the previous one.
                const wanted = breaks.length ? breaks[breaks.length - 1].end + 60 : timeToMin(WD_DEFAULT.breakStart);
                const start = Math.min(Math.max(wanted, from), Math.max(to - 60, from));
                wdAddBreak(day, start, Math.min(start + 60, to));
            } else if (button.classList.contains('wd-break-remove')) {
                button.closest('.wd-break').remove();
            } else if (button.classList.contains('wd-manual-toggle')) {
                if (!window.confirm(wdText.manualConfirm)) return;
                wdSetState(day, { enabled: true, manual: false });
            } else {
                return;
            }
            wdSyncDay(day);
            wdShowError(day, wdDayError(day));
            wdNotify(day);
            renderMonthlyCalendar();
        });
    }
    function localIsoDate(date = new Date()) {
        return [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
    }
    const WEEKDAY_KEYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat']; // Date#getDay(): 0 = Sunday
    // What the active rule (weekly or shift cycle) says for a date, ignoring any
    // picked-by-hand override. Must stay in step with ScheduleService::resolveWeeklySlots
    // / resolveCycleSlots (same positive-modulo cycle math, dates before the start count too).
    function ruleSlotsForDate(iso) {
        const [y, m, d] = isoParts(iso);
        if (getSelectedScheduleMode() === 'cycle') {
            const work = Math.min(Math.max(parseInt(document.getElementById('cycle_work_days').value, 10) || 0, 0), 31);
            const rest = Math.min(Math.max(parseInt(document.getElementById('cycle_rest_days').value, 10) || 0, 0), 31);
            const length = work + rest;
            const anchorParts = (document.getElementById('cycle_anchor_date').value || '').split('-').map(Number);
            if (!(work > 0 && rest > 0 && anchorParts.length === 3 && anchorParts.every(Number.isFinite))) return [];
            const dayNumber = (yy, mm, dd) => Math.round(Date.UTC(yy, mm, dd) / 86400000);
            const anchor = dayNumber(anchorParts[0], anchorParts[1] - 1, anchorParts[2]);
            const position = (((dayNumber(y, m - 1, d) - anchor) % length) + length) % length;
            return position < work ? parseSlots(wdField('cycle', '.weekly-day-slots').value) : [];
        }
        const dayKey = WEEKDAY_KEYS[new Date(y, m - 1, d).getDay()];
        if (!document.getElementById('weekly-day-' + dayKey).checked) return [];
        return parseSlots(wdField(dayKey, '.weekly-day-slots').value);
    }
    function populateCycleSchedule(cycle = {}) {
        document.getElementById('cycle_anchor_date').value = cycle.anchor_date || localIsoDate();
        document.getElementById('cycle_work_days').value = cycle.work_days || 2;
        document.getElementById('cycle_rest_days').value = cycle.rest_days || 2;

        const slots = Array.isArray(cycle.slots) ? cycle.slots : [];
        const inferred = inferDay(slots, wdStep());
        const manual = slots.length > 0 && !inferred;
        wdWrite('cycle', inferred || wdDefaults());
        document.getElementById('cycle_slots').value = manual ? slotsToInput(slots) : '';
        wdSetState('cycle', { enabled: true, manual });
        wdSyncDay('cycle');
        wdShowError('cycle', '');
    }
    /* ---------- calendar: click a date to override the rule for just that day ----------
       monthlyDates (date -> slots, [] meaning an explicit day off) holds only the
       overrides; every other date follows the active weekly/cycle rule. The single
       editor row ("mday") edits whichever date is active. */

    let monthlyDates = {};
    let monthlyActive = null;
    let monthlyTemplate = null; // slots of the override touched last: a new override starts with the same hours
    const monthlyView = { year: new Date().getFullYear(), month: new Date().getMonth() };

    function monthlyIsOverridden(date) {
        return Object.prototype.hasOwnProperty.call(monthlyDates, date);
    }
    function monthlyNewDaySlots() {
        if (monthlyTemplate && monthlyTemplate.length) return monthlyTemplate.slice();
        const { from, to } = wdDefaults();
        return buildSlots(from, to, wdStep());
    }
    // An un-overridden date opens pre-filled with what the rule currently says for
    // it, so the switch merely confirms or flips that — not a blank slate.
    function loadMonthlyEditor(date) {
        const slots = monthlyIsOverridden(date) ? monthlyDates[date] : ruleSlotsForDate(date);
        const inferred = inferDay(slots, wdStep());
        const manual = slots.length > 0 && !inferred;
        wdWrite('mday', inferred || wdDefaults());
        document.querySelector('.weekly-day-slots[data-day="mday"]').value = manual ? slotsToInput(slots) : '';
        document.getElementById('mday-work-toggle').checked = slots.length > 0;
        wdSetState('mday', { enabled: slots.length > 0, manual });
        wdSyncDay('mday');
        wdShowError('mday', '');
        document.getElementById('mday-reset').hidden = !monthlyIsOverridden(date);
    }
    function describeSlots(slots) {
        if (!slots.length) return @json(__('settings.schedule_day_off'));
        const inferred = inferDay(slots, wdStep());
        return inferred
            ? minToTime(inferred.from) + '–' + minToTime(inferred.to)
            : slots[0] + '–' + slots[slots.length - 1];
    }
    function renderMonthlyChips() {
        const box = document.getElementById('mday-chips');
        box.innerHTML = '';
        Object.keys(monthlyDates).sort().forEach(date => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'mday-chip' + (date === monthlyActive ? ' is-active' : '');
            chip.dataset.date = date;
            chip.textContent = formatShortDate(date, false) + ' · ' + describeSlots(monthlyDates[date]);
            box.appendChild(chip);
        });
    }
    function setMonthlyActive(date) {
        monthlyActive = date;
        const editor = document.getElementById('mday');
        editor.hidden = !date;
        if (date) {
            const [y, m, d] = isoParts(date);
            document.getElementById('mday-title').textContent = new Intl.DateTimeFormat(document.documentElement.lang || 'ru', {
                weekday: 'long', day: 'numeric', month: 'long',
            }).format(new Date(y, m - 1, d));
            loadMonthlyEditor(date);
        }
        renderMonthlyCalendar();
        if (date) editor.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
    function renderMonthlyCalendar() {
        const locale = document.documentElement.lang || 'ru';
        const { year, month } = monthlyView;
        const first = new Date(year, month, 1);
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const lead = (first.getDay() + 6) % 7; // Monday first
        const todayKey = localIsoDate();
        const grid = document.getElementById('mcal-grid');
        grid.innerHTML = '';

        document.getElementById('mcal-title').textContent = new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' }).format(first);
        [...Array(7)].forEach((_, i) => {
            const cell = document.createElement('div');
            cell.className = 'mcal__dow';
            cell.textContent = new Intl.DateTimeFormat(locale, { weekday: 'short' }).format(new Date(2024, 0, 1 + i));
            grid.appendChild(cell);
        });
        for (let i = 0; i < lead; i++) grid.appendChild(document.createElement('div'));
        for (let day = 1; day <= daysInMonth; day++) {
            const iso = localIsoDate(new Date(year, month, day));
            const overridden = monthlyIsOverridden(iso);
            const isWork = overridden ? monthlyDates[iso].length > 0 : ruleSlotsForDate(iso).length > 0;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'mcal-day';
            button.textContent = day;
            button.dataset.date = iso;
            if (isWork) button.classList.add('is-work');
            if (overridden) button.classList.add('is-override');
            if (iso === todayKey) button.classList.add('is-today');
            if (iso === monthlyActive) button.classList.add('is-active');
            button.setAttribute('aria-pressed', isWork ? 'true' : 'false');
            grid.appendChild(button);
        }
        const count = Object.keys(monthlyDates).length;
        document.getElementById('mcal-summary').textContent = count
            ? @json(__('settings.schedule_overrides_count', ['count' => ':count'])).replace(':count', count)
            : @json(__('settings.schedule_overrides_none'));
        renderMonthlyChips();
    }
    function populateMonthlySchedule(monthly = {}) {
        monthlyDates = {};
        Object.entries(monthly?.dates || {}).forEach(([date, slots]) => {
            if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) return;
            monthlyDates[date] = parseSlots(slots);
        });
        monthlyTemplate = Object.values(monthlyDates).find(slots => slots.length) || null;
        const now = new Date();
        monthlyView.year = now.getFullYear();
        monthlyView.month = now.getMonth();
        setMonthlyActive(null);
    }
    function initMonthlyCalendar() {
        document.getElementById('mcal-grid').addEventListener('click', event => {
            const button = event.target.closest('.mcal-day');
            if (!button) return;
            const date = button.dataset.date;
            setMonthlyActive(monthlyActive === date ? null : date);
        });
        document.getElementById('mcal-prev').addEventListener('click', () => {
            const view = new Date(monthlyView.year, monthlyView.month - 1, 1);
            monthlyView.year = view.getFullYear();
            monthlyView.month = view.getMonth();
            renderMonthlyCalendar();
        });
        document.getElementById('mcal-next').addEventListener('click', () => {
            const view = new Date(monthlyView.year, monthlyView.month + 1, 1);
            monthlyView.year = view.getFullYear();
            monthlyView.month = view.getMonth();
            renderMonthlyCalendar();
        });
        document.getElementById('mday-chips').addEventListener('click', event => {
            const chip = event.target.closest('.mday-chip');
            if (!chip) return;
            const [y, m] = isoParts(chip.dataset.date);
            monthlyView.year = y;
            monthlyView.month = m - 1;
            setMonthlyActive(monthlyActive === chip.dataset.date ? null : chip.dataset.date);
        });
        document.getElementById('mday-close').addEventListener('click', () => setMonthlyActive(null));

        // The switch is the one action that always creates/updates an override —
        // just opening a date to look at it never writes anything.
        document.getElementById('mday-work-toggle').addEventListener('change', event => {
            if (!monthlyActive) return;
            const enabled = event.target.checked;
            if (enabled) {
                const slots = monthlyNewDaySlots();
                wdWrite('mday', inferDay(slots, wdStep()) || wdDefaults());
                document.querySelector('.weekly-day-slots[data-day="mday"]').value = '';
                wdSetState('mday', { enabled: true, manual: false });
                wdSyncDay('mday');
                monthlyDates[monthlyActive] = parseSlots(document.querySelector('.weekly-day-slots[data-day="mday"]').value);
            } else {
                wdSetState('mday', { enabled: false, manual: false });
                monthlyDates[monthlyActive] = [];
            }
            wdShowError('mday', enabled ? wdDayError('mday') : '');
            monthlyTemplate = monthlyDates[monthlyActive].length ? monthlyDates[monthlyActive].slice() : monthlyTemplate;
            document.getElementById('mday-reset').hidden = false;
            renderMonthlyCalendar();
            touchSettingsForm();
        });

        document.getElementById('mday-reset').addEventListener('click', () => {
            delete monthlyDates[monthlyActive];
            loadMonthlyEditor(monthlyActive);
            renderMonthlyCalendar();
            touchSettingsForm();
        });

        // The editor row writes its slots into the hidden input; mirror them into the active override.
        const editorList = document.querySelector('[data-day-row="mday"]').closest('.wd-list');
        const mirror = () => {
            if (!monthlyActive || !wdIsOn('mday')) return;
            const slots = parseSlots(document.querySelector('.weekly-day-slots[data-day="mday"]').value);
            if (!slots.length) return;
            monthlyDates[monthlyActive] = slots;
            monthlyTemplate = slots.slice();
            document.getElementById('mday-reset').hidden = false;
            renderMonthlyChips();
            renderMonthlyCalendar();
        };
        editorList.addEventListener('change', mirror);
        editorList.addEventListener('input', mirror);
    }

    function collectScheduleRules() {
        const weekly = {};
        scheduleDays.forEach(day => {
            const enabled = document.getElementById('weekly-day-' + day).checked;
            const slots = parseSlots(document.querySelector(`.weekly-day-slots[data-day="${day}"]`).value);
            weekly[day] = { enabled: enabled && slots.length > 0, slots: enabled ? slots : [] };
        });
        const monthlyOut = {};
        Object.keys(monthlyDates).sort().forEach(date => {
            monthlyOut[date] = monthlyDates[date];
        });
        return {
            mode: getSelectedScheduleMode(),
            weekly,
            cycle: {
                anchor_date: document.getElementById('cycle_anchor_date').value || '',
                work_days: parseInt(document.getElementById('cycle_work_days').value || '2', 10),
                rest_days: parseInt(document.getElementById('cycle_rest_days').value || '2', 10),
                slots: parseSlots(document.getElementById('cycle_slots').value || ''),
            },
            monthly: {
                dates: monthlyOut,
            },
        };
    }
    function buildLegacyScheduleFromRules(scheduleRules) {
        const work_days = [];
        const work_hours = {};
        const weekly = scheduleRules?.weekly || {};
        scheduleDays.forEach(day => {
            const dayRules = weekly[day] || {};
            const slots = Array.isArray(dayRules.slots) ? dayRules.slots : [];
            if (dayRules.enabled && slots.length) {
                work_days.push(day);
                work_hours[day] = slots;
            }
        });
        return { work_days, work_hours };
    }
    function parseReminderList(value) {
        return Array.from(new Set(String(value || '')
            .split(/[\n,;]+/)
            .map(item => item.trim())
            .filter(Boolean)));
    }
    function populateReminderServiceOptions(services, selectedIds) {
        const select = document.getElementById('allergy_reminder_service_exclusions');
        if (!select) return;
        const selected = new Set((selectedIds || []).map(id => String(id)));
        select.innerHTML = '';
        if (!(services || []).length) {
            const option = document.createElement('option');
            option.textContent = 'Сначала добавьте услуги';
            option.disabled = true;
            select.appendChild(option);
            return;
        }
        (services || []).forEach(service => {
            const option = document.createElement('option');
            option.value = service.id;
            option.textContent = service.name;
            option.selected = selected.has(String(service.id));
            select.appendChild(option);
        });
    }
    const hexColorPattern = /^#[0-9A-Fa-f]{6}$/;
    function syncBrandingColorPicker(textId, pickerId) {
        const text = document.getElementById(textId);
        const picker = document.getElementById(pickerId);
        if (!text || !picker) return;
        if (hexColorPattern.test(text.value)) {
            picker.value = text.value;
        }
    }
    ['branding_primary_color', 'branding_secondary_color'].forEach((textId) => {
        const pickerId = `${textId}_picker`;
        const text = document.getElementById(textId);
        const picker = document.getElementById(pickerId);
        if (!text || !picker) return;
        picker.addEventListener('input', () => {
            text.value = picker.value;
            text.dispatchEvent(new Event('input'));
        });
        text.addEventListener('input', () => syncBrandingColorPicker(textId, pickerId));
    });
    function setReminderFieldsDisabled(disabled) {
        ['allergy_reminder_enabled', 'allergy_reminder_minutes', 'allergy_reminder_service_exclusions', 'allergy_reminder_allergy_exclusions'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.disabled = disabled;
            }
        });
    }
    async function loadSettings() {
        const res = await fetch('/api/v1/settings', { headers: authHeaders(), credentials: 'include' });
        // Silence here left an empty form that could be saved over the account.
        if(!res.ok) {
            setBlocked(true);
            return;
        }
        setBlocked(false);
        const data = await res.json();
        const form = document.getElementById('settings-form');
        const allergyReminderFeature = data.settings.features?.allergy_reminders || {};
        const hasAllergyReminderAccess = Boolean(allergyReminderFeature.available);
        const allergyReminderCard = document.getElementById('allergy-reminders-pro');
        const allergyReminderLockedCard = document.getElementById('allergy-reminders-locked-shared');
        const dailyIdeasFeature = data.settings.features?.daily_post_ideas || {};
        const hasDailyIdeasAccess = Boolean(dailyIdeasFeature.available);
        const dailyIdeasEliteCard = document.getElementById('daily-post-ideas-elite');
        const dailyIdeasLockedCard = document.getElementById('daily-post-ideas-locked');
        const dailyIdeasLockedSharedCard = document.getElementById('daily-post-ideas-locked-shared');
        const dailyIdeasToggle = document.getElementById('daily_post_ideas_enabled');
        const dailyIdeasChannel = document.getElementById('daily_post_ideas_channel');
        const dailyIdeasPreferences = document.getElementById('daily_post_ideas_preferences');
        form.name.value = data.user.name || '';
        form.email.value = data.user.email || '';
        form.phone.value = data.user.phone || '';
        if (form.phone.hasAttribute('data-phone-mask')) {
            form.phone.dispatchEvent(new Event('input'));
        }
        form.timezone.value = data.user.timezone || '';
        form.time_format.value = data.user.time_format || '24h';
        document.getElementById('notif-email').checked = data.settings.notifications?.email ?? false;
        document.getElementById('notif-telegram').checked = data.settings.notifications?.telegram ?? false;
        document.getElementById('notif-sms').checked = data.settings.notifications?.sms ?? false;
        document.getElementById('notif-telegram').disabled = !data.user.telegram_id;
        document.getElementById('notif-sms').disabled = !data.user.phone;
        if (allergyReminderCard) {
            allergyReminderCard.classList.toggle('d-none', !hasAllergyReminderAccess);
        }
        if (allergyReminderLockedCard) {
            allergyReminderLockedCard.classList.toggle('d-none', hasAllergyReminderAccess);
        }
        document.getElementById('allergy_reminder_enabled').checked = Boolean(allergyReminderFeature.enabled);
        document.getElementById('allergy_reminder_minutes').value = allergyReminderFeature.minutes || 15;
        document.getElementById('allergy_reminder_allergy_exclusions').value = (allergyReminderFeature.exclusions?.allergies || []).join(', ');
        populateReminderServiceOptions(data.settings.options?.services || [], allergyReminderFeature.exclusions?.services || []);
        setReminderFieldsDisabled(!hasAllergyReminderAccess);
        if (dailyIdeasEliteCard) {
            dailyIdeasEliteCard.classList.toggle('d-none', !hasDailyIdeasAccess);
        }
        if (dailyIdeasLockedCard) {
            dailyIdeasLockedCard.classList.add('d-none');
        }
        if (dailyIdeasLockedSharedCard) {
            dailyIdeasLockedSharedCard.classList.toggle('d-none', hasDailyIdeasAccess);
        }
        if (dailyIdeasToggle) {
            dailyIdeasToggle.checked = Boolean(dailyIdeasFeature.enabled);
        }
        if (dailyIdeasChannel) {
            dailyIdeasChannel.value = dailyIdeasFeature.channel || 'both';
        }
        if (dailyIdeasPreferences) {
            dailyIdeasPreferences.value = dailyIdeasFeature.preferences || '';
        }
        const brandingFeature = data.settings.features?.branding || {};
        const hasBrandingAccess = Boolean(brandingFeature.available);
        const brandingCard = document.getElementById('branding-pro');
        const brandingLockedCard = document.getElementById('branding-locked-shared');
        if (brandingCard) {
            brandingCard.classList.toggle('d-none', !hasBrandingAccess);
        }
        if (brandingLockedCard) {
            brandingLockedCard.classList.toggle('d-none', hasBrandingAccess);
        }
        document.getElementById('branding_app_display_name').value = brandingFeature.app_display_name || '';
        document.getElementById('branding_logo_url').value = brandingFeature.logo_url || '';
        document.getElementById('branding_primary_color').value = brandingFeature.primary_color || '';
        document.getElementById('branding_secondary_color').value = brandingFeature.secondary_color || '';
        syncBrandingColorPicker('branding_primary_color', 'branding_primary_color_picker');
        syncBrandingColorPicker('branding_secondary_color', 'branding_secondary_color_picker');
        const scheduleRules = data.settings.schedule_rules || {};
        const modeInput = document.querySelector(`input[name="schedule_mode"][value="${scheduleRules.mode || 'weekly'}"]`);
        if (modeInput) {
            modeInput.checked = true;
        }
        toggleSchedulePanels(scheduleRules.mode || 'weekly');
        populateWeeklySchedule(scheduleRules.weekly || {}, scheduleRules.cycle || {});
        populateCycleSchedule(scheduleRules.cycle || {});
        populateMonthlySchedule(scheduleRules.monthly || {});
        // Nothing to look at yet: open the rule editor instead of hiding an empty schedule behind a collapsed summary.
        const hasWeeklyRule = scheduleDays.some(day => scheduleRules.weekly?.[day]?.enabled && scheduleRules.weekly[day].slots?.length);
        const hasCycleRule = (scheduleRules.cycle?.slots || []).length > 0;
        document.getElementById('schedule-rule-details').open = !hasWeeklyRule && !hasCycleRule;
        setHolidays((data.settings.holidays || []).map(date => String(date).split('T')[0]));
        form.address.value = data.settings.address || '';
        form['map_point[lat]'].value = data.settings.map_point?.lat || '';
        form['map_point[lng]'].value = data.settings.map_point?.lng || '';
        form.reminder_message.value = (data.settings && data.settings.reminder_message) || '';

        setSettingsAvatar(data.user.avatar_url || null, data.user.initials || computeInitials(form.name.value));
    }
    loadSettings().then(() => {
        state.baseline = snapshot();
        refreshDirty();
    });
    initWeeklyScheduleControls();
    initHolidays();
    initMonthlyCalendar();
    document.querySelectorAll('input[name="schedule_mode"]').forEach(input => {
        input.addEventListener('change', () => toggleSchedulePanels(input.value));
    });
    toggleSchedulePanels(getSelectedScheduleMode());

    function showMessage(type, text){
        const container = document.getElementById('form-messages');
        container.innerHTML = `<div class="alert alert-${type}" role="alert">${text}</div>`;
    }

    function computeInitials(fullName) {
        const s = (fullName || '').trim();
        if (!s) return '?';
        const parts = s.split(/\s+/).filter(Boolean);
        const first = parts[0] || '';
        const last = parts.length > 1 ? parts[parts.length - 1] : '';
        const a = first ? first[0].toUpperCase() : '';
        const b = last ? last[0].toUpperCase() : '';
        return (a + b) || '?';
    }

    function setSettingsAvatar(avatarUrl, initials) {
        const img = document.getElementById('uploadedAvatarImg');
        const init = document.getElementById('uploadedAvatarInitials');
        if (avatarUrl) {
            img.src = avatarUrl;
            img.classList.remove('d-none');
            init.classList.add('d-none');
        } else {
            img.removeAttribute('src');
            img.classList.add('d-none');
            init.textContent = initials || '?';
            init.classList.remove('d-none');
        }
    }

    const passwordToggleBtn = document.getElementById('toggle-password-fields');
    const passwordFields = document.getElementById('settings-password-fields');
    if (passwordToggleBtn && passwordFields) {
        passwordToggleBtn.addEventListener('click', () => {
            const isVisible = passwordFields.classList.toggle('is-visible');
            passwordToggleBtn.textContent = isVisible ? 'Скрыть поля' : 'Открыть поля';
        });
    }

    function buildPayload() {
        const form = document.getElementById('settings-form');
        const payload = {
            name: form.name.value,
            email: form.email.value,
            phone: form.phone.value,
            timezone: form.timezone.value,
            time_format: form.time_format.value,
            notifications: {
                email: document.getElementById('notif-email').checked,
                telegram: document.getElementById('notif-telegram').checked,
                sms: document.getElementById('notif-sms').checked,
            },
            holidays: holidayDates.slice(),
            address: form.address.value,
            reminder_message: form.reminder_message.value,
            daily_post_ideas_enabled: Boolean(document.getElementById('daily_post_ideas_enabled')?.checked),
            daily_post_ideas_channel: document.getElementById('daily_post_ideas_channel')?.value || 'both',
            daily_post_ideas_preferences: document.getElementById('daily_post_ideas_preferences')?.value || '',
            map_point: {
                lat: form['map_point[lat]'].value,
                lng: form['map_point[lng]'].value,
            },
            schedule_rules: collectScheduleRules(),
        };
        const allergyReminderToggle = document.getElementById('allergy_reminder_enabled');
        if (allergyReminderToggle && !allergyReminderToggle.disabled) {
            payload.allergy_reminder_enabled = Boolean(allergyReminderToggle.checked);
            payload.allergy_reminder_minutes = parseInt(document.getElementById('allergy_reminder_minutes').value || '15', 10);
            payload.allergy_reminder_exclusions = {
                allergies: parseReminderList(document.getElementById('allergy_reminder_allergy_exclusions').value || ''),
                services: Array.from(document.getElementById('allergy_reminder_service_exclusions').selectedOptions || [])
                    .map(option => parseInt(option.value, 10))
                    .filter(Number.isFinite),
            };
        }
        const brandingCard = document.getElementById('branding-pro');
        if (brandingCard && !brandingCard.classList.contains('d-none')) {
            payload.branding = {
                app_display_name: document.getElementById('branding_app_display_name').value || null,
                primary_color: document.getElementById('branding_primary_color').value || null,
                secondary_color: document.getElementById('branding_secondary_color').value || null,
                logo_url: document.getElementById('branding_logo_url').value || null,
            };
        }
        const legacySchedule = buildLegacyScheduleFromRules(payload.schedule_rules);
        payload.work_days = legacySchedule.work_days;
        payload.work_hours = legacySchedule.work_hours;

        return payload;
    }

    /* ---------- tabs ---------- */

    const settingsTabIds = Array.from(document.querySelectorAll('[data-settings-tab]')).map(el => el.dataset.settingsTab);

    function showSettingsTab(id, updateHash = true) {
        if (!settingsTabIds.includes(id)) id = settingsTabIds[0];

        settingsTabIds.forEach(tabId => {
            const active = tabId === id;
            document.getElementById(tabId).hidden = !active;
            document.querySelectorAll('[data-settings-extra="' + tabId + '"]').forEach(el => { el.hidden = !active; });
            const link = document.querySelector('[data-settings-tab="' + tabId + '"]');
            link.classList.toggle('active', active);
            link.setAttribute('aria-selected', active ? 'true' : 'false');
            if (active) link.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        });

        if (updateHash && location.hash !== '#' + id) history.replaceState(null, '', '#' + id);
    }

    document.getElementById('settings-tabs').addEventListener('click', event => {
        const link = event.target.closest('[data-settings-tab]');
        if (!link) return;
        event.preventDefault();
        showSettingsTab(link.dataset.settingsTab);
    });

    window.addEventListener('hashchange', () => showSettingsTab(location.hash.slice(1), false));
    showSettingsTab(location.hash.slice(1), false);

    /* A dot on every tab the reader has edited since the last save. */
    const touchedTabs = new Set();

    function refreshTabDots() {
        settingsTabIds.forEach(id => {
            const dot = document.querySelector('[data-settings-tab="' + id + '"] .settings-tab-dot');
            dot.hidden = !(touchedTabs.has(id) && isDirty());
        });
    }

    document.getElementById('settings-form').addEventListener('input', markTouchedTab);
    document.getElementById('settings-form').addEventListener('change', markTouchedTab);

    function markTouchedTab(event) {
        const pane = event.target.closest('.settings-card[id^="settings-"]');
        if (pane) touchedTabs.add(pane.id);
        refreshTabDots();
    }

    /* ---------- unsaved changes ---------- */

    const state = { baseline: null, blocked: false };
    const stateEl = document.getElementById('settings-state');
    const cancelBtn = document.getElementById('settings-cancel');
    const saveBtn = document.getElementById('settings-save');

    function snapshot() {
        return JSON.stringify(buildPayload());
    }

    function isDirty() {
        if (state.baseline === null) return false;

        const passwordTyped = Boolean(document.getElementById('new_password').value
            || document.getElementById('current_password').value);

        return passwordTyped || snapshot() !== state.baseline;
    }

    function setBarState(kind, text) {
        stateEl.className = 'settings-actionbar__state' + (kind ? ' is-' + kind : '');
        stateEl.textContent = text;
    }

    function refreshDirty() {
        if (state.blocked) return;

        const dirty = isDirty();
        cancelBtn.hidden = !dirty;
        if (!dirty) touchedTabs.clear();
        if (typeof refreshTabDots === 'function') refreshTabDots();

        if (dirty) {
            setBarState('dirty', 'Есть несохранённые изменения');
        } else if (!stateEl.classList.contains('is-ok')) {
            setBarState('', 'Все изменения сохранены');
        }
    }

    function setBlocked(blocked) {
        state.blocked = blocked;
        saveBtn.disabled = blocked;

        const container = document.getElementById('form-messages');
        container.innerHTML = blocked
            ? '<div class="alert alert-danger mb-0" role="alert">Не удалось загрузить настройки. Обновите страницу — пока данные не загрузились, сохранять нельзя, иначе можно стереть заполненное.</div>'
            : '';

        if (blocked) setBarState('bad', 'Настройки не загрузились');
    }

    /* ---------- the day that would not save ---------- */

    function validateWeeklyHours() {
        let firstBad = null;

        scheduleDays.forEach(day => {
            const enabled = document.getElementById('weekly-day-' + day).checked;
            // The rule used to be silent: an enabled day without hours was
            // saved as «disabled» and the switch came back off.
            const message = enabled && getSelectedScheduleMode() === 'weekly' ? wdDayError(day) : '';

            wdShowError(day, message);

            if (message && !firstBad) firstBad = wdFirstField(day);
        });

        // The shift schedule has the same hours rule: without valid hours it saves no slots at all.
        const cycleMessage = getSelectedScheduleMode() === 'cycle' ? wdDayError('cycle') : '';
        wdShowError('cycle', cycleMessage);
        if (cycleMessage && !firstBad) firstBad = wdFirstField('cycle');

        // Same rule for the day being edited in the custom month.
        const monthlyOpen = getSelectedScheduleMode() === 'monthly' && !document.getElementById('mday').hidden;
        const dayMessage = monthlyOpen ? wdDayError('mday') : '';
        wdShowError('mday', dayMessage);
        if (dayMessage && !firstBad) firstBad = wdFirstField('mday');

        return firstBad;
    }

    function focusField(element) {
        if (!element) return;

        const tabPane = element.closest('.settings-card[id^="settings-"]');
        if (tabPane) showSettingsTab(tabPane.id);

        const details = element.closest('details');
        if (details) details.open = true;

        const hiddenPassword = element.closest('.settings-password-fields');
        if (hiddenPassword) hiddenPassword.classList.add('is-visible');

        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => element.focus({ preventScroll: true }), 300);
    }

    document.getElementById('settings-form').addEventListener('input', refreshDirty);
    document.getElementById('settings-form').addEventListener('change', refreshDirty);

    cancelBtn.addEventListener('click', async () => {
        if (!confirm('Вернуть сохранённые значения? Всё, что вы ввели и не сохранили, пропадёт.')) return;

        document.getElementById('current_password').value = '';
        document.getElementById('new_password').value = '';
        document.getElementById('new_password_confirmation').value = '';
        await loadSettings();
        state.baseline = snapshot();
        setBarState('', 'Все изменения сохранены');
        refreshDirty();
    });

    window.addEventListener('beforeunload', (event) => {
        if (!isDirty()) return;

        event.preventDefault();
        event.returnValue = 'Изменения не сохранены.';
        return 'Изменения не сохранены.';
    });

    document.getElementById('settings-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        form.querySelectorAll('.invalid-feedback').forEach(el=>el.remove());
        form.querySelectorAll('.is-invalid').forEach(el=>el.classList.remove('is-invalid'));
        document.querySelectorAll('[data-slot-error]').forEach(el => el.hidden = true);

        if (state.blocked) return;

        const badDay = validateWeeklyHours();
        if (badDay) {
            setBarState('bad', 'Укажите часы у включённых дней.');
            focusField(badDay);
            return;
        }

        const payload = buildPayload();

        if(form.new_password.value){
            payload.current_password = form.current_password.value;
            payload.new_password = form.new_password.value;
            payload.new_password_confirmation = form.new_password_confirmation.value;
        }

        saveBtn.disabled = true;
        setBarState('', 'Сохраняем…');

        const res = await fetch('/api/v1/settings', {
            method: 'PATCH',
            headers: authHeaders({ 'Content-Type': 'application/json' }),
            credentials: 'include',
            body: JSON.stringify(payload)
        });
        const result = await res.json().catch(()=>({}));
        saveBtn.disabled = false;
        if(!res.ok){
            const errors = result.error?.fields || {};
            const firstMessage = Object.keys(errors).length
                ? errors[Object.keys(errors)[0]][0]
                : (result.error?.message || 'Не удалось сохранить.');

            // The answer used to be inserted at the top of a six-screen page,
            // 2300px above the button that had just been pressed.
            setBarState('bad', firstMessage);

            let firstInvalid = null;

            Object.keys(errors).forEach(key=>{
                const fieldName = key.replace(/\.(\w+)/g,'[$1]');
                let input = form.querySelector(`[name="${fieldName}"]`);
                if(!input){
                    const base = key.split('.')[0];
                    input = form.querySelector(`[name="${base}[]"]`);
                }
                if(input){
                    input.classList.add('is-invalid');
                    const container = input.closest('.form-control-validation') || input.parentNode;
                    const div = document.createElement('div');
                    div.classList.add('invalid-feedback');
                    div.style.display = 'block';
                    div.textContent = errors[key][0];
                    container.appendChild(div);
                    if (!firstInvalid) firstInvalid = input;
                }
            });

            focusField(firstInvalid);
            return;
        }
        form.current_password.value='';
        form.new_password.value='';
        form.new_password_confirmation.value='';
        // Reloading brings back what the server actually stored — the phone,
        // for one, comes home without the mask it was typed in.
        await loadSettings();
        state.baseline = snapshot();
        cancelBtn.hidden = true;
        setBarState('ok', 'Сохранено');

        // If user changed name, update initials in settings avatar (when no image).
        const initials = computeInitials(form.name.value);
        const img = document.getElementById('uploadedAvatarImg');
        if (!img || img.classList.contains('d-none')) {
            setSettingsAvatar(null, initials);
        }
    });

    document.getElementById('upload').addEventListener('change', async (e) => {
        const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
        if (!file) return;

        const fd = new FormData();
        fd.append('avatar', file);

        const res = await fetch('/api/v1/user/avatar', {
            method: 'POST',
            headers: authHeaders(),
            credentials: 'include',
            body: fd
        });

        const result = await res.json().catch(()=>({}));
        if (!res.ok) {
            const msg = result.message || result.error?.message || 'Error';
            showMessage('danger', msg);
            return;
        }

        setSettingsAvatar(result.avatar_url, result.initials);
        document.querySelectorAll('[data-user-avatar-img]').forEach(img => {
            img.src = result.avatar_url;
            img.classList.remove('d-none');
        });
        document.querySelectorAll('[data-user-initial]').forEach(el => el.classList.add('d-none'));
        showMessage('success', '{{ __('settings.saved') }}');
    });

    const resetBtn = document.querySelector('.account-image-reset');
    if (resetBtn) resetBtn.addEventListener('click', async () => {
        const res = await fetch('/api/v1/user/avatar', {
            method: 'DELETE',
            headers: authHeaders(),
            credentials: 'include',
        });

        const result = await res.json().catch(()=>({}));
        if (!res.ok) {
            const msg = result.message || result.error?.message || 'Error';
            showMessage('danger', msg);
            return;
        }

        const form = document.getElementById('settings-form');
        const currentName = form && form.name ? form.name.value : '';
        setSettingsAvatar(null, result.initials || computeInitials(currentName));
        document.querySelectorAll('[data-user-avatar-img]').forEach(img => {
            img.removeAttribute('src');
            img.classList.add('d-none');
        });
        document.querySelectorAll('[data-user-initial]').forEach(el => el.classList.remove('d-none'));
    });

    document.getElementById('delete-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        if(!confirm('{{ __('settings.confirm_delete') }}')) return;
        const res = await fetch('/api/v1/user', {
            method: 'DELETE',
            headers: authHeaders({ 'Content-Type': 'application/json' }),
            credentials: 'include',
            body: JSON.stringify({password: e.target.password.value})
        });
        if(res.status === 204){
            window.location.href = '/';
        } else {
            const err = await res.json().catch(()=>({}));
            showMessage('danger', err.error?.message || 'Error');
        }
    });
    </script>
    @include('components.phone-mask-script')
@endsection
