@php
    $benefits = collect(preg_split('/\r\n|\r|\n/', (string) ($settings['benefit_items_text'] ?? '')))
        ->map(fn ($item) => trim($item))
        ->filter()
        ->values();
@endphp

<section class="landing-section-card">
    <div class="landing-section-head">
        <h2>{{ $settings['service_name'] ?? __('landings.templates.service.default_title') }}</h2>
        <p>{{ $settings['service_description'] ?? __('landings.templates.service.default_description') }}</p>
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
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    @endif
</section>
