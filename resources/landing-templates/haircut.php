<?php

/**
 * Imported by landing:import-template. See resources/landing-templates/salone.php for the manifest contract.
 */
$view = 'landings.full.haircut';

return [
    'slug' => 'haircut',
    'name' => 'Парикмахерская',
    'description' => 'Тёмная страница с крупным героем и ценами. Подойдёт парикмахерам и барберам.',
    'full_page' => true,
    'thumb' => 'landing-templates/haircut/preview.jpg', // scripts/landing-thumbs.mjs makes it
    'purposes' => ['booking'],
    'source' => 'https://htmlcodex.com/hair-salon-html-template/',
    'license' => 'CC-BY-4.0',
    'attribution' => 'https://htmlcodex.com',
    'categories' => ['hair', 'barber'],
    'templates' => [
        'general' => $view,
        'promotion' => $view,
        'service' => $view,
        'seasonal' => $view,
        'consultation' => $view,
    ],
    'images' => [
        'hero_image_1' => 'landing-templates/haircut/img/carousel-1.jpg',
        'hero_image_2' => 'landing-templates/haircut/img/carousel-2.jpg',
        'about_image' => 'landing-templates/haircut/img/about.jpg',
        'master_photo' => 'landing-templates/haircut/img/team-1.jpg',
        'extra_image_1' => 'landing-templates/haircut/img/price.jpg',
        'extra_image_2' => 'landing-templates/haircut/img/open.jpg',
    ],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text', 'master_role',
        'hero_image_1', 'hero_image_2', 'about_image', 'master_photo',
        'extra_image_1', 'extra_image_2',
    ],
];
