<?php

return [
    /*
     * The answers a master actually asks for, each pointing at a screen that
     * exists. The previous set described a Google/Apple Calendar sync, a
     * «Настройки → Команда» page and a «Настройки → Безопасность → Экспорт
     * данных» page, none of which this CRM has.
     */
    'faqs' => [
        [
            'question' => 'help.faq.reminders.question',
            'answer' => 'help.faq.reminders.answer',
            'link' => ['label' => 'help.faq.reminders.link', 'url' => '/integrations'],
        ],
        [
            'question' => 'help.faq.schedule.question',
            'answer' => 'help.faq.schedule.answer',
            'link' => ['label' => 'help.faq.schedule.link', 'url' => '/settings'],
        ],
        [
            'question' => 'help.faq.telegram.question',
            'answer' => 'help.faq.telegram.answer',
            'link' => ['label' => 'help.faq.telegram.link', 'url' => '/integrations'],
        ],
        [
            'question' => 'help.faq.payments.question',
            'answer' => 'help.faq.payments.answer',
            'link' => ['label' => 'help.faq.payments.link', 'url' => '/integrations'],
        ],
        [
            'question' => 'help.faq.money.question',
            'answer' => 'help.faq.money.answer',
            'link' => ['label' => 'help.faq.money.link', 'url' => '/analytics'],
        ],
        [
            'question' => 'help.faq.plans.question',
            'answer' => 'help.faq.plans.answer',
            'link' => ['label' => 'help.faq.plans.link', 'url' => '/subscription'],
        ],
    ],

    'support' => [
        'contact_email' => 'support@veloria.io',
        'response_time_hours' => 12,
        'working_hours' => 'help.support.working_hours',
        'tips' => [
            'help.support.tips.context',
            'help.support.tips.attachments',
            'help.support.tips.updates',
        ],
    ],

    /*
     * One place for the rules, so the form can say them out loud and the
     * validator can enforce the same numbers.
     */
    'limits' => [
        'subject_max' => 255,
        'message_min' => 10,
        'reply_min' => 3,
    ],

    'attachment' => [
        'max_mb' => 10,
        'extensions' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt', 'csv'],
    ],
];
