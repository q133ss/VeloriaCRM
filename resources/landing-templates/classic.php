<?php

/**
 * Manifest of a landing layout. One file per layout is the whole contract:
 * the registry, the wizard, the validation and the click editor all read it.
 *
 * templates: view used for each landing type. Full-page layouts point every
 *            type at the same view, the classic one has a view per type.
 * fields:    content keys the layout exposes to the click editor
 *            (kinds and limits live in App\Services\Landing\LandingContent).
 */
return [
    'slug' => 'classic',
    'name' => 'landings.wizard.style.layouts.classic.title',
    'description' => 'landings.wizard.style.layouts.classic.desc',
    'full_page' => false,
    'thumb' => null,
    'templates' => [
        'general' => 'landings.templates.general',
        'promotion' => 'landings.templates.promotion',
        'service' => 'landings.templates.service',
        'seasonal' => 'landings.templates.seasonal',
        'consultation' => 'landings.templates.consultation',
    ],
    // The classic page shows the general-type greeting in its own section, so the
    // hero text of a general page is the subtitle.
    'keys' => [
        'hero_text' => ['general' => 'subtitle'],
    ],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'secondary_cta_label', 'booking_hint',
        'proof_items_text', 'faq_items_text', 'benefit_items_text', 'season_label', 'lead_magnet',
    ],
];
