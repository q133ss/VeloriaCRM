@extends('layouts.app')

@section('title', __('services.title'))

@section('content')
    <style>
        .services-page {
            --services-border: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.12);
            --services-shadow: 0 24px 54px -36px rgba(37, 26, 84, 0.42);
        }

        .services-page .services-hero,
        .services-page .services-surface {
            border: 1px solid var(--services-border);
            border-radius: 1.5rem;
            box-shadow: var(--services-shadow);
            background: color-mix(in srgb, var(--bs-card-bg) 96%, transparent);
        }

        .services-page .services-hero {
            position: relative;
            overflow: hidden;
            padding: 1.5rem;
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.14), transparent 34%),
                linear-gradient(140deg, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.06), rgba(var(--bs-info-rgb, 0, 207, 232), 0.05) 58%, rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.12));
        }

        .services-page .services-hero::after {
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

        .services-page .services-hero > * {
            position: relative;
            z-index: 1;
        }

        .services-page .services-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.72);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .services-page .services-hero .btn {
            white-space: nowrap;
        }

        .services-page .services-surface {
            padding: 1.25rem;
        }

        .services-page .services-search-icon {
            left: 0.15rem;
            pointer-events: none;
        }

        .services-page .services-search-input {
            padding-left: 1.3rem !important;
        }

        .services-page .services-summary-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.75rem;
            border-radius: 999px;
            background: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.04);
            color: var(--bs-secondary-color);
            font-size: 0.88rem;
        }

        .services-page details.services-advanced summary {
            cursor: pointer;
            list-style: none;
            color: var(--bs-secondary-color);
            font-size: 0.9rem;
            font-weight: 600;
        }

        .services-page details.services-advanced summary::-webkit-details-marker {
            display: none;
        }

        .services-page details.services-advanced summary::after {
            content: 'Развернуть';
            margin-left: 0.5rem;
        }

        .services-page details.services-advanced[open] summary::after {
            content: 'Свернуть';
        }

        .services-page .services-group-card {
            border: 1px solid rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.08);
            border-radius: 1.35rem;
            background: color-mix(in srgb, var(--bs-card-bg) 98%, transparent);
            box-shadow: var(--services-shadow);
        }

        .services-page .service-row + .service-row {
            margin-top: 0.85rem;
            padding-top: 0.85rem;
            border-top: 1px solid rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.08);
        }

        .services-page .service-price {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.12);
            color: var(--bs-primary);
            font-weight: 700;
            font-size: 0.85rem;
        }

        .services-page .service-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 0.45rem;
            color: var(--bs-secondary-color);
            font-size: 0.9rem;
        }

        .services-page .service-meta span {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .services-page .service-secondary {
            margin-top: 0.4rem;
            font-size: 0.82rem;
            color: var(--bs-secondary-color);
        }

        .services-page .service-actions .btn,
        .services-page .group-actions .btn {
            white-space: nowrap;
        }

        @media (max-width: 767.98px) {
            .services-page .service-row {
                gap: 0.75rem;
            }

            .services-page .service-actions {
                width: 100%;
            }

            .services-page .service-actions .btn-group {
                width: 100%;
            }

            .services-page .service-actions .btn-group .btn {
                flex: 1 1 auto;
            }
        }
    </style>

    <div class="services-page d-flex flex-column gap-4">
        <section class="services-hero">
            <div class="d-flex flex-column flex-xl-row align-items-xl-start justify-content-between gap-4">
                <div class="d-flex flex-column gap-3">
                    <span class="services-eyebrow">
                        <i class="ri ri-scissors-cut-line text-primary"></i>
                        Каталог услуг
                    </span>
                    <div>
                        <h4 class="mb-1">{{ __('services.title') }}</h4>
                        <p class="text-muted mb-0">{{ __('services.subtitle') }}</p>
                    </div>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2 align-self-start">
                    <button type="button" class="btn btn-outline-secondary" id="new-category-btn">
                        <i class="ri ri-folder-add-line me-1"></i>
                        {{ __('services.actions.create_category') }}
                    </button>
                    <button type="button" class="btn btn-primary" id="new-service-btn">
                        <i class="ri ri-scissors-2-line me-1"></i>
                        {{ __('services.actions.create_service') }}
                    </button>
                </div>
            </div>
        </section>

        <div id="services-alerts"></div>

        <section class="services-surface">
            <form id="services-filters-form" class="d-flex flex-column gap-3">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-6">
                        <label for="filter-search" class="form-label">{{ __('services.filters.search_label') }}</label>
                        <div class="position-relative">
                            <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted">
                                <span class="services-search-icon position-absolute top-50 translate-middle-y text-muted">
                                    <i class="ri ri-search-line"></i>
                                </span>
                            </span>
                            <input
                                type="text"
                                class="form-control services-search-input"
                                id="filter-search"
                                name="search"
                                placeholder="{{ __('services.filters.search_placeholder') }}"
                            />
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label for="filter-category" class="form-label">{{ __('services.filters.category_label') }}</label>
                        <select class="form-select" id="filter-category" name="category_id">
                            <option value="">{{ __('services.filters.category_placeholder') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">{{ __('services.filters.apply') }}</button>
                        <button type="button" class="btn btn-outline-secondary flex-fill" id="filters-reset">
                            {{ __('services.filters.reset') }}
                        </button>
                    </div>
                </div>

                <details class="services-advanced">
                    <summary>Расширенный фильтр</summary>
                    <div class="row g-3 mt-1">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">{{ __('services.filters.price_label') }}</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="form-control"
                                        id="filter-price-min"
                                        name="price_min"
                                        placeholder="{{ __('services.filters.price_min_placeholder') }}"
                                    />
                                </div>
                                <div class="col-6">
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="form-control"
                                        id="filter-price-max"
                                        name="price_max"
                                        placeholder="{{ __('services.filters.price_max_placeholder') }}"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">{{ __('services.filters.duration_label') }}</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input
                                        type="number"
                                        min="0"
                                        step="5"
                                        class="form-control"
                                        id="filter-duration-min"
                                        name="duration_min"
                                        placeholder="{{ __('services.filters.duration_min_placeholder') }}"
                                    />
                                </div>
                                <div class="col-6">
                                    <input
                                        type="number"
                                        min="0"
                                        step="5"
                                        class="form-control"
                                        id="filter-duration-max"
                                        name="duration_max"
                                        placeholder="{{ __('services.filters.duration_max_placeholder') }}"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label for="filter-sort" class="form-label">{{ __('services.filters.sort_label') }}</label>
                            <select class="form-select" id="filter-sort" name="sort">
                                <option value="name">{{ __('services.filters.sort_options.name') }}</option>
                                <option value="base_price">{{ __('services.filters.sort_options.base_price') }}</option>
                                <option value="duration_min">{{ __('services.filters.sort_options.duration_min') }}</option>
                                <option value="created_at">{{ __('services.filters.sort_options.created_at') }}</option>
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label for="filter-direction" class="form-label">{{ __('services.filters.direction_label') }}</label>
                            <select class="form-select" id="filter-direction" name="direction">
                                <option value="asc">{{ __('services.filters.direction_options.asc') }}</option>
                                <option value="desc">{{ __('services.filters.direction_options.desc') }}</option>
                            </select>
                        </div>
                    </div>
                </details>

                <div id="services-summary" class="d-flex flex-wrap gap-2"></div>
            </form>
        </section>

        <div id="services-groups" class="d-flex flex-column gap-3"></div>
    </div>

    <div class="modal fade" id="serviceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="serviceModalLabel">{{ __('services.modals.service.create_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="service-form" onsubmit="return false;">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="service-name" class="form-label">{{ __('services.modals.service.name') }}</label>
                                <input type="text" class="form-control" id="service-name" name="name" required />
                            </div>
                            <div class="col-md-6">
                                <label for="service-category" class="form-label">{{ __('services.modals.service.category') }}</label>
                                <select class="form-select" id="service-category" name="category_id">
                                    <option value="">{{ __('services.modals.service.category_placeholder') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="service-price" class="form-label">{{ __('services.modals.service.base_price') }}</label>
                                <input type="number" class="form-control" id="service-price" name="base_price" min="0" step="0.01" required />
                            </div>
                            <div class="col-md-4">
                                <label for="service-cost" class="form-label">{{ __('services.modals.service.cost') }}</label>
                                <input type="number" class="form-control" id="service-cost" name="cost" min="0" step="0.01" />
                                <div class="form-text">{{ __('services.modals.service.cost_hint') }}</div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="cost-calculator-toggle">
                                    {{ __('services.modals.service.cost_calculator.open') }}
                                </button>
                            </div>
                            <div class="col-md-4">
                                <label for="service-duration" class="form-label">{{ __('services.modals.service.duration_min') }}</label>
                                <input type="number" class="form-control" id="service-duration" name="duration_min" min="5" step="5" required />
                            </div>
                            <div class="col-12">
                                <div id="cost-calculator-panel" class="border rounded p-3 d-none">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="cost-calculator-materials" class="form-label">{{ __('services.modals.service.cost_calculator.materials') }}</label>
                                            <input type="number" class="form-control" id="cost-calculator-materials" min="0" step="0.01" />
                                        </div>
                                        <div class="col-md-4">
                                            <label for="cost-calculator-staff" class="form-label">{{ __('services.modals.service.cost_calculator.staff') }}</label>
                                            <input type="number" class="form-control" id="cost-calculator-staff" min="0" step="0.01" />
                                        </div>
                                        <div class="col-md-4">
                                            <label for="cost-calculator-other" class="form-label">{{ __('services.modals.service.cost_calculator.other') }}</label>
                                            <input type="number" class="form-control" id="cost-calculator-other" min="0" step="0.01" />
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3 mt-3">
                                        <div>
                                            <div class="text-muted small">{{ __('services.modals.service.cost_calculator.total') }}</div>
                                            <div class="fw-semibold" id="cost-calculator-total">—</div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-light border" id="cost-calculator-reset">{{ __('services.modals.service.cost_calculator.reset') }}</button>
                                            <button type="button" class="btn btn-primary" id="cost-calculator-apply">{{ __('services.modals.service.cost_calculator.apply') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-2 mt-2 p-3 border rounded bg-light" id="service-margin-wrapper">
                                    <div class="text-muted">{{ __('services.modals.service.margin_label') }}</div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-label-success" id="service-margin-indicator">—</span>
                                        <span class="text-muted small" id="service-margin-hint">{{ __('services.modals.service.margin_hint') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="service-form-errors" class="alert alert-danger mt-3 d-none"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('services.actions.cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="service-form-submit">{{ __('services.modals.service.create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">{{ __('services.modals.category.create_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="category-form" onsubmit="return false;">
                    <div class="modal-body">
                        <label for="category-name" class="form-label">{{ __('services.modals.category.name') }}</label>
                        <input type="text" class="form-control" id="category-name" name="name" required />
                        <div id="category-form-errors" class="alert alert-danger mt-3 d-none"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('services.actions.cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="category-form-submit">{{ __('services.modals.category.create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">{{ __('services.modals.confirm.title_service') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="confirmModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('services.actions.cancel') }}</button>
                    <button type="button" class="btn btn-danger" id="confirmModalConfirm">{{ __('services.modals.confirm.confirm') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const locale = document.documentElement.lang || 'ru';

            const translations = {
                actions: {
                    createService: @json(__('services.actions.create_service')),
                    createCategory: @json(__('services.actions.create_category')),
                    cancel: @json(__('services.actions.cancel')),
                },
                alerts: {
                    loadError: @json(__('services.alerts.load_error')),
                    noServices: @json(__('services.alerts.no_services')),
                    validationFailed: @json(__('services.alerts.validation_failed')),
                },
                modals: {
                    service: {
                        createTitle: @json(__('services.modals.service.create_title')),
                        editTitle: @json(__('services.modals.service.edit_title')),
                        name: @json(__('services.modals.service.name')),
                        category: @json(__('services.modals.service.category')),
                        categoryPlaceholder: @json(__('services.modals.service.category_placeholder')),
                        basePrice: @json(__('services.modals.service.base_price')),
                        cost: @json(__('services.modals.service.cost')),
                        costHint: @json(__('services.modals.service.cost_hint')),
                        durationMin: @json(__('services.modals.service.duration_min')),
                        costCalculator: {
                            open: @json(__('services.modals.service.cost_calculator.open')),
                            close: @json(__('services.modals.service.cost_calculator.close')),
                            materials: @json(__('services.modals.service.cost_calculator.materials')),
                            staff: @json(__('services.modals.service.cost_calculator.staff')),
                            other: @json(__('services.modals.service.cost_calculator.other')),
                            total: @json(__('services.modals.service.cost_calculator.total')),
                            apply: @json(__('services.modals.service.cost_calculator.apply')),
                            reset: @json(__('services.modals.service.cost_calculator.reset')),
                        },
                    margin: {
                        label: @json(__('services.modals.service.margin_label')),
                        hint: @json(__('services.modals.service.margin_hint')),
                        positiveHint: @json(__('services.modals.service.margin_positive_hint')),
                        negativeHint: @json(__('services.modals.service.margin_negative_hint')),
                    },
                        saveButton: @json(__('services.modals.service.save')),
                        createButton: @json(__('services.modals.service.create')),
                    },
                    category: {
                        createTitle: @json(__('services.modals.category.create_title')),
                        editTitle: @json(__('services.modals.category.edit_title')),
                        createButton: @json(__('services.modals.category.create')),
                        saveButton: @json(__('services.modals.category.save')),
                    },
                    confirm: {
                        serviceTitle: @json(__('services.modals.confirm.title_service')),
                        serviceBody: @json(__('services.modals.confirm.body_service')),
                        categoryTitle: @json(__('services.modals.confirm.title_category')),
                        categoryBody: @json(__('services.modals.confirm.body_category')),
                        confirm: @json(__('services.modals.confirm.confirm')),
                    },
                },
                messages: {
                    created: @json(__('services.messages.created')),
                    updated: @json(__('services.messages.updated')),
                    deleted: @json(__('services.messages.deleted')),
                    categoryCreated: @json(__('services.messages.category_created')),
                    categoryUpdated: @json(__('services.messages.category_updated')),
                    categoryDeleted: @json(__('services.messages.category_deleted')),
                },
                stats: {
                    filtered: @json(__('services.stats.summary.filtered', ['count' => ':count'])),
                    total: @json(__('services.stats.summary.total', ['count' => ':count'])),
                    avgPrice: @json(__('services.stats.summary.avg_price', ['value' => ':value'])),
                    avgDuration: @json(__('services.stats.summary.avg_duration', ['value' => ':value'])),
                    uncategorized: @json(__('services.stats.summary.uncategorized', ['count' => ':count'])),
                },
                table: {
                    duration: @json(__('services.table.duration')),
                    cost: @json(__('services.table.cost')),
                    margin: @json(__('services.table.margin')),
                    updatedAt: @json(__('services.table.updated_at', ['date' => ':date'])),
                },
                groups: {
                    uncategorized: @json(__('services.groups.uncategorized')),
                },
                filters: {
                    categoryPlaceholder: @json(__('services.filters.category_placeholder')),
                    serviceCategoryPlaceholder: @json(__('services.modals.service.category_placeholder')),
                },
                ui: {
                    loading: @json(app()->getLocale() === 'en' ? 'Loading...' : 'Загрузка...'),
                },
            };

            function getCookie(name) {
                const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
                return match ? decodeURIComponent(match[2]) : null;
            }

            function authHeaders(extra = {}) {
                const token = getCookie('token');
                const headers = Object.assign({ 'Accept': 'application/json', 'Content-Type': 'application/json' }, extra);
                if (token) headers['Authorization'] = 'Bearer ' + token;
                return headers;
            }

            function formatCurrency(value) {
                if (value === null || value === undefined || isNaN(value)) {
                    return '—';
                }
                try {
                    return new Intl.NumberFormat(locale, {
                        style: 'currency',
                        currency: 'RUB',
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2,
                    }).format(value);
                } catch (e) {
                    return value + ' ₽';
                }
            }

            function formatDuration(value) {
                if (value === null || value === undefined || isNaN(value)) {
                    return '—';
                }
                const suffix = locale.startsWith('ru') ? 'мин' : 'min';
                return value + ' ' + suffix;
            }

            function formatDate(value) {
                if (!value) return '';
                try {
                    const date = new Date(value);
                    return new Intl.DateTimeFormat(locale, { dateStyle: 'medium', timeStyle: 'short' }).format(date);
                } catch (e) {
                    return value;
                }
            }

            function escapeHtml(value) {
                if (typeof value !== 'string') return value;
                return value
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function showAlert(type, message) {
                if (!message) return;
                const alert = document.createElement('div');
                alert.className = 'alert alert-' + type + ' alert-dismissible fade show';
                alert.innerHTML = '<div>' + escapeHtml(message) + '</div>' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                alertsContainer.appendChild(alert);
            }

            function clearFormErrors(container) {
                container.classList.add('d-none');
                container.innerHTML = '';
            }

            function displayFormErrors(container, errors) {
                if (!errors) {
                    clearFormErrors(container);
                    return;
                }
                const messages = [];
                Object.keys(errors).forEach(function (key) {
                    const value = errors[key];
                    if (Array.isArray(value)) {
                        value.forEach(function (message) {
                            messages.push(message);
                        });
                    }
                });

                if (!messages.length) {
                    clearFormErrors(container);
                    return;
                }

                container.classList.remove('d-none');
                container.innerHTML = '<ul class="mb-0 ps-3">' + messages.map(function (message) {
                    return '<li>' + escapeHtml(message) + '</li>';
                }).join('') + '</ul>';
            }

            function parseNumber(value) {
                if (value === null || value === undefined || value === '') {
                    return null;
                }
                const number = Number(value);
                return isNaN(number) ? null : number;
            }

            const alertsContainer = document.getElementById('services-alerts');
            const groupsContainer = document.getElementById('services-groups');
            const summaryContainer = document.getElementById('services-summary');

            const filtersForm = document.getElementById('services-filters-form');
            const searchInput = document.getElementById('filter-search');
            const categorySelect = document.getElementById('filter-category');
            const priceMinInput = document.getElementById('filter-price-min');
            const priceMaxInput = document.getElementById('filter-price-max');
            const durationMinInput = document.getElementById('filter-duration-min');
            const durationMaxInput = document.getElementById('filter-duration-max');
            const sortSelect = document.getElementById('filter-sort');
            const directionSelect = document.getElementById('filter-direction');
            const resetButton = document.getElementById('filters-reset');

            const serviceModalEl = document.getElementById('serviceModal');
            const serviceModal = new bootstrap.Modal(serviceModalEl);
            const serviceModalLabel = document.getElementById('serviceModalLabel');
            const serviceForm = document.getElementById('service-form');
            const serviceNameInput = document.getElementById('service-name');
            const serviceCategorySelect = document.getElementById('service-category');
            const servicePriceInput = document.getElementById('service-price');
            const serviceCostInput = document.getElementById('service-cost');
            const serviceDurationInput = document.getElementById('service-duration');
            const costCalculatorToggle = document.getElementById('cost-calculator-toggle');
            const costCalculatorPanel = document.getElementById('cost-calculator-panel');
            const costCalculatorMaterialsInput = document.getElementById('cost-calculator-materials');
            const costCalculatorStaffInput = document.getElementById('cost-calculator-staff');
            const costCalculatorOtherInput = document.getElementById('cost-calculator-other');
            const costCalculatorTotal = document.getElementById('cost-calculator-total');
            const costCalculatorApply = document.getElementById('cost-calculator-apply');
            const costCalculatorReset = document.getElementById('cost-calculator-reset');
            const serviceMarginIndicator = document.getElementById('service-margin-indicator');
            const serviceMarginHint = document.getElementById('service-margin-hint');
            const serviceFormErrors = document.getElementById('service-form-errors');
            const serviceFormSubmit = document.getElementById('service-form-submit');

            const categoryModalEl = document.getElementById('categoryModal');
            const categoryModal = new bootstrap.Modal(categoryModalEl);
            const categoryModalLabel = document.getElementById('categoryModalLabel');
            const categoryForm = document.getElementById('category-form');
            const categoryNameInput = document.getElementById('category-name');
            const categoryFormErrors = document.getElementById('category-form-errors');
            const categoryFormSubmit = document.getElementById('category-form-submit');

            const confirmModalEl = document.getElementById('confirmModal');
            const confirmModal = new bootstrap.Modal(confirmModalEl);
            const confirmModalLabel = document.getElementById('confirmModalLabel');
            const confirmModalBody = document.getElementById('confirmModalBody');
            const confirmModalConfirm = document.getElementById('confirmModalConfirm');

            function calculatorInputs() {
                return [costCalculatorMaterialsInput, costCalculatorStaffInput, costCalculatorOtherInput].filter(Boolean);
            }

            function setCalculatorVisibility(visible) {
                if (!costCalculatorPanel || !costCalculatorToggle) {
                    return;
                }

                costCalculatorPanel.classList.toggle('d-none', !visible);
                costCalculatorToggle.textContent = visible
                    ? translations.modals.service.costCalculator.close
                    : translations.modals.service.costCalculator.open;
                costCalculatorToggle.setAttribute('aria-expanded', visible ? 'true' : 'false');
            }

            function calculateCalculatorTotal() {
                if (!costCalculatorTotal) {
                    return null;
                }

                let total = 0;
                let hasValue = false;

                calculatorInputs().forEach(function (input) {
                    const value = parseNumber(input.value);
                    if (value !== null) {
                        total += value;
                        hasValue = true;
                    }
                });

                if (costCalculatorTotal) {
                    costCalculatorTotal.textContent = hasValue ? formatCurrency(total) : '—';
                }

                if (costCalculatorApply) {
                    costCalculatorApply.disabled = !hasValue;
                }

                return hasValue ? Number(total.toFixed(2)) : null;
            }

            function resetCostCalculator(hidePanel = true) {
                calculatorInputs().forEach(function (input) {
                    input.value = '';
                });

                calculateCalculatorTotal();

                if (costCalculatorApply) {
                    costCalculatorApply.disabled = true;
                }

                if (hidePanel) {
                    setCalculatorVisibility(false);
                }
            }

            function applyCostFromCalculator() {
                const total = calculateCalculatorTotal();
                if (total === null) {
                    return;
                }

                if (serviceCostInput) {
                    const normalized = Number(total.toFixed(2));
                    serviceCostInput.value = Number.isInteger(normalized)
                        ? normalized.toString()
                        : normalized.toFixed(2);
                    serviceCostInput.dispatchEvent(new Event('input'));
                }

                setCalculatorVisibility(false);
            }

            function updateMarginIndicator() {
                if (!serviceMarginIndicator || !serviceMarginHint) {
                    return;
                }

                const price = parseNumber(servicePriceInput?.value);
                const cost = parseNumber(serviceCostInput?.value);

                if (price === null || cost === null) {
                    serviceMarginIndicator.textContent = '—';
                    serviceMarginIndicator.classList.remove('bg-label-danger');
                    serviceMarginIndicator.classList.add('bg-label-success');
                    serviceMarginHint.textContent = translations.modals.service.margin.hint;
                    return;
                }

                const margin = Number((price - cost).toFixed(2));
                serviceMarginIndicator.textContent = formatCurrency(margin);

                if (margin < 0) {
                    serviceMarginIndicator.classList.add('bg-label-danger');
                    serviceMarginIndicator.classList.remove('bg-label-success');
                    serviceMarginHint.textContent = translations.modals.service.margin.negativeHint;
                } else {
                    serviceMarginIndicator.classList.remove('bg-label-danger');
                    serviceMarginIndicator.classList.add('bg-label-success');
                    serviceMarginHint.textContent = translations.modals.service.margin.positiveHint;
                }
            }

            const state = {
                filters: {
                    search: '',
                    category_id: '',
                    price_min: '',
                    price_max: '',
                    duration_min: '',
                    duration_max: '',
                    sort: 'name',
                    direction: 'asc',
                },
                groups: [],
                categoryOptions: [],
                stats: {
                    total_filtered: 0,
                    total_all: 0,
                    avg_price: 0,
                    avg_duration: 0,
                },
                uncategorized: {
                    total_services: 0,
                    filtered_services: 0,
                },
                loading: false,
                editingServiceId: null,
                editingCategoryId: null,
                confirmAction: null,
            };

            if (costCalculatorToggle) {
                costCalculatorToggle.addEventListener('click', function () {
                    const isVisible = costCalculatorPanel && !costCalculatorPanel.classList.contains('d-none');
                    setCalculatorVisibility(!isVisible);
                    calculateCalculatorTotal();
                });
            }

            calculatorInputs().forEach(function (input) {
                input.addEventListener('input', function () {
                    calculateCalculatorTotal();
                });
            });

            if (costCalculatorApply) {
                costCalculatorApply.addEventListener('click', function () {
                    applyCostFromCalculator();
                    updateMarginIndicator();
                });
            }

            if (costCalculatorReset) {
                costCalculatorReset.addEventListener('click', function () {
                    resetCostCalculator(false);
                    calculateCalculatorTotal();
                    updateMarginIndicator();
                });
            }

            if (servicePriceInput) {
                servicePriceInput.addEventListener('input', updateMarginIndicator);
            }

            if (serviceCostInput) {
                serviceCostInput.addEventListener('input', updateMarginIndicator);
            }

            resetCostCalculator();
            updateMarginIndicator();

            function setFiltersFromState() {
                searchInput.value = state.filters.search;
                categorySelect.value = state.filters.category_id || '';
                priceMinInput.value = state.filters.price_min;
                priceMaxInput.value = state.filters.price_max;
                durationMinInput.value = state.filters.duration_min;
                durationMaxInput.value = state.filters.duration_max;
                sortSelect.value = state.filters.sort;
                directionSelect.value = state.filters.direction;
            }

            function setLoading(loading) {
                state.loading = loading;
                if (loading) {
                    groupsContainer.innerHTML = '<div class="services-group-card p-4 text-center text-muted">' +
                        '<div class="spinner-border text-primary mb-3" role="status"></div>' +
                        '<div>' + escapeHtml(translations.ui.loading) + '</div>' +
                        '</div>';
                }
            }

            function updateCategorySelects() {
                const filterOptions = ['<option value="">' + escapeHtml(translations.filters.categoryPlaceholder) + '</option>'];
                state.categoryOptions.forEach(function (category) {
                    filterOptions.push('<option value="' + category.id + '">' + escapeHtml(category.name) + '</option>');
                });
                categorySelect.innerHTML = filterOptions.join('');
                categorySelect.value = state.filters.category_id || '';

                const serviceOptions = ['<option value="">' + escapeHtml(translations.filters.serviceCategoryPlaceholder) + '</option>'];
                state.categoryOptions.forEach(function (category) {
                    serviceOptions.push('<option value="' + category.id + '">' + escapeHtml(category.name) + '</option>');
                });
                serviceCategorySelect.innerHTML = serviceOptions.join('');
            }

            function renderSummary() {
                const fragments = [];
                fragments.push(escapeHtml(translations.stats.filtered.replace(':count', state.stats.total_filtered)));
                fragments.push(escapeHtml(translations.stats.total.replace(':count', state.stats.total_all)));

                if (state.stats.avg_price) {
                    fragments.push(escapeHtml(translations.stats.avgPrice.replace(':value', formatCurrency(state.stats.avg_price))));
                }

                if (state.stats.avg_duration) {
                    fragments.push(escapeHtml(translations.stats.avgDuration.replace(':value', state.stats.avg_duration)));
                }

                if (state.uncategorized.total_services) {
                    fragments.push(escapeHtml(translations.stats.uncategorized.replace(':count', state.uncategorized.total_services)));
                }

                summaryContainer.innerHTML = fragments.map(function (item) {
                    return '<span class="services-summary-pill">' + item + '</span>';
                }).join('');
            }

            function totalForCategory(groupId) {
                if (groupId === null) {
                    return state.uncategorized.total_services;
                }
                const match = state.categoryOptions.find(function (category) {
                    return Number(category.id) === Number(groupId);
                });
                return match ? match.total_services : 0;
            }

            function renderGroups() {
                if (state.loading) {
                    return;
                }

                if (!state.groups.length) {
                    groupsContainer.innerHTML = '<div class="services-group-card p-4"><div class="alert alert-info mb-0">' + escapeHtml(translations.alerts.noServices) + '</div></div>';
                    return;
                }

                const html = state.groups.map(function (group) {
                    const totalInCategory = totalForCategory(group.id);
                    const servicesHtml = (group.services || []).map(function (service) {
                        const secondaryParts = [];
                        if (service.cost !== null && service.cost !== undefined) {
                            secondaryParts.push(escapeHtml(translations.table.cost) + ': ' + escapeHtml(formatCurrency(service.cost)));
                        }
                        if (service.margin !== null && service.margin !== undefined) {
                            secondaryParts.push(escapeHtml(translations.table.margin) + ': ' + escapeHtml(formatCurrency(service.margin)));
                        }

                        const secondaryHtml = secondaryParts.length
                            ? '<div class="service-secondary">' + secondaryParts.join(' · ') + '</div>'
                            : '';

                        return (
                            '<div class="service-row d-flex flex-column flex-md-row align-items-md-start justify-content-between">' +
                                '<div class="flex-grow-1 pe-md-3">' +
                                    '<div class="d-flex flex-wrap align-items-center gap-2">' +
                                        '<h6 class="mb-0">' + escapeHtml(service.name) + '</h6>' +
                                        '<span class="service-price">' + escapeHtml(formatCurrency(service.base_price)) + '</span>' +
                                    '</div>' +
                                    '<div class="service-meta">' +
                                        '<span><i class="ri ri-time-line"></i>' + escapeHtml(formatDuration(service.duration_min)) + '</span>' +
                                    '</div>' +
                                    secondaryHtml +
                                '</div>' +
                                '<div class="service-actions mt-3 mt-md-0">' +
                                    '<div class="btn-group btn-group-sm">' +
                                        '<button type="button" class="btn btn-outline-primary" data-action="edit-service" data-service-id="' + service.id + '"><i class="ri ri-edit-line"></i></button>' +
                                        '<button type="button" class="btn btn-outline-danger" data-action="delete-service" data-service-id="' + service.id + '"><i class="ri ri-delete-bin-line"></i></button>' +
                                    '</div>' +
                                '</div>' +
                            '</div>'
                        );
                    }).join('');

                    const servicesContent = servicesHtml
                        ? servicesHtml
                        : '<div class="text-muted">' + escapeHtml(translations.alerts.noServices) + '</div>';

                    const categoryActions = group.id !== null
                        ? '<div class="group-actions btn-group btn-group-sm">' +
                            '<button type="button" class="btn btn-outline-primary" data-action="edit-category" data-category-id="' + group.id + '"><i class="ri ri-edit-line"></i></button>' +
                            '<button type="button" class="btn btn-outline-danger" data-action="delete-category" data-category-id="' + group.id + '"><i class="ri ri-delete-bin-line"></i></button>' +
                          '</div>'
                        : '';

                    const subtitle = group.id === null && !totalInCategory
                        ? ''
                        : '<small class="text-muted">' + group.services_count + ' / ' + (totalInCategory || group.services_count) + '</small>';

                    return (
                        '<section class="services-group-card p-4">' +
                            '<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3 mb-3">' +
                                '<div>' +
                                    '<h5 class="mb-1">' + escapeHtml(group.name) + '</h5>' +
                                    (subtitle ? subtitle : '') +
                                '</div>' +
                                categoryActions +
                            '</div>' +
                            '<div>' + servicesContent + '</div>' +
                        '</section>'
                    );
                }).join('');

                groupsContainer.innerHTML = html;
            }

            async function loadServices() {
                setLoading(true);
                alertsContainer.innerHTML = '';

                const params = new URLSearchParams();
                Object.keys(state.filters).forEach(function (key) {
                    const value = state.filters[key];
                    if (value !== null && value !== undefined && value !== '') {
                        params.append(key, value);
                    }
                });

                try {
                    const response = await fetch('/api/v1/services?' + params.toString(), {
                        headers: authHeaders(),
                    });

                    if (!response.ok) {
                        throw new Error('Failed to load');
                    }

                    const payload = await response.json();
                    const data = payload.data || {};
                    const meta = payload.meta || {};

                    state.groups = Array.isArray(data.groups) ? data.groups : [];
                    state.categoryOptions = Array.isArray(meta.category_options) ? meta.category_options : [];
                    state.stats = meta.stats || state.stats;
                    state.uncategorized = meta.uncategorized || state.uncategorized;

                    updateCategorySelects();
                    renderSummary();
                    setLoading(false);
                    renderGroups();
                } catch (error) {
                    setLoading(false);
                    groupsContainer.innerHTML = '<div class="col-12"><div class="alert alert-danger mb-0">' + escapeHtml(translations.alerts.loadError) + '</div></div>';
                }
            }

            filtersForm.addEventListener('submit', function (event) {
                event.preventDefault();
                state.filters.search = searchInput.value.trim();
                state.filters.category_id = categorySelect.value || '';
                state.filters.price_min = priceMinInput.value;
                state.filters.price_max = priceMaxInput.value;
                state.filters.duration_min = durationMinInput.value;
                state.filters.duration_max = durationMaxInput.value;
                state.filters.sort = sortSelect.value;
                state.filters.direction = directionSelect.value;
                loadServices();
            });

            resetButton.addEventListener('click', function () {
                state.filters = {
                    search: '',
                    category_id: '',
                    price_min: '',
                    price_max: '',
                    duration_min: '',
                    duration_max: '',
                    sort: 'name',
                    direction: 'asc',
                };
                setFiltersFromState();
                loadServices();
            });

            document.getElementById('new-service-btn').addEventListener('click', function () {
                state.editingServiceId = null;
                serviceForm.reset();
                clearFormErrors(serviceFormErrors);
                serviceCategorySelect.value = '';
                resetCostCalculator();
                updateMarginIndicator();
                serviceModalLabel.textContent = translations.modals.service.createTitle;
                serviceFormSubmit.textContent = translations.modals.service.createButton;
                serviceModal.show();
            });

            document.getElementById('new-category-btn').addEventListener('click', function () {
                state.editingCategoryId = null;
                categoryForm.reset();
                clearFormErrors(categoryFormErrors);
                categoryModalLabel.textContent = translations.modals.category.createTitle;
                categoryFormSubmit.textContent = translations.modals.category.createButton;
                categoryModal.show();
            });

            groupsContainer.addEventListener('click', function (event) {
                const editServiceBtn = event.target.closest('[data-action="edit-service"]');
                if (editServiceBtn) {
                    const serviceId = editServiceBtn.getAttribute('data-service-id');
                    openServiceModal(serviceId);
                    return;
                }

                const deleteServiceBtn = event.target.closest('[data-action="delete-service"]');
                if (deleteServiceBtn) {
                    const serviceId = deleteServiceBtn.getAttribute('data-service-id');
                    const service = findServiceById(serviceId);
                    openConfirmModal('service', serviceId, service ? service.name : '');
                    return;
                }

                const editCategoryBtn = event.target.closest('[data-action="edit-category"]');
                if (editCategoryBtn) {
                    const categoryId = editCategoryBtn.getAttribute('data-category-id');
                    openCategoryModal(categoryId);
                    return;
                }

                const deleteCategoryBtn = event.target.closest('[data-action="delete-category"]');
                if (deleteCategoryBtn) {
                    const categoryId = deleteCategoryBtn.getAttribute('data-category-id');
                    const category = findCategoryById(categoryId);
                    openConfirmModal('category', categoryId, category ? category.name : '');
                }
            });

            function findServiceById(id) {
                id = Number(id);
                for (const group of state.groups) {
                    const found = (group.services || []).find(function (service) {
                        return Number(service.id) === id;
                    });
                    if (found) {
                        return found;
                    }
                }
                return null;
            }

            function findCategoryById(id) {
                id = Number(id);
                return state.categoryOptions.find(function (category) {
                    return Number(category.id) === id;
                }) || null;
            }

            function openServiceModal(serviceId) {
                const service = findServiceById(serviceId);
                if (!service) {
                    return;
                }

                state.editingServiceId = service.id;
                serviceModalLabel.textContent = translations.modals.service.editTitle;
                serviceFormSubmit.textContent = translations.modals.service.saveButton;

                serviceNameInput.value = service.name || '';
                serviceCategorySelect.value = service.category_id || '';
                servicePriceInput.value = service.base_price !== null && service.base_price !== undefined ? service.base_price : '';
                serviceCostInput.value = service.cost !== null && service.cost !== undefined ? service.cost : '';
                serviceDurationInput.value = service.duration_min !== null && service.duration_min !== undefined ? service.duration_min : '';
                resetCostCalculator();
                updateMarginIndicator();
                clearFormErrors(serviceFormErrors);
                serviceModal.show();
            }

            function openCategoryModal(categoryId) {
                const category = findCategoryById(categoryId);
                if (!category) {
                    return;
                }
                state.editingCategoryId = category.id;
                categoryNameInput.value = category.name || '';
                categoryModalLabel.textContent = translations.modals.category.editTitle;
                categoryFormSubmit.textContent = translations.modals.category.saveButton;
                clearFormErrors(categoryFormErrors);
                categoryModal.show();
            }

            function openConfirmModal(type, id, name) {
                state.confirmAction = { type: type, id: id };
                if (type === 'service') {
                    confirmModalLabel.textContent = translations.modals.confirm.serviceTitle;
                    confirmModalBody.textContent = translations.modals.confirm.serviceBody.replace(':name', name || '');
                } else {
                    confirmModalLabel.textContent = translations.modals.confirm.categoryTitle;
                    confirmModalBody.textContent = translations.modals.confirm.categoryBody.replace(':name', name || '');
                }
                confirmModalConfirm.textContent = translations.modals.confirm.confirm;
                confirmModal.show();
            }

            confirmModalConfirm.addEventListener('click', async function () {
                if (!state.confirmAction) {
                    return;
                }

                confirmModalConfirm.disabled = true;
                try {
                    if (state.confirmAction.type === 'service') {
                        await deleteService(state.confirmAction.id);
                    } else {
                        await deleteCategory(state.confirmAction.id);
                    }
                    confirmModal.hide();
                } catch (error) {
                    showAlert('danger', translations.alerts.loadError);
                } finally {
                    confirmModalConfirm.disabled = false;
                    state.confirmAction = null;
                }
            });

            serviceForm.addEventListener('submit', async function (event) {
                event.preventDefault();

                const payload = {
                    name: serviceNameInput.value.trim(),
                    category_id: serviceCategorySelect.value ? Number(serviceCategorySelect.value) : null,
                    base_price: parseNumber(servicePriceInput.value),
                    cost: parseNumber(serviceCostInput.value),
                    duration_min: parseNumber(serviceDurationInput.value),
                };

                const method = state.editingServiceId ? 'PATCH' : 'POST';
                const url = state.editingServiceId ? '/api/v1/services/' + state.editingServiceId : '/api/v1/services';

                serviceFormSubmit.disabled = true;
                clearFormErrors(serviceFormErrors);

                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: authHeaders(),
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        if (response.status === 422 && data.error && data.error.fields) {
                            displayFormErrors(serviceFormErrors, data.error.fields);
                        } else {
                            showAlert('danger', data.error?.message || translations.alerts.validationFailed);
                        }
                        return;
                    }

                    serviceModal.hide();
                    showAlert('success', data.message || (state.editingServiceId ? translations.messages.updated : translations.messages.created));
                    await loadServices();
                } catch (error) {
                    showAlert('danger', translations.alerts.loadError);
                } finally {
                    serviceFormSubmit.disabled = false;
                }
            });

            categoryForm.addEventListener('submit', async function (event) {
                event.preventDefault();

                const payload = {
                    name: categoryNameInput.value.trim(),
                };

                const method = state.editingCategoryId ? 'PATCH' : 'POST';
                const url = state.editingCategoryId ? '/api/v1/service-categories/' + state.editingCategoryId : '/api/v1/service-categories';

                categoryFormSubmit.disabled = true;
                clearFormErrors(categoryFormErrors);

                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: authHeaders(),
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        if (response.status === 422 && data.error && data.error.fields) {
                            displayFormErrors(categoryFormErrors, data.error.fields);
                        } else {
                            showAlert('danger', data.error?.message || translations.alerts.validationFailed);
                        }
                        return;
                    }

                    categoryModal.hide();
                    showAlert('success', data.message || (state.editingCategoryId ? translations.messages.categoryUpdated : translations.messages.categoryCreated));
                    await loadServices();
                } catch (error) {
                    showAlert('danger', translations.alerts.loadError);
                } finally {
                    categoryFormSubmit.disabled = false;
                }
            });

            async function deleteService(id) {
                const response = await fetch('/api/v1/services/' + id, {
                    method: 'DELETE',
                    headers: authHeaders(),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    showAlert('danger', data.error?.message || translations.alerts.loadError);
                    return;
                }
                showAlert('success', data.message || translations.messages.deleted);
                await loadServices();
            }

            async function deleteCategory(id) {
                const response = await fetch('/api/v1/service-categories/' + id, {
                    method: 'DELETE',
                    headers: authHeaders(),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    showAlert('danger', data.error?.message || translations.alerts.loadError);
                    return;
                }
                showAlert('success', data.message || translations.messages.categoryDeleted);
                await loadServices();
            }

            setFiltersFromState();
            loadServices();
        });
    </script>
@endsection
