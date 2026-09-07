<?php

return [
    'title' => 'Services',
    'subtitle' => 'Price, duration and demand — everything a service is decided on.',
    'actions' => [
        'create_service' => 'New service',
        'create_category' => 'New category',
        'cancel' => 'Cancel',
        'edit' => 'Edit',
        'book' => 'Book this service',
        'more' => 'More',
        'delete' => 'Delete service',
        'rename' => 'Rename',
        'delete_category' => 'Delete category',
        'close' => 'Close',
    ],
    'filters' => [
        'search_label' => 'Search services',
        'search_placeholder' => 'Name or category',
    ],
    'groups' => [
        'uncategorized' => 'Without category',
    ],
    'stats' => [
        'summary' => [
            'all_categories' => 'All',
        ],
    ],
    'messages' => [
        'created' => 'Service created.',
        'updated' => 'Service updated.',
        'deleted' => 'Service deleted.',
        'category_created' => 'Category created.',
        'category_updated' => 'Category updated.',
        'category_deleted' => 'Category deleted. Services were moved to "Without category".',
    ],
    'alerts' => [
        'no_services' => 'No services found. Try adjusting the filters or add your first service.',
        'load_error' => 'Failed to load services. Please refresh the page.',
    ],
    'table' => [
        'price' => 'Price',
        'duration' => 'Duration',
        'upsell' => 'Often booked together',
        'service' => 'Service',
        'demand' => 'Demand',
    ],
    'units' => [
        'visits' => ['one' => 'visit', 'few' => 'visits', 'many' => 'visits'],
        'bookings' => ['one' => 'booking', 'few' => 'bookings', 'many' => 'bookings'],
        'services' => ['one' => 'service', 'few' => 'services', 'many' => 'services'],
        'times' => ['one' => 'time', 'few' => 'times', 'many' => 'times'],
    ],
    'duration' => [
        'planned' => ':minutes min',
        'measured' => 'usually :minutes min',
        'samples' => 'over :count :unit',
        'apply' => 'Set :minutes min',
        'applied' => 'Duration updated: :minutes min.',
        'review_lead' => '{1} one service takes longer than planned|[0,*] :count services take longer than planned',
    ],
    'demand' => [
        'never' => 'Never booked',
        'bookings' => ':count :unit',
        'never_completed' => 'none finished',
        'below_cost' => 'Price is below cost',
    ],
    'lead' => [
        'catalog' => ':count :unit in the catalogue',
        'empty' => 'The catalogue is empty — bookings start here.',
    ],
    'modals' => [
        'service' => [
            'create_title' => 'New service',
            'edit_title' => 'Edit service',
            'name' => 'Name',
            'category' => 'Category',
            'category_placeholder' => 'No category',
            'base_price' => 'Base price, ₽',
            'cost' => 'Cost, ₽',
            'cost_hint' => 'Optional.',
            'upsell' => 'Often booked together',
            'upsell_hint' => 'Services you offer alongside this one.',
            'upsell_suggested' => 'Bookings show these taken with it:',
            'duration_min' => 'Duration, min',
            'cost_calculator' => [
                'open' => 'Cost calculator',
                'close' => 'Hide calculator',
                'materials' => 'Materials',
                'staff' => 'Team time',
                'other' => 'Other expenses',
                'total' => 'Total cost',
                'apply' => 'Apply to cost',
                'reset' => 'Clear',
            ],
            'margin_label' => 'Margin',
            'margin_hint' => 'Fill price and cost to see the margin.',
            'margin_positive_hint' => 'Great! The service stays profitable.',
            'margin_negative_hint' => 'Attention: the cost is higher than the price.',
            'save' => 'Save',
            'create' => 'Create',
        ],
        'category' => [
            'create_title' => 'New category',
            'edit_title' => 'Rename category',
            'name' => 'Category name',
            'save' => 'Save',
            'create' => 'Create',
        ],
        'confirm' => [
            'title_service' => 'Delete service?',
            'body_service' => 'The service “:name” will be removed from the catalogue. Bookings keep their history.',
            'title_category' => 'Delete category?',
            'body_category' => 'All services from “:name” will be moved to "Without category".',
            'confirm' => 'Delete',
        ],
    ],
    'validation' => [
        'form' => [
            'name' => [
                'required' => 'Enter a service name.',
                'string' => 'The service name must be a string.',
                'max' => 'The service name may not exceed :max characters.',
                'unique' => 'A service with this name already exists.',
            ],
            'category' => [
                'integer' => 'The category is invalid.',
                'exists' => 'Please choose an existing category.',
            ],
            'base_price' => [
                'required' => 'Enter the service price.',
                'numeric' => 'The price must be a number.',
                'min' => 'The price cannot be negative.',
                'max' => 'The price may not be greater than :max.',
            ],
            'cost' => [
                'numeric' => 'The cost must be a number.',
                'min' => 'The cost cannot be negative.',
                'max' => 'The cost may not be greater than :max.',
            ],
            'duration' => [
                'required' => 'Enter the service duration.',
                'integer' => 'Duration must be an integer.',
                'min' => 'The minimum duration is :min minutes.',
                'max' => 'The maximum duration is :max minutes.',
            ],
            'upsell' => [
                'array' => 'The related services list must be an array.',
                'max' => 'You can add up to :max services.',
                'item' => 'Pick a service from your own catalogue.',
                'self' => 'A service cannot accompany itself.',
            ],
        ],
        'category' => [
            'name' => [
                'required' => 'Enter a category name.',
                'string' => 'The category name must be a string.',
                'max' => 'The category name may not exceed :max characters.',
                'unique' => 'A category with this name already exists.',
            ],
        ],
        'filters' => [
            'search' => [
                'string' => 'The search query must be a string.',
                'max' => 'The search query may not exceed :max characters.',
            ],
            'category' => [
                'integer' => 'The category value is invalid.',
                'exists' => 'The selected category is not available.',
            ],
            'price' => [
                'numeric' => 'Price must be a number.',
                'min' => 'Price cannot be negative.',
            ],
            'duration' => [
                'integer' => 'Duration must be an integer.',
                'min' => 'Duration cannot be negative.',
            ],
            'sort' => [
                'in' => 'The selected sort option is invalid.',
            ],
            'direction' => [
                'in' => 'The selected order is invalid.',
            ],
        ],
    ],
];
