<?php

/**
 * Imported by landing:import-template. See resources/landing-templates/salone.php for the manifest contract.
 */
$view = 'landings.full.one-service';

return [
    'slug' => 'one-service',
    'name' => 'Одна услуга',
    'description' => 'Страница одной главной услуги: цена, преимущества, скидка с таймером и запись.',
    'full_page' => true,
    'thumb' => 'landing-templates/one-service/preview.jpg', // scripts/landing-thumbs.mjs makes it
    'purposes' => ['single'],
    'source' => 'https://htmlcodex.com/single-product-website-template/',
    'license' => 'CC-BY-4.0',
    'attribution' => 'https://htmlcodex.com',
    'categories' => ['hair', 'barber', 'cosmetology'],
    'templates' => [
        'general' => $view,
        'promotion' => $view,
        'service' => $view,
        'seasonal' => $view,
        'consultation' => $view,
    ],
    'images' => [
        'hero_image_1' => 'landing-templates/one-service/img/shampoo.png',
        'about_image' => 'landing-templates/one-service/img/shampoo-1.png',
    ],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text', 'master_bio',
        'hero_image_1', 'about_image',
    ],
];
