{{--
    Визитка мастера: ProMan, Personal Portfolio Template by HTML Codex (https://htmlcodex.com/personal-portfolio-html-template/),
    CC BY 4.0: the author's credit link in the footer must stay. Assets and the license live in
    public/landing-templates/master-card.

    Imported with landing:import-template, then finished by hand: it is the master's own page, so the
    skills bars, experience tabs, team, testimonials, counters, video and map of the original are gone.
    Services and prices come from her catalog, the hours from her schedule, the contact form is the
    booking widget. The typed-text, counter, isotope and carousel plugins are not loaded.
--}}
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/master-card/' . $path);
    $price = fn ($value) => number_format((float) $value, 0, ',', ' ');
    $hasWork = $editing || ! empty($isDemo) || collect([1, 2, 3])->contains(fn ($i) => ! empty($settings['images']['work_image_' . $i]));
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
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700;800&amp;subset=cyrillic&amp;display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="{{ $asset('lib/animate/animate.min.css') }}" rel="stylesheet">
    <link href="{{ $asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ $asset('css/style.css') }}" rel="stylesheet">
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
        <a href="#home" class="navbar-brand d-block d-lg-none">
            <h1 class="text-primary fw-bold m-0 fs-4"><x-landing.text key="title" tag="bdi" /></h1>
        </a>
        <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-between py-4 py-lg-0" id="navbarCollapse">
            <div class="navbar-nav ms-auto py-0">
                <a href="#home" class="nav-item nav-link active">{{ __('landings.common.nav_master') }}</a>
                <a href="#about" class="nav-item nav-link">{{ __('landings.common.about_kicker') }}</a>
            </div>
            <a href="#home" class="navbar-brand bg-secondary py-3 px-4 mx-3 d-none d-lg-block">
                <h1 class="text-primary fw-bold m-0 fs-4"><x-landing.text key="title" tag="bdi" /></h1>
            </a>
            <div class="navbar-nav me-auto py-0">
                <a href="#service" class="nav-item nav-link">{{ __('landings.common.nav_services') }}</a>
                @if($hasWork)<a href="#project" class="nav-item nav-link">{{ __('landings.pretty.nav_work') }}</a>@endif
                <a href="#contact" class="nav-item nav-link">{{ __('landings.common.nav_booking') }}</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid bg-light my-6 mt-0" id="home">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6 py-6 pb-0 pt-lg-0">
                    <h3 class="text-primary mb-3"><x-landing.text key="master_role" tag="bdi" :default="__('landings.common.master_role')" /></h3>
                    <x-landing.text key="title" tag="h1" class="display-3 mb-3" />
                    <x-landing.text key="hero_title" tag="h2" class="mb-3" :default="__('landings.common.hero_default')" :title-fallback="false" />
                    @if($editing || filled($heroText))
                        <x-landing.text key="hero_text" tag="p" class="mb-0" />
                    @endif
                    <div class="d-flex align-items-center pt-5">
                        <a href="#contact" class="btn btn-primary py-3 px-4 me-5"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
                        @if($phoneHref)
                            <a href="{{ $phoneHref }}" class="fw-bold text-dark">{{ $phone }}</a>
                        @endif
                    </div>
                </div>
                <div class="col-lg-6">
                    <x-landing.image key="master_photo" default="landing-templates/master-card/img/profile.png" class="img-fluid" alt="" />
                </div>
            </div>
        </div>
    </div>

    <div class="container-xxl py-6" id="about">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.1s">
                    <h1 class="display-5 mb-4">{{ __('landings.common.master_title') }}</h1>
                    @if($editing || filled($settings['master_bio'] ?? null))
                        <x-landing.text key="master_bio" tag="p" class="mb-4" />
                    @endif
                    @if($editing || $proofItems->isNotEmpty())
                        @foreach($proofItems->take(5) as $item)
                            <p class="mb-3"><i class="far fa-check-circle text-primary me-3"></i><x-landing.text key="proof_items_text" :index="$loop->index" tag="bdi" /></p>
                        @endforeach
                    @endif
                    <a class="btn btn-primary py-3 px-5 mt-3" href="#contact">{{ __('landings.common.book') }}</a>
                </div>
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.3s">
                    @if(count($hours))
                        <h5 class="mb-3">{{ __('landings.common.hours_kicker') }}</h5>
                        @foreach($hours as $line)
                            <div class="d-flex align-items-center mb-2">
                                <h6 class="border-end pe-3 me-3 mb-0">{{ $line['days'] }}</h6>
                                <span class="text-primary fw-bold">{{ $line['from'] }}–{{ $line['to'] }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($cards->isNotEmpty())
        <div class="container-fluid bg-light my-5 py-6" id="service">
            <div class="container">
                <div class="row g-5 mb-5 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="col-lg-6">
                        <h1 class="display-5 mb-0">{{ __('landings.common.services_title') }}</h1>
                    </div>
                    <div class="col-lg-6 text-lg-end">
                        <a class="btn btn-primary py-3 px-5" href="#contact">{{ __('landings.common.book') }}</a>
                    </div>
                </div>
                <div class="row g-4">
                    @foreach($cards->take(8) as $card)
                        <div class="col-lg-6 wow fadeInUp" data-wow-delay="{{ 0.1 + ($loop->index % 2) * 0.2 }}s">
                            <div class="service-item d-flex flex-column flex-sm-row bg-white rounded h-100 p-4 p-lg-5">
                                <div class="bg-icon flex-shrink-0 mb-3">
                                    <i class="fa fa-spa fa-2x text-dark"></i>
                                </div>
                                <div class="ms-sm-4">
                                    <h4 class="mb-3">{{ $card['name'] }}</h4>
                                    @if(!empty($card['price']))
                                        <h6 class="mb-3"><span class="text-primary">{{ $price($card['price']) }} ₽</span>@if(!empty($card['duration'])) · {{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}@endif</h6>
                                    @endif
                                    <a href="#contact" data-pick-service="{{ $card['id'] }}">{{ __('landings.common.book') }}</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if($hasWork)
    <div class="container-xxl py-6 pt-5" id="project">
        <div class="container">
            <div class="row g-5 mb-5 align-items-center wow fadeInUp" data-wow-delay="0.1s">
                <div class="col-lg-6">
                    <h1 class="display-5 mb-0">{{ __('landings.pretty.work_title') }}</h1>
                    @if($editing)
                        <p class="mt-2 mb-0">{{ __('landings.pretty.work_hint') }}</p>
                    @endif
                </div>
            </div>
            <div class="row g-4 wow fadeInUp" data-wow-delay="0.1s">
                @foreach([1, 2, 3] as $i)
                    <div class="col-lg-4 col-md-6">
                        <div class="portfolio-img rounded overflow-hidden">
                            <x-landing.image :key="'work_image_' . $i" :default="'landing-templates/master-card/img/project-' . $i . '.jpg'" class="img-fluid" alt="" />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <div class="container-xxl pb-5" id="contact">
        <div class="container py-5">
            <div class="row g-5 mb-5 wow fadeInUp" data-wow-delay="0.1s">
                <div class="col-lg-6">
                    <h1 class="display-5 mb-0">{{ __('landings.common.booking_title') }}</h1>
                </div>
            </div>
            <div class="row g-5">
                <div class="col-lg-5 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                    @if($address !== '' || $editing)
                        <p class="mb-2">{{ __('landings.common.contacts_title') }}:</p>
                        <h3 class="fw-bold"><x-landing.text key="address" tag="bdi" /></h3>
                        <hr class="w-100">
                    @endif
                    @if($phoneHref || $editing)
                        <p class="mb-2">{{ __('landings.salone.field_phone') }}</p>
                        <h3 class="fw-bold"><a href="{{ $phoneHref ?: '#' }}"><x-landing.text key="phone" tag="bdi" /></a></h3>
                        <hr class="w-100">
                    @endif
                    @if($telegram || $whatsapp)
                        <div class="d-flex pt-2">
                            @if($telegram)<a class="btn btn-square btn-primary me-2" href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>@endif
                            @if($whatsapp)<a class="btn btn-square btn-primary me-2" href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>@endif
                        </div>
                    @endif
                </div>
                <div class="col-lg-7 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
                    @if($editing || filled($settings['booking_hint'] ?? null))
                        <x-landing.text key="booking_hint" tag="h4" class="mb-4" />
                    @endif
                    <form id="request-form" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="client_name" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120" autocomplete="name">
                                    <label for="client_name">{{ __('landings.salone.field_name') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" id="client_phone" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask>
                                    <label for="client_phone">{{ __('landings.salone.field_phone') }}</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <select class="form-select py-3" name="service_id" id="request-service">
                                    <option value="">{{ __('landings.salone.field_service_any') }}</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" placeholder="{{ __('landings.salone.field_message') }}" id="message" name="message" maxlength="1000" style="height: 100px"></textarea>
                                    <label for="message">{{ __('landings.salone.field_message') }}</label>
                                </div>
                            </div>
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

    <div class="container-fluid bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    &copy; {{ date('Y') }} <x-landing.text key="title" tag="bdi" />
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <!--/*** The author’s attribution link must remain intact in the template. ***/-->
                    {{ __('landings.pretty.credit') }} <a class="border-bottom text-secondary" href="https://htmlcodex.com" target="_blank" rel="noopener">HTML Codex</a>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ $asset('lib/wow/wow.min.js') }}"></script>
    <script src="{{ $asset('lib/easing/easing.min.js') }}"></script>
    <script src="{{ $asset('js/main.js') }}"></script>

    @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker'], 'accent' => '#6244C5', 'accentText' => '#fff'])
    @include('landings.partials.editor-assets')
</body>

</html>
