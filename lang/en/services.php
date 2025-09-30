<?php

return [
    'title' => 'Services',
    'subtitle' => 'Organise your catalogue, track prices and duration for every treatment.',
    'actions' => [
        'create_service' => 'New service',
        'create_category' => 'New category',
    ],
    'filters' => [
        'search_label' => 'Search services',
        'search_placeholder' => 'Name, category or keyword',
        'category_label' => 'Category',
        'category_placeholder' => 'All categories',
        'price_label' => 'Price (₽)',
        'duration_label' => 'Duration (min)',
        'price_min_placeholder' => 'from',
        'price_max_placeholder' => 'to',
        'duration_min_placeholder' => 'from',
        'duration_max_placeholder' => 'to',
        'sort_label' => 'Sort by',
        'direction_label' => 'Order',
        'sort_options' => [
            'name' => 'Name',
            'base_price' => 'Price',
            'duration_min' => 'Duration',
            'created_at' => 'Creation date',
        ],
        'direction_options' => [
            'asc' => 'Ascending',
            'desc' => 'Descending',
        ],
        'apply' => 'Apply',
        'reset' => 'Reset',
    ],
    'groups' => [
        'uncategorized' => 'Without category',
    ],
    'stats' => [
        'summary' => [
            'filtered' => 'Matching: :count',
            'total' => 'Total services: :count',
            'avg_price' => 'Average price: :value',
            'avg_duration' => 'Average duration: :value min',
            'uncategorized' => 'Without category: :count',
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
        'validation_failed' => 'Please check the highlighted fields.',
    ],
    'table' => [
        'price' => 'Price',
        'duration' => 'Duration',
        'cost' => 'Cost',
        'margin' => 'Margin',
        'upsell' => 'Upsell',
        'updated_at' => 'Updated :date',
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
            'duration_min' => 'Duration, min',
            'upsell' => 'Upsell suggestions',
            'upsell_hint' => 'Each line becomes a separate suggestion.',
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
                'array' => 'The upsell list must be an array.',
                'max' => 'You can add up to :max suggestions.',
                'string' => 'Each suggestion must be a string.',
                'item_max' => 'A suggestion may not exceed :max characters.',
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
