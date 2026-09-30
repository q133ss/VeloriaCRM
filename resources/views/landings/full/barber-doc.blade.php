{{--
    Barber: free HTML5 barber shop template by Colorlib (https://colorlib.com), distributed by ThemeWagon,
    CC BY 3.0: the link back to Colorlib in the footer must stay. Assets and the license live in
    public/landing-templates/barber-doc.

    Imported with landing:import-template, then finished by hand: the master's own data instead of
    placeholder text; no invented team, reviews or blog; services and prices come from her catalog;
    the opening hours are her real booking schedule; the booking block is the shared widget (the
    original only had a pop-up form). The carousels and plugins of the original are not loaded.
--}}
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/barber-doc/' . $path);
    $price = fn ($value) => number_format((float) $value, 0, ',', ' ');
    $icons = ['flaticon-shave', 'flaticon-barber', 'flaticon-shave'];
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{ $landing->title }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $heroText), 150) }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ $asset('img/favicon.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ $asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ $asset('css/flaticon.css') }}">
    <link rel="stylesheet" href="{{ $asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ $asset('css/veloria.css') }}">
</head>

<body>
    @if($isPreview && empty($isEdit))
        <div style="position: fixed; top: 0; left: 0; right: 0; z-index: 2000; background: #111; color: #fff; text-align: center; font-size: 12px; padding: 2px 0;">{{ __('landings.public.preview_badge') }}</div>
    @endif

    <header>
        <div class="header-area">
            <div id="sticky-header" class="main-header-area">
                <div class="container">
                    <div class="vb-bar">
                        <a href="#top" class="vb-brand"><x-landing.text key="title" tag="bdi" /></a>
                        <button type="button" class="vb-toggle" aria-label="{{ __('landings.pretty.menu') }}" aria-expanded="false"><span></span><span></span><span></span></button>
                        <div class="vb-menu">
                            <div class="main-menu">
                                <nav>
                                    <ul id="navigation">
                                        <li><a href="#about">{{ __('landings.common.about_title') }}</a></li>
                                        <li><a href="#services">{{ __('landings.common.nav_services') }}</a></li>
                                        @if($priced->isNotEmpty())
                                            <li><a href="#prices">{{ __('landings.common.nav_prices') }}</a></li>
                                        @endif
                                        <li><a href="#contacts">{{ __('landings.common.nav_contacts') }}</a></li>
                                    </ul>
                                </nav>
                            </div>
                            <div class="book_room">
                                <div class="book_btn"><a href="#booking">{{ __('landings.common.book') }}</a></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <a id="top"></a>
    <div class="slider_area">
        <x-landing.bg key="hero_image_1" default="landing-templates/barber-doc/img/banner/banner.jpg" tag="div" class="single_slider d-flex align-items-center justify-content-center overlay">
            <div class="container">
                <div class="row">
                    <div class="col-lg-7 col-md-9">
                        <div class="slider_text">
                            <x-landing.text key="hero_title" tag="h3" :default="__('landings.common.hero_default')" :title-fallback="false" />
                            @if($editing || filled($heroText))
                                <x-landing.text key="hero_text" tag="p" />
                            @endif
                            <a href="#booking" class="boxed-btn3"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
                        </div>
                    </div>
                </div>
            </div>
        </x-landing.bg>
    </div>

    <div class="about_area" id="about">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-xl-6 col-lg-6 col-md-6">
                    <div class="about_thumbs">
                        <div class="large_img_1"><x-landing.image key="work_image_1" default="landing-templates/barber-doc/img/about/about_lft.png" alt="" /></div>
                        <div class="small_img_1"><x-landing.image key="work_image_2" default="landing-templates/barber-doc/img/about/about_right.png" alt="" /></div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6 col-md-6">
                    <div class="about_info">
                        <div class="section_title mb-20px">
                            <h3>{{ __('landings.common.about_title') }}</h3>
                            @if($editing || $proofItems->isNotEmpty())
                                <ul class="vb-list">
                                    @foreach($proofItems->take(5) as $item)
                                        <x-landing.text key="proof_items_text" :index="$loop->index" tag="li" />
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        @if(count($hours))
                            <p class="opening_hour">
                                {{ __('landings.common.hours_kicker') }}
                                @foreach($hours as $line)
                                    <span class="d-block ml-0" style="font-size:17px">{{ $line["days"] }}: {{ $line["from"] }}–{{ $line["to"] }}</span>
                                @endforeach
                            </p>
                        @endif
                        <a href="#booking" class="boxed-btn3">{{ __('landings.common.book') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="service_area" id="services">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-10">
                    <div class="section_title text-center mb-55">
                        <h3>{{ __('landings.common.services_title') }}</h3>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                @foreach($cards->take(3) as $card)
                    <div class="col-lg-4 col-md-6">
                        <div class="single_service">
                            <div class="service_thumb"><img src="{{ $asset('img/service/' . $loop->iteration . '.png') }}" alt=""></div>
                            <div class="service_content text-center">
                                <div class="icon"><i class="{{ $icons[$loop->index] }}"></i></div>
                                <h3>{{ $card['name'] }}</h3>
                                @if(!empty($card['price']) || !empty($card['duration']))
                                    <p>
                                        @if(!empty($card['price'])){{ __('landings.salone.from_price', ['price' => $price($card['price'])]) }}@endif
                                        @if(!empty($card['price']) && !empty($card['duration'])) · @endif
                                        @if(!empty($card['duration'])){{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}@endif
                                    </p>
                                @endif
                                <a href="#booking" class="vb-link" data-pick-service="{{ $card['id'] }}">{{ __('landings.common.book') }}</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if($priced->isNotEmpty())
        <div class="prising_area" id="prices">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-6 col-md-10">
                        <div class="section_title text-center mb-55">
                            <h3>{{ __('landings.common.prices_title') }}</h3>
                        </div>
                    </div>
                </div>
                <div class="row">
                    @foreach($priced as $card)
                        <div class="col-lg-6">
                            <div class="single_prising vb-price">
                                <div class="single_service">
                                    <div class="service_inner"><div class="thumb"><img src="{{ $asset('img/prising/1.png') }}" alt=""></div></div>
                                    <div class="hair_style_info">
                                        <div class="prise d-flex justify-content-between">
                                            <span>{{ $card['name'] }}</span>
                                            <span>{{ $price($card['price']) }} ₽</span>
                                        </div>
                                        @if(!empty($card['duration']))
                                            <p>{{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }} · <a href="#booking" class="vb-link" data-pick-service="{{ $card['id'] }}">{{ __('landings.common.book') }}</a></p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="vb-booking" id="booking">
        <div class="container">
            <div class="section_title text-center mb-55">
                <h3>{{ __('landings.common.booking_title') }}</h3>
                @if($editing || filled($settings['booking_hint'] ?? null))
                    <x-landing.text key="booking_hint" tag="p" />
                @endif
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <form id="request-form" novalidate>
                        <div class="row">
                            <div class="col-sm-6 mb-3"><input type="text" class="vb-field" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120" autocomplete="name"></div>
                            <div class="col-sm-6 mb-3"><input type="tel" class="vb-field" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask></div>
                            <div class="col-12 mb-3">
                                <select class="vb-field" name="service_id" id="request-service">
                                    <option value="">{{ __('landings.salone.field_service_any') }}</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12"><div id="request-picker" class="mb-3"></div></div>
                            <div class="col-12 mb-3"><textarea class="vb-field" name="message" rows="3" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000"></textarea></div>
                        </div>
                        <button type="submit" id="request-submit" class="boxed-btn3">{{ __('landings.salone.submit') }}</button>
                        <div id="request-message" class="mt-3" role="status" aria-live="polite"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer" id="contacts">
        <div class="footer_top">
            <div class="container">
                <div class="row">
                    <div class="col-xl-5 col-md-6 col-lg-5">
                        <div class="footer_widget">
                            <div class="footer_logo vb-brand"><x-landing.text key="title" tag="bdi" /></div>
                            @if($address !== '' || $editing)
                                <p class="address_text"><x-landing.text key="address" tag="bdi" /></p>
                            @endif
                            @if($phoneHref || $editing)
                                <p class="address_text"><a href="{{ $phoneHref ?: '#' }}"><x-landing.text key="phone" tag="bdi" /></a></p>
                            @endif
                            @if($telegram || $whatsapp)
                                <div class="socail_links">
                                    <ul>
                                        @if($telegram)<li><a href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><i class="bi bi-telegram"></i></a></li>@endif
                                        @if($whatsapp)<li><a href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a></li>@endif
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 col-lg-3">
                        <div class="footer_widget">
                            <h3 class="footer_title">{{ __('landings.common.quick_links') }}</h3>
                            <ul class="links">
                                <li><a href="#about">{{ __('landings.common.about_title') }}</a></li>
                                <li><a href="#services">{{ __('landings.common.nav_services') }}</a></li>
                                <li><a href="#booking">{{ __('landings.common.nav_booking') }}</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6 col-lg-4">
                        <div class="footer_widget">
                            <h3 class="footer_title">{{ __('landings.common.nav_services') }}</h3>
                            <ul class="links">
                                @foreach($cards->take(4) as $card)
                                    <li><a href="#booking" data-pick-service="{{ $card['id'] }}">{{ $card['name'] }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="copy-right_text">
            <div class="container">
                <div class="row">
                    <div class="col-xl-12">
                        <p class="copy_right text-center">
                            <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
                            &copy;{{ date('Y') }} <x-landing.text key="title" tag="bdi" /> | {{ __('landings.pretty.credit') }} <i class="bi bi-heart-fill" aria-hidden="true"></i> <a href="https://colorlib.com" target="_blank" rel="noopener">Colorlib</a>
                            <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            var bar = document.querySelector('.vb-toggle');
            if (!bar) return;
            var header = document.querySelector('.main-header-area');
            bar.addEventListener('click', function () {
                var open = header.classList.toggle('vb-open');
                bar.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            document.querySelectorAll('.vb-menu a').forEach(function (a) {
                a.addEventListener('click', function () { header.classList.remove('vb-open'); });
            });
            window.addEventListener('scroll', function () {
                header.classList.toggle('sticky', window.scrollY > 80);
            });
        })();
    </script>

    @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker'], 'accent' => '#eb592d', 'accentText' => '#fff'])
    @include('landings.partials.editor-assets')
</body>
</html>
