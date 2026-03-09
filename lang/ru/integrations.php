<?php

return [
    'eyebrow' => 'Veloria Connect',
    'title' => 'Интеграции',
    'description' => 'Подключите SMS, email, мессенджеры и оплату в отдельном разделе без лишних настроек аккаунта.',
    'security_note' => 'Секретные ключи сохраняются только в вашем кабинете и не выводятся клиентам.',
    'summary_label' => 'Статус подключений',
    'summary_empty' => 'Пока ничего не подключено',
    'summary_pattern' => 'Подключено: :count из 5',
    'status_ready' => 'Подключено',
    'status_partial' => 'Заполнено частично',
    'status_empty' => 'Не настроено',
    'helper_badge' => 'Подсказка',
    'helper_title' => 'Настраивайте только нужные каналы',
    'helper_text' => 'Если вы пока не используете какой-то сервис, оставьте его поля пустыми. CRM продолжит работать без него и не будет пытаться отправлять сообщения через неподключённый канал.',
    'save' => 'Сохранить интеграции',
    'reset' => 'Обновить из сервера',
    'saved' => 'Интеграции сохранены.',
    'load_error' => 'Не удалось загрузить интеграции.',
    'moved_notice' => 'Интеграции перенесены в раздел',
    'sections' => [
        'smsaero' => [
            'title' => 'SmsAero (SMS)',
            'description' => 'Используется для SMS-напоминаний и прямых сообщений клиентам.',
            'email_hint' => 'Email аккаунта SmsAero для авторизации по Basic Auth.',
            'api_key_hint' => 'API-ключ из личного кабинета SmsAero.',
        ],
        'smtp' => [
            'title' => 'SMTP',
            'description' => 'Подключите почтовый сервер для email-уведомлений и рассылок.',
            'hint' => 'Для рабочего канала обычно достаточно `host`, `port`, `username`, `password` и `from email`.',
        ],
        'whatsapp' => [
            'title' => 'WhatsApp',
            'description' => 'Подключение провайдера WhatsApp Business API для сообщений клиентам.',
            'hint' => 'Нужны API-ключ и идентификатор отправителя, который выдал провайдер канала.',
        ],
        'telegram' => [
            'title' => 'Telegram Bot',
            'description' => 'Бот для записи клиентов и уведомлений в Telegram.',
            'hint' => 'Создайте бота через BotFather, вставьте токен и при необходимости укажите username бота.',
        ],
        'yookassa' => [
            'title' => 'YooKassa',
            'description' => 'Приём предоплаты и онлайн-оплат внутри CRM.',
            'hint' => 'Укажите `Shop ID` и `Secret key` из кабинета YooKassa.',
        ],
    ],
];
