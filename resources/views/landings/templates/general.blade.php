@php
    $serviceNames = $settings['service_names'] ?? [];
    $showAll = $settings['show_all_services'] ?? false;
@endphp
<div style="display: flex; flex-direction: column; gap: 32px;">
    <div style="font-size: 20px; line-height: 1.6; color: rgba(15, 23, 42, 0.8);">
        {{ $settings['greeting'] ?? __('landings.templates.general.default_greeting') }}
    </div>
    <div style="display: grid; gap: 16px;">
        <div style="font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(15, 23, 42, 0.6);">
            {{ __('landings.templates.general.services_title') }}
        </div>
        @if($showAll)
            <div style="display: flex; align-items: center; gap: 12px; background: rgba(99,102,241,0.08); padding: 16px 20px; border-radius: 14px;">
                <div style="width: 12px; height: 12px; background: var(--primary-color); border-radius: 50%;"></div>
                <div style="font-weight: 500;">{{ __('landings.templates.general.all_services') }}</div>
            </div>
        @elseif(!empty($serviceNames))
            <div style="display: grid; gap: 12px;">
                @foreach($serviceNames as $name)
                    <div style="padding: 16px 20px; border-radius: 14px; background: rgba(99,102,241,0.08); display: flex; align-items: center; gap: 12px;">
                        <div style="width: 10px; height: 10px; border-radius: 50%; background: var(--primary-color);"></div>
                        <div>{{ $name }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="color: rgba(15, 23, 42, 0.6);">{{ __('landings.templates.general.no_services') }}</div>
        @endif
    </div>
    <div>
        <a href="#booking" style="display: inline-flex; align-items: center; gap: 12px; padding: 16px 28px; border-radius: 999px; background: var(--primary-color); color: #fff; text-decoration: none; font-weight: 600;">
            {{ __('landings.templates.general.cta') }}
        </a>
    </div>
</div>
