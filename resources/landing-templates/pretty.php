<?php

/**
 * Pretty by Colorlib, distributed by ThemeWagon (CC BY 3.0). The link back to
 * Colorlib in the footer must stay. See resources/landing-templates/salone.php
 * for the manifest contract and docs/landing-templates.md.
 */
$view = 'landings.full.pretty';

return [
    'slug' => 'pretty',
    'name' => 'landings.wizard.style.layouts.pretty.title',
    'description' => 'landings.wizard.style.layouts.pretty.desc',
    'full_page' => true,
    'thumb' => 'landing-templates/pretty/preview.jpg',
    'templates' => [
        'general' => $view,
        'promotion' => $view,
        'service' => $view,
        'seasonal' => $view,
        'consultation' => $view,
    ],
    // Stock photos; the owner replaces them in the click editor.
    'images' => [
        'hero_image_1' => 'landing-templates/pretty/images/bg_1.jpg',
        'master_photo' => 'landing-templates/pretty/images/person_1.jpg',
        'work_image_1' => 'landing-templates/pretty/images/work-1.jpg',
        'work_image_2' => 'landing-templates/pretty/images/work-2.jpg',
        'work_image_3' => 'landing-templates/pretty/images/work-3.jpg',
        'extra_image_1' => 'landing-templates/pretty/images/bg_2.jpg',
        'extra_image_2' => 'landing-templates/pretty/images/bg_2.jpg',
    ],
    // What the template is good for; the wizard filters by these.
    'purposes' => ['booking', 'portfolio'],
    'source' => 'https://themewagon.com/themes/free-bootstrap-4-html5-beauty-salon-website-template-pretty/',
    'license' => 'CC-BY-3.0',
    'attribution' => 'https://colorlib.com',
    'categories' => ['nails', 'brows', 'makeup', 'cosmetology'],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text', 'faq_items_text', 'master_role', 'master_bio',
        'hero_image_1', 'master_photo', 'work_image_1', 'work_image_2', 'work_image_3',
        'extra_image_1', 'extra_image_2',
    ],
];
