<!--
author: Boostraptheme
author URL: https://boostraptheme.com
License: Creative Commons Attribution 4.0 Unported
License URL: https://creativecommons.org/licenses/by/4.0/

Beauty and Salon, a free Bootstrap 4 template (distributed by ThemeWagon). CC BY 4.0: the credit link
to Boostraptheme in the footer must stay. Assets and the license live in public/landing-templates/salon-style.

Imported with landing:import-template, then finished by hand: the master's own data instead of
placeholder text; no invented team or reviews; services and prices come from her catalog, the opening
hours are her real booking schedule; the contact form became the shared booking widget. The slider,
popup and testimonial plugins of the original are not loaded.
-->
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/salon-style/' . $path);
    $price = fn ($value) => number_format((float) $value, 0, ',', ' ');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>{{ $landing->title }}</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $heroText), 150) }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link href="{{ $asset('img/beauty-salon_logo_96dp.png') }}" rel="icon" sizes="96x96" type="image/png">

        <link href="https://fonts.googleapis.com/css?family=Lobster&amp;subset=cyrillic|Roboto:400,700&amp;subset=cyrillic&amp;display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
        <link rel="stylesheet" href="{{ $asset('css/app.css') }}">
        <link rel="stylesheet" href="{{ $asset('css/veloria.css') }}">
    </head>
    <body id="page-top-body">
        @if($isPreview && empty($isEdit))
            <div style="position: fixed; top: 0; left: 0; right: 0; z-index: 2000; background: #111; color: #fff; text-align: center; font-size: 12px; padding: 2px 0;">{{ __('landings.public.preview_badge') }}</div>
        @endif

        @if($phone !== '' || $address !== '' || $editing)
            <div class="topmenu">
                <div class="container">
                    <div class="row" id="page-top">
                        <div class="col-md-6 col-sm-6 phone">
                            @if($phoneHref || $editing)
                                <div><i class="fa fa-phone"></i> <a href="{{ $phoneHref ?: '#' }}"><x-landing.text key="phone" tag="bdi" /></a></div>
                            @endif
                        </div>
                        <div class="col-md-6 col-sm-6 address">
                            @if($address !== '' || $editing)
                                <div><i class="fa fa-map-marker"></i> <x-landing.text key="address" tag="bdi" /></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <nav class="navbar navbar-expand-lg navbar-light" id="mainNav" data-toggle="affix">
            <div class="container-fluid">
                <a class="navbar-brand js-scroll-trigger ss-brand" href="#home"><x-landing.text key="title" tag="bdi" /></a>
                <button class="navbar-toggler navbar-toggler-center ml-auto py-3 my-2" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="{{ __('landings.pretty.menu') }}">
                    {{ __('landings.pretty.menu') }} <i class="fa fa-bars"></i>
                </button>
                <div class="collapse navbar-collapse" id="navbarResponsive">
                    <ul class="navbar-nav text-uppercase ml-auto">
                        <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#services"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></a></li>
                        <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#about"><x-landing.text key="lbl_common_about_title" tag="bdi" :default="__('landings.common.about_title')" :title-fallback="false" /></a></li>
                        <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#portfolio"><x-landing.text key="lbl_pretty_nav_work" tag="bdi" :default="__('landings.pretty.nav_work')" :title-fallback="false" /></a></li>
                        <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#contact"><x-landing.text key="lbl_common_nav_booking" tag="bdi" :default="__('landings.common.nav_booking')" :title-fallback="false" /></a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <header id="home">
            <div class="carousel slide">
                <div class="carousel-inner">
                    <x-landing.bg key="hero_image_1" default="landing-templates/salon-style/img/header-background-2.jpg" tag="div" class="carousel-item active">
                        <div class="home-content-box">
                            <div class="home-content-box-inner text-center">
                                <div class="home-heading">
                                    <x-landing.text key="hero_title" tag="h3" :default="__('landings.common.hero_default')" :title-fallback="false" />
                                    @if($editing || filled($heroText))
                                        <x-landing.text key="hero_text" tag="p" class="ss-hero-text" />
                                    @endif
                                </div>
                                <div class="home-btn">
                                    <a class="js-scroll-trigger" href="#contact" role="button"><button class="btn btn-lg btn-general btn-white" type="button"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></button></a>
                                </div>
                            </div>
                        </div>
                    </x-landing.bg>
                </div>
            </div>
        </header>

        <section id="services" class="services">
            <div class="container">
                <div class="row mb-5">
                    <div class="col-md-12 text-center mb-5">
                        <div class="heading">
                            <h1><x-landing.text key="lbl_common_services_title" tag="bdi" :default="__('landings.common.services_title')" :title-fallback="false" /></h1>
                            <div class="bord-bot"></div>
                        </div>
                    </div>
                </div>
                <div class="row text-center">
                    @foreach($cards->take(4) as $card)
                        <div class="col-md-3 col-sm-6">
                            <div class="service-cont">
                                <img src="{{ $asset('img/service/service-' . $loop->iteration . '.jpg') }}" alt="" class="img-fluid">
                                <div class="service-desc">
                                    {{ $card['name'] }}
                                    <p>
                                        @if(!empty($card['price'])){{ $price($card['price']) }} ₽@endif
                                        @if(!empty($card['price']) && !empty($card['duration'])) · @endif
                                        @if(!empty($card['duration'])){{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}@endif
                                    </p>
                                    <a href="#contact" class="js-scroll-trigger ss-book" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="about" class="about">
            <div class="container">
                <div class="row mb-5">
                    <div class="col-md-12 text-center">
                        <div class="heading">
                            <h1><x-landing.text key="lbl_common_about_title" tag="bdi" :default="__('landings.common.about_title')" :title-fallback="false" /></h1>
                            <div class="bord-bot"></div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-5 my-auto">
                        <div class="body-cont mb-5">
                            @if($editing || $proofItems->isNotEmpty())
                                <ul class="ss-list">
                                    @foreach($proofItems->take(5) as $item)
                                        <x-landing.text key="proof_items_text" :index="$loop->index" tag="li" />
                                    @endforeach
                                </ul>
                            @endif
                            @if(count($hours))
                                <p class="ss-hours">
                                    <strong><x-landing.text key="lbl_common_hours_kicker" tag="bdi" :default="__('landings.common.hours_kicker')" :title-fallback="false" /></strong>
                                    @foreach($hours as $line)
                                        <br>{{ $line['days'] }}: {{ $line['from'] }}–{{ $line['to'] }}
                                    @endforeach
                                </p>
                            @endif
                            <a href="#contact" class="js-scroll-trigger"><button class="btn btn-general btn-white" type="button">{{ __('landings.common.book') }}</button></a>
                        </div>
                    </div>
                    <div class="col-md-7 m-auto text-center">
                        <div class="body-img-1">
                            <x-landing.image key="extra_image_1" default="landing-templates/salon-style/img/treamer-small.png" alt="" class="img-fluid" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="portfolio" class="portfolio pb-0 pt-5">
            <div class="container-fluid">
                <div class="row mb-5">
                    <div class="col-md-12 text-center mb-3">
                        <div class="heading">
                            <h1><x-landing.text key="lbl_pretty_work_title" tag="bdi" :default="__('landings.pretty.work_title')" :title-fallback="false" /></h1>
                            <div class="bord-bot"></div>
                            @if($editing)
                                <p>{{ __('landings.pretty.work_hint') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    @foreach([1, 2, 3] as $i)
                        <div class="col-md-4 col-sm-6 p-0">
                            <div class="port-cont">
                                <x-landing.image :key="'work_image_' . $i" :default="'landing-templates/salon-style/img/portfolio/portfolio-' . $i . '.jpg'" class="img-fluid" alt="" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="contact" class="contact pb-0">
            <div class="container">
                <div class="row mb-5">
                    <div class="col-md-12 text-center">
                        <div class="heading">
                            <h1><x-landing.text key="lbl_common_booking_title" tag="bdi" :default="__('landings.common.booking_title')" :title-fallback="false" /></h1>
                            <div class="bord-bot"></div>
                            @if($editing || filled($settings['booking_hint'] ?? null))
                                <x-landing.text key="booking_hint" tag="p" class="desc" />
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        <form id="request-form" novalidate>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group"><input class="form-control" type="text" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120" autocomplete="name"></div>
                                    <div class="form-group"><input class="form-control" type="tel" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask></div>
                                    <div class="form-group">
                                        <select class="form-control ss-select" name="service_id" id="request-service">
                                            <option value="">{{ __('landings.salone.field_service_any') }}</option>
                                            @foreach($services as $service)
                                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group"><textarea class="form-control" name="message" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000"></textarea></div>
                                </div>
                                <div class="col-12"><div id="request-picker" class="mb-3"></div></div>
                                <div class="col-lg-12 text-center">
                                    <button id="request-submit" class="btn btn-general btn-greenish btn-xl text-uppercase" type="submit" style="color: white; border-color: white;">{{ __('landings.salone.submit') }}</button>
                                    <div id="request-message" class="mt-3" role="status" aria-live="polite"></div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <footer>
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-5">
                            <!-- Credit link to Boostraptheme must stay: the template is licensed under CC BY 4.0. -->
                            <span class="copyright">&copy;{{ date('Y') }} <x-landing.text key="title" tag="bdi" /> | {{ __('landings.pretty.credit') }} <a href="https://boostraptheme.com" target="_blank" rel="noopener">Boostraptheme</a></span>
                        </div>
                        <div class="col-md-3">
                            @if($telegram || $whatsapp)
                                <ul class="list-inline social-buttons">
                                    @if($telegram)<li class="list-inline-item"><a href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><i class="bi bi-telegram"></i></a></li>@endif
                                    @if($whatsapp)<li class="list-inline-item"><a href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a></li>@endif
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </footer>
        </section>

        <script src="{{ $asset('js/jquery.min.js') }}"></script>
        <script src="{{ $asset('js/popper.min.js') }}"></script>
        <script src="{{ $asset('js/bootstrap.min.js') }}"></script>
        <script src="{{ $asset('js/app.js') }}"></script>

        @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker'], 'accent' => '#f59f7e', 'accentText' => '#fff'])
        @include('landings.partials.editor-assets')
    </body>
</html>
