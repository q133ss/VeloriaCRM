<?php

namespace App\Services\Landing;

use App\Models\Landing;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Everything a full-page template needs from a landing, prepared once.
 *
 * A template starts with
 *
 *     @php(extract(app(\App\Services\Landing\PageData::class)->for($landing, $featuredServices)))
 *
 * and then simply uses $phone, $address, $cards, $hours … so the 30th template
 * does not repeat what the first one does (and a fix lands in one place).
 *
 * Keys: settings, content, editing, phone, phoneHref, address, telegram, whatsapp,
 * heroText, proofItems, faqItems, hours, stats, showCounters, services, cards,
 * priced, hasOffer, offerPercent, promoCode, endsAt, ctaDefault, hasInfo.
 */
class PageData
{
    public function __construct(
        private readonly LandingContent $content,
        private readonly WorkingHours $workingHours,
        private readonly PageStats $pageStats,
    ) {
    }

    /**
     * @param  Collection<int, object>  $featuredServices  services the page offers (id, name, base_price, duration_min)
     * @return array<string, mixed>
     */
    public function for(Landing $landing, Collection $featuredServices): array
    {
        $settings = $landing->settings ?? [];
        $editing = $this->content->editing();

        $phone = trim((string) ($settings['phone'] ?? ''));
        $address = trim((string) ($settings['address'] ?? ''));

        $services = $featuredServices->values();
        $cards = $services->isNotEmpty()
            ? $services->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'price' => $s->base_price, 'duration' => $s->duration_min])
            : collect($settings['service_names'] ?? [])->map(fn ($n) => ['id' => null, 'name' => $n, 'price' => null, 'duration' => null]);

        $hours = $landing->user_id !== null ? $this->workingHours->forUser((int) $landing->user_id) : [];
        $stats = $this->pageStats->forUser((int) ($landing->user_id ?? 0));

        $hasOffer = $landing->type === 'promotion' && ! empty($settings['discount_percent']);

        return [
            'settings' => $settings,
            'content' => $this->content,
            'editing' => $editing,

            'phone' => $phone,
            'phoneHref' => $phone !== '' ? 'tel:' . preg_replace('/[^0-9+]+/', '', $phone) : null,
            'address' => $address,
            'telegram' => $settings['telegram_url'] ?? null,
            'whatsapp' => $settings['whatsapp_url'] ?? null,

            'heroText' => $this->content->text('hero_text'),
            'proofItems' => collect($this->content->items('proof_items_text')),
            'faqItems' => collect($this->content->items('faq_items_text')),

            'hours' => $hours,
            'stats' => $stats,
            'showCounters' => $this->pageStats->worthShowing($stats),

            'services' => $services,
            'cards' => $cards,
            'priced' => $cards->filter(fn ($c) => ! empty($c['price']))->take(4)->values(),

            'hasOffer' => $hasOffer,
            'offerPercent' => $hasOffer ? rtrim(rtrim(number_format((float) $settings['discount_percent'], 1, '.', ''), '0'), '.') : null,
            'promoCode' => ! empty($settings['promo_code']) ? strtoupper((string) $settings['promo_code']) : null,
            'endsAt' => ! empty($settings['ends_at']) ? Carbon::parse($settings['ends_at'])->format('d.m.Y') : null,

            'ctaDefault' => __('landings.salone.book'),
            'hasInfo' => $address !== '' || $phone !== '' || $hours !== [] || $editing,
        ];
    }
}
