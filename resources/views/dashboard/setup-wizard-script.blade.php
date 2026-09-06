<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modalElement = document.getElementById('setupWizardModal');

        if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return;
        }

        var ORDER = ['schedule', 'services', 'clients'];
        var STORAGE_KEY = 'veloria:onboarding:pending';
        var GENERIC_ERROR = @json(__('dashboard.setup.wizard.error'));
        var SAVING_LABEL = @json(__('dashboard.setup.wizard.saving'));

        var state;

        try {
            state = JSON.parse(modalElement.dataset.setupState || '{}');
        } catch (error) {
            state = {};
        }

        var modal = new bootstrap.Modal(modalElement);
        var sections = {};

        modalElement.querySelectorAll('[data-step]').forEach(function (section) {
            sections[section.dataset.step] = section;
        });

        function readMarker() {
            try {
                return window.localStorage.getItem(STORAGE_KEY);
            } catch (error) {
                return null;
            }
        }

        function clearMarker() {
            try {
                window.localStorage.removeItem(STORAGE_KEY);
            } catch (error) {
                /* Private mode and blocked storage are fine: the strip still opens the wizard. */
            }
        }

        function firstPending() {
            for (var i = 0; i < ORDER.length; i++) {
                if (!state[ORDER[i]]) {
                    return ORDER[i];
                }
            }

            return 'done';
        }

        function show(step) {
            Object.keys(sections).forEach(function (key) {
                sections[key].hidden = key !== step;
            });

            if (step === 'done') {
                clearMarker();
            }

            var focusTarget = sections[step]
                ? sections[step].querySelector('input, select, button:not(.btn-close)')
                : null;

            if (focusTarget) {
                focusTarget.focus({ preventScroll: true });
            }
        }

        function advanceFrom(step) {
            state[step] = true;
            show(firstPending());
        }

        function stepBefore(step) {
            var index = ORDER.indexOf(step);

            return index > 0 ? ORDER[index - 1] : step;
        }

        function showError(form, message) {
            var target = form.querySelector('[data-setup-error]');

            if (!target) {
                return;
            }

            target.textContent = message;
            target.hidden = !message;
        }

        /**
         * Laravel answers a failed validation with {message, errors: {field: [...]}}.
         * The first field message is the specific one, so prefer it over the generic
         * summary; fall back only when the shape is not what we expect.
         */
        function extractError(payload) {
            if (payload && payload.errors) {
                var keys = Object.keys(payload.errors);

                if (keys.length && Array.isArray(payload.errors[keys[0]]) && payload.errors[keys[0]].length) {
                    return payload.errors[keys[0]][0];
                }
            }

            if (payload && typeof payload.message === 'string' && payload.message) {
                return payload.message;
            }

            return GENERIC_ERROR;
        }

        function headers() {
            if (typeof window.authHeaders === 'function') {
                return window.authHeaders();
            }

            var match = document.cookie.match(/(^| )token=([^;]+)/);
            var result = { Accept: 'application/json', 'Content-Type': 'application/json' };

            if (match) {
                result.Authorization = 'Bearer ' + decodeURIComponent(match[2]);
            }

            return result;
        }

        var ENDPOINTS = {
            schedule: '/api/v1/onboarding/schedule',
            services: '/api/v1/services',
            clients: '/api/v1/clients',
        };

        function buildPayload(step, form) {
            var data = new FormData(form);

            if (step === 'schedule') {
                return {
                    days: data.getAll('days'),
                    start: data.get('start'),
                    end: data.get('end'),
                    step: Number(data.get('step')),
                };
            }

            if (step === 'services') {
                return {
                    name: (data.get('name') || '').trim(),
                    base_price: Number(data.get('base_price')),
                    duration_min: Number(data.get('duration_min')),
                };
            }

            return {
                name: (data.get('name') || '').trim(),
                phone: (data.get('phone') || '').trim(),
            };
        }

        modalElement.querySelectorAll('[data-setup-form]').forEach(function (form) {
            var step = form.dataset.setupForm;

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                var submit = form.querySelector('[data-setup-submit]');
                var originalLabel = submit ? submit.textContent : '';

                showError(form, '');

                if (submit) {
                    submit.disabled = true;
                    submit.textContent = SAVING_LABEL;
                }

                fetch(ENDPOINTS[step], {
                    method: 'POST',
                    headers: headers(),
                    credentials: 'include',
                    body: JSON.stringify(buildPayload(step, form)),
                })
                    .then(function (response) {
                        return response
                            .json()
                            .catch(function () {
                                return {};
                            })
                            .then(function (payload) {
                                return { ok: response.ok, payload: payload };
                            });
                    })
                    .then(function (result) {
                        if (!result.ok) {
                            showError(form, extractError(result.payload));

                            return;
                        }

                        advanceFrom(step);
                    })
                    .catch(function () {
                        showError(form, GENERIC_ERROR);
                    })
                    .finally(function () {
                        if (submit) {
                            submit.disabled = false;
                            submit.textContent = originalLabel;
                        }
                    });
            });
        });

        modalElement.querySelectorAll('[data-setup-back]').forEach(function (button) {
            button.addEventListener('click', function () {
                var section = button.closest('[data-step]');

                if (section) {
                    show(stepBefore(section.dataset.step));
                }
            });
        });

        modalElement.querySelectorAll('[data-setup-finish]').forEach(function (button) {
            button.addEventListener('click', function () {
                window.location.reload();
            });
        });

        document.querySelectorAll('[data-setup-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                show(firstPending());
                modal.show();
            });
        });

        /*
         * Reload once the wizard closes with progress made, so the day sheet and the
         * setup strip reflect what was just saved. Closing without saving anything
         * leaves the page alone.
         */
        var openedAt = null;

        modalElement.addEventListener('show.bs.modal', function () {
            openedAt = JSON.stringify(state);
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            if (openedAt !== null && openedAt !== JSON.stringify(state)) {
                window.location.reload();
            }
        });

        show(firstPending());

        // Registration leaves a marker so the wizard greets a genuinely new account
        // once. After that it is reached from the setup strip on the dashboard.
        if (readMarker()) {
            modal.show();
        }
    });
</script>
