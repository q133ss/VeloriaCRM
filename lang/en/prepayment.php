<?php

return [
    'payment_description' => 'Booking prepayment: :service, :datetime',
    'required' => 'This booking needs a prepayment of :amount. It counts toward the price of the service.',
    'payment_failed' => 'We could not create the payment. Please try again in a moment.',
    'cancel_reason_unpaid' => 'The prepayment did not arrive in time.',
    'lapsed_title' => 'Booking not confirmed',
    'lapsed_message' => 'The prepayment for ":service" on :datetime did not arrive, so the time was released. You can book again.',
    'refund_failed' => 'The booking was cancelled, but the prepayment could not be refunded. Refund it in your YooKassa account.',
    'return' => [
        'title_waiting' => 'Checking your payment…',
        'text_waiting' => 'This usually takes a few seconds. You can keep this page open.',
        'title_paid' => 'You are booked',
        'text_paid' => 'The prepayment of :amount was received. We will remind you before the visit.',
        'title_expired' => 'Booking not confirmed',
        'text_expired' => 'The prepayment did not arrive and the time was released. Please book again.',
        'title_refunded' => 'Prepayment refunded',
        'text_refunded' => 'The booking was cancelled and the money will return to your card.',
        'title_unknown' => 'Link not found',
        'text_unknown' => 'Check the address or book again.',
        'when' => ':date at :time',
        'open_app' => 'Open the app',
    ],
];
