{{-- /help for someone who is not signed in: leave a contact, no ticket thread. --}}
<!doctype html>
<html lang="{{ app()->getLocale() }}" data-bs-theme="system">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('help.title') }} — Veloria</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f6f6f8; --surface: #fff; --text: #232333; --muted: #6f6f85;
            --line: #e6e6ee; --accent: #e400a5; --accent-text: #fff; --danger: #d92d4a;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #232333; --surface: #2b2c40; --text: #e4e4ee; --muted: #a5a5bd;
                --line: #3b3c53; --accent: #ff3fc0; --accent-text: #17171f; --danger: #ff6b82;
            }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 24px; font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg); color: var(--text); line-height: 1.5;
        }
        .card { width: 100%; max-width: 480px; background: var(--surface); border: 1px solid var(--line); border-radius: 18px; padding: 36px; }
        h1 { margin: 0 0 8px; font-size: 24px; letter-spacing: -0.01em; }
        .lead { margin: 0 0 24px; color: var(--muted); }
        label, .label { display: block; margin: 16px 0 6px; font-size: 14px; font-weight: 500; }
        input, textarea {
            width: 100%; padding: 11px 14px; font: inherit; color: var(--text); background: transparent;
            border: 1px solid var(--line); border-radius: 10px;
        }
        input:focus, textarea:focus { outline: 2px solid var(--accent); outline-offset: 1px; }
        textarea { min-height: 90px; resize: vertical; }
        .types { display: flex; gap: 8px; flex-wrap: wrap; }
        .types button {
            padding: 8px 14px; font: inherit; font-size: 14px; color: var(--text); background: transparent;
            border: 1px solid var(--line); border-radius: 999px; cursor: pointer;
        }
        .types button[aria-pressed="true"] { border-color: var(--accent); color: var(--accent); font-weight: 600; }
        .submit {
            width: 100%; margin-top: 24px; padding: 13px 20px; font: inherit; font-weight: 600;
            color: var(--accent-text); background: var(--accent); border: 0; border-radius: 10px; cursor: pointer;
        }
        .submit:disabled { opacity: .6; cursor: default; }
        .error { margin-top: 6px; color: var(--danger); font-size: 14px; }
        .hp { position: absolute; left: -9999px; height: 0; overflow: hidden; }
        .done { text-align: center; }
        .done button { margin-top: 16px; background: none; border: 0; color: var(--accent); font: inherit; cursor: pointer; }
        .foot { margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--line); font-size: 14px; color: var(--muted); text-align: center; }
        .foot a { color: var(--accent); text-decoration: none; font-weight: 500; margin-left: 6px; }
        [hidden] { display: none !important; }
        @media (max-width: 575px) { .card { padding: 26px 20px; } h1 { font-size: 20px; } }
    </style>
</head>
<body>
<main class="card">
    <form id="guest-form" novalidate>
        <h1>{{ __('help.guest.title') }}</h1>
        <p class="lead">{{ __('help.guest.subtitle') }}</p>

        <label for="g-name">{{ __('help.guest.name_label') }}</label>
        <input id="g-name" name="name" maxlength="100" autocomplete="given-name" placeholder="{{ __('help.guest.name_placeholder') }}">

        <span class="label">{{ __('help.guest.contact_label') }}</span>
        <div class="types" id="types">
            <button type="button" data-type="phone" aria-pressed="true">{{ __('help.guest.contact_phone') }}</button>
            <button type="button" data-type="email" aria-pressed="false">{{ __('help.guest.contact_email') }}</button>
            <button type="button" data-type="telegram" aria-pressed="false">{{ __('help.guest.contact_telegram') }}</button>
        </div>
        <input id="g-contact" name="contact" maxlength="120" required style="margin-top:10px" aria-label="{{ __('help.guest.contact_label') }}">
        <div class="error" id="g-error" role="alert" hidden></div>

        <label for="g-message">{{ __('help.guest.message_label') }}</label>
        <textarea id="g-message" name="message" maxlength="2000" placeholder="{{ __('help.guest.message_placeholder') }}"></textarea>

        <div class="hp" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>

        <button class="submit" type="submit" id="g-submit">{{ __('help.guest.submit') }}</button>
    </form>

    <div class="done" id="done" hidden>
        <h1>{{ __('help.guest.success_title') }}</h1>
        <p class="lead">{{ __('help.guest.success') }}</p>
        <button type="button" id="again">{{ __('help.guest.again') }}</button>
    </div>

    <div class="foot">
        {{ __('help.guest.have_account') }}
        <a href="/login">{{ __('help.guest.login') }}</a>
        <a href="/register">{{ __('help.guest.register') }}</a>
    </div>
</main>

<script>
    (function () {
        const placeholders = {
            phone: @json(__('help.guest.placeholder_phone')),
            email: @json(__('help.guest.placeholder_email')),
            telegram: @json(__('help.guest.placeholder_telegram')),
        };
        const form = document.getElementById('guest-form');
        const done = document.getElementById('done');
        const contact = document.getElementById('g-contact');
        const errorBox = document.getElementById('g-error');
        const submit = document.getElementById('g-submit');
        const labels = { submit: submit.textContent, sending: @json(__('help.guest.sending')) };
        let type = 'phone';

        function setType(next) {
            type = next;
            document.querySelectorAll('#types button').forEach(function (b) {
                b.setAttribute('aria-pressed', b.dataset.type === next ? 'true' : 'false');
            });
            contact.placeholder = placeholders[next];
            contact.type = next === 'email' ? 'email' : (next === 'phone' ? 'tel' : 'text');
            contact.autocomplete = next === 'email' ? 'email' : (next === 'phone' ? 'tel' : 'off');
        }

        function showError(text) {
            errorBox.textContent = text || '';
            errorBox.hidden = !text;
        }

        document.getElementById('types').addEventListener('click', function (e) {
            const btn = e.target.closest('button[data-type]');
            if (btn) { setType(btn.dataset.type); showError(''); }
        });

        document.getElementById('again').addEventListener('click', function () {
            form.reset();
            setType('phone');
            done.hidden = true;
            form.hidden = false;
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            showError('');

            if (!contact.value.trim()) {
                showError(@json(__('help.guest.contact_required')));
                contact.focus();
                return;
            }

            submit.disabled = true;
            submit.textContent = labels.sending;

            fetch('/api/v1/support-requests', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    name: form.name.value,
                    contact_type: type,
                    contact: contact.value,
                    message: form.message.value,
                    website: form.website.value,
                }),
            }).then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (body) {
                    if (res.ok) {
                        form.hidden = true;
                        done.hidden = false;
                        return;
                    }
                    const fields = (body.error && body.error.fields) || {};
                    const first = fields.contact && fields.contact[0];
                    showError(first || @json(__('help.guest.error')));
                });
            }).catch(function () {
                showError(@json(__('help.guest.error')));
            }).finally(function () {
                submit.disabled = false;
                submit.textContent = labels.submit;
            });
        });

        setType('phone');
    })();
</script>
</body>
</html>
