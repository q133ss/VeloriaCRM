@php($vkAppId = (int) config('services.vkid.client_id'))
<div class="d-grid gap-3">
    <div id="vkid-button" class="d-flex justify-content-center">
        @unless ($vkAppId)
            <button type="button" class="btn btn-outline-primary w-100" disabled>
                {{ __('auth.social_login_not_configured', ['provider' => __('auth.providers.vkid')]) }}
            </button>
        @endunless
    </div>

    <a
        href="{{ route('social.redirect', ['provider' => 'yandex']) }}"
        class="btn d-flex align-items-center justify-content-center gap-2 text-white"
        style="background:#fc3f1d;border-color:#fc3f1d;min-height:44px;"
        aria-label="{{ __('auth.continue_with_yandex') }}"
    >
        <span class="fw-bold" aria-hidden="true">Я</span>
        <span>{{ __('auth.continue_with_yandex') }}</span>
    </a>
</div>

@if ($vkAppId)
    <div id="vkid-error" class="invalid-feedback d-block text-center mt-3"></div>
    <script src="{{ asset('assets/vendor/libs/vkid/sdk-2.6.9.js') }}"></script>
    <script>
        (function () {
            var errorBox = document.getElementById('vkid-error');
            var defaultError = @json(__('auth.social_login_failed', ['provider' => __('auth.providers.vkid')]));
            var fail = function (message) { errorBox.textContent = message || defaultError; };

            if (!('VKIDSDK' in window)) { fail(); return; }
            var VKID = window.VKIDSDK;

            VKID.Config.init({
                app: {{ $vkAppId }},
                redirectUrl: @json(config('services.vkid.redirect') ?: url('/auth/vkid/callback')),
                responseMode: VKID.ConfigResponseMode.Callback,
                source: VKID.ConfigSource.LOWCODE,
                scope: 'vkid.personal_info email'
            });

            var theme = document.documentElement.getAttribute('data-bs-theme');
            var dark = theme === 'dark'
                || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

            new VKID.OneTap().render({
                container: document.getElementById('vkid-button'),
                showAlternativeLogin: false,
                scheme: dark ? VKID.Scheme.DARK : VKID.Scheme.LIGHT,
                lang: document.documentElement.lang === 'en' ? VKID.Languages.ENG : VKID.Languages.RUS
            })
            .on(VKID.WidgetEvents.ERROR, function () { fail(); })
            .on(VKID.OneTapInternalEvents.LOGIN_SUCCESS, function (payload) {
                VKID.Auth.exchangeCode(payload.code, payload.device_id)
                    .then(function (data) {
                        return fetch('/api/v1/auth/vkid', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'Accept-Language': document.documentElement.lang
                            },
                            body: JSON.stringify({ access_token: data.access_token })
                        });
                    })
                    .then(function (response) {
                        return response.json().catch(function () { return {}; }).then(function (result) {
                            if (!response.ok || !result.token) {
                                fail(result.error && result.error.message);
                                return;
                            }
                            document.cookie = 'token=' + result.token + '; path=/';
                            window.location.href = '/dashboard';
                        });
                    })
                    .catch(function () { fail(); });
            });
        })();
    </script>
@endif
