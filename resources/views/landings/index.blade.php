@extends('layouts.app')

@section('title', __('landings.index.title'))

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ __('landings.index.title') }}</h4>
            <p class="text-muted mb-0">{{ __('landings.index.subtitle') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('landings.create') }}" class="btn btn-primary">
                <i class="ri ri-add-line me-1"></i>
                {{ __('landings.actions.create') }}
            </a>
        </div>
    </div>

    <div id="landings-alerts"></div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('landings.table.headers.title') }}</th>
                            <th>{{ __('landings.table.headers.type') }}</th>
                            <th>{{ __('landings.table.headers.views') }}</th>
                            <th>{{ __('landings.table.headers.status') }}</th>
                            <th>{{ __('landings.table.headers.created_at') }}</th>
                            <th class="text-end">{{ __('landings.table.headers.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="landings-table-body">
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted" id="landings-empty">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <i class="ri ri-layout-4-line icon-base" style="font-size: 32px;"></i>
                                    <div>{{ __('landings.table.empty') }}</div>
                                    <a href="{{ route('landings.create') }}" class="btn btn-sm btn-primary">
                                        {{ __('landings.actions.create_first') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
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

            function buildRow(landing) {
                const tr = document.createElement('tr');
                tr.dataset.id = landing.id;
                const statusBadge = landing.is_active
                    ? `<span class="badge bg-label-success">${STATUS_LABELS.active}</span>`
                    : `<span class="badge bg-label-secondary">${STATUS_LABELS.inactive}</span>`;
                const toggleId = 'toggle-' + landing.id;

                tr.innerHTML = `
                    <td>
                        <div class="fw-semibold mb-1">${landing.title}</div>
                        <div class="small text-muted">${landing.urls.public}</div>
                    </td>
                    <td>${TYPE_LABELS[landing.type] || landing.type}</td>
                    <td>${landing.views}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            ${statusBadge}
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="${toggleId}" ${landing.is_active ? 'checked' : ''} data-toggle-landing="${landing.id}">
                            </div>
                        </div>
                    </td>
                    <td>${formatDate(landing.created_at)}</td>
                    <td class="text-end">
                        <div class="btn-group" role="group">
                            <a href="${landing.urls.public}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="ri ri-external-link-line"></i>
                            </a>
                            <a href="/landings/${landing.id}/edit" class="btn btn-sm btn-outline-primary">
                                <i class="ri ri-edit-line"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-delete-landing="${landing.id}">
                                <i class="ri ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </td>
                `;

                return tr;
            }

            function renderLandings(landings) {
                if (!tableBody) return;
                tableBody.innerHTML = '';
                if (!landings.length) {
                    if (emptyState) {
                        const clone = emptyState.cloneNode(true);
                        clone.id = '';
                        tableBody.appendChild(clone);
                    }
                    return;
                }

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
