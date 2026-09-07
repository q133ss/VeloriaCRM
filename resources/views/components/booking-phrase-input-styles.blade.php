<style>
    .booking-phrase {
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
        padding: 0.85rem;
        margin-bottom: 1rem;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.65rem;
        background: var(--bs-tertiary-bg, rgba(var(--bs-emphasis-color-rgb, 0, 0, 0), 0.02));
    }

    .booking-phrase__row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .booking-phrase__spark {
        display: flex;
        flex: 0 0 auto;
        color: var(--bs-primary);
        font-size: 1.15rem;
    }

    .booking-phrase__input {
        flex: 1 1 auto;
        min-width: 0;
    }

    .booking-phrase__icon,
    .booking-phrase__submit {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        padding: 0;
        border-radius: 0.5rem;
    }

    .booking-phrase__icon {
        border: 1px solid var(--bs-border-color);
        color: var(--bs-secondary-color);
    }

    .booking-phrase__icon:hover {
        color: var(--bs-primary);
    }

    .booking-phrase__status {
        min-height: 1.1rem;
        font-size: 0.82rem;
        color: var(--bs-secondary-color);
    }

    .booking-phrase__status--error {
        color: var(--bs-danger);
    }

    .booking-phrase__availability {
        font-size: 0.85rem;
        padding: 0.5rem 0.7rem;
        border-radius: 0.5rem;
        background: rgba(var(--bs-warning-rgb), 0.12);
        color: var(--bs-body-color);
    }

    .booking-phrase__choices:empty {
        display: none;
    }

    .booking-phrase__choice + .booking-phrase__choice {
        margin-top: 0.6rem;
    }

    .booking-phrase__question {
        margin-bottom: 0.35rem;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .booking-phrase__options {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
    }

    .booking-phrase__option {
        display: inline-flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.1rem;
        padding: 0.4rem 0.7rem;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.5rem;
        background: var(--bs-body-bg);
        font-size: 0.85rem;
        line-height: 1.2;
        text-align: left;
    }

    .booking-phrase__option:hover,
    .booking-phrase__option.is-active {
        border-color: var(--bs-primary);
        color: var(--bs-primary);
    }

    .booking-phrase__option-meta {
        font-size: 0.75rem;
        color: var(--bs-secondary-color);
    }

    .booking-phrase__option:hover .booking-phrase__option-meta,
    .booking-phrase__option.is-active .booking-phrase__option-meta {
        color: inherit;
        opacity: 0.75;
    }

    .booking-phrase.is-busy .booking-phrase__input,
    .booking-phrase.is-busy .booking-phrase__submit {
        opacity: 0.6;
        pointer-events: none;
    }
</style>
