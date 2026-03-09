@extends('layouts.app')

@section('title', 'Тренды')

@section('content')
    <style>
        .trends-hero {
            border: 1px solid rgba(var(--bs-primary-rgb), 0.16);
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.18), transparent 30%),
                linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08), rgba(var(--bs-body-bg-rgb), 0.02));
        }

        .trends-spotlight {
            border: 1px solid rgba(var(--bs-primary-rgb), 0.14);
            background: rgba(var(--bs-primary-rgb), 0.05);
        }

        .trends-chip {
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.1);
            border-radius: 999px;
            background: transparent;
            color: inherit;
            padding: 0.5rem 0.9rem;
            font-size: 0.875rem;
        }

        .trends-chip.active {
            background: rgba(var(--bs-primary-rgb), 0.12);
            border-color: rgba(var(--bs-primary-rgb), 0.28);
            color: rgb(var(--bs-primary-rgb));
        }

        .trends-card {
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
            height: 100%;
        }

        .trends-card .badge {
            white-space: nowrap;
        }

        .trends-play-card {
            border: 1px dashed rgba(var(--bs-primary-rgb), 0.28);
            background: rgba(var(--bs-primary-rgb), 0.04);
        }

        .trends-article-card {
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
        }

        .trends-empty {
            border: 1px dashed rgba(var(--bs-body-color-rgb), 0.16);
            border-radius: 1rem;
            padding: 1.25rem;
            text-align: center;
            color: var(--bs-secondary-color);
        }
    </style>

    <div class="card border-0 shadow-sm trends-hero mb-4">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-4">
                <div class="mw-lg-50">
                    <span class="badge bg-label-primary mb-3">Veloria Trends</span>
                    <h3 class="mb-2" id="trends-title">Тренды</h3>
                    <p class="text-muted mb-3" id="trends-subtitle">Следите за тем, что сейчас хотят клиентки, и обновляйте свои услуги без лишней теории.</p>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge bg-label-secondary" id="trends-specialty-badge">Подбираем нишу...</span>
                        <span class="small text-muted" id="trends-specialty-hint"></span>
                    </div>
                </div>
                <div class="card trends-spotlight border-0 shadow-none mb-0">
                    <div class="card-body p-4">
                        <div class="small text-uppercase text-muted mb-2" id="trends-spotlight-eyebrow">Главное сейчас</div>
                        <h4 class="mb-2" id="trends-spotlight-title">Собираем подборку...</h4>
                        <p class="text-muted mb-3" id="trends-spotlight-summary">Через пару секунд здесь появится главный тренд для вашей сферы.</p>
                        <div class="small text-muted mb-2" id="trends-spotlight-period"></div>
                        <div class="fw-medium" id="trends-spotlight-takeaway"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <input type="search" class="form-control" id="trends-search" placeholder="Искать по трендам, техникам и статьям" />
        </div>
        <div class="col-lg-4">
            <div class="d-flex flex-wrap gap-2 justify-content-lg-end" id="trends-category-filters"></div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-4">
            <div class="card trends-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0">Сигналы ниши</h5>
                        <span class="badge bg-label-primary">Сейчас</span>
                    </div>
                    <div id="trends-signals" class="d-flex flex-column gap-3"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-8">
            <div class="card trends-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                        <div>
                            <h5 class="mb-1">Что пробовать у себя</h5>
                            <p class="text-muted mb-0">Подборка эффектов, техник и подач, которые можно быстро внедрить в услуги и коммуникацию.</p>
                        </div>
                    </div>
                    <div class="row g-3" id="trends-grid"></div>
                    <div class="trends-empty d-none mt-3" id="trends-grid-empty">По этому запросу тренды не найдены.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-5">
            <div class="card trends-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="mb-1">Сценарии и идеи</h5>
                            <p class="text-muted mb-0">Готовые форматы для текста, сторис и быстрых материалов под актуальные запросы клиенток.</p>
                        </div>
                    </div>
                    <div id="trends-playbooks" class="d-flex flex-column gap-3"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-7">
            <div class="card trends-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                        <div>
                            <h5 class="mb-1">Статьи и разборы</h5>
                            <p class="text-muted mb-0">Короткие материалы о новых предпочтениях клиенток, трендовых эффектах и позиционировании услуг.</p>
                        </div>
                    </div>
                    <div id="trends-articles" class="d-flex flex-column gap-3"></div>
                    <div class="trends-empty d-none mt-3" id="trends-articles-empty">По этому запросу статьи не найдены.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="trendContentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="small text-muted mb-1" id="trend-modal-type"></div>
                        <h5 class="modal-title" id="trend-modal-title"></h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <div id="trend-modal-body" class="d-flex flex-column gap-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Закрыть</button>
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

            function createContentBlocks(content) {
                if (Array.isArray(content)) {
                    return content.map(function (item) {
                        return '<div class="p-3 rounded-3 bg-body-tertiary">' + item + '</div>';
                    }).join('');
                }

                if (content && typeof content === 'object') {
                    return Object.keys(content).map(function (key) {
                        return '<div><div class="small text-muted text-uppercase mb-1">' + key + '</div><div class="p-3 rounded-3 bg-body-tertiary">' + content[key] + '</div></div>';
                    }).join('');
                }

                return '<div class="p-3 rounded-3 bg-body-tertiary">' + (content || 'Материал скоро появится.') + '</div>';
            }

            var state = {
                search: '',
                activeCategory: 'all',
                payload: {
                    meta: {},
                    spotlight: {},
                    signals: [],
                    trend_cards: [],
                    playbooks: [],
                    articles: [],
                    categories: [],
                },
            };

            var modalEl = document.getElementById('trendContentModal');
            var modal = modalEl ? new bootstrap.Modal(modalEl) : null;

            function setText(id, value) {
                var el = document.getElementById(id);
                if (el) {
                    el.textContent = value || '';
                }
            }

            function renderHero(meta, spotlight) {
                setText('trends-title', meta.title || 'Тренды');
                setText('trends-subtitle', meta.subtitle || '');
                setText('trends-specialty-badge', (meta.specialty && meta.specialty.label) ? meta.specialty.label : 'Подборка по вашей нише');
                setText('trends-specialty-hint', meta.specialty ? meta.specialty.hint : '');
                setText('trends-spotlight-eyebrow', spotlight.eyebrow || 'Главное сейчас');
                setText('trends-spotlight-title', spotlight.title || 'Тренд недели');
                setText('trends-spotlight-summary', spotlight.summary || '');
                setText('trends-spotlight-period', spotlight.period_label || '');
                setText('trends-spotlight-takeaway', spotlight.takeaway || '');
            }

            function renderFilters(categories) {
                var root = document.getElementById('trends-category-filters');
                if (!root) return;
                root.innerHTML = '';

                var allButton = document.createElement('button');
                allButton.type = 'button';
                allButton.className = 'trends-chip' + (state.activeCategory === 'all' ? ' active' : '');
                allButton.textContent = 'Все';
                allButton.dataset.category = 'all';
                root.appendChild(allButton);

                categories.forEach(function (category) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'trends-chip' + (state.activeCategory === category.slug ? ' active' : '');
                    button.textContent = category.name;
                    button.dataset.category = category.slug;
                    root.appendChild(button);
                });
            }

            function renderSignals(signals) {
                var root = document.getElementById('trends-signals');
                if (!root) return;
                root.innerHTML = '';

                signals.forEach(function (signal) {
                    var item = document.createElement('div');
                    item.className = 'border rounded-4 p-3';
                    item.innerHTML =
                        '<div class="fw-semibold mb-1">' + signal.title + '</div>' +
                        '<div class="text-muted small mb-2">' + signal.description + '</div>' +
                        '<div class="small">' + signal.next_step + '</div>';
                    root.appendChild(item);
                });
            }

            function lessonMatches(card) {
                var search = state.search.toLowerCase();
                var categoryMatch = state.activeCategory === 'all' || ((card.category && card.category.name || '').toLowerCase().indexOf(state.activeCategory.replace('-', ' ')) !== -1) || (card.category_slug === state.activeCategory);
                var searchMatch = !search || [card.title, card.summary, (card.category && card.category.name) || ''].join(' ').toLowerCase().indexOf(search) !== -1;
                return categoryMatch && searchMatch;
            }

            function articleMatches(article) {
                var search = state.search.toLowerCase();
                var categoryMatch = state.activeCategory === 'all' || ((article.topic || '').toLowerCase().indexOf(state.activeCategory.replace('-', ' ')) !== -1);
                var searchMatch = !search || [article.title, article.summary, article.topic || ''].join(' ').toLowerCase().indexOf(search) !== -1;
                return categoryMatch && searchMatch;
            }

            function openModal(type, title, content) {
                if (!modal) return;
                setText('trend-modal-type', type);
                setText('trend-modal-title', title);
                document.getElementById('trend-modal-body').innerHTML = createContentBlocks(content);
                modal.show();
            }

            function renderTrendCards(cards) {
                var root = document.getElementById('trends-grid');
                var empty = document.getElementById('trends-grid-empty');
                if (!root || !empty) return;
                root.innerHTML = '';

                var visibleCards = cards.filter(lessonMatches);

                if (!visibleCards.length) {
                    empty.classList.remove('d-none');
                    return;
                }

                empty.classList.add('d-none');

                visibleCards.forEach(function (card) {
                    var col = document.createElement('div');
                    col.className = 'col-md-6 col-xl-4';
                    col.innerHTML =
                        '<div class="card trends-card shadow-none">' +
                            '<div class="card-body p-4 h-100 d-flex flex-column">' +
                                '<div class="d-flex justify-content-between align-items-start gap-2 mb-3">' +
                                    '<span class="badge bg-label-primary">' + ((card.category && card.category.name) || 'Тренд') + '</span>' +
                                    '<span class="small text-muted">' + (card.duration_minutes ? card.duration_minutes + ' мин' : (card.format || 'формат')) + '</span>' +
                                '</div>' +
                                '<h6 class="mb-2">' + card.title + '</h6>' +
                                '<p class="text-muted small mb-3 flex-grow-1">' + card.summary + '</p>' +
                                '<button type="button" class="btn btn-sm btn-outline-primary align-self-start" data-trend-open="lesson" data-id="' + card.id + '">Открыть тренд</button>' +
                            '</div>' +
                        '</div>';
                    root.appendChild(col);
                });
            }

            function renderPlaybooks(items) {
                var root = document.getElementById('trends-playbooks');
                if (!root) return;
                root.innerHTML = '';

                items.forEach(function (item) {
                    var card = document.createElement('div');
                    card.className = 'card trends-play-card shadow-none';
                    card.innerHTML =
                        '<div class="card-body p-4">' +
                            '<div class="d-flex justify-content-between align-items-start gap-2 mb-2">' +
                                '<h6 class="mb-0">' + item.title + '</h6>' +
                                '<span class="badge bg-label-secondary">' + item.type_label + '</span>' +
                            '</div>' +
                            '<p class="text-muted small mb-3">' + item.description + '</p>' +
                            '<button type="button" class="btn btn-sm btn-outline-primary" data-trend-open="playbook" data-id="' + item.id + '">Смотреть сценарий</button>' +
                        '</div>';
                    root.appendChild(card);
                });
            }

            function renderArticles(items) {
                var root = document.getElementById('trends-articles');
                var empty = document.getElementById('trends-articles-empty');
                if (!root || !empty) return;
                root.innerHTML = '';

                var visibleItems = items.filter(articleMatches);

                if (!visibleItems.length) {
                    empty.classList.remove('d-none');
                    return;
                }

                empty.classList.add('d-none');

                visibleItems.forEach(function (article) {
                    var card = document.createElement('div');
                    card.className = 'card trends-article-card shadow-none';
                    card.innerHTML =
                        '<div class="card-body p-4">' +
                            '<div class="d-flex flex-column flex-lg-row justify-content-between gap-3">' +
                                '<div>' +
                                    '<div class="small text-muted mb-2">' + (article.topic || 'Тренд') + '</div>' +
                                    '<h6 class="mb-2">' + article.title + '</h6>' +
                                    '<p class="text-muted small mb-0">' + article.summary + '</p>' +
                                '</div>' +
                                '<div class="d-flex flex-column align-items-lg-end gap-2">' +
                                    '<span class="badge bg-label-primary">' + (article.reading_time_minutes ? article.reading_time_minutes + ' мин' : 'Статья') + '</span>' +
                                    '<button type="button" class="btn btn-sm btn-outline-primary" data-trend-open="article" data-id="' + article.id + '">Читать</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>';
                    root.appendChild(card);
                });
            }

            function renderAll() {
                renderHero(state.payload.meta || {}, state.payload.spotlight || {});
                renderFilters(state.payload.categories || []);
                renderSignals(state.payload.signals || []);
                renderTrendCards(state.payload.trend_cards || []);
                renderPlaybooks(state.payload.playbooks || []);
                renderArticles(state.payload.articles || []);
            }

            function loadOverview() {
                fetch('/api/v1/trends/overview', { headers: buildHeaders() })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Не удалось загрузить тренды.');
                        }
                        return response.json();
                    })
                    .then(function (payload) {
                        state.payload = payload;
                        renderAll();
                    })
                    .catch(function (error) {
                        var signals = document.getElementById('trends-signals');
                        if (signals) {
                            signals.innerHTML = '<div class="trends-empty">Не удалось загрузить тренды. ' + error.message + '</div>';
                        }
                    });
            }

            document.getElementById('trends-search').addEventListener('input', function (event) {
                state.search = event.target.value.trim();
                renderTrendCards(state.payload.trend_cards || []);
                renderArticles(state.payload.articles || []);
            });

            document.getElementById('trends-category-filters').addEventListener('click', function (event) {
                var button = event.target.closest('[data-category]');
                if (!button) return;
                state.activeCategory = button.dataset.category;
                renderFilters(state.payload.categories || []);
                renderTrendCards(state.payload.trend_cards || []);
                renderArticles(state.payload.articles || []);
            });

            document.addEventListener('click', function (event) {
                var button = event.target.closest('[data-trend-open]');
                if (!button) return;

                var type = button.dataset.trendOpen;
                var id = parseInt(button.dataset.id, 10);
                var collection = [];
                var label = '';

                if (type === 'lesson') {
                    collection = state.payload.trend_cards || [];
                    label = 'Тренд';
                } else if (type === 'playbook') {
                    collection = state.payload.playbooks || [];
                    label = 'Сценарий';
                } else {
                    collection = state.payload.articles || [];
                    label = 'Статья';
                }

                var item = collection.find(function (entry) { return entry.id === id; });
                if (!item) return;

                openModal(label, item.title, item.content);
            });

            loadOverview();
        });
    </script>
@endsection
