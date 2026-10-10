<?php

/**
 * Imported by landing:import-template. See resources/landing-templates/salone.php for the manifest contract.
 */
$view = 'landings.full.masterclass';

return [
    'slug' => 'masterclass',
    'name' => 'Курсы и мастер-классы',
    'description' => 'Для мастеров, которые обучают: занятия с ценами, скидка для новых учеников и запись.',
    'full_page' => true,
    'thumb' => 'landing-templates/masterclass/preview.jpg', // scripts/landing-thumbs.mjs makes it
    'purposes' => ['course'],
    'source' => 'https://htmlcodex.com/online-courses-html-template/',
    'license' => 'CC-BY-4.0',
    'attribution' => 'https://htmlcodex.com',
    'categories' => ['hair', 'makeup', 'brows', 'nails', 'cosmetology'],
    'templates' => [
        'general' => $view,
        'promotion' => $view,
        'service' => $view,
        'seasonal' => $view,
        'consultation' => $view,
    ],
    'images' => [
        'hero_image_1' => 'landing-templates/masterclass/img/carousel-1.jpg',
        'about_image' => 'landing-templates/masterclass/img/about.jpg',
        'extra_image_1' => 'landing-templates/masterclass/img/registration.jpg',
    ],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text', 'master_bio',
        'hero_image_1', 'about_image',
        'extra_image_1',
    ],
];
