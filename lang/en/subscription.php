<?php

return [
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
            'name' => 'Lite',
            'tagline' => 'Start quickly and test ideas',
            'description' => 'Core scheduling tools and customer records for everyday work.',
            'badge' => 'Your plan',
            'features' => [
                'Online booking and calendar',
                'Client CRM cards',
                'Automatic SMS and email reminders',
            ],
        ],
        'pro' => [
            'name' => 'Pro',
            'tagline' => 'Grow your team and marketing',
            'description' => 'Landing pages, automations and analytics to scale faster.',
            'badge' => 'Most popular',
            'features' => [
                'Everything from Lite',
                'Landing page builder',
                'Automated campaigns and segmentation',
                'Advanced analytics and dashboards',
            ],
        ],
        'elite' => [
            'name' => 'Elite',
            'tagline' => 'Maximum automation and support',
            'description' => 'Premium support, AI insights and tailored workflows.',
            'badge' => 'Best value',
            'features' => [
                'Everything from Pro',
                'AI analytics and forecasts',
                '24/7 premium support',
                'Custom automations and integrations',
            ],
        ],
    ],
    'comparison' => [
        [
            'feature' => 'Landing pages',
            'description' => 'Build and publish promo pages for services and offers.',
            'plans' => [
                'lite' => false,
                'pro' => true,
                'elite' => true,
            ],
        ],
        [
            'feature' => 'Automated campaigns',
            'description' => 'Communication workflows and drip sequences.',
            'plans' => [
                'lite' => false,
                'pro' => true,
                'elite' => true,
            ],
        ],
        [
            'feature' => 'AI analytics',
            'description' => 'Revenue predictions and smart recommendations.',
            'plans' => [
                'lite' => false,
                'pro' => false,
                'elite' => true,
            ],
        ],
        [
            'feature' => 'Advanced statistics',
            'description' => 'Deep reports by team members, services and channels.',
            'plans' => [
                'lite' => false,
                'pro' => true,
                'elite' => true,
            ],
        ],
        [
            'feature' => 'Core features',
            'description' => 'Scheduling, CRM and reminders.',
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
        'cancel_success' => 'Subscription will be disabled on :date. Until then all features remain available.',
    ],
    'cancel' => [
        'title' => 'What happens after cancellation',
        'keep_title' => '✅ You keep:',
        'lose_title' => '⛔ You lose access to:',
        'keep' => [
            'All client data and appointment history.',
            'Access to core tools (calendar, CRM).',
        ],
        'lose' => [
            'Landing page builder (published pages will stop working).',
            'AI analytics and forecasts.',
            'Automated campaigns.',
            'Advanced reports.',
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
