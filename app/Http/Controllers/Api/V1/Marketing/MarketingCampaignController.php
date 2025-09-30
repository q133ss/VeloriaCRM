<?php

namespace App\Http\Controllers\Api\V1\Marketing;

use App\Http\Requests\CampaignFilterRequest;
use App\Http\Requests\CampaignFormRequest;
use App\Http\Requests\CampaignLaunchRequest;
use App\Http\Requests\CampaignWinnerRequest;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\MarketingCampaign;
use App\Models\MarketingCampaignVariant;
use App\Models\MarketingDelivery;
use App\Models\MessageTemplate;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MarketingCampaignController extends MarketingController
{
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

        if ($errorResponse = $this->validateChannelConfiguration($campaign)) {
            return $errorResponse;
        }

        if (isset($validated['test_group_size'])) {
            $campaign->test_group_size = (int) $validated['test_group_size'];
            $campaign->save();
        }

        $recipients = $this->resolveRecipients($campaign);
        $reachable = $this->filterReachableRecipients($recipients, $campaign->channel);

        if ($reachable->isEmpty()) {
            return response()->json([
                'error' => [
                    'code' => 'no_recipients',
                    'message' => __('marketing.campaigns.no_recipients'),
                ],
            ], 422);
        }

        $dispatchResult = $this->dispatchCampaign($campaign, $reachable, $mode, $validated);

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
        $clients = Client::where('user_id', $userId)->get();

        $loyaltySegments = ['gold', 'platinum', 'vip', 'ambassador'];
        $newCount = $clients->filter(fn ($client) => $client->last_visit_at === null)->count();
        $loyalCount = $clients->filter(fn ($client) => in_array($client->loyalty_level, $loyaltySegments, true))->count();
        $sleepingCount = $clients->filter(fn ($client) => $this->isSleeping($client->last_visit_at))->count();

        $segments = [
            ['value' => 'all', 'label' => __('marketing.segments.all'), 'count' => $clients->count()],
            ['value' => 'new', 'label' => __('marketing.segments.new'), 'count' => $newCount],
            ['value' => 'loyal', 'label' => __('marketing.segments.loyal'), 'count' => $loyalCount],
            ['value' => 'sleeping', 'label' => __('marketing.segments.sleeping'), 'count' => $sleepingCount],
            ['value' => 'by_service', 'label' => __('marketing.segments.by_service')],
            ['value' => 'by_master', 'label' => __('marketing.segments.by_master')],
            ['value' => 'custom', 'label' => __('marketing.segments.custom')],
        ];

        $templates = MessageTemplate::where(function ($query) use ($userId) {
            $query->whereNull('user_id')
                ->orWhere('user_id', $userId);
        })
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (MessageTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'channel' => $template->channel,
                'content' => $template->content,
            ])->values()->all();

        return [
            'channels' => [
                ['value' => 'sms', 'label' => 'SMS'],
                ['value' => 'email', 'label' => 'Email'],
                ['value' => 'telegram', 'label' => 'Telegram'],
                ['value' => 'whatsapp', 'label' => 'WhatsApp'],
            ],
            'segments' => $segments,
            'templates' => $templates,
            'ab_test_tip' => __('marketing.campaigns.ab_test_tip'),
        ];
    }

    protected function validateChannelConfiguration(MarketingCampaign $campaign): ?JsonResponse
    {
        $settings = $this->resolveUserSettings();
        $channelLabels = [
            'sms' => 'SMS',
            'email' => 'Email',
            'telegram' => 'Telegram',
            'whatsapp' => 'WhatsApp',
        ];

        if (! $settings) {
            return response()->json([
                'error' => [
                    'code' => 'channel_not_configured',
                    'message' => __('marketing.campaigns.channel_not_configured', ['channel' => $channelLabels[$campaign->channel] ?? strtoupper($campaign->channel)]),
                ],
            ], 422);
        }

        $missing = false;

        $smtpMissing = empty($settings->smtp_host)
            || empty($settings->smtp_port)
            || empty($settings->smtp_username)
            || empty($settings->smtp_password)
            || empty($settings->smtp_from_address);

        switch ($campaign->channel) {
            case 'sms':
                $missing = empty($settings->smsaero_email)
                    || empty($settings->smsaero_api_key)
                    || $smtpMissing;
                break;
            case 'email':
                $missing = $smtpMissing;
                break;
            case 'telegram':
                $missing = empty($settings->telegram_bot_token);
                break;
            case 'whatsapp':
                $missing = empty($settings->whatsapp_api_key) || empty($settings->whatsapp_sender);
                break;
        }

        if ($missing) {
            $channelName = $channelLabels[$campaign->channel] ?? strtoupper($campaign->channel);

            return response()->json([
                'error' => [
                    'code' => 'channel_not_configured',
                    'message' => __('marketing.campaigns.channel_not_configured', ['channel' => $channelName]),
                ],
            ], 422);
        }

        return null;
    }

    protected function resolveRecipients(MarketingCampaign $campaign): Collection
    {
        $query = Client::where('user_id', $campaign->user_id);
        $filters = $campaign->segment_filters ?? [];
        $now = Carbon::now();

        switch ($campaign->segment) {
            case 'new':
                $query->where(function ($builder) use ($now) {
                    $builder
                        ->whereNull('last_visit_at')
                        ->orWhere('created_at', '>=', $now->copy()->subDays(30));
                });
                break;
            case 'loyal':
                $query->whereIn('loyalty_level', ['gold', 'platinum', 'vip', 'ambassador']);
                break;
            case 'sleeping':
                $threshold = $now->copy()->subMonths(3);
                $query->where(function ($builder) use ($threshold) {
                    $builder
                        ->whereNull('last_visit_at')
                        ->orWhere('last_visit_at', '<', $threshold);
                });
                break;
            case 'by_service':
                $serviceIds = collect(Arr::get($filters, 'service_ids', []))
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->values();

                if ($serviceIds->isNotEmpty()) {
                    $clientIds = Appointment::query()
                        ->where('user_id', $campaign->user_id)
                        ->where(function ($builder) use ($serviceIds) {
                            foreach ($serviceIds as $serviceId) {
                                $builder->orWhereJsonContains('service_ids', $serviceId);
                            }
                        })
                        ->pluck('client_id')
                        ->unique()
                        ->all();

                    if (! empty($clientIds)) {
                        $query->whereIn('id', $clientIds);
                    } else {
                        return collect();
                    }
                }
                break;
            case 'by_master':
                $masterIds = collect(Arr::get($filters, 'master_ids', []))
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->values();

                if ($masterIds->isNotEmpty()) {
                    $query->where(function ($builder) use ($masterIds) {
                        foreach ($masterIds as $masterId) {
                            $builder
                                ->orWhereJsonContains('preferences->preferred_master_id', $masterId)
                                ->orWhereJsonContains('preferences->masters', $masterId);
                        }
                    });
                }
                break;
            case 'custom':
                $tags = collect(Arr::get($filters, 'tags', []))
                    ->filter()
                    ->values();

                if ($tags->isNotEmpty()) {
                    $query->where(function ($builder) use ($tags) {
                        foreach ($tags as $tag) {
                            $builder->orWhereJsonContains('tags', $tag);
                        }
                    });
                }
                break;
        }

        return $query->get();
    }

    protected function filterReachableRecipients(Collection $recipients, string $channel): Collection
    {
        return $recipients->filter(function (Client $client) use ($channel) {
            return match ($channel) {
                'sms' => (bool) $this->normalizePhone($client->phone),
                'email' => ! empty($client->email),
                'telegram' => (bool) $this->resolveTelegramRecipient($client),
                'whatsapp' => (bool) $this->resolveWhatsappContact($client),
                default => true,
            };
        })->values();
    }

    protected function dispatchCampaign(MarketingCampaign $campaign, Collection $recipients, string $mode, array $payload = []): array
    {
        $campaign->loadMissing('variants');

        $allocationData = $this->buildAllocations($campaign, $recipients, $mode, $payload);
        $allocations = $allocationData['allocations'];
        $groupSize = $allocationData['group_size'];
        $now = Carbon::now();

        $result = [
            'mode' => $mode,
            'sent' => 0,
            'variant_stats' => [],
        ];

        DB::transaction(function () use ($campaign, $allocations, $mode, $now, &$result, $groupSize) {
            foreach ($allocations as $allocation) {
                /** @var MarketingCampaignVariant|null $variant */
                $variant = $allocation['variant'];
                /** @var Collection $clients */
                $clients = $allocation['recipients'];

                $variantSent = 0;

                foreach ($clients as $client) {
                    /** @var Client $client */
                    $subject = $variant?->subject ?? $campaign->subject;
                    $content = $variant?->content ?? $campaign->content;
                    $address = $this->resolveChannelAddress($client, $campaign->channel);

                    MarketingDelivery::create([
                        'campaign_id' => $campaign->id,
                        'variant_id' => $variant?->id,
                        'client_id' => $client->id,
                        'channel' => $campaign->channel,
                        'status' => 'sent',
                        'sent_at' => $now,
                        'meta' => array_filter([
                            'mode' => $mode,
                            'subject' => $subject,
                            'content' => $content,
                            'address' => $address,
                        ]),
                    ]);

                    $variantSent++;
                }

                if ($variant) {
                    if ($mode === 'test') {
                        $variant->sample_size = $variantSent;
                        $variant->status = 'testing';
                    } elseif ($variant->sample_size === null) {
                        $variant->sample_size = $variantSent;
                        $variant->status = 'sent';
                    } else {
                        $variant->status = 'sent';
                    }

                    $variant->delivered_count = (int) $variant->delivered_count + $variantSent;
                    $variant->save();

                    $result['variant_stats'][] = [
                        'variant_id' => $variant->id,
                        'label' => $variant->label,
                        'sent' => $variantSent,
                    ];
                }

                $result['sent'] += $variantSent;
            }

            if ($mode === 'test' && $groupSize !== null) {
                $campaign->test_group_size = $groupSize;
            }

            $campaign->delivered_count = (int) $campaign->delivered_count + $result['sent'];
            $campaign->status = $mode === 'test' ? 'testing' : 'completed';
            $campaign->scheduled_at = $now;
            $campaign->save();
        });

        if ($groupSize !== null) {
            $result['group_size'] = $groupSize;
        }

        return $result;
    }

    protected function buildAllocations(MarketingCampaign $campaign, Collection $recipients, string $mode, array $payload): array
    {
        $allocations = [];
        $groupSize = null;

        if ($mode === 'test' && $campaign->is_ab_test && $campaign->variants->count() >= 2) {
            $groupSize = (int) ($payload['test_group_size'] ?? $campaign->test_group_size ?? $recipients->count());
            $groupSize = max(1, min($groupSize, $recipients->count()));
            $testRecipients = $recipients->take($groupSize)->values();
            $variantCount = max(1, $campaign->variants->count());
            $chunkSize = (int) ceil($groupSize / $variantCount);

            foreach ($campaign->variants as $index => $variant) {
                $chunk = $testRecipients->slice($index * $chunkSize, $chunkSize)->values();

                if ($chunk->isNotEmpty()) {
                    $allocations[] = [
                        'variant' => $variant,
                        'recipients' => $chunk,
                    ];
                }
            }
        } else {
            $variant = null;

            if ($campaign->is_ab_test) {
                $variant = $campaign->variants->firstWhere('id', $campaign->winning_variant_id)
                    ?? $campaign->variants->first();
            }

            $allocations[] = [
                'variant' => $variant,
                'recipients' => $recipients->values(),
            ];
        }

        return [
            'allocations' => $allocations,
            'group_size' => $groupSize,
        ];
    }

    protected function resolveChannelAddress(Client $client, string $channel): ?string
    {
        return match ($channel) {
            'sms' => $this->normalizePhone($client->phone),
            'email' => $client->email,
            'telegram' => $this->resolveTelegramRecipient($client),
            'whatsapp' => $this->resolveWhatsappContact($client),
            default => null,
        };
    }

    protected function resolveWhatsappContact(Client $client): ?string
    {
        $preferences = $client->preferences ?? [];
        $phone = Arr::get($preferences, 'whatsapp')
            ?? Arr::get($preferences, 'whatsapp_phone')
            ?? $client->phone;

        return $this->normalizePhone($phone);
    }

    protected function resolveTelegramRecipient(Client $client): ?string
    {
        $preferences = $client->preferences ?? [];

        $telegram = Arr::get($preferences, 'telegram_id')
            ?? Arr::get($preferences, 'telegram_username')
            ?? Arr::get($preferences, 'telegram');

        return $telegram ? (string) $telegram : null;
    }

    protected function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/[^0-9+]+/', '', $phone);

        if (! $digits) {
            return null;
        }

        if ($digits[0] !== '+' && strlen($digits) >= 11) {
            $digits = '+' . $digits;
        }

        return $digits;
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

    protected function isSleeping($lastVisit): bool
    {
        if (! $lastVisit) {
            return false;
        }

        $lastVisitAt = $lastVisit instanceof Carbon ? $lastVisit : Carbon::parse($lastVisit);

        return $lastVisitAt->lt(Carbon::now()->subMonths(3));
    }
}
