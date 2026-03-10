@php
    $badge = $badge ?? __('analytics.smart_lock.badge');
    $title = $title ?? __('analytics.smart_lock.title');
    $description = $description ?? __('analytics.smart_lock.description');
    $cta = $cta ?? __('analytics.smart_lock.cta');
    $href = $href ?? url('/subscription');
    $icon = $icon ?? 'ri ri-vip-crown-line';
    $wrapperClass = $wrapperClass ?? '';
    $buttonClass = $buttonClass ?? 'btn btn-primary';
    $previewCount = max(1, (int) ($previewCount ?? 3));
@endphp

@once
    <style>
        .elite-lock-card {
            display: grid;
            gap: 1rem;
            align-items: center;
            padding: 1.15rem;
            border-radius: 1.2rem;
            border: 1px dashed rgba(var(--bs-primary-rgb, 255, 0, 252), 0.22);
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08), transparent 34%),
                rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.6);
        }

        .elite-lock-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(180px, 0.8fr);
            gap: 1rem;
        }

        .elite-lock-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.4rem 0.8rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08);
            color: var(--bs-primary);
            font-size: 0.78rem;
            font-weight: 700;
        }

        .elite-lock-preview {
            display: grid;
            gap: 0.75rem;
            padding: 1rem;
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.03);
        }

        .elite-lock-preview-pill {
            min-height: 2.8rem;
            border-radius: 0.9rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.05);
        }

        html[data-bs-theme="dark"] .elite-lock-card {
            background: rgba(20, 23, 34, 0.84);
        }

        html[data-bs-theme="dark"] .elite-lock-preview,
        html[data-bs-theme="dark"] .elite-lock-preview-pill {
            background: rgba(255, 255, 255, 0.03);
        }

        @media (max-width: 991.98px) {
            .elite-lock-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endonce

<div class="elite-lock-card {{ $wrapperClass }}">
    <div class="elite-lock-grid">
        <div>
            <span class="elite-lock-badge">
                <i class="{{ $icon }}"></i>
                {{ $badge }}
            </span>
            <h3 class="h5 mt-3 mb-2">{{ $title }}</h3>
            <p class="text-muted mb-3">{{ $description }}</p>
            <a href="{{ $href }}" class="{{ $buttonClass }}">
                {{ $cta }}
            </a>
        </div>
        <div class="elite-lock-preview" aria-hidden="true">
            @for ($i = 0; $i < $previewCount; $i++)
                <div class="elite-lock-preview-pill"></div>
            @endfor
        </div>
    </div>
</div>
