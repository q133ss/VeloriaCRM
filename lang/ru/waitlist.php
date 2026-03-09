<?php

return [
    'messages' => [
        'created' => 'Клиент добавлен в умный лист ожидания.',
        'updated' => 'Лист ожидания обновлён.',
        'deleted' => 'Запись из листа ожидания удалена.',
    ],
    'notifications' => [
        'slot_opened_title' => 'Освободился слот',
        'slot_opened_message' => 'На :time есть :count кандидат(ов) в waitlist. Сильнее всего подходит :client.',
    ],
    'reasons' => [
        'exact_date' => 'точная дата',
        'flexible_date' => 'гибкий диапазон дат',
        'time_window' => 'подходит по времени',
        'service_match' => 'точное совпадение услуги',
        'manual_priority' => 'ручной приоритет мастера',
        'high_ltv' => 'высокий LTV',
        'good_ltv' => 'хороший LTV',
        'regular_client' => 'регулярный клиент',
        'no_show_risk' => 'риск no-show',
    ],
];
