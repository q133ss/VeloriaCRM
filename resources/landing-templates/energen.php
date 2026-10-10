<?php

/**
 * Imported by landing:import-template. See resources/landing-templates/salone.php for the manifest contract.
 */
$view = 'landings.full.energen';

return [
    'slug' => 'energen',
    'name' => 'Энергия красоты',
    'description' => 'Яркая страница для салона красоты и ухода: услуги, галерея, запись.',
    'full_page' => true,
    'thumb' => 'landing-templates/energen/preview.jpg', // scripts/landing-thumbs.mjs makes it
    'purposes' => ['booking'],
    'source' => 'https://themewagon.com/themes/free-bootstrap-4-html5-beauty-salon-website-template-energen/',
    'license' => 'CC-BY-3.0',
    'attribution' => 'https://colorlib.com',
    'categories' => ['spa', 'cosmetology', 'brows', 'makeup'],
    'templates' => [
        'general' => $view,
        'promotion' => $view,
        'service' => $view,
        'seasonal' => $view,
        'consultation' => $view,
    ],
    'images' => [
        'hero_image_1' => 'landing-templates/energen/images/bg_1.jpg',
        'work_image_1' => 'landing-templates/energen/images/gallery-1.jpg',
        'work_image_2' => 'landing-templates/energen/images/gallery-2.jpg',
        'work_image_3' => 'landing-templates/energen/images/gallery-3.jpg',
        'extra_image_1' => 'landing-templates/energen/images/intro.jpg',
        'extra_image_2' => 'landing-templates/energen/images/bg_3.jpg',
    ],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text', 'faq_items_text',
        'hero_image_1', 'extra_image_1', 'extra_image_2', 'work_image_1', 'work_image_2', 'work_image_3',
    ],
];
