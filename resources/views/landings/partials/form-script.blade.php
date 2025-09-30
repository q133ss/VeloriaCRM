<script>
    document.addEventListener('DOMContentLoaded', function () {
        const config = window.LANDING_FORM_CONFIG || {};
        const form = document.getElementById('landing-form');
        if (!form) return;

        const alertsContainer = document.getElementById('landing-alerts');
        const titleInput = document.getElementById('landing-title');
        const slugInput = document.getElementById('landing-slug');
        const slugPrefix = document.getElementById('landing-url-prefix');
        const fullUrl = document.getElementById('landing-full-url');
        const templateInput = document.getElementById('landing-template');
        const activeInput = document.getElementById('landing-active');
        const statusIndicator = document.getElementById('landing-status-indicator');
        const previewButton = document.getElementById('landing-preview-btn');
        const greetingInput = document.getElementById('landing-greeting');
        const allServicesInput = document.getElementById('landing-all-services');
        const serviceIdsSelect = document.getElementById('landing-service-ids');
        const serviceSelect = document.getElementById('landing-service');
        const serviceDescription = document.getElementById('landing-service-description');
        const promotionSelect = document.getElementById('landing-promotion');
        const promotionDetails = document.getElementById('landing-promotion-details');
        const headlineInput = document.getElementById('landing-headline');
        const descriptionInput = document.getElementById('landing-description');
        const discountInput = document.getElementById('landing-discount');
        const promoCodeInput = document.getElementById('landing-promo-code');
        const endsAtInput = document.getElementById('landing-ends-at');
        const primaryColorSelect = document.getElementById('landing-primary-color');
        const backgroundTypeSelect = document.getElementById('landing-background-type');
        const backgroundValueInput = document.getElementById('landing-background-value');
        const typeRadios = document.querySelectorAll('input[name="type"]');
        const typeSections = document.querySelectorAll('[data-type-section]');

        const appUrl = config.appUrl || window.location.origin;
        const translations = config.translations || {};
        const typeLabels = config.typeLabels || {};
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
            const headers = { 'Accept': 'application/json', 'Content-Type': 'application/json' };
            if (token) headers['Authorization'] = 'Bearer ' + token;
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

        function toggleSections(type) {
            typeSections.forEach(function (section) {
                const sectionType = section.getAttribute('data-type-section');
                if (sectionType === type) {
                    section.classList.remove('d-none');
                } else if (sectionType) {
                    section.classList.add('d-none');
                }
            });
        }

        function populateServiceSelects(services) {
            if (!serviceIdsSelect || !serviceSelect) return;
            serviceIdsSelect.innerHTML = '';
            serviceSelect.innerHTML = `<option value="">${'{{ __('landings.form.placeholders.service') }}'}</option>`;

            services.forEach(function (service) {
                const optionMultiple = document.createElement('option');
                optionMultiple.value = service.id;
                optionMultiple.textContent = service.name;
                serviceIdsSelect.appendChild(optionMultiple);

                const optionSingle = document.createElement('option');
                optionSingle.value = service.id;
                optionSingle.textContent = service.name;
                serviceSelect.appendChild(optionSingle);
            });
        }

        function populatePromotions(promotions) {
            if (!promotionSelect) return;
            promotionSelect.innerHTML = `<option value="">${'{{ __('landings.form.placeholders.promotion') }}'}</option>`;

            promotions.forEach(function (promotion) {
                const option = document.createElement('option');
                option.value = promotion.id;
                option.textContent = promotion.name;
                option.dataset.promoCode = promotion.promo_code || '';
                promotionSelect.appendChild(option);
            });
        }

        function updatePromotionDetails() {
            if (!promotionDetails) return;
            const selected = promotionSelect ? promotionSelect.selectedOptions[0] : null;
            if (selected && selected.value) {
                const promoCode = selected.dataset.promoCode;
                promotionDetails.textContent = promoCode ? '{{ __('landings.form.helpers.promo_code_prefix') }}' + ' ' + promoCode : '';
            } else {
                promotionDetails.textContent = '';
            }
        }

        function setTemplateForType(type) {
            if (!templateInput) return;
            const template = defaultTemplates[type] || templateInput.value || 'landings.templates.' + type;
            if (!templateInput.value || templateInput.value.startsWith('landings.templates.')) {
                templateInput.value = template;
            }
        }

        function disableServiceSelectIfNeeded() {
            if (!allServicesInput || !serviceIdsSelect) return;
            const disabled = allServicesInput.checked;
            serviceIdsSelect.disabled = disabled;
        }

        function renderLandingData(landing) {
            if (!landing) return;
            titleInput.value = landing.title || '';
            slugInput.value = landing.slug || '';
            templateInput.value = landing.landing || '';
            primaryColorSelect.value = landing.settings?.primary_color || 'indigo';
            backgroundTypeSelect.value = landing.settings?.background_type || 'preset';
            backgroundValueInput.value = landing.settings?.background_value || '';
            greetingInput.value = landing.settings?.greeting || '';
            const showAll = Boolean(landing.settings?.show_all_services);
            allServicesInput.checked = showAll;
            disableServiceSelectIfNeeded();

            if (serviceIdsSelect && Array.isArray(landing.settings?.service_ids)) {
                Array.from(serviceIdsSelect.options).forEach(function (option) {
                    option.selected = landing.settings.service_ids.includes(Number(option.value));
                });
            }

            if (serviceSelect && landing.settings?.service_id) {
                Array.from(serviceSelect.options).forEach(function (option) {
                    option.selected = Number(option.value) === Number(landing.settings.service_id);
                });
            }

            if (serviceDescription) {
                serviceDescription.value = landing.settings?.service_description || '';
            }
            if (promotionSelect) {
                promotionSelect.value = landing.settings?.promotion_id || '';
            }
            updatePromotionDetails();
            if (headlineInput) {
                headlineInput.value = landing.settings?.headline || '';
            }
            if (descriptionInput) {
                descriptionInput.value = landing.settings?.description || '';
            }
            if (discountInput) {
                discountInput.value = landing.settings?.discount_percent ?? '';
            }
            if (promoCodeInput) {
                promoCodeInput.value = landing.settings?.promo_code || '';
            }
            if (endsAtInput) {
                endsAtInput.value = landing.settings?.ends_at || '';
            }

            const type = landing.type || 'general';
            typeRadios.forEach(function (radio) {
                radio.checked = radio.value === type;
            });
            toggleSections(type);
            updateFullUrl();
            updateStatusIndicator(Boolean(landing.is_active));
            activeInput.checked = Boolean(landing.is_active);
            slugAuto = false;
            if (isEdit && previewButton) {
                previewButton.classList.remove('d-none');
            }
        }

        function collectGeneralSettings(settings) {
            settings.greeting = greetingInput.value || '';
            settings.show_all_services = allServicesInput.checked;
            if (!allServicesInput.checked) {
                const selectedOptions = Array.from(serviceIdsSelect.selectedOptions);
                settings.service_ids = selectedOptions.map(function (option) { return Number(option.value); });
                settings.service_names = selectedOptions.map(function (option) { return option.textContent; });
            } else {
                settings.service_ids = [];
                settings.service_names = [];
            }
        }

        function collectPromotionSettings(settings) {
            const selected = promotionSelect ? promotionSelect.selectedOptions[0] : null;
            settings.promotion_id = selected && selected.value ? Number(selected.value) : null;
            settings.promotion_name = selected && selected.value ? selected.textContent : null;
            settings.headline = headlineInput.value || '';
            settings.description = descriptionInput.value || '';
            settings.discount_percent = discountInput.value ? Number(discountInput.value) : null;
            settings.promo_code = promoCodeInput.value || '';
            settings.ends_at = endsAtInput.value || null;
        }

        function collectServiceSettings(settings) {
            const selected = serviceSelect ? serviceSelect.selectedOptions[0] : null;
            settings.service_id = selected && selected.value ? Number(selected.value) : null;
            settings.service_name = selected && selected.value ? selected.textContent : null;
            settings.service_description = serviceDescription.value || '';
        }

        function collectPayload() {
            const type = Array.from(typeRadios).find(function (radio) { return radio.checked; })?.value || 'general';
            const payload = {
                title: titleInput.value.trim(),
                type: type,
                is_active: activeInput.checked,
                settings: {
                    primary_color: primaryColorSelect.value,
                    background_type: backgroundTypeSelect.value,
                    background_value: backgroundValueInput.value || null,
                }
            };

            const slugValue = slugInput.value.trim();
            if (!isEdit) {
                payload.slug = slugValue || null;
            } else if (slugValue && (!state.landing || slugValue !== state.landing.slug)) {
                payload.slug = slugValue;
            } else if (!slugValue && state.landing && state.landing.slug) {
                payload.slug = null;
            }

            const templateValue = templateInput.value.trim();
            if (!isEdit) {
                payload.landing = templateValue || null;
            } else if (!state.landing || templateValue !== state.landing.landing) {
                payload.landing = templateValue || null;
            }

            if (type === 'general') {
                collectGeneralSettings(payload.settings);
            }

            if (type === 'promotion') {
                collectPromotionSettings(payload.settings);
            }

            if (type === 'service') {
                collectServiceSettings(payload.settings);
            }

            return payload;
        }

        function fetchOptions() {
            return fetch('/api/v1/landings/options', { headers: authHeaders() })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Failed');
                    }
                    return response.json();
                })
                .then(function (data) {
                    state.services = data.data?.services || [];
                    state.promotions = data.data?.promotions || [];
                    populateServiceSelects(state.services);
                    populatePromotions(state.promotions);
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
                    if (!response.ok) {
                        throw new Error('Failed');
                    }
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
                        return response.json().then(function (data) {
                            throw data;
                        });
                    }
                    if (!response.ok) {
                        throw new Error('Failed');
                    }
                    return response.json();
                })
                .then(function (data) {
                    showAlert('success', data.message || translations.saved || '{{ __('landings.notifications.saved') }}');
                    if (!isEdit && data.data && data.data.id) {
                        window.location.href = '/landings/' + data.data.id + '/edit';
                    } else if (isEdit) {
                        state.landing = data.data;
                        renderLandingData(state.landing);
                    }
                })
                .catch(function (error) {
                    if (error && error.error && error.error.fields) {
                        const messages = Object.values(error.error.fields).flat().join('<br>');
                        showAlert('danger', messages || translations.save_failed || '{{ __('landings.notifications.save_failed') }}');
                    } else {
                        showAlert('danger', translations.save_failed || '{{ __('landings.notifications.save_failed') }}');
                    }
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
        }

        function handleTypeChange(event) {
            const type = event.target.value;
            toggleSections(type);
            if (!isEdit) {
                setTemplateForType(type);
            }
        }

        function initListeners() {
            form.addEventListener('submit', handleSubmit);
            if (titleInput) {
                titleInput.addEventListener('input', handleTitleInput);
            }
            if (slugInput) {
                slugInput.addEventListener('input', handleSlugInput);
            }
            if (activeInput) {
                activeInput.addEventListener('change', function () {
                    updateStatusIndicator(activeInput.checked);
                });
            }
            if (allServicesInput) {
                allServicesInput.addEventListener('change', disableServiceSelectIfNeeded);
            }
            if (promotionSelect) {
                promotionSelect.addEventListener('change', function () {
                    updatePromotionDetails();
                    const selected = promotionSelect.selectedOptions[0];
                    if (selected && selected.dataset.promoCode && !promoCodeInput.value) {
                        promoCodeInput.value = selected.dataset.promoCode;
                    }
                });
            }
            typeRadios.forEach(function (radio) {
                radio.addEventListener('change', handleTypeChange);
            });
            if (previewButton) {
                previewButton.addEventListener('click', function () {
                    const slug = slugInput.value.trim();
                    if (!slug) return;
                    window.open(appUrl.replace(/\/$/, '') + '/l/' + slug + '?preview=1', '_blank');
                });
            }
        }

        Promise.resolve()
            .then(fetchOptions)
            .then(fetchLanding)
            .then(function () {
                if (!isEdit) {
                    toggleSections('general');
                    setTemplateForType('general');
                    updateStatusIndicator(true);
                    updateFullUrl();
                    disableServiceSelectIfNeeded();
                }
                if (isEdit && state.landing) {
                    updateFullUrl();
                }
                if (isEdit && previewButton && slugInput.value) {
                    previewButton.classList.remove('d-none');
                }
                updatePromotionDetails();
                initListeners();
            });
    });
</script>
