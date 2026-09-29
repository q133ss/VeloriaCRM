<?php

/**
 * Salone by HTML Codex (CC BY 4.0, the credit link in the footer must stay).
 * See resources/landing-templates/classic.php for the manifest contract.
 */
$view = 'landings.full.salone';

return [
    'slug' => 'salone',
    'name' => 'landings.wizard.style.layouts.salone.title',
    'description' => 'landings.wizard.style.layouts.salone.desc',
    'full_page' => true,
    'thumb' => 'landing-templates/salone/preview.jpg',
    'templates' => [
        'general' => $view,
        'promotion' => $view,
        'service' => $view,
        'seasonal' => $view,
        'consultation' => $view,
    ],
    'images' => [
        'hero_image_1' => 'landing-templates/salone/img/hero-slider-1.jpg',
        'hero_image_2' => 'landing-templates/salone/img/hero-slider-2.jpg',
        'hero_image_3' => 'landing-templates/salone/img/hero-slider-3.jpg',
        'about_image' => 'landing-templates/salone/img/about.jpg',
    ],
    // What the template is good for; the wizard filters by these.
    'categories' => ['hair', 'barber', 'spa', 'cosmetology'],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text',
        'hero_image_1', 'hero_image_2', 'hero_image_3', 'about_image',
    ],
];
