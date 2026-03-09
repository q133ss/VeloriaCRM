@extends('layouts.app')

@section('title', __('subscription.title'))

@section('meta')
    <style>
        .subscription-shell {
            display: grid;
            gap: 1.5rem;
        }

        .subscription-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 0, 214, 0.14);
            border-radius: 1.75rem;
            padding: 1.75rem;
            background:
                radial-gradient(circle at top right, rgba(255, 0, 214, 0.18), transparent 28%),
                linear-gradient(135deg, rgba(42, 46, 88, 0.98), rgba(32, 36, 74, 0.96));
            color: #f7f6ff;
        }

        .subscription-hero::after {
            content: '';
            position: absolute;
            inset: auto -6rem -7rem auto;
            width: 16rem;
            height: 16rem;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(255, 0, 214, 0.18), transparent 68%);
            pointer-events: none;
        }

        .subscription-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            color: #ff8cf3;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .subscription-hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.8fr) minmax(300px, 0.95fr);
            gap: 1.25rem;
            margin-top: 1.1rem;
            position: relative;
            z-index: 1;
        }

        .subscription-hero-copy h1,
        .subscription-hero-copy h2,
        .subscription-hero-copy h3,
        .subscription-hero-copy h4,
        .subscription-hero-copy h5 {
            color: #fff;
        }

        .subscription-hero-copy p {
            color: rgba(247, 246, 255, 0.82);
            max-width: 48rem;
        }

        .subscription-status-card,
        .subscription-summary-card,
        .subscription-plan-card,
        .subscription-section-card {
            border-radius: 1.4rem;
            border: 1px solid rgba(130, 143, 255, 0.12);
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 24px 48px -32px rgba(15, 23, 42, 0.35);
        }

        .subscription-status-card {
            padding: 1.2rem;
            background: rgba(14, 18, 46, 0.28);
            border-color: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
        }

        .subscription-status-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.38rem 0.72rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            color: #9fd6ff;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .subscription-status-card h4 {
            margin: 0.9rem 0 0.35rem;
            color: #fff;
        }

        .subscription-status-card p {
            margin-bottom: 0;
            color: rgba(247, 246, 255, 0.78);
        }

        .subscription-hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .subscription-hero-actions .btn {
            white-space: nowrap;
        }

        .subscription-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .subscription-summary-card {
            padding: 1.05rem 1.1rem;
        }

        .subscription-summary-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--bs-gray-600);
            margin-bottom: 0.45rem;
        }

        .subscription-summary-value {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--bs-heading-color);
        }

        .subscription-summary-note {
            margin-top: 0.35rem;
            font-size: 0.9rem;
            color: var(--bs-gray-600);
        }

        .subscription-section-card {
            padding: 1.35rem;
        }

        .subscription-section-head {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .subscription-section-head p {
            margin: 0.35rem 0 0;
            color: var(--bs-gray-600);
        }

        .subscription-plans-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .subscription-plan-card {
            position: relative;
            overflow: hidden;
            padding: 1.35rem;
            display: flex;
            flex-direction: column;
            min-height: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .subscription-plan-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 28px 52px -34px rgba(91, 33, 182, 0.4);
            border-color: rgba(255, 0, 214, 0.22);
        }

        .subscription-plan-card.is-recommended {
            border-color: rgba(255, 0, 214, 0.28);
            box-shadow: 0 28px 56px -36px rgba(255, 0, 214, 0.42);
        }

        .subscription-plan-card.is-current {
            border-color: rgba(26, 188, 156, 0.28);
            box-shadow: 0 28px 56px -36px rgba(26, 188, 156, 0.35);
        }

        .subscription-plan-card::before {
            content: '';
            position: absolute;
            inset: 0 0 auto;
            height: 0.32rem;
            background: linear-gradient(90deg, rgba(255, 0, 214, 0.95), rgba(111, 76, 255, 0.9));
            opacity: 0;
        }

        .subscription-plan-card.is-recommended::before,
        .subscription-plan-card.is-current::before {
            opacity: 1;
        }

        .subscription-plan-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 0.9rem;
        }

        .subscription-plan-price {
            display: flex;
            align-items: baseline;
            gap: 0.45rem;
            margin: 1rem 0 0.35rem;
        }

        .subscription-plan-price strong {
            font-size: 2rem;
            line-height: 1;
            color: var(--bs-heading-color);
        }

        .subscription-plan-features {
            list-style: none;
            padding: 0;
            margin: 1rem 0 1.25rem;
            display: grid;
            gap: 0.7rem;
        }

        .subscription-plan-features li {
            display: flex;
            gap: 0.7rem;
            align-items: flex-start;
            color: var(--bs-body-color);
        }

        .subscription-plan-features i {
            color: #16a34a;
            margin-top: 0.15rem;
        }

        .subscription-plan-footer {
            margin-top: auto;
        }

        .subscription-comparison-grid {
            display: grid;
            gap: 0.85rem;
        }

        .subscription-comparison-item {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) repeat(3, minmax(90px, 0.35fr));
            gap: 0.85rem;
            align-items: center;
            padding: 1rem 1.05rem;
            border: 1px solid rgba(130, 143, 255, 0.12);
            border-radius: 1rem;
            background: rgba(112, 126, 255, 0.04);
        }

        .subscription-comparison-plans {
            display: contents;
        }

        .subscription-comparison-plan {
            text-align: center;
            font-weight: 600;
        }

        .subscription-comparison-value {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 2.2rem;
            min-width: 4.8rem;
            border-radius: 999px;
            padding: 0.35rem 0.75rem;
            background: rgba(112, 126, 255, 0.08);
            color: var(--bs-body-color);
            font-size: 0.82rem;
            font-weight: 700;
        }

        .subscription-comparison-value.is-yes {
            background: rgba(22, 163, 74, 0.12);
            color: #15803d;
        }

        .subscription-comparison-value.is-no {
            background: rgba(148, 163, 184, 0.12);
            color: var(--bs-gray-600);
        }

        .subscription-empty-note {
            border: 1px dashed rgba(130, 143, 255, 0.22);
            border-radius: 1rem;
            padding: 1.1rem;
            color: var(--bs-gray-600);
            background: rgba(112, 126, 255, 0.03);
        }

        .subscription-manage-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .subscription-manage-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 0.7rem;
        }

        .subscription-manage-list li {
            display: flex;
            gap: 0.7rem;
            align-items: flex-start;
        }

        .subscription-manage-list i {
            margin-top: 0.2rem;
        }

        .subscription-transactions-table {
            margin-bottom: 0;
        }

        html[data-bs-theme="dark"] .subscription-summary-card,
        html[data-bs-theme="dark"] .subscription-plan-card,
        html[data-bs-theme="dark"] .subscription-section-card {
            background: linear-gradient(180deg, rgba(39, 44, 87, 0.96), rgba(30, 34, 69, 0.98));
            border-color: rgba(157, 167, 255, 0.14);
            box-shadow: 0 28px 56px -36px rgba(0, 0, 0, 0.55);
        }

        html[data-bs-theme="dark"] .subscription-summary-label,
        html[data-bs-theme="dark"] .subscription-summary-note,
        html[data-bs-theme="dark"] .subscription-section-head p,
        html[data-bs-theme="dark"] .subscription-plan-card p.text-muted,
        html[data-bs-theme="dark"] .subscription-comparison-item .text-muted,
        html[data-bs-theme="dark"] .subscription-empty-note,
        html[data-bs-theme="dark"] .subscription-manage-list li span,
        html[data-bs-theme="dark"] .subscription-transactions-table td,
        html[data-bs-theme="dark"] .subscription-transactions-table th {
            color: rgba(225, 229, 255, 0.72) !important;
        }

        html[data-bs-theme="dark"] .subscription-summary-value,
        html[data-bs-theme="dark"] .subscription-plan-price strong,
        html[data-bs-theme="dark"] .subscription-plan-card h5,
        html[data-bs-theme="dark"] .subscription-section-card h4,
        html[data-bs-theme="dark"] .subscription-comparison-item .fw-semibold,
        html[data-bs-theme="dark"] .subscription-empty-note .fw-semibold {
            color: #f6f7ff !important;
        }

        html[data-bs-theme="dark"] .subscription-plan-features li {
            color: rgba(240, 242, 255, 0.86);
        }

        html[data-bs-theme="dark"] .subscription-comparison-item,
        html[data-bs-theme="dark"] .subscription-empty-note {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(157, 167, 255, 0.14);
        }

        html[data-bs-theme="dark"] .subscription-comparison-value {
            background: rgba(255, 255, 255, 0.06);
            color: #eef1ff;
        }

        html[data-bs-theme="dark"] .subscription-comparison-value.is-no {
            background: rgba(148, 163, 184, 0.14);
            color: rgba(225, 229, 255, 0.72);
        }

        html[data-bs-theme="dark"] .subscription-plan-card .badge.bg-label-secondary {
            background: rgba(255, 255, 255, 0.08) !important;
            color: rgba(241, 243, 255, 0.88) !important;
        }

        html[data-bs-theme="dark"] .subscription-plan-card .badge.bg-label-success {
            background: rgba(34, 197, 94, 0.18) !important;
            color: #8ef0a7 !important;
        }

        html[data-bs-theme="dark"] .subscription-plan-card .badge.bg-label-primary {
            background: rgba(255, 0, 214, 0.18) !important;
            color: #ff8cf3 !important;
        }

        html[data-bs-theme="dark"] .subscription-hero-actions .btn-outline-light {
            border-color: rgba(255, 255, 255, 0.18);
            color: #eef1ff;
            background: rgba(255, 255, 255, 0.04);
        }

        html[data-bs-theme="dark"] .subscription-hero-actions .btn-outline-light:hover {
            border-color: rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        html[data-bs-theme="dark"] .subscription-transactions-table code {
            color: #ffd8fb;
        }

        @media (max-width: 1199.98px) {
            .subscription-hero-grid,
            .subscription-plans-grid,
            .subscription-manage-grid {
                grid-template-columns: 1fr;
            }

            .subscription-comparison-item {
                grid-template-columns: 1fr;
            }

            .subscription-comparison-plans {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 0.75rem;
            }
        }

        @media (max-width: 767.98px) {
            .subscription-hero,
            .subscription-section-card {
                padding: 1rem;
                border-radius: 1.1rem;
            }

            .subscription-summary-grid,
            .subscription-comparison-plans {
                grid-template-columns: 1fr;
            }

            .subscription-plan-price strong {
                font-size: 1.65rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="subscription-shell">
        <div class="alert alert-dismissible d-none" role="alert" data-subscription-feedback>
            <span data-feedback-text></span>
            <button type="button" class="btn-close" data-feedback-close aria-label="Close"></button>
        </div>

        <section class="subscription-hero">
            <span class="subscription-hero-badge">
                <i class="ri-vip-crown-2-line"></i>
                Veloria Plans
            </span>

            <div class="subscription-hero-grid">
                <div class="subscription-hero-copy">
                    <h3 class="mb-3">Выберите тариф под ваш темп роста</h3>
                    <p class="mb-0">
                        Начните с базового набора инструментов или подключите автоматизацию, аналитику и ИИ,
                        когда захотите расти быстрее без ручной рутины.
                    </p>

                    <div class="subscription-hero-actions">
                        <a href="#subscription-plans" class="btn btn-primary">Выбрать тариф</a>
                        <a href="#subscription-comparison" class="btn btn-outline-light">Сравнить возможности</a>
                    </div>
                </div>

                <div class="subscription-status-card">
                    <span class="subscription-status-chip">
                        <i class="ri-flashlight-line"></i>
                        Состояние аккаунта
                    </span>
                    <h4 data-hero-status-title>Подписка ещё не оформлена</h4>
                    <p data-hero-status-caption>Можно начать с Lite и перейти на более сильный тариф позже.</p>
                    <div class="mt-3 small text-white-50" data-hero-status-note>
                        Данные клиентов и история работы сохраняются при любом переходе между тарифами.
                    </div>
                </div>
            </div>
        </section>

        <section class="subscription-summary-grid">
            <div class="subscription-summary-card">
                <div class="subscription-summary-label">Текущий план</div>
                <div class="subscription-summary-value" data-summary-current-plan>—</div>
                <div class="subscription-summary-note" data-summary-current-note>Подберите тариф под ваши задачи.</div>
            </div>
            <div class="subscription-summary-card">
                <div class="subscription-summary-label">Рекомендуем начать</div>
                <div class="subscription-summary-value" data-summary-recommended-plan>Pro</div>
                <div class="subscription-summary-note" data-summary-recommended-note>Оптимальный баланс роста и автоматизации.</div>
            </div>
            <div class="subscription-summary-card">
                <div class="subscription-summary-label">История оплат</div>
                <div class="subscription-summary-value" data-summary-transactions-count>0</div>
                <div class="subscription-summary-note" data-summary-transactions-note>Покажем её, когда появятся первые платежи.</div>
            </div>
        </section>

        <section class="subscription-section-card" id="subscription-plans">
            <div class="subscription-section-head">
                <div>
                    <h4 class="mb-1">Тарифы Veloria</h4>
                    <p>Сначала выберите нужный объём инструментов, а платежи и управление подключением остаются вторым шагом.</p>
                </div>
                <div class="small text-muted text-md-end" data-payment-hint></div>
            </div>

            <div class="subscription-plans-grid" data-plans-container></div>
        </section>

        <section class="subscription-section-card" id="subscription-comparison">
            <div class="subscription-section-head">
                <div>
                    <h4 class="mb-1">Что входит в каждый тариф</h4>
                    <p>Короткое сравнение по главным возможностям, без тяжёлой таблицы на весь экран.</p>
                </div>
            </div>

            <div class="subscription-comparison-grid" data-comparison-list></div>
        </section>

        <section class="subscription-section-card d-none" data-manage-section>
            <div class="subscription-section-head">
                <div>
                    <h4 class="mb-1">Управление подпиской</h4>
                    <p>Если отключить платный тариф, все данные, клиенты, записи и история останутся в аккаунте. Отключатся только платные возможности.</p>
                </div>
            </div>

            <div class="subscription-manage-grid">
                <div class="subscription-empty-note">
                    <div class="fw-semibold mb-2">Все сохранится в аккаунте</div>
                    <div class="small mb-3">Карточки клиентов, записи, расписание и история работы никуда не исчезнут. Вы сможете вернуться на платный тариф позже.</div>
                    <ul class="subscription-manage-list">
                        @foreach (__('subscription.cancel.keep') as $item)
                            <li>
                                <i class="ri-check-line text-success"></i>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="subscription-empty-note">
                    <div class="fw-semibold mb-2">Что станет недоступно</div>
                    <ul class="subscription-manage-list">
                        @foreach (__('subscription.cancel.lose') as $item)
                            <li>
                                <i class="ri-close-line text-danger"></i>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <button type="button" class="btn btn-outline-danger w-100 mt-3" data-cancel-button disabled>
                        {{ __('subscription.actions.cancel') }}
                    </button>
                    <div class="small text-muted mt-2">Подписку можно будет включить снова в любой момент без потери данных.</div>
                </div>
            </div>
        </section>

        <section class="subscription-section-card d-none" data-transactions-section>
            <div class="subscription-section-head">
                <div>
                    <h4 class="mb-1">{{ __('subscription.transactions.title') }}</h4>
                    <p>Показываем только реальные оплаты, чтобы экран не захламлялся пустым блоком.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle subscription-transactions-table">
                    <thead>
                        <tr>
                            <th>{{ __('subscription.transactions.date') }}</th>
                            <th>{{ __('subscription.transactions.plan') }}</th>
                            <th>{{ __('subscription.transactions.amount') }}</th>
                            <th>{{ __('subscription.transactions.status') }}</th>
                            <th>{{ __('subscription.transactions.payment_id') }}</th>
                        </tr>
                    </thead>
                    <tbody data-transactions-body></tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const STRINGS = {
                billingPeriod: @json(__('subscription.billing_period')),
                actions: @json(__('subscription.actions')),
                currentPlan: {
                    noPlan: @json(__('subscription.current_plan.no_plan')),
                    freePlan: @json(__('subscription.current_plan.free_plan')),
                    activeUntil: @json(__('subscription.current_plan.active_until', ['date' => ':date'])),
                },
                alerts: {
                    upgradeError: @json(__('subscription.alerts.upgrade_error')),
                },
                transactions: {
                    empty: @json(__('subscription.transactions.empty')),
                },
            };

            const feedback = document.querySelector('[data-subscription-feedback]');
            const feedbackText = document.querySelector('[data-feedback-text]');
            const feedbackClose = document.querySelector('[data-feedback-close]');
            const cancelButton = document.querySelector('[data-cancel-button]');
            const plansContainer = document.querySelector('[data-plans-container]');
            const comparisonList = document.querySelector('[data-comparison-list]');
            const transactionsSection = document.querySelector('[data-transactions-section]');
            const transactionsBody = document.querySelector('[data-transactions-body]');
            const manageSection = document.querySelector('[data-manage-section]');
            const paymentHint = document.querySelector('[data-payment-hint]');

            const heroStatusTitle = document.querySelector('[data-hero-status-title]');
            const heroStatusCaption = document.querySelector('[data-hero-status-caption]');
            const heroStatusNote = document.querySelector('[data-hero-status-note]');
            const summaryCurrentPlan = document.querySelector('[data-summary-current-plan]');
            const summaryCurrentNote = document.querySelector('[data-summary-current-note]');
            const summaryRecommendedPlan = document.querySelector('[data-summary-recommended-plan]');
            const summaryRecommendedNote = document.querySelector('[data-summary-recommended-note]');
            const summaryTransactionsCount = document.querySelector('[data-summary-transactions-count]');
            const summaryTransactionsNote = document.querySelector('[data-summary-transactions-note]');

            let latestSubscriptionData = null;

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function getCookie(name) {
                const match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.$?*|{}()\[\]\\\/\+^]/g, '\\$&') + '=([^;]*)'));
                return match ? decodeURIComponent(match[1]) : null;
            }

            const baseHeaders = {
                'Accept': 'application/json',
                'Accept-Language': document.documentElement.lang || 'en',
                'X-Requested-With': 'XMLHttpRequest',
            };

            const token = getCookie('token');
            if (token) {
                baseHeaders['Authorization'] = 'Bearer ' + token;
            }

            function hideFeedback() {
                if (!feedback) {
                    return;
                }

                feedback.classList.add('d-none');
                feedback.classList.remove('alert-success', 'alert-danger');
                if (feedbackText) {
                    feedbackText.textContent = '';
                }
            }

            function showFeedback(type, message) {
                if (!feedback || !feedbackText) {
                    return;
                }

                feedback.classList.remove('d-none', 'alert-success', 'alert-danger');
                feedback.classList.add(type === 'error' ? 'alert-danger' : 'alert-success');
                feedbackText.textContent = message;
            }

            if (feedbackClose) {
                feedbackClose.addEventListener('click', hideFeedback);
            }

            function apiFetch(url, options = {}) {
                const config = {
                    method: options.method || 'GET',
                    headers: Object.assign({}, baseHeaders, options.headers || {}),
                };

                if (options.body !== undefined) {
                    if (options.body instanceof FormData) {
                        config.body = options.body;
                    } else {
                        config.headers['Content-Type'] = 'application/json';
                        config.body = JSON.stringify(options.body);
                    }
                }

                return fetch(url, config).then(async function (response) {
                    if (response.status === 401) {
                        window.location.href = '/login';
                        throw new Error('Unauthorized');
                    }

                    if (response.status === 204) {
                        return {};
                    }

                    let data = {};
                    try {
                        data = await response.json();
                    } catch (error) {
                        data = {};
                    }

                    if (!response.ok) {
                        const error = new Error(data.message || STRINGS.alerts.upgradeError);
                        error.data = data;
                        error.status = response.status;
                        throw error;
                    }

                    return data;
                });
            }

            function renderHero(data) {
                const ui = data.ui || {};
                const currentPlan = data.current_plan || null;

                if (heroStatusTitle) {
                    heroStatusTitle.textContent = ui.status_title || STRINGS.currentPlan.noPlan;
                }

                if (heroStatusCaption) {
                    heroStatusCaption.textContent = ui.status_caption || 'Можно начать с Lite и перейти на более сильный тариф позже.';
                }

                if (heroStatusNote) {
                    if (ui.can_cancel) {
                        heroStatusNote.textContent = 'Платный тариф активен. Управление отменой находится ниже и не мешает выбору плана.';
                    } else if (currentPlan && currentPlan.is_free) {
                        heroStatusNote.textContent = 'Вы уже на бесплатном тарифе. Платные функции можно подключить в любой момент.';
                    } else {
                        heroStatusNote.textContent = 'Данные клиентов и история работы сохраняются при любом переходе между тарифами.';
                    }
                }

                if (summaryCurrentPlan) {
                    summaryCurrentPlan.textContent = currentPlan ? (currentPlan.name || '—') : 'Без подписки';
                }

                if (summaryCurrentNote) {
                    if (currentPlan && currentPlan.active_until_label) {
                        summaryCurrentNote.textContent = currentPlan.active_until_label;
                    } else if (currentPlan && currentPlan.is_free) {
                        summaryCurrentNote.textContent = STRINGS.currentPlan.freePlan;
                    } else {
                        summaryCurrentNote.textContent = 'Подберите тариф под ваши задачи.';
                    }
                }

                if (summaryRecommendedPlan) {
                    summaryRecommendedPlan.textContent = ui.recommended_plan_name || 'Pro';
                }

                if (summaryRecommendedNote) {
                    summaryRecommendedNote.textContent = ui.recommended_plan_slug === 'elite'
                        ? 'Подойдёт, если уже нужны ИИ и максимум автоматизации.'
                        : 'Оптимальный баланс роста, маркетинга и автоматизации.';
                }

                if (summaryTransactionsCount) {
                    summaryTransactionsCount.textContent = String(ui.transactions_count || 0);
                }

                if (summaryTransactionsNote) {
                    summaryTransactionsNote.textContent = ui.has_transactions
                        ? 'Показываем только последние реальные оплаты.'
                        : 'Покажем её, когда появятся первые платежи.';
                }

                if (paymentHint) {
                    paymentHint.textContent = data.yookassa && data.yookassa.enabled
                        ? 'Оплата подключена: переход на платный тариф доступен сразу.'
                        : 'Оплата пока не настроена. Бесплатный тариф доступен сразу, платные можно подключить позже.';
                }
            }

            function renderPlans(data) {
                if (!plansContainer) {
                    return;
                }

                plansContainer.innerHTML = '';

                (data.plans || []).forEach(function (plan) {
                    const card = document.createElement('article');
                    const cardClasses = ['subscription-plan-card'];
                    if (plan.is_current) {
                        cardClasses.push('is-current');
                    }
                    if (plan.is_recommended && !plan.is_current) {
                        cardClasses.push('is-recommended');
                    }

                    let actionButton = '';
                    if (plan.is_current) {
                        actionButton = '<button class="btn btn-outline-secondary w-100" disabled>' + escapeHtml(STRINGS.actions.current) + '</button>';
                    } else if (plan.is_upgrade && (plan.is_free || (data.yookassa && data.yookassa.enabled))) {
                        actionButton = '<button class="btn btn-primary w-100" data-upgrade-button data-plan-id="' + plan.id + '">' + escapeHtml(STRINGS.actions.upgrade.replace(':plan', plan.name)) + '</button>';
                    } else {
                        actionButton = '<button class="btn btn-outline-secondary w-100" disabled>' + escapeHtml(STRINGS.actions.upgrade.replace(':plan', plan.name)) + '</button>';
                    }

                    const badges = [];
                    if (plan.is_current) {
                        badges.push('<span class="badge bg-label-success">Активен</span>');
                    } else if (plan.is_recommended) {
                        badges.push('<span class="badge bg-label-primary">Рекомендуем</span>');
                    }

                    if (plan.badge) {
                        badges.push('<span class="badge bg-label-secondary">' + escapeHtml(plan.badge) + '</span>');
                    }

                    const priceHtml = plan.price_display
                        ? '<div class="subscription-plan-price"><strong>' + escapeHtml(plan.price_display) + '</strong><span class="text-muted">' + escapeHtml(STRINGS.billingPeriod) + '</span></div>'
                        : '<div class="subscription-plan-price"><strong>0 ' + escapeHtml(data.meta ? data.meta.currency : '₽') + '</strong><span class="text-muted">' + escapeHtml(STRINGS.billingPeriod) + '</span></div>';

                    const features = (plan.features || []).map(function (feature) {
                        return '<li><i class="ri-check-line"></i><span>' + escapeHtml(feature) + '</span></li>';
                    }).join('');

                    card.className = cardClasses.join(' ');
                    card.innerHTML = ''
                        + '<div class="subscription-plan-badges">' + badges.join('') + '</div>'
                        + '<h5 class="mb-1">' + escapeHtml(plan.name || '') + '</h5>'
                        + '<p class="text-muted mb-0">' + escapeHtml(plan.tagline || '') + '</p>'
                        + priceHtml
                        + '<p class="text-muted mt-3 mb-0">' + escapeHtml(plan.description || '') + '</p>'
                        + '<ul class="subscription-plan-features">' + features + '</ul>'
                        + '<div class="subscription-plan-footer">' + actionButton + '</div>';

                    plansContainer.appendChild(card);
                });

                plansContainer.querySelectorAll('[data-upgrade-button]').forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.preventDefault();
                        const planId = parseInt(button.getAttribute('data-plan-id'), 10);
                        if (!planId || button.disabled) {
                            return;
                        }

                        button.disabled = true;

                        apiFetch('/api/v1/subscription/upgrade', {
                            method: 'POST',
                            body: { plan_id: planId },
                        })
                            .then(function (response) {
                                if (response.redirect_url) {
                                    window.location.href = response.redirect_url;
                                    return;
                                }

                                if (response.message) {
                                    showFeedback('success', response.message);
                                }

                                return loadSubscription();
                            })
                            .catch(function (error) {
                                showFeedback('error', error.message || STRINGS.alerts.upgradeError);
                            })
                            .finally(function () {
                                button.disabled = false;
                            });
                    });
                });
            }

            function renderComparison(data) {
                if (!comparisonList) {
                    return;
                }

                const plans = data.plans || [];
                const comparison = data.comparison || [];

                comparisonList.innerHTML = comparison.map(function (row) {
                    const cells = plans.map(function (plan) {
                        const value = row.plans ? row.plans[plan.slug] : null;

                        let content = '—';
                        let className = 'subscription-comparison-value';

                        if (value === true) {
                            content = 'Есть';
                            className += ' is-yes';
                        } else if (value === false) {
                            content = 'Нет';
                            className += ' is-no';
                        } else if (value !== null && value !== undefined) {
                            content = escapeHtml(value);
                        }

                        return ''
                            + '<div class="subscription-comparison-plan">'
                            + '<div class="small text-muted mb-2">' + escapeHtml(plan.name || '') + '</div>'
                            + '<span class="' + className + '">' + content + '</span>'
                            + '</div>';
                    }).join('');

                    return ''
                        + '<article class="subscription-comparison-item">'
                        + '<div>'
                        + '<div class="fw-semibold">' + escapeHtml(row.feature || '') + '</div>'
                        + '<div class="small text-muted mt-1">' + escapeHtml(row.description || '') + '</div>'
                        + '</div>'
                        + '<div class="subscription-comparison-plans">' + cells + '</div>'
                        + '</article>';
                }).join('');
            }

            function renderManageSection(data) {
                if (!manageSection || !cancelButton) {
                    return;
                }

                const canCancel = !!(data.ui && data.ui.can_cancel);
                manageSection.classList.toggle('d-none', !canCancel);
                cancelButton.disabled = !canCancel;
            }

            function renderTransactions(data) {
                if (!transactionsSection || !transactionsBody) {
                    return;
                }

                const transactions = data.transactions || [];
                const hasTransactions = !!(data.ui && data.ui.has_transactions);

                transactionsSection.classList.toggle('d-none', !hasTransactions);

                if (!hasTransactions) {
                    transactionsBody.innerHTML = '';
                    return;
                }

                transactionsBody.innerHTML = transactions.map(function (transaction) {
                    const paymentId = transaction.payment_id ? transaction.payment_id : '—';
                    const statusBadge = transaction.status_badge || 'bg-label-secondary';
                    const statusLabel = transaction.status_label || '';

                    return ''
                        + '<tr>'
                        + '<td class="text-nowrap">' + escapeHtml(transaction.created_at_formatted || '') + '</td>'
                        + '<td>' + escapeHtml(transaction.plan_name || '') + '</td>'
                        + '<td>' + escapeHtml(transaction.amount_display || '') + '</td>'
                        + '<td><span class="badge ' + escapeHtml(statusBadge) + '">' + escapeHtml(statusLabel) + '</span></td>'
                        + '<td class="text-break"><code>' + escapeHtml(paymentId) + '</code></td>'
                        + '</tr>';
                }).join('');
            }

            function loadSubscription() {
                return apiFetch('/api/v1/subscription')
                    .then(function (data) {
                        latestSubscriptionData = data;
                        renderHero(data);
                        renderPlans(data);
                        renderComparison(data);
                        renderManageSection(data);
                        renderTransactions(data);
                    })
                    .catch(function (error) {
                        showFeedback('error', error.message || STRINGS.alerts.upgradeError);
                    });
            }

            if (cancelButton) {
                cancelButton.addEventListener('click', function (event) {
                    event.preventDefault();

                    if (cancelButton.disabled) {
                        return;
                    }

                    cancelButton.disabled = true;

                    apiFetch('/api/v1/subscription/cancel', {
                        method: 'POST',
                    })
                        .then(function (response) {
                            if (response.message) {
                                showFeedback('success', response.message);
                            }

                            return loadSubscription();
                        })
                        .catch(function (error) {
                            showFeedback('error', error.message || STRINGS.alerts.upgradeError);
                        })
                        .finally(function () {
                            if (!(latestSubscriptionData && latestSubscriptionData.ui && latestSubscriptionData.ui.can_cancel)) {
                                cancelButton.disabled = true;
                            }
                        });
                });
            }

            loadSubscription();
        });
    </script>
@endsection
