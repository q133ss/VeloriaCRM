@extends('layouts.app')

@section('title', __('services.title'))

@section('content')
    <style>
        .services-page {
            --services-card-shadow: 0 20px 48px -34px rgba(37, 26, 84, 0.45);
            --services-line: color-mix(in srgb, var(--bs-border-color) 70%, transparent);
        }

        .services-hero {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            border: 1px solid rgba(var(--bs-primary-rgb, 255, 0, 252), 0.14);
            border-radius: 1.5rem;
            padding: 1.35rem 1.5rem;
            background: linear-gradient(135deg, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.07), rgba(var(--bs-primary-rgb, 255, 0, 252), 0.015) 60%, rgba(var(--bs-info-rgb, 0, 207, 232), 0.04));
            box-shadow: var(--services-card-shadow);
        }

        .services-hero__title {
            margin: 0 0 0.2rem;
            font-size: clamp(1.35rem, 2vw, 1.75rem);
            letter-spacing: -0.02em;
        }

        .services-hero__lead {
            color: var(--bs-secondary-color);
        }

        .services-hero__cta {
            white-space: nowrap;
        }

        .services-surface {
            border: none;
            border-radius: 1.35rem;
            box-shadow: var(--services-card-shadow);
            background: color-mix(in srgb, var(--bs-card-bg) 94%, transparent);
        }

        .services-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
        }

        .services-chips {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.3rem;
        }

        .services-chip {
            border: 0;
            border-radius: 999px;
            padding: 0.45rem 0.85rem;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.07);
            color: var(--bs-secondary-color);
            font-weight: 600;
            font-size: 0.88rem;
            white-space: nowrap;
        }

        .services-chip.is-active {
            background: var(--bs-card-bg, #fff);
            color: var(--bs-primary);
            box-shadow: 0 8px 20px -14px rgba(0, 0, 0, 0.65);
        }

        .services-chip__count {
            margin-left: 0.3rem;
            opacity: 0.65;
        }

        .services-chip--add {
            background: transparent;
            border: 1px dashed var(--bs-border-color);
        }

        .services-toolbar__right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1 1 240px;
            justify-content: flex-end;
        }

        /* Field and buttons to one height: the theme gives them three. */
        .services-toolbar__right .services-search {
            max-width: 280px;
            height: 2.625rem;
            padding-top: 0;
            padding-bottom: 0;
        }

        .services-category-bar {
            display: none;
            align-items: center;
            gap: 0.6rem;
            padding: 0 1.25rem 0.9rem;
            color: var(--bs-secondary-color);
            font-size: 0.9rem;
        }

        .services-category-bar.is-visible {
            display: flex;
            flex-wrap: wrap;
        }

        /* Rows, not a table: on a phone the same data folds into a card
           instead of running off the right edge. */
        .services-row,
        .services-list-head {
            display: grid;
            grid-template-columns: minmax(170px, 1.5fr) 116px minmax(150px, 1fr) minmax(140px, 1fr) 150px;
            align-items: center;
            gap: 0.9rem;
            padding: 0.85rem 1.25rem;
        }

        .services-list-head {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            color: var(--bs-secondary-color);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--services-line);
        }

        .services-row {
            border-bottom: 1px solid var(--services-line);
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .services-row:hover {
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.03);
        }

        .services-row__who strong {
            display: block;
            font-size: 0.98rem;
        }

        .services-row small,
        .services-row__muted {
            display: block;
            margin-top: 0.15rem;
            color: var(--bs-secondary-color);
        }

        .services-row__price {
            font-weight: 600;
            font-size: 1rem;
        }

        .services-row__warn {
            color: var(--bs-danger);
            font-weight: 600;
        }

        .services-row__plan {
            font-weight: 600;
        }

        .services-row__fix {
            margin-top: 0.3rem;
            padding: 0.15rem 0.55rem;
            font-size: 0.78rem;
            line-height: 1.4;
            white-space: nowrap;
        }

        .services-row__measured {
            color: var(--bs-warning-text-emphasis, var(--bs-warning));
            font-weight: 600;
        }

        .services-row__idle {
            color: var(--bs-warning-text-emphasis, var(--bs-warning));
        }

        .services-row__act {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.35rem;
        }

        .services-row__act .btn {
            white-space: nowrap;
        }

        .services-empty {
            padding: 3rem 1.25rem;
            text-align: center;
            color: var(--bs-secondary-color);
        }

        .services-companions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            margin-top: 0.4rem;
        }

        .services-companion {
            border: 1px solid var(--bs-border-color);
            border-radius: 999px;
            padding: 0.2rem 0.6rem;
            background: transparent;
            font-size: 0.78rem;
            color: var(--bs-secondary-color);
        }

        .services-companion.is-suggested {
            border-style: dashed;
        }

        @media (max-width: 767.98px) {
            .services-list-head {
                display: none;
            }

            .services-row {
                grid-template-columns: 1fr auto;
                grid-template-areas:
                    'who price'
                    'duration duration'
                    'demand demand'
                    'act act';
                row-gap: 0.45rem;
                padding: 1rem 1.1rem;
            }

            .services-row__who { grid-area: who; }
            .services-row__price { grid-area: price; justify-self: end; }
            .services-row__duration { grid-area: duration; }
            .services-row__demand { grid-area: demand; }

            /* The delete button used to be full width and sat closer to the
               next service's name than to its own. It lives in the menu now. */
            .services-row__act {
                grid-area: act;
                justify-content: stretch;
                margin-top: 0.35rem;
            }

            .services-row__act .services-row__primary {
                flex: 1;
            }

            .services-toolbar,
            .services-toolbar__right {
                justify-content: flex-start;
            }

            .services-toolbar__right .services-search {
                max-width: none;
            }

            .services-chips {
                width: 100%;
            }

            .services-hero {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>

    <div class="services-page d-flex flex-column gap-4">
        <section class="services-hero">
            <div>
                <h1 class="services-hero__title">{{ __('services.title') }}</h1>
                <p class="services-hero__lead mb-0" id="services-lead">{{ __('services.subtitle') }}</p>
            </div>
            <button type="button" class="btn btn-primary services-hero__cta" id="new-service-btn">
                <i class="ri ri-scissors-2-line me-1"></i>
                {{ __('services.actions.create_service') }}
            </button>
        </section>

        <div id="services-alerts"></div>

        <div class="card services-surface">
            <div class="services-toolbar">
                <div class="services-chips" id="services-chips"></div>
                <div class="services-toolbar__right">
                    <input
                        type="search"
                        class="form-control services-search"
                        id="filter-search"
                        placeholder="{{ __('services.filters.search_placeholder') }}"
                        aria-label="{{ __('services.filters.search_label') }}"
                        autocomplete="off"
                    />
                </div>
            </div>

            {{-- Renaming or removing a category is offered where it makes sense:
                 once the master has picked that category out of the list. --}}
            <div class="services-category-bar" id="services-category-bar">
                <span id="services-category-name"></span>
                <button type="button" class="btn btn-sm btn-text-secondary" id="edit-category-btn">{{ __('services.actions.rename') }}</button>
                <button type="button" class="btn btn-sm btn-text-secondary text-danger" id="delete-category-btn">{{ __('services.actions.delete_category') }}</button>
            </div>

            <div class="services-list-head">
                <span>{{ __('services.table.service') }}</span>
                <span>{{ __('services.table.price') }}</span>
                <span>{{ __('services.table.duration') }}</span>
                <span>{{ __('services.table.demand') }}</span>
                <span></span>
            </div>

            <div class="services-list" id="services-body">
                <div class="services-empty">{{ app()->getLocale() === 'en' ? 'Loading...' : 'Загрузка...' }}</div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="serviceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="serviceModalLabel">{{ __('services.modals.service.create_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('services.actions.close') }}"></button>
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
                                <button type="button" class="btn btn-sm btn-text-primary px-0" id="cost-calculator-toggle">
                                    {{ __('services.modals.service.cost_calculator.open') }}
                                </button>
                            </div>
                            <div class="col-md-4">
                                <label for="service-duration" class="form-label">{{ __('services.modals.service.duration_min') }}</label>
                                <input type="number" class="form-control" id="service-duration" name="duration_min" min="5" step="5" required />
                                <div class="form-text" id="service-duration-hint" hidden></div>
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
                                <label class="form-label mb-1">{{ __('services.modals.service.upsell') }}</label>
                                <div class="form-text mt-0 mb-2">{{ __('services.modals.service.upsell_hint') }}</div>
                                <div id="service-upsell" class="d-flex flex-wrap gap-2"></div>
                                <div id="service-upsell-suggested" class="small text-muted mt-2" hidden></div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between gap-2 p-3 border rounded" id="service-margin-wrapper">
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('services.actions.close') }}"></button>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('services.actions.close') }}"></button>
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

            const t = {
                actions: {
                    createService: @json(__('services.actions.create_service')),
                    createCategory: @json(__('services.actions.create_category')),
                    edit: @json(__('services.actions.edit')),
                    book: @json(__('services.actions.book')),
                    more: @json(__('services.actions.more')),
                    delete: @json(__('services.actions.delete')),
                },
                alerts: {
                    loadError: @json(__('services.alerts.load_error')),
                    noServices: @json(__('services.alerts.no_services')),
                },
                modals: {
                    service: {
                        createTitle: @json(__('services.modals.service.create_title')),
                        editTitle: @json(__('services.modals.service.edit_title')),
                        createButton: @json(__('services.modals.service.create')),
                        saveButton: @json(__('services.modals.service.save')),
                        upsellSuggested: @json(__('services.modals.service.upsell_suggested')),
                        costCalculator: {
                            open: @json(__('services.modals.service.cost_calculator.open')),
                            close: @json(__('services.modals.service.cost_calculator.close')),
                        },
                        margin: {
                            hint: @json(__('services.modals.service.margin_hint')),
                            positiveHint: @json(__('services.modals.service.margin_positive_hint')),
                            negativeHint: @json(__('services.modals.service.margin_negative_hint')),
                        },
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
                table: {
                    upsell: @json(__('services.table.upsell')),
                },
                groups: { uncategorized: @json(__('services.groups.uncategorized')) },
                stats: { allCategories: @json(__('services.stats.summary.all_categories')) },
                duration: {
                    planned: @json(__('services.duration.planned', ['minutes' => ':minutes'])),
                    apply: @json(__('services.duration.apply', ['minutes' => ':minutes'])),
                    applied: @json(__('services.duration.applied', ['minutes' => ':minutes'])),
                },
                demand: { belowCost: @json(__('services.demand.below_cost')) },
                lead: {
                    catalog: @json(__('services.lead.catalog', ['count' => ':count', 'unit' => ':unit'])),
                    empty: @json(__('services.lead.empty')),
                    review: @json(__('services.duration.review_lead')),
                },
                units: @json(__('services.units')),
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

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, function (character) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
                });
            }

            // Russian needs three forms, English two; the forms come from the
            // language file so this stays a rule about numbers.
            function unit(count, key) {
                const forms = t.units[key] || {};
                if (!locale.startsWith('ru')) {
                    return count === 1 ? forms.one : forms.many;
                }
                const mod100 = count % 100;
                if (mod100 >= 11 && mod100 <= 14) return forms.many;
                const mod10 = count % 10;
                if (mod10 === 1) return forms.one;
                if (mod10 >= 2 && mod10 <= 4) return forms.few;
                return forms.many;
            }

            function formatCurrency(value) {
                if (value === null || value === undefined || isNaN(value)) return '—';
                try {
                    return new Intl.NumberFormat(locale, {
                        style: 'currency', currency: 'RUB',
                        minimumFractionDigits: 0, maximumFractionDigits: 2,
                    }).format(value);
                } catch (e) {
                    return value + ' ₽';
                }
            }

            function parseNumber(value) {
                if (value === null || value === undefined || value === '') return null;
                const number = Number(value);
                return isNaN(number) ? null : number;
            }

            const state = {
                search: '',
                categoryId: '',
                services: [],
                categoryOptions: [],
                uncategorized: { total_services: 0 },
                stats: { total_all: 0, needs_review: 0 },
                editingServiceId: null,
                editingCategoryId: null,
                confirmAction: null,
            };

            const alertsContainer = document.getElementById('services-alerts');
            const listContainer = document.getElementById('services-body');
            const leadEl = document.getElementById('services-lead');
            const chipsContainer = document.getElementById('services-chips');
            const searchInput = document.getElementById('filter-search');
            const categoryBar = document.getElementById('services-category-bar');
            const categoryBarName = document.getElementById('services-category-name');

            const serviceModalEl = document.getElementById('serviceModal');
            const serviceModal = new bootstrap.Modal(serviceModalEl);
            const serviceModalLabel = document.getElementById('serviceModalLabel');
            const serviceForm = document.getElementById('service-form');
            const serviceNameInput = document.getElementById('service-name');
            const serviceCategorySelect = document.getElementById('service-category');
            const servicePriceInput = document.getElementById('service-price');
            const serviceCostInput = document.getElementById('service-cost');
            const serviceDurationInput = document.getElementById('service-duration');
            const serviceDurationHint = document.getElementById('service-duration-hint');
            const serviceUpsell = document.getElementById('service-upsell');
            const serviceUpsellSuggested = document.getElementById('service-upsell-suggested');
            const costCalculatorToggle = document.getElementById('cost-calculator-toggle');
            const costCalculatorPanel = document.getElementById('cost-calculator-panel');
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

            function showAlert(type, message) {
                if (!message) return;
                const alert = document.createElement('div');
                alert.className = 'alert alert-' + type + ' alert-dismissible fade show';
                alert.innerHTML = '<div>' + escapeHtml(message) + '</div>' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                alertsContainer.appendChild(alert);
                setTimeout(function () {
                    alert.classList.remove('show');
                    alert.addEventListener('transitionend', () => alert.remove());
                }, 5000);
            }

            function clearFormErrors(container) {
                container.classList.add('d-none');
                container.innerHTML = '';
            }

            function displayFormErrors(container, errors) {
                const messages = [];
                Object.keys(errors || {}).forEach(function (key) {
                    const value = errors[key];
                    if (Array.isArray(value)) value.forEach(message => messages.push(message));
                });

                if (!messages.length) {
                    clearFormErrors(container);
                    return;
                }

                container.classList.remove('d-none');
                container.innerHTML = '<ul class="mb-0 ps-3">' +
                    messages.map(message => '<li>' + escapeHtml(message) + '</li>').join('') + '</ul>';
            }

            function findService(id) {
                return state.services.find(service => Number(service.id) === Number(id));
            }

            /* ---------- rendering ---------- */

            function renderLead() {
                const total = state.stats.total_all || 0;

                if (!total) {
                    leadEl.textContent = t.lead.empty;
                    return;
                }

                const parts = [t.lead.catalog.replace(':count', total).replace(':unit', unit(total, 'services'))];

                if (state.stats.needs_review) {
                    const count = state.stats.needs_review;
                    const choice = t.lead.review.split('|');
                    const line = (count === 1 ? choice[0] : choice[choice.length - 1]) || '';
                    parts.push(line.replace(/^[{\[][^\]}]*[}\]]\s*/, '').replace(':count', count));
                }

                leadEl.textContent = parts.join(' · ');
            }

            function renderChips() {
                const chips = [
                    { id: '', name: t.stats.allCategories, count: state.stats.total_all },
                ];

                state.categoryOptions.forEach(function (category) {
                    chips.push({ id: String(category.id), name: category.name, count: category.total_services });
                });

                if (state.uncategorized.total_services) {
                    chips.push({ id: 'none', name: t.groups.uncategorized, count: state.uncategorized.total_services });
                }

                chipsContainer.innerHTML = chips.map(function (chip) {
                    const active = String(state.categoryId) === chip.id ? ' is-active' : '';
                    return '<button type="button" class="services-chip' + active + '" data-category="' + escapeHtml(chip.id) + '">' +
                        escapeHtml(chip.name) +
                        '<span class="services-chip__count">' + chip.count + '</span>' +
                        '</button>';
                }).join('') +
                    '<button type="button" class="services-chip services-chip--add" id="new-category-btn">+ ' +
                    escapeHtml(t.actions.createCategory) + '</button>';

                const selected = state.categoryOptions.find(c => String(c.id) === String(state.categoryId));
                categoryBar.classList.toggle('is-visible', Boolean(selected));
                if (selected) {
                    categoryBarName.textContent = selected.name;
                }
            }

            function durationCell(service) {
                const duration = service.duration || {};
                const planned = t.duration.planned.replace(':minutes', duration.planned ?? service.duration_min ?? '—');

                if (!duration.needs_review) {
                    return '<span class="services-row__plan">' + escapeHtml(planned) + '</span>';
                }

                // The clock disagrees with the price list. Saying so is half the
                // job; the other half is making it one press to agree with it.
                return '<span class="services-row__plan">' + escapeHtml(planned) + '</span>' +
                    '<small class="services-row__measured">' + escapeHtml(duration.hint) + '</small>' +
                    '<small>' + escapeHtml(duration.samples_text) + '</small>' +
                    '<button type="button" class="btn btn-sm btn-outline-primary services-row__fix js-service-action" data-action="fix-duration">' +
                    escapeHtml(t.duration.apply.replace(':minutes', duration.measured)) + '</button>';
            }

            function demandCell(service) {
                const demand = service.demand || {};
                const idle = !demand.bookings || demand.note ? ' services-row__idle' : '';
                const money = demand.revenue ? formatCurrency(demand.revenue) : null;
                const note = demand.note || money;

                return '<span class="' + idle.trim() + '">' + escapeHtml(demand.text || '—') + '</span>' +
                    (note ? '<small>' + escapeHtml(note) + '</small>' : '');
            }

            function companionsMarkup(service) {
                const chosen = (service.companions && service.companions.chosen) || [];
                const observed = (service.companions && service.companions.observed) || [];

                if (!chosen.length && !observed.length) {
                    return '';
                }

                const items = chosen.map(item => '<span class="services-companion">' + escapeHtml(item.name) + '</span>')
                    .concat(observed.map(item => '<span class="services-companion is-suggested">' + escapeHtml(item.name) + '</span>'));

                return '<div class="services-companions">' + items.join('') + '</div>';
            }

            function renderServices() {
                listContainer.innerHTML = '';

                if (!state.services.length) {
                    const empty = document.createElement('div');
                    empty.className = 'services-empty';
                    empty.textContent = t.alerts.noServices;
                    listContainer.appendChild(empty);
                    return;
                }

                state.services.forEach(function (service) {
                    const row = document.createElement('article');
                    row.className = 'services-row';
                    row.dataset.id = service.id;

                    // Repeating the only category under every service says
                    // nothing; it earns its line once there is a choice.
                    const category = !state.categoryId && service.category_name && state.categoryOptions.length > 1
                        ? '<small>' + escapeHtml(service.category_name) + '</small>'
                        : '';

                    row.innerHTML =
                        '<div class="services-row__who">' +
                            '<strong>' + escapeHtml(service.name) + '</strong>' +
                            category +
                            companionsMarkup(service) +
                        '</div>' +
                        '<div class="services-row__price">' +
                            escapeHtml(formatCurrency(service.base_price)) +
                            (service.below_cost ? '<small class="services-row__warn">' + escapeHtml(t.demand.belowCost) + '</small>' : '') +
                        '</div>' +
                        '<div class="services-row__duration">' + durationCell(service) + '</div>' +
                        '<div class="services-row__demand">' + demandCell(service) + '</div>' +
                        '<div class="services-row__act">' +
                            '<button type="button" class="btn btn-sm btn-primary services-row__primary js-service-action" data-action="edit">' +
                                '<i class="ri ri-edit-line me-1"></i>' + escapeHtml(t.actions.edit) +
                            '</button>' +
                            '<div class="dropdown">' +
                                '<button type="button" class="btn btn-sm btn-icon btn-text-secondary" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="' + escapeHtml(t.actions.more) + '">' +
                                    '<i class="ri ri-more-2-line"></i>' +
                                '</button>' +
                                '<div class="dropdown-menu dropdown-menu-end">' +
                                    '<a class="dropdown-item" href="/orders/create?service=' + service.id + '"><i class="ri ri-calendar-check-line me-2"></i>' + escapeHtml(t.actions.book) + '</a>' +
                                    '<div class="dropdown-divider"></div>' +
                                    '<button type="button" class="dropdown-item text-danger js-service-action" data-action="delete"><i class="ri ri-delete-bin-line me-2"></i>' + escapeHtml(t.actions.delete) + '</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>';

                    row.addEventListener('click', function (event) {
                        if (event.target.closest('a, button, .dropdown')) return;
                        openServiceModal(service);
                    });

                    row.querySelectorAll('.js-service-action').forEach(function (button) {
                        button.addEventListener('click', function (event) {
                            event.stopPropagation();
                            runServiceAction(service, this.dataset.action);
                        });
                    });

                    listContainer.appendChild(row);
                });
            }

            function runServiceAction(service, action) {
                if (action === 'edit') return openServiceModal(service);
                if (action === 'delete') return askToDeleteService(service);
                if (action === 'fix-duration') return applyMeasuredDuration(service);
            }

            /* ---------- loading ---------- */

            async function loadServices() {
                listContainer.innerHTML = '<div class="services-empty">' + escapeHtml(t.alerts.noServices) + '</div>';

                const params = new URLSearchParams();
                if (state.search) params.append('search', state.search);
                if (state.categoryId && state.categoryId !== 'none') params.append('category_id', state.categoryId);

                try {
                    const response = await fetch('/api/v1/services?' + params.toString(), {
                        headers: authHeaders(),
                        credentials: 'include',
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        listContainer.innerHTML = '<div class="services-empty text-danger">' + escapeHtml(t.alerts.loadError) + '</div>';
                        return;
                    }

                    const groups = result.data?.groups || [];
                    let services = groups.flatMap(group => group.services || []);

                    // «Без категории» is a chip, not a query the API knows about.
                    if (state.categoryId === 'none') {
                        services = services.filter(service => !service.category_id);
                    }

                    state.services = services;
                    state.categoryOptions = result.meta?.category_options || [];
                    state.uncategorized = result.meta?.uncategorized || { total_services: 0 };
                    state.stats = result.meta?.stats || { total_all: 0, needs_review: 0 };

                    renderChips();
                    renderLead();
                    renderServices();
                    updateCategorySelect();
                } catch (error) {
                    console.error(error);
                    listContainer.innerHTML = '<div class="services-empty text-danger">' + escapeHtml(t.alerts.loadError) + '</div>';
                }
            }

            function updateCategorySelect() {
                const current = serviceCategorySelect.value;
                serviceCategorySelect.innerHTML = '<option value="">' + escapeHtml(t.groups.uncategorized) + '</option>' +
                    state.categoryOptions.map(category =>
                        '<option value="' + category.id + '">' + escapeHtml(category.name) + '</option>').join('');
                serviceCategorySelect.value = current;
            }

            /* ---------- service modal ---------- */

            function setCalculatorVisibility(visible) {
                costCalculatorPanel.classList.toggle('d-none', !visible);
                costCalculatorToggle.textContent = visible
                    ? t.modals.service.costCalculator.close
                    : t.modals.service.costCalculator.open;
                costCalculatorToggle.setAttribute('aria-expanded', visible ? 'true' : 'false');
            }

            function calculatorInputs() {
                return ['materials', 'staff', 'other']
                    .map(key => document.getElementById('cost-calculator-' + key))
                    .filter(Boolean);
            }

            function calculateCalculatorTotal() {
                let total = 0;
                let hasValue = false;

                calculatorInputs().forEach(function (input) {
                    const value = parseNumber(input.value);
                    if (value !== null) {
                        total += value;
                        hasValue = true;
                    }
                });

                costCalculatorTotal.textContent = hasValue ? formatCurrency(total) : '—';
                costCalculatorApply.disabled = !hasValue;

                return hasValue ? Number(total.toFixed(2)) : null;
            }

            function resetCostCalculator(hidePanel = true) {
                calculatorInputs().forEach(input => { input.value = ''; });
                calculateCalculatorTotal();
                if (hidePanel) setCalculatorVisibility(false);
            }

            function updateMarginIndicator() {
                const price = parseNumber(servicePriceInput.value);
                const cost = parseNumber(serviceCostInput.value);

                if (price === null || cost === null) {
                    serviceMarginIndicator.textContent = '—';
                    serviceMarginIndicator.classList.remove('bg-label-danger');
                    serviceMarginIndicator.classList.add('bg-label-success');
                    serviceMarginHint.textContent = t.modals.service.margin.hint;
                    return;
                }

                const margin = Number((price - cost).toFixed(2));
                serviceMarginIndicator.textContent = formatCurrency(margin);
                serviceMarginIndicator.classList.toggle('bg-label-danger', margin < 0);
                serviceMarginIndicator.classList.toggle('bg-label-success', margin >= 0);
                serviceMarginHint.textContent = margin < 0
                    ? t.modals.service.margin.negativeHint
                    : t.modals.service.margin.positiveHint;
            }

            function renderUpsellPicker(service) {
                const chosen = new Set((service?.upsell_suggestions || []).map(Number));

                serviceUpsell.innerHTML = state.services
                    .filter(item => Number(item.id) !== Number(service?.id))
                    .map(function (item) {
                        const active = chosen.has(Number(item.id)) ? ' is-active btn-outline-primary' : ' btn-outline-secondary';
                        return '<button type="button" class="btn btn-sm services-upsell-option' + active + '" data-id="' + item.id + '"' +
                            ' aria-pressed="' + (chosen.has(Number(item.id)) ? 'true' : 'false') + '">' +
                            escapeHtml(item.name) + '</button>';
                    }).join('');

                serviceUpsell.querySelectorAll('.services-upsell-option').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const pressed = this.getAttribute('aria-pressed') === 'true';
                        this.setAttribute('aria-pressed', pressed ? 'false' : 'true');
                        this.classList.toggle('btn-outline-primary', !pressed);
                        this.classList.toggle('btn-outline-secondary', pressed);
                    });
                });

                // What the bookings already show, offered rather than asserted.
                const observed = (service?.companions?.observed || []);
                serviceUpsellSuggested.hidden = observed.length === 0;
                serviceUpsellSuggested.textContent = observed.length
                    ? t.modals.service.upsellSuggested + ' ' + observed.map(item => item.name).join(', ')
                    : '';
            }

            function selectedUpsell() {
                return Array.from(serviceUpsell.querySelectorAll('.services-upsell-option[aria-pressed="true"]'))
                    .map(button => Number(button.dataset.id));
            }

            function openServiceModal(service) {
                state.editingServiceId = service ? service.id : null;
                clearFormErrors(serviceFormErrors);
                serviceForm.reset();
                resetCostCalculator();

                serviceModalLabel.textContent = service ? t.modals.service.editTitle : t.modals.service.createTitle;
                serviceFormSubmit.textContent = service ? t.modals.service.saveButton : t.modals.service.createButton;

                updateCategorySelect();
                serviceNameInput.value = service?.name || '';
                serviceCategorySelect.value = service?.category_id || '';
                servicePriceInput.value = service?.base_price ?? '';
                serviceCostInput.value = service?.cost ?? '';
                serviceDurationInput.value = service?.duration_min ?? '';

                const duration = service?.duration || {};
                serviceDurationHint.hidden = !duration.needs_review;
                serviceDurationHint.textContent = duration.needs_review
                    ? duration.hint + ' · ' + duration.samples_text
                    : '';

                renderUpsellPicker(service);
                updateMarginIndicator();
                serviceModal.show();
            }

            async function submitService() {
                clearFormErrors(serviceFormErrors);
                serviceFormSubmit.disabled = true;

                const payload = {
                    name: serviceNameInput.value.trim(),
                    category_id: serviceCategorySelect.value ? Number(serviceCategorySelect.value) : null,
                    base_price: parseNumber(servicePriceInput.value),
                    cost: parseNumber(serviceCostInput.value),
                    duration_min: parseNumber(serviceDurationInput.value),
                    upsell_suggestions: selectedUpsell(),
                };

                const editing = state.editingServiceId;

                try {
                    const response = await fetch(editing ? '/api/v1/services/' + editing : '/api/v1/services', {
                        method: editing ? 'PATCH' : 'POST',
                        headers: authHeaders(),
                        credentials: 'include',
                        body: JSON.stringify(payload),
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        displayFormErrors(serviceFormErrors, result.error?.fields || result.errors || {});
                        return;
                    }

                    serviceModal.hide();
                    showAlert('success', result.message || (editing ? t.messages.updated : t.messages.created));
                    await loadServices();
                } catch (error) {
                    console.error(error);
                    showAlert('danger', t.alerts.loadError);
                } finally {
                    serviceFormSubmit.disabled = false;
                }
            }

            /**
             * Agreeing with the clock: the plan is replaced by what was measured,
             * and everything else about the service is sent back untouched.
             */
            async function applyMeasuredDuration(service) {
                const minutes = service.duration?.measured;

                if (!minutes) {
                    return;
                }

                try {
                    const response = await fetch('/api/v1/services/' + service.id, {
                        method: 'PATCH',
                        headers: authHeaders(),
                        credentials: 'include',
                        body: JSON.stringify({
                            name: service.name,
                            category_id: service.category_id,
                            base_price: service.base_price,
                            cost: service.cost,
                            duration_min: minutes,
                            upsell_suggestions: service.upsell_suggestions || [],
                        }),
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        showAlert('danger', result.error?.message || t.alerts.loadError);
                        return;
                    }

                    showAlert('success', t.duration.applied.replace(':minutes', minutes));
                    await loadServices();
                } catch (error) {
                    console.error(error);
                    showAlert('danger', t.alerts.loadError);
                }
            }

            /* ---------- categories ---------- */

            function openCategoryModal(category) {
                state.editingCategoryId = category ? category.id : null;
                clearFormErrors(categoryFormErrors);
                categoryForm.reset();
                categoryModalLabel.textContent = category ? t.modals.category.editTitle : t.modals.category.createTitle;
                categoryFormSubmit.textContent = category ? t.modals.category.saveButton : t.modals.category.createButton;
                categoryNameInput.value = category?.name || '';
                categoryModal.show();
            }

            async function submitCategory() {
                clearFormErrors(categoryFormErrors);
                categoryFormSubmit.disabled = true;

                const editing = state.editingCategoryId;

                try {
                    const response = await fetch(editing ? '/api/v1/service-categories/' + editing : '/api/v1/service-categories', {
                        method: editing ? 'PATCH' : 'POST',
                        headers: authHeaders(),
                        credentials: 'include',
                        body: JSON.stringify({ name: categoryNameInput.value.trim() }),
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        displayFormErrors(categoryFormErrors, result.error?.fields || result.errors || {});
                        return;
                    }

                    categoryModal.hide();
                    showAlert('success', result.message || (editing ? t.messages.categoryUpdated : t.messages.categoryCreated));
                    await loadServices();
                } catch (error) {
                    console.error(error);
                    showAlert('danger', t.alerts.loadError);
                } finally {
                    categoryFormSubmit.disabled = false;
                }
            }

            /* ---------- deletion ---------- */

            function askToDeleteService(service) {
                confirmModalLabel.textContent = t.modals.confirm.serviceTitle;
                confirmModalBody.textContent = t.modals.confirm.serviceBody.replace(':name', service.name);
                state.confirmAction = () => deleteService(service.id);
                confirmModal.show();
            }

            function askToDeleteCategory(category) {
                confirmModalLabel.textContent = t.modals.confirm.categoryTitle;
                confirmModalBody.textContent = t.modals.confirm.categoryBody.replace(':name', category.name);
                state.confirmAction = () => deleteCategory(category.id);
                confirmModal.show();
            }

            async function deleteService(id) {
                const response = await fetch('/api/v1/services/' + id, {
                    method: 'DELETE',
                    headers: authHeaders(),
                    credentials: 'include',
                });

                const result = await response.json().catch(() => ({}));
                confirmModal.hide();

                if (!response.ok) {
                    showAlert('danger', result.error?.message || t.alerts.loadError);
                    return;
                }

                showAlert('success', result.message || t.messages.deleted);
                await loadServices();
            }

            async function deleteCategory(id) {
                const response = await fetch('/api/v1/service-categories/' + id, {
                    method: 'DELETE',
                    headers: authHeaders(),
                    credentials: 'include',
                });

                const result = await response.json().catch(() => ({}));
                confirmModal.hide();

                if (!response.ok) {
                    showAlert('danger', result.error?.message || t.alerts.loadError);
                    return;
                }

                state.categoryId = '';
                showAlert('success', result.message || t.messages.categoryDeleted);
                await loadServices();
            }

            /* ---------- wiring ---------- */

            document.getElementById('new-service-btn').addEventListener('click', () => openServiceModal(null));

            chipsContainer.addEventListener('click', function (event) {
                if (event.target.closest('#new-category-btn')) {
                    openCategoryModal(null);
                    return;
                }

                const chip = event.target.closest('.services-chip');
                if (!chip || chip.dataset.category === undefined) return;

                state.categoryId = chip.dataset.category;
                loadServices();
            });

            document.getElementById('edit-category-btn').addEventListener('click', function () {
                const category = state.categoryOptions.find(c => String(c.id) === String(state.categoryId));
                if (category) openCategoryModal(category);
            });

            document.getElementById('delete-category-btn').addEventListener('click', function () {
                const category = state.categoryOptions.find(c => String(c.id) === String(state.categoryId));
                if (category) askToDeleteCategory(category);
            });

            // The filter applies itself. «Применить» was one more press between
            // typing a name and seeing it.
            let searchTimer = null;
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    const value = searchInput.value.trim();
                    if (value === state.search) return;
                    state.search = value;
                    loadServices();
                }, 400);
            });

            serviceForm.addEventListener('submit', function (event) {
                event.preventDefault();
                submitService();
            });

            categoryForm.addEventListener('submit', function (event) {
                event.preventDefault();
                submitCategory();
            });

            confirmModalConfirm.addEventListener('click', function () {
                const action = state.confirmAction;
                state.confirmAction = null;
                if (action) action();
            });

            costCalculatorToggle.addEventListener('click', function () {
                setCalculatorVisibility(costCalculatorPanel.classList.contains('d-none'));
                calculateCalculatorTotal();
            });

            calculatorInputs().forEach(input => input.addEventListener('input', calculateCalculatorTotal));

            costCalculatorApply.addEventListener('click', function () {
                const total = calculateCalculatorTotal();
                if (total === null) return;
                serviceCostInput.value = Number.isInteger(total) ? String(total) : total.toFixed(2);
                setCalculatorVisibility(false);
                updateMarginIndicator();
            });

            costCalculatorReset.addEventListener('click', function () {
                resetCostCalculator(false);
                updateMarginIndicator();
            });

            servicePriceInput.addEventListener('input', updateMarginIndicator);
            serviceCostInput.addEventListener('input', updateMarginIndicator);

            resetCostCalculator();
            updateMarginIndicator();
            loadServices();
        });
    </script>
@endsection
