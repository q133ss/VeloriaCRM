{{--
    Energen: Free Bootstrap 4 beauty salon template by Colorlib (https://colorlib.com), distributed by
    ThemeWagon (https://themewagon.com/themes/free-bootstrap-4-html5-beauty-salon-website-template-energen/),
    CC BY 3.0: the link back to Colorlib in the footer must stay. Assets and the license live in
    public/landing-templates/energen.

    Imported with landing:import-template, then finished by hand: the master's own data instead of
    placeholder text; no invented specialists, reviews or blog; treatments, price cards, offers and
    contacts come from her catalog; counters are real figures shown when there are enough; the questions
    block carries her FAQ; the booking block is the shared widget (the template has no form of its own).
    main.js is the trimmed Colorlib one.
--}}
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/energen/' . $path);
    $price = fn ($value) => number_format((float) $value, 0, ',', ' ');
    $icons = ['flaticon-candle', 'flaticon-beauty-treatment', 'flaticon-stone', 'flaticon-relax'];
    $offerCards = $cards->take(3)->values();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <title>{{ $landing->title }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $heroText), 150) }}">

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700&amp;subset=cyrillic&amp;display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Prata&amp;subset=cyrillic&amp;display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="{{ $asset('css/open-iconic-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ $asset('css/animate.css') }}">
    <link rel="stylesheet" href="{{ $asset('css/ionicons.min.css') }}">
    <link rel="stylesheet" href="{{ $asset('css/flaticon.css') }}">
    <link rel="stylesheet" href="{{ $asset('css/icomoon.css') }}">
    <link rel="stylesheet" href="{{ $asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ $asset('css/veloria.css') }}">
  </head>
  <body id="top">
    @if($isPreview && empty($isEdit))
      <div style="position: fixed; top: 0; left: 0; right: 0; z-index: 2000; background: #111; color: #fff; text-align: center; font-size: 12px; padding: 2px 0;">{{ __('landings.public.preview_badge') }}</div>
    @endif

    <nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
      <div class="container">
        <a class="navbar-brand" href="#top"><span class="flaticon-lotus"></span><x-landing.text key="title" tag="bdi" /></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Menu">
          <span class="oi oi-menu"></span> {{ __('landings.pretty.menu') }}
        </button>

        <div class="collapse navbar-collapse" id="ftco-nav">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a href="#services" class="nav-link"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></a></li>
            @if($priced->isNotEmpty())
              <li class="nav-item"><a href="#prices" class="nav-link"><x-landing.text key="lbl_common_nav_prices" tag="bdi" :default="__('landings.common.nav_prices')" :title-fallback="false" /></a></li>
            @endif
            <li class="nav-item"><a href="#booking" class="nav-link"><x-landing.text key="lbl_common_nav_booking" tag="bdi" :default="__('landings.common.nav_booking')" :title-fallback="false" /></a></li>
            <li class="nav-item"><a href="#gallery" class="nav-link"><x-landing.text key="lbl_pretty_nav_work" tag="bdi" :default="__('landings.pretty.nav_work')" :title-fallback="false" /></a></li>
            <li class="nav-item"><a href="#contacts" class="nav-link"><x-landing.text key="lbl_common_nav_contacts" tag="bdi" :default="__('landings.common.nav_contacts')" :title-fallback="false" /></a></li>
          </ul>
        </div>
      </div>
    </nav>
    <!-- END nav -->

    <x-landing.bg key="hero_image_1" default="landing-templates/energen/images/bg_1.jpg" tag="section" class="hero-wrap js-fullheight">
      <div class="overlay"></div>
      <div class="container">
        <div class="row no-gutters slider-text js-fullheight align-items-center justify-content-center">
          <div class="col-md-10 ftco-animate text-center">
            <div class="icon">
              <span class="flaticon-lotus"></span>
            </div>
            <x-landing.text key="hero_title" tag="h1" :default="__('landings.common.hero_default')" :title-fallback="false" />
            @if($editing || filled($heroText))
              <div class="row justify-content-center">
                <div class="col-md-7 mb-3">
                  <x-landing.text key="hero_text" tag="p" />
                </div>
              </div>
            @endif
            <p>
              <a href="#booking" class="btn btn-primary p-3 px-5 py-4 mr-md-2"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a>
              <a href="#services" class="btn btn-outline-primary p-3 px-5 py-4 ml-md-2"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></a>
            </p>
          </div>
        </div>
      </div>
    </x-landing.bg>

    @if($editing || $proofItems->isNotEmpty())
      <x-landing.bg key="extra_image_1" default="landing-templates/energen/images/intro.jpg" tag="section" class="ftco-section ftco-intro">
        <div class="container">
          <div class="row justify-content-end">
            <div class="col-md-6">
              <div class="heading-section ftco-animate">
                <h2 class="mb-4"><x-landing.text key="lbl_common_about_title" tag="bdi" :default="__('landings.common.about_title')" :title-fallback="false" /></h2>
              </div>
              <ul class="mt-4 do-list">
                @foreach($proofItems->take(5) as $item)
                  <li class="ftco-animate"><span class="ion-ios-checkmark-circle mr-3"></span><x-landing.text key="proof_items_text" :index="$loop->index" tag="bdi" /></li>
                @endforeach
              </ul>
            </div>
          </div>
        </div>
      </x-landing.bg>
    @endif

    <section class="ftco-section ftco-no-pt ftco-no-pb" id="services">
      <div class="container">
        <div class="row no-gutters">
          @foreach($offerCards as $card)
            <div class="col-md-4 d-flex align-items-stretch">
              <div class="offer-deal {{ $loop->iteration === 2 ? 'active' : '' }} text-center px-2 px-lg-5">
                <x-landing.bg :key="$card['id'] ? 'service_image_' . $card['id'] : null" :default="'landing-templates/energen/images/offer-deal-' . $loop->iteration . '.jpg'" class="img" />
                <div class="text mt-4">
                  <h3 class="mb-4">{{ $card['name'] }}</h3>
                  <p class="mb-5">
                    @if(!empty($card['price']))
                      {{ __('landings.salone.from_price', ['price' => $price($card['price'])]) }}
                    @endif
                    @if(!empty($card['price']) && !empty($card['duration'])) · @endif
                    @if(!empty($card['duration']))
                      {{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}
                    @endif
                  </p>
                  <p><a href="#booking" class="btn btn-white px-4 py-3" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /> <span class="ion-ios-arrow-round-forward"></span></a></p>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    {{-- The first three services are the big cards above; this grid only carries the rest, so one service is not shown three times in a row. --}}
    @if($cards->count() > $offerCards->count())
    <section class="ftco-section ftco-section-services bg-light" id="more-services">
      <div class="container-fluid px-md-5">
        <div class="row justify-content-center mb-5 pb-3">
          <div class="col-md-12 heading-section ftco-animate text-center">
            <h3 class="subheading"><x-landing.text key="lbl_common_services_kicker" tag="bdi" :default="__('landings.common.services_kicker')" :title-fallback="false" /></h3>
            <h2 class="mb-1"><x-landing.text key="lbl_common_services_title" tag="bdi" :default="__('landings.common.services_title')" :title-fallback="false" /></h2>
          </div>
        </div>
        <div class="row justify-content-center">
          @foreach($cards->slice($offerCards->count())->take(8) as $card)
            <div class="col-md-6 col-lg-3">
              <div class="services text-center ftco-animate">
                <div class="icon d-flex justify-content-center align-items-center">
                  <span class="{{ $icons[$loop->index % count($icons)] }}"></span>
                </div>
                <div class="text mt-3">
                  <h3>{{ $card['name'] }}</h3>
                  @if(!empty($card['price']) || !empty($card['duration']))
                    <p>
                      @if(!empty($card['price']))
                        {{ __('landings.salone.from_price', ['price' => $price($card['price'])]) }}
                      @endif
                      @if(!empty($card['price']) && !empty($card['duration'])) · @endif
                      @if(!empty($card['duration']))
                        {{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}
                      @endif
                    </p>
                  @endif
                  <p><a href="#booking" class="btn btn-primary btn-sm px-3" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a></p>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </section>
    @endif

    @if($priced->isNotEmpty())
      <section class="ftco-section" id="prices">
        <div class="container">
          <div class="row justify-content-center mb-5 pb-3">
            <div class="col-md-7 heading-section ftco-animate text-center">
              <h3 class="subheading"><x-landing.text key="lbl_common_prices_kicker" tag="bdi" :default="__('landings.common.prices_kicker')" :title-fallback="false" /></h3>
              <h2 class="mb-1"><x-landing.text key="lbl_common_prices_title" tag="bdi" :default="__('landings.common.prices_title')" :title-fallback="false" /></h2>
            </div>
          </div>
          <div class="row justify-content-center">
            @foreach($priced->take(3) as $card)
              <div class="col-md-4 ftco-animate">
                <div class="block-7">
                  <div class="text-center">
                    <h2 class="heading" style="overflow-wrap: break-word;">{{ $card['name'] }}</h2>
                    <span class="price"><span class="number">{{ $price($card['price']) }}</span> <sup>₽</sup></span>
                    @if(!empty($card['duration']))
                      <span class="excerpt d-block">{{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}</span>
                    @endif
                    <a href="#booking" class="btn btn-primary d-block px-2 py-4 mt-4" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </section>
    @endif

    @if($showCounters)
      <x-landing.bg key="extra_image_2" default="landing-templates/energen/images/bg_3.jpg" tag="section" class="ftco-counter img" id="section-counter">
        <div class="container">
          <div class="row d-md-flex align-items-center justify-content-center">
            @foreach([['clients', 'counters_clients'], ['visits', 'counters_visits'], ['services', 'counters_services'], ['upcoming', 'counters_upcoming']] as [$stat, $label])
              <div class="col-md d-flex justify-content-center counter-wrap ftco-animate">
                <div class="block-18">
                  <div class="text">
                    <strong class="number">{{ number_format($stats[$stat], 0, ',', ' ') }}</strong>
                    <span>{{ __('landings.pretty.' . $label) }}</span>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </x-landing.bg>
    @endif

    <section class="ftco-section bg-light" id="booking">
      <div class="container">
        <div class="row justify-content-center mb-5 pb-3">
          <div class="col-md-7 heading-section ftco-animate text-center">
            <h3 class="subheading"><x-landing.text key="lbl_common_booking_kicker" tag="bdi" :default="__('landings.common.booking_kicker')" :title-fallback="false" /></h3>
            <h2 class="mb-1"><x-landing.text key="lbl_common_booking_title" tag="bdi" :default="__('landings.common.booking_title')" :title-fallback="false" /></h2>
            @if($editing || filled($settings['booking_hint'] ?? null))
              <x-landing.text key="booking_hint" tag="p" class="mt-3" />
            @endif
          </div>
        </div>
        <div class="row justify-content-center">
          <div class="col-md-8 ftco-animate">
            <form id="request-form" novalidate>
              <div class="row">
                <div class="col-sm-6">
                  <div class="form-group">
                    <input type="text" class="form-control" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120" autocomplete="name" style="height: 52px;">
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="form-group">
                    <input type="tel" class="form-control" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask style="height: 52px;">
                  </div>
                </div>
                <div class="col-12">
                  <div class="form-group">
                    <select class="form-control" name="service_id" id="request-service" style="height: 52px;">
                      <option value="">{{ __('landings.salone.field_service_any') }}</option>
                      @foreach($services as $service)
                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="col-12"><div id="request-picker" class="mb-3"></div></div>
                <div class="col-12">
                  <div class="form-group">
                    <textarea class="form-control" name="message" rows="4" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000"></textarea>
                  </div>
                </div>
              </div>
              <button type="submit" id="request-submit" class="btn btn-primary px-5 py-3">{{ __('landings.salone.submit') }}</button>
              <div id="request-message" class="mt-3" role="status" aria-live="polite"></div>
            </form>
          </div>
        </div>
      </div>
    </section>

    @if($faqItems->isNotEmpty())
      <section class="ftco-section">
        <div class="container">
          <div class="row justify-content-center mb-5 pb-3">
            <div class="col-md-7 heading-section ftco-animate text-center">
              <h3 class="subheading"><x-landing.text key="lbl_pretty_faq_title" tag="bdi" :default="__('landings.pretty.faq_title')" :title-fallback="false" /></h3>
              <h2 class="mb-1"><x-landing.text key="lbl_pretty_faq_lead" tag="bdi" :default="__('landings.pretty.faq_lead')" :title-fallback="false" /></h2>
            </div>
          </div>
          <div class="row d-flex">
            @foreach($faqItems->take(3) as $item)
              <div class="col-md-4 d-flex ftco-animate">
                <div class="blog-entry justify-content-end">
                  <x-landing.bg tag="span" :key="'faq_image_' . ($loop->index + 1)" :default="'landing-templates/energen/images/image_' . ($loop->index + 1) . '.jpg'" class="block-20" />
                  <div class="text p-4 float-right d-block">
                    <x-landing.text key="faq_items_text" :index="$loop->index" tag="p" />
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </section>
    @endif

    <section class="ftco-gallery ftco-section" id="gallery">
      <div class="container">
        <div class="row justify-content-center mb-5 pb-3">
          <div class="col-md-7 heading-section ftco-animate text-center">
            <h3 class="subheading"><x-landing.text key="lbl_pretty_work_title" tag="bdi" :default="__('landings.pretty.work_title')" :title-fallback="false" /></h3>
            @if($editing)
              <p>{{ __('landings.pretty.work_hint') }}</p>
            @endif
          </div>
        </div>
        <div class="row">
          @foreach([1, 2, 3] as $i)
            <div class="col-md-4 ftco-animate">
              <div class="gallery img">
                <x-landing.image :key="'work_image_' . $i" :default="'landing-templates/energen/images/gallery-' . $i . '.jpg'" class="img-fluid" alt="" />
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    <footer class="ftco-footer ftco-section" id="contacts">
      <div class="container">
        <div class="row d-flex">
          <div class="col-md">
            <div class="ftco-footer-widget mb-4">
              <h2 class="ftco-heading-2"><x-landing.text key="title" tag="bdi" /></h2>
              @if($proofItems->isNotEmpty())
                <ul class="list-unstyled">
                  @foreach($proofItems->take(3) as $item)
                    <x-landing.text key="proof_items_text" :index="$loop->index" tag="li" class="mb-2" />
                  @endforeach
                </ul>
              @endif
              @if($telegram || $whatsapp)
                <ul class="ftco-footer-social list-unstyled float-lft mt-3">
                  @if($telegram)
                    <li class="ftco-animate"><a href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="Telegram"><span class="bi bi-telegram"></span></a></li>
                  @endif
                  @if($whatsapp)
                    <li class="ftco-animate"><a href="{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><span class="bi bi-whatsapp"></span></a></li>
                  @endif
                </ul>
              @endif
            </div>
          </div>
          <div class="col-md">
            <div class="ftco-footer-widget mb-4 ml-md-4">
              <h2 class="ftco-heading-2"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></h2>
              <ul class="list-unstyled">
                @foreach($cards->take(5) as $card)
                  <li><a href="#booking" data-pick-service="{{ $card['id'] }}">{{ $card['name'] }}</a></li>
                @endforeach
              </ul>
            </div>
          </div>
          <div class="col-md">
            <div class="ftco-footer-widget mb-4">
              <h2 class="ftco-heading-2"><x-landing.text key="lbl_common_quick_links" tag="bdi" :default="__('landings.common.quick_links')" :title-fallback="false" /></h2>
              <ul class="list-unstyled">
                <li><a href="#services"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></a></li>
                <li><a href="#booking"><x-landing.text key="lbl_common_nav_booking" tag="bdi" :default="__('landings.common.nav_booking')" :title-fallback="false" /></a></li>
                <li><a href="#gallery"><x-landing.text key="lbl_pretty_nav_work" tag="bdi" :default="__('landings.pretty.nav_work')" :title-fallback="false" /></a></li>
              </ul>
            </div>
          </div>
          @if($address !== '' || $phoneHref || $editing)
            <div class="col-md">
              <div class="ftco-footer-widget mb-4">
                <h2 class="ftco-heading-2"><x-landing.text key="lbl_common_contacts_title" tag="bdi" :default="__('landings.common.contacts_title')" :title-fallback="false" /></h2>
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
            <p class="mb-0">
              <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
              &copy;{{ date('Y') }} <x-landing.text key="title" tag="bdi" /> | {{ __('landings.pretty.credit') }} <i class="icon-heart" aria-hidden="true"></i> <a href="https://colorlib.com" target="_blank" rel="noopener">Colorlib</a>
              <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
            </p>
          </div>
        </div>
      </div>
    </footer>

    <script src="{{ $asset('js/jquery.min.js') }}"></script>
    <script src="{{ $asset('js/popper.min.js') }}"></script>
    <script src="{{ $asset('js/bootstrap.min.js') }}"></script>
    <script src="{{ $asset('js/jquery.waypoints.min.js') }}"></script>
    <script src="{{ $asset('js/main.js') }}"></script>

    @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker'], 'accent' => '#111111', 'accentText' => '#fff'])
    @include('landings.partials.editor-assets')
  </body>
</html>
