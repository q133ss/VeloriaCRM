<!--
Original Veloria layout (license: free-commercial, see public/landing-templates/price-list/LICENSE.txt).
A master's page as her VK group already is: who she is, the price list by category, her work, booking.
Visitors never see a stock picture: a photo slot without her own photo is left out.
Every text can be long (group names run to a hundred characters), so headings wrap and are clamped.
-->
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/price-list/' . $path);
    $placeholder = 'landing-templates/price-list/placeholder.svg';
    $hasPhoto = fn (string $key) => filled(data_get($settings, 'images.' . $key));
    $showPhoto = fn (string $key) => $editing || $hasPhoto($key);

    $gallery = collect(['work_image_1', 'work_image_2', 'work_image_3', 'work_image_4', 'work_image_5', 'work_image_6', 'extra_image_1', 'extra_image_2', 'extra_image_3'])
        ->filter($showPhoto)->values();

    // Long names get a smaller heading instead of five lines of 56px.
    $titleLength = mb_strlen((string) $content->text('hero_title', $landing->title));
    $titleSize = $titleLength > 70 ? 'is-xlong' : ($titleLength > 38 ? 'is-long' : '');

    $money = fn ($price) => $price ? number_format((float) $price, 0, ',', ' ') . ' ₽' : null;

    // +79261494757 reads as one number; +7 926 149-47-57 reads as a phone.
    $digits = preg_replace('/\D/', '', $phone);
    $phoneShown = strlen($digits) === 11 && in_array($digits[0], ['7', '8'], true)
        ? sprintf('+7 %s %s-%s-%s', substr($digits, 1, 3), substr($digits, 4, 3), substr($digits, 7, 2), substr($digits, 9, 2))
        : $phone;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <title>{{ $landing->title }}</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) ($heroText ?: $landing->title)), 150) }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;subset=cyrillic&amp;display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ $asset('style.css') }}?v={{ @filemtime(public_path('landing-templates/price-list/style.css')) ?: 1 }}">
    </head>
    <body class="{{ $editing ? 'pl-editing' : '' }}">
        @if($isPreview && empty($isEdit))
            <div class="pl-preview">{{ __('landings.public.preview_badge') }}</div>
        @endif

        <header class="pl-top">
            <div class="pl-wrap pl-top-in">
                <x-landing.text key="title" tag="div" class="pl-brand" />
                <div class="pl-top-actions">
                    @if($phoneHref)
                        <a class="pl-top-phone" href="{{ $phoneHref }}">{{ $phoneShown }}</a>
                    @endif
                    <a class="pl-btn pl-btn-sm" href="#booking"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
                </div>
            </div>
        </header>

        <section class="pl-hero">
            <div class="pl-wrap pl-hero-in {{ $showPhoto('hero_image_1') ? 'has-photo' : '' }}">
                <div class="pl-hero-text">
                    @if($showPhoto('master_photo'))
                        <x-landing.image key="master_photo" :default="$placeholder" class="pl-avatar" alt="" />
                    @endif
                    <x-landing.text key="hero_title" tag="h1" class="pl-h1 {{ $titleSize }}" :default="$landing->title" />
                    @if($editing || filled($heroText))
                        <x-landing.text key="hero_text" tag="p" class="pl-lead" />
                    @endif
                    <div class="pl-hero-actions">
                        <a class="pl-btn" href="#booking"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
                        @if($phoneHref)
                            <a class="pl-btn pl-btn-ghost" href="{{ $phoneHref }}"><x-landing.text key="lbl_price_list_call" tag="bdi" :default="__('landings.price_list.call')" :title-fallback="false" /></a>
                        @endif
                    </div>
                    @if($address !== '')
                        <p class="pl-hero-address">{{ $address }}</p>
                    @endif
                </div>
                @if($showPhoto('hero_image_1'))
                    <div class="pl-hero-photo">
                        <x-landing.image key="hero_image_1" :default="$placeholder" alt="" loading="eager" />
                    </div>
                @endif
            </div>
        </section>

        <section class="pl-section" id="services">
            <div class="pl-wrap">
                <h2 class="pl-h2"><x-landing.text key="lbl_price_list_prices_title" tag="bdi" :default="__('landings.price_list.prices_title')" :title-fallback="false" /></h2>
                @if($cards->isEmpty())
                    <p class="pl-muted"><x-landing.text key="lbl_price_list_prices_empty" tag="bdi" :default="__('landings.price_list.prices_empty')" :title-fallback="false" /></p>
                @else
                    <div class="pl-prices">
                        @foreach($serviceGroups as $category => $items)
                            <div class="pl-group">
                                @if($category !== '' && $serviceGroups->count() > 1)
                                    <h3 class="pl-group-title">{{ $category }}</h3>
                                @endif
                                <ul class="pl-list">
                                    @foreach($items as $card)
                                        <li class="pl-item">
                                            <div class="pl-item-main">
                                                <span class="pl-item-name">{{ $card['name'] }}</span>
                                                @if($card['duration'])
                                                    <span class="pl-item-meta">{{ $card['duration'] }} {{ __('landings.price_list.min') }}</span>
                                                @endif
                                            </div>
                                            <div class="pl-item-side">
                                                <span class="pl-price">{{ $money($card['price']) ?? __('landings.price_list.price_on_request') }}</span>
                                                @if($card['id'])
                                                    <a class="pl-item-book" href="#booking" data-service="{{ $card['id'] }}">{{ __('landings.price_list.book_this') }}</a>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        @if($gallery->isNotEmpty())
            <section class="pl-section pl-soft" id="works">
                <div class="pl-wrap">
                    <h2 class="pl-h2"><x-landing.text key="lbl_price_list_works_title" tag="bdi" :default="__('landings.price_list.works_title')" :title-fallback="false" /></h2>
                    <div class="pl-gallery">
                        @foreach([1, 2, 3, 4, 5, 6] as $i)
                            @if($showPhoto('work_image_' . $i))
                                <figure class="pl-shot"><x-landing.image :key="'work_image_' . $i" :default="$placeholder" alt="" loading="lazy" /></figure>
                            @endif
                        @endforeach
                        @foreach([1, 2, 3] as $i)
                            @if($showPhoto('extra_image_' . $i))
                                <figure class="pl-shot"><x-landing.image :key="'extra_image_' . $i" :default="$placeholder" alt="" loading="lazy" /></figure>
                            @endif
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="pl-section" id="booking">
            <div class="pl-wrap pl-booking">
                <div class="pl-booking-head">
                    <h2 class="pl-h2"><x-landing.text key="lbl_price_list_booking_title" tag="bdi" :default="__('landings.price_list.booking_title')" :title-fallback="false" /></h2>
                    @if($editing || filled($settings['booking_hint'] ?? null))
                        <x-landing.text key="booking_hint" tag="p" class="pl-muted" />
                    @else
                        <p class="pl-muted"><x-landing.text key="lbl_price_list_booking_lead" tag="bdi" :default="__('landings.price_list.booking_lead')" :title-fallback="false" /></p>
                    @endif
                </div>
                <form id="request-form" class="pl-form" novalidate>
                    <select name="service_id" id="request-service">
                        <option value="">{{ __('landings.salone.field_service_any') }}</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                        @endforeach
                    </select>
                    <div id="request-picker"></div>
                    <div class="pl-form-row">
                        <input type="text" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120" autocomplete="name">
                        <input type="tel" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask>
                    </div>
                    <textarea name="message" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000" rows="2"></textarea>
                    <button id="request-submit" type="submit" class="pl-btn pl-btn-block">{{ __('landings.salone.submit') }}</button>
                    <div id="request-message" role="status" aria-live="polite"></div>
                </form>
            </div>
        </section>

        @if($hasInfo)
            <section class="pl-section pl-soft" id="contacts">
                <div class="pl-wrap">
                    <h2 class="pl-h2"><x-landing.text key="lbl_price_list_contacts_title" tag="bdi" :default="__('landings.price_list.contacts_title')" :title-fallback="false" /></h2>
                    <div class="pl-contacts">
                        @if($address !== '' || $editing)
                            <div class="pl-contact"><x-landing.text key="address" tag="span" /></div>
                        @endif
                        @if($phone !== '' || $editing)
                            <div class="pl-contact"><a href="{{ $phoneHref ?: '#' }}">@if($editing)<x-landing.text key="phone" tag="bdi" />@else{{ $phoneShown }}@endif</a></div>
                        @endif
                        @if($telegram || $whatsapp)
                            <div class="pl-contact pl-links">
                                @if($telegram)<a href="{{ $telegram }}" target="_blank" rel="noopener">Telegram</a>@endif
                                @if($whatsapp)<a href="{{ $whatsapp }}" target="_blank" rel="noopener">WhatsApp</a>@endif
                            </div>
                        @endif
                        @if(count($hours))
                            <div class="pl-contact">
                                <div class="pl-hours-title"><x-landing.text key="lbl_price_list_hours_title" tag="bdi" :default="__('landings.price_list.hours_title')" :title-fallback="false" /></div>
                                @foreach($hours as $line)<div>{{ $line['days'] }}: {{ $line['from'] }}–{{ $line['to'] }}</div>@endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        <footer class="pl-foot"><div class="pl-wrap">&copy; {{ date('Y') }} <x-landing.text key="title" tag="bdi" /></div></footer>

        <a class="pl-sticky pl-btn" href="#booking"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>

        <script>
            // On a phone the button follows the visitor once the first screen is behind
            // and steps aside while the form itself is on screen.
            (function () {
                var sticky = document.querySelector('.pl-sticky');
                var hero = document.querySelector('.pl-hero');
                var booking = document.getElementById('booking');
                if (!sticky || !hero || !booking || !('IntersectionObserver' in window)) return;
                var heroVisible = true, bookingVisible = false;
                var update = function () { sticky.classList.toggle('is-on', !heroVisible && !bookingVisible); };
                new IntersectionObserver(function (e) { heroVisible = e[0].isIntersecting; update(); }).observe(hero);
                new IntersectionObserver(function (e) { bookingVisible = e[0].isIntersecting; update(); }).observe(booking);
            })();

            // "Записаться" next to a price picks that service in the form below.
            document.querySelectorAll('[data-service]').forEach(function (link) {
                link.addEventListener('click', function () {
                    var select = document.getElementById('request-service');
                    if (select) {
                        select.value = link.getAttribute('data-service');
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            });
        </script>
        @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker'], 'accent' => '#a3366a', 'accentText' => '#fff'])
        @include('landings.partials.editor-assets')
    </body>
</html>
