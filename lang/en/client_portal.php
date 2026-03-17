<?php

return [
    'auth' => [
        'unauthorized' => 'Unauthorized.',
        'code_sent' => 'Verification code sent.',
        'invalid_or_expired' => 'Invalid or expired verification code.',
        'client_not_found' => 'Client not found.',
        'invalid_master_selection' => 'Invalid master selection.',
        'too_many_requests' => 'Too many requests. Please try again later.',
    ],
    'email' => [
        'otp_subject' => 'Your verification code',
        'otp_intro' => 'Use this code to continue:',
        'otp_expires' => 'This code expires in :minutes minutes.',
    ],
    'booking' => [
        'slot_unavailable' => 'This time is no longer available.',
        'master_notification_title' => 'New booking from client portal',
        'master_notification_message' => 'Client :client booked ":service" for :datetime.',
    ],
];
