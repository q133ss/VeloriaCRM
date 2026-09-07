@extends('layouts.app')

@section('title', 'Полезное')

@section('content')
    <style>
        .useful-page {
            --useful-shadow: 0 20px 48px -34px rgba(37, 26, 84, 0.45);
            --useful-line: color-mix(in srgb, var(--bs-border-color) 70%, transparent);
        }

        .useful-hero {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            border: 1px solid rgba(var(--bs-primary-rgb, 255, 0, 252), 0.14);
            border-radius: 1.5rem;
            padding: 1.35rem 1.5rem;
            background: linear-gradient(135deg, rgba(var(--bs-primary-rgb, 255, 0, 252), 0.07), rgba(var(--bs-primary-rgb, 255, 0, 252), 0.015) 60%, rgba(var(--bs-info-rgb, 0, 207, 232), 0.04));
            box-shadow: var(--useful-shadow);
        }

        .useful-hero__title {
            margin: 0 0 0.2rem;
            font-size: clamp(1.35rem, 2vw, 1.75rem);
            letter-spacing: -0.02em;
        }

        .useful-hero__lead {
            color: var(--bs-secondary-color);
        }

        .useful-surface {
            border: none;
            border-radius: 1.35rem;
            box-shadow: var(--useful-shadow);
            background: color-mix(in srgb, var(--bs-card-bg) 94%, transparent);
        }

        .useful-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
        }

        .useful-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
        }

        .useful-chip {
            border: 0;
            border-radius: 999px;
            padding: 0.45rem 0.9rem;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.07);
            color: var(--bs-secondary-color);
            font-weight: 600;
            font-size: 0.88rem;
            white-space: nowrap;
        }

        .useful-chip.is-active {
            background: var(--bs-card-bg, #fff);
            color: var(--bs-primary);
            box-shadow: 0 8px 20px -14px rgba(0, 0, 0, 0.65);
        }

        .useful-toolbar__right {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex: 1 1 260px;
            justify-content: flex-end;
        }

        /* Field and switch to one height: the theme gives them three. */
        .useful-toolbar__right .useful-search {
            max-width: 280px;
            height: 2.625rem;
            padding-top: 0;
            padding-bottom: 0;
        }

        .useful-featured h2 {
            line-height: 1.3;
        }

        .useful-featured {
            border: 1px solid rgba(var(--bs-primary-rgb, 255, 0, 252), 0.18);
            border-radius: 1.35rem;
            padding: 1.35rem 1.5rem;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.04);
        }

        .useful-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            color: var(--bs-secondary-color);
            font-size: 0.85rem;
        }

        .useful-meta__dot::before {
            content: '·';
            margin-right: 0.5rem;
        }

        .useful-match {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            background: rgba(var(--bs-success-rgb, 40, 199, 111), 0.12);
            color: var(--bs-success-text-emphasis, var(--bs-success));
            font-weight: 600;
            font-size: 0.78rem;
        }

        .useful-read-flag {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            color: var(--bs-success-text-emphasis, var(--bs-success));
            font-weight: 600;
            font-size: 0.8rem;
        }

        .useful-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1rem;
            padding: 0 1.25rem 1.25rem;
        }

        .useful-card {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            border: 1px solid var(--useful-line);
            border-radius: 1.1rem;
            padding: 1.1rem;
            background: var(--bs-card-bg);
            cursor: pointer;
            transition: border-color 0.15s ease;
        }

        .useful-card:hover {
            border-color: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.35);
        }

        /* Read posts step back without disappearing: the master may want to
           open one again, and a list that hides its own history is confusing. */
        .useful-card.is-read .useful-card__title,
        .useful-card.is-read .useful-card__summary {
            opacity: 0.68;
        }

        .useful-card__title {
            margin: 0;
            font-size: 1.02rem;
            line-height: 1.35;
            font-weight: 600;
        }

        .useful-card__summary {
            margin: 0;
            color: var(--bs-secondary-color);
            font-size: 0.9rem;
        }

        .useful-action {
            display: flex;
            align-items: baseline;
            gap: 0.35rem;
            padding: 0.55rem 0.7rem;
            border-radius: 0.75rem;
            background: rgba(var(--bs-primary-rgb, 255, 0, 252), 0.06);
            font-size: 0.88rem;
        }

        .useful-action__label {
            color: var(--bs-secondary-color);
            white-space: nowrap;
        }

        .useful-action__value {
            font-weight: 600;
        }

        .useful-card__foot {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem;
            margin-top: auto;
            padding-top: 0.35rem;
        }

        .useful-empty {
            padding: 3rem 1.25rem;
            text-align: center;
            color: var(--bs-secondary-color);
        }

        .useful-blocks {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .useful-block__title {
            margin-bottom: 0.35rem;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--bs-secondary-color);
        }

        .useful-block ul {
            margin: 0;
            padding-left: 1.1rem;
        }

        .useful-block li + li {
            margin-top: 0.35rem;
        }

        @media (max-width: 767.98px) {
            .useful-hero {
                flex-direction: column;
                align-items: stretch;
            }

            .useful-toolbar,
            .useful-toolbar__right {
                justify-content: flex-start;
            }

            .useful-toolbar__right .useful-search {
                max-width: none;
            }

            .useful-chips {
                width: 100%;
            }

            /* The switch label wrapped into two lines beside the field. */
            .useful-toolbar__right {
                flex-wrap: wrap;
            }

            .useful-toolbar__right .form-check {
                width: 100%;
            }

            .useful-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="useful-page d-flex flex-column gap-4">
        <section class="useful-hero">
            <div>
                <h1 class="useful-hero__title" id="useful-title">Полезное</h1>
                <p class="useful-hero__lead mb-0" id="useful-lead">Загрузка...</p>
            </div>
            <div class="d-flex flex-column align-items-sm-end gap-1">
                <button type="button" class="btn btn-outline-primary" id="useful-digest-btn">
                    <i class="ri ri-mail-send-line me-1"></i>
                    Подборка раз в неделю
                </button>
                {{-- The plan is named before the press, not after it. --}}
                <small class="text-muted" id="useful-digest-note"></small>
            </div>
        </section>

        <div id="useful-alerts"></div>

        <section class="useful-featured" id="useful-featured" hidden></section>

        <div class="card useful-surface">
            <div class="useful-toolbar">
                <div class="useful-chips" id="useful-chips"></div>
                <div class="useful-toolbar__right">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="useful-unread-only" />
                        <label class="form-check-label" for="useful-unread-only">Не прочитано</label>
                    </div>
                    <input
                        type="search"
                        class="form-control useful-search"
                        id="useful-search"
                        placeholder="Найти материал"
                        aria-label="Найти материал"
                        autocomplete="off"
                    />
                </div>
            </div>

            <div class="useful-grid" id="useful-list">
                <div class="useful-empty">Загрузка...</div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="usefulContentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="useful-meta mb-1" id="useful-modal-meta"></div>
                        <h5 class="modal-title" id="useful-modal-title">Материал</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted" id="useful-modal-summary"></p>
                    <div class="useful-blocks" id="useful-modal-body"></div>
                    <div class="useful-action mt-3" id="useful-modal-action" hidden>
                        <span class="useful-action__label">Что сделать:</span>
                        <span class="useful-action__value" id="useful-modal-action-value"></span>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <a href="#" target="_blank" rel="noopener" class="btn btn-text-secondary d-none" id="useful-modal-link">Источник</a>
                    <div class="d-flex gap-2 ms-auto">
                        <button type="button" class="btn btn-outline-primary" id="useful-modal-read"></button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="usefulDigestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="small text-muted mb-1">Раз в неделю, по понедельникам</div>
                        <h5 class="modal-title">Подборка на почту и в Telegram</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-4">Одно письмо в неделю: важное обновление и пара материалов, которые подходят вашим услугам.</p>

                    <div id="useful-pref-lock" class="useful-empty d-none mb-3"></div>

                    <div id="useful-pref-form">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="useful-pref-enabled">
                            <label class="form-check-label fw-semibold" for="useful-pref-enabled">Получать подборку</label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="useful-pref-channel">Куда отправлять</label>
                            <select class="form-select" id="useful-pref-channel">
                                <option value="platform">Только в CRM</option>
                                <option value="telegram">Только в Telegram</option>
                                <option value="both">И в CRM, и в Telegram</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="useful-pref-preferences">Что вам особенно важно</label>
                            <textarea class="form-control" id="useful-pref-preferences" rows="3" placeholder="Например: налоги, идеи для постов, возврат клиентов."></textarea>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-primary" id="useful-pref-save">Сохранить</button>
                            <button type="button" class="btn btn-outline-primary" id="useful-pref-test">Прислать тест</button>
                        </div>

                        <div class="small text-muted mt-3" id="useful-pref-status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function getCookie(name) {
                var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
                return match ? decodeURIComponent(match[2]) : null;
            }

            function authHeaders(extra) {
                var token = getCookie('token');
                var headers = Object.assign({ 'Accept': 'application/json', 'Content-Type': 'application/json' }, extra || {});
                if (token) headers['Authorization'] = 'Bearer ' + token;
                return headers;
            }

            function escapeHtml(value) {
                return String(value === null || value === undefined ? '' : value).replace(/[&<>"']/g, function (character) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
                });
            }

            function plural(count, one, few, many) {
                var mod100 = count % 100;
                if (mod100 >= 11 && mod100 <= 14) return many;
                var mod10 = count % 10;
                if (mod10 === 1) return one;
                if (mod10 >= 2 && mod10 <= 4) return few;
                return many;
            }

            var state = {
                search: '',
                category: 'all',
                unreadOnly: false,
                payload: { meta: {}, preferences: {}, counts: {}, featured_post: null, filters: [], posts: [] },
                byId: {},
                openPostId: null,
            };

            var alertsEl = document.getElementById('useful-alerts');
            var leadEl = document.getElementById('useful-lead');
            var featuredEl = document.getElementById('useful-featured');
            var listEl = document.getElementById('useful-list');
            var chipsEl = document.getElementById('useful-chips');
            var searchEl = document.getElementById('useful-search');
            var unreadEl = document.getElementById('useful-unread-only');
            var digestBtn = document.getElementById('useful-digest-btn');
            var digestNote = document.getElementById('useful-digest-note');

            var contentModalEl = document.getElementById('usefulContentModal');
            var contentModal = contentModalEl ? new bootstrap.Modal(contentModalEl) : null;
            var digestModalEl = document.getElementById('usefulDigestModal');
            var digestModal = digestModalEl ? new bootstrap.Modal(digestModalEl) : null;

            var prefLock = document.getElementById('useful-pref-lock');
            var prefForm = document.getElementById('useful-pref-form');
            var prefEnabled = document.getElementById('useful-pref-enabled');
            var prefChannel = document.getElementById('useful-pref-channel');
            var prefPreferences = document.getElementById('useful-pref-preferences');
            var prefStatus = document.getElementById('useful-pref-status');

            function showAlert(type, message) {
                if (!message) return;
                var el = document.createElement('div');
                el.className = 'alert alert-' + type + ' alert-dismissible fade show';
                el.innerHTML = '<div>' + escapeHtml(message) + '</div><button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                alertsEl.appendChild(el);
                setTimeout(function () {
                    el.classList.remove('show');
                    el.addEventListener('transitionend', function () { el.remove(); });
                }, 5000);
            }

            /* ---------- rendering ---------- */

            function metaLine(post) {
                var parts = [
                    '<span>' + escapeHtml(post.topic || 'Материал') + '</span>',
                    '<span class="useful-meta__dot">' + post.reading_minutes + ' ' + plural(post.reading_minutes, 'минута', 'минуты', 'минут') + '</span>',
                ];

                if (post.matches_specialty) {
                    parts.push('<span class="useful-match"><i class="ri ri-check-line"></i>подходит вашим услугам</span>');
                }

                if (post.is_read) {
                    parts.push('<span class="useful-read-flag"><i class="ri ri-check-double-line"></i>прочитано</span>');
                }

                return '<div class="useful-meta">' + parts.join('') + '</div>';
            }

            function actionLine(post) {
                var label = post.action && post.action.label;

                if (!label) {
                    return '';
                }

                // «Что с этим делать» was stored on every post and shown on none:
                // the old page only rendered it when the action carried a url.
                var value = post.action.url
                    ? '<a class="useful-action__value" href="' + escapeHtml(post.action.url) + '" target="_blank" rel="noopener">' + escapeHtml(label) + '</a>'
                    : '<span class="useful-action__value">' + escapeHtml(label) + '</span>';

                return '<div class="useful-action"><span class="useful-action__label">Что сделать:</span>' + value + '</div>';
            }

            function readButton(post, extraClass) {
                return '<button type="button" class="btn btn-sm ' + (post.is_read ? 'btn-text-secondary' : 'btn-outline-primary') + ' ' + (extraClass || '') + '" data-useful-read="' + post.id + '">' +
                    (post.is_read ? 'Снять отметку' : 'Прочитано') +
                    '</button>';
            }

            function renderLead() {
                var counts = state.payload.counts || {};
                var total = counts.total || 0;

                if (!total) {
                    leadEl.textContent = 'Материалов пока нет — они появятся здесь и в подборке.';
                    return;
                }

                var parts = [total + ' ' + plural(total, 'материал', 'материала', 'материалов')];

                if (counts.unread) {
                    parts.push(counts.unread + ' не ' + plural(counts.unread, 'прочитан', 'прочитано', 'прочитано'));
                }

                var specialty = (state.payload.meta || {}).specialty;
                if (specialty && specialty.label) {
                    parts.push('подобрано под «' + specialty.label + '»');
                }

                leadEl.textContent = parts.join(' · ');
            }

            function renderDigestNote() {
                var prefs = state.payload.preferences || {};

                if (!prefs.available) {
                    digestNote.textContent = 'Доступно на тарифах Pro и Elite';
                    return;
                }

                digestNote.textContent = prefs.enabled ? 'Включена, по понедельникам' : 'Пока выключена';
            }

            function renderFeatured() {
                var post = state.payload.featured_post;

                if (!post) {
                    featuredEl.hidden = true;
                    return;
                }

                featuredEl.hidden = false;
                featuredEl.innerHTML =
                    '<div class="text-muted small mb-2">Читать на этой неделе</div>' +
                    metaLine(post) +
                    '<h2 class="h4 mt-2 mb-2">' + escapeHtml(post.title) + '</h2>' +
                    '<p class="text-muted">' + escapeHtml(post.summary || '') + '</p>' +
                    actionLine(post) +
                    '<div class="d-flex flex-wrap gap-2 mt-3">' +
                        '<button type="button" class="btn btn-primary btn-sm" data-useful-open="' + post.id + '">Открыть</button>' +
                        readButton(post) +
                    '</div>';
            }

            function renderChips() {
                var filters = state.payload.filters || [];

                chipsEl.innerHTML = filters.map(function (filter) {
                    var active = filter.key === state.category ? ' is-active' : '';
                    return '<button type="button" class="useful-chip' + active + '" data-useful-filter="' + escapeHtml(filter.key) + '">' +
                        escapeHtml(filter.label) + '</button>';
                }).join('');
            }

            function renderList() {
                var posts = state.payload.posts || [];

                if (!posts.length) {
                    var box = document.createElement('div');
                    box.className = 'useful-empty';

                    if (state.search) {
                        box.textContent = 'По запросу «' + state.search + '» ничего не нашли.';
                    } else if (state.unreadOnly) {
                        box.textContent = 'Вы прочитали всё, что здесь есть.';
                    } else if (state.category !== 'all') {
                        box.textContent = 'В этой теме пока пусто.';
                    } else if (state.payload.featured_post) {
                        box.textContent = 'Это пока единственный материал.';
                    } else {
                        box.textContent = 'Материалов пока нет — они появятся здесь и в подборке.';
                    }

                    listEl.innerHTML = '';
                    listEl.appendChild(box);
                    return;
                }

                listEl.innerHTML = posts.map(function (post) {
                    return '<article class="useful-card' + (post.is_read ? ' is-read' : '') + '" data-useful-open="' + post.id + '">' +
                        metaLine(post) +
                        '<h3 class="useful-card__title">' + escapeHtml(post.title) + '</h3>' +
                        '<p class="useful-card__summary">' + escapeHtml(post.summary || '') + '</p>' +
                        actionLine(post) +
                        '<div class="useful-card__foot">' +
                            '<button type="button" class="btn btn-sm btn-primary" data-useful-open="' + post.id + '">Открыть</button>' +
                            readButton(post) +
                        '</div>' +
                    '</article>';
                }).join('');
            }

            function indexPosts() {
                state.byId = {};
                (state.payload.posts || []).forEach(function (post) { state.byId[post.id] = post; });
                if (state.payload.featured_post) {
                    state.byId[state.payload.featured_post.id] = state.payload.featured_post;
                }
            }

            function render() {
                indexPosts();
                renderLead();
                renderDigestNote();
                renderFeatured();
                renderChips();
                renderList();
                renderPreferences();
            }

            /* ---------- loading ---------- */

            function load() {
                var params = new URLSearchParams();
                if (state.search) params.append('search', state.search);
                if (state.category && state.category !== 'all') params.append('category', state.category);
                if (state.unreadOnly) params.append('unread', '1');

                return fetch('/api/v1/useful/overview?' + params.toString(), {
                    headers: authHeaders(),
                    credentials: 'include',
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('failed');
                        return response.json();
                    })
                    .then(function (payload) {
                        state.payload = payload || state.payload;
                        render();
                    })
                    .catch(function (error) {
                        console.error(error);
                        listEl.innerHTML = '<div class="useful-empty text-danger">Не удалось загрузить материалы.</div>';
                    });
            }

            /* ---------- reading ---------- */

            function openPost(id) {
                var post = state.byId[id];
                if (!post || !contentModal) return;

                state.openPostId = post.id;

                document.getElementById('useful-modal-meta').innerHTML = metaLine(post).replace('<div class="useful-meta">', '').replace(/<\/div>$/, '');
                document.getElementById('useful-modal-title').textContent = post.title || '';
                document.getElementById('useful-modal-summary').textContent = post.summary || '';

                var blocks = post.content_blocks || [];
                document.getElementById('useful-modal-body').innerHTML = blocks.length
                    ? blocks.map(function (block) {
                        return '<div class="useful-block">' +
                            (block.title ? '<div class="useful-block__title">' + escapeHtml(block.title) + '</div>' : '') +
                            '<ul>' + block.items.map(function (item) { return '<li>' + escapeHtml(item) + '</li>'; }).join('') + '</ul>' +
                            '</div>';
                    }).join('')
                    : '<p class="text-muted mb-0">Материал скоро появится.</p>';

                var actionBox = document.getElementById('useful-modal-action');
                if (post.action && post.action.label) {
                    actionBox.hidden = false;
                    document.getElementById('useful-modal-action-value').textContent = post.action.label;
                } else {
                    actionBox.hidden = true;
                }

                var linkEl = document.getElementById('useful-modal-link');
                var link = post.source_url || (post.action && post.action.url) || null;
                linkEl.href = link || '#';
                linkEl.classList.toggle('d-none', !link);

                syncModalReadButton(post);
                contentModal.show();

                // Opening it is the answer to «читала ли я это» — no second press.
                if (!post.is_read) {
                    setRead(post.id, true, { quiet: true });
                }
            }

            function syncModalReadButton(post) {
                var button = document.getElementById('useful-modal-read');
                button.textContent = post.is_read ? 'Снять отметку' : 'Отметить прочитанным';
                button.dataset.usefulRead = String(post.id);
            }

            function setRead(id, read, options) {
                var quiet = options && options.quiet;

                return fetch('/api/v1/useful/posts/' + id + '/read', {
                    method: 'POST',
                    headers: authHeaders(),
                    credentials: 'include',
                    body: JSON.stringify({ read: read }),
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('failed');
                        return load();
                    })
                    .then(function () {
                        var post = state.byId[id];
                        if (post && state.openPostId === id) {
                            syncModalReadButton(post);
                        }
                    })
                    .catch(function (error) {
                        console.error(error);
                        if (!quiet) showAlert('danger', 'Не удалось сохранить отметку.');
                    });
            }

            /* ---------- weekly digest ---------- */

            function renderPreferences() {
                var prefs = state.payload.preferences || {};

                if (!prefs.available) {
                    prefLock.classList.remove('d-none');
                    prefLock.innerHTML = 'Подборка приходит на тарифах Pro и Elite. <a href="' + escapeHtml(prefs.upgrade_url || '/subscription') + '">Открыть тарифы</a>';
                    prefForm.classList.add('d-none');
                    return;
                }

                prefLock.classList.add('d-none');
                prefForm.classList.remove('d-none');
                prefEnabled.checked = Boolean(prefs.enabled);
                prefChannel.value = prefs.channel || 'platform';
                prefPreferences.value = prefs.preferences || '';
            }

            function savePreferences() {
                prefStatus.textContent = 'Сохраняем...';

                return fetch('/api/v1/useful/preferences', {
                    method: 'PATCH',
                    headers: authHeaders(),
                    credentials: 'include',
                    body: JSON.stringify({
                        enabled: prefEnabled.checked,
                        channel: prefChannel.value,
                        preferences: prefPreferences.value,
                    }),
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('failed');
                        return response.json();
                    })
                    .then(function (payload) {
                        state.payload.preferences = payload.data || state.payload.preferences;
                        renderDigestNote();
                        prefStatus.textContent = 'Сохранено.';
                    })
                    .catch(function (error) {
                        console.error(error);
                        prefStatus.textContent = 'Не удалось сохранить.';
                    });
            }

            /* ---------- wiring ---------- */

            document.addEventListener('click', function (event) {
                var openTrigger = event.target.closest('[data-useful-open]');
                var readTrigger = event.target.closest('[data-useful-read]');
                var filterTrigger = event.target.closest('[data-useful-filter]');

                if (readTrigger) {
                    event.stopPropagation();
                    var id = Number(readTrigger.dataset.usefulRead);
                    var post = state.byId[id];
                    setRead(id, !(post && post.is_read));
                    return;
                }

                if (openTrigger) {
                    openPost(Number(openTrigger.dataset.usefulOpen));
                    return;
                }

                if (filterTrigger) {
                    state.category = filterTrigger.dataset.usefulFilter;
                    load();
                }
            });

            // The filter applies itself; there was never an Apply button here
            // and there should not be one.
            var searchTimer = null;
            searchEl.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    var value = searchEl.value.trim();
                    if (value === state.search) return;
                    state.search = value;
                    load();
                }, 400);
            });

            unreadEl.addEventListener('change', function () {
                state.unreadOnly = unreadEl.checked;
                load();
            });

            digestBtn.addEventListener('click', function () {
                if (digestModal) digestModal.show();
            });

            document.getElementById('useful-pref-save').addEventListener('click', savePreferences);

            document.getElementById('useful-pref-test').addEventListener('click', function () {
                prefStatus.textContent = 'Отправляем...';

                fetch('/api/v1/useful/test-digest', {
                    method: 'POST',
                    headers: authHeaders(),
                    credentials: 'include',
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('failed');
                        prefStatus.textContent = 'Тестовая подборка отправлена.';
                    })
                    .catch(function () {
                        prefStatus.textContent = 'Не удалось отправить тест.';
                    });
            });

            load();
        });
    </script>
@endsection
