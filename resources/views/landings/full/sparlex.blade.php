{{--
    Sparlex: Beauty & Spa Website Template by HTML Codex (https://htmlcodex.com/spa-website-template),
    CC BY 4.0: the author's credit link in the footer must stay. Assets and the license live in
    public/landing-templates/sparlex.

    Imported with landing:import-template, then finished by hand: the master's own data instead of
    placeholder text; no invented team, reviews, newsletter or map; the opening hours, price cards,
    counters and services come from her schedule, catalog and real figures; the appointment form is
    the booking widget.
--}}
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/sparlex/' . $path);
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

        <!-- Google Web Fonts (Open Sans and PT Serif both cover Cyrillic) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=PT+Serif:wght@400;700&display=swap&subset=cyrillic" rel="stylesheet">

        <!-- Icon Font Stylesheet -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

        <!-- Libraries Stylesheet -->
        <link href="{{ $asset('lib/animate/animate.min.css') }}" rel="stylesheet">
        <link href="{{ $asset('lib/lightbox/css/lightbox.min.css') }}" rel="stylesheet">
        <link href="{{ $asset('lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">

        <!-- Customized Bootstrap Stylesheet -->
        <link href="{{ $asset('css/bootstrap.min.css') }}" rel="stylesheet">

        <!-- Template Stylesheet -->
        <link href="{{ $asset('css/style.css') }}" rel="stylesheet">
        <link href="{{ $asset('css/veloria.css') }}" rel="stylesheet">
    </head>

    <body id="top">
        @if($isPreview && empty($isEdit))
            <div class="bg-dark text-white text-center small py-1">{{ __('landings.public.preview_badge') }}</div>
        @endif

        <!-- Navbar start -->
        <div class="container-fluid sticky-top px-0">
            <div class="container-fluid topbar d-none d-lg-block">
                <div class="container px-0">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div class="d-flex flex-wrap">
                                @if($address !== '' || $editing)
                                    <span class="me-4 text-light"><i class="fas fa-map-marker-alt text-primary me-2"></i><x-landing.text key="address" tag="bdi" /></span>
                                @endif
                                @if($phoneHref || $editing)
                                    <a href="{{ $phoneHref ?: '#' }}" class="text-light"><i class="fas fa-phone-alt text-primary me-2"></i><x-landing.text key="phone" tag="bdi" /></a>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="d-flex align-items-center justify-content-end">
                                @if($telegram)
                                    <a href="{{ $telegram }}" target="_blank" rel="noopener" class="me-3 btn-square border rounded-circle nav-fill" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>
                                @endif
                                @if($whatsapp)
                                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="btn-square border rounded-circle nav-fill" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container-fluid bg-light">
                <div class="container px-0">
                    <nav class="navbar navbar-light navbar-expand-xl">
                        <a href="#top" class="navbar-brand">
                            <h1 class="text-primary display-4"><x-landing.text key="title" tag="bdi" /></h1>
                        </a>
                        <button class="navbar-toggler py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                            <span class="fa fa-bars text-primary"></span>
                        </button>
                        <div class="collapse navbar-collapse bg-light py-3" id="navbarCollapse">
                            <div class="navbar-nav mx-auto border-top">
                                <a href="#services" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></a>
                                <a href="#booking" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_booking" tag="bdi" :default="__('landings.common.nav_booking')" :title-fallback="false" /></a>
                                @if($priced->isNotEmpty())
                                    <a href="#prices" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_prices" tag="bdi" :default="__('landings.common.nav_prices')" :title-fallback="false" /></a>
                                @endif
                                <a href="#master" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_master" tag="bdi" :default="__('landings.common.nav_master')" :title-fallback="false" /></a>
                                <a href="#contacts" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_contacts" tag="bdi" :default="__('landings.common.nav_contacts')" :title-fallback="false" /></a>
                            </div>
                            <div class="d-flex align-items-center flex-nowrap pt-xl-0">
                                <a href="#booking" class="btn btn-primary btn-primary-outline-0 rounded-pill py-3 px-4 ms-4"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
        <!-- Navbar End -->


        <!-- Carousel Start -->
        <div class="container-fluid carousel-header px-0">
            <div id="carouselId" class="carousel slide" data-bs-ride="carousel">
                <ol class="carousel-indicators">
                    <li data-bs-target="#carouselId" data-bs-slide-to="0" class="active"></li>
                    <li data-bs-target="#carouselId" data-bs-slide-to="1"></li>
                    <li data-bs-target="#carouselId" data-bs-slide-to="2"></li>
                </ol>
                <div class="carousel-inner" role="listbox">
                    <div class="carousel-item active">
                        <x-landing.image key="hero_image_1" default="landing-templates/sparlex/img/carousel-3.jpg" class="img-fluid" alt="" />
                        <div class="carousel-caption">
                            <div class="p-3" style="max-width: 900px;">
                                <h4 class="text-primary text-uppercase mb-3"><x-landing.text key="title" tag="bdi" /></h4>
                                <x-landing.text key="hero_title" tag="h1" class="display-1 text-capitalize text-dark mb-3" :default="__('landings.common.hero_default')" :title-fallback="false" />
                                @if($editing || filled($heroText))
                                    <x-landing.text key="hero_text" tag="p" class="mx-md-5 fs-4 px-4 mb-5 text-dark" />
                                @endif
                                <div class="d-flex align-items-center justify-content-center">
                                    <a class="btn btn-primary btn-primary-outline-0 rounded-pill py-3 px-5" href="#booking"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <x-landing.image key="hero_image_2" default="landing-templates/sparlex/img/carousel-2.jpg" class="img-fluid" alt="" />
                        <div class="carousel-caption">
                            <div class="p-3" style="max-width: 900px;">
                                <h1 class="display-1 text-capitalize text-dark mb-3"><x-landing.text key="lbl_common_hero_slide_two" tag="bdi" :default="__('landings.common.hero_slide_two')" :title-fallback="false" /></h1>
                                <div class="d-flex align-items-center justify-content-center">
                                    <a class="btn btn-primary btn-primary-outline-0 rounded-pill py-3 px-5" href="#booking"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <x-landing.image key="hero_image_3" default="landing-templates/sparlex/img/carousel-1.jpg" class="img-fluid" alt="" />
                        <div class="carousel-caption">
                            <div class="p-3" style="max-width: 900px;">
                                <h1 class="display-1 text-capitalize text-dark mb-3"><x-landing.text key="lbl_common_services_title" tag="bdi" :default="__('landings.common.services_title')" :title-fallback="false" /></h1>
                                <div class="d-flex align-items-center justify-content-center">
                                    <a class="btn btn-light btn-light-outline-0 rounded-pill py-3 px-5" href="#services"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselId" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselId" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
        <!-- Carousel End -->


        <!-- Services Start -->
        <div class="container-fluid services py-5" id="services">
            <div class="container py-5">
                <div class="mx-auto text-center mb-5" style="max-width: 800px;">
                    <p class="fs-4 text-uppercase text-center text-primary"><x-landing.text key="lbl_common_services_kicker" tag="bdi" :default="__('landings.common.services_kicker')" :title-fallback="false" /></p>
                    <h1 class="display-3"><x-landing.text key="lbl_common_services_title" tag="bdi" :default="__('landings.common.services_title')" :title-fallback="false" /></h1>
                </div>
                <div class="row g-4">
                    @foreach($cards->take(8) as $card)
                        <div class="col-lg-6">
                            <div class="services-item bg-light border-4 {{ $loop->odd ? 'border-end' : 'border-start' }} border-primary rounded p-4">
                                <div class="row align-items-center {{ $loop->odd ? '' : 'flex-row-reverse' }}">
                                    <div class="col-8">
                                        <div class="services-content {{ $loop->odd ? 'text-end' : 'text-start' }}">
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
                                            <a href="#booking" class="btn btn-primary btn-primary-outline-0 rounded-pill py-2 px-4" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="services-img d-flex align-items-center justify-content-center rounded">
                                            <img src="{{ $asset('img/services-' . (($loop->index % 6) + 1) . '.jpg') }}" class="img-fluid rounded" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <!-- Services End -->


        <!-- About Start -->
        @if($editing || $proofItems->isNotEmpty() || filled($heroText))
            <div class="container-fluid about py-5">
                <div class="container py-5">
                    <div class="row g-5 align-items-center">
                        <div class="col-lg-5">
                            <div class="video">
                                <x-landing.image key="about_image" default="landing-templates/sparlex/img/about-1.jpg" class="img-fluid rounded" alt="" />
                                <div class="position-absolute rounded border-5 border-top border-start border-white" style="bottom: 0; right: 0;">
                                    <x-landing.image key="extra_image_1" default="landing-templates/sparlex/img/about-2.jpg" class="img-fluid rounded" alt="" />
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <p class="fs-4 text-uppercase text-primary"><x-landing.text key="lbl_common_about_kicker" tag="bdi" :default="__('landings.common.about_kicker')" :title-fallback="false" /></p>
                            <h1 class="display-4 mb-4"><x-landing.text key="lbl_common_about_title" tag="bdi" :default="__('landings.common.about_title')" :title-fallback="false" /></h1>
                            @if($editing || filled($heroText))
                                <p class="mb-4">{{ $heroText }}</p>
                            @endif
                            <div class="row g-4">
                                @foreach($proofItems->take(4) as $item)
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-check-circle fa-2x text-primary"></i>
                                            <div class="ms-4">
                                                <h5 class="mb-0"><x-landing.text key="proof_items_text" :index="$loop->index" tag="bdi" /></h5>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <a href="#booking" class="btn btn-primary btn-primary-outline-0 rounded-pill py-3 px-5 mt-4"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <!-- About End -->


        <!-- Appointment Start -->
        <div class="container-fluid appointment py-5" id="booking">
            <div class="container py-5">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-6">
                        <div class="appointment-form p-5">
                            <p class="fs-4 text-uppercase text-primary"><x-landing.text key="lbl_common_booking_kicker" tag="bdi" :default="__('landings.common.booking_kicker')" :title-fallback="false" /></p>
                            <h1 class="display-4 mb-4 text-white"><x-landing.text key="lbl_common_booking_title" tag="bdi" :default="__('landings.common.booking_title')" :title-fallback="false" /></h1>
                            @if($editing || filled($settings['booking_hint'] ?? null))
                                <x-landing.text key="booking_hint" tag="p" class="text-white" />
                            @endif
                            <form id="request-form" novalidate>
                                <div class="row gy-3 gx-4">
                                    <div class="col-lg-6">
                                        <input type="text" class="form-control py-3 border-white bg-transparent text-white" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120" autocomplete="name">
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="tel" class="form-control py-3 border-white bg-transparent text-white" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask>
                                    </div>
                                    <div class="col-lg-12">
                                        <select class="form-select py-3 border-white bg-transparent text-white" name="service_id" id="request-service">
                                            <option value="">{{ __('landings.salone.field_service_any') }}</option>
                                            @foreach($services as $service)
                                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-12"><div id="request-picker"></div></div>
                                    <div class="col-lg-12">
                                        <textarea class="form-control border-white bg-transparent text-white" name="message" rows="3" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000"></textarea>
                                    </div>
                                    <div class="col-lg-12">
                                        <button type="submit" id="request-submit" class="btn btn-primary btn-primary-outline-0 w-100 py-3 px-5 text-uppercase">{{ __('landings.salone.submit') }}</button>
                                    </div>
                                    <div class="col-lg-12 text-center text-white" id="request-message" role="status" aria-live="polite"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @if($hours || $editing)
                        <div class="col-lg-6">
                            <div class="appointment-time p-5">
                                <h1 class="display-5 mb-4"><x-landing.text key="lbl_common_hours_kicker" tag="bdi" :default="__('landings.common.hours_kicker')" :title-fallback="false" /></h1>
                                @foreach($hours as $line)
                                    <div class="d-flex justify-content-between fs-5 text-white">
                                        <p>{{ $line['days'] }}:</p>
                                        <p>{{ $line['from'] }} – {{ $line['to'] }}</p>
                                    </div>
                                @endforeach
                                <p class="text-dark mt-3 mb-0"><x-landing.text key="lbl_common_hours_note" tag="bdi" :default="__('landings.common.hours_note')" :title-fallback="false" /></p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Counters: real figures, shown once they are worth showing -->
            @if($showCounters)
                <div class="container-fluid counter-section">
                    <div class="container py-5">
                        <div class="row g-5 justify-content-center">
                            @foreach([['clients', 'counters_clients', 'fa-users'], ['visits', 'counters_visits', 'fa-spa'], ['services', 'counters_services', 'fa-star']] as [$stat, $label, $icon])
                                <div class="col-md-6 col-lg-4 col-xl-4">
                                    <div class="counter-item p-5">
                                        <div class="counter-content bg-white p-4">
                                            <i class="fas {{ $icon }} fa-5x text-primary mb-3"></i>
                                            <h5 class="text-primary">{{ __('landings.pretty.' . $label) }}</h5>
                                        </div>
                                        <div class="counter-quantity">
                                            <span class="text-white fs-2 fw-bold">{{ number_format($stats[$stat], 0, ',', ' ') }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <!-- Appointment End -->


        <!-- Gallery Start -->
        <div class="container-fluid gallery py-5">
            <div class="container py-5">
                <div class="text-center mx-auto mb-5" style="max-width: 800px;">
                    <p class="fs-4 text-uppercase text-primary"><x-landing.text key="lbl_pretty_work_title" tag="bdi" :default="__('landings.pretty.work_title')" :title-fallback="false" /></p>
                    @if($editing)
                        <p>{{ __('landings.pretty.work_hint') }}</p>
                    @endif
                </div>
                <div class="row g-4 justify-content-center">
                    @foreach([1, 2, 3] as $i)
                        <div class="col-lg-4 col-md-6">
                            <div class="gallery-img">
                                <x-landing.image :key="'work_image_' . $i" :default="'landing-templates/sparlex/img/gallery-' . $i . '.jpg'" class="img-fluid rounded w-100" alt="" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <!-- Gallery End -->


        <!-- Pricing Start -->
        @if($priced->isNotEmpty())
            <div class="container-fluid pricing py-5" id="prices">
                <div class="container py-5">
                    <div class="owl-carousel pricing-carousel">
                        @foreach($priced as $card)
                            <div class="pricing-item">
                                <div class="rounded pricing-content">
                                    <div class="d-flex align-items-center justify-content-between bg-light rounded-top border-3 border-bottom border-primary p-4">
                                        <h1 class="display-4 mb-0" style="white-space: nowrap;">{{ $price($card['price']) }} <small class="text-muted" style="font-size: 22px;">₽</small></h1>
                                        <h5 class="text-primary text-uppercase m-0 text-end" style="overflow-wrap: break-word;">{{ $card['name'] }}</h5>
                                    </div>
                                    <div class="p-4">
                                        @if(!empty($card['duration']))
                                            <p><i class="fa fa-clock text-primary me-2"></i>{{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}</p>
                                        @endif
                                        <a href="#booking" class="btn btn-primary btn-primary-outline-0 rounded-pill my-2 px-4" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
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
        <div class="container-fluid team py-5" id="master">
            <div class="container py-5">
                <div class="text-center mx-auto mb-5" style="max-width: 800px;">
                    <p class="fs-4 text-uppercase text-primary"><x-landing.text key="lbl_common_master_kicker" tag="bdi" :default="__('landings.common.master_kicker')" :title-fallback="false" /></p>
                    <h1 class="display-4 mb-4"><x-landing.text key="lbl_common_master_title" tag="bdi" :default="__('landings.common.master_title')" :title-fallback="false" /></h1>
                </div>
                <div class="row g-4 justify-content-center">
                    <div class="col-md-6 col-lg-6 col-xl-4">
                        <div class="team-item">
                            <div class="team-img rounded-top">
                                <x-landing.image key="master_photo" default="landing-templates/sparlex/img/team-1.png" class="img-fluid w-100 rounded-top bg-light" alt="" />
                            </div>
                            <div class="team-text rounded-bottom text-center p-4">
                                <h3 class="text-white"><x-landing.text key="title" tag="bdi" /></h3>
                                <x-landing.text key="master_role" tag="p" class="mb-0 text-white" :default="__('landings.common.master_role')" />
                            </div>
                            @if($telegram || $whatsapp)
                                <div class="team-social">
                                    @if($telegram)
                                        <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle mb-2" href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>
                                    @endif
                                    @if($whatsapp)
                                        <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle" href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Master End -->


        <!-- Contact Start -->
        @if($address !== '' || $phoneHref || $editing)
            <div class="container-fluid py-5" id="contacts">
                <div class="container py-5">
                    <div class="row g-4 justify-content-center">
                        @if($address !== '' || $editing)
                            <div class="col-lg-5">
                                <div class="d-inline-flex bg-light w-100 border border-primary p-4 rounded">
                                    <i class="fas fa-map-marker-alt fa-2x text-primary me-4"></i>
                                    <div>
                                        <h4><x-landing.text key="lbl_pretty_info_address" tag="bdi" :default="__('landings.pretty.info_address')" :title-fallback="false" /></h4>
                                        <x-landing.text key="address" tag="p" class="mb-0" />
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if($phoneHref || $editing)
                            <div class="col-lg-5">
                                <div class="d-inline-flex bg-light w-100 border border-primary p-4 rounded">
                                    <i class="fa fa-phone-alt fa-2x text-primary me-4"></i>
                                    <div>
                                        <h4><x-landing.text key="lbl_pretty_info_phone" tag="bdi" :default="__('landings.pretty.info_phone')" :title-fallback="false" /></h4>
                                        <p class="mb-0"><a href="{{ $phoneHref ?: '#' }}" class="text-dark"><x-landing.text key="phone" tag="bdi" /></a></p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
        <!-- Contact End -->


        <!-- Copyright Start -->
        <div class="container-fluid copyright py-4">
            <div class="container">
                <div class="row g-4 align-items-center">
                    <div class="col-md-6 text-center text-md-start mb-md-0">
                        <span class="text-light"><i class="fas fa-copyright text-light me-2"></i><x-landing.text key="title" tag="bdi" /></span>
                    </div>
                    <div class="col-md-6 text-center text-md-end text-white">
                        <!--/*** This template is free as long as you keep the below author’s credit link/attribution link/backlink. ***/-->
                        Designed By <a class="border-bottom" href="https://htmlcodex.com" target="_blank" rel="noopener">HTML Codex</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Copyright End -->


        <!-- Back to Top -->
        <a href="#" class="btn btn-primary btn-primary-outline-0 btn-md-square rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>


        <!-- JavaScript Libraries -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="{{ $asset('lib/wow/wow.min.js') }}"></script>
        <script src="{{ $asset('lib/easing/easing.min.js') }}"></script>
        <script src="{{ $asset('lib/waypoints/waypoints.min.js') }}"></script>
        <script src="{{ $asset('lib/counterup/counterup.min.js') }}"></script>
        <script src="{{ $asset('lib/lightbox/js/lightbox.min.js') }}"></script>
        <script src="{{ $asset('lib/owlcarousel/owl.carousel.min.js') }}"></script>

        <!-- Template Javascript -->
        <script src="{{ $asset('js/main.js') }}"></script>

        @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker'], 'accent' => '#FF4F9D', 'accentText' => '#fff'])
        @include('landings.partials.editor-assets')
    </body>

</html>
