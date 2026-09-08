<?php

return [
    'errors' => [
        'plan_required' => 'This section is part of the :plan plan. Upgrade your subscription to unlock it.',
    ],
    'title' => 'Subscription',
    'subtitle' => 'Manage your Veloria CRM plan and billing',
    'current_plan' => [
        'title' => 'Current plan',
        'active_until' => 'Active until :date',
        'renews_at' => 'Next charge: :date',
        'cancelled_at' => 'Will be disabled on :date',
        'no_plan' => 'No subscription yet',
        'free_plan' => 'Free plan does not require payment',
    ],
    'plans' => [
        'lite' => [
            'name' => 'Start',
            'tagline' => 'So the notebook can finally go',
            'description' => 'Your workspace, a booking site and an app for your clients — everything that brings clients in.',
            'badge' => 'Your plan',
            'features' => [
                'Calendar, client records and services',
                'Booking site and Telegram bot',
                'App for your clients',
                'Waitlist and reminders',
            ],
        ],
        'pro' => [
            'name' => 'Master',
            'tagline' => 'So clients come back on their own',
            'description' => 'Campaigns, offers and reports — everything that brings clients back.',
            'badge' => 'Most popular',
            'features' => [
                'Everything from Start',
                'Campaigns and client segments',
                'A/B tested messages and cashback offers',
                'Advanced statistics and reports',
            ],
        ],
        'elite' => [
            'name' => 'Maximum',
            'tagline' => 'So decisions rest on numbers',
            'description' => 'Win-back journeys, smart insights and a read on every client card.',
            'badge' => 'Best value',
            'features' => [
                'Everything from Master',
                'Win-back journeys',
                'Peak hours and smart insights',
                'A content idea every day',
                'Priority support',
            ],
        ],
    ],
    'comparison' => [
        [
            'feature' => 'Booking site',
            'description' => 'Your own address, your services, requests landing straight in your workspace. One site on the free plan.',
            'plans' => [
                'lite' => true,
                'pro' => true,
                'elite' => true,
            ],
        ],
        [
            'feature' => 'App for your clients',
            'description' => 'Booking from a phone, under your name.',
            'plans' => [
                'lite' => true,
                'pro' => true,
                'elite' => true,
            ],
        ],
        [
            'feature' => 'Campaigns and segments',
            'description' => 'Messages to sleeping, new and regular clients.',
            'plans' => [
                'lite' => false,
                'pro' => true,
                'elite' => true,
            ],
        ],
        [
            'feature' => 'Cashback offers',
            'description' => 'A percentage back or a free service, with usage tracked.',
            'plans' => [
                'lite' => false,
                'pro' => true,
                'elite' => true,
            ],
        ],
        [
            'feature' => 'Advanced statistics',
            'description' => 'Revenue, retention, LTV and service mix.',
            'plans' => [
                'lite' => false,
                'pro' => true,
                'elite' => true,
            ],
        ],
        [
            'feature' => 'Win-back journeys and smart insights',
            'description' => 'Return scenarios, peak hours and client forecasts.',
            'plans' => [
                'lite' => false,
                'pro' => false,
                'elite' => true,
            ],
        ],
        [
            'feature' => 'Core features',
            'description' => 'Scheduling, clients and reminders.',
            'plans' => [
                'lite' => true,
                'pro' => true,
                'elite' => true,
            ],
        ],
    ],
    'comparison_title' => 'Plan comparison',
    'comparison_feature' => 'Capability',
    'transactions' => [
        'title' => 'Transaction history',
        'date' => 'Date',
        'plan' => 'Plan',
        'amount' => 'Amount',
        'status' => 'Status',
        'payment_id' => 'Payment ID',
        'empty' => 'No transactions yet. Payments will appear here once they are processed.',
    ],
    'statuses' => [
        'pending' => 'Pending',
        'succeeded' => 'Paid',
        'canceled' => 'Cancelled',
        'cancelled' => 'Cancelled',
        'waiting_for_capture' => 'Awaiting capture',
        'failed' => 'Failed',
        'unknown' => 'Unknown',
    ],
    'actions' => [
        'upgrade' => 'Upgrade to :plan',
        'current' => 'Current plan',
        'cancel' => 'Cancel subscription',
        'contact' => 'Contact support',
    ],
    'alerts' => [
        'upgrade_error' => 'Unable to create a payment. Please try again later or contact support.',
        'cancel_success' => 'Your subscription ends on :date — everything keeps working until then. Bookings, clients and history stay where they are, and you can come back to the plan at any time.',
    ],
    'cancel' => [
        'title' => 'What happens after cancellation',
        'description' => 'Here is what changes once your paid access ends.',
        'keep_title' => '✅ You keep:',
        'lose_title' => '⛔ You lose access to:',
        'keep' => [
            'All client data and appointment history.',
            'Your workspace, calendar and client records.',
            'Your booking site and the app for your clients.',
        ],
        'lose' => [
            'Campaigns and segments.',
            'Cashback offers.',
            'Advanced reports.',
            'Win-back journeys and smart insights.',
        ],
        'note' => 'You can upgrade again at any time — your data stays safe.',
    ],
    'payment' => [
        'description' => 'Subscription payment for :plan',
    ],
    'yookassa' => [
        'connected' => 'YooKassa connected',
        'missing' => 'YooKassa is not configured',
        'hint' => 'Reach out to support to enable payments.',
    ],
    'currency' => '₽',
    'billing_period' => 'per month',
];
