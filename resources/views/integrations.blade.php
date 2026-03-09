@extends('layouts.app')

@php
    $integrationStatusText = [
        'ready' => __('integrations.status_ready'),
        'partial' => __('integrations.status_partial'),
        'empty' => __('integrations.status_empty'),
        'summary_empty' => __('integrations.summary_empty'),
        'summary_pattern' => __('integrations.summary_pattern'),
        'saved' => __('integrations.saved'),
        'load_error' => __('integrations.load_error'),
    ];
@endphp

@section('content')
    <style>
        .integrations-hero {
            border: 1px solid rgba(var(--bs-primary-rgb), 0.18);
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.18), transparent 28%),
                linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08), rgba(var(--bs-body-bg-rgb), 0.02));
        }

        .integration-overview-card {
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
            transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .integration-overview-card:hover {
            border-color: rgba(var(--bs-primary-rgb), 0.28);
            transform: translateY(-2px);
            box-shadow: 0 1rem 2.5rem rgba(15, 23, 42, 0.12);
        }

        .integration-overview-card.is-active {
            border-color: rgba(var(--bs-primary-rgb), 0.4);
            box-shadow: 0 1rem 2.5rem rgba(var(--bs-primary-rgb), 0.12);
        }

        .integration-overview-icon {
            width: 3rem;
            height: 3rem;
        }

        .integration-panel {
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
            border-radius: 1.25rem;
            background: rgba(var(--bs-body-bg-rgb), 0.18);
            overflow: hidden;
        }

        .integration-panel + .integration-panel {
            margin-top: 1rem;
        }

        .integration-panel summary {
            list-style: none;
            cursor: pointer;
        }

        .integration-panel summary::-webkit-details-marker {
            display: none;
        }

        .integration-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
        }

        .integration-panel-body {
            padding: 0 1.5rem 1.5rem;
            border-top: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
        }

        .integration-panel-chevron {
            transition: transform 0.2s ease;
        }

        .integration-panel[open] .integration-panel-chevron {
            transform: rotate(180deg);
        }

        .integration-helper-card {
            position: sticky;
            top: 1.5rem;
        }

        .integration-note-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.45rem 0.7rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb), 0.08);
            color: var(--bs-body-color);
            font-size: 0.8125rem;
            font-weight: 600;
        }
    </style>

    <div class="row g-6">
        <div class="col-12">
            <div class="card border-0 shadow-sm integrations-hero">
                <div class="card-body p-6 p-lg-8">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-5">
                        <div class="mw-lg-50">
                            <span class="badge bg-label-primary mb-3">{{ __('integrations.eyebrow') }}</span>
                            <h3 class="mb-2">{{ __('integrations.title') }}</h3>
                            <p class="text-muted mb-3">{{ __('integrations.description') }}</p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="integration-note-chip">
                                    <i class="icon-base ri ri-lock-2-line"></i>
                                    {{ __('integrations.security_note') }}
                                </span>
                            </div>
                        </div>
                        <div class="rounded-4 bg-label-primary px-4 py-3">
                            <div class="text-primary small text-uppercase fw-semibold mb-1">{{ __('integrations.summary_label') }}</div>
                            <div class="fs-5 fw-semibold" id="integrations-summary">{{ __('integrations.summary_empty') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div id="form-messages"></div>
        </div>

        <div class="col-12">
            <form id="integrations-form" onsubmit="return false">
                <div class="row g-6">
                    <div class="col-12">
                        <div class="row g-4">
                            <div class="col-md-6 col-xl">
                                <div class="card h-100 integration-overview-card" data-provider-card="smsaero">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-label-primary text-primary integration-overview-icon">
                                                <i class="icon-base ri ri-message-2-line fs-4"></i>
                                            </div>
                                            <span class="badge rounded-pill bg-label-secondary integration-status" data-provider="smsaero"></span>
                                        </div>
                                        <h5 class="mb-1">{{ __('integrations.sections.smsaero.title') }}</h5>
                                        <p class="text-muted mb-3 small">{{ __('integrations.sections.smsaero.description') }}</p>
                                        <div class="small text-muted mb-4 integration-summary-text" data-provider-summary="smsaero">2 поля для подключения</div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-open-provider="smsaero">Настроить</button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-xl">
                                <div class="card h-100 integration-overview-card" data-provider-card="smtp">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-label-info text-info integration-overview-icon">
                                                <i class="icon-base ri ri-mail-send-line fs-4"></i>
                                            </div>
                                            <span class="badge rounded-pill bg-label-secondary integration-status" data-provider="smtp"></span>
                                        </div>
                                        <h5 class="mb-1">{{ __('integrations.sections.smtp.title') }}</h5>
                                        <p class="text-muted mb-3 small">{{ __('integrations.sections.smtp.description') }}</p>
                                        <div class="small text-muted mb-4 integration-summary-text" data-provider-summary="smtp">Быстрая почтовая настройка</div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-open-provider="smtp">Настроить</button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-xl">
                                <div class="card h-100 integration-overview-card" data-provider-card="whatsapp">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-label-success text-success integration-overview-icon">
                                                <i class="icon-base ri ri-whatsapp-line fs-4"></i>
                                            </div>
                                            <span class="badge rounded-pill bg-label-secondary integration-status" data-provider="whatsapp"></span>
                                        </div>
                                        <h5 class="mb-1">{{ __('integrations.sections.whatsapp.title') }}</h5>
                                        <p class="text-muted mb-3 small">{{ __('integrations.sections.whatsapp.description') }}</p>
                                        <div class="small text-muted mb-4 integration-summary-text" data-provider-summary="whatsapp">API-ключ и отправитель</div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-open-provider="whatsapp">Настроить</button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-xl">
                                <div class="card h-100 integration-overview-card" data-provider-card="telegram">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-label-warning text-warning integration-overview-icon">
                                                <i class="icon-base ri ri-telegram-2-line fs-4"></i>
                                            </div>
                                            <span class="badge rounded-pill bg-label-secondary integration-status" data-provider="telegram"></span>
                                        </div>
                                        <h5 class="mb-1">{{ __('integrations.sections.telegram.title') }}</h5>
                                        <p class="text-muted mb-3 small">{{ __('integrations.sections.telegram.description') }}</p>
                                        <div class="small text-muted mb-4 integration-summary-text" data-provider-summary="telegram">Токен и имя отправителя</div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-open-provider="telegram">Настроить</button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-xl">
                                <div class="card h-100 integration-overview-card" data-provider-card="yookassa">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-label-danger text-danger integration-overview-icon">
                                                <i class="icon-base ri ri-bank-card-line fs-4"></i>
                                            </div>
                                            <span class="badge rounded-pill bg-label-secondary integration-status" data-provider="yookassa"></span>
                                        </div>
                                        <h5 class="mb-1">{{ __('integrations.sections.yookassa.title') }}</h5>
                                        <p class="text-muted mb-3 small">{{ __('integrations.sections.yookassa.description') }}</p>
                                        <div class="small text-muted mb-4 integration-summary-text" data-provider-summary="yookassa">Подключение оплаты для клиентов</div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-open-provider="yookassa">Настроить</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-5 p-lg-6">
                                <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-5">
                                    <div>
                                        <h4 class="mb-1">Подключение каналов</h4>
                                        <p class="text-muted mb-0">Откройте только нужную интеграцию. Остальные настройки можно оставить скрытыми.</p>
                                    </div>
                                    <span class="integration-note-chip">
                                        <i class="icon-base ri ri-sparkling-2-line"></i>
                                        Сначала подключают Telegram или SMS, остальное по необходимости
                                    </span>
                                </div>

                                <details class="integration-panel" data-provider-panel="smsaero">
                                    <summary class="integration-panel-header">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-label-primary text-primary integration-overview-icon">
                                                <i class="icon-base ri ri-message-2-line fs-4"></i>
                                            </div>
                                            <div>
                                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                                    <h5 class="mb-0">{{ __('integrations.sections.smsaero.title') }}</h5>
                                                    <span class="badge rounded-pill bg-label-secondary integration-status" data-provider="smsaero"></span>
                                                </div>
                                                <p class="text-muted mb-0">{{ __('integrations.sections.smsaero.description') }}</p>
                                            </div>
                                        </div>
                                        <i class="icon-base ri ri-arrow-down-s-line fs-4 integration-panel-chevron"></i>
                                    </summary>
                                    <div class="integration-panel-body">
                                        <div class="row g-4 pt-4">
                                            <div class="col-12">
                                                <div class="form-floating form-floating-outline">
                                                    <input type="email" class="form-control" id="smsaero_email" name="integrations[smsaero][email]" />
                                                    <label for="smsaero_email">{{ __('settings.smsaero_email') }}</label>
                                                </div>
                                                <small class="text-muted d-block mt-2">{{ __('integrations.sections.smsaero.email_hint') }}</small>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-floating form-floating-outline">
                                                    <input type="text" class="form-control" id="smsaero_api_key" name="integrations[smsaero][api_key]" />
                                                    <label for="smsaero_api_key">{{ __('settings.smsaero_api_key') }}</label>
                                                </div>
                                                <small class="text-muted d-block mt-2">{{ __('integrations.sections.smsaero.api_key_hint') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </details>

                                <details class="integration-panel" data-provider-panel="smtp">
                                    <summary class="integration-panel-header">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-label-info text-info integration-overview-icon">
                                                <i class="icon-base ri ri-mail-send-line fs-4"></i>
                                            </div>
                                            <div>
                                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                                    <h5 class="mb-0">{{ __('integrations.sections.smtp.title') }}</h5>
                                                    <span class="badge rounded-pill bg-label-secondary integration-status" data-provider="smtp"></span>
                                                </div>
                                                <p class="text-muted mb-0">{{ __('integrations.sections.smtp.description') }}</p>
                                            </div>
                                        </div>
                                        <i class="icon-base ri ri-arrow-down-s-line fs-4 integration-panel-chevron"></i>
                                    </summary>
                                    <div class="integration-panel-body">
                                        <div class="row g-4 pt-4">
                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                    <input type="text" class="form-control" id="smtp_host" name="integrations[smtp][host]" />
                                                    <label for="smtp_host">{{ __('settings.smtp_host') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-floating form-floating-outline">
                                                    <input type="number" class="form-control" id="smtp_port" name="integrations[smtp][port]" min="1" max="65535" />
                                                    <label for="smtp_port">{{ __('settings.smtp_port') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-floating form-floating-outline">
                                                    <select class="form-select" id="smtp_encryption" name="integrations[smtp][encryption]">
                                                        <option value="">-</option>
                                                        <option value="tls">TLS</option>
                                                        <option value="ssl">SSL</option>
                                                        <option value="starttls">STARTTLS</option>
                                                        <option value="none">{{ __('settings.smtp_encryption_none') }}</option>
                                                    </select>
                                                    <label for="smtp_encryption">{{ __('settings.smtp_encryption') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                    <input type="text" class="form-control" id="smtp_username" name="integrations[smtp][username]" />
                                                    <label for="smtp_username">{{ __('settings.smtp_username') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                    <input type="password" class="form-control" id="smtp_password" name="integrations[smtp][password]" />
                                                    <label for="smtp_password">{{ __('settings.smtp_password') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                    <input type="email" class="form-control" id="smtp_from_address" name="integrations[smtp][from_address]" />
                                                    <label for="smtp_from_address">{{ __('settings.smtp_from_address') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                    <input type="text" class="form-control" id="smtp_from_name" name="integrations[smtp][from_name]" />
                                                    <label for="smtp_from_name">{{ __('settings.smtp_from_name') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-3">{{ __('integrations.sections.smtp.hint') }}</small>
                                    </div>
                                </details>

                                <details class="integration-panel" data-provider-panel="whatsapp">
                                    <summary class="integration-panel-header">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-label-success text-success integration-overview-icon">
                                                <i class="icon-base ri ri-whatsapp-line fs-4"></i>
                                            </div>
                                            <div>
                                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                                    <h5 class="mb-0">{{ __('integrations.sections.whatsapp.title') }}</h5>
                                                    <span class="badge rounded-pill bg-label-secondary integration-status" data-provider="whatsapp"></span>
                                                </div>
                                                <p class="text-muted mb-0">{{ __('integrations.sections.whatsapp.description') }}</p>
                                            </div>
                                        </div>
                                        <i class="icon-base ri ri-arrow-down-s-line fs-4 integration-panel-chevron"></i>
                                    </summary>
                                    <div class="integration-panel-body">
                                        <div class="row g-4 pt-4">
                                            <div class="col-12">
                                                <div class="form-floating form-floating-outline">
                                                    <input type="text" class="form-control" id="whatsapp_api_key" name="integrations[whatsapp][api_key]" />
                                                    <label for="whatsapp_api_key">{{ __('settings.whatsapp_api_key') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-floating form-floating-outline">
                                                    <input type="text" class="form-control" id="whatsapp_sender" name="integrations[whatsapp][sender]" />
                                                    <label for="whatsapp_sender">{{ __('settings.whatsapp_sender') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-3">{{ __('integrations.sections.whatsapp.hint') }}</small>
                                    </div>
                                </details>

                                <details class="integration-panel" data-provider-panel="telegram">
                                    <summary class="integration-panel-header">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-label-warning text-warning integration-overview-icon">
                                                <i class="icon-base ri ri-telegram-2-line fs-4"></i>
                                            </div>
                                            <div>
                                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                                    <h5 class="mb-0">{{ __('integrations.sections.telegram.title') }}</h5>
                                                    <span class="badge rounded-pill bg-label-secondary integration-status" data-provider="telegram"></span>
                                                </div>
                                                <p class="text-muted mb-0">{{ __('integrations.sections.telegram.description') }}</p>
                                            </div>
                                        </div>
                                        <i class="icon-base ri ri-arrow-down-s-line fs-4 integration-panel-chevron"></i>
                                    </summary>
                                    <div class="integration-panel-body">
                                        <div class="row g-4 pt-4">
                                            <div class="col-12">
                                                <div class="form-floating form-floating-outline">
                                                    <input type="text" class="form-control" id="telegram_bot_token" name="integrations[telegram][bot_token]" />
                                                    <label for="telegram_bot_token">{{ __('settings.telegram_bot_token') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-floating form-floating-outline">
                                                    <input type="text" class="form-control" id="telegram_sender" name="integrations[telegram][sender]" />
                                                    <label for="telegram_sender">{{ __('settings.telegram_sender') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-3">{{ __('integrations.sections.telegram.hint') }}</small>
                                    </div>
                                </details>

                                <details class="integration-panel" data-provider-panel="yookassa">
                                    <summary class="integration-panel-header">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-label-danger text-danger integration-overview-icon">
                                                <i class="icon-base ri ri-bank-card-line fs-4"></i>
                                            </div>
                                            <div>
                                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                                    <h5 class="mb-0">{{ __('integrations.sections.yookassa.title') }}</h5>
                                                    <span class="badge rounded-pill bg-label-secondary integration-status" data-provider="yookassa"></span>
                                                </div>
                                                <p class="text-muted mb-0">{{ __('integrations.sections.yookassa.description') }}</p>
                                            </div>
                                        </div>
                                        <i class="icon-base ri ri-arrow-down-s-line fs-4 integration-panel-chevron"></i>
                                    </summary>
                                    <div class="integration-panel-body">
                                        <div class="row g-4 pt-4">
                                            <div class="col-12">
                                                <div class="form-floating form-floating-outline">
                                                    <input type="text" class="form-control" id="yookassa_shop_id" name="integrations[yookassa][shop_id]" />
                                                    <label for="yookassa_shop_id">{{ __('settings.yookassa_shop_id') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-floating form-floating-outline">
                                                    <input type="text" class="form-control" id="yookassa_secret_key" name="integrations[yookassa][secret_key]" />
                                                    <label for="yookassa_secret_key">{{ __('settings.yookassa_secret_key') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-3">{{ __('integrations.sections.yookassa.hint') }}</small>
                                    </div>
                                </details>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="card border-dashed integration-helper-card">
                            <div class="card-body p-5">
                                <span class="badge bg-label-secondary mb-3">{{ __('integrations.helper_badge') }}</span>
                                <h5 class="mb-2">{{ __('integrations.helper_title') }}</h5>
                                <p class="text-muted mb-4">{{ __('integrations.helper_text') }}</p>
                                <div class="rounded-4 bg-label-primary p-4 mb-4">
                                    <div class="small text-primary text-uppercase fw-semibold mb-2">Как выбрать канал</div>
                                    <div class="d-flex flex-column gap-2 small">
                                        <div>Telegram: быстрое подключение для онлайн-записи и уведомлений.</div>
                                        <div>SMS: когда важно повысить доходимость до визита.</div>
                                        <div>SMTP: если нужны письма и рассылки.</div>
                                        <div>ЮKassa: если хотите принимать оплату онлайн.</div>
                                    </div>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">{{ __('integrations.save') }}</button>
                                    <button type="button" class="btn btn-outline-secondary" id="reload-integrations">{{ __('integrations.reset') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    }

    function authHeaders(extra) {
        var token = getCookie('token');
        var headers = Object.assign({
            'Accept': 'application/json',
            'Accept-Language': document.documentElement.lang
        }, extra || {});

        if (token) {
            headers['Authorization'] = 'Bearer ' + token;
        }

        return headers;
    }

    function showMessage(type, text) {
        var container = document.getElementById('form-messages');
        container.innerHTML = '<div class="alert alert-' + type + '" role="alert">' + text + '</div>';
    }

    function clearErrors(form) {
        form.querySelectorAll('.invalid-feedback').forEach(function (el) { el.remove(); });
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
    }

    var STATUS_TEXT = {{ \Illuminate\Support\Js::from($integrationStatusText) }};

    var PROVIDERS = {
        smsaero: {
            required: ['integrations[smsaero][email]', 'integrations[smsaero][api_key]'],
            fields: ['integrations[smsaero][email]', 'integrations[smsaero][api_key]'],
            emptySummary: '2 поля для подключения',
            readySummary: 'SMS-уведомления готовы к работе'
        },
        smtp: {
            required: [
                'integrations[smtp][host]',
                'integrations[smtp][port]',
                'integrations[smtp][username]',
                'integrations[smtp][password]',
                'integrations[smtp][from_address]'
            ],
            fields: [
                'integrations[smtp][host]',
                'integrations[smtp][port]',
                'integrations[smtp][username]',
                'integrations[smtp][password]',
                'integrations[smtp][encryption]',
                'integrations[smtp][from_address]',
                'integrations[smtp][from_name]'
            ],
            emptySummary: 'Быстрая почтовая настройка',
            readySummary: 'Почтовый сервер подключен'
        },
        whatsapp: {
            required: ['integrations[whatsapp][api_key]', 'integrations[whatsapp][sender]'],
            fields: ['integrations[whatsapp][api_key]', 'integrations[whatsapp][sender]'],
            emptySummary: 'API-ключ и отправитель',
            readySummary: 'Канал WhatsApp готов'
        },
        telegram: {
            required: ['integrations[telegram][bot_token]'],
            fields: ['integrations[telegram][bot_token]', 'integrations[telegram][sender]'],
            emptySummary: 'Токен и имя отправителя',
            readySummary: 'Бот подключен и может принимать записи'
        },
        yookassa: {
            required: ['integrations[yookassa][shop_id]', 'integrations[yookassa][secret_key]'],
            fields: ['integrations[yookassa][shop_id]', 'integrations[yookassa][secret_key]'],
            emptySummary: 'Подключение оплаты для клиентов',
            readySummary: 'Оплата онлайн активна'
        }
    };

    function setProviderActiveState(provider) {
        document.querySelectorAll('[data-provider-card]').forEach(function (card) {
            card.classList.toggle('is-active', card.getAttribute('data-provider-card') === provider);
        });
    }

    function openProviderPanel(provider) {
        var target = document.querySelector('[data-provider-panel="' + provider + '"]');
        if (!target) {
            return;
        }

        document.querySelectorAll('[data-provider-panel]').forEach(function (panel) {
            panel.open = panel === target;
        });

        setProviderActiveState(provider);
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function refreshProviderStatuses(form) {
        var connectedCount = 0;

        Object.keys(PROVIDERS).forEach(function (provider) {
            var config = PROVIDERS[provider];
            var badges = document.querySelectorAll('.integration-status[data-provider="' + provider + '"]');
            var summaryNode = document.querySelector('[data-provider-summary="' + provider + '"]');

            var complete = config.required.every(function (name) {
                var field = form.elements[name];
                return field && String(field.value || '').trim() !== '';
            });

            var filledCount = config.fields.filter(function (name) {
                var field = form.elements[name];
                return field && String(field.value || '').trim() !== '';
            }).length;

            var hasAnyValue = filledCount > 0;

            badges.forEach(function (badge) {
                badge.className = 'badge rounded-pill integration-status';

                if (complete) {
                    badge.classList.add('bg-label-success');
                    badge.textContent = STATUS_TEXT.ready;
                    return;
                }

                if (hasAnyValue) {
                    badge.classList.add('bg-label-warning');
                    badge.textContent = STATUS_TEXT.partial;
                    return;
                }

                badge.classList.add('bg-label-secondary');
                badge.textContent = STATUS_TEXT.empty;
            });

            if (complete) {
                connectedCount++;
            }

            if (summaryNode) {
                summaryNode.textContent = complete
                    ? config.readySummary
                    : hasAnyValue
                        ? 'Заполнено ' + filledCount + ' из ' + config.fields.length + ' полей'
                        : config.emptySummary;
            }
        });

        var summary = document.getElementById('integrations-summary');
        if (summary) {
            summary.textContent = connectedCount === 0
                ? STATUS_TEXT.summary_empty
                : STATUS_TEXT.summary_pattern.replace(':count', connectedCount);
        }
    }

    async function loadIntegrations(showError) {
        var res = await fetch('/api/v1/settings/integrations', {
            headers: authHeaders(),
            credentials: 'include'
        });

        if (!res.ok) {
            if (showError) {
                showMessage('danger', STATUS_TEXT.load_error);
            }
            return;
        }

        var data = await res.json();
        var form = document.getElementById('integrations-form');

        form['integrations[smsaero][email]'].value = data.integrations.smsaero.email || '';
        form['integrations[smsaero][api_key]'].value = data.integrations.smsaero.api_key || '';
        form['integrations[smtp][host]'].value = data.integrations.smtp.host || '';
        form['integrations[smtp][port]'].value = data.integrations.smtp.port || '';
        form['integrations[smtp][username]'].value = data.integrations.smtp.username || '';
        form['integrations[smtp][password]'].value = data.integrations.smtp.password || '';
        form['integrations[smtp][encryption]'].value = data.integrations.smtp.encryption || '';
        form['integrations[smtp][from_address]'].value = data.integrations.smtp.from_address || '';
        form['integrations[smtp][from_name]'].value = data.integrations.smtp.from_name || '';
        form['integrations[whatsapp][api_key]'].value = data.integrations.whatsapp.api_key || '';
        form['integrations[whatsapp][sender]'].value = data.integrations.whatsapp.sender || '';
        form['integrations[telegram][bot_token]'].value = data.integrations.telegram.bot_token || '';
        form['integrations[telegram][sender]'].value = data.integrations.telegram.sender || '';
        form['integrations[yookassa][shop_id]'].value = data.integrations.yookassa.shop_id || '';
        form['integrations[yookassa][secret_key]'].value = data.integrations.yookassa.secret_key || '';

        refreshProviderStatuses(form);

        var defaultProvider = Object.keys(PROVIDERS).find(function (provider) {
            return PROVIDERS[provider].required.every(function (name) {
                return String(form[name].value || '').trim() !== '';
            });
        }) || Object.keys(PROVIDERS).find(function (provider) {
            return PROVIDERS[provider].fields.some(function (name) {
                return String(form[name].value || '').trim() !== '';
            });
        }) || 'telegram';

        openProviderPanel(defaultProvider);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('integrations-form');

        loadIntegrations(false);

        form.addEventListener('input', function () {
            refreshProviderStatuses(form);
        });

        document.querySelectorAll('[data-open-provider]').forEach(function (button) {
            button.addEventListener('click', function () {
                openProviderPanel(button.getAttribute('data-open-provider'));
            });
        });

        document.querySelectorAll('[data-provider-panel]').forEach(function (panel) {
            panel.addEventListener('toggle', function () {
                if (!panel.open) {
                    return;
                }

                document.querySelectorAll('[data-provider-panel]').forEach(function (otherPanel) {
                    if (otherPanel !== panel) {
                        otherPanel.open = false;
                    }
                });

                setProviderActiveState(panel.getAttribute('data-provider-panel'));
            });
        });

        document.getElementById('reload-integrations').addEventListener('click', function () {
            clearErrors(form);
            loadIntegrations(true);
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            clearErrors(form);

            var payload = {
                integrations: {
                    smsaero: {
                        email: form['integrations[smsaero][email]'].value,
                        api_key: form['integrations[smsaero][api_key]'].value
                    },
                    smtp: {
                        host: form['integrations[smtp][host]'].value,
                        port: form['integrations[smtp][port]'].value,
                        username: form['integrations[smtp][username]'].value,
                        password: form['integrations[smtp][password]'].value,
                        encryption: form['integrations[smtp][encryption]'].value,
                        from_address: form['integrations[smtp][from_address]'].value,
                        from_name: form['integrations[smtp][from_name]'].value
                    },
                    whatsapp: {
                        api_key: form['integrations[whatsapp][api_key]'].value,
                        sender: form['integrations[whatsapp][sender]'].value
                    },
                    telegram: {
                        bot_token: form['integrations[telegram][bot_token]'].value,
                        sender: form['integrations[telegram][sender]'].value
                    },
                    yookassa: {
                        shop_id: form['integrations[yookassa][shop_id]'].value,
                        secret_key: form['integrations[yookassa][secret_key]'].value
                    }
                }
            };

            var res = await fetch('/api/v1/settings/integrations', {
                method: 'PATCH',
                headers: authHeaders({ 'Content-Type': 'application/json' }),
                credentials: 'include',
                body: JSON.stringify(payload)
            });

            var result = await res.json().catch(function () { return {}; });

            if (!res.ok) {
                var errors = result.error && result.error.fields ? result.error.fields : {};
                if (Object.keys(errors).length === 0 && result.error && result.error.message) {
                    showMessage('danger', result.error.message);
                }

                Object.keys(errors).forEach(function (key) {
                    var fieldName = key.replace(/\.(\w+)/g, '[$1]');
                    var input = form.querySelector('[name="' + fieldName + '"]');

                    if (!input) {
                        return;
                    }

                    input.classList.add('is-invalid');
                    var container = input.closest('.form-control-validation') || input.parentNode;
                    var div = document.createElement('div');
                    div.classList.add('invalid-feedback');
                    div.textContent = errors[key][0];
                    container.appendChild(div);
                });

                return;
            }

            showMessage('success', STATUS_TEXT.saved);
            refreshProviderStatuses(form);
        });
    });
    </script>
@endsection
