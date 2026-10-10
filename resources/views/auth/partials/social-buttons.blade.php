<div class="d-grid gap-3">
    <a
        href="{{ route('social.redirect', ['provider' => 'vkid']) }}"
        class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2"
        aria-label="{{ __('auth.continue_with_vkid') }}"
    >
        <i class="icon-base ri ri-vk-fill icon-18px"></i>
        <span>{{ __('auth.continue_with_vkid') }}</span>
    </a>

    <a
        href="{{ route('social.redirect', ['provider' => 'yandex']) }}"
        class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2"
        aria-label="{{ __('auth.continue_with_yandex') }}"
    >
        <i class="icon-base ri ri-mail-fill icon-18px"></i>
        <span>{{ __('auth.continue_with_yandex') }}</span>
    </a>
</div>
