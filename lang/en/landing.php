<?php

/**
 * Home page copy.
 *
 * Keys carrying `soon` mark what is promised but still being built. The list
 * and its status live in LANDING_TODO.md at the project root. When a feature
 * ships, the badge goes away by deleting one line, not by rewriting a section.
 */
return [
    'meta' => [
        'title' => 'Veloria — workspace, booking site and client app for beauty pros. Free',
        'description' => 'A CRM for solo beauty professionals, a booking site and an app for your clients, all on the free plan. Your workspace shows who is coming, who is due for a visit, and what an empty gap in the day costs you.',
    ],

    'nav' => [
        'skip' => 'Skip to content',
        'features' => 'Features',
        'booking' => 'Booking',
        'pricing' => 'Pricing',
        'menu' => 'Menu',
    ],

    'soon' => 'Soon',
    'soon_hint' => 'This part is still being built',

    'hero' => [
        'title' => 'Bookings, clients and money in one workspace',
        'title_accent' => 'Free',
        'lead' => 'Veloria puts your day together for you: who is coming, who is due for a visit, and what an empty gap in the schedule costs. A booking site and an app for your clients are already on the free plan.',
        'cta_secondary' => 'See how it works',
        'note' => 'No card. Setup takes five minutes: your hours, one service, your first client.',
        'mockup' => [
            'label' => 'Today',
            'title' => 'Bookings today',
            'count' => '3 bookings, 8 400 ₽ expected',
            'rows' => [
                ['time' => '10:00', 'client' => 'Irina Sokolova', 'service' => 'Manicure with gel', 'state' => 'ok'],
                ['time' => '12:30', 'client' => 'Marina Kruglova', 'service' => 'Brow shaping', 'state' => 'risk'],
                ['time' => '15:00', 'client' => 'Alina Vetrova', 'service' => 'Lash extensions', 'state' => 'care'],
            ],
            'states' => [
                'ok' => 'Will show up',
                'risk' => 'Might not show',
                'care' => 'Needs attention',
            ],
            'due_title' => 'Due for a visit',
            'due_client' => 'Olga Timoshenko',
            'due_rhythm' => 'usually every 5 weeks, 7 have passed',
            'due_action' => 'Write',
        ],
    ],

    'free' => [
        'label' => 'Free plan',
        'title' => 'Three things you normally pay for separately',
        'lead' => 'Everything that brings clients in is free. We only charge for what brings them back.',
        'items' => [
            'crm' => [
                'title' => 'Your workspace',
                'text' => 'Calendar, client records, services and prices. Weekly hours, a two-on two-off rotation or hand-picked dates — the schedule understands all three, and it will not let two bookings land on the same hour.',
            ],
            'site' => [
                'title' => 'Booking site',
                'text' => 'Your own address, your services and prices, photos of your work. A request from the site lands straight in your workspace. Five ready templates — an evening of work, and the link goes in your profile bio.',
            ],
            'app' => [
                'title' => 'App for your clients',
                'text' => 'Your client installs it and books herself, with no back and forth in the DMs. Your name, your colours — not a booking service, your app.',
                'soon' => true,
            ],
        ],
    ],

    'day' => [
        'label' => 'Every day',
        'title' => 'A workspace that puts your day together',
        'lead' => 'Not a clever assistant — plain arithmetic on your own history. It counts what nobody has time for between clients.',
        'items' => [
            'gap' => [
                'title' => 'An empty gap costs money',
                'text' => 'The calendar shows more than the hole between bookings: it shows the price, based on your own average hour. Two buttons sit right next to it — book someone, or write to someone.',
                'demo_label' => 'Gap between bookings',
                'demo_value' => '2 hours · ≈ 3 000 ₽',
                'demo_actions' => 'Book · Write',
            ],
            'due' => [
                'title' => 'Who is due for a visit',
                'text' => 'The workspace learns each client\'s own rhythm. Irina comes every five weeks and seven have passed, so she is first on the list. No blanket message to everyone who has been away a while.',
                'demo_label' => 'Due for a visit',
                'demo_value' => 'Irina Sokolova',
                'demo_hint' => 'usually every 5 weeks, 7 have passed',
            ],
            'waitlist' => [
                'title' => 'A cancellation stops being a loss',
                'text' => 'When time frees up, the waitlist decides who to offer it to and explains why. It weighs the matching service, what the client actually spends, and past no-shows.',
                'demo_label' => '15:00 just opened up',
                'demo_value' => '3 clients waiting, Marina fits best',
                'demo_reasons' => ['same service', 'spends well', 'has missed before'],
            ],
            'phrase' => [
                'title' => 'A booking out of a sentence',
                'text' => 'Write it the way you think it, and the form fills itself in. It reads dates, weekdays, times, phone numbers and prices, and only asks when it genuinely could not tell.',
                'demo_label' => 'You type',
                'demo_value' => 'Marina tomorrow manicure at 3pm',
                'demo_hint' => 'Marina Kruglova · Manicure with gel · tomorrow, 15:00',
            ],
            'risk' => [
                'title' => 'Who might not show up',
                'text' => 'A mark on the booking, based on past no-shows and reschedules. Cancelling ahead of time does not count against anyone: a warning is a courtesy, not an offence.',
                'demo_label' => 'Tomorrow',
                'demo_chips' => ['Will show up', 'Might not show', 'Needs attention'],
            ],
            'duration' => [
                'title' => 'A price list that learns',
                'text' => 'A timer measures how long the work really takes. If a manicure reliably runs ninety minutes rather than sixty, the workspace says so — and you stop stacking bookings back to back.',
                'demo_label' => 'Manicure with gel',
                'demo_value' => 'priced at 60 min · really 85 min',
            ],
        ],
    ],

    'booking' => [
        'label' => 'Online booking',
        'title' => 'Clients book themselves',
        'lead' => 'Three ways in. They all land in the same calendar, and none of them asks you to answer DMs at eleven at night.',
        'items' => [
            'telegram' => [
                'title' => 'Telegram bot',
                'text' => 'Your client opens the bot, picks a service, a date and a time from your real open slots, and leaves a phone number. The booking is in your calendar and you get a notification. Free on every plan, five minutes to connect.',
            ],
            'site' => [
                'title' => 'Your booking site',
                'text' => 'A link in your profile bio that works instead of a conversation. Services, prices, answers to the usual questions and a booking form.',
            ],
            'app' => [
                'title' => 'The app',
                'text' => 'Books, reschedules, cancels, sees past visits and gets a push reminder the day before.',
                'soon' => true,
            ],
        ],
        'phone' => [
            'brand' => 'Your name',
            'title' => 'Book',
            'service' => 'Manicure with gel',
            'duration' => '60 min · 2 800 ₽',
            'date' => 'Tomorrow, 12 September',
            'slots' => ['10:00', '12:30', '15:00', '17:30'],
            'slot_active' => '12:30',
            'cta' => 'Book now',
        ],
    ],

    'noshow' => [
        'label' => 'Empty gaps',
        'title' => 'So one cancellation does not undo the day',
        'lead' => 'A no-show costs more than it looks: not just the money, but an hour that can no longer be sold.',
        'items' => [
            'reminder' => [
                'title' => 'A reminder the day before',
                'text' => 'It goes out on its own, through the channel your client actually uses: Telegram, SMS, WhatsApp or email.',
            ],
            'prepay' => [
                'title' => 'A deposit on the booking',
                'text' => 'A small amount up front, and "I forgot" happens noticeably less. An unpaid booking releases itself and frees the time.',
                'soon' => true,
            ],
            'waitlist' => [
                'title' => 'Clients hear that time opened up',
                'text' => 'The moment a slot frees, the offer goes to the people waiting, in the order the workspace has already worked out.',
                'soon' => true,
            ],
            'allergy' => [
                'title' => 'Allergies do not get forgotten',
                'text' => 'If a card records an allergy, the workspace brings it up before the visit rather than after.',
            ],
        ],
    ],

    'retention' => [
        'label' => 'Bringing clients back',
        'title' => 'A client who returns costs less than a new one',
        'lead' => 'This is the paid part, and the only thing we charge for. It pays for itself with the first client who comes back.',
        'items' => [
            'segments' => [
                'title' => 'Segments, not a mailing list',
                'text' => 'Sleeping, new, regular, or everyone who books one particular service. You write to people you actually have something to say to.',
            ],
            'ab' => [
                'title' => 'A/B tested messages',
                'text' => 'Two versions on a small group, then the campaign goes out with the one that worked.',
            ],
            'cashback' => [
                'title' => 'Cashback offers',
                'text' => 'A percentage back on an order, a service or a category — or a free service. You see who used it and what it brought in.',
            ],
            'flows' => [
                'title' => 'Win-back journeys',
                'text' => 'A sequence that runs without you: away for two months, a message goes out; no reply, another one a week later.',
                'soon' => true,
            ],
            'loyalty' => [
                'title' => 'Loyalty tiers',
                'text' => 'Counted from visits on their own. Your personal notes, like marking someone a favourite, stay untouched by arithmetic.',
                'soon' => true,
            ],
            'reviews' => [
                'title' => 'A review after the visit',
                'text' => 'The request goes out by itself, and the good ones become the shop window on your site.',
                'soon' => true,
            ],
        ],
    ],

    'analytics' => [
        'label' => 'Numbers',
        'title' => 'Clear where the money comes from',
        'lead' => 'No pivot tables, no charts for the sake of charts. Enough to decide what to change.',
        'items' => [
            ['title' => 'Occupancy', 'text' => 'How much of your working time is actually booked, and whether that is rising or falling.'],
            ['title' => 'Revenue and average ticket', 'text' => 'Against the previous period, not on their own.'],
            ['title' => 'Margin per service', 'text' => 'Price minus the cost of materials — you can see what feeds you and what merely fills the day.'],
            ['title' => 'Retention and LTV', 'text' => 'How many clients come back, and what each one brings over time.'],
            ['title' => 'Peak hours', 'text' => 'When people come to you, and when the room sits empty.'],
            ['title' => 'CSV export', 'text' => 'Your data stays yours: one click and it is out.'],
        ],
    ],

    'pricing' => [
        'label' => 'Pricing',
        'title' => 'Pay for clients coming back, not for getting in',
        'lead' => 'Workspace, site, bot and app — free, with no time limit.',
        'period' => 'per month',
        'free' => 'free',
        'note' => 'No card needed. The free plan is forever, not for two weeks.',
        'cta_free' => 'Start for free',
        'cta_paid' => 'Choose :plan',
        'plans' => [
            'lite' => [
                'name' => 'Start',
                'tagline' => 'So the notebook can finally go',
                'features' => [
                    'Calendar, clients, services and prices',
                    'Booking site',
                    'Telegram booking bot',
                    'App for your clients',
                    'Waitlist and reminders',
                    '3 written messages a month',
                ],
            ],
            'pro' => [
                'name' => 'Master',
                'tagline' => 'So clients come back on their own',
                'badge' => 'Most chosen',
                'features' => [
                    'Everything from Start',
                    'Campaigns and segments',
                    'A/B tested messages',
                    'Cashback offers',
                    'Allergy reminders',
                    'Advanced statistics',
                    'Written messages with no limit',
                ],
            ],
            'elite' => [
                'name' => 'Maximum',
                'tagline' => 'So decisions rest on numbers',
                'features' => [
                    'Everything from Master',
                    'Win-back journeys',
                    'Peak hours and smart insights',
                    'A content idea every day',
                    'A read on every client card',
                    'Priority support',
                ],
            ],
        ],
    ],

    'faq' => [
        'label' => 'Questions',
        'title' => 'The short answers',
        'items' => [
            [
                'q' => 'Free, or free for a month?',
                'a' => 'Free. The Start plan costs 0 ₽ and does not expire: no card, nothing to be charged. Paid plans add campaigns, offers and analytics — the things that bring clients back. The workspace, the site, the bot and the app stay free.',
            ],
            [
                'q' => 'How do I move my client list over?',
                'a' => 'Add cards by hand, or straight from a booking: type a name and a phone number and the card creates itself. Visit history starts counting from your first booking, and each client\'s rhythm settles in after a couple of weeks.',
            ],
            [
                'q' => 'Do I need a site if I already have a social profile?',
                'a' => 'A profile shows your work; a site takes bookings at night while you sleep. One link in your bio, and the "how much is it?" conversation turns into a booking in the calendar.',
            ],
            [
                'q' => 'What happens to my data?',
                'a' => 'It is yours. Analytics export to CSV whenever you want, and if you drop a paid plan your bookings, clients and history stay put — only the paid part closes.',
            ],
            [
                'q' => 'I work two days on, two days off. Does that fit?',
                'a' => 'Yes. Besides an ordinary week, the workspace understands shift rotations like two-on two-off or three-on one-off, and hand-picked dates when the schedule really floats.',
            ],
        ],
    ],

    'cta' => [
        'title' => 'Open your workspace today, and bookings start collecting themselves',
        'lead' => 'Your hours, one service, your first client. Five minutes, and tomorrow fits on a single screen.',
        'note' => 'No card, no call from a salesperson.',
    ],

    'footer' => [
        'tagline' => 'Workspace, site and app for a professional who values her time.',
        'rights' => '© :year Veloria. All rights reserved.',
        'contact' => 'Contact us',
        'language' => 'Language',
    ],
];
