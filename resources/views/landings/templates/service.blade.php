<div style="display: grid; gap: 28px;">
    <div>
        <div style="text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600; color: rgba(15,23,42,0.6);">
            {{ __('landings.templates.service.highlight') }}
        </div>
        <h2 style="margin: 12px 0 0; font-size: 36px; color: var(--primary-color);">
            {{ $settings['service_name'] ?? __('landings.templates.service.default_title') }}
        </h2>
    </div>
    <div style="font-size: 18px; line-height: 1.7; color: rgba(15,23,42,0.78);">
        {{ $settings['service_description'] ?? __('landings.templates.service.default_description') }}
    </div>
    <div style="display: grid; gap: 16px;">
        <div style="display: inline-flex; align-items: center; gap: 12px; padding: 14px 20px; border-radius: 14px; background: rgba(99,102,241,0.08); color: var(--primary-color); font-weight: 600;">
            <span>✨</span>
            <span>{{ __('landings.templates.service.benefit_one') }}</span>
        </div>
        <div style="display: inline-flex; align-items: center; gap: 12px; padding: 14px 20px; border-radius: 14px; background: rgba(99,102,241,0.08); color: var(--primary-color); font-weight: 600;">
            <span>🤝</span>
            <span>{{ __('landings.templates.service.benefit_two') }}</span>
        </div>
    </div>
    <div>
        <a href="#booking" style="display: inline-flex; align-items: center; gap: 12px; padding: 16px 28px; border-radius: 999px; background: var(--primary-color); color: #fff; text-decoration: none; font-weight: 600;">
            {{ __('landings.templates.service.cta') }}
        </a>
    </div>
</div>
