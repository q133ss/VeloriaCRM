@extends('layouts.app')

@section('title', __('analytics.title'))

@section('meta')
    <style>
        .analytics-chart {
            position: relative;
            height: 320px;
        }

        .analytics-chart--share {
            height: 260px;
        }
    </style>
@endsection

@section('content')
    <style>
        .analytics-page {
            --analytics-border: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.12);
            --analytics-shadow: 0 24px 54px -36px rgba(37, 26, 84, 0.42);
        }

        .analytics-page .analytics-hero,
        .analytics-page .analytics-surface,
        .analytics-page .analytics-card {
            border: 1px solid var(--analytics-border);
            border-radius: 1.5rem;
            box-shadow: var(--analytics-shadow);
            background: color-mix(in srgb, var(--bs-card-bg) 96%, transparent);
        }

        .analytics-page .analytics-hero {
            position: relative;
            overflow: hidden;
            padding: 1.5rem;
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.14), transparent 34%),
                linear-gradient(140deg, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.06), rgba(var(--bs-info-rgb, 0, 207, 232), 0.05) 58%, rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.12));
        }

        .analytics-page .analytics-hero::after {
            content: '';
            position: absolute;
            right: -3rem;
            bottom: -4rem;
            width: 12rem;
            height: 12rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08);
            filter: blur(12px);
        }

        .analytics-page .analytics-hero > * {
            position: relative;
            z-index: 1;
        }

        .analytics-page .analytics-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.72);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .analytics-page .analytics-hero .btn {
            white-space: nowrap;
        }

        .analytics-page .analytics-surface,
        .analytics-page .analytics-card {
            padding: 1.25rem;
        }

        .analytics-page .analytics-kpi {
            padding: 1.15rem;
            border-radius: 1.2rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.03);
            height: 100%;
        }

        .analytics-page .analytics-kpi-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .analytics-page .analytics-kpi-note {
            min-height: 1.25rem;
        }

        .analytics-page .analytics-soft-card {
            padding: 1rem;
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.03);
        }

        .analytics-page .analytics-pill-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
        }

        .analytics-page .analytics-pill-list > * {
            margin: 0;
        }

        .analytics-page .analytics-focus-card {
            overflow: hidden;
            background:
                radial-gradient(circle at top left, rgba(var(--bs-warning-rgb, 255, 171, 0), 0.12), transparent 30%),
                linear-gradient(160deg, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.04), rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.12));
        }

        .analytics-page .analytics-section-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: var(--bs-primary);
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.08);
        }

        .analytics-page .analytics-peak-summary {
            position: relative;
            padding: 1.35rem;
            border-radius: 1.35rem;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.72);
            border: 1px solid rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.08);
        }

        .analytics-page .analytics-peak-meta {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .analytics-page .analytics-peak-stat {
            padding: 0.85rem 0.95rem;
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.04);
        }

        .analytics-page .analytics-driver-list {
            display: grid;
            gap: 0.75rem;
        }

        .analytics-page .analytics-driver-item,
        .analytics-page .analytics-action-card {
            padding: 1rem 1.05rem;
            border-radius: 1rem;
            border: 1px solid rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.08);
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.72);
        }

        .analytics-page .analytics-heatmap-shell {
            padding: 1rem;
            border-radius: 1.35rem;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.72);
            border: 1px solid rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.08);
        }

        .analytics-page .analytics-heatmap-grid {
            display: grid;
            grid-template-columns: minmax(76px, auto) repeat(7, minmax(0, 1fr));
            gap: 0.55rem;
            align-items: stretch;
        }

        .analytics-page .analytics-heatmap-label {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 2.8rem;
            padding: 0.55rem;
            text-align: center;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--bs-secondary-color);
        }

        .analytics-page .analytics-heatmap-slot {
            justify-content: flex-start;
            text-align: left;
            padding-inline: 0.2rem 0.7rem;
        }

        .analytics-page .analytics-heatmap-cell {
            position: relative;
            min-height: 4.6rem;
            padding: 0.75rem;
            border-radius: 1rem;
            border: 1px solid rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.05);
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.03);
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .analytics-page .analytics-heatmap-cell:hover {
            transform: translateY(-1px);
            border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.28);
            box-shadow: 0 14px 26px -22px rgba(37, 26, 84, 0.45);
        }

        .analytics-page .analytics-heatmap-cell.is-active {
            border-color: rgba(var(--bs-warning-rgb, 255, 171, 0), 0.45);
            box-shadow: 0 16px 30px -24px rgba(255, 171, 0, 0.45);
        }

        .analytics-page .analytics-heatmap-amount {
            display: block;
            font-size: 0.95rem;
            font-weight: 700;
        }

        .analytics-page .analytics-heatmap-note {
            display: block;
            margin-top: 0.3rem;
            font-size: 0.74rem;
            color: var(--bs-secondary-color);
        }

        .analytics-page .analytics-empty-card {
            display: grid;
            place-items: center;
            min-height: 11rem;
            padding: 1.25rem;
            text-align: center;
            border-radius: 1.35rem;
            border: 1px dashed rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.12);
            background:
                radial-gradient(circle at top center, rgba(var(--bs-warning-rgb, 255, 171, 0), 0.1), transparent 42%),
                rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.025);
        }

        .analytics-page .analytics-empty-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            margin-bottom: 0.85rem;
            border-radius: 999px;
            color: #b7791f;
            background: rgba(var(--bs-warning-rgb, 255, 171, 0), 0.16);
        }

        html[data-bs-theme="dark"] .analytics-page .analytics-focus-card,
        html[data-bs-theme="dark"] .analytics-page .analytics-peak-summary,
        html[data-bs-theme="dark"] .analytics-page .analytics-driver-item,
        html[data-bs-theme="dark"] .analytics-page .analytics-action-card,
        html[data-bs-theme="dark"] .analytics-page .analytics-heatmap-shell {
            background: rgba(20, 23, 34, 0.84);
        }

        html[data-bs-theme="dark"] .analytics-page .analytics-peak-stat,
        html[data-bs-theme="dark"] .analytics-page .analytics-heatmap-cell {
            background: rgba(255, 255, 255, 0.03);
        }

        .analytics-page details.analytics-collapse {
            border-radius: 1.25rem;
            border: 1px solid rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.08);
            background: color-mix(in srgb, var(--bs-card-bg) 98%, transparent);
            box-shadow: var(--analytics-shadow);
        }

        .analytics-page details.analytics-collapse > summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.25rem;
            cursor: pointer;
            list-style: none;
        }

        .analytics-page details.analytics-collapse > summary::-webkit-details-marker {
            display: none;
        }

        .analytics-page details.analytics-collapse > summary::after {
            content: 'Развернуть';
            color: var(--bs-secondary-color);
            font-size: 0.85rem;
            font-weight: 600;
        }

        .analytics-page details.analytics-collapse[open] > summary::after {
            content: 'Свернуть';
        }

        .analytics-page .analytics-collapse-body {
            padding: 0 1.25rem 1.25rem;
        }

        .analytics-page [data-report] {
            display: none !important;
        }

        .analytics-page .table-sm td,
        .analytics-page .table-sm th {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }

        @media (max-width: 991.98px) {
            .analytics-page .analytics-summary-grid > div {
                width: 100%;
            }

            .analytics-page .analytics-peak-meta {
                grid-template-columns: 1fr;
            }

            .analytics-page .analytics-heatmap-grid {
                grid-template-columns: minmax(64px, auto) repeat(7, minmax(52px, 1fr));
                gap: 0.45rem;
            }

            .analytics-page .analytics-heatmap-cell {
                min-height: 4rem;
                padding: 0.65rem;
            }

            .analytics-page .analytics-heatmap-amount {
                font-size: 0.82rem;
            }

        }

        @media (max-width: 575.98px) {
            .analytics-page .analytics-heatmap-shell {
                overflow-x: auto;
            }

            .analytics-page .analytics-heatmap-grid {
                min-width: 34rem;
            }
        }
    </style>

    <div class="analytics-page d-flex flex-column gap-4">
        <section class="analytics-hero">
            <div class="d-flex flex-column flex-xl-row align-items-xl-start justify-content-between gap-4">
                <div class="d-flex flex-column gap-3">
                    <span class="analytics-eyebrow">
                        <i class="ri ri-line-chart-line text-primary"></i>
                        Обзор бизнеса
                    </span>
                    <div>
                        <h4 class="mb-1">@lang('analytics.heading')</h4>
                        <p class="text-muted mb-0">@lang('analytics.subtitle')</p>
                    </div>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2 align-self-start">
                    <button type="button" class="btn btn-outline-secondary" id="analytics-refresh">
                        <i class="ri ri-refresh-line me-1"></i>
                        @lang('analytics.actions.refresh')
                    </button>
                    <a href="#" class="btn btn-primary disabled" id="analytics-export" target="_blank" rel="noopener">
                        <i class="ri ri-file-excel-2-line me-1"></i>
                        @lang('analytics.actions.export')
                    </a>
                </div>
            </div>
        </section>

        <section class="analytics-surface">
            <form id="analytics-filters" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="filter-from" class="form-label">@lang('analytics.filters.from')</label>
                    <input type="date" class="form-control" id="filter-from" name="from" />
                </div>
                <div class="col-md-3">
                    <label for="filter-to" class="form-label">@lang('analytics.filters.to')</label>
                    <input type="date" class="form-control" id="filter-to" name="to" />
                </div>
                <div class="col-md-3">
                    <label for="filter-grouping" class="form-label">@lang('analytics.filters.grouping')</label>
                    <select class="form-select" id="filter-grouping" name="grouping"></select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">@lang('analytics.filters.apply')</button>
                    <button type="button" class="btn btn-outline-secondary flex-fill" id="analytics-reset">@lang('analytics.filters.reset')</button>
                </div>
            </form>
        </section>

        <div id="analytics-alerts"></div>

        <section class="row g-4 analytics-summary-grid" id="analytics-summary">
            <div class="col-xl-3 col-md-6">
                <div class="analytics-kpi">
                    <div class="analytics-kpi-header">
                        <div>
                            <p class="text-muted mb-2">@lang('analytics.summary.revenue')</p>
                            <h4 class="mb-1" data-metric-value="revenue">—</h4>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge" data-metric-delta="revenue"></span>
                                <small class="text-muted">@lang('analytics.labels.vs_previous')</small>
                            </div>
                        </div>
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="ri ri-bar-chart-2-line"></i>
                        </span>
                    </div>
                    <div class="analytics-pill-list mt-3 small text-muted">
                        <span class="analytics-soft-card py-2 px-3">@lang('analytics.summary.services_revenue'): <span data-metric-value="services_revenue">—</span></span>
                        <span class="analytics-soft-card py-2 px-3">@lang('analytics.summary.retail_revenue'): <span data-metric-value="retail_revenue">—</span></span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="analytics-kpi">
                    <div class="analytics-kpi-header">
                        <div>
                            <p class="text-muted mb-2">@lang('analytics.summary.average_ticket')</p>
                            <h4 class="mb-1" data-metric-value="average_ticket">—</h4>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge" data-metric-delta="average_ticket"></span>
                                <small class="text-muted">@lang('analytics.labels.vs_previous')</small>
                            </div>
                        </div>
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="ri ri-bank-card-line"></i>
                        </span>
                    </div>
                    <p class="text-muted small mt-3 mb-0 analytics-kpi-note" data-metric-note="average_ticket"></p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="analytics-kpi">
                    <div class="analytics-kpi-header">
                        <div>
                            <p class="text-muted mb-2">@lang('analytics.summary.transactions')</p>
                            <h4 class="mb-1" data-metric-value="transactions">—</h4>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge" data-metric-delta="transactions"></span>
                                <small class="text-muted">@lang('analytics.labels.vs_previous')</small>
                            </div>
                        </div>
                        <span class="avatar-initial rounded bg-label-info">
                            <i class="ri ri-calendar-check-line"></i>
                        </span>
                    </div>
                    <p class="text-muted small mt-3 mb-0">@lang('analytics.summary.transactions_hint')</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="analytics-kpi">
                    <div class="analytics-kpi-header">
                        <div>
                            <p class="text-muted mb-2">@lang('analytics.summary.retention')</p>
                            <h4 class="mb-1" data-metric-value="retention_rate">—</h4>
                        </div>
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="ri ri-user-smile-line"></i>
                        </span>
                    </div>
                    <ul class="list-unstyled mb-0 mt-3 small text-muted" id="analytics-client-overview">
                        <li>@lang('analytics.summary.new_clients'): <span data-clients-count="new">—</span></li>
                        <li>@lang('analytics.summary.active_clients'): <span data-clients-count="active">—</span></li>
                        <li>@lang('analytics.summary.loyal_clients'): <span data-clients-count="loyal">—</span></li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="row g-4">
            <div class="col-xl-8">
                <div class="analytics-card h-100">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <h5 class="mb-1">@lang('analytics.cards.revenue_trend')</h5>
                            <p class="text-muted mb-0 small" id="analytics-revenue-trend-summary"></p>
                        </div>
                    </div>
                    <div class="analytics-chart">
                        <canvas id="analytics-revenue-chart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="analytics-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0">@lang('analytics.cards.revenue_share')</h5>
                    </div>
                    <div class="mb-3 analytics-chart analytics-chart--share">
                        <canvas id="analytics-share-chart"></canvas>
                    </div>
                    <div class="small" id="analytics-share-legend"></div>
                </div>
            </div>
        </section>

        <section class="analytics-card analytics-focus-card" id="analytics-peak-hours-section">
            <div class="d-flex flex-column flex-xl-row align-items-xl-start justify-content-between gap-3 mb-4">
                <div>
                    <span class="analytics-section-kicker">
                        <i class="ri ri-sparkling-2-line"></i>
                        @lang('analytics.cards.peak_hours')
                    </span>
                    <h5 class="mt-3 mb-1">@lang('analytics.peak_hours.title')</h5>
                    <p class="text-muted mb-0">@lang('analytics.peak_hours.description')</p>
                </div>
            </div>

            <div class="row g-4 align-items-stretch" id="analytics-peak-hours-ready">
                <div class="col-xl-5">
                    <div class="analytics-peak-summary h-100">
                        <div class="text-muted small mb-2">@lang('analytics.labels.best_period')</div>
                        <h3 class="mb-2" id="analytics-peak-headline">—</h3>
                        <p class="text-muted mb-4" id="analytics-peak-summary-text">—</p>

                        <div class="analytics-peak-meta">
                            <div class="analytics-peak-stat">
                                <div class="text-muted small mb-1">@lang('analytics.labels.best_revenue')</div>
                                <div class="fw-semibold" id="analytics-peak-revenue">—</div>
                            </div>
                            <div class="analytics-peak-stat">
                                <div class="text-muted small mb-1">@lang('analytics.labels.average_ticket_short')</div>
                                <div class="fw-semibold" id="analytics-peak-ticket">—</div>
                            </div>
                            <div class="analytics-peak-stat">
                                <div class="text-muted small mb-1">@lang('analytics.labels.appointments')</div>
                                <div class="fw-semibold" id="analytics-peak-transactions">—</div>
                            </div>
                        </div>
                    </div>

                    <div class="analytics-driver-list mt-3" id="analytics-peak-drivers"></div>

                    <div class="analytics-action-card mt-3">
                        <div class="text-muted small mb-1" id="analytics-peak-action-title">@lang('analytics.peak_hours.action_title')</div>
                        <div class="fw-semibold" id="analytics-peak-action-body">—</div>
                    </div>
                </div>

                <div class="col-xl-7">
                    <div class="analytics-heatmap-shell">
                        <div class="analytics-heatmap-grid" id="analytics-peak-grid"></div>
                    </div>
                </div>
            </div>

            <div class="analytics-empty-card" id="analytics-peak-hours-empty" hidden>
                <div>
                    <span class="analytics-empty-icon">
                        <i class="ri ri-time-line"></i>
                    </span>
                    <h6 class="mb-2" id="analytics-peak-empty-title">—</h6>
                    <p class="text-muted mb-0" id="analytics-peak-empty-description">—</p>
                </div>
            </div>

            <div class="analytics-lock-card" id="analytics-peak-hours-locked" hidden>
                @include('components.elite-lock-card')
            </div>
        </section>

        <section class="row g-4" id="analytics-smart-panels">
            <div class="col-xl-6">
                <div class="analytics-card h-100">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                        <div>
                            <h5 class="mb-1">@lang('analytics.cards.ai')</h5>
                            <p class="text-muted mb-0 small">@lang('analytics.ai.subtitle')</p>
                        </div>
                    </div>
                    <div class="analytics-soft-card mb-3">
                        <h6 class="text-muted text-uppercase small mb-2">@lang('analytics.ai.summary_title')</h6>
                        <p class="mb-0" id="analytics-ai-summary">—</p>
                    </div>
                    <div class="analytics-soft-card mb-3">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <div class="text-muted small">@lang('analytics.labels.revenue_forecast')</div>
                                <h5 class="mb-0" id="analytics-ai-forecast-value">—</h5>
                            </div>
                            <div class="text-muted small" id="analytics-ai-forecast-meta">—</div>
                        </div>
                        <p class="text-muted small mb-0 mt-2" id="analytics-ai-forecast-comment"></p>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase small mb-2">@lang('analytics.ai.recommendations_title')</h6>
                        <ul class="list-unstyled mb-0" id="analytics-ai-recommendations"></ul>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="analytics-card h-100">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                        <div>
                            <h5 class="mb-1">Что важно сейчас</h5>
                            <p class="text-muted mb-0 small">Короткие сигналы по выручке, клиентам и удержанию.</p>
                        </div>
                    </div>
                    <div class="analytics-soft-card mb-3">
                        <h6 class="text-muted text-uppercase small mb-2">Финансовые сигналы</h6>
                        <div class="small" id="analytics-financial-insights"></div>
                    </div>
                    <div class="analytics-soft-card">
                        <h6 class="text-muted text-uppercase small mb-2">Клиентские сигналы</h6>
                        <div class="small mb-3 text-muted" id="analytics-persona"></div>
                        <div class="small" id="analytics-client-insights"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="analytics-card" id="analytics-smart-panels-locked" hidden>
            @include('components.elite-lock-card')
        </section>

        <details class="analytics-collapse" open id="analytics-sales-details">
            <summary>
                <div>
                    <h5 class="mb-1">Продажи и сегменты</h5>
                    <p class="text-muted mb-0 small">Воронка, статусы клиентов и структура базы.</p>
                </div>
            </summary>
            <div class="analytics-collapse-body">
                <div class="row g-4">
                    <div class="col-xl-6">
                        <div class="analytics-card h-100">
                            <h5 class="mb-3">@lang('analytics.cards.funnel')</h5>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>@lang('analytics.tables.funnel_stage')</th>
                                            <th class="text-end">@lang('analytics.tables.funnel_clients')</th>
                                            <th class="text-end">@lang('analytics.tables.funnel_conversion')</th>
                                        </tr>
                                    </thead>
                                    <tbody id="analytics-funnel-body">
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">@lang('analytics.labels.loading')</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="analytics-card h-100">
                            <h5 class="mb-3">@lang('analytics.cards.segments')</h5>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>@lang('analytics.tables.segment')</th>
                                            <th class="text-end">@lang('analytics.tables.count')</th>
                                            <th class="text-end">@lang('analytics.tables.share')</th>
                                        </tr>
                                    </thead>
                                    <tbody id="analytics-segments-body">
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">@lang('analytics.labels.loading')</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="small text-muted" id="analytics-persona-duplicate-placeholder" hidden></div>
                        </div>
                    </div>
                </div>
            </div>
        </details>

        <details class="analytics-collapse" id="analytics-risk-details">
            <summary>
                <div>
                    <h5 class="mb-1">Риск и ценность клиентов</h5>
                    <p class="text-muted mb-0 small">Отток, LTV и список клиентов, которым нужен возврат.</p>
                </div>
            </summary>
            <div class="analytics-collapse-body">
                <div class="row g-4">
                    <div class="col-xl-6">
                        <div class="analytics-card h-100">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="avatar avatar-lg flex-shrink-0 bg-label-danger d-flex align-items-center justify-content-center">
                                    <i class="ri ri-alert-line"></i>
                                </div>
                                <div>
                                    <p class="mb-1 text-muted">@lang('analytics.churn.rate')</p>
                                    <h4 class="mb-0" id="analytics-churn-rate">—</h4>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>@lang('analytics.tables.client')</th>
                                            <th>@lang('analytics.tables.last_visit')</th>
                                            <th class="text-end">@lang('analytics.tables.segment')</th>
                                        </tr>
                                    </thead>
                                    <tbody id="analytics-risk-body">
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">@lang('analytics.labels.risk_clients_empty')</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="analytics-card h-100">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="avatar avatar-lg flex-shrink-0 bg-label-primary d-flex align-items-center justify-content-center">
                                    <i class="ri ri-vip-crown-line"></i>
                                </div>
                                <div>
                                    <p class="mb-1 text-muted">@lang('analytics.ltv.value')</p>
                                    <h4 class="mb-0" id="analytics-ltv-value">—</h4>
                                    <div class="d-flex align-items-center gap-2 small text-muted mt-1">
                                        <span id="analytics-ltv-delta">—</span>
                                    </div>
                                </div>
                            </div>
                            <p class="text-muted small mb-0" id="analytics-ltv-insight"></p>
                        </div>
                    </div>
                </div>
            </div>
        </details>

        <details class="analytics-collapse" id="analytics-top-details">
            <summary>
                <div>
                    <h5 class="mb-1">@lang('analytics.top_clients.title')</h5>
                    <p class="text-muted mb-0 small">Открывайте тех, кто приносит максимум выручки.</p>
                </div>
            </summary>
            <div class="analytics-collapse-body">
                <div class="analytics-card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>@lang('analytics.tables.client')</th>
                                    <th>@lang('analytics.tables.revenue')</th>
                                    <th>@lang('analytics.tables.transactions')</th>
                                    <th>@lang('analytics.tables.last_visit')</th>
                                </tr>
                            </thead>
                            <tbody id="analytics-top-clients">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">@lang('analytics.labels.loading')</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </details>
    </div>@endsection

@section('scripts')
    <script src="{{ asset('assets/vendor/libs/chartjs/chartjs.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const texts = @json(trans('analytics.labels'));
            const funnelLabels = @json(trans('analytics.funnel'));
            const segmentLabels = @json(trans('analytics.segments'));
            const aiTexts = @json(trans('analytics.ai'));
            const peakHoursTexts = @json(trans('analytics.peak_hours'));

            const state = {
                filters: {
                    from: '',
                    to: '',
                    grouping: 'day'
                },
                exports: {
                    excel: null
                },
                charts: {
                    revenue: null,
                    share: null
                }
            };

            const alertsContainer = document.getElementById('analytics-alerts');
            const filtersForm = document.getElementById('analytics-filters');
            const refreshButton = document.getElementById('analytics-refresh');
            const resetButton = document.getElementById('analytics-reset');
            const exportLink = document.getElementById('analytics-export');
            const groupingSelect = document.getElementById('filter-grouping');
            const fromInput = document.getElementById('filter-from');
            const toInput = document.getElementById('filter-to');
            const revenueTrendSummary = document.getElementById('analytics-revenue-trend-summary');
            const churnRateEl = document.getElementById('analytics-churn-rate');
            const ltvValueEl = document.getElementById('analytics-ltv-value');
            const ltvDeltaEl = document.getElementById('analytics-ltv-delta');
            const ltvInsightEl = document.getElementById('analytics-ltv-insight');
            const personaEl = document.getElementById('analytics-persona');
            const aiSummaryEl = document.getElementById('analytics-ai-summary');
            const aiForecastValueEl = document.getElementById('analytics-ai-forecast-value');
            const aiForecastMetaEl = document.getElementById('analytics-ai-forecast-meta');
            const aiForecastCommentEl = document.getElementById('analytics-ai-forecast-comment');
            const aiRecommendationsEl = document.getElementById('analytics-ai-recommendations');
            const funnelBody = document.getElementById('analytics-funnel-body');
            const segmentsBody = document.getElementById('analytics-segments-body');
            const riskBody = document.getElementById('analytics-risk-body');
            const topClientsBody = document.getElementById('analytics-top-clients');
            const shareLegend = document.getElementById('analytics-share-legend');
            const financialInsightsEl = document.getElementById('analytics-financial-insights');
            const clientInsightsEl = document.getElementById('analytics-client-insights');
            const riskDetails = document.getElementById('analytics-risk-details');
            const topDetails = document.getElementById('analytics-top-details');
            const peakHoursReady = document.getElementById('analytics-peak-hours-ready');
            const peakHoursEmpty = document.getElementById('analytics-peak-hours-empty');
            const peakHeadlineEl = document.getElementById('analytics-peak-headline');
            const peakSummaryEl = document.getElementById('analytics-peak-summary-text');
            const peakRevenueEl = document.getElementById('analytics-peak-revenue');
            const peakTicketEl = document.getElementById('analytics-peak-ticket');
            const peakTransactionsEl = document.getElementById('analytics-peak-transactions');
            const peakDriversEl = document.getElementById('analytics-peak-drivers');
            const peakActionTitleEl = document.getElementById('analytics-peak-action-title');
            const peakActionBodyEl = document.getElementById('analytics-peak-action-body');
            const peakGridEl = document.getElementById('analytics-peak-grid');
            const peakEmptyTitleEl = document.getElementById('analytics-peak-empty-title');
            const peakEmptyDescriptionEl = document.getElementById('analytics-peak-empty-description');
            const peakLockedEl = document.getElementById('analytics-peak-hours-locked');
            const smartPanelsEl = document.getElementById('analytics-smart-panels');
            const smartPanelsLockedEl = document.getElementById('analytics-smart-panels-locked');

            if (typeof window.Chart === 'undefined') {
                console.error('Chart.js library is required for analytics charts.');
                showAlert(texts.charts_unavailable || 'Charts are temporarily unavailable.');
                return;
            }

            function getCookie(name) {
                const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                return match ? decodeURIComponent(match[1]) : null;
            }

            function authHeaders(extra = {}) {
                const token = getCookie('token');
                const headers = Object.assign({ 'Accept': 'application/json' }, extra);
                if (token) headers['Authorization'] = 'Bearer ' + token;
                return headers;
            }

            function formatCurrency(value) {
                if (value === null || value === undefined) return '—';
                try {
                    return new Intl.NumberFormat(document.documentElement.lang || 'ru-RU', {
                        style: 'currency',
                        currency: 'RUB',
                        maximumFractionDigits: 0
                    }).format(value);
                } catch (e) {
                    return value.toFixed ? value.toFixed(0) : String(value);
                }
            }

            function formatNumber(value, fractionDigits = 0) {
                if (value === null || value === undefined) return '—';
                try {
                    return new Intl.NumberFormat(document.documentElement.lang || 'ru-RU', {
                        maximumFractionDigits: fractionDigits
                    }).format(value);
                } catch (e) {
                    return value.toFixed ? value.toFixed(fractionDigits) : String(value);
                }
            }

            function formatPercent(value, suffix = '%') {
                if (value === null || value === undefined) return '—';
                return formatNumber(value, 1) + suffix;
            }

            function clearAlerts() {
                alertsContainer.innerHTML = '';
            }

            function showAlert(message, type = 'danger') {
                const div = document.createElement('div');
                div.className = 'alert alert-' + type;
                div.textContent = message;
                alertsContainer.appendChild(div);
            }

            function setLoading(isLoading) {
                [refreshButton, resetButton].forEach(function (button) {
                    button.disabled = isLoading;
                });
                if (isLoading) {
                    refreshButton.classList.add('btn-progress');
                } else {
                    refreshButton.classList.remove('btn-progress');
                }
            }

            function updateMetricCard(key, metric) {
                const valueEl = document.querySelector('[data-metric-value="' + key + '"]');
                const deltaEl = document.querySelector('[data-metric-delta="' + key + '"]');
                const noteEl = document.querySelector('[data-metric-note="' + key + '"]');

                if (!metric || !valueEl) return;

                if (key === 'retention_rate') {
                    valueEl.textContent = formatPercent(metric.current);
                } else if (key === 'transactions') {
                    valueEl.textContent = formatNumber(metric.current);
                } else {
                    valueEl.textContent = formatCurrency(metric.current);
                }

                if (deltaEl) {
                    if (metric.delta === null || metric.delta === undefined) {
                        deltaEl.className = 'badge bg-label-secondary';
                        deltaEl.textContent = texts.no_change;
                    } else {
                        const delta = metric.delta;
                        if (delta > 0) {
                            deltaEl.className = 'badge bg-label-success';
                            deltaEl.textContent = '+' + formatPercent(delta);
                        } else if (delta < 0) {
                            deltaEl.className = 'badge bg-label-danger';
                            deltaEl.textContent = formatPercent(delta);
                        } else {
                            deltaEl.className = 'badge bg-label-secondary';
                            deltaEl.textContent = texts.no_change;
                        }
                    }
                }

                if (noteEl) {
                    if (metric.previous !== null && metric.previous !== undefined) {
                        noteEl.textContent = texts.previous_value.replace(':value', key === 'transactions' ? formatNumber(metric.previous) : formatCurrency(metric.previous));
                    } else {
                        noteEl.textContent = '';
                    }
                }
            }

            function updateClientsSummary(clients) {
                const mapping = {
                    new: document.querySelector('[data-clients-count="new"]'),
                    active: document.querySelector('[data-clients-count="active"]'),
                    loyal: document.querySelector('[data-clients-count="loyal"]')
                };

                if (!clients) return;
                if (mapping.new) mapping.new.textContent = formatNumber(clients.new || 0);
                if (mapping.active) mapping.active.textContent = formatNumber(clients.active || 0);
                if (mapping.loyal) mapping.loyal.textContent = formatNumber(clients.loyal || 0);
            }

            function updateShareLegend(data) {
                shareLegend.innerHTML = '';
                if (!data || !data.labels || data.labels.length === 0) {
                    shareLegend.innerHTML = '<span class="text-muted">' + texts.no_data + '</span>';
                    return;
                }

                const total = data.values.reduce((acc, value) => acc + value, 0) || 1;

                data.labels.forEach(function (label, index) {
                    const value = data.values[index] || 0;
                    const percent = Math.round((value / total) * 100);
                    const row = document.createElement('div');
                    row.className = 'd-flex align-items-center justify-content-between small text-muted mb-1';
                    row.innerHTML = '<span>' + label + '</span><span>' + percent + '%</span>';
                    shareLegend.appendChild(row);
                });
            }

            function renderFunnel(funnel) {
                funnelBody.innerHTML = '';
                if (!funnel || funnel.length === 0) {
                    funnelBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">' + texts.no_data + '</td></tr>';
                    return;
                }

                funnel.forEach(function (stage) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + (stage.label || funnelLabels[stage.key] || '') + '</td>' +
                        '<td class="text-end">' + formatNumber(stage.count || 0) + '</td>' +
                        '<td class="text-end">' + formatPercent(stage.conversion || 0) + '</td>';
                    funnelBody.appendChild(tr);
                });
            }

            function renderSegments(segments) {
                segmentsBody.innerHTML = '';
                if (!segments || !segments.distribution) {
                    segmentsBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">' + texts.no_data + '</td></tr>';
                    return;
                }

                Object.keys(segments.distribution).forEach(function (key) {
                    const segment = segments.distribution[key];
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + (segmentLabels[key] || key) + '</td>' +
                        '<td class="text-end">' + formatNumber(segment.count || 0) + '</td>' +
                        '<td class="text-end">' + formatPercent(segment.share || 0) + '</td>';
                    segmentsBody.appendChild(tr);
                });

                personaEl.textContent = '';
                if (clientInsightsEl) clientInsightsEl.innerHTML = '';
            }

            function renderRiskClients(riskClients) {
                riskBody.innerHTML = '';
                if (!riskClients || riskClients.length === 0) {
                    riskBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">' + texts.risk_clients_empty + '</td></tr>';
                    return;
                }

                riskClients.forEach(function (client) {
                    const lastVisit = client.last_visit_at ? new Date(client.last_visit_at) : null;
                    const formatted = lastVisit ? lastVisit.toLocaleDateString() : '—';
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + (client.name || texts.unknown_client) + '</td>' +
                        '<td>' + formatted + '</td>' +
                        '<td class="text-end"><span class="badge bg-label-warning">' + (client.loyalty_level || '—') + '</span></td>';
                    riskBody.appendChild(tr);
                });
            }

            function renderTopClients(clients) {
                topClientsBody.innerHTML = '';
                if (!clients || clients.length === 0) {
                    topClientsBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">' + texts.no_data + '</td></tr>';
                    return;
                }

                clients.forEach(function (client) {
                    const last = client.last_purchase_at ? new Date(client.last_purchase_at).toLocaleDateString() : '—';
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + (client.name || texts.unknown_client) + '</td>' +
                        '<td>' + formatCurrency(client.amount || 0) + '</td>' +
                        '<td>' + formatNumber(client.transactions || 0) + '</td>' +
                        '<td>' + last + '</td>';
                    topClientsBody.appendChild(tr);
                });
            }

            function renderPersona(persona) {
                if (!persona || Object.keys(persona).length === 0) {
                    personaEl.textContent = '';
                    return;
                }

                const parts = [];
                if (persona.avg_age) parts.push(aiTexts.persona_age.replace(':value', persona.avg_age));
                if (persona.age_group) parts.push(aiTexts.persona_age_group.replace(':value', persona.age_group));
                if (persona.top_loyalty) parts.push(aiTexts.persona_loyalty.replace(':value', persona.top_loyalty));
                if (persona.favorite_service) parts.push(aiTexts.persona_service.replace(':value', persona.favorite_service));
                if (persona.popular_tags && persona.popular_tags.length) {
                    const tags = persona.popular_tags.map(function (tag) { return '#' + tag.tag; }).join(', ');
                    parts.push(aiTexts.persona_tags.replace(':value', tags));
                }

                personaEl.textContent = parts.join(' • ');
            }

            function renderAi(ai) {
                if (!ai) return;
                aiSummaryEl.textContent = ai.summary || '—';
                aiForecastValueEl.textContent = formatCurrency(ai.forecast ? ai.forecast.revenue : null);
                const confidence = ai.forecast ? ai.forecast.confidence : null;
                const delta = ai.forecast ? ai.forecast.delta : null;
                aiForecastMetaEl.textContent = (delta !== null && delta !== undefined ? formatPercent(delta) : '—') + ' • ' + texts.confidence + ' ' + (confidence ? formatPercent(confidence * 100) : '—');
                aiForecastCommentEl.textContent = ai.forecast ? ai.forecast.comment || '' : '';

                aiRecommendationsEl.innerHTML = '';
                (ai.recommendations || []).forEach(function (item) {
                    const li = document.createElement('li');
                    li.className = 'mb-2';
                    li.innerHTML = '<strong>' + item.title + '</strong><br><span class="text-muted small">' + item.description + '</span>';
                    aiRecommendationsEl.appendChild(li);
                });
            }

            function renderInsights(container, items) {
                if (!container) return;
                container.innerHTML = '';
                if (!items || items.length === 0) {
                    return;
                }

                items.forEach(function (item) {
                    const div = document.createElement('div');
                    div.className = 'd-flex align-items-start gap-2 mb-2';
                    div.innerHTML = '<i class="ri ri-lightbulb-line text-warning mt-1"></i>' +
                        '<div><div class="fw-semibold">' + (item.title || '') + '</div>' +
                        '<div class="text-muted">' + (item.body || '') + '</div></div>';
                    container.appendChild(div);
                });
            }

            function renderPeakHours(data) {
                if (!peakHoursReady || !peakHoursEmpty || !peakLockedEl) return;

                if (data && data.status === 'locked') {
                    peakHoursReady.hidden = true;
                    peakHoursEmpty.hidden = true;
                    peakLockedEl.hidden = false;
                    return;
                }

                peakLockedEl.hidden = true;

                if (!data || data.status === 'empty') {
                    peakHoursReady.hidden = true;
                    peakHoursEmpty.hidden = false;
                    peakEmptyTitleEl.textContent = data && data.title ? data.title : (peakHoursTexts.empty_title || texts.no_data);
                    peakEmptyDescriptionEl.textContent = data && data.description ? data.description : (peakHoursTexts.empty_description || '');
                    return;
                }

                peakHoursReady.hidden = false;
                peakHoursEmpty.hidden = true;

                peakHeadlineEl.textContent = data.headline || '—';
                peakSummaryEl.textContent = data.summary || '—';
                peakRevenueEl.textContent = formatCurrency(data.highlight ? data.highlight.revenue : null);
                peakTicketEl.textContent = formatCurrency(data.highlight ? data.highlight.average_ticket : null);
                peakTransactionsEl.textContent = formatNumber(data.highlight ? data.highlight.transactions : 0);
                peakActionTitleEl.textContent = data.action && data.action.title ? data.action.title : (peakHoursTexts.action_title || '');
                peakActionBodyEl.textContent = data.action && data.action.body ? data.action.body : '—';

                peakDriversEl.innerHTML = '';
                (data.drivers || []).forEach(function (item) {
                    const card = document.createElement('div');
                    card.className = 'analytics-driver-item';
                    const title = document.createElement('div');
                    title.className = 'fw-semibold mb-1';
                    title.textContent = item.title || '';
                    const body = document.createElement('div');
                    body.className = 'text-muted small';
                    body.textContent = item.body || '';
                    card.appendChild(title);
                    card.appendChild(body);
                    peakDriversEl.appendChild(card);
                });

                peakGridEl.innerHTML = '';
                const highlightDay = (data.days || []).find(function (entry) {
                    return entry.full_label === (data.highlight ? data.highlight.day_label : null);
                });
                const highlightSlot = (data.slots || []).find(function (entry) {
                    return entry.label === (data.highlight ? data.highlight.slot_label : null);
                });

                const corner = document.createElement('div');
                corner.className = 'analytics-heatmap-label';
                peakGridEl.appendChild(corner);

                (data.days || []).forEach(function (day) {
                    const label = document.createElement('div');
                    label.className = 'analytics-heatmap-label';
                    label.textContent = day.label || '';
                    label.title = day.full_label || day.label || '';
                    peakGridEl.appendChild(label);
                });

                (data.slots || []).forEach(function (slot) {
                    const slotLabel = document.createElement('div');
                    slotLabel.className = 'analytics-heatmap-label analytics-heatmap-slot';
                    slotLabel.textContent = slot.label || '';
                    peakGridEl.appendChild(slotLabel);

                    (data.days || []).forEach(function (day) {
                        const cellData = (data.cells || []).find(function (cell) {
                            return Number(cell.day_index) === Number(day.index) && cell.slot_key === slot.key;
                        }) || {};

                        const cell = document.createElement('div');
                        cell.className = 'analytics-heatmap-cell';

                        if (data.highlight &&
                            highlightDay &&
                            highlightSlot &&
                            Number(cellData.day_index) === Number(highlightDay.index) &&
                            cellData.slot_key === highlightSlot.key) {
                            cell.classList.add('is-active');
                        }

                        const intensity = Math.max(0, Math.min(1, Number(cellData.intensity || 0)));
                        cell.style.background = 'linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.02)), rgba(255, 171, 0, ' + (0.08 + intensity * 0.42).toFixed(3) + ')';

                        const amount = document.createElement('span');
                        amount.className = 'analytics-heatmap-amount';
                        amount.textContent = cellData.transactions ? formatCurrency(cellData.revenue) : '—';
                        cell.appendChild(amount);

                        const note = document.createElement('span');
                        note.className = 'analytics-heatmap-note';
                        note.textContent = cellData.transactions
                            ? formatNumber(cellData.transactions) + ' ' + (texts.appointments || '')
                            : texts.no_data;
                        cell.appendChild(note);

                        const tooltipParts = [];
                        if (day.full_label) tooltipParts.push(day.full_label);
                        if (slot.label) tooltipParts.push(slot.label);
                        if (cellData.transactions) {
                            tooltipParts.push((texts.best_revenue || '') + ': ' + formatCurrency(cellData.revenue));
                            tooltipParts.push((texts.average_ticket_short || '') + ': ' + formatCurrency(cellData.average_ticket));
                        }
                        cell.title = tooltipParts.filter(Boolean).join(' • ');
                        peakGridEl.appendChild(cell);
                    });
                });
            }

            function renderSmartAccess(access) {
                const smartAccess = access && access.smart_insights ? access.smart_insights : null;
                const available = !smartAccess || smartAccess.available !== false;

                if (smartPanelsEl && smartPanelsLockedEl) {
                    smartPanelsEl.hidden = !available;
                    smartPanelsLockedEl.hidden = available;
                }

                if (available && peakLockedEl) {
                    peakLockedEl.hidden = true;
                }
            }

            function renderCharts(data) {
                if (!data || !data.revenue_trend || !data.service_share) {
                    return;
                }
                const ctxRevenue = document.getElementById('analytics-revenue-chart');
                const ctxShare = document.getElementById('analytics-share-chart');

                if (state.charts.revenue) {
                    state.charts.revenue.destroy();
                }

                state.charts.revenue = new Chart(ctxRevenue, {
                    type: 'line',
                    data: {
                        labels: data.revenue_trend.labels,
                        datasets: [
                            {
                                label: texts.current_period,
                                data: data.revenue_trend.current,
                                tension: 0.4,
                                borderColor: '#696cff',
                                backgroundColor: 'rgba(105, 108, 255, 0.1)',
                                fill: true,
                                borderWidth: 2,
                                pointRadius: 2
                            },
                            {
                                label: texts.previous_period,
                                data: data.revenue_trend.previous,
                                tension: 0.4,
                                borderColor: '#a8b1c5',
                                backgroundColor: 'rgba(168, 177, 197, 0.1)',
                                fill: false,
                                borderDash: [6, 4],
                                borderWidth: 2,
                                pointRadius: 2
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        scales: {
                            y: {
                                ticks: {
                                    callback: (value) => formatNumber(value)
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true
                            }
                        }
                    }
                });

                if (state.charts.share) {
                    state.charts.share.destroy();
                }

                state.charts.share = new Chart(ctxShare, {
                    type: 'doughnut',
                    data: {
                        labels: data.service_share.labels,
                        datasets: [
                            {
                                data: data.service_share.values,
                                backgroundColor: ['#696cff', '#8592a3', '#03c3ec', '#71dd37', '#ffab00', '#ff3e1d', '#836af9'],
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });

                updateShareLegend(data.service_share);
            }

            function populateGroupings(options) {
                groupingSelect.innerHTML = '';
                options.forEach(function (option) {
                    const opt = document.createElement('option');
                    opt.value = option.value;
                    opt.textContent = option.label;
                    groupingSelect.appendChild(opt);
                });
            }

            function applyFiltersToInputs(period) {
                if (period.from) fromInput.value = period.from;
                if (period.to) toInput.value = period.to;
                if (period.grouping && groupingSelect.querySelector('option[value="' + period.grouping + '"]')) {
                    groupingSelect.value = period.grouping;
                    state.filters.grouping = period.grouping;
                }
            }

            function syncFiltersFromInputs() {
                state.filters.from = fromInput.value;
                state.filters.to = toInput.value;
                state.filters.grouping = groupingSelect.value || 'day';
            }

            function updateDeferredPlaceholders() {
                if (churnRateEl) churnRateEl.textContent = '—';
                if (ltvValueEl) ltvValueEl.textContent = '—';
                if (ltvDeltaEl) ltvDeltaEl.textContent = '—';
                if (ltvInsightEl) ltvInsightEl.textContent = '';
                if (riskBody) {
                    riskBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Раскройте секцию, чтобы загрузить клиентов в зоне риска.</td></tr>';
                }
                if (topClientsBody) {
                    topClientsBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">Раскройте секцию, чтобы загрузить топ-клиентов.</td></tr>';
                }
            }

            function buildQuery(extraSections = []) {
                const params = new URLSearchParams();
                if (state.filters.from) params.append('from', state.filters.from);
                if (state.filters.to) params.append('to', state.filters.to);
                if (state.filters.grouping) params.append('grouping', state.filters.grouping);
                extraSections.forEach(function (section) {
                    params.append('sections[]', section);
                });
                return params.toString();
            }

            function fetchAnalytics() {
                clearAlerts();
                setLoading(true);
                syncFiltersFromInputs();
                updateDeferredPlaceholders();

                const query = buildQuery();
                fetch('/api/v1/analytics/overview' + (query ? '?' + query : ''), { headers: authHeaders() })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error(texts.request_failed || 'Request failed');
                        }
                        return response.json();
                    })
                    .then(function (payload) {
                        const data = payload.data || {};
                        const meta = payload.meta || {};

                        renderSmartAccess(meta.access || null);

                        if (meta.filters && meta.filters.groupings) {
                            populateGroupings(meta.filters.groupings);
                        }
                        if (meta.period) {
                            applyFiltersToInputs(meta.period);
                        }
                        if (meta.exports && meta.exports.excel) {
                            state.exports.excel = meta.exports.excel;
                            exportLink.href = meta.exports.excel;
                            exportLink.classList.remove('disabled');
                        } else {
                            state.exports.excel = null;
                            exportLink.href = '#';
                            exportLink.classList.add('disabled');
                        }

                        updateMetricCard('revenue', data.summary ? data.summary.revenue : null);
                        updateMetricCard('services_revenue', data.summary ? data.summary.services_revenue : null);
                        updateMetricCard('retail_revenue', data.summary ? data.summary.retail_revenue : null);
                        updateMetricCard('average_ticket', data.summary ? data.summary.average_ticket : null);
                        updateMetricCard('transactions', data.summary ? data.summary.transactions : null);
                        updateMetricCard('retention_rate', data.summary ? data.summary.retention_rate : null);
                        updateClientsSummary(data.summary ? data.summary.clients : null);

                        if (data.financial) {
                            renderCharts(data.financial);
                            const totalCurrent = formatCurrency(data.financial.revenue_trend ? data.financial.revenue_trend.current_total : 0);
                            const totalPrevious = formatCurrency(data.financial.revenue_trend ? data.financial.revenue_trend.previous_total : 0);
                            revenueTrendSummary.textContent = aiTexts.revenue_trend.replace(':current', totalCurrent).replace(':previous', totalPrevious);
                            renderPeakHours(data.financial.peak_hours || null);
                            renderInsights(financialInsightsEl, data.financial.insights || []);
                        }

                        renderFunnel(data.clients ? data.clients.funnel : null);
                        renderSegments(data.clients ? data.clients.segments : null);
                        renderInsights(clientInsightsEl, data.clients ? data.clients.insights : []);

                        renderPersona(data.clients ? data.clients.persona : null);
                        renderAi(data.ai || null);

                        if (riskDetails && riskDetails.open) {
                            fetchDeferredSections(['churn', 'ltv']);
                        }

                        if (topDetails && topDetails.open) {
                            fetchDeferredSections(['top_clients']);
                        }
                    })
                    .catch(function (error) {
                        console.error(error);
                        showAlert(error.message || 'Ошибка загрузки аналитики');
                    })
                    .finally(function () {
                        setLoading(false);
                    });
            }

            function fetchDeferredSections(sections) {
                const query = buildQuery(sections);

                fetch('/api/v1/analytics/overview' + (query ? '?' + query : ''), { headers: authHeaders() })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error(texts.request_failed || 'Request failed');
                        }
                        return response.json();
                    })
                    .then(function (payload) {
                        const data = payload.data || {};

                        if (sections.includes('churn')) {
                            renderRiskClients(data.clients && data.clients.churn ? data.clients.churn.risk_clients : null);
                            churnRateEl.textContent = formatPercent(data.clients && data.clients.churn ? data.clients.churn.rate : 0);
                        }

                        if (sections.includes('ltv')) {
                            ltvValueEl.textContent = formatCurrency(data.clients && data.clients.ltv ? data.clients.ltv.value : 0);
                            ltvDeltaEl.textContent = data.clients && data.clients.ltv && data.clients.ltv.delta !== null ? formatPercent(data.clients.ltv.delta) : '—';
                            ltvInsightEl.textContent = data.clients && data.clients.ltv ? data.clients.ltv.insight : '';
                        }

                        if (sections.includes('top_clients')) {
                            renderTopClients(data.top_clients || []);
                        }
                    })
                    .catch(function (error) {
                        console.error(error);
                    });
            }

            filtersForm.addEventListener('submit', function (event) {
                event.preventDefault();
                fetchAnalytics();
            });

            refreshButton.addEventListener('click', function () {
                fetchAnalytics();
            });

            resetButton.addEventListener('click', function () {
                fromInput.value = '';
                toInput.value = '';
                if (groupingSelect.options.length) {
                    groupingSelect.selectedIndex = 0;
                }
                fetchAnalytics();
            });

            if (riskDetails) {
                riskDetails.addEventListener('toggle', function () {
                    if (riskDetails.open) {
                        fetchDeferredSections(['churn', 'ltv']);
                    }
                });
            }

            if (topDetails) {
                topDetails.addEventListener('toggle', function () {
                    if (topDetails.open) {
                        fetchDeferredSections(['top_clients']);
                    }
                });
            }

            document.querySelectorAll('[data-report]').forEach(function (button) {
                button.addEventListener('click', function () {
                    showAlert(texts.report_placeholder || 'Детальный отчёт появится в следующих релизах.', 'info');
                });
            });

            fetchAnalytics();
        });
    </script>
@endsection
