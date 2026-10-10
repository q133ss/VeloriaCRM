{{--
    Велнес: Yooga, Free Yoga Website Template by HTML Codex (https://htmlcodex.com/free-yoga-website-template/),
    CC BY 4.0: the author's credit link in the footer must stay. Assets and the license live in
    public/landing-templates/wellness.

    Imported with landing:import-template, then finished by hand: the services are the master's own catalog,
    the pricing plans are her priced services, the discount block carries her real offer, hours come from
    her schedule, the contact form is the booking widget. No invented classes, trainers, reviews or blog;
    the carousel, isotope and contact-validation scripts of the original are not loaded.
--}}
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/wellness/' . $path);
    $price = fn ($value) => number_format((float) $value, 0, ',', ' ');

    $has = fn (string $key) => $editing || ! empty($isDemo) || ! empty($settings['images'][$key] ?? null);
    $icons = ['flaticon-workout', 'flaticon-workout-1', 'flaticon-workout-2'];
    $firstHours = $hours[0] ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <title>{{ $landing->title }}</title>
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <meta content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $heroText), 150) }}" name="description">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700;800&amp;subset=cyrillic&amp;display=swap" rel="stylesheet">

        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
        <link href="{{ $asset('lib/animate/animate.min.css') }}" rel="stylesheet">
        <link href="{{ $asset('lib/flaticon/font/flaticon.css') }}" rel="stylesheet">
        <link href="{{ $asset('css/style.css') }}" rel="stylesheet">
        <style>
            .service-item a { color: inherit; font-weight: 600; text-decoration: underline; }
            .wl-form { background: #fff; padding: 32px; box-shadow: 0 0 30px rgba(0, 0, 0, .08); }
            .wl-form .form-control { border-radius: 0; padding: 22px 16px; }
            .wl-form select.form-control { height: 50px; padding: 10px 16px; }
            .wl-form textarea.form-control { padding: 14px 16px; }
            .wl-form .btn { border-radius: 0; background: #343148; color: #F7CAC9; padding: 14px 28px; font-weight: 600; letter-spacing: 1px; }
        </style>
    </head>
    <body>
        @if($isPreview && empty($isEdit))
            <div style="position: fixed; top: 0; left: 0; right: 0; z-index: 2000; background: #111; color: #fff; text-align: center; font-size: 12px; padding: 2px 0;">{{ __('landings.public.preview_badge') }}</div>
        @endif

        @if($firstHours || $phoneHref || $editing)
            <div class="top-bar d-none d-md-block">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="top-bar-left">
                                @if($firstHours)
                                    <div class="text">
                                        <i class="far fa-clock"></i>
                                        <h2>{{ $firstHours['from'] }} – {{ $firstHours['to'] }}</h2>
                                        <p>{{ $firstHours['days'] }}</p>
                                    </div>
                                @endif
                                @if($phoneHref || $editing)
                                    <div class="text">
                                        <i class="fa fa-phone-alt"></i>
                                        <h2><a href="{{ $phoneHref ?: '#' }}" style="color: inherit"><x-landing.text key="phone" tag="bdi" /></a></h2>
                                        <p><x-landing.text key="lbl_common_nav_booking" tag="bdi" :default="__('landings.common.nav_booking')" :title-fallback="false" /></p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="top-bar-right">
                                <div class="social">
                                    @if($telegram)<a href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>@endif
                                    @if($whatsapp)<a href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>@endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="navbar navbar-expand-lg bg-dark navbar-dark">
            <div class="container-fluid">
                <a href="#home" class="navbar-brand" style="font-size: 28px"><x-landing.text key="title" tag="bdi" /></a>
                <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                    <div class="navbar-nav ml-auto">
                        <a href="#about" class="nav-item nav-link"><x-landing.text key="lbl_common_about_kicker" tag="bdi" :default="__('landings.common.about_kicker')" :title-fallback="false" /></a>
                        @if($cards->isNotEmpty())<a href="#service" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></a>@endif
                        @if($priced->isNotEmpty())<a href="#price" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_prices" tag="bdi" :default="__('landings.common.nav_prices')" :title-fallback="false" /></a>@endif
                        <a href="#booking" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_booking" tag="bdi" :default="__('landings.common.nav_booking')" :title-fallback="false" /></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="hero" id="home">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-sm-12 {{ $has('hero_image_1') ? 'col-md-6' : 'col-md-10 mx-auto text-center' }}">
                        <div class="hero-text">
                            <x-landing.text key="hero_title" tag="h1" :default="__('landings.common.hero_default')" :title-fallback="false" />
                            @if($editing || filled($heroText))
                                <x-landing.text key="hero_text" tag="p" />
                            @endif
                            <div class="hero-btn">
                                <a class="btn" href="#booking"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
                                @if($phoneHref)<a class="btn" href="{{ $phoneHref }}">{{ $phone }}</a>@endif
                            </div>
                        </div>
                    </div>
                    @if($has('hero_image_1'))
                        <div class="col-sm-12 col-md-6">
                            <div class="hero-image">
                                <x-landing.image key="hero_image_1" default="landing-templates/wellness/img/hero.png" alt="" />
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="about wow fadeInUp" data-wow-delay="0.1s" id="about">
            <div class="container">
                <div class="row align-items-center">
                    @if($has('about_image'))
                        <div class="col-lg-5 col-md-6">
                            <div class="about-img">
                                <x-landing.image key="about_image" default="landing-templates/wellness/img/about.png" alt="" />
                            </div>
                        </div>
                    @endif
                    <div class="{{ $has('about_image') ? 'col-lg-7 col-md-6' : 'col-lg-9 mx-auto text-center' }}">
                        <div class="section-header {{ $has('about_image') ? 'text-left' : 'text-center' }}">
                            <p><x-landing.text key="lbl_common_about_kicker" tag="bdi" :default="__('landings.common.about_kicker')" :title-fallback="false" /></p>
                            <h2><x-landing.text key="lbl_common_about_title" tag="bdi" :default="__('landings.common.about_title')" :title-fallback="false" /></h2>
                        </div>
                        <div class="about-text">
                            @if($editing || filled($settings['master_bio'] ?? null))
                                <x-landing.text key="master_bio" tag="p" />
                            @endif
                            @if($editing || $proofItems->isNotEmpty())
                                @foreach($proofItems->take(5) as $item)
                                    <p class="mb-2"><i class="fa fa-check mr-2" style="color: #F7CAC9"></i><x-landing.text key="proof_items_text" :index="$loop->index" tag="bdi" /></p>
                                @endforeach
                            @endif
                            <a class="btn mt-3" href="#booking"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($cards->isNotEmpty())
            <div class="service" id="service">
                <div class="container">
                    <div class="section-header text-center wow zoomIn" data-wow-delay="0.1s">
                        <p><x-landing.text key="lbl_common_services_kicker" tag="bdi" :default="__('landings.common.services_kicker')" :title-fallback="false" /></p>
                        <h2><x-landing.text key="lbl_common_services_title" tag="bdi" :default="__('landings.common.services_title')" :title-fallback="false" /></h2>
                    </div>
                    <div class="row">
                        @foreach($cards->take(6) as $card)
                            <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="{{ ($loop->index % 3) * 0.2 }}s">
                                <div class="service-item {{ $loop->index === 1 ? 'active' : '' }}">
                                    <div class="service-icon"><i class="{{ $icons[$loop->index % 3] }}"></i></div>
                                    <h3>{{ $card['name'] }}</h3>
                                    <p>
                                        @if(!empty($card['price'])){{ $price($card['price']) }} ₽@endif
                                        @if(!empty($card['price']) && !empty($card['duration'])) · @endif
                                        @if(!empty($card['duration'])){{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}@endif
                                    </p>
                                    <a href="#booking" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if($hasOffer)
            <div class="discount wow zoomIn" data-wow-delay="0.1s">
                <div class="container">
                    <div class="section-header text-center">
                        <p><x-landing.text key="lbl_pretty_nav_offer" tag="bdi" :default="__('landings.pretty.nav_offer')" :title-fallback="false" /></p>
                        <h2>−<span>{{ $offerPercent }}%</span></h2>
                    </div>
                    <div class="container discount-text">
                        @if($promoCode || $endsAt)
                            <p>
                                @if($promoCode){{ __('landings.pretty.offer_code', ['code' => $promoCode]) }}. @endif
                                @if($endsAt){{ __('landings.pretty.offer_until', ['date' => $endsAt]) }}@endif
                            </p>
                        @endif
                        <a class="btn" href="#booking"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                    </div>
                </div>
            </div>
        @endif

        @if($priced->isNotEmpty())
            <div class="price" id="price">
                <div class="container">
                    <div class="section-header text-center wow zoomIn" data-wow-delay="0.1s">
                        <p><x-landing.text key="lbl_common_prices_kicker" tag="bdi" :default="__('landings.common.prices_kicker')" :title-fallback="false" /></p>
                        <h2><x-landing.text key="lbl_common_prices_title" tag="bdi" :default="__('landings.common.prices_title')" :title-fallback="false" /></h2>
                    </div>
                    <div class="row justify-content-center">
                        @foreach($priced as $card)
                            <div class="col-md-4 wow fadeInUp" data-wow-delay="{{ ($loop->index % 3) * 0.3 }}s">
                                <div class="price-item {{ $loop->index === 1 ? 'featured-item' : '' }}">
                                    <div class="price-header">
                                        <div class="price-title"><h2>{{ $card['name'] }}</h2></div>
                                        <div class="price-prices"><h2>{{ $price($card['price']) }}<small>₽</small></h2></div>
                                    </div>
                                    <div class="price-body">
                                        <div class="price-description">
                                            @if(!empty($card['duration']))
                                                <ul><li>{{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}</li></ul>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="price-footer">
                                        <div class="price-action"><a class="btn" href="#booking" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="price" id="booking" style="padding-top: 0">
            <div class="container">
                <div class="section-header text-center wow zoomIn" data-wow-delay="0.1s">
                    <p><x-landing.text key="lbl_common_booking_kicker" tag="bdi" :default="__('landings.common.booking_kicker')" :title-fallback="false" /></p>
                    <h2><x-landing.text key="lbl_common_booking_title" tag="bdi" :default="__('landings.common.booking_title')" :title-fallback="false" /></h2>
                </div>
                <div class="row justify-content-center">
                    <div class="col-lg-7">
                        @if($editing || filled($settings['booking_hint'] ?? null))
                            <x-landing.text key="booking_hint" tag="p" class="text-center" />
                        @endif
                        <form id="request-form" class="wl-form" novalidate>
                            <div class="form-row">
                                <div class="col-md-6 form-group"><input type="text" class="form-control" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120" autocomplete="name"></div>
                                <div class="col-md-6 form-group"><input type="tel" class="form-control" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask></div>
                            </div>
                            <div class="form-group">
                                <select class="form-control" name="service_id" id="request-service">
                                    <option value="">{{ __('landings.salone.field_service_any') }}</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group"><textarea class="form-control" name="message" rows="3" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000"></textarea></div>
                            <div id="request-picker" class="mb-3"></div>
                            <button id="request-submit" class="btn" type="submit">{{ __('landings.salone.submit') }}</button>
                            <div id="request-message" class="mt-3" role="status" aria-live="polite"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer wow fadeIn" data-wow-delay="0.3s">
            <div class="container-fluid">
                <div class="container">
                    <div class="footer-info">
                        <a href="#home" class="footer-logo" style="font-size: 32px"><x-landing.text key="title" tag="bdi" /></a>
                        @if($address !== '' || $editing)<h3><x-landing.text key="address" tag="bdi" /></h3>@endif
                        @if($phoneHref)<div class="footer-menu"><p><a href="{{ $phoneHref }}" style="color: inherit">{{ $phone }}</a></p></div>@endif
                        <div class="footer-social">
                            @if($telegram)<a href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>@endif
                            @if($whatsapp)<a href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>@endif
                        </div>
                    </div>
                </div>
                <div class="container copyright">
                    <div class="row">
                        <div class="col-md-6"><p>&copy; {{ date('Y') }} <x-landing.text key="title" tag="bdi" /></p></div>
                        <div class="col-md-6">
                            <!--/*** This template is free as long as you keep the footer author’s credit link/attribution link/backlink. ***/-->
                            <p>{{ __('landings.pretty.credit') }} <a href="https://htmlcodex.com" target="_blank" rel="noopener">HTML Codex</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <a href="#" class="back-to-top"><i class="fa fa-chevron-up"></i></a>

        <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
        <script src="{{ $asset('lib/easing/easing.min.js') }}"></script>
        <script src="{{ $asset('lib/wow/wow.min.js') }}"></script>
        <script src="{{ $asset('js/main.js') }}"></script>

        @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker'], 'accent' => '#343148', 'accentText' => '#F7CAC9'])
        @include('landings.partials.editor-assets')
    </body>
</html>
