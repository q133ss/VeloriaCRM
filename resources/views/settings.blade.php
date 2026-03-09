@extends('layouts.app')
@section('content')
    <style>
        .settings-hero {
            border: 1px solid rgba(var(--bs-primary-rgb), 0.16);
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.16), transparent 28%),
                linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08), rgba(var(--bs-body-bg-rgb), 0.02));
        }

        .settings-anchor-nav .nav-link {
            border-radius: 999px;
        }

        .settings-card {
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
        }

        .settings-section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .settings-meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.75rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb), 0.08);
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--bs-body-color);
        }

        .settings-compact-note {
            padding: 0.9rem 1rem;
            border-radius: 1rem;
            background: rgba(var(--bs-body-color-rgb), 0.04);
            height: 100%;
        }

        .settings-password-toggle {
            border: 1px dashed rgba(var(--bs-body-color-rgb), 0.14);
            border-radius: 1rem;
        }

        .settings-password-fields {
            display: none;
        }

        .settings-password-fields.is-visible {
            display: flex;
        }

        .settings-danger-card {
            border: 1px solid rgba(var(--bs-danger-rgb), 0.18);
        }

        .settings-work-table th,
        .settings-work-table td {
            vertical-align: middle;
        }

        .settings-summary-card {
            position: sticky;
            top: 1.5rem;
        }
    </style>

    <div class="row g-6">
        <div class="col-12">
            <div class="card border-0 shadow-sm settings-hero">
                <div class="card-body p-6 p-lg-8">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-5">
                        <div class="mw-lg-50">
                            <span class="badge bg-label-primary mb-3">Veloria Profile</span>
                            <h3 class="mb-2">{{ __('menu.settings') }}</h3>
                            <p class="text-muted mb-0">Собрали настройки в короткие блоки: профиль, уведомления, график и безопасность. Лишние технические поля вынесены из первого экрана.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="settings-meta-chip"><i class="icon-base ri ri-user-line"></i> Профиль</span>
                            <span class="settings-meta-chip"><i class="icon-base ri ri-notification-4-line"></i> Уведомления</span>
                            <span class="settings-meta-chip"><i class="icon-base ri ri-time-line"></i> График</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="nav-align-top settings-anchor-nav">
                <ul class="nav nav-pills flex-column flex-md-row mb-0 gap-2 gap-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="#settings-account"><i class="icon-base ri ri-group-line icon-sm me-2"></i>{{ __('settings.nav_account') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#settings-notifications"><i class="icon-base ri ri-notification-4-line icon-sm me-2"></i>{{ __('settings.nav_notifications') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#settings-work"><i class="icon-base ri ri-calendar-line icon-sm me-2"></i>{{ __('settings.work_settings') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#settings-location"><i class="icon-base ri ri-map-pin-line icon-sm me-2"></i>{{ __('settings.address') }}</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-xl-8">
            <form id="settings-form" onsubmit="return false">
                <div id="form-messages" class="mb-6"></div>

                <div class="card mb-6 settings-card" id="settings-account">
                    <div class="card-body p-5 p-lg-6">
                        <div class="settings-section-title">
                            <div>
                                <h5 class="mb-1">Профиль аккаунта</h5>
                                <p class="text-muted mb-0">Только основные данные, которые нужны для связи с вами и работы системы.</p>
                            </div>
                            <span class="settings-meta-chip"><i class="icon-base ri ri-shield-user-line"></i> Основное</span>
                        </div>

                        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-6 mb-6">
                            <div class="avatar w-px-100 h-px-100 rounded-4 overflow-hidden" id="uploadedAvatar">
                                <img alt="user-avatar" class="w-100 h-100 d-none" id="uploadedAvatarImg" />
                                <span class="avatar-initial w-100 h-100 rounded-4 bg-primary text-white fw-semibold d-flex align-items-center justify-content-center fs-2" id="uploadedAvatarInitials">?</span>
                            </div>
                            <div class="button-wrapper">
                                <label for="upload" class="btn btn-primary me-3 mb-3" tabindex="0">
                                    <span class="d-none d-sm-block">{{ __('settings.upload_photo') }}</span>
                                    <i class="icon-base ri ri-upload-2-line d-block d-sm-none"></i>
                                    <input type="file" id="upload" class="account-file-input" hidden accept="image/png, image/jpeg" />
                                </label>
                                <button type="button" class="btn btn-outline-danger account-image-reset mb-3">
                                    <i class="icon-base ri ri-refresh-line d-block d-sm-none"></i>
                                    <span class="d-none d-sm-block">{{ __('settings.reset') }}</span>
                                </button>
                                <div class="text-muted small">{{ __('settings.allowed_formats') }}</div>
                            </div>
                        </div>

                        <div class="row g-5">
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="name" name="name" />
                                    <label for="name">{{ __('settings.name') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="email" class="form-control" id="email" name="email" />
                                    <label for="email">{{ __('settings.email') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="phone" name="phone" data-phone-mask placeholder="+7(999)999-99-99" />
                                    <label for="phone">{{ __('settings.phone') }}</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating form-floating-outline">
                                    <select id="timezone" name="timezone" class="form-select">
                                        @foreach(timezone_identifiers_list() as $tz)
                                            <option value="{{ $tz }}">{{ $tz }}</option>
                                        @endforeach
                                    </select>
                                    <label for="timezone">{{ __('settings.timezone') }}</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating form-floating-outline">
                                    <select id="time_format" name="time_format" class="form-select">
                                        <option value="24h">24h</option>
                                        <option value="12h">12h</option>
                                    </select>
                                    <label for="time_format">{{ __('settings.time_format') }}</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="settings-password-toggle p-4">
                                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                                        <div>
                                            <h6 class="mb-1">Сменить пароль</h6>
                                            <p class="text-muted mb-0">Поля ниже нужны только если вы действительно хотите поменять пароль.</p>
                                        </div>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="toggle-password-fields">Открыть поля</button>
                                    </div>
                                    <div class="row g-4 mt-1 settings-password-fields" id="settings-password-fields">
                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="password" class="form-control" id="current_password" name="current_password" />
                                                <label for="current_password">{{ __('settings.current_password') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="password" class="form-control" id="new_password" name="new_password" />
                                                <label for="new_password">{{ __('settings.new_password') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" />
                                                <label for="new_password_confirmation">{{ __('settings.password_confirmation') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-6 settings-card" id="settings-notifications">
                    <div class="card-body p-5 p-lg-6">
                        <div class="settings-section-title">
                            <div>
                                <h5 class="mb-1">Уведомления</h5>
                                <p class="text-muted mb-0">Выберите каналы, по которым хотите получать события о записях и сообщениях.</p>
                            </div>
                            <span class="settings-meta-chip"><i class="icon-base ri ri-mail-open-line"></i> Каналы</span>
                        </div>

                        <div class="row g-5">
                            <div class="col-md-4">
                                <div class="settings-compact-note">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="notif-email" />
                                        <label class="form-check-label fw-semibold" for="notif-email">{{ __('settings.email_notifications') }}</label>
                                    </div>
                                    <small class="text-muted d-block">Письма будут приходить на email из профиля.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="settings-compact-note">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="notif-telegram" />
                                        <label class="form-check-label fw-semibold" for="notif-telegram">{{ __('settings.telegram_notifications') }}</label>
                                    </div>
                                    <small class="text-muted d-block">Канал доступен после подключения Telegram в интеграциях.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="settings-compact-note">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="notif-sms" />
                                        <label class="form-check-label fw-semibold" for="notif-sms">{{ __('settings.sms_notifications') }}</label>
                                    </div>
                                    <small class="text-muted d-block">Нужен телефон и подключённый SMS-провайдер.</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating form-floating-outline">
                                    <textarea class="form-control" id="reminder_message" name="reminder_message" style="height: 140px"></textarea>
                                    <label for="reminder_message">{{ __('settings.reminder_message') }}</label>
                                </div>
                                <small class="text-muted">{{ __('settings.reminder_message_hint') }}</small>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-primary mt-1 mb-0">
                                    {{ __('integrations.moved_notice') }}
                                    <a href="{{ route('integrations') }}" class="alert-link">{{ __('menu.integrations') }}</a>.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-6 settings-card" id="settings-work">
                    <div class="card-body p-5 p-lg-6">
                        <div class="settings-section-title">
                            <div>
                                <h5 class="mb-1">{{ __('settings.work_settings') }}</h5>
                                <p class="text-muted mb-0">Этот график влияет на свободные слоты в календаре и расчёт доступного времени.</p>
                            </div>
                            <span class="settings-meta-chip"><i class="icon-base ri ri-time-line"></i> Расписание</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table settings-work-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('settings.work_days') }}</th>
                                        <th>{{ __('settings.from') }}</th>
                                        <th>{{ __('settings.to') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(['mon','tue','wed','thu','fri','sat','sun'] as $day)
                                    <tr>
                                        <td>
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input workday-check" type="checkbox" id="workday-{{ $day }}" data-day="{{ $day }}" />
                                                <label class="form-check-label" for="workday-{{ $day }}">{{ __('settings.day_' . $day) }}</label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="time" class="form-control workday-start" data-day="{{ $day }}" />
                                        </td>
                                        <td>
                                            <input type="time" class="form-control workday-end" data-day="{{ $day }}" />
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-5">
                            <h6 class="mb-3">{{ __('settings.holidays') }}</h6>
                            <div class="table-responsive">
                                <table class="table" id="holidays-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('settings.date') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" id="add-holiday">{{ __('settings.add') }}</button>
                        </div>
                    </div>
                </div>

                <div class="card mb-6 settings-card" id="settings-location">
                    <div class="card-body p-5 p-lg-6">
                        <div class="settings-section-title">
                            <div>
                                <h5 class="mb-1">Локация</h5>
                                <p class="text-muted mb-0">Адрес видят клиенты. Координаты можно заполнять только при необходимости.</p>
                            </div>
                            <span class="settings-meta-chip"><i class="icon-base ri ri-map-pin-line"></i> Адрес</span>
                        </div>

                        <div class="row g-5">
                            <div class="col-12">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="address" name="address" />
                                    <label for="address">{{ __('settings.address') }}</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <details class="settings-password-toggle p-4">
                                    <summary class="fw-semibold cursor-pointer">Координаты карты</summary>
                                    <p class="text-muted small mt-2 mb-4">Нужны только если хотите вручную уточнить точку на карте. Обычно достаточно адреса.</p>
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" class="form-control" id="map_lat" name="map_point[lat]" />
                                                <label for="map_lat">lat</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" class="form-control" id="map_lng" name="map_point[lng]" />
                                                <label for="map_lng">lng</label>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-6">
                    <div class="text-muted">После редактирования любого блока просто нажмите сохранить внизу страницы.</div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('settings.save_changes') }}</button>
                        <button type="reset" class="btn btn-outline-secondary">{{ __('settings.reset') }}</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-xl-4">
            <div class="card mb-6 settings-card settings-summary-card">
                <div class="card-body p-5">
                    <span class="badge bg-label-secondary mb-3">Подсказка</span>
                    <h5 class="mb-2">Что важно настроить сначала</h5>
                    <p class="text-muted mb-4">Для старта достаточно профиля, уведомлений и графика. Остальное можно заполнить позже.</p>
                    <div class="d-flex flex-column gap-3 small">
                        <div class="settings-compact-note">Проверьте имя, email и телефон.</div>
                        <div class="settings-compact-note">Выберите каналы уведомлений.</div>
                        <div class="settings-compact-note">Укажите рабочие дни и часы.</div>
                        <div class="settings-compact-note">Адрес нужен, если вы принимаете клиентов офлайн.</div>
                    </div>
                </div>
            </div>

            <div class="card settings-danger-card">
                <div class="card-body p-5">
                    <h5 class="mb-2">{{ __('settings.delete_account_title') }}</h5>
                    <p class="text-muted mb-4">Эта секция скрыта, чтобы не мешать обычной работе с настройками.</p>
                    <details>
                        <summary class="fw-semibold text-danger cursor-pointer">{{ __('settings.delete_account') }}</summary>
                        <div class="pt-4">
                            <div class="alert alert-warning">
                                <h6 class="alert-heading mb-1">{{ __('settings.delete_account') }}</h6>
                                <p class="mb-0">{{ __('settings.delete_account_warning') }}</p>
                            </div>
                            <form id="delete-form" onsubmit="return false">
                                <div class="mb-4">
                                    <input type="password" class="form-control" name="password" placeholder="{{ __('settings.current_password') }}" />
                                </div>
                                <button type="submit" class="btn btn-danger">{{ __('settings.delete') }}</button>
                            </form>
                        </div>
                    </details>
                </div>
            </div>
        </div>
    </div>
    <script>
    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    }
    function authHeaders(extra = {}) {
        var token = getCookie('token');
        var headers = Object.assign({ 'Accept': 'application/json', 'Accept-Language': document.documentElement.lang }, extra);
        if (token) headers['Authorization'] = 'Bearer ' + token;
        return headers;
    }
    function addHolidayRow(date = '') {
        const tbody = document.querySelector('#holidays-table tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `<td><input type="date" class="form-control holiday-date" name="holidays[]" value="${date}"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-holiday">{{ __('settings.delete') }}</button></td>`;
        tr.querySelector('.remove-holiday').addEventListener('click', () => tr.remove());
        tbody.appendChild(tr);
    }
    async function loadSettings() {
        const res = await fetch('/api/v1/settings', { headers: authHeaders(), credentials: 'include' });
        if(!res.ok) return;
        const data = await res.json();
        const form = document.getElementById('settings-form');
        form.name.value = data.user.name || '';
        form.email.value = data.user.email || '';
        form.phone.value = data.user.phone || '';
        if (form.phone.hasAttribute('data-phone-mask')) {
            form.phone.dispatchEvent(new Event('input'));
        }
        form.timezone.value = data.user.timezone || '';
        form.time_format.value = data.user.time_format || '24h';
        document.getElementById('notif-email').checked = data.settings.notifications?.email ?? false;
        document.getElementById('notif-telegram').checked = data.settings.notifications?.telegram ?? false;
        document.getElementById('notif-sms').checked = data.settings.notifications?.sms ?? false;
        document.getElementById('notif-telegram').disabled = !data.user.telegram_id;
        document.getElementById('notif-sms').disabled = !data.user.phone;
        const days = ['mon','tue','wed','thu','fri','sat','sun'];
        days.forEach(day=>{
            const check = document.getElementById('workday-'+day);
            const start = document.querySelector(`input.workday-start[data-day="${day}"]`);
            const end = document.querySelector(`input.workday-end[data-day="${day}"]`);
            check.checked = (data.settings.work_days || []).includes(day);
            const hours = data.settings.work_hours?.[day] || [];
            if(hours.length){
                start.value = hours[0];
                end.value = hours[hours.length-1];
            } else {
                start.value = '';
                end.value = '';
            }
        });
        const holidaysBody = document.querySelector('#holidays-table tbody');
        holidaysBody.innerHTML = '';
        (data.settings.holidays || []).forEach(date => addHolidayRow(date.split('T')[0]));
        if(!holidaysBody.children.length) addHolidayRow();
        form.address.value = data.settings.address || '';
        form['map_point[lat]'].value = data.settings.map_point?.lat || '';
        form['map_point[lng]'].value = data.settings.map_point?.lng || '';
        form.reminder_message.value = (data.settings && data.settings.reminder_message) || '';

        setSettingsAvatar(data.user.avatar_url || null, data.user.initials || computeInitials(form.name.value));
    }
    loadSettings();
    document.getElementById('add-holiday').addEventListener('click', () => addHolidayRow());

    function showMessage(type, text){
        const container = document.getElementById('form-messages');
        container.innerHTML = `<div class="alert alert-${type}" role="alert">${text}</div>`;
    }

    function computeInitials(fullName) {
        const s = (fullName || '').trim();
        if (!s) return '?';
        const parts = s.split(/\s+/).filter(Boolean);
        const first = parts[0] || '';
        const last = parts.length > 1 ? parts[parts.length - 1] : '';
        const a = first ? first[0].toUpperCase() : '';
        const b = last ? last[0].toUpperCase() : '';
        return (a + b) || '?';
    }

    function setSettingsAvatar(avatarUrl, initials) {
        const img = document.getElementById('uploadedAvatarImg');
        const init = document.getElementById('uploadedAvatarInitials');
        if (avatarUrl) {
            img.src = avatarUrl;
            img.classList.remove('d-none');
            init.classList.add('d-none');
        } else {
            img.removeAttribute('src');
            img.classList.add('d-none');
            init.textContent = initials || '?';
            init.classList.remove('d-none');
        }
    }

    const passwordToggleBtn = document.getElementById('toggle-password-fields');
    const passwordFields = document.getElementById('settings-password-fields');
    if (passwordToggleBtn && passwordFields) {
        passwordToggleBtn.addEventListener('click', () => {
            const isVisible = passwordFields.classList.toggle('is-visible');
            passwordToggleBtn.textContent = isVisible ? 'Скрыть поля' : 'Открыть поля';
        });
    }

    document.getElementById('settings-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        form.querySelectorAll('.invalid-feedback').forEach(el=>el.remove());
        form.querySelectorAll('.is-invalid').forEach(el=>el.classList.remove('is-invalid'));
        const payload = {
            name: form.name.value,
            email: form.email.value,
            phone: form.phone.value,
            timezone: form.timezone.value,
            time_format: form.time_format.value,
            notifications: {
                email: document.getElementById('notif-email').checked,
                telegram: document.getElementById('notif-telegram').checked,
                sms: document.getElementById('notif-sms').checked,
            },
            holidays: Array.from(document.querySelectorAll('.holiday-date')).map(i=>i.value).filter(Boolean),
            address: form.address.value,
            reminder_message: form.reminder_message.value,
            map_point: {
                lat: form['map_point[lat]'].value,
                lng: form['map_point[lng]'].value,
            }
        };
        const days=['mon','tue','wed','thu','fri','sat','sun'];
        payload.work_days=[];
        payload.work_hours={};
        days.forEach(day=>{
            const check=document.getElementById('workday-'+day);
            const start=document.querySelector(`input.workday-start[data-day="${day}"]`).value;
            const end=document.querySelector(`input.workday-end[data-day="${day}"]`).value;
            if(check.checked){
                payload.work_days.push(day);
                if(start && end){
                    let s=parseInt(start.split(':')[0]);
                    let e=parseInt(end.split(':')[0]);
                    let arr=[];
                    for(let h=s; h<=e; h++){
                        arr.push(String(h).padStart(2,'0')+':00');
                    }
                    payload.work_hours[day]=arr;
                }
            }
        });
        if(form.new_password.value){
            payload.current_password = form.current_password.value;
            payload.new_password = form.new_password.value;
            payload.new_password_confirmation = form.new_password_confirmation.value;
        }
        const res = await fetch('/api/v1/settings', {
            method: 'PATCH',
            headers: authHeaders({ 'Content-Type': 'application/json' }),
            credentials: 'include',
            body: JSON.stringify(payload)
        });
        const result = await res.json().catch(()=>({}));
        if(!res.ok){
            const errors = result.error?.fields || {};
            if(Object.keys(errors).length === 0 && result.error?.message){
                showMessage('danger', result.error.message);
            }
            Object.keys(errors).forEach(key=>{
                const fieldName = key.replace(/\.(\w+)/g,'[$1]');
                let input = form.querySelector(`[name="${fieldName}"]`);
                if(!input){
                    const base = key.split('.')[0];
                    input = form.querySelector(`[name="${base}[]"]`);
                }
                if(input){
                    input.classList.add('is-invalid');
                    const container = input.closest('.form-control-validation') || input.parentNode;
                    const div = document.createElement('div');
                    div.classList.add('invalid-feedback');
                    div.textContent = errors[key][0];
                    container.appendChild(div);
                }
            });
            return;
        }
        showMessage('success', '{{ __('settings.saved') }}');
        form.current_password.value='';
        form.new_password.value='';
        form.new_password_confirmation.value='';

        // If user changed name, update initials in settings avatar (when no image).
        const initials = computeInitials(form.name.value);
        const img = document.getElementById('uploadedAvatarImg');
        if (!img || img.classList.contains('d-none')) {
            setSettingsAvatar(null, initials);
        }
    });

    document.getElementById('upload').addEventListener('change', async (e) => {
        const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
        if (!file) return;

        const fd = new FormData();
        fd.append('avatar', file);

        const res = await fetch('/api/v1/user/avatar', {
            method: 'POST',
            headers: authHeaders(),
            credentials: 'include',
            body: fd
        });

        const result = await res.json().catch(()=>({}));
        if (!res.ok) {
            const msg = result.message || result.error?.message || 'Error';
            showMessage('danger', msg);
            return;
        }

        setSettingsAvatar(result.avatar_url, result.initials);
        document.querySelectorAll('[data-user-avatar-img]').forEach(img => {
            img.src = result.avatar_url;
            img.classList.remove('d-none');
        });
        document.querySelectorAll('[data-user-initial]').forEach(el => el.classList.add('d-none'));
        showMessage('success', '{{ __('settings.saved') }}');
    });

    const resetBtn = document.querySelector('.account-image-reset');
    if (resetBtn) resetBtn.addEventListener('click', async () => {
        const res = await fetch('/api/v1/user/avatar', {
            method: 'DELETE',
            headers: authHeaders(),
            credentials: 'include',
        });

        const result = await res.json().catch(()=>({}));
        if (!res.ok) {
            const msg = result.message || result.error?.message || 'Error';
            showMessage('danger', msg);
            return;
        }

        const form = document.getElementById('settings-form');
        const currentName = form && form.name ? form.name.value : '';
        setSettingsAvatar(null, result.initials || computeInitials(currentName));
        document.querySelectorAll('[data-user-avatar-img]').forEach(img => {
            img.removeAttribute('src');
            img.classList.add('d-none');
        });
        document.querySelectorAll('[data-user-initial]').forEach(el => el.classList.remove('d-none'));
    });

    document.getElementById('delete-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        if(!confirm('{{ __('settings.confirm_delete') }}')) return;
        const res = await fetch('/api/v1/user', {
            method: 'DELETE',
            headers: authHeaders({ 'Content-Type': 'application/json' }),
            credentials: 'include',
            body: JSON.stringify({password: e.target.password.value})
        });
        if(res.status === 204){
            window.location.href = '/';
        } else {
            const err = await res.json().catch(()=>({}));
            showMessage('danger', err.error?.message || 'Error');
        }
    });
    </script>
    @include('components.phone-mask-script')
@endsection
