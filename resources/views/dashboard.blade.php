@extends('layouts.app')

@section('title', __('dashboard.title'))

@section('meta')
    @include('components.veloria-datetime-picker-styles')
    <style>
        /*
         * The dashboard is a day sheet: who is coming today, who is slipping away,
         * and where the gaps are.
         *
         * It uses the same shell as the rest of the app - full container width, a
         * hero, and cards - so moving between Clients, Orders and here does not
         * feel like changing products. Inside each card the day keeps its own
         * grammar: time in a gutter set in tabular figures, hairline rows rather
         * than nested boxes, and brand colour reserved for the primary action and
         * the dot marking a visit worth a second look.
         */
        .day {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* The card shell the other pages use: soft, borderless, gently raised. */
        .day-surface {
            border: none;
            border-radius: 1.35rem;
            box-shadow: 0 24px 54px -36px rgba(37, 26, 84, 0.42);
            background: color-mix(in srgb, var(--bs-card-bg) 96%, transparent);
        }

        .day-surface > .card-body {
            padding: 1.35rem 1.5rem;
        }

        /*
         * The width is spent on a second column rather than on stretching rows:
         * the day on the left, what to do about the rest of the week on the
         * right. Both sides are actionable, so this is not a widget rail.
         */
        .day-split {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1.5rem;
            align-items: start;
        }

        @media (min-width: 1200px) {
            .day-split {
                grid-template-columns: minmax(0, 1fr) minmax(0, 23rem);
            }
        }

        .day-primary,
        .day-aside {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Setup strip ------------------------------------------------------- */

        .day-setup {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem 1.25rem;
            padding: 0.85rem 1.1rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.625rem;
            background: var(--bs-body-bg);
        }

        .day-setup-label {
            font-weight: 600;
            color: var(--bs-heading-color);
        }

        .day-setup-track {
            display: flex;
            gap: 0.25rem;
            flex: 1 1 8rem;
            min-width: 6rem;
            max-width: 14rem;
        }

        .day-setup-track span {
            flex: 1;
            height: 0.25rem;
            border-radius: 999px;
            background: var(--bs-border-color);
        }

        .day-setup-track span[data-done="true"] {
            background: var(--bs-primary);
        }

        .day-setup-count {
            color: var(--bs-secondary-color);
            font-variant-numeric: tabular-nums;
        }

        .day-setup .btn {
            margin-inline-start: auto;
        }

        /* Hero -------------------------------------------------------------- */

        /*
         * Matches the hero on Clients, Orders and Analytics so the app reads as
         * one product: same radius, same soft brand tint, same eyebrow pill.
         * The decorative blurred circle those pages carry is left out, since it
         * says nothing and the day sheet below is already the point of interest.
         */
        .day-hero {
            border: 1px solid rgba(var(--bs-primary-rgb), 0.12);
            border-radius: 1.6rem;
            padding: 1.6rem;
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.12), transparent 36%),
                linear-gradient(140deg, rgba(var(--bs-primary-rgb), 0.06), rgba(var(--bs-info-rgb, 0, 207, 232), 0.05) 58%, rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.12));
            box-shadow: 0 24px 54px -36px rgba(37, 26, 84, 0.42);
        }

        .day-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.72);
            color: var(--bs-body-color);
            font-size: 0.8125rem;
            font-weight: 600;
        }

        .day-title {
            margin: 0 0 0.5rem;
            font-size: 1.5rem;
            font-weight: 600;
            line-height: 1.2;
            color: var(--bs-heading-color);
        }

        .day-summary {
            margin: 0;
            color: var(--bs-secondary-color);
            font-variant-numeric: tabular-nums;
        }

        /* The day list ------------------------------------------------------ */

        .day-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .day-row {
            display: grid;
            grid-template-columns: 4.5rem minmax(0, 1fr) auto;
            align-items: start;
            gap: 0 1.25rem;
            padding: 1.1rem 0;
            border-top: 1px solid var(--bs-border-color);
        }

        .day-row:last-child {
            border-bottom: 1px solid var(--bs-border-color);
        }

        /*
         * The hairline through the time gutter is the one piece of decoration on the
         * page, and it earns its place: it turns a list of rows into a sequence of
         * hours. It stops short at the first and last row so the day has a beginning
         * and an end rather than running off the screen.
         */
        .day-row-time {
            position: relative;
            padding-inline-start: 0.9rem;
            font-size: 1.0625rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            color: var(--bs-heading-color);
            white-space: nowrap;
        }

        .day-row-time::before {
            content: '';
            position: absolute;
            inset-block: -1.1rem;
            inset-inline-start: 0.185rem;
            width: 1px;
            background: var(--bs-border-color);
        }

        .day-row:first-child .day-row-time::before {
            inset-block-start: 0.55rem;
        }

        .day-row:last-child .day-row-time::before {
            inset-block-end: 0.55rem;
        }

        .day-row-time::after {
            content: '';
            position: absolute;
            inset-inline-start: 0;
            inset-block-start: 0.4rem;
            width: 0.4375rem;
            height: 0.4375rem;
            border-radius: 999px;
            background: var(--bs-border-color);
        }

        .day-row[data-attention="true"] .day-row-time::after {
            background: var(--bs-primary);
        }

        .day-row-client {
            display: inline-block;
            font-size: 1rem;
            font-weight: 600;
            color: var(--bs-heading-color);
            text-decoration: none;
        }

        .day-row-client:hover,
        .day-row-client:focus-visible {
            color: var(--bs-primary);
            text-decoration: underline;
            text-underline-offset: 0.2em;
        }

        .day-row-service,
        .day-row-note {
            margin: 0.15rem 0 0;
            color: var(--bs-secondary-color);
        }

        .day-row-note {
            font-size: 0.8125rem;
        }

        .day-row-side {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.2rem;
            text-align: end;
        }

        .day-row-price {
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            color: var(--bs-heading-color);
            white-space: nowrap;
        }

        .day-row-status {
            font-size: 0.8125rem;
            color: var(--bs-secondary-color);
            white-space: nowrap;
        }

        /* Empty state ------------------------------------------------------- */

        .day-empty {
            padding: 2.5rem 0;
            border-top: 1px solid var(--bs-border-color);
            border-bottom: 1px solid var(--bs-border-color);
        }

        .day-empty-title {
            margin: 0 0 0.35rem;
            font-size: 1.0625rem;
            font-weight: 600;
            color: var(--bs-heading-color);
        }

        .day-empty-text {
            margin: 0 0 1.1rem;
            max-width: 34rem;
            color: var(--bs-secondary-color);
        }

        /* Secondary blocks -------------------------------------------------- */

        /*
         * Everything below the day sheet follows the same grammar: a quiet title,
         * hairline-separated rows, the claim on the left and the action on the
         * right. No cards, so the eye keeps reading down instead of hopping
         * between boxes.
         */
        .day-block-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        .day-block-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--bs-heading-color);
        }

        .day-block-link {
            color: var(--bs-secondary-color);
            font-size: 0.8125rem;
            text-decoration: none;
        }

        .day-block-link:hover,
        .day-block-link:focus-visible {
            color: var(--bs-primary);
            text-decoration: underline;
            text-underline-offset: 0.2em;
        }

        .day-due,
        .day-free {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        /*
         * A grid rather than a wrapping flex row: in a 23rem column some names fit
         * beside the button and some do not, and with flex-wrap the button jumped
         * between the right edge and the next line from row to row.
         */
        .day-due-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 0.5rem 0.75rem;
            padding: 0.85rem 0;
            border-top: 1px solid var(--bs-border-color);
        }

        .day-due-row:first-child {
            border-top: none;
            padding-top: 0.35rem;
        }

        .day-due-action {
            white-space: nowrap;
        }

        .day-due-name {
            font-weight: 600;
            color: var(--bs-heading-color);
            text-decoration: none;
        }

        .day-due-name:hover,
        .day-due-name:focus-visible {
            color: var(--bs-primary);
            text-decoration: underline;
            text-underline-offset: 0.2em;
        }

        .day-due-reason {
            margin: 0.1rem 0 0;
            color: var(--bs-secondary-color);
            font-size: 0.875rem;
            font-variant-numeric: tabular-nums;
        }

        .day-free-row {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            padding: 0.7rem 0;
            border-top: 1px solid var(--bs-border-color);
        }

        .day-free-row:first-child {
            border-top: none;
            padding-top: 0.35rem;
        }

        .day-free-day {
            font-weight: 600;
            color: var(--bs-heading-color);
        }

        .day-free-slots {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        .day-free-slot {
            padding: 0.15rem 0.5rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.375rem;
            font-variant-numeric: tabular-nums;
            color: var(--bs-heading-color);
        }

        .day-free-more,
        .day-free-hint {
            color: var(--bs-secondary-color);
            font-size: 0.875rem;
        }

        .day-free-more {
            align-self: center;
        }

        .day-free-hint {
            margin: 0.75rem 0 0;
        }

        /* Week strip -------------------------------------------------------- */

        .day-week {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem 2.5rem;
            align-items: baseline;
        }

        .day-week-label {
            color: var(--bs-secondary-color);
            font-size: 0.8125rem;
        }

        .day-week-item {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }

        .day-week-value {
            font-size: 1.0625rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            color: var(--bs-heading-color);
        }

        .day-week-item--chart .day-week-value {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        /*
         * Deliberately unlabelled and unscaled. The number beside it carries the
         * value; the line carries only the shape of the last two months, which is
         * the part a glance can actually use.
         */
        .day-spark {
            width: 5.5rem;
            height: 1.625rem;
            overflow: visible;
        }

        .day-spark polyline {
            fill: none;
            stroke: var(--bs-primary);
            stroke-width: 1.5;
            stroke-linecap: round;
            stroke-linejoin: round;
            vector-effect: non-scaling-stroke;
        }

        /* Outreach modal ---------------------------------------------------- */

        .outreach-modal .modal-content {
            border: 0;
            border-radius: 0.875rem;
        }

        .outreach-modal .modal-header {
            align-items: flex-start;
            border-bottom: 0;
            padding-bottom: 0;
        }

        .outreach-modal .modal-body {
            padding-top: 1rem;
        }

        .outreach-title {
            margin: 0 0 0.2rem;
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--bs-heading-color);
        }

        .outreach-subtitle {
            color: var(--bs-secondary-color);
            font-size: 0.875rem;
        }

        .outreach-text {
            min-height: 8rem;
            line-height: 1.5;
        }

        .outreach-note,
        .outreach-error {
            margin: 0.75rem 0 0;
            font-size: 0.8125rem;
        }

        .outreach-note {
            color: var(--bs-secondary-color);
        }

        .outreach-error {
            color: var(--bs-danger);
        }

        .outreach-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-top: 1.25rem;
        }

        .outreach-actions .btn:last-child {
            margin-inline-start: auto;
        }

        /* Setup wizard ------------------------------------------------------ */

        .setup-modal .modal-content {
            border: 0;
            border-radius: 0.875rem;
        }

        .setup-modal .modal-body {
            padding: 1.75rem;
        }

        .setup-step-of {
            margin: 0 0 0.35rem;
            font-size: 0.8125rem;
            color: var(--bs-secondary-color);
        }

        .setup-title {
            margin: 0 0 0.4rem;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--bs-heading-color);
        }

        .setup-text {
            margin: 0 0 1.5rem;
            color: var(--bs-secondary-color);
        }

        .setup-days {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        /*
         * Day toggles are checkboxes wearing a different coat: real inputs, so the
         * keyboard and screen readers keep working, with the box itself hidden and
         * the label carrying the visible state.
         */
        .setup-day input {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        .setup-day span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 3rem;
            padding: 0.5rem 0.65rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.5rem;
            color: var(--bs-body-color);
            cursor: pointer;
            transition: border-color 0.15s ease, color 0.15s ease, background-color 0.15s ease;
        }

        .setup-day:hover span {
            border-color: var(--bs-primary);
        }

        .setup-day input:checked + span {
            border-color: var(--bs-primary);
            background: var(--bs-primary);
            color: #fff;
        }

        .setup-day input:focus-visible + span {
            outline: 2px solid var(--bs-primary);
            outline-offset: 2px;
        }

        /* Aligned to the bottom so the inputs line up even when one label wraps. */
        .setup-field-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(8rem, 1fr));
            align-items: end;
            gap: 1rem;
        }

        .setup-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1.75rem;
        }

        .setup-actions .setup-actions-primary {
            margin-inline-start: auto;
        }

        .setup-error {
            margin-top: 1rem;
            color: var(--bs-danger);
        }

        .setup-done-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            margin-bottom: 1rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb), 0.1);
            color: var(--bs-primary);
            font-size: 1.35rem;
        }

        @media (max-width: 575.98px) {
            .day {
                gap: 1.5rem;
            }

            .day-row {
                grid-template-columns: 3.75rem minmax(0, 1fr);
                gap: 0.35rem 0.9rem;
            }

            .day-row-side {
                grid-column: 2;
                flex-direction: row;
                align-items: baseline;
                justify-content: flex-start;
                gap: 0.6rem;
                text-align: start;
            }

            .day-setup .btn {
                margin-inline-start: 0;
                width: 100%;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $onboardingSteps = collect($onboarding['steps'] ?? []);
        $onboardingTotal = max($onboardingSteps->count(), 1);
        $onboardingCompleted = (int) ($onboarding['completed_steps'] ?? 0);
        $setupPending = $onboardingCompleted < $onboardingSteps->count();
        $formatServices = static fn (array $services): string => collect($services)->filter()->implode(', ');
    @endphp

    <div class="day">
        @if ($setupPending)
            <div class="day-setup">
                <span class="day-setup-label">{{ __('dashboard.setup.title') }}</span>
                <span class="day-setup-track" aria-hidden="true">
                    @foreach ($onboardingSteps as $step)
                        <span data-done="{{ ! empty($step['completed']) ? 'true' : 'false' }}"></span>
                    @endforeach
                </span>
                <span class="day-setup-count">
                    {{ __('dashboard.setup.progress', ['done' => $onboardingCompleted, 'total' => $onboardingTotal]) }}
                </span>
                <button type="button" class="btn btn-primary btn-sm" data-setup-open>
                    {{ __('dashboard.setup.continue') }}
                </button>
            </div>
        @endif

        <section class="day-hero">
            <div class="d-flex flex-column flex-xl-row justify-content-between gap-4 align-items-xl-start">
                <div class="d-flex flex-column gap-3">
                    <span class="day-eyebrow">
                        <i class="ri ri-calendar-check-line text-primary"></i>
                        {{ __('dashboard.hero.eyebrow') }}
                    </span>
                    <div>
                        <h1 class="day-title">{{ $today['date_label'] }}</h1>
                        <p class="day-summary">
                            @if ($today['count'] > 0)
                                {{ $today['count'] }} {{ trans_choice('dashboard.day.appointments', $today['count']) }}
                                @if ($today['expected_revenue'] > 0)
                                    &middot; {{ __('dashboard.day.expected', ['amount' => $today['expected_revenue_formatted']]) }}
                                @endif
                            @else
                                {{ __('dashboard.day.empty.title') }}
                            @endif
                        </p>
                    </div>
                </div>
                {{--
                    One primary action per screen. Until the account is set up, that
                    action is finishing setup, so booking steps back to a quieter button.
                --}}
                <div class="d-flex flex-column flex-sm-row gap-2 align-self-start">
                    <a href="{{ route('calendar') }}" class="btn btn-outline-secondary">
                        <i class="ri ri-calendar-line me-1"></i>
                        {{ __('dashboard.free.open_calendar') }}
                    </a>
                    <button
                        type="button"
                        class="btn {{ $setupPending ? 'btn-outline-primary' : 'btn-primary' }}"
                        data-bs-toggle="modal"
                        data-bs-target="#quickCreateModal">
                        <i class="ri ri-add-line me-1"></i>
                        {{ __('dashboard.day.new_appointment') }}
                    </button>
                </div>
            </div>
        </section>

        <div class="day-split">
            <div class="day-primary">
                <section class="card day-surface">
                    <div class="card-body">
                        <div class="day-block-head">
                            <h2 class="day-block-title">{{ __('dashboard.day.title') }}</h2>
                            <a class="day-block-link" href="{{ route('orders.index') }}">{{ __('dashboard.day.all_appointments') }}</a>
                        </div>

                        @if ($schedule->isNotEmpty())
                            <ol class="day-list">
                                @foreach ($schedule as $appointment)
                                    <li class="day-row" data-attention="{{ $appointment['indicator']['type'] !== 'green' ? 'true' : 'false' }}">
                                        <time class="day-row-time">{{ $appointment['time'] }}</time>
                                        <div class="day-row-body">
                                            @if ($appointment['client_url'])
                                                <a class="day-row-client" href="{{ $appointment['client_url'] }}">{{ $appointment['client'] }}</a>
                                            @else
                                                <span class="day-row-client">{{ $appointment['client'] }}</span>
                                            @endif
                                            <p class="day-row-service">
                                                {{ $formatServices($appointment['services']) ?: __('dashboard.day.no_service') }}
                                            </p>
                                            @if (! empty($appointment['note']))
                                                <p class="day-row-note">{{ \Illuminate\Support\Str::limit($appointment['note'], 90) }}</p>
                                            @endif
                                        </div>
                                        <div class="day-row-side">
                                            @if ($appointment['price_formatted'])
                                                <span class="day-row-price">{{ $appointment['price_formatted'] }}</span>
                                            @endif
                                            <span class="day-row-status">{{ $appointment['indicator']['label'] }}</span>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <div class="day-empty">
                                @if ($setupPending)
                                    {{-- No button here: the setup strip above already carries that action. --}}
                                    <p class="day-empty-title">{{ __('dashboard.day.setup_empty.title') }}</p>
                                    <p class="day-empty-text mb-0">{{ __('dashboard.day.setup_empty.text') }}</p>
                                @else
                                    <p class="day-empty-title">{{ __('dashboard.day.empty.title') }}</p>
                                    <p class="day-empty-text mb-0">{{ __('dashboard.day.empty.text') }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </section>

                @if ($week['has_data'] || ! empty($occupancy['has_data']))
                    {{--
                        Money figures appear only once money has actually come in. A row
                        of "0 ₽" is not information, it is discouragement, and the week a
                        master sees their first client is the worst week to serve it.
                    --}}
                    <section class="card day-surface">
                        <div class="card-body">
                            <div class="day-block-head">
                                <h2 class="day-block-title">{{ __('dashboard.week.title') }}</h2>
                                <a class="day-block-link" href="{{ route('analytics') }}">{{ __('dashboard.week.all_analytics') }}</a>
                            </div>
                            <div class="day-week">
                                @if (! empty($occupancy['has_data']))
                                    @php
                                        // A sparkline, not a chart: eight points, no axes, no legend.
                                        // Occupancy leads revenue by a couple of weeks, so a sagging
                                        // line is a warning while there is still time to act.
                                        $points = collect($occupancy['weeks'])->filter(fn ($w) => $w['share'] !== null)->values();
                                        $shares = $points->pluck('share');

                                        // Scaled to its own range, not to 0-100. A master working at
                                        // 60-70% would otherwise get a flat line across the middle and
                                        // learn nothing from it. The number beside it carries the level;
                                        // the line only has to carry the shape.
                                        $low = (int) $shares->min();
                                        $high = (int) $shares->max();
                                        $span = max(1, $high - $low);
                                        $stepX = $points->count() > 1 ? 104 / ($points->count() - 1) : 0;
                                        $path = $points
                                            ->map(fn ($w, $i) => round(2 + $i * $stepX, 1) . ',' . round(21 - (($w['share'] - $low) / $span) * 16, 1))
                                            ->implode(' ');
                                    @endphp
                                    <div class="day-week-item day-week-item--chart">
                                        <span class="day-week-value">
                                            <svg class="day-spark" viewBox="0 0 108 26" preserveAspectRatio="none" aria-hidden="true">
                                                <polyline points="{{ $path }}" />
                                            </svg>
                                            {{ __('dashboard.occupancy.value', ['value' => $occupancy['current']]) }}
                                        </span>
                                        <span class="day-week-label">
                                            {{ __('dashboard.occupancy.title') }}@if ($occupancy['previous'] !== null),
                                                @if ($occupancy['current'] > $occupancy['previous'])
                                                    {{ __('dashboard.occupancy.up', ['value' => $occupancy['previous']]) }}
                                                @elseif ($occupancy['current'] < $occupancy['previous'])
                                                    {{ __('dashboard.occupancy.down', ['value' => $occupancy['previous']]) }}
                                                @else
                                                    {{ __('dashboard.occupancy.same') }}
                                                @endif
                                            @endif
                                        </span>
                                    </div>
                                @endif
                                @if ($week['revenue'] > 0)
                                    <div class="day-week-item">
                                        <span class="day-week-value">{{ $week['revenue_formatted'] }}</span>
                                        <span class="day-week-label">{{ __('dashboard.week.revenue') }}</span>
                                    </div>
                                @endif
                                @if ($week['clients'] > 0)
                                    <div class="day-week-item">
                                        <span class="day-week-value">{{ $week['clients'] }}</span>
                                        <span class="day-week-label">{{ __('dashboard.week.clients') }}</span>
                                    </div>
                                @endif
                                @if ($week['revenue'] > 0)
                                    <div class="day-week-item">
                                        <span class="day-week-value">{{ $week['average_ticket_formatted'] }}</span>
                                        <span class="day-week-label">{{ __('dashboard.week.average_ticket') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </section>
                @endif
            </div>

            <aside class="day-aside">
                {{--
                    Clients who have slipped past their own rhythm. Each row is a name,
                    the arithmetic behind the claim, and the one action worth taking.
                --}}
                @if ($dueClients->isNotEmpty())
                    <section class="card day-surface">
                        <div class="card-body">
                            <div class="day-block-head">
                                <h2 class="day-block-title">{{ __('dashboard.due.title') }}</h2>
                                <a class="day-block-link" href="{{ route('clients.index') }}">{{ __('dashboard.due.all_clients') }}</a>
                            </div>
                            <ul class="day-due">
                                @foreach ($dueClients as $due)
                                    <li class="day-due-row">
                                        <div class="day-due-body">
                                            <a class="day-due-name" href="{{ $due['url'] }}">{{ $due['name'] }}</a>
                                            <p class="day-due-reason">
                                                @if ($due['interval_days'])
                                                    {{ __('dashboard.due.rhythm', [
                                                        'interval' => $due['interval_days'] . ' ' . trans_choice('dashboard.due.days', $due['interval_days']),
                                                        'passed' => $due['days_since'] . ' ' . trans_choice('dashboard.due.days', $due['days_since']),
                                                    ]) }}
                                                @else
                                                    {{ __('dashboard.due.once', [
                                                        'passed' => $due['days_since'] . ' ' . trans_choice('dashboard.due.days', $due['days_since']),
                                                    ]) }}
                                                @endif
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary btn-sm day-due-action"
                                            data-outreach-open
                                            data-client-id="{{ $due['client_id'] }}"
                                            data-client-name="{{ $due['name'] }}"
                                            data-client-phone="{{ $due['phone'] }}"
                                            @if (! empty($freeSlots))
                                                data-free-day="{{ \Illuminate\Support\Str::ucfirst($freeSlots[0]['label']) }}"
                                                data-free-slots="{{ implode(',', $freeSlots[0]['free']) }}"
                                            @endif
                                        >
                                            {{ __('dashboard.due.write') }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </section>
                @endif

                {{--
                    Open slots are only worth showing next to the people who could fill
                    them, so the two blocks are deliberately adjacent.
                --}}
                @if (! empty($freeSlots))
                    <section class="card day-surface">
                        <div class="card-body">
                            <div class="day-block-head">
                                <h2 class="day-block-title">{{ __('dashboard.free.title') }}</h2>
                            </div>
                            <ul class="day-free">
                                @foreach ($freeSlots as $day)
                                    <li class="day-free-row">
                                        <span class="day-free-day">{{ \Illuminate\Support\Str::ucfirst($day['label']) }}</span>
                                        <span class="day-free-slots">
                                            @foreach ($day['free'] as $slot)
                                                <span class="day-free-slot">{{ $slot }}</span>
                                            @endforeach
                                            @if ($day['free_count'] > count($day['free']))
                                                <span class="day-free-more">
                                                    {{ __('dashboard.free.more', ['count' => $day['free_count'] - count($day['free'])]) }}
                                                </span>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                            @if ($dueClients->isNotEmpty())
                                <p class="day-free-hint">
                                    {{ __('dashboard.free.hint', ['names' => $dueClients->pluck('name')->take(2)->implode(', ')]) }}
                                </p>
                            @endif
                        </div>
                    </section>
                @endif
            </aside>
        </div>
    </div>

    @if ($setupPending)
        @include('dashboard.setup-wizard', ['onboarding' => $onboarding])
    @endif

    @if ($dueClients->isNotEmpty())
        @include('components.message-sheet')
    @endif

    <div id="quick-create-alerts" class="mt-4"></div>
@endsection

@section('scripts')
    @include('components.phone-mask-script')
    @include('components.veloria-datetime-picker-script')
    @include('components.order-quick-create-modal')
    @if ($setupPending)
        @include('dashboard.setup-wizard-script')
    @endif
    @if ($dueClients->isNotEmpty())
        @include('components.message-sheet-script')
    @endif
    @include('components.order-quick-create-script')
@endsection
