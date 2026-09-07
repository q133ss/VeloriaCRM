@php
    $messageSheetLabels = [
        'loading' => __('dashboard.outreach.loading'),
        'error' => __('dashboard.outreach.error'),
        'copied' => __('dashboard.outreach.copied'),
        'copy' => __('dashboard.outreach.copy'),
        'template' => __('dashboard.outreach.template_note'),
        'limit' => __('dashboard.outreach.limit_note'),
        'remaining' => __('dashboard.outreach.remaining_note'),
        'send' => __('dashboard.outreach.send'),
        'sending' => __('dashboard.outreach.sending'),
    ];
@endphp

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var sheetEl = document.getElementById('messageSheet');

        if (!sheetEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return;
        }

        var modal = new bootstrap.Modal(sheetEl);
        var textField = sheetEl.querySelector('[data-message-text]');
        var clientLabel = sheetEl.querySelector('[data-message-client]');
        var noteField = sheetEl.querySelector('[data-message-note]');
        var errorField = sheetEl.querySelector('[data-message-error]');
        var regenerateButton = sheetEl.querySelector('[data-message-regenerate]');
        var copyButton = sheetEl.querySelector('[data-message-copy]');
        var sendButton = sheetEl.querySelector('[data-message-send]');
        var whatsappLink = sheetEl.querySelector('[data-message-whatsapp]');

        var LABELS = @json($messageSheetLabels);
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

        // WhatsApp deep link is the last resort: it needs no configuration and
        // hands the message to an app the master already has open.
        function updateWhatsappLink() {
            var usable = current && current.phone && !(current.channels || []).length;

            whatsappLink.hidden = !usable;

            if (usable) {
                whatsappLink.href = 'https://wa.me/' + current.phone.replace(/\D+/g, '')
                    + '?text=' + encodeURIComponent(textField.value);
            }
        }

        function updateSendButton() {
            var channels = (current && current.channels) || [];
            sendButton.hidden = channels.length === 0;

            if (!sendButton.hidden) {
                sendButton.textContent = LABELS.send.replace(':channel', channelLabel(channels[0]));
                sendButton.disabled = false;
            }
        }

        function channelLabel(channel) {
            return {
                telegram: 'Telegram',
                whatsapp: 'WhatsApp',
                sms: 'SMS',
                email: 'Email',
            }[channel] || channel;
        }

        function draft() {
            if (!current || !current.draft) {
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
                body: JSON.stringify(current.draft),
            })
                .then(function (response) {
                    return response.json().catch(function () { return {}; }).then(function (payload) {
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

                    updateWhatsappLink();
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

        function open(options) {
            current = {
                id: options.clientId,
                name: options.clientName || '',
                phone: options.clientPhone || '',
                channels: options.channels || [],
                draft: options.draft || null,
            };

            clientLabel.textContent = current.name;
            regenerateButton.hidden = !current.draft;
            setNote('');
            setError('');
            updateSendButton();

            // A ready text goes straight in; only a draft costs a request. With
            // neither, the box is cleared rather than left holding whatever was
            // written to the last person it was opened for.
            if (options.text || !current.draft) {
                textField.value = options.text || '';
                updateWhatsappLink();
                modal.show();

                return;
            }

            modal.show();
            draft();
        }

        // Delegated, because calendar buttons are rendered long after this runs.
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-outreach-open]');

            if (!trigger) {
                return;
            }

            open({
                clientId: trigger.dataset.clientId,
                clientName: trigger.dataset.clientName,
                clientPhone: trigger.dataset.clientPhone,
                draft: {
                    free_day: trigger.dataset.freeDay || null,
                    free_slots: trigger.dataset.freeSlots ? trigger.dataset.freeSlots.split(',') : [],
                },
            });
        });

        regenerateButton.addEventListener('click', draft);
        textField.addEventListener('input', updateWhatsappLink);

        sendButton.addEventListener('click', function () {
            if (!current || !textField.value.trim()) {
                return;
            }

            // One press, one message: this reaches a real person and cannot be undone.
            sendButton.disabled = true;
            sendButton.textContent = LABELS.sending;
            setError('');

            fetch('/api/v1/clients/' + current.id + '/message', {
                method: 'POST',
                headers: headers(),
                credentials: 'include',
                body: JSON.stringify({ text: textField.value }),
            })
                .then(function (response) {
                    return response.json().catch(function () { return {}; }).then(function (payload) {
                        return { ok: response.ok, payload: payload };
                    });
                })
                .then(function (result) {
                    if (!result.ok) {
                        setError((result.payload.error && result.payload.error.message) || LABELS.error);
                        updateSendButton();

                        return;
                    }

                    setNote(result.payload.message || '');
                    sendButton.textContent = result.payload.message || LABELS.send;
                })
                .catch(function () {
                    setError(LABELS.error);
                    updateSendButton();
                });
        });

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

        window.veloriaMessage = { open: open };
    });
</script>
