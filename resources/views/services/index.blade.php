@extends('layouts.app')

@section('title', __('services.title'))

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ __('services.title') }}</h4>
            <p class="text-muted mb-0">{{ __('services.subtitle') }}</p>
        </div>
        <div class="d-flex gap-2">
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

    <div id="services-alerts"></div>

    <div class="card mb-4">
        <div class="card-body">
            <form id="services-filters-form" class="row g-3 align-items-end">
                <div class="col-lg-4">
                    <label for="filter-search" class="form-label">{{ __('services.filters.search_label') }}</label>
                    <div class="position-relative">
                        <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted">
                            <i class="ri ri-search-line"></i>
                        </span>
                        <input
                            type="text"
                            class="form-control ps-5"
                            id="filter-search"
                            name="search"
                            placeholder="{{ __('services.filters.search_placeholder') }}"
                        />
                    </div>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label for="filter-category" class="form-label">{{ __('services.filters.category_label') }}</label>
                    <select class="form-select" id="filter-category" name="category_id">
                        <option value="">{{ __('services.filters.category_placeholder') }}</option>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
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
                <div class="col-sm-6 col-lg-2">
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
                <div class="col-sm-6 col-lg-1">
                    <label for="filter-sort" class="form-label">{{ __('services.filters.sort_label') }}</label>
                    <select class="form-select" id="filter-sort" name="sort">
                        <option value="name">{{ __('services.filters.sort_options.name') }}</option>
                        <option value="base_price">{{ __('services.filters.sort_options.base_price') }}</option>
                        <option value="duration_min">{{ __('services.filters.sort_options.duration_min') }}</option>
                        <option value="created_at">{{ __('services.filters.sort_options.created_at') }}</option>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-1">
                    <label for="filter-direction" class="form-label">{{ __('services.filters.direction_label') }}</label>
                    <select class="form-select" id="filter-direction" name="direction">
                        <option value="asc">{{ __('services.filters.direction_options.asc') }}</option>
                        <option value="desc">{{ __('services.filters.direction_options.desc') }}</option>
                    </select>
                </div>
                <div class="col-lg-12 col-xl-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">{{ __('services.filters.apply') }}</button>
                    <button type="button" class="btn btn-outline-secondary flex-fill" id="filters-reset">
                        {{ __('services.filters.reset') }}
                    </button>
                </div>
            </form>
            <div id="services-summary" class="small text-muted mt-3 d-flex flex-wrap gap-3"></div>
        </div>
    </div>

    <div id="services-groups" class="row g-4"></div>

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
                            </div>
                            <div class="col-md-4">
                                <label for="service-duration" class="form-label">{{ __('services.modals.service.duration_min') }}</label>
                                <input type="number" class="form-control" id="service-duration" name="duration_min" min="5" step="5" required />
                            </div>
                            <div class="col-12">
                                <label for="service-upsell" class="form-label">{{ __('services.modals.service.upsell') }}</label>
                                <textarea class="form-control" id="service-upsell" name="upsell_suggestions" rows="3"></textarea>
                                <div class="form-text">{{ __('services.modals.service.upsell_hint') }}</div>
                            </div>
                        </div>
                        <div id="service-form-errors" class="alert alert-danger mt-3 d-none"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">@lang('Cancel')</button>
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
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">@lang('Cancel')</button>
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
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">@lang('Cancel')</button>
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
                alerts: {
                    loadError: @json(__('services.alerts.load_error')),
                    noServices: @json(__('services.alerts.no_services')),
                    validationFailed: @json(__('services.alerts.validation_failed')),
                },
                modals: {
                    service: {
                        createTitle: @json(__('services.modals.service.create_title')),
                        editTitle: @json(__('services.modals.service.edit_title')),
                        createButton: @json(__('services.modals.service.create')),
                        saveButton: @json(__('services.modals.service.save')),
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
                    upsell: @json(__('services.table.upsell')),
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
            const serviceUpsellTextarea = document.getElementById('service-upsell');
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
                    groupsContainer.innerHTML = '<div class="col-12"><div class="card border-0 shadow-sm">' +
                        '<div class="card-body text-center py-5 text-muted">' +
                        '<div class="spinner-border text-primary mb-3" role="status"></div>' +
                        '<div>' + escapeHtml(translations.ui.loading) + '</div>' +
                        '</div></div></div>';
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
                    return '<span>' + item + '</span>';
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
                    groupsContainer.innerHTML = '<div class="col-12"><div class="alert alert-info mb-0">' + escapeHtml(translations.alerts.noServices) + '</div></div>';
                    return;
                }

                const html = state.groups.map(function (group) {
                    const totalInCategory = totalForCategory(group.id);
                    const servicesHtml = (group.services || []).map(function (service) {
                        const upsell = (service.upsell_suggestions || []).map(function (item) {
                            return '<span class="badge bg-label-primary me-1 mb-1">' + escapeHtml(item) + '</span>';
                        }).join('');

                        const metadataItems = [
                            { icon: 'ri-time-line', label: translations.table.duration, value: formatDuration(service.duration_min) },
                        ];

                        if (service.cost !== null && service.cost !== undefined) {
                            metadataItems.push({ icon: 'ri-calculator-line', label: translations.table.cost, value: formatCurrency(service.cost) });
                        }

                        if (service.margin !== null && service.margin !== undefined) {
                            metadataItems.push({ icon: 'ri-equalizer-line', label: translations.table.margin, value: formatCurrency(service.margin) });
                        }

                        const metadataHtml = metadataItems.map(function (item) {
                            return '<li><i class="ri ' + item.icon + ' me-2"></i><span class="text-muted me-1">' + escapeHtml(item.label) + ':</span>' + escapeHtml(item.value) + '</li>';
                        }).join('');

                        const updated = service.updated_at ? escapeHtml(translations.table.updatedAt.replace(':date', formatDate(service.updated_at))) : '';

                        return (
                            '<div class="col-12 col-lg-4">' +
                                '<div class="card h-100 shadow-none border">' +
                                    '<div class="card-body d-flex flex-column">' +
                                        '<div class="d-flex justify-content-between align-items-start mb-3">' +
                                            '<div>' +
                                                '<h6 class="mb-1">' + escapeHtml(service.name) + '</h6>' +
                                                '<span class="badge bg-label-primary">' + escapeHtml(formatCurrency(service.base_price)) + '</span>' +
                                            '</div>' +
                                            '<div class="btn-group btn-group-sm">' +
                                                '<button type="button" class="btn btn-outline-primary" data-action="edit-service" data-service-id="' + service.id + '"><i class="ri ri-edit-line"></i></button>' +
                                                '<button type="button" class="btn btn-outline-danger" data-action="delete-service" data-service-id="' + service.id + '"><i class="ri ri-delete-bin-line"></i></button>' +
                                            '</div>' +
                                        '</div>' +
                                        '<ul class="list-unstyled small text-muted mb-3 d-flex flex-column gap-1">' + (metadataHtml || '') + '</ul>' +
                                        (upsell ? '<div class="small mb-3"><span class="text-muted d-block mb-1">' + escapeHtml(translations.table.upsell) + ':</span>' + upsell + '</div>' : '') +
                                        (updated ? '<div class="mt-auto text-muted small">' + updated + '</div>' : '') +
                                    '</div>' +
                                '</div>' +
                            '</div>'
                        );
                    }).join('');

                    const categoryActions = group.id !== null
                        ? '<div class="btn-group btn-group-sm">' +
                            '<button type="button" class="btn btn-outline-primary" data-action="edit-category" data-category-id="' + group.id + '"><i class="ri ri-edit-line"></i></button>' +
                            '<button type="button" class="btn btn-outline-danger" data-action="delete-category" data-category-id="' + group.id + '"><i class="ri ri-delete-bin-line"></i></button>' +
                          '</div>'
                        : '';

                    const subtitle = group.id === null && !totalInCategory
                        ? ''
                        : '<small class="text-muted">' + group.services_count + ' / ' + (totalInCategory || group.services_count) + '</small>';

                    return (
                        '<div class="col-12">' +
                            '<div class="card">' +
                                '<div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-2">' +
                                    '<div>' +
                                        '<h5 class="mb-0">' + escapeHtml(group.name) + '</h5>' +
                                        (subtitle ? subtitle : '') +
                                    '</div>' +
                                    categoryActions +
                                '</div>' +
                                '<div class="card-body">' +
                                    (servicesHtml || '<div class="text-muted">' + escapeHtml(translations.alerts.noServices) + '</div>') +
                                '</div>' +
                            '</div>' +
                        '</div>'
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
                serviceUpsellTextarea.value = (service.upsell_suggestions || []).join('\n');
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
                    upsell_suggestions: serviceUpsellTextarea.value
                        ? serviceUpsellTextarea.value.split(/\r?\n/).map(function (item) { return item.trim(); }).filter(Boolean)
                        : [],
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
