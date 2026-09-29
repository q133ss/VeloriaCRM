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
    $settings = $landing->settings ?? [];
    $content = app(\App\Services\Landing\LandingContent::class);
    $editing = $content->editing();
    $asset = fn (string $path) => asset('landing-templates/pretty/' . $path);

    $phone = trim((string) ($settings['phone'] ?? ''));
    $phoneHref = $phone !== '' ? 'tel:' . preg_replace('/[^0-9+]+/', '', $phone) : null;
    $address = trim((string) ($settings['address'] ?? ''));
    $telegram = $settings['telegram_url'] ?? null;
    $whatsapp = $settings['whatsapp_url'] ?? null;
    $heroText = $content->text('hero_text');
    $proofItems = collect($content->items('proof_items_text'));
    $faqItems = collect($content->items('faq_items_text'));
    $hours = app(\App\Services\Landing\WorkingHours::class)->forUser($landing->user_id);

    $stats = app(\App\Services\Landing\PageStats::class)->forUser($landing->user_id);
    $showCounters = app(\App\Services\Landing\PageStats::class)->worthShowing($stats);

    $services = $featuredServices->values();
    $cards = $services->isNotEmpty()
        ? $services->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'price' => $s->base_price, 'duration' => $s->duration_min])
        : collect($settings['service_names'] ?? [])->map(fn ($n) => ['id' => null, 'name' => $n, 'price' => null, 'duration' => null]);
    $priced = $cards->filter(fn ($c) => ! empty($c['price']))->take(4)->values();
    $icons = ['flaticon-facial-treatment', 'flaticon-cosmetics', 'flaticon-curl', 'flaticon-flower'];

    $hasOffer = $landing->type === 'promotion' && ! empty($settings['discount_percent']);
    $endsAt = ! empty($settings['ends_at']) ? \Illuminate\Support\Carbon::parse($settings['ends_at'])->format('d.m.Y') : null;
    $ctaDefault = __('landings.salone.book');
    $hasInfo = $address !== '' || $phoneHref || $hours || $editing;
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
                    <li class="nav-item"><a href="#services" class="nav-link">{{ __('landings.pretty.nav_services') }}</a></li>
                    <li class="nav-item"><a href="#team" class="nav-link">{{ __('landings.pretty.nav_team') }}</a></li>
                    @if($hasOffer)
                        <li class="nav-item"><a href="#offer" class="nav-link">{{ __('landings.pretty.nav_offer') }}</a></li>
                    @endif
                    <li class="nav-item"><a href="#work" class="nav-link">{{ __('landings.pretty.nav_work') }}</a></li>
                    @if($priced->isNotEmpty())
                        <li class="nav-item"><a href="#pricing" class="nav-link">{{ __('landings.pretty.nav_pricing') }}</a></li>
                    @endif
                    @if($faqItems->isNotEmpty())
                        <li class="nav-item"><a href="#faq" class="nav-link">{{ __('landings.pretty.nav_faq') }}</a></li>
                    @endif
                    <li class="nav-item"><a href="#booking" class="nav-link">{{ __('landings.pretty.nav_booking') }}</a></li>
                    <li class="nav-item"><a href="#contacts" class="nav-link">{{ __('landings.pretty.nav_contacts') }}</a></li>
                </ul>
            </div>
        </div>
    </nav>

    {{-- Services --}}
    <section class="ftco-section" id="services">
        <div class="container">
            <div class="row justify-content-center mb-5 pb-3">
                <div class="col-md-7 heading-section ftco-animate text-center">
                    <h2 class="mb-4">{{ __('landings.pretty.services_title') }}</h2>
                    <p>{{ __('landings.pretty.services_lead') }}</p>
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
                                <p><a href="#booking" class="btn btn-primary btn-outline-primary btn-sm px-3 py-2 smoothscroll" data-pick-service="{{ $card['id'] }}">{{ __('landings.salone.book') }}</a></p>
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
                    <h2 class="mb-4">{{ __('landings.pretty.team_title') }}</h2>
                    <p>{{ __('landings.pretty.team_lead') }}</p>
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
        <section class="ftco-section ftco-discount img" id="offer" style="background-image: url({{ $asset('images/bg_2.jpg') }});">
            <div class="overlay"></div>
            <div class="container">
                <div class="row justify-content-end">
                    <div class="col-md-5 discount ftco-animate">
                        <h3>{{ __('landings.pretty.offer_kicker', ['percent' => rtrim(rtrim(number_format((float) $settings['discount_percent'], 1, '.', ''), '0'), '.')]) }}</h3>
                        @if(!empty($settings['promo_code']))
                            <h2 class="mb-4">{{ __('landings.pretty.offer_code', ['code' => strtoupper($settings['promo_code'])]) }}</h2>
                        @endif
                        <p class="mb-4">
                            @if($endsAt)
                                {{ __('landings.pretty.offer_until', ['date' => $endsAt]) }}
                            @endif
                            {{ __('landings.pretty.offer_say_code') }}
                        </p>
                        <p><a href="#booking" class="btn btn-white btn-outline-white px-4 py-3 smoothscroll"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a></p>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Work --}}
    <section class="ftco-section" id="work">
        <div class="container">
            <div class="row justify-content-center mb-5 pb-3">
                <div class="col-md-7 heading-section text-center ftco-animate">
                    <h2 class="mb-4">{{ __('landings.pretty.work_title') }}</h2>
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
                        <h2 class="mb-4">{{ __('landings.pretty.pricing_title') }}</h2>
                        <p>{{ __('landings.pretty.pricing_lead') }}</p>
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
                                <p class="button text-center"><a href="#booking" class="btn btn-primary btn-outline-primary px-4 py-3 smoothscroll" data-pick-service="{{ $card['id'] }}">{{ __('landings.pretty.pricing_order') }}</a></p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Counters: real figures, only once there are enough of them --}}
    @if($showCounters)
        <section class="ftco-section ftco-counter img" id="section-counter" style="background-image: url({{ $asset('images/bg_2.jpg') }});">
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
        </section>
    @endif

    {{-- Questions (the template's "blog" block) --}}
    @if($faqItems->isNotEmpty())
        <section class="ftco-section" id="faq">
            <div class="container">
                <div class="row justify-content-center mb-5 pb-3">
                    <div class="col-md-7 heading-section ftco-animate text-center">
                        <h2 class="mb-4">{{ __('landings.pretty.faq_title') }}</h2>
                        <p>{{ __('landings.pretty.faq_lead') }}</p>
                    </div>
                </div>
                <div class="row d-flex">
                    @foreach($faqItems->take(3) as $item)
                        <div class="col-md-4 d-flex ftco-animate">
                            <div class="blog-entry align-self-stretch">
                                <span class="block-20" style="background-image: url('{{ $asset('images/image_' . ($loop->index + 1) . '.jpg') }}');"></span>
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
                                    <h3 class="mb-3">{{ __('landings.pretty.info_address') }}</h3>
                                    <x-landing.text key="address" tag="p" />
                                </div>
                            @endif
                            @if($phoneHref || $editing)
                                <div class="mb-4">
                                    <h3 class="mb-3">{{ __('landings.pretty.info_phone') }}</h3>
                                    <p class="day"><strong><a href="{{ $phoneHref ?: '#' }}" style="color: inherit;"><x-landing.text key="phone" tag="bdi" /></a></strong></p>
                                </div>
                            @endif
                            @if($hours)
                                <div>
                                    <h3 class="mb-3">{{ __('landings.pretty.info_hours') }}</h3>
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
                    <h3 class="mb-3">{{ __('landings.pretty.booking_title') }}</h3>
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
                        <h2 class="ftco-heading-2">{{ __('landings.pretty.footer_about') }}</h2>
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
                        <h2 class="ftco-heading-2">{{ __('landings.pretty.footer_services') }}</h2>
                        <ul class="list-unstyled">
                            @foreach($cards->take(6) as $card)
                                <li class="mb-2"><a href="#booking" class="smoothscroll" data-pick-service="{{ $card['id'] }}">{{ $card['name'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="ftco-footer-widget mb-4 ml-md-4">
                        <h2 class="ftco-heading-2">{{ __('landings.pretty.footer_menu') }}</h2>
                        <ul class="list-unstyled">
                            <li class="mb-2"><a href="#services" class="smoothscroll">{{ __('landings.pretty.nav_services') }}</a></li>
                            <li class="mb-2"><a href="#work" class="smoothscroll">{{ __('landings.pretty.nav_work') }}</a></li>
                            <li class="mb-2"><a href="#booking" class="smoothscroll">{{ __('landings.pretty.nav_booking') }}</a></li>
                        </ul>
                    </div>
                </div>
                @if($address !== '' || $phoneHref || $editing)
                    <div class="col-md-3">
                        <div class="ftco-footer-widget mb-4">
                            <h2 class="ftco-heading-2">{{ __('landings.pretty.footer_contacts') }}</h2>
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
