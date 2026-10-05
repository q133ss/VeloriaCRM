<?php

/**
 * Imported by landing:import-template. See resources/landing-templates/salone.php for the manifest contract.
 */
$view = 'landings.full.master-card';

return [
    'slug' => 'master-card',
    'name' => 'Визитка мастера',
    'description' => 'Личная страница мастера: фото, обо мне, услуги, работы и запись.',
    'full_page' => true,
    'thumb' => 'landing-templates/master-card/preview.jpg', // scripts/landing-thumbs.mjs makes it
    'purposes' => ['card'],
    'source' => 'https://htmlcodex.com/personal-portfolio-html-template/',
    'license' => 'CC-BY-4.0',
    'attribution' => 'https://htmlcodex.com',
    'categories' => ['hair', 'makeup', 'brows', 'nails', 'cosmetology'],
    'templates' => [
        'general' => $view,
        'promotion' => $view,
        'service' => $view,
        'seasonal' => $view,
        'consultation' => $view,
    ],
    'images' => [
        'master_photo' => 'landing-templates/master-card/img/profile.png',
        'work_image_1' => 'landing-templates/master-card/img/project-1.jpg',
        'work_image_2' => 'landing-templates/master-card/img/project-2.jpg',
        'work_image_3' => 'landing-templates/master-card/img/project-3.jpg',
    ],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text', 'master_role', 'master_bio',
        'master_photo', 'work_image_1', 'work_image_2', 'work_image_3',
    ],
];
