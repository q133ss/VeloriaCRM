@extends('layouts.app')

@section('title', __('analytics.title'))

@section('content')
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ __('analytics.title') }}</h4>
            <p class="text-muted mb-0">{{ __('analytics.description') }}</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary" id="export-all">
                <i class="ri ri-file-excel-2-line me-1"></i>
                {{ __('analytics.buttons.export') }}
            </button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-1">{{ __('analytics.filters.heading') }}</h5>
        </div>
        <div class="card-body">
            <form id="analytics-filters" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="filter-start" class="form-label">{{ __('analytics.filters.start') }}</label>
                    <input type="date" class="form-control" id="filter-start" name="start_date" />
                </div>
                <div class="col-md-3">
                    <label for="filter-end" class="form-label">{{ __('analytics.filters.end') }}</label>
                    <input type="date" class="form-control" id="filter-end" name="end_date" />
                </div>
                <div class="col-md-3">
                    <label for="filter-compare" class="form-label">{{ __('analytics.filters.compare_to') }}</label>
                    <select class="form-select" id="filter-compare" name="compare_to">
                        <option value="previous_period">{{ __('analytics.compare_options.previous_period') }}</option>
                        <option value="previous_year">{{ __('analytics.compare_options.previous_year') }}</option>
                        <option value="none">{{ __('analytics.compare_options.none') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filter-group" class="form-label">{{ __('analytics.filters.group_by') }}</label>
                    <select class="form-select" id="filter-group" name="group_by">
                        <option value="day">{{ __('analytics.group_options.day') }}</option>
                        <option value="week">{{ __('analytics.group_options.week') }}</option>
                        <option value="month">{{ __('analytics.group_options.month') }}</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">{{ __('analytics.filters.apply') }}</button>
                    <button type="button" class="btn btn-outline-secondary flex-fill" id="filters-reset">{{ __('analytics.filters.reset') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div id="analytics-alerts"></div>

    <div class="row g-4 mb-4" id="analytics-summary"></div>

    <div class="card mb-4">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center">
            <div>
                <h5 class="mb-1">{{ __('analytics.finance.heading') }}</h5>
                <p class="mb-0 text-muted">{{ __('analytics.finance.timeline') }}</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" data-export="revenue">{{ __('analytics.finance.details') }}</button>
                <button class="btn btn-sm btn-outline-primary" data-export="average">{{ __('analytics.finance.average_ticket') }}</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="chart-wrapper" id="revenue-timeline"></div>
                </div>
                <div class="col-lg-5">
                    <div class="chart-wrapper" id="average-ticket-chart"></div>
                </div>
                <div class="col-12">
                    <h6 class="mb-3">{{ __('analytics.finance.sources') }}</h6>
                    <div id="revenue-sources" class="d-flex flex-column gap-3"></div>
                </div>
                <div class="col-12">
                    <h6 class="mb-3">{{ __('analytics.ai.heading') }}</h6>
                    <ul class="list-unstyled mb-0" id="finance-insights"></ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center">
            <div>
                <h5 class="mb-1">{{ __('analytics.clients.heading') }}</h5>
                <p class="mb-0 text-muted">{{ __('analytics.clients.funnel') }}</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" data-export="funnel">{{ __('analytics.finance.details') }}</button>
                <button class="btn btn-sm btn-outline-primary" data-export="segments">{{ __('analytics.clients.segments') }}</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div id="funnel-chart" class="chart-wrapper"></div>
                </div>
                <div class="col-lg-6">
                    <div id="segments-chart" class="chart-wrapper"></div>
                </div>
                <div class="col-lg-6">
                    <h6 class="mb-3">{{ __('analytics.clients.churn') }}</h6>
                    <div id="churn-table" class="table-responsive"></div>
                </div>
                <div class="col-lg-6">
                    <h6 class="mb-3">{{ __('analytics.clients.ltv') }}</h6>
                    <div id="ltv-table" class="table-responsive"></div>
                </div>
                <div class="col-12">
                    <h6 class="mb-3">{{ __('analytics.ai.recommendations') }}</h6>
                    <ul class="list-unstyled mb-0" id="client-insights"></ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-5">
        <div class="card-header">
            <h5 class="mb-1">{{ __('analytics.ai.heading') }}</h5>
            <p class="mb-0 text-muted">{{ __('analytics.ai.forecast') }}</p>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase">{{ __('analytics.ai.forecast') }}</h6>
                            <div class="display-6 fw-semibold" id="forecast-value">—</div>
                            <p class="text-muted mb-2 small" id="forecast-comment"></p>
                        </div>
                        <div class="small text-muted" id="forecast-confidence"></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="border rounded p-3 h-100">
                        <h6 class="text-muted text-uppercase">{{ __('analytics.ai.associations') }}</h6>
                        <ul class="list-unstyled mb-0" id="ai-associations"></ul>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="border rounded p-3 h-100">
                        <h6 class="text-muted text-uppercase">{{ __('analytics.ai.pricing') }}</h6>
                        <ul class="list-unstyled mb-0" id="ai-pricing"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <style>
        .chart-wrapper {
            min-height: 260px;
            background: var(--bs-body-bg);
            border: 1px dashed rgba(105, 108, 255, 0.12);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .chart-wrapper svg {
            width: 100%;
            height: 220px;
        }

        .chart-empty {
            color: var(--bs-secondary-color);
            text-align: center;
            padding: 2rem 1rem;
        }

        .progress-bar-soft {
            height: 8px;
            border-radius: 999px;
            background: linear-gradient(90deg, rgba(105,108,255,0.18), rgba(105,108,255,0.5));
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const i18n = @json(trans('analytics'));
            const loadingText = @json(trans('menu.loading'));

            function getCookie(name) {
                const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
                return match ? decodeURIComponent(match[2]) : null;
            }

            function authHeaders(extra = {}) {
                const token = getCookie('token');
                const headers = Object.assign({ 'Accept': 'application/json' }, extra);
                if (token) headers['Authorization'] = 'Bearer ' + token;
                return headers;
            }

            const state = {
                filters: {
                    start_date: null,
                    end_date: null,
                    compare_to: 'previous_period',
                    group_by: 'day',
                },
                data: null,
            };

            const summaryContainer = document.getElementById('analytics-summary');
            const alertsContainer = document.getElementById('analytics-alerts');
            const revenueTimeline = document.getElementById('revenue-timeline');
            const averageTicketChart = document.getElementById('average-ticket-chart');
            const revenueSources = document.getElementById('revenue-sources');
            const financeInsights = document.getElementById('finance-insights');
            const clientInsights = document.getElementById('client-insights');
            const funnelChart = document.getElementById('funnel-chart');
            const segmentsChart = document.getElementById('segments-chart');
            const churnTable = document.getElementById('churn-table');
            const ltvTable = document.getElementById('ltv-table');
            const forecastValue = document.getElementById('forecast-value');
            const forecastComment = document.getElementById('forecast-comment');
            const forecastConfidence = document.getElementById('forecast-confidence');
            const aiAssociations = document.getElementById('ai-associations');
            const aiPricing = document.getElementById('ai-pricing');
            const exportButtons = document.querySelectorAll('[data-export]');
            const exportAllButton = document.getElementById('export-all');

            const filterForm = document.getElementById('analytics-filters');
            const startInput = document.getElementById('filter-start');
            const endInput = document.getElementById('filter-end');
            const compareSelect = document.getElementById('filter-compare');
            const groupSelect = document.getElementById('filter-group');
            const resetButton = document.getElementById('filters-reset');

            const formatter = new Intl.NumberFormat(undefined, { maximumFractionDigits: 1 });
            const currencyFormatter = new Intl.NumberFormat(undefined, { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 });

            function initFilters() {
                const today = new Date();
                const defaultEnd = today.toISOString().slice(0, 10);
                const defaultStartDate = new Date();
                defaultStartDate.setDate(today.getDate() - 29);
                const defaultStart = defaultStartDate.toISOString().slice(0, 10);

                startInput.value = defaultStart;
                endInput.value = defaultEnd;
                state.filters.start_date = defaultStart;
                state.filters.end_date = defaultEnd;
            }

            function showAlert(message, type = 'info') {
                alertsContainer.innerHTML = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
            }

            function clearAlert() {
                alertsContainer.innerHTML = '';
            }

            function buildQuery(params) {
                const query = new URLSearchParams();
                Object.entries(params).forEach(([key, value]) => {
                    if (value !== null && value !== '') {
                        query.append(key, value);
                    }
                });
                return query.toString();
            }

            function fetchAnalytics() {
                clearAlert();
                summaryContainer.innerHTML = `<div class="col-12 text-center text-muted py-5">${loadingText}</div>`;
                revenueTimeline.innerHTML = '';
                averageTicketChart.innerHTML = '';
                revenueSources.innerHTML = '';
                financeInsights.innerHTML = '';
                clientInsights.innerHTML = '';
                funnelChart.innerHTML = '';
                segmentsChart.innerHTML = '';
                churnTable.innerHTML = '';
                ltvTable.innerHTML = '';
                forecastValue.textContent = '—';
                forecastComment.textContent = '';
                forecastConfidence.textContent = '';
                aiAssociations.innerHTML = '';
                aiPricing.innerHTML = '';

                const query = buildQuery(state.filters);

                fetch(`/api/v1/analytics/overview?${query}`, {
                    headers: authHeaders(),
                })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error('Failed to load analytics');
                        }
                        return response.json();
                    })
                    .then((payload) => {
                        state.data = payload.data;
                        renderAnalytics();
                    })
                    .catch((error) => {
                        console.error(error);
                        showAlert(i18n.messages.load_failed, 'danger');
                    });
            }

            function renderAnalytics() {
                if (!state.data) return;

                const { summary, finance, clients, ai } = state.data;

                renderSummary(summary);
                renderTimeline(revenueTimeline, finance.revenue_timeline, i18n.finance.timeline);
                renderTimeline(averageTicketChart, finance.average_ticket_trend, i18n.finance.average_ticket, true);
                renderRevenueSources(finance.revenue_sources);
                renderList(financeInsights, finance.insights);
                renderFunnel(clients.funnel);
                renderSegments(clients.segments);
                renderChurn(clients.churn);
                renderLtv(clients.ltv);
                renderList(clientInsights, clients.insights);
                renderAi(ai);
            }

            function renderSummary(items) {
                if (!Array.isArray(items) || !items.length) {
                    summaryContainer.innerHTML = `<div class="col-12"><div class="chart-empty">${i18n.labels.no_data}</div></div>`;
                    return;
                }

                const template = items.map((item) => {
                    const value = formatValue(item.value, item.suffix);
                    const change = renderChange(item.change);
                    const breakdown = renderBreakdown(item.breakdown, item.suffix);

                    return `
                        <div class="col-sm-6 col-xl-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <span class="text-muted text-uppercase small">${item.label}</span>
                                        ${change}
                                    </div>
                                    <div class="display-6 fw-semibold">${value}</div>
                                    ${breakdown}
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');

                summaryContainer.innerHTML = template;
            }

            function formatValue(value, suffix = '') {
                if (suffix === i18n.currency || suffix === i18n.labels.currency) {
                    return currencyFormatter.format(value || 0);
                }

                if (suffix === '%') {
                    return `${formatter.format(value || 0)}%`;
                }

                return formatter.format(value || 0) + (suffix ? ` ${suffix}` : '');
            }

            function renderChange(change) {
                if (!change) return '';
                const arrows = {
                    up: 'ri-arrow-up-s-line text-success',
                    down: 'ri-arrow-down-s-line text-danger',
                    equal: 'ri-subtract-line text-muted',
                };
                const icon = arrows[change.direction] || arrows.equal;
                const percentage = formatter.format(change.percentage || 0);
                return `
                    <span class="badge bg-label-${change.direction === 'down' ? 'danger' : (change.direction === 'up' ? 'success' : 'secondary')}">
                        <i class="ri ${icon} me-1"></i>
                        ${percentage}%
                    </span>
                `;
            }

            function renderBreakdown(breakdown, suffix) {
                if (!Array.isArray(breakdown) || !breakdown.length) {
                    if (breakdown && breakdown.client) {
                        return `<p class="mb-0 text-muted small">${breakdown.client} — ${formatValue(breakdown.value, suffix)}</p>`;
                    }
                    return '';
                }

                const items = breakdown.map((item) => {
                    return `
                        <div class="d-flex justify-content-between align-items-center small text-muted">
                            <span>${item.label}</span>
                            <span>${formatValue(item.value, suffix)} (${formatter.format(item.share)}%)</span>
                        </div>
                        <div class="progress-bar-soft mb-2" style="width:${item.share}%"></div>
                    `;
                }).join('');

                return `<div class="mt-3">${items}</div>`;
            }

            function renderTimeline(container, dataset, title, smooth = false) {
                if (!dataset || !dataset.labels || !dataset.labels.length) {
                    container.innerHTML = `<div class="chart-empty">${i18n.labels.no_data}</div>`;
                    return;
                }

                const values = dataset.series.map(Number);
                const maxValue = Math.max(...values, 1);
                const width = container.clientWidth || 600;
                const height = 220;
                const padding = 20;
                const stepX = (width - padding * 2) / Math.max(values.length - 1, 1);
                const points = values.map((value, index) => {
                    const x = padding + stepX * index;
                    const y = height - padding - (value / maxValue) * (height - padding * 2);
                    return `${x},${y}`;
                }).join(' ');

                const labels = dataset.labels.map((label, index) => {
                    const x = padding + stepX * index;
                    const y = height - 4;
                    return `<text x="${x}" y="${y}" text-anchor="middle" class="small" fill="currentColor" opacity="0.65">${label}</text>`;
                }).join('');

                const areaPoints = `${padding},${height - padding} ${points} ${padding + stepX * (values.length - 1)},${height - padding}`;

                container.innerHTML = `
                    <h6 class="mb-3">${title}</h6>
                    <svg viewBox="0 0 ${width} ${height}">
                        <polyline points="${points}" fill="none" stroke="var(--bs-primary)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>
                        <polygon points="${areaPoints}" fill="rgba(105,108,255,0.12)"></polygon>
                        ${values.map((value, index) => {
                            const [x, y] = points.split(' ')[index].split(',');
                            return `<circle cx="${x}" cy="${y}" r="4" fill="var(--bs-body-bg)" stroke="var(--bs-primary)" stroke-width="2"></circle>`;
                        }).join('')}
                        ${labels}
                    </svg>
                `;
            }

            function renderRevenueSources(sources) {
                if (!sources || !sources.labels || !sources.labels.length) {
                    revenueSources.innerHTML = `<div class="chart-empty">${i18n.labels.no_data}</div>`;
                    return;
                }

                const total = sources.series.reduce((acc, value) => acc + Number(value), 0) || 1;
                revenueSources.innerHTML = sources.labels.map((label, index) => {
                    const value = Number(sources.series[index] || 0);
                    const share = Math.round((value / total) * 1000) / 10;
                    return `
                        <div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold">${label}</span>
                                <span class="text-muted">${currencyFormatter.format(value)} · ${share}%</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar" role="progressbar" style="width: ${share}%"></div>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            function renderList(container, items) {
                if (!Array.isArray(items) || !items.length) {
                    container.innerHTML = `<li class="text-muted">${i18n.labels.no_data}</li>`;
                    return;
                }

                container.innerHTML = items.map((item) => `<li class="mb-2"><i class="ri ri-sparkling-line text-primary me-2"></i>${item}</li>`).join('');
            }

            function renderFunnel(funnel) {
                if (!funnel || !Array.isArray(funnel.stages) || !funnel.stages.length) {
                    funnelChart.innerHTML = `<div class="chart-empty">${i18n.labels.no_data}</div>`;
                    return;
                }

                const max = Math.max(...funnel.stages.map((stage) => stage.value), 1);
                funnelChart.innerHTML = `
                    <h6 class="mb-3">${i18n.clients.funnel}</h6>
                    <div class="d-flex flex-column gap-3">
                        ${funnel.stages.map((stage) => {
                            const width = Math.max((stage.value / max) * 100, 5);
                            return `
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold">${stage.label}</span>
                                        <span class="text-muted small">${formatter.format(stage.value)} · ${i18n.labels.conversion} ${formatter.format(stage.conversion)}%</span>
                                    </div>
                                    <div class="progress" style="height: 16px;">
                                        <div class="progress-bar bg-primary" style="width:${width}%"></div>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                        <div class="text-muted small">${i18n.labels.conversion}: ${formatter.format(funnel.conversion)}%</div>
                    </div>
                `;
            }

            function renderSegments(segments) {
                if (!segments) {
                    segmentsChart.innerHTML = `<div class="chart-empty">${i18n.labels.no_data}</div>`;
                    return;
                }

                const total = Object.values(segments).reduce((acc, value) => acc + Number(value), 0) || 1;
                segmentsChart.innerHTML = `
                    <h6 class="mb-3">${i18n.clients.segments}</h6>
                    <div class="d-flex flex-column gap-3">
                        ${Object.entries(segments).map(([key, value]) => {
                            const share = Math.round((Number(value) / total) * 1000) / 10;
                            const label = i18n.summary_breakdown[key] || key;
                            return `
                                <div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-semibold text-capitalize">${label}</span>
                                        <span class="text-muted small">${formatter.format(value)} · ${share}%</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-info" style="width:${share}%"></div>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `;
            }

            function renderChurn(churn) {
                if (!churn) {
                    churnTable.innerHTML = `<div class="chart-empty">${i18n.labels.no_data}</div>`;
                    return;
                }

                const rows = (churn.at_risk || []).map((item) => {
                    return `<tr><td>${item.client}</td><td>${item.last_visit}</td><td>${item.days_inactive}</td></tr>`;
                }).join('');

                churnTable.innerHTML = `
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('analytics.churn_table.client') }}</th>
                                <th>{{ __('analytics.churn_table.last_visit') }}</th>
                                <th>{{ __('analytics.churn_table.days_inactive') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows || `<tr><td colspan="3" class="text-center text-muted">${i18n.labels.no_data}</td></tr>`}
                        </tbody>
                    </table>
                    <div class="text-muted small">${i18n.clients.at_risk}: ${formatter.format(churn.rate || 0)}%</div>
                `;
            }

            function renderLtv(ltv) {
                if (!ltv) {
                    ltvTable.innerHTML = `<div class="chart-empty">${i18n.labels.no_data}</div>`;
                    return;
                }

                const rows = (ltv.top_services || []).map((item) => {
                    return `<tr><td>${item.service}</td><td>${currencyFormatter.format(item.value)}</td></tr>`;
                }).join('');

                ltvTable.innerHTML = `
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('analytics.ltv_table.service') }}</th>
                                <th>{{ __('analytics.ltv_table.ltv') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows || `<tr><td colspan="2" class="text-center text-muted">${i18n.labels.no_data}</td></tr>`}
                        </tbody>
                    </table>
                    <div class="text-muted small">${i18n.clients.ltv_avg}: ${currencyFormatter.format(ltv.average || 0)}</div>
                `;
            }

            function renderAi(ai) {
                if (!ai) return;
                if (ai.forecast) {
                    forecastValue.textContent = currencyFormatter.format(ai.forecast.value || 0);
                    forecastComment.textContent = ai.forecast.comment || '';
                    forecastConfidence.textContent = `${i18n.forecast.confidence}: ${ai.forecast.confidence || 0}%`;
                }

                renderList(aiAssociations, ai.associations);
                renderList(aiPricing, ai.pricing);
            }

            function downloadCsv(filename, headers, rows) {
                const csvRows = [];
                csvRows.push(headers.join(';'));
                rows.forEach((row) => {
                    csvRows.push(row.map((value) => `"${String(value).replace(/"/g, '""')}"`).join(';'));
                });
                const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', filename);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            }

            function handleExport(type) {
                if (!state.data) return;
                const { finance, clients } = state.data;
                let headers = [];
                let rows = [];
                let filename = `analytics-${type}-${Date.now()}.csv`;

                if (type === 'revenue') {
                    headers = ['Label', 'Revenue'];
                    rows = finance.revenue_timeline.labels.map((label, index) => [label, finance.revenue_timeline.series[index] || 0]);
                } else if (type === 'average') {
                    headers = ['Label', 'Average Ticket'];
                    rows = finance.average_ticket_trend.labels.map((label, index) => [label, finance.average_ticket_trend.series[index] || 0]);
                } else if (type === 'funnel') {
                    headers = ['Stage', 'Value', 'Conversion'];
                    rows = clients.funnel.stages.map((stage) => [stage.label, stage.value, stage.conversion]);
                } else if (type === 'segments') {
                    headers = ['Segment', 'Clients'];
                    rows = Object.entries(clients.segments).map(([key, value]) => [i18n.summary_breakdown[key] || key, value]);
                }

                if (!rows.length) {
                    showAlert(i18n.labels.no_data, 'warning');
                    return;
                }

                downloadCsv(filename, headers, rows);
            }

            function exportAll() {
                if (!state.data) return;
                const rows = [];
                state.data.summary.forEach((item) => {
                    rows.push([item.label, item.value, item.change ? item.change.percentage : 0]);
                });
                downloadCsv(`analytics-summary-${Date.now()}.csv`, ['Metric', 'Value', 'Change %'], rows);
            }

            filterForm.addEventListener('submit', function (event) {
                event.preventDefault();
                state.filters.start_date = startInput.value;
                state.filters.end_date = endInput.value;
                state.filters.compare_to = compareSelect.value;
                state.filters.group_by = groupSelect.value;
                fetchAnalytics();
            });

            resetButton.addEventListener('click', function () {
                initFilters();
                compareSelect.value = 'previous_period';
                groupSelect.value = 'day';
                state.filters.compare_to = 'previous_period';
                state.filters.group_by = 'day';
                fetchAnalytics();
            });

            exportButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    handleExport(this.dataset.export);
                });
            });

            exportAllButton.addEventListener('click', exportAll);

            initFilters();
            fetchAnalytics();
        });
    </script>
@endsection
