@extends('layouts.app')

@php
    use App\Services\Integrations\IntegrationCatalog;

    $providers = IntegrationCatalog::PROVIDERS;

    // The mail form is the only one with fields that share a row.
    $fieldWidths = [
        'smtp' => [
            'host' => 'col-md-7',
            'port' => 'col-md-5',
            'encryption' => 'col-md-5',
            'username' => 'col-md-7',
            'password' => 'col-md-7',
            'from_address' => 'col-md-7',
            'from_name' => 'col-md-5',
        ],
    ];

    $providerConfig = [];

    foreach ($providers as $key => $provider) {
        $fields = [];

        foreach ($provider['fields'] as $name => $definition) {
            $fields[$name] = [
                'secret' => (bool) ($definition['secret'] ?? false),
                'required' => (bool) ($definition['required'] ?? false),
                'label' => __("integrations.sections.{$key}.fields.{$name}.label"),
            ];
        }

        $providerConfig[$key] = [
            'title' => __("integrations.sections.{$key}.title"),
            'fields' => $fields,
        ];
    }

    $copy = [
        'status' => __('integrations.status'),
        'status_hint' => __('integrations.status_hint'),
        'actions' => __('integrations.actions'),
        'messages' => __('integrations.messages'),
        'summary_empty' => __('integrations.summary_empty'),
        'summary_pattern' => __('integrations.summary_pattern'),
    ];
@endphp

@section('title', __('integrations.title'))

@section('content')
    <style>
        body {
            --integrations-line: color-mix(in srgb, var(--bs-border-color) 70%, transparent);
            /* Field labels used to run at 2.3:1 — on a form the label is the
               only thing telling you what belongs in the box. */
            --integrations-text: color-mix(in srgb, var(--bs-heading-color) 82%, transparent);
            --integrations-faint: color-mix(in srgb, var(--bs-heading-color) 74%, transparent);
        }

        .integrations-page [hidden] {
            display: none !important;
        }

        .integrations-page {
            max-width: 60rem;
        }

        .integrations-hero__title {
            margin: 0 0 0.15rem;
            font-size: clamp(1.25rem, 1.6vw, 1.5rem);
            letter-spacing: -0.02em;
        }

        .integrations-hero__lead,
        .integrations-note {
            color: var(--integrations-faint);
            font-size: 0.9rem;
            margin: 0;
        }

        .integrations-note {
            display: flex;
            align-items: baseline;
            gap: 0.4rem;
        }

        /* The count is a line of its own, not a tail of the description. */
        #integrations-summary {
            margin-top: 0.35rem;
            font-weight: 600;
            color: var(--integrations-text);
        }

        .integration-panel {
            border: 1px solid var(--integrations-line);
            border-radius: 1rem;
            background: var(--bs-card-bg);
            overflow: hidden;
        }

        .integration-panel + .integration-panel {
            margin-top: 0.75rem;
        }

        .integration-panel summary {
            list-style: none;
            cursor: pointer;
        }

        .integration-panel summary::-webkit-details-marker {
            display: none;
        }

        .integration-panel summary:focus-visible {
            outline: 2px solid var(--bs-primary);
            outline-offset: -2px;
        }

        .integration-panel__head {
            display: flex;
            align-items: flex-start;
            gap: 0.9rem;
            padding: 1.1rem 1.25rem;
        }

        .integration-panel__icon {
            flex: 0 0 auto;
            width: 2.5rem;
            height: 2.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
        }

        .integration-panel__main {
            flex: 1 1 auto;
            min-width: 0;
        }

        .integration-panel__title {
            margin: 0;
            font-size: 1.02rem;
            font-weight: 600;
        }

        .integration-panel__desc {
            margin: 0.15rem 0 0;
            color: var(--integrations-text);
            font-size: 0.9rem;
        }

        .integration-panel__hint {
            display: block;
            margin-top: 0.2rem;
            font-size: 0.85rem;
            color: var(--integrations-faint);
        }

        .integration-panel__hint.is-bad {
            color: var(--bs-danger-text-emphasis, var(--bs-danger));
        }

        .integration-panel__chevron {
            flex: 0 0 auto;
            color: var(--integrations-faint);
            transition: transform 0.2s ease;
        }

        .integration-panel[open] .integration-panel__chevron {
            transform: rotate(180deg);
        }

        .integration-panel__body {
            padding: 0 1.25rem 1.25rem;
            border-top: 1px solid var(--integrations-line);
        }

        .integration-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            background: color-mix(in srgb, var(--bs-heading-color) 8%, transparent);
            color: var(--integrations-text);
        }

        .integration-badge.is-verified {
            background: rgba(var(--bs-success-rgb, 40, 199, 111), 0.14);
            color: var(--bs-success-text-emphasis, var(--bs-success));
        }

        .integration-badge.is-failed {
            background: rgba(var(--bs-danger-rgb, 255, 62, 29), 0.14);
            color: var(--bs-danger-text-emphasis, var(--bs-danger));
        }

        .integration-badge.is-partial,
        .integration-badge.is-filled {
            background: rgba(var(--bs-warning-rgb, 255, 171, 0), 0.16);
            color: var(--bs-warning-text-emphasis, #8a5a00);
        }

        .integration-dirty {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--bs-warning-text-emphasis, #8a5a00);
        }

        /* The instruction sits above the fields it explains, not in a column
           on the other side of the screen. */
        .integration-steps {
            margin: 1.25rem 0;
            padding: 0.9rem 1rem;
            border-radius: 0.75rem;
            background: color-mix(in srgb, var(--bs-heading-color) 4%, transparent);
        }

        .integration-steps__title {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            /* On the tinted block the faint tint fell to 4.4:1. */
            color: var(--integrations-text);
            margin-bottom: 0.4rem;
        }

        .integration-steps ol {
            margin: 0;
            padding-left: 1.15rem;
            color: var(--integrations-text);
            font-size: 0.9rem;
        }

        .integration-steps li + li {
            margin-top: 0.25rem;
        }

        .integration-field__label {
            display: flex;
            align-items: baseline;
            gap: 0.4rem;
            margin-bottom: 0.3rem;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--integrations-text);
        }

        .integration-required {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--bs-danger-text-emphasis, var(--bs-danger));
        }

        .integration-field__hint {
            display: block;
            margin-top: 0.3rem;
            font-size: 0.82rem;
            color: var(--integrations-faint);
        }

        .integration-secret__saved {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.6rem;
            padding: 0.6rem 0.75rem;
            border: 1px dashed var(--integrations-line);
            border-radius: 0.75rem;
            font-size: 0.88rem;
            color: var(--integrations-text);
        }

        .integration-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.25rem;
        }

        .integration-actions .integration-disconnect {
            margin-left: auto;
        }

        .integration-result {
            margin-top: 0.85rem;
            padding: 0.7rem 0.85rem;
            border-radius: 0.75rem;
            font-size: 0.9rem;
        }

        .integration-result.is-ok {
            background: rgba(var(--bs-success-rgb, 40, 199, 111), 0.12);
            color: var(--bs-success-text-emphasis, var(--bs-success));
        }

        .integration-result.is-bad {
            background: rgba(var(--bs-danger-rgb, 255, 62, 29), 0.12);
            color: var(--bs-danger-text-emphasis, var(--bs-danger));
        }

        .integration-result.is-warn {
            background: rgba(var(--bs-warning-rgb, 255, 171, 0), 0.14);
            color: var(--bs-warning-text-emphasis, #8a5a00);
        }

        @media (max-width: 575.98px) {
            .integration-panel__head {
                padding: 1rem;
            }

            .integration-panel__body {
                padding: 0 1rem 1rem;
            }

            .integration-actions .btn {
                flex: 1 1 100%;
            }

            .integration-actions .integration-disconnect {
                margin-left: 0;
            }
        }
    </style>

    <div class="integrations-page d-flex flex-column gap-3">
        <header class="integrations-hero">
            <h1 class="integrations-hero__title">{{ __('integrations.title') }}</h1>
            <p class="integrations-hero__lead">{{ __('integrations.description') }}</p>
            <p class="integrations-hero__lead" id="integrations-summary">{{ __('integrations.summary_empty') }}</p>
        </header>

        <p class="integrations-note">
            <i class="ri ri-lock-2-line"></i>
            <span>{{ __('integrations.security_note') }}</span>
        </p>

        <div id="integrations-alert"></div>

        <div class="integrations-list">
            @foreach ($providers as $key => $provider)
                @php
                    $steps = __("integrations.sections.{$key}.steps");
                    $linkUrl = __("integrations.sections.{$key}.link_url");
                    $linkLabel = __("integrations.sections.{$key}.link_label");
                    $hasLink = is_string($linkUrl) && str_starts_with($linkUrl, 'http');
                @endphp

                <details class="integration-panel" data-panel="{{ $key }}" @if ($loop->first) open @endif>
                    <summary>
                        <div class="integration-panel__head">
                            <span class="integration-panel__icon bg-label-{{ $provider['tone'] }} text-{{ $provider['tone'] }}">
                                <i class="ri {{ $provider['icon'] }} fs-5"></i>
                            </span>
                            <span class="integration-panel__main">
                                <span class="d-flex align-items-center flex-wrap gap-2">
                                    <span class="integration-panel__title">{{ __("integrations.sections.{$key}.title") }}</span>
                                    <span class="integration-badge" data-status="{{ $key }}">{{ __('integrations.status.empty') }}</span>
                                    <span class="integration-dirty" data-dirty="{{ $key }}" hidden>{{ __('integrations.messages.unsaved') }}</span>
                                </span>
                                <span class="integration-panel__desc d-block">{{ __("integrations.sections.{$key}.description") }}</span>
                                <span class="integration-panel__hint" data-status-hint="{{ $key }}"></span>
                            </span>
                            <i class="ri ri-arrow-down-s-line fs-4 integration-panel__chevron"></i>
                        </div>
                    </summary>

                    <div class="integration-panel__body">
                        <div class="integration-steps">
                            <div class="integration-steps__title">{{ __('integrations.actions.steps') }}</div>
                            <ol>
                                @foreach ((array) $steps as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            </ol>
                            @if ($hasLink)
                                <a class="btn btn-sm btn-outline-primary mt-3" href="{{ $linkUrl }}" target="_blank" rel="noopener">
                                    {{ $linkLabel }}
                                    <i class="ri ri-external-link-line ms-1"></i>
                                </a>
                            @endif
                        </div>

                        <div class="row g-4">
                            @foreach ($provider['fields'] as $name => $definition)
                                @php
                                    $inputId = $key . '_' . $name;
                                    $inputName = "integrations[{$key}][{$name}]";
                                    $width = $fieldWidths[$key][$name] ?? 'col-12';
                                    $isSecret = (bool) ($definition['secret'] ?? false);
                                    $isRequired = (bool) ($definition['required'] ?? false);
                                    $label = __("integrations.sections.{$key}.fields.{$name}.label");
                                    $hint = __("integrations.sections.{$key}.fields.{$name}.hint");
                                @endphp

                                <div class="{{ $width }}">
                                    <label class="integration-field__label" for="{{ $inputId }}">
                                        <span>{{ $label }}</span>
                                        @if ($isRequired)
                                            <span class="integration-required">{{ __('integrations.messages.required') }}</span>
                                        @endif
                                    </label>

                                    @if ($isSecret)
                                        <div class="integration-secret__saved" data-secret-saved="{{ $key }}.{{ $name }}" hidden>
                                            <i class="ri ri-key-2-line"></i>
                                            <span class="flex-grow-1" data-secret-preview="{{ $key }}.{{ $name }}"></span>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-secret-replace="{{ $key }}.{{ $name }}">
                                                {{ __('integrations.actions.replace') }}
                                            </button>
                                        </div>
                                    @endif

                                    <div data-field-box="{{ $key }}.{{ $name }}">
                                        @if (($definition['input'] ?? 'text') === 'select')
                                            <select class="form-select" id="{{ $inputId }}" name="{{ $inputName }}" data-field="{{ $key }}.{{ $name }}">
                                                <option value="">—</option>
                                                @foreach (($definition['options'] ?? []) as $option)
                                                    <option value="{{ $option }}">{{ __("integrations.smtp_encryption.{$option}") }}</option>
                                                @endforeach
                                            </select>
                                        @elseif ($isSecret)
                                            <div class="input-group">
                                                <input
                                                    type="password"
                                                    class="form-control"
                                                    id="{{ $inputId }}"
                                                    name="{{ $inputName }}"
                                                    autocomplete="off"
                                                    spellcheck="false"
                                                    data-field="{{ $key }}.{{ $name }}" />
                                                <button type="button" class="btn btn-outline-secondary" data-secret-toggle="{{ $key }}.{{ $name }}">
                                                    {{ __('integrations.actions.show') }}
                                                </button>
                                            </div>
                                        @else
                                            <input
                                                type="{{ $definition['input'] ?? 'text' }}"
                                                class="form-control"
                                                id="{{ $inputId }}"
                                                name="{{ $inputName }}"
                                                autocomplete="off"
                                                @if (($definition['input'] ?? '') === 'number') min="1" max="65535" @endif
                                                data-field="{{ $key }}.{{ $name }}" />
                                        @endif

                                        <small class="integration-field__hint">{{ $hint }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="integration-actions">
                            <button type="button" class="btn btn-primary" data-save="{{ $key }}">
                                {{ __('integrations.actions.save') }}
                            </button>
                            <button type="button" class="btn btn-text-secondary" data-cancel="{{ $key }}" hidden>
                                {{ __('integrations.actions.cancel') }}
                            </button>
                            <button type="button" class="btn btn-text-secondary integration-disconnect" data-disconnect="{{ $key }}" hidden>
                                {{ __('integrations.actions.disconnect') }}
                            </button>
                        </div>

                        <div class="integration-result" data-result="{{ $key }}" hidden></div>
                    </div>
                </details>
            @endforeach
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var PROVIDERS = {{ \Illuminate\Support\Js::from($providerConfig) }};
            var COPY = {{ \Illuminate\Support\Js::from($copy) }};

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

                if (token) headers['Authorization'] = 'Bearer ' + token;

                return headers;
            }

            function escapeHtml(value) {
                return String(value === null || value === undefined ? '' : value).replace(/[&<>"']/g, function (character) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
                });
            }

            var state = {
                loaded: false,
                payload: {},
                baseline: {},
                replacing: {},
            };

            var alertEl = document.getElementById('integrations-alert');
            var summaryEl = document.getElementById('integrations-summary');

            function field(provider, name) {
                return document.querySelector('[data-field="' + provider + '.' + name + '"]');
            }

            function key(provider, name) {
                return provider + '.' + name;
            }

            /* ---------- rendering ---------- */

            function formatDate(iso) {
                if (!iso) return '';

                var date = new Date(iso);
                if (isNaN(date.getTime())) return '';

                return date.toLocaleString(document.documentElement.lang || 'ru-RU', {
                    day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit'
                });
            }

            function missingLabels(provider, missing) {
                var config = PROVIDERS[provider] || { fields: {} };

                return (missing || []).map(function (name) {
                    return (config.fields[name] || {}).label || name;
                }).join(', ');
            }

            function renderStatus(provider) {
                var data = state.payload[provider];
                if (!data) return;

                var status = data.status || { state: 'empty' };
                var badge = document.querySelector('[data-status="' + provider + '"]');
                var hint = document.querySelector('[data-status-hint="' + provider + '"]');

                badge.className = 'integration-badge is-' + status.state;
                badge.textContent = COPY.status[status.state] || COPY.status.empty;

                var text = '';
                var bad = false;

                if (status.state === 'partial') {
                    text = COPY.status_hint.partial.replace(':fields', missingLabels(provider, status.missing));
                } else if (status.state === 'filled') {
                    text = COPY.status_hint.filled;
                } else if (status.state === 'verified') {
                    text = COPY.status_hint.verified.replace(':date', formatDate(status.checked_at));
                    if (status.message) text = status.message + ' · ' + text;
                } else if (status.state === 'failed') {
                    bad = true;
                    text = status.message || COPY.status_hint.failed.replace(':date', formatDate(status.checked_at));
                }

                hint.textContent = text;
                hint.classList.toggle('is-bad', bad);

                var disconnect = document.querySelector('[data-disconnect="' + provider + '"]');
                disconnect.hidden = status.state === 'empty';

                var save = document.querySelector('[data-save="' + provider + '"]');
                save.textContent = status.state === 'verified' && !isDirty(provider)
                    ? COPY.actions.check
                    : COPY.actions.save;
            }

            function renderSecret(provider, name) {
                var data = ((state.payload[provider] || {}).fields || {})[name] || {};
                var savedBox = document.querySelector('[data-secret-saved="' + key(provider, name) + '"]');
                var fieldBox = document.querySelector('[data-field-box="' + key(provider, name) + '"]');
                var preview = document.querySelector('[data-secret-preview="' + key(provider, name) + '"]');

                if (!savedBox || !fieldBox) return;

                // A stored key is shown as four characters and a button, never
                // as the key itself in a text input.
                var showSaved = Boolean(data.filled) && !state.replacing[key(provider, name)];

                savedBox.hidden = !showSaved;
                fieldBox.hidden = showSaved;

                if (preview) {
                    preview.textContent = COPY.messages.secret_saved.replace(':preview', data.preview || '••••');
                }
            }

            function renderProvider(provider) {
                var config = PROVIDERS[provider];
                var data = state.payload[provider] || { fields: {} };

                Object.keys(config.fields).forEach(function (name) {
                    var input = field(provider, name);
                    if (!input) return;

                    if (config.fields[name].secret) {
                        if (!state.replacing[key(provider, name)]) input.value = '';
                        renderSecret(provider, name);
                        return;
                    }

                    input.value = (data.fields[name] || {}).value || '';
                });

                renderStatus(provider);
                renderDirty(provider);
            }

            function renderSummary() {
                if (!summaryEl) return;

                var providers = Object.keys(PROVIDERS);
                var working = providers.filter(function (provider) {
                    return ((state.payload[provider] || {}).status || {}).state === 'verified';
                }).length;

                summaryEl.textContent = working === 0
                    ? COPY.summary_empty
                    : COPY.summary_pattern.replace(':count', working).replace(':total', providers.length);
            }

            function render() {
                Object.keys(PROVIDERS).forEach(renderProvider);
                renderSummary();
            }

            /* ---------- dirty state ---------- */

            function currentValues(provider) {
                var values = {};

                Object.keys(PROVIDERS[provider].fields).forEach(function (name) {
                    var input = field(provider, name);
                    values[name] = input ? String(input.value || '').trim() : '';
                });

                return values;
            }

            function isDirty(provider) {
                var base = state.baseline[provider] || {};
                var values = currentValues(provider);

                return Object.keys(values).some(function (name) {
                    // A secret is dirty only when something new was typed: the
                    // stored one never reaches the page to be compared.
                    if (PROVIDERS[provider].fields[name].secret) {
                        return values[name] !== '';
                    }

                    return values[name] !== (base[name] || '');
                });
            }

            function renderDirty(provider) {
                var dirty = isDirty(provider);

                document.querySelector('[data-dirty="' + provider + '"]').hidden = !dirty;
                document.querySelector('[data-cancel="' + provider + '"]').hidden = !dirty;
            }

            function anythingDirty() {
                return Object.keys(PROVIDERS).some(isDirty);
            }

            function captureBaseline() {
                state.baseline = {};

                Object.keys(PROVIDERS).forEach(function (provider) {
                    var values = {};

                    Object.keys(PROVIDERS[provider].fields).forEach(function (name) {
                        if (PROVIDERS[provider].fields[name].secret) {
                            values[name] = '';
                            return;
                        }

                        values[name] = (((state.payload[provider] || {}).fields || {})[name] || {}).value || '';
                    });

                    state.baseline[provider] = values;
                });
            }

            /* ---------- messages ---------- */

            function showResult(provider, tone, message) {
                var box = document.querySelector('[data-result="' + provider + '"]');
                box.className = 'integration-result is-' + tone;
                box.textContent = message;
                box.hidden = false;
            }

            function clearResult(provider) {
                document.querySelector('[data-result="' + provider + '"]').hidden = true;
            }

            function setBlocked(blocked, message) {
                state.loaded = !blocked;

                alertEl.innerHTML = blocked
                    ? '<div class="alert alert-danger mb-0" role="alert">' + escapeHtml(message) + '</div>'
                    : '';

                document.querySelectorAll('[data-save], [data-disconnect]').forEach(function (button) {
                    button.disabled = blocked;
                });
            }

            /* ---------- loading ---------- */

            function applyPayload(payload) {
                state.payload = payload || {};
                captureBaseline();
                render();
            }

            function load() {
                return fetch('/api/v1/settings/integrations', {
                    headers: authHeaders(),
                    credentials: 'include',
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('failed');
                        return response.json();
                    })
                    .then(function (data) {
                        setBlocked(false);
                        applyPayload(data.integrations);
                    })
                    .catch(function (error) {
                        console.error(error);
                        // Saving on top of a failed load is how every key gets
                        // wiped: the form would send fifteen empty fields.
                        setBlocked(true, COPY.messages.load_error);
                    });
            }

            /* ---------- saving ---------- */

            function payloadFor(provider) {
                var values = currentValues(provider);
                var body = {};

                Object.keys(values).forEach(function (name) {
                    if (PROVIDERS[provider].fields[name].secret && values[name] === '') {
                        // Nothing typed means «keep the stored key», so the
                        // field is left out of the request entirely.
                        return;
                    }

                    body[name] = values[name];
                });

                return body;
            }

            function save(provider) {
                if (!state.loaded) return Promise.resolve();

                var button = document.querySelector('[data-save="' + provider + '"]');
                var label = button.textContent;
                var body = {};
                body[provider] = payloadFor(provider);

                button.disabled = true;
                button.textContent = COPY.actions.saving;
                clearResult(provider);

                return fetch('/api/v1/settings/integrations', {
                    method: 'PATCH',
                    headers: authHeaders({ 'Content-Type': 'application/json' }),
                    credentials: 'include',
                    body: JSON.stringify({ integrations: body }),
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('failed');
                        return response.json();
                    })
                    .then(function (data) {
                        state.replacing = {};
                        applyPayload(data.integrations);

                        var status = (state.payload[provider] || {}).status || {};

                        if (status.state === 'partial') {
                            showResult(provider, 'warn', COPY.messages.saved + ' ' +
                                COPY.status_hint.partial.replace(':fields', missingLabels(provider, status.missing)));
                            return null;
                        }

                        if (status.state === 'empty') {
                            showResult(provider, 'warn', COPY.messages.saved);
                            return null;
                        }

                        return check(provider);
                    })
                    .catch(function (error) {
                        console.error(error);
                        showResult(provider, 'bad', COPY.messages.save_error);
                    })
                    .finally(function () {
                        button.disabled = !state.loaded;
                        button.textContent = label;
                        renderStatus(provider);
                    });
            }

            // The badge says «Работает» only after the service itself said so.
            function check(provider) {
                var button = document.querySelector('[data-save="' + provider + '"]');
                button.disabled = true;
                button.textContent = COPY.actions.saving;

                return fetch('/api/v1/settings/integrations/' + provider + '/check', {
                    method: 'POST',
                    headers: authHeaders({ 'Content-Type': 'application/json' }),
                    credentials: 'include',
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('failed');
                        return response.json();
                    })
                    .then(function (data) {
                        applyPayload(data.integrations);
                        showResult(provider, data.result.ok ? 'ok' : 'bad', data.result.message);
                    })
                    .catch(function (error) {
                        console.error(error);
                        showResult(provider, 'bad', COPY.messages.save_error);
                    })
                    .finally(function () {
                        button.disabled = !state.loaded;
                        renderStatus(provider);
                    });
            }

            function disconnect(provider) {
                var title = (PROVIDERS[provider] || {}).title || provider;

                if (!window.confirm(COPY.messages.disconnect_confirm.replace(':title', title))) return;

                fetch('/api/v1/settings/integrations/' + provider, {
                    method: 'DELETE',
                    headers: authHeaders(),
                    credentials: 'include',
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('failed');
                        return response.json();
                    })
                    .then(function (data) {
                        state.replacing = {};
                        applyPayload(data.integrations);
                        showResult(provider, 'warn', COPY.messages.disconnected);
                    })
                    .catch(function (error) {
                        console.error(error);
                        showResult(provider, 'bad', COPY.messages.save_error);
                    });
            }

            /* ---------- wiring ---------- */

            document.addEventListener('input', function (event) {
                var input = event.target.closest('[data-field]');
                if (!input) return;

                renderDirty(input.dataset.field.split('.')[0]);
            });

            document.addEventListener('change', function (event) {
                var input = event.target.closest('[data-field]');
                if (!input) return;

                renderDirty(input.dataset.field.split('.')[0]);
            });

            document.addEventListener('click', function (event) {
                var saveButton = event.target.closest('[data-save]');
                if (saveButton) {
                    save(saveButton.dataset.save);
                    return;
                }

                var cancelButton = event.target.closest('[data-cancel]');
                if (cancelButton) {
                    var provider = cancelButton.dataset.cancel;
                    // Named for what it does. It used to be «Обновить из
                    // сервера», sat under the save button and threw away
                    // everything typed without asking.
                    if (!window.confirm(COPY.messages.cancel_confirm)) return;

                    Object.keys(PROVIDERS[provider].fields).forEach(function (name) {
                        state.replacing[key(provider, name)] = false;
                        var input = field(provider, name);
                        if (input) input.value = '';
                    });

                    renderProvider(provider);
                    clearResult(provider);
                    return;
                }

                var disconnectButton = event.target.closest('[data-disconnect]');
                if (disconnectButton) {
                    disconnect(disconnectButton.dataset.disconnect);
                    return;
                }

                var replaceButton = event.target.closest('[data-secret-replace]');
                if (replaceButton) {
                    var path = replaceButton.dataset.secretReplace.split('.');
                    state.replacing[replaceButton.dataset.secretReplace] = true;
                    renderSecret(path[0], path[1]);
                    var input = field(path[0], path[1]);
                    if (input) input.focus();
                    return;
                }

                var toggleButton = event.target.closest('[data-secret-toggle]');
                if (toggleButton) {
                    var target = toggleButton.dataset.secretToggle.split('.');
                    var secretInput = field(target[0], target[1]);
                    if (!secretInput) return;

                    var hidden = secretInput.type === 'password';
                    secretInput.type = hidden ? 'text' : 'password';
                    toggleButton.textContent = hidden ? COPY.actions.hide : COPY.actions.show;
                }
            });

            window.addEventListener('beforeunload', function (event) {
                if (!anythingDirty()) return;

                event.preventDefault();
                event.returnValue = COPY.messages.leave_confirm;
                return COPY.messages.leave_confirm;
            });

            load();
        });
    </script>
@endsection
