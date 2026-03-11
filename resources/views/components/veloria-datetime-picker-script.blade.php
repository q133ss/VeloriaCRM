<script src="/assets/vendor/libs/flatpickr/flatpickr.js"></script>
<script>
    (function () {
        if (window.VeloriaDateTimePicker) {
            return;
        }

        const pickerInstances = new Map();
        const localeRu = {
            weekdays: {
                shorthand: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
                longhand: ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота']
            },
            months: {
                shorthand: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
                longhand: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь']
            },
            firstDayOfWeek: 1,
            rangeSeparator: ' — ',
            time_24hr: true
        };

        function parseIsoDateTime(value) {
            const normalized = String(value || '').trim();
            if (!normalized) {
                return null;
            }

            const match = normalized.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T\s](\d{2}):(\d{2}))?$/);
            if (!match) {
                const fallback = new Date(normalized);
                return Number.isNaN(fallback.getTime()) ? null : fallback;
            }

            const year = Number(match[1]);
            const month = Number(match[2]) - 1;
            const day = Number(match[3]);
            const hours = Number(match[4] || '0');
            const minutes = Number(match[5] || '0');

            return new Date(year, month, day, hours, minutes, 0, 0);
        }

        function formatIsoDateTime(date) {
            if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
                return '';
            }

            const pad = (value) => String(value).padStart(2, '0');

            return [
                date.getFullYear(),
                pad(date.getMonth() + 1),
                pad(date.getDate())
            ].join('-') + 'T' + [pad(date.getHours()), pad(date.getMinutes())].join(':');
        }

        function formatHumanDateTime(date) {
            if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
                return '';
            }

            return new Intl.DateTimeFormat('ru-RU', {
                day: '2-digit',
                month: 'long',
                hour: '2-digit',
                minute: '2-digit'
            }).format(date);
        }

        function getShortcuts(root) {
            const rawSlots = (root.dataset.timeSlots || '').split(',').map((slot) => slot.trim()).filter(Boolean);
            return rawSlots.length ? rawSlots : ['09:00', '11:00', '13:00', '15:00', '18:00'];
        }

        function dispatchInputEvents(hiddenInput) {
            hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function syncDisplay(instance) {
            const hiddenInput = instance.hiddenInput;
            const displayInput = instance.displayInput;
            const value = hiddenInput.value;
            const date = parseIsoDateTime(value);

            if (!date) {
                displayInput.value = '';
                return;
            }

            displayInput.value = formatHumanDateTime(date);
        }

        function setValue(target, value, options = {}) {
            const hiddenInput = typeof target === 'string' ? document.querySelector(target) : target;
            if (!hiddenInput) {
                return;
            }

            const instance = pickerInstances.get(hiddenInput);
            const date = parseIsoDateTime(value);
            const shouldDispatch = options.dispatch !== false;

            if (instance && instance.picker) {
                if (date) {
                    instance.picker.setDate(date, false);
                    hiddenInput.value = formatIsoDateTime(date);
                    syncDisplay(instance);
                } else {
                    instance.picker.clear(false);
                    hiddenInput.value = '';
                    syncDisplay(instance);
                }
            } else {
                hiddenInput.value = date ? formatIsoDateTime(date) : (value || '');
            }

            if (shouldDispatch) {
                dispatchInputEvents(hiddenInput);
            }
        }

        function applyDayShortcut(hiddenInput, offsetDays) {
            const current = parseIsoDateTime(hiddenInput.value) || new Date();
            const updated = new Date(current.getTime());
            updated.setDate(updated.getDate() + offsetDays);

            if (!hiddenInput.value) {
                updated.setHours(10, 0, 0, 0);
            }

            setValue(hiddenInput, formatIsoDateTime(updated));
        }

        function applyTimeShortcut(hiddenInput, timeValue) {
            const match = String(timeValue || '').match(/^(\d{2}):(\d{2})$/);
            if (!match) {
                return;
            }

            const current = parseIsoDateTime(hiddenInput.value) || new Date();
            current.setHours(Number(match[1]), Number(match[2]), 0, 0);
            setValue(hiddenInput, formatIsoDateTime(current));
        }

        function createPicker(root) {
            const hiddenInput = root.querySelector('[data-veloria-datetime-value]');
            const displayInput = root.querySelector('[data-veloria-datetime-display]');
            const openButton = root.querySelector('[data-veloria-datetime-open]');

            if (!hiddenInput || !displayInput || pickerInstances.has(hiddenInput)) {
                return;
            }

            if (typeof flatpickr !== 'function') {
                displayInput.readOnly = false;
                displayInput.value = hiddenInput.value || '';
                return;
            }

            const picker = flatpickr(displayInput, {
                enableTime: true,
                dateFormat: 'd.m.Y H:i',
                time_24hr: true,
                minuteIncrement: 5,
                allowInput: false,
                clickOpens: true,
                disableMobile: true,
                static: true,
                locale: localeRu,
                defaultDate: parseIsoDateTime(hiddenInput.value),
                onReady: function (selectedDates, dateStr, instance) {
                    syncDisplay({ hiddenInput, displayInput, picker: instance });
                },
                onChange: function (selectedDates) {
                    hiddenInput.value = selectedDates.length ? formatIsoDateTime(selectedDates[0]) : '';
                    syncDisplay({ hiddenInput, displayInput, picker });
                    dispatchInputEvents(hiddenInput);
                }
            });

            const instance = { root, hiddenInput, displayInput, picker };
            pickerInstances.set(hiddenInput, instance);

            hiddenInput.addEventListener('sync-datetime-picker', function () {
                syncDisplay(instance);
            });

            openButton?.addEventListener('click', function () {
                picker.open();
            });

            root.querySelectorAll('[data-datetime-day]').forEach((button) => {
                button.addEventListener('click', function () {
                    applyDayShortcut(hiddenInput, Number(button.dataset.datetimeDay || 0));
                });
            });

            root.querySelectorAll('[data-datetime-time]').forEach((button) => {
                button.addEventListener('click', function () {
                    applyTimeShortcut(hiddenInput, button.dataset.datetimeTime || '');
                });
            });

            if (hiddenInput.value) {
                syncDisplay(instance);
            }
        }

        function init(scope = document) {
            scope.querySelectorAll('[data-veloria-datetime-root]').forEach(createPicker);
        }

        window.VeloriaDateTimePicker = {
            init,
            setValue,
            sync(target) {
                const hiddenInput = typeof target === 'string' ? document.querySelector(target) : target;
                if (!hiddenInput) {
                    return;
                }

                const instance = pickerInstances.get(hiddenInput);
                if (instance) {
                    syncDisplay(instance);
                } else {
                    hiddenInput.dispatchEvent(new Event('sync-datetime-picker', { bubbles: true }));
                }
            },
            getShortcuts
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                init(document);
            });
        } else {
            init(document);
        }
    })();
</script>
