{{--
    The prepayment tab of the settings page. It sits OUTSIDE the settings <form>
    (its own forms are built by script, and forms cannot nest) and has its own
    save buttons, so the page's «save changes» bar is hidden while it is shown.
--}}
@php
    $copy = __('prepayment.page');
@endphp

<div class="card mb-6 settings-card" id="settings-prepayment" hidden>
    <style>
        #settings-prepayment [hidden] { display: none !important; }
        #settings-prepayment .prepay-section + .prepay-section { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--bs-border-color); }
        #settings-prepayment .prepay-section h2 { margin: 0 0 .25rem; font-size: 1.05rem; }
        #settings-prepayment .prepay-hint { margin: .25rem 0 0; font-size: .875rem; color: var(--bs-secondary-color); }
        #settings-prepayment .prepay-gate { display: flex; gap: 1rem; align-items: center; justify-content: space-between; flex-wrap: wrap; padding: 1rem 1.25rem; border: 1px solid var(--bs-warning); border-radius: .75rem; margin-bottom: 1.5rem; }
        #settings-prepayment .prepay-gate h2 { margin: 0; font-size: 1.05rem; }
        #settings-prepayment .prepay-gate p { margin: .25rem 0 0; color: var(--bs-secondary-color); }
        #settings-prepayment .prepay-row { display: flex; gap: .75rem; flex-wrap: wrap; }
        #settings-prepayment .prepay-row > * { flex: 1 1 10rem; }
        #settings-prepayment .prepay-rule { display: flex; gap: .75rem; align-items: center; justify-content: space-between; flex-wrap: wrap; padding: .75rem 0; border-top: 1px solid var(--bs-border-color); }
        #settings-prepayment .prepay-rule:first-of-type { border-top: 0; }
        #settings-prepayment .prepay-rule__name { font-weight: 600; }
        #settings-prepayment .prepay-rule__meta { font-size: .875rem; color: var(--bs-secondary-color); }
        #settings-prepayment .prepay-rule.is-off .prepay-rule__name { text-decoration: line-through; opacity: .7; }
        #settings-prepayment .prepay-days { display: flex; flex-wrap: wrap; gap: .4rem; }
        #settings-prepayment .prepay-days label { margin: 0; }
        #settings-prepayment .prepay-days input { position: absolute; opacity: 0; pointer-events: none; }
        #settings-prepayment .prepay-days span { display: inline-block; min-width: 2.6rem; padding: .35rem .6rem; text-align: center; border: 1px solid var(--bs-border-color); border-radius: .5rem; cursor: pointer; }
        #settings-prepayment .prepay-days input:checked + span { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
        #settings-prepayment .prepay-days input:focus-visible + span { outline: 2px solid var(--bs-primary); outline-offset: 2px; }
        #settings-prepayment .prepay-services { display: grid; gap: .25rem; max-height: 12rem; overflow: auto; padding: .5rem; border: 1px solid var(--bs-border-color); border-radius: .5rem; }
        #settings-prepayment .prepay-details > summary { cursor: pointer; font-weight: 600; padding: .25rem 0; }
        #settings-prepayment .prepay-alert { padding: .75rem 1rem; margin-bottom: 1rem; border-radius: .5rem; border: 1px solid var(--bs-border-color); }
        #settings-prepayment .prepay-alert.is-error { border-color: var(--bs-danger); color: var(--bs-danger); }
        #settings-prepayment .prepay-alert.is-ok { border-color: var(--bs-success); }
    </style>

    <div class="card-body p-5 p-lg-6">
        <div class="settings-section-title">
            <div>
                <h5 class="mb-1">{{ $copy['title'] }}</h5>
                <p class="text-muted mb-0">{{ $copy['lead'] }}</p>
            </div>
            <span class="settings-meta-chip"><i class="icon-base ri ri-bank-card-line"></i> ЮKassa</span>
        </div>

        <div id="prepay-alert" role="status" hidden></div>
        <div id="prepay-body"><p class="prepay-hint">{{ $copy['loading'] }}</p></div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var COPY = {{ \Illuminate\Support\Js::from($copy) }};
            var INTEGRATIONS_URL = {{ \Illuminate\Support\Js::from(route('integrations')) }};
            var bodyEl = document.getElementById('prepay-body');
            var alertEl = document.getElementById('prepay-alert');
            var data = null;
            var editingRule = null; // null: closed, 0: new, id: editing

            function getCookie(name) {
                var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
                return match ? decodeURIComponent(match[2]) : null;
            }

            function api(method, url, body) {
                var headers = { 'Accept': 'application/json', 'Accept-Language': document.documentElement.lang };
                var token = getCookie('token');
                if (token) headers['Authorization'] = 'Bearer ' + token;
                if (body) headers['Content-Type'] = 'application/json';

                return fetch(url, { method: method, headers: headers, body: body ? JSON.stringify(body) : undefined })
                    .then(function (response) {
                        return response.json().catch(function () { return {}; }).then(function (json) {
                            if (!response.ok) {
                                var errors = json.errors ? Object.values(json.errors)[0] : null;
                                var error = new Error((errors && errors[0]) || json.message || COPY.save_failed);
                                error.status = response.status;
                                throw error;
                            }
                            return json;
                        });
                    });
            }

            function esc(value) {
                return String(value === null || value === undefined ? '' : value).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            function say(message, tone) {
                alertEl.hidden = !message;
                alertEl.className = 'prepay-alert ' + (tone === 'error' ? 'is-error' : 'is-ok');
                alertEl.textContent = message || '';
                if (message) alertEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }

            function number(value) {
                return new Intl.NumberFormat(document.documentElement.lang || 'ru').format(value);
            }

            function dayName(index) { return COPY.weekdays[index]; }

            function ruleSummary(rule) {
                var when;
                if (rule.type === 'weekday') {
                    when = [1, 2, 3, 4, 5, 6, 0].filter(function (d) { return (rule.weekdays || []).indexOf(d) !== -1; }).map(dayName).join(', ');
                } else {
                    when = rule.starts_on.split('-').reverse().join('.') + ' — ' + rule.ends_on.split('-').reverse().join('.');
                }
                var amount = rule.mode === 'percent' ? number(rule.value) + '%' : number(rule.value) + ' ₽';
                var services = (rule.service_ids || []).length
                    ? rule.service_ids.map(function (id) {
                        var s = data.services.find(function (x) { return x.id === id; });
                        return s ? s.name : '';
                    }).filter(Boolean).join(', ')
                    : COPY.rule_services_all;
                return when + ' · ' + amount + ' · ' + services;
            }

            function gate() {
                if (data.shop === 'verified') return '';
                var text = data.shop === 'unchecked' ? COPY.unchecked_text : COPY.connect_text;
                var title = data.shop === 'unchecked' ? COPY.unchecked_cta : COPY.connect_title;
                var cta = data.shop === 'unchecked' ? COPY.unchecked_cta : COPY.connect_cta;
                return '<section class="prepay-gate"><div><h2>' + esc(title) + '</h2><p>' + esc(text) + '</p></div>'
                    + '<a class="btn btn-primary" href="' + esc(INTEGRATIONS_URL) + '">' + esc(cta) + '</a></section>';
            }

            function policyForm() {
                var p = data.policy;
                var locked = data.shop !== 'verified';
                return '<form class="prepay-section d-flex flex-column gap-3" id="policy-form" novalidate>'
                    + '<div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="p-enabled" ' + (p.enabled ? 'checked ' : '') + (locked ? 'disabled ' : '') + '>'
                    + '<label class="form-check-label fw-semibold" for="p-enabled">' + esc(COPY.toggle) + '</label>'
                    + '<p class="prepay-hint">' + esc(COPY.toggle_hint) + '</p></div>'
                    + '<div><h2>' + esc(COPY.amount_title) + '</h2><div class="prepay-row mt-2">'
                    + '<div><label class="form-label" for="p-mode">&nbsp;</label><select class="form-select" id="p-mode" aria-label="' + esc(COPY.amount_title) + '">'
                    + '<option value="percent"' + (p.default_mode === 'percent' ? ' selected' : '') + '>' + esc(COPY.mode_percent) + '</option>'
                    + '<option value="fixed"' + (p.default_mode === 'fixed' ? ' selected' : '') + '>' + esc(COPY.mode_fixed) + '</option></select></div>'
                    + '<div><label class="form-label" for="p-value" id="p-value-label"></label><input class="form-control" id="p-value" type="number" min="1" step="1" inputmode="decimal" value="' + esc(p.default_value) + '"></div></div>'
                    + '<p class="prepay-hint" id="p-example"></p></div>'
                    + '<div class="form-check"><input class="form-check-input" type="checkbox" id="p-new" ' + (p.new_clients_only ? 'checked' : '') + '>'
                    + '<label class="form-check-label" for="p-new">' + esc(COPY.new_only) + '</label><p class="prepay-hint">' + esc(COPY.new_only_hint) + '</p></div>'
                    + '<p class="prepay-hint">' + esc(COPY.per_service_hint) + '</p>'
                    + '<details class="prepay-details"><summary>' + esc(COPY.advanced) + '</summary><div class="d-flex flex-column gap-3 pt-3">'
                    + '<div><label class="form-label" for="p-hold">' + esc(COPY.hold_label) + '</label><input class="form-control" id="p-hold" type="number" min="5" max="120" value="' + esc(p.hold_minutes) + '"><p class="prepay-hint">' + esc(COPY.hold_hint) + '</p></div>'
                    + '<div><label class="form-label" for="p-noshow">' + esc(COPY.no_show_label) + '</label><input class="form-control" id="p-noshow" type="number" min="0" max="10" value="' + esc(p.no_show_threshold) + '"><p class="prepay-hint">' + esc(COPY.no_show_hint) + '</p></div>'
                    + '<div><h2 class="fs-6">' + esc(COPY.refund_title) + '</h2><div class="prepay-row mt-2">'
                    + '<div><label class="form-label" for="p-refund-hours">' + esc(COPY.refund_hours) + '</label><input class="form-control" id="p-refund-hours" type="number" min="0" max="720" value="' + esc(p.refund.full_before_hours === null ? '' : p.refund.full_before_hours) + '"></div>'
                    + '<div><label class="form-label" for="p-refund-partial">' + esc(COPY.refund_partial) + '</label><input class="form-control" id="p-refund-partial" type="number" min="0" max="100" value="' + esc(p.refund.partial_percent) + '"></div></div>'
                    + '<p class="prepay-hint">' + esc(COPY.refund_hours_hint) + '</p></div>'
                    + '<div><label class="form-label" for="p-webhook">' + esc(COPY.webhook_title) + '</label><div class="input-group"><input class="form-control" id="p-webhook" readonly value="' + esc(data.webhook_url) + '">'
                    + '<button class="btn btn-outline-secondary" type="button" id="p-copy">' + esc(COPY.copy) + '</button></div><p class="prepay-hint">' + esc(COPY.webhook_hint) + '</p></div>'
                    + '</div></details>'
                    + '<div><button class="btn btn-primary" type="submit" id="p-save">' + esc(COPY.save) + '</button></div></form>';
            }

            function rulesCard() {
                var rows = data.rules.map(function (rule) {
                    return '<div class="prepay-rule' + (rule.is_active ? '' : ' is-off') + '" data-rule="' + rule.id + '"><div>'
                        + '<div class="prepay-rule__name">' + esc(rule.name) + (rule.is_active ? '' : ' <span class="badge bg-label-secondary">' + esc(COPY.rule_off) + '</span>') + '</div>'
                        + '<div class="prepay-rule__meta">' + esc(ruleSummary(rule)) + '</div></div>'
                        + '<div class="d-flex gap-2 flex-wrap">'
                        + '<button class="btn btn-sm btn-outline-secondary" type="button" data-act="toggle" data-id="' + rule.id + '">' + esc(rule.is_active ? COPY.rule_turn_off : COPY.rule_turn_on) + '</button>'
                        + '<button class="btn btn-sm btn-outline-secondary" type="button" data-act="edit" data-id="' + rule.id + '">' + esc(COPY.edit) + '</button>'
                        + '<button class="btn btn-sm btn-outline-danger" type="button" data-act="delete" data-id="' + rule.id + '">' + esc(COPY.delete) + '</button></div></div>';
                }).join('');

                return '<section class="prepay-section"><h2>' + esc(COPY.periods_title) + '</h2><p class="prepay-hint">' + esc(COPY.periods_hint) + '</p>'
                    + '<div class="mt-2">' + (rows || '<p class="prepay-hint">' + esc(COPY.rules_empty) + '</p>') + '</div>'
                    + (editingRule === null ? '<button class="btn btn-outline-primary mt-3" type="button" data-act="new">' + esc(COPY.rule_add) + '</button>' : ruleForm())
                    + '</section>';
            }

            function ruleForm() {
                var rule = editingRule ? data.rules.find(function (r) { return r.id === editingRule; }) : null;
                rule = rule || { name: '', type: 'period', starts_on: '', ends_on: '', weekdays: [6, 0], mode: 'percent', value: 50, service_ids: [], is_active: true };
                var some = (rule.service_ids || []).length > 0;

                var days = [1, 2, 3, 4, 5, 6, 0].map(function (d) {
                    return '<label><input type="checkbox" name="weekday" value="' + d + '"' + ((rule.weekdays || []).indexOf(d) !== -1 ? ' checked' : '') + '><span>' + esc(dayName(d)) + '</span></label>';
                }).join('');

                var services = data.services.map(function (s) {
                    return '<label class="form-check"><input class="form-check-input" type="checkbox" name="service" value="' + s.id + '"' + ((rule.service_ids || []).indexOf(s.id) !== -1 ? ' checked' : '') + '><span class="form-check-label">' + esc(s.name) + '</span></label>';
                }).join('');

                return '<form class="d-flex flex-column gap-3 mt-3 pt-3 border-top" id="rule-form" novalidate>'
                    + '<div><label class="form-label" for="r-name">' + esc(COPY.rule_name) + '</label><input class="form-control" id="r-name" maxlength="80" placeholder="' + esc(COPY.rule_name_placeholder) + '" value="' + esc(rule.name) + '"></div>'
                    + '<div><label class="form-label" for="r-type">' + esc(COPY.rule_kind) + '</label><select class="form-select" id="r-type">'
                    + '<option value="period"' + (rule.type === 'period' ? ' selected' : '') + '>' + esc(COPY.rule_kind_period) + '</option>'
                    + '<option value="weekday"' + (rule.type === 'weekday' ? ' selected' : '') + '>' + esc(COPY.rule_kind_weekday) + '</option></select></div>'
                    + '<div class="prepay-row" id="r-period"' + (rule.type === 'period' ? '' : ' hidden') + '>'
                    + '<div><label class="form-label" for="r-from">' + esc(COPY.rule_from) + '</label><input class="form-control" id="r-from" type="date" value="' + esc(rule.starts_on || '') + '"></div>'
                    + '<div><label class="form-label" for="r-to">' + esc(COPY.rule_to) + '</label><input class="form-control" id="r-to" type="date" value="' + esc(rule.ends_on || '') + '"></div></div>'
                    + '<div class="prepay-days" id="r-weekdays" role="group"' + (rule.type === 'weekday' ? '' : ' hidden') + '>' + days + '</div>'
                    + '<div class="prepay-row"><div><label class="form-label" for="r-mode">&nbsp;</label><select class="form-select" id="r-mode" aria-label="' + esc(COPY.amount_title) + '">'
                    + '<option value="percent"' + (rule.mode === 'percent' ? ' selected' : '') + '>' + esc(COPY.mode_percent) + '</option>'
                    + '<option value="fixed"' + (rule.mode === 'fixed' ? ' selected' : '') + '>' + esc(COPY.mode_fixed) + '</option></select></div>'
                    + '<div><label class="form-label" for="r-value" id="r-value-label"></label><input class="form-control" id="r-value" type="number" min="1" step="1" value="' + esc(rule.value) + '"></div></div>'
                    + '<div><span class="form-label d-block">' + esc(COPY.rule_services) + '</span>'
                    + '<div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="r-scope" id="r-all" value="all"' + (some ? '' : ' checked') + '><label class="form-check-label" for="r-all">' + esc(COPY.rule_services_all) + '</label></div>'
                    + '<div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="r-scope" id="r-some" value="some"' + (some ? ' checked' : '') + '><label class="form-check-label" for="r-some">' + esc(COPY.rule_services_some) + '</label></div>'
                    + '<div class="prepay-services mt-2" id="r-services"' + (some ? '' : ' hidden') + '>' + services + '</div></div>'
                    + '<div class="d-flex gap-2"><button class="btn btn-primary" type="submit">' + esc(COPY.save) + '</button><button class="btn btn-outline-secondary" type="button" data-act="cancel">' + esc(COPY.cancel) + '</button></div></form>';
            }

            function render() {
                bodyEl.innerHTML = gate() + policyForm() + rulesCard();
                syncPolicyLabels();
                syncRuleLabels();
            }

            function syncPolicyLabels() {
                var mode = document.getElementById('p-mode');
                if (!mode) return;
                var percent = mode.value === 'percent';
                document.getElementById('p-value-label').textContent = percent ? COPY.value_percent : COPY.value_fixed;
                var value = parseFloat(document.getElementById('p-value').value) || 0;
                var example = percent ? Math.min(2000, Math.max(1, 2000 * value / 100)) : Math.min(2000, value);
                document.getElementById('p-example').textContent = COPY.example.replace(':amount', number(Math.round(example)));
            }

            function syncRuleLabels() {
                var mode = document.getElementById('r-mode');
                if (!mode) return;
                document.getElementById('r-value-label').textContent = mode.value === 'percent' ? COPY.value_percent : COPY.value_fixed;
            }

            function load() {
                return api('GET', '/api/v1/settings/prepayment').then(function (json) {
                    data = json.data;
                    render();
                }).catch(function () {
                    bodyEl.innerHTML = '';
                    say(COPY.load_failed, 'error');
                });
            }

            function savePolicy(form) {
                var hours = document.getElementById('p-refund-hours').value;
                var button = document.getElementById('p-save');
                button.disabled = true;
                button.textContent = COPY.saving;
                say('');

                api('PUT', '/api/v1/settings/prepayment', {
                    enabled: document.getElementById('p-enabled').checked,
                    default_mode: document.getElementById('p-mode').value,
                    default_value: document.getElementById('p-value').value,
                    new_clients_only: document.getElementById('p-new').checked,
                    hold_minutes: document.getElementById('p-hold').value,
                    no_show_threshold: document.getElementById('p-noshow').value || 0,
                    refund: {
                        full_before_hours: hours === '' ? null : hours,
                        partial_percent: document.getElementById('p-refund-partial').value || 0
                    }
                }).then(function (json) {
                    data = json.data;
                    render();
                    say(json.message, 'ok');
                }).catch(function (error) {
                    button.disabled = false;
                    button.textContent = COPY.save;
                    say(error.message, 'error');
                });
            }

            function ruleBody() {
                var type = document.getElementById('r-type').value;
                var scopeSome = document.getElementById('r-some').checked;
                var rule = editingRule ? data.rules.find(function (r) { return r.id === editingRule; }) : null;

                return {
                    name: document.getElementById('r-name').value,
                    type: type,
                    starts_on: type === 'period' ? document.getElementById('r-from').value || null : null,
                    ends_on: type === 'period' ? document.getElementById('r-to').value || null : null,
                    weekdays: type === 'weekday' ? Array.prototype.map.call(document.querySelectorAll('input[name="weekday"]:checked'), function (i) { return parseInt(i.value, 10); }) : null,
                    mode: document.getElementById('r-mode').value,
                    value: document.getElementById('r-value').value,
                    service_ids: scopeSome ? Array.prototype.map.call(document.querySelectorAll('input[name="service"]:checked'), function (i) { return parseInt(i.value, 10); }) : [],
                    is_active: rule ? rule.is_active : true
                };
            }

            function saveRule() {
                var path = editingRule ? '/api/v1/prepayment-rules/' + editingRule : '/api/v1/prepayment-rules';
                say('');

                api(editingRule ? 'PUT' : 'POST', path, ruleBody()).then(function (json) {
                    editingRule = null;
                    return load().then(function () { say(json.message, 'ok'); });
                }).catch(function (error) { say(error.message, 'error'); });
            }

            function toggleRule(id) {
                var rule = data.rules.find(function (r) { return r.id === id; });
                var body = Object.assign({}, rule, { is_active: !rule.is_active, starts_on: rule.starts_on, ends_on: rule.ends_on });
                api('PUT', '/api/v1/prepayment-rules/' + id, body).then(function () { return load(); })
                    .catch(function (error) { say(error.message, 'error'); });
            }

            function deleteRule(id) {
                var rule = data.rules.find(function (r) { return r.id === id; });
                if (!window.confirm(COPY.delete_confirm.replace(':name', rule.name))) return;
                api('DELETE', '/api/v1/prepayment-rules/' + id).then(function (json) {
                    return load().then(function () { say(json.message, 'ok'); });
                }).catch(function (error) { say(error.message, 'error'); });
            }

            bodyEl.addEventListener('submit', function (event) {
                event.preventDefault();
                if (event.target.id === 'policy-form') savePolicy(event.target);
                if (event.target.id === 'rule-form') saveRule();
            });

            bodyEl.addEventListener('input', function (event) {
                if (event.target.id === 'p-value' || event.target.id === 'p-mode') syncPolicyLabels();
                if (event.target.id === 'r-mode') syncRuleLabels();
            });

            bodyEl.addEventListener('change', function (event) {
                if (event.target.id === 'p-mode') syncPolicyLabels();
                if (event.target.id === 'r-mode') syncRuleLabels();
                if (event.target.id === 'r-type') {
                    document.getElementById('r-period').hidden = event.target.value !== 'period';
                    document.getElementById('r-weekdays').hidden = event.target.value !== 'weekday';
                }
                if (event.target.name === 'r-scope') {
                    document.getElementById('r-services').hidden = document.getElementById('r-some').checked === false;
                }
            });

            bodyEl.addEventListener('click', function (event) {
                var target = event.target.closest('[data-act], #p-copy');
                if (!target) return;

                if (target.id === 'p-copy') {
                    var input = document.getElementById('p-webhook');
                    input.select();
                    (navigator.clipboard ? navigator.clipboard.writeText(input.value) : Promise.resolve(document.execCommand('copy')))
                        .then(function () { target.textContent = COPY.copied; setTimeout(function () { target.textContent = COPY.copy; }, 1500); });
                    return;
                }

                var id = parseInt(target.dataset.id, 10);
                switch (target.dataset.act) {
                    case 'new': editingRule = 0; render(); document.getElementById('r-name').focus(); break;
                    case 'edit': editingRule = id; render(); document.getElementById('r-name').focus(); break;
                    case 'cancel': editingRule = null; render(); break;
                    case 'toggle': toggleRule(id); break;
                    case 'delete': deleteRule(id); break;
                }
            });

            // Only when the tab is opened: most visits to settings never need it.
            var loaded = false;
            function loadOnce() {
                if (loaded) return;
                loaded = true;
                load();
            }

            if (location.hash === '#settings-prepayment') loadOnce();
            window.addEventListener('hashchange', function () { if (location.hash === '#settings-prepayment') loadOnce(); });
            document.addEventListener('click', function (event) {
                if (event.target.closest('[data-settings-tab="settings-prepayment"]')) loadOnce();
            });
        });
    </script>
@endpush
