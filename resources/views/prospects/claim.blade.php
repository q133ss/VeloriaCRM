<!doctype html>
<html lang="ru" class="layout-wide customizer-hide" dir="ltr" data-skin="default" data-bs-theme="light" data-assets-path="/assets/">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Ваш кабинет Veloria</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/vendor/css/core.css" />
    <link rel="stylesheet" href="/assets/css/demo.css" />
    <link rel="stylesheet" href="/assets/vendor/css/pages/page-auth.css" />
</head>
<body>
<div class="authentication-wrapper authentication-basic container-p-y p-4 p-sm-0">
    <div class="authentication-inner py-6">
        <div class="card p-md-7 p-1">
            <div class="card-body">
                <h4 class="mb-2">Это ваш кабинет</h4>
                <p class="mb-2">
                    Страница записи «{{ $prospect->title }}», ваши услуги и журнал уже настроены.
                </p>
                @if ($prospect->landing)
                    <p class="mb-5">
                        <a href="{{ route('landings.public', $prospect->landing->slug) }}" target="_blank" rel="noopener">Посмотреть страницу записи</a>
                    </p>
                @endif

                <p class="mb-4 text-body-secondary">Укажите свою почту и придумайте пароль, по ним будете входить.</p>

                <form method="POST" action="{{ route('prospects.claim.store', $token) }}" class="mb-4">
                    @csrf
                    <div class="form-floating form-floating-outline mb-4">
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                               value="{{ old('email') }}" placeholder="Почта" autocomplete="email" required autofocus />
                        <label for="email">Почта</label>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-floating form-floating-outline mb-4">
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                               placeholder="Пароль" autocomplete="new-password" required minlength="8" />
                        <label for="password">Пароль, не короче 8 символов</label>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-floating form-floating-outline mb-5">
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                               placeholder="Пароль ещё раз" autocomplete="new-password" required minlength="8" />
                        <label for="password_confirmation">Пароль ещё раз</label>
                    </div>
                    <button class="btn btn-primary d-grid w-100">Войти в кабинет</button>
                </form>

                <p class="small text-body-secondary mb-0">Это бесплатно, карта не нужна.</p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
