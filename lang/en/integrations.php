<?php

return [
    'title' => 'Integrations',
    'description' => 'Connect what you actually use: client reminders, email and payments. Leave the rest alone — the CRM works without them.',
    'start_hint' => 'Start with Telegram: it is free and takes five minutes.',
    'security_note' => 'Keys stay in your own cabinet, are never shown to clients and are not sent back to this page after saving.',
    'summary_empty' => 'Nothing connected yet',
    'summary_pattern' => 'Working: :count of :total',
    'moved_notice' => 'Integrations have been moved to',

    'status' => [
        'empty' => 'Not connected',
        'partial' => 'Details missing',
        'filled' => 'Not verified',
        'verified' => 'Working',
        'failed' => 'Not working',
    ],

    'status_hint' => [
        'partial' => 'Missing: :fields',
        'filled' => 'Saved, but the connection has not been verified yet.',
        'verified' => 'Verified :date',
        'failed' => 'Check failed :date',
    ],

    'actions' => [
        'save' => 'Save and verify',
        'saving' => 'Verifying…',
        'check' => 'Verify again',
        'cancel' => 'Discard changes',
        'disconnect' => 'Disconnect',
        'replace' => 'Replace',
        'show' => 'Show',
        'hide' => 'Hide',
        'steps' => 'Where to find these details',
    ],

    'messages' => [
        'saved' => 'Saved.',
        'load_error' => 'Could not load your settings. Reload the page: saving before the data arrives can wipe what is already connected.',
        'save_error' => 'Could not save. Please try again.',
        'disconnect_confirm' => 'Disconnect “:title”? The saved details will be deleted and messages will stop going through this channel.',
        'disconnected' => 'Channel disconnected.',
        'cancel_confirm' => 'Restore the saved values? Anything you typed and did not save will be lost.',
        'leave_confirm' => 'You have unsaved changes.',
        'unsaved' => 'Not saved',
        'required' => 'required',
        'secret_saved' => 'Key saved: :preview',
        'secret_replace_hint' => 'To replace it, type a new key.',
        'secret_kept' => 'The saved key stays in place.',
    ],

    'sections' => [
        'telegram' => [
            'title' => 'Telegram bot',
            'description' => 'Free reminders for clients and booking through a bot.',
            'link_label' => 'Open @BotFather',
            'link_url' => 'https://t.me/BotFather',
            'steps' => [
                'Open a chat with @BotFather in Telegram and send the /newbot command.',
                'Pick a name and an address for the bot — BotFather asks for them one at a time.',
                'It replies with a long string like 1234567890:AAH… — copy all of it.',
            ],
            'fields' => [
                'bot_token' => [
                    'label' => 'Bot token',
                    'hint' => 'The string from the BotFather message, digits before the colon included.',
                ],
                'sender' => [
                    'label' => 'Bot address',
                    'hint' => 'For example veloria_bot, so clients can see who is writing.',
                ],
            ],
        ],

        'smsaero' => [
            'title' => 'SMS via SmsAero',
            'description' => 'SMS reminders about the visit — they arrive without internet.',
            'link_label' => 'SmsAero dashboard',
            'link_url' => 'https://smsaero.ru/cabinet/',
            'steps' => [
                'Sign in to SmsAero with the email the account is registered to.',
                'Open the “API” section and copy the key.',
            ],
            'fields' => [
                'email' => [
                    'label' => 'SmsAero account email',
                    'hint' => 'The one you sign in with.',
                ],
                'api_key' => [
                    'label' => 'API key',
                    'hint' => 'From the “API” section. It is not your account password.',
                ],
            ],
        ],

        'smtp' => [
            'title' => 'Email server',
            'description' => 'Letters and campaigns go out from your own mailbox.',
            'link_label' => null,
            'link_url' => null,
            'steps' => [
                'Your mail provider gives you the server address, port, login and password.',
                'Yandex, Mail.ru and Gmail need a separate “app password” — the normal one is refused.',
                'Typical values: smtp.yandex.ru, port 465, SSL.',
            ],
            'fields' => [
                'host' => [
                    'label' => 'Mail server address',
                    'hint' => 'For example smtp.yandex.ru.',
                ],
                'port' => [
                    'label' => 'Port',
                    'hint' => 'Usually 465 for SSL or 587 for TLS.',
                ],
                'encryption' => [
                    'label' => 'Encryption',
                    'hint' => 'Listed next to the port in your provider’s guide.',
                ],
                'username' => [
                    'label' => 'Login',
                    'hint' => 'Usually your full email address.',
                ],
                'password' => [
                    'label' => 'Password',
                    'hint' => 'The app password, not your mailbox password.',
                ],
                'from_address' => [
                    'label' => 'Sender address',
                    'hint' => 'Clients will receive letters from this address.',
                ],
                'from_name' => [
                    'label' => 'Sender name',
                    'hint' => 'What the client sees in the “From” field.',
                ],
            ],
        ],

        'yookassa' => [
            'title' => 'YooKassa payments',
            'description' => 'Deposits and payments straight from a booking.',
            'link_label' => 'YooKassa dashboard',
            'link_url' => 'https://yookassa.ru/my',
            'steps' => [
                'Sign in to YooKassa and open Settings → Shop.',
                'Copy the shop identifier (shopId).',
                'Issue a secret key there and copy it — it is shown only once.',
            ],
            'fields' => [
                'shop_id' => [
                    'label' => 'Shop identifier',
                    'hint' => 'The shopId number from the YooKassa dashboard.',
                ],
                'secret_key' => [
                    'label' => 'Secret key',
                    'hint' => 'Starts with live_ or test_.',
                ],
            ],
        ],

        'whatsapp' => [
            'title' => 'WhatsApp Business',
            'description' => 'Messages to clients in WhatsApp. Requires WhatsApp Business API access.',
            'link_label' => 'Meta for Developers',
            'link_url' => 'https://developers.facebook.com/apps',
            'steps' => [
                'Access is issued by a WhatsApp Business API provider or Meta for Developers.',
                'You need two things from there: the permanent application token and the Phone number ID. The second one is a number, not the phone itself.',
            ],
            'fields' => [
                'api_key' => [
                    'label' => 'Access token',
                    'hint' => 'The permanent application token, not a temporary debug one.',
                ],
                'sender' => [
                    'label' => 'Phone number ID',
                    'hint' => 'The long number from the dashboard.',
                ],
            ],
        ],
    ],

    'checks' => [
        'missing' => 'Fill in the required fields first.',
        'network' => 'Could not reach the service. Check your connection and try again.',
        'unsupported' => 'Verification is not available for this channel yet.',
        'telegram' => [
            'ok' => 'The bot is online.',
            'ok_named' => 'Bot :name is online.',
            'fail' => 'Telegram refused the token. Copy the whole string from the BotFather message, digits before the colon included.',
        ],
        'smsaero' => [
            'ok' => 'SmsAero accepted the email and the key.',
            'ok_balance' => 'SmsAero accepted the key, balance :balance ₽.',
            'fail' => 'SmsAero refused the email and the key. The key lives in the “API” section and differs from the account password.',
        ],
        'smtp' => [
            'ok' => 'The mail server accepted the login and the password.',
            'auth' => 'The mail server refused the login or the password. Yandex, Mail.ru and Gmail need a separate app password.',
            'fail' => 'Could not reach the mail server. Check the address, the port and the encryption.',
        ],
        'yookassa' => [
            'ok' => 'YooKassa confirmed the shop.',
            'fail' => 'YooKassa refused the shop identifier or the secret key.',
        ],
        'whatsapp' => [
            'ok' => 'WhatsApp confirmed the sender number.',
            'fail' => 'WhatsApp refused the token or the number identifier.',
        ],
    ],

    'smtp_encryption' => [
        'tls' => 'TLS',
        'ssl' => 'SSL',
        'starttls' => 'STARTTLS',
        'none' => 'No encryption',
    ],
];
