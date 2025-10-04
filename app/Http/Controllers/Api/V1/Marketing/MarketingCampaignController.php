<?php

namespace App\Http\Controllers\Api\V1\Marketing;

use App\Http\Requests\CampaignFilterRequest;
use App\Http\Requests\CampaignFormRequest;
use App\Http\Requests\CampaignLaunchRequest;
use App\Http\Requests\CampaignWinnerRequest;
use App\Models\MarketingCampaign;
use App\Models\MarketingCampaignVariant;
use App\Services\Marketing\MarketingCampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MarketingCampaignController extends MarketingController
{
    public function __construct(private readonly MarketingCampaignService $campaignService)
    {
    }

    public function index(CampaignFilterRequest $request): JsonResponse
    {
        $userId = $this->currentUserId();
        $filters = $request->validated();

        $query = MarketingCampaign::with(['variants', 'winningVariant'])
            ->forUser($userId)
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (! empty($filters['segment'])) {
            $query->where('segment', $filters['segment']);
        }

        $campaigns = $query->get();

        $statusCounts = MarketingCampaign::forUser($userId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $channelCounts = MarketingCampaign::forUser($userId)
            ->selectRaw('channel, COUNT(*) as aggregate')
            ->groupBy('channel')
            ->pluck('aggregate', 'channel')
            ->all();

        $totals = [
            'total_campaigns' => $campaigns->count(),
            'total_delivered' => (int) $campaigns->sum('delivered_count'),
            'total_reads' => (int) $campaigns->sum('read_count'),
            'total_clicks' => (int) $campaigns->sum('click_count'),
        ];

        return response()->json([
            'data' => $campaigns->map(fn (MarketingCampaign $campaign) => $this->transformCampaign($campaign))->all(),
            'meta' => [
                'filters' => $filters,
                'status_counts' => $statusCounts,
                'channel_counts' => $channelCounts,
                'totals' => $totals,
                'suggestions' => $this->buildCampaignSuggestions($campaigns),
            ],
        ]);
    }

    public function store(CampaignFormRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->currentUserId();

        $variantsInput = $request->input('variants', null);
        $variants = collect(is_array($variantsInput) ? $variantsInput : []);
        $content = $validated['content'] ?? $variants->first()['content'] ?? '';
        $subject = $validated['subject'] ?? $variants->first()['subject'] ?? null;

        $campaign = MarketingCampaign::create([
            'user_id' => $userId,
            'template_id' => $validated['template_id'] ?? null,
            'name' => $validated['name'],
            'channel' => $validated['channel'],
            'segment' => $validated['segment'],
            'segment_filters' => Arr::get($validated, 'segment_filters'),
            'is_ab_test' => (bool) ($validated['is_ab_test'] ?? false),
            'status' => $validated['status'] ?? 'draft',
            'scheduled_at' => Arr::get($validated, 'scheduled_at') ? Carbon::parse($validated['scheduled_at']) : null,
            'subject' => $subject,
            'content' => $content,
            'test_group_size' => $validated['test_group_size'] ?? null,
            'metadata' => Arr::get($validated, 'metadata'),
        ]);

        $this->syncVariants($campaign, $variants, $variantsInput !== null);

        $campaign->load(['variants', 'winningVariant']);

        return response()->json([
            'data' => $this->transformCampaign($campaign),
            'message' => __('marketing.campaigns.created'),
        ], 201);
    }

    public function show(MarketingCampaign $campaign): JsonResponse
    {
        $this->ensureCampaignBelongsToUser($campaign);
        $campaign->load(['variants', 'winningVariant']);

        return response()->json([
            'data' => $this->transformCampaign($campaign),
            'meta' => [
                'options' => $this->optionsPayload(),
            ],
        ]);
    }

    public function update(CampaignFormRequest $request, MarketingCampaign $campaign): JsonResponse
    {
        $this->ensureCampaignBelongsToUser($campaign);
        $validated = $request->validated();

        $variantsInput = $request->input('variants', null);
        $variants = collect(is_array($variantsInput) ? $variantsInput : []);
        $content = $validated['content'] ?? $variants->first()['content'] ?? $campaign->content;
        $subject = $validated['subject'] ?? $variants->first()['subject'] ?? $campaign->subject;

        $campaign->update([
            'template_id' => $validated['template_id'] ?? $campaign->template_id,
            'name' => $validated['name'] ?? $campaign->name,
            'channel' => $validated['channel'] ?? $campaign->channel,
            'segment' => $validated['segment'] ?? $campaign->segment,
            'segment_filters' => Arr::get($validated, 'segment_filters', $campaign->segment_filters),
            'is_ab_test' => (bool) ($validated['is_ab_test'] ?? $campaign->is_ab_test),
            'status' => $validated['status'] ?? $campaign->status,
            'scheduled_at' => Arr::get($validated, 'scheduled_at') ? Carbon::parse($validated['scheduled_at']) : $campaign->scheduled_at,
            'subject' => $subject,
            'content' => $content,
            'test_group_size' => $validated['test_group_size'] ?? $campaign->test_group_size,
            'metadata' => Arr::get($validated, 'metadata', $campaign->metadata),
        ]);

        $this->syncVariants($campaign, $variants, $variantsInput !== null);

        $campaign->load(['variants', 'winningVariant']);

        return response()->json([
            'data' => $this->transformCampaign($campaign),
            'message' => __('marketing.campaigns.updated'),
        ]);
    }

    public function destroy(MarketingCampaign $campaign): JsonResponse
    {
        $this->ensureCampaignBelongsToUser($campaign);
        $campaign->delete();

        return response()->json([
            'message' => __('marketing.campaigns.deleted'),
        ]);
    }

    public function launch(CampaignLaunchRequest $request, MarketingCampaign $campaign): JsonResponse
    {
        $this->ensureCampaignBelongsToUser($campaign);
        $validated = $request->validated();

        $mode = $validated['mode'] ?? 'immediate';
        $scheduledAt = Arr::get($validated, 'scheduled_at');

        $campaign->loadMissing(['variants', 'winningVariant']);

        if ($mode === 'schedule') {
            $scheduledMoment = $scheduledAt ? Carbon::parse($scheduledAt) : Carbon::now();

            if (isset($validated['test_group_size'])) {
                $campaign->test_group_size = (int) $validated['test_group_size'];
            }

            $campaign->status = 'scheduled';
            $campaign->scheduled_at = $scheduledMoment;
            $campaign->save();

            $campaign->refresh()->load(['variants', 'winningVariant']);

            return response()->json([
                'data' => $this->transformCampaign($campaign),
                'message' => __('marketing.campaigns.launched'),
            ]);
        }

        if ($mode === 'test' && empty($validated['test_group_size'] ?? $campaign->test_group_size)) {
            return response()->json([
                'error' => [
                    'code' => 'test_group_missing',
                    'message' => __('marketing.campaigns.test_group_missing'),
                ],
            ], 422);
        }

        if ($campaign->is_ab_test && $mode === 'immediate' && ! $campaign->winning_variant_id) {
            return response()->json([
                'error' => [
                    'code' => 'ab_test_requires_winner',
                    'message' => __('marketing.campaigns.ab_test_requires_winner'),
                ],
            ], 422);
        }

        try {
            $settings = $this->campaignService->ensureChannelConfigured($campaign, $this->resolveUserSettings());
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? __('marketing.campaigns.channel_not_configured', ['channel' => strtoupper($campaign->channel)]);

            return response()->json([
                'error' => [
                    'code' => 'channel_not_configured',
                    'message' => $message,
                ],
            ], 422);
        }

        if (isset($validated['test_group_size'])) {
            $campaign->test_group_size = (int) $validated['test_group_size'];
            $campaign->save();
        }

        $recipients = $this->campaignService->resolveRecipients($campaign);
        $reachable = $this->campaignService->filterReachableRecipients($recipients, $campaign->channel);

        if ($reachable->isEmpty()) {
            return response()->json([
                'error' => [
                    'code' => 'no_recipients',
                    'message' => __('marketing.campaigns.no_recipients'),
                ],
            ], 422);
        }

        $dispatchResult = $this->campaignService->dispatchCampaign($campaign, $reachable, $mode, $validated, $settings);

        $campaign->refresh()->load(['variants', 'winningVariant']);

        return response()->json([
            'data' => $this->transformCampaign($campaign),
            'meta' => [
                'dispatched' => $dispatchResult,
            ],
            'message' => __('marketing.campaigns.launched'),
        ]);
    }

    public function selectWinner(CampaignWinnerRequest $request, MarketingCampaign $campaign): JsonResponse
    {
        $this->ensureCampaignBelongsToUser($campaign);
        $validated = $request->validated();

        /** @var MarketingCampaignVariant|null $variant */
        $variant = $campaign->variants()->where('id', $validated['variant_id'])->first();

        if (! $variant) {
            return response()->json([
                'error' => [
                    'code' => 'variant_not_found',
                    'message' => __('marketing.campaigns.variant_not_found'),
                ],
            ], 404);
        }

        $campaign->update([
            'winning_variant_id' => $variant->id,
            'status' => 'completed',
        ]);

        $campaign->load(['variants', 'winningVariant']);

        return response()->json([
            'data' => $this->transformCampaign($campaign),
            'message' => __('marketing.campaigns.winner_selected'),
        ]);
    }

    public function options(): JsonResponse
    {
        return response()->json([
            'data' => $this->optionsPayload(),
        ]);
    }

    protected function optionsPayload(): array
    {
        $userId = $this->currentUserId();
        $settings = $this->resolveUserSettings();
        $segmentsData = $this->campaignService->resolveSegmentsForUser($userId);

        return [
            'channels' => $this->campaignService->availableChannels($settings),
            'segments' => $segmentsData['segments'],
            'templates' => $this->campaignService->resolveTemplatesForUser($userId),
            'clients' => $segmentsData['clients'],
            'ab_test_tip' => __('marketing.campaigns.ab_test_tip'),
        ];
    }

    protected function transformCampaign(MarketingCampaign $campaign): array
    {
        $campaign->loadMissing(['variants', 'winningVariant']);

        $variants = $campaign->variants->map(function (MarketingCampaignVariant $variant) {
            $totalDelivered = max(1, $variant->delivered_count);

            return [
                'id' => $variant->id,
                'label' => $variant->label,
                'subject' => $variant->subject,
                'content' => $variant->content,
                'sample_size' => $variant->sample_size,
                'delivered_count' => $variant->delivered_count,
                'read_count' => $variant->read_count,
                'click_count' => $variant->click_count,
                'status' => $variant->status,
                'open_rate' => $variant->delivered_count > 0 ? round($variant->read_count / $totalDelivered, 2) : 0,
                'ctr' => $variant->delivered_count > 0 ? round($variant->click_count / $totalDelivered, 2) : 0,
            ];
        })->values()->all();

        $totalDelivered = max(1, $campaign->delivered_count);

        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'channel' => $campaign->channel,
            'segment' => $campaign->segment,
            'segment_filters' => $campaign->segment_filters,
            'is_ab_test' => $campaign->is_ab_test,
            'status' => $campaign->status,
            'scheduled_at' => optional($campaign->scheduled_at)->toIso8601String(),
            'subject' => $campaign->subject,
            'content' => $campaign->content,
            'test_group_size' => $campaign->test_group_size,
            'metrics' => [
                'delivered' => (int) $campaign->delivered_count,
                'read' => (int) $campaign->read_count,
                'clicks' => (int) $campaign->click_count,
                'open_rate' => $campaign->delivered_count > 0 ? round($campaign->read_count / $totalDelivered, 2) : 0,
                'ctr' => $campaign->delivered_count > 0 ? round($campaign->click_count / $totalDelivered, 2) : 0,
            ],
            'winning_variant_id' => $campaign->winning_variant_id,
            'variants' => $variants,
            'created_at' => optional($campaign->created_at)->toIso8601String(),
            'updated_at' => optional($campaign->updated_at)->toIso8601String(),
        ];
    }

    protected function syncVariants(MarketingCampaign $campaign, Collection $variants, bool $forceClear = false): void
    {
        $campaign->loadMissing('variants');

        if ($variants->isEmpty()) {
            if ($forceClear) {
                $campaign->variants()->delete();
            }

            return;
        }

        $existing = $campaign->variants->keyBy('label');

        $variants->each(function (array $variant) use ($campaign, $existing) {
            $label = Arr::get($variant, 'label');

            if (! $label) {
                return;
            }

            /** @var MarketingCampaignVariant|null $model */
            $model = $existing->get($label);

            if ($model) {
                $model->update([
                    'subject' => Arr::get($variant, 'subject', $model->subject),
                    'content' => Arr::get($variant, 'content', $model->content),
                    'sample_size' => Arr::get($variant, 'sample_size', $model->sample_size),
                ]);
            } else {
                $campaign->variants()->create([
                    'label' => $label,
                    'subject' => Arr::get($variant, 'subject'),
                    'content' => Arr::get($variant, 'content', ''),
                    'sample_size' => Arr::get($variant, 'sample_size'),
                ]);
            }
        });

        $labels = $variants->pluck('label')->filter()->all();
        $campaign->variants()->whereNotIn('label', $labels)->delete();
    }

    protected function ensureCampaignBelongsToUser(MarketingCampaign $campaign): void
    {
        if ($campaign->user_id !== $this->currentUserId()) {
            abort(403);
        }
    }

    protected function buildCampaignSuggestions(EloquentCollection $campaigns): array
    {
        if ($campaigns->isEmpty()) {
            return [
                [
                    'title' => __('marketing.suggestions.start_title'),
                    'description' => __('marketing.suggestions.start_description'),
                ],
            ];
        }

        $pending = $campaigns->whereIn('status', ['draft', 'scheduled']);
        $testing = $campaigns->where('status', 'testing');

        $suggestions = [];

        if ($pending->isNotEmpty()) {
            $suggestions[] = [
                'title' => __('marketing.suggestions.launch_title'),
                'description' => __('marketing.suggestions.launch_description', ['count' => $pending->count()]),
            ];
        }

        if ($testing->isNotEmpty()) {
            $suggestions[] = [
                'title' => __('marketing.suggestions.test_title'),
                'description' => __('marketing.suggestions.test_description'),
            ];
        }

        if ($suggestions === []) {
            $suggestions[] = [
                'title' => __('marketing.suggestions.iterate_title'),
                'description' => __('marketing.suggestions.iterate_description'),
            ];
        }

        return $suggestions;
    }

}
