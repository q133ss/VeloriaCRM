<!--
Original Veloria layout (license: free-commercial, see public/landing-templates/ads-one/LICENSE.txt).
One-screen ad page: promise, three benefits, a booking form, contacts. No price list, no gallery.
-->
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/ads-one/' . $path);
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

        <header class="ao-hero">
            <div class="ao-wrap">
                <div class="ao-brand">
                    <x-landing.text key="title" tag="span" />
                    @if($phoneHref || $editing)
                        <a href="{{ $phoneHref ?: '#' }}"><x-landing.text key="phone" tag="bdi" /></a>
                    @endif
                </div>
                <x-landing.text key="hero_title" tag="h1" :default="__('landings.common.hero_default')" :title-fallback="false" />
                @if($editing || filled($heroText))
                    <x-landing.text key="hero_text" tag="p" />
                @endif
                <a class="ao-btn" href="#booking"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
            </div>
        </header>

        @if($editing || $proofItems->isNotEmpty())
            <section class="ao-section">
                <div class="ao-wrap">
                    <h2>{{ __('landings.ads.benefits_title') }}</h2>
                    <ul class="ao-benefits">
                        @foreach($proofItems->take(3) as $item)
                            <x-landing.text key="proof_items_text" :index="$loop->index" tag="li" />
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif

        <section class="ao-form-box" id="booking">
            <div class="ao-wrap">
                <h2>{{ __('landings.ads.form_title') }}</h2>
                @if($editing || filled($settings['booking_hint'] ?? null))
                    <x-landing.text key="booking_hint" tag="p" class="ao-lead" />
                @else
                    <p class="ao-lead">{{ __('landings.ads.form_lead') }}</p>
                @endif
                <form id="request-form" class="ao-form" novalidate>
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

        @if($hasInfo)
            <section class="ao-section">
                <div class="ao-wrap">
                    <h2>{{ __('landings.ads.contacts_title') }}</h2>
                    <div class="ao-contacts">
                        @if($address !== '' || $editing)<x-landing.text key="address" tag="span" />@endif
                        @if($telegram)<a href="{{ $telegram }}" target="_blank" rel="noopener">Telegram</a>@endif
                        @if($whatsapp)<a href="{{ $whatsapp }}" target="_blank" rel="noopener">WhatsApp</a>@endif
                    </div>
                    @if(count($hours))
                        <div class="ao-hours">
                            @foreach($hours as $line)<div>{{ $line['days'] }}: {{ $line['from'] }}–{{ $line['to'] }}</div>@endforeach
                        </div>
                    @endif
                </div>
            </section>
        @endif

        <footer class="ao-foot"><div class="ao-wrap">&copy; {{ date('Y') }} <x-landing.text key="title" tag="bdi" /></div></footer>

        @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker'], 'accent' => '#1f7a6d', 'accentText' => '#fff'])
        @include('landings.partials.editor-assets')
    </body>
</html>
