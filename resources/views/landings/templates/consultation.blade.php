@php
    $benefits = collect(preg_split('/\r\n|\r|\n/', (string) ($settings['benefit_items_text'] ?? '')))
        ->map(fn ($item) => trim($item))
        ->filter()
        ->values();
@endphp

<section class="landing-section-card">
    <div class="landing-section-head">
        <h2>{{ $settings['headline'] ?? __('landings.templates.consultation.default_headline') }}</h2>
        <p>{{ $settings['description'] ?? __('landings.templates.consultation.default_description') }}</p>
    </div>

    @if(!empty($settings['lead_magnet']))
        <div class="landing-proof-card" style="margin-bottom: 1rem;">
            <strong>{{ __('landings.templates.consultation.lead_magnet_title') }}</strong>
            <div class="landing-booking-meta">{{ $settings['lead_magnet'] }}</div>
        </div>
    @endif

    @if($benefits->isNotEmpty())
        <ul class="landing-list">
            @foreach($benefits as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    @endif
</section>
