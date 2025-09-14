@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1>{{ __('settings.title') }}</h1>

    <form id="settings-form" class="my-4">
        @csrf
        <h2>{{ __('settings.basic_info') }}</h2>
        <div>
            <label class="form-label">{{ __('settings.name') }}
                <input type="text" name="name" class="form-control" />
            </label>
        </div>
        <div>
            <label class="form-label">{{ __('settings.email') }}
                <input type="email" name="email" class="form-control" />
            </label>
        </div>
        <div>
            <label class="form-label">{{ __('settings.phone') }}
                <input type="text" name="phone" class="form-control" />
            </label>
        </div>

        <h2 class="mt-4">{{ __('settings.time') }}</h2>
        <div>
            <label class="form-label">{{ __('settings.timezone') }}
                <select name="timezone" class="form-select">
                    @foreach(timezone_identifiers_list() as $tz)
                        <option value="{{ $tz }}">{{ $tz }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div>
            <label class="form-label">{{ __('settings.time_format') }}
                <select name="time_format" class="form-select">
                    <option value="24h">24h</option>
                    <option value="12h">12h</option>
                </select>
            </label>
        </div>

        <h2 class="mt-4">{{ __('settings.password') }}</h2>
        <div>
            <label class="form-label">{{ __('settings.current_password') }}
                <input type="password" name="current_password" class="form-control" />
            </label>
        </div>
        <div>
            <label class="form-label">{{ __('settings.new_password') }}
                <input type="password" name="new_password" class="form-control" />
            </label>
        </div>
        <div>
            <label class="form-label">{{ __('settings.password_confirmation') }}
                <input type="password" name="new_password_confirmation" class="form-control" />
            </label>
        </div>

        <h2 class="mt-4">{{ __('settings.notifications') }}</h2>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="notifications[email]" id="notif-email">
            <label class="form-check-label" for="notif-email">{{ __('settings.email_notifications') }}</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="notifications[telegram]" id="notif-telegram">
            <label class="form-check-label" for="notif-telegram">{{ __('settings.telegram_notifications') }}</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="notifications[sms]" id="notif-sms">
            <label class="form-check-label" for="notif-sms">{{ __('settings.sms_notifications') }}</label>
        </div>

        <h2 class="mt-4">{{ __('settings.integrations') }}</h2>
        <h3>{{ __('settings.smsaero') }}</h3>
        <div>
            <label class="form-label">{{ __('settings.smsaero_email') }}
                <input type="email" name="integrations[smsaero][email]" class="form-control" />
            </label>
        </div>
        <div>
            <label class="form-label">{{ __('settings.smsaero_api_key') }}
                <input type="text" name="integrations[smsaero][api_key]" class="form-control" />
            </label>
        </div>
        <h3>{{ __('settings.yookassa') }}</h3>
        <div>
            <label class="form-label">{{ __('settings.yookassa_shop_id') }}
                <input type="text" name="integrations[yookassa][shop_id]" class="form-control" />
            </label>
        </div>
        <div>
            <label class="form-label">{{ __('settings.yookassa_secret_key') }}
                <input type="text" name="integrations[yookassa][secret_key]" class="form-control" />
            </label>
        </div>

        <h2 class="mt-4">{{ __('settings.work_settings') }}</h2>
        <div>
            <label class="form-label">{{ __('settings.work_days') }}
                <input type="text" name="work_days" class="form-control" placeholder="{\"mon\":true}" />
            </label>
        </div>
        <div>
            <label class="form-label">{{ __('settings.work_hours') }}
                <input type="text" name="work_hours" class="form-control" placeholder="{\"mon\":[\"09:00-18:00\"]}" />
            </label>
        </div>
        <div>
            <label class="form-label">{{ __('settings.holidays') }}
                <input type="text" name="holidays" class="form-control" placeholder="2025-01-01,2025-01-02" />
            </label>
        </div>
        <div>
            <label class="form-label">{{ __('settings.address') }}
                <input type="text" name="address" class="form-control" />
            </label>
        </div>
        <div>
            <label class="form-label">{{ __('settings.map_point') }}
                <input type="text" name="map_point[lat]" class="form-control" placeholder="lat" />
                <input type="text" name="map_point[lng]" class="form-control mt-1" placeholder="lng" />
            </label>
        </div>

        <button type="submit" class="btn btn-primary mt-3">{{ __('settings.save') }}</button>
    </form>

    <hr>
    <h2 class="text-danger">{{ __('settings.delete_account') }}</h2>
    <form id="delete-form" class="my-3">
        @csrf
        @method('DELETE')
        <input type="password" name="password" class="form-control mb-2" placeholder="{{ __('settings.current_password') }}" />
        <button type="submit" class="btn btn-danger">{{ __('settings.delete') }}</button>
    </form>
</div>

<script>
async function loadSettings() {
    const res = await fetch('/api/v1/settings', {credentials:'include'});
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
        headers: {'Content-Type': 'application/json'},
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
        headers: {'Content-Type': 'application/json'},
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
