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
        'magic_link_subject' => 'Your login link',
        'magic_link_intro' => 'Tap the button below to log in to the app:',
        'magic_link_cta' => 'Log in',
    ],
    'redirect' => [
        'title' => 'Opening the app',
        'opening' => 'One second, switching to Veloria Client…',
        'button' => 'Open the app manually',
        'code_hint' => "If the app didn't open on its own, enter this code on the login screen:",
        'no_app' => "Don't have the app yet? Install Veloria Client and log in with your email again.",
    ],
    'booking' => [
        'slot_unavailable' => 'This time is no longer available.',
        'master_notification_title' => 'New booking from client portal',
        'master_notification_message' => 'Client :client booked ":service" for :datetime.',
    ],
];
