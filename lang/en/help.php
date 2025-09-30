<?php

return [
    'title' => 'Help Center',
    'subtitle' => 'Find answers, explore the knowledge base, or reach out to the support team.',
    'knowledge_base' => [
        'title' => 'Knowledge Base',
        'subtitle' => 'Ready-to-use guides and best practices for Veloria CRM.',
        'getting_started' => [
            'title' => 'Getting started',
            'description' => 'Learn how to launch online booking, set up services and automate reminders in under an hour.'
        ],
        'automation' => [
            'title' => 'Automation playbook',
            'description' => 'Templates for service flows, waitlists and nurture campaigns that save hours every week.'
        ],
        'billing' => [
            'title' => 'Billing & plans',
            'description' => 'How to manage subscriptions, invoices and payment methods for your workspace.'
        ],
        'cta' => 'Open article',
    ],
    'faq' => [
        'title' => 'Frequently asked questions',
        'subtitle' => 'Short answers for the most popular support tickets.',
        'sync' => [
            'question' => 'How do I sync bookings with my calendar?',
            'answer' => 'Open the calendar settings and connect Google or Apple Calendar. Pick the calendars you want to sync and Veloria will push new bookings instantly.'
        ],
        'notifications' => [
            'question' => 'Clients do not receive reminders — what should I check?',
            'answer' => 'Make sure SMS credits are available and notification templates are active. You can send yourself a test message from Settings → Notifications.'
        ],
        'roles' => [
            'question' => 'Can I invite team members with limited access?',
            'answer' => 'Yes, in Settings → Team you can invite specialists and set granular permissions for services, finances and marketing.'
        ],
        'security' => [
            'question' => 'Where can I download data export for compliance?',
            'answer' => 'Go to Settings → Security → Data export. You can download JSON or CSV snapshots once every 24 hours.'
        ],
    ],
    'support' => [
        'title' => 'Contact support',
        'subtitle' => 'Describe your issue and attach files or screenshots if needed. We answer every request.',
        'response_time' => 'Average response time — :hours h within business hours.',
        'working_hours' => 'Weekdays 09:00 – 21:00 (GMT+3).',
        'tips' => [
            'context' => 'Add links or booking IDs so the team can reproduce the issue faster.',
            'attachments' => 'Attach screenshots or exports to speed up investigation.',
            'updates' => 'We notify you by email and in-app once there is an update.',
        ],
        'form' => [
            'subject_label' => 'Subject',
            'subject_placeholder' => 'E.g. “Reminders are sent twice”',
            'message_label' => 'Message',
            'message_placeholder' => 'Describe what happened, what you expected and how we can help.',
            'attachment_label' => 'Attachment (optional)',
            'submit' => 'Send ticket',
            'success' => 'Ticket created. We will reply by email and in this chat.',
        ],
    ],
    'tickets' => [
        'title' => 'My tickets',
        'empty' => 'You have not contacted support yet.',
        'statuses' => [
            'open' => 'New',
            'waiting' => 'Waiting for support',
            'responded' => 'Reply sent',
            'closed' => 'Closed',
        ],
        'updated_at' => 'Updated :date',
        'view' => 'View conversation',
        'messages' => [
            'from_support' => 'Support team',
            'from_you' => 'You',
            'no_messages' => 'No messages yet.',
        ],
    ],
    'alerts' => [
        'load_error' => 'Could not load help center data. Please refresh the page.',
        'ticket_load_error' => 'Could not load tickets. Try again later.',
        'ticket_submit_error' => 'Could not send the ticket. Check the form and try again.',
        'attachment_too_large' => 'File is too large. Maximum size — 10 MB.',
        'attachment_type' => 'Unsupported file type. Allowed: jpg, png, pdf, doc, docx, txt, csv.',
    ],
];
