@extends('layouts.app')

@section('title', 'Backoffice Useful')

@section('content')
    @include('admin.partials.styles')

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-shell">
            <section class="admin-hero">
                <h1>Полезное</h1>
                <p>Редакционные публикации для раздела «Полезное»: weekly-дайджест, бизнес-подсказки, налоги, идеи и важные обновления.</p>
            </section>

            @include('admin.partials.nav')

            <div class="admin-two-column">
                <section class="admin-panel">
                    <div class="admin-panel-body admin-stack">
                        <div class="admin-toolbar">
                            <button type="button" class="btn btn-primary" id="admin-useful-create-btn">Новая публикация</button>
                            <input type="search" class="form-control" id="admin-useful-search" placeholder="Найти по заголовку, slug или теме">
                            <select class="form-select" id="admin-useful-status">
                                <option value="all">Все публикации</option>
                                <option value="published">Опубликованные</option>
                                <option value="draft">Черновики</option>
                            </select>
                        </div>
                        <div id="admin-useful-list" class="admin-list"></div>
                    </div>
                </section>

                <section class="admin-panel soft">
                    <div class="admin-panel-body">
                        <div id="admin-useful-detail" class="admin-empty">Выберите публикацию слева, чтобы отредактировать материал или создать новый.</div>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var listEl = document.getElementById('admin-useful-list');
            var detailEl = document.getElementById('admin-useful-detail');
            var searchInput = document.getElementById('admin-useful-search');
            var statusSelect = document.getElementById('admin-useful-status');
            var createButton = document.getElementById('admin-useful-create-btn');
            var posts = [];
            var selectedId = null;
            var createMode = false;

            function formatDate(value) {
                return value ? new Date(value).toLocaleString() : 'Без даты';
            }

            function stringifyContent(value) {
                if (value === null || value === undefined || value === '') {
                    return '';
                }
                return typeof value === 'string' ? value : JSON.stringify(value, null, 2);
            }

            function parseContent(value) {
                var trimmed = value.trim();
                if (!trimmed) return null;
                return trimmed;
            }

            function renderList() {
                if (!posts.length) {
                    listEl.innerHTML = '<div class="admin-empty">Публикации по этим условиям не найдены.</div>';
                    return;
                }

                listEl.innerHTML = posts.map(function (post) {
                    var active = post.id === selectedId && !createMode ? 'is-active' : '';
                    var status = post.is_published ? 'Опубликовано' : 'Черновик';
                    var statusClass = post.is_published ? 'success' : '';
                    return '<article class="admin-row is-clickable ' + active + '" data-post-id="' + post.id + '"><div><div class="admin-row-title">' + (post.title || 'Без названия') + '</div><div class="admin-row-meta">' + (post.topic || 'Без темы') + ' • ' + post.slug + ' • ' + formatDate(post.published_at) + '</div></div><span class="admin-chip ' + statusClass + '">' + status + '</span></article>';
                }).join('');

                listEl.querySelectorAll('[data-post-id]').forEach(function (node) {
                    node.addEventListener('click', function () {
                        loadPost(Number(node.getAttribute('data-post-id')));
                    });
                });
            }

            function basePayload() {
                return {
                    slug: '',
                    topic: '',
                    reading_time_minutes: 5,
                    sort_order: 0,
                    source_url: '',
                    published_at: '',
                    is_published: true,
                    is_featured: false,
                    title: { ru: '', en: '' },
                    summary: { ru: '', en: '' },
                    content: { ru: '', en: '' },
                    action: {
                        ru: { label: 'Открыть материал', url: '' },
                        en: { label: 'Open material', url: '' }
                    }
                };
            }

            function renderForm(post, mode) {
                var isCreate = mode === 'create';
                var value = post || basePayload();
                var publishedAt = value.published_at ? new Date(value.published_at).toISOString().slice(0, 16) : '';

                detailEl.innerHTML = '<div class="admin-stack"><div class="d-flex justify-content-between align-items-start gap-3 flex-wrap"><div><h3 class="mb-1">' + (isCreate ? 'Новая публикация' : (value.title.ru || 'Без названия')) + '</h3><p class="text-muted mb-0">' + (isCreate ? 'Материал для раздела «Полезное» и weekly-дайджеста.' : ((value.topic || 'Без темы') + ' • ' + value.slug)) + '</p></div><span class="admin-chip ' + (value.is_published ? 'success' : '') + '">' + (value.is_published ? 'Опубликовано' : 'Черновик') + '</span></div><form id="admin-useful-form" class="admin-stack"><div class="admin-detail-grid"><div><label class="form-label" for="useful-slug">Slug</label><input class="form-control" id="useful-slug" value="' + (value.slug || '') + '"></div><div><label class="form-label" for="useful-topic">Тема</label><input class="form-control" id="useful-topic" value="' + (value.topic || '') + '" placeholder="marketing, legal, business"></div><div><label class="form-label" for="useful-reading-time">Время чтения</label><input type="number" min="1" max="120" class="form-control" id="useful-reading-time" value="' + (value.reading_time_minutes || 5) + '"></div><div><label class="form-label" for="useful-sort-order">Порядок</label><input type="number" min="0" max="10000" class="form-control" id="useful-sort-order" value="' + (value.sort_order || 0) + '"></div><div><label class="form-label" for="useful-published-at">Дата публикации</label><input type="datetime-local" class="form-control" id="useful-published-at" value="' + publishedAt + '"></div><div><label class="form-label" for="useful-source-url">Источник</label><input class="form-control" id="useful-source-url" value="' + (value.source_url || '') + '" placeholder="https://..."></div></div><div class="admin-detail-grid"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="useful-is-published" ' + (value.is_published ? 'checked' : '') + '><label class="form-check-label" for="useful-is-published">Опубликовано</label></div><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="useful-is-featured" ' + (value.is_featured ? 'checked' : '') + '><label class="form-check-label" for="useful-is-featured">Показывать в приоритете</label></div></div><div class="admin-detail-grid"><div><label class="form-label" for="useful-title-ru">Заголовок RU</label><input class="form-control" id="useful-title-ru" value="' + (value.title.ru || '') + '"></div><div><label class="form-label" for="useful-title-en">Заголовок EN</label><input class="form-control" id="useful-title-en" value="' + (value.title.en || '') + '"></div></div><div class="admin-detail-grid"><div><label class="form-label" for="useful-summary-ru">Короткое описание RU</label><textarea class="form-control" rows="3" id="useful-summary-ru">' + (value.summary.ru || '') + '</textarea></div><div><label class="form-label" for="useful-summary-en">Короткое описание EN</label><textarea class="form-control" rows="3" id="useful-summary-en">' + (value.summary.en || '') + '</textarea></div></div><div class="admin-detail-grid"><div><label class="form-label" for="useful-content-ru">Контент RU</label><textarea class="form-control" rows="10" id="useful-content-ru">' + stringifyContent(value.content.ru) + '</textarea><div class="form-text">Можно вставить обычный текст или JSON.</div></div><div><label class="form-label" for="useful-content-en">Контент EN</label><textarea class="form-control" rows="10" id="useful-content-en">' + stringifyContent(value.content.en) + '</textarea><div class="form-text">Можно вставить обычный текст или JSON.</div></div></div><div class="admin-detail-grid"><div><label class="form-label" for="useful-action-label-ru">Кнопка RU</label><input class="form-control" id="useful-action-label-ru" value="' + ((value.action.ru && value.action.ru.label) || '') + '"></div><div><label class="form-label" for="useful-action-label-en">Кнопка EN</label><input class="form-control" id="useful-action-label-en" value="' + ((value.action.en && value.action.en.label) || '') + '"></div><div><label class="form-label" for="useful-action-url-ru">Ссылка RU</label><input class="form-control" id="useful-action-url-ru" value="' + ((value.action.ru && value.action.ru.url) || '') + '"></div><div><label class="form-label" for="useful-action-url-en">Ссылка EN</label><input class="form-control" id="useful-action-url-en" value="' + ((value.action.en && value.action.en.url) || '') + '"></div></div><div class="d-flex gap-2 flex-wrap"><button class="btn btn-primary" type="submit">' + (isCreate ? 'Создать публикацию' : 'Сохранить') + '</button>' + (isCreate ? '<button class="btn btn-outline-secondary" type="button" id="admin-useful-cancel">Отмена</button>' : '<button class="btn btn-outline-danger" type="button" id="admin-useful-delete">Удалить</button>') + '</div></form></div>';

                document.getElementById('admin-useful-form').addEventListener('submit', function (event) {
                    event.preventDefault();

                    var payload = {
                        slug: document.getElementById('useful-slug').value.trim(),
                        topic: document.getElementById('useful-topic').value.trim(),
                        reading_time_minutes: Number(document.getElementById('useful-reading-time').value || 5),
                        sort_order: Number(document.getElementById('useful-sort-order').value || 0),
                        source_url: document.getElementById('useful-source-url').value.trim() || null,
                        published_at: document.getElementById('useful-published-at').value ? new Date(document.getElementById('useful-published-at').value).toISOString() : null,
                        is_published: document.getElementById('useful-is-published').checked,
                        is_featured: document.getElementById('useful-is-featured').checked,
                        title: {
                            ru: document.getElementById('useful-title-ru').value.trim(),
                            en: document.getElementById('useful-title-en').value.trim()
                        },
                        summary: {
                            ru: document.getElementById('useful-summary-ru').value.trim(),
                            en: document.getElementById('useful-summary-en').value.trim()
                        },
                        content: {
                            ru: parseContent(document.getElementById('useful-content-ru').value),
                            en: parseContent(document.getElementById('useful-content-en').value)
                        },
                        action: {
                            ru: {
                                label: document.getElementById('useful-action-label-ru').value.trim(),
                                url: document.getElementById('useful-action-url-ru').value.trim() || null
                            },
                            en: {
                                label: document.getElementById('useful-action-label-en').value.trim(),
                                url: document.getElementById('useful-action-url-en').value.trim() || null
                            }
                        }
                    };

                    fetch(isCreate ? '/api/v1/admin/useful/posts' : '/api/v1/admin/useful/posts/' + value.id, {
                        method: isCreate ? 'POST' : 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload)
                    })
                        .then(function (response) {
                            if (!response.ok) throw new Error('Не удалось сохранить публикацию.');
                            return response.json();
                        })
                        .then(function (payload) {
                            createMode = false;
                            return loadPosts().then(function () {
                                return loadPost(payload.data.id);
                            });
                        })
                        .catch(function (error) {
                            alert(error.message);
                        });
                });

                var cancelBtn = document.getElementById('admin-useful-cancel');
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', function () {
                        createMode = false;
                        detailEl.innerHTML = '<div class="admin-empty">Выберите публикацию слева, чтобы отредактировать материал или создать новый.</div>';
                    });
                }

                var deleteBtn = document.getElementById('admin-useful-delete');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', function () {
                        if (!confirm('Удалить публикацию?')) return;

                        fetch('/api/v1/admin/useful/posts/' + value.id, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(function (response) {
                                if (!response.ok) throw new Error('Не удалось удалить публикацию.');
                                createMode = false;
                                selectedId = null;
                                return loadPosts();
                            })
                            .then(function () {
                                detailEl.innerHTML = '<div class="admin-empty">Публикация удалена. Выберите другую слева или создайте новую.</div>';
                            })
                            .catch(function (error) {
                                alert(error.message);
                            });
                    });
                }
            }

            function loadPosts() {
                var params = new URLSearchParams();
                if (searchInput.value.trim()) params.set('search', searchInput.value.trim());
                if (statusSelect.value !== 'all') params.set('status', statusSelect.value);

                listEl.innerHTML = '<div class="admin-empty">Загрузка...</div>';

                return fetch('/api/v1/admin/useful/posts?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) { return response.json(); })
                    .then(function (payload) {
                        posts = payload.data || [];
                        renderList();
                    });
            }

            function loadPost(id) {
                selectedId = id;
                createMode = false;
                detailEl.innerHTML = '<div class="admin-empty">Загрузка публикации...</div>';

                fetch('/api/v1/admin/useful/posts/' + id, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) { return response.json(); })
                    .then(function (payload) {
                        renderList();
                        renderForm(payload.data, 'edit');
                    });
            }

            searchInput.addEventListener('input', function () {
                loadPosts();
            });

            statusSelect.addEventListener('change', function () {
                loadPosts();
            });

            createButton.addEventListener('click', function () {
                createMode = true;
                selectedId = null;
                renderList();
                renderForm(basePayload(), 'create');
            });

            loadPosts();
        });
    </script>
@endsection
