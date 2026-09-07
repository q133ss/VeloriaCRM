<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateIntegrationsRequest;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\AllergyReminderService;
use App\Services\ClientIdentityService;
use App\Services\Integrations\IntegrationCatalog;
use App\Services\Integrations\IntegrationChecker;
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
        private readonly IntegrationChecker $integrationChecker,
        private readonly ClientIdentityService $clientIdentityService,
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
            // The form sends what its mask drew — «+7(999)000-55-44» — and that
            // string was stored and then handed to the SMS gateway.
            'phone' => $this->normalizePhone($data['phone'] ?? null),
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
            // Empty coordinates were stored as ['lat' => null, 'lng' => null],
            // which every «if the point is set» check reads as a point.
            'map_point' => $this->normalizeMapPoint($data['map_point'] ?? null),
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

    /**
     * Secrets are never sent back to the browser: the page used to refill the
     * token, the API keys and the secret key into visible text inputs, while
     * promising in its own header that they are not shown to anyone.
     */
    protected function integrationPayload(?Setting $settings): array
    {
        $payload = [];

        foreach (IntegrationCatalog::providers() as $provider) {
            $values = IntegrationCatalog::values($settings, $provider);
            $fields = [];

            foreach (IntegrationCatalog::fields($provider) as $field => $definition) {
                $secret = (bool) ($definition['secret'] ?? false);

                $fields[$field] = [
                    'value' => $secret ? null : $values[$field],
                    'filled' => $values[$field] !== null,
                    'secret' => $secret,
                    'required' => (bool) ($definition['required'] ?? false),
                    'preview' => $secret ? IntegrationCatalog::preview($values[$field]) : null,
                ];
            }

            $payload[$provider] = [
                'fields' => $fields,
                'status' => $this->integrationStatus($settings, $provider),
            ];
        }

        return $payload;
    }

    /**
     * «Подключено» used to mean «the fields are not empty», so the word
     * «ляляля» in a token field produced a green badge. It now means that the
     * service answered, and it stops meaning it the moment a field changes.
     *
     * @return array{state: string, missing: array<int, string>, checked_at: ?string, message: ?string}
     */
    protected function integrationStatus(?Setting $settings, string $provider): array
    {
        $missing = IntegrationCatalog::missing($settings, $provider);

        $status = [
            'state' => 'empty',
            'missing' => $missing,
            'checked_at' => null,
            'message' => null,
        ];

        if (! IntegrationCatalog::hasAnyValue($settings, $provider)) {
            return $status;
        }

        if ($missing !== []) {
            $status['state'] = 'partial';

            return $status;
        }

        $check = ($settings?->integration_checks ?? [])[$provider] ?? null;
        $status['state'] = 'filled';

        if (! is_array($check) || ($check['fingerprint'] ?? null) !== IntegrationCatalog::fingerprint($settings, $provider)) {
            return $status;
        }

        $status['state'] = ($check['ok'] ?? false) ? 'verified' : 'failed';
        $status['checked_at'] = $check['checked_at'] ?? null;
        $status['message'] = $check['message'] ?? null;

        return $status;
    }

    /**
     * Asks the service itself and remembers the answer.
     */
    public function checkIntegration(Request $request, string $provider)
    {
        abort_unless(IntegrationCatalog::has($provider), 404);

        $settings = Setting::firstOrNew(['user_id' => $request->user()->id]);
        $result = $this->integrationChecker->check($settings, $provider);

        $checks = $settings->integration_checks ?? [];
        $checks[$provider] = [
            'ok' => $result['ok'],
            'message' => $result['message'],
            'checked_at' => Carbon::now()->toIso8601String(),
            'fingerprint' => IntegrationCatalog::fingerprint($settings, $provider),
        ];

        $settings->integration_checks = $checks;
        $settings->save();

        return response()->json([
            'result' => $result,
            'integrations' => $this->integrationPayload($settings),
        ]);
    }

    /**
     * Turning a channel off is an action of its own: it used to be «clear
     * every field by hand and press save», with nothing asking whether that
     * was meant.
     */
    public function disconnectIntegration(Request $request, string $provider)
    {
        abort_unless(IntegrationCatalog::has($provider), 404);

        $settings = Setting::firstOrNew(['user_id' => $request->user()->id]);

        foreach (IntegrationCatalog::columns($provider) as $column) {
            $settings->{$column} = null;
        }

        $checks = $settings->integration_checks ?? [];
        unset($checks[$provider]);
        $settings->integration_checks = $checks;
        $settings->save();

        return response()->json([
            'integrations' => $this->integrationPayload($settings),
        ]);
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

    protected function normalizePhone(?string $phone): ?string
    {
        $phone = $phone !== null ? trim($phone) : null;

        if ($phone === null || $phone === '') {
            return null;
        }

        return $this->clientIdentityService->normalisePhone($phone);
    }

    /**
     * @param  array<string, mixed>|null  $point
     * @return array{lat: float, lng: float}|null
     */
    protected function normalizeMapPoint(?array $point): ?array
    {
        $lat = $point['lat'] ?? null;
        $lng = $point['lng'] ?? null;

        if ($lat === null || $lat === '' || $lng === null || $lng === '') {
            return null;
        }

        return ['lat' => (float) $lat, 'lng' => (float) $lng];
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
