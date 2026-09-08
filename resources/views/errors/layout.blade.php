{{--
    Общая рамка для 403, 404, 419, 429, 500 и 503.

    До этого продукт не переопределял ни одну из них, и мастер, промахнувшийся
    ссылкой, попадал на стандартную страницу Laravel — по-английски, без шапки и
    без единого пути обратно, кроме кнопки браузера.
--}}
<!doctype html>
<html lang="{{ app()->getLocale() }}" data-bs-theme="system">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — Veloria</title>
    <link rel="icon" type="image/x-icon" href="/assets/img/favicon/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --err-bg: #f6f6f8;
            --err-surface: #ffffff;
            --err-text: #232333;
            --err-muted: #6f6f85;
            --err-line: #e6e6ee;
            --err-accent: #e400a5;
            --err-accent-text: #ffffff;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --err-bg: #232333;
                --err-surface: #2b2c40;
                --err-text: #e4e4ee;
                --err-muted: #a5a5bd;
                --err-line: #3b3c53;
                --err-accent: #ff3fc0;
                --err-accent-text: #17171f;
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--err-bg);
            color: var(--err-text);
            line-height: 1.6;
        }

        .err-card {
            width: 100%;
            max-width: 460px;
            background: var(--err-surface);
            border: 1px solid var(--err-line);
            border-radius: 18px;
            padding: 40px;
            text-align: center;
        }

        .err-code {
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.08em;
            color: var(--err-muted);
            margin-bottom: 12px;
        }

        h1 { margin: 0 0 12px; font-size: 24px; font-weight: 700; letter-spacing: -0.01em; }

        p { margin: 0 0 28px; color: var(--err-muted); }

        .err-actions { display: flex; flex-direction: column; gap: 10px; }

        .err-btn {
            display: block;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .err-btn-primary { background: var(--err-accent); color: var(--err-accent-text); }

        .err-btn-ghost { border-color: var(--err-line); color: var(--err-text); }

        .err-btn-ghost:hover { border-color: var(--err-accent); color: var(--err-accent); }

        @media (max-width: 575px) {
            .err-card { padding: 28px 22px; border-radius: 14px; }
            h1 { font-size: 20px; }
        }
    </style>
</head>
<body>
<div class="err-card">
    <div class="err-code">{{ $code }}</div>
    <h1>{{ $title }}</h1>
    <p>{{ $message }}</p>

    <div class="err-actions">
        <a class="err-btn err-btn-primary" href="/dashboard">{{ __('errors.to_dashboard') }}</a>
        <a class="err-btn err-btn-ghost" href="/help">{{ __('errors.to_help') }}</a>
    </div>
</div>
</body>
</html>
