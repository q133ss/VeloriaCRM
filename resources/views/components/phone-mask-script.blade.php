<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.__phoneMaskInitialized) {
            return;
        }
        window.__phoneMaskInitialized = true;

        function formatPhone(value) {
            const digits = (value || '').replace(/\D/g, '').slice(0, 11);
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

        function applyMask(input) {
            const updateValue = () => {
                input.value = formatPhone(input.value);
            };

            updateValue();

            input.addEventListener('input', updateValue);
            input.addEventListener('blur', updateValue);
            input.addEventListener('paste', function () {
                setTimeout(updateValue, 0);
            });
        }

        document.querySelectorAll('[data-phone-mask]').forEach(function (input) {
            applyMask(input);
        });
    });
</script>
