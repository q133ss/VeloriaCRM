@php
    $benefits = collect(preg_split('/\r\n|\r|\n/', (string) ($settings['benefit_items_text'] ?? '')))
        ->map(fn ($item) => trim($item))
        ->filter()
        ->values();
@endphp

<section class="landing-section-card">
    <div class="landing-section-head">
        <x-landing.text key="hero_title" tag="h2" :default="__('landings.templates.service.default_title')" :title-fallback="false" />
        <x-landing.text key="hero_text" tag="p" :default="__('landings.templates.service.default_description')" />
    </div>

    <div class="landing-metric-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        @if(!empty($settings['price_from']))
            <div class="landing-metric">
                <span>{{ __('landings.templates.service.price_label') }}</span>
                <strong>{{ $settings['price_from'] }}</strong>
            </div>
        @endif
        @if(!empty($settings['duration_label']))
            <div class="landing-metric">
                <span>{{ __('landings.templates.service.duration_label') }}</span>
                <strong>{{ $settings['duration_label'] }}</strong>
            </div>
        @endif
    </div>

    @if($benefits->isNotEmpty())
        <ul class="landing-list" style="margin-top: 1rem;">
            @foreach($benefits as $item)
                <x-landing.text key="benefit_items_text" :index="$loop->index" tag="li" />
            @endforeach
        </ul>
    @endif
</section>
