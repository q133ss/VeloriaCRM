<?php

return [
    'messages' => [
        'created' => 'The client was added to the smart waitlist.',
        'updated' => 'The waitlist entry was updated.',
        'deleted' => 'The waitlist entry was removed.',
    ],
    'notifications' => [
        'slot_opened_title' => 'A slot just opened',
        'slot_opened_message' => 'There are :count waitlist candidates for :time. The strongest fit is :client.',
    ],
    'reasons' => [
        'exact_date' => 'exact date match',
        'flexible_date' => 'flexible date range',
        'time_window' => 'time preference match',
        'service_match' => 'exact service match',
        'manual_priority' => 'manual priority',
        'high_ltv' => 'high LTV',
        'good_ltv' => 'solid LTV',
        'regular_client' => 'regular client',
        'no_show_risk' => 'no-show risk',
    ],
];
