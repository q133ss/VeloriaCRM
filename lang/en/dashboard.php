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
    'indicators' => [
        'high_attendance' => '🟢 Likely to show',
        'no_show_risk' => '🟡 No-show risk',
        'complex_visit' => '🔴 Complex visit',
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
