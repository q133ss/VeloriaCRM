<?php

use App\Models\MarketingCampaign;
use App\Models\Setting;
use App\Models\User;
use App\Services\DailyPostIdeaService;
use App\Services\Marketing\MarketingCampaignService;
use App\Services\SubscriptionPaymentSyncService;
use App\Services\Telegram\TelegramBookingBotService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Validation\ValidationException;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('marketing:dispatch-scheduled', function () {
    /** @var MarketingCampaignService $service */
    $service = app(MarketingCampaignService::class);
    $campaigns = MarketingCampaign::with(['variants', 'winningVariant'])
        ->where('status', 'scheduled')
        ->whereNotNull('scheduled_at')
        ->where('scheduled_at', '<=', Carbon::now())
        ->get();

    $processed = 0;

    foreach ($campaigns as $campaign) {
        $settings = Setting::where('user_id', $campaign->user_id)->first();

        try {
            $settings = $service->ensureChannelConfigured($campaign, $settings);
        } catch (ValidationException $exception) {
            $campaign->status = 'cancelled';
            $campaign->save();
            $this->warn("Campaign {$campaign->id} skipped due to configuration.");
            continue;
        }

        $recipients = $service->resolveRecipients($campaign);
        $reachable = $service->filterReachableRecipients($recipients, $campaign->channel);

        if ($reachable->isEmpty()) {
            $campaign->status = 'cancelled';
            $campaign->save();
            $this->warn("Campaign {$campaign->id} has no reachable recipients.");
            continue;
        }

        $service->dispatchCampaign($campaign, $reachable, 'scheduled', [], $settings);
        $campaign->refresh()->load(['variants', 'winningVariant']);
        $processed++;
    }

    $this->info("Dispatched {$processed} scheduled campaigns.");
})->purpose('Dispatch scheduled marketing campaigns');

Artisan::command('telegram:poll-booking {--once} {--timeout=10} {--sleep=1}', function () {
    /** @var TelegramBookingBotService $service */
    $service = app(TelegramBookingBotService::class);
    $once = (bool) $this->option('once');
    $timeout = max(0, (int) $this->option('timeout'));
    $sleepSeconds = max(0, (int) $this->option('sleep'));

    do {
        $service->pollOnce($timeout);

        if ($once) {
            break;
        }

        if ($sleepSeconds > 0) {
            sleep($sleepSeconds);
        }
    } while (true);
})->purpose('Poll Telegram bots and create booking requests from chat');

Artisan::command('subscription:sync-pending', function () {
    /** @var SubscriptionPaymentSyncService $service */
    $service = app(SubscriptionPaymentSyncService::class);
    $updated = $service->syncAllPending();

    $this->info("Synced {$updated} subscription payment(s).");
})->purpose('Sync pending subscription payments with YooKassa');

Artisan::command('content:send-daily-ideas {userId?}', function (?int $userId = null) {
    /** @var DailyPostIdeaService $service */
    $service = app(DailyPostIdeaService::class);
    $result = $service->dispatchEnabledIdeas($userId);

    $this->info(sprintf(
        'Processed: %d, sent: %d, skipped: %d',
        $result['processed'],
        $result['sent'],
        $result['skipped']
    ));

    foreach ($result['items'] as $item) {
        $this->line(json_encode($item, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
})->purpose('Generate and deliver daily AI content ideas for Elite users');

Artisan::command('content:test-daily-idea {userId?}', function (?int $userId = null) {
    /** @var DailyPostIdeaService $service */
    $service = app(DailyPostIdeaService::class);

    $user = $userId
        ? User::query()->findOrFail($userId)
        : User::query()
            ->whereHas('plans', function ($query) {
                $query->whereIn('name', ['elite', 'Elite', 'ELITE']);
            })
            ->whereHas('setting', function ($query) {
                $query->where('daily_post_ideas_enabled', true);
            })
            ->firstOrFail();

    $idea = $service->generateIdeaForUser($user, $user->setting);

    $this->info('AI idea generated for user #' . $user->id . ' (' . $user->name . ')');
    $this->newLine();
    $this->line('Title: ' . $idea['title']);
    $this->line('Channel: ' . $idea['channel']);
    $this->newLine();
    $this->line($idea['idea']);
    $this->newLine();
    $this->line('CTA: ' . $idea['cta']);
})->purpose('Generate one real AI content idea without sending it');

Schedule::command('marketing:dispatch-scheduled')->everyMinute();
Schedule::command('subscription:sync-pending')->everyMinute();
Schedule::command('content:send-daily-ideas')->dailyAt('09:00');
