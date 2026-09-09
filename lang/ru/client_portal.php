<?php

return [
    'auth' => [
        'unauthorized' => 'Не авторизовано.',
        'code_sent' => 'Код подтверждения отправлен.',
        'invalid_or_expired' => 'Неверный или истекший код подтверждения.',
        'client_not_found' => 'Клиент не найден.',
        'invalid_master_selection' => 'Неверный выбор мастера.',
        'too_many_requests' => 'Слишком много запросов. Попробуйте позже.',
    ],
    'email' => [
        'otp_subject' => 'Код подтверждения',
        'otp_intro' => 'Используйте этот код, чтобы продолжить:',
        'otp_expires' => 'Код действует :minutes мин.',
        'magic_link_subject' => 'Ссылка для входа',
        'magic_link_intro' => 'Нажмите на кнопку ниже, чтобы войти в приложение:',
        'magic_link_cta' => 'Войти',
    ],
    'redirect' => [
        'title' => 'Открываем приложение',
        'opening' => 'Секунду, переключаемся в Veloria Client…',
        'button' => 'Открыть приложение вручную',
        'code_hint' => 'Если приложение не открылось само, введите этот код на экране входа:',
        'no_app' => 'Приложения ещё нет на телефоне? Установите Veloria Client и войдите по email ещё раз.',
    ],
    'booking' => [
        'slot_unavailable' => 'Это время уже занято.',
        'master_notification_title' => 'Новая запись (Client Portal)',
        'master_notification_message' => 'Клиент :client записался(лась) на ":service" на :datetime.',
    ],
    'device' => [
        'token_saved' => 'Токен устройства сохранён.',
    ],
    'notifications' => [
        'booking_confirmed_title' => 'Запись подтверждена',
        'booking_confirmed_message' => 'Вы записаны на ":service" на :datetime.',
        'appointment_reminder_title' => 'Напоминание о записи',
        'appointment_reminder_message' => 'Не забудьте: ":service" завтра, :datetime.',
        'new_post_title' => 'Новость от мастера',
    ],
];
