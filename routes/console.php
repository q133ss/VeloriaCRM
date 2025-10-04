<?php

use App\Jobs\SendDailyMasterMoodCheckJob;
use App\Models\MarketingCampaign;
use App\Models\Setting;
use App\Services\Marketing\MarketingCampaignService;
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

Schedule::command('marketing:dispatch-scheduled')->everyMinute();

Schedule::job(new SendDailyMasterMoodCheckJob())
    ->dailyAt('21:00')
    ->name('send-daily-master-mood-check')
    ->withoutOverlapping();
