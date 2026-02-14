<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>VeloriaCRM — умная CRM для соло-мастеров</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
        <style>
            :root {
                color-scheme: light;
                font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                line-height: 1.6;
                font-size: 16px;
                --brand: #6b4eff;
                --brand-dark: #5536f0;
                --text: #1f2430;
                --muted: #5b6478;
                --bg: #f5f7fb;
                --card-bg: #ffffff;
                --border: #e3e8f4;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                background: radial-gradient(circle at top left, rgba(107, 78, 255, 0.08), transparent 55%), var(--bg);
                color: var(--text);
                display: flex;
                flex-direction: column;
            }

            header {
                max-width: 1100px;
                margin: 0 auto;
                width: 100%;
                padding: 24px 24px 0;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .logo {
                font-weight: 700;
                font-size: 1.25rem;
                letter-spacing: 0.04em;
            }

            .actions {
                display: flex;
                gap: 12px;
            }

            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 999px;
                padding: 12px 24px;
                font-weight: 600;
                text-decoration: none;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .btn-primary {
                background: var(--brand);
                color: white;
                box-shadow: 0 12px 30px rgba(107, 78, 255, 0.25);
            }

            .btn-primary:hover {
                background: var(--brand-dark);
                transform: translateY(-1px);
            }

            .btn-secondary {
                border: 1px solid var(--border);
                color: var(--text);
                background: rgba(255, 255, 255, 0.8);
            }

            .btn-secondary:hover {
                border-color: var(--brand);
                color: var(--brand);
            }
            .btn-danger {
                border: 1px solid #f2c8cf;
                background: #fff5f7;
                color: #bb2d3b;
            }

            .btn-danger:hover {
                border-color: #bb2d3b;
                color: #bb2d3b;
            }

            main {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 40px 24px 72px;
            }

            .hero {
                max-width: 1100px;
                width: 100%;
                display: grid;
                gap: 56px;
                grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
                align-items: center;
            }

            .hero-copy h1 {
                font-size: clamp(2.4rem, 4vw, 3.2rem);
                line-height: 1.15;
                margin-bottom: 18px;
            }

            .hero-copy p {
                margin: 0 0 28px;
                color: var(--muted);
                font-size: 1.1rem;
            }

            .features {
                display: grid;
                gap: 16px;
            }

            .feature {
                background: var(--card-bg);
                border: 1px solid var(--border);
                border-radius: 16px;
                padding: 20px 22px;
                box-shadow: 0 18px 40px rgba(25, 35, 56, 0.08);
            }

            .feature h3 {
                margin: 0 0 8px;
                font-size: 1.05rem;
            }

            .feature p {
                margin: 0;
                color: var(--muted);
            }

            footer {
                text-align: center;
                padding: 32px 24px;
                color: var(--muted);
                font-size: 0.9rem;
            }

            @media (max-width: 640px) {
                header {
                    flex-direction: column;
                    gap: 16px;
                }

                .actions {
                    width: 100%;
                    justify-content: center;
                }

                .btn {
                    flex: 1;
                }
            }
        </style>
    </head>
    <body>
        <header>
            <div class="logo">VeloriaCRM</div>
            <div class="actions">
                @if(!empty($isAuthenticated))
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">{{ __('menu.dashboard') }}</a>
                    <button type="button" class="btn btn-danger" data-welcome-logout>{{ __('navigation.logout') }}</button>
                @else
                    <a href="{{ url('/login') }}" class="btn btn-secondary">{{ __('auth.login') }}</a>
                    <a href="{{ url('/register') }}" class="btn btn-primary">{{ __('auth.create_account') }}</a>
                @endif
            </div>
        </header>
        <main>
            <section class="hero">
                <div class="hero-copy">
                    <h1>Умная CRM для соло-мастеров бьюти</h1>
                    <p>
                        VeloriaCRM — ассистент с ИИ, который сам напоминает клиентам, предсказывает неявки
                        и помогает заполнять пустые слоты. Считает маржу за час, показывает сложные визиты,
                        предлагает апсейлы и микро-обучение по трендам.
                    </p>
                    <div class="actions">
                        @if(!empty($isAuthenticated))
                            <a href="{{ url('/dashboard') }}" class="btn btn-primary">{{ __('menu.dashboard') }}</a>
                            <button type="button" class="btn btn-danger" data-welcome-logout>{{ __('navigation.logout') }}</button>
                        @else
                            <a href="{{ url('/register') }}" class="btn btn-primary">{{ __('auth.create_account') }}</a>
                            <a href="{{ url('/login') }}" class="btn btn-secondary">{{ __('auth.login') }}</a>
                        @endif
                    </div>
                </div>
                <div class="features">
                    <article class="feature">
                        <h3>ИИ-ассистент в базе</h3>
                        <p>Авто-напоминания клиентам, прогнозы неявок и рекомендации по заполнению расписания.</p>
                    </article>
                    <article class="feature">
                        <h3>Финансовая прозрачность</h3>
                        <p>Маржа/час, сложные визиты и подсказки по апсейлам помогают зарабатывать больше.</p>
                    </article>
                    <article class="feature">
                        <h3>Готовность к росту</h3>
                        <p>Работа строго через REST API — подключайте мобильное приложение и сторонние сервисы без ограничений.</p>
                    </article>
                </div>
            </section>
        </main>
        <footer>
            © {{ date('Y') }} VeloriaCRM. Подходит для мастеров маникюра, визажистов, парикмахеров и всех, кто ценит время.
        </footer>
        <script>
            (function () {
                var logoutButtons = document.querySelectorAll('[data-welcome-logout]');
                if (!logoutButtons.length) {
                    return;
                }

                function getCookie(name) {
                    var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                    return match ? match[1] : null;
                }

                function deleteCookie(name) {
                    document.cookie = name + '=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT;';
                    document.cookie = name + '=; path=/; Max-Age=0;';
                }

                function logout() {
                    var headers = {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept-Language': document.documentElement.lang || 'en'
                    };

                    var token = getCookie('token');
                    if (token) {
                        headers['Authorization'] = 'Bearer ' + token;
                    }

                    fetch('/api/v1/logout', {
                        method: 'POST',
                        headers: headers
                    }).finally(function () {
                        deleteCookie('token');
                        window.location.href = '/';
                    });
                }

                logoutButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        if (button.disabled) {
                            return;
                        }
                        button.disabled = true;
                        logout();
                    });
                });
            })();
        </script>
    </body>
</html>

