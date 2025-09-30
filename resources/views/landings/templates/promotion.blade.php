@php
    $endsAtFormatted = null;
    if (!empty($settings['ends_at'])) {
        try {
            $endsAtFormatted = \Illuminate\Support\Carbon::parse($settings['ends_at'])->translatedFormat('d F Y');
        } catch (Exception $e) {
            $endsAtFormatted = $settings['ends_at'];
        }
    }
@endphp
<div style="display: grid; gap: 28px;">
    <div>
        <div style="text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600; color: rgba(15,23,42,0.6);">
            {{ __('landings.templates.promotion.highlight') }}
        </div>
        <h2 style="margin: 12px 0 0; font-size: 36px; color: var(--primary-color);">
            {{ $settings['headline'] ?? __('landings.templates.promotion.default_headline') }}
        </h2>
    </div>
    <div style="font-size: 18px; line-height: 1.7; color: rgba(15,23,42,0.78);">
        {{ $settings['description'] ?? __('landings.templates.promotion.default_description') }}
    </div>
    <div style="display: flex; flex-wrap: wrap; gap: 16px;">
        @if(!empty($settings['discount_percent']))
            <div style="flex: 1 1 220px; padding: 20px; border-radius: 18px; background: rgba(99,102,241,0.08);">
                <div style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(15,23,42,0.5);">
                    {{ __('landings.templates.promotion.discount_label') }}
                </div>
                <div style="font-size: 32px; font-weight: 700; color: var(--primary-color);">
                    {{ number_format((float) $settings['discount_percent'], 0) }}%
                </div>
            </div>
        @endif
        @if(!empty($settings['promo_code']))
            <div style="flex: 1 1 220px; padding: 20px; border-radius: 18px; background: rgba(79,70,229,0.08);">
                <div style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(15,23,42,0.5);">
                    {{ __('landings.templates.promotion.promo_code_label') }}
                </div>
                <div style="font-size: 28px; font-weight: 700; letter-spacing: 0.08em;">
                    {{ strtoupper($settings['promo_code']) }}
                </div>
            </div>
        @endif
        @if($endsAtFormatted)
            <div style="flex: 1 1 220px; padding: 20px; border-radius: 18px; background: rgba(15,23,42,0.08);">
                <div style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(15,23,42,0.5);">
                    {{ __('landings.templates.promotion.ends_at_label') }}
                </div>
                <div style="font-size: 22px; font-weight: 600;">
                    {{ $endsAtFormatted }}
                </div>
            </div>
        @endif
    </div>
    <div>
        <a href="#book" style="display: inline-flex; align-items: center; gap: 12px; padding: 16px 28px; border-radius: 999px; background: var(--primary-color); color: #fff; text-decoration: none; font-weight: 600;">
            {{ __('landings.templates.promotion.cta') }}
        </a>
    </div>
</div>
