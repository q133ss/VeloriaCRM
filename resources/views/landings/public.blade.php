@php
    $settings = $landing->settings ?? [];
    $colorMap = [
        'indigo' => '#6366f1',
        'emerald' => '#10b981',
        'sunset' => '#f97316',
        'midnight' => '#0f172a',
    ];
    $backgroundPresets = [
        'preset' => 'linear-gradient(135deg, rgba(99,102,241,0.9), rgba(14,165,233,0.9))',
        'midnight' => 'linear-gradient(120deg, rgba(15,23,42,0.95), rgba(30,64,175,0.85))',
        'sunset' => 'linear-gradient(120deg, rgba(251,113,133,0.9), rgba(251,191,36,0.85))',
        'emerald' => 'linear-gradient(120deg, rgba(16,185,129,0.95), rgba(34,197,94,0.85))',
    ];
    $primaryColor = $colorMap[$settings['primary_color'] ?? 'indigo'] ?? '#6366f1';
    $backgroundType = $settings['background_type'] ?? 'preset';
    $backgroundValue = $settings['background_value'] ?? null;
    $backgroundStyle = $backgroundType === 'upload' && $backgroundValue
        ? "url('" . e($backgroundValue) . "') center/cover no-repeat"
        : ($backgroundPresets[$backgroundValue ?? 'preset'] ?? $backgroundPresets['preset']);
@endphp
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $landing->title }}</title>
    <style>
        :root {
            --primary-color: {{ $primaryColor }};
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: {{ $backgroundStyle }};
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: stretch;
            justify-content: center;
            padding: 0;
        }
        .landing-shell {
            width: 100%;
            max-width: 960px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            box-shadow: 0 30px 60px rgba(15, 23, 42, 0.12);
            border-radius: 24px;
            margin: 40px 20px;
            overflow: hidden;
        }
        .landing-header {
            padding: 48px;
            background: rgba(255, 255, 255, 0.7);
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        }
        .landing-content {
            padding: 48px;
        }
        .badge-preview {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            background: rgba(148, 163, 184, 0.2);
            color: rgba(15, 23, 42, 0.7);
            padding: 6px 12px;
            border-radius: 999px;
            margin-bottom: 24px;
        }
        @media (max-width: 768px) {
            .landing-header,
            .landing-content {
                padding: 32px;
            }
        }
    </style>
</head>
<body>
    <main class="landing-shell">
        <header class="landing-header">
            @if($isPreview)
                <div class="badge-preview">{{ __('landings.public.preview_badge') }}</div>
            @endif
            <h1 style="margin: 0; font-size: 42px; color: var(--primary-color);">{{ $landing->title }}</h1>
        </header>
        <section class="landing-content">
            @include($template, ['landing' => $landing, 'settings' => $settings, 'primaryColor' => $primaryColor])
        </section>
    </main>
</body>
</html>
