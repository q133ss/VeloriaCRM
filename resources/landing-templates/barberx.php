<?php

/**
 * Imported by landing:import-template. See resources/landing-templates/salone.php for the manifest contract.
 */
$view = 'landings.full.barberx';

return [
    'slug' => 'barberx',
    'name' => 'Барбершоп',
    'description' => 'Тёмный барберский шаблон с крупными фото. Подойдёт барберам и мужским мастерам.',
    'full_page' => true,
    'thumb' => 'landing-templates/barberx/preview.jpg', // scripts/landing-thumbs.mjs makes it
    'purposes' => ['booking'],
    'source' => 'https://htmlcodex.com/barber-shop-template/',
    'license' => 'CC-BY-4.0',
    'attribution' => 'https://htmlcodex.com',
    'categories' => ['barber', 'hair'],
    'templates' => [
        'general' => $view,
        'promotion' => $view,
        'service' => $view,
        'seasonal' => $view,
        'consultation' => $view,
    ],
    'images' => [
        'hero_image_1' => 'landing-templates/barberx/img/hero.png',
        'about_image' => 'landing-templates/barberx/img/about.jpg',
        'master_photo' => 'landing-templates/barberx/img/team-1.jpg',
    ],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text', 'faq_items_text', 'master_role',
        'hero_image_1', 'about_image', 'master_photo',
    ],
];
