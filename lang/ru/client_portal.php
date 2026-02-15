<?php

return [
    'auth' => [
        'unauthorized' => 'Не авторизовано.',
        'code_sent' => 'Код подтверждения отправлен.',
        'invalid_or_expired' => 'Неверный или истекший код подтверждения.',
        'client_not_found' => 'Клиент не найден.',
        'already_registered' => 'Аккаунт с этим email уже существует. Пожалуйста, войдите.',
        'phone_mismatch' => 'Номер телефона не совпадает.',
        'too_many_requests' => 'Слишком много запросов. Попробуйте позже.',
    ],
    'email' => [
        'otp_subject' => 'Код подтверждения',
        'otp_intro' => 'Используйте этот код, чтобы продолжить:',
        'otp_expires' => 'Код действует :minutes мин.',
    ],
    'booking' => [
        'slot_unavailable' => 'Это время уже занято.',
        'master_notification_title' => 'Новая запись (Client Portal)',
        'master_notification_message' => 'Клиент :client записался(лась) на ":service" на :datetime.',
    ],
];
