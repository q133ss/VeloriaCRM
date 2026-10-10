@php
    $locale = app()->getLocale();
    $isRu = $locale === 'ru';
    $isGuest = empty($isAuthenticated);

    // Цены приходят из HomeController — из базы, а не из текста страницы.
    $planPrices = $planPrices ?? ['lite' => 0, 'pro' => 990, 'elite' => 1990];
    $templates = $templates ?? [];
    $templateCount = $templateCount ?? count($templates);
    $videoReviews = $videoReviews ?? [];
    $androidAppUrl = $androidAppUrl ?? null;

    $exampleSlug = $templates[0]['slug'] ?? \App\Services\Landing\TemplateRegistry::DEFAULT_LAYOUT;
    $startUrl = $isGuest ? url('/register') : url('/dashboard');
    $startLabel = $isGuest ? __('landing.hero.cta_primary') : __('menu.dashboard');

    $formatPrice = static function (int $value): string {
        return $value > 0
            ? number_format($value, 0, '', ' ') . ' ' . __('subscription.currency')
            : __('landing.pricing.free');
    };

    // Иконки держим здесь, а не файлами: они по 200 байт, и лишний запрос
    // к серверу на холодном трафике дороже, чем эта разметка.
    $icons = [
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'shield' => '<path d="M12 3 5 6v6c0 4.2 2.9 7.6 7 9 4.1-1.4 7-4.8 7-9V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
        'send' => '<path d="M21 4 3 11l7 3 3 7 8-17Z"/><path d="M10 14l4-4"/>',
        'chat' => '<path d="M4 5h16v11H9l-5 4V5Z"/><path d="M8 9.5h8M8 12.5h5"/>',
        'calendar-x' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4M10 13.5l4 4M14 13.5l-4 4"/>',
        'wave' => '<path d="M3 17c3 0 3-4 6-4s3 4 6 4 3-4 6-4"/><path d="M3 9c3 0 3-4 6-4s3 4 6 4 3-4 6-4"/>',
        'puzzle' => '<path d="M10 4a2 2 0 1 1 4 0v2h4v4h-2a2 2 0 1 0 0 4h2v4H6v-4h2a2 2 0 1 0 0-4H6V6h4V4Z"/>',
        'users' => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19c.7-3.2 2.9-5 5.5-5s4.8 1.8 5.5 5"/><circle cx="17" cy="9" r="2.4"/><path d="M15.5 14.3c2.3.1 4.2 1.6 4.8 4.7"/>',
        'auto' => '<path d="M20 12a8 8 0 1 1-2.3-5.7"/><path d="M20 4v4h-4"/><path d="m9 12 2 2 4-4"/>',
        'gift' => '<rect x="4" y="9" width="16" height="11" rx="2"/><path d="M3 9h18M12 9v11M12 9c-1.5-3.5-5-4-5-1.5S10 9 12 9Zm0 0c1.5-3.5 5-4 5-1.5S14 9 12 9Z"/>',
        'star' => '<path d="m12 3.5 2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8-4.3-4.1 5.9-.9L12 3.5Z"/>',
        'android' => '<path d="M7 10h10v7a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2v-7Z"/><path d="M7 9a5 5 0 0 1 10 0M9.5 6 8 4M14.5 6 16 4M4.5 11v5M19.5 11v5"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'play' => '<path d="M8 5.5v13l10.5-6.5L8 5.5Z" fill="currentColor"/>',
        'bell' => '<path d="M6 16V11a6 6 0 1 1 12 0v5l1.5 2h-15L6 16Z"/><path d="M10 20.5a2 2 0 0 0 4 0"/>',
    ];

    $icon = static function (string $name, string $class = 'icon') use ($icons): string {
        return '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ($icons[$name] ?? '') . '</svg>';
    };

    $faqItems = __('landing.faq.items');
    $structuredData = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'Veloria',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web, Android',
            'description' => __('landing.meta.description'),
            'url' => url('/'),
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'RUB'],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($faqItems)->map(fn ($item) => [
                '@type' => 'Question',
                'name' => $item['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
            ])->all(),
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ __('landing.meta.title') }}</title>
    <meta name="description" content="{{ __('landing.meta.description') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ __('landing.meta.title') }}">
    <meta property="og:description" content="{{ __('landing.meta.description') }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ asset('images/home/og.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">

    @include('partials.favicon')

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|lora:600,600i&display=swap" rel="stylesheet">
    <link rel="preload" as="image" href="/images/home/site-pretty.webp">

    <script type="application/ld+json">@json($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)</script>

    <style>
        :root {
            color-scheme: light;

            /* Фуксия — фирменный цвет кабинета и приложения (Client Mobile App/src/theme/theme.ts),
               её держим на кнопках. Всё вокруг — тёплое и приглушённое, чтобы цвет
               работал акцентом, а не фоном. */
            --brand: #ff00fc;
            --brand-press: #d700d4;
            --accent: #c2007f;
            --brand-soft: #fde3f3;
            --tint: #fcf1f4;
            --bg: #fffaf8;
            --surface: #ffffff;
            --border: #f0dbe5;
            --line: #eee4e8;
            --text: #1d1418;
            --muted: #6b5d66;
            --faint: #9c8e97;
            --ink: #1d1418;
            --success: #1e9a6a;
            --success-bg: #e5f6ee;
            --warn: #b4521f;
            --warn-bg: #fff0e5;

            --radius: 22px;
            --radius-sm: 14px;
            --shadow: 0 24px 60px rgba(120, 20, 80, .12);
            --shadow-sm: 0 8px 22px rgba(29, 20, 24, .07);
            --wrap: 1140px;

            --serif: "Lora", Georgia, "Times New Roman", serif;
            font-family: "Inter", system-ui, -apple-system, "Segoe UI", sans-serif;
            font-size: 16px;
            line-height: 1.6;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        h1, h2, h3 { line-height: 1.15; margin: 0; }
        h1, h2 { font-family: var(--serif); font-weight: 600; letter-spacing: -.01em; }
        h3 { letter-spacing: -.01em; }
        p { margin: 0; }
        img { max-width: 100%; display: block; }
        a { color: inherit; }

        .wrap { max-width: var(--wrap); margin: 0 auto; padding: 0 24px; }

        .skip {
            position: absolute; left: -9999px; top: 0; z-index: 100;
            background: var(--text); color: #fff; padding: 12px 18px; border-radius: 0 0 12px 0;
            text-decoration: none;
        }
        .skip:focus { left: 0; }

        .icon { width: 22px; height: 22px; flex: none; }
        .icon--sm { width: 18px; height: 18px; flex: none; }

        /* ---------- Шапка ---------- */

        .site-header {
            position: sticky; top: 0; z-index: 40;
            background: rgba(255, 250, 248, .88);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
        }
        .site-header .wrap { display: flex; align-items: center; gap: 20px; min-height: 68px; }

        .brand {
            display: inline-flex; align-items: center; gap: 9px;
            font-weight: 700; font-size: 1.12rem; letter-spacing: -.02em;
            text-decoration: none; flex: none;
        }
        .brand img { width: 30px; height: auto; }

        .site-nav { display: flex; gap: 24px; margin-left: 12px; }
        .site-nav a {
            text-decoration: none; color: var(--muted); font-size: .93rem; font-weight: 500;
            padding: 6px 0; border-bottom: 2px solid transparent;
        }
        .site-nav a:hover { color: var(--text); border-bottom-color: var(--brand); }

        .header-actions { display: flex; align-items: center; gap: 10px; margin-left: auto; }

        .lang { margin: 0; }
        .lang button {
            font: inherit; font-size: .8rem; font-weight: 700; letter-spacing: .04em;
            color: var(--muted); background: none; border: 1px solid var(--line);
            border-radius: 999px; padding: 7px 12px; cursor: pointer;
        }
        .lang button:hover { color: var(--accent); border-color: var(--border); background: var(--tint); }

        .only-narrow { display: none; }

        /* ---------- Кнопки ---------- */

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            border-radius: 999px; padding: 13px 24px;
            font-weight: 600; font-size: .96rem; text-decoration: none;
            border: 1px solid transparent; cursor: pointer; font-family: inherit;
            transition: transform .18s ease, background-color .18s ease, box-shadow .18s ease, color .18s ease, border-color .18s ease;
            white-space: nowrap;
        }
        .btn--primary { background: var(--brand); color: #fff; box-shadow: 0 10px 24px rgba(215, 0, 160, .22); }
        .btn--primary:hover { background: var(--brand-press); transform: translateY(-1px); }
        .btn--ghost { border-color: var(--border); color: var(--text); background: var(--surface); }
        .btn--ghost:hover { border-color: var(--brand); color: var(--accent); }
        .btn--dark { background: var(--ink); color: #fff; }
        .btn--dark:hover { background: #3a2a31; transform: translateY(-1px); }
        .btn--danger { border-color: #f2c8cf; background: #fff5f7; color: #bb2d3b; }
        .btn--danger:hover { border-color: #bb2d3b; }
        .btn--sm { padding: 9px 16px; font-size: .88rem; }
        .btn--lg { padding: 15px 26px; font-size: 1.02rem; }
        .btn--block { width: 100%; }
        .btn--light { background: #fff; color: var(--accent); }
        .btn--light:hover { background: var(--tint); transform: translateY(-1px); }

        /* ---------- Секции ---------- */

        .section { padding: 84px 0; }
        .section--tint { background: var(--tint); }
        .section--white { background: var(--surface); }
        .section--tight { padding: 72px 0; }

        .section-head { max-width: 720px; margin-bottom: 44px; }
        .section-head--center { margin-left: auto; margin-right: auto; text-align: center; }

        .eyebrow {
            display: inline-block; font-size: .78rem; font-weight: 700;
            letter-spacing: .1em; text-transform: uppercase;
            color: var(--accent); margin-bottom: 14px;
        }
        .section-head h2 { font-size: clamp(1.8rem, 3.4vw, 2.6rem); }
        .section-head p { margin-top: 14px; color: var(--muted); font-size: 1.06rem; }

        .card {
            background: var(--surface); border: 1px solid var(--line);
            border-radius: var(--radius); padding: 26px;
        }
        .card h3 { font-size: 1.08rem; margin-bottom: 8px; }
        .card p { color: var(--muted); font-size: .95rem; }

        .card-icon {
            width: 46px; height: 46px; border-radius: 14px;
            display: grid; place-items: center; margin-bottom: 16px;
            background: var(--brand-soft); color: var(--accent);
        }

        .grid { display: grid; gap: 20px; }
        .grid--4 { grid-template-columns: repeat(4, 1fr); }
        .grid--3 { grid-template-columns: repeat(3, 1fr); }
        .grid--2 { grid-template-columns: repeat(2, 1fr); }

        /* ---------- Герой ---------- */

        .hero {
            padding: 64px 0 96px;
            background:
                radial-gradient(800px 460px at 92% 10%, rgba(255, 0, 252, .10), transparent 65%),
                radial-gradient(600px 380px at -8% 0%, rgba(255, 170, 200, .18), transparent 60%);
        }
        .hero .wrap { display: grid; gap: 48px; grid-template-columns: 1.15fr .95fr; align-items: center; }

        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--surface); border: 1px solid var(--border);
            color: var(--accent); font-weight: 600; font-size: .86rem;
            border-radius: 999px; padding: 6px 14px 6px 8px; margin-bottom: 22px;
        }
        .hero-badge::before {
            content: ""; width: 8px; height: 8px; border-radius: 50%;
            background: var(--brand); box-shadow: 0 0 0 4px var(--brand-soft); margin-left: 4px;
        }
        .hero h1 { font-size: clamp(2.6rem, 5.6vw, 4.1rem); line-height: 1.04; }
        .hero h1 .accent { color: var(--accent); font-style: italic; }
        .hero-lead { margin-top: 22px; color: var(--muted); font-size: 1.14rem; max-width: 31em; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 32px; }
        .hero-note { margin-top: 18px; color: var(--faint); font-size: .88rem; }

        /* Визуал: сайт мастера в окне браузера, поверх — приложение и уведомление о записи */
        .hero-visual { position: relative; padding: 18px 70px 40px 0; }
        .browser {
            background: var(--surface); border-radius: 18px; overflow: hidden;
            border: 1px solid var(--line); box-shadow: var(--shadow);
        }
        .browser-bar {
            display: flex; align-items: center; gap: 6px; padding: 10px 14px;
            background: #f7f1f3; border-bottom: 1px solid var(--line);
        }
        .browser-bar i { width: 9px; height: 9px; border-radius: 50%; background: #e3d5db; }
        .browser-url {
            margin-left: 10px; flex: 1; min-width: 0;
            background: var(--surface); border-radius: 999px; padding: 4px 12px;
            font-size: .74rem; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .browser img { width: 100%; height: auto; aspect-ratio: 1280 / 820; object-fit: cover; }

        .hero-phone { position: absolute; right: 0; bottom: 0; width: 34%; max-width: 200px; }

        .notify {
            position: absolute; left: -26px; top: 46%;
            display: flex; gap: 12px; align-items: center;
            background: rgba(255, 255, 255, .96); backdrop-filter: blur(8px);
            border: 1px solid var(--line); border-radius: 18px;
            box-shadow: var(--shadow); padding: 12px 16px 12px 12px; max-width: 290px;
            animation: notify-in .7s .5s ease both;
        }
        .notify-icon {
            width: 40px; height: 40px; border-radius: 12px; flex: none;
            display: grid; place-items: center; background: var(--brand); color: #fff;
        }
        .notify-label { font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--accent); }
        .notify strong { display: block; font-size: .9rem; line-height: 1.3; }
        .notify span.time { font-size: .8rem; color: var(--muted); }
        @keyframes notify-in { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }

        /* ---------- Телефон со скриншотом ---------- */

        .device {
            border: 7px solid var(--ink); border-radius: 32px; overflow: hidden;
            background: var(--ink); box-shadow: var(--shadow);
        }
        .device img { width: 100%; height: auto; aspect-ratio: 560 / 1212; object-fit: cover; object-position: top; }

        /* ---------- Боли ---------- */

        .pain { padding: 24px; }
        .pain h3 { font-size: 1.04rem; }
        .pain-answer {
            margin-top: 28px; padding: 22px 26px; border-radius: var(--radius);
            background: var(--ink); color: #fff; font-size: 1.06rem;
            display: flex; gap: 16px; align-items: center;
        }
        .pain-answer .icon { color: var(--brand); width: 26px; height: 26px; }

        /* ---------- Шаги ---------- */

        .step { position: relative; }
        .step-num {
            width: 44px; height: 44px; border-radius: 50%;
            display: grid; place-items: center; margin-bottom: 18px;
            font-family: var(--serif); font-size: 1.3rem; font-weight: 600;
            background: var(--brand); color: #fff;
        }

        /* ---------- Галерея сайтов ---------- */

        .gallery { display: grid; gap: 22px; grid-template-columns: repeat(3, 1fr); }
        .tpl {
            display: block; text-decoration: none; background: var(--surface);
            border: 1px solid var(--line); border-radius: var(--radius); overflow: hidden;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .tpl:hover { transform: translateY(-3px); box-shadow: var(--shadow); border-color: var(--border); }
        .tpl img { width: 100%; height: auto; aspect-ratio: 16 / 10; object-fit: cover; object-position: top; background: var(--tint); }
        .tpl-meta { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 14px 18px; }
        .tpl-meta strong { font-size: .98rem; }
        .tpl-meta span { display: inline-flex; align-items: center; gap: 4px; color: var(--accent); font-size: .86rem; font-weight: 600; white-space: nowrap; }
        .gallery-foot { margin-top: 30px; display: flex; flex-wrap: wrap; gap: 14px 22px; align-items: center; }
        .gallery-foot p { color: var(--faint); font-size: .9rem; }

        /* ---------- Приложение ---------- */

        .app-grid { display: grid; gap: 56px; grid-template-columns: .9fr 1.1fr; align-items: center; }
        .checks { list-style: none; margin: 26px 0 30px; padding: 0; display: grid; gap: 12px; }
        .checks li { position: relative; padding-left: 32px; color: var(--text); }
        .checks li::before {
            content: ""; position: absolute; left: 0; top: .15em;
            width: 22px; height: 22px; border-radius: 50%; background: var(--brand-soft);
        }
        .checks li::after {
            content: ""; position: absolute; left: 7px; top: .48em;
            width: 8px; height: 4.5px; border-left: 2px solid var(--accent);
            border-bottom: 2px solid var(--accent); transform: rotate(-45deg);
        }
        .app-note { margin-top: 14px; color: var(--muted); font-size: .9rem; max-width: 30em; }

        .screens { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; align-items: end; }
        .screens figure { margin: 0; }
        .screens figure:nth-child(2) { transform: translateY(-28px); }
        .screens figcaption { text-align: center; margin-top: 12px; font-size: .84rem; color: var(--muted); font-weight: 500; }

        /* ---------- Бот и кабинет ---------- */

        .duo { display: grid; gap: 22px; grid-template-columns: 1fr 1fr; }
        .duo .card { padding: 30px; display: flex; flex-direction: column; }
        .duo .card > p { margin-bottom: 22px; }

        .tg {
            margin-top: auto; background: #eef3f8; border-radius: var(--radius-sm);
            padding: 16px; display: grid; gap: 8px;
        }
        .tg-msg {
            max-width: 82%; padding: 8px 12px; border-radius: 14px; font-size: .88rem; line-height: 1.4;
            background: #fff; justify-self: start; box-shadow: 0 1px 1px rgba(0,0,0,.05);
        }
        .tg-msg--me { justify-self: end; background: #dcf3c8; }

        .mini {
            margin-top: auto; border: 1px solid var(--line); border-radius: var(--radius-sm);
            padding: 16px; background: var(--bg);
        }
        .mini-head { font-size: .8rem; font-weight: 600; color: var(--muted); margin-bottom: 10px; }
        .mini-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
        .mini-list li {
            display: grid; grid-template-columns: auto 1fr auto; gap: 12px; align-items: center;
            background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 9px 12px;
        }
        .mini-time { font-weight: 700; font-size: .88rem; font-variant-numeric: tabular-nums; }
        .mini-body { min-width: 0; display: flex; flex-direction: column; }
        .mini-client { font-weight: 600; font-size: .86rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .mini-service { color: var(--faint); font-size: .78rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chip { font-size: .72rem; font-weight: 600; border-radius: 999px; padding: 3px 9px; white-space: nowrap; }
        .chip--ok { background: var(--success-bg); color: var(--success); }
        .chip--risk { background: var(--warn-bg); color: var(--warn); }
        .mini-due {
            margin-top: 10px; display: flex; gap: 12px; align-items: center; justify-content: space-between;
            background: var(--brand-soft); border-radius: 12px; padding: 10px 12px;
        }
        .mini-due small { display: block; font-size: .7rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--accent); }
        .mini-due span { font-size: .84rem; }
        .mini-due b { font-size: .8rem; background: var(--brand); color: #fff; border-radius: 999px; padding: 6px 12px; white-space: nowrap; }

        /* ---------- Видео-отзывы ---------- */

        .reviews { display: grid; gap: 20px; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); }
        .review { margin: 0; }
        .review-frame {
            position: relative; aspect-ratio: 9 / 16; border-radius: var(--radius); overflow: hidden;
            background: linear-gradient(160deg, #f9d4e8, #c2007f);
        }
        .review-frame iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
        .review-play {
            position: absolute; inset: 0; width: 100%; border: 0; padding: 0; cursor: pointer;
            background: transparent center / cover no-repeat; display: grid; place-items: center;
        }
        .review-play::after {
            content: ""; position: absolute; inset: 0;
            background: linear-gradient(180deg, transparent 55%, rgba(29, 20, 24, .45));
        }
        .review-play .play {
            position: relative; z-index: 1; width: 64px; height: 64px; border-radius: 50%;
            display: grid; place-items: center; background: rgba(255, 255, 255, .92); color: var(--accent);
            box-shadow: 0 10px 30px rgba(0,0,0,.2); transition: transform .2s ease;
        }
        .review-play:hover .play { transform: scale(1.08); }
        .review-play .play .icon { width: 26px; height: 26px; margin-left: 4px; }
        .review figcaption { margin-top: 12px; }
        .review figcaption strong { display: block; }
        .review figcaption span { color: var(--muted); font-size: .88rem; }

        /* ---------- Тарифы ---------- */

        .plans { display: grid; gap: 22px; grid-template-columns: repeat(3, 1fr); align-items: start; }
        .plan {
            background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius);
            padding: 30px 26px; display: flex; flex-direction: column; height: 100%;
        }
        .plan--featured { border-color: var(--brand); box-shadow: var(--shadow); position: relative; }
        .plan-badge {
            position: absolute; top: -13px; left: 26px;
            background: var(--brand); color: #fff; font-size: .72rem; font-weight: 700;
            letter-spacing: .06em; text-transform: uppercase; border-radius: 999px; padding: 5px 13px;
        }
        .plan h3 { font-size: 1.25rem; }
        .plan-tagline { color: var(--muted); font-size: .92rem; margin-top: 7px; min-height: 2.6em; }
        .plan-price { margin: 18px 0 4px; font-family: var(--serif); font-size: 2.3rem; font-weight: 600; }
        .plan-period { color: var(--faint); font-size: .86rem; min-height: 1.4em; }
        .plan ul { list-style: none; margin: 22px 0 26px; padding: 0; display: grid; gap: 10px; }
        .plan li { position: relative; padding-left: 26px; font-size: .93rem; color: var(--muted); }
        .plan li::before {
            content: ""; position: absolute; left: 4px; top: .52em;
            width: 10px; height: 6px; border-left: 2px solid var(--brand);
            border-bottom: 2px solid var(--brand); transform: rotate(-45deg);
        }
        .plan .btn { margin-top: auto; }
        .pricing-note { margin-top: 26px; text-align: center; color: var(--faint); font-size: .88rem; }

        /* ---------- FAQ ---------- */

        .faq { max-width: 780px; margin: 0 auto; display: grid; gap: 12px; }
        .faq details {
            background: var(--surface); border: 1px solid var(--line);
            border-radius: var(--radius-sm); padding: 18px 22px;
        }
        .faq details[open] { border-color: var(--border); }
        .faq summary {
            cursor: pointer; font-weight: 600; font-size: 1rem;
            list-style: none; display: flex; justify-content: space-between; gap: 16px; align-items: center;
        }
        .faq summary::-webkit-details-marker { display: none; }
        .faq summary::after { content: "+"; color: var(--accent); font-weight: 700; font-size: 1.3rem; line-height: 1; flex: none; }
        .faq details[open] summary::after { content: "−"; }
        .faq p { margin-top: 12px; color: var(--muted); font-size: .95rem; }

        /* ---------- Финальный экран ---------- */

        .final { padding: 40px 0 96px; }
        .final-box {
            background:
                radial-gradient(600px 300px at 85% 0%, rgba(255, 0, 252, .35), transparent 70%),
                var(--ink);
            border-radius: 32px; padding: 68px 34px; text-align: center; color: #fff;
        }
        .final-box h2 { font-size: clamp(1.8rem, 3.6vw, 2.7rem); max-width: 16em; margin: 0 auto; }
        .final-box p { margin-top: 16px; opacity: .85; max-width: 34em; margin-left: auto; margin-right: auto; }
        .final-actions { margin-top: 30px; display: flex; justify-content: center; flex-wrap: wrap; gap: 12px; }
        .final-note { margin-top: 16px; font-size: .85rem; opacity: .65; }

        /* ---------- Подвал ---------- */

        .site-footer { border-top: 1px solid var(--line); padding: 44px 0; background: var(--surface); }
        .footer-grid { display: flex; flex-wrap: wrap; gap: 24px; align-items: center; justify-content: space-between; }
        .footer-tagline { color: var(--muted); font-size: .9rem; max-width: 32em; margin-top: 10px; }
        .footer-links { display: flex; flex-wrap: wrap; gap: 20px; }
        .footer-links a { color: var(--muted); font-size: .9rem; text-decoration: none; }
        .footer-links a:hover { color: var(--accent); }
        .footer-rights { margin-top: 26px; color: var(--faint); font-size: .84rem; }

        /* ---------- Липкая кнопка на телефоне ---------- */

        .sticky-cta {
            position: fixed; left: 12px; right: 12px; bottom: 12px; z-index: 50;
            display: none; transform: translateY(140%); transition: transform .28s ease;
        }
        .sticky-cta.is-visible { transform: none; }
        .sticky-cta .btn { width: 100%; box-shadow: 0 14px 34px rgba(120, 0, 90, .35); }

        /* ---------- Адаптив ---------- */

        @media (max-width: 1020px) {
            .hero .wrap, .app-grid { grid-template-columns: 1fr; gap: 44px; }
            .hero-visual { max-width: 620px; }
            .grid--4 { grid-template-columns: repeat(2, 1fr); }
            .plans { grid-template-columns: 1fr; max-width: 460px; margin: 0 auto; }
        }

        @media (max-width: 900px) {
            .site-nav { display: none; }
        }

        @media (max-width: 760px) {
            .section { padding: 68px 0; }
            .section--tight { padding: 56px 0; }
            .hero { padding: 40px 0 64px; }
            .grid--3, .grid--2, .duo { grid-template-columns: 1fr; }

            /* Горизонтальные ленты вместо сеток: на телефоне листают, а не скроллят километр */
            .gallery, .screens, .reviews {
                display: flex; overflow-x: auto; scroll-snap-type: x mandatory;
                margin: 0 -24px; padding: 4px 24px 12px; gap: 14px; scrollbar-width: none;
            }
            .gallery::-webkit-scrollbar, .screens::-webkit-scrollbar, .reviews::-webkit-scrollbar { display: none; }
            .reviews .review { flex: 0 0 62%; scroll-snap-align: center; }
            .gallery .tpl { flex: 0 0 78%; scroll-snap-align: center; }
            .screens figure { flex: 0 0 58%; scroll-snap-align: center; }
            .screens figure:nth-child(2) { transform: none; }
            .sticky-cta { display: block; }
            .site-footer { padding-bottom: 96px; }
        }

        @media (max-width: 560px) {
            .wrap { padding: 0 16px; }
            .gallery, .screens, .reviews { margin: 0 -16px; padding-left: 16px; padding-right: 16px; }
            .site-header .wrap { min-height: 60px; gap: 8px; }
            .brand span { display: none; }
            .only-wide { display: none; }
            .only-narrow { display: inline; }
            .header-actions { gap: 6px; }
            .header-actions .btn--sm { padding: 8px 13px; }
            .lang button { padding: 7px 10px; }
            .hero-actions .btn { flex: 1 1 100%; }
            .grid--4 { grid-template-columns: 1fr; }
            .hero-visual { padding: 10px 44px 30px 0; }
            .hero-phone { width: 38%; }
            .notify { left: -6px; top: auto; bottom: -8px; max-width: 250px; padding: 10px 12px 10px 10px; }
            .notify-icon { width: 34px; height: 34px; }
            .mini-list li { grid-template-columns: auto 1fr; }
            .card { padding: 20px; }
            .card--row { display: grid; grid-template-columns: auto 1fr; column-gap: 14px; align-items: start; }
            .card--row .card-icon, .card--row .step-num { grid-row: span 2; width: 40px; height: 40px; margin: 0; }
            .card--row h3 { margin-top: 2px; }
            .mini-list .chip { grid-column: 2; justify-self: start; }
            .pain-answer { flex-direction: column; align-items: flex-start; }
            .final-box { padding: 48px 22px; border-radius: 26px; }
        }

        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; animation: none !important; }
        }
    </style>
</head>
<body>
<a class="skip" href="#main">{{ __('landing.nav.skip') }}</a>

<header class="site-header">
    <div class="wrap">
        <a href="{{ url('/') }}" class="brand">
            <img src="/logo.svg" alt="Veloria" width="30" height="16">
            <span>Veloria</span>
        </a>

        <nav class="site-nav" aria-label="{{ __('landing.nav.menu') }}">
            <a href="#how">{{ __('landing.nav.how') }}</a>
            <a href="#site">{{ __('landing.nav.site') }}</a>
            <a href="#app">{{ __('landing.nav.app') }}</a>
            @if ($videoReviews)
                <a href="#reviews">{{ __('landing.nav.reviews') }}</a>
            @endif
            <a href="#pricing">{{ __('landing.nav.pricing') }}</a>
        </nav>

        <div class="header-actions">
            <form method="POST" action="{{ route('locale.update') }}" class="lang">
                @csrf
                <input type="hidden" name="locale" value="{{ $isRu ? 'en' : 'ru' }}">
                <button type="submit" title="{{ __('landing.footer.language') }}">{{ $isRu ? 'EN' : 'RU' }}</button>
            </form>

            @if ($isGuest)
                <a href="{{ url('/login') }}" class="btn btn--ghost btn--sm">{{ __('auth.login') }}</a>
                <a href="{{ url('/register') }}" class="btn btn--primary btn--sm">
                    <span class="only-wide">{{ __('auth.create_account') }}</span>
                    <span class="only-narrow">{{ __('landing.nav.start_short') }}</span>
                </a>
            @else
                <a href="{{ url('/dashboard') }}" class="btn btn--primary btn--sm">{{ __('menu.dashboard') }}</a>
                <button type="button" class="btn btn--danger btn--sm" data-welcome-logout>{{ __('navigation.logout') }}</button>
            @endif
        </div>
    </div>
</header>

<main id="main">

    {{-- Герой: одно обещание и продукт, который его выполняет --}}
    <section class="hero" data-hero>
        <div class="wrap">
            <div>
                <span class="hero-badge">{{ __('landing.hero.badge') }}</span>
                <h1>{{ __('landing.hero.title') }} <span class="accent">{{ __('landing.hero.title_accent') }}</span></h1>
                <p class="hero-lead">{{ __('landing.hero.lead') }}</p>

                <div class="hero-actions">
                    <a href="{{ $startUrl }}" class="btn btn--primary btn--lg">{{ $startLabel }}</a>
                    <a href="{{ route('landings.demo', $exampleSlug) }}" class="btn btn--ghost btn--lg" target="_blank" rel="noopener">{{ __('landing.hero.cta_example') }}</a>
                </div>

                <p class="hero-note">{{ __('landing.hero.note') }}</p>
            </div>

            <div class="hero-visual">
                <div class="browser">
                    <div class="browser-bar" aria-hidden="true">
                        <i></i><i></i><i></i>
                        <span class="browser-url">{{ parse_url(url('/'), PHP_URL_HOST) }}/l/{{ __('landing.hero.site_address') }}</span>
                    </div>
                    <img src="/images/home/site-pretty.webp" width="960" height="615" alt="{{ __('landing.hero.visual_alt') }}" fetchpriority="high">
                </div>

                <div class="hero-phone device" aria-hidden="true">
                    <img src="/images/home/app-booking.webp" width="560" height="1212" alt="">
                </div>

                <div class="notify" aria-hidden="true">
                    <span class="notify-icon">{!! $icon('bell') !!}</span>
                    <div>
                        <span class="notify-label">{{ __('landing.hero.notify_label') }}</span>
                        <strong>{{ __('landing.hero.notify_title') }}</strong>
                        <span class="time">{{ __('landing.hero.notify_time') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Боли --}}
    <section class="section section--white section--tight">
        <div class="wrap">
            <div class="section-head">
                <span class="eyebrow">{{ __('landing.pains.label') }}</span>
                <h2>{{ __('landing.pains.title') }}</h2>
            </div>

            <div class="grid grid--4">
                @foreach (__('landing.pains.items') as $i => $item)
                    <article class="card card--row pain">
                        <div class="card-icon">{!! $icon(['chat', 'calendar-x', 'wave', 'puzzle'][$i] ?? 'chat') !!}</div>
                        <h3>{{ $item['title'] }}</h3>
                        <p>{{ $item['text'] }}</p>
                    </article>
                @endforeach
            </div>

            <p class="pain-answer">{!! $icon('auto') !!} <span>{{ __('landing.pains.answer') }}</span></p>
        </div>
    </section>

    {{-- Как это работает --}}
    <section class="section section--tight" id="how">
        <div class="wrap">
            <div class="section-head">
                <span class="eyebrow">{{ __('landing.steps.label') }}</span>
                <h2>{{ __('landing.steps.title') }}</h2>
            </div>

            <ol class="grid grid--3" style="list-style:none;margin:0;padding:0">
                @foreach (__('landing.steps.items') as $i => $item)
                    <li class="card card--row step">
                        <span class="step-num">{{ $i + 1 }}</span>
                        <h3>{{ $item['title'] }}</h3>
                        <p>{{ $item['text'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Сайт мастера: живые примеры шаблонов --}}
    <section class="section section--tint" id="site">
        <div class="wrap">
            <div class="section-head">
                <span class="eyebrow">{{ __('landing.site.label') }}</span>
                <h2>{{ __('landing.site.title') }}</h2>
                <p>{{ __('landing.site.lead', ['count' => $templateCount]) }}</p>
            </div>

            @if ($templates)
                <div class="gallery">
                    @foreach ($templates as $template)
                        <a class="tpl" href="{{ route('landings.demo', $template['slug']) }}" target="_blank" rel="noopener">
                            <img src="{{ asset($template['thumb']) }}" width="480" height="316" alt="{{ $template['title'] }}" loading="lazy" decoding="async">
                            <span class="tpl-meta">
                                <strong>{{ $template['title'] }}</strong>
                                <span>{{ __('landing.site.open') }} {!! $icon('arrow', 'icon--sm') !!}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="gallery-foot">
                <a href="{{ $startUrl }}" class="btn btn--primary">{{ $isGuest ? __('landing.site.all') : __('menu.dashboard') }}</a>
                @if ($templates)
                    <p>{{ __('landing.site.hint') }}</p>
                @endif
            </div>
        </div>
    </section>

    {{-- Приложение для клиенток: настоящие экраны --}}
    <section class="section section--white" id="app">
        <div class="wrap app-grid">
            <div>
                <span class="eyebrow">{{ __('landing.app.label') }}</span>
                <div class="section-head" style="margin-bottom:0">
                    <h2>{{ __('landing.app.title') }}</h2>
                    <p>{{ __('landing.app.lead') }}</p>
                </div>

                <ul class="checks">
                    @foreach (__('landing.app.points') as $point)
                        <li>{{ $point }}</li>
                    @endforeach
                </ul>

                @if ($androidAppUrl)
                    <a href="{{ $androidAppUrl }}" class="btn btn--dark" target="_blank" rel="noopener" data-android-link>
                        {!! $icon('android') !!} {{ __('landing.app.android') }}
                    </a>
                @endif
                <p class="app-note">{{ __('landing.app.iphone_note') }}</p>
            </div>

            <div class="screens">
                @foreach (['home', 'booking', 'chat'] as $screen)
                    <figure>
                        <div class="device">
                            <img src="/images/home/app-{{ $screen }}.webp" width="560" height="1212" alt="{{ __('landing.app.screens.' . $screen) }}" loading="lazy" decoding="async">
                        </div>
                        <figcaption>{{ __('landing.app.screens.' . $screen) }}</figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Бот и кабинет --}}
    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <span class="eyebrow">{{ __('landing.more.label') }}</span>
                <h2>{{ __('landing.more.title') }}</h2>
            </div>

            <div class="duo">
                @php $bot = __('landing.more.bot'); @endphp
                <article class="card">
                    <div class="card-icon">{!! $icon('send') !!}</div>
                    <h3>{{ $bot['title'] }}</h3>
                    <p>{{ $bot['text'] }}</p>
                    <div class="tg" aria-hidden="true">
                        @foreach ($bot['chat'] as $line)
                            <span class="tg-msg{{ $line['from'] === 'me' ? ' tg-msg--me' : '' }}">{{ $line['text'] }}</span>
                        @endforeach
                    </div>
                </article>

                @php $crm = __('landing.more.crm'); @endphp
                <article class="card">
                    <div class="card-icon">{!! $icon('users') !!}</div>
                    <h3>{{ $crm['title'] }}</h3>
                    <p>{{ $crm['text'] }}</p>
                    <div class="mini" aria-hidden="true">
                        <div class="mini-head">{{ $crm['today'] }}</div>
                        <ul class="mini-list">
                            @foreach ($crm['rows'] as $row)
                                <li>
                                    <span class="mini-time">{{ $row['time'] }}</span>
                                    <span class="mini-body">
                                        <span class="mini-client">{{ $row['client'] }}</span>
                                        <span class="mini-service">{{ $row['service'] }}</span>
                                    </span>
                                    <span class="chip chip--{{ $row['state'] }}">{{ $crm['states'][$row['state']] }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mini-due">
                            <span><small>{{ $crm['due_title'] }}</small>{{ $crm['due_client'] }}</span>
                            <b>{{ $crm['due_action'] }}</b>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- Меньше неявок --}}
    <section class="section section--white">
        <div class="wrap">
            <div class="section-head">
                <span class="eyebrow">{{ __('landing.noshow.label') }}</span>
                <h2>{{ __('landing.noshow.title') }}</h2>
                <p>{{ __('landing.noshow.lead') }}</p>
            </div>

            <div class="grid grid--3">
                @foreach (['reminder' => 'clock', 'prepay' => 'shield', 'waitlist' => 'bell'] as $key => $iconName)
                    @php $item = __('landing.noshow.items.' . $key); @endphp
                    <article class="card card--row">
                        <div class="card-icon">{!! $icon($iconName) !!}</div>
                        <h3>{{ $item['title'] }}</h3>
                        <p>{{ $item['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Возврат клиенток (платная часть) --}}
    <section class="section section--tint">
        <div class="wrap">
            <div class="section-head">
                <span class="eyebrow">{{ __('landing.retention.label') }}</span>
                <h2>{{ __('landing.retention.title') }}</h2>
                <p>{{ __('landing.retention.lead') }}</p>
            </div>

            <div class="grid grid--4">
                @foreach (__('landing.retention.items') as $i => $item)
                    <article class="card card--row">
                        <div class="card-icon">{!! $icon(['users', 'auto', 'gift', 'star'][$i] ?? 'star') !!}</div>
                        <h3>{{ $item['title'] }}</h3>
                        <p>{{ $item['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Видео-отзывы: список в config/landing_home.php, пустой — секции нет --}}
    @if ($videoReviews)
        <section class="section section--white" id="reviews">
            <div class="wrap">
                <div class="section-head">
                    <span class="eyebrow">{{ __('landing.reviews.label') }}</span>
                    <h2>{{ __('landing.reviews.title') }}</h2>
                    <p>{{ __('landing.reviews.lead') }}</p>
                </div>

                <div class="reviews">
                    @foreach ($videoReviews as $review)
                        <figure class="review">
                            <div class="review-frame">
                                {{-- Плеер подгружается по клику: пять iframe сразу утяжелили бы страницу в разы --}}
                                <button type="button" class="review-play"
                                        data-review-embed="{{ $review['embed'] }}"
                                        @if ($review['poster']) style="background-image:url('{{ asset($review['poster']) }}')" @endif
                                        aria-label="{{ __('landing.reviews.play', ['name' => $review['name']]) }}">
                                    <span class="play">{!! $icon('play') !!}</span>
                                </button>
                            </div>
                            <figcaption>
                                <strong>{{ $review['name'] }}</strong>
                                <span>{{ collect([$review['role'], $review['city']])->filter()->implode(' · ') }}</span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Тарифы --}}
    <section class="section" id="pricing">
        <div class="wrap">
            <div class="section-head section-head--center">
                <span class="eyebrow">{{ __('landing.pricing.label') }}</span>
                <h2>{{ __('landing.pricing.title') }}</h2>
                <p>{{ __('landing.pricing.lead') }}</p>
            </div>

            <div class="plans">
                @foreach (['lite', 'pro', 'elite'] as $slug)
                    @php
                        $plan = __('landing.pricing.plans.' . $slug);
                        $price = $planPrices[$slug] ?? 0;
                        $featured = $slug === 'pro';
                    @endphp
                    <article class="plan{{ $featured ? ' plan--featured' : '' }}">
                        @if ($featured && !empty($plan['badge']))
                            <span class="plan-badge">{{ $plan['badge'] }}</span>
                        @endif

                        <h3>{{ $plan['name'] }}</h3>
                        <p class="plan-tagline">{{ $plan['tagline'] }}</p>

                        <div class="plan-price">{{ $formatPrice($price) }}</div>
                        <div class="plan-period">{{ $price > 0 ? __('landing.pricing.period') : ' ' }}</div>

                        <ul>
                            @foreach ($plan['features'] as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>

                        <a href="{{ url('/register') }}" class="btn btn--block {{ $featured ? 'btn--primary' : 'btn--ghost' }}">
                            {{ $price > 0
                                ? __('landing.pricing.cta_paid', ['plan' => $plan['name']])
                                : __('landing.pricing.cta_free') }}
                        </a>
                    </article>
                @endforeach
            </div>

            <p class="pricing-note">{{ __('landing.pricing.note') }}</p>
        </div>
    </section>

    {{-- Вопросы --}}
    <section class="section section--white">
        <div class="wrap">
            <div class="section-head section-head--center">
                <span class="eyebrow">{{ __('landing.faq.label') }}</span>
                <h2>{{ __('landing.faq.title') }}</h2>
            </div>

            <div class="faq">
                @foreach ($faqItems as $item)
                    <details @if ($loop->first) open @endif>
                        <summary>{{ $item['q'] }}</summary>
                        <p>{{ $item['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Финальный экран --}}
    <section class="final section--white" data-final>
        <div class="wrap">
            <div class="final-box">
                <h2>{{ __('landing.cta.title') }}</h2>
                <p>{{ __('landing.cta.lead') }}</p>
                <div class="final-actions">
                    <a href="{{ $startUrl }}" class="btn btn--primary btn--lg">{{ $startLabel }}</a>
                </div>
                <p class="final-note">{{ __('landing.cta.note') }}</p>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="wrap">
        <div class="footer-grid">
            <div>
                <a href="{{ url('/') }}" class="brand">
                    <img src="/logo.svg" alt="Veloria" width="30" height="16">
                    <span>Veloria</span>
                </a>
                <p class="footer-tagline">{{ __('landing.footer.tagline') }}</p>
            </div>

            <nav class="footer-links" aria-label="{{ __('landing.nav.menu') }}">
                <a href="{{ route('terms') }}">{{ __('legal.terms.title') }}</a>
                <a href="{{ route('policy') }}">{{ __('legal.policy.title') }}</a>
                <a href="mailto:{{ config('mail.from.address') }}">{{ __('landing.footer.contact') }}</a>
            </nav>
        </div>

        <p class="footer-rights">{{ __('landing.footer.rights', ['year' => date('Y')]) }}</p>
    </div>
</footer>

@if ($isGuest)
    <div class="sticky-cta" data-sticky-cta>
        <a href="{{ url('/register') }}" class="btn btn--primary btn--lg">{{ __('landing.sticky') }}</a>
    </div>
@endif

<script>
    (function () {
        // Липкая кнопка на телефоне: появляется, когда первый экран ушёл из
        // вида, и прячется у финального призыва, чтобы не дублировать его.
        var sticky = document.querySelector('[data-sticky-cta]');
        var hero = document.querySelector('[data-hero]');
        var final = document.querySelector('[data-final]');

        if (sticky && hero && final && 'IntersectionObserver' in window) {
            var heroVisible = true;
            var finalVisible = false;
            var sync = function () {
                sticky.classList.toggle('is-visible', !heroVisible && !finalVisible);
            };

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.target === hero) heroVisible = entry.isIntersecting;
                    if (entry.target === final) finalVisible = entry.isIntersecting;
                });
                sync();
            });
            observer.observe(hero);
            observer.observe(final);
        }

        // Видео-отзывы: постер меняется на плеер только по клику.
        document.querySelectorAll('[data-review-embed]').forEach(function (button) {
            button.addEventListener('click', function () {
                var frame = document.createElement('iframe');
                frame.src = button.getAttribute('data-review-embed');
                frame.title = button.getAttribute('aria-label') || '';
                frame.allow = 'autoplay; encrypted-media; fullscreen; picture-in-picture';
                frame.allowFullscreen = true;
                button.replaceWith(frame);
            });
        });
    })();

    (function () {
        var logoutButtons = document.querySelectorAll('[data-welcome-logout]');
        if (!logoutButtons.length) {
            return;
        }

        function getCookie(name) {
            var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
            return match ? match[1] : null;
        }

        function deleteCookie(name) {
            document.cookie = name + '=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT;';
            document.cookie = name + '=; path=/; Max-Age=0;';
        }

        function logout() {
            var headers = {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept-Language': document.documentElement.lang || 'en'
            };

            var token = getCookie('token');
            if (token) {
                headers['Authorization'] = 'Bearer ' + token;
            }

            fetch('/api/v1/logout', {
                method: 'POST',
                headers: headers
            }).finally(function () {
                deleteCookie('token');
                window.location.href = '/';
            });
        }

        logoutButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (button.disabled) {
                    return;
                }
                button.disabled = true;
                logout();
            });
        });
    })();
</script>
</body>
</html>
