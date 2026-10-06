<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.__phoneMaskInitialized) {
            return;
        }
        window.__phoneMaskInitialized = true;

        function formatPhone(value) {
            let digits = (value || '').replace(/\D/g, '');

            // 8 900… and 900… both mean +7 900…: people type a Russian number the
            // way they dial it, and a mask that keeps the leading 8 stores the wrong country.
            if (digits[0] === '8') {
                digits = '7' + digits.slice(1);
            } else if (digits[0] === '9') {
                digits = '7' + digits;
            }

            digits = digits.slice(0, 11);
            if (!digits.length) {
                return '';
            }

            let formatted = '+' + digits[0];

            if (digits.length > 1) {
                formatted += '(' + digits.slice(1, Math.min(4, digits.length));
            }

            if (digits.length >= 4) {
                formatted += ')';
            }

            if (digits.length > 4) {
                const body = digits.slice(4);
                const first = body.slice(0, 3);
                const second = body.slice(3, 5);
                const third = body.slice(5, 7);

                formatted += first;

                if (second.length) {
                    formatted += '-' + second;
                }

                if (third.length) {
                    formatted += '-' + third;
                }
            }

            return formatted;
        }

        function digitsBeforePosition(value, pos) {
            return (value.slice(0, pos).match(/\d/g) || []).length;
        }

        // Literal mask characters like "(" and ")" get re-inserted on every
        // reformat even if the user just deleted them, so putting the caret
        // back at the end (the browser's default after a programmatic value
        // set) can trap backspace into deleting/restoring the same literal
        // forever. Placing it after the same digit it was after before fixes
        // that for backspace, delete, and mid-string edits alike.
        function caretPositionForDigitCount(value, count) {
            if (count <= 0) {
                const idx = value.search(/\d/);
                return idx === -1 ? value.length : idx;
            }

            let seen = 0;
            for (let i = 0; i < value.length; i++) {
                if (/\d/.test(value[i])) {
                    seen++;
                    if (seen === count) {
                        return i + 1;
                    }
                }
            }

            return value.length;
        }

        function applyMask(input) {
            const reformat = () => {
                const rawValue = input.value;
                const caret = input.selectionStart == null ? rawValue.length : input.selectionStart;
                const digitCount = digitsBeforePosition(rawValue, caret);

                input.value = formatPhone(rawValue);

                const newCaret = caretPositionForDigitCount(input.value, digitCount);
                input.setSelectionRange(newCaret, newCaret);
            };

            reformat();

            input.addEventListener('input', reformat);
            input.addEventListener('blur', reformat);
            input.addEventListener('paste', function () {
                setTimeout(reformat, 0);
            });
        }

        document.querySelectorAll('[data-phone-mask]').forEach(function (input) {
            applyMask(input);
        });
    });
</script>
