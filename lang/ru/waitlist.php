<?php

return [
    'messages' => [
        'created' => 'Клиент добавлен в лист ожидания.',
        'updated' => 'Лист ожидания обновлён.',
        'deleted' => 'Запись из листа ожидания удалена.',
    ],
    'notifications' => [
        'slot_opened_title' => 'Освободилось время',
        'slot_opened_message' => 'На :time ждут своей очереди :count клиент(ов). Больше всего подходит :client.',
    ],
    'validation' => [
        'already_waiting' => ':name уже ждёт эту услугу на :date.',
    ],
    'reasons' => [
        'exact_date' => 'дата совпадает',
        'flexible_date' => 'гибкие даты',
        'time_window' => 'время подходит',
        'service_match' => 'та же услуга',
        'manual_priority' => 'высокий приоритет',
        'high_ltv' => 'приносит много выручки',
        'good_ltv' => 'хорошая выручка',
        'regular_client' => 'постоянный клиент',
        'no_show_risk' => 'были неявки',
    ],
];
