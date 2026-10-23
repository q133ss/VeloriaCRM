@extends('layouts.app')

@section('title', 'Backoffice Prospects')

@section('content')
    @include('admin.partials.styles')

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-shell">
            <section class="admin-hero">
                <h1>Заготовки</h1>
                <p>Аккаунты, собранные из групп ВК для рассылки. Мастер забирает свой аккаунт по ссылке передачи. Если этого не случилось за {{ config('prospects.ttl_days') }} дней, аккаунт удаляется сам.</p>
            </section>

            @include('admin.partials.nav')

            <section class="admin-panel">
                <div class="admin-panel-body admin-stack">
                    <div class="admin-toolbar">
                        <input type="search" class="form-control" id="prospect-search" placeholder="Найти по названию или адресу группы">
                        <select class="form-select" id="prospect-status">
                            <option value="all">Все</option>
                            <option value="built">Собраны</option>
                            <option value="sent">Отправлены</option>
                            <option value="opened">Открыли ссылку, не зарегистрировались</option>
                            <option value="claimed">Забраны</option>
                            <option value="expired">Истекли</option>
                        </select>
                    </div>
                    <div id="prospect-list" class="admin-list"></div>
                </div>
            </section>

            <section class="admin-panel soft">
                <div class="admin-panel-body">
                    <details>
                        <summary class="fw-semibold">Загрузить zip или подключить VeloriaBomber</summary>
                        <div class="admin-stack mt-3">
                            <form id="prospect-import" class="d-flex flex-wrap gap-2 align-items-center">
                                <input type="file" accept=".zip" class="form-control" name="file" style="max-width: 360px" required>
                                <button type="submit" class="btn btn-primary">Собрать из zip</button>
                                <span id="prospect-import-state" class="admin-row-meta"></span>
                            </form>
                            <div class="admin-row-meta">В архиве лежат prospect.json и фото, на которые он ссылается. Формат тот же, что присылает Bomber.</div>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <button type="button" class="btn btn-outline-secondary" id="prospect-token-btn">Выдать токен для Bomber</button>
                                <span class="admin-row-meta">Старый токен перестанет работать. С новым можно только собирать заготовки.</span>
                            </div>
                            <input type="text" class="form-control d-none" id="prospect-token" readonly>
                        </div>
                    </details>
                </div>
            </section>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const listEl = document.getElementById('prospect-list');
            const searchEl = document.getElementById('prospect-search');
            const statusEl = document.getElementById('prospect-status');
            const headers = {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'};
            const statusLabels = {built: 'Собрана', sent: 'Отправлена', claimed: 'Забрана', expired: 'Истекла'};
            const sourceLabels = {ai: 'прайс прочитан из группы', items: 'услуги из раздела группы', default: 'типовые услуги, проверьте цены'};
            let items = [];

            const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
            const formatDate = (value) => value ? new Date(value).toLocaleDateString() : '';
            const formatTime = (value) => value ? new Date(value).toLocaleString([], {day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'}) : '';
            const kindLabels = {claim: 'ссылка кабинета', landing: 'страница записи'};

            // Where she is in the funnel: nothing opened, looked at the page, opened the hand-over link, took the account.
            const stage = (p) => {
                const v = p.visits || {};
                if (p.status === 'claimed') return ['Зарегистрировалась' + (p.email ? ': ' + esc(p.email) : ''), 'text-success'];
                if (p.status === 'expired') return ['Не забрала, истекла', 'text-muted'];
                if (v.claim && v.claim.views) return ['Открыла ссылку кабинета, но не зарегистрировалась', 'text-warning'];
                if (v.landing && v.landing.views) return ['Смотрела страницу записи, ссылку кабинета не открывала', 'text-warning'];
                return [p.status === 'sent' ? 'Отправлено, пока никто не открывал' : 'Ещё не отправлено', 'text-muted'];
            };

            const visitsLine = (p) => {
                const v = p.visits || {};
                const part = (label, d) => d && d.views
                    ? `${label}: открытий ${d.views}${d.visitors > 1 ? `, разных людей ${d.visitors}` : ''}, первый ${formatTime(d.first_at)}, последний ${formatTime(d.last_at)}`
                    : null;
                return [part('Ссылка кабинета', v.claim), part('Страница записи', v.landing),
                    v.bot_hits ? `превью и боты: ${v.bot_hits}` : null].filter(Boolean).join(' • ');
            };

            const copy = async (text, button) => {
                try {
                    await navigator.clipboard.writeText(text);
                    const label = button.textContent;
                    button.textContent = 'Скопировано';
                    setTimeout(() => { button.textContent = label; }, 1500);
                } catch (e) {
                    window.prompt('Скопируйте:', text);
                }
            };

            const row = (p) => {
                const pending = p.status === 'built' || p.status === 'sent';
                const meta = [
                    p.booking_service ? 'запись через ' + esc(p.booking_service) : null,
                    p.services_count ? p.services_count + ' услуг, ' + esc(sourceLabels[p.services_source] || '') : null,
                    'собрана ' + formatDate(p.created_at),
                    pending && p.expires_at ? 'удалится ' + formatDate(p.expires_at) : null,
                ].filter(Boolean).join(' • ');

                const [stageText, stageClass] = stage(p);
                const visitsText = visitsLine(p);

                return `
                    <article class="admin-row" data-id="${p.id}">
                        <div class="flex-grow-1">
                            <div class="admin-row-title">${esc(p.title || p.external_id)}</div>
                            <div class="admin-row-meta">${meta}</div>
                            <div class="admin-row-meta fw-semibold ${stageClass}">${stageText}</div>
                            ${visitsText ? `<div class="admin-row-meta">${visitsText}</div>` : ''}
                            ${visitsText ? `<details class="mt-1" data-visits><summary class="admin-row-meta">Все переходы</summary><div class="admin-row-meta mt-1" data-visits-body>Загружаю…</div></details>` : ''}
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                ${p.landing_url ? `<a class="btn btn-sm btn-primary" href="${esc(p.landing_url)}" target="_blank" rel="noopener">Страница</a>` : ''}
                                ${p.landing_url ? `<button type="button" class="btn btn-sm btn-outline-primary" data-copy="${esc(p.landing_url)}">Ссылка на страницу</button>` : ''}
                                ${p.claim_url ? `<button type="button" class="btn btn-sm btn-outline-primary" data-copy="${esc(p.claim_url)}">Ссылка передачи</button>` : ''}
                                ${p.url ? `<a class="btn btn-sm btn-outline-secondary" href="${esc(p.url)}" target="_blank" rel="noopener">Группа</a>` : ''}
                                ${p.status === 'built' ? `<button type="button" class="btn btn-sm btn-outline-secondary" data-status="sent">Отправила</button>` : ''}
                                ${pending || p.status === 'expired' ? `<button type="button" class="btn btn-sm btn-outline-danger" data-delete>Удалить</button>` : ''}
                            </div>
                            ${pending && p.login ? `
                                <details class="mt-2">
                                    <summary class="admin-row-meta">Логин и пароль (для правок в кабинете)</summary>
                                    <div class="admin-row-meta mt-1">${esc(p.login)} / ${esc(p.password)}</div>
                                </details>` : ''}
                        </div>
                        <span class="admin-chip align-self-start text-nowrap">${esc(statusLabels[p.status] || p.status)}</span>
                    </article>`;
            };

            const load = async () => {
                const query = new URLSearchParams({search: searchEl.value, status: statusEl.value});
                const response = await fetch(`/api/v1/admin/prospects?${query}`, {headers});

                if (!response.ok) {
                    listEl.innerHTML = '<div class="admin-empty">Не удалось загрузить заготовки.</div>';
                    return;
                }

                items = (await response.json()).data || [];
                listEl.innerHTML = items.length
                    ? items.map(row).join('')
                    : '<div class="admin-empty">Заготовок пока нет. Их собирает Bomber кнопкой «Собрать страницу», или загрузите zip ниже.</div>';
            };

            listEl.addEventListener('click', async (event) => {
                const button = event.target.closest('button');
                const article = event.target.closest('[data-id]');

                if (!button || !article) {
                    return;
                }

                if (button.dataset.copy) {
                    copy(button.dataset.copy, button);
                    return;
                }

                const id = article.dataset.id;

                if (button.dataset.status) {
                    await fetch(`/api/v1/admin/prospects/${id}`, {
                        method: 'PATCH',
                        headers: {...headers, 'Content-Type': 'application/json'},
                        body: JSON.stringify({status: button.dataset.status}),
                    });
                    load();
                    return;
                }

                if (button.hasAttribute('data-delete')) {
                    if (!confirm('Удалить заготовку вместе с аккаунтом и страницей?')) {
                        return;
                    }
                    const response = await fetch(`/api/v1/admin/prospects/${id}`, {method: 'DELETE', headers});
                    if (!response.ok) {
                        alert((await response.json().catch(() => ({}))).message || 'Не удалось удалить.');
                    }
                    load();
                }
            });

            listEl.addEventListener('toggle', async (event) => {
                const details = event.target;
                const article = details.closest('[data-id]');

                if (!details.matches('[data-visits]') || !details.open || !article) {
                    return;
                }

                const body = details.querySelector('[data-visits-body]');
                const response = await fetch(`/api/v1/admin/prospects/${article.dataset.id}/visits`, {headers});
                const rows = response.ok ? ((await response.json()).data || []) : null;

                body.innerHTML = rows === null ? 'Не удалось загрузить.' : rows.map((r) => {
                    const from = r.referer ? 'из ' + esc(r.referer.replace(/^https?:\/\//, '')) : 'без источника (прямая ссылка, мессенджер)';
                    const who = r.is_bot ? '<em>превью/бот</em>' : 'человек ' + esc(r.visitor);
                    return `<div>${formatTime(r.at)} • ${kindLabels[r.kind] || r.kind} • ${who} • ${from}${r.is_bot ? '' : ' • ' + esc((r.user_agent || '').slice(0, 60))}</div>`;
                }).join('') || 'Пока пусто.';
            }, true);

            document.getElementById('prospect-import').addEventListener('submit', async (event) => {
                event.preventDefault();
                const state = document.getElementById('prospect-import-state');
                state.textContent = 'Собираю, это до минуты…';
                const response = await fetch('/api/v1/admin/prospects/import', {method: 'POST', headers, body: new FormData(event.target)});
                const payload = await response.json().catch(() => ({}));
                state.textContent = response.ok ? 'Готово.' : (payload.message || 'Не получилось собрать.');
                if (response.ok) {
                    event.target.reset();
                    load();
                }
            });

            document.getElementById('prospect-token-btn').addEventListener('click', async () => {
                if (!confirm('Выдать новый токен? Старый перестанет работать.')) {
                    return;
                }
                const response = await fetch('/api/v1/admin/prospects/token', {method: 'POST', headers});
                const payload = await response.json().catch(() => ({}));
                const field = document.getElementById('prospect-token');
                field.value = response.ok ? payload.data.token : 'Не удалось выдать токен.';
                field.classList.remove('d-none');
                field.select();
            });

            searchEl.addEventListener('input', () => {
                clearTimeout(searchEl._timer);
                searchEl._timer = setTimeout(load, 250);
            });
            statusEl.addEventListener('change', load);

            load();
        });
    </script>
@endsection
