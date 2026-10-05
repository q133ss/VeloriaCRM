<?php

/**
 * Imported by landing:import-template. See resources/landing-templates/salone.php for the manifest contract.
 */
$view = 'landings.full.photo-studio';

return [
    'slug' => 'photo-studio',
    'name' => 'Фотостудия-портфолио',
    'description' => 'Страница-витрина: крупные фото работ, услуги с ценами и запись. Для мастеров с сильным портфолио.',
    'full_page' => true,
    'thumb' => 'landing-templates/photo-studio/preview.jpg', // scripts/landing-thumbs.mjs makes it
    'purposes' => ['portfolio'],
    'source' => 'https://htmlcodex.com/photo-studio-website-template/',
    'license' => 'CC-BY-4.0',
    'attribution' => 'https://htmlcodex.com',
    'categories' => ['makeup', 'hair', 'brows'],
    'templates' => [
        'general' => $view,
        'promotion' => $view,
        'service' => $view,
        'seasonal' => $view,
        'consultation' => $view,
    ],
    'images' => [
        'hero_image_1' => 'landing-templates/photo-studio/img/hero-1.jpg',
        'hero_image_2' => 'landing-templates/photo-studio/img/hero-2.jpg',
        'hero_image_3' => 'landing-templates/photo-studio/img/hero-3.jpg',
        'work_image_1' => 'landing-templates/photo-studio/img/project-5.jpg',
        'work_image_2' => 'landing-templates/photo-studio/img/project-1.jpg',
        'work_image_3' => 'landing-templates/photo-studio/img/project-2.jpg',
        'work_image_4' => 'landing-templates/photo-studio/img/project-3.jpg',
        'work_image_5' => 'landing-templates/photo-studio/img/project-4.jpg',
        'work_image_6' => 'landing-templates/photo-studio/img/project-6.jpg',
    ],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'master_role',
        'hero_image_1', 'hero_image_2', 'hero_image_3',
        'work_image_1', 'work_image_2', 'work_image_3', 'work_image_4', 'work_image_5', 'work_image_6',
    ],
];
