@extends('layouts.guest')

@section('title', 'Сброс пароля')

@section('content')
<div class="container" style="margin-top: 50px; max-width: 500px;">
    <div class="card">
        <div class="card-content">
            <span class="card-title">Сброс пароля</span>
            <form id="reset-form">
                <input id="token" type="hidden" value="{{ request('token') }}" />
                <div class="input-field">
                    <input id="email" type="email" value="{{ request('email') }}" required />
                    <label for="email" class="active">Email</label>
                </div>
                <div class="input-field">
                    <input id="password" type="password" required />
                    <label for="password">Новый пароль</label>
                </div>
                <div class="input-field">
                    <input id="password_confirmation" type="password" required />
                    <label for="password_confirmation">Подтверждение пароля</label>
                </div>
                <div class="card-action">
                    <button class="btn waves-effect waves-light" type="submit">Сбросить пароль</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('reset-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    await fetch('/sanctum/csrf-cookie');
    const res = await fetch('/api/v1/auth/reset-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
            token: document.getElementById('token').value,
            email: document.getElementById('email').value,
            password: document.getElementById('password').value,
            password_confirmation: document.getElementById('password_confirmation').value,
        }),
    });
    if (res.ok) {
        window.location.href = '/login';
    } else {
        M.toast({ html: 'Ошибка сброса', classes: 'red' });
    }
});
</script>
@endsection
