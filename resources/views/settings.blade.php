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
                        <img src="../../assets/img/avatars/1.png" alt="user-avatar" class="d-block w-px-100 h-px-100 rounded-4" id="uploadedAvatar" />
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
                                    <input type="text" class="form-control" id="phone" name="phone" />
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
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="work_days" name="work_days" />
                                    <label for="work_days">{{ __('settings.work_days') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="work_hours" name="work_hours" />
                                    <label for="work_hours">{{ __('settings.work_hours') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="holidays" name="holidays" />
                                    <label for="holidays">{{ __('settings.holidays') }}</label>
                                </div>
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
        var headers = Object.assign({ 'Accept': 'application/json' }, extra);
        if (token) headers['Authorization'] = 'Bearer ' + token;
        return headers;
    }
    async function loadSettings() {
        const res = await fetch('/api/v1/settings', { headers: authHeaders(), credentials: 'include' });
        if(!res.ok) return;
        const data = await res.json();
        const form = document.getElementById('settings-form');
        form.name.value = data.user.name || '';
        form.email.value = data.user.email || '';
        form.phone.value = data.user.phone || '';
        form.timezone.value = data.user.timezone || '';
        form.time_format.value = data.user.time_format || '24h';
        document.getElementById('notif-email').checked = data.settings.notifications?.email ?? false;
        document.getElementById('notif-telegram').checked = data.settings.notifications?.telegram ?? false;
        document.getElementById('notif-sms').checked = data.settings.notifications?.sms ?? false;
        document.getElementById('notif-telegram').disabled = !data.user.telegram_id;
        document.getElementById('notif-sms').disabled = !data.user.phone;
        form['integrations[smsaero][email]'].value = data.settings.integrations.smsaero.email || '';
        form['integrations[smsaero][api_key]'].value = data.settings.integrations.smsaero.api_key || '';
        form['integrations[yookassa][shop_id]'].value = data.settings.integrations.yookassa.shop_id || '';
        form['integrations[yookassa][secret_key]'].value = data.settings.integrations.yookassa.secret_key || '';
        form.work_days.value = JSON.stringify(data.settings.work_days || {});
        form.work_hours.value = JSON.stringify(data.settings.work_hours || {});
        form.holidays.value = (data.settings.holidays || []).join(',');
        form.address.value = data.settings.address || '';
        form['map_point[lat]'].value = data.settings.map_point?.lat || '';
        form['map_point[lng]'].value = data.settings.map_point?.lng || '';
    }
    loadSettings();

    document.getElementById('settings-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const payload = {
            name: form.name.value,
            email: form.email.value,
            phone: form.phone.value,
            timezone: form.timezone.value,
            time_format: form.time_format.value,
            current_password: form.current_password.value,
            new_password: form.new_password.value,
            new_password_confirmation: form.new_password_confirmation.value,
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
                yookassa: {
                    shop_id: form['integrations[yookassa][shop_id]'].value,
                    secret_key: form['integrations[yookassa][secret_key]'].value,
                }
            },
            work_days: form.work_days.value ? JSON.parse(form.work_days.value) : {},
            work_hours: form.work_hours.value ? JSON.parse(form.work_hours.value) : {},
            holidays: form.holidays.value ? form.holidays.value.split(',').map(s=>s.trim()).filter(Boolean) : [],
            address: form.address.value,
            map_point: {
                lat: form['map_point[lat]'].value,
                lng: form['map_point[lng]'].value,
            }
        };
        const res = await fetch('/api/v1/settings', {
            method: 'PATCH',
            headers: authHeaders({ 'Content-Type': 'application/json' }),
            credentials: 'include',
            body: JSON.stringify(payload)
        });
        if(res.ok){
            alert('{{ __('settings.saved') }}');
        }else{
            const err = await res.json();
            alert(err.error?.message || 'Error');
        }
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
            const err = await res.json();
            alert(err.error?.message || 'Error');
        }
    });
    </script>
@endsection
