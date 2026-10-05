<?php

/**
 * Imported by landing:import-template. See resources/landing-templates/salone.php for the manifest contract.
 */
$view = 'landings.full.wellness';

return [
    'slug' => 'wellness',
    'name' => 'Велнес',
    'description' => 'Мягкая страница для спа и ухода: услуги, тарифы из прайса, скидка и запись.',
    'full_page' => true,
    'thumb' => 'landing-templates/wellness/preview.jpg', // scripts/landing-thumbs.mjs makes it
    'purposes' => ['booking'],
    'source' => 'https://htmlcodex.com/free-yoga-website-template/',
    'license' => 'CC-BY-4.0',
    'attribution' => 'https://htmlcodex.com',
    'categories' => ['spa', 'cosmetology'],
    'templates' => [
        'general' => $view,
        'promotion' => $view,
        'service' => $view,
        'seasonal' => $view,
        'consultation' => $view,
    ],
    'images' => [
        'hero_image_1' => 'landing-templates/wellness/img/hero.png',
        'about_image' => 'landing-templates/wellness/img/about.png',
    ],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text', 'master_bio',
        'hero_image_1', 'about_image',
    ],
];
