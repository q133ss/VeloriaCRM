<?php

/**
 * Imported by landing:import-template. See resources/landing-templates/salone.php for the manifest contract.
 */
$view = 'landings.full.salon-style';

return [
    'slug' => 'salon-style',
    'name' => 'Стиль',
    'description' => 'Светлая страница парикмахера: услуги, галерея причёсок, запись.',
    'full_page' => true,
    'thumb' => 'landing-templates/salon-style/preview.jpg', // scripts/landing-thumbs.mjs makes it
    'purposes' => ['booking', 'portfolio'],
    'source' => 'https://themewagon.com/themes/free-bootstrap-4-html5-hair-salon-website-template-salon/',
    'license' => 'CC-BY-4.0',
    'attribution' => 'https://boostraptheme.com',
    'categories' => ['hair', 'makeup'],
    'templates' => [
        'general' => $view,
        'promotion' => $view,
        'service' => $view,
        'seasonal' => $view,
        'consultation' => $view,
    ],
    'images' => [
        'hero_image_1' => 'landing-templates/salon-style/img/header-background-2.jpg',
        'extra_image_1' => 'landing-templates/salon-style/img/treamer-small.png',
        'work_image_1' => 'landing-templates/salon-style/img/portfolio/portfolio-1.jpg',
        'work_image_2' => 'landing-templates/salon-style/img/portfolio/portfolio-2.jpg',
        'work_image_3' => 'landing-templates/salon-style/img/portfolio/portfolio-3.jpg',
        'extra_image_2' => 'landing-templates/salon-style/img/bg-footer1.jpg',
    ],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text',
        'hero_image_1', 'extra_image_1', 'work_image_1', 'work_image_2', 'work_image_3',
        'extra_image_2',
    ],
];
