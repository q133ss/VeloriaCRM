@php
    $outreachLabels = [
        'loading' => __('dashboard.outreach.loading'),
        'error' => __('dashboard.outreach.error'),
        'copied' => __('dashboard.outreach.copied'),
        'copy' => __('dashboard.outreach.copy'),
        'template' => __('dashboard.outreach.template_note'),
        'limit' => __('dashboard.outreach.limit_note'),
        'remaining' => __('dashboard.outreach.remaining_note'),
    ];
@endphp

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modalElement = document.getElementById('outreachModal');

        if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return;
        }

        var modal = new bootstrap.Modal(modalElement);
        var textField = modalElement.querySelector('[data-outreach-text]');
        var clientLabel = modalElement.querySelector('[data-outreach-client]');
        var noteField = modalElement.querySelector('[data-outreach-note]');
        var errorField = modalElement.querySelector('[data-outreach-error]');
        var sendLink = modalElement.querySelector('[data-outreach-send]');
        var regenerateButton = modalElement.querySelector('[data-outreach-regenerate]');
        var copyButton = modalElement.querySelector('[data-outreach-copy]');

        var LABELS = @json($outreachLabels);

        // Free slots from the block beside the list, so the draft can offer a real
        // time instead of a vague "let me know when suits you".
        var slotsBlock = document.querySelector('.day-free-row');
        var freeContext = slotsBlock
            ? {
                free_day: (slotsBlock.querySelector('.day-free-day')?.textContent || '').trim(),
                free_slots: [...slotsBlock.querySelectorAll('.day-free-slot')].map(function (el) {
                    return el.textContent.trim();
                }),
            }
            : { free_day: null, free_slots: [] };

        var current = null;

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

        function setNote(text) {
            noteField.textContent = text || '';
            noteField.hidden = !text;
        }

        function setError(text) {
            errorField.textContent = text || '';
            errorField.hidden = !text;
        }

        function updateSendLink() {
            if (!current || !current.phone) {
                sendLink.hidden = true;

                return;
            }

            var digits = current.phone.replace(/\D+/g, '');
            sendLink.hidden = false;
            sendLink.href = 'https://wa.me/' + digits + '?text=' + encodeURIComponent(textField.value);
        }

        function draft() {
            if (!current) {
                return;
            }

            setError('');
            setNote('');
            textField.value = LABELS.loading;
            textField.disabled = true;
            regenerateButton.disabled = true;

            fetch('/api/v1/clients/' + current.id + '/outreach-message', {
                method: 'POST',
                headers: headers(),
                credentials: 'include',
                body: JSON.stringify(freeContext),
            })
                .then(function (response) {
                    return response.json().catch(function () {
                        return {};
                    }).then(function (payload) {
                        return { ok: response.ok, payload: payload };
                    });
                })
                .then(function (result) {
                    if (!result.ok) {
                        textField.value = '';
                        setError(LABELS.error);

                        return;
                    }

                    var data = result.payload.data || {};
                    textField.value = data.text || '';

                    if (data.source === 'limit') {
                        setNote(LABELS.limit);
                    } else if (data.source === 'template') {
                        setNote(LABELS.template);
                    } else if (typeof data.remaining === 'number') {
                        setNote(LABELS.remaining.replace(':count', data.remaining));
                    }

                    updateSendLink();
                })
                .catch(function () {
                    textField.value = '';
                    setError(LABELS.error);
                })
                .finally(function () {
                    textField.disabled = false;
                    regenerateButton.disabled = false;
                });
        }

        document.querySelectorAll('[data-outreach-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                current = {
                    id: button.dataset.clientId,
                    name: button.dataset.clientName || '',
                    phone: button.dataset.clientPhone || '',
                };

                clientLabel.textContent = current.name;
                modal.show();
                draft();
            });
        });

        regenerateButton.addEventListener('click', draft);
        textField.addEventListener('input', updateSendLink);

        copyButton.addEventListener('click', function () {
            var done = function () {
                copyButton.textContent = LABELS.copied;
                setTimeout(function () {
                    copyButton.textContent = LABELS.copy;
                }, 1800);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(textField.value).then(done).catch(function () {
                    textField.select();
                });

                return;
            }

            // Older browsers and insecure origins have no clipboard API; selecting
            // the text at least leaves one keystroke between here and pasting.
            textField.select();
        });
    });
</script>
