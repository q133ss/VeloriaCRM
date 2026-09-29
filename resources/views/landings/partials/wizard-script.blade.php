<script>
    document.addEventListener('DOMContentLoaded', function () {
        const W = @json(__('landings.wizard'));
        const cfg = window.LANDING_WIZARD_CONFIG || {};
        const root = document.getElementById('lw-root');
        if (!root) return;

        const TYPES = ['general', 'promotion', 'service', 'seasonal', 'consultation'];
        const LAYOUTS = cfg.layouts || [];
        const CATEGORIES = cfg.categories || [];

        function layout() {
            return LAYOUTS.find(function (item) { return item.slug === state.layout; }) || LAYOUTS[0] || { templates: {} };
        }
        const state = {
            step: 1,
            type: 'general',
            layout: null,
            category: 'all',
            services: [],
            promotions: [],
            optionsLoaded: false,
            optionsFailed: false,
            allServices: true,
            serviceIds: [],
            serviceId: '',
            promotionId: '',
            season: '',
            seasonCustom: '',
            promo: { percent: '', code: '', ends: '' },
            saving: false,
            created: null,
        };

        const $ = function (id) { return document.getElementById(id); };
        const stepsEls = Array.from(root.querySelectorAll('.lw-step'));
        const progressEls = Array.from(root.querySelectorAll('.lw-progress-step'));
        const prevBtn = $('lw-prev');
        const nextBtn = $('lw-next');
        const nav = $('lw-nav');
        const alerts = $('lw-alerts');
        const totalSteps = 3;

        /* ---------- helpers ---------- */
        function esc(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
            });
        }

        function fmt(template, vars) {
            return String(template).replace(/:([a-z_]+)/g, function (match, key) {
                return Object.prototype.hasOwnProperty.call(vars || {}, key) ? vars[key] : match;
            });
        }

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

        function showAlert(kind, html) {
            alerts.innerHTML = '<div class="alert alert-' + kind + ' mb-3">' + html + '</div>';
            alerts.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function clearAlert() { alerts.innerHTML = ''; }

        function formatMoney(value) {
            return new Intl.NumberFormat('ru-RU').format(Math.round(Number(value))) + ' ₽';
        }

        function formatDate(iso) {
            if (!iso) return '';
            const parts = String(iso).slice(0, 10).split('-');
            return parts.length === 3 ? parts[2] + '.' + parts[1] + '.' + parts[0] : iso;
        }

        function serviceById(id) {
            return state.services.find(function (item) { return Number(item.id) === Number(id); }) || null;
        }

        function promotionById(id) {
            return state.promotions.find(function (item) { return Number(item.id) === Number(id); }) || null;
        }

        function seasonLabel() {
            return (state.season === '__custom' ? state.seasonCustom : state.season).trim();
        }

        /* ---------- step 1: template gallery ---------- */
        function renderCategories() {
            const chips = [{ slug: 'all', label: W.all }].concat(CATEGORIES);
            $('lw-cats').innerHTML = chips.map(function (chip) {
                return '<button type="button" class="lw-cat' + (state.category === chip.slug ? ' is-active' : '') + '" data-category="' + esc(chip.slug) + '" aria-pressed="' + (state.category === chip.slug) + '">' + esc(chip.label) + '</button>';
            }).join('');
        }

        function renderLayouts() {
            const visible = LAYOUTS.filter(function (item) {
                return state.category === 'all' || (item.categories || []).indexOf(state.category) !== -1;
            });

            if (!visible.length) {
                $('lw-layouts').innerHTML = '<div class="lw-empty-note" style="grid-column: 1 / -1">' + esc(W.no_templates) + '</div>';
                return;
            }

            const labels = {};
            CATEGORIES.forEach(function (c) { labels[c.slug] = c.label; });

            $('lw-layouts').innerHTML = visible.map(function (item) {
                const selected = state.layout === item.slug;
                const tags = (item.categories || []).map(function (c) { return '<span>' + esc(labels[c] || c) + '</span>'; }).join('');
                return '<article class="lw-layout' + (selected ? ' is-selected' : '') + '">' +
                    '<span class="lw-layout-thumb"><img src="' + esc(item.thumb) + '" alt="" loading="lazy" /></span>' +
                    '<div class="lw-layout-body"><strong>' + esc(item.title) + '</strong><span class="lw-layout-desc">' + esc(item.description) + '</span>' +
                    (tags ? '<div class="lw-layout-tags">' + tags + '</div>' : '') + '</div>' +
                    '<div class="lw-layout-actions">' +
                    '<button type="button" class="btn btn-primary" data-layout="' + esc(item.slug) + '">' + esc(W.choose) + '</button>' +
                    '<a class="btn btn-outline-secondary" href="' + esc(item.demo_url) + '" target="_blank" rel="noopener">' + esc(W.view) + '</a>' +
                    '</div></article>';
            }).join('');
        }

        /* ---------- step 2: what the page shows ---------- */
        function renderTypes() {
            $('lw-types').innerHTML = TYPES.map(function (type) {
                const info = W.goal.types[type];
                const selected = state.type === type;
                return '<label class="lw-type' + (selected ? ' is-selected' : '') + '">' +
                    '<input type="radio" name="lw-type" value="' + type + '"' + (selected ? ' checked' : '') + ' />' +
                    '<span class="lw-type-dot" aria-hidden="true"></span>' +
                    '<span><strong>' + esc(info.title) + '</strong><small>' + esc(info.desc) + '</small></span></label>';
            }).join('');
        }

        /* ---------- step 2 fields ---------- */
        function servicesEmptyHtml() {
            return '<div class="lw-empty"><p>' + esc(W.about.services_none) + '</p><a class="btn btn-outline-primary" href="' + esc(cfg.servicesUrl) + '">' + esc(W.about.services_open) + '</a></div>';
        }

        function checkListHtml() {
            return '<div class="lw-check-list" id="lw-service-list">' + state.services.map(function (service) {
                const checked = state.serviceIds.indexOf(Number(service.id)) !== -1;
                return '<label class="lw-check"><input type="checkbox" value="' + service.id + '"' + (checked ? ' checked' : '') + ' /><span>' + esc(service.name) + '</span></label>';
            }).join('') + '</div>';
        }

        function serviceSelectHtml(id) {
            return '<select class="form-select" id="' + id + '"><option value="">' + esc(W.about.service_pick_ph) + '</option>' +
                state.services.map(function (service) {
                    return '<option value="' + service.id + '"' + (String(state.serviceId) === String(service.id) ? ' selected' : '') + '>' + esc(service.name) + '</option>';
                }).join('') + '</select>';
        }

        function typeBlockHtml() {
            const type = state.type;
            if (state.optionsFailed) return '<div class="alert alert-danger mb-0">' + esc(W.about.options_failed) + '</div>';
            let html = '';

            if (type === 'general') {
                html += '<div class="lw-field" data-field="services"><span class="lw-label">' + esc(W.about.services) + '</span>';
                if (!state.services.length) {
                    html += servicesEmptyHtml();
                } else {
                    html += '<div class="lw-seg mb-3" role="radiogroup">' +
                        '<label><input type="radio" name="lw-scope" value="all"' + (state.allServices ? ' checked' : '') + ' />' + esc(W.about.services_all) + '</label>' +
                        '<label><input type="radio" name="lw-scope" value="pick"' + (!state.allServices ? ' checked' : '') + ' />' + esc(W.about.services_pick) + '</label></div>';
                    html += '<div id="lw-pick-wrap"' + (state.allServices ? ' hidden' : '') + '>' + checkListHtml() + '</div>';
                }
                html += '<div class="lw-field-error"></div></div>';
            }

            if (type === 'seasonal') {
                html += '<div class="lw-field" data-field="season"><span class="lw-label">' + esc(W.about.season) + '</span><div class="lw-chips" role="radiogroup">' +
                    W.about.seasons.map(function (label) {
                        return '<label class="lw-chip"><input type="radio" name="lw-season" value="' + esc(label) + '"' + (state.season === label ? ' checked' : '') + ' /><span>' + esc(label) + '</span></label>';
                    }).join('') +
                    '<label class="lw-chip"><input type="radio" name="lw-season" value="__custom"' + (state.season === '__custom' ? ' checked' : '') + ' /><span>' + esc(W.about.season_custom) + '</span></label></div>' +
                    '<input type="text" class="form-control mt-2" id="lw-season-custom" maxlength="100" value="' + esc(state.seasonCustom) + '" placeholder="' + esc(W.about.season_custom_ph) + '"' + (state.season === '__custom' ? '' : ' hidden') + ' />' +
                    '<div class="lw-field-error"></div></div>';
                html += '<div class="lw-field" data-field="services"><span class="lw-label">' + esc(W.about.services) + '</span>' +
                    (state.services.length ? checkListHtml() : servicesEmptyHtml()) + '<div class="lw-field-error"></div></div>';
            }

            if (type === 'service' || type === 'consultation') {
                html += '<div class="lw-field" data-field="service"><label class="lw-label" for="lw-service">' + esc(W.about.service_one) + '</label>' +
                    (state.services.length ? serviceSelectHtml('lw-service') : servicesEmptyHtml()) + '<div class="lw-field-error"></div></div>';
            }

            if (type === 'promotion') {
                html += '<div class="lw-field" data-field="promotion"><label class="lw-label" for="lw-promotion">' + esc(W.about.promotion) + '</label>';
                if (!state.promotions.length) {
                    html += '<div class="lw-empty"><p>' + esc(W.about.promotion_none) + '</p><a class="btn btn-outline-primary" href="' + esc(cfg.marketingUrl) + '">' + esc(W.about.promotion_open) + '</a></div>';
                } else {
                    html += '<select class="form-select" id="lw-promotion"><option value="">' + esc(W.about.promotion_ph) + '</option>' +
                        state.promotions.map(function (promo) {
                            return '<option value="' + promo.id + '"' + (String(state.promotionId) === String(promo.id) ? ' selected' : '') + '>' + esc(promo.name) + '</option>';
                        }).join('') + '</select>';
                }
                html += '<div class="lw-field-error"></div></div>';
                if (state.promotionId) {
                    html += '<div class="lw-card"><div class="row g-3">' +
                        '<div class="col-6 col-md-4 lw-field" data-field="percent"><label class="lw-label" for="lw-promo-percent">' + esc(W.about.promo_percent) + '</label><input type="number" min="1" max="100" inputmode="decimal" class="form-control" id="lw-promo-percent" value="' + esc(state.promo.percent) + '" /><div class="lw-field-error"></div></div>' +
                        '<div class="col-6 col-md-4 lw-field" data-field="code"><label class="lw-label" for="lw-promo-code">' + esc(W.about.promo_code) + '</label><input type="text" class="form-control" id="lw-promo-code" maxlength="100" value="' + esc(state.promo.code) + '" /><div class="lw-field-error"></div></div>' +
                        '<div class="col-12 col-md-4 lw-field" data-field="ends"><label class="lw-label" for="lw-promo-ends">' + esc(W.about.promo_ends) + '</label><input type="date" class="form-control" id="lw-promo-ends" value="' + esc(state.promo.ends) + '" /><div class="lw-field-error"></div></div>' +
                        '</div></div>';
                }
            }
            return html;
        }

        function renderTypeBlock() {
            $('lw-type-block').innerHTML = typeBlockHtml();
        }

        /* ---------- validation ---------- */
        function setError(field, message) {
            const el = root.querySelector('.lw-field[data-field="' + field + '"]');
            if (!el) return;
            el.classList.add('has-error');
            const box = el.querySelector('.lw-field-error');
            if (box) box.textContent = message;
        }

        function clearErrors() {
            root.querySelectorAll('.lw-field.has-error').forEach(function (el) { el.classList.remove('has-error'); });
        }

        function parseTelegram(raw) {
            const value = raw.trim();
            if (!value) return { ok: true, url: '' };
            const match = value.match(/^(?:https?:\/\/)?(?:t\.me\/)?@?([A-Za-z0-9_]{4,32})\/?$/);
            return match ? { ok: true, url: 'https://t.me/' + match[1] } : { ok: false, url: '' };
        }

        function phoneDigits(raw) { return raw.replace(/\D+/g, ''); }

        function validateAbout() {
            clearErrors();
            let firstBad = null;
            function bad(field, message) { setError(field, message); firstBad = firstBad || field; }

            if (!$('lw-name').value.trim()) bad('name', W.errors.name);
            const type = state.type;

            if (type === 'general' && !state.allServices && !state.serviceIds.length) bad('services', W.errors.services);
            if (type === 'general' && !state.services.length) bad('services', W.errors.services);
            if (type === 'seasonal') {
                if (!seasonLabel()) bad('season', W.errors.season);
                if (!state.serviceIds.length) bad('services', W.errors.services);
            }
            if ((type === 'service' || type === 'consultation') && !state.serviceId) bad('service', W.errors.service);
            if (type === 'promotion') {
                if (!state.promotionId) {
                    bad('promotion', W.errors.promotion);
                } else {
                    const percent = Number(state.promo.percent);
                    if (!(percent > 0 && percent <= 100)) bad('percent', W.errors.percent);
                    if (!state.promo.code.trim()) bad('code', W.errors.code);
                    if (!state.promo.ends) bad('ends', W.errors.ends);
                }
            }
            if (phoneDigits($('lw-phone').value).length < 7) bad('phone', W.errors.phone);
            if (!parseTelegram($('lw-telegram').value).ok) bad('telegram', W.errors.telegram);

            if (firstBad) {
                const el = root.querySelector('.lw-field[data-field="' + firstBad + '"]');
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            return true;
        }

        /* ---------- payload ---------- */
        function buildPayload() {
            const type = state.type;
            const name = $('lw-name').value.trim();
            const phone = $('lw-phone').value.trim();
            const digits = phoneDigits(phone);
            const c = W.copy;

            const settings = {
                primary_color: 'indigo',
                background_type: 'preset',
                background_value: 'preset',
                cta_label: c.cta,
                secondary_cta_label: c.secondary,
                booking_hint: c.booking_hint,
                phone: phone,
                whatsapp_url: $('lw-whatsapp').checked && digits.length >= 10 ? 'https://wa.me/' + digits : '',
                telegram_url: parseTelegram($('lw-telegram').value).url,
                address: $('lw-address').value.trim(),
                proof_items_text: c.proof,
                faq_items_text: c.faq,
            };

            if (type === 'general') {
                settings.subtitle = c.general.subtitle;
                settings.greeting = fmt(c.general.greeting, { name: name });
                settings.bonus_text = '';
                settings.show_all_services = state.allServices;
                const picked = state.allServices ? [] : state.services.filter(function (s) { return state.serviceIds.indexOf(Number(s.id)) !== -1; });
                settings.service_ids = picked.map(function (s) { return Number(s.id); });
                settings.service_names = picked.map(function (s) { return s.name; });
            }

            if (type === 'promotion') {
                const promo = promotionById(state.promotionId);
                const percent = Number(state.promo.percent);
                settings.promotion_id = Number(state.promotionId);
                settings.promotion_name = promo ? promo.name : null;
                settings.discount_percent = percent;
                settings.promo_code = state.promo.code.trim();
                settings.ends_at = state.promo.ends;
                settings.headline = fmt(c.promotion.headline, { percent: percent, name: name });
                settings.description = fmt(c.promotion.description, { date: formatDate(state.promo.ends) });
                settings.subtitle = fmt(c.promotion.subtitle, { percent: percent, code: settings.promo_code });
                settings.bonus_text = '';
                if (promo && promo.service_id) {
                    settings.service_id = Number(promo.service_id);
                    const s = serviceById(promo.service_id);
                    settings.service_name = s ? s.name : null;
                }
            }

            if (type === 'service') {
                const s = serviceById(state.serviceId);
                settings.service_id = Number(state.serviceId);
                settings.service_name = s ? s.name : null;
                settings.service_description = fmt(c.service.description, { service: s ? s.name : '', name: name });
                settings.subtitle = fmt(c.service.subtitle, { service: s ? s.name : '' });
                settings.price_from = s && s.price ? 'от ' + formatMoney(s.price) : '';
                settings.duration_label = s && s.duration ? s.duration + ' мин' : '';
                settings.benefit_items_text = c.benefits.service;
            }

            if (type === 'seasonal') {
                const season = seasonLabel();
                const picked = state.services.filter(function (item) { return state.serviceIds.indexOf(Number(item.id)) !== -1; });
                settings.season_label = season;
                settings.headline = fmt(c.seasonal.headline, { season: season, name: name });
                settings.description = fmt(c.seasonal.description, { season: season });
                settings.subtitle = fmt(c.seasonal.subtitle, { season: season });
                settings.bonus_text = '';
                settings.service_ids = picked.map(function (s) { return Number(s.id); });
                settings.service_names = picked.map(function (s) { return s.name; });
            }

            if (type === 'consultation') {
                const s = serviceById(state.serviceId);
                settings.service_id = Number(state.serviceId);
                settings.service_name = s ? s.name : null;
                settings.headline = fmt(c.consultation.headline, { name: name });
                settings.description = c.consultation.description;
                settings.subtitle = c.consultation.subtitle;
                settings.lead_magnet = c.consultation.lead_magnet;
                settings.benefit_items_text = c.benefits.consultation;
            }

            return {
                title: name,
                type: type,
                landing: layout().templates[type] || null,
                is_active: true,
                settings: settings,
            };
        }

        /* ---------- navigation ---------- */
        function updateChrome() {
            $('lw-progress-label').textContent = state.step < totalSteps
                ? fmt(W.step_of, { current: state.step, total: totalSteps }) + ' · ' + W.steps[state.step - 1]
                : W.steps[totalSteps - 1];
            progressEls.forEach(function (el, index) {
                el.classList.toggle('is-done', index + 1 < state.step);
                el.classList.toggle('is-current', index + 1 === state.step);
            });
            stepsEls.forEach(function (el) {
                el.classList.toggle('is-active', Number(el.dataset.step) === state.step);
            });
            nav.classList.toggle('is-hidden', state.step === totalSteps);
            prevBtn.style.visibility = state.step === 1 ? 'hidden' : 'visible';
            nextBtn.textContent = state.step === 2 ? (state.saving ? W.creating : W.create) : W.next;
            nextBtn.disabled = state.saving || (state.step === 1 && !state.layout);
        }

        function go(step) {
            state.step = step;
            clearAlert();
            if (step === 2) { renderTypes(); renderTypeBlock(); }
            updateChrome();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function submit() {
            if (state.saving) return;
            if (!validateAbout()) return;
            state.saving = true;
            clearAlert();
            updateChrome();

            fetch('/api/v1/landings', { method: 'POST', headers: authHeaders(), body: JSON.stringify(buildPayload()) })
                .then(function (response) {
                    return response.json().catch(function () { return {}; }).then(function (data) {
                        if (!response.ok) { const err = new Error('failed'); err.status = response.status; err.body = data; throw err; }
                        return data;
                    });
                })
                .then(function (data) {
                    state.created = data.data;
                    showDone();
                })
                .catch(function (err) {
                    let message = W.errors.save;
                    if (err.status === 403 && err.body && err.body.error && err.body.error.code === 'landing_limit_reached') {
                        message = esc(err.body.error.message || W.errors.limit) + ' <a href="' + esc(cfg.listUrl) + '">' + esc(W.to_list) + '</a>';
                    } else if (err.status === 422 && err.body && err.body.error && err.body.error.fields) {
                        message = Object.values(err.body.error.fields).flat().map(esc).join('<br>');
                    }
                    showAlert('danger', message);
                })
                .finally(function () {
                    state.saving = false;
                    updateChrome();
                });
        }

        /* The preview shows the real page at a real width and scales it down to fit,
           so what the master sees is what a phone or a laptop would show. */
        const PREVIEW = { phone: { w: 375, h: 680 }, desktop: { w: 1280, h: 800 } };
        let previewMode = 'phone';

        function fitPreview() {
            const stage = $('lw-stage');
            const holder = $('lw-holder');
            const frame = $('lw-frame');
            if (!stage || !holder || !frame || !stage.clientWidth) return;

            const size = PREVIEW[previewMode];
            const chrome = previewMode === 'phone' ? 16 : 2;
            const k = Math.min(1, (stage.clientWidth - chrome) / size.w);

            frame.style.width = size.w + 'px';
            frame.style.height = size.h + 'px';
            frame.style.transform = 'scale(' + k + ')';
            holder.style.width = Math.round(size.w * k) + 'px';
            holder.style.height = Math.round(size.h * k) + 'px';
        }

        function showDone() {
            const landing = state.created;
            const url = (landing.urls && landing.urls.public) || (cfg.appUrl + '/l/' + landing.slug);
            $('lw-link').value = url;
            $('lw-open').href = url;
            $('lw-edit').href = '/l/' + landing.slug + '?edit=1';
            $('lw-settings').href = '/landings/' + landing.id + '/edit';
            $('lw-frame').src = url + '?preview=1';

            const shareText = W.done.share_text;
            $('lw-share-tg').href = 'https://t.me/share/url?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(shareText);
            $('lw-share-wa').href = 'https://wa.me/?text=' + encodeURIComponent(shareText + ' ' + url);
            go(3);
            fitPreview();
        }

        /* ---------- events ---------- */
        prevBtn.addEventListener('click', function () { if (state.step > 1) go(state.step - 1); });
        nextBtn.addEventListener('click', function () {
            if (state.step === 1 && state.layout) return go(2);
            if (state.step === 2) return submit();
        });

        root.addEventListener('change', function (event) {
            const target = event.target;
            if (target.name === 'lw-type') {
                state.type = target.value;
                state.serviceIds = [];
                state.serviceId = '';
                state.promotionId = '';
                state.season = '';
                renderTypes();
                renderTypeBlock();
            } else if (target.name === 'lw-preview-mode') {
                previewMode = target.value;
                $('lw-preview').classList.toggle('is-phone', previewMode === 'phone');
                $('lw-preview').classList.toggle('is-desktop', previewMode === 'desktop');
                fitPreview();
            } else if (target.name === 'lw-scope') {
                state.allServices = target.value === 'all';
                const wrap = $('lw-pick-wrap');
                if (wrap) wrap.hidden = state.allServices;
            } else if (target.name === 'lw-season') {
                state.season = target.value;
                const custom = $('lw-season-custom');
                if (custom) { custom.hidden = state.season !== '__custom'; if (!custom.hidden) custom.focus(); }
            } else if (target.closest && target.closest('#lw-service-list')) {
                state.serviceIds = Array.from(root.querySelectorAll('#lw-service-list input:checked')).map(function (input) { return Number(input.value); });
            } else if (target.id === 'lw-service') {
                state.serviceId = target.value;
            } else if (target.id === 'lw-promotion') {
                state.promotionId = target.value;
                const promo = promotionById(state.promotionId);
                state.promo = {
                    percent: promo && promo.percent ? String(promo.percent) : '',
                    code: promo && promo.promo_code ? promo.promo_code : '',
                    ends: promo && promo.ends_at ? promo.ends_at : '',
                };
                renderTypeBlock();
            }
        });

        root.addEventListener('click', function (event) {
            const cat = event.target.closest && event.target.closest('[data-category]');
            if (cat) {
                state.category = cat.getAttribute('data-category');
                renderCategories();
                renderLayouts();
                return;
            }

            const pick = event.target.closest && event.target.closest('[data-layout]');
            if (pick) {
                state.layout = pick.getAttribute('data-layout');
                renderLayouts();
                updateChrome();
                go(2);
            }
        });

        root.addEventListener('input', function (event) {
            const id = event.target.id;
            if (id === 'lw-season-custom') state.seasonCustom = event.target.value;
            if (id === 'lw-promo-percent') state.promo.percent = event.target.value;
            if (id === 'lw-promo-code') state.promo.code = event.target.value;
            if (id === 'lw-promo-ends') state.promo.ends = event.target.value;
        });

        $('lw-about-form').addEventListener('submit', function (event) { event.preventDefault(); submit(); });

        $('lw-copy').addEventListener('click', function () {
            const input = $('lw-link');
            const button = $('lw-copy');
            const label = button.querySelector('span');
            const icon = button.querySelector('i');
            const done = function () {
                button.classList.add('is-copied');
                icon.className = 'ri ri-check-line';
                label.textContent = W.done.copied;
                setTimeout(function () {
                    button.classList.remove('is-copied');
                    icon.className = 'ri ri-file-copy-line';
                    label.textContent = W.done.copy;
                }, 2000);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(input.value).then(done, function () { input.select(); document.execCommand('copy'); done(); });
            } else {
                input.select();
                document.execCommand('copy');
                done();
            }
        });

        // The preview is the same site, so its scrollbar can be hidden: a native
        // scrollbar inside a phone frame looks broken. Scrolling still works.
        $('lw-frame').addEventListener('load', function () {
            try {
                const doc = $('lw-frame').contentDocument;
                const style = doc.createElement('style');
                style.textContent = 'html{scrollbar-width:none}html::-webkit-scrollbar{display:none}';
                doc.head.appendChild(style);
            } catch (e) { /* cross-origin: leave it */ }
        });

        window.addEventListener('resize', fitPreview);

        /* ---------- boot ---------- */
        renderCategories();
        renderLayouts();
        renderTypes();
        updateChrome();

        fetch('/api/v1/landings/options', { headers: authHeaders() })
            .then(function (response) { if (!response.ok) throw new Error('failed'); return response.json(); })
            .then(function (data) {
                state.services = (data.data && data.data.services) || [];
                state.promotions = (data.data && data.data.promotions) || [];
            })
            .catch(function () { state.optionsFailed = true; })
            .finally(function () {
                state.optionsLoaded = true;
                if (state.step === 2) renderTypeBlock();
            });
    });
</script>
