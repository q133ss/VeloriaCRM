@extends('layouts.app')

@section('title', __('learning.title'))

@section('content')
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ __('learning.title') }}</h4>
            <p class="text-muted mb-0">{{ __('learning.subtitle') }}</p>
        </div>
    </div>

    <ul class="nav nav-pills flex-nowrap overflow-auto mb-4" id="learningTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="learning-plan-tab-button" data-bs-toggle="pill" data-bs-target="#learning-plan"
                type="button" role="tab" aria-controls="learning-plan" aria-selected="true">
                <i class="ri-map-pin-user-line me-2"></i>{{ __('learning.tabs.plan') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="learning-lessons-tab-button" data-bs-toggle="pill"
                data-bs-target="#learning-lessons" type="button" role="tab" aria-controls="learning-lessons"
                aria-selected="false">
                <i class="ri-flashlight-line me-2"></i>{{ __('learning.tabs.lessons') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="learning-knowledge-tab-button" data-bs-toggle="pill"
                data-bs-target="#learning-knowledge" type="button" role="tab" aria-controls="learning-knowledge"
                aria-selected="false">
                <i class="ri-book-2-line me-2"></i>{{ __('learning.tabs.knowledge') }}
            </button>
        </li>
    </ul>

    <div class="tab-content" id="learningTabContent">
        <div class="tab-pane fade show active" id="learning-plan" role="tabpanel" aria-labelledby="learning-plan-tab-button">
            <div class="row g-4">
                <div class="col-12 col-xl-8">
                    <div class="d-flex flex-column gap-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                    <div>
                                        <h5 class="mb-1">{{ __('learning.plan.header.title') }}</h5>
                                        <p class="text-muted mb-0">{{ __('learning.plan.header.subtitle') }}</p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-label-primary">AI</span>
                                    </div>
                                </div>
                                <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center mb-4">
                                    <div id="learning-period-label" class="fw-semibold"></div>
                                </div>
                                <div class="bg-label-primary text-primary rounded-3 p-4 mb-4">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-2" id="learning-ai-headline"></h6>
                                            <p class="mb-0 text-body" id="learning-ai-description"></p>
                                        </div>
                                    </div>
                                </div>
                                <h6 class="text-uppercase text-muted small mb-2">{{ __('learning.plan.ai_summary.tips_title') }}</h6>
                                <ul class="list-unstyled d-flex flex-column gap-2 mb-0" id="learning-ai-tips"></ul>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                                    <div>
                                        <h5 class="mb-1">{{ __('learning.plan.insights.title') }}</h5>
                                        <p class="text-muted mb-0">{{ __('learning.plan.ai_summary.subtitle') }}</p>
                                    </div>
                                </div>
                                <div id="learning-insights" class="d-flex flex-column gap-3"></div>
                                <div id="learning-insights-empty" class="text-muted small d-none">
                                    {{ __('learning.plan.insights.empty') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-1">{{ __('learning.plan.tasks.title') }}</h5>
                                    <p class="text-muted mb-0">{{ __('learning.plan.tasks.subtitle') }}</p>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted small d-block">{{ __('learning.plan.tasks.progress_label') }}</span>
                                    <span class="fw-semibold" id="learning-plan-progress-label"></span>
                                </div>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar" role="progressbar" id="learning-plan-progress-bar" style="width: 0%"
                                    aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div id="learning-task-alert" class="alert d-none" role="alert"></div>
                            <div id="learning-tasks" class="d-flex flex-column gap-3 flex-grow-1"></div>
                            <div id="learning-tasks-empty" class="text-muted small d-none">
                                {{ __('learning.plan.tasks.empty') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="learning-lessons" role="tabpanel" aria-labelledby="learning-lessons-tab-button">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                        <div>
                            <h5 class="mb-1">{{ __('learning.lessons.title') }}</h5>
                            <p class="text-muted mb-0">{{ __('learning.lessons.subtitle') }}</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2" id="learning-lesson-filters"></div>
                    </div>
                    <div class="row g-4" id="learning-lessons-list"></div>
                    <div id="learning-lessons-empty" class="text-muted small d-none">
                        {{ __('learning.lessons.empty') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="learning-knowledge" role="tabpanel" aria-labelledby="learning-knowledge-tab-button">
            <div class="row g-4">
                <div class="col-12 col-xl-7">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                                <div>
                                    <h5 class="mb-1">{{ __('learning.knowledge.title') }}</h5>
                                    <p class="text-muted mb-0">{{ __('learning.knowledge.subtitle') }}</p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <input type="search" class="form-control" id="learning-knowledge-search"
                                    placeholder="{{ __('learning.knowledge.search_placeholder') }}" />
                            </div>
                            <div class="d-flex flex-column gap-3 flex-grow-1" id="learning-articles-list"></div>
                            <div id="learning-articles-empty" class="text-muted small d-none">
                                {{ __('learning.knowledge.articles_empty') }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-5">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="mb-3">{{ __('learning.knowledge.templates_title') }}</h5>
                            <div id="learning-templates-groups" class="d-flex flex-column gap-4"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="learningTemplateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="learningTemplateModalLabel"></h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="learning-template-modal-content" class="d-flex flex-column gap-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ __('learning.knowledge.modal.close') }}
                    </button>
                    <button type="button" class="btn btn-primary" id="learning-template-copy">
                        <i class="ri-file-copy-line me-1"></i>{{ __('learning.knowledge.modal.copy') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const translations = {
                alerts: {
                    loadError: @json(__('learning.alerts.load_error')),
                    taskSuccess: @json(__('learning.notifications.task_updated')),
                    taskError: @json(__('learning.notifications.task_error')),
                },
                plan: {
                    ai: {
                        tipsTitle: @json(__('learning.plan.ai_summary.tips_title')),
                        fallbackTip: @json(__('learning.plan.ai_summary.fallback.tip_default')),
                    },
                    insights: {
                        impactLabel: @json(__('learning.plan.insights.impact_label')),
                        actionLabel: @json(__('learning.plan.insights.action_label')),
                        empty: @json(__('learning.plan.insights.empty')),
                        confidence: @json(__('learning.plan.insights.confidence')),
                    },
                    tasks: {
                        progressLabel: @json(__('learning.plan.tasks.progress_label')),
                        progressSummary: @json(__('learning.plan.tasks.progress_summary')),
                        progressSummaryEmpty: @json(__('learning.plan.tasks.progress_summary_empty')),
                        counter: @json(__('learning.plan.tasks.counter')),
                        counterSimple: @json(__('learning.plan.tasks.counter_simple')),
                        duePrefix: @json(__('learning.plan.tasks.due_prefix')),
                        empty: @json(__('learning.plan.tasks.empty')),
                        complete: @json(__('learning.plan.tasks.complete')),
                        undo: @json(__('learning.plan.tasks.undo')),
                    },
                },
                lessons: {
                    filterAll: @json(__('learning.lessons.filters.all')),
                    duration: @json(__('learning.lessons.duration')),
                    empty: @json(__('learning.lessons.empty')),
                    cta: @json(__('learning.lessons.cta')),
                },
                knowledge: {
                    openTemplate: @json(__('learning.knowledge.open_template')),
                    templateEmpty: @json(__('learning.knowledge.template_empty')),
                    groups: @json(__('learning.knowledge.groups')),
                },
            };

            function getCookie(name) {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) return parts.pop().split(';').shift();
                return null;
            }

            function authHeaders(extra = {}) {
                const headers = Object.assign({
                    'Accept': 'application/json',
                    'Accept-Language': document.documentElement.lang || 'en',
                }, extra);

                const token = getCookie('token');
                if (token) {
                    headers['Authorization'] = `Bearer ${token}`;
                }

                return headers;
            }

            const aiHeadlineEl = document.getElementById('learning-ai-headline');
            const aiDescriptionEl = document.getElementById('learning-ai-description');
            const aiTipsEl = document.getElementById('learning-ai-tips');
            const periodLabelEl = document.getElementById('learning-period-label');
            const insightsContainer = document.getElementById('learning-insights');
            const insightsEmpty = document.getElementById('learning-insights-empty');
            const taskContainer = document.getElementById('learning-tasks');
            const taskEmpty = document.getElementById('learning-tasks-empty');
            const taskAlert = document.getElementById('learning-task-alert');
            const progressBar = document.getElementById('learning-plan-progress-bar');
            const progressLabel = document.getElementById('learning-plan-progress-label');
            const lessonFilters = document.getElementById('learning-lesson-filters');
            const lessonsList = document.getElementById('learning-lessons-list');
            const lessonsEmpty = document.getElementById('learning-lessons-empty');
            const articlesList = document.getElementById('learning-articles-list');
            const articlesEmpty = document.getElementById('learning-articles-empty');
            const templatesGroups = document.getElementById('learning-templates-groups');
            const searchInput = document.getElementById('learning-knowledge-search');
            const templateModal = document.getElementById('learningTemplateModal');
            const templateModalLabel = document.getElementById('learningTemplateModalLabel');
            const templateModalContent = document.getElementById('learning-template-modal-content');
            const templateCopyButton = document.getElementById('learning-template-copy');

            let templatesCache = {};
            let activeCategory = null;
            let lastSearch = '';
            let currentTemplateContent = '';

            function showAlert(element, message, type = 'success') {
                if (!element) return;
                element.classList.remove('d-none', 'alert-success', 'alert-danger');
                element.classList.add(`alert-${type}`);
                element.textContent = message;
            }

            function hideAlert(element) {
                if (!element) return;
                element.classList.add('d-none');
                element.textContent = '';
            }

            function renderPlan(data) {
                const ai = data.ai || {};
                aiHeadlineEl.textContent = ai.headline || '';
                aiDescriptionEl.textContent = ai.description || '';

                aiTipsEl.innerHTML = '';
                const tips = Array.isArray(ai.tips) && ai.tips.length ? ai.tips : [translations.plan.ai.fallbackTip];
                tips.forEach(function (tip) {
                    const li = document.createElement('li');
                    li.className = 'd-flex align-items-start gap-2';
                    li.innerHTML = '<i class="ri-lightbulb-flash-line text-primary mt-1"></i><span>' + tip + '</span>';
                    aiTipsEl.appendChild(li);
                });

                if (data.period && data.period.label) {
                    periodLabelEl.textContent = data.period.label;
                }

                const insights = Array.isArray(data.insights) ? data.insights : [];
                insightsContainer.innerHTML = '';
                if (!insights.length) {
                    insightsEmpty.classList.remove('d-none');
                } else {
                    insightsEmpty.classList.add('d-none');
                    insights.forEach(function (insight) {
                        const card = document.createElement('div');
                        card.className = 'border rounded-3 p-3';

                        const badge = insight.impact ?
                            '<span class="badge bg-label-success text-wrap">' + translations.plan.insights.impactLabel + ': ' + insight.impact + '</span>' : '';

                        const confidence = typeof insight.confidence === 'number'
                            ? translations.plan.insights.confidence.replace(':value', insight.confidence)
                            : '';

                        card.innerHTML = `
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <h6 class="mb-1">${insight.title || ''}</h6>
                                        <p class="text-muted mb-0">${insight.description || ''}</p>
                                    </div>
                                    <div class="text-end small text-muted">${confidence}</div>
                                </div>
                                ${badge}
                                ${insight.action ? `<div class="small fw-semibold">${translations.plan.insights.actionLabel}: ${insight.action}</div>` : ''}
                            </div>
                        `;

                        insightsContainer.appendChild(card);
                    });
                }

                const plan = data.plan || {};
                const progress = plan.progress || {};
                const percent = Math.min(100, Math.max(0, progress.percent || 0));
                progressBar.style.width = percent + '%';
                progressBar.setAttribute('aria-valuenow', percent);

                if (progress.total > 0) {
                    const summary = translations.plan.tasks.progressSummary
                        .replace(':done', progress.completed ?? 0)
                        .replace(':total', progress.total ?? 0);
                    progressLabel.textContent = summary;
                } else {
                    progressLabel.textContent = translations.plan.tasks.progressSummaryEmpty;
                }

                const tasks = Array.isArray(plan.tasks) ? plan.tasks : [];
                taskContainer.innerHTML = '';
                hideAlert(taskAlert);
                if (!tasks.length) {
                    taskEmpty.classList.remove('d-none');
                } else {
                    taskEmpty.classList.add('d-none');
                    tasks.forEach(function (task) {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'border rounded-3 p-3';

                        const progressInfo = task.progress || {};
                        let counterText = '';
                        if (progressInfo.target > 0) {
                            counterText = translations.plan.tasks.counter
                                .replace(':current', progressInfo.current ?? 0)
                                .replace(':target', progressInfo.target ?? 0)
                                .replace(':unit', progressInfo.unit || '');
                        } else {
                            counterText = translations.plan.tasks.counterSimple
                                .replace(':current', progressInfo.current ?? 0);
                        }

                        const dueLabel = task.due_label ? `${translations.plan.tasks.duePrefix} ${task.due_label}` : '';
                        const completed = task.status === 'completed';
                        const buttonLabel = completed ? translations.plan.tasks.undo : translations.plan.tasks.complete;
                        const buttonClass = completed ? 'btn-outline-secondary' : 'btn-primary';

                        wrapper.innerHTML = `
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <h6 class="mb-1">${task.title || ''}</h6>
                                        <p class="text-muted small mb-0">${task.description || ''}</p>
                                    </div>
                                    <div class="text-end small text-muted">${dueLabel}</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-label-info">${counterText}</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress" style="width: 120px; height: 6px;">
                                            <div class="progress-bar" role="progressbar" style="width: ${(progressInfo.percent || 0)}%"></div>
                                        </div>
                                        <button type="button" class="btn btn-sm ${buttonClass}" data-task-action data-task-id="${task.id}" data-completed="${completed ? '1' : '0'}">
                                            ${buttonLabel}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;

                        const button = wrapper.querySelector('[data-task-action]');
                        button.addEventListener('click', function () {
                            const isCompleted = button.getAttribute('data-completed') === '1';
                            updateTask(task.id, !isCompleted);
                        });

                        taskContainer.appendChild(wrapper);
                    });
                }
            }

            function renderLessonFilters(categories) {
                lessonFilters.innerHTML = '';
                const allButton = document.createElement('button');
                allButton.type = 'button';
                allButton.className = 'btn btn-sm ' + (activeCategory ? 'btn-outline-secondary' : 'btn-primary');
                allButton.textContent = translations.lessons.filterAll;
                allButton.addEventListener('click', function () {
                    activeCategory = null;
                    loadLessons();
                });
                lessonFilters.appendChild(allButton);

                categories.forEach(function (category) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'btn btn-sm ' + (category.active ? 'btn-primary' : 'btn-outline-secondary');
                    button.textContent = category.name;
                    button.addEventListener('click', function () {
                        activeCategory = category.slug;
                        loadLessons();
                    });
                    lessonFilters.appendChild(button);
                });
            }

            function renderLessons(data) {
                const lessons = Array.isArray(data.lessons) ? data.lessons : [];
                lessonsList.innerHTML = '';

                if (!lessons.length) {
                    lessonsEmpty.classList.remove('d-none');
                } else {
                    lessonsEmpty.classList.add('d-none');
                    lessons.forEach(function (lesson) {
                        const col = document.createElement('div');
                        col.className = 'col-12 col-md-6 col-xl-4';
                        const duration = translations.lessons.duration.replace(':minutes', lesson.duration_minutes || '');
                        col.innerHTML = `
                            <div class="card h-100">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="badge bg-label-secondary">${lesson.category?.name || ''}</span>
                                        <span class="text-muted small">${duration}</span>
                                    </div>
                                    <h6 class="mb-2">${lesson.title || ''}</h6>
                                    <p class="text-muted flex-grow-1">${lesson.summary || ''}</p>
                                    <button class="btn btn-sm btn-outline-primary mt-3 align-self-start" type="button" data-lesson-content='${JSON.stringify(lesson.content || {})}' data-lesson-title="${lesson.title || ''}">
                                        <i class="ri-file-text-line me-1"></i>${translations.lessons.cta}
                                    </button>
                                </div>
                            </div>
                        `;

                        const button = col.querySelector('button[data-lesson-content]');
                        button.addEventListener('click', function () {
                            const title = button.getAttribute('data-lesson-title');
                            const content = JSON.parse(button.getAttribute('data-lesson-content') || '{}');
                            openTemplateModal(title, formatContent(content));
                        });

                        lessonsList.appendChild(col);
                    });
                }

                const categories = Array.isArray(data.categories) ? data.categories : [];
                renderLessonFilters(categories);
            }

            function renderKnowledge(data) {
                const articles = Array.isArray(data.articles) ? data.articles : [];
                articlesList.innerHTML = '';
                if (!articles.length) {
                    articlesEmpty.classList.remove('d-none');
                } else {
                    articlesEmpty.classList.add('d-none');
                    articles.forEach(function (article) {
                        const card = document.createElement('div');
                        card.className = 'border rounded-3 p-3';
                        const actionLabel = article.action?.label || translations.knowledge.openTemplate;

                        card.innerHTML = `
                            <div class="d-flex flex-column gap-2">
                                <div>
                                    <h6 class="mb-1">${article.title || ''}</h6>
                                    <p class="text-muted mb-0">${article.summary || ''}</p>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-label-secondary">${article.reading_time_minutes || 0} min</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-article='${JSON.stringify(article)}'>
                                        <i class="ri-arrow-right-up-line me-1"></i>${actionLabel}
                                    </button>
                                </div>
                            </div>
                        `;

                        const button = card.querySelector('button[data-article]');
                        button.addEventListener('click', function () {
                            const payload = JSON.parse(button.getAttribute('data-article') || '{}');
                            openTemplateModal(payload.title || '', formatContent(payload.content));
                        });

                        articlesList.appendChild(card);
                    });
                }

                templatesGroups.innerHTML = '';
                templatesCache = data.templates || {};
                const groupKeys = Object.keys(translations.knowledge.groups || {});
                groupKeys.forEach(function (groupKey) {
                    const groupTitle = translations.knowledge.groups[groupKey] || groupKey;
                    const templates = Array.isArray(templatesCache[groupKey]) ? templatesCache[groupKey] : [];

                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">${groupTitle}</h6>
                            <span class="badge bg-label-info">${templates.length}</span>
                        </div>
                    `;

                    if (!templates.length) {
                        const empty = document.createElement('div');
                        empty.className = 'text-muted small';
                        empty.textContent = translations.knowledge.templateEmpty;
                        wrapper.appendChild(empty);
                    } else {
                        const list = document.createElement('div');
                        list.className = 'd-flex flex-column gap-2';
                        templates.forEach(function (template) {
                            const item = document.createElement('div');
                            item.className = 'border rounded-3 p-3';
                            item.innerHTML = `
                                <div class="d-flex flex-column gap-1">
                                    <h6 class="mb-1">${template.title || ''}</h6>
                                    <p class="text-muted small mb-2">${template.description || ''}</p>
                                    <button type="button" class="btn btn-sm btn-outline-primary align-self-start" data-template='${JSON.stringify(template)}'>
                                        <i class="ri-eye-line me-1"></i>${translations.knowledge.openTemplate}
                                    </button>
                                </div>
                            `;

                            const button = item.querySelector('button[data-template]');
                            button.addEventListener('click', function () {
                                const payload = JSON.parse(button.getAttribute('data-template') || '{}');
                                openTemplateModal(payload.title || '', formatContent(payload.content));
                            });

                            list.appendChild(item);
                        });
                        wrapper.appendChild(list);
                    }

                    templatesGroups.appendChild(wrapper);
                });
            }

            function formatContent(content) {
                if (!content) {
                    return '';
                }

                if (typeof content === 'string') {
                    currentTemplateContent = content;
                    return `<pre class="mb-0">${content}</pre>`;
                }

                if (Array.isArray(content)) {
                    currentTemplateContent = content.map(item => (typeof item === 'string' ? item : JSON.stringify(item))).join('\n');
                    return '<ul class="mb-0 ps-3">' + content.map(item => `<li>${item}</li>`).join('') + '</ul>';
                }

                if (content.body) {
                    currentTemplateContent = content.body;
                    return `<pre class="mb-0">${content.body}</pre>`;
                }

                if (content.structure) {
                    currentTemplateContent = content.structure.join('\n');
                    return '<ol class="mb-0 ps-3">' + content.structure.map(item => `<li>${item}</li>`).join('') + '</ol>';
                }

                if (content.steps) {
                    currentTemplateContent = content.steps.join('\n');
                    return '<ol class="mb-0 ps-3">' + content.steps.map(step => `<li>${step}</li>`).join('') + '</ol>';
                }

                if (content.phrases) {
                    currentTemplateContent = content.phrases.join('\n');
                    return '<ul class="mb-0 ps-3">' + content.phrases.map(item => `<li>${item}</li>`).join('') + '</ul>';
                }

                if (content.ideas) {
                    currentTemplateContent = content.ideas.join('\n');
                    return '<ul class="mb-0 ps-3">' + content.ideas.map(item => `<li>${item}</li>`).join('') + '</ul>';
                }

                if (content.sections) {
                    currentTemplateContent = content.sections.join('\n');
                    return '<ul class="mb-0 ps-3">' + content.sections.map(item => `<li>${item}</li>`).join('') + '</ul>';
                }

                if (content.items) {
                    currentTemplateContent = content.items.join('\n');
                    return '<ul class="mb-0 ps-3">' + content.items.map(item => `<li>${item}</li>`).join('') + '</ul>';
                }

                if (content.slides) {
                    currentTemplateContent = content.slides.map(slide => `${slide.title || ''}: ${slide.text || ''}`).join('\n');
                    return '<div class="d-flex flex-column gap-2">' + content.slides.map(slide => `
                        <div class="border rounded-3 p-2">
                            <strong>${slide.title || ''}</strong>
                            <div class="text-muted small">${slide.text || ''}</div>
                        </div>
                    `).join('') + '</div>';
                }

                currentTemplateContent = JSON.stringify(content);
                return `<pre class="mb-0">${currentTemplateContent}</pre>`;
            }

            function openTemplateModal(title, formattedContent) {
                templateModalLabel.textContent = title;
                templateModalContent.innerHTML = formattedContent || `<p class="text-muted mb-0">${translations.knowledge.templateEmpty}</p>`;
                const modal = bootstrap.Modal.getOrCreateInstance(templateModal);
                modal.show();
            }

            templateModal.addEventListener('hidden.bs.modal', function () {
                currentTemplateContent = '';
            });

            templateCopyButton.addEventListener('click', function () {
                if (!currentTemplateContent) {
                    return;
                }

                navigator.clipboard.writeText(currentTemplateContent).then(function () {
                    templateCopyButton.classList.remove('btn-primary');
                    templateCopyButton.classList.add('btn-success');
                    setTimeout(function () {
                        templateCopyButton.classList.remove('btn-success');
                        templateCopyButton.classList.add('btn-primary');
                    }, 1500);
                });
            });

            function updateTask(taskId, completed) {
                hideAlert(taskAlert);
                fetch(`/api/v1/learning/tasks/${taskId}`, {
                    method: 'PATCH',
                    headers: authHeaders({
                        'Content-Type': 'application/json',
                    }),
                    body: JSON.stringify({ completed }),
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Request failed');
                        }
                        return response.json();
                    })
                    .then(function () {
                        showAlert(taskAlert, translations.alerts.taskSuccess, 'success');
                        loadPlan();
                    })
                    .catch(function () {
                        showAlert(taskAlert, translations.alerts.taskError, 'danger');
                    });
            }

            function loadPlan() {
                fetch('/api/v1/learning/plan', {
                    headers: authHeaders(),
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Failed');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        renderPlan(data || {});
                    })
                    .catch(function () {
                        showAlert(taskAlert, translations.alerts.loadError, 'danger');
                    });
            }

            function loadLessons() {
                const params = new URLSearchParams();
                if (activeCategory) {
                    params.set('category', activeCategory);
                }

                fetch('/api/v1/learning/lessons' + (params.toString() ? `?${params.toString()}` : ''), {
                    headers: authHeaders(),
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Failed');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        renderLessons(data || {});
                    })
                    .catch(function () {
                        lessonsEmpty.classList.remove('d-none');
                        lessonsEmpty.textContent = translations.alerts.loadError;
                    });
            }

            function loadKnowledge(search = '') {
                const params = new URLSearchParams();
                if (search) {
                    params.set('search', search);
                }

                fetch('/api/v1/learning/knowledge' + (params.toString() ? `?${params.toString()}` : ''), {
                    headers: authHeaders(),
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Failed');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        renderKnowledge(data || {});
                    })
                    .catch(function () {
                        articlesEmpty.classList.remove('d-none');
                        articlesEmpty.textContent = translations.alerts.loadError;
                    });
            }

            let searchTimeout = null;
            searchInput.addEventListener('input', function () {
                const value = searchInput.value.trim();
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                searchTimeout = setTimeout(function () {
                    if (value === lastSearch) {
                        return;
                    }
                    lastSearch = value;
                    loadKnowledge(value);
                }, 300);
            });

            loadPlan();
            loadLessons();
            loadKnowledge();
        });
    </script>
@endsection
