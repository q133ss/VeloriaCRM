<?php

return [
    'messages' => [
        'created' => 'Добавили в лист ожидания.',
        'updated' => 'Лист ожидания обновлён.',
        'deleted' => 'Убрали из листа ожидания.',
    ],
    'notifications' => [
        'slot_opened_title' => 'Освободилось время',

        // Three forms, because Russian counts in three. It used to read
        // «3 клиент(ов)» — a developer's way around declension, shown to a
        // living person. The first form drops the number on purpose: the
        // ranking hands over at most three matches, so it only ever means one.
        'slot_opened_message' => ':time свободно. Ждёт :client.'
            . '|:time свободно. Ждут :count клиентки, больше всех подходит :client.'
            . '|:time свободно. Ждут :count клиенток, больше всех подходит :client.',
    ],
    'validation' => [
        'already_waiting' => ':name уже ждёт эту услугу на :date.',
    ],
    'reasons' => [
        'exact_date' => 'дата совпадает',
        'flexible_date' => 'гибкие даты',
        'time_window' => 'время подходит',
        'service_match' => 'та же услуга',
        'manual_priority' => 'просили позвать раньше',

        // One line for both scoring bands. Which side of the threshold she
        // falls on is the ranking's business, not something to show a master.
        'valuable_client' => 'приносит хорошую выручку',

        'regular_client' => 'постоянная клиентка',
    ],
    'warnings' => [
        'no_show_risk' => 'бывали неявки',
    ],
];
