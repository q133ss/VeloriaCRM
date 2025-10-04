@extends('layouts.app')

@section('title', 'Маркетинг')

@section('content')
    <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Маркетинг</h4>
            <p class="text-muted mb-0">Создавайте рассылки, управляйте акциями и возвращайте клиентов за счёт точных подсказок.</p>
        </div>
        <div class="text-end small text-muted" id="marketing-plan-badge"></div>
    </div>

    <div id="marketing-alerts"></div>

    <div class="row g-4">
        <div class="col-12 col-xxl-8">
            <div class="card h-100">
                <div class="card-header d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
                    <div>
                        <h5 class="mb-1">Создать рассылку</h5>
                        <p class="text-muted mb-0">Выберите канал, сегмент и текст — система подскажет, кого и как лучше вовлечь.</p>
                    </div>
                    <div class="text-muted small" id="campaign-ab-tip"></div>
                </div>
                <div class="card-body">
                    <form id="campaign-create-form" class="row g-3">
                        <div class="col-md-6">
                            <label for="campaign-name" class="form-label">Название кампании</label>
                            <input type="text" class="form-control" id="campaign-name" name="name" placeholder="Например, «Мы скучаем»" required />
                        </div>
                        <div class="col-md-3">
                            <label for="campaign-channel" class="form-label">Канал</label>
                            <select id="campaign-channel" name="channel" class="form-select" required></select>
                        </div>
                        <div class="col-md-3">
                            <label for="campaign-segment" class="form-label">Сегмент аудитории</label>
                            <select id="campaign-segment" name="segment" class="form-select" required></select>
                        </div>
                        <div class="col-12" id="campaign-segment-extra" style="display: none;">
                            <div class="card border border-dashed rounded-2">
                                <div class="card-body">
                                    <h6 class="fw-semibold mb-2">Уточните условия сегмента</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4" data-segment-field="service_ids" style="display: none;">
                                            <label for="segment-service-ids" class="form-label">ID услуг (через запятую)</label>
                                            <input type="text" class="form-control" id="segment-service-ids" placeholder="12, 18, 24" />
                                            <div class="form-text">Укажите идентификаторы услуг для точного таргета.</div>
                                        </div>
                                        <div class="col-md-4" data-segment-field="master_ids" style="display: none;">
                                            <label for="segment-master-ids" class="form-label">ID мастеров (через запятую)</label>
                                            <input type="text" class="form-control" id="segment-master-ids" placeholder="3, 7" />
                                            <div class="form-text">Сегментируйте клиентов по мастерам.</div>
                                        </div>
                                        <div class="col-md-4" data-segment-field="tags" style="display: none;">
                                            <label for="segment-tags" class="form-label">Теги клиентов</label>
                                            <input type="text" class="form-control" id="segment-tags" placeholder="VIP, Маникюр" />
                                            <div class="form-text">Перечислите теги клиентов через запятую.</div>
                                        </div>
                                        <div class="col-12" data-segment-field="client_ids" style="display: none;">
                                            <label for="segment-client-ids" class="form-label">Выбранные клиенты</label>
                                            <select id="segment-client-ids" class="form-select" multiple size="6"></select>
                                            <div class="form-text">Отправьте сообщение только отмеченным клиентам.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="campaign-template" class="form-label">Шаблон сообщения</label>
                            <select id="campaign-template" name="template_id" class="form-select">
                                <option value="">— Выберите шаблон —</option>
                            </select>
                            <div class="form-text">Заготовки для приветствий, прогрева и поздравлений.</div>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" role="switch" id="campaign-is-ab" />
                                <label class="form-check-label" for="campaign-is-ab">Запустить A/B-тест</label>
                            </div>
                        </div>
                        <div class="col-md-6" id="campaign-subject-wrapper">
                            <label for="campaign-subject" class="form-label">Тема письма / превью</label>
                            <input type="text" class="form-control" id="campaign-subject" name="subject" placeholder="Например, «Дарим -20% на любимую услугу»" />
                        </div>
                        <div class="col-12" id="campaign-content-wrapper">
                            <label for="campaign-content" class="form-label">Текст сообщения</label>
                            <textarea class="form-control" id="campaign-content" name="content" rows="4" placeholder="Расскажите о предложении и добавьте CTA"></textarea>
                        </div>
                        <div class="col-12" id="campaign-variants" style="display: none;">
                            <div class="border border-dashed rounded-2 p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Варианты для теста</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="variant-add">Добавить вариант</button>
                                </div>
                                <div class="row g-3" id="variant-list"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="campaign-status" class="form-label">Статус</label>
                            <select id="campaign-status" name="status" class="form-select">
                                <option value="draft">Черновик</option>
                                <option value="scheduled">Запланирована</option>
                                <option value="sending">В процессе</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="campaign-scheduled" class="form-label">Запланировать на</label>
                            <input type="datetime-local" class="form-control" id="campaign-scheduled" name="scheduled_at" />
                        </div>
                        <div class="col-md-4">
                            <label for="campaign-test-size" class="form-label">Размер тестовой группы</label>
                            <input type="number" min="0" class="form-control" id="campaign-test-size" name="test_group_size" placeholder="Например, 50" />
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri ri-send-plane-2-line me-1"></i>
                                Сохранить кампанию
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-xxl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-1">Подсказки</h5>
                    <p class="text-muted mb-0">ИИ анализирует ваши кампании и подсказывает, что стоит сделать дальше.</p>
                </div>
                <div class="card-body">
                    <div id="campaign-suggestions" class="d-flex flex-column gap-3"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
            <div>
                <h5 class="mb-1">Активные кампании</h5>
                <p class="text-muted mb-0">Контролируйте статус, доставку и эффективность A/B-тестов.</p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center" id="campaign-metrics">
                <span class="badge bg-label-primary" data-metric="total">Кампаний: 0</span>
                <span class="badge bg-label-success" data-metric="delivered">Доставлено: 0</span>
                <span class="badge bg-label-info" data-metric="read">Прочитано: 0</span>
                <span class="badge bg-label-warning" data-metric="clicks">Переходы: 0</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Кампания</th>
                        <th>Статус</th>
                        <th>Метрики</th>
                        <th class="text-end">Действия</th>
                    </tr>
                </thead>
                <tbody id="campaigns-table">
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Загрузка...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-12 col-xxl-7">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-1">Управление акциями</h5>
                    <p class="text-muted mb-0">Создавайте скидки, подарки и промокоды с анализом эффективности.</p>
                </div>
                <div class="card-body">
                    <form id="promotion-form" class="row g-3">
                        <div class="col-md-6">
                            <label for="promotion-name" class="form-label">Название</label>
                            <input type="text" class="form-control" id="promotion-name" placeholder="Весенний кэшбэк" required />
                        </div>
                        <div class="col-md-3">
                            <label for="promotion-type" class="form-label">Тип акции</label>
                            <select id="promotion-type" class="form-select" required></select>
                        </div>
                        <div class="col-md-3" data-promotion-field="percent" style="display: none;">
                            <label for="promotion-percent" class="form-label">Процент кэшбэка</label>
                            <input type="number" class="form-control" id="promotion-percent" placeholder="15" min="1" max="100" />
                        </div>
                        <div class="col-md-3" data-promotion-field="service" style="display: none;">
                            <label for="promotion-service" class="form-label">Услуга</label>
                            <select id="promotion-service" class="form-select"></select>
                        </div>
                        <div class="col-md-3" data-promotion-field="category" style="display: none;">
                            <label for="promotion-category" class="form-label">Категория услуг</label>
                            <select id="promotion-category" class="form-select"></select>
                        </div>
                        <div class="col-md-4">
                            <label for="promotion-code" class="form-label">Промокод</label>
                            <input type="text" class="form-control" id="promotion-code" placeholder="SPRING24" />
                        </div>
                        <div class="col-md-4">
                            <label for="promotion-usage-limit" class="form-label">Лимит использований</label>
                            <input type="number" class="form-control" id="promotion-usage-limit" min="0" placeholder="100" />
                        </div>
                        <div class="col-md-4">
                            <label for="promotion-start" class="form-label">Дата старта</label>
                            <input type="date" class="form-control" id="promotion-start" />
                        </div>
                        <div class="col-md-4">
                            <label for="promotion-end" class="form-label">Дата завершения</label>
                            <input type="date" class="form-control" id="promotion-end" />
                        </div>
                        <div class="col-12">
                            <label for="promotion-note" class="form-label">Комментарий для команды</label>
                            <textarea class="form-control" id="promotion-note" rows="3" placeholder="Например: предложить кэшбэк к годовщине посещения"></textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="ri ri-sparkling-2-line me-1"></i>
                                Сохранить акцию
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-xxl-5">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-1">Подсказки по акциям</h5>
                    <p class="text-muted mb-0">Следите за черновиками, актуальными предложениями и их результатами.</p>
                </div>
                <div class="card-body">
                    <div id="promotion-suggestions" class="d-flex flex-column gap-3"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
            <div>
                <h5 class="mb-1">Акции и скидки</h5>
                <p class="text-muted mb-0">Аналитика показывает, сколько клиентов воспользовались предложением и какую выручку оно принесло.</p>
            </div>
            <div class="d-flex flex-wrap gap-2" id="promotion-totals">
                <span class="badge bg-label-success" data-total="active">Активных: 0</span>
                <span class="badge bg-label-secondary" data-total="archived">В архиве: 0</span>
            </div>
        </div>
        <div class="row g-0">
            <div class="col-12 col-lg-6 border-end">
                <div class="p-3">
                    <h6 class="text-muted text-uppercase mb-3">Активные</h6>
                    <div id="promotions-active" class="d-flex flex-column gap-3"></div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="p-3">
                    <h6 class="text-muted text-uppercase mb-3">Архив</h6>
                    <div id="promotions-archived" class="d-flex flex-column gap-3"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4" id="warmup-card" style="display: none;">
        <div class="card-header d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
            <div>
                <h5 class="mb-1">Прогрев «холодных» клиентов</h5>
                <p class="text-muted mb-0">Доступно только на тарифе Elite. ИИ подскажет, кого вернуть прямо сейчас.</p>
            </div>
            <div class="text-muted small" id="warmup-summary"></div>
        </div>
        <div class="card-body">
            <div class="row g-4" id="warmup-groups"></div>
            <div class="mt-4">
                <h6 class="mb-3">Рекомендованные сценарии</h6>
                <div id="warmup-suggestions" class="d-flex flex-column gap-3"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="campaignLaunchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form>
                    <div class="modal-header">
                        <h5 class="modal-title">Запуск кампании</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="launch-campaign-id" />
                        <div class="mb-3">
                            <label for="launch-mode" class="form-label">Режим</label>
                            <select class="form-select" id="launch-mode">
                                <option value="immediate">Отправить сейчас</option>
                                <option value="schedule">Запланировать</option>
                                <option value="test">A/B-тест на малой группе</option>
                            </select>
                        </div>
                        <div class="mb-3" data-launch-field="schedule" style="display: none;">
                            <label for="launch-scheduled-at" class="form-label">Дата и время</label>
                            <input type="datetime-local" class="form-control" id="launch-scheduled-at" />
                        </div>
                        <div class="mb-3" data-launch-field="test" style="display: none;">
                            <label for="launch-test-size" class="form-label">Размер тестовой группы</label>
                            <input type="number" min="0" class="form-control" id="launch-test-size" placeholder="Например, 50" />
                            <div class="form-text">Укажите, скольким клиентам отправить варианты A/B до выбора победителя.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Запустить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="campaignWinnerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form>
                    <div class="modal-header">
                        <h5 class="modal-title">Выбор победителя A/B-теста</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="winner-campaign-id" />
                        <div class="mb-3">
                            <label for="winner-variant" class="form-label">Выберите лучший вариант</label>
                            <select id="winner-variant" class="form-select"></select>
                        </div>
                        <div class="alert alert-info mb-0">
                            После подтверждения рассылка завершится и победитель будет отправлен всей аудитории.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Подтвердить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="promotionUsageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form>
                    <div class="modal-header">
                        <h5 class="modal-title">Зафиксировать использование акции</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="usage-promotion-id" />
                        <div class="mb-3">
                            <label for="usage-revenue" class="form-label">Дополнительная выручка</label>
                            <input type="number" class="form-control" id="usage-revenue" placeholder="0" min="0" step="0.01" />
                        </div>
                        <div class="mb-3">
                            <label for="usage-used-at" class="form-label">Дата использования</label>
                            <input type="datetime-local" class="form-control" id="usage-used-at" />
                        </div>
                        <div class="mb-3">
                            <label for="usage-context" class="form-label">Комментарий</label>
                            <textarea id="usage-context" class="form-control" rows="3" placeholder="Уточните услугу, клиента или номер заказа"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
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

            function apiRequest(url, options) {
                options = options || {};
                var fetchOptions = Object.assign({
                    headers: buildHeaders(options.headers),
                }, options);

                if (fetchOptions.body && !(fetchOptions.body instanceof FormData)) {
                    fetchOptions.headers['Content-Type'] = 'application/json';
                    if (typeof fetchOptions.body !== 'string') {
                        fetchOptions.body = JSON.stringify(fetchOptions.body);
                    }
                }

                return fetch(url, fetchOptions)
                    .then(function (response) {
                        return response
                            .text()
                            .then(function (text) {
                                var data = text ? JSON.parse(text) : {};
                                if (!response.ok) {
                                    var error = new Error(data.message || (data.error ? data.error.message : 'Произошла ошибка'));
                                    error.payload = data;
                                    throw error;
                                }
                                return data;
                            })
                            .catch(function (err) {
                                if (err instanceof SyntaxError) {
                                    if (!response.ok) {
                                        var error = new Error('Произошла ошибка (' + response.status + ')');
                                        error.payload = null;
                                        throw error;
                                    }
                                    return {};
                                }
                                throw err;
                            });
                    });
            }

            function showAlert(type, message) {
                if (!message) return;
                var container = document.getElementById('marketing-alerts');
                if (!container) return;
                var wrapper = document.createElement('div');
                wrapper.className = 'alert alert-' + type + ' alert-dismissible fade show';
                wrapper.setAttribute('role', 'alert');
                wrapper.innerHTML =
                    '<div>' +
                    message +
                    '</div><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                container.appendChild(wrapper);
            }

            function clearAlerts() {
                var container = document.getElementById('marketing-alerts');
                if (container) {
                    container.innerHTML = '';
                }
            }

            var state = {
                plan: 'lite',
                campaignOptions: {
                    channels: [],
                    segments: [],
                    templates: [],
                    clients: [],
                    ab_test_tip: '',
                },
                templatesMap: {},
                campaigns: [],
                campaignsMeta: {
                    totals: { total_campaigns: 0, total_delivered: 0, total_reads: 0, total_clicks: 0 },
                    suggestions: [],
                },
                promotions: [],
                promotionsMeta: {
                    totals: { active: 0, archived: 0 },
                    suggestions: [],
                },
                promotionOptions: {
                    types: [],
                    services: [],
                    categories: [],
                },
                warmup: null,
                warmupMeta: {},
            };

            var CAMPAIGN_STATUS = {
                draft: 'Черновик',
                scheduled: 'Запланирована',
                sending: 'Отправляется',
                testing: 'A/B тест',
                completed: 'Завершена',
                cancelled: 'Отменена',
            };

            var CHANNEL_ICONS = {
                sms: 'ri-chat-3-line',
                email: 'ri-mail-send-line',
                whatsapp: 'ri-whatsapp-line',
            };

            var EMAIL_VARIANT_SUBJECT_WARNING = @json(__('marketing.validation.ab_test_email_subjects'));
            var NO_CHANNELS_TEXT = @json(__('marketing.campaigns.no_channels_available'));

            function formatDateTime(value) {
                if (!value) return '—';
                var date = new Date(value);
                if (Number.isNaN(date.getTime())) return '—';
                return date.toLocaleString();
            }

            function renderPlan(plan) {
                var badge = document.getElementById('marketing-plan-badge');
                if (!badge) return;
                var label = plan ? plan.toUpperCase() : 'LITE';
                badge.innerHTML = '<span class="badge bg-label-primary">Тариф ' + label + '</span>';
            }

            function renderCampaignOptions() {
                var channelSelect = document.getElementById('campaign-channel');
                var segmentSelect = document.getElementById('campaign-segment');
                var templateSelect = document.getElementById('campaign-template');
                var clientSelect = document.getElementById('segment-client-ids');
                var submitButton = document.querySelector('#campaign-create-form button[type="submit"]');
                if (!channelSelect || !segmentSelect || !templateSelect) return;

                var currentChannel = channelSelect.value;
                channelSelect.innerHTML = '';

                if (!state.campaignOptions.channels || state.campaignOptions.channels.length === 0) {
                    var placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = NO_CHANNELS_TEXT;
                    channelSelect.appendChild(placeholder);
                    channelSelect.disabled = true;
                    if (submitButton) submitButton.disabled = true;
                } else {
                    state.campaignOptions.channels.forEach(function (channel) {
                        var option = document.createElement('option');
                        option.value = channel.value;
                        option.textContent = channel.label;
                        channelSelect.appendChild(option);
                    });

                    if (state.campaignOptions.channels.some(function (channel) { return channel.value === currentChannel; })) {
                        channelSelect.value = currentChannel;
                    } else if (channelSelect.options.length) {
                        channelSelect.selectedIndex = 0;
                    }

                    channelSelect.disabled = false;
                    if (submitButton) submitButton.disabled = false;
                }

                var currentSegment = segmentSelect.value;
                segmentSelect.innerHTML = '';
                state.campaignOptions.segments.forEach(function (segment) {
                    var option = document.createElement('option');
                    option.value = segment.value;
                    var label = segment.label;
                    if (segment.count !== undefined) {
                        label += ' (' + segment.count + ')';
                    }
                    option.textContent = label;
                    segmentSelect.appendChild(option);
                });

                if (state.campaignOptions.segments.some(function (segment) { return segment.value === currentSegment; })) {
                    segmentSelect.value = currentSegment;
                }

                templateSelect.innerHTML = '<option value="">— Выберите шаблон —</option>';
                state.templatesMap = {};
                state.campaignOptions.templates.forEach(function (template) {
                    state.templatesMap[template.id] = template;
                    var option = document.createElement('option');
                    option.value = template.id;
                    option.textContent = template.name + ' · ' + template.channel.toUpperCase();
                    templateSelect.appendChild(option);
                });

                if (clientSelect) {
                    var selectedClients = Array.prototype.map.call(clientSelect.selectedOptions || [], function (opt) {
                        return opt.value;
                    });
                    clientSelect.innerHTML = '';

                    state.campaignOptions.clients.forEach(function (client) {
                        var option = document.createElement('option');
                        option.value = String(client.id);
                        var label = client.name || ('Клиент #' + client.id);
                        var details = [];
                        if (client.phone) details.push(client.phone);
                        if (client.email) details.push(client.email);
                        if (details.length) {
                            label += ' · ' + details.join(' / ');
                        }
                        option.textContent = label;
                        if (selectedClients.indexOf(String(client.id)) !== -1) {
                            option.selected = true;
                        }
                        clientSelect.appendChild(option);
                    });
                }

                document.getElementById('campaign-ab-tip').textContent = state.campaignOptions.ab_test_tip || '';

                handleChannelChange();
            }

            function updateSegmentExtraFields(segment) {
                var wrapper = document.getElementById('campaign-segment-extra');
                if (!wrapper) return;

                var serviceField = wrapper.querySelector('[data-segment-field="service_ids"]');
                var masterField = wrapper.querySelector('[data-segment-field="master_ids"]');
                var tagsField = wrapper.querySelector('[data-segment-field="tags"]');
                var clientField = wrapper.querySelector('[data-segment-field="client_ids"]');

                wrapper.style.display = 'none';
                [serviceField, masterField, tagsField, clientField].forEach(function (field) {
                    if (field) field.style.display = 'none';
                });

                if (segment === 'by_service' && serviceField) {
                    wrapper.style.display = '';
                    serviceField.style.display = '';
                } else if (segment === 'by_master' && masterField) {
                    wrapper.style.display = '';
                    masterField.style.display = '';
                } else if (segment === 'custom' && tagsField) {
                    wrapper.style.display = '';
                    tagsField.style.display = '';
                } else if (segment === 'selected' && clientField) {
                    wrapper.style.display = '';
                    clientField.style.display = '';
                }
            }

            function isEmailChannel(channel) {
                return channel === 'email';
            }

            function updateSubjectVisibility(channel, isAbTest) {
                var subjectWrapper = document.getElementById('campaign-subject-wrapper');
                var contentWrapper = document.getElementById('campaign-content-wrapper');
                var subjectInput = document.getElementById('campaign-subject');

                if (contentWrapper) {
                    contentWrapper.style.display = isAbTest ? 'none' : '';
                }

                if (subjectWrapper) {
                    if (isAbTest || !isEmailChannel(channel)) {
                        subjectWrapper.style.display = 'none';
                        if (!isEmailChannel(channel) && subjectInput) {
                            subjectInput.value = '';
                        }
                    } else {
                        subjectWrapper.style.display = '';
                    }
                }
            }

            function updateVariantSubjectVisibility(channel) {
                var variantBlocks = document.querySelectorAll('#variant-list [data-variant]');
                var showSubject = isEmailChannel(channel);

                Array.prototype.forEach.call(variantBlocks, function (block) {
                    var subjectWrapper = block.querySelector('[data-role="variant-subject"]');
                    if (subjectWrapper) {
                        subjectWrapper.style.display = showSubject ? '' : 'none';
                    }
                    if (!showSubject) {
                        var subjectInput = block.querySelector('[data-field="subject"]');
                        if (subjectInput) {
                            subjectInput.value = '';
                        }
                    }
                });
            }

            function handleChannelChange() {
                var channelSelect = document.getElementById('campaign-channel');
                var segmentSelect = document.getElementById('campaign-segment');
                var channel = channelSelect ? channelSelect.value : '';
                var isAbTest = document.getElementById('campaign-is-ab').checked;

                updateSubjectVisibility(channel, isAbTest);
                updateVariantSubjectVisibility(channel);

                if (segmentSelect) {
                    updateSegmentExtraFields(segmentSelect.value);
                }
            }

            function toggleAbTestFields(isAbTest) {
                var variantsContainer = document.getElementById('campaign-variants');
                var variantList = document.getElementById('variant-list');
                var channel = document.getElementById('campaign-channel').value;

                if (variantsContainer) {
                    variantsContainer.style.display = isAbTest ? '' : 'none';
                }

                if (isAbTest) {
                    if (variantList && variantList.children.length === 0) {
                        addVariantBlock();
                        addVariantBlock();
                    }
                } else if (variantList) {
                    variantList.innerHTML = '';
                }

                updateSubjectVisibility(channel, isAbTest);
                updateVariantSubjectVisibility(channel);
            }

            function renderCampaignMetrics() {
                var totals = state.campaignsMeta.totals || {};
                var metricsEl = document.getElementById('campaign-metrics');
                if (!metricsEl) return;
                metricsEl.querySelector('[data-metric="total"]').textContent = 'Кампаний: ' + (totals.total_campaigns || 0);
                metricsEl.querySelector('[data-metric="delivered"]').textContent = 'Доставлено: ' + (totals.total_delivered || 0);
                metricsEl.querySelector('[data-metric="read"]').textContent = 'Прочитано: ' + (totals.total_reads || 0);
                metricsEl.querySelector('[data-metric="clicks"]').textContent = 'Переходы: ' + (totals.total_clicks || 0);
            }

            function buildCampaignRow(campaign) {
                var channelOption = state.campaignOptions.channels.find(function (c) {
                    return c.value === campaign.channel;
                });
                var segmentOption = state.campaignOptions.segments.find(function (s) {
                    return s.value === campaign.segment;
                });
                var icon = CHANNEL_ICONS[campaign.channel] || 'ri-megaphone-line';
                var status = CAMPAIGN_STATUS[campaign.status] || campaign.status;
                var metrics = campaign.metrics || {};

                var variantsHtml = '';
                if (campaign.variants && campaign.variants.length > 0) {
                    variantsHtml =
                        '<div class="small text-muted">Вариантов: ' + campaign.variants.length +
                        (campaign.winning_variant_id ? ', победитель выбран' : '') + '</div>';
                }

                return (
                    '<tr data-id="' + campaign.id + '">' +
                    '<td>' +
                    '<div class="d-flex align-items-start gap-3">' +
                    '<div class="avatar flex-shrink-0"><span class="avatar-initial bg-label-primary"><i class="' + icon + '"></i></span></div>' +
                    '<div>' +
                    '<div class="fw-semibold">' + campaign.name + '</div>' +
                    '<div class="text-muted small">' +
                    (channelOption ? channelOption.label : campaign.channel.toUpperCase()) +
                    ' • ' +
                    (segmentOption ? segmentOption.label : campaign.segment) +
                    '</div>' +
                    '<div class="text-muted small">Создана: ' + formatDateTime(campaign.created_at) + '</div>' +
                    variantsHtml +
                    '</div>' +
                    '</div>' +
                    '</td>' +
                    '<td>' +
                    '<span class="badge bg-label-secondary">' + status + '</span>' +
                    '<div class="small text-muted">Запланировано: ' + formatDateTime(campaign.scheduled_at) + '</div>' +
                    '</td>' +
                    '<td>' +
                    '<div class="small">Доставлено: <strong>' + (metrics.delivered || 0) + '</strong></div>' +
                    '<div class="small">Открытия: <strong>' + (metrics.read || 0) + '</strong> (' + ((metrics.open_rate || 0) * 100).toFixed(0) + '%)</div>' +
                    '<div class="small">Переходы: <strong>' + (metrics.clicks || 0) + '</strong> (' + ((metrics.ctr || 0) * 100).toFixed(0) + '%)</div>' +
                    '</td>' +
                    '<td class="text-end">' +
                    '<div class="btn-group btn-group-sm" role="group">' +
                    '<button class="btn btn-outline-primary" data-action="launch">Запустить</button>' +
                    (campaign.is_ab_test ? '<button class="btn btn-outline-info" data-action="winner">Победитель</button>' : '') +
                    '<button class="btn btn-outline-danger" data-action="delete">Удалить</button>' +
                    '</div>' +
                    '</td>' +
                    '</tr>'
                );
            }

            function renderCampaigns() {
                var tbody = document.getElementById('campaigns-table');
                if (!tbody) return;
                if (!state.campaigns || state.campaigns.length === 0) {
                    tbody.innerHTML =
                        '<tr><td colspan="4" class="text-center text-muted py-4">Пока нет кампаний. Создайте первую, чтобы вернуть клиентов.</td></tr>';
                    return;
                }
                tbody.innerHTML = state.campaigns.map(buildCampaignRow).join('');
            }

            function renderCampaignSuggestions() {
                var container = document.getElementById('campaign-suggestions');
                if (!container) return;
                var suggestions = state.campaignsMeta.suggestions || [];
                if (suggestions.length === 0) {
                    container.innerHTML = '<div class="text-muted">Подсказок пока нет — запустите кампанию и мы начнём анализ.</div>';
                    return;
                }
                container.innerHTML = suggestions
                    .map(function (item) {
                        return (
                            '<div class="border rounded-2 p-3">' +
                            '<div class="fw-semibold mb-1">' + item.title + '</div>' +
                            '<div class="text-muted small mb-0">' + item.description + '</div>' +
                            '</div>'
                        );
                    })
                    .join('');
            }

            function buildPromotionCard(promotion) {
                var metrics = promotion.metrics || {};
                var typeLabel = '';
                var serviceName = null;
                var categoryName = null;

                if (promotion.service_id) {
                    var serviceMatch = state.promotionOptions.services.find(function (service) {
                        return Number(service.id) === Number(promotion.service_id);
                    });
                    serviceName = serviceMatch ? serviceMatch.name : null;
                }

                if (promotion.service_category_id) {
                    var categoryMatch = state.promotionOptions.categories.find(function (category) {
                        return Number(category.id) === Number(promotion.service_category_id);
                    });
                    categoryName = categoryMatch ? categoryMatch.name : null;
                }

                switch (promotion.type) {
                    case 'order_percent':
                        typeLabel = (promotion.percent || 0) + '% на весь заказ';
                        break;
                    case 'service_percent':
                        typeLabel = (promotion.percent || 0) + '% на услугу' + (serviceName ? ' «' + serviceName + '»' : '');
                        break;
                    case 'category_percent':
                        typeLabel = (promotion.percent || 0) + '% на категорию' + (categoryName ? ' «' + categoryName + '»' : '');
                        break;
                    case 'free_service':
                        typeLabel = 'Бесплатная услуга' + (serviceName ? ' «' + serviceName + '»' : '');
                        break;
                    default:
                        typeLabel = 'Специальное предложение';
                        break;
                }

                var now = new Date();
                var statusBadge;
                if (promotion.is_archived) {
                    statusBadge = '<span class="badge bg-label-secondary">Архив</span>';
                } else if (promotion.is_active) {
                    statusBadge = '<span class="badge bg-label-success">Активна</span>';
                } else if (promotion.starts_at && new Date(promotion.starts_at) > now) {
                    statusBadge = '<span class="badge bg-label-info">Запланирована</span>';
                } else {
                    statusBadge = '<span class="badge bg-label-warning">Готова</span>';
                }

                var note = (promotion.metadata && promotion.metadata.note) || '';

                return (
                    '<div class="border rounded-2 p-3" data-id="' + promotion.id + '">' +
                    '<div class="d-flex justify-content-between align-items-start mb-2">' +
                    '<div>' +
                    '<div class="fw-semibold">' + promotion.name + '</div>' +
                    '<div class="text-muted small">' + (typeLabel || 'Специальное предложение') + '</div>' +
                    '</div>' +
                    statusBadge +
                    '</div>' +
                    '<div class="text-muted small mb-2">' + (note || 'Без доп. комментариев') + '</div>' +
                    '<div class="row g-2 text-center small mb-3">' +
                    '<div class="col"><div class="fw-semibold">' + (metrics.usage_count || 0) + '</div><div class="text-muted">Использований</div></div>' +
                    '<div class="col"><div class="fw-semibold">' + (metrics.unique_clients || 0) + '</div><div class="text-muted">Клиентов</div></div>' +
                    '<div class="col"><div class="fw-semibold">' + ((metrics.revenue_generated || 0).toFixed ? metrics.revenue_generated.toFixed(2) : metrics.revenue_generated || 0) + '</div><div class="text-muted">Выручка</div></div>' +
                    '</div>' +
                    '<div class="d-flex flex-wrap gap-2">' +
                    (!promotion.is_archived
                        ? '<button class="btn btn-sm btn-outline-success" data-action="usage">Зафиксировать использование</button>'
                        : '') +
                    (!promotion.is_archived
                        ? '<button class="btn btn-sm btn-outline-secondary" data-action="archive">В архив</button>'
                        : '') +
                    '</div>' +
                    '</div>'
                );
            }

            function renderPromotions() {
                var activeContainer = document.getElementById('promotions-active');
                var archivedContainer = document.getElementById('promotions-archived');
                if (!activeContainer || !archivedContainer) return;

                var active = state.promotions.filter(function (promo) {
                    return !promo.is_archived;
                });
                var archived = state.promotions.filter(function (promo) {
                    return promo.is_archived;
                });

                activeContainer.innerHTML = active.length
                    ? active.map(buildPromotionCard).join('')
                    : '<div class="text-muted">Нет активных акций — создайте предложение и привлеките клиентов.</div>';
                archivedContainer.innerHTML = archived.length
                    ? archived.map(buildPromotionCard).join('')
                    : '<div class="text-muted">Архив пуст. Завершённые акции появятся здесь.</div>';

                var totals = state.promotionsMeta.totals || {};
                var totalsEl = document.getElementById('promotion-totals');
                if (totalsEl) {
                    totalsEl.querySelector('[data-total="active"]').textContent = 'Активных: ' + (totals.active || 0);
                    totalsEl.querySelector('[data-total="archived"]').textContent = 'В архиве: ' + (totals.archived || 0);
                }
            }

            function renderPromotionSuggestions() {
                var container = document.getElementById('promotion-suggestions');
                if (!container) return;
                var suggestions = state.promotionsMeta.suggestions || [];
                if (suggestions.length === 0) {
                    container.innerHTML = '<div class="text-muted">Подсказок пока нет — запустите акцию и возвращайтесь за аналитикой.</div>';
                    return;
                }
                container.innerHTML = suggestions
                    .map(function (item) {
                        return (
                            '<div class="border rounded-2 p-3">' +
                            '<div class="fw-semibold mb-1">' + item.title + '</div>' +
                            '<div class="text-muted small mb-0">' + item.description + '</div>' +
                            '</div>'
                        );
                    })
                    .join('');
            }

            function renderWarmup() {
                var card = document.getElementById('warmup-card');
                if (!card) return;
                if (state.plan !== 'elite') {
                    card.style.display = 'none';
                    return;
                }

                card.style.display = '';
                var summary = document.getElementById('warmup-summary');
                if (summary) {
                    summary.textContent = state.warmupMeta.ai_summary || '';
                }

                var groupsContainer = document.getElementById('warmup-groups');
                var suggestionsContainer = document.getElementById('warmup-suggestions');
                if (!groupsContainer || !suggestionsContainer) return;

                groupsContainer.innerHTML = '';
                suggestionsContainer.innerHTML = '';

                if (!state.warmup || !state.warmup.groups) {
                    groupsContainer.innerHTML = '<div class="col-12 text-muted">Данных пока нет.</div>';
                    return;
                }

                var groups = state.warmup.groups;
                var mapping = [
                    { key: 'almost_sleeping', title: 'Скоро уснут', icon: 'ri-alarm-warning-line' },
                    { key: 'sleeping', title: 'Уже спят', icon: 'ri-moon-line' },
                    { key: 'new_idle', title: 'Новые, но не дошли', icon: 'ri-user-time-line' },
                ];

                mapping.forEach(function (item) {
                    var clients = groups[item.key] || [];
                    var html =
                        '<div class="card h-100">' +
                        '<div class="card-body">' +
                        '<div class="d-flex align-items-center gap-2 mb-2">' +
                        '<span class="badge bg-label-warning"><i class="' + item.icon + '"></i></span>' +
                        '<h6 class="mb-0">' + item.title + ' (' + clients.length + ')</h6>' +
                        '</div>' +
                        '<div class="d-flex flex-column gap-2" style="max-height: 220px; overflow-y: auto;">';
                    if (clients.length === 0) {
                        html += '<div class="text-muted small">Пока пусто</div>';
                    } else {
                        html += clients
                            .map(function (client) {
                                return (
                                    '<div class="border rounded-2 px-2 py-1">' +
                                    '<div class="fw-semibold small">' + client.name + '</div>' +
                                    '<div class="text-muted small">' +
                                    (client.days_since_touch !== undefined ? client.days_since_touch + ' дн. без визита' : '—') +
                                    '</div>' +
                                    '</div>'
                                );
                            })
                            .join('');
                    }
                    html += '</div></div></div>';
                    var col = document.createElement('div');
                    col.className = 'col-12 col-lg-4';
                    col.innerHTML = html;
                    groupsContainer.appendChild(col);
                });

                var suggestions = (state.warmup && state.warmup.suggestions) || [];
                if (suggestions.length === 0) {
                    suggestionsContainer.innerHTML = '<div class="text-muted">Подсказок пока нет.</div>';
                } else {
                    suggestionsContainer.innerHTML = suggestions
                        .map(function (item) {
                            return (
                                '<div class="border rounded-2 p-3">' +
                                '<div class="fw-semibold mb-1">' + item.title + '</div>' +
                                '<div class="text-muted small mb-2">' + item.description + '</div>' +
                                (item.recommended_action
                                    ? '<div class="badge bg-label-primary">' + item.recommended_action + '</div>'
                                    : '') +
                                '</div>'
                            );
                        })
                        .join('');
                }
            }

            function fetchCampaigns() {
                return apiRequest('/api/v1/marketing/campaigns').then(function (data) {
                    state.campaigns = data.data || [];
                    state.campaignsMeta = data.meta || state.campaignsMeta;
                    renderCampaigns();
                    renderCampaignMetrics();
                    renderCampaignSuggestions();
                });
            }

            function fetchCampaignOptions() {
                return apiRequest('/api/v1/marketing/campaigns/options').then(function (data) {
                    state.campaignOptions = data.data || state.campaignOptions;
                    renderCampaignOptions();
                    updateSegmentExtraFields(document.getElementById('campaign-segment').value);
                    renderCampaigns();
                });
            }

            function fetchPromotions() {
                return apiRequest('/api/v1/marketing/promotions').then(function (data) {
                    state.promotions = data.data || [];
                    state.promotionsMeta = data.meta || state.promotionsMeta;
                    renderPromotions();
                    renderPromotionSuggestions();
                });
            }

            function fetchPromotionOptions() {
                return apiRequest('/api/v1/marketing/promotions/options').then(function (data) {
                    var options = data.data || {};
                    state.promotionOptions = {
                        types: options.types || [],
                        services: options.services || [],
                        categories: options.categories || [],
                    };

                    var typeSelect = document.getElementById('promotion-type');
                    var serviceSelect = document.getElementById('promotion-service');
                    var categorySelect = document.getElementById('promotion-category');

                    if (typeSelect) {
                        typeSelect.innerHTML = '';
                        state.promotionOptions.types.forEach(function (option) {
                            var opt = document.createElement('option');
                            opt.value = option.value;
                            opt.textContent = option.label;
                            typeSelect.appendChild(opt);
                        });
                    }

                    if (serviceSelect) {
                        serviceSelect.innerHTML = '<option value="">Выберите услугу</option>';
                        state.promotionOptions.services.forEach(function (service) {
                            var opt = document.createElement('option');
                            opt.value = service.id;
                            opt.textContent = service.name;
                            serviceSelect.appendChild(opt);
                        });
                    }

                    if (categorySelect) {
                        categorySelect.innerHTML = '<option value="">Выберите категорию</option>';
                        state.promotionOptions.categories.forEach(function (category) {
                            var opt = document.createElement('option');
                            opt.value = category.id;
                            opt.textContent = category.name;
                            categorySelect.appendChild(opt);
                        });
                    }

                    handlePromotionTypeChange(typeSelect ? typeSelect.value : null);
                    renderPromotions();
                });
            }

            function fetchWarmup() {
                if (state.plan !== 'elite') {
                    renderWarmup();
                    return Promise.resolve();
                }
                return apiRequest('/api/v1/marketing/warmup')
                    .then(function (data) {
                        state.warmup = data.data || null;
                        state.warmupMeta = data.meta || {};
                        renderWarmup();
                    })
                    .catch(function (error) {
                        renderWarmup();
                        showAlert('warning', error.message);
                    });
            }

            function fetchPlan() {
                return apiRequest('/api/v1/auth/me')
                    .then(function (data) {
                        var user = data.user || {};
                        var slug = user.plan && user.plan.slug ? String(user.plan.slug).toLowerCase() : null;
                        if (!slug && user.plans && user.plans.length) {
                            slug = String(user.plans[0].slug || user.plans[0].name || 'lite').toLowerCase();
                        }
                        if (['lite', 'pro', 'elite'].indexOf(slug) === -1) {
                            slug = 'lite';
                        }
                        state.plan = slug;
                        renderPlan(slug);
                    })
                    .catch(function () {
                        state.plan = 'lite';
                        renderPlan('lite');
                    });
            }

            function parseIds(input) {
                if (!input) return [];
                return input
                    .split(',')
                    .map(function (value) {
                        return parseInt(value.trim(), 10);
                    })
                    .filter(function (value) {
                        return !Number.isNaN(value);
                    });
            }

            function parseTags(input) {
                if (!input) return [];
                return input
                    .split(',')
                    .map(function (tag) {
                        return tag.trim();
                    })
                    .filter(function (tag) {
                        return tag !== '';
                    });
            }

            function collectVariants() {
                var variantsContainer = document.getElementById('variant-list');
                if (!variantsContainer) return [];
                var blocks = variantsContainer.querySelectorAll('[data-variant]');
                return Array.prototype.map.call(blocks, function (block, index) {
                    var label = String.fromCharCode(65 + index);
                    var subjectInput = block.querySelector('[data-field="subject"]');
                    var subject = subjectInput ? subjectInput.value.trim() : '';
                    var content = block.querySelector('[data-field="content"]').value.trim();
                    var sampleSize = block.querySelector('[data-field="sample_size"]').value;
                    return {
                        label: label,
                        subject: subject || null,
                        content: content,
                        sample_size: sampleSize ? parseInt(sampleSize, 10) : null,
                    };
                }).filter(function (variant) {
                    return variant.content;
                });
            }

            function addVariantBlock(defaults) {
                var container = document.getElementById('variant-list');
                if (!container) return;
                var count = container.querySelectorAll('[data-variant]').length;
                if (count >= 5) return;
                var label = String.fromCharCode(65 + count);
                var variantNumber = count + 1;
                var div = document.createElement('div');
                div.className = 'col-12';
                div.setAttribute('data-variant', label);
                div.innerHTML =
                    '<div class="border rounded-2 p-3 position-relative">' +
                    '<button type="button" class="btn btn-sm btn-icon btn-outline-danger position-absolute top-0 end-0 m-2" data-action="remove-variant">' +
                    '<i class="ri ri-close-line"></i></button>' +
                    '<div class="row g-2">' +
                    '<div class="col-sm-4" data-role="variant-subject">' +
                    '<label class="form-label">Тема варианта ' + variantNumber + '</label>' +
                    '<input type="text" class="form-control" data-field="subject" placeholder="Например, «Мы скучаем»" value="' + (defaults && defaults.subject ? defaults.subject : '') + '" />' +
                    '</div>' +
                    '<div class="col-sm-4">' +
                    '<label class="form-label">Размер выборки</label>' +
                    '<input type="number" class="form-control" min="0" data-field="sample_size" value="' + (defaults && defaults.sample_size ? defaults.sample_size : '') + '" />' +
                    '</div>' +
                    '<div class="col-12">' +
                    '<label class="form-label">Текст варианта ' + variantNumber + '</label>' +
                    '<textarea class="form-control" rows="3" data-field="content" placeholder="Опишите предложение для клиентов">' + (defaults && defaults.content ? defaults.content : '') + '</textarea>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                container.appendChild(div);
                updateVariantSubjectVisibility(document.getElementById('campaign-channel').value);
            }

            function resetCampaignForm() {
                var form = document.getElementById('campaign-create-form');
                if (form) {
                    form.reset();
                }
                document.getElementById('campaign-content').value = '';
                document.getElementById('campaign-subject').value = '';
                document.getElementById('variant-list').innerHTML = '';
                document.getElementById('campaign-variants').style.display = 'none';
                document.getElementById('campaign-is-ab').checked = false;
                toggleAbTestFields(false);
                handleChannelChange();
            }

            function handleCampaignFormSubmit(event) {
                event.preventDefault();
                clearAlerts();
                var isAb = document.getElementById('campaign-is-ab').checked;
                var payload = {
                    name: document.getElementById('campaign-name').value.trim(),
                    channel: document.getElementById('campaign-channel').value,
                    segment: document.getElementById('campaign-segment').value,
                    template_id: document.getElementById('campaign-template').value || null,
                    subject: document.getElementById('campaign-subject').value.trim() || null,
                    content: document.getElementById('campaign-content').value.trim(),
                    status: document.getElementById('campaign-status').value,
                    scheduled_at: document.getElementById('campaign-scheduled').value || null,
                    test_group_size: document.getElementById('campaign-test-size').value || null,
                    is_ab_test: isAb,
                };

                if (payload.channel !== 'email' || isAb) {
                    delete payload.subject;
                }

                var segment = payload.segment;
                var filters = {};
                if (segment === 'by_service') {
                    var serviceIds = parseIds(document.getElementById('segment-service-ids').value);
                    if (serviceIds.length) {
                        filters.service_ids = serviceIds;
                    }
                } else if (segment === 'by_master') {
                    var masterIds = parseIds(document.getElementById('segment-master-ids').value);
                    if (masterIds.length) {
                        filters.master_ids = masterIds;
                    }
                } else if (segment === 'custom') {
                    var tags = parseTags(document.getElementById('segment-tags').value);
                    if (tags.length) {
                        filters.tags = tags;
                    }
                } else if (segment === 'selected') {
                    var clientSelect = document.getElementById('segment-client-ids');
                    var clientIds = [];
                    if (clientSelect) {
                        clientIds = Array.prototype.filter.call(clientSelect.options, function (option) {
                            return option.selected;
                        }).map(function (option) {
                            return parseInt(option.value, 10);
                        }).filter(function (value) {
                            return !Number.isNaN(value);
                        });
                    }

                    if (clientIds.length === 0) {
                        showAlert('warning', 'Выберите хотя бы одного клиента.');
                        return;
                    }

                    filters.client_ids = clientIds;
                }
                if (Object.keys(filters).length > 0) {
                    payload.segment_filters = filters;
                }

                if (payload.test_group_size) {
                    payload.test_group_size = parseInt(payload.test_group_size, 10);
                } else {
                    delete payload.test_group_size;
                }

                if (payload.scheduled_at === '') {
                    delete payload.scheduled_at;
                }

                if (payload.template_id === null || payload.template_id === '') {
                    delete payload.template_id;
                }

                if (!payload.content && !isAb) {
                    showAlert('warning', 'Добавьте текст сообщения или варианты для A/B-теста.');
                    return;
                }

                if (isAb) {
                    var variants = collectVariants();
                    if (variants.length < 2) {
                        showAlert('warning', 'Для A/B-теста добавьте как минимум два варианта.');
                        return;
                    }

                    if (payload.channel === 'email') {
                        var missingSubject = variants.some(function (variant) {
                            return !variant.subject;
                        });
                        if (missingSubject) {
                            showAlert('warning', EMAIL_VARIANT_SUBJECT_WARNING);
                            return;
                        }
                    }

                    payload.variants = variants;
                    delete payload.content;
                    delete payload.subject;
                }

                apiRequest('/api/v1/marketing/campaigns', {
                    method: 'POST',
                    body: payload,
                })
                    .then(function (data) {
                        showAlert('success', data.message || 'Кампания сохранена.');
                        resetCampaignForm();
                        return fetchCampaigns();
                    })
                    .catch(function (error) {
                        showAlert('danger', error.message || 'Не удалось сохранить кампанию.');
                    });
            }

            function handlePromotionTypeChange(type) {
                var percentField = document.querySelector('[data-promotion-field="percent"]');
                var serviceField = document.querySelector('[data-promotion-field="service"]');
                var categoryField = document.querySelector('[data-promotion-field="category"]');

                if (percentField) {
                    percentField.style.display =
                        type === 'order_percent' || type === 'service_percent' || type === 'category_percent'
                            ? ''
                            : 'none';
                }

                if (serviceField) {
                    serviceField.style.display =
                        type === 'service_percent' || type === 'free_service' ? '' : 'none';
                }

                if (categoryField) {
                    categoryField.style.display = type === 'category_percent' ? '' : 'none';
                }
            }

            function resetPromotionForm() {
                var form = document.getElementById('promotion-form');
                if (form) {
                    form.reset();
                }
                var typeSelect = document.getElementById('promotion-type');
                if (typeSelect && typeSelect.options.length) {
                    typeSelect.selectedIndex = 0;
                }
                handlePromotionTypeChange(typeSelect ? typeSelect.value : null);
            }

            function handlePromotionFormSubmit(event) {
                event.preventDefault();
                clearAlerts();
                var payload = {
                    name: document.getElementById('promotion-name').value.trim(),
                    type: document.getElementById('promotion-type').value,
                    promo_code: document.getElementById('promotion-code').value.trim() || null,
                    usage_limit: document.getElementById('promotion-usage-limit').value || null,
                    starts_at: document.getElementById('promotion-start').value || null,
                    ends_at: document.getElementById('promotion-end').value || null,
                };

                if (payload.usage_limit) {
                    payload.usage_limit = parseInt(payload.usage_limit, 10);
                } else {
                    delete payload.usage_limit;
                }

                var note = document.getElementById('promotion-note').value.trim();
                if (note) {
                    payload.metadata = { note: note };
                }

                if (
                    payload.type === 'order_percent' ||
                    payload.type === 'service_percent' ||
                    payload.type === 'category_percent'
                ) {
                    var percentValue = document.getElementById('promotion-percent').value;
                    if (!percentValue) {
                        showAlert('warning', 'Укажите процент кэшбэка.');
                        return;
                    }
                    payload.percent = parseFloat(percentValue);
                }

                if (payload.type === 'service_percent' || payload.type === 'free_service') {
                    var serviceId = parseInt(document.getElementById('promotion-service').value || '0', 10);
                    if (!serviceId) {
                        showAlert('warning', 'Выберите услугу для акции.');
                        return;
                    }
                    payload.service_id = serviceId;
                }

                if (payload.type === 'category_percent') {
                    var categoryId = parseInt(document.getElementById('promotion-category').value || '0', 10);
                    if (!categoryId) {
                        showAlert('warning', 'Выберите категорию услуг.');
                        return;
                    }
                    payload.service_category_id = categoryId;
                }

                apiRequest('/api/v1/marketing/promotions', {
                    method: 'POST',
                    body: payload,
                })
                    .then(function (data) {
                        showAlert('success', data.message || 'Акция сохранена.');
                        resetPromotionForm();
                        return fetchPromotions();
                    })
                    .catch(function (error) {
                        showAlert('danger', error.message || 'Не удалось сохранить акцию.');
                    });
            }

            var launchModalEl = document.getElementById('campaignLaunchModal');
            var launchModal = launchModalEl ? new bootstrap.Modal(launchModalEl) : null;
            var winnerModalEl = document.getElementById('campaignWinnerModal');
            var winnerModal = winnerModalEl ? new bootstrap.Modal(winnerModalEl) : null;
            var usageModalEl = document.getElementById('promotionUsageModal');
            var usageModal = usageModalEl ? new bootstrap.Modal(usageModalEl) : null;

            function openLaunchModal(campaignId) {
                if (!launchModalEl) return;
                launchModalEl.querySelector('#launch-campaign-id').value = campaignId;
                launchModalEl.querySelector('#launch-mode').value = 'immediate';
                launchModalEl.querySelector('#launch-scheduled-at').value = '';
                launchModalEl.querySelector('#launch-test-size').value = '';
                launchModalEl.querySelector('[data-launch-field="schedule"]').style.display = 'none';
                launchModalEl.querySelector('[data-launch-field="test"]').style.display = 'none';
                launchModal.show();
            }

            function openWinnerModal(campaignId) {
                if (!winnerModalEl) return;
                var select = winnerModalEl.querySelector('#winner-variant');
                select.innerHTML = '';
                var campaign = state.campaigns.find(function (item) {
                    return item.id === campaignId;
                });
                if (!campaign) return;
                campaign.variants.forEach(function (variant) {
                    var option = document.createElement('option');
                    option.value = variant.id;
                    option.textContent = variant.label + ' · открытий ' + variant.read_count + ', CTR ' + ((variant.ctr || 0) * 100).toFixed(0) + '%';
                    select.appendChild(option);
                });
                winnerModalEl.querySelector('#winner-campaign-id').value = campaignId;
                winnerModal.show();
            }

            function openUsageModal(promotionId) {
                if (!usageModalEl) return;
                usageModalEl.querySelector('#usage-promotion-id').value = promotionId;
                usageModalEl.querySelector('#usage-revenue').value = '';
                var now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                usageModalEl.querySelector('#usage-used-at').value = now.toISOString().slice(0, 16);
                usageModalEl.querySelector('#usage-context').value = '';
                usageModal.show();
            }

            function handleCampaignTableClick(event) {
                var button = event.target.closest('[data-action]');
                if (!button) return;
                var row = button.closest('tr[data-id]');
                if (!row) return;
                var id = parseInt(row.getAttribute('data-id'), 10);
                if (button.dataset.action === 'launch') {
                    openLaunchModal(id);
                } else if (button.dataset.action === 'winner') {
                    openWinnerModal(id);
                } else if (button.dataset.action === 'delete') {
                    if (confirm('Удалить кампанию?')) {
                        apiRequest('/api/v1/marketing/campaigns/' + id, { method: 'DELETE' })
                            .then(function (data) {
                                showAlert('success', data.message || 'Кампания удалена.');
                                return fetchCampaigns();
                            })
                            .catch(function (error) {
                                showAlert('danger', error.message || 'Не удалось удалить кампанию.');
                            });
                    }
                }
            }

            function handlePromotionsClick(event) {
                var button = event.target.closest('[data-action]');
                if (!button) return;
                var card = button.closest('[data-id]');
                if (!card) return;
                var id = parseInt(card.getAttribute('data-id'), 10);
                if (button.dataset.action === 'archive') {
                    if (!confirm('Отправить акцию в архив?')) return;
                    apiRequest('/api/v1/marketing/promotions/' + id + '/archive', { method: 'POST' })
                        .then(function (data) {
                            showAlert('success', data.message || 'Акция архивирована.');
                            return fetchPromotions();
                        })
                        .catch(function (error) {
                            showAlert('danger', error.message || 'Не удалось обновить акцию.');
                        });
                } else if (button.dataset.action === 'usage') {
                    openUsageModal(id);
                }
            }

            function handleLaunchModeChange(event) {
                var mode = event.target.value;
                var scheduleField = launchModalEl.querySelector('[data-launch-field="schedule"]');
                var testField = launchModalEl.querySelector('[data-launch-field="test"]');
                scheduleField.style.display = mode === 'schedule' ? '' : 'none';
                testField.style.display = mode === 'test' ? '' : 'none';
            }

            function handleLaunchSubmit(event) {
                event.preventDefault();
                var campaignId = launchModalEl.querySelector('#launch-campaign-id').value;
                var mode = launchModalEl.querySelector('#launch-mode').value;
                var payload = { mode: mode };
                if (mode === 'schedule') {
                    payload.scheduled_at = launchModalEl.querySelector('#launch-scheduled-at').value;
                }
                if (mode === 'test') {
                    payload.test_group_size = parseInt(launchModalEl.querySelector('#launch-test-size').value || '0', 10);
                }
                apiRequest('/api/v1/marketing/campaigns/' + campaignId + '/launch', {
                    method: 'POST',
                    body: payload,
                })
                    .then(function (data) {
                        showAlert('success', data.message || 'Запуск запланирован.');
                        launchModal.hide();
                        return fetchCampaigns();
                    })
                    .catch(function (error) {
                        showAlert('danger', error.message || 'Не удалось запустить кампанию.');
                    });
            }

            function handleWinnerSubmit(event) {
                event.preventDefault();
                var campaignId = winnerModalEl.querySelector('#winner-campaign-id').value;
                var variantId = winnerModalEl.querySelector('#winner-variant').value;
                apiRequest('/api/v1/marketing/campaigns/' + campaignId + '/winner', {
                    method: 'POST',
                    body: { variant_id: parseInt(variantId, 10) },
                })
                    .then(function (data) {
                        showAlert('success', data.message || 'Победитель выбран.');
                        winnerModal.hide();
                        return fetchCampaigns();
                    })
                    .catch(function (error) {
                        showAlert('danger', error.message || 'Не удалось выбрать победителя.');
                    });
            }

            function handleUsageSubmit(event) {
                event.preventDefault();
                var promotionId = usageModalEl.querySelector('#usage-promotion-id').value;
                var payload = {
                    revenue: parseFloat(usageModalEl.querySelector('#usage-revenue').value || '0') || 0,
                    used_at: usageModalEl.querySelector('#usage-used-at').value,
                    context: usageModalEl.querySelector('#usage-context').value.trim() || null,
                };
                apiRequest('/api/v1/marketing/promotions/' + promotionId + '/usage', {
                    method: 'POST',
                    body: payload,
                })
                    .then(function (data) {
                        showAlert('success', data.message || 'Использование акции зафиксировано.');
                        usageModal.hide();
                        return fetchPromotions();
                    })
                    .catch(function (error) {
                        showAlert('danger', error.message || 'Не удалось сохранить использование.');
                    });
            }

            document.getElementById('campaign-create-form').addEventListener('submit', handleCampaignFormSubmit);
            document.getElementById('promotion-form').addEventListener('submit', handlePromotionFormSubmit);
            document.getElementById('campaign-channel').addEventListener('change', handleChannelChange);
            document.getElementById('campaign-segment').addEventListener('change', function (event) {
                updateSegmentExtraFields(event.target.value);
            });
            document.getElementById('campaign-template').addEventListener('change', function (event) {
                var template = state.templatesMap[event.target.value];
                if (template) {
                    var channel = document.getElementById('campaign-channel').value;
                    if (isEmailChannel(channel) && !document.getElementById('campaign-subject').value) {
                        document.getElementById('campaign-subject').value = template.subject || '';
                    }
                    document.getElementById('campaign-content').value = template.content || '';
                }
            });
            document.getElementById('campaign-is-ab').addEventListener('change', function (event) {
                toggleAbTestFields(event.target.checked);
            });
            document.getElementById('variant-add').addEventListener('click', function () {
                addVariantBlock();
            });
            document.getElementById('variant-list').addEventListener('click', function (event) {
                var button = event.target.closest('[data-action="remove-variant"]');
                if (!button) return;
                var block = button.closest('[data-variant]');
                if (block) {
                    block.remove();
                }
            });
            document.getElementById('promotion-type').addEventListener('change', function (event) {
                handlePromotionTypeChange(event.target.value);
            });

            toggleAbTestFields(document.getElementById('campaign-is-ab').checked);
            handleChannelChange();

            document.getElementById('campaigns-table').addEventListener('click', handleCampaignTableClick);
            document.getElementById('promotions-active').addEventListener('click', handlePromotionsClick);
            document.getElementById('promotions-archived').addEventListener('click', handlePromotionsClick);

            if (launchModalEl) {
                launchModalEl.querySelector('#launch-mode').addEventListener('change', handleLaunchModeChange);
                launchModalEl.querySelector('form').addEventListener('submit', handleLaunchSubmit);
            }
            if (winnerModalEl) {
                winnerModalEl.querySelector('form').addEventListener('submit', handleWinnerSubmit);
            }
            if (usageModalEl) {
                usageModalEl.querySelector('form').addEventListener('submit', handleUsageSubmit);
            }

            Promise.resolve()
                .then(fetchPlan)
                .then(function () {
                    return Promise.all([
                        fetchCampaignOptions(),
                        fetchCampaigns(),
                        fetchPromotionOptions(),
                        fetchPromotions(),
                    ]);
                })
                .then(fetchWarmup)
                .catch(function (error) {
                    showAlert('danger', error.message || 'Не удалось загрузить маркетинговые данные.');
                });
        });
    </script>
@endsection
