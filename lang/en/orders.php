<?php

return [
    'validation' => [
        'client_phone' => [
            'required' => "Please enter the client's phone number.",
            'string' => "The client's phone number must be a string.",
            'max' => "The client's phone number may not be greater than :max characters.",
        ],
        'client_name' => [
            'string' => "The client's name must be a string.",
            'max' => "The client's name may not be greater than :max characters.",
        ],
        'client_email' => [
            'email' => "Please provide a valid client email address.",
            'max' => "The client email may not be greater than :max characters.",
        ],
        'scheduled_at' => [
            'required' => 'Please specify the appointment date and time.',
            'date' => 'The appointment date has an invalid format.',
        ],
        'services' => [
            'array' => 'The services list must be an array.',
            'integer' => 'Each selected service identifier must be a number.',
            'exists' => 'Some of the selected services are not available.',
        ],
        'note' => [
            'string' => 'The note must be text.',
            'max' => 'The note may not be greater than :max characters.',
        ],
        'total_price' => [
            'numeric' => 'The total amount must be a number.',
            'min' => 'The total amount cannot be negative.',
        ],
        'status' => [
            'required' => 'Please choose a booking status.',
            'in' => 'The selected booking status is invalid.',
        ],
        'source' => [
            'string' => 'The source must be a string.',
            'max' => 'The source may not be greater than :max characters.',
        ],
    ],
    'attributes' => [
        'client_phone' => "Client's phone",
        'client_name' => "Client's name",
        'client_email' => "Client's email",
        'scheduled_at' => 'Appointment date and time',
        'services' => 'Services',
        'note' => 'Note',
        'total_price' => 'Total amount',
        'status' => 'Status',
        'source' => 'Source',
    ],
];
