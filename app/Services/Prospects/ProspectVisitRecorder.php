<?php

namespace App\Services\Prospects;

use App\Models\Prospect;
use App\Models\ProspectVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Remembers who opened the link we sent a master. Link previews (VK, Telegram)
 * and crawlers open it too, so they are stored apart from real people.
 */
class ProspectVisitRecorder
{
    private const BOT_PATTERN = '/bot|crawl|spider|preview|vkshare|facebookexternalhit|telegram|whatsapp|slack|curl|wget|python|go-http|headless|monitor/i';

    public function record(Prospect $prospect, string $kind, Request $request): void
    {
        $agent = (string) $request->userAgent();
        $referer = (string) $request->headers->get('referer', '');

        // Our own pages (the master moving from the claim link to her cabinet) are not a source.
        if ($referer !== '' && parse_url($referer, PHP_URL_HOST) === $request->getHost()) {
            $referer = '';
        }

        ProspectVisit::query()->create([
            'prospect_id' => $prospect->id,
            'kind' => $kind,
            'visitor' => sha1($request->ip() . '|' . $agent . '|' . config('app.key')),
            'is_bot' => $agent === '' || preg_match(self::BOT_PATTERN, $agent) === 1,
            'referer' => $referer !== '' ? Str::limit($referer, 250, '') : null,
            'user_agent' => $agent !== '' ? Str::limit($agent, 250, '') : null,
        ]);
    }

    /**
     * Numbers for the backoffice list, for all listed prospects in two queries.
     *
     * @param  array<int>  $prospectIds
     * @return array<int, array>  prospect id => ['claim' => [...], 'landing' => [...], 'bot_hits' => n]
     */
    public function summaries(array $prospectIds): array
    {
        $empty = ['views' => 0, 'visitors' => 0, 'first_at' => null, 'last_at' => null];
        $out = [];

        foreach ($prospectIds as $id) {
            $out[$id] = ['claim' => $empty, 'landing' => $empty, 'bot_hits' => 0];
        }

        if ($prospectIds === []) {
            return $out;
        }

        $rows = ProspectVisit::query()
            ->whereIn('prospect_id', $prospectIds)
            ->selectRaw('prospect_id, kind, is_bot, count(*) as views, count(distinct visitor) as visitors, min(created_at) as first_at, max(created_at) as last_at')
            ->groupBy('prospect_id', 'kind', 'is_bot')
            ->get();

        foreach ($rows as $row) {
            if ($row->is_bot) {
                $out[$row->prospect_id]['bot_hits'] += (int) $row->views;
                continue;
            }

            $out[$row->prospect_id][$row->kind] = [
                'views' => (int) $row->views,
                'visitors' => (int) $row->visitors,
                'first_at' => Carbon::parse($row->first_at)->toIso8601String(),
                'last_at' => Carbon::parse($row->last_at)->toIso8601String(),
            ];
        }

        return $out;
    }
}
