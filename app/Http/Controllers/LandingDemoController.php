<?php

namespace App\Http\Controllers;

use App\Models\Landing;
use App\Services\Landing\LandingContent;
use App\Services\Landing\TemplateRegistry;
use Illuminate\Contracts\View\View;

/**
 * A template shown with sample data, so a master can look at it before choosing.
 * Nothing is stored: the landing is never saved and the booking widget is off.
 */
class LandingDemoController extends Controller
{
    public function __invoke(TemplateRegistry $registry, string $layout): View
    {
        $manifest = $registry->layout($layout);

        abort_unless($manifest, 404);

        $template = $manifest['templates']['general'];
        $sample = (array) __('landings.demo');

        // A promo layout is shown the way it is used: a discount, a code and a deadline.
        $promo = in_array('promo', $manifest['purposes'], true);

        $landing = new Landing([
            'title' => $sample['title'],
            'type' => $promo ? 'promotion' : 'general',
            'landing' => $template,
            'slug' => 'demo-' . $layout,
            'settings' => [
                'phone' => $sample['phone'],
                'address' => $sample['address'],
                'greeting' => $sample['note'],
                'cta_label' => __('landings.wizard.copy.cta'),
                'booking_hint' => __('landings.wizard.copy.booking_hint'),
                'proof_items_text' => __('landings.wizard.copy.proof'),
                'faq_items_text' => __('landings.wizard.copy.faq'),
            ] + ($promo ? [
                'discount_percent' => 20,
                'promo_code' => 'BEAUTY20',
                'ends_at' => now()->addDays(7)->toDateString(),
            ] : []),
            'is_active' => true,
        ]);

        // No owner: the views ask for her schedule and figures, and get empty answers.
        $landing->user_id = 0;

        app(LandingContent::class)->bind($landing, false);

        $services = collect($sample['services'])->values()->map(fn (array $row, int $i) => (object) [
            'id' => $i + 1,
            'name' => $row[0],
            'base_price' => $row[1],
            'duration_min' => $row[2],
        ]);

        return view($template, [
            'landing' => $landing,
            'template' => $template,
            'isPreview' => false,
            'isEdit' => false,
            'isDemo' => true,
            'featuredServices' => $services,
        ]);
    }
}
