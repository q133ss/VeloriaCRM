@php
    $benefits = collect(preg_split('/\r\n|\r|\n/', (string) ($settings['benefit_items_text'] ?? '')))
        ->map(fn ($item) => trim($item))
        ->filter()
        ->values();
@endphp

<section class="landing-section-card">
    <div class="landing-section-head">
        <h2>{{ __('landings.templates.consultation.section_title') }}</h2>
        <x-landing.text key="lead_magnet" tag="p" :default="__('landings.templates.consultation.default_description')" />
    </div>

    <div class="landing-proof-card" style="margin-bottom: 1rem;">
        <strong>{{ __('landings.templates.consultation.lead_magnet_title') }}</strong>
        <x-landing.text key="lead_magnet" tag="div" class="landing-booking-meta" :default="__('landings.templates.consultation.default_headline')" />
    </div>

    @if($benefits->isNotEmpty())
        <ul class="landing-list">
            @foreach($benefits as $item)
                <x-landing.text key="benefit_items_text" :index="$loop->index" tag="li" />
            @endforeach
        </ul>
    @endif
</section>
