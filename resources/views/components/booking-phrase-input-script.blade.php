<script>
    (function () {
        if (window.BookingPhraseInput) {
            return;
        }

        const T = @json(trans('calendar.phrase'));

        let options = null;
        let controller = null;
        let micTimer = null;

        function root() {
            return document.querySelector('[data-booking-phrase]');
        }

        function el(id) {
            return document.getElementById(id);
        }

        function say(message, isError) {
            const status = el('booking-phrase-status');
            if (!status) return;
            // Whatever is being said now outlives the mic hint's countdown.
            clearTimeout(micTimer);
            status.textContent = message || '';
            status.classList.toggle('booking-phrase__status--error', Boolean(isError));
        }

        function busy(on) {
            const node = root();
            if (node) node.classList.toggle('is-busy', on);
        }

        function reset() {
            const input = el('booking-phrase-text');
            if (input) input.value = '';
            say('');
            const choices = el('booking-phrase-choices');
            if (choices) choices.innerHTML = '';
            const availability = el('booking-phrase-availability');
            if (availability) {
                availability.textContent = '';
                availability.classList.add('d-none');
            }
        }

        function optionButton(option, onPick) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'booking-phrase__option';
            button.innerHTML = '<span></span><span class="booking-phrase__option-meta"></span>';
            button.firstChild.textContent = option.label || '';
            button.lastChild.textContent = option.meta || '';
            button.addEventListener('click', function () {
                onPick(option, button);
            });

            return button;
        }

        function renderChoices(choices) {
            const holder = el('booking-phrase-choices');
            if (!holder) return;

            holder.innerHTML = '';

            (choices || []).forEach(function (choice) {
                const block = document.createElement('div');
                block.className = 'booking-phrase__choice';

                const question = document.createElement('div');
                question.className = 'booking-phrase__question';
                question.textContent = choice.question || '';
                block.appendChild(question);

                const list = document.createElement('div');
                list.className = 'booking-phrase__options';

                (choice.options || []).forEach(function (option) {
                    list.appendChild(optionButton(option, function (picked, button) {
                        options.onChoice(choice.field, picked);

                        if (choice.multiple) {
                            // Several services really can be meant at once, so the
                            // block stays open and the chips keep their state.
                            button.classList.toggle('is-active');
                            return;
                        }

                        block.remove();
                    }));
                });

                block.appendChild(list);
                holder.appendChild(block);
            });
        }

        function renderAvailability(availability) {
            const node = el('booking-phrase-availability');
            if (!node) return;

            const message = availability && availability.message;
            node.textContent = message || '';
            node.classList.toggle('d-none', !message);
        }

        async function submit() {
            const input = el('booking-phrase-text');
            const text = input ? input.value.trim() : '';

            if (text.length < 2) {
                say(T.too_short, true);
                return;
            }

            if (controller) {
                controller.abort();
            }
            controller = new AbortController();

            busy(true);
            say(T.parsing);

            try {
                if (options.ensureOptions) {
                    // The service checkboxes have to exist before anything can be ticked.
                    await options.ensureOptions();
                }

                const response = await fetch('/api/v1/orders/parse-intent', {
                    method: 'POST',
                    headers: Object.assign({}, options.authHeaders, { 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ text: text, date: options.getAnchorDate() || null }),
                    signal: controller.signal,
                });

                const result = await response.json().catch(function () { return {}; });

                if (!response.ok) {
                    const fields = (result.error && result.error.fields) || result.errors || {};
                    const first = Object.keys(fields)[0];
                    say((first && fields[first][0]) || (result.error && result.error.message) || T.failed, true);
                    return;
                }

                options.onParsed(result);
                renderChoices(result.choices);
                renderAvailability(result.availability);
                say(summarise(result));
            } catch (error) {
                if (error && error.name === 'AbortError') return;
                say(T.failed, true);
            } finally {
                busy(false);
            }
        }

        function summarise(result) {
            const filled = result.filled || {};
            const parts = [];

            if (filled.client) {
                parts.push(filled.client.name);
            } else if (filled.new_client && filled.new_client.name) {
                parts.push(filled.new_client.name);
            }

            (filled.services || []).forEach(function (service) {
                parts.push(service.name);
            });

            if (filled.scheduled_at) {
                parts.push(filled.scheduled_at.slice(11, 16));
            }

            if (!parts.length) {
                return T.nothing_understood;
            }

            return T.filled_prefix + ' ' + parts.join(' · ');
        }

        function init(userOptions) {
            options = userOptions;

            const input = el('booking-phrase-text');
            const submitButton = document.querySelector('[data-booking-phrase-submit]');
            const mic = document.querySelector('[data-booking-phrase-mic]');

            if (!input || !submitButton) {
                return;
            }

            input.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter') return;

                // The field lives inside <form id="calendar-create-form">, where a
                // bare Enter creates an empty order.
                event.preventDefault();
                event.stopPropagation();
                submit();
            });

            submitButton.addEventListener('click', submit);

            if (mic) {
                mic.addEventListener('click', function () {
                    say(T.mic_soon);
                    clearTimeout(micTimer);
                    micTimer = setTimeout(function () { say(''); }, 3000);
                });
            }
        }

        window.BookingPhraseInput = { init: init, reset: reset };
    })();
</script>
