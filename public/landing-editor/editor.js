/*
 * Click-to-edit for landing pages. Template-agnostic: it only looks for
 * [data-lf-key] elements that the Blade components under components/landing
 * emit for the page owner. See docs/landing-templates.md.
 */
(function () {
    'use strict';

    var cfg = window.LF;
    if (!cfg) return;

    var t = cfg.i18n || {};
    var ui = t.ui || {};
    var errors = t.errors || {};
    var TEXT_KINDS = ['text', 'textarea', 'phone', 'list'];

    var active = null;      // element being edited
    var pending = null;     // last failed save, for retry
    var statusTimer = null;

    /* ---------- helpers ---------- */
    function cookie(name) {
        var m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : null;
    }

    function headers(json) {
        var h = { Accept: 'application/json' };
        var token = cookie('token');
        if (token) h.Authorization = 'Bearer ' + token;
        if (json) h['Content-Type'] = 'application/json';
        return h;
    }

    function all(key, index) {
        var sel = '[data-lf-key="' + key + '"]';
        if (index != null && index !== '') sel += '[data-lf-index="' + index + '"]';
        return Array.prototype.slice.call(document.querySelectorAll(sel));
    }

    function read(el) {
        var kind = el.getAttribute('data-lf-kind');
        var value = kind === 'textarea' ? el.innerText : el.textContent;
        return value.replace(/ /g, ' ').trim();
    }

    function write(el, value) {
        el.textContent = value;
    }

    function firstError(body) {
        var f = body && body.error && body.error.fields;
        if (f) return Object.keys(f).map(function (k) { return [].concat(f[k]).join(' '); })[0];
        return (body && body.message) || null;
    }

    /* ---------- bar ---------- */
    var bar = document.createElement('div');
    bar.className = 'lf-bar';
    bar.innerHTML =
        '<span class="lf-bar-text"></span>' +
        '<button type="button" class="lf-bar-status" hidden></button>' +
        '<a class="lf-btn lf-btn-ghost" target="_blank" rel="noopener"></a>' +
        '<a class="lf-btn lf-btn-main"></a>';
    var barText = bar.querySelector('.lf-bar-text');
    var barStatus = bar.querySelector('.lf-bar-status');
    var viewLink = bar.querySelector('.lf-btn-ghost');
    var doneLink = bar.querySelector('.lf-btn-main');
    barText.textContent = ui.hint || '';
    viewLink.textContent = ui.view || '';
    viewLink.href = cfg.publicUrl;
    doneLink.textContent = ui.done || 'OK';
    doneLink.href = cfg.doneUrl;
    document.body.appendChild(bar);

    function status(kind, text) {
        clearTimeout(statusTimer);
        barStatus.hidden = false;
        barStatus.className = 'lf-bar-status is-' + kind;
        barStatus.textContent = text;
        barText.hidden = true;
        if (kind === 'ok') {
            statusTimer = setTimeout(function () { barStatus.hidden = true; barText.hidden = false; }, 1800);
        }
    }

    barStatus.addEventListener('click', function () {
        if (pending) { var p = pending; pending = null; save(p.key, p.value, p.el, p.index, p.original); }
    });

    function toast(message) {
        var el = document.createElement('div');
        el.className = 'lf-toast';
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 3500);
    }

    /* ---------- text editing ---------- */
    var plaintext = (function () {
        var probe = document.createElement('div');
        try { probe.contentEditable = 'plaintext-only'; } catch (e) { return false; }
        return probe.contentEditable === 'plaintext-only';
    })();

    function startEdit(el) {
        if (active === el) return;
        if (active) active.blur();
        active = el;
        el.__original = read(el);
        el.setAttribute('contenteditable', plaintext ? 'plaintext-only' : 'true');
        el.spellcheck = true;
        el.focus();
        var range = document.createRange();
        range.selectNodeContents(el);
        range.collapse(false);
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
    }

    function finishEdit(el, cancel) {
        el.removeAttribute('contenteditable');
        el.classList.remove('lf-error');
        if (active === el) active = null;

        var original = el.__original;
        var kind = el.getAttribute('data-lf-kind');
        var key = el.getAttribute('data-lf-key');
        var index = el.getAttribute('data-lf-index');
        var value = read(el);

        if (cancel || value === original) { write(el, original); return; }
        // A list item cannot be left blank yet: removing items comes later.
        if (kind === 'list' && value === '') { write(el, original); return; }

        save(key, value, el, index, original);
    }

    function collect(key, el, index, value) {
        // A list is stored as one text, one item per line, in page order.
        var kind = el.getAttribute('data-lf-kind');
        if (kind !== 'list') return value;
        return all(key).map(function (item) {
            return item === el || item.getAttribute('data-lf-index') === String(index) ? value : read(item);
        }).filter(Boolean).join('\n');
    }

    function save(key, value, el, index, original) {
        var kind = el.getAttribute('data-lf-kind');
        var payload = collect(key, el, index, value);
        status('saving', ui.saving || '…');

        fetch(cfg.contentUrl, {
            method: 'PATCH',
            headers: headers(true),
            body: JSON.stringify({ changes: [{ key: key, value: payload }] })
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (body) {
                return { ok: response.ok, status: response.status, body: body };
            });
        }).then(function (res) {
            if (res.ok) {
                var stored = (res.body.data && res.body.data.values && res.body.data.values[key]);
                if (kind === 'list') {
                    // Keep the edited item and every copy of it in step.
                    all(key, index).forEach(function (n) { write(n, value); });
                } else {
                    all(key).forEach(function (n) { write(n, stored != null && stored !== '' ? stored : value); });
                }
                status('ok', (ui.saved || 'Saved') + ' ✓');
                return;
            }
            // Rejected by validation: put the old text back and say why.
            write(el, original);
            barStatus.hidden = true; barText.hidden = false;
            el.classList.add('lf-error');
            setTimeout(function () { el.classList.remove('lf-error'); }, 1500);
            toast(firstError(res.body) || errors.invalid || 'Error');
        }).catch(function () {
            pending = { key: key, value: value, el: el, index: index, original: original };
            status('error', ui.retry || 'Retry');
        });
    }

    /* ---------- images ---------- */
    var picker = document.createElement('input');
    picker.type = 'file';
    picker.accept = 'image/jpeg,image/png,image/webp';
    picker.hidden = true;
    document.body.appendChild(picker);
    var pickerKey = null;
    var pop = null;

    function closePop() { if (pop) { pop.remove(); pop = null; } }

    function openImagePop(img) {
        closePop();
        var key = img.getAttribute('data-lf-key');
        pop = document.createElement('div');
        pop.className = 'lf-pop';
        var replace = document.createElement('button');
        replace.type = 'button';
        replace.className = 'lf-main';
        replace.textContent = ui.photo_replace || 'Replace';
        replace.addEventListener('click', function () { pickerKey = key; closePop(); picker.value = ''; picker.click(); });
        pop.appendChild(replace);

        if (img.getAttribute('data-lf-custom') === '1') {
            var reset = document.createElement('button');
            reset.type = 'button';
            reset.textContent = ui.photo_reset || 'Reset';
            reset.addEventListener('click', function () { closePop(); resetImage(key); });
            pop.appendChild(reset);
        }

        var r = img.getBoundingClientRect();
        pop.style.top = (window.scrollY + Math.max(8, r.top) + 12) + 'px';
        pop.style.left = (window.scrollX + Math.max(8, r.left) + 12) + 'px';
        document.body.appendChild(pop);
    }

    function setBusy(key, busy) {
        all(key).forEach(function (n) { n.classList.toggle('lf-busy', busy); });
    }

    function applyImage(key, url, custom) {
        all(key).forEach(function (n) {
            var cssVar = n.getAttribute('data-lf-bgvar');
            if (n.tagName === 'IMG') n.src = url;
            else if (cssVar) n.style.setProperty(cssVar, 'url("' + url + '")');
            else n.style.backgroundImage = 'url("' + url + '")';
            n.setAttribute('data-lf-custom', custom ? '1' : '0');
        });
    }

    picker.addEventListener('change', function () {
        var file = picker.files && picker.files[0];
        if (!file || !pickerKey) return;
        var key = pickerKey;
        var form = new FormData();
        form.append('key', key);
        form.append('file', file);
        setBusy(key, true);
        status('saving', ui.photo_uploading || '…');

        fetch(cfg.imagesUrl, { method: 'POST', headers: headers(false), body: form })
            .then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (body) { return { ok: response.ok, status: response.status, body: body }; });
            })
            .then(function (res) {
                setBusy(key, false);
                if (res.ok && res.body.data) {
                    applyImage(key, res.body.data.url, true);
                    status('ok', (ui.saved || 'Saved') + ' ✓');
                } else {
                    barStatus.hidden = true; barText.hidden = false;
                    toast(firstError(res.body) || (res.status === 413 ? errors.image_size : errors.image_failed) || 'Error');
                }
            })
            .catch(function () {
                setBusy(key, false);
                barStatus.hidden = true; barText.hidden = false;
                toast(errors.image_failed || 'Error');
            });
    });

    function resetImage(key) {
        setBusy(key, true);
        fetch(cfg.imagesUrl + '/' + encodeURIComponent(key), { method: 'DELETE', headers: headers(false) })
            .then(function (response) { return response.json().catch(function () { return {}; }).then(function (b) { return { ok: response.ok, body: b }; }); })
            .then(function (res) {
                setBusy(key, false);
                var stock = res.ok && res.body.data ? (res.body.data.url || (all(key)[0] && all(key)[0].getAttribute('data-lf-stock'))) : null;
                if (stock) { applyImage(key, stock, false); status('ok', (ui.saved || 'Saved') + ' ✓'); }
                else toast(firstError(res.body) || errors.image_failed || 'Error');
            })
            .catch(function () { setBusy(key, false); toast(errors.image_failed || 'Error'); });
    }

    /* ---------- wiring ---------- */
    function init() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-lf-key]'), function (el) {
            var kind = el.getAttribute('data-lf-kind');
            if (kind === 'image') el.classList.add('lf-image');
            else if (TEXT_KINDS.indexOf(kind) !== -1) el.classList.add('lf-editable');
        });
    }

    /** The smallest editable photo whose box contains the point, or null. */
    function imageAt(x, y) {
        var best = null, bestArea = Infinity;
        Array.prototype.forEach.call(document.querySelectorAll('[data-lf-kind="image"]'), function (img) {
            var r = img.getBoundingClientRect();
            if (r.width < 8 || r.height < 8 || x < r.left || x > r.right || y < r.top || y > r.bottom) return;
            var area = r.width * r.height;
            if (area < bestArea) { best = img; bestArea = area; }
        });
        return best;
    }

    // Outline the photo under the pointer even when an overlay sits on top of it.
    var hovered = null, hoverFrame = 0;
    document.addEventListener('mousemove', function (e) {
        if (hoverFrame) return;
        var x = e.clientX, y = e.clientY, t = e.target;
        hoverFrame = requestAnimationFrame(function () {
            hoverFrame = 0;
            var next = t instanceof Element && !t.closest('[data-lf-key], .lf-bar, .lf-pop') ? imageAt(x, y) : null;
            if (next === hovered) return;
            if (hovered) hovered.classList.remove('lf-hover');
            hovered = next;
            if (hovered) hovered.classList.add('lf-hover');
        });
    });

    // Capture phase, so links, buttons and carousels never react to an edit click.
    document.addEventListener('click', function (e) {
        var target = e.target;
        if (!(target instanceof Element)) return;

        if (target.closest('.lf-bar, .lf-pop')) return;

        // The nearest editable element decides: a heading inside a photo background is text.
        var el = target.closest('[data-lf-key]');

        // Templates lay overlays over photos (hover tints, gradients, caption boxes), so the click
        // lands on a sibling of the <img>. Fall back to the photo under the pointer.
        if (!el) el = imageAt(e.clientX, e.clientY);
        if (el && el.getAttribute('data-lf-kind') === 'image') { e.preventDefault(); e.stopPropagation(); openImagePop(el); return; }
        closePop();

        if (el && TEXT_KINDS.indexOf(el.getAttribute('data-lf-kind')) !== -1) {
            e.preventDefault();
            e.stopPropagation();
            if (el.getAttribute('contenteditable') == null) startEdit(el);
        }
    }, true);

    document.addEventListener('focusout', function (e) {
        var el = e.target;
        if (el && el.getAttribute && el.getAttribute('contenteditable') != null && el.hasAttribute('data-lf-key')) {
            finishEdit(el, el.__cancel === true);
            el.__cancel = false;
        }
    });

    document.addEventListener('keydown', function (e) {
        var el = e.target;
        if (!el || !el.hasAttribute || !el.hasAttribute('data-lf-key') || el.getAttribute('contenteditable') == null) return;
        var multi = el.getAttribute('data-lf-kind') === 'textarea';

        if (e.key === 'Escape') { el.__cancel = true; el.blur(); e.preventDefault(); }
        else if (e.key === 'Enter' && (!multi || e.ctrlKey || e.metaKey)) { e.preventDefault(); el.blur(); }
    });

    document.addEventListener('paste', function (e) {
        var el = e.target;
        if (plaintext || !el || !el.hasAttribute || !el.hasAttribute('data-lf-key')) return;
        e.preventDefault();
        var text = (e.clipboardData || window.clipboardData).getData('text');
        document.execCommand('insertText', false, text);
    });

    var max = null;
    document.addEventListener('input', function (e) {
        var el = e.target;
        if (!el || !el.getAttribute || el.getAttribute('contenteditable') == null) return;
        max = parseInt(el.getAttribute('data-lf-max'), 10);
        if (max && el.textContent.length > max) {
            el.textContent = el.textContent.slice(0, max);
            var r = document.createRange();
            r.selectNodeContents(el);
            r.collapse(false);
            var s = window.getSelection();
            s.removeAllRanges();
            s.addRange(r);
        }
    });

    window.addEventListener('beforeunload', function (e) {
        if (pending) { e.preventDefault(); e.returnValue = ''; }
    });

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
