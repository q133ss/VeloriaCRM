/*
 * Booking widget for landing pages: free windows from the master's schedule,
 * booking on one, a plain request when no time is picked, and a confirmation
 * window. Template-agnostic: the page gives it a form, a picker placeholder and
 * ids through window.LandingBookingConfig (see partials/request-script.blade.php).
 */
(function () {
    'use strict';

    var cfg = window.LandingBookingConfig;
    if (!cfg) return;

    var ui = cfg.i18n || {};
    var ids = cfg.ids || {};
    var form = document.getElementById(ids.form);
    if (!form) return;

    var button = document.getElementById(ids.button);
    var box = document.getElementById(ids.message);
    var select = ids.service ? document.getElementById(ids.service) : null;
    var pickerRoot = ids.picker ? document.getElementById(ids.picker) : null;
    var lang = (document.documentElement.lang || 'ru').slice(0, 5);
    var idleLabel = button ? button.innerHTML : '';

    var state = { days: [], date: null, time: null, loaded: false, token: 0 };

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text != null) node.textContent = text;
        return node;
    }

    function fmt(template, vars) {
        return String(template).replace(/:([a-z_]+)/g, function (m, k) { return vars && vars[k] != null ? vars[k] : m; });
    }

    function parseDate(iso) {
        var p = iso.split('-');
        return new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]));
    }

    function dayLabel(iso, index) {
        var d = parseDate(iso);
        var today = new Date();
        var diff = Math.round((new Date(d.getFullYear(), d.getMonth(), d.getDate()) - new Date(today.getFullYear(), today.getMonth(), today.getDate())) / 86400000);
        var top = diff === 0 ? ui.today : (diff === 1 ? ui.tomorrow : d.toLocaleDateString(lang, { weekday: 'short' }));
        return { top: top, bottom: d.toLocaleDateString(lang, { day: 'numeric', month: 'short' }) };
    }

    function longDate(iso) {
        return parseDate(iso).toLocaleDateString(lang, { day: 'numeric', month: 'long', weekday: 'long' });
    }

    function whenLabel() {
        return state.date && state.time ? parseDate(state.date).toLocaleDateString(lang, { day: 'numeric', month: 'long' }) + ', ' + state.time : '';
    }

    function say(kind, text) {
        if (!box) return;
        box.className = 'lb-message ' + (kind === 'error' ? 'text-danger lb-error' : 'text-success');
        box.textContent = text || '';
    }

    function updateButton() {
        if (!button) return;
        if (state.date && state.time) button.textContent = fmt(ui.submit_book, { when: whenLabel() });
        else button.innerHTML = idleLabel;
    }

    /* ---------------- picker ---------------- */
    function renderPicker() {
        if (!pickerRoot) return;
        pickerRoot.innerHTML = '';
        pickerRoot.classList.add('lb-picker');

        if (cfg.editing) {
            pickerRoot.appendChild(el('p', 'lb-note', ui.editing));
            return;
        }

        pickerRoot.appendChild(el('div', 'lb-title', ui.pick_title));

        if (!state.loaded) {
            pickerRoot.appendChild(el('p', 'lb-note', ui.loading));
            return;
        }

        if (!state.days.length) {
            pickerRoot.appendChild(el('p', 'lb-note', ui.none));
            return;
        }

        pickerRoot.appendChild(el('p', 'lb-note', ui.pick_hint));

        var strip = el('div', 'lb-days');
        strip.setAttribute('role', 'listbox');
        state.days.forEach(function (day, i) {
            var label = dayLabel(day.date, i);
            var b = el('button', 'lb-day' + (state.date === day.date ? ' is-selected' : ''));
            b.type = 'button';
            b.setAttribute('role', 'option');
            b.setAttribute('aria-selected', state.date === day.date ? 'true' : 'false');
            b.appendChild(el('span', 'lb-day-top', label.top));
            b.appendChild(el('span', 'lb-day-bottom', label.bottom));
            b.addEventListener('click', function () {
                state.date = day.date;
                if (state.time && day.slots.indexOf(state.time) === -1) state.time = null;
                renderPicker();
                updateButton();
            });
            strip.appendChild(b);
        });
        pickerRoot.appendChild(strip);

        var current = state.days.filter(function (d) { return d.date === state.date; })[0];
        if (current) {
            var times = el('div', 'lb-times');
            current.slots.forEach(function (slot) {
                var t = el('button', 'lb-time' + (state.time === slot ? ' is-selected' : ''), slot);
                t.type = 'button';
                t.addEventListener('click', function () {
                    state.time = state.time === slot ? null : slot;
                    renderPicker();
                    updateButton();
                });
                times.appendChild(t);
            });
            pickerRoot.appendChild(times);
        }

        if (state.date && state.time) {
            var clear = el('button', 'lb-clear', ui.clear);
            clear.type = 'button';
            clear.addEventListener('click', function () {
                state.date = null;
                state.time = null;
                renderPicker();
                updateButton();
            });
            pickerRoot.appendChild(clear);
        }
    }

    function loadDays() {
        if (cfg.editing || !pickerRoot) { renderPicker(); return Promise.resolve(); }
        var token = ++state.token;
        state.loaded = false;
        renderPicker();
        var url = cfg.urls.availability + (select && select.value ? '?service_id=' + encodeURIComponent(select.value) : '');

        return fetch(url, { headers: { Accept: 'application/json' } })
            .then(function (r) { if (!r.ok) throw new Error('failed'); return r.json(); })
            .then(function (body) {
                if (token !== state.token) return;
                state.days = (body.data && body.data.days) || [];
                var keep = state.days.filter(function (d) { return d.date === state.date && d.slots.indexOf(state.time) !== -1; })[0];
                if (!keep) { state.date = null; state.time = null; }
                if (!state.date && state.days.length) state.date = state.days[0].date;
                state.loaded = true;
                renderPicker();
                updateButton();
            })
            .catch(function () {
                if (token !== state.token) return;
                state.days = [];
                state.loaded = true;
                renderPicker();
            });
    }

    /* ---------------- confirmation window ---------------- */
    function showModal(kind, data) {
        var previous = document.activeElement;
        var overlay = el('div', 'lb-overlay');
        var card = el('div', 'lb-modal');
        card.setAttribute('role', 'dialog');
        card.setAttribute('aria-modal', 'true');

        var icon = el('div', 'lb-modal-icon', '✓');
        card.appendChild(icon);
        card.appendChild(el('h3', 'lb-modal-title', kind === 'booked' ? ui.booked_title : ui.lead_title));

        if (kind === 'booked') {
            card.appendChild(el('p', 'lb-modal-text', fmt(ui.booked_text, { when: (data.date_label || longDate(data.date)) + ', ' + data.time })));
            var rows = el('div', 'lb-modal-rows');
            if (data.service) {
                var r1 = el('div', 'lb-modal-row');
                r1.appendChild(el('span', '', ui.booked_service));
                r1.appendChild(el('strong', '', data.service));
                rows.appendChild(r1);
            }
            if (data.address) {
                var r2 = el('div', 'lb-modal-row');
                r2.appendChild(el('span', '', ui.booked_address));
                r2.appendChild(el('strong', '', data.address));
                rows.appendChild(r2);
            }
            if (rows.childNodes.length) card.appendChild(rows);
        } else {
            card.appendChild(el('p', 'lb-modal-text', ui.lead_text));
        }

        var ok = el('button', 'lb-modal-btn', ui.close);
        ok.type = 'button';
        card.appendChild(ok);
        overlay.appendChild(card);
        document.body.appendChild(overlay);
        document.body.classList.add('lb-locked');
        ok.focus();

        function close() {
            overlay.remove();
            document.body.classList.remove('lb-locked');
            document.removeEventListener('keydown', onKey);
            if (previous && previous.focus) previous.focus();
        }
        function onKey(e) { if (e.key === 'Escape') close(); if (e.key === 'Tab') { e.preventDefault(); ok.focus(); } }
        ok.addEventListener('click', close);
        overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
        document.addEventListener('keydown', onKey);
    }

    /* ---------------- submit ---------------- */
    function value(name) {
        var f = form.elements.namedItem(name);
        return f ? String(f.value).trim() : '';
    }

    function messagesFrom(error) {
        var fields = error && (error.errors || (error.error && error.error.fields));
        return fields ? Object.keys(fields).map(function (k) { return [].concat(fields[k]).join(' '); }).join(' ') : null;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (cfg.editing) { say('error', ui.editing); return; }

        if (!value('client_name')) { say('error', ui.name_required); return; }
        if (value('client_phone').replace(/\D+/g, '').length < 10) { say('error', ui.phone_required); return; }

        var booking = Boolean(state.date && state.time);
        var payload = {
            client_name: value('client_name'),
            client_phone: value('client_phone'),
            service_id: value('service_id') || null,
            message: value('message')
        };
        if (booking) { payload.date = state.date; payload.time = state.time; }
        else { payload.client_email = null; payload.preferred_date = null; }

        button.disabled = true;
        say('ok', ui.sending);
        var csrf = document.querySelector('meta[name="csrf-token"]');

        fetch(booking ? cfg.urls.book : cfg.urls.lead, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : ''
            },
            body: JSON.stringify(payload)
        })
            .then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (body) {
                    if (!response.ok) { var e = new Error('failed'); e.status = response.status; e.body = body; throw e; }
                    return body;
                });
            })
            .then(function (body) {
                form.reset();
                say('ok', '');
                showModal(booking ? 'booked' : 'lead', body.data || {});
                state.date = null;
                state.time = null;
                updateButton();
                if (booking) loadDays();
            })
            .catch(function (error) {
                var text = messagesFrom(error && error.body) || cfg.failed || ui.failed;
                say('error', text);
                if (error && error.body && error.body.errors && error.body.errors.time) loadDays();
            })
            .then(function () { button.disabled = false; });
    });

    if (select) select.addEventListener('change', function () { state.date = null; state.time = null; updateButton(); loadDays(); });

    loadDays();
})();
