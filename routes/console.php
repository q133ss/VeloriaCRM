<?php

use App\Models\MarketingCampaign;
use App\Models\Setting;
use App\Services\Marketing\MarketingCampaignService;
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

Schedule::command('marketing:dispatch-scheduled')->everyMinute();
Schedule::command('telegram:poll-booking --once --timeout=1')->everyMinute();
