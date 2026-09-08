<div style="font-family: Arial, sans-serif; line-height: 1.5;">
    <p>{{ __('client_portal.email.magic_link_intro') }}</p>
    <p>
        <a href="{{ $link }}" style="display: inline-block; padding: 12px 20px; background: #ff00fc; color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: bold;">
            {{ __('client_portal.email.magic_link_cta') }}
        </a>
    </p>
    <p>{{ __('client_portal.email.otp_expires', ['minutes' => $expiresMinutes]) }}</p>
</div>
