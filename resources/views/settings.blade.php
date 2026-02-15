@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="nav-align-top">
                <ul class="nav nav-pills flex-column flex-md-row mb-6 gap-2 gap-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="javascript:void(0);"><i class="icon-base ri ri-group-line icon-sm me-2"></i>{{ __('settings.nav_account') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages-account-settings-security.html"><i class="icon-base ri ri-lock-line icon-sm me-2"></i>{{ __('settings.nav_security') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages-account-settings-billing.html"><i class="icon-base ri ri-bookmark-line icon-sm me-2"></i>{{ __('settings.nav_billing') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages-account-settings-notifications.html"><i class="icon-base ri ri-notification-4-line icon-sm me-2"></i>{{ __('settings.nav_notifications') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages-account-settings-connections.html"><i class="icon-base ri ri-link-m icon-sm me-2"></i>{{ __('settings.nav_connections') }}</a>
                    </li>
                </ul>
            </div>
            <div class="card mb-6">
                <div class="card-body">
                    <div class="d-flex align-items-start align-items-sm-center gap-6">
                        <div class="avatar w-px-100 h-px-100 rounded-4 overflow-hidden" id="uploadedAvatar">
                            <img alt="user-avatar" class="w-100 h-100 d-none" id="uploadedAvatarImg" />
                            <span class="avatar-initial w-100 h-100 rounded-4 bg-primary text-white fw-semibold d-flex align-items-center justify-content-center fs-2" id="uploadedAvatarInitials">?</span>
                        </div>
                        <div class="button-wrapper">
                            <label for="upload" class="btn btn-primary me-3 mb-4" tabindex="0">
                                <span class="d-none d-sm-block">{{ __('settings.upload_photo') }}</span>
                                <i class="icon-base ri ri-upload-2-line d-block d-sm-none"></i>
                                <input type="file" id="upload" class="account-file-input" hidden accept="image/png, image/jpeg" />
                            </label>
                            <button type="button" class="btn btn-outline-danger account-image-reset mb-4">
                                <i class="icon-base ri ri-refresh-line d-block d-sm-none"></i>
                                <span class="d-none d-sm-block">{{ __('settings.reset') }}</span>
                            </button>
                            <div>{{ __('settings.allowed_formats') }}</div>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form id="settings-form" onsubmit="return false">
                        <div id="form-messages"></div>
                        <div class="row mt-1 g-5">
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
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <select id="timezone" name="timezone" class="form-select">
                                        @foreach(timezone_identifiers_list() as $tz)
                                            <option value="{{ $tz }}">{{ $tz }}</option>
                                        @endforeach
                                    </select>
                                    <label for="timezone">{{ __('settings.timezone') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <select id="time_format" name="time_format" class="form-select">
                                        <option value="24h">24h</option>
                                        <option value="12h">12h</option>
                                    </select>
                                    <label for="time_format">{{ __('settings.time_format') }}</label>
                                </div>
                            </div>
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
                            <div class="col-md-4">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" id="notif-email" />
                                    <label class="form-check-label" for="notif-email">{{ __('settings.email_notifications') }}</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" id="notif-telegram" />
                                    <label class="form-check-label" for="notif-telegram">{{ __('settings.telegram_notifications') }}</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" id="notif-sms" />
                                    <label class="form-check-label" for="notif-sms">{{ __('settings.sms_notifications') }}</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating form-floating-outline">
                                    <textarea
                                        class="form-control"
                                        id="reminder_message"
                                        name="reminder_message"
                                        style="height: 140px"
                                    ></textarea>
                                    <label for="reminder_message">{{ __('settings.reminder_message') }}</label>
                                </div>
                                <small class="text-muted">{{ __('settings.reminder_message_hint') }}</small>
                            </div>
                            <div class="col-12">
                                <h5 class="mt-4">{{ __('settings.integrations') }}</h5>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="email" class="form-control" id="smsaero_email" name="integrations[smsaero][email]" />
                                    <label for="smsaero_email">{{ __('settings.smsaero_email') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="smsaero_api_key" name="integrations[smsaero][api_key]" />
                                    <label for="smsaero_api_key">{{ __('settings.smsaero_api_key') }}</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <h6 class="mt-2">{{ __('settings.smtp') }}</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="smtp_host" name="integrations[smtp][host]" />
                                    <label for="smtp_host">{{ __('settings.smtp_host') }}</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="number" class="form-control" id="smtp_port" name="integrations[smtp][port]" min="1" max="65535" />
                                    <label for="smtp_port">{{ __('settings.smtp_port') }}</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating form-floating-outline mb-4">
                                    <select class="form-select" id="smtp_encryption" name="integrations[smtp][encryption]">
                                        <option value="">—</option>
                                        <option value="tls">TLS</option>
                                        <option value="ssl">SSL</option>
                                        <option value="starttls">STARTTLS</option>
                                        <option value="none">{{ __('settings.smtp_encryption_none') }}</option>
                                    </select>
                                    <label for="smtp_encryption">{{ __('settings.smtp_encryption') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="smtp_username" name="integrations[smtp][username]" />
                                    <label for="smtp_username">{{ __('settings.smtp_username') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="password" class="form-control" id="smtp_password" name="integrations[smtp][password]" />
                                    <label for="smtp_password">{{ __('settings.smtp_password') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="email" class="form-control" id="smtp_from_address" name="integrations[smtp][from_address]" />
                                    <label for="smtp_from_address">{{ __('settings.smtp_from_address') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="smtp_from_name" name="integrations[smtp][from_name]" />
                                    <label for="smtp_from_name">{{ __('settings.smtp_from_name') }}</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <h6 class="mt-2">{{ __('settings.whatsapp') }}</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="whatsapp_api_key" name="integrations[whatsapp][api_key]" />
                                    <label for="whatsapp_api_key">{{ __('settings.whatsapp_api_key') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="whatsapp_sender" name="integrations[whatsapp][sender]" />
                                    <label for="whatsapp_sender">{{ __('settings.whatsapp_sender') }}</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <h6 class="mt-2">{{ __('settings.telegram_notifications') }}</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="telegram_bot_token" name="integrations[telegram][bot_token]" />
                                    <label for="telegram_bot_token">{{ __('settings.telegram_bot_token') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="telegram_sender" name="integrations[telegram][sender]" />
                                    <label for="telegram_sender">{{ __('settings.telegram_sender') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="yookassa_shop_id" name="integrations[yookassa][shop_id]" />
                                    <label for="yookassa_shop_id">{{ __('settings.yookassa_shop_id') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="yookassa_secret_key" name="integrations[yookassa][secret_key]" />
                                    <label for="yookassa_secret_key">{{ __('settings.yookassa_secret_key') }}</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <h5 class="mt-4">{{ __('settings.work_settings') }}</h5>
                                <table class="table">
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
                            <div class="col-12">
                                <h6 class="mt-4">{{ __('settings.holidays') }}</h6>
                                <table class="table" id="holidays-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('settings.date') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                                <button type="button" class="btn btn-sm btn-secondary" id="add-holiday">{{ __('settings.add') }}</button>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="address" name="address" />
                                    <label for="address">{{ __('settings.address') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="map_lat" name="map_point[lat]" />
                                    <label for="map_lat">lat</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="map_lng" name="map_point[lng]" />
                                    <label for="map_lng">lng</label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6">
                            <button type="submit" class="btn btn-primary me-3">{{ __('settings.save_changes') }}</button>
                            <button type="reset" class="btn btn-outline-secondary">{{ __('settings.reset') }}</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <h5 class="card-header mb-1">{{ __('settings.delete_account_title') }}</h5>
                <div class="card-body">
                    <div class="mb-6 col-12 mb-0">
                        <div class="alert alert-warning">
                            <h6 class="alert-heading mb-1">{{ __('settings.delete_account') }}</h6>
                            <p class="mb-0">{{ __('settings.delete_account_warning') }}</p>
                        </div>
                    </div>
                    <form id="delete-form" onsubmit="return false">
                        <div class="mb-6">
                            <input type="password" class="form-control" name="password" placeholder="{{ __('settings.current_password') }}" />
                        </div>
                        <button type="submit" class="btn btn-danger">{{ __('settings.delete') }}</button>
                    </form>
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
        form['integrations[smsaero][email]'].value = data.settings.integrations.smsaero.email || '';
        form['integrations[smsaero][api_key]'].value = data.settings.integrations.smsaero.api_key || '';
        form['integrations[smtp][host]'].value = data.settings.integrations.smtp.host || '';
        form['integrations[smtp][port]'].value = data.settings.integrations.smtp.port || '';
        form['integrations[smtp][username]'].value = data.settings.integrations.smtp.username || '';
        form['integrations[smtp][password]'].value = data.settings.integrations.smtp.password || '';
        form['integrations[smtp][encryption]'].value = data.settings.integrations.smtp.encryption || '';
        form['integrations[smtp][from_address]'].value = data.settings.integrations.smtp.from_address || '';
        form['integrations[smtp][from_name]'].value = data.settings.integrations.smtp.from_name || '';
        form['integrations[whatsapp][api_key]'].value = data.settings.integrations.whatsapp.api_key || '';
        form['integrations[whatsapp][sender]'].value = data.settings.integrations.whatsapp.sender || '';
        form['integrations[telegram][bot_token]'].value = data.settings.integrations.telegram.bot_token || '';
        form['integrations[telegram][sender]'].value = data.settings.integrations.telegram.sender || '';
        form['integrations[yookassa][shop_id]'].value = data.settings.integrations.yookassa.shop_id || '';
        form['integrations[yookassa][secret_key]'].value = data.settings.integrations.yookassa.secret_key || '';
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
            integrations: {
                smsaero: {
                    email: form['integrations[smsaero][email]'].value,
                    api_key: form['integrations[smsaero][api_key]'].value,
                },
                smtp: {
                    host: form['integrations[smtp][host]'].value,
                    port: form['integrations[smtp][port]'].value,
                    username: form['integrations[smtp][username]'].value,
                    password: form['integrations[smtp][password]'].value,
                    encryption: form['integrations[smtp][encryption]'].value,
                    from_address: form['integrations[smtp][from_address]'].value,
                    from_name: form['integrations[smtp][from_name]'].value,
                },
                whatsapp: {
                    api_key: form['integrations[whatsapp][api_key]'].value,
                    sender: form['integrations[whatsapp][sender]'].value,
                },
                telegram: {
                    bot_token: form['integrations[telegram][bot_token]'].value,
                    sender: form['integrations[telegram][sender]'].value,
                },
                yookassa: {
                    shop_id: form['integrations[yookassa][shop_id]'].value,
                    secret_key: form['integrations[yookassa][secret_key]'].value,
                }
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
