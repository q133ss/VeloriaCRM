{{--
    Barber X: Barber Shop Template by HTML Codex (https://htmlcodex.com/barber-shop-template),
    CC BY 4.0: the author's credit link in the footer must stay. Assets and the license live in
    public/landing-templates/barberx.

    Imported with landing:import-template, then finished by hand: the master's own data instead of
    placeholder text; no invented team, reviews, blog or newsletter; the price grid, opening hours and
    the questions block come from her catalog, schedule and FAQ; the contact form is the booking widget.
--}}
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/barberx/' . $path);
    $price = fn ($value) => number_format((float) $value, 0, ',', ' ');
    $firstHours = $hours[0] ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <title>{{ $landing->title }}</title>
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $heroText), 150) }}">

        <!-- Google Font (Open Sans covers Cyrillic) -->
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700;800&display=swap&subset=cyrillic" rel="stylesheet">

        <!-- CSS Libraries -->
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
        <link href="{{ $asset('lib/animate/animate.min.css') }}" rel="stylesheet">
        <link href="{{ $asset('lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">
        <link href="{{ $asset('lib/lightbox/css/lightbox.min.css') }}" rel="stylesheet">

        <!-- Template Stylesheet -->
        <link href="{{ $asset('css/style.css') }}" rel="stylesheet">
        <link href="{{ $asset('css/veloria.css') }}" rel="stylesheet">
        <style>.contact .container-fluid{background-image:var(--lf-contact-bg)}</style>
</head>

    <body id="top">
        @if($isPreview && empty($isEdit))
            <div style="background: #D5B981; color: #000; text-align: center; font-size: 12px; padding: 2px 0;">{{ __('landings.public.preview_badge') }}</div>
        @endif

        <!-- Top Bar Start -->
        <div class="top-bar d-none d-md-block">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <div class="top-bar-left">
                            @if($firstHours)
                                <div class="text">
                                    <h2>{{ $firstHours['from'] }} - {{ $firstHours['to'] }}</h2>
                                    <p>{{ $firstHours['days'] }}</p>
                                </div>
                            @endif
                            @if($phoneHref || $editing)
                                <div class="text">
                                    <h2><a href="{{ $phoneHref ?: '#' }}" style="color: inherit;"><x-landing.text key="phone" tag="bdi" /></a></h2>
                                    <p><x-landing.text key="lbl_common_appointment" tag="bdi" :default="__('landings.common.appointment')" :title-fallback="false" /></p>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="top-bar-right">
                            <div class="social">
                                @if($telegram)
                                    <a href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>
                                @endif
                                @if($whatsapp)
                                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Top Bar End -->

        <!-- Nav Bar Start -->
        <div class="navbar navbar-expand-lg bg-dark navbar-dark">
            <div class="container-fluid">
                <a href="#top" class="navbar-brand"><x-landing.text key="title" tag="bdi" /></a>
                <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                    <div class="navbar-nav ml-auto">
                        <a href="#services" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></a>
                        @if($priced->isNotEmpty())
                            <a href="#prices" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_prices" tag="bdi" :default="__('landings.common.nav_prices')" :title-fallback="false" /></a>
                        @endif
                        <a href="#master" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_master" tag="bdi" :default="__('landings.common.nav_master')" :title-fallback="false" /></a>
                        <a href="#booking" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_booking" tag="bdi" :default="__('landings.common.nav_booking')" :title-fallback="false" /></a>
                        <a href="#contacts" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_contacts" tag="bdi" :default="__('landings.common.nav_contacts')" :title-fallback="false" /></a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Nav Bar End -->

        <!-- Hero Start -->
        <div class="hero">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-12 col-md-6">
                        <div class="hero-text">
                            <x-landing.text key="hero_title" tag="h1" :default="__('landings.common.hero_default')" :title-fallback="false" />
                            @if($editing || filled($heroText))
                                <x-landing.text key="hero_text" tag="p" />
                            @endif
                            <a class="btn" href="#booking"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6 d-none d-md-block">
                        <div class="hero-image">
                            <x-landing.image key="hero_image_1" default="landing-templates/barberx/img/hero.png" alt="" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Hero End -->

        <!-- About Start -->
        @if($editing || $proofItems->isNotEmpty())
            <div class="about" id="about">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-5 col-md-6">
                            <div class="about-img">
                                <x-landing.image key="about_image" default="landing-templates/barberx/img/about.jpg" alt="" />
                            </div>
                        </div>
                        <div class="col-lg-7 col-md-6">
                            <div class="section-header text-left">
                                <p><x-landing.text key="lbl_common_about_kicker" tag="bdi" :default="__('landings.common.about_kicker')" :title-fallback="false" /></p>
                                <h2><x-landing.text key="lbl_common_about_title" tag="bdi" :default="__('landings.common.about_title')" :title-fallback="false" /></h2>
                            </div>
                            <div class="about-text">
                                @foreach($proofItems->take(3) as $item)
                                    <p><i class="fa fa-check" style="color: #D5B981; margin-right: 8px;"></i><x-landing.text key="proof_items_text" :index="$loop->index" tag="bdi" /></p>
                                @endforeach
                                <a class="btn" href="#booking"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <!-- About End -->

        <!-- Service Start -->
        <div class="service" id="services">
            <div class="container">
                <div class="section-header text-center">
                    <p><x-landing.text key="lbl_common_services_kicker" tag="bdi" :default="__('landings.common.services_kicker')" :title-fallback="false" /></p>
                    <h2><x-landing.text key="lbl_common_services_title" tag="bdi" :default="__('landings.common.services_title')" :title-fallback="false" /></h2>
                </div>
                <div class="row">
                    @foreach($cards->take(6) as $card)
                        <div class="col-lg-4 col-md-6">
                            <div class="service-item">
                                <div class="service-img">
                                    <x-landing.image :key="$card['id'] ? 'service_image_' . $card['id'] : null" :default="'landing-templates/barberx/img/service-' . (($loop->index % 3) + 1) . '.jpg'" alt="" />
                                </div>
                                <h3>{{ $card['name'] }}</h3>
                                <p>
                                    @if(!empty($card['price']))
                                        {{ __('landings.salone.from_price', ['price' => $price($card['price'])]) }}
                                    @endif
                                    @if(!empty($card['price']) && !empty($card['duration'])) · @endif
                                    @if(!empty($card['duration']))
                                        {{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}
                                    @endif
                                </p>
                                <a class="btn" href="#booking" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <!-- Service End -->

        <!-- Pricing Start -->
        @if($priced->isNotEmpty())
            <div class="price" id="prices">
                <div class="container">
                    <div class="section-header text-center">
                        <p><x-landing.text key="lbl_common_prices_kicker" tag="bdi" :default="__('landings.common.prices_kicker')" :title-fallback="false" /></p>
                        <h2><x-landing.text key="lbl_common_prices_title" tag="bdi" :default="__('landings.common.prices_title')" :title-fallback="false" /></h2>
                    </div>
                    <div class="row justify-content-center">
                        @foreach($cards->filter(fn ($c) => ! empty($c['price']))->take(8)->values() as $card)
                            <div class="col-lg-3 col-md-4 col-sm-6">
                                <div class="price-item">
                                    <div class="price-img">
                                        <img src="{{ $asset('img/price-' . (($loop->index % 12) + 1) . '.jpg') }}" alt="">
                                    </div>
                                    <div class="price-text">
                                        <h2>{{ $card['name'] }}</h2>
                                        <h3>{{ $price($card['price']) }} ₽</h3>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
        <!-- Pricing End -->

        <!-- Master Start -->
        <div class="team" id="master">
            <div class="container">
                <div class="section-header text-center">
                    <p><x-landing.text key="lbl_common_master_kicker" tag="bdi" :default="__('landings.common.master_kicker')" :title-fallback="false" /></p>
                    <h2><x-landing.text key="lbl_common_master_title" tag="bdi" :default="__('landings.common.master_title')" :title-fallback="false" /></h2>
                </div>
                <div class="row justify-content-center">
                    <div class="col-lg-4 col-md-6">
                        <div class="team-item">
                            <div class="team-img">
                                <x-landing.image key="master_photo" default="landing-templates/barberx/img/team-1.jpg" alt="" />
                            </div>
                            <div class="team-text">
                                <h2><x-landing.text key="title" tag="bdi" /></h2>
                                <x-landing.text key="master_role" tag="p" :default="__('landings.common.master_role')" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Master End -->

        <!-- Booking Start -->
        <div class="contact" id="booking">
            <x-landing.bg key="extra_image_1" default="landing-templates/barberx/img/contact.jpg" tag="div" var="lf-contact-bg" class="container-fluid">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-md-4"></div>
                        <div class="col-md-8">
                            <div class="contact-form">
                                <div class="section-header text-left" style="margin-bottom: 20px;">
                                    <p><x-landing.text key="lbl_common_booking_kicker" tag="bdi" :default="__('landings.common.booking_kicker')" :title-fallback="false" /></p>
                                    <h2 style="color: #fff;"><x-landing.text key="lbl_common_booking_title" tag="bdi" :default="__('landings.common.booking_title')" :title-fallback="false" /></h2>
                                    @if($editing || filled($settings['booking_hint'] ?? null))
                                        <x-landing.text key="booking_hint" tag="p" />
                                    @endif
                                </div>
                                <form id="request-form" novalidate>
                                    <div class="control-group">
                                        <input type="text" class="form-control" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120" autocomplete="name" />
                                    </div>
                                    <div class="control-group">
                                        <input type="tel" class="form-control" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask />
                                    </div>
                                    <div class="control-group">
                                        <select class="form-control" name="service_id" id="request-service" style="height: 45px;">
                                            <option value="">{{ __('landings.salone.field_service_any') }}</option>
                                            @foreach($services as $service)
                                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div id="request-picker" class="control-group"></div>
                                    <div class="control-group">
                                        <textarea class="form-control" name="message" rows="3" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000"></textarea>
                                    </div>
                                    <div>
                                        <button class="btn" type="submit" id="request-submit">{{ __('landings.salone.submit') }}</button>
                                    </div>
                                    <div id="request-message" role="status" aria-live="polite" style="margin-top: 12px;"></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </x-landing.bg>
        </div>
        <!-- Booking End -->

        <!-- Questions Start -->
        @if($faqItems->isNotEmpty())
            <div class="blog" id="faq">
                <div class="container">
                    <div class="section-header text-center">
                        <p><x-landing.text key="lbl_pretty_faq_title" tag="bdi" :default="__('landings.pretty.faq_title')" :title-fallback="false" /></p>
                        <h2><x-landing.text key="lbl_pretty_faq_lead" tag="bdi" :default="__('landings.pretty.faq_lead')" :title-fallback="false" /></h2>
                    </div>
                    <div class="row">
                        @foreach($faqItems->take(3) as $item)
                            <div class="col-md-4">
                                <div class="blog-item">
                                    <div class="blog-img">
                                        <x-landing.image :key="'faq_image_' . ($loop->index + 1)" :default="'landing-templates/barberx/img/blog-' . ($loop->index + 1) . '.jpg'" alt="" />
                                    </div>
                                    <div class="blog-text">
                                        <x-landing.text key="faq_items_text" :index="$loop->index" tag="p" />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
        <!-- Questions End -->

        <!-- Footer Start -->
        <div class="footer" id="contacts">
            <div class="container">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="footer-contact">
                                    <h2><x-landing.text key="lbl_common_contacts_title" tag="bdi" :default="__('landings.common.contacts_title')" :title-fallback="false" /></h2>
                                    @if($address !== '' || $editing)
                                        <p><i class="fa fa-map-marker-alt"></i><x-landing.text key="address" tag="bdi" /></p>
                                    @endif
                                    @if($phoneHref || $editing)
                                        <p><i class="fa fa-phone-alt"></i><a href="{{ $phoneHref ?: '#' }}" style="color: inherit;"><x-landing.text key="phone" tag="bdi" /></a></p>
                                    @endif
                                    <div class="footer-social">
                                        @if($telegram)
                                            <a href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>
                                        @endif
                                        @if($whatsapp)
                                            <a href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="footer-link">
                                    <h2><x-landing.text key="lbl_common_quick_links" tag="bdi" :default="__('landings.common.quick_links')" :title-fallback="false" /></h2>
                                    <a href="#services"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></a>
                                    @if($priced->isNotEmpty())
                                        <a href="#prices"><x-landing.text key="lbl_common_nav_prices" tag="bdi" :default="__('landings.common.nav_prices')" :title-fallback="false" /></a>
                                    @endif
                                    <a href="#master"><x-landing.text key="lbl_common_nav_master" tag="bdi" :default="__('landings.common.nav_master')" :title-fallback="false" /></a>
                                    <a href="#booking"><x-landing.text key="lbl_common_nav_booking" tag="bdi" :default="__('landings.common.nav_booking')" :title-fallback="false" /></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container copyright">
                <div class="row">
                    <div class="col-md-6">
                        <p>&copy; <x-landing.text key="title" tag="bdi" /></p>
                    </div>
                    <div class="col-md-6">
                        <p>Designed By <a href="https://htmlcodex.com" target="_blank" rel="noopener">HTML Codex</a></p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer End -->

        <a href="#" class="back-to-top"><i class="fa fa-chevron-up"></i></a>

        <!-- JavaScript Libraries -->
        <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
        <script src="{{ $asset('lib/easing/easing.min.js') }}"></script>
        <script src="{{ $asset('lib/owlcarousel/owl.carousel.min.js') }}"></script>
        <script src="{{ $asset('lib/isotope/isotope.pkgd.min.js') }}"></script>
        <script src="{{ $asset('lib/lightbox/js/lightbox.min.js') }}"></script>

        <!-- Template Javascript -->
        <script src="{{ $asset('js/main.js') }}"></script>

        @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker'], 'accent' => '#D5B981', 'accentText' => '#000'])
        @include('landings.partials.editor-assets')
    </body>
</html>
