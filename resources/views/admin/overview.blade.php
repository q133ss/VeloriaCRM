@extends('layouts.app')

@section('title', 'Backoffice')

@section('content')
    @include('admin.partials.styles')

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-shell">
            <section class="admin-hero">
                <h1>Backoffice Veloria</h1>
                <p>Одна спокойная панель для ключевых операционных задач: состояние продукта, платежи, пользователи и очередь поддержки.</p>
            </section>

            @include('admin.partials.nav')

            <div class="admin-stack">
                <div id="overview-metrics" class="admin-grid metrics"></div>

                <div class="admin-two-column">
                    <section class="admin-panel soft">
                        <div class="admin-panel-body">
                            <h4 class="mb-1">Планы и выручка</h4>
                            <p class="text-muted mb-3">Только ключевые платформенные сигналы, без перегруза лишними графиками.</p>
                            <div id="overview-plans" class="admin-list"></div>
                        </div>
                    </section>

                    <section class="admin-panel">
                        <div class="admin-panel-body">
                            <h4 class="mb-1">Поддержка</h4>
                            <p class="text-muted mb-3">Что происходит в очереди прямо сейчас.</p>
                            <div id="overview-tickets" class="admin-list"></div>
                        </div>
                    </section>
                </div>

                <section class="admin-panel">
                    <div class="admin-panel-body">
                        <h4 class="mb-1">Новые аккаунты</h4>
                        <p class="text-muted mb-3">Последние пользователи, чтобы замечать новые регистрации и проблемные статусы.</p>
                        <div id="overview-users" class="admin-list"></div>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const metricsEl = document.getElementById('overview-metrics');
            const plansEl = document.getElementById('overview-plans');
            const ticketsEl = document.getElementById('overview-tickets');
            const usersEl = document.getElementById('overview-users');

            const formatMoney = (value) => new Intl.NumberFormat(document.documentElement.lang === 'ru' ? 'ru-RU' : 'en-US', {
                style: 'currency',
                currency: 'RUB',
                maximumFractionDigits: 0
            }).format(value || 0);

            const response = await fetch('/api/v1/admin/overview', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                metricsEl.innerHTML = '<div class="admin-empty">Не удалось загрузить обзор backoffice.</div>';
                return;
            }

            const payload = await response.json();
            const summary = payload.summary || {};

            metricsEl.innerHTML = [
                ['Всего пользователей', summary.total_users || 0],
                ['Новые за 7 дней', summary.new_users_7d || 0],
                ['Платящие', summary.paid_users || 0],
                ['Открытые тикеты', summary.open_tickets || 0],
                ['Приостановлены', summary.suspended_users || 0],
                ['Выручка 30 дней', formatMoney(summary.revenue_30d || 0)]
            ].map(function (item) {
                return `<section class="admin-panel"><div class="admin-panel-body"><div class="admin-metric-label">${item[0]}</div><div class="admin-metric-value">${item[1]}</div></div></section>`;
            }).join('');

            const plans = Array.isArray(payload.plans) ? payload.plans : [];
            plansEl.innerHTML = plans.length
                ? plans.map(function (plan) {
                    return `<div class="admin-row"><div><div class="admin-row-title">${plan.name}</div><div class="admin-row-meta">Активных пользователей на плане</div></div><span class="admin-chip">${plan.total}</span></div>`;
                }).join('')
                : '<div class="admin-empty">Пока нет оплаченных подписок.</div>';

            const tickets = payload.tickets || {};
            ticketsEl.innerHTML = [
                ['Ожидают', tickets.waiting || 0, 'warning'],
                ['Открыты', tickets.open || 0, 'warning'],
                ['Отвечены', tickets.responded || 0, 'success'],
                ['Закрыты', tickets.closed || 0, 'danger']
            ].map(function (item) {
                return `<div class="admin-row"><div class="admin-row-title">${item[0]}</div><span class="admin-chip ${item[2]}">${item[1]}</span></div>`;
            }).join('');

            const users = Array.isArray(payload.recent_users) ? payload.recent_users : [];
            usersEl.innerHTML = users.length
                ? users.map(function (user) {
                    return `<div class="admin-row"><div><div class="admin-row-title">${user.name || 'Без имени'}</div><div class="admin-row-meta">${user.email || 'Без email'}${user.admin_role_label ? ' • ' + user.admin_role_label : ''}</div></div><span class="admin-chip ${user.status === 'suspended' ? 'danger' : 'success'}">${user.status === 'suspended' ? 'Suspended' : 'Active'}</span></div>`;
                }).join('')
                : '<div class="admin-empty">Новых аккаунтов пока нет.</div>';
        });
    </script>
@endsection
