{{--
    Фотостудия-портфолио: Photozone, Photo Studio Website Template by HTML Codex
    (https://htmlcodex.com/photo-studio-website-template/), CC BY 4.0: the author's credit link in the
    footer must stay. Assets and the license live in public/landing-templates/photo-studio.

    Imported with landing:import-template, then finished by hand: this is the "show the work" layout.
    The photo collage and the gallery are the master's own photos (slots hero_image_1..3, work_image_1..6);
    the stock photos of the original appear only in the editor and in the template preview, so visitors
    never see strangers' work. Services and prices come from her catalog, the contact form is the booking
    widget. No invented team, counters or testimonials; video, counter and carousel scripts are not loaded.
--}}
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/photo-studio/' . $path);
    $price = fn ($value) => number_format((float) $value, 0, ',', ' ');

    $has = fn (string $key) => $editing || ! empty($isDemo) || ! empty($settings['images'][$key] ?? null);
    $heroPhotos = collect(['hero_image_1', 'hero_image_2', 'hero_image_3'])->filter($has)->isNotEmpty();
    $works = collect([1, 2, 3, 4, 5, 6])->filter(fn ($i) => $has('work_image_' . $i));
    $workDefaults = [1 => 'project-5', 2 => 'project-1', 3 => 'project-2', 4 => 'project-3', 5 => 'project-4', 6 => 'project-6'];
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
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600&amp;family=Playfair+Display:wght@500;600;700&amp;subset=cyrillic&amp;display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ $asset('lib/animate/animate.min.css') }}" rel="stylesheet">
    <link href="{{ $asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ $asset('css/style.css') }}" rel="stylesheet">
    <style>
        .ps-gallery img { width: 100%; height: 100%; object-fit: cover; aspect-ratio: 4 / 5; }
        .ps-price { font-weight: 700; color: var(--primary); }
    </style>
</head>
<body>
    @if($isPreview && empty($isEdit))
        <div style="position: fixed; top: 0; left: 0; right: 0; z-index: 2000; background: #111; color: #fff; text-align: center; font-size: 12px; padding: 2px 0;">{{ __('landings.public.preview_badge') }}</div>
    @endif

    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg bg-white navbar-light fixed-top shadow py-lg-0 px-4 px-lg-5 wow fadeIn" data-wow-delay="0.1s">
        <a href="#home" class="navbar-brand d-block d-lg-none"><h1 class="text-primary fs-4 m-0"><x-landing.text key="title" tag="bdi" /></h1></a>
        <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-between py-4 py-lg-0" id="navbarCollapse">
            <div class="navbar-nav ms-auto py-0">
                <a href="#home" class="nav-item nav-link active"><x-landing.text key="lbl_common_nav_master" tag="bdi" :default="__('landings.common.nav_master')" :title-fallback="false" /></a>
                @if($cards->isNotEmpty())<a href="#services" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></a>@endif
            </div>
            <a href="#home" class="navbar-brand bg-primary py-2 px-4 mx-3 d-none d-lg-block"><h1 class="text-white fs-4 m-0"><x-landing.text key="title" tag="bdi" /></h1></a>
            <div class="navbar-nav me-auto py-0">
                @if($works->isNotEmpty())<a href="#works" class="nav-item nav-link"><x-landing.text key="lbl_pretty_nav_work" tag="bdi" :default="__('landings.pretty.nav_work')" :title-fallback="false" /></a>@endif
                <a href="#contact" class="nav-item nav-link"><x-landing.text key="lbl_common_nav_booking" tag="bdi" :default="__('landings.common.nav_booking')" :title-fallback="false" /></a>
            </div>
        </div>
    </nav>

    <div class="container-fluid hero-header bg-light py-5 mb-5" id="home">
        <div class="container py-5">
            <div class="row g-5 align-items-center">
                <div class="{{ $heroPhotos ? 'col-lg-6' : 'col-lg-8 mx-auto text-center' }}">
                    <p class="text-primary text-uppercase mb-2 animated slideInDown"><x-landing.text key="master_role" tag="bdi" :default="__('landings.common.master_role')" /></p>
                    <x-landing.text key="hero_title" tag="h1" class="display-4 mb-3 animated slideInDown" :default="__('landings.common.hero_default')" :title-fallback="false" />
                    @if($editing || filled($heroText))
                        <x-landing.text key="hero_text" tag="p" class="animated slideInDown" />
                    @endif
                    <div class="pt-4 animated slideInDown">
                        <a href="#contact" class="btn btn-primary py-3 px-4 me-4"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
                        @if($phoneHref)<a href="{{ $phoneHref }}" class="fw-bold text-dark">{{ $phone }}</a>@endif
                    </div>
                </div>
                @if($heroPhotos)
                    <div class="col-lg-6 animated fadeIn">
                        <div class="row g-3">
                            <div class="col-6">
                                <x-landing.image key="hero_image_1" default="landing-templates/photo-studio/img/hero-1.jpg" class="img-fluid bg-white p-3 w-100" alt="" />
                            </div>
                            <div class="col-6">
                                <x-landing.image key="hero_image_2" default="landing-templates/photo-studio/img/hero-2.jpg" class="img-fluid bg-white p-3 w-100 mb-3" alt="" />
                                <x-landing.image key="hero_image_3" default="landing-templates/photo-studio/img/hero-3.jpg" class="img-fluid bg-white p-3 w-100" alt="" />
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($cards->isNotEmpty())
        <div class="container-xxl bg-light py-5 my-5" id="services">
            <div class="container py-5">
                <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">
                    <p class="text-primary text-uppercase mb-2"><x-landing.text key="lbl_common_services_kicker" tag="bdi" :default="__('landings.common.services_kicker')" :title-fallback="false" /></p>
                    <h1 class="display-6 mb-0"><x-landing.text key="lbl_common_services_title" tag="bdi" :default="__('landings.common.services_title')" :title-fallback="false" /></h1>
                </div>
                <div class="row g-3">
                    @foreach($cards->take(8) as $card)
                        <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="{{ 0.1 + ($loop->index % 4) * 0.2 }}s">
                            <div class="d-flex flex-column bg-white p-4 h-100 text-center">
                                <h4 class="mb-2">{{ $card['name'] }}</h4>
                                @if(!empty($card['duration']))<small class="text-muted mb-2">{{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}</small>@endif
                                @if(!empty($card['price']))<div class="ps-price fs-4 mb-3">{{ $price($card['price']) }} ₽</div>@endif
                                <a href="#contact" class="btn btn-outline-primary mt-auto" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if($works->isNotEmpty())
        <div class="container-xxl py-5" id="works">
            <div class="container">
                <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">
                    <h1 class="display-6 mb-0"><x-landing.text key="lbl_pretty_work_title" tag="bdi" :default="__('landings.pretty.work_title')" :title-fallback="false" /></h1>
                    @if($editing)<p class="mt-2 mb-0">{{ __('landings.pretty.work_hint') }}</p>@endif
                </div>
                <div class="row g-3 ps-gallery">
                    @foreach($works as $i)
                        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="{{ 0.1 + ($loop->index % 3) * 0.2 }}s">
                            <div class="bg-white p-3 h-100">
                                <x-landing.image :key="'work_image_' . $i" :default="'landing-templates/photo-studio/img/' . $workDefaults[$i] . '.jpg'" class="img-fluid" alt="" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="container-xxl bg-light py-5 mt-5" id="contact">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-5">
                    <p class="text-primary text-uppercase mb-2"><x-landing.text key="lbl_common_booking_kicker" tag="bdi" :default="__('landings.common.booking_kicker')" :title-fallback="false" /></p>
                    <h1 class="display-6 mb-4"><x-landing.text key="lbl_common_booking_title" tag="bdi" :default="__('landings.common.booking_title')" :title-fallback="false" /></h1>
                    @if($editing || filled($settings['booking_hint'] ?? null))
                        <x-landing.text key="booking_hint" tag="p" class="mb-4" />
                    @endif
                    @if($address !== '' || $editing)<p class="mb-2"><i class="fa fa-map-marker-alt text-primary me-3"></i><x-landing.text key="address" tag="bdi" /></p>@endif
                    @if($phoneHref || $editing)<p class="mb-2"><i class="fa fa-phone-alt text-primary me-3"></i><a href="{{ $phoneHref ?: '#' }}" class="text-dark"><x-landing.text key="phone" tag="bdi" /></a></p>@endif
                    @foreach($hours as $line)
                        <div class="text-muted">{{ $line['days'] }}: {{ $line['from'] }}–{{ $line['to'] }}</div>
                    @endforeach
                    @if($telegram || $whatsapp)
                        <div class="pt-3">
                            @if($telegram)<a class="btn btn-square btn-primary me-2" href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>@endif
                            @if($whatsapp)<a class="btn btn-square btn-primary" href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>@endif
                        </div>
                    @endif
                </div>
                <div class="col-lg-7">
                    <form id="request-form" class="bg-white p-4 p-md-5" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6"><input type="text" class="form-control py-3" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120" autocomplete="name"></div>
                            <div class="col-md-6"><input type="tel" class="form-control py-3" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask></div>
                            <div class="col-12">
                                <select class="form-select py-3" name="service_id" id="request-service">
                                    <option value="">{{ __('landings.salone.field_service_any') }}</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12"><textarea class="form-control" name="message" rows="3" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000"></textarea></div>
                            <div class="col-12"><div id="request-picker"></div></div>
                            <div class="col-12">
                                <button id="request-submit" class="btn btn-primary py-3 px-5" type="submit">{{ __('landings.salone.submit') }}</button>
                                <div id="request-message" class="mt-3" role="status" aria-live="polite"></div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid bg-dark text-white border-top border-secondary px-0">
        <div class="d-flex flex-column flex-md-row justify-content-between">
            <div class="py-4 px-5 text-center text-md-start">
                <p class="mb-0">&copy; {{ date('Y') }} <x-landing.text key="title" tag="bdi" /></p>
            </div>
            <div class="py-4 px-5 bg-secondary footer-shape position-relative text-center text-md-end">
                <!--/*** This template is free as long as you keep the footer author’s credit link/attribution link/backlink. ***/-->
                <p class="mb-0">{{ __('landings.pretty.credit') }} <a class="text-primary fw-bold" href="https://htmlcodex.com" target="_blank" rel="noopener">HTML Codex</a></p>
            </div>
        </div>
    </div>

    <a href="#" class="btn btn-lg btn-primary btn-lg-square rounded-circle back-to-top"><i class="bi bi-arrow-up"></i></a>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ $asset('lib/wow/wow.min.js') }}"></script>
    <script src="{{ $asset('lib/easing/easing.min.js') }}"></script>
    <script src="{{ $asset('js/main.js') }}"></script>

    @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker'], 'accent' => '#EAA636', 'accentText' => '#fff'])
    @include('landings.partials.editor-assets')
</body>
</html>
