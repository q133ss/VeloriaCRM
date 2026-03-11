@php
    $serviceNames = $settings['service_names'] ?? $featuredServices->pluck('name')->all();
@endphp

<section class="landing-section-card">
    <div class="landing-section-head">
        <h2>{{ $settings['greeting'] ?? __('landings.templates.general.default_greeting') }}</h2>
        <p>{{ __('landings.templates.general.default_description') }}</p>
    </div>

    <div class="landing-service-grid">
        @foreach(collect($serviceNames)->take(6) as $name)
            <article class="landing-service-card">
                <strong>{{ $name }}</strong>
                <div class="landing-booking-meta">{{ __('landings.templates.general.service_note') }}</div>
            </article>
        @endforeach
    </div>
</section>
