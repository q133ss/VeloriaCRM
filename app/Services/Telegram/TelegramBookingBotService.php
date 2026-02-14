<?php

namespace App\Services\Telegram;

use App\Models\Client;
use App\Models\Order;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\User;
use App\Services\Marketing\MarketingChannelSender;
use App\Services\NotificationService;
use App\Services\OrderService;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class TelegramBookingBotService
{
    protected const OFFSET_TTL_DAYS = 14;
    protected const SESSION_TTL_MINUTES = 120;
    protected const LOOKAHEAD_DAYS = 14;

    public function __construct(
        private readonly TelegramBotApiService $telegramApi,
        private readonly NotificationService $notifications,
        private readonly MarketingChannelSender $channelSender,
        private readonly OrderService $orderService,
    ) {
    }

    public function pollOnce(int $timeout = 1): void
    {
        Setting::query()
            ->with('user')
            ->whereNotNull('telegram_bot_token')
            ->where('telegram_bot_token', '!=', '')
            ->chunkById(50, function (Collection $settings) use ($timeout) {
                foreach ($settings as $setting) {
                    $this->pollSetting($setting, $timeout);
                }
            });
    }

    protected function pollSetting(Setting $setting, int $timeout): void
    {
        $token = trim((string) $setting->telegram_bot_token);
        $master = $setting->user;

        if ($token === '' || ! $master) {
            return;
        }

        $offsetKey = $this->offsetKey($token);
        $offset = Cache::get($offsetKey);
        $offset = is_numeric($offset) ? (int) $offset : null;

        try {
            $updates = $this->telegramApi->getUpdates($token, $offset, $timeout);
        } catch (Throwable $exception) {
            Log::warning('Telegram polling failed for master.', [
                'master_id' => $master->id,
                'exception' => $exception->getMessage(),
            ]);

            return;
        }

        if ($updates === []) {
            return;
        }

        $maxUpdateId = null;

        foreach ($updates as $update) {
            $updateId = Arr::get($update, 'update_id');
            if (is_numeric($updateId)) {
                $maxUpdateId = max((int) $updateId, (int) ($maxUpdateId ?? 0));
            }

            try {
                $this->handleUpdate($setting, $token, $update);
            } catch (Throwable $exception) {
                // Never crash polling worker because of a single bad update.
                Log::warning('Telegram update handling failed.', [
                    'master_id' => $master->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        if ($maxUpdateId !== null) {
            Cache::put($offsetKey, $maxUpdateId + 1, now()->addDays(self::OFFSET_TTL_DAYS));
        }
    }

    protected function handleUpdate(Setting $setting, string $token, array $update): void
    {
        $callbackQuery = Arr::get($update, 'callback_query');
        if (is_array($callbackQuery)) {
            $this->handleCallbackQuery($setting, $token, $callbackQuery);
            return;
        }

        $message = Arr::get($update, 'message');
        if (is_array($message)) {
            $this->handleMessage($setting, $token, $message);
        }
    }

    protected function handleMessage(Setting $setting, string $token, array $message): void
    {
        $chatId = Arr::get($message, 'chat.id');
        if (! is_numeric($chatId) && ! is_string($chatId)) {
            return;
        }

        $from = Arr::get($message, 'from');
        $fromUser = is_array($from) ? $this->extractTelegramUser($from) : [];

        $contact = Arr::get($message, 'contact');
        if (is_array($contact)) {
            $this->handleContactMessage($setting, $token, (string) $chatId, $fromUser, $contact);
            return;
        }

        $text = trim((string) Arr::get($message, 'text', ''));
        if ($text === '') {
            return;
        }

        $normalized = mb_strtolower($text);

        if ($normalized === '/start' || $normalized === '/book' || Str::contains($normalized, mb_strtolower(__('telegram.booking.book_button')))) {
            $this->forgetSession($token, (string) $chatId);
            $this->sendStartMenu($setting, $token, (string) $chatId, $fromUser);
            return;
        }

        // Manual phone entry if we are waiting for it.
        $session = $this->getSession($token, (string) $chatId);
        if (! empty($session['awaiting_phone'])) {
            $this->handleManualPhoneEntry($setting, $token, (string) $chatId, $fromUser, $text);
            return;
        }

        $this->sendStartMenu($setting, $token, (string) $chatId, $fromUser);
    }

    protected function handleCallbackQuery(Setting $setting, string $token, array $callbackQuery): void
    {
        $queryId = (string) Arr::get($callbackQuery, 'id', '');
        $chatId = Arr::get($callbackQuery, 'message.chat.id');
        $data = (string) Arr::get($callbackQuery, 'data', '');

        if ($queryId !== '') {
            try {
                $this->telegramApi->answerCallbackQuery($token, $queryId);
            } catch (Throwable $exception) {
                Log::debug('Telegram callback acknowledgement failed.', [
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        if ((! is_numeric($chatId) && ! is_string($chatId)) || ! Str::startsWith($data, 'tgbook:')) {
            return;
        }

        $master = $setting->user;
        if (! $master) {
            return;
        }

        $chatId = (string) $chatId;
        $action = Str::after($data, 'tgbook:');

        if ($action === 'start') {
            $this->forgetSession($token, $chatId);
            $this->sendCategoryPicker($setting, $token, $chatId);
            return;
        }

        if ($action === 'cancel') {
            $session = $this->getSession($token, $chatId);
            $this->forgetSession($token, $chatId);
            $this->sendStartMenu($setting, $token, $chatId, Arr::get($session, 'client', []));
            return;
        }

        $session = $this->getSession($token, $chatId);
        $session['chat_id'] = $chatId;
        $session['master_id'] = $master->id;
        $session['client'] = $this->extractTelegramUser(Arr::get($callbackQuery, 'from', []));

        if (Str::startsWith($action, 'cat:')) {
            $categoryId = Str::after($action, 'cat:');

            if ($categoryId === 'all') {
                unset($session['category_id']);
                $this->putSession($token, $chatId, $session);
                $this->sendServicePicker($setting, $token, $chatId, null);
                return;
            }

            if ($categoryId === 'uncat') {
                $session['category_id'] = 'uncat';
                unset($session['service_id'], $session['date'], $session['time']);
                $this->putSession($token, $chatId, $session);
                $this->sendServicePicker($setting, $token, $chatId, null);
                return;
            }

            $categoryIdInt = (int) $categoryId;
            $category = ServiceCategory::query()
                ->where('id', $categoryIdInt)
                ->where('user_id', $master->id)
                ->first();

            if (! $category) {
                $this->sendCategoryPicker($setting, $token, $chatId);
                return;
            }

            $session['category_id'] = $category->id;
            unset($session['service_id'], $session['date'], $session['time']);
            $this->putSession($token, $chatId, $session);
            $this->sendServicePicker($setting, $token, $chatId, $category);
            return;
        }

        if (Str::startsWith($action, 'service:')) {
            $serviceId = (int) Str::after($action, 'service:');
            $service = Service::query()->where('id', $serviceId)->where('user_id', $master->id)->first();

            if (! $service) {
                $this->sendStartMenu($setting, $token, $chatId, Arr::get($session, 'client', []));
                return;
            }

            $session['service_id'] = $service->id;
            unset($session['date'], $session['time']);
            $this->putSession($token, $chatId, $session);
            $this->sendDatePicker($setting, $token, $chatId, $service);
            return;
        }

        if (Str::startsWith($action, 'date:')) {
            $service = $this->resolveSessionService($session, $master->id);
            if (! $service) {
                $this->sendCategoryPicker($setting, $token, $chatId);
                return;
            }

            $dateValue = Str::after($action, 'date:');
            if (! preg_match('/^\d{8}$/', $dateValue)) {
                $this->sendDatePicker($setting, $token, $chatId, $service);
                return;
            }

            $date = Carbon::createFromFormat('Ymd', $dateValue)->format('Y-m-d');
            $session['date'] = $date;
            unset($session['time']);
            $this->putSession($token, $chatId, $session);
            $this->sendTimePicker($setting, $token, $chatId, $service, $date);
            return;
        }

        if (Str::startsWith($action, 'time:')) {
            $service = $this->resolveSessionService($session, $master->id);
            $date = Arr::get($session, 'date');
            if (! $service || ! is_string($date)) {
                $this->sendCategoryPicker($setting, $token, $chatId);
                return;
            }

            $timeValue = Str::after($action, 'time:');
            if (! preg_match('/^\d{4}$/', $timeValue)) {
                $this->sendTimePicker($setting, $token, $chatId, $service, $date);
                return;
            }

            $time = substr($timeValue, 0, 2) . ':' . substr($timeValue, 2, 2);
            $available = $this->availableSlotsForDate($master, $setting, $service, $date);
            if (! in_array($time, $available, true)) {
                $this->sendTimePicker($setting, $token, $chatId, $service, $date);
                return;
            }

            $session['time'] = $time;
            $this->putSession($token, $chatId, $session);
            $this->sendConfirmation($token, $chatId, $service, $date, $time, Arr::get($session, 'client.name'));
            return;
        }

        // Backward-compatible: older messages had tgbook:confirm without payload.
        if ($action === 'confirm') {
            $service = $this->resolveSessionService($session, $master->id);
            $date = Arr::get($session, 'date');
            $time = Arr::get($session, 'time');

            if (! $service || ! is_string($date) || ! is_string($time)) {
                $this->sendCategoryPicker($setting, $token, $chatId);
                return;
            }

            $available = $this->availableSlotsForDate($master, $setting, $service, $date);
            if (! in_array($time, $available, true)) {
                $this->sendTimePicker($setting, $token, $chatId, $service, $date);
                return;
            }

            if (! $this->clientHasPhone($session)) {
                $this->askForPhone($setting, $token, $chatId, $session, [
                    'service_id' => $service->id,
                    'date' => $date,
                    'time' => $time,
                ]);
                return;
            }

            $order = $this->createOrderFromSession($master, $setting, $service, $session, $date, $time);
            $this->forgetSession($token, $chatId);
            $this->sendSuccessMessage($token, $chatId, $order->id, $service->name, $date, $time);
            return;
        }

        if (Str::startsWith($action, 'confirm:')) {
            // Stateless confirm to avoid cache/session issues:
            // tgbook:confirm:{serviceId}:{yyyymmdd}:{hhmm}
            $parts = explode(':', $action);
            $serviceId = isset($parts[1]) ? (int) $parts[1] : 0;
            $dateRaw = $parts[2] ?? '';
            $timeRaw = $parts[3] ?? '';

            if ($serviceId <= 0 || ! preg_match('/^\\d{8}$/', $dateRaw) || ! preg_match('/^\\d{4}$/', $timeRaw)) {
                $this->sendCategoryPicker($setting, $token, $chatId);
                return;
            }

            $service = Service::query()->where('id', $serviceId)->where('user_id', $master->id)->first();
            if (! $service) {
                $this->sendCategoryPicker($setting, $token, $chatId);
                return;
            }

            $date = Carbon::createFromFormat('Ymd', $dateRaw)->format('Y-m-d');
            $time = substr($timeRaw, 0, 2) . ':' . substr($timeRaw, 2, 2);

            $available = $this->availableSlotsForDate($master, $setting, $service, $date);
            if (! in_array($time, $available, true)) {
                $this->sendTimePicker($setting, $token, $chatId, $service, $date);
                return;
            }

            // Persist for audit/debug and client creation.
            $session['service_id'] = $service->id;
            $session['date'] = $date;
            $session['time'] = $time;
            $this->putSession($token, $chatId, $session);

            if (! $this->clientHasPhone($session)) {
                $this->askForPhone($setting, $token, $chatId, $session, [
                    'service_id' => $service->id,
                    'date' => $date,
                    'time' => $time,
                ]);
                return;
            }

            $order = $this->createOrderFromSession($master, $setting, $service, $session, $date, $time);
            $this->forgetSession($token, $chatId);
            $this->sendSuccessMessage($token, $chatId, $order->id, $service->name, $date, $time);
        }
    }

    protected function sendStartMenu(Setting $setting, string $token, string $chatId, array $fromUser = []): void
    {
        $master = $setting->user;
        $masterName = $this->escapeHtml($master?->name ?: __('telegram.booking.master_default_name'));
        $address = $this->escapeHtml(trim((string) ($setting->address ?? '')));
        $clientName = trim((string) Arr::get($fromUser, 'name', ''));
        $username = trim((string) Arr::get($fromUser, 'username', ''));
        $clientDisplay = $clientName !== '' ? $clientName : __('telegram.booking.unknown_client');
        if ($username !== '') {
            $clientDisplay = $clientDisplay . ' (@' . $username . ')';
        }
        $clientDisplay = $this->escapeHtml($clientDisplay);

        $nextVisit = null;
        if ($master && ($fromId = Arr::get($fromUser, 'id'))) {
            $client = User::query()->where('telegram_id', (string) $fromId)->first();
            if ($client) {
                $timezone = $master->timezone ?? config('app.timezone');
                $next = Order::query()
                    ->where('master_id', $master->id)
                    ->where('client_id', $client->id)
                    ->whereNotIn('status', ['cancelled', 'no_show'])
                    ->where('scheduled_at', '>', now())
                    ->orderBy('scheduled_at')
                    ->first();

                if ($next?->scheduled_at) {
                    $nextVisit = $next->scheduled_at->copy()->timezone($timezone)->format('d.m.Y H:i');
                }
            }
        }

        $nextVisit = $nextVisit ? $this->escapeHtml($nextVisit) : null;

        $text = __('telegram.booking.start_card', [
            'master' => $masterName,
            'client' => $clientDisplay,
            'address' => $address !== '' ? $address : __('telegram.booking.address_missing'),
            'next_visit' => $nextVisit ?: __('telegram.booking.next_visit_missing'),
        ]);

        $this->safeSendMessage($token, $chatId, $text, [
            'parse_mode' => 'HTML',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => __('telegram.booking.book_button'), 'callback_data' => 'tgbook:start'],
                    ],
                ],
            ],
        ]);

        // Ask for phone on /start if it's missing, so booking can be created safely.
        if ($this->resolveClientUserByTelegram($fromUser)?->phone) {
            return;
        }

        $this->safeSendMessage($token, $chatId, __('telegram.booking.phone_required_text'), [
            'reply_markup' => [
                'keyboard' => [
                    [
                        ['text' => __('telegram.booking.share_phone_button'), 'request_contact' => true],
                    ],
                ],
                'one_time_keyboard' => true,
                'resize_keyboard' => true,
            ],
        ]);
    }

    protected function sendCategoryPicker(Setting $setting, string $token, string $chatId): void
    {
        $categories = ServiceCategory::query()
            ->where('user_id', $setting->user_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $servicesCount = Service::query()
            ->where('user_id', $setting->user_id)
            ->count();

        if ($servicesCount === 0) {
            $this->safeSendMessage($token, $chatId, __('telegram.booking.no_services'));
            return;
        }

        $keyboard = [];

        foreach ($categories as $category) {
            $keyboard[] = [[
                'text' => $category->name,
                'callback_data' => 'tgbook:cat:' . $category->id,
            ]];
        }

        $hasUncategorized = Service::query()
            ->where('user_id', $setting->user_id)
            ->whereNull('category_id')
            ->exists();

        if ($hasUncategorized) {
            $keyboard[] = [[
                'text' => __('telegram.booking.uncategorized_button'),
                'callback_data' => 'tgbook:cat:uncat',
            ]];
        }

        $keyboard[] = [[
            'text' => __('telegram.booking.all_services_button'),
            'callback_data' => 'tgbook:cat:all',
        ]];

        $keyboard[] = [
            ['text' => __('telegram.booking.cancel_button'), 'callback_data' => 'tgbook:cancel'],
        ];

        $this->safeSendMessage($token, $chatId, __('telegram.booking.pick_category'), [
            'reply_markup' => ['inline_keyboard' => $keyboard],
        ]);
    }

    protected function sendServicePicker(Setting $setting, string $token, string $chatId, ?ServiceCategory $category): void
    {
        $servicesQuery = Service::query()
            ->where('user_id', $setting->user_id)
            ->orderBy('name');

        $session = $this->getSession($token, $chatId);
        $categoryMode = $category ? 'category' : null;

        if (! $category && (Arr::get($session, 'category_id') === 'uncat' || Arr::get($session, 'category_id') === 'uncategorized')) {
            $servicesQuery->whereNull('category_id');
            $categoryMode = 'uncat';
        } elseif ($category) {
            $servicesQuery->where('category_id', $category->id);
        }

        $services = $servicesQuery->get(['id', 'name', 'duration_min', 'base_price']);

        if ($services->isEmpty()) {
            $this->safeSendMessage($token, $chatId, __('telegram.booking.no_services_in_category'));
            $this->sendCategoryPicker($setting, $token, $chatId);
            return;
        }

        $keyboard = $services
            ->map(fn (Service $service) => [[
                'text' => $this->formatServiceButton($service),
                'callback_data' => 'tgbook:service:' . $service->id,
            ]])
            ->values()
            ->all();

        $keyboard[] = [
            ['text' => __('telegram.booking.back_categories_button'), 'callback_data' => 'tgbook:start'],
            ['text' => __('telegram.booking.cancel_button'), 'callback_data' => 'tgbook:cancel'],
        ];

        $titleKey = $categoryMode === 'uncat'
            ? 'telegram.booking.pick_service_uncategorized'
            : ($category ? 'telegram.booking.pick_service_in_category' : 'telegram.booking.pick_service');

        $this->safeSendMessage($token, $chatId, __($titleKey, [
            'category' => $category?->name,
        ]), [
            'reply_markup' => ['inline_keyboard' => $keyboard],
        ]);
    }

    protected function sendDatePicker(Setting $setting, string $token, string $chatId, Service $service): void
    {
        $master = $setting->user;
        if (! $master) {
            return;
        }

        $timezone = $master->timezone ?? config('app.timezone');
        $today = Carbon::now($timezone)->startOfDay();
        $rows = [];

        for ($i = 0; $i < self::LOOKAHEAD_DAYS; $i++) {
            $date = $today->copy()->addDays($i);
            $dateValue = $date->format('Y-m-d');
            $slots = $this->availableSlotsForDate($master, $setting, $service, $dateValue);

            if ($slots === []) {
                continue;
            }

            $rows[] = [[
                'text' => __('telegram.booking.date_option', [
                    'date' => $date->translatedFormat('d.m (D)'),
                    'slots' => count($slots),
                ]),
                'callback_data' => 'tgbook:date:' . $date->format('Ymd'),
            ]];
        }

        if ($rows === []) {
            $this->safeSendMessage($token, $chatId, __('telegram.booking.no_slots'));
            $this->sendStartMenu($setting, $token, $chatId, []);
            return;
        }

        $rows[] = [
            ['text' => __('telegram.booking.back_services_button'), 'callback_data' => 'tgbook:start'],
        ];

        $this->safeSendMessage($token, $chatId, __('telegram.booking.pick_date', [
            'service' => $service->name,
        ]), [
            'reply_markup' => ['inline_keyboard' => $rows],
        ]);
    }

    protected function sendTimePicker(Setting $setting, string $token, string $chatId, Service $service, string $date): void
    {
        $master = $setting->user;
        if (! $master) {
            return;
        }

        $slots = $this->availableSlotsForDate($master, $setting, $service, $date);
        if ($slots === []) {
            $this->safeSendMessage($token, $chatId, __('telegram.booking.no_slots_date'));
            $this->sendDatePicker($setting, $token, $chatId, $service);
            return;
        }

        $rows = [];
        foreach (array_chunk($slots, 3) as $chunk) {
            $rows[] = array_map(fn (string $slot) => [
                'text' => $slot,
                'callback_data' => 'tgbook:time:' . str_replace(':', '', $slot),
            ], $chunk);
        }

        $rows[] = [
            ['text' => __('telegram.booking.back_dates_button'), 'callback_data' => 'tgbook:service:' . $service->id],
        ];

        $formattedDate = Carbon::parse($date)->translatedFormat('d.m.Y');

        $this->safeSendMessage($token, $chatId, __('telegram.booking.pick_time', [
            'service' => $service->name,
            'date' => $formattedDate,
        ]), [
            'reply_markup' => ['inline_keyboard' => $rows],
        ]);
    }

    protected function sendConfirmation(string $token, string $chatId, Service $service, string $date, string $time, ?string $clientName): void
    {
        $dateRaw = Carbon::parse($date)->format('Ymd');
        $timeRaw = str_replace(':', '', $time);

        $text = __('telegram.booking.confirm_text', [
            'service' => $service->name,
            'date' => Carbon::parse($date)->translatedFormat('d.m.Y'),
            'time' => $time,
            'client' => $clientName ?: __('telegram.booking.unknown_client'),
        ]);

        $this->safeSendMessage($token, $chatId, $text, [
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => __('telegram.booking.confirm_button'), 'callback_data' => 'tgbook:confirm:' . $service->id . ':' . $dateRaw . ':' . $timeRaw],
                    ],
                    [
                        ['text' => __('telegram.booking.back_dates_button'), 'callback_data' => 'tgbook:service:' . $service->id],
                        ['text' => __('telegram.booking.cancel_button'), 'callback_data' => 'tgbook:cancel'],
                    ],
                ],
            ],
        ]);
    }

    protected function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    protected function formatServiceButton(Service $service): string
    {
        $name = $service->name ?: __('telegram.booking.unnamed_service');
        $duration = (int) ($service->duration_min ?? 0);
        $price = is_numeric($service->base_price) ? (float) $service->base_price : null;

        $parts = [$name];

        if ($price !== null) {
            $parts[] = number_format($price, 0, '.', ' ') . ' ' . __('telegram.booking.currency_short');
        }

        if ($duration > 0) {
            $parts[] = $duration . ' ' . __('telegram.booking.minutes_short');
        }

        return implode(' · ', $parts);
    }

    protected function sendSuccessMessage(string $token, string $chatId, int $orderId, string $serviceName, string $date, string $time): void
    {
        $this->safeSendMessage($token, $chatId, __('telegram.booking.success', [
            'order_id' => $orderId,
            'service' => $serviceName,
            'date' => Carbon::parse($date)->translatedFormat('d.m.Y'),
            'time' => $time,
        ]), [
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => __('telegram.booking.book_another_button'), 'callback_data' => 'tgbook:start'],
                    ],
                ],
            ],
        ]);
    }

    protected function createOrderFromSession(
        User $master,
        Setting $setting,
        Service $service,
        array $session,
        string $date,
        string $time
    ): Order {
        $timezone = $master->timezone ?? config('app.timezone');
        $scheduledAtLocal = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $time, $timezone);
        $scheduledAt = $scheduledAtLocal->copy()->timezone(config('app.timezone'));

        $client = $this->resolveTelegramClient($session, $date);
        $serviceDuration = (int) ($service->duration_min ?? 60);
        $servicePrice = (float) ($service->base_price ?? 0);

        $order = Order::query()->create([
            'master_id' => $master->id,
            'client_id' => $client->id,
            'services' => [[
                'id' => $service->id,
                'name' => $service->name,
                'price' => $servicePrice,
                'duration' => $serviceDuration,
            ]],
            'scheduled_at' => $scheduledAt,
            'duration_forecast' => $serviceDuration,
            'total_price' => $servicePrice,
            'status' => 'new',
            'source' => 'telegram_bot',
        ]);

        $this->orderService->scheduleStartReminder($order);
        $this->notifyMasterAboutOrder($master, $setting, $order, $client, $service, $scheduledAtLocal);

        return $order;
    }

    protected function resolveTelegramClient(array $session, string $date): User
    {
        $telegramUser = Arr::get($session, 'client', []);
        $telegramId = (string) Arr::get($telegramUser, 'id', Arr::get($session, 'chat_id', ''));
        $name = trim((string) Arr::get($telegramUser, 'name', ''));
        $phoneInput = (string) Arr::get($session, 'client_phone', '');
        $phoneInput = trim($phoneInput);

        $query = User::query();

        if ($telegramId !== '') {
            $query->where('telegram_id', $telegramId);
        } else {
            $query->whereRaw('1 = 0');
        }

        $client = $query->first();

        if (! $client) {
            $client = User::query()->create([
                'name' => $name !== '' ? $name : __('telegram.booking.default_client_name', [
                    'date' => Carbon::parse($date)->format('dm'),
                ]),
                'email' => null,
                'phone' => $phoneInput !== '' ? $this->normalizePhone($phoneInput) : null,
                'password' => Str::random(24),
            ]);

            if ($telegramId !== '') {
                $client->forceFill(['telegram_id' => $telegramId])->save();
            }
        } else {
            $client->forceFill([
                'name' => $name !== '' ? $name : $client->name,
                'telegram_id' => $telegramId !== '' ? $telegramId : $client->telegram_id,
            ])->save();

            if ($phoneInput !== '') {
                $client->forceFill(['phone' => $this->normalizePhone($phoneInput)])->save();
            }
        }

        if (! $client->phone) {
            // clients.phone is NOT NULL in schema; booking requires phone registration.
            throw new \RuntimeException('Client phone is required for Telegram booking.');
        }

        Client::query()->updateOrCreate(
            ['user_id' => $client->id],
            [
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
            ]
        );

        return $client;
    }

    protected function handleContactMessage(Setting $setting, string $token, string $chatId, array $fromUser, array $contact): void
    {
        $phone = trim((string) Arr::get($contact, 'phone_number', ''));
        if ($phone === '') {
            $this->safeSendMessage($token, $chatId, __('telegram.booking.phone_invalid'));
            return;
        }

        $normalized = $this->normalizePhone($phone);
        if ($normalized === '') {
            $this->safeSendMessage($token, $chatId, __('telegram.booking.phone_invalid'));
            return;
        }

        $this->persistClientPhone($fromUser, $normalized);

        $session = $this->getSession($token, $chatId);
        $session['client_phone'] = $normalized;
        $session['awaiting_phone'] = false;
        $this->putSession($token, $chatId, $session);

        $this->safeSendMessage($token, $chatId, __('telegram.booking.phone_saved'));

        $this->maybeCompletePendingBooking($setting, $token, $chatId, $session);
    }

    protected function handleManualPhoneEntry(Setting $setting, string $token, string $chatId, array $fromUser, string $text): void
    {
        $normalized = $this->normalizePhone($text);
        if ($normalized === '') {
            $this->safeSendMessage($token, $chatId, __('telegram.booking.phone_invalid'));
            return;
        }

        $this->persistClientPhone($fromUser, $normalized);

        $session = $this->getSession($token, $chatId);
        $session['client_phone'] = $normalized;
        $session['awaiting_phone'] = false;
        $this->putSession($token, $chatId, $session);

        $this->safeSendMessage($token, $chatId, __('telegram.booking.phone_saved'));

        $this->maybeCompletePendingBooking($setting, $token, $chatId, $session);
    }

    protected function askForPhone(Setting $setting, string $token, string $chatId, array $session, array $pendingBooking): void
    {
        $session['awaiting_phone'] = true;
        $session['pending_booking'] = $pendingBooking;
        $this->putSession($token, $chatId, $session);

        $this->safeSendMessage($token, $chatId, __('telegram.booking.phone_required_to_book'), [
            'reply_markup' => [
                'keyboard' => [
                    [
                        ['text' => __('telegram.booking.share_phone_button'), 'request_contact' => true],
                    ],
                ],
                'one_time_keyboard' => true,
                'resize_keyboard' => true,
            ],
        ]);
        $this->safeSendMessage($token, $chatId, __('telegram.booking.enter_phone_hint'));
    }

    protected function maybeCompletePendingBooking(Setting $setting, string $token, string $chatId, array $session): void
    {
        $pending = Arr::get($session, 'pending_booking');
        if (! is_array($pending)) {
            return;
        }

        $serviceId = (int) Arr::get($pending, 'service_id', 0);
        $date = (string) Arr::get($pending, 'date', '');
        $time = (string) Arr::get($pending, 'time', '');

        if ($serviceId <= 0 || $date === '' || $time === '') {
            return;
        }

        $master = $setting->user;
        if (! $master) {
            return;
        }

        $service = Service::query()->where('id', $serviceId)->where('user_id', $master->id)->first();
        if (! $service) {
            return;
        }

        $available = $this->availableSlotsForDate($master, $setting, $service, $date);
        if (! in_array($time, $available, true)) {
            $this->safeSendMessage($token, $chatId, __('telegram.booking.slot_no_longer_available'));
            return;
        }

        unset($session['pending_booking'], $session['awaiting_phone']);
        $this->putSession($token, $chatId, $session);

        $order = $this->createOrderFromSession($master, $setting, $service, $session, $date, $time);
        $this->forgetSession($token, $chatId);
        $this->sendSuccessMessage($token, $chatId, $order->id, $service->name, $date, $time);
    }

    protected function clientHasPhone(array $session): bool
    {
        $phone = trim((string) Arr::get($session, 'client_phone', ''));
        if ($phone !== '') {
            return true;
        }

        $client = $this->resolveClientUserByTelegram(Arr::get($session, 'client', []));

        return (bool) $client?->phone;
    }

    protected function persistClientPhone(array $fromUser, string $normalizedPhone): void
    {
        $client = $this->resolveClientUserByTelegram($fromUser);
        if ($client) {
            $client->forceFill(['phone' => $normalizedPhone])->save();
        }
    }

    protected function resolveClientUserByTelegram(array $fromUser): ?User
    {
        $telegramId = Arr::get($fromUser, 'id');
        if (! $telegramId) {
            return null;
        }

        return User::query()->where('telegram_id', (string) $telegramId)->first();
    }

    protected function normalizePhone(string $phone): string
    {
        // Keep compatible with existing OrderController normalization.
        $digits = preg_replace('/[^0-9]+/', '', $phone);

        if (! $digits) {
            return '';
        }

        if (strlen($digits) === 10) {
            $digits = '7' . $digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
            $digits = '7' . substr($digits, 1);
        }

        if (! str_starts_with($digits, '7') && ! str_starts_with($digits, '8')) {
            $digits = '7' . $digits;
        }

        return '+' . $digits;
    }

    protected function notifyMasterAboutOrder(
        User $master,
        Setting $setting,
        Order $order,
        User $client,
        Service $service,
        Carbon $scheduledAtLocal
    ): void {
        $title = __('telegram.notifications.order_title');
        $message = __('telegram.notifications.order_message', [
            'client' => $client->name ?: __('telegram.booking.unknown_client'),
            'service' => $service->name,
            'date' => $scheduledAtLocal->format('d.m.Y'),
            'time' => $scheduledAtLocal->format('H:i'),
        ]);

        $this->notifications->send($master->id, $title, $message, '/orders/' . $order->id);

        $prefs = is_array($setting->notification_prefs) ? $setting->notification_prefs : [];

        if (! empty($prefs['email']) && ! empty($master->email)) {
            $this->sendChannelNotificationSafe($setting, 'email', $master->email, $title, $message . "\n" . url('/orders/' . $order->id), $master->id);
        }

        if (! empty($prefs['sms']) && ! empty($master->phone)) {
            $this->sendChannelNotificationSafe($setting, 'sms', $master->phone, null, $message, $master->id);
        }

        if (! empty($prefs['telegram']) && ! empty($master->telegram_id)) {
            $this->sendChannelNotificationSafe($setting, 'telegram', $master->telegram_id, null, $message, $master->id);
        }
    }

    protected function sendChannelNotificationSafe(
        Setting $setting,
        string $channel,
        string $recipient,
        ?string $subject,
        string $content,
        int $masterId
    ): void {
        try {
            $this->channelSender->send($setting, $channel, $recipient, $subject, $content);
        } catch (Throwable $exception) {
            Log::warning('Failed to send booking notification channel.', [
                'master_id' => $masterId,
                'channel' => $channel,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    protected function availableSlotsForDate(User $master, Setting $setting, Service $service, string $date): array
    {
        $timezone = $master->timezone ?? config('app.timezone');
        $day = Carbon::parse($date, $timezone)->startOfDay();
        $dayKey = strtolower($day->format('D'));
        $serviceDuration = (int) ($service->duration_min ?? 60);

        $workHours = $setting->work_hours ?? [];
        $slots = collect(is_array($workHours) ? Arr::get($workHours, $dayKey, []) : [])
            ->filter(fn ($slot) => is_string($slot) && preg_match('/^\d{2}:\d{2}$/', $slot))
            ->values();

        if ($slots->isEmpty()) {
            return [];
        }

        $startDb = $day->copy()->timezone(config('app.timezone'));
        $endDb = $day->copy()->endOfDay()->timezone(config('app.timezone'));

        $orders = Order::query()
            ->where('master_id', $master->id)
            ->whereBetween('scheduled_at', [$startDb, $endDb])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->get(['scheduled_at', 'services']);

        $busy = $orders->map(function (Order $order) use ($timezone) {
            $start = $order->scheduled_at?->copy()->timezone($timezone);
            if (! $start) {
                return null;
            }

            $duration = collect($order->services ?? [])
                ->sum(fn ($item) => (int) Arr::get($item, 'duration', 0));
            $duration = $duration > 0 ? $duration : 60;

            return [
                'start' => $start,
                'end' => $start->copy()->addMinutes($duration),
            ];
        })->filter()->values();

        $now = Carbon::now($timezone);

        return $slots->filter(function (string $slot) use ($day, $serviceDuration, $busy, $now) {
            $candidateStart = Carbon::createFromFormat('Y-m-d H:i', $day->format('Y-m-d') . ' ' . $slot, $day->getTimezone());

            if ($candidateStart->lessThanOrEqualTo($now->copy()->addMinutes(5))) {
                return false;
            }

            $candidateEnd = $candidateStart->copy()->addMinutes($serviceDuration);

            foreach ($busy as $interval) {
                if ($candidateStart->lt($interval['end']) && $candidateEnd->gt($interval['start'])) {
                    return false;
                }
            }

            return true;
        })->values()->all();
    }

    protected function extractTelegramUser(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $firstName = trim((string) Arr::get($payload, 'first_name', ''));
        $lastName = trim((string) Arr::get($payload, 'last_name', ''));
        $fullName = trim($firstName . ' ' . $lastName);

        return [
            'id' => Arr::get($payload, 'id'),
            'name' => $fullName,
            'username' => Arr::get($payload, 'username'),
        ];
    }

    protected function resolveSessionService(array $session, int $masterId): ?Service
    {
        $serviceId = Arr::get($session, 'service_id');
        if (! is_numeric($serviceId)) {
            return null;
        }

        return Service::query()
            ->where('id', (int) $serviceId)
            ->where('user_id', $masterId)
            ->first();
    }

    protected function safeSendMessage(string $token, string $chatId, string $text, array $options = []): void
    {
        try {
            $this->telegramApi->sendMessage($token, $chatId, $text, $options);
        } catch (Throwable $exception) {
            Log::warning('Telegram sendMessage failed.', [
                'chat_id' => $chatId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    protected function getSession(string $token, string $chatId): array
    {
        $session = Cache::get($this->sessionKey($token, $chatId), []);

        return is_array($session) ? $session : [];
    }

    protected function putSession(string $token, string $chatId, array $session): void
    {
        Cache::put($this->sessionKey($token, $chatId), $session, now()->addMinutes(self::SESSION_TTL_MINUTES));
    }

    protected function forgetSession(string $token, string $chatId): void
    {
        Cache::forget($this->sessionKey($token, $chatId));
    }

    protected function offsetKey(string $token): string
    {
        return 'telegram:booking:offset:' . hash('sha256', $token);
    }

    protected function sessionKey(string $token, string $chatId): string
    {
        return 'telegram:booking:session:' . hash('sha256', $token) . ':' . $chatId;
    }
}

