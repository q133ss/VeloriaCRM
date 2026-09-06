@php
    use Illuminate\Support\Carbon;

    /*
     * The wizard collects the setup itself instead of linking away to three
     * different screens. A checklist that only points elsewhere gets dismissed and
     * leaves nothing behind; this one leaves a working schedule, a service and a
     * client.
     */
    $wizardDayKeys = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    $wizardWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->locale(app()->getLocale());
    $wizardDays = collect($wizardDayKeys)->map(fn (string $key, int $index) => [
        'key' => $key,
        'label' => \Illuminate\Support\Str::ucfirst($wizardWeekStart->copy()->addDays($index)->isoFormat('dd')),
        'default' => $index < 5,
    ]);

    $wizardTimes = collect(range(0, 35))
        ->map(fn (int $slot) => sprintf('%02d:%02d', intdiv(360 + $slot * 30, 60), (360 + $slot * 30) % 60));

    $wizardDurations = [
        30 => __('dashboard.setup.wizard.schedule.step_option', ['minutes' => 30]),
        60 => __('dashboard.setup.wizard.schedule.step_hour'),
        90 => __('dashboard.setup.wizard.schedule.step_hour_half'),
        120 => __('dashboard.setup.wizard.schedule.step_two_hours'),
    ];

    $wizardState = [
        'schedule' => (bool) ($onboarding['schedule_configured'] ?? false),
        'services' => (int) ($onboarding['service_count'] ?? 0) > 0,
        'clients' => (int) ($onboarding['client_count'] ?? 0) > 0,
    ];
@endphp

<div
    class="modal fade setup-modal"
    id="setupWizardModal"
    tabindex="-1"
    aria-labelledby="setupWizardTitle"
    aria-hidden="true"
    data-setup-state="{{ json_encode($wizardState) }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <button
                    type="button"
                    class="btn-close float-end"
                    data-bs-dismiss="modal"
                    aria-label="{{ __('dashboard.setup.wizard.skip') }}"></button>

                {{-- Step 1: working hours --}}
                <section class="setup-step" data-step="schedule" hidden>
                    <p class="setup-step-of">{{ __('dashboard.setup.wizard.step_of', ['current' => 1, 'total' => 3]) }}</p>
                    <h2 class="setup-title" id="setupWizardTitle">{{ __('dashboard.setup.wizard.schedule.title') }}</h2>
                    <p class="setup-text">{{ __('dashboard.setup.wizard.schedule.text') }}</p>

                    <form data-setup-form="schedule" novalidate>
                        <fieldset class="mb-4">
                            <legend class="form-label">{{ __('dashboard.setup.wizard.schedule.days') }}</legend>
                            <div class="setup-days">
                                @foreach ($wizardDays as $day)
                                    <label class="setup-day">
                                        <input
                                            type="checkbox"
                                            name="days"
                                            value="{{ $day['key'] }}"
                                            @checked($day['default'])>
                                        <span>{{ $day['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <div class="setup-field-row">
                            <div>
                                <label class="form-label" for="setup-schedule-start">
                                    {{ __('dashboard.setup.wizard.schedule.from') }}
                                </label>
                                <select class="form-select" id="setup-schedule-start" name="start">
                                    @foreach ($wizardTimes as $time)
                                        <option value="{{ $time }}" @selected($time === '10:00')>{{ $time }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="setup-schedule-end">
                                    {{ __('dashboard.setup.wizard.schedule.to') }}
                                </label>
                                <select class="form-select" id="setup-schedule-end" name="end">
                                    @foreach ($wizardTimes as $time)
                                        <option value="{{ $time }}" @selected($time === '20:00')>{{ $time }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="setup-schedule-step">
                                    {{ __('dashboard.setup.wizard.schedule.step') }}
                                </label>
                                <select class="form-select" id="setup-schedule-step" name="step">
                                    @foreach ($wizardDurations as $minutes => $label)
                                        <option value="{{ $minutes }}" @selected($minutes === 60)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <p class="setup-error" data-setup-error hidden></p>

                        <div class="setup-actions">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                {{ __('dashboard.setup.wizard.skip') }}
                            </button>
                            <button type="submit" class="btn btn-primary setup-actions-primary" data-setup-submit>
                                {{ __('dashboard.setup.wizard.next') }}
                            </button>
                        </div>
                    </form>
                </section>

                {{-- Step 2: first service --}}
                <section class="setup-step" data-step="services" hidden>
                    <p class="setup-step-of">{{ __('dashboard.setup.wizard.step_of', ['current' => 2, 'total' => 3]) }}</p>
                    <h2 class="setup-title">{{ __('dashboard.setup.wizard.service.title') }}</h2>
                    <p class="setup-text">{{ __('dashboard.setup.wizard.service.text') }}</p>

                    <form data-setup-form="services" novalidate>
                        <div class="mb-3">
                            <label class="form-label" for="setup-service-name">
                                {{ __('dashboard.setup.wizard.service.name') }}
                            </label>
                            <input
                                type="text"
                                class="form-control"
                                id="setup-service-name"
                                name="name"
                                maxlength="255"
                                placeholder="{{ __('dashboard.setup.wizard.service.name_placeholder') }}"
                                required>
                        </div>

                        <div class="setup-field-row">
                            <div>
                                <label class="form-label" for="setup-service-price">
                                    {{ __('dashboard.setup.wizard.service.price') }}
                                </label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="setup-service-price"
                                    name="base_price"
                                    min="0"
                                    step="50"
                                    inputmode="numeric"
                                    value="1500"
                                    required>
                            </div>
                            <div>
                                <label class="form-label" for="setup-service-duration">
                                    {{ __('dashboard.setup.wizard.service.duration') }}
                                </label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="setup-service-duration"
                                    name="duration_min"
                                    min="5"
                                    max="1440"
                                    step="5"
                                    inputmode="numeric"
                                    value="60"
                                    required>
                            </div>
                        </div>

                        <p class="setup-error" data-setup-error hidden></p>

                        <div class="setup-actions">
                            <button type="button" class="btn btn-outline-secondary" data-setup-back>
                                {{ __('dashboard.setup.wizard.back') }}
                            </button>
                            <button type="submit" class="btn btn-primary setup-actions-primary" data-setup-submit>
                                {{ __('dashboard.setup.wizard.next') }}
                            </button>
                        </div>
                    </form>
                </section>

                {{-- Step 3: first client --}}
                <section class="setup-step" data-step="clients" hidden>
                    <p class="setup-step-of">{{ __('dashboard.setup.wizard.step_of', ['current' => 3, 'total' => 3]) }}</p>
                    <h2 class="setup-title">{{ __('dashboard.setup.wizard.client.title') }}</h2>
                    <p class="setup-text">{{ __('dashboard.setup.wizard.client.text') }}</p>

                    <form data-setup-form="clients" novalidate>
                        <div class="mb-3">
                            <label class="form-label" for="setup-client-name">
                                {{ __('dashboard.setup.wizard.client.name') }}
                            </label>
                            <input
                                type="text"
                                class="form-control"
                                id="setup-client-name"
                                name="name"
                                maxlength="255"
                                placeholder="{{ __('dashboard.setup.wizard.client.name_placeholder') }}"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="setup-client-phone">
                                {{ __('dashboard.setup.wizard.client.phone') }}
                            </label>
                            <input
                                type="tel"
                                class="form-control"
                                id="setup-client-phone"
                                name="phone"
                                maxlength="32"
                                inputmode="tel"
                                data-phone-mask
                                required>
                        </div>

                        <p class="setup-error" data-setup-error hidden></p>

                        <div class="setup-actions">
                            <button type="button" class="btn btn-outline-secondary" data-setup-back>
                                {{ __('dashboard.setup.wizard.back') }}
                            </button>
                            <button type="submit" class="btn btn-primary setup-actions-primary" data-setup-submit>
                                {{ __('dashboard.setup.wizard.next') }}
                            </button>
                        </div>
                    </form>
                </section>

                {{-- Done --}}
                <section class="setup-step" data-step="done" hidden>
                    <div class="setup-done-mark" aria-hidden="true">
                        <i class="icon-base ri ri-check-line"></i>
                    </div>
                    <h2 class="setup-title">{{ __('dashboard.setup.wizard.finish.title') }}</h2>
                    <p class="setup-text">{{ __('dashboard.setup.wizard.finish.text') }}</p>
                    <div class="setup-actions">
                        <button type="button" class="btn btn-primary setup-actions-primary" data-setup-finish>
                            {{ __('dashboard.setup.wizard.finish.action') }}
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
