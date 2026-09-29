{{--
    Salone — Beauty Salon Website Template by HTML Codex (https://htmlcodex.com/beauty-salon-website-template),
    CC BY 4.0: the author's credit link in the footer must stay. Assets live in public/landing-templates/salone
    together with the original LICENSE.txt.

    Markup is the original index.html; changes are limited to: data instead of lorem ipsum, a booking form
    that posts to the landing request route, and the sections with made-up people and numbers removed.
--}}
@php
    $settings = $landing->settings ?? [];
    $asset = fn (string $path) => asset('landing-templates/salone/' . $path);

    $content = app(\App\Services\Landing\LandingContent::class);
    $editing = $content->editing();
    $heroText = $content->text('hero_text');

    $proofItems = collect($content->items('proof_items_text'));
    $phone = trim((string) ($settings['phone'] ?? ''));
    $phoneHref = $phone !== '' ? 'tel:' . preg_replace('/[^0-9+]+/', '', $phone) : null;
    $address = trim((string) ($settings['address'] ?? ''));
    $telegram = $settings['telegram_url'] ?? null;
    $whatsapp = $settings['whatsapp_url'] ?? null;

    $serviceIcons = ['haircut', 'makeup', 'manicure', 'pedicure', 'massage', 'skin-care'];
    $services = $featuredServices->values();
    $serviceNames = collect($settings['service_names'] ?? []);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>{{ $landing->title }}</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $heroText), 150) }}">

    <!-- Google Web Fonts (Cyrillic-capable replacements for Work Sans / Dancing Script) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Marck+Script&family=Playfair+Display:wght@500&family=Manrope:wght@400;600&display=swap&subset=cyrillic" rel="stylesheet">

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
</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner"
        class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    @if($isPreview && empty($isEdit))
        <div class="bg-dark text-white text-center small py-1">{{ __('landings.public.preview_badge') }}</div>
    @endif

    <!-- Navbar Start -->
    <div class="container-fluid bg-light sticky-top p-0">
        <nav class="navbar navbar-expand-lg navbar-light p-0">
            <a href="#top" class="navbar-brand bg-primary py-4 px-5 me-0">
                <h1 class="mb-0"><i class="bi bi-scissors"></i><x-landing.text key="title" /></h1>
            </a>
            <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse"
                data-bs-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse p-3" id="navbarCollapse">
                <div class="navbar-nav mx-auto">
                    @if($proofItems->isNotEmpty())
                        <a href="#about" class="nav-item nav-link">{{ __('landings.salone.nav_about') }}</a>
                    @endif
                    <a href="#services" class="nav-item nav-link">{{ __('landings.salone.nav_services') }}</a>
                    <a href="#booking" class="nav-item nav-link">{{ __('landings.salone.nav_booking') }}</a>
                    <a href="#contacts" class="nav-item nav-link">{{ __('landings.salone.nav_contacts') }}</a>
                </div>
                <a class="btn btn-sm btn-primary" href="#booking"><x-landing.text key="cta_label" :default="__('landings.salone.book')" /></a>
            </div>
        </nav>
    </div>
    <!-- Navbar End -->


    <!-- Hero Start -->
    <div class="container-fluid p-0 hero-header bg-light mb-5" id="top">
        <div class="container p-0">
            <div class="row g-0 align-items-center">
                <div class="col-lg-6 hero-header-text py-5">
                    <div class="py-5 px-3 ps-lg-0">
                        <h1 class="font-dancing-script text-primary animated slideInLeft">{{ __('landings.salone.welcome') }}</h1>
                        <x-landing.text key="hero_title" tag="h1" class="display-3 mb-4 animated slideInLeft" :default="$landing->title" />
                        @if($editing || filled($heroText))
                            <x-landing.text key="hero_text" tag="p" class="fs-5 mb-4 animated slideInLeft" />
                        @endif
                        <div class="row g-4 animated slideInLeft">
                            @if($phoneHref || $editing)
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="btn-square btn btn-primary flex-shrink-0">
                                            <i class="fa fa-phone text-dark"></i>
                                        </div>
                                        <div class="px-3">
                                            <h5 class="text-primary mb-0">{{ __('landings.salone.call_us') }}</h5>
                                            <a class="fs-5 text-dark" href="{{ $phoneHref ?: '#' }}"><x-landing.text key="phone" /></a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if($address !== '' || $telegram || $whatsapp || $editing)
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="btn-square btn btn-primary flex-shrink-0">
                                            <i class="fa {{ $address !== '' ? 'fa-map-marker-alt' : 'fa-paper-plane' }} text-dark"></i>
                                        </div>
                                        <div class="px-3">
                                            @if($address !== '' || $editing)
                                                <h5 class="text-primary mb-0">{{ __('landings.salone.find_us') }}</h5>
                                                <x-landing.text key="address" tag="p" class="fs-5 text-dark mb-0" />
                                            @else
                                                <h5 class="text-primary mb-0">{{ __('landings.salone.write_us') }}</h5>
                                                <a class="fs-5 text-dark" href="{{ $telegram ?: $whatsapp }}" target="_blank" rel="noopener">{{ $telegram ? 'Telegram' : 'WhatsApp' }}</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="owl-carousel header-carousel animated fadeIn">
                        <x-landing.image key="hero_image_1" default="landing-templates/salone/img/hero-slider-1.jpg" class="img-fluid" alt="" />
                        <x-landing.image key="hero_image_2" default="landing-templates/salone/img/hero-slider-2.jpg" class="img-fluid" alt="" />
                        <x-landing.image key="hero_image_3" default="landing-templates/salone/img/hero-slider-3.jpg" class="img-fluid" alt="" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Hero End -->


    @if($proofItems->isNotEmpty())
        <!-- About Start -->
        <div class="container-fluid py-5" id="about">
            <div class="container">
                <div class="row g-5">
                    <div class="col-lg-6 wow fadeIn" data-wow-delay="0.2s">
                        <x-landing.image key="about_image" default="landing-templates/salone/img/about.jpg" class="img-fluid mb-3" alt="" />
                        @if($phoneHref || $editing)
                            <div class="d-flex align-items-center bg-light">
                                <div class="btn-square flex-shrink-0 bg-primary" style="width: 100px; height: 100px;">
                                    <i class="fa fa-phone fa-2x text-dark"></i>
                                </div>
                                <div class="px-3">
                                    <h3><a class="text-dark" href="{{ $phoneHref ?: '#' }}"><x-landing.text key="phone" /></a></h3>
                                    <span>{{ __('landings.salone.call_direct') }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s">
                        <h1 class="font-dancing-script text-primary">{{ __('landings.salone.about_kicker') }}</h1>
                        <h1 class="mb-5">{{ __('landings.salone.about_title') }}</h1>
                        <div class="row g-3 mb-5">
                            @foreach($proofItems->take(3) as $item)
                                <div class="col-12">
                                    <div class="bg-light d-flex align-items-center p-4">
                                        <i class="fas fa-check fa-2x text-primary flex-shrink-0"></i>
                                        <x-landing.text key="proof_items_text" :index="$loop->index" tag="p" class="text-dark mb-0 ms-4 fs-5" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <a class="btn btn-primary text-uppercase px-5 py-3" href="#booking"><x-landing.text key="cta_label" :default="__('landings.salone.book')" /></a>
                    </div>
                </div>
            </div>
        </div>
        <!-- About End -->
    @endif


    <!-- Service Start -->
    <div class="container-fluid service py-5" id="services">
        <div class="container">
            <div class="text-center wow fadeIn" data-wow-delay="0.1s">
                <h1 class="font-dancing-script text-primary">{{ __('landings.salone.services_kicker') }}</h1>
                <h1 class="mb-5">{{ __('landings.salone.services_title') }}</h1>
            </div>
            <div class="row g-4 g-md-0 text-center">
                @php
                    $cards = $services->isNotEmpty()
                        ? $services->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'price' => $s->base_price, 'duration' => $s->duration_min])
                        : $serviceNames->map(fn ($n) => ['id' => null, 'name' => $n, 'price' => null, 'duration' => null]);
                @endphp
                @foreach($cards->take(6) as $card)
                    <div class="col-md-6 col-lg-4">
                        <div class="service-item h-100 p-4 border-bottom border-end wow fadeIn" data-wow-delay="{{ 0.1 + ($loop->index % 3) * 0.2 }}s">
                            <img class="img-fluid" src="{{ $asset('img/' . $serviceIcons[$loop->index % count($serviceIcons)] . '.png') }}" alt="">
                            <h3 class="mb-3">{{ $card['name'] }}</h3>
                            <p class="mb-3">
                                @if(!empty($card['price']))
                                    {{ __('landings.salone.from_price', ['price' => number_format((float) $card['price'], 0, ',', ' ')]) }}
                                @endif
                                @if(!empty($card['price']) && !empty($card['duration'])) · @endif
                                @if(!empty($card['duration']))
                                    {{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}
                                @endif
                            </p>
                            <a class="btn btn-sm btn-primary text-uppercase" href="#booking" data-pick-service="{{ $card['id'] }}">{{ __('landings.salone.book') }} <i
                                    class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <!-- Service End -->


    <!-- Booking Start -->
    <div class="container-fluid bg-light py-5" id="booking">
        <div class="container">
            <div class="text-center wow fadeIn" data-wow-delay="0.2s">
                <h1 class="font-dancing-script text-primary">{{ __('landings.salone.booking_kicker') }}</h1>
                <h1 class="mb-5">{{ __('landings.salone.booking_title') }}</h1>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <form id="salone-form" novalidate>
                        <div class="row g-3">
                            <div class="col-12">
                                <select class="form-select border-0 px-4" name="service_id" id="salone-service" style="height: 55px;">
                                    <option value="">{{ __('landings.salone.field_service_any') }}</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12"><div id="salone-picker"></div></div>
                            <div class="col-md-6">
                                <input type="text" class="form-control border-0 px-4" name="client_name" style="height: 55px;" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120">
                            </div>
                            <div class="col-md-6">
                                <input type="tel" class="form-control border-0 px-4" name="client_phone" style="height: 55px;" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask>
                            </div>
                            <div class="col-12">
                                <textarea class="form-control border-0 px-4 py-3" name="message" rows="3" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000"></textarea>
                            </div>
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-primary text-uppercase px-5 py-3" id="salone-submit">{{ __('landings.salone.submit') }}</button>
                            </div>
                            <div class="col-12 text-center" id="salone-message" role="status" aria-live="polite"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Booking End -->


    <!-- Footer Start -->
    <div class="container-fluid footer position-relative bg-dark text-white-50 py-5 wow fadeIn" data-wow-delay="0.2s" id="contacts">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-6 pe-lg-5">
                    <a href="#top" class="navbar-brand">
                        <h1 class="display-5 text-primary mb-0"><i class="bi bi-scissors"></i><x-landing.text key="title" /></h1>
                    </a>
                    @if($address !== '' || $editing)
                        <p class="mb-2 mt-4"><i class="fa fa-map-marker-alt me-2"></i><x-landing.text key="address" /></p>
                    @endif
                    @if($phoneHref || $editing)
                        <p class="mb-2"><i class="fa fa-phone-alt me-2"></i><a class="text-white-50" href="{{ $phoneHref ?: '#' }}"><x-landing.text key="phone" /></a></p>
                    @endif
                    <div class="d-flex justify-content-start mt-4">
                        @if($telegram)
                            <a class="btn btn-sm-square btn-primary me-3" href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>
                        @endif
                        @if($whatsapp)
                            <a class="btn btn-sm-square btn-primary me-3" href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                        @endif
                    </div>
                </div>
                <div class="col-lg-6 ps-lg-5">
                    @if($editing || filled($settings['booking_hint'] ?? null))
                        <h5 class="text-primary mb-4">{{ __('landings.salone.booking_title') }}</h5>
                        <x-landing.text key="booking_hint" tag="p" class="mb-4" />
                        <a class="btn btn-primary text-uppercase px-5 py-3" href="#booking"><x-landing.text key="cta_label" :default="__('landings.salone.book')" /></a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->


    <!-- Copyright Start -->
    <div class="container-fluid bg-dark text-white border-top border-secondary py-4 wow fadeIn" data-wow-delay="0.1s">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    &copy; <x-landing.text key="title" />, {{ __('landings.salone.rights') }}
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <!--/*** The author’s attribution link must remain intact in the template. ***/-->
                    {{ __('landings.salone.designed_by') }} <a class="border-bottom" href="https://htmlcodex.com" target="_blank" rel="noopener">HTML Codex</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Copyright End -->


    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ $asset('lib/wow/wow.min.js') }}"></script>
    <script src="{{ $asset('lib/owlcarousel/owl.carousel.min.js') }}"></script>

    <!-- Template Javascript -->
    <script src="{{ $asset('js/main.js') }}"></script>

    @include('landings.partials.request-script', ['ids' => ['form' => 'salone-form', 'button' => 'salone-submit', 'message' => 'salone-message', 'service' => 'salone-service', 'picker' => 'salone-picker'], 'accent' => '#BF9456', 'accentText' => '#000'])
    @include('landings.partials.editor-assets')
</body>

</html>
