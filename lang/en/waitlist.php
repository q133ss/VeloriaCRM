<?php

return [
    'messages' => [
        'created' => 'Added to the waiting list.',
        'updated' => 'The waitlist entry was updated.',
        'deleted' => 'Removed from the waiting list.',
    ],
    'notifications' => [
        'slot_opened_title' => 'A slot just opened',
        'slot_opened_message' => ':time is free. :client is waiting for it.'
            . '|:time is free. :count clients are waiting, :client fits best.',
    ],
    'validation' => [
        'already_waiting' => ':name is already waiting for this service on :date.',
    ],
    'reasons' => [
        'exact_date' => 'exact date match',
        'flexible_date' => 'flexible date range',
        'time_window' => 'time preference match',
        'service_match' => 'exact service match',
        'manual_priority' => 'asked to be called first',
        'valuable_client' => 'brings good revenue',
        'regular_client' => 'regular client',
    ],
    'warnings' => [
        'no_show_risk' => 'has missed appointments',
    ],
];
