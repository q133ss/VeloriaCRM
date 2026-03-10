@extends('layouts.app')

@section('title', 'Backoffice Audit')

@section('content')
    @include('admin.partials.styles')

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-shell">
            <section class="admin-hero">
                <h1>Журнал действий</h1>
                <p>Полный лог изменений из backoffice: кто что изменил, у какого пользователя, с какими метаданными и когда именно.</p>
            </section>

            @include('admin.partials.nav')

            <section class="admin-panel">
                <div class="admin-panel-body admin-stack">
                    <div class="admin-toolbar">
                        <input type="search" class="form-control" id="admin-audit-search" placeholder="Поиск по действию, пользователю или админу">
                        <input type="search" class="form-control" id="admin-audit-action" placeholder="Фильтр по action">
                    </div>
                    <div id="admin-audit-list" class="admin-list"></div>
                </div>
            </section>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchEl = document.getElementById('admin-audit-search');
            const actionEl = document.getElementById('admin-audit-action');
            const listEl = document.getElementById('admin-audit-list');

            const formatDate = (value) => value ? new Date(value).toLocaleString() : 'Нет данных';
            const formatMeta = (meta) => {
                if (!meta || typeof meta !== 'object') {
                    return 'Без метаданных';
                }

                return Object.entries(meta)
                    .map(([key, value]) => `${key}: ${typeof value === 'object' ? JSON.stringify(value) : value}`)
                    .join(' • ');
            };

            const loadAudit = async () => {
                const query = new URLSearchParams({
                    search: searchEl.value,
                    action: actionEl.value
                });

                const response = await fetch(`/api/v1/admin/audit?${query.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    listEl.innerHTML = '<div class="admin-empty">Не удалось загрузить журнал действий.</div>';
                    return;
                }

                const payload = await response.json();
                const items = Array.isArray(payload.data) ? payload.data : [];

                listEl.innerHTML = items.length
                    ? items.map(function (item) {
                        return `
                            <article class="admin-row">
                                <div>
                                    <div class="admin-row-title">${item.action}</div>
                                    <div class="admin-row-meta">Админ: ${item.actor?.name || 'Неизвестно'} • Пользователь: ${item.user?.name || 'Неизвестно'} • ${formatDate(item.created_at)}</div>
                                    <div class="admin-row-meta mt-1">${formatMeta(item.meta)}</div>
                                </div>
                                <span class="admin-chip">${item.subject_type ? item.subject_type.split('\\\\').pop() : 'Log'}</span>
                            </article>
                        `;
                    }).join('')
                    : '<div class="admin-empty">По этим фильтрам действий не найдено.</div>';
            };

            searchEl.addEventListener('input', function () {
                window.clearTimeout(searchEl._timer);
                searchEl._timer = window.setTimeout(loadAudit, 250);
            });
            actionEl.addEventListener('input', function () {
                window.clearTimeout(actionEl._timer);
                actionEl._timer = window.setTimeout(loadAudit, 250);
            });

            loadAudit();
        });
    </script>
@endsection
