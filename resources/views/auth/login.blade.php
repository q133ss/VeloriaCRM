@extends('layouts.guest')

@section('title', 'Вход')

@section('content')
<div class="container" style="margin-top: 50px; max-width: 500px;">
    <div class="card">
        <div class="card-content">
            <span class="card-title">Вход</span>
            <form id="login-form">
                <div class="input-field">
                    <input id="email" type="email" required />
                    <label for="email">Email</label>
                </div>
                <div class="input-field">
                    <input id="password" type="password" required />
                    <label for="password">Пароль</label>
                </div>
                <div class="card-action">
                    <button class="btn waves-effect waves-light" type="submit">Войти</button>
                </div>
            </form>
            <p><a href="/register">Регистрация</a> | <a href="/password/forgot">Забыли пароль?</a></p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('login-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    await fetch('/sanctum/csrf-cookie');
    const res = await fetch('/api/v1/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
            email: document.getElementById('email').value,
            password: document.getElementById('password').value,
        }),
    });
    if (res.ok) {
        window.location.href = '/';
    } else {
        alert('Ошибка входа');
    }
});
</script>
@endsection
