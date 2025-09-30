@extends('layouts.app')

@section('title', __('analytics.title'))

@section('content')
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">@lang('analytics.heading')</h4>
            <p class="text-muted mb-0">@lang('analytics.subtitle')</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
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

    <div class="card mb-4">
        <div class="card-body">
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
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">@lang('analytics.filters.apply')</button>
                        <button type="button" class="btn btn-outline-secondary flex-fill" id="analytics-reset">@lang('analytics.filters.reset')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="analytics-alerts"></div>

    <div class="row g-4 mb-4" id="analytics-summary">
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
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
                    <div class="mt-3">
                        <div class="d-flex align-items-center justify-content-between small text-muted">
                            <span>@lang('analytics.summary.services_revenue')</span>
                            <span data-metric-value="services_revenue">—</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between small text-muted">
                            <span>@lang('analytics.summary.retail_revenue')</span>
                            <span data-metric-value="retail_revenue">—</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
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
                    <p class="text-muted small mt-3 mb-0" data-metric-note="average_ticket"></p>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
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
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
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
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h5 class="mb-1">@lang('analytics.cards.revenue_trend')</h5>
                        <p class="text-muted mb-0 small" id="analytics-revenue-trend-summary"></p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-report="revenue">
                        @lang('analytics.actions.details')
                    </button>
                </div>
                <div class="card-body">
                    <canvas id="analytics-revenue-chart" height="260"></canvas>
                    <div class="mt-3 small" id="analytics-financial-insights"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">@lang('analytics.cards.revenue_share')</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-report="services">
                        @lang('analytics.actions.details')
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <canvas id="analytics-share-chart" height="220"></canvas>
                    </div>
                    <div class="small" id="analytics-share-legend"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">@lang('analytics.cards.funnel')</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-report="funnel">
                        @lang('analytics.actions.details')
                    </button>
                </div>
                <div class="card-body">
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
        </div>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">@lang('analytics.cards.segments')</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-report="segments">
                        @lang('analytics.actions.details')
                    </button>
                </div>
                <div class="card-body">
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
                    <div class="small text-muted" id="analytics-persona"></div>
                    <div class="small mt-3" id="analytics-client-insights"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">@lang('analytics.cards.churn')</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-report="churn">
                        @lang('analytics.actions.details')
                    </button>
                </div>
                <div class="card-body">
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
        </div>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">@lang('analytics.cards.ltv')</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-report="ltv">
                        @lang('analytics.actions.details')
                    </button>
                </div>
                <div class="card-body">
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

    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-1">@lang('analytics.cards.ai')</h5>
                <p class="text-muted mb-0 small">@lang('analytics.ai.subtitle')</p>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-report="ai">
                @lang('analytics.actions.details')
            </button>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-xl-4">
                    <h6 class="text-muted text-uppercase small mb-2">@lang('analytics.ai.summary_title')</h6>
                    <p class="mb-0" id="analytics-ai-summary">—</p>
                </div>
                <div class="col-xl-4">
                    <h6 class="text-muted text-uppercase small mb-2">@lang('analytics.ai.forecast_title')</h6>
                    <div class="d-flex align-items-center gap-3">
                        <div>
                            <div class="text-muted small">@lang('analytics.labels.revenue_forecast')</div>
                            <h5 class="mb-0" id="analytics-ai-forecast-value">—</h5>
                        </div>
                        <div class="text-muted small" id="analytics-ai-forecast-meta">—</div>
                    </div>
                    <p class="text-muted small mb-0 mt-2" id="analytics-ai-forecast-comment"></p>
                </div>
                <div class="col-xl-4">
                    <h6 class="text-muted text-uppercase small mb-2">@lang('analytics.ai.recommendations_title')</h6>
                    <ul class="list-unstyled mb-0" id="analytics-ai-recommendations"></ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">@lang('analytics.top_clients.title')</h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-report="top-clients">
                @lang('analytics.actions.details')
            </button>
        </div>
        <div class="card-body">
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
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js" integrity="sha384-xHF+rXyH8ZPiXUTGZGBVevVWFVdGZpXCtDD9ocUfYxE7SVtKpRUXDm1dX7Ute7tS" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const texts = @json(trans('analytics.labels'));
            const funnelLabels = @json(trans('analytics.funnel'));
            const segmentLabels = @json(trans('analytics.segments'));
            const aiTexts = @json(trans('analytics.ai'));

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

            function buildQuery() {
                const params = new URLSearchParams();
                if (state.filters.from) params.append('from', state.filters.from);
                if (state.filters.to) params.append('to', state.filters.to);
                if (state.filters.grouping) params.append('grouping', state.filters.grouping);
                return params.toString();
            }

            function fetchAnalytics() {
                clearAlerts();
                setLoading(true);
                syncFiltersFromInputs();

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
                            renderInsights(financialInsightsEl, data.financial.insights || []);
                        }

                        renderFunnel(data.clients ? data.clients.funnel : null);
                        renderSegments(data.clients ? data.clients.segments : null);
                        renderRiskClients(data.clients && data.clients.churn ? data.clients.churn.risk_clients : null);
                        renderInsights(clientInsightsEl, data.clients ? data.clients.insights : []);

                        churnRateEl.textContent = formatPercent(data.clients && data.clients.churn ? data.clients.churn.rate : 0);
                        ltvValueEl.textContent = formatCurrency(data.clients && data.clients.ltv ? data.clients.ltv.value : 0);
                        ltvDeltaEl.textContent = data.clients && data.clients.ltv && data.clients.ltv.delta !== null ? formatPercent(data.clients.ltv.delta) : '—';
                        ltvInsightEl.textContent = data.clients && data.clients.ltv ? data.clients.ltv.insight : '';

                        renderPersona(data.clients ? data.clients.persona : null);
                        renderAi(data.ai || null);
                        renderTopClients(data.top_clients || []);
                    })
                    .catch(function (error) {
                        console.error(error);
                        showAlert(error.message || 'Ошибка загрузки аналитики');
                    })
                    .finally(function () {
                        setLoading(false);
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

            document.querySelectorAll('[data-report]').forEach(function (button) {
                button.addEventListener('click', function () {
                    showAlert(texts.report_placeholder || 'Детальный отчёт появится в следующих релизах.', 'info');
                });
            });

            fetchAnalytics();
        });
    </script>
@endsection
