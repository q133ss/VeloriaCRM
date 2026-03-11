<script>
    document.addEventListener('DOMContentLoaded', function () {
        const config = window.LANDING_FORM_CONFIG || {};
        const form = document.getElementById('landing-form');
        if (!form) return;

        const alertsContainer = document.getElementById('landing-alerts');
        const titleInput = document.getElementById('landing-title');
        const slugInput = document.getElementById('landing-slug');
        const fullUrl = document.getElementById('landing-full-url');
        const templateInput = document.getElementById('landing-template');
        const activeInput = document.getElementById('landing-active');
        const statusIndicator = document.getElementById('landing-status-indicator');
        const previewButton = document.getElementById('landing-preview-btn');
        const requestsCard = document.getElementById('landing-requests-card');
        const requestsList = document.getElementById('landing-requests-list');
        const requestsEmpty = document.getElementById('landing-requests-empty');

        const subtitleInput = document.getElementById('landing-subtitle');
        const bookingHintInput = document.getElementById('landing-booking-hint');
        const ctaLabelInput = document.getElementById('landing-cta-label');
        const secondaryCtaLabelInput = document.getElementById('landing-secondary-cta-label');
        const phoneInput = document.getElementById('landing-phone');
        const whatsappInput = document.getElementById('landing-whatsapp');
        const telegramInput = document.getElementById('landing-telegram');
        const addressInput = document.getElementById('landing-address');
        const proofItemsInput = document.getElementById('landing-proof-items');
        const faqItemsInput = document.getElementById('landing-faq-items');

        const primaryColorSelect = document.getElementById('landing-primary-color');
        const backgroundTypeSelect = document.getElementById('landing-background-type');
        const backgroundValueInput = document.getElementById('landing-background-value');

        const greetingInput = document.getElementById('landing-greeting');
        const allServicesInput = document.getElementById('landing-all-services');
        const serviceIdsSelect = document.getElementById('landing-service-ids');
        const generalBonusInput = document.getElementById('landing-general-bonus');

        const promotionSelect = document.getElementById('landing-promotion');
        const promotionDetails = document.getElementById('landing-promotion-details');
        const promoServiceSelect = document.getElementById('landing-promo-service');
        const promotionHeadlineInput = document.getElementById('landing-headline');
        const promotionDescriptionInput = document.getElementById('landing-description');
        const discountInput = document.getElementById('landing-discount');
        const promoCodeInput = document.getElementById('landing-promo-code');
        const promoEndsAtInput = document.getElementById('landing-ends-at');
        const promoBonusInput = document.getElementById('landing-promo-bonus');

        const serviceSelect = document.getElementById('landing-service');
        const serviceDescriptionInput = document.getElementById('landing-service-description');
        const priceFromInput = document.getElementById('landing-price-from');
        const durationLabelInput = document.getElementById('landing-duration-label');
        const benefitItemsInput = document.getElementById('landing-benefit-items');

        const seasonLabelInput = document.getElementById('landing-season-label');
        const seasonEndsAtInput = document.getElementById('landing-season-ends-at');
        const seasonBonusInput = document.getElementById('landing-season-bonus');
        const seasonHeadlineInput = document.getElementById('landing-season-headline');
        const seasonDescriptionInput = document.getElementById('landing-season-description');
        const seasonServiceIdsSelect = document.getElementById('landing-season-service-ids');

        const consultationServiceSelect = document.getElementById('landing-consultation-service');
        const leadMagnetInput = document.getElementById('landing-lead-magnet');
        const consultationHeadlineInput = document.getElementById('landing-consultation-headline');
        const consultationDescriptionInput = document.getElementById('landing-consultation-description');
        const consultationBenefitsInput = document.getElementById('landing-consultation-benefits');

        const typeRadios = Array.from(document.querySelectorAll('input[name="type"]'));
        const scenarioCards = Array.from(document.querySelectorAll('[data-scenario-option]'));
        const typeSections = document.querySelectorAll('[data-type-section]');

        const appUrl = config.appUrl || window.location.origin;
        const translations = config.translations || {};
        const statusLabels = config.statusLabels || {};
        const defaultTemplates = config.defaultTemplate || {};
        const isEdit = config.mode === 'edit';
        const landingId = config.landingId;

        let slugAuto = true;
        const state = {
            services: [],
            promotions: [],
            landing: null
        };

        function getCookie(name) {
            const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
            return match ? decodeURIComponent(match[1]) : null;
        }

        function authHeaders() {
            const token = getCookie('token');
            const headers = { Accept: 'application/json', 'Content-Type': 'application/json' };
            if (token) headers.Authorization = 'Bearer ' + token;
            return headers;
        }

        function showAlert(type, message) {
            if (!alertsContainer) return;
            const alert = document.createElement('div');
            alert.className = 'alert alert-' + type + ' alert-dismissible fade show';
            alert.role = 'alert';
            alert.innerHTML = `
                <div>${message}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            alertsContainer.appendChild(alert);
        }

        function clearAlerts() {
            if (alertsContainer) {
                alertsContainer.innerHTML = '';
            }
        }

        function slugify(value) {
            return value
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)+/g, '')
                .replace(/-{2,}/g, '-');
        }

        function selectedType() {
            return typeRadios.find(function (radio) { return radio.checked; })?.value || 'general';
        }

        function selectedTypeInput() {
            return typeRadios.find(function (radio) { return radio.checked; }) || typeRadios[0];
        }

        function updateFullUrl() {
            if (!fullUrl) return;
            const slug = slugInput.value.trim();
            const base = appUrl.replace(/\/$/, '');
            fullUrl.textContent = slug ? base + '/l/' + slug : base + '/l/{slug}';
        }

        function updateStatusIndicator(isActive) {
            if (!statusIndicator) return;
            statusIndicator.textContent = isActive ? (statusLabels.active || 'Active') : (statusLabels.inactive || 'Inactive');
            statusIndicator.className = isActive ? 'badge bg-label-success' : 'badge bg-label-secondary';
        }

        function syncScenarioCards() {
            scenarioCards.forEach(function (card) {
                const input = card.querySelector('input[name="type"]');
                card.classList.toggle('is-selected', Boolean(input && input.checked));
            });
        }

        function toggleSections(type) {
            typeSections.forEach(function (section) {
                section.classList.toggle('d-none', section.getAttribute('data-type-section') !== type);
            });
        }

        function setTemplateForType(type) {
            const input = typeRadios.find(function (radio) { return radio.value === type; });
            templateInput.value = input?.dataset.template || defaultTemplates[type] || ('landings.templates.' + type);
        }

        function updateTemplateAndSelection(type) {
            toggleSections(type);
            setTemplateForType(type);
            syncScenarioCards();
        }

        function buildServiceOption(service) {
            return `<option value="${service.id}">${service.name}</option>`;
        }

        function populateSingleSelect(select) {
            if (!select) return;
            select.innerHTML = `<option value="">${'{{ __('landings.form.placeholders.service') }}'}</option>${state.services.map(buildServiceOption).join('')}`;
        }

        function populateMultiSelect(select) {
            if (!select) return;
            select.innerHTML = state.services.map(buildServiceOption).join('');
        }

        function populateServiceSelects() {
            populateMultiSelect(serviceIdsSelect);
            populateMultiSelect(seasonServiceIdsSelect);
            populateSingleSelect(serviceSelect);
            populateSingleSelect(promoServiceSelect);
            populateSingleSelect(consultationServiceSelect);
        }

        function populatePromotions() {
            if (!promotionSelect) return;
            promotionSelect.innerHTML = `<option value="">${'{{ __('landings.form.placeholders.promotion') }}'}</option>`;

            state.promotions.forEach(function (promotion) {
                const option = document.createElement('option');
                option.value = promotion.id;
                option.textContent = promotion.name;
                option.dataset.promoCode = promotion.promo_code || '';
                promotionSelect.appendChild(option);
            });
        }

        function setMultiSelected(select, values) {
            if (!select) return;
            const normalized = (values || []).map(function (value) { return Number(value); });
            Array.from(select.options).forEach(function (option) {
                option.selected = normalized.includes(Number(option.value));
            });
        }

        function setSingleSelected(select, value) {
            if (!select) return;
            Array.from(select.options).forEach(function (option) {
                option.selected = Number(option.value) === Number(value);
            });
        }

        function updatePromotionDetails() {
            if (!promotionDetails || !promotionSelect) return;
            const selected = promotionSelect.selectedOptions[0];
            if (selected && selected.value) {
                promotionDetails.textContent = selected.dataset.promoCode
                    ? '{{ __('landings.form.helpers.promo_code_prefix') }} ' + selected.dataset.promoCode
                    : '';
            } else {
                promotionDetails.textContent = '';
            }
        }

        function disableServiceSelectIfNeeded() {
            if (!allServicesInput || !serviceIdsSelect) return;
            serviceIdsSelect.disabled = allServicesInput.checked;
        }

        function renderRequests(items) {
            if (!requestsCard || !requestsList || !requestsEmpty) return;

            if (!isEdit && (!items || !items.length)) {
                requestsCard.classList.add('d-none');
                requestsList.innerHTML = '';
                requestsEmpty.classList.add('d-none');
                return;
            }

            if (!items || !items.length) {
                requestsCard.classList.remove('d-none');
                requestsList.innerHTML = '';
                requestsEmpty.classList.remove('d-none');
                return;
            }

            requestsCard.classList.remove('d-none');
            requestsEmpty.classList.add('d-none');
            requestsList.innerHTML = items.map(function (item) {
                const serviceLine = item.service_name ? `<div class="small text-muted">${item.service_name}</div>` : '';
                const contactLine = [item.client_phone, item.client_email].filter(Boolean).join(' · ');
                const preferredDate = item.preferred_date ? `<div class="small text-muted">{{ __('landings.form.fields.preferred_date') }}: ${item.preferred_date}</div>` : '';
                const message = item.message ? `<div class="mt-2">${item.message}</div>` : '';
                return `
                    <div class="landing-request-item">
                        <strong>${item.client_name}</strong>
                        <div class="small">${contactLine}</div>
                        ${serviceLine}
                        ${preferredDate}
                        ${message}
                    </div>
                `;
            }).join('');
        }

        function serviceMetaById(id) {
            return state.services.find(function (service) {
                return Number(service.id) === Number(id);
            }) || null;
        }

        function maybeFillServiceMeta(select) {
            const selected = select?.selectedOptions?.[0];
            if (!selected || !selected.value) return;

            const meta = serviceMetaById(selected.value);
            if (!meta) return;

            if (priceFromInput && !priceFromInput.value && meta.price) {
                priceFromInput.value = String(meta.price);
            }

            if (durationLabelInput && !durationLabelInput.value && meta.duration) {
                durationLabelInput.value = meta.duration + ' мин';
            }
        }

        function renderLandingData(landing) {
            if (!landing) return;

            titleInput.value = landing.title || '';
            slugInput.value = landing.slug || '';
            templateInput.value = landing.landing || '';
            primaryColorSelect.value = landing.settings?.primary_color || 'indigo';
            backgroundTypeSelect.value = landing.settings?.background_type || 'preset';
            backgroundValueInput.value = landing.settings?.background_value || '';
            subtitleInput.value = landing.settings?.subtitle || '';
            bookingHintInput.value = landing.settings?.booking_hint || '';
            ctaLabelInput.value = landing.settings?.cta_label || '';
            secondaryCtaLabelInput.value = landing.settings?.secondary_cta_label || '';
            phoneInput.value = landing.settings?.phone || '';
            whatsappInput.value = landing.settings?.whatsapp_url || '';
            telegramInput.value = landing.settings?.telegram_url || '';
            addressInput.value = landing.settings?.address || '';
            proofItemsInput.value = landing.settings?.proof_items_text || '';
            faqItemsInput.value = landing.settings?.faq_items_text || '';

            greetingInput.value = landing.settings?.greeting || '';
            allServicesInput.checked = Boolean(landing.settings?.show_all_services);
            generalBonusInput.value = landing.settings?.bonus_text || '';
            setMultiSelected(serviceIdsSelect, landing.settings?.service_ids || []);

            promotionSelect.value = landing.settings?.promotion_id || '';
            updatePromotionDetails();
            setSingleSelected(promoServiceSelect, landing.settings?.service_id || null);
            promotionHeadlineInput.value = landing.settings?.headline || '';
            promotionDescriptionInput.value = landing.settings?.description || '';
            discountInput.value = landing.settings?.discount_percent ?? '';
            promoCodeInput.value = landing.settings?.promo_code || '';
            promoEndsAtInput.value = landing.settings?.ends_at || '';
            promoBonusInput.value = landing.settings?.bonus_text || '';

            setSingleSelected(serviceSelect, landing.settings?.service_id || null);
            serviceDescriptionInput.value = landing.settings?.service_description || '';
            priceFromInput.value = landing.settings?.price_from || '';
            durationLabelInput.value = landing.settings?.duration_label || '';
            benefitItemsInput.value = landing.settings?.benefit_items_text || '';

            seasonLabelInput.value = landing.settings?.season_label || '';
            seasonEndsAtInput.value = landing.settings?.ends_at || '';
            seasonBonusInput.value = landing.settings?.bonus_text || '';
            seasonHeadlineInput.value = landing.settings?.headline || '';
            seasonDescriptionInput.value = landing.settings?.description || '';
            setMultiSelected(seasonServiceIdsSelect, landing.settings?.service_ids || []);

            setSingleSelected(consultationServiceSelect, landing.settings?.service_id || null);
            leadMagnetInput.value = landing.settings?.lead_magnet || '';
            consultationHeadlineInput.value = landing.settings?.headline || '';
            consultationDescriptionInput.value = landing.settings?.description || '';
            consultationBenefitsInput.value = landing.settings?.benefit_items_text || '';

            typeRadios.forEach(function (radio) {
                radio.checked = radio.value === (landing.type || 'general');
            });
            updateTemplateAndSelection(landing.type || 'general');
            updateStatusIndicator(Boolean(landing.is_active));
            activeInput.checked = Boolean(landing.is_active);
            slugAuto = false;
            updateFullUrl();
            disableServiceSelectIfNeeded();
            renderRequests(landing.recent_requests || []);

            if (previewButton && slugInput.value.trim()) {
                previewButton.classList.remove('d-none');
            }
        }

        function collectCommonSettings() {
            return {
                primary_color: primaryColorSelect.value,
                background_type: backgroundTypeSelect.value,
                background_value: backgroundValueInput.value || null,
                subtitle: subtitleInput.value || '',
                booking_hint: bookingHintInput.value || '',
                cta_label: ctaLabelInput.value || '',
                secondary_cta_label: secondaryCtaLabelInput.value || '',
                phone: phoneInput.value || '',
                whatsapp_url: whatsappInput.value || '',
                telegram_url: telegramInput.value || '',
                address: addressInput.value || '',
                proof_items_text: proofItemsInput.value || '',
                faq_items_text: faqItemsInput.value || '',
            };
        }

        function collectGeneralSettings(settings) {
            const selectedOptions = Array.from(serviceIdsSelect.selectedOptions);
            settings.greeting = greetingInput.value || '';
            settings.show_all_services = allServicesInput.checked;
            settings.bonus_text = generalBonusInput.value || '';
            settings.service_ids = allServicesInput.checked ? [] : selectedOptions.map(function (option) { return Number(option.value); });
            settings.service_names = allServicesInput.checked ? [] : selectedOptions.map(function (option) { return option.textContent; });
        }

        function collectPromotionSettings(settings) {
            const selectedPromotion = promotionSelect.selectedOptions[0];
            const selectedService = promoServiceSelect.selectedOptions[0];
            settings.promotion_id = selectedPromotion && selectedPromotion.value ? Number(selectedPromotion.value) : null;
            settings.promotion_name = selectedPromotion && selectedPromotion.value ? selectedPromotion.textContent : null;
            settings.service_id = selectedService && selectedService.value ? Number(selectedService.value) : null;
            settings.service_name = selectedService && selectedService.value ? selectedService.textContent : null;
            settings.headline = promotionHeadlineInput.value || '';
            settings.description = promotionDescriptionInput.value || '';
            settings.discount_percent = discountInput.value ? Number(discountInput.value) : null;
            settings.promo_code = promoCodeInput.value || '';
            settings.ends_at = promoEndsAtInput.value || null;
            settings.bonus_text = promoBonusInput.value || '';
        }

        function collectServiceSettings(settings) {
            const selectedService = serviceSelect.selectedOptions[0];
            settings.service_id = selectedService && selectedService.value ? Number(selectedService.value) : null;
            settings.service_name = selectedService && selectedService.value ? selectedService.textContent : null;
            settings.service_description = serviceDescriptionInput.value || '';
            settings.price_from = priceFromInput.value || '';
            settings.duration_label = durationLabelInput.value || '';
            settings.benefit_items_text = benefitItemsInput.value || '';
        }

        function collectSeasonalSettings(settings) {
            const selectedOptions = Array.from(seasonServiceIdsSelect.selectedOptions);
            settings.season_label = seasonLabelInput.value || '';
            settings.ends_at = seasonEndsAtInput.value || null;
            settings.bonus_text = seasonBonusInput.value || '';
            settings.headline = seasonHeadlineInput.value || '';
            settings.description = seasonDescriptionInput.value || '';
            settings.service_ids = selectedOptions.map(function (option) { return Number(option.value); });
            settings.service_names = selectedOptions.map(function (option) { return option.textContent; });
        }

        function collectConsultationSettings(settings) {
            const selectedService = consultationServiceSelect.selectedOptions[0];
            settings.service_id = selectedService && selectedService.value ? Number(selectedService.value) : null;
            settings.service_name = selectedService && selectedService.value ? selectedService.textContent : null;
            settings.headline = consultationHeadlineInput.value || '';
            settings.description = consultationDescriptionInput.value || '';
            settings.lead_magnet = leadMagnetInput.value || '';
            settings.benefit_items_text = consultationBenefitsInput.value || '';
        }

        function collectPayload() {
            const type = selectedType();
            const payload = {
                title: titleInput.value.trim(),
                type: type,
                landing: templateInput.value || defaultTemplates[type] || null,
                is_active: activeInput.checked,
                settings: collectCommonSettings()
            };

            const slugValue = slugInput.value.trim();
            if (!isEdit) {
                payload.slug = slugValue || null;
            } else if (!state.landing || slugValue !== state.landing.slug) {
                payload.slug = slugValue || null;
            }

            if (type === 'general') collectGeneralSettings(payload.settings);
            if (type === 'promotion') collectPromotionSettings(payload.settings);
            if (type === 'service') collectServiceSettings(payload.settings);
            if (type === 'seasonal') collectSeasonalSettings(payload.settings);
            if (type === 'consultation') collectConsultationSettings(payload.settings);

            return payload;
        }

        function fetchOptions() {
            return fetch('/api/v1/landings/options', { headers: authHeaders() })
                .then(function (response) {
                    if (!response.ok) throw new Error('Failed');
                    return response.json();
                })
                .then(function (data) {
                    state.services = data.data?.services || [];
                    state.promotions = data.data?.promotions || [];
                    populateServiceSelects();
                    populatePromotions();
                })
                .catch(function () {
                    showAlert('danger', translations.options_failed || '{{ __('landings.notifications.options_failed') }}');
                });
        }

        function fetchLanding() {
            if (!isEdit || !landingId) {
                return Promise.resolve();
            }

            return fetch('/api/v1/landings/' + landingId, { headers: authHeaders() })
                .then(function (response) {
                    if (!response.ok) throw new Error('Failed');
                    return response.json();
                })
                .then(function (data) {
                    state.landing = data.data;
                    renderLandingData(state.landing);
                })
                .catch(function () {
                    showAlert('danger', translations.load_failed || '{{ __('landings.notifications.load_failed') }}');
                });
        }

        function handleSubmit(event) {
            event.preventDefault();
            clearAlerts();

            const payload = collectPayload();
            const url = isEdit ? '/api/v1/landings/' + landingId : '/api/v1/landings';
            const method = isEdit ? 'PATCH' : 'POST';

            fetch(url, {
                method: method,
                headers: authHeaders(),
                body: JSON.stringify(payload)
            })
                .then(function (response) {
                    if (response.status === 422) {
                        return response.json().then(function (data) { throw data; });
                    }
                    if (!response.ok) throw new Error('Failed');
                    return response.json();
                })
                .then(function (data) {
                    showAlert('success', data.message || translations.saved || '{{ __('landings.notifications.saved') }}');
                    if (!isEdit && data.data?.id) {
                        window.location.href = '/landings/' + data.data.id + '/edit';
                        return;
                    }
                    state.landing = data.data;
                    renderLandingData(state.landing);
                })
                .catch(function (error) {
                    const messages = error?.error?.fields
                        ? Object.values(error.error.fields).flat().join('<br>')
                        : (translations.save_failed || '{{ __('landings.notifications.save_failed') }}');
                    showAlert('danger', messages);
                });
        }

        function handleTitleInput() {
            if (!slugAuto) return;
            slugInput.value = slugify(titleInput.value);
            updateFullUrl();
        }

        function handleSlugInput() {
            slugAuto = false;
            slugInput.value = slugify(slugInput.value);
            updateFullUrl();
            if (previewButton) {
                previewButton.classList.toggle('d-none', !slugInput.value.trim());
            }
        }

        function handleTypeChange(event) {
            updateTemplateAndSelection(event.target.value);
        }

        function openPreview() {
            const slug = slugInput.value.trim();
            if (!slug) return;
            window.open(appUrl.replace(/\/$/, '') + '/l/' + slug + '?preview=1', '_blank');
        }

        function initListeners() {
            form.addEventListener('submit', handleSubmit);
            titleInput.addEventListener('input', handleTitleInput);
            slugInput.addEventListener('input', handleSlugInput);
            activeInput.addEventListener('change', function () {
                updateStatusIndicator(activeInput.checked);
            });
            allServicesInput.addEventListener('change', disableServiceSelectIfNeeded);
            promotionSelect.addEventListener('change', function () {
                updatePromotionDetails();
                const selected = promotionSelect.selectedOptions[0];
                if (selected && selected.dataset.promoCode && !promoCodeInput.value) {
                    promoCodeInput.value = selected.dataset.promoCode;
                }
            });
            typeRadios.forEach(function (radio) {
                radio.addEventListener('change', handleTypeChange);
            });
            if (previewButton) {
                previewButton.addEventListener('click', openPreview);
            }
            [serviceSelect, promoServiceSelect, consultationServiceSelect].forEach(function (select) {
                if (select) {
                    select.addEventListener('change', function () {
                        maybeFillServiceMeta(select);
                    });
                }
            });
        }

        Promise.resolve()
            .then(fetchOptions)
            .then(fetchLanding)
            .then(function () {
                if (!isEdit) {
                    updateTemplateAndSelection('general');
                    updateStatusIndicator(true);
                    updateFullUrl();
                    disableServiceSelectIfNeeded();
                    renderRequests([]);
                }
                initListeners();
            });
    });
</script>
