{{--
    SPA Center: Beauty & Spa HTML Template by HTML Codex (https://htmlcodex.com/spa-html-template),
    CC BY 4.0: the author's credit link in the footer must stay. Assets and the license live in
    public/landing-templates/spacenter.

    Imported with landing:import-template, then finished by hand: the master's own data instead of
    placeholder text; no invented team, reviews, figures or newsletter; the price cards, opening hours
    and service carousel come from her catalog and schedule; the appointment block is the booking widget.
    The template's Poppins has no Cyrillic, css/veloria.css swaps it for Montserrat.
--}}
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/spacenter/' . $path);
    $price = fn ($value) => number_format((float) $value, 0, ',', ' ');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $landing->title }}</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $heroText), 150) }}">

    <!-- Google Web Fonts: Montserrat covers Cyrillic (the original Poppins does not) -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap&subset=cyrillic" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="{{ $asset('lib/animate/animate.min.css') }}" rel="stylesheet">
    <link href="{{ $asset('lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="{{ $asset('css/style.css') }}" rel="stylesheet">
    <link href="{{ $asset('css/veloria.css') }}" rel="stylesheet">
    <style>.bg-appointment{background-image:linear-gradient(rgba(33,30,28,.7),rgba(33,30,28,.7)),var(--lf-appointment-bg)}</style>
    <style>.bg-pricing{background-image:linear-gradient(rgba(33,40,28,.7),rgba(33,40,28,.7)),var(--lf-pricing-bg)}</style>
</head>

<body id="top">
    @if($isPreview && empty($isEdit))
        <div class="bg-dark text-white text-center small py-1">{{ __('landings.public.preview_badge') }}</div>
    @endif

    <!-- Topbar Start -->
    <div class="container-fluid bg-light d-none d-lg-block">
        <div class="row py-2 px-lg-5">
            <div class="col-lg-6 text-left mb-2 mb-lg-0">
                <div class="d-inline-flex align-items-center">
                    @if($phoneHref || $editing)
                        <small><i class="fa fa-phone-alt mr-2"></i><a href="{{ $phoneHref ?: '#' }}" class="text-dark"><x-landing.text key="phone" tag="bdi" /></a></small>
                    @endif
                    @if($address !== '' || $editing)
                        <small class="px-3">|</small>
                        <small><i class="fa fa-map-marker-alt mr-2"></i><x-landing.text key="address" tag="bdi" /></small>
                    @endif
                </div>
            </div>
            <div class="col-lg-6 text-right">
                <div class="d-inline-flex align-items-center">
                    @if($telegram)
                        <a class="text-primary px-2" href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>
                    @endif
                    @if($whatsapp)
                        <a class="text-primary px-2" href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- Topbar End -->


    <!-- Navbar Start -->
    <div class="container-fluid p-0">
        <nav class="navbar navbar-expand-lg bg-white navbar-light py-3 py-lg-0 px-lg-5">
            <a href="#top" class="navbar-brand ml-lg-3">
                <h1 class="m-0 text-primary"><x-landing.text key="title" tag="bdi" /></h1>
            </a>
            <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-between px-lg-3" id="navbarCollapse">
                <div class="navbar-nav m-auto py-0">
                    <a href="#services" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></a>
                    @if($hours || $editing)
                        <a href="#hours" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_hours" tag="bdi" :default="__('landings.common.nav_hours')" :title-fallback="false" /></a>
                    @endif
                    @if($priced->isNotEmpty())
                        <a href="#prices" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_prices" tag="bdi" :default="__('landings.common.nav_prices')" :title-fallback="false" /></a>
                    @endif
                    <a href="#master" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_master" tag="bdi" :default="__('landings.common.nav_master')" :title-fallback="false" /></a>
                    <a href="#contacts" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_contacts" tag="bdi" :default="__('landings.common.nav_contacts')" :title-fallback="false" /></a>
                </div>
                <a href="#booking" class="btn btn-primary d-none d-lg-block"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
            </div>
        </nav>
    </div>
    <!-- Navbar End -->


    <!-- Carousel Start -->
    <div class="container-fluid p-0 mb-5 pb-5">
        <div id="header-carousel" class="carousel slide carousel-fade" data-ride="carousel">
            <ol class="carousel-indicators">
                <li data-target="#header-carousel" data-slide-to="0" class="active"></li>
                <li data-target="#header-carousel" data-slide-to="1"></li>
                <li data-target="#header-carousel" data-slide-to="2"></li>
            </ol>
            <div class="carousel-inner">
                <div class="carousel-item position-relative active" style="min-height: 100vh;">
                    <x-landing.image key="hero_image_1" default="landing-templates/spacenter/img/carousel-1.jpg" class="position-absolute w-100 h-100" style="object-fit: cover;" alt="" />
                    <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                        <div class="p-3" style="max-width: 900px;">
                            <h6 class="text-white text-uppercase mb-3 animate__animated animate__fadeInDown" style="letter-spacing: 3px;"><x-landing.text key="title" tag="bdi" /></h6>
                            <x-landing.text key="hero_title" tag="h3" class="display-3 text-capitalize text-white mb-3" :default="__('landings.common.hero_default')" :title-fallback="false" />
                            @if($editing || filled($heroText))
                                <x-landing.text key="hero_text" tag="p" class="mx-md-5 px-5" />
                            @endif
                            <a class="btn btn-outline-light py-3 px-4 mt-3 animate__animated animate__fadeInUp" href="#booking"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
                        </div>
                    </div>
                </div>
                <div class="carousel-item position-relative" style="min-height: 100vh;">
                    <x-landing.image key="hero_image_2" default="landing-templates/spacenter/img/carousel-2.jpg" class="position-absolute w-100 h-100" style="object-fit: cover;" alt="" />
                    <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                        <div class="p-3" style="max-width: 900px;">
                            <h3 class="display-3 text-capitalize text-white mb-3"><x-landing.text key="lbl_common_hero_slide_two" tag="bdi" :default="__('landings.common.hero_slide_two')" :title-fallback="false" /></h3>
                            <a class="btn btn-outline-light py-3 px-4 mt-3" href="#booking"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
                        </div>
                    </div>
                </div>
                <div class="carousel-item position-relative" style="min-height: 100vh;">
                    <x-landing.image key="hero_image_3" default="landing-templates/spacenter/img/carousel-3.jpg" class="position-absolute w-100 h-100" style="object-fit: cover;" alt="" />
                    <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                        <div class="p-3" style="max-width: 900px;">
                            <h3 class="display-3 text-capitalize text-white mb-3"><x-landing.text key="lbl_common_services_title" tag="bdi" :default="__('landings.common.services_title')" :title-fallback="false" /></h3>
                            <a class="btn btn-outline-light py-3 px-4 mt-3" href="#services"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Carousel End -->


    <!-- About Start -->
    @if($editing || $proofItems->isNotEmpty())
        <div class="container-fluid py-5">
            <div class="container py-5">
                <div class="row align-items-center">
                    <div class="col-lg-6 pb-5 pb-lg-0">
                        <x-landing.image key="about_image" default="landing-templates/spacenter/img/about.jpg" class="img-fluid w-100" alt="" />
                    </div>
                    <div class="col-lg-6">
                        <h6 class="d-inline-block text-primary text-uppercase bg-light py-1 px-2"><x-landing.text key="lbl_common_about_kicker" tag="bdi" :default="__('landings.common.about_kicker')" :title-fallback="false" /></h6>
                        <h1 class="mb-4"><x-landing.text key="lbl_common_about_title" tag="bdi" :default="__('landings.common.about_title')" :title-fallback="false" /></h1>
                        @if($editing || filled($heroText))
                            <p class="pl-4 border-left border-primary">{{ $heroText }}</p>
                        @endif
                        <div class="row pt-3">
                            @foreach($proofItems->take(2) as $item)
                                <div class="col-6">
                                    <div class="bg-light text-center p-4 h-100 d-flex align-items-center justify-content-center">
                                        <h6 class="text-uppercase mb-0"><x-landing.text key="proof_items_text" :index="$loop->index" tag="bdi" /></h6>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!-- About End -->


    <!-- Service Start -->
    <div class="container-fluid px-0 py-5 my-5" id="services">
        <div class="row mx-0 justify-content-center text-center">
            <div class="col-lg-6">
                <h6 class="d-inline-block bg-light text-primary text-uppercase py-1 px-2"><x-landing.text key="lbl_common_services_kicker" tag="bdi" :default="__('landings.common.services_kicker')" :title-fallback="false" /></h6>
                <h1><x-landing.text key="lbl_common_services_title" tag="bdi" :default="__('landings.common.services_title')" :title-fallback="false" /></h1>
            </div>
        </div>
        <div class="owl-carousel service-carousel">
            @foreach($cards->take(9) as $card)
                <div class="service-item position-relative">
                    <x-landing.image :key="$card['id'] ? 'service_image_' . $card['id'] : null" :default="'landing-templates/spacenter/img/service-' . (($loop->index % 6) + 1) . '.jpg'" class="img-fluid" alt="" />
                    <div class="service-text text-center">
                        <h4 class="text-white font-weight-medium px-3">{{ $card['name'] }}</h4>
                        <p class="text-white px-3 mb-3">
                            @if(!empty($card['price']))
                                {{ __('landings.salone.from_price', ['price' => $price($card['price'])]) }}
                            @endif
                            @if(!empty($card['price']) && !empty($card['duration'])) · @endif
                            @if(!empty($card['duration']))
                                {{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}
                            @endif
                        </p>
                        <div class="w-100 bg-white text-center p-4">
                            <a class="btn btn-primary" href="#booking" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <x-landing.bg key="extra_image_3" default="landing-templates/spacenter/img/carousel-1.jpg" tag="div" var="lf-appointment-bg" class="row justify-content-center bg-appointment mx-0" id="booking">
            <div class="col-lg-6 py-5">
                <div class="p-5 my-5" style="background: rgba(33, 30, 28, 0.7);">
                    <h1 class="text-white text-center mb-4"><x-landing.text key="lbl_common_booking_title" tag="bdi" :default="__('landings.common.booking_title')" :title-fallback="false" /></h1>
                    @if($editing || filled($settings['booking_hint'] ?? null))
                        <x-landing.text key="booking_hint" tag="p" class="text-white text-center mb-4" />
                    @endif
                    <form id="request-form" novalidate>
                        <div class="form-row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <input type="text" class="form-control bg-transparent p-4" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120" autocomplete="name" />
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <input type="tel" class="form-control bg-transparent p-4" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask />
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <select class="custom-select bg-transparent px-4" name="service_id" id="request-service" style="height: 47px;">
                                <option value="">{{ __('landings.salone.field_service_any') }}</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="request-picker" class="mb-3"></div>
                        <div class="form-group">
                            <textarea class="form-control bg-transparent p-4" name="message" rows="3" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000"></textarea>
                        </div>
                        <button class="btn btn-primary btn-block" type="submit" id="request-submit" style="height: 47px;">{{ __('landings.salone.submit') }}</button>
                        <div id="request-message" class="text-center mt-3" role="status" aria-live="polite"></div>
                    </form>
                </div>
            </div>
        </x-landing.bg>
    </div>
    <!-- Service End -->


    <!-- Open Hours Start -->
    @if($hours || $editing)
        <div class="container-fluid py-5" id="hours">
            <div class="container py-5">
                <div class="row">
                    <div class="col-lg-6" style="min-height: 500px;">
                        <div class="position-relative h-100">
                            <x-landing.image key="extra_image_1" default="landing-templates/spacenter/img/opening.jpg" class="position-absolute w-100 h-100" style="object-fit: cover;" alt="" />
                        </div>
                    </div>
                    <div class="col-lg-6 pt-5 pb-lg-5">
                        <div class="hours-text bg-light p-4 p-lg-5 my-lg-5">
                            <h6 class="d-inline-block text-white text-uppercase bg-primary py-1 px-2"><x-landing.text key="lbl_common_hours_kicker" tag="bdi" :default="__('landings.common.hours_kicker')" :title-fallback="false" /></h6>
                            <h1 class="mb-4"><x-landing.text key="lbl_common_hours_title" tag="bdi" :default="__('landings.common.hours_title')" :title-fallback="false" /></h1>
                            <p><x-landing.text key="lbl_common_hours_note" tag="bdi" :default="__('landings.common.hours_note')" :title-fallback="false" /></p>
                            <ul class="list-inline">
                                @foreach($hours as $line)
                                    <li class="h6 py-1"><i class="far fa-circle text-primary mr-3"></i>{{ $line['days'] }} : {{ $line['from'] }} - {{ $line['to'] }}</li>
                                @endforeach
                            </ul>
                            <a href="#booking" class="btn btn-primary mt-2"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!-- Open Hours End -->


    <!-- Pricing Start -->
    @if($priced->isNotEmpty())
        <x-landing.bg key="extra_image_4" default="landing-templates/spacenter/img/carousel-2.jpg" tag="div" var="lf-pricing-bg" class="container-fluid bg-pricing" style="margin: 90px 0;" id="prices">
            <div class="container">
                <div class="row">
                    <div class="col-lg-5" style="min-height: 500px;">
                        <div class="position-relative h-100">
                            <x-landing.image key="extra_image_2" default="landing-templates/spacenter/img/pricing.jpg" class="position-absolute w-100 h-100" style="object-fit: cover;" alt="" />
                        </div>
                    </div>
                    <div class="col-lg-7 pt-5 pb-lg-5">
                        <div class="pricing-text bg-light p-4 p-lg-5 my-lg-5">
                            <h6 class="d-inline-block text-white text-uppercase bg-primary py-1 px-2 mb-3"><x-landing.text key="lbl_common_prices_kicker" tag="bdi" :default="__('landings.common.prices_kicker')" :title-fallback="false" /></h6>
                            <div class="owl-carousel pricing-carousel">
                                @foreach($priced as $card)
                                    <div class="bg-white">
                                        <div class="d-flex align-items-center justify-content-between border-bottom border-primary p-4">
                                            <h1 class="display-4 mb-0" style="font-size: 2.4rem;">{{ $price($card['price']) }} <small class="text-muted" style="font-size: 22px;">₽</small></h1>
                                            <h5 class="text-primary text-uppercase m-0 text-right" style="overflow-wrap: anywhere;">{{ $card['name'] }}</h5>
                                        </div>
                                        <div class="p-4">
                                            @if(!empty($card['duration']))
                                                <p><i class="fa fa-clock text-success mr-2"></i>{{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}</p>
                                            @endif
                                            <a href="#booking" class="btn btn-primary my-2" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-landing.bg>
    @endif
    <!-- Pricing End -->


    <!-- Master Start -->
    <div class="container-fluid py-5" id="master">
        <div class="container pt-5">
            <div class="row justify-content-center text-center">
                <div class="col-lg-6">
                    <h6 class="d-inline-block bg-light text-primary text-uppercase py-1 px-2"><x-landing.text key="lbl_common_master_kicker" tag="bdi" :default="__('landings.common.master_kicker')" :title-fallback="false" /></h6>
                    <h1 class="mb-5"><x-landing.text key="lbl_common_master_title" tag="bdi" :default="__('landings.common.master_title')" :title-fallback="false" /></h1>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6">
                    <div class="team position-relative overflow-hidden mb-5">
                        <x-landing.image key="master_photo" default="landing-templates/spacenter/img/team-1.jpg" class="img-fluid" alt="" />
                        <div class="position-relative text-center">
                            <div class="team-text bg-primary text-white">
                                <h5 class="text-white text-uppercase"><x-landing.text key="title" tag="bdi" /></h5>
                                <x-landing.text key="master_role" tag="p" class="m-0" :default="__('landings.common.master_role')" />
                            </div>
                            @if($telegram || $whatsapp)
                                <div class="team-social bg-dark text-center">
                                    @if($telegram)
                                        <a class="btn btn-outline-primary btn-square mr-2" href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>
                                    @endif
                                    @if($whatsapp)
                                        <a class="btn btn-outline-primary btn-square" href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Master End -->


    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light py-5" id="contacts" style="margin-top: 60px;">
        <div class="container pt-4">
            <div class="row">
                <div class="col-md-6 mb-5">
                    <h5 class="text-white text-uppercase mb-4"><x-landing.text key="lbl_common_contacts_title" tag="bdi" :default="__('landings.common.contacts_title')" :title-fallback="false" /></h5>
                    @if($address !== '' || $editing)
                        <p><i class="fa fa-map-marker-alt mr-2"></i><x-landing.text key="address" tag="bdi" /></p>
                    @endif
                    @if($phoneHref || $editing)
                        <p><i class="fa fa-phone-alt mr-2"></i><a href="{{ $phoneHref ?: '#' }}" class="text-white-50"><x-landing.text key="phone" tag="bdi" /></a></p>
                    @endif
                    <div class="d-flex justify-content-start mt-4">
                        @if($telegram)
                            <a class="btn btn-outline-light btn-square mr-2" href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>
                        @endif
                        @if($whatsapp)
                            <a class="btn btn-outline-light btn-square" href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                        @endif
                    </div>
                </div>
                <div class="col-md-6 mb-5">
                    <h5 class="text-white text-uppercase mb-4"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></h5>
                    <div class="d-flex flex-column justify-content-start">
                        @foreach($cards->take(6) as $card)
                            <a class="text-white-50 mb-2" href="#booking" data-pick-service="{{ $card['id'] }}"><i class="fa fa-angle-right mr-2"></i>{{ $card['name'] }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid bg-dark text-light border-top py-4" style="border-color: rgba(256, 256, 256, .15) !important;">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-center text-md-left mb-3 mb-md-0">
                    <p class="m-0 text-white">&copy; <x-landing.text key="title" tag="bdi" /></p>
                </div>
                <div class="col-md-6 text-center text-md-right">
                    <p class="m-0 text-white">Designed by <a href="https://htmlcodex.com" target="_blank" rel="noopener">HTML Codex</a></p>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->


    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="{{ $asset('lib/easing/easing.min.js') }}"></script>
    <script src="{{ $asset('lib/waypoints/waypoints.min.js') }}"></script>
    <script src="{{ $asset('lib/counterup/counterup.min.js') }}"></script>
    <script src="{{ $asset('lib/owlcarousel/owl.carousel.min.js') }}"></script>

    <!-- Template Javascript -->
    <script src="{{ $asset('js/main.js') }}"></script>

    @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker'], 'accent' => '#211e1c', 'accentText' => '#fff'])
    @include('landings.partials.editor-assets')
</body>
</html>
