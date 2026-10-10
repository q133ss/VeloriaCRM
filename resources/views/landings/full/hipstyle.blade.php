{{--
    Hipstyle Barber: free HTML5 template by Colorlib (https://colorlib.com), distributed by ThemeWagon,
    CC BY 3.0: the link back to Colorlib in the footer must stay. Assets and the license live in
    public/landing-templates/hipstyle.

    Imported with landing:import-template, then finished by hand: the master's own data instead of
    placeholder text; no invented artists, reviews, blog or newsletter; the four photo tiles show her
    services, the price list comes from her catalog, questions carry her FAQ; the booking block is the
    shared widget (the original form had no back end). Carousels and pickers are not loaded.
--}}
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/hipstyle/' . $path);
    $price = fn ($value) => number_format((float) $value, 0, ',', ' ');
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $landing->title }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $heroText), 150) }}">
    <link rel="icon" href="{{ $asset('img/favicon.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ $asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ $asset('css/flaticon.css') }}">
    <link rel="stylesheet" href="{{ $asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ $asset('css/veloria.css') }}">
    <style>.regervation_part:after{background-image:var(--lf-booking-bg)}</style>
</head>

<body>
    @if($isPreview && empty($isEdit))
        <div style="position: fixed; top: 0; left: 0; right: 0; z-index: 2000; background: #111; color: #fff; text-align: center; font-size: 12px; padding: 2px 0;">{{ __('landings.public.preview_badge') }}</div>
    @endif

    <header class="main_menu home_menu">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-12">
                    <nav class="navbar navbar-expand-lg navbar-light">
                        <a class="navbar-brand hs-brand" href="#top"><x-landing.text key="title" tag="bdi" /></a>
                        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('landings.pretty.menu') }}">
                            <span class="menu_icon"></span>
                        </button>
                        <div class="collapse navbar-collapse main-menu-item" id="navbarSupportedContent">
                            <ul class="navbar-nav">
                                <li class="nav-item"><a class="nav-link" href="#about"><x-landing.text key="lbl_common_about_title" tag="bdi" :default="__('landings.common.about_title')" :title-fallback="false" /></a></li>
                                <li class="nav-item"><a class="nav-link" href="#services"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></a></li>
                                @if($priced->isNotEmpty())
                                    <li class="nav-item"><a class="nav-link" href="#prices"><x-landing.text key="lbl_common_nav_prices" tag="bdi" :default="__('landings.common.nav_prices')" :title-fallback="false" /></a></li>
                                @endif
                                <li class="nav-item"><a class="nav-link" href="#booking"><x-landing.text key="lbl_common_nav_booking" tag="bdi" :default="__('landings.common.nav_booking')" :title-fallback="false" /></a></li>
                                <li class="nav-item"><a class="nav-link" href="#contacts"><x-landing.text key="lbl_common_nav_contacts" tag="bdi" :default="__('landings.common.nav_contacts')" :title-fallback="false" /></a></li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <a id="top"></a>
    <x-landing.bg key="hero_image_1" default="landing-templates/hipstyle/img/banner_bg.jpg" tag="section" var="lf-hero-bg" class="banner_part">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="banner_text">
                        <div class="banner_text_iner">
                            <x-landing.text key="hero_title" tag="h1" :default="__('landings.common.hero_default')" :title-fallback="false" />
                            @if($editing || filled($heroText))
                                <x-landing.text key="hero_text" tag="p" />
                            @endif
                            <div class="banner_btn">
                                <a href="#booking" class="btn_1"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
                                <a href="#services" class="btn_2"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-landing.bg>

    <section class="about_part" id="about">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-4 col-lg-6">
                    <div class="about_img">
                        <x-landing.image key="extra_image_1" default="landing-templates/hipstyle/img/about_us_1.png" class="about_img_1" alt="" />
                        <x-landing.image key="extra_image_2" default="landing-templates/hipstyle/img/about_us_2.png" class="about_img_2" alt="" />
                        <x-landing.image key="extra_image_3" default="landing-templates/hipstyle/img/about_us_3.png" class="about_img_3" alt="" />
                    </div>
                </div>
                <div class="col-md-7 offset-md-1 col-lg-4 offset-lg-1">
                    <div class="about_text">
                        <h2><x-landing.text key="lbl_common_about_title" tag="bdi" :default="__('landings.common.about_title')" :title-fallback="false" /></h2>
                        @if($editing || $proofItems->isNotEmpty())
                            <ul class="hs-list">
                                @foreach($proofItems->take(5) as $item)
                                    <x-landing.text key="proof_items_text" :index="$loop->index" tag="li" />
                                @endforeach
                            </ul>
                        @endif
                        @if(count($hours))
                            <p class="mt-3">
                                <strong><x-landing.text key="lbl_common_hours_kicker" tag="bdi" :default="__('landings.common.hours_kicker')" :title-fallback="false" /></strong>
                                @foreach($hours as $line)
                                    <br>{{ $line['days'] }}: {{ $line['from'] }}–{{ $line['to'] }}
                                @endforeach
                            </p>
                        @endif
                        <a href="#booking" class="btn_3"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="service_part section_padding pb-0" id="services">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7 col-sm-10">
                    <div class="section_tittle">
                        <img src="{{ $asset('img/section_tittle_icon.png') }}" alt="">
                        <h2><x-landing.text key="lbl_common_services_title" tag="bdi" :default="__('landings.common.services_title')" :title-fallback="false" /></h2>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="our_offer">
        <div class="container-fluid">
            <div class="row justify-content-between">
                <div class="col-lg-12">
                    @foreach($cards->take(4) as $card)
                        <div class="single_offer_part">
                            <div class="single_offer">
                                <x-landing.image :key="$card['id'] ? 'service_image_' . $card['id'] : null" :default="'landing-templates/hipstyle/img/offer_img_' . $loop->iteration . '.jpg'" alt="" />
                                <div class="hover_text">
                                    <img src="{{ $asset('img/icon/cutter.svg') }}" alt="">
                                    <h2>{{ $card['name'] }}</h2>
                                    <p>
                                        @if(!empty($card['price'])){{ __('landings.salone.from_price', ['price' => $price($card['price'])]) }}@endif
                                        @if(!empty($card['price']) && !empty($card['duration'])) · @endif
                                        @if(!empty($card['duration'])){{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}@endif
                                    </p>
                                    <a href="#booking" class="offer_btn" data-pick-service="{{ $card['id'] }}" aria-label="{{ __('landings.common.book') }}"><span class="flaticon-slim-right"></span></a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    @if($priced->isNotEmpty())
        <section class="priceing_part" id="prices">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-7 col-sm-10">
                        <div class="section_tittle">
                            <img src="{{ $asset('img/section_tittle_icon.png') }}" alt="">
                            <h2><x-landing.text key="lbl_common_prices_title" tag="bdi" :default="__('landings.common.prices_title')" :title-fallback="false" /></h2>
                        </div>
                    </div>
                </div>
                <div class="row align-items-center">
                    @foreach($priced as $card)
                        <div class="col-md-6 col-lg-6">
                            <div class="single_pricing_item">
                                <img src="{{ $asset('img/pricing_img/pricing_img_' . $loop->iteration . '.png') }}" alt="">
                                <div class="single_pricing_text">
                                    <h5>{{ $card['name'] }}</h5>
                                    <h6>{{ $price($card['price']) }} ₽</h6>
                                    <p>
                                        @if(!empty($card['duration'])){{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }} · @endif
                                        <a href="#booking" class="hs-link" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-landing.bg key="extra_image_4" default="landing-templates/hipstyle/img/reservation_bg.jpg" tag="section" var="lf-booking-bg" class="regervation_part section_padding" id="booking">
        <div class="container">
            <div class="row justify-content-end">
                <div class="col-lg-7">
                    <div class="regervation_part_iner">
                        <h2 class="hs-book-title"><x-landing.text key="lbl_common_booking_title" tag="bdi" :default="__('landings.common.booking_title')" :title-fallback="false" /></h2>
                        @if($editing || filled($settings['booking_hint'] ?? null))
                            <x-landing.text key="booking_hint" tag="p" class="hs-book-hint" />
                        @endif
                        <form id="request-form" novalidate>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <input type="text" class="form-control" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120" autocomplete="name">
                                </div>
                                <div class="form-group col-md-6">
                                    <input type="tel" class="form-control" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask>
                                </div>
                                <div class="form-group col-md-12">
                                    <select class="form-control" name="service_id" id="request-service">
                                        <option value="">{{ __('landings.salone.field_service_any') }}</option>
                                        @foreach($services as $service)
                                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-12"><div id="request-picker"></div></div>
                                <div class="form-group col-md-12">
                                    <textarea class="form-control" name="message" rows="3" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000"></textarea>
                                </div>
                            </div>
                            <div class="regerv_btn">
                                <button type="submit" id="request-submit" class="regerv_btn_iner hs-submit">{{ __('landings.salone.submit') }}</button>
                            </div>
                            <div id="request-message" class="mt-3" role="status" aria-live="polite"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </x-landing.bg>

    @if($faqItems->isNotEmpty())
        <section class="service_part section_padding">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-7 col-sm-10">
                        <div class="section_tittle">
                            <img src="{{ $asset('img/section_tittle_icon.png') }}" alt="">
                            <h2><x-landing.text key="lbl_pretty_faq_title" tag="bdi" :default="__('landings.pretty.faq_title')" :title-fallback="false" /></h2>
                        </div>
                    </div>
                </div>
                <div class="row">
                    @foreach($faqItems->take(3) as $item)
                        <div class="col-md-6 col-lg-4">
                            <div class="hs-faq">
                                <x-landing.text key="faq_items_text" :index="$loop->index" tag="p" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <footer class="footer-area" id="contacts">
        <div class="container">
            <div class="row justify-content-between">
                <div class="col-xl-4 col-sm-6 col-lg-4">
                    <div class="single-footer-widget footer_1">
                        <h4><x-landing.text key="title" tag="bdi" /></h4>
                        @if($proofItems->isNotEmpty())
                            @foreach($proofItems->take(2) as $item)
                                <x-landing.text key="proof_items_text" :index="$loop->index" tag="p" />
                            @endforeach
                        @endif
                    </div>
                </div>
                <div class="col-xl-4 col-sm-6 col-lg-4">
                    <div class="single-footer-widget footer_2">
                        <h4><x-landing.text key="lbl_common_contacts_title" tag="bdi" :default="__('landings.common.contacts_title')" :title-fallback="false" /></h4>
                        @if($address !== '' || $editing)
                            <div class="contact_info">
                                <span class="bi bi-geo-alt"></span>
                                <h5><x-landing.text key="address" tag="bdi" /></h5>
                            </div>
                        @endif
                        @if($phoneHref || $editing)
                            <div class="contact_info">
                                <span class="bi bi-telephone"></span>
                                <h5><a href="{{ $phoneHref ?: '#' }}"><x-landing.text key="phone" tag="bdi" /></a></h5>
                            </div>
                        @endif
                        @if($telegram || $whatsapp)
                            <div class="hs-social">
                                @if($telegram)<a href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><i class="bi bi-telegram"></i></a>@endif
                                @if($whatsapp)<a href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>@endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="copyright_part_text text-center">
                <div class="row">
                    <div class="col-lg-12">
                        <p class="footer-text m-0">
                            <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
                            &copy;{{ date('Y') }} <x-landing.text key="title" tag="bdi" /> | {{ __('landings.pretty.credit') }} <i class="bi bi-heart-fill" aria-hidden="true"></i> <a href="https://colorlib.com" target="_blank" rel="noopener">Colorlib</a>
                            <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="{{ $asset('js/jquery-1.12.1.min.js') }}"></script>
    <script src="{{ $asset('js/popper.min.js') }}"></script>
    <script src="{{ $asset('js/bootstrap.min.js') }}"></script>
    <script>
        $(function () {
            $('.main_menu .nav-link').on('click', function () { $('#navbarSupportedContent').collapse('hide'); });
            $(window).on('scroll', function () { $('.main_menu').toggleClass('menu_fixed', $(window).scrollTop() > 100); });
        });
    </script>

    @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker'], 'accent' => '#f81c1c', 'accentText' => '#fff'])
    @include('landings.partials.editor-assets')
</body>
</html>
