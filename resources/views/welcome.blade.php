@php
    $locale = app()->getLocale();
    $isRu = $locale === 'ru';

    // Цены приходят из HomeController — из базы, а не из текста страницы.
    $planPrices = $planPrices ?? ['lite' => 0, 'pro' => 990, 'elite' => 1990];

    $formatPrice = static function (int $value): string {
        return $value > 0
            ? number_format($value, 0, '', ' ') . ' ' . __('subscription.currency')
            : __('landing.pricing.free');
    };

    // Иконки держим здесь, а не файлами: их восемь, они по 200 байт, и лишний
    // запрос к серверу на холодном трафике дороже, чем эта разметка.
    $icons = [
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 2.5 15.3 0 18M12 3c-2.5 2.7-2.5 15.3 0 18"/>',
        'phone' => '<rect x="6" y="2" width="12" height="20" rx="3"/><path d="M11 18h2"/>',
        'send' => '<path d="M21 4 3 11l7 3 3 7 8-17Z"/><path d="M10 14l4-4"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'spark' => '<path d="m12 3 2.2 5.8L20 11l-5.8 2.2L12 19l-2.2-5.8L4 11l5.8-2.2L12 3Z"/>',
        'shield' => '<path d="M12 3 5 6v6c0 4.2 2.9 7.6 7 9 4.1-1.4 7-4.8 7-9V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
    ];

    $icon = static function (string $name) use ($icons): string {
        $body = $icons[$name] ?? '';

        return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $body . '</svg>';
    };
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

    <link rel="icon" href="/logo.svg" type="image/svg+xml">
    <link rel="alternate icon" href="/favicon.ico">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">

    <style>
        :root {
            color-scheme: light;

            /* Палитра взята из мобильного приложения (src/theme/theme.ts) —
               чтобы лендинг, кабинет и приложение были одного цвета. */
            --brand: #ff00fc;
            --brand-press: #d700d4;
            --brand-soft: #ffe5ff;
            --brand-tint: #fff0fe;
            --border: #f0d7f7;
            --line: #ece7f0;
            --text: #16111d;
            --muted: #655d73;
            --faint: #9a90a8;
            --success: #1ea672;
            --success-bg: #e7f7f0;
            --warn: #b8501b;
            --warn-bg: #fff0e6;
            --bg: #ffffff;

            --radius: 22px;
            --radius-sm: 14px;
            --shadow: 0 18px 40px rgba(255, 0, 252, .10);
            --shadow-sm: 0 6px 18px rgba(22, 17, 29, .06);
            --wrap: 1120px;

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

        h1, h2, h3 { letter-spacing: -.022em; line-height: 1.15; margin: 0; }
        p { margin: 0; }
        img, svg { max-width: 100%; }
        a { color: inherit; }

        .wrap { max-width: var(--wrap); margin: 0 auto; padding: 0 24px; }

        .skip {
            position: absolute; left: -9999px; top: 0; z-index: 100;
            background: var(--text); color: #fff; padding: 12px 18px; border-radius: 0 0 12px 0;
            text-decoration: none;
        }
        .skip:focus { left: 0; }

        .icon { width: 22px; height: 22px; }

        /* ---------- Шапка ---------- */

        .site-header {
            position: sticky; top: 0; z-index: 40;
            background: rgba(255, 255, 255, .88);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
        }

        .site-header .wrap {
            display: flex; align-items: center; gap: 20px;
            min-height: 68px;
        }

        .brand {
            display: inline-flex; align-items: center; gap: 9px;
            font-weight: 800; font-size: 1.12rem; letter-spacing: -.02em;
            text-decoration: none; flex: none;
        }
        .brand img { width: 30px; height: auto; display: block; }

        .site-nav { display: flex; gap: 26px; margin-left: 12px; }
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
        .lang button:hover { color: var(--brand-press); border-color: var(--border); background: var(--brand-tint); }

        /* ---------- Кнопки ---------- */

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            border-radius: 999px; padding: 12px 22px;
            font-weight: 600; font-size: .95rem; text-decoration: none;
            border: 1px solid transparent; cursor: pointer; font-family: inherit;
            transition: transform .18s ease, background-color .18s ease, box-shadow .18s ease, color .18s ease;
            white-space: nowrap;
        }
        .btn--primary { background: var(--brand); color: #fff; box-shadow: 0 10px 26px rgba(255, 0, 252, .28); }
        .btn--primary:hover { background: var(--brand-press); transform: translateY(-1px); }
        .btn--ghost { border-color: var(--line); color: var(--text); background: #fff; }
        .btn--ghost:hover { border-color: var(--brand); color: var(--brand-press); background: var(--brand-tint); }
        .btn--danger { border-color: #f2c8cf; background: #fff5f7; color: #bb2d3b; }
        .btn--danger:hover { border-color: #bb2d3b; }
        .btn--sm { padding: 9px 16px; font-size: .88rem; }
        .btn--block { width: 100%; }
        .btn--light { background: #fff; color: var(--brand-press); }
        .btn--light:hover { background: var(--brand-tint); transform: translateY(-1px); }

        /* ---------- Секции ---------- */

        .section { padding: 92px 0; }
        .section--tint { background: var(--brand-tint); }
        .section--line { border-top: 1px solid var(--line); }

        .section-head { max-width: 720px; margin-bottom: 48px; }
        .section-head--center { margin-left: auto; margin-right: auto; text-align: center; }

        .eyebrow {
            display: inline-block; font-size: .78rem; font-weight: 700;
            letter-spacing: .1em; text-transform: uppercase;
            color: var(--brand-press); margin-bottom: 14px;
        }
        .section-head h2 { font-size: clamp(1.7rem, 3.3vw, 2.4rem); }
        .section-head p { margin-top: 14px; color: var(--muted); font-size: 1.05rem; }

        .soon {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: .7rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
            color: var(--brand-press); background: var(--brand-soft);
            border-radius: 999px; padding: 3px 9px; vertical-align: middle;
        }

        .card {
            background: #fff; border: 1px solid var(--line);
            border-radius: var(--radius); padding: 26px;
        }
        .card h3 { font-size: 1.08rem; margin-bottom: 9px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .card p { color: var(--muted); font-size: .95rem; }

        .card-icon {
            width: 44px; height: 44px; border-radius: 13px;
            display: grid; place-items: center; margin-bottom: 16px;
            background: var(--brand-tint); color: var(--brand-press);
        }

        .grid { display: grid; gap: 20px; }
        .grid--3 { grid-template-columns: repeat(auto-fit, minmax(272px, 1fr)); }
        .grid--2 { grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }

        /* ---------- Герой ---------- */

        .hero {
            padding: 72px 0 88px;
            background:
                radial-gradient(900px 420px at 88% -8%, rgba(255, 0, 252, .13), transparent 62%),
                radial-gradient(700px 360px at -6% 4%, rgba(255, 0, 252, .07), transparent 60%);
        }
        .hero .wrap { display: grid; gap: 56px; grid-template-columns: 1.02fr .98fr; align-items: center; }
        .hero h1 { font-size: clamp(2.15rem, 4.6vw, 3.35rem); }
        .hero h1 .accent {
            color: var(--brand);
            position: relative; white-space: nowrap;
        }
        .hero-lead { margin-top: 20px; color: var(--muted); font-size: 1.1rem; max-width: 33em; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; }
        .hero-note { margin-top: 16px; color: var(--faint); font-size: .88rem; }

        /* ---------- Мокап кабинета ---------- */

        .mock {
            background: #fff; border: 1px solid var(--border);
            border-radius: 24px; box-shadow: var(--shadow); padding: 22px;
        }
        .mock-label {
            font-size: .72rem; font-weight: 700; letter-spacing: .09em;
            text-transform: uppercase; color: var(--faint);
        }
        .mock-head { display: flex; flex-direction: column; gap: 3px; margin-bottom: 18px; }
        .mock-head strong { font-size: 1.15rem; }
        .mock-sub { color: var(--muted); font-size: .88rem; }

        .mock-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
        .mock-list li {
            display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 12px;
            border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 12px 14px;
        }
        .mock-time { font-weight: 700; font-size: .95rem; font-variant-numeric: tabular-nums; }
        .mock-body { display: flex; flex-direction: column; min-width: 0; }
        .mock-client { font-weight: 600; font-size: .93rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .mock-service { color: var(--faint); font-size: .82rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .chip {
            font-size: .74rem; font-weight: 600; border-radius: 999px;
            padding: 4px 10px; white-space: nowrap;
        }
        .chip--ok { background: var(--success-bg); color: var(--success); }
        .chip--risk { background: var(--warn-bg); color: var(--warn); }
        .chip--care { background: var(--brand-soft); color: #7b2d87; }

        .mock-due {
            margin-top: 16px; padding: 15px 16px;
            background: var(--brand-tint); border-radius: var(--radius-sm);
            display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
        }
        .mock-due-text { flex: 1 1 190px; min-width: 0; }
        .mock-due-title {
            font-size: .72rem; font-weight: 700; letter-spacing: .09em;
            text-transform: uppercase; color: var(--brand-press);
        }
        .mock-due strong { display: block; font-size: .96rem; margin-top: 3px; }
        .mock-due span.hint { color: var(--muted); font-size: .84rem; }

        /* ---------- Демо-фрагменты ---------- */

        .demo {
            margin-top: 18px; padding: 14px 16px;
            background: var(--brand-tint); border: 1px solid var(--border);
            border-radius: var(--radius-sm); font-size: .88rem;
        }
        .demo-label {
            font-size: .7rem; font-weight: 700; letter-spacing: .08em;
            text-transform: uppercase; color: var(--faint); display: block; margin-bottom: 5px;
        }
        .demo-value { font-weight: 600; color: var(--text); }
        .demo-hint { display: block; margin-top: 5px; color: var(--muted); font-size: .84rem; }
        .demo-arrow { display: block; margin: 7px 0 4px; color: var(--brand-press); font-weight: 700; }
        .demo-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .tag {
            font-size: .76rem; background: #fff; border: 1px solid var(--border);
            color: var(--muted); border-radius: 999px; padding: 3px 10px;
        }

        /* ---------- Телефон ---------- */

        .booking-grid { display: grid; gap: 56px; grid-template-columns: 1.05fr .95fr; align-items: center; }
        .booking-list { display: grid; gap: 18px; }
        .booking-item { display: grid; grid-template-columns: auto 1fr; gap: 16px; align-items: start; }
        .booking-item .card-icon { margin-bottom: 0; }
        .booking-item h3 { font-size: 1.05rem; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .booking-item p { color: var(--muted); font-size: .95rem; }

        .phone-stage { display: flex; justify-content: center; }
        .phone {
            width: 272px; border: 9px solid #16111d; border-radius: 38px;
            background: #fff; overflow: hidden; box-shadow: var(--shadow);
        }
        .phone-top {
            background: var(--brand); color: #fff; padding: 20px 20px 22px; text-align: center;
        }
        .phone-brand { font-size: .74rem; letter-spacing: .12em; text-transform: uppercase; opacity: .85; }
        .phone-top strong { display: block; font-size: 1.2rem; margin-top: 4px; }
        .phone-body { padding: 18px 18px 22px; }
        .phone-service { font-weight: 600; font-size: .98rem; }
        .phone-duration { color: var(--faint); font-size: .85rem; margin-top: 2px; }
        .phone-date { margin-top: 16px; font-size: .82rem; font-weight: 600; color: var(--muted); }
        .phone-slots { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-top: 10px; }
        .slot {
            text-align: center; padding: 10px 0; border-radius: 11px;
            border: 1px solid var(--line); font-size: .9rem; font-weight: 600;
            font-variant-numeric: tabular-nums; color: var(--muted);
        }
        .slot--active { background: var(--brand); border-color: var(--brand); color: #fff; }
        .phone-cta {
            margin-top: 16px; text-align: center; padding: 12px 0;
            border-radius: 999px; background: var(--text); color: #fff;
            font-weight: 600; font-size: .92rem;
        }

        /* ---------- Тарифы ---------- */

        .plans { display: grid; gap: 22px; grid-template-columns: repeat(auto-fit, minmax(286px, 1fr)); align-items: start; }
        .plan {
            background: #fff; border: 1px solid var(--line); border-radius: var(--radius);
            padding: 30px 26px; display: flex; flex-direction: column; height: 100%;
        }
        .plan--featured {
            border-color: var(--brand); box-shadow: var(--shadow); position: relative;
        }
        .plan-badge {
            position: absolute; top: -13px; left: 26px;
            background: var(--brand); color: #fff; font-size: .72rem; font-weight: 700;
            letter-spacing: .06em; text-transform: uppercase;
            border-radius: 999px; padding: 5px 13px;
        }
        .plan h3 { font-size: 1.25rem; }
        .plan-tagline { color: var(--muted); font-size: .92rem; margin-top: 7px; min-height: 2.6em; }
        .plan-price { margin: 20px 0 4px; font-size: 2.1rem; font-weight: 800; letter-spacing: -.03em; }
        .plan-period { color: var(--faint); font-size: .86rem; }
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
            background: #fff; border: 1px solid var(--line);
            border-radius: var(--radius-sm); padding: 18px 22px;
        }
        .faq details[open] { border-color: var(--border); }
        .faq summary {
            cursor: pointer; font-weight: 600; font-size: 1rem;
            list-style: none; display: flex; justify-content: space-between; gap: 16px; align-items: center;
        }
        .faq summary::-webkit-details-marker { display: none; }
        .faq summary::after {
            content: "+"; color: var(--brand); font-weight: 700; font-size: 1.3rem; line-height: 1; flex: none;
        }
        .faq details[open] summary::after { content: "−"; }
        .faq p { margin-top: 12px; color: var(--muted); font-size: .95rem; }

        /* ---------- Финальный экран ---------- */

        .final { padding: 92px 0; }
        .final-box {
            background: linear-gradient(135deg, var(--brand) 0%, #b800b6 100%);
            border-radius: 30px; padding: 62px 34px; text-align: center; color: #fff;
        }
        .final-box h2 { font-size: clamp(1.6rem, 3.2vw, 2.3rem); max-width: 18em; margin: 0 auto; }
        .final-box p { margin-top: 16px; opacity: .92; max-width: 34em; margin-left: auto; margin-right: auto; }
        .final-actions { margin-top: 28px; display: flex; justify-content: center; flex-wrap: wrap; gap: 12px; }
        .final-note { margin-top: 16px; font-size: .85rem; opacity: .8; }

        /* ---------- Подвал ---------- */

        .site-footer { border-top: 1px solid var(--line); padding: 44px 0; background: #fff; }
        .footer-grid { display: flex; flex-wrap: wrap; gap: 24px; align-items: center; justify-content: space-between; }
        .footer-tagline { color: var(--muted); font-size: .9rem; max-width: 30em; margin-top: 10px; }
        .footer-links { display: flex; flex-wrap: wrap; gap: 20px; }
        .footer-links a { color: var(--muted); font-size: .9rem; text-decoration: none; }
        .footer-links a:hover { color: var(--brand-press); }
        .footer-rights { margin-top: 26px; color: var(--faint); font-size: .84rem; }

        /* ---------- Адаптив ---------- */

        @media (max-width: 980px) {
            .hero .wrap, .booking-grid { grid-template-columns: 1fr; gap: 40px; }
            .hero { padding: 52px 0 68px; }
            .section { padding: 68px 0; }
        }

        @media (max-width: 860px) {
            .site-nav { display: none; }
        }

        @media (max-width: 560px) {
            .site-header .wrap { min-height: 60px; gap: 10px; }
            .brand span { display: none; }
            .header-actions .btn--ghost { display: none; }
            .hero-actions .btn { flex: 1 1 100%; }
            .final-box { padding: 46px 22px; }
            .mock { padding: 16px; }
            .mock-list li { grid-template-columns: auto 1fr; }
            .mock-list .chip { grid-column: 2; justify-self: start; }
        }

        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; }
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
            <a href="#features">{{ __('landing.nav.features') }}</a>
            <a href="#booking">{{ __('landing.nav.booking') }}</a>
            <a href="#pricing">{{ __('landing.nav.pricing') }}</a>
        </nav>

        <div class="header-actions">
            <form method="POST" action="{{ route('locale.update') }}" class="lang">
                @csrf
                <input type="hidden" name="locale" value="{{ $isRu ? 'en' : 'ru' }}">
                <button type="submit" title="{{ __('landing.footer.language') }}">{{ $isRu ? 'EN' : 'RU' }}</button>
            </form>

            @if (!empty($isAuthenticated))
                <a href="{{ url('/dashboard') }}" class="btn btn--primary btn--sm">{{ __('menu.dashboard') }}</a>
                <button type="button" class="btn btn--danger btn--sm" data-welcome-logout>{{ __('navigation.logout') }}</button>
            @else
                <a href="{{ url('/login') }}" class="btn btn--ghost btn--sm">{{ __('auth.login') }}</a>
                <a href="{{ url('/register') }}" class="btn btn--primary btn--sm">{{ __('auth.create_account') }}</a>
            @endif
        </div>
    </div>
</header>

<main id="main">

    {{-- Герой --}}
    <section class="hero">
        <div class="wrap">
            <div>
                <h1>{{ __('landing.hero.title') }}<br><span class="accent">{{ __('landing.hero.title_accent') }}</span></h1>
                <p class="hero-lead">{{ __('landing.hero.lead') }}</p>

                <div class="hero-actions">
                    @if (!empty($isAuthenticated))
                        <a href="{{ url('/dashboard') }}" class="btn btn--primary">{{ __('menu.dashboard') }}</a>
                    @else
                        <a href="{{ url('/register') }}" class="btn btn--primary">{{ __('landing.pricing.cta_free') }}</a>
                    @endif
                    <a href="#features" class="btn btn--ghost">{{ __('landing.hero.cta_secondary') }}</a>
                </div>

                <p class="hero-note">{{ __('landing.hero.note') }}</p>
            </div>

            <div class="mock" aria-hidden="true">
                <div class="mock-head">
                    <span class="mock-label">{{ __('landing.hero.mockup.label') }}</span>
                    <strong>{{ __('landing.hero.mockup.title') }}</strong>
                    <span class="mock-sub">{{ __('landing.hero.mockup.count') }}</span>
                </div>

                <ul class="mock-list">
                    @foreach (__('landing.hero.mockup.rows') as $row)
                        <li>
                            <span class="mock-time">{{ $row['time'] }}</span>
                            <span class="mock-body">
                                <span class="mock-client">{{ $row['client'] }}</span>
                                <span class="mock-service">{{ $row['service'] }}</span>
                            </span>
                            <span class="chip chip--{{ $row['state'] }}">{{ __('landing.hero.mockup.states.' . $row['state']) }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="mock-due">
                    <div class="mock-due-text">
                        <span class="mock-due-title">{{ __('landing.hero.mockup.due_title') }}</span>
                        <strong>{{ __('landing.hero.mockup.due_client') }}</strong>
                        <span class="hint">{{ __('landing.hero.mockup.due_rhythm') }}</span>
                    </div>
                    <span class="btn btn--primary btn--sm">{{ __('landing.hero.mockup.due_action') }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Что входит бесплатно --}}
    <section class="section section--tint" id="features">
        <div class="wrap">
            <div class="section-head">
                <span class="eyebrow">{{ __('landing.free.label') }}</span>
                <h2>{{ __('landing.free.title') }}</h2>
                <p>{{ __('landing.free.lead') }}</p>
            </div>

            <div class="grid grid--3">
                @foreach (['crm' => 'calendar', 'site' => 'globe', 'app' => 'phone'] as $key => $iconName)
                    @php $item = __('landing.free.items.' . $key); @endphp
                    <article class="card">
                        <div class="card-icon">{!! $icon($iconName) !!}</div>
                        <h3>
                            {{ $item['title'] }}
                            @if (!empty($item['soon']))
                                <span class="soon" title="{{ __('landing.soon_hint') }}">{{ __('landing.soon') }}</span>
                            @endif
                        </h3>
                        <p>{{ $item['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Кабинет каждый день --}}
    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <span class="eyebrow">{{ __('landing.day.label') }}</span>
                <h2>{{ __('landing.day.title') }}</h2>
                <p>{{ __('landing.day.lead') }}</p>
            </div>

            <div class="grid grid--3">
                @php $gap = __('landing.day.items.gap'); @endphp
                <article class="card">
                    <h3>{{ $gap['title'] }}</h3>
                    <p>{{ $gap['text'] }}</p>
                    <div class="demo">
                        <span class="demo-label">{{ $gap['demo_label'] }}</span>
                        <span class="demo-value">{{ $gap['demo_value'] }}</span>
                        <span class="demo-hint">{{ $gap['demo_actions'] }}</span>
                    </div>
                </article>

                @php $due = __('landing.day.items.due'); @endphp
                <article class="card">
                    <h3>{{ $due['title'] }}</h3>
                    <p>{{ $due['text'] }}</p>
                    <div class="demo">
                        <span class="demo-label">{{ $due['demo_label'] }}</span>
                        <span class="demo-value">{{ $due['demo_value'] }}</span>
                        <span class="demo-hint">{{ $due['demo_hint'] }}</span>
                    </div>
                </article>

                @php $waitlist = __('landing.day.items.waitlist'); @endphp
                <article class="card">
                    <h3>{{ $waitlist['title'] }}</h3>
                    <p>{{ $waitlist['text'] }}</p>
                    <div class="demo">
                        <span class="demo-label">{{ $waitlist['demo_label'] }}</span>
                        <span class="demo-value">{{ $waitlist['demo_value'] }}</span>
                        <span class="demo-chips">
                            @foreach ($waitlist['demo_reasons'] as $reason)
                                <span class="tag">{{ $reason }}</span>
                            @endforeach
                        </span>
                    </div>
                </article>

                @php $phrase = __('landing.day.items.phrase'); @endphp
                <article class="card">
                    <h3>{{ $phrase['title'] }}</h3>
                    <p>{{ $phrase['text'] }}</p>
                    <div class="demo">
                        <span class="demo-label">{{ $phrase['demo_label'] }}</span>
                        <span class="demo-value">«{{ $phrase['demo_value'] }}»</span>
                        <span class="demo-arrow">↓</span>
                        <span class="demo-hint">{{ $phrase['demo_hint'] }}</span>
                    </div>
                </article>

                @php $risk = __('landing.day.items.risk'); @endphp
                <article class="card">
                    <h3>{{ $risk['title'] }}</h3>
                    <p>{{ $risk['text'] }}</p>
                    <div class="demo">
                        <span class="demo-label">{{ $risk['demo_label'] }}</span>
                        <span class="demo-chips">
                            @foreach (['ok', 'risk', 'care'] as $i => $state)
                                <span class="chip chip--{{ $state }}">{{ $risk['demo_chips'][$i] }}</span>
                            @endforeach
                        </span>
                    </div>
                </article>

                @php $duration = __('landing.day.items.duration'); @endphp
                <article class="card">
                    <h3>{{ $duration['title'] }}</h3>
                    <p>{{ $duration['text'] }}</p>
                    <div class="demo">
                        <span class="demo-label">{{ $duration['demo_label'] }}</span>
                        <span class="demo-value">{{ $duration['demo_value'] }}</span>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- Онлайн-запись --}}
    <section class="section section--tint" id="booking">
        <div class="wrap">
            <div class="section-head">
                <span class="eyebrow">{{ __('landing.booking.label') }}</span>
                <h2>{{ __('landing.booking.title') }}</h2>
                <p>{{ __('landing.booking.lead') }}</p>
            </div>

            <div class="booking-grid">
                <div class="booking-list">
                    @foreach (['telegram' => 'send', 'site' => 'globe', 'app' => 'phone'] as $key => $iconName)
                        @php $item = __('landing.booking.items.' . $key); @endphp
                        <article class="booking-item">
                            <div class="card-icon">{!! $icon($iconName) !!}</div>
                            <div>
                                <h3>
                                    {{ $item['title'] }}
                                    @if (!empty($item['soon']))
                                        <span class="soon" title="{{ __('landing.soon_hint') }}">{{ __('landing.soon') }}</span>
                                    @endif
                                </h3>
                                <p>{{ $item['text'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>

                @php $phone = __('landing.booking.phone'); @endphp
                <div class="phone-stage" aria-hidden="true">
                    <div class="phone">
                        <div class="phone-top">
                            <span class="phone-brand">{{ $phone['brand'] }}</span>
                            <strong>{{ $phone['title'] }}</strong>
                        </div>
                        <div class="phone-body">
                            <div class="phone-service">{{ $phone['service'] }}</div>
                            <div class="phone-duration">{{ $phone['duration'] }}</div>
                            <div class="phone-date">{{ $phone['date'] }}</div>
                            <div class="phone-slots">
                                @foreach ($phone['slots'] as $slot)
                                    <div class="slot{{ $slot === $phone['slot_active'] ? ' slot--active' : '' }}">{{ $slot }}</div>
                                @endforeach
                            </div>
                            <div class="phone-cta">{{ $phone['cta'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Пустые окна --}}
    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <span class="eyebrow">{{ __('landing.noshow.label') }}</span>
                <h2>{{ __('landing.noshow.title') }}</h2>
                <p>{{ __('landing.noshow.lead') }}</p>
            </div>

            <div class="grid grid--2">
                @foreach (['reminder' => 'clock', 'prepay' => 'shield', 'waitlist' => 'send', 'allergy' => 'spark'] as $key => $iconName)
                    @php $item = __('landing.noshow.items.' . $key); @endphp
                    <article class="card">
                        <div class="card-icon">{!! $icon($iconName) !!}</div>
                        <h3>
                            {{ $item['title'] }}
                            @if (!empty($item['soon']))
                                <span class="soon" title="{{ __('landing.soon_hint') }}">{{ __('landing.soon') }}</span>
                            @endif
                        </h3>
                        <p>{{ $item['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Возврат клиенток --}}
    <section class="section section--tint">
        <div class="wrap">
            <div class="section-head">
                <span class="eyebrow">{{ __('landing.retention.label') }}</span>
                <h2>{{ __('landing.retention.title') }}</h2>
                <p>{{ __('landing.retention.lead') }}</p>
            </div>

            <div class="grid grid--3">
                @foreach (['segments', 'ab', 'cashback', 'flows', 'loyalty', 'reviews'] as $key)
                    @php $item = __('landing.retention.items.' . $key); @endphp
                    <article class="card">
                        <h3>
                            {{ $item['title'] }}
                            @if (!empty($item['soon']))
                                <span class="soon" title="{{ __('landing.soon_hint') }}">{{ __('landing.soon') }}</span>
                            @endif
                        </h3>
                        <p>{{ $item['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Аналитика --}}
    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <span class="eyebrow">{{ __('landing.analytics.label') }}</span>
                <h2>{{ __('landing.analytics.title') }}</h2>
                <p>{{ __('landing.analytics.lead') }}</p>
            </div>

            <div class="grid grid--3">
                @foreach (__('landing.analytics.items') as $item)
                    <article class="card">
                        <div class="card-icon">{!! $icon('chart') !!}</div>
                        <h3>{{ $item['title'] }}</h3>
                        <p>{{ $item['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Тарифы --}}
    <section class="section section--tint" id="pricing">
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
    <section class="section">
        <div class="wrap">
            <div class="section-head section-head--center">
                <span class="eyebrow">{{ __('landing.faq.label') }}</span>
                <h2>{{ __('landing.faq.title') }}</h2>
            </div>

            <div class="faq">
                @foreach (__('landing.faq.items') as $item)
                    <details @if ($loop->first) open @endif>
                        <summary>{{ $item['q'] }}</summary>
                        <p>{{ $item['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Финальный экран --}}
    <section class="final">
        <div class="wrap">
            <div class="final-box">
                <h2>{{ __('landing.cta.title') }}</h2>
                <p>{{ __('landing.cta.lead') }}</p>
                <div class="final-actions">
                    @if (!empty($isAuthenticated))
                        <a href="{{ url('/dashboard') }}" class="btn btn--light">{{ __('menu.dashboard') }}</a>
                    @else
                        <a href="{{ url('/register') }}" class="btn btn--light">{{ __('landing.pricing.cta_free') }}</a>
                    @endif
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

<script>
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
