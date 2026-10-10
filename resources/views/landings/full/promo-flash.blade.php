<!--
Original Veloria layout (license: free-commercial, see public/landing-templates/promo-flash/LICENSE.txt).
The offer block is the point of the page: the discount, the promo code and a countdown to the
promotion's own deadline (settings.ends_at). Without an offer it degrades to a plain booking page.
-->
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/promo-flash/' . $path);
    $price = fn ($value) => number_format((float) $value, 0, ',', ' ');

    $deadline = null;
    if (! empty($settings['ends_at'])) {
        try {
            $deadline = \Illuminate\Support\Carbon::parse($settings['ends_at'])->endOfDay();
        } catch (\Throwable $e) {
            $deadline = null;
        }
    }
    $running = $deadline && $deadline->isFuture();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <title>{{ $landing->title }}</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $heroText), 150) }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&amp;subset=cyrillic&amp;display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ $asset('style.css') }}">
    </head>
    <body>
        @if($isPreview && empty($isEdit))
            <div style="position: fixed; top: 0; left: 0; right: 0; z-index: 2000; background: #111; color: #fff; text-align: center; font-size: 12px; padding: 2px 0;">{{ __('landings.public.preview_badge') }}</div>
        @endif

        <header class="pf-hero" id="top">
            <div class="pf-wrap">
                <div class="pf-top">
                    <x-landing.text key="title" tag="span" />
                    @if($phoneHref || $editing)
                        <a href="{{ $phoneHref ?: '#' }}"><x-landing.text key="phone" tag="bdi" /></a>
                    @endif
                </div>

                @if($hasOffer)
                    <span class="pf-kicker"><x-landing.text key="lbl_pretty_nav_offer" tag="bdi" :default="__('landings.pretty.nav_offer')" :title-fallback="false" /></span>
                    <div class="pf-percent">−{{ $offerPercent }}%</div>
                @endif

                <x-landing.text key="hero_title" tag="h1" class="pf-title" :default="__('landings.common.hero_default')" :title-fallback="false" />
                @if($editing || filled($heroText))
                    <x-landing.text key="hero_text" tag="p" class="pf-text" />
                @endif

                @if($hasOffer && $promoCode)
                    <div class="pf-code">{{ $promoCode }}</div>
                    <span class="pf-code-note">{{ __('landings.promo.say_code') }}</span>
                @endif

                @if($hasOffer && $deadline)
                    @if($running)
                        <p class="pf-until">{{ __('landings.promo.timer_left') }}</p>
                        <div class="pf-timer" id="pf-timer" data-end="{{ $deadline->toIso8601String() }}">
                            <div><b data-u="d">0</b><span>{{ __('landings.promo.days') }}</span></div>
                            <div><b data-u="h">0</b><span>{{ __('landings.promo.hours') }}</span></div>
                            <div><b data-u="m">0</b><span>{{ __('landings.promo.minutes') }}</span></div>
                            <div><b data-u="s">0</b><span>{{ __('landings.promo.seconds') }}</span></div>
                        </div>
                    @else
                        <p class="pf-until">{{ __('landings.promo.ended') }}</p>
                    @endif
                @elseif($hasOffer && $endsAt)
                    <p class="pf-until">{{ __('landings.pretty.offer_until', ['date' => $endsAt]) }}</p>
                @endif

                <a class="pf-btn" href="#booking"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
            </div>
        </header>

        @if($cards->isNotEmpty())
            <section class="pf-section" id="services">
                <div class="pf-wrap">
                    <h2>{{ $hasOffer ? __('landings.promo.services_title') : __('landings.common.services_title') }}</h2>
                    <div class="pf-grid">
                        @foreach($cards->take(6) as $card)
                            <div class="pf-card">
                                <strong>{{ $card['name'] }}</strong>
                                @if(!empty($card['duration']))<small>{{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}</small>@endif
                                @if(!empty($card['price']))<span class="pf-price">{{ $price($card['price']) }} ₽</span>@endif
                                <a href="#booking" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($editing || $proofItems->isNotEmpty())
            <section class="pf-section" style="padding-top: 0">
                <div class="pf-wrap">
                    <ul class="pf-list">
                        @foreach($proofItems->take(5) as $item)
                            <x-landing.text key="proof_items_text" :index="$loop->index" tag="li" />
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif

        <section class="pf-section" id="booking">
            <div class="pf-wrap">
                <h2><x-landing.text key="lbl_common_booking_title" tag="bdi" :default="__('landings.common.booking_title')" :title-fallback="false" /></h2>
                @if($editing || filled($settings['booking_hint'] ?? null))
                    <x-landing.text key="booking_hint" tag="p" class="pf-lead" />
                @endif
                <form id="request-form" class="pf-form" novalidate>
                    <input type="text" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120" autocomplete="name">
                    <input type="tel" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask>
                    <select name="service_id" id="request-service">
                        <option value="">{{ __('landings.salone.field_service_any') }}</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                        @endforeach
                    </select>
                    <textarea name="message" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000"></textarea>
                    <div id="request-picker"></div>
                    <button id="request-submit" type="submit">{{ __('landings.salone.submit') }}</button>
                    <div id="request-message" role="status" aria-live="polite"></div>
                </form>
            </div>
        </section>

        <footer class="pf-foot">
            <div class="pf-wrap">
                @if($address !== '' || $editing)<x-landing.text key="address" tag="div" />@endif
                @foreach($hours as $line)<div>{{ $line['days'] }}: {{ $line['from'] }}–{{ $line['to'] }}</div>@endforeach
                @if($telegram || $whatsapp)
                    <div>
                        @if($telegram)<a href="{{ $telegram }}" target="_blank" rel="noopener">Telegram</a>@endif
                        @if($whatsapp)<a href="{{ $whatsapp }}" target="_blank" rel="noopener">WhatsApp</a>@endif
                    </div>
                @endif
                <div>&copy; {{ date('Y') }} <x-landing.text key="title" tag="bdi" /></div>
            </div>
        </footer>

        @if($running && $hasOffer)
            <script>
                (function () {
                    var box = document.getElementById('pf-timer');
                    if (!box) return;
                    var end = new Date(box.getAttribute('data-end')).getTime();
                    function tick() {
                        var left = Math.max(0, Math.floor((end - Date.now()) / 1000));
                        var parts = {d: Math.floor(left / 86400), h: Math.floor(left % 86400 / 3600), m: Math.floor(left % 3600 / 60), s: left % 60};
                        for (var k in parts) { box.querySelector('[data-u="' + k + '"]').textContent = parts[k]; }
                        if (left > 0) setTimeout(tick, 1000);
                    }
                    tick();
                })();
            </script>
        @endif

        @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker'], 'accent' => '#e8445a', 'accentText' => '#fff'])
        @include('landings.partials.editor-assets')
    </body>
</html>
