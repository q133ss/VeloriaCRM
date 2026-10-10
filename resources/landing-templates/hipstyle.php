<?php

/**
 * Imported by landing:import-template. See resources/landing-templates/salone.php for the manifest contract.
 */
$view = 'landings.full.hipstyle';

return [
    'slug' => 'hipstyle',
    'name' => 'Хипстер',
    'description' => 'Стильная страница для парикмахера и барбера: услуги, преимущества, запись.',
    'full_page' => true,
    'thumb' => 'landing-templates/hipstyle/preview.jpg', // scripts/landing-thumbs.mjs makes it
    'purposes' => ['booking', 'portfolio'],
    'source' => 'https://themewagon.com/themes/hipstyle-barber-free-html5-website-template/',
    'license' => 'CC-BY-3.0',
    'attribution' => 'https://colorlib.com',
    'categories' => ['hair', 'barber'],
    'templates' => [
        'general' => $view,
        'promotion' => $view,
        'service' => $view,
        'seasonal' => $view,
        'consultation' => $view,
    ],
    'images' => [
        'hero_image_1' => 'landing-templates/hipstyle/img/banner_bg.jpg',
        'extra_image_1' => 'landing-templates/hipstyle/img/about_us_1.png',
        'extra_image_2' => 'landing-templates/hipstyle/img/about_us_2.png',
        'extra_image_3' => 'landing-templates/hipstyle/img/about_us_3.png',
    ],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text',
        'hero_image_1', 'extra_image_1', 'extra_image_2', 'extra_image_3', 'faq_items_text',
    ],
];
