<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ __('prepayment.return.title_waiting') }} — Veloria</title>
    @include('partials.favicon')
    <style>
        :root {
            --pr-bg: #f6f6f8;
            --pr-surface: #ffffff;
            --pr-text: #232333;
            --pr-muted: #6f6f85;
            --pr-line: #e6e6ee;
            --pr-ok: #1f9d55;
            --pr-warn: #c2410c;
            --pr-accent: #e400a5;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --pr-bg: #232333;
                --pr-surface: #2b2c40;
                --pr-text: #e4e4ee;
                --pr-muted: #a5a5bd;
                --pr-line: #3b3c53;
                --pr-ok: #4ade80;
                --pr-warn: #fb923c;
                --pr-accent: #ff3fc0;
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 16px;
            background: var(--pr-bg);
            color: var(--pr-text);
            font: 16px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }

        .card {
            width: 100%;
            max-width: 420px;
            padding: 32px 24px;
            text-align: center;
            background: var(--pr-surface);
            border: 1px solid var(--pr-line);
            border-radius: 16px;
        }

        h1 { margin: 0 0 8px; font-size: 22px; }
        p { margin: 0; color: var(--pr-muted); }
        .when { margin-top: 16px; color: var(--pr-text); font-weight: 600; }
        .dot { width: 14px; height: 14px; margin: 0 auto 20px; border-radius: 50%; background: var(--pr-accent); animation: pulse 1.2s ease-in-out infinite; }
        .card[data-state="paid"] .dot { background: var(--pr-ok); animation: none; }
        .card[data-state="expired"] .dot, .card[data-state="refunded"] .dot, .card[data-state="unknown"] .dot { background: var(--pr-warn); animation: none; }
        .app { display: none; margin-top: 24px; padding: 12px 20px; border-radius: 10px; background: var(--pr-accent); color: #fff; text-decoration: none; font-weight: 600; }

        @keyframes pulse { 50% { opacity: .35; } }
        @media (prefers-reduced-motion: reduce) { .dot { animation: none; } }
    </style>
</head>
<body>
    <main class="card" id="card" data-state="awaiting" aria-live="polite">
        <div class="dot" aria-hidden="true"></div>
        <h1 id="title">{{ __('prepayment.return.title_waiting') }}</h1>
        <p id="text">{{ __('prepayment.return.text_waiting') }}</p>
        <p class="when" id="when" hidden></p>
        <a class="app" id="app" href="veloriaclient://payment-return?token={{ $token }}">{{ __('prepayment.return.open_app') }}</a>
    </main>

    <script>
        (function () {
            var statusUrl = @json($statusUrl);
            var copy = @json(__('prepayment.return'));
            var card = document.getElementById('card');
            var tries = 0;

            function money(value) {
                return new Intl.NumberFormat(document.documentElement.lang || 'ru').format(value) + ' ₽';
            }

            function show(data) {
                var state = data.state || 'unknown';
                card.dataset.state = state;
                document.getElementById('title').textContent = copy['title_' + (state === 'awaiting' ? 'waiting' : state)] || copy.title_unknown;
                document.getElementById('text').textContent = (copy['text_' + (state === 'awaiting' ? 'waiting' : state)] || copy.text_unknown)
                    .replace(':amount', money(data.amount || 0));

                var when = document.getElementById('when');
                if (state === 'paid' && data.date_label) {
                    when.textContent = (data.service ? data.service + ' · ' : '') + copy.when.replace(':date', data.date_label).replace(':time', data.time || '');
                    when.hidden = false;
                } else {
                    when.hidden = true;
                }
            }

            function poll() {
                tries += 1;

                fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
                    .then(function (response) { return response.json(); })
                    .then(function (body) {
                        var data = body.data || {};
                        show(data);

                        // The money may still be on its way; keep asking for as long as the booking is held.
                        if (data.state === 'awaiting' && tries < 120) {
                            setTimeout(poll, 3000);
                        }
                    })
                    .catch(function () {
                        if (tries < 120) { setTimeout(poll, 5000); }
                    });
            }

            // Only phones can open the app link; elsewhere it would lead nowhere.
            if (/android|iphone|ipad/i.test(navigator.userAgent)) {
                document.getElementById('app').style.display = 'inline-block';
            }

            poll();
        })();
    </script>
</body>
</html>
