@extends('layouts.app')

@section('title', __('subscription.title'))

@section('meta')
    <style>
        .subscription-current-card {
            background: linear-gradient(135deg, rgba(105, 108, 255, 0.12), rgba(3, 195, 236, 0.08));
            border: 1px solid rgba(105, 108, 255, 0.18);
        }

        .subscription-plan-card {
            border: 1px solid transparent;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .subscription-plan-card:hover {
            border-color: rgba(105, 108, 255, 0.4);
            box-shadow: 0 1.5rem 3rem -1.5rem rgba(105, 108, 255, 0.35);
        }

        .subscription-plan-price {
            font-size: 2rem;
            font-weight: 700;
        }

        .subscription-plan-features li + li {
            margin-top: 0.65rem;
        }

        .subscription-comparison-table td,
        .subscription-comparison-table th {
            vertical-align: middle;
        }

        .subscription-keep-lose ul {
            padding-left: 1.5rem;
        }

        .subscription-keep-lose li + li {
            margin-top: 0.5rem;
        }

        .subscription-current-summary {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            height: 100%;
        }

        .subscription-current-actions {
            margin-top: auto;
        }
    </style>
@endsection

@section('content')
    <div class="alert alert-dismissible d-none" role="alert" data-subscription-feedback>
        <span data-feedback-text></span>
        <button type="button" class="btn-close" data-feedback-close aria-label="Close"></button>
    </div>

    <div class="card subscription-current-card mb-4">
        <div class="card-body">
            <div class="row gy-4 align-items-start">
                <div class="col-lg-4">
                    <div class="subscription-current-summary">
                        <div>
                            <p class="text-uppercase text-muted fw-medium mb-1 small">{{ __('subscription.current_plan.title') }}</p>
                            <h3 class="mb-4">{{ __('subscription.subtitle') }}</h3>
                            <div data-current-plan>
                                <p class="text-muted mb-0">{{ __('subscription.current_plan.no_plan') }}</p>
                            </div>
                        </div>

                        <div class="subscription-current-actions">
                            <button type="button" class="btn btn-outline-danger w-100" data-cancel-button disabled>
                                {{ __('subscription.actions.cancel') }}
                            </button>
                            <small class="text-muted d-block mt-2">{{ __('subscription.cancel.note') }}</small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="subscription-keep-lose h-100">
                        <h5 class="fw-semibold mb-3">{{ __('subscription.cancel.title') }}</h5>
                        <p class="text-muted mb-4">{{ __('subscription.cancel.description') }}</p>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="bg-white border rounded h-100 p-4">
                                    <h6 class="fw-semibold mb-3 text-success">{{ __('subscription.cancel.keep_title') }}</h6>
                                    <ul class="text-muted mb-0">
                                        @foreach (__('subscription.cancel.keep') as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-white border rounded h-100 p-4">
                                    <h6 class="fw-semibold mb-3 text-danger">{{ __('subscription.cancel.lose_title') }}</h6>
                                    <ul class="text-muted mb-0">
                                        @foreach (__('subscription.cancel.lose') as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4" data-plans-container></div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-3">{{ __('subscription.comparison_title') }}</h5>
            <div class="table-responsive">
                <table class="table align-middle subscription-comparison-table">
                    <thead>
                        <tr data-comparison-header>
                            <th class="w-50">{{ __('subscription.comparison_feature') }}</th>
                        </tr>
                    </thead>
                    <tbody data-comparison-body></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                <div>
                    <h5 class="mb-1">{{ __('subscription.transactions.title') }}</h5>
                    <p class="text-muted mb-0">{{ __('subscription.subtitle') }}</p>
                </div>
            </div>

            <div class="text-center text-muted py-5" data-transactions-empty>
                <i class="ri ri-bill-line icon-base mb-2"></i>
                <p class="mb-0">{{ __('subscription.transactions.empty') }}</p>
            </div>

            <div class="table-responsive d-none" data-transactions-table>
                <table class="table table-hover align-middle subscription-comparison-table">
                    <thead>
                        <tr>
                            <th>{{ __('subscription.transactions.date') }}</th>
                            <th>{{ __('subscription.transactions.plan') }}</th>
                            <th>{{ __('subscription.transactions.amount') }}</th>
                            <th>{{ __('subscription.transactions.status') }}</th>
                            <th>{{ __('subscription.transactions.payment_id') }}</th>
                        </tr>
                    </thead>
                    <tbody data-transactions-body></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const STRINGS = {
                billingPeriod: @json(__('subscription.billing_period')),
                actions: @json(__('subscription.actions')),
                currentPlan: {
                    noPlan: @json(__('subscription.current_plan.no_plan')),
                    freePlan: @json(__('subscription.current_plan.free_plan')),
                    activeUntil: @json(__('subscription.current_plan.active_until', ['date' => ':date'])),
                },
                comparisonFeature: @json(__('subscription.comparison_feature')),
                alerts: {
                    upgradeError: @json(__('subscription.alerts.upgrade_error')),
                },
                transactions: {
                    empty: @json(__('subscription.transactions.empty')),
                },
            };

            const feedback = document.querySelector('[data-subscription-feedback]');
            const feedbackText = document.querySelector('[data-feedback-text]');
            const feedbackClose = document.querySelector('[data-feedback-close]');
            const currentPlanContainer = document.querySelector('[data-current-plan]');
            const cancelButton = document.querySelector('[data-cancel-button]');
            const plansContainer = document.querySelector('[data-plans-container]');
            const comparisonHeader = document.querySelector('[data-comparison-header]');
            const comparisonBody = document.querySelector('[data-comparison-body]');
            const transactionsEmpty = document.querySelector('[data-transactions-empty]');
            const transactionsTable = document.querySelector('[data-transactions-table]');
            const transactionsBody = document.querySelector('[data-transactions-body]');

            function getCookie(name) {
                const match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.$?*|{}()\[\]\\\/\+^]/g, '\\$&') + '=([^;]*)'));
                return match ? decodeURIComponent(match[1]) : null;
            }

            const baseHeaders = {
                'Accept': 'application/json',
                'Accept-Language': document.documentElement.lang || 'en',
                'X-Requested-With': 'XMLHttpRequest',
            };

            const token = getCookie('token');
            if (token) {
                baseHeaders['Authorization'] = 'Bearer ' + token;
            }

            function hideFeedback() {
                if (!feedback) {
                    return;
                }

                feedback.classList.add('d-none');
                feedback.classList.remove('alert-success', 'alert-danger');
                if (feedbackText) {
                    feedbackText.textContent = '';
                }
            }

            function showFeedback(type, message) {
                if (!feedback || !feedbackText) {
                    return;
                }

                feedback.classList.remove('d-none', 'alert-success', 'alert-danger');
                feedback.classList.add(type === 'error' ? 'alert-danger' : 'alert-success');
                feedbackText.textContent = message;
            }

            if (feedbackClose) {
                feedbackClose.addEventListener('click', function () {
                    hideFeedback();
                });
            }

            function apiFetch(url, options = {}) {
                const config = {
                    method: options.method || 'GET',
                    headers: Object.assign({}, baseHeaders, options.headers || {}),
                };

                if (options.body !== undefined) {
                    if (options.body instanceof FormData) {
                        config.body = options.body;
                    } else {
                        config.headers['Content-Type'] = 'application/json';
                        config.body = JSON.stringify(options.body);
                    }
                }

                return fetch(url, config).then(async (response) => {
                    if (response.status === 401) {
                        window.location.href = '/login';
                        throw new Error('Unauthorized');
                    }

                    if (response.status === 204) {
                        return {};
                    }

                    let data = {};
                    try {
                        data = await response.json();
                    } catch (error) {
                        data = {};
                    }

                    if (!response.ok) {
                        const error = new Error(data.message || STRINGS.alerts.upgradeError);
                        error.data = data;
                        error.status = response.status;
                        throw error;
                    }

                    return data;
                });
            }

            function renderCurrentPlan(plan) {
                if (!currentPlanContainer) {
                    return;
                }

                if (!plan) {
                    currentPlanContainer.innerHTML = '<p class="text-muted mb-0">' + STRINGS.currentPlan.noPlan + '</p>';
                    if (cancelButton) {
                        cancelButton.disabled = true;
                    }
                    return;
                }

                const initialsSource = (plan.slug || plan.name || '').toString().trim();
                const initials = initialsSource ? initialsSource.slice(0, 2).toUpperCase() : '—';

                const planTagline = plan.tagline ? '<div class="text-muted small">' + plan.tagline + '</div>' : '';
                const badge = plan.badge ? '<span class="badge bg-label-primary">' + plan.badge + '</span>' : '';
                const nameRow = `
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="avatar avatar-lg bg-primary text-white">
                            <span class="avatar-initial fw-semibold text-uppercase">${initials}</span>
                        </div>
                        <div>
                            <h4 class="mb-1">${plan.name}</h4>
                            ${planTagline}
                        </div>
                        ${badge}
                    </div>
                `;

                let statusBlock = '';
                if (plan.active_until_label) {
                    statusBlock = `
                        <div class="d-flex align-items-center gap-2">
                            <i class="ri ri-time-line text-primary"></i>
                            <span class="small text-muted">${plan.active_until_label}</span>
                        </div>
                    `;
                } else {
                    statusBlock = '<div class="small text-muted">' + STRINGS.currentPlan.freePlan + '</div>';
                }

                currentPlanContainer.innerHTML = nameRow + `
                    <div class="border rounded p-3 bg-white">${statusBlock}</div>
                `;

                if (cancelButton) {
                    cancelButton.disabled = !plan.is_active;
                }
            }

            function renderPlans(data) {
                if (!plansContainer) {
                    return;
                }

                plansContainer.innerHTML = '';

                (data.plans || []).forEach(function (plan) {
                    const col = document.createElement('div');
                    col.className = 'col-12 col-md-6 col-xl-4';

                    const cardClasses = ['card', 'subscription-plan-card', 'h-100'];
                    if (plan.is_current) {
                        cardClasses.push('border-primary', 'shadow-sm');
                    }

                    const priceBlock = plan.price_display
                        ? '<div class="subscription-plan-price">' + plan.price_display + '</div><div class="text-muted">' + STRINGS.billingPeriod + '</div>'
                        : '<div class="fw-semibold">' + STRINGS.currentPlan.freePlan + '</div>';

                    const features = (plan.features || []).map(function (feature) {
                        return '<li class="d-flex align-items-center gap-2"><i class="ri ri-check-line text-success"></i><span>' + feature + '</span></li>';
                    }).join('');

                    const featuresList = features
                        ? '<ul class="subscription-plan-features list-unstyled flex-grow-1 mb-4">' + features + '</ul>'
                        : '';

                    let actionButton = '';
                    if (plan.is_current) {
                        actionButton = '<button class="btn btn-outline-secondary w-100" disabled>' + STRINGS.actions.current + '</button>';
                    } else if (plan.is_upgrade && (plan.is_free || data.yookassa.enabled)) {
                        actionButton = '<button class="btn btn-primary w-100" data-upgrade-button data-plan-id="' + plan.id + '">' + STRINGS.actions.upgrade.replace(':plan', plan.name) + '</button>';
                    } else {
                        actionButton = '<button class="btn btn-outline-secondary w-100" disabled>' + STRINGS.actions.upgrade.replace(':plan', plan.name) + '</button>';
                    }

                    const badge = plan.badge ? '<span class="badge bg-label-primary">' + plan.badge + '</span>' : '';

                    col.innerHTML = `
                        <div class="${cardClasses.join(' ')}">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="mb-1">${plan.name}</h5>
                                        <p class="text-muted mb-0">${plan.tagline || ''}</p>
                                    </div>
                                    ${badge}
                                </div>
                                <div class="mb-3">${priceBlock}</div>
                                ${featuresList}
                                <div class="mt-auto">${actionButton}</div>
                            </div>
                        </div>
                    `;

                    plansContainer.appendChild(col);
                });

                plansContainer.querySelectorAll('[data-upgrade-button]').forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.preventDefault();
                        const planId = parseInt(button.getAttribute('data-plan-id'), 10);
                        if (!planId || button.disabled) {
                            return;
                        }

                        button.disabled = true;
                        apiFetch('/api/v1/subscription/upgrade', {
                            method: 'POST',
                            body: { plan_id: planId },
                        })
                            .then(function (data) {
                                if (data.redirect_url) {
                                    window.location.href = data.redirect_url;
                                    return;
                                }

                                if (data.message) {
                                    showFeedback('success', data.message);
                                }

                                return loadSubscription();
                            })
                            .catch(function (error) {
                                showFeedback('error', error.message || STRINGS.alerts.upgradeError);
                            })
                            .finally(function () {
                                button.disabled = false;
                            });
                    });
                });
            }

            function renderComparison(data) {
                if (!comparisonHeader || !comparisonBody) {
                    return;
                }

                const plans = data.plans || [];
                const comparison = data.comparison || [];

                comparisonHeader.innerHTML = '<th class="w-50">' + STRINGS.comparisonFeature + '</th>' + plans.map(function (plan) {
                    return '<th class="text-center">' + plan.name + '</th>';
                }).join('');

                comparisonBody.innerHTML = comparison.map(function (row) {
                    const planCells = plans.map(function (plan) {
                        const value = row.plans ? row.plans[plan.slug] : null;
                        if (value === true) {
                            return '<td class="text-center"><i class="ri ri-check-line text-success"></i></td>';
                        }

                        if (value === false) {
                            return '<td class="text-center"><i class="ri ri-close-line text-muted"></i></td>';
                        }

                        if (value === null || value === undefined) {
                            return '<td class="text-center text-muted">—</td>';
                        }

                        return '<td class="text-center"><span class="badge bg-label-info">' + value + '</span></td>';
                    }).join('');

                    return '<tr>' +
                        '<td><div class="fw-semibold">' + (row.feature || '') + '</div><div class="text-muted small">' + (row.description || '') + '</div></td>' +
                        planCells +
                        '</tr>';
                }).join('');
            }

            function renderTransactions(transactions) {
                if (!transactionsEmpty || !transactionsTable || !transactionsBody) {
                    return;
                }

                if (!transactions || transactions.length === 0) {
                    transactionsEmpty.classList.remove('d-none');
                    transactionsTable.classList.add('d-none');
                    transactionsBody.innerHTML = '';
                    return;
                }

                transactionsEmpty.classList.add('d-none');
                transactionsTable.classList.remove('d-none');

                transactionsBody.innerHTML = transactions.map(function (transaction) {
                    const paymentId = transaction.payment_id ? transaction.payment_id : '—';
                    const statusBadge = transaction.status_badge || 'bg-label-secondary';
                    const statusLabel = transaction.status_label || '';

                    return '<tr>' +
                        '<td class="text-nowrap">' + (transaction.created_at_formatted || '') + '</td>' +
                        '<td>' + (transaction.plan_name || '') + '</td>' +
                        '<td>' + (transaction.amount_display || '') + '</td>' +
                        '<td><span class="badge ' + statusBadge + '">' + statusLabel + '</span></td>' +
                        '<td class="text-break"><code>' + paymentId + '</code></td>' +
                        '</tr>';
                }).join('');
            }

            function loadSubscription() {
                return apiFetch('/api/v1/subscription')
                    .then(function (data) {
                        renderCurrentPlan(data.current_plan);
                        renderPlans(data);
                        renderComparison(data);
                        renderTransactions(data.transactions);
                    })
                    .catch(function (error) {
                        showFeedback('error', error.message || STRINGS.alerts.upgradeError);
                    });
            }

            if (cancelButton) {
                cancelButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    if (cancelButton.disabled) {
                        return;
                    }

                    cancelButton.disabled = true;
                    apiFetch('/api/v1/subscription/cancel', {
                        method: 'POST',
                    })
                        .then(function (data) {
                            if (data.message) {
                                showFeedback('success', data.message);
                            }
                            return loadSubscription();
                        })
                        .catch(function (error) {
                            showFeedback('error', error.message || STRINGS.alerts.upgradeError);
                        })
                        .finally(function () {
                            cancelButton.disabled = false;
                        });
                });
            }

            loadSubscription();
        });
    </script>
@endsection
