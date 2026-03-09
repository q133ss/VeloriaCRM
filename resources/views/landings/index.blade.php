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

        .landings-stat {
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
            border-radius: 1rem;
            background: rgba(var(--bs-body-bg-rgb), 0.16);
            padding: 1rem 1.1rem;
        }

        .landings-list-card {
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
        }

        .landings-hero .btn {
            white-space: nowrap;
            align-self: flex-start;
        }

        .landing-row {
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
            border-radius: 1rem;
            background: rgba(var(--bs-body-bg-rgb), 0.16);
            padding: 1rem 1.1rem;
        }

        .landing-row + .landing-row {
            margin-top: 0.9rem;
        }

        .landing-row-url {
            word-break: break-all;
        }

        .landing-actions .btn {
            white-space: nowrap;
        }
    </style>

    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm landings-hero">
                <div class="card-body p-5 p-lg-6">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-4">
                        <div class="mw-lg-50">
                            <span class="badge bg-label-primary mb-3">Veloria Pages</span>
                            <h3 class="mb-2">{{ __('landings.index.title') }}</h3>
                            <p class="text-muted mb-0">{{ __('landings.index.subtitle') }} Создавайте страницы под акции, услуги и быстрый захват заявок без лишней технической рутины.</p>
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
                <div class="col-md-4">
                    <div class="landings-stat">
                        <div class="small text-muted text-uppercase mb-1">Всего лендингов</div>
                        <div class="fs-4 fw-semibold" id="landings-stat-total">0</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="landings-stat">
                        <div class="small text-muted text-uppercase mb-1">Активных</div>
                        <div class="fs-4 fw-semibold" id="landings-stat-active">0</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="landings-stat">
                        <div class="small text-muted text-uppercase mb-1">Всего переходов</div>
                        <div class="fs-4 fw-semibold" id="landings-stat-views">0</div>
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
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
                        <div>
                            <h4 class="mb-1">Мои лендинги</h4>
                            <p class="text-muted mb-0">Каждая строка показывает страницу, ссылку, статус публикации и быстрые действия.</p>
                        </div>
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
            const TYPE_LABELS = @json(__('landings.types'));
            const STATUS_LABELS = @json(__('landings.statuses'));

            function getCookie(name) {
                const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                return match ? decodeURIComponent(match[1]) : null;
            }

            function authHeaders() {
                const token = getCookie('token');
                const headers = { 'Accept': 'application/json', 'Content-Type': 'application/json' };
                if (token) headers['Authorization'] = 'Bearer ' + token;
                return headers;
            }

            function showAlert(type, message) {
                if (!alertsContainer) return;
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
                } catch (e) {
                    return value;
                }
            }

            function updateStats(landings) {
                const total = landings.length;
                const active = landings.filter(function (landing) { return !!landing.is_active; }).length;
                const views = landings.reduce(function (sum, landing) { return sum + Number(landing.views || 0); }, 0);
                totalStat.textContent = total;
                activeStat.textContent = active;
                viewsStat.textContent = views;
            }

            function buildRow(landing) {
                const row = document.createElement('div');
                row.className = 'landing-row';
                row.dataset.id = landing.id;

                const statusBadge = landing.is_active
                    ? `<span class="badge bg-label-success">${STATUS_LABELS.active}</span>`
                    : `<span class="badge bg-label-secondary">${STATUS_LABELS.inactive}</span>`;
                const toggleId = 'toggle-' + landing.id;

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
                            <div class="small text-muted text-uppercase mb-1">${@json(__('landings.table.headers.status'))}</div>
                            <div class="d-flex align-items-center gap-2">
                                ${statusBadge}
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="${toggleId}" ${landing.is_active ? 'checked' : ''} data-toggle-landing="${landing.id}">
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-2">
                            <div class="small text-muted text-uppercase mb-1">${@json(__('landings.table.headers.created_at'))}</div>
                            <div>${formatDate(landing.created_at)}</div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex flex-wrap justify-content-lg-end gap-2 landing-actions">
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
                if (!tableBody) return;
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
                        if (!response.ok) {
                            throw new Error('Failed to load landings');
                        }
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
                        if (!response.ok) {
                            throw new Error('Failed to update');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        showAlert('success', data.message || '{{ __('landings.notifications.updated') }}');
                        return data.data;
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
                        if (!response.ok) {
                            throw new Error('Failed to delete');
                        }
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
                    const landingId = target.getAttribute('data-toggle-landing');
                    updateLandingStatus(landingId, target.checked);
                }
            });

            tableBody.addEventListener('click', function (event) {
                const target = event.target.closest('[data-delete-landing]');
                if (target) {
                    const landingId = target.getAttribute('data-delete-landing');
                    deleteLanding(landingId);
                }
            });

            fetchLandings();
        });
    </script>
@endsection
