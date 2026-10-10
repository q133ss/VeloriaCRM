{{--
    Одна услуга: Hairnic, Single Product Website Template by HTML Codex
    (https://htmlcodex.com/single-product-website-template/), CC BY 4.0: the author's credit link in the
    footer must stay. Assets and the license live in public/landing-templates/one-service.

    Imported with landing:import-template, then finished by hand: the "product" is the master's first
    service (name, price, duration from her catalog), the three feature boxes are her proof items,
    the deal block carries her real discount, promo code and deadline, the other services are a short
    list, the contact form is the booking widget. No invented reviews, blog or newsletter. The product
    photos of the original are shown only after she uploads her own (or in the editor).
--}}
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/one-service/' . $path);
    $price = fn ($value) => number_format((float) $value, 0, ',', ' ');

    $main = $cards->first();
    $others = $cards->slice(1)->take(6);
    $uploaded = fn (string $key) => $editing || ! empty($isDemo) || ! empty($settings['images'][$key] ?? null);

    $deadline = null;
    if ($hasOffer && ! empty($settings['ends_at'])) {
        try {
            $deadline = \Illuminate\Support\Carbon::parse($settings['ends_at'])->endOfDay();
        } catch (\Throwable $e) {
            $deadline = null;
        }
    }
    $running = $deadline && $deadline->isFuture();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $landing->title }}</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $heroText), 150) }}" name="description">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&amp;subset=cyrillic&amp;display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ $asset('lib/animate/animate.min.css') }}" rel="stylesheet">
    <link href="{{ $asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ $asset('css/style.css') }}" rel="stylesheet">
    <style>
        body, h1, h2, h3, h4, h5, h6 { font-family: 'Montserrat', 'Open Sans', sans-serif; }
        .os-others li { display: flex; justify-content: space-between; gap: 12px; padding: 12px 0; border-bottom: 1px solid rgba(0, 0, 0, .1); }
    </style>
</head>
<body>
    @if($isPreview && empty($isEdit))
        <div style="position: fixed; top: 0; left: 0; right: 0; z-index: 2000; background: #111; color: #fff; text-align: center; font-size: 12px; padding: 2px 0;">{{ __('landings.public.preview_badge') }}</div>
    @endif

    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="container-fluid sticky-top">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-light p-0">
                <a href="#home" class="navbar-brand"><h2 class="text-white fs-4 my-2"><x-landing.text key="title" tag="bdi" /></h2></a>
                <button type="button" class="navbar-toggler ms-auto me-0" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav ms-auto">
                        <a href="#about" class="nav-item nav-link"><x-landing.text key="lbl_common_about_kicker" tag="bdi" :default="__('landings.common.about_kicker')" :title-fallback="false" /></a>
                        @if($others->isNotEmpty())<a href="#others" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></a>@endif
                        <a href="#booking" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_booking" tag="bdi" :default="__('landings.common.nav_booking')" :title-fallback="false" /></a>
                    </div>
                    <a href="#booking" class="btn btn-dark py-2 px-4 d-none d-lg-inline-block"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
                </div>
            </nav>
        </div>
    </div>

    <div class="container-fluid bg-primary hero-header mb-5" id="home">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="{{ $uploaded('hero_image_1') ? 'col-lg-6 text-center text-lg-start' : 'col-lg-8 mx-auto text-center' }}">
                    @if($main)<h3 class="fw-light text-white animated slideInRight">{{ $main['name'] }}</h3>@endif
                    <x-landing.text key="hero_title" tag="h1" class="display-5 text-white animated slideInRight" :default="__('landings.common.hero_default')" :title-fallback="false" />
                    @if($editing || filled($heroText))
                        <x-landing.text key="hero_text" tag="p" class="text-white mb-4 animated slideInRight" />
                    @endif
                    <a href="#booking" class="btn btn-dark py-2 px-4 me-3 animated slideInRight"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
                    @if($phoneHref)<a href="{{ $phoneHref }}" class="btn btn-outline-light py-2 px-4 animated slideInRight">{{ $phone }}</a>@endif
                </div>
                @if($uploaded('hero_image_1'))
                    <div class="col-lg-6">
                        <x-landing.image key="hero_image_1" default="landing-templates/one-service/img/shampoo.png" class="img-fluid" alt="" />
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($editing || $proofItems->isNotEmpty())
        <div class="container-fluid py-5">
            <div class="container">
                <div class="row g-4 justify-content-center">
                    @foreach($proofItems->take(3) as $item)
                        <div class="col-lg-4 wow fadeIn" data-wow-delay="{{ 0.1 + $loop->index * 0.2 }}s">
                            <div class="feature-item position-relative bg-primary text-center p-3 h-100">
                                <div class="border py-5 px-3 h-100">
                                    <i class="fa fa-check fa-3x text-dark mb-4"></i>
                                    <x-landing.text key="proof_items_text" :index="$loop->index" tag="h5" class="text-white mb-0" />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="container-fluid py-5" id="about">
        <div class="container">
            <div class="row g-5 align-items-center">
                @if($uploaded('about_image'))
                    <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s">
                        <x-landing.image key="about_image" default="landing-templates/one-service/img/shampoo-1.png" class="img-fluid" alt="" />
                    </div>
                @endif
                <div class="{{ $uploaded('about_image') ? 'col-lg-6' : 'col-lg-8 mx-auto text-center' }} wow fadeIn" data-wow-delay="0.3s">
                    <h1 class="text-primary mb-4"><x-landing.text key="lbl_common_master_title" tag="bdi" :default="__('landings.common.master_title')" :title-fallback="false" /></h1>
                    @if($editing || filled($settings['master_bio'] ?? null))
                        <x-landing.text key="master_bio" tag="p" class="mb-4" />
                    @endif
                    @if(count($hours))
                        <p class="mb-1"><strong><x-landing.text key="lbl_common_hours_kicker" tag="bdi" :default="__('landings.common.hours_kicker')" :title-fallback="false" /></strong></p>
                        @foreach($hours as $line)<div>{{ $line['days'] }}: {{ $line['from'] }}–{{ $line['to'] }}</div>@endforeach
                    @endif
                    <a class="btn btn-primary py-2 px-4 mt-4" href="#booking"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                </div>
            </div>
        </div>
    </div>

    @if($main)
        <div class="container-fluid deal bg-primary my-5 py-5">
            <div class="container py-5">
                <div class="row g-5 align-items-center justify-content-center">
                    <div class="col-lg-7 wow fadeIn" data-wow-delay="0.1s">
                        <div class="bg-white text-center p-4">
                            <div class="border p-4">
                                @if($hasOffer)
                                    <p class="mb-2">{{ __('landings.pretty.offer_kicker', ['percent' => $offerPercent]) }}@if($promoCode) · {{ $promoCode }}@endif</p>
                                @endif
                                <h2 class="fw-bold mb-4">{{ $main['name'] }}</h2>
                                @if(!empty($main['price']))
                                    <h1 class="display-4 text-primary mb-4">{{ $price($main['price']) }} ₽</h1>
                                @endif
                                @if(!empty($main['duration']))
                                    <h5 class="mb-4">{{ __('landings.salone.minutes', ['n' => (int) $main['duration']]) }}</h5>
                                @endif
                                @if($running)
                                    <div class="row g-0 cdt mb-4" id="cdt" data-end="{{ $deadline->toIso8601String() }}">
                                        <div class="col-3"><h1 class="display-6" id="cdt-days"></h1><small>{{ __('landings.promo.days') }}</small></div>
                                        <div class="col-3"><h1 class="display-6" id="cdt-hours"></h1><small>{{ __('landings.promo.hours') }}</small></div>
                                        <div class="col-3"><h1 class="display-6" id="cdt-minutes"></h1><small>{{ __('landings.promo.minutes') }}</small></div>
                                        <div class="col-3"><h1 class="display-6" id="cdt-seconds"></h1><small>{{ __('landings.promo.seconds') }}</small></div>
                                    </div>
                                @elseif($hasOffer && $endsAt)
                                    <p class="mb-4">{{ __('landings.pretty.offer_until', ['date' => $endsAt]) }}</p>
                                @endif
                                <a class="btn btn-primary py-2 px-4" href="#booking" data-pick-service="{{ $main['id'] }}"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($others->isNotEmpty())
        <div class="container-fluid py-5" id="others">
            <div class="container">
                <div class="mx-auto text-center mb-4" style="max-width: 600px;">
                    <h1 class="text-primary"><x-landing.text key="lbl_common_services_title" tag="bdi" :default="__('landings.common.services_title')" :title-fallback="false" /></h1>
                </div>
                <ul class="list-unstyled os-others mx-auto" style="max-width: 640px;">
                    @foreach($others as $card)
                        <li>
                            <a href="#booking" class="text-dark" data-pick-service="{{ $card['id'] }}">{{ $card['name'] }}</a>
                            @if(!empty($card['price']))<strong>{{ $price($card['price']) }} ₽</strong>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="container-fluid bg-primary py-5 my-5" id="booking">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="bg-white p-4 p-md-5">
                        <h2 class="text-center mb-2"><x-landing.text key="lbl_common_booking_title" tag="bdi" :default="__('landings.common.booking_title')" :title-fallback="false" /></h2>
                        @if($editing || filled($settings['booking_hint'] ?? null))
                            <x-landing.text key="booking_hint" tag="p" class="text-center text-muted" />
                        @endif
                        <form id="request-form" novalidate>
                            <div class="mb-3"><input type="text" class="form-control py-3" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120" autocomplete="name"></div>
                            <div class="mb-3"><input type="tel" class="form-control py-3" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask></div>
                            <div class="mb-3">
                                <select class="form-select py-3" name="service_id" id="request-service">
                                    <option value="">{{ __('landings.salone.field_service_any') }}</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3"><textarea class="form-control" name="message" rows="2" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000"></textarea></div>
                            <div id="request-picker" class="mb-3"></div>
                            <button id="request-submit" class="btn btn-primary w-100 py-3" type="submit">{{ __('landings.salone.submit') }}</button>
                            <div id="request-message" class="mt-3" role="status" aria-live="polite"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid footer py-5">
        <div class="container">
            <div class="row g-4 text-center text-md-start">
                <div class="col-md-6">
                    <h5 class="mb-3"><x-landing.text key="title" tag="bdi" /></h5>
                    @if($address !== '' || $editing)<p class="mb-1"><i class="fa fa-map-marker-alt me-2"></i><x-landing.text key="address" tag="bdi" /></p>@endif
                    @if($phoneHref || $editing)<p class="mb-1"><i class="fa fa-phone-alt me-2"></i><a href="{{ $phoneHref ?: '#' }}"><x-landing.text key="phone" tag="bdi" /></a></p>@endif
                </div>
                <div class="col-md-6 text-md-end">
                    @if($telegram)<a class="btn btn-outline-light btn-square me-2" href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>@endif
                    @if($whatsapp)<a class="btn btn-outline-light btn-square" href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>@endif
                </div>
            </div>
            <div class="copyright mt-4 text-center">
                &copy; {{ date('Y') }} <x-landing.text key="title" tag="bdi" />
                <!--/*** The author’s attribution link must remain intact in the template. ***/-->
                · {{ __('landings.pretty.credit') }} <a class="border-bottom" href="https://htmlcodex.com" target="_blank" rel="noopener">HTML Codex</a>
            </div>
        </div>
    </div>

    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ $asset('lib/wow/wow.min.js') }}"></script>
    <script src="{{ $asset('lib/easing/easing.min.js') }}"></script>
    <script src="{{ $asset('js/main.js') }}"></script>

    @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker'], 'accent' => '#90BC79', 'accentText' => '#fff'])
    @include('landings.partials.editor-assets')
</body>
</html>
