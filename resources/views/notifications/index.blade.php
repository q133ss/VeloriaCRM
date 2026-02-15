@extends('layouts.app')

@section('title', 'Уведомления')

@section('content')
    <div class="card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h4 class="mb-1">Уведомления</h4>
                <p class="mb-0 text-muted">Все события, которые пришли в ваш аккаунт</p>
            </div>
            <div class="d-flex gap-2" data-notifications-actions>
                <input type="search" class="form-control" placeholder="Поиск по заголовку или тексту" data-notifications-search>
                <button type="button" class="btn btn-outline-primary" data-mark-all>
                    Отметить все как прочитанные
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Заголовок</th>
                            <th>Сообщение</th>
                            <th>Получено</th>
                            <th class="text-center">Статус</th>
                            <th class="text-center">Действие</th>
                        </tr>
                    </thead>
                    <tbody data-notifications-table>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                Загрузка...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center" data-pagination-wrapper>
            <div class="text-muted small" data-pagination-summary></div>
            <nav>
                <ul class="pagination mb-0" data-pagination></ul>
            </nav>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        document.addEventListener('DOMContentLoaded', () => {
            const token = (document.cookie.match(/(?:^|; )token=([^;]*)/) || [])[1];
            const headers = {
                'Accept': 'application/json',
            };
            if (token) {
                headers['Authorization'] = 'Bearer ' + token;
            }

            const tableBody = document.querySelector('[data-notifications-table]');
            const paginationWrapper = document.querySelector('[data-pagination-wrapper]');
            const paginationEl = document.querySelector('[data-pagination]');
            const paginationSummary = document.querySelector('[data-pagination-summary]');
            const searchInput = document.querySelector('[data-notifications-search]');
            const markAllBtn = document.querySelector('[data-mark-all]');

            let currentPage = 1;
            let currentSearch = '';

            const formatDate = (iso) => {
                if (!iso) return '';
                const date = new Date(iso);
                return date.toLocaleString();
            };

            const renderRows = (items) => {
                if (!items.length) {
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">Уведомлений пока нет</td></tr>';
                    return;
                }

                tableBody.innerHTML = items.map((item) => {
                    const actionHtml = item.action_url
                        ? `<a class="btn btn-sm btn-outline-primary" href="${item.action_url}">Открыть</a>`
                        : '<span class="text-muted">—</span>';

                    return `
                        <tr>
                            <td class="fw-semibold">${item.title}</td>
                            <td>${item.message}</td>
                            <td>${formatDate(item.created_at)}</td>
                            <td class="text-center">
                                ${item.is_read ? '<span class="badge bg-success-subtle text-success">Прочитано</span>' : '<span class="badge bg-warning-subtle text-warning">Непрочитано</span>'}
                            </td>
                            <td class="text-center">${actionHtml}</td>
                        </tr>
                    `;
                }).join('');
            };

            const renderPagination = (meta) => {
                paginationEl.innerHTML = '';
                const totalPages = meta.last_page || 1;

                const buildButton = (page, label, disabled = false, active = false) => {
                    const li = document.createElement('li');
                    li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
                    const a = document.createElement('a');
                    a.className = 'page-link';
                    a.href = '#';
                    a.textContent = label;
                    a.addEventListener('click', (event) => {
                        event.preventDefault();
                        if (disabled || active) return;
                        currentPage = page;
                        loadData();
                    });
                    li.appendChild(a);
                    paginationEl.appendChild(li);
                };

                buildButton(meta.current_page - 1, '‹', meta.current_page <= 1);

                for (let page = 1; page <= totalPages; page++) {
                    buildButton(page, page, false, page === meta.current_page);
                }

                buildButton(meta.current_page + 1, '›', meta.current_page >= totalPages);

                paginationSummary.textContent = `Показаны ${meta.per_page ?? 0} из ${meta.total ?? 0} уведомлений`;
            };

            const loadData = () => {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-5">Загрузка...</td></tr>';

                const params = new URLSearchParams({ page: currentPage });
                if (currentSearch) params.set('search', currentSearch);

                fetch(`/api/v1/notifications?${params.toString()}`, { headers })
                    .then((response) => response.json())
                    .then((payload) => {
                        renderRows(payload.data || []);
                        renderPagination(payload.meta || {});
                    })
                    .catch(() => {
                        tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-danger">Не удалось загрузить уведомления</td></tr>';
                        paginationEl.innerHTML = '';
                        paginationSummary.textContent = '';
                    });
            };

            const markAll = () => {
                if (markAllBtn) {
                    markAllBtn.disabled = true;
                }

                fetch('/api/v1/notifications/mark-as-read', {
                    method: 'POST',
                    headers: {
                        ...headers,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ ids: [] }),
                })
                    .then(() => syncNavbarUnreadBadge(0))
                    .then(() => loadData())
                    .finally(() => {
                        if (markAllBtn) {
                            markAllBtn.disabled = false;
                        }
                    });
            };

            const syncNavbarUnreadBadge = (count) => {
                const badge = document.querySelector('[data-notifications-count]');
                if (!badge) return;
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : String(count);
                    badge.classList.remove('d-none');
                } else {
                    badge.classList.add('d-none');
                }
            };

            // When user opens the notifications page, consider them "read" and reset the navbar counter.
            const markAllOnVisit = () => {
                return fetch('/api/v1/notifications/mark-as-read', {
                    method: 'POST',
                    headers: {
                        ...headers,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ ids: [] }),
                })
                    .then(() => syncNavbarUnreadBadge(0))
                    .catch(() => {});
            };

            if (searchInput) {
                let debounceTimer;
                searchInput.addEventListener('input', (event) => {
                    const value = event.target.value.trim();
                    currentSearch = value;
                    currentPage = 1;
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(loadData, 300);
                });
            }

            if (markAllBtn) {
                markAllBtn.addEventListener('click', () => markAll());
            }

            paginationWrapper?.classList.toggle('d-none', false);

            markAllOnVisit().finally(() => loadData());
        });
    </script>
@endpush
