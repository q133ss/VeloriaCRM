<?php

return [
    'title' => 'Dashboard',
    'currency' => ':amount ₽',
    'time' => [
        'hours_minutes' => ':hours h :minutes min',
        'hours_only' => ':hours h',
        'minutes_only' => ':minutes min',
    ],
    'metrics' => [
        'clients_summary' => [
            'with_capacity' => ':booked of :capacity',
            'without_capacity' => ':booked',
        ],
    ],
    'messages' => [
        'not_enough_data' => 'Not enough data',
        'no_comparison' => 'No comparison data yet',
    ],

    'hero' => [
        'eyebrow' => 'Today at a glance',
    ],

    'day' => [
        'title' => 'Today',
        'appointments' => 'appointment|appointments',
        'expected' => ':amount expected',
        'new_appointment' => 'New appointment',
        'all_appointments' => 'All appointments',
        'no_service' => 'No service set',
        'empty' => [
            'title' => 'Nothing booked today',
            'text' => 'A free day. Invite clients into the open slots, or take the time off.',
            'action' => 'Open calendar',
        ],
        'setup_empty' => [
            'title' => 'Your bookings will show up here',
            'text' => 'Once setup is done, this screen builds your day on its own.',
        ],
    ],

    'due' => [
        'title' => 'Time to book',
        'days' => 'day|days',
        'rhythm' => 'usually every :interval, :passed since',
        'once' => 'came once, :passed ago',
        'write' => 'Write',
        'all_clients' => 'All clients',
    ],

    'free' => [
        'title' => 'Open slots',
        'tomorrow' => 'Tomorrow',
        'slots' => ':slots free',
        'more' => 'and :count more',
        'suggest' => 'Who to offer',
        'hint' => 'These clients are due about now: :names.',
        'open_calendar' => 'Open calendar',
    ],

    'outreach' => [
        'title' => 'Message to a client',
        'label' => 'Text',
        'loading' => 'Writing…',
        'copy' => 'Copy',
        'copied' => 'Copied',
        'regenerate' => 'Another version',
        'open_whatsapp' => 'Open WhatsApp',
        'close' => 'Close',
        'send' => 'Send via :channel',
        'sending' => 'Sending…',
        'error' => 'Could not draft the text. Try again.',
        'template_note' => 'This is a template. Adjust it before sending.',
        'limit_note' => 'No free drafts left this month. Send the template as is, or edit it.',
        'remaining_note' => 'Free drafts left this month: :count.',
    ],

    'occupancy' => [
        'title' => 'Occupancy',
        'value' => ':value%',
        'up' => ':value% a week ago, rising',
        'down' => ':value% a week ago, falling',
        'same' => 'same as last week',
        'empty' => 'Not enough data yet, a couple of weeks of bookings will do.',
    ],

    'week' => [
        'title' => 'This week',
        'all_analytics' => 'All analytics',
        'revenue' => 'Revenue',
        'clients' => 'Clients',
        'clients_word' => 'client|clients',
        'average_ticket' => 'Average ticket',
    ],

    'setup' => [
        'title' => 'Account setup',
        'progress' => ':done of :total',
        'continue' => 'Continue',
        'done' => 'Your account is ready',
        'steps' => [
            'schedule' => 'Working hours',
            'services' => 'Services',
            'clients' => 'First client',
        ],
        'wizard' => [
            'step_of' => 'Step :current of :total',
            'skip' => 'Later',
            'back' => 'Back',
            'next' => 'Next',
            'saving' => 'Saving…',
            'schedule' => [
                'title' => 'When do you work?',
                'text' => 'Pick the days and hours you see clients. You can change this later in settings.',
                'days' => 'Working days',
                'from' => 'Day starts',
                'to' => 'Day ends',
                'step' => 'How long one appointment runs',
                'step_option' => ':minutes min',
                'step_hour' => '1 hour',
                'step_hour_half' => '1.5 hours',
                'step_two_hours' => '2 hours',
            ],
            'service' => [
                'title' => 'What do you offer?',
                'text' => 'Add one service so you can book clients in a single click. The rest can wait.',
                'name' => 'Name',
                'name_placeholder' => 'Gel manicure',
                'price' => 'Price, ₽',
                'duration' => 'Duration, min',
            ],
            'client' => [
                'title' => 'First client',
                'text' => 'Add the person who comes most often. After this, cards are created straight from a booking.',
                'name' => 'Name',
                'name_placeholder' => 'Irina Sokolova',
                'phone' => 'Phone',
            ],
            'finish' => [
                'title' => 'All set',
                'text' => 'Setup is done. Your home screen will now list whoever is coming today.',
                'action' => 'Get to work',
            ],
            'already_done' => 'This step is already done',
            'error' => 'Could not save. Check the fields and try again.',
        ],
    ],
    'indicators' => [
        'high_attendance' => 'Coming',
        'no_show_risk' => 'May not show',
        'complex_visit' => 'Needs attention',
        'unconfirmed' => 'Not confirmed yet',
    ],
    'finance' => [
        'services' => [
            'insight' => [
                'multi' => 'AI: :first_service brings :first_margin per hour, followed by :second_service at :second_margin.',
                'single' => 'AI: The highest margin now is :service — :margin per hour.',
            ],
        ],
    ],
    'sections' => [
        'focus' => [
            'label' => "Today's hub",
            'title' => 'Focus for today',
            'updated' => 'Updated :time',
            'schedule' => [
                'title' => "Today's schedule",
                'subtitle' => 'Track key visits and AI signals',
                'quick_book' => 'Quick booking',
                'remind' => 'Send reminder',
                'open_card' => 'Open profile',
                'empty' => 'No visits booked today — a great moment to engage new clients.',
            ],
            'metrics' => [
                'title' => 'Today in numbers',
                'forecast_pill' => 'Profit forecast — :amount',
                'revenue' => [
                    'label' => 'Revenue',
                    'description' => 'Revenue vs forecast',
                ],
                'clients' => [
                    'label' => 'Clients today',
                    'description' => 'Clients scheduled',
                ],
                'avg_ticket' => [
                    'label' => 'Average ticket',
                    'description' => 'Net revenue per visit',
                ],
                'retention' => [
                    'label' => 'Repeat visits',
                    'description' => 'Share of returning clients',
                ],
            ],
            'ai' => [
                'title' => 'AI assistant tips',
                'subtitle' => 'What to act on right now',
                'badge' => 'Top priority',
                'fallback_action' => 'Go to clients',
                'empty' => 'No suggestions yet — they will appear as new bookings and payments arrive.',
                'priority' => [
                    'urgent' => 'Urgent',
                    'high' => 'Important',
                    'normal' => 'Later',
                ],
            ],
        ],
        'finance' => [
            'label' => 'Growth analytics',
            'title' => 'Finance and efficiency',
            'cta' => 'Open full analytics',
            'margin' => [
                'title' => 'Margin per hour',
                'subtitle' => 'See which days are most profitable',
                'best_day' => 'Best day: :day — :value',
            ],
            'revenue' => [
                'title' => 'Revenue over period',
                'subtitle' => 'Compared to previous period',
                'delta' => 'vs previous period: :value%',
                'growth' => 'Up :value% vs previous period',
                'decline' => 'Down :value% vs previous period',
            ],
            'services' => [
                'title' => 'Top-3 profitable services',
                'avg_duration' => 'Average duration: :value',
                'per_hour' => '₽/hour',
                'empty' => 'No service data yet',
                'empty_insight' => 'As soon as sales appear we will highlight your most profitable services.',
            ],
            'clients' => [
                'title' => 'Best clients',
                'ltv' => 'LTV: :value',
                'last_visit' => 'Last visit: :date',
                'empty' => 'No highlighted clients yet',
                'note' => 'We spotlight clients who refer, review and come back most often.',
            ],
        ],
        'learning' => [
            'label' => 'Micro-learning & trends',
            'title' => 'Veloria daily tip',
            'fallback' => 'Stay tuned — a personalised Veloria tip will appear here soon.',
            'button' => 'Learn more',
            'source' => 'Source: :value',
            'default_source' => 'Veloria AI assistant',
        ],
    ],
];
