<section class="landing-section-card">
    <div class="landing-section-head">
        <h2>{{ $settings['headline'] ?? __('landings.templates.seasonal.default_headline') }}</h2>
        <p>{{ $settings['description'] ?? __('landings.templates.seasonal.default_description') }}</p>
    </div>

    <div class="landing-metric-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        @if(!empty($settings['season_label']))
            <div class="landing-metric">
                <span>{{ __('landings.templates.seasonal.season_label') }}</span>
                <strong>{{ $settings['season_label'] }}</strong>
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
