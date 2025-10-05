<!doctype html>

<html
    lang="{{ app()->getLocale() }}"
    class="layout-wide customizer-hide"
    dir="ltr"
    data-skin="default"
    data-bs-theme="light"
    data-assets-path="/assets/"
    data-template="vertical-menu-template">
<head>
    <meta charset="utf-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>{{ __('auth.login_title') }}</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="/assets/vendor/fonts/iconify-icons.css" />

    <!-- Core CSS -->
    <!-- build:css assets/vendor/css/theme.css -->

    <link rel="stylesheet" href="/assets/vendor/libs/node-waves/node-waves.css" />

    <link rel="stylesheet" href="/assets/vendor/libs/pickr/pickr-themes.css" />

    <link rel="stylesheet" href="/assets/vendor/css/core.css" />
    <link rel="stylesheet" href="/assets/css/demo.css" />

    <!-- Vendors CSS -->

    <link rel="stylesheet" href="/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- endbuild -->

    <!-- Vendor -->
    <link rel="stylesheet" href="/assets/vendor/libs/@form-validation/form-validation.css" />

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="/assets/vendor/css/pages/page-auth.css" />

    <!-- Helpers -->
    <script src="/assets/vendor/js/helpers.js"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js. -->
    <script src="/assets/vendor/js/template-customizer.js"></script>

    <!--? Config: Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file. -->

    <script src="/assets/js/config.js"></script>
</head>

<body>
<!-- Content -->

<div class="position-relative">
    <div class="authentication-wrapper authentication-basic container-p-y p-4 p-sm-0">
        <div class="authentication-inner py-6">
            <!-- Login -->
            <div class="card p-md-7 p-1">
                <!-- Logo -->
                <div class="app-brand justify-content-center mt-5">
                    <a href="index.html" class="app-brand-link gap-2">
                <span class="app-brand-logo demo">
                  <span class="text-primary">
                    <img src="/logo.svg">
                  </span>
                </span>
                        <span class="app-brand-text demo text-heading fw-semibold">Veloria</span>
                    </a>
                </div>
                <!-- /Logo -->

                <div class="card-body mt-1">
                    @if (session('auth_error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('auth_error') }}
                        </div>
                    @endif
                    <h4 class="mb-1">{{ __('auth.login_heading') }}</h4>
                    <p class="mb-5">{{ __('auth.login_subtitle') }}</p>

                    <form id="formAuthentication" class="mb-5" action="/api/v1/login" method="POST">
                        @csrf
                        <div class="form-floating form-floating-outline mb-5 form-control-validation">
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                placeholder="{{ __('auth.email') }}"
                                required
                                autofocus />
                            <label for="email">{{ __('auth.email') }}</label>
                        </div>
                        <div class="mb-5">
                            <div class="form-password-toggle form-control-validation">
                                <div class="input-group input-group-merge">
                                    <div class="form-floating form-floating-outline">
                                        <input
                                            type="password"
                                            id="password"
                                            class="form-control"
                                            name="password"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                            aria-describedby="password"
                                            required
                                            minlength="8" />
                                        <label for="password">{{ __('auth.password') }}</label>
                                    </div>
                                    <span class="input-group-text cursor-pointer"
                                    ><i class="icon-base ri ri-eye-off-line icon-20px"></i
                                        ></span>
                                </div>
                            </div>
                        </div>
                        <div class="mb-5 d-flex justify-content-between mt-5">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="remember-me" name="remember" />
                                <label class="form-check-label" for="remember-me"> {{ __('auth.remember') }} </label>
                            </div>
                            <a href="/forgot-password" class="float-end mb-1 mt-2">
                                <span>{{ __('auth.forgot_password') }}</span>
                            </a>
                        </div>
                        <div class="mb-5">
                            <button class="btn btn-primary d-grid w-100" type="submit">{{ __('auth.login') }}</button>
                        </div>
                    </form>

                    <p class="text-center mb-5">
                        <span>{{ __('auth.new_here') }}</span>
                        <a href="/register">
                            <span>{{ __('auth.create_account') }}</span>
                        </a>
                    </p>

                    <div class="divider my-5">
                        <div class="divider-text">{{ __('auth.or') }}</div>
                    </div>

                    <div class="d-grid gap-3">
                        <a
                            href="{{ route('social.redirect', ['provider' => 'vkontakte']) }}"
                            class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2"
                            aria-label="{{ __('auth.continue_with_vkontakte') }}"
                        >
                            <i class="icon-base ri ri-vk-fill icon-18px"></i>
                            <span>{{ __('auth.continue_with_vkontakte') }}</span>
                        </a>

                        <a
                            href="{{ route('social.redirect', ['provider' => 'yandex']) }}"
                            class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2"
                            aria-label="{{ __('auth.continue_with_yandex') }}"
                        >
                            <i class="icon-base ri ri-mail-fill icon-18px"></i>
                            <span>{{ __('auth.continue_with_yandex') }}</span>
                        </a>

                        <a
                            href="{{ route('social.redirect', ['provider' => 'google']) }}"
                            class="btn btn-outline-danger d-flex align-items-center justify-content-center gap-2"
                            aria-label="{{ __('auth.continue_with_google') }}"
                        >
                            <i class="icon-base ri ri-google-fill icon-18px"></i>
                            <span>{{ __('auth.continue_with_google') }}</span>
                        </a>
                    </div>
                </div>
            </div>
            <!-- /Login -->
            <img
                alt="mask"
                src="/assets/img/illustrations/auth-basic-login-mask-light.png"
                class="authentication-image d-none d-lg-block"
                data-app-light-img="illustrations/auth-basic-login-mask-light.png"
                data-app-dark-img="illustrations/auth-basic-login-mask-dark.png" />
        </div>
    </div>
</div>

<!-- / Content -->

<!-- Core JS -->

<!-- build:js assets/vendor/js/theme.js  -->

<script src="/assets/vendor/libs/jquery/jquery.js"></script>

<script src="/assets/vendor/libs/popper/popper.js"></script>
<script src="/assets/vendor/js/bootstrap.js"></script>
<script src="/assets/vendor/libs/node-waves/node-waves.js"></script>

<script src="/assets/vendor/libs/@algolia/autocomplete-js.js"></script>

<script src="/assets/vendor/libs/pickr/pickr.js"></script>

<script src="/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

<script src="/assets/vendor/libs/hammer/hammer.js"></script>

<script src="/assets/vendor/libs/i18n/i18n.js"></script>

<script src="/assets/vendor/js/menu.js"></script>

<!-- endbuild -->

<!-- Main JS -->

<script src="/assets/js/main.js"></script>

<!-- Page JS -->
<script src="/assets/js/pages-auth.js"></script>
<script>
document.getElementById('formAuthentication').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = this;
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    const formData = new FormData(form);
    const response = await fetch(form.action, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept-Language': document.documentElement.lang
        },
        body: formData
    });
    const result = await response.json().catch(() => ({}));
    form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    if (!response.ok) {
        const errors = result.error?.fields || {};
        if (Object.keys(errors).length === 0 && result.error?.message) {
            const div = document.createElement('div');
            div.classList.add('invalid-feedback', 'd-block', 'mb-4', 'text-center');
            div.textContent = result.error.message;
            form.prepend(div);
        }
        Object.keys(errors).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                const container = input.closest('.form-control-validation') || input.parentNode;
                const div = document.createElement('div');
                div.classList.add('invalid-feedback', 'd-block');
                div.textContent = errors[key][0];
                container.appendChild(div);
            }
        });
        return;
    }
    if (result.token) {
        document.cookie = 'token=' + result.token + '; path=/';
    }
    window.location.href = '/';
});
</script>
</body>
</html>
