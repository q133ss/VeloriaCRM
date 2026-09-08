{{--
    Пользовательское соглашение и политика конфиденциальности.

    Обе ссылки стоят в футере каждой страницы кабинета и до этого вели в 404 —
    на продукте, который принимает оплату и обрабатывает персональные данные
    клиентов. Страницы теперь существуют и оформлены как часть продукта.

    Текст документов — за юристом оператора. Он лежит в `lang/*/legal.php`
    (`terms.body` и `policy.body`, массив абзацев), меняется без правки шаблона.
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
            --legal-bg: #f6f6f8;
            --legal-surface: #ffffff;
            --legal-text: #232333;
            --legal-muted: #6f6f85;
            --legal-line: #e6e6ee;
            --legal-accent: #e400a5;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --legal-bg: #232333;
                --legal-surface: #2b2c40;
                --legal-text: #e4e4ee;
                --legal-muted: #a5a5bd;
                --legal-line: #3b3c53;
                --legal-accent: #ff3fc0;
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 40px 20px 64px;
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--legal-bg);
            color: var(--legal-text);
            line-height: 1.65;
        }

        .legal-shell { max-width: 760px; margin: 0 auto; }

        .legal-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
            color: var(--legal-muted);
            text-decoration: none;
            font-size: 14px;
        }

        .legal-back:hover { color: var(--legal-accent); }

        .legal-card {
            background: var(--legal-surface);
            border: 1px solid var(--legal-line);
            border-radius: 18px;
            padding: 40px;
        }

        h1 { margin: 0 0 8px; font-size: 28px; font-weight: 700; letter-spacing: -0.01em; }

        .legal-updated { margin: 0 0 32px; color: var(--legal-muted); font-size: 14px; }

        .legal-card p { margin: 0 0 18px; }

        .legal-card p:last-child { margin-bottom: 0; }

        .legal-contact {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--legal-line);
            color: var(--legal-muted);
            font-size: 14px;
        }

        .legal-contact a { color: var(--legal-accent); }

        @media (max-width: 575px) {
            body { padding: 24px 16px 40px; }
            .legal-card { padding: 24px; border-radius: 14px; }
            h1 { font-size: 22px; }
        }
    </style>
</head>
<body>
<div class="legal-shell">
    <a class="legal-back" href="/">&larr; {{ __('legal.back') }}</a>

    <div class="legal-card">
        <h1>{{ $title }}</h1>
        <p class="legal-updated">{{ __('legal.updated', ['date' => $updatedAt]) }}</p>

        @foreach ($body as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach

        <div class="legal-contact">
            {!! __('legal.contact', ['email' => '<a href="mailto:' . $contactEmail . '">' . $contactEmail . '</a>']) !!}
        </div>
    </div>
</div>
</body>
</html>
