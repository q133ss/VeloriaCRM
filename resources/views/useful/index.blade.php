@extends('layouts.app')

@section('title', 'Полезное')

@section('content')
    <style>
        .useful-shell {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .useful-hero,
        .useful-featured,
        .useful-article-card,
        .useful-empty {
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
            border-radius: 1.25rem;
        }

        .useful-hero {
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.14), transparent 32%),
                linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.05), rgba(var(--bs-body-bg-rgb), 0.02));
        }

        .useful-hero-copy {
            max-width: 760px;
        }

        .useful-hero-actions {
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
        }

        .useful-featured {
            background: rgba(var(--bs-primary-rgb), 0.04);
            padding: 1.5rem;
        }

        .useful-featured-meta,
        .useful-article-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 0.75rem;
            color: var(--bs-secondary-color);
            font-size: 0.875rem;
        }

        .useful-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .useful-filter {
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.12);
            border-radius: 999px;
            padding: 0.65rem 1rem;
            background: transparent;
            color: inherit;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .useful-filter:hover,
        .useful-filter.is-active {
            border-color: rgba(var(--bs-primary-rgb), 0.35);
            background: rgba(var(--bs-primary-rgb), 0.08);
            color: rgb(var(--bs-primary-rgb));
        }

        .useful-article-card {
            padding: 1.25rem;
            height: 100%;
            background: rgba(var(--bs-body-bg-rgb), 0.55);
        }

        .useful-empty {
            padding: 2rem;
            text-align: center;
            color: var(--bs-secondary-color);
            border-style: dashed;
        }

        html[data-bs-theme="dark"] .useful-featured,
        html[data-bs-theme="dark"] .useful-article-card,
        html[data-bs-theme="dark"] .useful-filter.is-active,
        html[data-bs-theme="dark"] .useful-filter:hover {
            background: rgba(var(--bs-primary-rgb), 0.1);
        }

        @media (max-width: 991.98px) {
            .useful-hero-actions {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="useful-shell">
            <section class="card shadow-sm useful-hero">
                <div class="card-body p-4 p-lg-5">
                    <div class="row g-4 align-items-start">
                        <div class="col-12 col-lg-8">
                            <div class="useful-hero-copy">
                                <span class="badge bg-label-primary mb-3">Полезное</span>
                                <h2 class="mb-2" id="useful-title">Полезное</h2>
                                <p class="text-muted mb-3" id="useful-subtitle">Короткие статьи, идеи и важные изменения для работы.</p>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge bg-label-secondary" id="useful-specialty-badge">Подбираем фокус...</span>
                                    <span class="small text-muted" id="useful-specialty-hint"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="useful-hero-actions">
                                <button type="button" class="btn btn-outline-primary" id="useful-open-digest-settings">
                                    Получать подборку раз в неделю
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
                    <div>
                        <p class="small text-uppercase text-muted mb-1" id="useful-digest-badge">Главное на этой неделе</p>
                        <h4 class="mb-1">Главная статья недели</h4>
                        <p class="text-muted mb-0" id="useful-digest-summary">Подбираем одну важную публикацию без перегруза.</p>
                    </div>
                    <div class="small text-muted" id="useful-digest-week"></div>
                </div>
                <div id="useful-featured"></div>
            </section>

            <section>
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
                    <div>
                        <h4 class="mb-1">Статьи</h4>
                        <p class="text-muted mb-0">Открывайте только то, что полезно вам прямо сейчас.</p>
                    </div>
                </div>
                <div class="useful-filters mb-4" id="useful-filters"></div>
                <div class="row g-4" id="useful-posts"></div>
                <div class="useful-empty d-none mt-4" id="useful-posts-empty">По этому фильтру пока нет статей.</div>
            </section>
        </div>
    </div>

    <div class="modal fade" id="usefulContentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="small text-muted mb-1" id="useful-modal-type"></div>
                        <h5 class="modal-title" id="useful-modal-title"></h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <div id="useful-modal-body" class="d-flex flex-column gap-3"></div>
                </div>
                <div class="modal-footer justify-content-between">
                    <a href="#" target="_blank" rel="noopener" class="btn btn-outline-primary d-none" id="useful-modal-link">Открыть источник</a>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="usefulDigestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="small text-muted mb-1">Weekly-подборка</div>
                        <h5 class="modal-title">Получать раз в неделю</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-4">Один спокойный дайджест с важными обновлениями и полезными статьями внутри CRM или в Telegram.</p>

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
                            <textarea class="form-control" id="useful-pref-preferences" rows="4" placeholder="Например: налоги, бизнес, идеи для постов, возврат клиентов."></textarea>
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
                var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                return match ? decodeURIComponent(match[1]) : null;
            }

            function buildHeaders(extra) {
                var headers = Object.assign({ Accept: 'application/json' }, extra || {});
                var token = getCookie('token');
                if (token) {
                    headers['Authorization'] = 'Bearer ' + token;
                }
                return headers;
            }

            function escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function formatDate(value) {
                if (!value) {
                    return '';
                }

                try {
                    return new Date(value).toLocaleDateString(document.documentElement.lang || 'ru-RU', {
                        day: 'numeric',
                        month: 'long'
                    });
                } catch (error) {
                    return value;
                }
            }

            function setText(id, value) {
                var el = document.getElementById(id);
                if (el) {
                    el.textContent = value || '';
                }
            }

            function createContentBlocks(content) {
                if (Array.isArray(content)) {
                    return content.map(function (item) {
                        return '<div class="p-3 rounded-3 bg-body-tertiary">' + escapeHtml(item) + '</div>';
                    }).join('');
                }

                if (content && typeof content === 'object') {
                    return Object.keys(content).map(function (key) {
                        var value = content[key];
                        var renderedValue = Array.isArray(value) ? value.map(escapeHtml).join('<br>') : escapeHtml(value);
                        return '<div><div class="small text-muted text-uppercase mb-1">' + escapeHtml(key) + '</div><div class="p-3 rounded-3 bg-body-tertiary">' + renderedValue + '</div></div>';
                    }).join('');
                }

                return '<div class="p-3 rounded-3 bg-body-tertiary">' + escapeHtml(content || 'Материал скоро появится.') + '</div>';
            }

            var state = {
                activeFilter: 'all',
                payload: {
                    meta: {},
                    digest: {},
                    preferences: {},
                    featured_post: null,
                    filters: [],
                    posts: []
                }
            };

            var contentModalEl = document.getElementById('usefulContentModal');
            var contentModal = contentModalEl ? new bootstrap.Modal(contentModalEl) : null;
            var digestModalEl = document.getElementById('usefulDigestModal');
            var digestModal = digestModalEl ? new bootstrap.Modal(digestModalEl) : null;

            function openContentModal(post) {
                if (!contentModal || !post) {
                    return;
                }

                setText('useful-modal-type', post.topic || 'Статья');
                setText('useful-modal-title', post.title || '');
                document.getElementById('useful-modal-body').innerHTML = createContentBlocks(post.content);

                var linkEl = document.getElementById('useful-modal-link');
                var link = post.source_url || (post.action && post.action.url) || null;
                if (link) {
                    linkEl.href = link;
                    linkEl.classList.remove('d-none');
                } else {
                    linkEl.href = '#';
                    linkEl.classList.add('d-none');
                }

                contentModal.show();
            }

            function renderHero() {
                var meta = state.payload.meta || {};
                var digest = state.payload.digest || {};

                setText('useful-title', meta.title || 'Полезное');
                setText('useful-subtitle', meta.subtitle || '');
                setText(
                    'useful-specialty-badge',
                    meta.specialty && meta.specialty.label ? meta.specialty.label : 'Подборка по вашему профилю'
                );
                setText('useful-specialty-hint', meta.specialty ? meta.specialty.hint : '');
                setText('useful-digest-badge', digest.badge || 'Главное на этой неделе');
                setText('useful-digest-summary', digest.summary || 'Подбираем одну важную публикацию без перегруза.');
                setText('useful-digest-week', digest.week_label || '');
            }

            function renderFeatured() {
                var root = document.getElementById('useful-featured');
                var post = state.payload.featured_post;

                if (!root) {
                    return;
                }

                if (!post) {
                    root.innerHTML = '<div class="useful-empty">Пока нет главной статьи недели.</div>';
                    return;
                }

                var sourceButton = post.source_url
                    ? '<a class="btn btn-outline-secondary" href="' + escapeHtml(post.source_url) + '" target="_blank" rel="noopener">Источник</a>'
                    : '';

                root.innerHTML =
                    '<div class="useful-featured">' +
                        '<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">' +
                            '<div>' +
                                '<span class="badge bg-label-primary mb-2">Главное</span>' +
                                '<h3 class="mb-2">' + escapeHtml(post.title) + '</h3>' +
                            '</div>' +
                            '<span class="badge bg-label-secondary">' + escapeHtml(post.topic || 'Полезное') + '</span>' +
                        '</div>' +
                        '<p class="text-muted mb-3">' + escapeHtml(post.summary || '') + '</p>' +
                        '<div class="useful-featured-meta mb-4">' +
                            (post.reading_time_minutes ? '<span>' + escapeHtml(post.reading_time_minutes) + ' мин чтения</span>' : '') +
                            (post.published_at ? '<span>' + escapeHtml(formatDate(post.published_at)) + '</span>' : '') +
                        '</div>' +
                        '<div class="d-flex flex-wrap gap-2">' +
                            '<button type="button" class="btn btn-primary" data-useful-open="' + escapeHtml(String(post.id)) + '">Открыть</button>' +
                            sourceButton +
                        '</div>' +
                    '</div>';
            }

            function renderFilters() {
                var root = document.getElementById('useful-filters');
                var filters = state.payload.filters || [];

                if (!root) {
                    return;
                }

                root.innerHTML = filters.map(function (filter) {
                    var isActive = filter.key === state.activeFilter;
                    return '<button type="button" class="useful-filter' + (isActive ? ' is-active' : '') + '" data-useful-filter="' + escapeHtml(filter.key) + '">' + escapeHtml(filter.label) + '</button>';
                }).join('');
            }

            function visiblePosts() {
                var posts = state.payload.posts || [];
                if (state.activeFilter === 'all') {
                    return posts;
                }

                return posts.filter(function (post) {
                    return post.topic_key === state.activeFilter;
                });
            }

            function renderPosts() {
                var root = document.getElementById('useful-posts');
                var empty = document.getElementById('useful-posts-empty');
                var posts = visiblePosts();

                if (!root || !empty) {
                    return;
                }

                if (!posts.length) {
                    root.innerHTML = '';
                    empty.classList.remove('d-none');
                    return;
                }

                empty.classList.add('d-none');

                root.innerHTML = posts.map(function (post) {
                    var sourceLink = post.source_url
                        ? '<a class="btn btn-sm btn-outline-secondary" href="' + escapeHtml(post.source_url) + '" target="_blank" rel="noopener">Источник</a>'
                        : '';

                    return (
                        '<div class="col-12 col-md-6">' +
                            '<article class="useful-article-card">' +
                                '<div class="d-flex justify-content-between align-items-start gap-3 mb-3">' +
                                    '<div>' +
                                        '<div class="small text-muted mb-2">' + escapeHtml(post.topic || 'Полезное') + '</div>' +
                                        '<h5 class="mb-0">' + escapeHtml(post.title) + '</h5>' +
                                    '</div>' +
                                    '<span class="badge bg-label-secondary">' + escapeHtml(post.reading_time_minutes || '3') + ' мин</span>' +
                                '</div>' +
                                '<p class="text-muted mb-3">' + escapeHtml(post.summary || '') + '</p>' +
                                '<div class="useful-article-meta mb-4">' +
                                    (post.published_at ? '<span>' + escapeHtml(formatDate(post.published_at)) + '</span>' : '') +
                                    (post.is_featured ? '<span>Приоритетный материал</span>' : '') +
                                '</div>' +
                                '<div class="d-flex flex-wrap gap-2">' +
                                    '<button type="button" class="btn btn-outline-primary btn-sm" data-useful-open="' + escapeHtml(String(post.id)) + '">Открыть</button>' +
                                    sourceLink +
                                '</div>' +
                            '</article>' +
                        '</div>'
                    );
                }).join('');
            }

            function renderPreferences(preferences) {
                var lockEl = document.getElementById('useful-pref-lock');
                var formEl = document.getElementById('useful-pref-form');

                if (!lockEl || !formEl) {
                    return;
                }

                if (!preferences.available) {
                    lockEl.innerHTML = 'Weekly-подборка доступна на тарифах Pro и Elite. <a href="' + escapeHtml(preferences.upgrade_url || '/subscription') + '">Открыть тарифы</a>';
                    lockEl.classList.remove('d-none');
                    formEl.classList.add('d-none');
                    return;
                }

                lockEl.classList.add('d-none');
                formEl.classList.remove('d-none');
                document.getElementById('useful-pref-enabled').checked = !!preferences.enabled;
                document.getElementById('useful-pref-channel').value = preferences.channel || 'platform';
                document.getElementById('useful-pref-preferences').value = preferences.preferences || '';
            }

            function renderAll() {
                renderHero();
                renderFeatured();
                renderFilters();
                renderPosts();
                renderPreferences(state.payload.preferences || {});
            }

            function findPostById(id) {
                var featured = state.payload.featured_post;
                if (featured && featured.id === id) {
                    return featured;
                }

                return (state.payload.posts || []).find(function (post) {
                    return post.id === id;
                }) || null;
            }

            function loadOverview() {
                fetch('/api/v1/useful/overview', { headers: buildHeaders() })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Не удалось загрузить раздел.');
                        }

                        return response.json();
                    })
                    .then(function (payload) {
                        state.payload = payload;
                        renderAll();
                    })
                    .catch(function (error) {
                        document.getElementById('useful-featured').innerHTML = '<div class="useful-empty">' + escapeHtml(error.message) + '</div>';
                    });
            }

            function savePreferences() {
                var statusEl = document.getElementById('useful-pref-status');
                statusEl.textContent = 'Сохраняем...';

                fetch('/api/v1/useful/preferences', {
                    method: 'PATCH',
                    headers: buildHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({
                        enabled: document.getElementById('useful-pref-enabled').checked,
                        channel: document.getElementById('useful-pref-channel').value,
                        preferences: document.getElementById('useful-pref-preferences').value.trim()
                    })
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Не удалось сохранить настройки.');
                        }

                        return response.json();
                    })
                    .then(function (payload) {
                        state.payload.preferences = payload.data || {};
                        renderPreferences(state.payload.preferences);
                        statusEl.textContent = 'Настройки weekly-подборки сохранены.';
                    })
                    .catch(function (error) {
                        statusEl.textContent = error.message;
                    });
            }

            function sendTestDigest() {
                var statusEl = document.getElementById('useful-pref-status');
                statusEl.textContent = 'Отправляем тест...';

                fetch('/api/v1/useful/test-digest', {
                    method: 'POST',
                    headers: buildHeaders({ 'Content-Type': 'application/json' })
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Не удалось отправить тестовую подборку.');
                        }

                        return response.json();
                    })
                    .then(function () {
                        statusEl.textContent = 'Тестовая weekly-подборка отправлена.';
                    })
                    .catch(function (error) {
                        statusEl.textContent = error.message;
                    });
            }

            document.getElementById('useful-open-digest-settings').addEventListener('click', function () {
                if (digestModal) {
                    digestModal.show();
                }
            });

            document.getElementById('useful-pref-save').addEventListener('click', savePreferences);
            document.getElementById('useful-pref-test').addEventListener('click', sendTestDigest);

            document.addEventListener('click', function (event) {
                var filterButton = event.target.closest('[data-useful-filter]');
                if (filterButton) {
                    state.activeFilter = filterButton.getAttribute('data-useful-filter') || 'all';
                    renderFilters();
                    renderPosts();
                    return;
                }

                var postButton = event.target.closest('[data-useful-open]');
                if (!postButton) {
                    return;
                }

                var id = parseInt(postButton.getAttribute('data-useful-open'), 10);
                var post = findPostById(id);
                openContentModal(post);
            });

            loadOverview();
        });
    </script>
@endsection
