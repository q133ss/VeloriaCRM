<?php

/**
 * Original Veloria layout. See resources/landing-templates/salone.php for the manifest contract.
 */
$view = 'landings.full.ads-one';

return [
    'slug' => 'ads-one',
    'name' => 'Реклама',
    'description' => 'Короткая страница для рекламы: обещание, три преимущества и запись. Без прайса и галереи.',
    'full_page' => true,
    'thumb' => 'landing-templates/ads-one/preview.jpg', // scripts/landing-thumbs.mjs makes it
    'purposes' => ['ads'],
    'source' => 'https://veloria.app/templates/ads-one',
    'license' => 'free-commercial',
    'attribution' => null,
    'categories' => ['nails', 'brows', 'hair', 'barber', 'spa', 'cosmetology', 'makeup'],
    'templates' => [
        'general' => $view,
        'promotion' => $view,
        'service' => $view,
        'seasonal' => $view,
        'consultation' => $view,
    ],
    'images' => [],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text',
    ],
];
