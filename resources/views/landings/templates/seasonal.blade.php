<section class="landing-section-card">
    <div class="landing-section-head">
        <x-landing.text key="hero_title" tag="h2" :default="__('landings.templates.seasonal.default_headline')" :title-fallback="false" />
        <x-landing.text key="hero_text" tag="p" :default="__('landings.templates.seasonal.default_description')" />
    </div>

    <div class="landing-metric-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        @if(!empty($settings['season_label']))
            <div class="landing-metric">
                <span>{{ __('landings.templates.seasonal.season_label') }}</span>
                <x-landing.text key="season_label" tag="strong" />
            </div>
        @endif
        @if(!empty($settings['ends_at']))
            <div class="landing-metric">
                <span>{{ __('landings.templates.seasonal.ends_at_label') }}</span>
                <strong>{{ $settings['ends_at'] }}</strong>
            </div>
        @endif
    </div>

    <div class="landing-service-grid" style="margin-top: 1rem;">
        @foreach($featuredServices as $service)
            <article class="landing-service-card">
                <strong>{{ $service->name }}</strong>
                <div class="landing-booking-meta">{{ __('landings.templates.seasonal.service_note') }}</div>
            </article>
        @endforeach
    </div>
</section>
