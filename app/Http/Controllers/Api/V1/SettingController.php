<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateIntegrationsRequest;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\AllergyReminderService;
use App\Services\ScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SettingController extends Controller
{
    public function __construct(
        private readonly AllergyReminderService $allergyReminderService,
        private readonly ScheduleService $scheduleService,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $settings = $user->setting ?? new Setting(['notification_prefs' => []]);
        $hasProAccess = $this->userHasProAccess($user);
        $hasEliteAccess = $this->userHasEliteAccess($user);
        $schedulePayload = $this->scheduleService->buildSettingsPayload($settings);

        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'timezone' => $user->timezone,
                'time_format' => $user->time_format,
                'telegram_id' => $user->telegram_id,
                'avatar_url' => $user->avatar_url,
                'initials' => $user->initials,
            ],
            'settings' => [
                'notifications' => $settings->notification_prefs ?? [],
                'integrations' => $this->integrationPayload($settings),
                'work_days' => $schedulePayload['work_days'],
                'work_hours' => $schedulePayload['work_hours'],
                'schedule_rules' => $schedulePayload['schedule_rules'],
                'holidays' => $user->holidays()->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString()),
                'address' => $settings->address,
                'map_point' => $settings->map_point,
                'reminder_message' => $settings->reminder_message,
                'options' => [
                    'services' => $this->allergyReminderService->availableServices($user),
                ],
                'features' => [
                    'allergy_reminders' => $this->allergyReminderService->buildSettingsPayload(
                        $settings,
                        $user,
                        $hasProAccess,
                    ),
                    'daily_post_ideas' => [
                        'enabled' => $hasEliteAccess ? (bool) $settings->daily_post_ideas_enabled : false,
                        'available' => $hasEliteAccess,
                        'channel' => $hasEliteAccess ? $settings->daily_post_ideas_channel : null,
                        'preferences' => $hasEliteAccess ? $settings->daily_post_ideas_preferences : null,
                        'required_plan' => 'elite',
                        'upgrade_url' => url('/subscription'),
                    ],
                ],
            ],
        ]);
    }

    public function integrations(Request $request)
    {
        $user = $request->user();
        $settings = $user->setting;

        return response()->json([
            'integrations' => $this->integrationPayload($settings),
        ]);
    }

    public function update(UpdateSettingsRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        $settings = Setting::firstOrNew(['user_id' => $user->id]);
        $hasProAccess = $this->userHasProAccess($user);
        $hasEliteAccess = $this->userHasEliteAccess($user);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'timezone' => $data['timezone'],
            'time_format' => $data['time_format'],
        ])->save();

        if (!empty($data['new_password'])) {
            $user->password = Hash::make($data['new_password']);
            $user->save();
        }

        $normalizedScheduleRules = $this->scheduleService->normalizeRules(
            $data['schedule_rules'] ?? null,
            $data['work_days'] ?? [],
            $data['work_hours'] ?? [],
        );
        $legacySchedule = $this->scheduleService->deriveLegacyFields($normalizedScheduleRules);

        $settingsAttributes = [
            'notification_prefs' => $data['notifications'] ?? [],
            'work_days' => $legacySchedule['work_days'],
            'work_hours' => $legacySchedule['work_hours'],
            'schedule_rules' => $normalizedScheduleRules,
            'address' => $data['address'] ?? null,
            'map_point' => $data['map_point'] ?? null,
            'reminder_message' => $data['reminder_message'] ?? null,
        ];

        if ($hasProAccess) {
            $settingsAttributes['allergy_reminder_enabled'] = (bool) ($data['allergy_reminder_enabled'] ?? false);
            $settingsAttributes['allergy_reminder_minutes'] = (int) ($data['allergy_reminder_minutes']
                ?? $settings->allergy_reminder_minutes
                ?? AllergyReminderService::DEFAULT_MINUTES);
            $settingsAttributes['allergy_reminder_exclusions'] = $this->allergyReminderService->normalizeExclusions(
                $data['allergy_reminder_exclusions'] ?? $settings->allergy_reminder_exclusions,
                $user,
            );
        }

        if ($hasEliteAccess) {
            $settingsAttributes['daily_post_ideas_enabled'] = (bool) ($data['daily_post_ideas_enabled'] ?? false);
            $settingsAttributes['daily_post_ideas_channel'] = $data['daily_post_ideas_channel'] ?? 'both';
            $settingsAttributes['daily_post_ideas_preferences'] = $data['daily_post_ideas_preferences'] ?? null;
        }

        if (array_key_exists('integrations', $data)) {
            $settingsAttributes = array_merge($settingsAttributes, $this->integrationAttributes($data));
        }

        Setting::updateOrCreate(['user_id' => $user->id], $settingsAttributes);

        if (isset($data['holidays'])) {
            $user->holidays()->delete();
            foreach ($data['holidays'] as $date) {
                $user->holidays()->create(['date' => $date]);
            }
        }

        return $this->index($request);
    }

    public function updateIntegrations(UpdateIntegrationsRequest $request)
    {
        $user = $request->user();
        $settings = Setting::firstOrNew(['user_id' => $user->id]);
        $settings->fill($this->integrationAttributes($request->validated()));
        $settings->save();

        return response()->json([
            'integrations' => $this->integrationPayload($settings),
        ]);
    }

    protected function integrationPayload(?Setting $settings): array
    {
        return [
            'smsaero' => [
                'email' => $settings?->smsaero_email,
                'api_key' => $settings?->smsaero_api_key,
            ],
            'smtp' => [
                'host' => $settings?->smtp_host,
                'port' => $settings?->smtp_port,
                'username' => $settings?->smtp_username,
                'password' => $settings?->smtp_password,
                'encryption' => $settings?->smtp_encryption,
                'from_address' => $settings?->smtp_from_address,
                'from_name' => $settings?->smtp_from_name,
            ],
            'whatsapp' => [
                'api_key' => $settings?->whatsapp_api_key,
                'sender' => $settings?->whatsapp_sender,
            ],
            'telegram' => [
                'bot_token' => $settings?->telegram_bot_token,
                'sender' => $settings?->telegram_sender,
            ],
            'yookassa' => [
                'shop_id' => $settings?->yookassa_shop_id,
                'secret_key' => $settings?->yookassa_secret_key,
            ],
        ];
    }

    protected function integrationAttributes(array $data): array
    {
        $map = [
            'integrations.smsaero.email' => 'smsaero_email',
            'integrations.smsaero.api_key' => 'smsaero_api_key',
            'integrations.smtp.host' => 'smtp_host',
            'integrations.smtp.port' => 'smtp_port',
            'integrations.smtp.username' => 'smtp_username',
            'integrations.smtp.password' => 'smtp_password',
            'integrations.smtp.encryption' => 'smtp_encryption',
            'integrations.smtp.from_address' => 'smtp_from_address',
            'integrations.smtp.from_name' => 'smtp_from_name',
            'integrations.whatsapp.api_key' => 'whatsapp_api_key',
            'integrations.whatsapp.sender' => 'whatsapp_sender',
            'integrations.telegram.bot_token' => 'telegram_bot_token',
            'integrations.telegram.sender' => 'telegram_sender',
            'integrations.yookassa.shop_id' => 'yookassa_shop_id',
            'integrations.yookassa.secret_key' => 'yookassa_secret_key',
        ];

        $attributes = [];

        foreach ($map as $path => $column) {
            if ($this->hasPath($data, $path)) {
                $attributes[$column] = data_get($data, $path);
            }
        }

        return $attributes;
    }

    protected function hasPath(array $data, string $path): bool
    {
        $segments = explode('.', $path);

        foreach ($segments as $segment) {
            if (! is_array($data) || ! array_key_exists($segment, $data)) {
                return false;
            }

            $data = $data[$segment];
        }

        return true;
    }

    protected function userHasEliteAccess(User $user): bool
    {
        return $user->plans()
            ->whereIn('name', ['elite', 'Elite', 'ELITE'])
            ->where(function ($query) {
                $query
                    ->whereNull('plan_user.ends_at')
                    ->orWhere('plan_user.ends_at', '>', Carbon::now());
            })
            ->exists();
    }

    protected function userHasProAccess(User $user): bool
    {
        return $user->plans()
            ->whereIn('name', ['pro', 'Pro', 'PRO', 'elite', 'Elite', 'ELITE'])
            ->where(function ($query) {
                $query
                    ->whereNull('plan_user.ends_at')
                    ->orWhere('plan_user.ends_at', '>', Carbon::now());
            })
            ->exists();
    }
}
