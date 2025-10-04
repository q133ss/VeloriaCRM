<?php

namespace App\Services\Marketing;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\MarketingCampaign;
use App\Models\MarketingCampaignVariant;
use App\Models\MarketingDelivery;
use App\Models\MessageTemplate;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MarketingCampaignService
{
    public function __construct(private readonly MarketingChannelSender $channelSender)
    {
    }

    /**
     * @throws ValidationException
     */
    public function ensureChannelConfigured(MarketingCampaign $campaign, ?Setting $settings): Setting
    {
        $channelLabels = [
            'sms' => 'SMS',
            'email' => 'Email',
            'whatsapp' => 'WhatsApp',
        ];

        if (! $settings) {
            throw ValidationException::withMessages([
                'channel' => __('marketing.campaigns.channel_not_configured', [
                    'channel' => $channelLabels[$campaign->channel] ?? strtoupper($campaign->channel),
                ]),
            ]);
        }

        $smtpMissing = empty($settings->smtp_host)
            || empty($settings->smtp_port)
            || empty($settings->smtp_username)
            || empty($settings->smtp_password)
            || empty($settings->smtp_from_address);

        $missing = match ($campaign->channel) {
            'sms' => empty($settings->smsaero_email) || empty($settings->smsaero_api_key),
            'email' => $smtpMissing,
            'whatsapp' => empty($settings->whatsapp_api_key) || empty($settings->whatsapp_sender),
            default => true,
        };

        if ($missing) {
            throw ValidationException::withMessages([
                'channel' => __('marketing.campaigns.channel_not_configured', [
                    'channel' => $channelLabels[$campaign->channel] ?? strtoupper($campaign->channel),
                ]),
            ]);
        }

        return $settings;
    }

    public function availableChannels(?Setting $settings): array
    {
        $channels = [
            ['value' => 'sms', 'label' => 'SMS'],
            ['value' => 'email', 'label' => 'Email'],
            ['value' => 'whatsapp', 'label' => 'WhatsApp'],
        ];

        if (! $settings) {
            return [];
        }

        return collect($channels)
            ->filter(function (array $channel) use ($settings) {
                return match ($channel['value']) {
                    'sms' => ! empty($settings->smsaero_email) && ! empty($settings->smsaero_api_key),
                    'email' => ! empty($settings->smtp_host)
                        && ! empty($settings->smtp_port)
                        && ! empty($settings->smtp_username)
                        && ! empty($settings->smtp_password)
                        && ! empty($settings->smtp_from_address),
                    'whatsapp' => ! empty($settings->whatsapp_api_key) && ! empty($settings->whatsapp_sender),
                    default => false,
                };
            })
            ->values()
            ->all();
    }

    public function resolveSegmentsForUser(int $userId): array
    {
        $clients = Client::where('user_id', $userId)->get();
        $loyaltySegments = ['gold', 'platinum', 'vip', 'ambassador'];

        $segments = [
            ['value' => 'all', 'label' => __('marketing.segments.all'), 'count' => $clients->count()],
            ['value' => 'new', 'label' => __('marketing.segments.new'), 'count' => $clients->filter(fn ($client) => $client->last_visit_at === null)->count()],
            ['value' => 'loyal', 'label' => __('marketing.segments.loyal'), 'count' => $clients->filter(fn ($client) => in_array($client->loyalty_level, $loyaltySegments, true))->count()],
            ['value' => 'sleeping', 'label' => __('marketing.segments.sleeping'), 'count' => $clients->filter(fn ($client) => $this->isSleeping($client->last_visit_at))->count()],
            ['value' => 'by_service', 'label' => __('marketing.segments.by_service')],
            ['value' => 'by_master', 'label' => __('marketing.segments.by_master')],
            ['value' => 'custom', 'label' => __('marketing.segments.custom')],
            ['value' => 'selected', 'label' => __('marketing.segments.selected')],
        ];

        $clientOptions = $clients->map(fn (Client $client) => [
            'id' => $client->id,
            'name' => $client->name ?? __('marketing.campaigns.unnamed_client', ['id' => $client->id]),
            'email' => $client->email,
            'phone' => $client->phone,
        ])->values()->all();

        return [
            'segments' => $segments,
            'clients' => $clientOptions,
        ];
    }

    public function resolveTemplatesForUser(int $userId): array
    {
        return MessageTemplate::where(function ($query) use ($userId) {
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
                'subject' => $template->subject,
            ])->values()->all();
    }

    public function isSleeping(?string $lastVisit): bool
    {
        if (! $lastVisit) {
            return true;
        }

        return Carbon::parse($lastVisit)->lt(Carbon::now()->subMonths(3));
    }

    public function resolveRecipients(MarketingCampaign $campaign): Collection
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
            case 'selected':
                $clientIds = collect(Arr::get($filters, 'client_ids', []))
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->values();

                if ($clientIds->isEmpty()) {
                    return collect();
                }

                $query->whereIn('id', $clientIds);
                break;
        }

        return $query->get();
    }

    public function filterReachableRecipients(Collection $recipients, string $channel): Collection
    {
        return $recipients->filter(function (Client $client) use ($channel) {
            return match ($channel) {
                'sms' => (bool) $this->normalizePhone($client->phone),
                'email' => ! empty($client->email),
                'whatsapp' => (bool) $this->resolveWhatsappContact($client),
                default => false,
            };
        })->values();
    }

    public function dispatchCampaign(MarketingCampaign $campaign, Collection $recipients, string $mode, array $payload, Setting $settings): array
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

        DB::transaction(function () use ($campaign, $allocations, $mode, $now, &$result, $groupSize, $settings) {
            $campaign->status = $mode === 'test' ? 'testing' : 'sending';
            $campaign->scheduled_at = $now;
            $campaign->save();

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

                    if (! $address) {
                        continue;
                    }

                    try {
                        $this->channelSender->send($settings, $campaign->channel, $address, $subject, (string) $content);
                    } catch (RuntimeException $exception) {
                        Log::warning('Failed to send marketing campaign message', [
                            'campaign_id' => $campaign->id,
                            'client_id' => $client->id,
                            'channel' => $campaign->channel,
                            'message' => $exception->getMessage(),
                        ]);
                        continue;
                    }

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

            if ($groupSize !== null) {
                $campaign->test_group_size = $groupSize;
            }

            $campaign->delivered_count = (int) $campaign->delivered_count + $result['sent'];
            $campaign->status = $mode === 'test' ? 'testing' : 'completed';
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
            $digits = '+'.$digits;
        }

        return $digits;
    }
}
