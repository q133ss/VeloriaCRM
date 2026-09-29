<?php

/**
 * Imported by landing:import-template. See resources/landing-templates/salone.php for the manifest contract.
 */
$view = 'landings.full.haircare';

return [
    'slug' => 'haircare',
    'name' => 'Стильная стрижка',
    'description' => 'Светлая страница с фото-галереей и записью. Подойдёт парикмахерам.',
    'full_page' => true,
    'thumb' => 'landing-templates/haircare/preview.jpg', // scripts/landing-thumbs.mjs makes it
    'purposes' => ['booking', 'portfolio'],
    'source' => 'https://themewagon.com/themes/free-bootstrap-4-html5-hair-salon-website-template-haircare/',
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
        'hero_image_1' => 'landing-templates/haircare/images/bg-2.jpg',
        'master_photo' => 'landing-templates/haircare/images/stylist-1.jpg',
        'work_image_1' => 'landing-templates/haircare/images/work-1.jpg',
        'work_image_2' => 'landing-templates/haircare/images/work-2.jpg',
        'work_image_3' => 'landing-templates/haircare/images/work-3.jpg',
    ],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text', 'master_role',
        'hero_image_1', 'master_photo', 'work_image_1', 'work_image_2', 'work_image_3',
    ],
];
