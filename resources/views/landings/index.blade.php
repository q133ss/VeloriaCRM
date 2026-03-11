@extends('layouts.app')

@section('title', __('landings.index.title'))

@section('content')
    <style>
        .landings-hero {
            border: 1px solid rgba(var(--bs-primary-rgb), 0.16);
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.16), transparent 28%),
                linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08), rgba(var(--bs-body-bg-rgb), 0.02));
        }

        .landings-stat,
        .landing-row,
        .landings-list-card {
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
            border-radius: 1rem;
            background: rgba(var(--bs-body-bg-rgb), 0.16);
        }

        .landings-stat {
            padding: 1rem 1.1rem;
        }

        .landing-row {
            padding: 1rem 1.1rem;
        }

        .landing-row + .landing-row {
            margin-top: 0.9rem;
        }

        .landing-row-url {
            word-break: break-all;
        }
    </style>

    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm landings-hero">
                <div class="card-body p-5">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-4">
                        <div class="mw-lg-50">
                            <span class="badge bg-label-primary mb-3">Veloria Pages</span>
                            <h3 class="mb-2">{{ __('landings.index.title') }}</h3>
                            <p class="text-muted mb-0">{{ __('landings.index.subtitle') }}</p>
                        </div>
                        <a href="{{ route('landings.create') }}" class="btn btn-primary px-4">
                            <i class="ri ri-add-line me-1"></i>
                            {{ __('landings.actions.create') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="landings-stat">
                        <div class="small text-muted text-uppercase mb-1">{{ __('landings.index.title') }}</div>
                        <div class="fs-4 fw-semibold" id="landings-stat-total">0</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="landings-stat">
                        <div class="small text-muted text-uppercase mb-1">{{ __('landings.table.headers.status') }}</div>
                        <div class="fs-4 fw-semibold" id="landings-stat-active">0</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="landings-stat">
                        <div class="small text-muted text-uppercase mb-1">{{ __('landings.table.headers.views') }}</div>
                        <div class="fs-4 fw-semibold" id="landings-stat-views">0</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="landings-stat">
                        <div class="small text-muted text-uppercase mb-1">{{ __('landings.table.headers.requests') }}</div>
                        <div class="fs-4 fw-semibold" id="landings-stat-requests">0</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div id="landings-alerts"></div>
        </div>

        <div class="col-12">
            <div class="card landings-list-card">
                <div class="card-body p-4 p-lg-5">
                    <div class="mb-4">
                        <h4 class="mb-1">{{ __('landings.index.title') }}</h4>
                        <p class="text-muted mb-0">Смотрите просмотры, заявки и статус каждого сценария в одном месте.</p>
                    </div>

                    <div id="landings-table-body"></div>

                    <div class="text-center py-5 text-muted d-none" id="landings-empty">
                        <div class="d-flex flex-column align-items-center gap-2">
                            <i class="ri ri-layout-4-line icon-base" style="font-size: 32px;"></i>
                            <div>{{ __('landings.table.empty') }}</div>
                            <a href="{{ route('landings.create') }}" class="btn btn-sm btn-primary">
                                {{ __('landings.actions.create_first') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tableBody = document.getElementById('landings-table-body');
            const emptyState = document.getElementById('landings-empty');
            const alertsContainer = document.getElementById('landings-alerts');
            const totalStat = document.getElementById('landings-stat-total');
            const activeStat = document.getElementById('landings-stat-active');
            const viewsStat = document.getElementById('landings-stat-views');
            const requestsStat = document.getElementById('landings-stat-requests');
            const TYPE_LABELS = @json(__('landings.types'));
            const STATUS_LABELS = @json(__('landings.statuses'));

            function getCookie(name) {
                const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                return match ? decodeURIComponent(match[1]) : null;
            }

            function authHeaders() {
                const token = getCookie('token');
                const headers = { Accept: 'application/json', 'Content-Type': 'application/json' };
                if (token) headers.Authorization = 'Bearer ' + token;
                return headers;
            }

            function showAlert(type, message) {
                const wrapper = document.createElement('div');
                wrapper.className = 'alert alert-' + type + ' alert-dismissible fade show';
                wrapper.role = 'alert';
                wrapper.innerHTML = `
                    <div>${message}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                alertsContainer.appendChild(wrapper);
            }

            function formatDate(value) {
                if (!value) return '—';
                try {
                    return new Date(value).toLocaleString();
                } catch (error) {
                    return value;
                }
            }

            function updateStats(landings) {
                totalStat.textContent = landings.length;
                activeStat.textContent = landings.filter(function (landing) { return !!landing.is_active; }).length;
                viewsStat.textContent = landings.reduce(function (sum, landing) { return sum + Number(landing.views || 0); }, 0);
                requestsStat.textContent = landings.reduce(function (sum, landing) { return sum + Number(landing.requests_count || 0); }, 0);
            }

            function buildRow(landing) {
                const row = document.createElement('div');
                row.className = 'landing-row';
                row.dataset.id = landing.id;

                const statusBadge = landing.is_active
                    ? `<span class="badge bg-label-success">${STATUS_LABELS.active}</span>`
                    : `<span class="badge bg-label-secondary">${STATUS_LABELS.inactive}</span>`;

                row.innerHTML = `
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-4">
                            <div class="fw-semibold mb-1">${landing.title}</div>
                            <div class="small text-muted landing-row-url">${landing.urls.public}</div>
                        </div>
                        <div class="col-sm-6 col-lg-2">
                            <div class="small text-muted text-uppercase mb-1">${@json(__('landings.table.headers.type'))}</div>
                            <div>${TYPE_LABELS[landing.type] || landing.type}</div>
                        </div>
                        <div class="col-sm-6 col-lg-2">
                            <div class="small text-muted text-uppercase mb-1">${@json(__('landings.table.headers.views'))}</div>
                            <div class="fw-semibold">${landing.views}</div>
                        </div>
                        <div class="col-sm-6 col-lg-2">
                            <div class="small text-muted text-uppercase mb-1">${@json(__('landings.table.headers.requests'))}</div>
                            <div class="fw-semibold">${landing.requests_count || 0}</div>
                        </div>
                        <div class="col-sm-6 col-lg-2">
                            <div class="small text-muted text-uppercase mb-1">${@json(__('landings.table.headers.status'))}</div>
                            <div class="d-flex align-items-center gap-2">
                                ${statusBadge}
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" ${landing.is_active ? 'checked' : ''} data-toggle-landing="${landing.id}">
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="small text-muted text-uppercase mb-1">${@json(__('landings.table.headers.created_at'))}</div>
                            <div>${formatDate(landing.created_at)}</div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="small text-muted text-uppercase mb-1">{{ __('landings.table.headers.requests') }}</div>
                            <div>${landing.last_request_at ? formatDate(landing.last_request_at) : '—'}</div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                                <a href="${landing.urls.public}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="ri ri-external-link-line me-1"></i> Открыть
                                </a>
                                <a href="/landings/${landing.id}/edit" class="btn btn-sm btn-outline-primary">
                                    <i class="ri ri-edit-line me-1"></i> Изменить
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-delete-landing="${landing.id}">
                                    <i class="ri ri-delete-bin-line me-1"></i> Удалить
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                return row;
            }

            function renderLandings(landings) {
                tableBody.innerHTML = '';
                updateStats(landings);

                if (!landings.length) {
                    emptyState.classList.remove('d-none');
                    return;
                }

                emptyState.classList.add('d-none');
                landings.forEach(function (landing) {
                    tableBody.appendChild(buildRow(landing));
                });
            }

            function fetchLandings() {
                return fetch('/api/v1/landings', { headers: authHeaders() })
                    .then(function (response) {
                        if (!response.ok) throw new Error('failed');
                        return response.json();
                    })
                    .then(function (data) {
                        renderLandings(data.data || []);
                    })
                    .catch(function () {
                        showAlert('danger', '{{ __('landings.notifications.load_failed') }}');
                    });
            }

            function updateLandingStatus(id, isActive) {
                return fetch('/api/v1/landings/' + id, {
                    method: 'PATCH',
                    headers: authHeaders(),
                    body: JSON.stringify({ is_active: isActive })
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('failed');
                        return response.json();
                    })
                    .then(function (data) {
                        showAlert('success', data.message || '{{ __('landings.notifications.updated') }}');
                    })
                    .catch(function () {
                        showAlert('danger', '{{ __('landings.notifications.status_failed') }}');
                        fetchLandings();
                    });
            }

            function deleteLanding(id) {
                if (!confirm('{{ __('landings.dialogs.delete_confirm') }}')) {
                    return;
                }

                fetch('/api/v1/landings/' + id, {
                    method: 'DELETE',
                    headers: authHeaders()
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('failed');
                        return response.json();
                    })
                    .then(function (data) {
                        showAlert('success', data.message || '{{ __('landings.notifications.deleted') }}');
                        fetchLandings();
                    })
                    .catch(function () {
                        showAlert('danger', '{{ __('landings.notifications.delete_failed') }}');
                    });
            }

            tableBody.addEventListener('change', function (event) {
                const target = event.target;
                if (target && target.matches('[data-toggle-landing]')) {
                    updateLandingStatus(target.getAttribute('data-toggle-landing'), target.checked);
                }
            });

            tableBody.addEventListener('click', function (event) {
                const target = event.target.closest('[data-delete-landing]');
                if (target) {
                    deleteLanding(target.getAttribute('data-delete-landing'));
                }
            });

            fetchLandings();
        });
    </script>
@endsection
