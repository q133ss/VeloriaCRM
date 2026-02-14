<!doctype html>

<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="layout-navbar-fixed layout-menu-fixed layout-compact"
    dir="ltr"
    data-skin="default"
    data-bs-theme="light"
    data-assets-path="/assets/"
    data-template="vertical-menu-template-starter"
    data-pusher-key="{{ config('broadcasting.connections.pusher.key') }}"
    data-pusher-cluster="{{ config('broadcasting.connections.pusher.options.cluster') }}"
    data-pusher-host="{{ config('broadcasting.connections.pusher.options.host') }}"
    data-pusher-port="{{ config('broadcasting.connections.pusher.options.port') }}"
    data-pusher-scheme="{{ config('broadcasting.connections.pusher.options.scheme') }}">
<head>
    <meta charset="utf-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>@yield('title')</title>

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

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="/assets/vendor/js/helpers.js"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js. -->
    <script src="/assets/vendor/js/template-customizer.js"></script>

    <!--? Config: Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file. -->

    <script src="/assets/js/config.js"></script>
    @yield('meta')
</head>

<body>
<!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <!-- Menu -->

        <aside id="layout-menu" class="layout-menu menu-vertical menu">
            <div class="app-brand demo">
                <a href="{{route('dashboard')}}" class="app-brand-link">
              <span class="app-brand-logo demo">
                <span class="text-primary">
                  <!-- <svg width="32" height="18" viewBox="0 0 38 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M30.0944 2.22569C29.0511 0.444187 26.7508 -0.172113 24.9566 0.849138C23.1623 1.87039 22.5536 4.14247 23.5969 5.92397L30.5368 17.7743C31.5801 19.5558 33.8804 20.1721 35.6746 19.1509C37.4689 18.1296 38.0776 15.8575 37.0343 14.076L30.0944 2.22569Z"
                        fill="currentColor" />
                    <path
                        d="M30.171 2.22569C29.1277 0.444187 26.8274 -0.172113 25.0332 0.849138C23.2389 1.87039 22.6302 4.14247 23.6735 5.92397L30.6134 17.7743C31.6567 19.5558 33.957 20.1721 35.7512 19.1509C37.5455 18.1296 38.1542 15.8575 37.1109 14.076L30.171 2.22569Z"
                        fill="url(#paint0_linear_2989_100980)"
                        fill-opacity="0.4" />
                    <path
                        d="M22.9676 2.22569C24.0109 0.444187 26.3112 -0.172113 28.1054 0.849138C29.8996 1.87039 30.5084 4.14247 29.4651 5.92397L22.5251 17.7743C21.4818 19.5558 19.1816 20.1721 17.3873 19.1509C15.5931 18.1296 14.9843 15.8575 16.0276 14.076L22.9676 2.22569Z"
                        fill="currentColor" />
                    <path
                        d="M14.9558 2.22569C13.9125 0.444187 11.6122 -0.172113 9.818 0.849138C8.02377 1.87039 7.41502 4.14247 8.45833 5.92397L15.3983 17.7743C16.4416 19.5558 18.7418 20.1721 20.5361 19.1509C22.3303 18.1296 22.9391 15.8575 21.8958 14.076L14.9558 2.22569Z"
                        fill="currentColor" />
                    <path
                        d="M14.9558 2.22569C13.9125 0.444187 11.6122 -0.172113 9.818 0.849138C8.02377 1.87039 7.41502 4.14247 8.45833 5.92397L15.3983 17.7743C16.4416 19.5558 18.7418 20.1721 20.5361 19.1509C22.3303 18.1296 22.9391 15.8575 21.8958 14.076L14.9558 2.22569Z"
                        fill="url(#paint1_linear_2989_100980)"
                        fill-opacity="0.4" />
                    <path
                        d="M7.82901 2.22569C8.87231 0.444187 11.1726 -0.172113 12.9668 0.849138C14.7611 1.87039 15.3698 4.14247 14.3265 5.92397L7.38656 17.7743C6.34325 19.5558 4.04298 20.1721 2.24875 19.1509C0.454514 18.1296 -0.154233 15.8575 0.88907 14.076L7.82901 2.22569Z"
                        fill="currentColor" />
                    <defs>
                      <linearGradient
                          id="paint0_linear_2989_100980"
                          x1="5.36642"
                          y1="0.849138"
                          x2="10.532"
                          y2="24.104"
                          gradientUnits="userSpaceOnUse">
                        <stop offset="0" stop-opacity="1" />
                        <stop offset="1" stop-opacity="0" />
                      </linearGradient>
                      <linearGradient
                          id="paint1_linear_2989_100980"
                          x1="5.19475"
                          y1="0.849139"
                          x2="10.3357"
                          y2="24.1155"
                          gradientUnits="userSpaceOnUse">
                        <stop offset="0" stop-opacity="1" />
                        <stop offset="1" stop-opacity="0" />
                      </linearGradient>
                    </defs>
                  </svg> -->
                  <img src="/logo.svg" alt="Veloria">
                </span>
              </span>
                    <span class="app-brand-text demo menu-text fw-semibold ms-2">Veloria</span>
                </a>

                <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M8.47365 11.7183C8.11707 12.0749 8.11707 12.6531 8.47365 13.0097L12.071 16.607C12.4615 16.9975 12.4615 17.6305 12.071 18.021C11.6805 18.4115 11.0475 18.4115 10.657 18.021L5.83009 13.1941C5.37164 12.7356 5.37164 11.9924 5.83009 11.5339L10.657 6.707C11.0475 6.31653 11.6805 6.31653 12.071 6.707C12.4615 7.09747 12.4615 7.73053 12.071 8.121L8.47365 11.7183Z"
                            fill-opacity="0.9" />
                        <path
                            d="M14.3584 11.8336C14.0654 12.1266 14.0654 12.6014 14.3584 12.8944L18.071 16.607C18.4615 16.9975 18.4615 17.6305 18.071 18.021C17.6805 18.4115 17.0475 18.4115 16.657 18.021L11.6819 13.0459C11.3053 12.6693 11.3053 12.0587 11.6819 11.6821L16.657 6.707C17.0475 6.31653 17.6805 6.31653 18.071 6.707C18.4615 7.09747 18.4615 7.73053 18.071 8.121L14.3584 11.8336Z"
                            fill-opacity="0.4" />
                    </svg>
                </a>
            </div>

            <div class="menu-inner-shadow"></div>
            <ul id="main-menu" class="menu-inner py-1">
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link">
                        <i class="menu-icon icon-base ri ri-loader-4-line"></i>
                        <div>{{ __('menu.loading') }}</div>
                    </a>
                </li>
            </ul>
        </aside>

        <div class="menu-mobile-toggler d-xl-none rounded-1">
            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
                <i class="ri ri-menu-line icon-base"></i>
                <i class="ri ri-arrow-right-s-line icon-base"></i>
            </a>
        </div>
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
            <!-- Navbar -->

            <nav
                class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
                id="layout-navbar">
                <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
                    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                        <i class="icon-base ri ri-menu-line icon-22px"></i>
                    </a>
                </div>

                <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
                    <div class="navbar-nav align-items-center">
                        <div class="nav-item dropdown me-2 me-xl-0">
                            <a
                                class="nav-link dropdown-toggle hide-arrow"
                                id="nav-language"
                                href="javascript:void(0);"
                                data-bs-toggle="dropdown"
                                aria-label="{{ __('navigation.language') }}">
                                <i class="icon-base ri ri-global-line icon-22px"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-start" aria-labelledby="nav-language">
                                @foreach (['ru', 'en'] as $locale)
                                    <li>
                                        <form method="POST" action="{{ route('locale.update') }}">
                                            @csrf
                                            <input type="hidden" name="locale" value="{{ $locale }}">
                                            <button
                                                type="submit"
                                                class="dropdown-item d-flex align-items-center {{ app()->getLocale() === $locale ? 'active' : '' }}">
                                                {{ __('navigation.languages.' . $locale) }}
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="nav-item dropdown me-2 me-xl-0">
                            <a
                                class="nav-link dropdown-toggle hide-arrow"
                                id="nav-theme"
                                href="javascript:void(0);"
                                data-bs-toggle="dropdown">
                                <i class="icon-base ri ri-sun-line icon-22px theme-icon-active"></i>
                                <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-start" aria-labelledby="nav-theme-text">
                                <li>
                                    <button
                                        type="button"
                                        class="dropdown-item align-items-center active"
                                        data-bs-theme-value="light"
                                        aria-pressed="false">
                                        <span><i class="icon-base ri ri-sun-line icon-22px me-3" data-icon="sun-line"></i>Light</span>
                                    </button>
                                </li>
                                <li>
                                    <button
                                        type="button"
                                        class="dropdown-item align-items-center"
                                        data-bs-theme-value="dark"
                                        aria-pressed="true">
                        <span
                        ><i class="icon-base ri ri-moon-clear-line icon-22px me-3" data-icon="moon-clear-line"></i
                            >Dark</span
                        >
                                    </button>
                                </li>
                                <li>
                                    <button
                                        type="button"
                                        class="dropdown-item align-items-center"
                                        data-bs-theme-value="system"
                                        aria-pressed="false">
                        <span
                        ><i class="icon-base ri ri-computer-line icon-22px me-3" data-icon="computer-line"></i
                            >System</span
                        >
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <ul class="navbar-nav flex-row align-items-center ms-md-auto">
                        <li class="nav-item dropdown me-2 me-lg-3" data-notifications-root>
                            <a
                                class="nav-link btn-icon dropdown-toggle hide-arrow position-relative"
                                href="javascript:void(0);"
                                data-bs-toggle="dropdown"
                                data-notifications-toggle
                            >
                                <i class="icon-base ri ri-notification-3-line icon-22px"></i>
                                <span
                                    class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle d-none"
                                    data-notifications-count
                                >0</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end w-px-320" data-notifications-menu>
                                <li class="dropdown-header d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold">Уведомления</span>
                                    <button type="button" class="btn btn-link btn-sm p-0" data-notifications-mark-all>
                                        Отметить все
                                    </button>
                                </li>
                                <li><div class="dropdown-divider"></div></li>
                                <li>
                                    <div class="list-group list-group-flush" data-notifications-list>
                                        <span class="dropdown-item-text text-muted small py-3 text-center">
                                            Загрузка...
                                        </span>
                                    </div>
                                </li>
                                <li><div class="dropdown-divider"></div></li>
                                <li>
                                    <a class="dropdown-item text-center" href="/notifications">Показать все</a>
                                </li>
                            </ul>
                        </li>
                        <!-- User -->
                        <li class="nav-item navbar-dropdown dropdown-user dropdown">
                            <a
                                class="nav-link dropdown-toggle hide-arrow p-0"
                                href="javascript:void(0);"
                                data-bs-toggle="dropdown">
                                <div class="avatar avatar-online">
                                    <span class="avatar-initial rounded-circle bg-primary text-white fw-semibold" data-user-initial>
                                        ?
                                    </span>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="#">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar avatar-online">
                                                    <span class="avatar-initial rounded-circle bg-primary text-white fw-semibold" data-user-initial>
                                                        ?
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0" data-user-name></h6>
                                                <small class="text-body-secondary" data-user-plan></small>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <div class="dropdown-divider my-1"></div>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/settings">
                                        <i class="icon-base ri ri-settings-4-line icon-22px me-3"></i><span>{{ __('navigation.settings') }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('subscription') }}">
                                        <span class="d-flex align-items-center align-middle">
                                            <i class="flex-shrink-0 icon-base ri ri-bank-card-line icon-22px me-3"></i>
                                            <span class="flex-grow-1 align-middle ms-1">{{ __('navigation.subscription') }}</span>
                                        </span>
                                    </a>
                                </li>
                                <li>
                                    <div class="dropdown-divider my-1"></div>
                                </li>
                                <li>
                                    <div class="d-grid px-4 pt-2 pb-1">
                                        <button type="button" class="btn btn-danger d-flex align-items-center justify-content-center" data-logout-button>
                                            <small class="align-middle">{{ __('navigation.logout') }}</small>
                                            <i class="icon-base ri ri-logout-box-r-line ms-2 icon-16px"></i>
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        <!--/ User -->
                    </ul>
                </div>
            </nav>

            <!-- / Navbar -->

            <!-- Content wrapper -->
            <div class="content-wrapper">
                <!-- Content -->
                <div class="container-xxl flex-grow-1 container-p-y">
                    @yield('content')
                </div>
                <!-- / Content -->

                <!-- Footer -->
                <footer class="content-footer footer bg-footer-theme">
                    <div class="container-xxl">
                        <div
                            class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
                            <div class="mb-2 mb-md-0">
                                &#169;
                                <script>
                                    document.write(new Date().getFullYear());
                                </script>
                                
                            </div>
                            <div class="d-none d-lg-inline-block">
                                <a href="/terms" target="_blank" class="footer-link me-4"
                                >Пользовательское соглашение</a
                                >
                                <a
                                    href="/policy"
                                    target="_blank"
                                    class="footer-link me-4"
                                >Политика конфиденциальности</a
                                >
                            </div>
                        </div>
                    </div>
                </footer>
                <!-- / Footer -->

                <div class="content-backdrop fade"></div>
            </div>
            <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
    </div>

    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>

    <!-- Drag Target Area To SlideIn Menu On Small Screens -->
    <div class="drag-target"></div>
</div>
<!-- / Layout wrapper -->

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

<script src="/assets/vendor/js/menu.js"></script>

<!-- endbuild -->

<!-- Vendors JS -->

<!-- Main JS -->

<script src="/assets/js/main.js"></script>
<script src="https://js.pusher.com/8.2/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var MENU_LABELS = @json(trans('menu'));

    function menuItem(key, href, icon) {
        return {
            label: MENU_LABELS[key] || key,
            href: href,
            icon: icon
        };
    }

    var BASE_MENU = [
        menuItem('dashboard', '/dashboard', 'ri-dashboard-2-line'),
        menuItem('calendar', '/calendar', 'ri-calendar-line'),
        menuItem('orders', '/orders', 'ri-calendar-check-line'),
        menuItem('clients', '/clients', 'ri-user-3-line'),
        menuItem('services', '/services', 'ri-scissors-2-line'),
        menuItem('invoices', '/invoices', 'ri-bill-line'),
        menuItem('messages', '/messages', 'ri-message-3-line'),
        menuItem('analytics', '/analytics', 'ri-bar-chart-line'),
        menuItem('integrations', '/integrations', 'ri-puzzle-line'),
        menuItem('settings', '/settings', 'ri-settings-3-line'),
        menuItem('help', '/help', 'ri-question-line')
    ];

    var PLAN_ADDITIONS = {
        lite: [],
        pro: [
            menuItem('landings', '/landings', 'ri-layout-4-line'),
            menuItem('marketing', '/marketing', 'ri-megaphone-line'),
            menuItem('learning', '/learning', 'ri-lightbulb-line'),
            menuItem('veloryStudio', '/velory', 'ri-robot-line')
        ],
        elite: [
            menuItem('landings', '/landings', 'ri-layout-4-line'),
            menuItem('marketing', '/marketing', 'ri-megaphone-line'),
            menuItem('learning', '/learning', 'ri-lightbulb-line'),
            menuItem('automations', '/automations', 'ri-magic-line'),
            menuItem('veloryStudio', '/velory', 'ri-robot-line')
        ]
    };

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return match ? match[1] : null;
    }

    function deleteCookie(name) {
        document.cookie = name + '=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT;';
        document.cookie = name + '=; path=/; Max-Age=0;';
    }

    var token = getCookie('token');
    var headers = {
        'Accept': 'application/json',
        'Accept-Language': document.documentElement.lang || 'en'
    };
    if (token) headers['Authorization'] = 'Bearer ' + token;

    // DOM-элементы выпадающего списка уведомлений.
    var notificationsRoot = document.querySelector('[data-notifications-root]');
    var notificationsList = document.querySelector('[data-notifications-list]');
    var notificationsCount = document.querySelector('[data-notifications-count]');
    var notificationsMarkAll = document.querySelector('[data-notifications-mark-all]');
    var notificationsState = {
        items: [],
        unreadCount: 0,
        echo: null,
        channel: null,
        userId: null,
    };

    // Экранируем пользовательский ввод перед вставкой в HTML.
    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Форматируем дату уведомления для отображения в шапке.
    function formatNotificationDate(isoString) {
        if (!isoString) {
            return '';
        }

        try {
            return new Date(isoString).toLocaleString();
        } catch (error) {
            return isoString;
        }
    }

    // Рисуем список уведомлений в выпадающем меню рядом с аватаром.
    function renderNotificationDropdown() {
        if (!notificationsList) {
            return;
        }

        if (!notificationsState.items.length) {
            notificationsList.innerHTML = '<span class="dropdown-item-text text-muted small py-3 text-center">Непрочитанных уведомлений нет</span>';
            return;
        }

        notificationsList.innerHTML = notificationsState.items.map(function (item) {
            var targetUrl = item.action_url || '/notifications';

            return (
                '<a class="list-group-item list-group-item-action py-3" href="' + targetUrl + '">' +
                '<div class="d-flex justify-content-between align-items-start">' +
                '<div class="me-2">' +
                '<div class="fw-semibold text-truncate">' + escapeHtml(item.title || 'Без заголовка') + '</div>' +
                '<div class="small text-muted mt-1">' + escapeHtml(item.message || '') + '</div>' +
                '</div>' +
                '<small class="text-muted ms-2">' + formatNotificationDate(item.created_at) + '</small>' +
                '</div>' +
                '</a>'
            );
        }).join('');
    }

    // Показываем/скрываем бейдж с количеством непрочитанных.
    function updateNotificationBadge(count) {
        notificationsState.unreadCount = count;

        if (!notificationsCount) {
            return;
        }

        if (count > 0) {
            notificationsCount.textContent = count > 99 ? '99+' : String(count);
            notificationsCount.classList.remove('d-none');
        } else {
            notificationsCount.classList.add('d-none');
        }
    }

    // Создаём (один раз) экземпляр Laravel Echo с авторизацией по Bearer токену.
    function ensureEchoInstance() {
        if (notificationsState.echo) {
            return notificationsState.echo;
        }

        if (typeof window.Echo !== 'function') {
            return null;
        }

        var root = document.documentElement;
        var key = root.getAttribute('data-pusher-key');
        if (!key) {
            return null;
        }

        var cluster = root.getAttribute('data-pusher-cluster') || undefined;
        var host = root.getAttribute('data-pusher-host') || undefined;
        var port = root.getAttribute('data-pusher-port');
        var scheme = root.getAttribute('data-pusher-scheme') || 'https';
        var forceTLS = scheme !== 'http';

        var echoOptions = {
            broadcaster: 'pusher',
            key: key,
            cluster: cluster,
            forceTLS: forceTLS,
            encrypted: true,
            authorizer: function (channel) {
                return {
                    authorize: function (socketId, callback) {
                        fetch('/broadcasting/auth', {
                            method: 'POST',
                            headers: Object.assign({}, headers, {
                                'Content-Type': 'application/json',
                            }),
                            body: JSON.stringify({
                                socket_id: socketId,
                                channel_name: channel.name,
                            }),
                        })
                            .then(function (response) { return response.json(); })
                            .then(function (data) { callback(false, data); })
                            .catch(function (error) { callback(true, error); });
                    },
                };
            },
            enabledTransports: ['ws', 'wss'],
        };

        if (host) {
            echoOptions.wsHost = host;
            echoOptions.wssHost = host;
        }

        if (port) {
            var parsedPort = parseInt(port, 10);
            if (!Number.isNaN(parsedPort)) {
                echoOptions.wsPort = parsedPort;
                echoOptions.wssPort = parsedPort;
            }
        }

        notificationsState.echo = new window.Echo(echoOptions);

        return notificationsState.echo;
    }

    // Загружаем последние непрочитанные уведомления через REST API.
    function loadUnreadNotifications() {
        if (!notificationsRoot || notificationsRoot.classList.contains('d-none')) {
            return;
        }

        fetch('/api/v1/notifications?unread=1&per_page=5', { headers: headers })
            .then(function (response) { return response.ok ? response.json() : Promise.reject(); })
            .then(function (payload) {
                notificationsState.items = Array.isArray(payload.data) ? payload.data.slice(0, 5) : [];
                renderNotificationDropdown();
                updateNotificationBadge(payload.unread_count || 0);
            })
            .catch(function () {
                if (notificationsList) {
                    notificationsList.innerHTML = '<span class="dropdown-item-text text-danger small py-3 text-center">Не удалось загрузить уведомления</span>';
                }
            });
    }

    // Отмечаем все уведомления прочитанными через API.
    function markAllNotificationsRead() {
        fetch('/api/v1/notifications/mark-as-read', {
            method: 'POST',
            headers: Object.assign({}, headers, {
                'Content-Type': 'application/json',
            }),
            body: JSON.stringify({ ids: [] }),
        })
            .then(function () {
                notificationsState.items = [];
                updateNotificationBadge(0);
                renderNotificationDropdown();
                loadUnreadNotifications();
            })
            .catch(function () {})
            .finally(function () {
                if (notificationsMarkAll) {
                    notificationsMarkAll.disabled = false;
                }
            });
    }

    // Подписываемся на приватный канал пользователя и обрабатываем пуш-события от Pusher.
    function subscribeToNotifications(userId) {
        var echo = ensureEchoInstance();
        if (!echo || !userId) {
            return;
        }

        if (notificationsState.channel) {
            echo.leave('notifications.' + notificationsState.userId);
        }

        notificationsState.userId = userId;
        notificationsState.channel = echo.private('notifications.' + userId);
        notificationsState.channel.listen('.UserNotificationCreated', function (event) {
            var item = {
                id: event.id,
                title: event.title,
                message: event.message,
                created_at: event.created_at,
            };
            notificationsState.items.unshift(item);
            notificationsState.items = notificationsState.items.slice(0, 5);
            updateNotificationBadge(notificationsState.unreadCount + 1);
            renderNotificationDropdown();
        });
    }

    if (notificationsMarkAll) {
        notificationsMarkAll.addEventListener('click', function (event) {
            event.preventDefault();
            if (notificationsMarkAll.disabled) {
                return;
            }
            notificationsMarkAll.disabled = true;
            markAllNotificationsRead();
        });
    }

    if (notificationsRoot) {
        notificationsRoot.addEventListener('show.bs.dropdown', function () {
            loadUnreadNotifications();
        });
    }

    fetch('/api/v1/auth/me', { headers: headers })
        .then(function (res) { return res.ok ? res.json() : {}; })
        .then(function (data) {
            var user = data.user || {};
            var userName = typeof user.name === 'string' ? user.name : '';
            var trimmedName = userName.trim();
            var userInitial = trimmedName ? trimmedName.charAt(0).toUpperCase() : '?';

            var nameTarget = document.querySelector('[data-user-name]');
            if (nameTarget) {
                nameTarget.textContent = trimmedName;
            }

            document.querySelectorAll('[data-user-initial]').forEach(function (el) {
                el.textContent = userInitial;
            });

            var slug = user.plan && user.plan.slug ? String(user.plan.slug).toLowerCase() : 'lite';
            if (['lite', 'pro', 'elite'].indexOf(slug) === -1) slug = 'lite';

            var planTarget = document.querySelector('[data-user-plan]');
            if (planTarget) {
                planTarget.textContent = slug;
            }

            var menu = BASE_MENU.slice();
            (PLAN_ADDITIONS[slug] || []).forEach(function (item) {
                if (!menu.some(function (m) { return m.href === item.href; })) {
                    menu.push(item);
                }
            });

            var menuEl = document.getElementById('main-menu');
            if (!menuEl) return;
            menuEl.innerHTML = '';
            menu.forEach(function (item) {
                var li = document.createElement('li');
                li.className = 'menu-item';
                var a = document.createElement('a');
                a.className = 'menu-link';
                a.href = item.href;
                a.innerHTML = '<i class="menu-icon icon-base ri ' + item.icon + '"></i><div>' + item.label + '</div>';
                if (window.location.pathname === item.href) {
                    li.classList.add('active');
                }
                li.appendChild(a);
                menuEl.appendChild(li);
            });
            if (!user.id) {
                if (notificationsRoot) {
                    notificationsRoot.classList.add('d-none');
                }
            } else {
                if (notificationsRoot) {
                    notificationsRoot.classList.remove('d-none');
                }
                loadUnreadNotifications();
                subscribeToNotifications(user.id);
            }
        })
        .catch(function () {
            if (notificationsRoot) {
                notificationsRoot.classList.add('d-none');
            }
        });

    var logoutButton = document.querySelector('[data-logout-button]');
    if (logoutButton) {
        logoutButton.addEventListener('click', function (event) {
            event.preventDefault();
            if (logoutButton.disabled) {
                return;
            }

            logoutButton.disabled = true;

            var logoutHeaders = {
                'Accept': 'application/json',
                'Accept-Language': document.documentElement.lang || 'en',
                'X-Requested-With': 'XMLHttpRequest'
            };

            var token = getCookie('token');
            if (token) {
                logoutHeaders['Authorization'] = 'Bearer ' + token;
            }

            var finalizeLogout = function () {
                deleteCookie('token');
                window.location.href = '/login';
            };

            fetch('/api/v1/logout', {
                method: 'POST',
                headers: logoutHeaders
            })
                .then(function () {
                    finalizeLogout();
                })
                .catch(function () {
                    finalizeLogout();
                });
        });
    }
});
</script>
@yield('scripts')
@stack('scripts')
<!-- Page JS -->
</body>
</html>
