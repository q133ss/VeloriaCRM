@extends('layouts.app')

@section('title', 'Backoffice Users')

@section('content')
    @include('admin.partials.styles')

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-shell">
            <section class="admin-hero">
                <h1>Пользователи</h1>
                <p>Люди, а не технический реестр: быстрый поиск, статус аккаунта, заметки оператора и понятная карточка пользователя.</p>
            </section>

            @include('admin.partials.nav')

            <div class="admin-two-column">
                <section class="admin-panel">
                    <div class="admin-panel-body admin-stack">
                        <div class="admin-toolbar">
                            <button type="button" class="btn btn-primary" id="admin-user-create-btn">Новый пользователь</button>
                            <input type="search" class="form-control" id="admin-user-search" placeholder="Найти по имени, email или телефону">
                            <select class="form-select" id="admin-user-status">
                                <option value="all">Все статусы</option>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                        <div id="admin-users-list" class="admin-list"></div>
                    </div>
                </section>

                <section class="admin-panel soft">
                    <div class="admin-panel-body">
                        <div id="admin-user-detail" class="admin-empty">Выберите пользователя слева, чтобы посмотреть контекст и управлять статусом.</div>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('admin-user-search');
            const statusSelect = document.getElementById('admin-user-status');
            const createButton = document.getElementById('admin-user-create-btn');
            const listEl = document.getElementById('admin-users-list');
            const detailEl = document.getElementById('admin-user-detail');
            let users = [];
            let selectedUserId = null;
            let createMode = false;

            const formatDate = (value) => value ? new Date(value).toLocaleString() : 'Нет данных';

            const baseFormValues = {
                name: '',
                email: '',
                phone: '',
                password: '',
                status: 'active',
                is_admin: false,
                admin_role: '',
                admin_notes: ''
            };

            const renderList = () => {
                if (!users.length) {
                    listEl.innerHTML = '<div class="admin-empty">По этим условиям пользователей не найдено.</div>';
                    return;
                }

                listEl.innerHTML = users.map(function (user) {
                    const active = user.id === selectedUserId && !createMode ? 'is-active' : '';
                    return `<article class="admin-row is-clickable ${active}" data-user-id="${user.id}"><div><div class="admin-row-title">${user.name || 'Без имени'}</div><div class="admin-row-meta">${user.email || 'Без email'} • ${user.clients_count} клиентов • ${user.subscription_transactions_count} платежей</div></div><span class="admin-chip ${user.status === 'suspended' ? 'danger' : 'success'}">${user.status_label}</span></article>`;
                }).join('');

                listEl.querySelectorAll('[data-user-id]').forEach(function (node) {
                    node.addEventListener('click', function () {
                        loadUser(Number(node.getAttribute('data-user-id')));
                    });
                });
            };

            const renderForm = (user, mode) => {
                const isCreate = mode === 'create';
                const values = isCreate ? { ...baseFormValues } : {
                    name: user.name || '',
                    email: user.email || '',
                    phone: user.phone || '',
                    password: '',
                    status: user.status || 'active',
                    is_admin: !!user.is_admin,
                    admin_role: user.admin_role || '',
                    admin_notes: user.admin_notes || ''
                };

                detailEl.innerHTML = `
                    <div class="admin-stack">
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div>
                                <h3 class="mb-1">${isCreate ? 'Новый пользователь' : (user.name || 'Без имени')}</h3>
                                <p class="text-muted mb-0">${isCreate ? 'Создание нового аккаунта из backoffice.' : `${user.email || 'Без email'}${user.phone ? ' • ' + user.phone : ''}`}</p>
                            </div>
                            ${isCreate ? '<span class="admin-chip">Draft</span>' : `<span class="admin-chip ${user.status === 'suspended' ? 'danger' : 'success'}">${user.status_label}</span>`}
                        </div>
                        ${isCreate ? '' : `
                            <div class="admin-detail-grid">
                                <div class="admin-detail-card"><div class="admin-metric-label">Тариф</div><div class="admin-row-title">${user.latest_plan ? user.latest_plan.name : 'Без плана'}</div></div>
                                <div class="admin-detail-card"><div class="admin-metric-label">Клиенты</div><div class="admin-row-title">${user.activity.clients_total}</div></div>
                                <div class="admin-detail-card"><div class="admin-metric-label">Заказы</div><div class="admin-row-title">${user.activity.orders_total}</div></div>
                                <div class="admin-detail-card"><div class="admin-metric-label">Тикеты</div><div class="admin-row-title">${user.support.total}</div></div>
                            </div>
                            <div class="admin-detail-card">
                                <div class="admin-metric-label mb-2">Последний платеж</div>
                                <div class="admin-row-title">${user.latest_transaction ? `${user.latest_transaction.plan || 'План'} • ${user.latest_transaction.amount}` : 'Нет платежей'}</div>
                                <div class="admin-row-meta">${user.latest_transaction ? formatDate(user.latest_transaction.paid_at) : 'История пока пустая'}</div>
                            </div>
                            <form id="admin-user-subscription-form" class="admin-stack">
                                <div class="admin-detail-card">
                                    <div class="admin-metric-label mb-2">Подписка</div>
                                    <div class="admin-detail-grid">
                                        <div>
                                            <label class="form-label" for="admin-user-plan">План</label>
                                            <select class="form-select" id="admin-user-plan" name="plan_id">
                                                <option value="">Без активного плана</option>
                                                ${(user.subscription?.plans || []).map(function (plan) {
                                                    return `<option value="${plan.id}" ${user.subscription?.current_plan?.id === plan.id ? 'selected' : ''}>${plan.name} • ${plan.price}</option>`;
                                                }).join('')}
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label" for="admin-user-plan-ends-at">Действует до</label>
                                            <input type="datetime-local" class="form-control" id="admin-user-plan-ends-at" name="ends_at" value="${user.subscription?.current_plan?.ends_at ? new Date(user.subscription.current_plan.ends_at).toISOString().slice(0, 16) : ''}">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap mt-3">
                                        <button class="btn btn-outline-primary" type="submit">Обновить подписку</button>
                                        <button class="btn btn-outline-secondary" type="button" id="admin-user-plan-clear">Снять подписку</button>
                                    </div>
                                </div>
                            </form>
                        `}
                        <form id="admin-user-form" class="admin-stack" data-mode="${mode}">
                            <div>
                                <label class="form-label" for="admin-user-name">Имя</label>
                                <input class="form-control" id="admin-user-name" name="name" autocomplete="name" value="${values.name}" required>
                            </div>
                            <div>
                                <label class="form-label" for="admin-user-email">Email</label>
                                <input type="email" class="form-control" id="admin-user-email" name="email" autocomplete="username" value="${values.email}" required>
                            </div>
                            <div>
                                <label class="form-label" for="admin-user-phone">Телефон</label>
                                <input class="form-control" id="admin-user-phone" name="phone" autocomplete="tel" value="${values.phone}">
                            </div>
                            <div>
                                <label class="form-label" for="admin-user-password">${isCreate ? 'Пароль' : 'Новый пароль'}</label>
                                <input type="password" class="form-control" id="admin-user-password" name="password" autocomplete="new-password" ${isCreate ? 'required' : ''} minlength="8" placeholder="${isCreate ? 'Минимум 8 символов' : 'Оставьте пустым, если не меняете'}">
                            </div>
                            <div>
                                <label class="form-label" for="admin-user-detail-status">Статус</label>
                                <select class="form-select" id="admin-user-detail-status" name="status">
                                    <option value="active" ${values.status === 'active' ? 'selected' : ''}>Active</option>
                                    <option value="suspended" ${values.status === 'suspended' ? 'selected' : ''}>Suspended</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="admin-user-is-admin">Доступ в backoffice</label>
                                <select class="form-select" id="admin-user-is-admin" name="is_admin">
                                    <option value="0" ${!values.is_admin ? 'selected' : ''}>Нет</option>
                                    <option value="1" ${values.is_admin ? 'selected' : ''}>Да</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="admin-user-admin-role">Роль backoffice</label>
                                <select class="form-select" id="admin-user-admin-role" name="admin_role">
                                    <option value="" ${values.admin_role === '' ? 'selected' : ''}>Без роли</option>
                                    <option value="super_admin" ${values.admin_role === 'super_admin' ? 'selected' : ''}>Super Admin</option>
                                    <option value="support" ${values.admin_role === 'support' ? 'selected' : ''}>Support</option>
                                    <option value="finance" ${values.admin_role === 'finance' ? 'selected' : ''}>Finance</option>
                                    <option value="analyst" ${values.admin_role === 'analyst' ? 'selected' : ''}>Analyst</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="admin-user-notes">Заметки оператора</label>
                                <textarea class="form-control" rows="5" id="admin-user-notes" name="admin_notes" placeholder="Почему аккаунт был ограничен, что уже проверили, какие следующие шаги">${values.admin_notes}</textarea>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-primary align-self-start" type="submit">${isCreate ? 'Создать пользователя' : 'Сохранить'}</button>
                                ${isCreate ? '<button class="btn btn-outline-secondary" type="button" id="admin-user-cancel-create">Отмена</button>' : '<button class="btn btn-outline-danger" type="button" id="admin-user-delete">Удалить пользователя</button>'}
                            </div>
                        </form>
                        ${isCreate ? '' : `
                            <div>
                                <div class="admin-metric-label mb-2">Последние административные события</div>
                                <div class="admin-list">
                                    ${(user.audit || []).length ? user.audit.map(function (item) {
                                        return `<div class="admin-row"><div><div class="admin-row-title">${item.action}</div><div class="admin-row-meta">${formatDate(item.created_at)}</div></div></div>`;
                                    }).join('') : '<div class="admin-empty">Аудит ещё не накопился.</div>'}
                                </div>
                            </div>
                        `}
                    </div>
                `;

                document.getElementById('admin-user-form').addEventListener('submit', async function (event) {
                    event.preventDefault();
                    const isAdmin = document.getElementById('admin-user-is-admin').value === '1';
                    const payload = {
                        name: document.getElementById('admin-user-name').value,
                        email: document.getElementById('admin-user-email').value,
                        phone: document.getElementById('admin-user-phone').value,
                        password: document.getElementById('admin-user-password').value,
                        status: document.getElementById('admin-user-detail-status').value,
                        is_admin: isAdmin,
                        admin_role: isAdmin ? document.getElementById('admin-user-admin-role').value || null : null,
                        admin_notes: document.getElementById('admin-user-notes').value
                    };
                    const response = await fetch(isCreate ? '/api/v1/admin/users' : `/api/v1/admin/users/${user.id}`, {
                        method: isCreate ? 'POST' : 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (!response.ok) {
                        alert('Не удалось обновить пользователя.');
                        return;
                    }

                    createMode = false;
                    await loadUsers();
                    const result = await response.json();
                    await loadUser(result.data.id);
                });

                const subscriptionForm = document.getElementById('admin-user-subscription-form');
                if (subscriptionForm) {
                    subscriptionForm.addEventListener('submit', async function (event) {
                        event.preventDefault();
                        const payload = {
                            plan_id: document.getElementById('admin-user-plan').value || null,
                            ends_at: document.getElementById('admin-user-plan-ends-at').value || null
                        };

                        const response = await fetch(`/api/v1/admin/users/${user.id}/subscription`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify(payload)
                        });

                        if (!response.ok) {
                            alert('Не удалось обновить подписку.');
                            return;
                        }

                        await loadUsers();
                        await loadUser(user.id);
                    });

                    document.getElementById('admin-user-plan-clear').addEventListener('click', async function () {
                        const response = await fetch(`/api/v1/admin/users/${user.id}/subscription`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                plan_id: null,
                                ends_at: new Date().toISOString()
                            })
                        });

                        if (!response.ok) {
                            alert('Не удалось снять подписку.');
                            return;
                        }

                        await loadUsers();
                        await loadUser(user.id);
                    });
                }

                const cancelButton = document.getElementById('admin-user-cancel-create');
                if (cancelButton) {
                    cancelButton.addEventListener('click', function () {
                        createMode = false;
                        if (selectedUserId) {
                            loadUser(selectedUserId);
                        } else {
                            detailEl.innerHTML = '<div class="admin-empty">Выберите пользователя слева, чтобы посмотреть контекст и управлять статусом.</div>';
                        }
                    });
                }

                const deleteButton = document.getElementById('admin-user-delete');
                if (deleteButton) {
                    deleteButton.addEventListener('click', async function () {
                        if (!confirm(`Удалить пользователя ${user.name}? Это действие необратимо.`)) {
                            return;
                        }

                        const response = await fetch(`/api/v1/admin/users/${user.id}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) {
                            alert('Не удалось удалить пользователя.');
                            return;
                        }

                        createMode = false;
                        selectedUserId = null;
                        await loadUsers();
                    });
                }
            };

            const loadUsers = async () => {
                const query = new URLSearchParams({ search: searchInput.value, status: statusSelect.value });
                const response = await fetch(`/api/v1/admin/users?${query.toString()}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const payload = await response.json();
                users = Array.isArray(payload.data) ? payload.data : [];

                if (!selectedUserId && users[0]) selectedUserId = users[0].id;
                if (selectedUserId && !users.some(function (user) { return user.id === selectedUserId; })) {
                    selectedUserId = users[0] ? users[0].id : null;
                }

                renderList();

                if (createMode) {
                    renderForm(null, 'create');
                } else if (selectedUserId) {
                    await loadUser(selectedUserId, false);
                } else {
                    detailEl.innerHTML = '<div class="admin-empty">Выберите пользователя слева, чтобы посмотреть контекст и управлять статусом.</div>';
                }
            };

            const loadUser = async (userId, rerenderList = true) => {
                createMode = false;
                selectedUserId = userId;
                if (rerenderList) renderList();

                const response = await fetch(`/api/v1/admin/users/${userId}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!response.ok) {
                    detailEl.innerHTML = '<div class="admin-empty">Не удалось загрузить карточку пользователя.</div>';
                    return;
                }

                const payload = await response.json();
                renderForm(payload.data, 'edit');
            };

            searchInput.addEventListener('input', function () {
                window.clearTimeout(searchInput._timer);
                searchInput._timer = window.setTimeout(loadUsers, 250);
            });
            statusSelect.addEventListener('change', loadUsers);
            createButton.addEventListener('click', function () {
                createMode = true;
                selectedUserId = null;
                renderList();
                renderForm(null, 'create');
            });

            loadUsers();
        });
    </script>
@endsection
