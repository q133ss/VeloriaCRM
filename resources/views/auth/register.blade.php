@extends('layouts.guest')

@section('title', 'Регистрация')

@section('content')
<div class="container" style="margin-top: 50px; max-width: 500px;">
    <div class="card">
        <div class="card-content">
            <span class="card-title">Регистрация</span>
            <form id="register-form">
                <div class="input-field">
                    <input id="name" type="text" required />
                    <label for="name">Имя</label>
                </div>
                <div class="input-field">
                    <input id="email" type="email" required />
                    <label for="email">Email</label>
                </div>
                <div class="input-field">
                    <input id="phone" type="text" />
                    <label for="phone">Телефон</label>
                </div>
                <div class="input-field">
                    <input id="password" type="password" required />
                    <label for="password">Пароль</label>
                </div>
                <div class="input-field">
                    <input id="password_confirmation" type="password" required />
                    <label for="password_confirmation">Подтверждение пароля</label>
                </div>
                <div class="card-action">
                    <button class="btn waves-effect waves-light" type="submit">Зарегистрироваться</button>
                </div>
            </form>
            <p><a href="/login">Уже есть аккаунт?</a></p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('register-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    await fetch('/sanctum/csrf-cookie');
    const res = await fetch('/api/v1/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            password: document.getElementById('password').value,
            password_confirmation: document.getElementById('password_confirmation').value,
        }),
    });
    if (res.ok) {
        window.location.href = '/login';
    } else {
        alert('Ошибка регистрации');
    }
});
</script>
@endsection
