{{--
    HairCut: Hair Salon HTML Template by HTML Codex (https://htmlcodex.com/hair-salon-html-template),
    CC BY 4.0: the author's credit link in the footer must stay. Assets and the license live in
    public/landing-templates/haircut.

    Imported with landing:import-template, then finished by hand: the master's own data instead of
    placeholder text, no invented team, reviews, figures or newsletter, the price list and the working
    hours come from her catalog and schedule, and there is a booking block (the shared widget).
--}}
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/haircut/' . $path);
    $serviceIcons = ['haircut', 'beard-trim', 'mans-shave', 'hair-dyeing', 'mustache', 'stacking'];
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

    <!-- Google Web Fonts (Roboto and Oswald both cover Cyrillic) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&family=Oswald:wght@600&display=swap&subset=cyrillic" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="{{ $asset('lib/animate/animate.min.css') }}" rel="stylesheet">
    <link href="{{ $asset('lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="{{ $asset('css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="{{ $asset('css/style.css') }}" rel="stylesheet">
    <link href="{{ $asset('css/veloria.css') }}" rel="stylesheet">
</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-dark position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    @if($isPreview && empty($isEdit))
        <div class="bg-primary text-dark text-center small py-1">{{ __('landings.public.preview_badge') }}</div>
    @endif

    <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg bg-secondary navbar-dark sticky-top py-lg-0 px-lg-5 wow fadeIn" data-wow-delay="0.1s">
        <a href="#top" class="navbar-brand ms-4 ms-lg-0">
            <h1 class="mb-0 text-primary text-uppercase"><i class="fa fa-cut me-3"></i><x-landing.text key="title" tag="bdi" /></h1>
        </a>
        <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto p-4 p-lg-0">
                <a href="#services" class="nav-item nav-link">{{ __('landings.common.nav_services') }}</a>
                @if($priced->isNotEmpty())
                    <a href="#prices" class="nav-item nav-link">{{ __('landings.common.nav_prices') }}</a>
                @endif
                <a href="#master" class="nav-item nav-link">{{ __('landings.common.nav_master') }}</a>
                @if($hours || $editing)
                    <a href="#hours" class="nav-item nav-link">{{ __('landings.common.nav_hours') }}</a>
                @endif
                <a href="#contacts" class="nav-item nav-link">{{ __('landings.common.nav_contacts') }}</a>
            </div>
            <a href="#booking" class="btn btn-primary rounded-0 py-2 px-lg-4 d-none d-lg-block"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /><i class="fa fa-arrow-right ms-3"></i></a>
        </div>
    </nav>
    <!-- Navbar End -->


    <!-- Carousel Start -->
    <div class="container-fluid p-0 mb-5 wow fadeIn" data-wow-delay="0.1s" id="top">
        <div id="header-carousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <x-landing.image key="hero_image_1" default="landing-templates/haircut/img/carousel-1.jpg" class="w-100" alt="" />
                    <div class="carousel-caption d-flex align-items-center justify-content-center text-start">
                        <div class="mx-sm-5 px-5" style="max-width: 900px;">
                            <x-landing.text key="hero_title" tag="h1" class="display-2 text-white text-uppercase mb-4 animated slideInDown" :default="__('landings.common.hero_default')" :title-fallback="false" />
                            @if($address !== '' || $editing)
                                <h4 class="text-white text-uppercase mb-4 animated slideInDown"><i class="fa fa-map-marker-alt text-primary me-3"></i><x-landing.text key="address" tag="bdi" /></h4>
                            @endif
                            @if($phoneHref || $editing)
                                <h4 class="text-white text-uppercase mb-4 animated slideInDown"><i class="fa fa-phone-alt text-primary me-3"></i><a href="{{ $phoneHref ?: '#' }}" class="text-white"><x-landing.text key="phone" tag="bdi" /></a></h4>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <x-landing.image key="hero_image_2" default="landing-templates/haircut/img/carousel-2.jpg" class="w-100" alt="" />
                    <div class="carousel-caption d-flex align-items-center justify-content-center text-start">
                        <div class="mx-sm-5 px-5" style="max-width: 900px;">
                            <h1 class="display-2 text-white text-uppercase mb-4 animated slideInDown">{{ __('landings.common.hero_slide_two') }}</h1>
                            <p class="mb-4"><a href="#booking" class="btn btn-primary rounded-0 py-3 px-5"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a></p>
                        </div>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#header-carousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#header-carousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>
    <!-- Carousel End -->


    <!-- About Start -->
    @if($editing || filled($heroText) || $proofItems->isNotEmpty())
        <div class="container-xxl py-5" id="about">
            <div class="container">
                <div class="row g-5">
                    <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s">
                        <div class="d-flex flex-column">
                            <x-landing.image key="about_image" default="landing-templates/haircut/img/about.jpg" class="img-fluid w-75 align-self-end" alt="" />
                            @if($phoneHref || $editing)
                                <div class="w-50 bg-secondary p-5" style="margin-top: -25%;">
                                    <h2 class="text-uppercase text-primary mb-3" style="font-size: 1.5rem;"><a href="{{ $phoneHref ?: '#' }}" class="text-primary"><x-landing.text key="phone" tag="bdi" /></a></h2>
                                    <h5 class="text-uppercase mb-0">{{ __('landings.common.appointment') }}</h5>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s">
                        <p class="d-inline-block bg-secondary text-primary py-1 px-4">{{ __('landings.common.about_kicker') }}</p>
                        <h1 class="text-uppercase mb-4">{{ __('landings.common.about_title') }}</h1>
                        @if($editing || filled($heroText))
                            <x-landing.text key="hero_text" tag="p" class="mb-4" />
                        @endif
                        <div class="row g-4">
                            @foreach($proofItems->take(4) as $item)
                                <div class="col-md-6">
                                    <h3 class="text-uppercase mb-3" style="font-size: 1.1rem;"><i class="fa fa-check text-primary me-2"></i><x-landing.text key="proof_items_text" :index="$loop->index" tag="bdi" /></h3>
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
    <div class="container-xxl py-5" id="services">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <p class="d-inline-block bg-secondary text-primary py-1 px-4">{{ __('landings.common.services_kicker') }}</p>
                <h1 class="text-uppercase">{{ __('landings.common.services_title') }}</h1>
            </div>
            <div class="row g-4">
                @foreach($cards->take(9) as $card)
                    <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="{{ 0.1 + ($loop->index % 3) * 0.2 }}s">
                        <div class="service-item position-relative overflow-hidden bg-secondary d-flex h-100 p-5 ps-0">
                            <div class="bg-dark d-flex flex-shrink-0 align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <img class="img-fluid" src="{{ $asset('img/' . $serviceIcons[$loop->index % count($serviceIcons)] . '.png') }}" alt="">
                            </div>
                            <div class="ps-4">
                                <h3 class="text-uppercase mb-3">{{ $card['name'] }}</h3>
                                @if(!empty($card['duration']))
                                    <p>{{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}</p>
                                @endif
                                @if(!empty($card['price']))
                                    <span class="text-uppercase text-primary">{{ __('landings.salone.from_price', ['price' => $price($card['price'])]) }}</span>
                                @endif
                            </div>
                            <a class="btn btn-square" href="#booking" data-pick-service="{{ $card['id'] }}" aria-label="{{ __('landings.common.book') }}"><i class="fa fa-plus text-primary"></i></a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <!-- Service End -->


    <!-- Price Start -->
    @if($priced->isNotEmpty())
        <div class="container-xxl py-5" id="prices">
            <div class="container">
                <div class="row g-0">
                    <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s">
                        <div class="bg-secondary h-100 d-flex flex-column justify-content-center p-5">
                            <p class="d-inline-flex bg-dark text-primary py-1 px-4 me-auto">{{ __('landings.common.prices_kicker') }}</p>
                            <h1 class="text-uppercase mb-4">{{ __('landings.common.prices_title') }}</h1>
                            <div>
                                @foreach($cards->filter(fn ($c) => ! empty($c['price']))->take(7) as $card)
                                    <div class="d-flex justify-content-between {{ $loop->last ? '' : 'border-bottom' }} py-2">
                                        <h6 class="text-uppercase mb-0">{{ $card['name'] }}</h6>
                                        <span class="text-uppercase text-primary">{{ $price($card['price']) }} ₽</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s">
                        <div class="h-100">
                            <img class="img-fluid h-100" style="object-fit: cover;" src="{{ $asset('img/price.jpg') }}" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!-- Price End -->


    <!-- Master Start -->
    <div class="container-xxl py-5" id="master">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <p class="d-inline-block bg-secondary text-primary py-1 px-4">{{ __('landings.common.master_kicker') }}</p>
                <h1 class="text-uppercase">{{ __('landings.common.master_title') }}</h1>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="team-item">
                        <div class="team-img position-relative overflow-hidden">
                            <x-landing.image key="master_photo" default="landing-templates/haircut/img/team-1.jpg" class="img-fluid w-100" alt="" />
                            @if($telegram || $whatsapp)
                                <div class="team-social">
                                    @if($telegram)
                                        <a class="btn btn-square" href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>
                                    @endif
                                    @if($whatsapp)
                                        <a class="btn btn-square" href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="bg-secondary text-center p-4">
                            <h5 class="text-uppercase"><x-landing.text key="title" tag="bdi" /></h5>
                            <x-landing.text key="master_role" tag="span" class="text-primary" :default="__('landings.common.master_role')" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Master End -->


    <!-- Working Hours Start -->
    @if($hours || $editing)
        <div class="container-xxl py-5" id="hours">
            <div class="container">
                <div class="row g-0">
                    <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s">
                        <div class="h-100">
                            <img class="img-fluid h-100" style="object-fit: cover;" src="{{ $asset('img/open.jpg') }}" alt="">
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s">
                        <div class="bg-secondary h-100 d-flex flex-column justify-content-center p-5">
                            <p class="d-inline-flex bg-dark text-primary py-1 px-4 me-auto">{{ __('landings.common.hours_kicker') }}</p>
                            <h1 class="text-uppercase mb-4">{{ __('landings.common.hours_title') }}</h1>
                            <div>
                                @foreach($hours as $line)
                                    <div class="d-flex justify-content-between {{ $loop->last ? '' : 'border-bottom' }} py-2">
                                        <h6 class="text-uppercase mb-0">{{ $line['days'] }}</h6>
                                        <span class="text-uppercase">{{ $line['from'] }} – {{ $line['to'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <p class="mt-4 mb-0">{{ __('landings.common.hours_note') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!-- Working Hours End -->


    <!-- Booking Start -->
    <div class="container-xxl py-5" id="booking">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <p class="d-inline-block bg-secondary text-primary py-1 px-4">{{ __('landings.common.booking_kicker') }}</p>
                <h1 class="text-uppercase">{{ __('landings.common.booking_title') }}</h1>
                @if($editing || filled($settings['booking_hint'] ?? null))
                    <x-landing.text key="booking_hint" tag="p" />
                @endif
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <form id="request-form" class="bg-secondary p-4 p-lg-5" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control border-0 py-3 ps-4" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120" autocomplete="name">
                            </div>
                            <div class="col-md-6">
                                <input type="tel" class="form-control border-0 py-3 ps-4" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask>
                            </div>
                            <div class="col-12">
                                <select class="form-select border-0 py-3 ps-4" name="service_id" id="request-service">
                                    <option value="">{{ __('landings.salone.field_service_any') }}</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12"><div id="request-picker"></div></div>
                            <div class="col-12">
                                <textarea class="form-control border-0 py-3 ps-4" name="message" rows="3" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000"></textarea>
                            </div>
                            <div class="col-12 text-center">
                                <button type="submit" id="request-submit" class="btn btn-primary rounded-0 py-3 px-5 text-uppercase">{{ __('landings.salone.submit') }}</button>
                            </div>
                            <div class="col-12 text-center" id="request-message" role="status" aria-live="polite"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Booking End -->


    <!-- Footer Start -->
    <div class="container-fluid bg-secondary text-light footer mt-5 pt-5 wow fadeIn" data-wow-delay="0.1s" id="contacts">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-4 col-md-6">
                    <h4 class="text-uppercase mb-4">{{ __('landings.common.contacts_title') }}</h4>
                    @if($address !== '' || $editing)
                        <div class="d-flex align-items-center mb-2">
                            <div class="btn-square bg-dark flex-shrink-0 me-3">
                                <span class="fa fa-map-marker-alt text-primary"></span>
                            </div>
                            <x-landing.text key="address" tag="span" />
                        </div>
                    @endif
                    @if($phoneHref || $editing)
                        <div class="d-flex align-items-center mb-2">
                            <div class="btn-square bg-dark flex-shrink-0 me-3">
                                <span class="fa fa-phone-alt text-primary"></span>
                            </div>
                            <a href="{{ $phoneHref ?: '#' }}" class="text-light"><x-landing.text key="phone" tag="bdi" /></a>
                        </div>
                    @endif
                </div>
                <div class="col-lg-4 col-md-6">
                    <h4 class="text-uppercase mb-4">{{ __('landings.common.quick_links') }}</h4>
                    <a class="btn btn-link" href="#services">{{ __('landings.common.nav_services') }}</a>
                    @if($priced->isNotEmpty())
                        <a class="btn btn-link" href="#prices">{{ __('landings.common.nav_prices') }}</a>
                    @endif
                    <a class="btn btn-link" href="#master">{{ __('landings.common.nav_master') }}</a>
                    <a class="btn btn-link" href="#booking">{{ __('landings.common.nav_booking') }}</a>
                </div>
                @if($telegram || $whatsapp)
                    <div class="col-lg-4 col-md-6">
                        <div class="d-flex pt-1 m-n1">
                            @if($telegram)
                                <a class="btn btn-lg-square btn-dark text-primary m-1" href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>
                            @endif
                            @if($whatsapp)
                                <a class="btn btn-lg-square btn-dark text-primary m-1" href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="container">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        &copy; <x-landing.text key="title" tag="bdi" />
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <!--/*** This template is free as long as you keep the footer author’s credit link/attribution link/backlink. ***/-->
                        Designed By <a class="border-bottom" href="https://htmlcodex.com" target="_blank" rel="noopener">HTML Codex</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->


    <!-- Back to Top -->
    <a href="#" class="btn btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ $asset('lib/wow/wow.min.js') }}"></script>
    <script src="{{ $asset('lib/easing/easing.min.js') }}"></script>
    <script src="{{ $asset('lib/waypoints/waypoints.min.js') }}"></script>
    <script src="{{ $asset('lib/owlcarousel/owl.carousel.min.js') }}"></script>

    <!-- Template Javascript -->
    <script src="{{ $asset('js/main.js') }}"></script>

    @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker'], 'accent' => '#EB1616', 'accentText' => '#fff'])
    @include('landings.partials.editor-assets')
</body>

</html>
