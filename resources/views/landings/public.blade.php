@php
    $settings = $landing->settings ?? [];
    $colorMap = [
        'indigo' => '#7f5af0',
        'emerald' => '#1b8f74',
        'sunset' => '#d86f52',
        'midnight' => '#433f72',
    ];
    $backgroundPresets = [
        'preset' => 'radial-gradient(circle at top, rgba(248, 226, 236, 0.95), transparent 34%), linear-gradient(160deg, #fffaf8 0%, #f7eef2 34%, #f3efe9 100%)',
        'midnight' => 'radial-gradient(circle at top, rgba(132, 104, 171, 0.28), transparent 34%), linear-gradient(160deg, #211f33 0%, #322f4d 42%, #5e597f 100%)',
        'sunset' => 'radial-gradient(circle at top, rgba(255, 210, 191, 0.95), transparent 36%), linear-gradient(160deg, #fff7f1 0%, #fde8de 42%, #f8efe8 100%)',
        'emerald' => 'radial-gradient(circle at top, rgba(202, 245, 229, 0.95), transparent 36%), linear-gradient(160deg, #fbfffd 0%, #eefaf5 38%, #edf4ef 100%)',
    ];
    $primaryColor = $colorMap[$settings['primary_color'] ?? 'indigo'] ?? '#7f5af0';
    $backgroundType = $settings['background_type'] ?? 'preset';
    $backgroundValue = $settings['background_value'] ?? null;
    $backgroundStyle = $backgroundType === 'upload' && $backgroundValue
        ? "url('" . e($backgroundValue) . "') center/cover no-repeat"
        : ($backgroundPresets[$backgroundValue ?? 'preset'] ?? $backgroundPresets['preset']);
    $proofItems = collect(preg_split('/\r\n|\r|\n/', (string) ($settings['proof_items_text'] ?? '')))
        ->map(fn ($item) => trim($item))
        ->filter()
        ->values();
    $faqItems = collect(preg_split('/\r\n|\r|\n/', (string) ($settings['faq_items_text'] ?? '')))
        ->map(fn ($item) => trim($item))
        ->filter()
        ->values();
    $benefitItems = collect(preg_split('/\r\n|\r|\n/', (string) ($settings['benefit_items_text'] ?? '')))
        ->map(fn ($item) => trim($item))
        ->filter()
        ->values();
    $phone = trim((string) ($settings['phone'] ?? ''));
    $phoneHref = $phone !== '' ? 'tel:' . preg_replace('/[^0-9+]+/', '', $phone) : null;
    $ctaLabel = $settings['cta_label'] ?? __('landings.public.default_cta');
    $secondaryCtaLabel = $settings['secondary_cta_label'] ?? __('landings.public.default_secondary_cta');
    $bookingHint = $settings['booking_hint'] ?? __('landings.public.booking_hint');
    $hasDirectContacts = $phoneHref || !empty($settings['telegram_url']) || !empty($settings['whatsapp_url']);
    $templateTitleMap = [
        'promotion' => $settings['headline'] ?? __('landings.templates.promotion.default_headline'),
        'service' => $settings['service_name'] ?? __('landings.templates.service.default_title'),
        'seasonal' => $settings['headline'] ?? __('landings.templates.seasonal.default_headline'),
        'consultation' => $settings['headline'] ?? __('landings.templates.consultation.default_headline'),
        'general' => __('landings.public.hero_title'),
    ];
    $templateDescriptionMap = [
        'promotion' => $settings['description'] ?? __('landings.templates.promotion.default_description'),
        'service' => $settings['service_description'] ?? __('landings.templates.service.default_description'),
        'seasonal' => $settings['description'] ?? __('landings.templates.seasonal.default_description'),
        'consultation' => $settings['description'] ?? __('landings.templates.consultation.default_description'),
        'general' => $settings['subtitle'] ?? __('landings.public.hero_description'),
    ];
    $heroTitle = $templateTitleMap[$landing->type] ?? __('landings.public.hero_title');
    $heroDescription = $templateDescriptionMap[$landing->type] ?? __('landings.public.hero_description');
    $heroPointDefaults = collect(data_get(trans('landings.public.hero_points'), $landing->type, []));
    $heroPoints = $benefitItems->isNotEmpty()
        ? $benefitItems->take(3)
        : ($proofItems->isNotEmpty() ? $proofItems->take(3) : $heroPointDefaults->take(3));
    $flowSteps = collect(trans('landings.public.flow_steps'));
    $showcaseServices = $featuredServices->take(3);
@endphp
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $landing->title }}</title>
    <style>
        :root {
            --primary-color: {{ $primaryColor }};
            --primary-strong: color-mix(in srgb, var(--primary-color) 82%, #2c2438);
            --bg-card: rgba(255, 252, 251, 0.86);
            --bg-card-strong: rgba(255, 250, 248, 0.96);
            --ink: #2d2430;
            --muted: rgba(45, 36, 48, 0.72);
            --shadow: 0 26px 60px rgba(77, 53, 67, 0.12);
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Manrope", "Segoe UI", sans-serif;
            color: var(--ink);
            background: {{ $backgroundStyle }};
            position: relative;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            border-radius: 999px;
            pointer-events: none;
            filter: blur(18px);
            opacity: 0.72;
        }

        body::before {
            width: 18rem;
            height: 18rem;
            top: 4rem;
            left: -5rem;
            background: color-mix(in srgb, var(--primary-color) 14%, white);
        }

        body::after {
            width: 24rem;
            height: 24rem;
            right: -6rem;
            bottom: 5rem;
            background: rgba(242, 211, 204, 0.52);
        }

        .landing-page {
            max-width: 1240px;
            margin: 0 auto;
            padding: 26px 18px 40px;
            position: relative;
            z-index: 1;
        }

        .landing-shell {
            position: relative;
            overflow: hidden;
            background: linear-gradient(180deg, rgba(255, 253, 252, 0.78), rgba(255, 249, 247, 0.94));
            border: 1px solid rgba(255, 255, 255, 0.75);
            border-radius: 40px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(24px);
        }

        .landing-shell::before,
        .landing-shell::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            background: color-mix(in srgb, var(--primary-color) 12%, white);
            z-index: 0;
        }

        .landing-shell::before {
            width: 24rem;
            height: 24rem;
            top: -10rem;
            right: -8rem;
        }

        .landing-shell::after {
            width: 18rem;
            height: 18rem;
            left: -7rem;
            bottom: 8rem;
            background: rgba(250, 224, 214, 0.68);
        }

        .landing-topbar,
        .landing-hero,
        .landing-main,
        .landing-sticky-cta {
            position: relative;
            z-index: 1;
        }

        .landing-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.1rem 1.35rem;
        }

        .landing-brand {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .landing-brand-mark {
            width: 2.15rem;
            height: 2.15rem;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: "Cormorant Garamond", "Georgia", serif;
            font-size: 1.2rem;
            background: linear-gradient(145deg, color-mix(in srgb, var(--primary-color) 18%, white), rgba(255, 255, 255, 0.96));
            color: var(--primary-strong);
        }

        .landing-preview-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.55rem 0.85rem;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--primary-strong);
            background: color-mix(in srgb, var(--primary-color) 11%, white);
        }

        .landing-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(340px, 0.92fr);
            gap: 1.4rem;
            padding: 0 1.5rem 1.25rem;
        }

        .landing-hero-copy,
        .landing-conversion-card,
        .landing-section-card,
        .landing-story-card,
        .landing-contact-panel {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.78);
            border-radius: 34px;
            box-shadow: 0 18px 42px rgba(118, 83, 104, 0.08);
            backdrop-filter: blur(20px);
        }

        .landing-hero-copy,
        .landing-story-card,
        .landing-section-card,
        .landing-contact-panel {
            padding: 1.45rem;
        }

        .landing-hero-copy {
            padding: 2rem;
        }

        .landing-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.9rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.82);
            color: var(--primary-strong);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .landing-kicker::before {
            content: "";
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 50%;
            background: var(--primary-color);
        }

        .landing-hero h1 {
            margin: 1.25rem 0 0;
            max-width: 12ch;
            font-family: "Cormorant Garamond", "Georgia", serif;
            font-size: clamp(3.4rem, 8vw, 5.6rem);
            line-height: 0.9;
            letter-spacing: -0.05em;
            font-weight: 600;
        }

        .landing-subtitle,
        .landing-form-note,
        .landing-booking-meta,
        .landing-section-head p,
        .landing-service-meta,
        .landing-step-card p {
            color: var(--muted);
            line-height: 1.7;
        }

        .landing-subtitle {
            max-width: 56ch;
            margin-top: 1rem;
            font-size: 1.05rem;
        }

        .landing-point-list,
        .landing-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 0.8rem;
        }

        .landing-point-list {
            margin-top: 1.6rem;
        }

        .landing-point-list li,
        .landing-list li {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            padding: 0.95rem 1rem;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.74);
            border: 1px solid rgba(255, 255, 255, 0.82);
            color: var(--ink);
            line-height: 1.55;
        }

        .landing-point-list li::before,
        .landing-list li::before {
            content: "";
            width: 0.72rem;
            height: 0.72rem;
            flex: none;
            border-radius: 50%;
            margin-top: 0.42rem;
            background: var(--primary-color);
        }

        .landing-cta-row,
        .landing-contact-row,
        .landing-contact-strip,
        .landing-service-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .landing-cta-row {
            margin-top: 1.6rem;
        }

        .landing-btn,
        .landing-btn-secondary,
        .landing-contact-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 52px;
            padding: 0.95rem 1.25rem;
            border-radius: 999px;
            font-weight: 800;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .landing-btn:hover,
        .landing-btn-secondary:hover,
        .landing-contact-chip:hover {
            transform: translateY(-1px);
        }

        .landing-btn {
            color: #fffdfd;
            background: linear-gradient(135deg, var(--primary-color), color-mix(in srgb, var(--primary-color) 76%, #b86d97));
            box-shadow: 0 18px 36px color-mix(in srgb, var(--primary-color) 24%, transparent);
            border: none;
            cursor: pointer;
        }

        .landing-btn[disabled] {
            opacity: 0.7;
            cursor: wait;
        }

        .landing-btn-secondary,
        .landing-contact-chip {
            color: var(--ink);
            background: rgba(255, 255, 255, 0.78);
            border: 1px solid rgba(255, 255, 255, 0.88);
            box-shadow: 0 10px 24px rgba(118, 83, 104, 0.08);
        }

        .landing-trust-note {
            margin-top: 1rem;
            color: var(--muted);
            line-height: 1.65;
        }

        .landing-conversion-card {
            padding: 1.2rem;
            position: relative;
            overflow: hidden;
        }

        .landing-form-shell {
            position: relative;
            z-index: 1;
            padding: 1.45rem;
            border-radius: 28px;
            background: var(--bg-card-strong);
            border: 1px solid rgba(255, 255, 255, 0.9);
        }

        .landing-form-topline {
            color: var(--primary-strong);
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .landing-form-shell h2 {
            margin: 0.75rem 0 0;
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            line-height: 1.05;
            letter-spacing: -0.04em;
        }

        .landing-form-shell p {
            margin: 0.8rem 0 0;
            color: var(--muted);
            line-height: 1.65;
        }

        .landing-contact-strip {
            margin-top: 1rem;
        }

        .landing-contact-strip span,
        .landing-service-tags span,
        .landing-story-item span,
        .landing-step-index {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 0.6rem 0.85rem;
            border-radius: 999px;
            font-size: 0.84rem;
            color: var(--primary-strong);
            background: color-mix(in srgb, var(--primary-color) 10%, white);
        }

        .landing-story-item span,
        .landing-step-index {
            width: 2rem;
            min-height: 2rem;
            padding: 0;
            font-weight: 800;
        }

        .landing-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
            margin-top: 1.35rem;
        }

        .landing-field,
        .landing-field-full {
            display: grid;
            gap: 0.45rem;
        }

        .landing-field-full {
            grid-column: 1 / -1;
        }

        .landing-field label,
        .landing-field-full label {
            font-size: 0.9rem;
            font-weight: 700;
            color: rgba(45, 36, 48, 0.86);
        }

        .landing-field input,
        .landing-field select,
        .landing-field textarea,
        .landing-field-full input,
        .landing-field-full select,
        .landing-field-full textarea {
            width: 100%;
            padding: 0.95rem 1rem;
            border-radius: 18px;
            border: 1px solid rgba(113, 83, 101, 0.15);
            background: rgba(255, 255, 255, 0.94);
            color: var(--ink);
            font: inherit;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .landing-field input:focus,
        .landing-field select:focus,
        .landing-field textarea:focus,
        .landing-field-full input:focus,
        .landing-field-full select:focus,
        .landing-field-full textarea:focus {
            border-color: color-mix(in srgb, var(--primary-color) 30%, white);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--primary-color) 10%, white);
        }

        .landing-field textarea,
        .landing-field-full textarea {
            min-height: 112px;
            resize: vertical;
        }

        .landing-form-helper {
            margin-top: 0.65rem;
            font-size: 0.88rem;
            color: var(--muted);
        }

        .landing-form-message {
            display: none;
            margin-top: 1rem;
            padding: 0.9rem 1rem;
            border-radius: 18px;
            font-weight: 700;
        }

        .landing-form-message.is-success {
            display: block;
            color: #13664b;
            background: #e9fbf1;
        }

        .landing-form-message.is-error {
            display: block;
            color: #8a2e3b;
            background: #fdeef1;
        }

        .landing-main {
            display: grid;
            gap: 1.15rem;
            padding: 0 1.5rem 1.5rem;
        }

        .landing-section-head {
            margin-bottom: 1rem;
        }

        .landing-section-head h2 {
            margin: 0;
            font-size: clamp(1.65rem, 3vw, 2.4rem);
            line-height: 1.08;
            letter-spacing: -0.04em;
        }

        .landing-story-grid,
        .landing-service-grid,
        .landing-proof-grid,
        .landing-flow-grid {
            display: grid;
            gap: 0.9rem;
        }

        .landing-story-grid,
        .landing-proof-grid,
        .landing-flow-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .landing-service-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .landing-story-item,
        .landing-service-card,
        .landing-proof-card,
        .landing-step-card {
            padding: 1.15rem;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.88);
        }

        .landing-story-item strong,
        .landing-service-card strong,
        .landing-proof-card strong {
            display: block;
            font-size: 1.05rem;
            line-height: 1.35;
        }

        .landing-service-meta {
            margin-top: 0.7rem;
        }

        .landing-step-card h3 {
            margin: 0.85rem 0 0.4rem;
            font-size: 1.08rem;
        }

        .landing-sticky-cta {
            display: none;
        }

        @media (max-width: 1080px) {
            .landing-hero,
            .landing-story-grid,
            .landing-proof-grid,
            .landing-flow-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .landing-page {
                padding: 10px 10px 24px;
            }

            .landing-shell {
                border-radius: 28px;
            }

            .landing-topbar,
            .landing-hero,
            .landing-main {
                padding-left: 0.9rem;
                padding-right: 0.9rem;
            }

            .landing-topbar {
                padding-top: 0.95rem;
                padding-bottom: 0.65rem;
                align-items: flex-start;
                flex-direction: column;
            }

            .landing-hero-copy,
            .landing-conversion-card,
            .landing-story-card,
            .landing-section-card,
            .landing-contact-panel {
                padding: 1.1rem;
                border-radius: 24px;
            }

            .landing-hero h1 {
                font-size: clamp(2.8rem, 16vw, 4.1rem);
            }

            .landing-form-grid {
                grid-template-columns: 1fr;
            }

            .landing-cta-row .landing-btn,
            .landing-cta-row .landing-btn-secondary {
                width: 100%;
            }

            .landing-sticky-cta {
                position: sticky;
                bottom: 0;
                z-index: 5;
                display: block;
                padding: 0.8rem 0.9rem 0.95rem;
                background: linear-gradient(180deg, rgba(247, 237, 241, 0), rgba(247, 237, 241, 0.9) 36%, rgba(247, 237, 241, 0.98) 100%);
            }

            .landing-sticky-cta .landing-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="landing-page">
        <main class="landing-shell">
            <div class="landing-topbar">
                <div class="landing-brand">
                    <span class="landing-brand-mark">{{ mb_substr($landing->title, 0, 1) }}</span>
                    <span>{{ $landing->title }}</span>
                </div>
                @if($isPreview)
                    <span class="landing-preview-badge">{{ __('landings.public.preview_badge') }}</span>
                @endif
            </div>

            <section class="landing-hero">
                <div class="landing-hero-copy">
                    <span class="landing-kicker">{{ __('landings.types.' . $landing->type) }}</span>
                    <h1>{{ $heroTitle }}</h1>
                    <div class="landing-subtitle">{{ $heroDescription }}</div>

                    @if($heroPoints->isNotEmpty())
                        <ul class="landing-point-list">
                            @foreach($heroPoints as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="landing-cta-row">
                        <a href="#booking" class="landing-btn">{{ $ctaLabel }}</a>
                        @if($hasDirectContacts)
                            <a href="#contacts" class="landing-btn-secondary">{{ $secondaryCtaLabel }}</a>
                        @endif
                    </div>

                    <div class="landing-trust-note">
                        {{ __('landings.public.trust_line') }}
                    </div>
                </div>

                <aside class="landing-conversion-card" id="booking">
                    <div class="landing-form-shell">
                        <div class="landing-form-topline">{{ __('landings.public.direct_label') }}</div>
                        <h2>{{ __('landings.public.booking_title') }}</h2>
                        <p>{{ __('landings.public.booking_text') }}</p>

                        <div class="landing-contact-strip">
                            <span>{{ $bookingHint }}</span>
                            <span>{{ __('landings.public.form.note') }}</span>
                        </div>

                        <form id="landing-request-form">
                            <div class="landing-form-grid">
                                <div class="landing-field">
                                    <label for="landing-request-name">{{ __('landings.public.form.name') }}</label>
                                    <input type="text" id="landing-request-name" name="client_name" required />
                                </div>
                                <div class="landing-field">
                                    <label for="landing-request-phone">{{ __('landings.public.form.phone') }}</label>
                                    <input type="text" id="landing-request-phone" name="client_phone" required />
                                </div>
                                @if($featuredServices->isNotEmpty())
                                    <div class="landing-field-full">
                                        <label for="landing-request-service">{{ __('landings.public.form.service') }}</label>
                                        <select id="landing-request-service" name="service_id">
                                            <option value="">{{ __('landings.public.form.service_placeholder') }}</option>
                                            @foreach($featuredServices as $service)
                                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="landing-form-helper">{{ __('landings.public.form_service_hint') }}</div>
                                    </div>
                                @endif
                                <div class="landing-field-full">
                                    <label for="landing-request-message">{{ __('landings.public.form.message') }}</label>
                                    <textarea id="landing-request-message" name="message"></textarea>
                                </div>
                            </div>

                            <button type="submit" class="landing-btn" style="margin-top: 1rem;">{{ $ctaLabel }}</button>
                            <div class="landing-form-message" id="landing-request-message-box"></div>
                        </form>

                        @if($hasDirectContacts)
                            <div class="landing-contact-row" id="contacts" style="margin-top: 1rem;">
                                @if($phoneHref)
                                    <a class="landing-contact-chip" href="{{ $phoneHref }}">{{ $phone }}</a>
                                @endif
                                @if(!empty($settings['whatsapp_url']))
                                    <a class="landing-contact-chip" href="{{ $settings['whatsapp_url'] }}" target="_blank" rel="noopener">WhatsApp</a>
                                @endif
                                @if(!empty($settings['telegram_url']))
                                    <a class="landing-contact-chip" href="{{ $settings['telegram_url'] }}" target="_blank" rel="noopener">Telegram</a>
                                @endif
                            </div>
                        @endif
                    </div>
                </aside>
            </section>

            <section class="landing-main">
                @if($showcaseServices->isNotEmpty())
                    <section class="landing-story-card">
                        <div class="landing-section-head">
                            <h2>{{ __('landings.public.story_title') }}</h2>
                            <p>{{ __('landings.public.story_text') }}</p>
                        </div>
                        <div class="landing-story-grid">
                            @foreach($showcaseServices as $service)
                                <article class="landing-story-item">
                                    <span>{{ $loop->iteration }}</span>
                                    <strong>{{ $service->name }}</strong>
                                    <div class="landing-service-meta">{{ __('landings.templates.general.service_note') }}</div>
                                    <div class="landing-service-tags">
                                        @if(!empty($service->base_price))
                                            <span>от {{ number_format((float) $service->base_price, 0, ',', ' ') }} ₽</span>
                                        @endif
                                        @if(!empty($service->duration_min))
                                            <span>{{ (int) $service->duration_min }} мин</span>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                @include($template, ['landing' => $landing, 'settings' => $settings, 'featuredServices' => $featuredServices, 'proofItems' => $proofItems])
 
                <section class="landing-section-card">
                    <div class="landing-section-head">
                        <h2>{{ __('landings.public.flow_title') }}</h2>
                        <p>{{ __('landings.public.flow_text') }}</p>
                    </div>
                    <div class="landing-flow-grid">
                        @foreach($flowSteps as $step)
                            <article class="landing-step-card">
                                <div class="landing-step-index">{{ $loop->iteration }}</div>
                                <h3>{{ $step['title'] ?? '' }}</h3>
                                <p>{{ $step['text'] ?? '' }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                @if($proofItems->isNotEmpty())
                    <section class="landing-section-card">
                        <div class="landing-section-head">
                            <h2>{{ __('landings.public.proof_title') }}</h2>
                            <p>{{ __('landings.public.proof_hint') }}</p>
                        </div>
                        <div class="landing-proof-grid">
                            @foreach($proofItems->take(3) as $item)
                                <article class="landing-proof-card">
                                    <strong>{{ $item }}</strong>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if($hasDirectContacts || !empty($settings['address']))
                    <section class="landing-contact-panel">
                        <div class="landing-section-head">
                            <h2>{{ __('landings.public.alternate_title') }}</h2>
                            <p>{{ __('landings.public.alternate_text') }}</p>
                        </div>
                        <div class="landing-contact-row">
                            @if($phoneHref)
                                <a class="landing-contact-chip" href="{{ $phoneHref }}">{{ __('landings.public.form.call') }}</a>
                            @endif
                            @if(!empty($settings['whatsapp_url']))
                                <a class="landing-contact-chip" href="{{ $settings['whatsapp_url'] }}" target="_blank" rel="noopener">WhatsApp</a>
                            @endif
                            @if(!empty($settings['telegram_url']))
                                <a class="landing-contact-chip" href="{{ $settings['telegram_url'] }}" target="_blank" rel="noopener">Telegram</a>
                            @endif
                        </div>
                        @if(!empty($settings['address']))
                            <div class="landing-booking-meta">{{ $settings['address'] }}</div>
                        @endif
                    </section>
                @endif

                @if($faqItems->isNotEmpty())
                    <section class="landing-section-card">
                        <div class="landing-section-head">
                            <h2>{{ __('landings.public.faq_title') }}</h2>
                        </div>
                        <ul class="landing-list">
                            @foreach($faqItems as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </section>

            <div class="landing-sticky-cta">
                <a href="#booking" class="landing-btn">{{ $ctaLabel }}</a>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('landing-request-form');
            const messageBox = document.getElementById('landing-request-message-box');
            const submitButton = form ? form.querySelector('button[type="submit"]') : null;

            if (!form || !messageBox || !submitButton) return;

            const getFieldValue = function (name) {
                const field = form.elements.namedItem(name);
                return field && 'value' in field ? String(field.value).trim() : '';
            };

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                submitButton.disabled = true;
                messageBox.className = 'landing-form-message';
                messageBox.textContent = '';
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                const payload = {
                    client_name: getFieldValue('client_name'),
                    client_phone: getFieldValue('client_phone'),
                    client_email: null,
                    preferred_date: null,
                    message: getFieldValue('message'),
                    service_id: getFieldValue('service_id') || null,
                };

                fetch('{{ route('landings.request', ['slug' => $landing->slug]) }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                    body: JSON.stringify(payload),
                })
                    .then(function (response) {
                        if (response.status === 422) {
                            return response.json().then(function (data) { throw data; });
                        }
                        if (!response.ok) {
                            throw new Error('failed');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        form.reset();
                        messageBox.className = 'landing-form-message is-success';
                        messageBox.textContent = data.message || '{{ __('landings.public.request_saved') }}';
                    })
                    .catch(function (error) {
                        const fieldErrors = error?.errors || error?.error?.fields;
                        const text = fieldErrors
                            ? Object.values(fieldErrors).flat().join(' ')
                            : '{{ __('landings.public.request_failed') }}';
                        messageBox.className = 'landing-form-message is-error';
                        messageBox.textContent = text;
                    })
                    .finally(function () {
                        submitButton.disabled = false;
                    });
            });
        });
    </script>
</body>
</html>
