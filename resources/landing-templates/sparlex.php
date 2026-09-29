<?php

/**
 * Imported by landing:import-template. See resources/landing-templates/salone.php for the manifest contract.
 */
$view = 'landings.full.sparlex';

return [
    'slug' => 'sparlex',
    'name' => 'Красота и спа',
    'description' => 'Лёгкая страница для салона красоты: услуги, команда, запись.',
    'full_page' => true,
    'thumb' => 'landing-templates/sparlex/preview.jpg', // scripts/landing-thumbs.mjs makes it
    'purposes' => ['booking'],
    'source' => 'https://htmlcodex.com/spa-website-template/',
    'license' => 'CC-BY-4.0',
    'attribution' => 'https://htmlcodex.com',
    'categories' => ['spa', 'makeup', 'nails', 'brows'],
    'templates' => [
        'general' => $view,
        'promotion' => $view,
        'service' => $view,
        'seasonal' => $view,
        'consultation' => $view,
    ],
    'images' => [
        'hero_image_1' => 'landing-templates/sparlex/img/carousel-3.jpg',
        'hero_image_2' => 'landing-templates/sparlex/img/carousel-2.jpg',
        'hero_image_3' => 'landing-templates/sparlex/img/carousel-1.jpg',
        'about_image' => 'landing-templates/sparlex/img/about-1.jpg',
        'master_photo' => 'landing-templates/sparlex/img/team-1.png',
        'work_image_1' => 'landing-templates/sparlex/img/gallery-1.jpg',
        'work_image_2' => 'landing-templates/sparlex/img/gallery-2.jpg',
        'work_image_3' => 'landing-templates/sparlex/img/gallery-3.jpg',
    ],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text', 'master_role',
        'hero_image_1', 'hero_image_2', 'hero_image_3', 'about_image', 'master_photo',
        'work_image_1', 'work_image_2', 'work_image_3',
    ],
];
