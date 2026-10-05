<?php

/**
 * Original Veloria layout. See resources/landing-templates/salone.php for the manifest contract.
 */
$view = 'landings.full.promo-flash';

return [
    'slug' => 'promo-flash',
    'name' => 'Акция',
    'description' => 'Крупная скидка, промокод и таймер до конца акции. Для акций и сезонных предложений.',
    'full_page' => true,
    'thumb' => 'landing-templates/promo-flash/preview.jpg', // scripts/landing-thumbs.mjs makes it
    'purposes' => ['promo'],
    'source' => 'https://veloria.app/templates/promo-flash',
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
