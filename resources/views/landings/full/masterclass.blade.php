{{--
    Курсы и мастер-классы: ECourses, Online Courses HTML Template by HTML Codex
    (https://htmlcodex.com/online-courses-html-template/), CC BY 4.0: the author's credit link in the footer
    must stay. Assets and the license live in public/landing-templates/masterclass.

    Imported with landing:import-template, then finished by hand: the courses are the master's own
    services (no invented students, ratings, teachers, testimonials or blog), the offer block carries her
    real discount, hours come from her schedule, the sign-up form is the booking widget. The owl carousel
    and the contact validation scripts of the original are not loaded.
--}}
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/masterclass/' . $path);
    $price = fn ($value) => number_format((float) $value, 0, ',', ' ');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $landing->title }}</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $heroText), 150) }}" name="description">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&amp;subset=cyrillic&amp;display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="{{ $asset('css/style.css') }}" rel="stylesheet">
    <style>
        .mc-hero { background-size: cover; background-position: center; }
        body, h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 { font-family: 'Montserrat', 'Poppins', sans-serif; }
    </style>
</head>
<body>
    @if($isPreview && empty($isEdit))
        <div style="position: fixed; top: 0; left: 0; right: 0; z-index: 2000; background: #111; color: #fff; text-align: center; font-size: 12px; padding: 2px 0;">{{ __('landings.public.preview_badge') }}</div>
    @endif

    <div class="container-fluid">
        <div class="row align-items-center py-3 px-xl-5">
            <div class="col-lg-4 col-12 text-center text-lg-left">
                <a href="#top" class="text-decoration-none"><h1 class="m-0 h3 text-primary"><x-landing.text key="title" tag="bdi" /></h1></a>
            </div>
            <div class="col-lg-8 d-none d-lg-flex justify-content-end">
                @if($address !== '' || $editing)
                    <div class="d-inline-flex align-items-center mr-5">
                        <i class="fa fa-2x fa-map-marker-alt text-primary mr-3"></i>
                        <small><x-landing.text key="address" tag="bdi" /></small>
                    </div>
                @endif
                @if($phoneHref || $editing)
                    <div class="d-inline-flex align-items-center">
                        <i class="fa fa-2x fa-phone-alt text-primary mr-3"></i>
                        <small><a href="{{ $phoneHref ?: '#' }}" class="text-dark"><x-landing.text key="phone" tag="bdi" /></a></small>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="container-fluid" id="top">
        <nav class="navbar navbar-expand-lg bg-light navbar-light py-3 py-lg-0 px-xl-5 border-top">
            <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                <div class="navbar-nav py-0">
                    <a href="#about" class="nav-item nav-link">{{ __('landings.masterclass.about_kicker') }}</a>
                    <a href="#courses" class="nav-item nav-link">{{ __('landings.masterclass.programs') }}</a>
                    <a href="#signup" class="nav-item nav-link">{{ __('landings.common.nav_booking') }}</a>
                </div>
                <a class="btn btn-primary py-2 px-4 ml-auto d-none d-lg-block" href="#signup"><x-landing.text key="cta_label" tag="bdi" :default="__('landings.masterclass.signup')" /></a>
            </div>
        </nav>
    </div>

    <x-landing.bg key="hero_image_1" default="landing-templates/masterclass/img/carousel-1.jpg" tag="div" class="container-fluid p-0 pb-5 mb-5 mc-hero">
        <div class="d-flex align-items-center justify-content-center text-center" style="min-height: 460px; background: rgba(0, 0, 0, .55);">
            <div class="p-5" style="width: 100%; max-width: 900px;">
                <h5 class="text-white text-uppercase mb-md-3">{{ __('landings.masterclass.kicker') }}</h5>
                <x-landing.text key="hero_title" tag="h1" class="display-4 text-white mb-md-4" :default="__('landings.common.hero_default')" :title-fallback="false" />
                @if($editing || filled($heroText))
                    <x-landing.text key="hero_text" tag="p" class="text-white mb-4" />
                @endif
                <a href="#signup" class="btn btn-primary py-md-2 px-md-4 font-weight-semi-bold mt-2"><x-landing.text key="cta_label" tag="bdi" :default="__('landings.masterclass.signup')" /></a>
            </div>
        </div>
    </x-landing.bg>

    <div class="container-fluid py-5" id="about">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-5">
                    <x-landing.image key="about_image" default="landing-templates/masterclass/img/about.jpg" class="img-fluid rounded mb-4 mb-lg-0" alt="" />
                </div>
                <div class="col-lg-7">
                    <div class="text-left mb-4">
                        <h5 class="text-primary text-uppercase mb-3" style="letter-spacing: 5px;">{{ __('landings.masterclass.about_kicker') }}</h5>
                        <h1>{{ __('landings.common.about_title') }}</h1>
                    </div>
                    @if($editing || filled($settings['master_bio'] ?? null))
                        <x-landing.text key="master_bio" tag="p" />
                    @endif
                    @if($editing || $proofItems->isNotEmpty())
                        <ul class="list-unstyled mb-3">
                            @foreach($proofItems->take(5) as $item)
                                <li class="py-1"><i class="fa fa-check text-primary mr-3"></i><x-landing.text key="proof_items_text" :index="$loop->index" tag="bdi" /></li>
                            @endforeach
                        </ul>
                    @endif
                    @if(count($hours))
                        <p class="mb-1"><strong>{{ __('landings.common.hours_kicker') }}</strong></p>
                        @foreach($hours as $line)<div>{{ $line['days'] }}: {{ $line['from'] }}–{{ $line['to'] }}</div>@endforeach
                    @endif
                    <a href="#signup" class="btn btn-primary py-md-2 px-md-4 font-weight-semi-bold mt-3">{{ __('landings.common.book') }}</a>
                </div>
            </div>
        </div>
    </div>

    @if($cards->isNotEmpty())
        <div class="container-fluid py-5" id="courses">
            <div class="container py-5">
                <div class="text-center mb-5">
                    <h5 class="text-primary text-uppercase mb-3" style="letter-spacing: 5px;">{{ __('landings.common.services_kicker') }}</h5>
                    <h1>{{ __('landings.masterclass.programs') }}</h1>
                    <p class="mb-0">{{ __('landings.masterclass.programs_lead') }}</p>
                </div>
                <div class="row">
                    @foreach($cards->take(6) as $card)
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="rounded overflow-hidden mb-2 h-100">
                                <div class="bg-secondary p-4 h-100 d-flex flex-column">
                                    @if(!empty($card['duration']))
                                        <small class="mb-3"><i class="far fa-clock text-primary mr-2"></i>{{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}</small>
                                    @endif
                                    <span class="h5">{{ $card['name'] }}</span>
                                    <div class="border-top mt-auto pt-4">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <a href="#signup" data-pick-service="{{ $card['id'] }}">{{ __('landings.common.book') }}</a>
                                            @if(!empty($card['price']))<h5 class="m-0">{{ $price($card['price']) }} ₽</h5>@endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="container-fluid bg-registration py-5" id="signup" style="margin: 90px 0;">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-7 mb-5 mb-lg-0">
                    <div class="mb-4">
                        <h5 class="text-primary text-uppercase mb-3" style="letter-spacing: 5px;">{{ __('landings.common.booking_kicker') }}</h5>
                        @if($hasOffer)
                            <h1 class="text-white">{{ __('landings.pretty.offer_kicker', ['percent' => $offerPercent]) }}</h1>
                        @else
                            <h1 class="text-white">{{ __('landings.common.booking_title') }}</h1>
                        @endif
                    </div>
                    @if($hasOffer && ($promoCode || $endsAt))
                        <p class="text-white">
                            @if($promoCode){{ __('landings.pretty.offer_code', ['code' => $promoCode]) }}. @endif
                            @if($endsAt){{ __('landings.pretty.offer_until', ['date' => $endsAt]) }}@endif
                        </p>
                    @endif
                    @if($editing || filled($settings['booking_hint'] ?? null))
                        <x-landing.text key="booking_hint" tag="p" class="text-white" />
                    @endif
                </div>
                <div class="col-lg-5">
                    <div class="card border-0">
                        <div class="card-header bg-light text-center p-4">
                            <h1 class="m-0 h2">{{ __('landings.masterclass.signup') }}</h1>
                        </div>
                        <div class="card-body rounded-bottom bg-primary p-5">
                            <form id="request-form" novalidate>
                                <div class="form-group">
                                    <input type="text" class="form-control border-0 p-4" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120" autocomplete="name">
                                </div>
                                <div class="form-group">
                                    <input type="tel" class="form-control border-0 p-4" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask>
                                </div>
                                <div class="form-group">
                                    <select class="custom-select border-0 px-4" style="height: 47px;" name="service_id" id="request-service">
                                        <option value="">{{ __('landings.salone.field_service_any') }}</option>
                                        @foreach($services as $service)
                                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <textarea class="form-control border-0 p-3" name="message" rows="2" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000"></textarea>
                                </div>
                                <div id="request-picker" class="mb-3"></div>
                                <div>
                                    <button id="request-submit" class="btn btn-dark btn-block border-0 py-3" type="submit">{{ __('landings.salone.submit') }}</button>
                                    <div id="request-message" class="mt-3 text-white" role="status" aria-live="polite"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid bg-dark text-white border-top py-4 px-sm-3 px-md-5" style="border-color: rgba(256, 256, 256, .1) !important;">
        <div class="row">
            <div class="col-lg-6 text-center text-lg-left mb-3 mb-lg-0">
                <p class="m-0 text-white">&copy; {{ date('Y') }} <x-landing.text key="title" tag="bdi" />
                    <!--/*** The author’s attribution link must remain intact in the template. ***/-->
                    · {{ __('landings.pretty.credit') }} <a href="https://htmlcodex.com" target="_blank" rel="noopener">HTML Codex</a>
                </p>
            </div>
            <div class="col-lg-6 text-center text-lg-right">
                @if($telegram)<a class="text-white mr-3" href="{{ $telegram }}" target="_blank" rel="noopener">Telegram</a>@endif
                @if($whatsapp)<a class="text-white" href="{{ $whatsapp }}" target="_blank" rel="noopener">WhatsApp</a>@endif
            </div>
        </div>
    </div>

    <a href="#top" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="fa fa-angle-double-up"></i></a>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="{{ $asset('lib/easing/easing.min.js') }}"></script>
    <script src="{{ $asset('js/main.js') }}"></script>

    @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker'], 'accent' => '#FF6600', 'accentText' => '#fff'])
    @include('landings.partials.editor-assets')
</body>
</html>
