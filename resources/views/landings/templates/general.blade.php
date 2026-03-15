@php
    $serviceNames = $settings['service_names'] ?? $featuredServices->pluck('name')->all();
    $generalDescription = filled($settings['greeting']) && mb_strlen(trim((string) $settings['greeting'])) > 12
        ? $settings['greeting']
        : __('landings.templates.general.default_description');
@endphp

<section class="landing-section-card">
    <div class="landing-section-head">
        <h2>{{ __('landings.templates.general.section_title') }}</h2>
        <p>{{ $generalDescription }}</p>
    </div>

    <div class="landing-service-grid">
        @foreach($featuredServices->take(6) as $service)
            <article class="landing-service-card">
                <strong>{{ $service->name }}</strong>
                <div class="landing-booking-meta">{{ __('landings.templates.general.service_note') }}</div>
                <div class="landing-service-tags">
                    @if(!empty($service->base_price))
                        <span>от {{ number_format((float) $service->base_price, 0, ',', ' ') }} ₽</span>
                    @endif
                    @if(!empty($service->duration_min))
                        <span>{{ (int) $service->duration_min }} мин</span>
                    @endif
                </div>
            </article>
        @endforeach
        @if($featuredServices->isEmpty())
            @foreach(collect($serviceNames)->take(6) as $name)
                <article class="landing-service-card">
                    <strong>{{ $name }}</strong>
                    <div class="landing-booking-meta">{{ __('landings.templates.general.service_note') }}</div>
                </article>
            @endforeach
        @endif
    </div>
</section>
