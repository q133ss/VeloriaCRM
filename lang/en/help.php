<?php

return [
    'title' => 'Help and support',
    'subtitle' => 'Start with the common questions below — most situations are answered there. If they are not, write to us.',

    'faq' => [
        'title' => 'Common questions',
        'subtitle' => 'Short answers with a link to the screen you need.',

        'reminders' => [
            'question' => 'A client did not get a reminder — what should I check?',
            'answer' => 'Reminders go out through a connected channel. Open “Integrations” and make sure the channel says “Working”: the Telegram bot for free messages or SmsAero for SMS. The “Save and verify” button there asks the service directly. Then enable the channel in Settings, under “Notifications”.',
            'link' => 'Open integrations',
        ],
        'schedule' => [
            'question' => 'How do I set working days, hours and days off?',
            'answer' => 'In Settings, under “Work settings”. Pick a mode: weekly, a shift cycle (2/2, 1/1) or specific dates. Hours are listed with commas — 09:00, 12:00, 15:30. Individual days off go into “Days off” below. A day switched on without hours will not be saved — the time is required.',
            'link' => 'Open settings',
        ],
        'telegram' => [
            'question' => 'How do I connect a Telegram bot for bookings?',
            'answer' => 'Open a chat with @BotFather in Telegram, send /newbot and pick a name. It replies with a long string like 1234567890:AAH… — paste it into “Integrations” → “Telegram bot” and press “Save and verify”. If the bot is online, the status becomes “Working”.',
            'link' => 'Open integrations',
        ],
        'payments' => [
            'question' => 'How do I take deposits online?',
            'answer' => 'Through YooKassa. In its dashboard, under Settings → Shop, take the shop identifier and the secret key, paste them into “Integrations” → “YooKassa payments” and verify the connection.',
            'link' => 'Open integrations',
        ],
        'money' => [
            'question' => 'Where do I see how much I earned?',
            'answer' => 'In “Analytics”: revenue, visit count, returning clients and the average ticket for the chosen period. The numbers come from the bookings in your calendar.',
            'link' => 'Open analytics',
        ],
        'plans' => [
            'question' => 'How do the plans differ and how do I switch?',
            'answer' => 'The “Subscription” page shows what each plan includes and which one you are on. Some features — the weekly digest in “Useful” and allergy reminders, for instance — are available on Pro and Elite.',
            'link' => 'Open plans',
        ],
    ],

    'support' => [
        'title' => 'Write to support',
        'subtitle' => 'If the answers above did not help, describe the situation and we will look into it.',
        'response_time' => 'We answer within :hours h on average.',
        'working_hours' => 'Weekdays 09:00–21:00 (GMT+3).',
        'contact_email' => 'If the form does not send, write to :email.',
        'tips' => [
            'context' => 'Add links or booking IDs so we can reproduce the problem faster.',
            'attachments' => 'Attach a screenshot — it shows more than a description does.',
            'updates' => 'We will notify you by email and in the app when there is an update.',
        ],
        'form' => [
            'subject_label' => 'Subject',
            'subject_placeholder' => 'For example, “Reminders are duplicated”',
            'message_label' => 'Message',
            'message_placeholder' => 'Tell us what happened, what you expected and how we can help.',
            'message_hint' => 'At least :min characters — so we understand the situation the first time.',
            'attachment_label' => 'Attachment (optional)',
            'attachment_hint' => 'Up to :size MB. Formats: :formats.',
            'attachment_choose' => 'Choose a file',
            'attachment_empty' => 'No file chosen',
            'attachment_clear' => 'Remove file',
            'submit' => 'Send message',
            'success' => 'Ticket created. We will reply by email and in this chat.',
            'open_conversation' => 'Open the conversation',
        ],
    ],

    'tickets' => [
        'title' => 'My tickets',
        'subtitle' => 'Your messages and the team’s replies will appear here.',
        'empty' => 'No tickets yet. Once you write to support, the conversation will appear here.',
        'refresh' => 'Refresh the list',
        'statuses' => [
            'open' => 'New',
            'waiting' => 'Waiting for support',
            'responded' => 'Answered',
            'closed' => 'Closed',
        ],
        'answered_notice' => 'Support has replied to your ticket.',
        'updated_at' => 'Updated :date',
        'view' => 'Open conversation',
        'reply_submit' => 'Send reply',
        'reply_hint' => 'At least :min characters.',
        'reply_sent' => 'Reply sent.',
        'messages' => [
            'from_support' => 'Support team',
            'from_you' => 'You',
            'no_messages' => 'No messages yet.',
        ],
    ],

    'alerts' => [
        'load_error' => 'Could not load the help section. Please reload the page.',
        'ticket_load_error' => 'Could not load your tickets. Please try again later.',
        'ticket_submit_error' => 'Could not send the message. Check the fields and try again.',
        'attachment_too_large' => 'The file is too large. Maximum size — :size MB.',
        'attachment_type' => 'We cannot accept this format. These will do: :formats.',
    ],
];
