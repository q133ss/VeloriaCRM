@php
    $featuredService = $featuredServices->first();
@endphp

<section class="landing-section-card">
    <div class="landing-section-head">
        <x-landing.text key="hero_title" tag="h2" :default="__('landings.templates.promotion.default_headline')" :title-fallback="false" />
        <x-landing.text key="hero_text" tag="p" :default="__('landings.templates.promotion.default_description')" />
    </div>

    <div class="landing-metric-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        @if(!empty($settings['discount_percent']))
            <div class="landing-metric">
                <span>{{ __('landings.templates.promotion.discount_label') }}</span>
                <strong>{{ number_format((float) $settings['discount_percent'], 0) }}%</strong>
            </div>
        @endif
        @if(!empty($settings['promo_code']))
            <div class="landing-metric">
                <span>{{ __('landings.templates.promotion.promo_code_label') }}</span>
                <strong>{{ strtoupper($settings['promo_code']) }}</strong>
            </div>
        @endif
        @if(!empty($settings['ends_at']))
            <div class="landing-metric">
                <span>{{ __('landings.templates.promotion.ends_at_label') }}</span>
                <strong>{{ $settings['ends_at'] }}</strong>
            </div>
        @endif
    </div>

    @if($featuredService)
        <div class="landing-proof-card" style="margin-top: 1rem;">
            <strong>{{ $featuredService->name }}</strong>
            <div class="landing-booking-meta">{{ __('landings.templates.promotion.featured_service_note') }}</div>
        </div>
    @endif
</section>
