<?php

/**
 * Original Veloria layout. See resources/landing-templates/salone.php for the manifest contract.
 *
 * Built around what a master already has in her VK group: a price list by category and
 * photos of her work. No stock pictures reach visitors: a photo slot without her own photo
 * is simply not shown (the placeholder exists for the editor only).
 */
$view = 'landings.full.price-list';
$placeholder = 'landing-templates/price-list/placeholder.svg';

return [
    'slug' => 'price-list',
    'name' => 'Прайс и работы',
    'description' => 'Прайс по категориям, фото работ и запись. Без стоковых картинок: на странице только ваши фото.',
    'full_page' => true,
    'thumb' => 'landing-templates/price-list/preview.jpg',
    'purposes' => ['booking', 'portfolio'],
    'source' => 'https://veloria.app/templates/price-list',
    'license' => 'free-commercial',
    'attribution' => null,
    'categories' => ['nails', 'brows', 'hair', 'barber', 'spa', 'cosmetology', 'makeup'],
    'templates' => [
        'general' => $view,
        'promotion' => $view,
        'service' => $view,
        'seasonal' => $view,
        'consultation' => $view,
    ],
    'images' => [
        'master_photo' => $placeholder,
        'hero_image_1' => $placeholder,
        'work_image_1' => $placeholder,
        'work_image_2' => $placeholder,
        'work_image_3' => $placeholder,
        'work_image_4' => $placeholder,
        'work_image_5' => $placeholder,
        'work_image_6' => $placeholder,
        'extra_image_1' => $placeholder,
        'extra_image_2' => $placeholder,
        'extra_image_3' => $placeholder,
    ],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint', 'phone', 'address',
        'master_photo', 'hero_image_1',
        'work_image_1', 'work_image_2', 'work_image_3', 'work_image_4', 'work_image_5', 'work_image_6',
        'extra_image_1', 'extra_image_2', 'extra_image_3',
    ],
];
