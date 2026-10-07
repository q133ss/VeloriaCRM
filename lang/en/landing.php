<?php

/**
 * Home page copy (resources/views/welcome.blade.php). Mirrors lang/ru/landing.php
 * key for key — the page reads the same structure in both locales.
 */
return [
    'meta' => [
        'title' => 'Veloria — clients book themselves. A free booking site, bot and app for beauty pros',
        'description' => 'Free forever: a booking website, a Telegram bot, a client app and a workspace for the master. No more booking in DMs, no more forgotten appointments.',
    ],

    'nav' => [
        'skip' => 'Skip to content',
        'how' => 'How it works',
        'site' => 'Website',
        'app' => 'App',
        'reviews' => 'Reviews',
        'pricing' => 'Pricing',
        'menu' => 'Menu',
        'start_short' => 'Start',
    ],

    'hero' => [
        'badge' => 'Free forever',
        'title' => 'Clients book',
        'title_accent' => 'themselves',
        'lead' => 'Your own booking website, a Telegram bot and a client app — free. You work, bookings keep coming: no back-and-forth in DMs and no “oops, I forgot”.',
        'cta_primary' => 'Get my free website',
        'cta_example' => 'See an example site',
        'note' => 'No card · not a trial, free forever · 5-minute setup',
        'site_address' => 'anna-nails',
        'notify_label' => 'New booking',
        'notify_title' => 'Anna K. · Gel manicure',
        'notify_time' => 'tomorrow, 12:30',
        'visual_alt' => 'A Veloria master website and the client app',
    ],

    'pains' => [
        'label' => 'Sound familiar?',
        'title' => 'If this is you, Veloria is for you',
        'items' => [
            [
                'title' => 'Answering DMs between clients',
                'text' => '“How much is it?”, “Any slot on Saturday?” — with gloves on, at 11 pm and on your day off.',
            ],
            [
                'title' => '“Oops, I forgot”',
                'text' => 'A client doesn’t show up, the slot is lost, and someone else could have taken it.',
            ],
            [
                'title' => 'Feast or famine',
                'text' => 'This week is packed, next week has gaps — and you worry about income.',
            ],
            [
                'title' => 'Software is complex and pricey',
                'text' => 'Salon tools with a hundred buttons and a subscription that’s scary to pay while you have few clients.',
            ],
        ],
        'answer' => 'Veloria takes this off your plate: clients book themselves, reminders go out on their own, and a free slot shows up right away — with an “invite” button.',
    ],

    'steps' => [
        'label' => 'How it works',
        'title' => 'Three steps — and booking runs without you',
        'items' => [
            [
                'title' => 'Add services and hours',
                'text' => 'Names, prices and durations. A regular week, 2-on-2-off or a floating schedule all work.',
            ],
            [
                'title' => 'Pick a site and share the link',
                'text' => 'A template, photos of your work — and the link goes into your bio, Telegram or stories.',
            ],
            [
                'title' => 'Clients book themselves',
                'text' => 'The booking lands in your calendar, you get a notification, the client gets a reminder the day before.',
            ],
        ],
    ],

    'site' => [
        'label' => 'Your website',
        'title' => 'A beautiful booking site in one evening',
        'lead' => 'Your services, prices and photos of your work. Clients pick a free time themselves and the booking lands in your calendar. :count templates — from soft to strict, with text and photos edited right on the page.',
        'hint' => 'Tap a template to open a live example',
        'open' => 'Open example',
        'all' => 'Create my website',
    ],

    'app' => [
        'label' => 'Client app',
        'title' => 'Your app on your client’s phone',
        'lead' => 'A client installs it once — then books in a couple of taps, sees her visits and news, and messages you in chat.',
        'points' => [
            'Booking in a couple of taps — only into your free slots',
            'Push reminder the day before the visit',
            'Chat with you, news, offers and “a slot just opened”',
            'Reschedule or cancel without messaging back and forth',
        ],
        'screens' => [
            'home' => 'Home screen',
            'booking' => 'Booking',
            'chat' => 'Chat with the master',
        ],
        'android' => 'Download for Android',
        'iphone_note' => 'iPhone clients book through your website or Telegram bot — into the same calendar.',
    ],

    'more' => [
        'label' => 'Also free',
        'title' => 'Telegram bot and master workspace',
        'bot' => [
            'title' => 'Telegram booking bot',
            'text' => 'Clients pick a service and a time from your real free slots. Connects in five minutes.',
            'chat' => [
                ['from' => 'bot', 'text' => 'Hi! Pick a service 💅'],
                ['from' => 'me', 'text' => 'Gel manicure'],
                ['from' => 'bot', 'text' => 'Free on Friday: 11:00, 14:00, 16:00'],
                ['from' => 'me', 'text' => '14:00'],
                ['from' => 'bot', 'text' => 'Done! I’ll remind you the day before ✨'],
            ],
        ],
        'crm' => [
            'title' => 'Master workspace',
            'text' => 'Calendar, clients and prices on one screen. See who will come, who might not, and who it’s time to invite back.',
            'today' => 'Today · 3 bookings · 8,400 ₽',
            'rows' => [
                ['time' => '10:00', 'client' => 'Irina Sokolova', 'service' => 'Gel manicure', 'state' => 'ok'],
                ['time' => '12:30', 'client' => 'Marina Kruglova', 'service' => 'Brow shaping', 'state' => 'risk'],
                ['time' => '15:00', 'client' => 'Alina Vetrova', 'service' => 'Lash extensions', 'state' => 'ok'],
            ],
            'states' => [
                'ok' => 'Coming',
                'risk' => 'Might not come',
            ],
            'due_title' => 'Time to invite',
            'due_client' => 'Olga · usually every 5 weeks, it’s been 7',
            'due_action' => 'Message',
        ],
    ],

    'noshow' => [
        'label' => 'Fewer no-shows',
        'title' => 'So one cancellation doesn’t ruin your day',
        'lead' => 'A no-show isn’t just lost money — it’s time you can no longer sell.',
        'items' => [
            'reminder' => [
                'title' => 'Reminder the day before',
                'text' => 'Goes out on its own — wherever the client prefers: Telegram, the app, SMS or email.',
            ],
            'prepay' => [
                'title' => 'Booking deposit',
                'text' => 'A small amount at booking — and “I forgot” happens much less often. Unpaid holds release themselves.',
            ],
            'waitlist' => [
                'title' => '“A slot just opened”',
                'text' => 'Someone cancels — people on the waitlist get an offer right away. The slot gets taken, not lost.',
            ],
        ],
    ],

    'retention' => [
        'label' => 'Bringing clients back · paid plans',
        'title' => 'A returning client costs less than a new one',
        'lead' => 'This is the only thing we charge for. It pays for itself with the first client who comes back.',
        'items' => [
            [
                'title' => 'Message those who haven’t been in a while',
                'text' => 'Veloria knows how often each client comes and tells you who’s due for a nudge.',
            ],
            [
                'title' => 'Messages send themselves',
                'text' => 'Two months without a visit — a warm message goes out. Veloria checks which text works better.',
            ],
            [
                'title' => 'Cashback and regulars',
                'text' => 'Rewards for visits and levels for regulars — calculated automatically, no spreadsheets.',
            ],
            [
                'title' => 'Reviews after the visit',
                'text' => 'The review request goes out on its own, and good reviews show up on your website.',
            ],
        ],
    ],

    'reviews' => [
        'label' => 'Reviews',
        'title' => 'Masters about Veloria',
        'lead' => 'Short videos from people who already take bookings with Veloria.',
        'play' => 'Watch review: :name',
    ],

    'pricing' => [
        'label' => 'Pricing',
        'title' => 'Website, bot and app — free forever',
        'lead' => 'Pay only when you want to bring clients back automatically.',
        'period' => 'per month',
        'free' => 'free',
        'note' => 'No card needed. The free plan is forever, not for two weeks.',
        'cta_free' => 'Start for free',
        'cta_paid' => 'Choose “:plan”',
        'plans' => [
            'lite' => [
                'name' => 'Start',
                'tagline' => 'So clients book themselves',
                'features' => [
                    'Booking website',
                    'Telegram booking bot',
                    'Client app',
                    'Calendar, clients, services and prices',
                    'Reminders and waitlist',
                    '3 AI assistant messages a month',
                ],
            ],
            'pro' => [
                'name' => 'Master',
                'tagline' => 'So clients come back',
                'badge' => 'Popular',
                'features' => [
                    'Everything in Start',
                    'Messages to those who haven’t been in a while',
                    'Finds out which message works better',
                    'Cashback offers',
                    'Allergy reminders',
                    'Clear statistics',
                    'Unlimited AI assistant',
                ],
            ],
            'elite' => [
                'name' => 'Maximum',
                'tagline' => 'So everything runs without you',
                'features' => [
                    'Everything in Master',
                    'Automatic win-back messages',
                    'Hints: when and whom to invite',
                    'Daily post ideas',
                    'Client card reviews',
                    'Priority support',
                ],
            ],
        ],
    ],

    'faq' => [
        'label' => 'FAQ',
        'title' => 'The short version',
        'items' => [
            [
                'q' => 'Is it really free, or free for a month?',
                'a' => 'Really free. The Start plan costs 0 ₽ and never expires: no card, nothing gets charged. The website, bot, app and workspace stay free. Paid plans are only about bringing clients back automatically.',
            ],
            [
                'q' => 'Is it complicated? I’m not good with software',
                'a' => 'If you use Telegram, you’ll manage. Setup is services, hours and picking a website template — usually about five minutes. It works from your phone too.',
            ],
            [
                'q' => 'Why a website if I already have a social profile?',
                'a' => 'Your profile shows your work; the website takes bookings at night while you sleep. One link in your bio turns “how much is it?” into a booking in your calendar.',
            ],
            [
                'q' => 'How do I switch from YCLIENTS, DIKIDI or a notebook?',
                'a' => 'Clients are created right from a booking: type a name and phone and the card appears. Put the new link in your profile and turn off the old service once everyone has moved.',
            ],
            [
                'q' => 'Is the app available on iPhone?',
                'a' => 'Right now the client app runs on Android. iPhone clients book through your website or Telegram bot — into the same calendar.',
            ],
            [
                'q' => 'What happens to my data?',
                'a' => 'It’s yours: clients and bookings can be exported to a file any time. If you leave a paid plan, everything stays — only the paid part closes.',
            ],
            [
                'q' => 'I work two days on, two off — will it fit?',
                'a' => 'Yes. Besides a regular week, Veloria understands shift cycles like 2/2 or 3/1 and individual dates if your schedule floats.',
            ],
        ],
    ],

    'cta' => [
        'title' => 'Let clients book themselves — start today',
        'lead' => 'Services, hours and a link in your bio. Five minutes — and the first booking arrives without any messaging.',
        'note' => 'No card and no sales call.',
    ],

    'sticky' => 'Get my free website',

    'footer' => [
        'tagline' => 'A free booking website, Telegram bot and client app. Plus a workspace where everything is in view.',
        'rights' => '© :year Veloria. All rights reserved.',
        'contact' => 'Contact us',
        'language' => 'Language',
    ],
];
