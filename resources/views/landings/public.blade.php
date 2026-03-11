@php
    $settings = $landing->settings ?? [];
    $colorMap = [
        'indigo' => '#5b5bd6',
        'emerald' => '#0f9f76',
        'sunset' => '#f26a3d',
        'midnight' => '#17324d',
    ];
    $backgroundPresets = [
        'preset' => 'linear-gradient(135deg, #f6f2ff 0%, #eef7ff 42%, #fff6ef 100%)',
        'midnight' => 'linear-gradient(130deg, #122033 0%, #1e3350 45%, #4d7398 100%)',
        'sunset' => 'linear-gradient(135deg, #fff1e8 0%, #ffe6d6 40%, #fffaf3 100%)',
        'emerald' => 'linear-gradient(135deg, #edf8f4 0%, #e6fbf5 44%, #f9fffc 100%)',
    ];
    $primaryColor = $colorMap[$settings['primary_color'] ?? 'indigo'] ?? '#5b5bd6';
    $backgroundType = $settings['background_type'] ?? 'preset';
    $backgroundValue = $settings['background_value'] ?? null;
    $backgroundStyle = $backgroundType === 'upload' && $backgroundValue
        ? "url('" . e($backgroundValue) . "') center/cover no-repeat"
        : ($backgroundPresets[$backgroundValue ?? 'preset'] ?? $backgroundPresets['preset']);
    $subtitle = $settings['subtitle'] ?? null;
    $proofItems = collect(preg_split('/\r\n|\r|\n/', (string) ($settings['proof_items_text'] ?? '')))
        ->map(fn ($item) => trim($item))
        ->filter()
        ->values();
    $faqItems = collect(preg_split('/\r\n|\r|\n/', (string) ($settings['faq_items_text'] ?? '')))
        ->map(fn ($item) => trim($item))
        ->filter()
        ->values();
    $phone = trim((string) ($settings['phone'] ?? ''));
    $phoneHref = $phone !== '' ? 'tel:' . preg_replace('/[^0-9+]+/', '', $phone) : null;
    $ctaLabel = $settings['cta_label'] ?? __('landings.public.default_cta');
    $secondaryCtaLabel = $settings['secondary_cta_label'] ?? __('landings.public.default_secondary_cta');
    $bookingHint = $settings['booking_hint'] ?? __('landings.public.booking_hint');
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
            --primary-soft: color-mix(in srgb, {{ $primaryColor }} 12%, white);
            --ink: #132238;
            --muted: rgba(19, 34, 56, 0.68);
            --panel: rgba(255, 255, 255, 0.88);
            --line: rgba(19, 34, 56, 0.12);
            --shadow: 0 30px 70px rgba(19, 34, 56, 0.14);
        }

        * { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Manrope", "Segoe UI", sans-serif;
            color: var(--ink);
            background: {{ $backgroundStyle }};
        }

        .landing-page {
            max-width: 1180px;
            margin: 0 auto;
            padding: 28px 20px 48px;
        }

        .landing-shell {
            background: var(--panel);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(255, 255, 255, 0.55);
            border-radius: 32px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .landing-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.52);
        }

        .landing-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .landing-brand-dot {
            width: 0.85rem;
            height: 0.85rem;
            border-radius: 50%;
            background: var(--primary-color);
            box-shadow: 0 0 0 8px color-mix(in srgb, var(--primary-color) 14%, white);
        }

        .landing-preview-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.4rem 0.7rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--primary-color);
            background: color-mix(in srgb, var(--primary-color) 12%, white);
        }

        .landing-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(320px, 0.9fr);
            gap: 1.5rem;
            padding: 2rem;
        }

        .landing-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 999px;
            padding: 0.45rem 0.8rem;
            background: color-mix(in srgb, var(--primary-color) 10%, white);
            color: var(--primary-color);
            font-weight: 700;
            font-size: 0.78rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .landing-hero h1 {
            margin: 1rem 0 0;
            font-size: clamp(2.4rem, 5vw, 4.6rem);
            line-height: 0.96;
            letter-spacing: -0.05em;
        }

        .landing-subtitle {
            max-width: 54ch;
            margin-top: 1rem;
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.65;
        }

        .landing-cta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.9rem;
            margin-top: 1.4rem;
        }

        .landing-btn,
        .landing-btn-secondary,
        .landing-contact-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            min-height: 50px;
            padding: 0.9rem 1.15rem;
            border-radius: 999px;
            font-weight: 700;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .landing-btn:hover,
        .landing-btn-secondary:hover,
        .landing-contact-chip:hover {
            transform: translateY(-1px);
        }

        .landing-btn {
            color: #fff;
            background: var(--primary-color);
            box-shadow: 0 18px 35px color-mix(in srgb, var(--primary-color) 28%, transparent);
        }

        .landing-btn-secondary {
            color: var(--ink);
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid var(--line);
        }

        .landing-side-card,
        .landing-section-card,
        .landing-booking-card {
            border: 1px solid var(--line);
            border-radius: 26px;
            background: rgba(255, 255, 255, 0.75);
        }

        .landing-side-card {
            padding: 1.4rem;
            display: grid;
            gap: 1rem;
            align-content: start;
        }

        .landing-stat-grid,
        .landing-proof-grid,
        .landing-service-grid,
        .landing-metric-grid {
            display: grid;
            gap: 0.9rem;
        }

        .landing-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .landing-metric,
        .landing-proof-card,
        .landing-service-card {
            border-radius: 20px;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid var(--line);
        }

        .landing-metric strong {
            display: block;
            font-size: 1.3rem;
            margin-top: 0.25rem;
        }

        .landing-main {
            display: grid;
            gap: 1.25rem;
            padding: 0 2rem 2rem;
        }

        .landing-section-card {
            padding: 1.5rem;
        }

        .landing-section-head {
            margin-bottom: 1rem;
        }

        .landing-section-head h2 {
            margin: 0;
            font-size: clamp(1.5rem, 2vw, 2.1rem);
            letter-spacing: -0.03em;
        }

        .landing-section-head p {
            margin: 0.45rem 0 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .landing-service-grid,
        .landing-proof-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .landing-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 0.75rem;
        }

        .landing-list li {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
            color: var(--muted);
            line-height: 1.55;
        }

        .landing-list li::before {
            content: "";
            width: 0.72rem;
            height: 0.72rem;
            flex: none;
            border-radius: 50%;
            margin-top: 0.35rem;
            background: var(--primary-color);
            box-shadow: 0 0 0 6px color-mix(in srgb, var(--primary-color) 14%, white);
        }

        .landing-contact-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .landing-contact-chip {
            color: var(--ink);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid var(--line);
        }

        .landing-booking-card {
            padding: 1.5rem;
        }

        .landing-booking-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 0.95fr);
            gap: 1.2rem;
        }

        .landing-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.9rem;
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
            font-size: 0.92rem;
            font-weight: 700;
        }

        .landing-field input,
        .landing-field select,
        .landing-field textarea,
        .landing-field-full input,
        .landing-field-full textarea {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 0.9rem 1rem;
            background: rgba(255, 255, 255, 0.95);
            color: var(--ink);
            font: inherit;
        }

        .landing-field textarea,
        .landing-field-full textarea {
            min-height: 120px;
            resize: vertical;
        }

        .landing-form-note,
        .landing-booking-meta {
            color: var(--muted);
            line-height: 1.6;
        }

        .landing-form-message {
            display: none;
            margin-top: 1rem;
            padding: 0.9rem 1rem;
            border-radius: 16px;
            font-weight: 600;
        }

        .landing-form-message.is-success {
            display: block;
            color: #0d6b4e;
            background: #e8fbf2;
        }

        .landing-form-message.is-error {
            display: block;
            color: #8a1d2f;
            background: #fdecef;
        }

        .landing-sticky-cta {
            display: none;
        }

        @media (max-width: 960px) {
            .landing-hero,
            .landing-booking-grid,
            .landing-form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .landing-page {
                padding: 14px 12px 30px;
            }

            .landing-shell {
                border-radius: 24px;
            }

            .landing-hero,
            .landing-main {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .landing-topbar {
                padding: 0.95rem 1rem;
            }

            .landing-sticky-cta {
                position: sticky;
                bottom: 0;
                z-index: 10;
                display: block;
                padding: 0.75rem 1rem 1rem;
                background: linear-gradient(180deg, rgba(246, 247, 251, 0), rgba(246, 247, 251, 0.86) 38%, rgba(246, 247, 251, 0.98) 100%);
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
                    <span class="landing-brand-dot"></span>
                    <span>{{ $landing->title }}</span>
                </div>
                @if($isPreview)
                    <span class="landing-preview-badge">{{ __('landings.public.preview_badge') }}</span>
                @endif
            </div>

            <section class="landing-hero">
                <div>
                    <span class="landing-kicker">{{ __('landings.types.' . $landing->type) }}</span>
                    <h1>{{ $landing->title }}</h1>
                    @if($subtitle)
                        <div class="landing-subtitle">{{ $subtitle }}</div>
                    @endif
                    <div class="landing-cta-row">
                        <a href="#booking" class="landing-btn">{{ $ctaLabel }}</a>
                        @if($phoneHref || !empty($settings['telegram_url']) || !empty($settings['whatsapp_url']))
                            <a href="#contacts" class="landing-btn-secondary">{{ $secondaryCtaLabel }}</a>
                        @endif
                    </div>
                </div>

                <aside class="landing-side-card">
                    <div class="landing-stat-grid">
                        <div class="landing-metric">
                            <span>{{ __('landings.public.metric_one') }}</span>
                            <strong>{{ count($featuredServices) ?: 1 }}</strong>
                        </div>
                        <div class="landing-metric">
                            <span>{{ __('landings.public.metric_two') }}</span>
                            <strong>{{ $bookingHint }}</strong>
                        </div>
                    </div>
                    @if(!empty($settings['bonus_text']))
                        <div class="landing-proof-card">
                            <strong>{{ __('landings.public.bonus_title') }}</strong>
                            <div class="landing-booking-meta">{{ $settings['bonus_text'] }}</div>
                        </div>
                    @endif
                    @if($phone || !empty($settings['telegram_url']) || !empty($settings['whatsapp_url']) || !empty($settings['address']))
                        <div id="contacts">
                            <strong>{{ __('landings.public.contacts_title') }}</strong>
                            <div class="landing-contact-row" style="margin-top: 0.85rem;">
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
                            @if(!empty($settings['address']))
                                <div class="landing-booking-meta" style="margin-top: 0.85rem;">{{ $settings['address'] }}</div>
                            @endif
                        </div>
                    @endif
                </aside>
            </section>

            <section class="landing-main">
                @include($template, ['landing' => $landing, 'settings' => $settings, 'featuredServices' => $featuredServices, 'proofItems' => $proofItems])

                @if($proofItems->isNotEmpty())
                    <section class="landing-section-card">
                        <div class="landing-section-head">
                            <h2>{{ __('landings.public.proof_title') }}</h2>
                            <p>{{ __('landings.public.proof_hint') }}</p>
                        </div>
                        <div class="landing-proof-grid">
                            @foreach($proofItems as $item)
                                <article class="landing-proof-card">{{ $item }}</article>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="landing-booking-card" id="booking">
                    <div class="landing-booking-grid">
                        <div>
                            <div class="landing-section-head">
                                <h2>{{ __('landings.public.booking_title') }}</h2>
                                <p>{{ __('landings.public.booking_text') }}</p>
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
                                    <div class="landing-field">
                                        <label for="landing-request-email">{{ __('landings.public.form.email') }}</label>
                                        <input type="email" id="landing-request-email" name="client_email" />
                                    </div>
                                    <div class="landing-field">
                                        <label for="landing-request-date">{{ __('landings.public.form.preferred_date') }}</label>
                                        <input type="date" id="landing-request-date" name="preferred_date" />
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
                                        </div>
                                    @endif
                                    <div class="landing-field-full">
                                        <label for="landing-request-message">{{ __('landings.public.form.message') }}</label>
                                        <textarea id="landing-request-message" name="message"></textarea>
                                    </div>
                                </div>
                                <button type="submit" class="landing-btn" style="margin-top: 1rem;">{{ $ctaLabel }}</button>
                                <div class="landing-form-note" style="margin-top: 0.85rem;">{{ __('landings.public.form.note') }}</div>
                                <div class="landing-form-message" id="landing-request-message-box"></div>
                            </form>
                        </div>
                        <div>
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
                        </div>
                    </div>
                </section>

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

            if (!form || !messageBox) return;

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                messageBox.className = 'landing-form-message';
                messageBox.textContent = '';
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                const payload = {
                    client_name: form.client_name.value.trim(),
                    client_phone: form.client_phone.value.trim(),
                    client_email: form.client_email.value.trim(),
                    preferred_date: form.preferred_date.value || null,
                    message: form.message.value.trim(),
                    service_id: form.service_id ? (form.service_id.value || null) : null,
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
                    });
            });
        });
    </script>
</body>
</html>
