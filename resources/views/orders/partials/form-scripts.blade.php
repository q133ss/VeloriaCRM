<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.service-checkbox');
        const summaryPrice = document.getElementById('summary-price');
        const summaryDuration = document.getElementById('summary-duration');
        const totalPriceInput = document.getElementById('total_price');
        const recommendationButtons = document.querySelectorAll('.js-recommendation');

        function formatCurrency(value) {
            try {
                return new Intl.NumberFormat('ru-RU', {
                    style: 'currency',
                    currency: 'RUB',
                    minimumFractionDigits: 2,
                }).format(value);
            } catch (e) {
                return value.toFixed(2) + ' ₽';
            }
        }

        function recalcSummary() {
            let price = 0;
            let duration = 0;

            checkboxes.forEach(function (checkbox) {
                if (checkbox.checked) {
                    price += parseFloat(checkbox.dataset.price || '0');
                    duration += parseInt(checkbox.dataset.duration || '0', 10);
                }
            });

            if (summaryPrice) {
                summaryPrice.textContent = formatCurrency(price);
            }

            if (summaryDuration) {
                summaryDuration.textContent = duration + ' мин';
            }

            if (totalPriceInput && totalPriceInput.dataset.manual !== 'true') {
                totalPriceInput.value = price ? price.toFixed(2) : '';
            }
        }

        if (totalPriceInput) {
            totalPriceInput.addEventListener('input', function () {
                totalPriceInput.dataset.manual = 'true';
            });
        }

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                recalcSummary();
            });
        });

        recommendationButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const serviceId = this.dataset.serviceId;
                if (!serviceId) return;

                const targetCheckbox = document.getElementById('service-' + serviceId);
                if (!targetCheckbox) return;

                if (!targetCheckbox.checked) {
                    targetCheckbox.checked = true;
                    targetCheckbox.dispatchEvent(new Event('change'));
                }

                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-success');
                this.innerHTML = '<i class="ri ri-check-line me-1"></i>Добавлено';
                this.disabled = true;
            });
        });

        recalcSummary();
    });
</script>
