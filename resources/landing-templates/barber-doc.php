<?php

/**
 * Imported by landing:import-template. See resources/landing-templates/salone.php for the manifest contract.
 */
$view = 'landings.full.barber-doc';

return [
    'slug' => 'barber-doc',
    'name' => 'Барбершоп',
    'description' => 'Тёмная страница барбершопа: услуги, цены, запись.',
    'full_page' => true,
    'thumb' => 'landing-templates/barber-doc/preview.jpg', // scripts/landing-thumbs.mjs makes it
    'purposes' => ['booking'],
    'source' => 'https://themewagon.com/themes/free-barber-shop-html5-website-template-barber/',
    'license' => 'CC-BY-3.0',
    'attribution' => 'https://colorlib.com',
    'categories' => ['barber', 'hair'],
    'templates' => [
        'general' => $view,
        'promotion' => $view,
        'service' => $view,
        'seasonal' => $view,
        'consultation' => $view,
    ],
    'images' => [
        'hero_image_1' => 'landing-templates/barber-doc/img/banner/banner.jpg',
        'work_image_1' => 'landing-templates/barber-doc/img/about/about_lft.png',
        'work_image_2' => 'landing-templates/barber-doc/img/about/about_right.png',
        'extra_image_1' => 'landing-templates/barber-doc/img/prising/1.png',
        'extra_image_2' => 'landing-templates/barber-doc/img/banner/prise_bg.jpg',
    ],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text',
        'hero_image_1', 'work_image_1', 'work_image_2', 'extra_image_1',
        'extra_image_2',
    ],
];
