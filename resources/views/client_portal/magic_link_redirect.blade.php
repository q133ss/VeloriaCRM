{{--
    Browser landing page for the magic-link email's https App Link
    (see App\Services\ClientPortal\ClientPortalAuthService::buildMagicLink()).

    Android opens the app directly here when the link is App-Link-verified
    (see /.well-known/assetlinks.json). Everyone else — unverified Android,
    a desktop client, a link-preview bot — lands on this page instead, which
    immediately retries via the `veloriaclient://` custom scheme and shows
    the code as a manual fallback.
--}}
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('client_portal.redirect.title') }} — Veloria</title>
    <link rel="icon" type="image/x-icon" href="/assets/img/favicon/favicon.ico">
    <meta http-equiv="refresh" content="0; url={{ $appSchemeLink }}">
    <style>
        :root {
            --rd-bg: #f6f6f8;
            --rd-surface: #ffffff;
            --rd-text: #232333;
            --rd-muted: #6f6f85;
            --rd-line: #e6e6ee;
            --rd-accent: #e400a5;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --rd-bg: #232333;
                --rd-surface: #2b2c40;
                --rd-text: #e4e4ee;
                --rd-muted: #a5a5bd;
                --rd-line: #3b3c53;
                --rd-accent: #ff3fc0;
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--rd-bg);
            color: var(--rd-text);
        }

        .rd-card {
            width: 100%;
            max-width: 420px;
            background: var(--rd-surface);
            border: 1px solid var(--rd-line);
            border-radius: 18px;
            padding: 32px 28px;
            text-align: center;
        }

        .rd-spinner {
            width: 36px;
            height: 36px;
            margin: 0 auto 20px;
            border-radius: 50%;
            border: 3px solid var(--rd-line);
            border-top-color: var(--rd-accent);
            animation: rd-spin 0.8s linear infinite;
        }

        @keyframes rd-spin {
            to { transform: rotate(360deg); }
        }

        h1 { margin: 0 0 8px; font-size: 20px; font-weight: 700; }

        p { margin: 0 0 20px; color: var(--rd-muted); line-height: 1.5; }

        .rd-button {
            display: inline-block;
            width: 100%;
            padding: 14px 20px;
            border-radius: 12px;
            background: var(--rd-accent);
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
        }

        .rd-code-block {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--rd-line);
        }

        .rd-code {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 6px;
            margin: 8px 0 0;
        }

        .rd-footnote {
            margin-top: 20px;
            font-size: 13px;
            color: var(--rd-muted);
        }
    </style>
</head>
<body>
    <div class="rd-card">
        <div class="rd-spinner" aria-hidden="true"></div>
        <h1>{{ __('client_portal.redirect.opening') }}</h1>

        <p>
            <a class="rd-button" href="{{ $appSchemeLink }}">{{ __('client_portal.redirect.button') }}</a>
        </p>

        @if ($code !== '')
            <div class="rd-code-block">
                <p style="margin-bottom: 4px;">{{ __('client_portal.redirect.code_hint') }}</p>
                <p class="rd-code">{{ $code }}</p>
            </div>
        @endif

        <p class="rd-footnote">{{ __('client_portal.redirect.no_app') }}</p>
    </div>

    <script>
        window.location.href = @json($appSchemeLink);
    </script>
</body>
</html>
