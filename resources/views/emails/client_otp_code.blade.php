<div style="font-family: Arial, sans-serif; line-height: 1.5;">
    <p>{{ __('client_portal.email.otp_intro') }}</p>
    <p style="font-size: 20px; font-weight: bold; letter-spacing: 2px;">{{ $code }}</p>
    <p>{{ __('client_portal.email.otp_expires', ['minutes' => $expiresMinutes]) }}</p>
</div>

