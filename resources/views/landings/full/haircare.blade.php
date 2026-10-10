{{--
    Haircare: Free Bootstrap 4 hair salon template by Colorlib (https://colorlib.com), distributed by
    ThemeWagon (https://themewagon.com/themes/free-bootstrap-4-html5-hair-salon-website-template-haircare/),
    CC BY 3.0: the link back to Colorlib in the footer must stay. Assets and the license live in
    public/landing-templates/haircare.

    Imported with landing:import-template, then finished by hand: the master's own data instead of
    placeholder text; no invented stylists, reviews or blog; the service menu, price cards and contacts
    come from her catalog; the booking block is the shared widget. main.js is the trimmed Colorlib one
    (no parallax, popups or date pickers). Poppins / Barlow Condensed have no Cyrillic, so the stylesheet
    uses Montserrat / Roboto Condensed.
--}}
@php
    extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices));

    $asset = fn (string $path) => asset('landing-templates/haircare/' . $path);
    $price = fn ($value) => number_format((float) $value, 0, ',', ' ');
    $icons = ['flaticon-male-hair-of-head-and-face-shapes', 'flaticon-beard', 'flaticon-beauty-products', 'flaticon-healthy-lifestyle-logo'];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <title>{{ $landing->title }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $heroText), 150) }}">

    <link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700&amp;subset=cyrillic" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto+Condensed:400,700&amp;subset=cyrillic&amp;display=swap" rel="stylesheet">

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
        <a class="navbar-brand" href="#top"><span class="flaticon-scissors-in-a-hair-salon-badge"></span><x-landing.text key="title" tag="bdi" /></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Menu">
          <span class="oi oi-menu"></span> {{ __('landings.pretty.menu') }}
        </button>

        <div class="collapse navbar-collapse" id="ftco-nav">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a href="#services" class="nav-link"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></a></li>
            <li class="nav-item"><a href="#booking" class="nav-link"><x-landing.text key="lbl_common_nav_booking" tag="bdi" :default="__('landings.common.nav_booking')" :title-fallback="false" /></a></li>
            <li class="nav-item"><a href="#master" class="nav-link"><x-landing.text key="lbl_common_nav_master" tag="bdi" :default="__('landings.common.nav_master')" :title-fallback="false" /></a></li>
            <li class="nav-item"><a href="#gallery" class="nav-link"><x-landing.text key="lbl_pretty_nav_work" tag="bdi" :default="__('landings.pretty.nav_work')" :title-fallback="false" /></a></li>
            @if($priced->isNotEmpty())
              <li class="nav-item"><a href="#prices" class="nav-link"><x-landing.text key="lbl_common_nav_prices" tag="bdi" :default="__('landings.common.nav_prices')" :title-fallback="false" /></a></li>
            @endif
            <li class="nav-item"><a href="#contacts" class="nav-link"><x-landing.text key="lbl_common_nav_contacts" tag="bdi" :default="__('landings.common.nav_contacts')" :title-fallback="false" /></a></li>
          </ul>
        </div>
      </div>
    </nav>
    <!-- END nav -->

    <x-landing.bg key="hero_image_1" default="landing-templates/haircare/images/bg-2.jpg" tag="section" class="hero-wrap js-fullheight">
      <div class="overlay"></div>
      <div class="container">
        <div class="row no-gutters slider-text js-fullheight justify-content-center align-items-center">
          <div class="col-lg-12 ftco-animate d-flex align-items-center">
            <div class="text text-center">
              <span class="subheading"><x-landing.text key="title" tag="bdi" /></span>
              <x-landing.text key="hero_title" tag="h1" class="mb-4" :default="__('landings.common.hero_default')" :title-fallback="false" />
              <p><a href="#booking" class="btn btn-primary btn-outline-primary px-4 py-2"><x-landing.text key="cta_label" tag="bdi" :default="$ctaDefault" /></a></p>
            </div>
          </div>
        </div>
      </div>
    </x-landing.bg>

    <section class="ftco-section ftco-no-pt ftco-no-pb">
      <div class="container-fluid px-0">
        <div class="row no-gutters">
          <div class="col-md text-center d-flex align-items-stretch">
            <x-landing.bg key="extra_image_1" default="landing-templates/haircare/images/formen.jpg" tag="div" class="services-wrap d-flex align-items-center img">
              <div class="text">
                <h3><x-landing.text key="lbl_haircare_for_men" tag="bdi" :default="__('landings.haircare.for_men')" :title-fallback="false" /></h3>
                <p><a href="#services" class="btn-custom"><x-landing.text key="lbl_haircare_see_services" tag="bdi" :default="__('landings.haircare.see_services')" :title-fallback="false" /> <span class="ion-ios-arrow-round-forward"></span></a></p>
              </div>
            </x-landing.bg>
          </div>
          <div class="col-md-3 text-center d-flex align-items-stretch">
            <div class="text-about py-5 px-4">
              <h1 class="logo">
                <a href="#top"><span class="flaticon-scissors-in-a-hair-salon-badge"></span><x-landing.text key="title" tag="bdi" /></a>
              </h1>
              <h2><x-landing.text key="lbl_haircare_welcome" tag="bdi" :default="__('landings.haircare.welcome')" :title-fallback="false" /></h2>
              @if($editing || filled($heroText))
                <x-landing.text key="hero_text" tag="p" />
              @endif
              <p class="mt-3"><a href="#booking" class="btn btn-primary btn-outline-primary"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a></p>
            </div>
          </div>
          <div class="col-md text-center d-flex align-items-stretch">
            <x-landing.bg key="extra_image_2" default="landing-templates/haircare/images/forwomen.jpg" tag="div" class="services-wrap d-flex align-items-center img">
              <div class="text">
                <h3><x-landing.text key="lbl_haircare_for_women" tag="bdi" :default="__('landings.haircare.for_women')" :title-fallback="false" /></h3>
                <p><a href="#services" class="btn-custom"><x-landing.text key="lbl_haircare_see_services" tag="bdi" :default="__('landings.haircare.see_services')" :title-fallback="false" /> <span class="ion-ios-arrow-round-forward"></span></a></p>
              </div>
            </x-landing.bg>
          </div>
        </div>
      </div>
    </section>

    <section class="services-section ftco-section" id="services">
      <div class="container">
        <div class="row justify-content-center pb-3">
          <div class="col-md-10 heading-section text-center ftco-animate">
            <span class="subheading"><x-landing.text key="lbl_common_services_kicker" tag="bdi" :default="__('landings.common.services_kicker')" :title-fallback="false" /></span>
            <h2 class="mb-4"><x-landing.text key="lbl_common_services_title" tag="bdi" :default="__('landings.common.services_title')" :title-fallback="false" /></h2>
          </div>
        </div>
        <div class="row no-gutters d-flex justify-content-center">
          @foreach($cards->take(8) as $card)
            <div class="col-md-6 col-lg-3 d-flex align-self-stretch ftco-animate">
              <div class="media block-6 services d-block text-center">
                <div class="icon"><span class="{{ $icons[$loop->index % count($icons)] }}"></span></div>
                <div class="media-body">
                  <h3 class="heading mb-3">{{ $card['name'] }}</h3>
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
                  <p><a href="#booking" class="btn btn-primary btn-outline-primary btn-sm px-3" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a></p>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    <section class="ftco-section ftco-booking bg-light" id="booking">
      <div class="container ftco-relative">
        <div class="row justify-content-center pb-3">
          <div class="col-md-10 heading-section text-center ftco-animate">
            <span class="subheading"><x-landing.text key="lbl_common_booking_kicker" tag="bdi" :default="__('landings.common.booking_kicker')" :title-fallback="false" /></span>
            <h2 class="mb-4"><x-landing.text key="lbl_common_booking_title" tag="bdi" :default="__('landings.common.booking_title')" :title-fallback="false" /></h2>
            @if($editing || filled($settings['booking_hint'] ?? null))
              <x-landing.text key="booking_hint" tag="p" />
            @endif
          </div>
        </div>
        @if($phoneHref || $editing)
          <h3 class="vr"><x-landing.text key="lbl_pretty_info_phone" tag="bdi" :default="__('landings.pretty.info_phone')" :title-fallback="false" />: <a href="{{ $phoneHref ?: '#' }}" style="color: inherit;"><x-landing.text key="phone" tag="bdi" /></a></h3>
        @endif
        <div class="row justify-content-center">
          <div class="col-md-10 ftco-animate">
            <form id="request-form" class="appointment-form" novalidate>
              <div class="row">
                <div class="col-sm-6">
                  <div class="form-group">
                    <input type="text" class="form-control" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120" autocomplete="name">
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="form-group">
                    <input type="tel" class="form-control" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask>
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="form-group">
                    <select name="service_id" id="request-service" class="form-control">
                      <option value="">{{ __('landings.salone.field_service_any') }}</option>
                      @foreach($services as $service)
                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="col-md-12"><div id="request-picker" class="mb-3"></div></div>
                <div class="col-md-12">
                  <div class="form-group">
                    <textarea cols="30" rows="4" class="form-control" name="message" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000"></textarea>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <button type="submit" id="request-submit" class="btn btn-primary">{{ __('landings.salone.submit') }}</button>
              </div>
              <div id="request-message" role="status" aria-live="polite"></div>
            </form>
          </div>
        </div>
      </div>
    </section>

    <section class="ftco-section ftco-team" id="master">
      <div class="container-fluid px-md-5">
        <div class="row justify-content-center pb-3">
          <div class="col-md-10 heading-section text-center ftco-animate">
            <span class="subheading"><x-landing.text key="lbl_common_master_kicker" tag="bdi" :default="__('landings.common.master_kicker')" :title-fallback="false" /></span>
            <h2 class="mb-4"><x-landing.text key="lbl_common_master_title" tag="bdi" :default="__('landings.common.master_title')" :title-fallback="false" /></h2>
          </div>
        </div>
        <div class="row justify-content-center">
          <div class="col-md-4 col-lg-3 ftco-animate">
            <div class="team text-center">
              <x-landing.bg key="master_photo" default="landing-templates/haircare/images/stylist-1.jpg" class="img" />
              <h2><x-landing.text key="title" tag="bdi" /></h2>
              <x-landing.text key="master_role" tag="span" class="position" :default="__('landings.common.master_role')" />
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="ftco-section ftco-no-pt ftco-no-pb" id="gallery">
      <div class="container">
        <div class="row no-gutters justify-content-center mb-5 pb-2">
          <div class="col-md-6 text-center heading-section ftco-animate">
            <span class="subheading"><x-landing.text key="lbl_pretty_work_title" tag="bdi" :default="__('landings.pretty.work_title')" :title-fallback="false" /></span>
            @if($editing)
              <p>{{ __('landings.pretty.work_hint') }}</p>
            @endif
          </div>
        </div>
      </div>
      <div class="container-fluid p-0">
        <div class="row no-gutters">
          @foreach([1, 2, 3, 4] as $i)
            <div class="col-md-6 col-lg-3 ftco-animate">
              <div class="project">
                <x-landing.image :key="'work_image_' . $i" :default="'landing-templates/haircare/images/work-' . $i . '.jpg'" class="img-fluid" alt="" />
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    @if($priced->isNotEmpty())
      <section class="ftco-section ftco-pricing" id="prices">
        <div class="container">
          <div class="row justify-content-center pb-3">
            <div class="col-md-10 heading-section text-center ftco-animate">
              <span class="subheading"><x-landing.text key="lbl_common_prices_kicker" tag="bdi" :default="__('landings.common.prices_kicker')" :title-fallback="false" /></span>
              <h2 class="mb-4"><x-landing.text key="lbl_common_prices_title" tag="bdi" :default="__('landings.common.prices_title')" :title-fallback="false" /></h2>
            </div>
          </div>
          <div class="row justify-content-center">
            @foreach($priced as $card)
              <div class="col-md-3 ftco-animate">
                <div class="pricing-entry pb-5 text-center">
                  <div>
                    <h3 class="mb-4">{{ $card['name'] }}</h3>
                    <p>
                      <span class="price">{{ $price($card['price']) }} ₽</span>
                      @if(!empty($card['duration']))
                        <span class="per">/ {{ __('landings.salone.minutes', ['n' => (int) $card['duration']]) }}</span>
                      @endif
                    </p>
                  </div>
                  <p class="button text-center"><a href="#booking" class="btn btn-primary px-4 py-3" data-pick-service="{{ $card['id'] }}"><x-landing.text key="lbl_common_book" tag="bdi" :default="__('landings.common.book')" :title-fallback="false" /></a></p>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </section>
    @endif

    <footer class="ftco-footer ftco-section" id="contacts">
      <div class="container">
        <div class="row mb-5">
          <div class="col-md">
            <div class="ftco-footer-widget mb-4">
              <h2 class="ftco-heading-2 logo"><x-landing.text key="title" tag="bdi" /></h2>
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
            <div class="ftco-footer-widget mb-4 ml-md-5">
              <h2 class="ftco-heading-2"><x-landing.text key="lbl_common_quick_links" tag="bdi" :default="__('landings.common.quick_links')" :title-fallback="false" /></h2>
              <ul class="list-unstyled">
                <li><a href="#services" class="py-2 d-block"><x-landing.text key="lbl_common_nav_services" tag="bdi" :default="__('landings.common.nav_services')" :title-fallback="false" /></a></li>
                <li><a href="#booking" class="py-2 d-block"><x-landing.text key="lbl_common_nav_booking" tag="bdi" :default="__('landings.common.nav_booking')" :title-fallback="false" /></a></li>
                <li><a href="#master" class="py-2 d-block"><x-landing.text key="lbl_common_nav_master" tag="bdi" :default="__('landings.common.nav_master')" :title-fallback="false" /></a></li>
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
            <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
            <p>
              &copy;{{ date('Y') }} <x-landing.text key="title" tag="bdi" /> | {{ __('landings.pretty.credit') }} <i class="icon-heart" aria-hidden="true"></i> <a href="https://colorlib.com" target="_blank" rel="noopener">Colorlib</a>
            </p>
            <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
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
