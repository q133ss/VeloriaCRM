<link rel="stylesheet" href="/assets/vendor/libs/flatpickr/flatpickr.css" />
<style>
    .veloria-datetime-field {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
    }

    .veloria-datetime-label {
        margin-bottom: 0;
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--bs-body-color);
    }

    .veloria-datetime-display-wrap {
        position: relative;
    }

    .veloria-datetime-display {
        padding-right: 3rem;
        cursor: pointer;
        background-image: none !important;
    }

    .veloria-datetime-display::placeholder {
        color: var(--bs-secondary-color);
    }

    .veloria-datetime-open {
        position: absolute;
        top: 50%;
        right: 0.65rem;
        transform: translateY(-50%);
        width: 2.1rem;
        height: 2.1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        border-radius: 999px;
        background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.1);
        color: var(--bs-primary);
    }

    .veloria-datetime-open:hover,
    .veloria-datetime-open:focus {
        background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.16);
        color: var(--bs-primary);
    }

    .veloria-datetime-shortcuts {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .veloria-datetime-chip {
        border: 1px solid rgba(var(--bs-primary-rgb, 255, 0, 252), 0.16);
        border-radius: 999px;
        padding: 0.45rem 0.8rem;
        background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.06);
        color: var(--bs-body-color);
        font-size: 0.82rem;
        font-weight: 700;
        line-height: 1;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
    }

    .veloria-datetime-chip:hover,
    .veloria-datetime-chip:focus {
        background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.12);
        border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.3);
        color: var(--bs-primary);
        transform: translateY(-1px);
    }

    .veloria-datetime-chip--day {
        background: rgba(var(--bs-info-rgb, 0, 207, 232), 0.08);
        border-color: rgba(var(--bs-info-rgb, 0, 207, 232), 0.2);
    }

    .veloria-datetime-chip--day:hover,
    .veloria-datetime-chip--day:focus {
        background: rgba(var(--bs-info-rgb, 0, 207, 232), 0.14);
        border-color: rgba(var(--bs-info-rgb, 0, 207, 232), 0.28);
        color: var(--bs-info);
    }

    .veloria-datetime-help {
        margin-top: -0.15rem;
    }

    .flatpickr-calendar {
        z-index: 1085;
        border: 1px solid rgba(var(--bs-border-color-rgb, 160, 169, 192), 0.55);
        border-radius: 1rem;
        box-shadow: 0 24px 60px -36px rgba(37, 26, 84, 0.5);
        background: var(--bs-body-bg);
    }

    .flatpickr-wrapper {
        position: relative;
        display: block;
        width: 100%;
    }

    .veloria-datetime-field .flatpickr-calendar.static.open {
        position: absolute;
        top: calc(100% + 0.5rem);
        left: 0;
        width: 20.5rem;
        max-width: min(20.5rem, calc(100vw - 2rem));
        margin-top: 0;
    }

    .veloria-datetime-field .flatpickr-calendar.static.arrowTop:before,
    .veloria-datetime-field .flatpickr-calendar.static.arrowTop:after {
        display: none;
    }

    .flatpickr-months .flatpickr-month,
    .flatpickr-current-month .flatpickr-monthDropdown-months,
    .flatpickr-current-month input.cur-year,
    span.flatpickr-weekday,
    .flatpickr-time input,
    .flatpickr-time .flatpickr-time-separator,
    .flatpickr-am-pm {
        color: var(--bs-body-color);
    }

    .flatpickr-day.today {
        border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.4);
    }

    .flatpickr-day.selected,
    .flatpickr-day.startRange,
    .flatpickr-day.endRange,
    .flatpickr-day.selected:hover,
    .flatpickr-day.startRange:hover,
    .flatpickr-day.endRange:hover {
        background: var(--bs-primary);
        border-color: var(--bs-primary);
    }

    .flatpickr-day:hover {
        background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08);
        border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08);
    }

    .flatpickr-time .numInputWrapper span.arrowUp:after {
        border-bottom-color: var(--bs-primary);
    }

    .flatpickr-time .numInputWrapper span.arrowDown:after {
        border-top-color: var(--bs-primary);
    }

    [data-bs-theme="dark"] .veloria-datetime-display {
        background-color: rgba(31, 36, 51, 0.92);
        border-color: rgba(147, 158, 184, 0.24);
        color: var(--bs-body-color);
    }

    [data-bs-theme="dark"] .veloria-datetime-open {
        background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.14);
    }

    [data-bs-theme="dark"] .veloria-datetime-chip {
        background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.1);
        border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.22);
    }

    [data-bs-theme="dark"] .veloria-datetime-chip--day {
        background: rgba(var(--bs-info-rgb, 0, 207, 232), 0.1);
        border-color: rgba(var(--bs-info-rgb, 0, 207, 232), 0.2);
    }

    [data-bs-theme="dark"] .flatpickr-calendar {
        background: #1f2433;
        border-color: rgba(147, 158, 184, 0.18);
    }

    [data-bs-theme="dark"] .flatpickr-months .flatpickr-month,
    [data-bs-theme="dark"] .flatpickr-weekdays,
    [data-bs-theme="dark"] .flatpickr-time {
        background: #1f2433;
    }

    [data-bs-theme="dark"] .flatpickr-day,
    [data-bs-theme="dark"] .flatpickr-time input,
    [data-bs-theme="dark"] .flatpickr-time .flatpickr-time-separator,
    [data-bs-theme="dark"] .flatpickr-am-pm,
    [data-bs-theme="dark"] span.flatpickr-weekday,
    [data-bs-theme="dark"] .flatpickr-current-month .flatpickr-monthDropdown-months,
    [data-bs-theme="dark"] .flatpickr-current-month input.cur-year {
        color: #f6f7fb;
    }

    [data-bs-theme="dark"] .flatpickr-day.flatpickr-disabled,
    [data-bs-theme="dark"] .flatpickr-day.prevMonthDay,
    [data-bs-theme="dark"] .flatpickr-day.nextMonthDay {
        color: rgba(246, 247, 251, 0.35);
    }

    @media (max-width: 575.98px) {
        .veloria-datetime-shortcuts {
            gap: 0.4rem;
        }

        .veloria-datetime-chip {
            flex: 1 1 calc(50% - 0.4rem);
            justify-content: center;
        }

        .veloria-datetime-field .flatpickr-calendar.static.open {
            width: 100%;
            max-width: 100%;
        }
    }
</style>
