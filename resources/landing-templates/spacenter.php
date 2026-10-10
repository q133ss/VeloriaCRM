<?php

/**
 * Imported by landing:import-template. See resources/landing-templates/salone.php for the manifest contract.
 */
$view = 'landings.full.spacenter';

return [
    'slug' => 'spacenter',
    'name' => 'Спа-центр',
    'description' => 'Спокойный шаблон в светлых тонах. Подойдёт спа, массажу и уходу.',
    'full_page' => true,
    'thumb' => 'landing-templates/spacenter/preview.jpg', // scripts/landing-thumbs.mjs makes it
    'purposes' => ['booking'],
    'source' => 'https://htmlcodex.com/spa-html-template/',
    'license' => 'CC-BY-4.0',
    'attribution' => 'https://htmlcodex.com',
    'categories' => ['spa', 'cosmetology', 'brows'],
    'templates' => [
        'general' => $view,
        'promotion' => $view,
        'service' => $view,
        'seasonal' => $view,
        'consultation' => $view,
    ],
    'images' => [
        'hero_image_1' => 'landing-templates/spacenter/img/carousel-1.jpg',
        'hero_image_2' => 'landing-templates/spacenter/img/carousel-2.jpg',
        'hero_image_3' => 'landing-templates/spacenter/img/carousel-3.jpg',
        'about_image' => 'landing-templates/spacenter/img/about.jpg',
        'master_photo' => 'landing-templates/spacenter/img/team-1.jpg',
        'extra_image_1' => 'landing-templates/spacenter/img/opening.jpg',
        'extra_image_2' => 'landing-templates/spacenter/img/pricing.jpg',
        'extra_image_3' => 'landing-templates/spacenter/img/carousel-1.jpg',
        'extra_image_4' => 'landing-templates/spacenter/img/carousel-2.jpg',
    ],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text', 'master_role',
        'hero_image_1', 'hero_image_2', 'hero_image_3', 'about_image', 'master_photo',
        'extra_image_1', 'extra_image_2',
        'extra_image_3', 'extra_image_4',
    ],
];
