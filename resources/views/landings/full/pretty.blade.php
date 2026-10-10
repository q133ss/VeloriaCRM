{{--
    Pretty: Free Bootstrap 4 beauty salon template by Colorlib (https://colorlib.com),
    distributed by ThemeWagon (https://themewagon.com/themes/free-bootstrap-4-html5-beauty-salon-website-template-pretty/),
    CC BY 3.0: the link back to Colorlib in the footer must stay. Assets and the license
    live in public/landing-templates/pretty.

    Every section of the original page is here. Placeholder content is replaced with the
    master's own data: services and prices from her catalog, opening hours from her
    schedule, counters from her real figures (shown once they are worth showing), the
    "blog" block carries her questions and answers. Photos are stock until she replaces
    them in the click editor. The appointment form is the shared booking widget.
--}}
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/pretty/' . $path);
    $icons = ['flaticon-facial-treatment', 'flaticon-cosmetics', 'flaticon-curl', 'flaticon-flower'];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ $landing->title }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $heroText), 150) }}">

    <link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,600,700&amp;subset=cyrillic" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="{{ $asset('css/open-iconic-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ $asset('css/animate.css') }}">
    <link rel="stylesheet" href="{{ $asset('css/flaticon.css') }}">
    <link rel="stylesheet" href="{{ $asset('css/icomoon.css') }}">
    <link rel="stylesheet" href="{{ $asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ $asset('css/veloria.css') }}">
</head>
<body id="top">

    @if($isPreview && empty($isEdit))
        <div style="position: fixed; top: 0; left: 0; right: 0; z-index: 2000; background: #111; color: #fff; text-align: center; font-size: 12px; padding: 2px 0;">{{ __('landings.public.preview_badge') }}</div>
    @endif

    <x-landing.bg key="hero_image_1" default="landing-templates/pretty/images/bg_1.jpg" class="hero-wrap js-fullheight">
        <div class="overlay"></div>
        <div class="container">
            <div class="row no-gutters slider-text js-fullheight align-items-center justify-content-center">
                <div class="col-md-8 ftco-animate text-center">
                    <div class="icon">
                        <a href="#top" class="logo">
                            <span class="flaticon-flower"></span>
                            <h1><x-landing.text key="title" tag="bdi" /></h1>
                        </a>
                    </div>
                    <x-landing.text key="hero_title" tag="h1" class="mb-4" :default="__('landings.pretty.hero_default')" :title-fallback="false" />
                    @if($editing || filled($heroText))
                        <x-landing.text key="hero_text" tag="p" class="mb-5" />
                    @endif
                    <p><a href="#booking" class="btn btn-white btn-outline-white px-4 py-3"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a></p>
                </div>
            </div>
        </div>
    </x-landing.bg>

    <nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
        <div class="container">
            <a class="navbar-brand" href="#top"><x-landing.text key="title" tag="bdi" /></a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Menu">
                <span class="oi oi-menu"></span> {{ __('landings.pretty.menu') }}
            </button>

            <div class="collapse navbar-collapse" id="ftco-nav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item"><a href="#services" class="nav-link"><x-landing.text key="lbl_pretty_nav_services" tag="bdi" :default="__('landings.pretty.nav_services')" :title-fallback="false" /></a></li>
                    <li class="nav-item"><a href="#team" class="nav-link"><x-landing.text key="lbl_pretty_nav_team" tag="bdi" :default="__('landings.pretty.nav_team')" :title-fallback="false" /></a></li>
                    @if($hasOffer)
                        <li class="nav-item"><a href="#offer" class="nav-link"><x-landing.text key="lbl_pretty_nav_offer" tag="bdi" :default="__('landings.pretty.nav_offer')" :title-fallback="false" /></a></li>
                    @endif
                    <li class="nav-item"><a href="#work" class="nav-link"><x-landing.text key="lbl_pretty_nav_work" tag="bdi" :default="__('landings.pretty.nav_work')" :title-fallback="false" /></a></li>
                    @if($priced->isNotEmpty())
                        <li class="nav-item"><a href="#pricing" class="nav-link"><x-landing.text key="lbl_pretty_nav_pricing" tag="bdi" :default="__('landings.pretty.nav_pricing')" :title-fallback="false" /></a></li>
                    @endif
                    @if($faqItems->isNotEmpty())
                        <li class="nav-item"><a href="#faq" class="nav-link"><x-landing.text key="lbl_pretty_nav_faq" tag="bdi" :default="__('landings.pretty.nav_faq')" :title-fallback="false" /></a></li>
                    @endif
                    <li class="nav-item"><a href="#booking" class="nav-link"><x-landing.text key="lbl_pretty_nav_booking" tag="bdi" :default="__('landings.pretty.nav_booking')" :title-fallback="false" /></a></li>
                    <li class="nav-item"><a href="#contacts" class="nav-link"><x-landing.text key="lbl_pretty_nav_contacts" tag="bdi" :default="__('landings.pretty.nav_contacts')" :title-fallback="false" /></a></li>
                </ul>
            </div>
        </div>
    </nav>

    {{-- Services --}}
    <section class="ftco-section" id="services">
        <div class="container">
            <div class="row justify-content-center mb-5 pb-3">
                <div class="col-md-7 heading-section ftco-animate text-center">
                    <h2 class="mb-4"><x-landing.text key="lbl_pretty_services_title" tag="bdi" :default="__('landings.pretty.services_title')" :title-fallback="false" /></h2>
                    <p><x-landing.text key="lbl_pretty_services_lead" tag="bdi" :default="__('landings.pretty.services_lead')" :title-fallback="false" /></p>
                </div>
            </div>
            <div class="row justify-content-center">
                @foreach($cards->take(9) as $card)
                    <div class="col-md-4 ftco-animate">
                        <div class="media d-block text-center block-6 services">
                            <div class="icon d-flex mb-3"><span class="{{ $icons[$loop->index % count($icons)] }}"></span></div>
                            <div class="media-body">
                                <h3 class="heading">{{ $card['name'] }}</h3>
                                @if(!empty($card['price']) || !empty($card['duration']))
                                    <p>
                                        @if(!empty($card['price']))
                                            {{ __('landings.salone.from_price', ['price' => number_format((float) $card['price'], 0, ',', ' ')]) }}
                                        @endif
                                        @if(!empty($card['price']) && !empty($card['duration'])) · @endif
                                        @if(!empty($card['duration']))
                                            {{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}
                                        @endif
                                    </p>
                                @endif
                                <p><a href="#booking" class="btn btn-primary btn-outline-primary btn-sm px-3 py-2 smoothscroll" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_salone_book" tag="bdi" :default="__('landings.salone.book')" :title-fallback="false" /></a></p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- The master --}}
    <section class="ftco-section bg-light" id="team">
        <div class="container">
            <div class="row justify-content-center mb-5 pb-3">
                <div class="col-md-7 heading-section ftco-animate text-center">
                    <h2 class="mb-4"><x-landing.text key="lbl_pretty_team_title" tag="bdi" :default="__('landings.pretty.team_title')" :title-fallback="false" /></h2>
                    <p><x-landing.text key="lbl_pretty_team_lead" tag="bdi" :default="__('landings.pretty.team_lead')" :title-fallback="false" /></p>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 d-flex mb-sm-4 ftco-animate">
                    <div class="staff">
                        <x-landing.bg key="master_photo" default="landing-templates/pretty/images/person_1.jpg" class="img mb-4" />
                        <div class="info text-center">
                            <h3><a href="#booking" class="smoothscroll"><x-landing.text key="title" tag="bdi" /></a></h3>
                            <x-landing.text key="master_role" tag="span" class="position" :default="__('landings.pretty.master_role_default')" />
                            <div class="text">
                                <x-landing.text key="master_bio" tag="p" :default="__('landings.pretty.master_bio_default')" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Offer (promotion pages) --}}
    @if($hasOffer)
        <x-landing.bg key="extra_image_1" default="landing-templates/pretty/images/bg_2.jpg" tag="section" class="ftco-section ftco-discount img" id="offer">
            <div class="overlay"></div>
            <div class="container">
                <div class="row justify-content-end">
                    <div class="col-md-5 discount ftco-animate">
                        <h3>{{ __('landings.pretty.offer_kicker', ['percent' => $offerPercent]) }}</h3>
                        @if($promoCode)
                            <h2 class="mb-4">{{ __('landings.pretty.offer_code', ['code' => $promoCode]) }}</h2>
                        @endif
                        <p class="mb-4">
                            @if($endsAt)
                                {{ __('landings.pretty.offer_until', ['date' => $endsAt]) }}
                            @endif
                            <x-landing.text key="lbl_pretty_offer_say_code" tag="bdi" :default="__('landings.pretty.offer_say_code')" :title-fallback="false" />
                        </p>
                        <p><a href="#booking" class="btn btn-white btn-outline-white px-4 py-3 smoothscroll"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a></p>
                    </div>
                </div>
            </div>
        </x-landing.bg>
    @endif

    {{-- Work --}}
    <section class="ftco-section" id="work">
        <div class="container">
            <div class="row justify-content-center mb-5 pb-3">
                <div class="col-md-7 heading-section text-center ftco-animate">
                    <h2 class="mb-4"><x-landing.text key="lbl_pretty_work_title" tag="bdi" :default="__('landings.pretty.work_title')" :title-fallback="false" /></h2>
                    @if($editing)
                        <p>{{ __('landings.pretty.work_hint') }}</p>
                    @endif
                </div>
            </div>
            <div class="row">
                @foreach([1, 2, 3] as $i)
                    <div class="col-md-4 ftco-animate">
                        <div class="work-entry">
                            <x-landing.image :key="'work_image_' . $i" :default="'landing-templates/pretty/images/work-' . $i . '.jpg'" class="img-fluid" alt="" />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    @if($priced->isNotEmpty())
        <section class="ftco-section bg-light" id="pricing">
            <div class="container">
                <div class="row justify-content-center mb-5 pb-3">
                    <div class="col-md-7 heading-section text-center ftco-animate">
                        <h2 class="mb-4"><x-landing.text key="lbl_pretty_pricing_title" tag="bdi" :default="__('landings.pretty.pricing_title')" :title-fallback="false" /></h2>
                        <p><x-landing.text key="lbl_pretty_pricing_lead" tag="bdi" :default="__('landings.pretty.pricing_lead')" :title-fallback="false" /></p>
                    </div>
                </div>
                <div class="row justify-content-center">
                    @foreach($priced as $card)
                        <div class="col-md-3 ftco-animate">
                            <div class="pricing-entry {{ $loop->iteration === 2 ? 'active' : '' }} pb-5 text-center">
                                <div>
                                    <h3 class="mb-4">{{ $card['name'] }}</h3>
                                    <p>
                                        <span class="price">{{ number_format((float) $card['price'], 0, ',', ' ') }} ₽</span>
                                        @if(!empty($card['duration']))
                                            <span class="per">/ {{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}</span>
                                        @endif
                                    </p>
                                </div>
                                <p class="button text-center"><a href="#booking" class="btn btn-primary btn-outline-primary px-4 py-3 smoothscroll" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_pretty_pricing_order" tag="bdi" :default="__('landings.pretty.pricing_order')" :title-fallback="false" /></a></p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Counters: real figures, only once there are enough of them --}}
    @if($showCounters)
        <x-landing.bg key="extra_image_2" default="landing-templates/pretty/images/bg_2.jpg" tag="section" class="ftco-section ftco-counter img" id="section-counter">
            <div class="overlay"></div>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <div class="row">
                            @foreach([['clients', 'counters_clients'], ['visits', 'counters_visits'], ['services', 'counters_services'], ['upcoming', 'counters_upcoming']] as [$stat, $label])
                                <div class="col-md-6 col-lg-3 d-flex justify-content-center counter-wrap ftco-animate">
                                    <div class="block-18 text-center">
                                        <div class="text">
                                            <div class="icon"><span class="flaticon-flower"></span></div>
                                            <span>{{ __('landings.pretty.' . $label) }}</span>
                                            <strong class="number">{{ number_format($stats[$stat], 0, ',', ' ') }}</strong>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </x-landing.bg>
    @endif

    {{-- Questions (the template's "blog" block) --}}
    @if($faqItems->isNotEmpty())
        <section class="ftco-section" id="faq">
            <div class="container">
                <div class="row justify-content-center mb-5 pb-3">
                    <div class="col-md-7 heading-section ftco-animate text-center">
                        <h2 class="mb-4"><x-landing.text key="lbl_pretty_faq_title" tag="bdi" :default="__('landings.pretty.faq_title')" :title-fallback="false" /></h2>
                        <p><x-landing.text key="lbl_pretty_faq_lead" tag="bdi" :default="__('landings.pretty.faq_lead')" :title-fallback="false" /></p>
                    </div>
                </div>
                <div class="row d-flex">
                    @foreach($faqItems->take(3) as $item)
                        <div class="col-md-4 d-flex ftco-animate">
                            <div class="blog-entry align-self-stretch">
                                <x-landing.bg tag="span" :key="'faq_image_' . ($loop->index + 1)" :default="'landing-templates/pretty/images/image_' . ($loop->index + 1) . '.jpg'" class="block-20" />
                                <div class="text py-4 d-block">
                                    <x-landing.text key="faq_items_text" :index="$loop->index" tag="p" />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Appointment --}}
    <section class="ftco-section ftco-appointment" id="booking">
        <div class="overlay"></div>
        <div class="container">
            <div class="row d-md-flex align-items-center">
                @if($hasInfo)
                    <div class="col-md-4 d-flex align-self-stretch ftco-animate">
                        <div class="appointment-info text-center p-5">
                            @if($address !== '' || $editing)
                                <div class="mb-4">
                                    <h3 class="mb-3"><x-landing.text key="lbl_pretty_info_address" tag="bdi" :default="__('landings.pretty.info_address')" :title-fallback="false" /></h3>
                                    <x-landing.text key="address" tag="p" />
                                </div>
                            @endif
                            @if($phoneHref || $editing)
                                <div class="mb-4">
                                    <h3 class="mb-3"><x-landing.text key="lbl_pretty_info_phone" tag="bdi" :default="__('landings.pretty.info_phone')" :title-fallback="false" /></h3>
                                    <p class="day"><strong><a href="{{ $phoneHref ?: '#' }}" style="color: inherit;"><x-landing.text key="phone" tag="bdi" /></a></strong></p>
                                </div>
                            @endif
                            @if($hours)
                                <div>
                                    <h3 class="mb-3"><x-landing.text key="lbl_pretty_info_hours" tag="bdi" :default="__('landings.pretty.info_hours')" :title-fallback="false" /></h3>
                                    @foreach($hours as $line)
                                        <p class="day mb-1"><strong>{{ $line['days'] }}</strong></p>
                                        <span class="d-block mb-3">{{ $line['from'] }} – {{ $line['to'] }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
                <div class="{{ $hasInfo ? 'col-md-8 pl-md-5' : 'col-md-8 offset-md-2' }} appointment ftco-animate">
                    <h3 class="mb-3"><x-landing.text key="lbl_pretty_booking_title" tag="bdi" :default="__('landings.pretty.booking_title')" :title-fallback="false" /></h3>
                    @if($editing || filled($settings['booking_hint'] ?? null))
                        <x-landing.text key="booking_hint" tag="p" class="mb-4" />
                    @endif
                    <form id="pretty-form" class="appointment-form" novalidate>
                        <div class="row form-group d-flex">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120" autocomplete="name">
                            </div>
                            <div class="col-md-6">
                                <input type="tel" class="form-control" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask>
                            </div>
                        </div>
                        <div class="form-group">
                            <select class="form-control" name="service_id" id="pretty-service" style="height: 55px;">
                                <option value="">{{ __('landings.salone.field_service_any') }}</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="pretty-picker" class="mb-3"></div>
                        <div class="form-group">
                            <textarea cols="30" rows="3" class="form-control" name="message" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000"></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit" id="pretty-submit" class="btn btn-white btn-outline-white py-3 px-4">{{ __('landings.salone.submit') }}</button>
                        </div>
                        <div id="pretty-message" role="status" aria-live="polite"></div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="ftco-footer ftco-section img" id="contacts">
        <div class="overlay"></div>
        <div class="container">
            <div class="row mb-5">
                <div class="col-md-4">
                    <div class="ftco-footer-widget mb-4">
                        <h2 class="ftco-heading-2"><x-landing.text key="lbl_pretty_footer_about" tag="bdi" :default="__('landings.pretty.footer_about')" :title-fallback="false" /></h2>
                        @if($proofItems->isNotEmpty())
                            <ul class="list-unstyled">
                                @foreach($proofItems->take(3) as $item)
                                    <x-landing.text key="proof_items_text" :index="$loop->index" tag="li" class="mb-2" />
                                @endforeach
                            </ul>
                        @endif
                        @if($telegram || $whatsapp)
                            <ul class="ftco-footer-social list-unstyled float-md-left float-lft mt-4">
                                @if($telegram)
                                    <li><a href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><span class="bi bi-telegram"></span></a></li>
                                @endif
                                @if($whatsapp)
                                    <li><a href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><span class="bi bi-whatsapp"></span></a></li>
                                @endif
                            </ul>
                        @endif
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="ftco-footer-widget mb-4 ml-md-4">
                        <h2 class="ftco-heading-2"><x-landing.text key="lbl_pretty_footer_services" tag="bdi" :default="__('landings.pretty.footer_services')" :title-fallback="false" /></h2>
                        <ul class="list-unstyled">
                            @foreach($cards->take(6) as $card)
                                <li class="mb-2"><a href="#booking" class="smoothscroll" data-pick-service="{{ $card['id'] }}">{{ $card['name'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="ftco-footer-widget mb-4 ml-md-4">
                        <h2 class="ftco-heading-2"><x-landing.text key="lbl_pretty_footer_menu" tag="bdi" :default="__('landings.pretty.footer_menu')" :title-fallback="false" /></h2>
                        <ul class="list-unstyled">
                            <li class="mb-2"><a href="#services" class="smoothscroll"><x-landing.text key="lbl_pretty_nav_services" tag="bdi" :default="__('landings.pretty.nav_services')" :title-fallback="false" /></a></li>
                            <li class="mb-2"><a href="#work" class="smoothscroll"><x-landing.text key="lbl_pretty_nav_work" tag="bdi" :default="__('landings.pretty.nav_work')" :title-fallback="false" /></a></li>
                            <li class="mb-2"><a href="#booking" class="smoothscroll"><x-landing.text key="lbl_pretty_nav_booking" tag="bdi" :default="__('landings.pretty.nav_booking')" :title-fallback="false" /></a></li>
                        </ul>
                    </div>
                </div>
                @if($address !== '' || $phoneHref || $editing)
                    <div class="col-md-3">
                        <div class="ftco-footer-widget mb-4">
                            <h2 class="ftco-heading-2"><x-landing.text key="lbl_pretty_footer_contacts" tag="bdi" :default="__('landings.pretty.footer_contacts')" :title-fallback="false" /></h2>
                            <div class="block-23 mb-3">
                                <ul>
                                    @if($address !== '' || $editing)
                                        <li><span class="icon icon-map-marker"></span><span class="text"><x-landing.text key="address" tag="bdi" /></span></li>
                                    @endif
                                    @if($phoneHref || $editing)
                                        <li><a href="{{ $phoneHref ?: '#' }}"><span class="icon icon-phone"></span><span class="text"><x-landing.text key="phone" tag="bdi" /></span></a></li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="row">
                <div class="col-md-12 text-center">
                    <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
                    <p>
                        &copy;{{ date('Y') }} <x-landing.text key="title" tag="bdi" /> | {{ __('landings.pretty.credit') }} <i class="icon-heart" aria-hidden="true"></i> <a href="https://colorlib.com" target="_blank" rel="noopener">Colorlib</a>
                    </p>
                    <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
                </div>
            </div>
        </div>
    </footer>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ $asset('js/jquery.waypoints.min.js') }}"></script>
    <script src="{{ $asset('js/main.js') }}"></script>

    @include('landings.partials.request-script', ['ids' => ['form' => 'pretty-form', 'button' => 'pretty-submit', 'message' => 'pretty-message', 'service' => 'pretty-service', 'picker' => 'pretty-picker'], 'accent' => '#252525', 'accentText' => '#fff'])
    @include('landings.partials.editor-assets')
</body>
</html>
