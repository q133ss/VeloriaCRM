@php
    $benefits = collect(preg_split('/\r\n|\r|\n/', (string) ($settings['benefit_items_text'] ?? '')))
        ->map(fn ($item) => trim($item))
        ->filter()
        ->values();
@endphp

<section class="landing-section-card">
    <div class="landing-section-head">
        <h2>{{ __('landings.templates.consultation.section_title') }}</h2>
        <p>{{ $settings['lead_magnet'] ?? __('landings.templates.consultation.default_description') }}</p>
    </div>

    <div class="landing-proof-card" style="margin-bottom: 1rem;">
        <strong>{{ __('landings.templates.consultation.lead_magnet_title') }}</strong>
        <div class="landing-booking-meta">{{ $settings['lead_magnet'] ?? __('landings.templates.consultation.default_headline') }}</div>
    </div>

    @if($benefits->isNotEmpty())
        <ul class="landing-list">
            @foreach($benefits as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    @endif
</section>
