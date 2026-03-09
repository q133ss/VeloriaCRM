<?php

return [
    'eyebrow' => 'Veloria Connect',
    'title' => 'Integrations',
    'description' => 'Manage SMS, email, messengers, and payments from a dedicated page instead of mixing them into account settings.',
    'security_note' => 'Secret keys stay in your private cabinet and are never exposed to clients.',
    'summary_label' => 'Connection status',
    'summary_empty' => 'Nothing connected yet',
    'summary_pattern' => 'Connected: :count of 5',
    'status_ready' => 'Connected',
    'status_partial' => 'Partially filled',
    'status_empty' => 'Not configured',
    'helper_badge' => 'Tip',
    'helper_title' => 'Only configure the channels you actually use',
    'helper_text' => 'If you are not using a service yet, leave its fields empty. The CRM will continue to work and will not try to send messages through an unconfigured channel.',
    'save' => 'Save integrations',
    'reset' => 'Reload from server',
    'saved' => 'Integrations saved.',
    'load_error' => 'Failed to load integrations.',
    'moved_notice' => 'Integrations have been moved to',
    'sections' => [
        'smsaero' => [
            'title' => 'SmsAero (SMS)',
            'description' => 'Used for SMS reminders and direct messages to clients.',
            'email_hint' => 'SmsAero account email for Basic Auth.',
            'api_key_hint' => 'API key from your SmsAero dashboard.',
        ],
        'smtp' => [
            'title' => 'SMTP',
            'description' => 'Connect a mail server for email notifications and campaigns.',
            'hint' => 'A working channel usually needs `host`, `port`, `username`, `password`, and `from email`.',
        ],
        'whatsapp' => [
            'title' => 'WhatsApp',
            'description' => 'Connect your WhatsApp Business API provider for client messaging.',
            'hint' => 'You need the API key and sender identifier issued by your provider.',
        ],
        'telegram' => [
            'title' => 'Telegram Bot',
            'description' => 'Bot-based booking flow and Telegram notifications.',
            'hint' => 'Create a bot in BotFather, paste the token, and optionally add the bot username.',
        ],
        'yookassa' => [
            'title' => 'YooKassa',
            'description' => 'Accept prepayments and online payments inside the CRM.',
            'hint' => 'Provide the `Shop ID` and `Secret key` from your YooKassa dashboard.',
        ],
    ],
];
