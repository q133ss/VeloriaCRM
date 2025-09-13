@extends('layouts.guest')

@section('title', 'Восстановление пароля')

@section('content')
<div class="container" style="margin-top: 50px; max-width: 500px;">
    <div class="card">
        <div class="card-content">
            <span class="card-title">Восстановление пароля</span>
            <form id="forgot-form">
                <div class="input-field">
                    <input id="email" type="email" required />
                    <label for="email">Email</label>
                </div>
                <div class="card-action">
                    <button class="btn waves-effect waves-light" type="submit">Отправить ссылку</button>
                </div>
            </form>
            <p><a href="/login">Назад ко входу</a></p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('forgot-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    await fetch('/sanctum/csrf-cookie');
    const res = await fetch('/api/v1/auth/forgot-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ email: document.getElementById('email').value }),
    });
    if (res.ok) {
        M.toast({ html: 'Ссылка отправлена', classes: 'green' });
    } else {
        M.toast({ html: 'Ошибка отправки', classes: 'red' });
    }
});
</script>
@endsection
