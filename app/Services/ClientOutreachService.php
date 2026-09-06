<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes the message that asks a client to come back.
 *
 * This is the one place in the dashboard where a language model earns its keep.
 * Working out *who* has slipped past her rhythm is arithmetic over the booking
 * history: exact, instant and free, and a model would only do it worse. What a
 * master actually stalls on is the wording, so that is what gets generated, on a
 * click, with the facts already established.
 *
 * When the model is unavailable or the plan does not allow another generation,
 * a written fallback is returned rather than an error. A master who wanted a
 * message should never be left staring at an empty box.
 */
class ClientOutreachService
{
    /** Generations a month for accounts without a paid plan. */
    public const FREE_MONTHLY_LIMIT = 3;

    public function __construct(private readonly OpenAIService $openAI)
    {
    }

    /**
     * @return array{text: string, source: string, remaining: int|null}
     */
    public function draft(User $master, Client $card, array $context = []): array
    {
        $facts = $this->collectFacts($master, $card, $context);
        $paid = $this->hasPaidPlan($master);
        $used = $this->usedThisMonth($master);
        $allowed = $paid || $used < self::FREE_MONTHLY_LIMIT;

        if (! $allowed || ! $this->aiEnabled()) {
            return [
                'text' => $this->fallbackText($facts),
                'source' => $allowed ? 'template' : 'limit',
                'remaining' => $paid ? null : max(0, self::FREE_MONTHLY_LIMIT - $used),
            ];
        }

        $text = $this->generate($facts);

        if ($text === null) {
            return [
                'text' => $this->fallbackText($facts),
                'source' => 'template',
                'remaining' => $paid ? null : max(0, self::FREE_MONTHLY_LIMIT - $used),
            ];
        }

        if (! $paid) {
            $used = $this->recordUsage($master);
        }

        return [
            'text' => $text,
            'source' => 'ai',
            'remaining' => $paid ? null : max(0, self::FREE_MONTHLY_LIMIT - $used),
        ];
    }

    /**
     * Everything the wording should be built on, and nothing else. No prices, no
     * notes, no allergies: this text goes to the client, and the model has no
     * business seeing what the master wrote about her privately.
     */
    private function collectFacts(User $master, Client $card, array $context): array
    {
        $timezone = $master->timezone ?? config('app.timezone');
        $now = Carbon::now($timezone);

        $visits = Order::query()
            ->where('master_id', $master->id)
            ->where('client_id', $card->client_user_id)
            ->whereIn('status', ['completed', 'in_progress', 'confirmed'])
            ->where('scheduled_at', '<=', $now)
            ->orderByDesc('scheduled_at')
            ->limit(10)
            ->get(['services', 'scheduled_at']);

        $lastVisit = $visits->first()?->scheduled_at?->copy()->timezone($timezone);

        $usualService = $visits
            ->flatMap(fn (Order $order) => collect($order->services ?? [])->pluck('name'))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        return [
            'master_name' => $master->name,
            'client_name' => $card->name,
            'days_since' => $lastVisit ? (int) $lastVisit->copy()->startOfDay()->diffInDays($now->copy()->startOfDay()) : null,
            'visits' => $visits->count(),
            'usual_service' => $usualService,
            'free_slots' => collect(Arr::get($context, 'free_slots', []))->take(3)->values()->all(),
            'free_day' => Arr::get($context, 'free_day'),
        ];
    }

    private function generate(array $facts): ?string
    {
        $prompt = <<<'PROMPT'
Ты пишешь короткое сообщение от мастера бьюти-сферы её клиентке, чтобы позвать
её записаться снова.

Требования:
- Обращайся на «вы», по имени.
- Два-три предложения, не больше. Это сообщение в мессенджер, не письмо.
- Тёплый живой тон, без канцелярита и без рекламных штампов.
- Не выдумывай фактов: опирайся только на то, что дано в контексте.
- Не упоминай цены и скидки, если их нет в контексте.
- Если даны свободные окна, предложи их прямо, назвав время.
- Без эмодзи, без подписи, без темы письма.
- Верни только текст сообщения, ничего больше.
PROMPT;

        try {
            $response = $this->openAI->respond($prompt, $facts, [
                'max_tokens' => 220,
                'temperature' => 0.8,
            ]);

            $content = trim((string) Arr::get($response, 'content'));

            return $content !== '' ? $content : null;
        } catch (Throwable $exception) {
            Log::warning('Failed to draft a client outreach message.', [
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Plain, correct and slightly dull. It exists so the button always produces
     * something the master can send after a small edit.
     */
    private function fallbackText(array $facts): string
    {
        $name = $facts['client_name'] ?: 'Здравствуйте';
        $service = $facts['usual_service'];
        $slots = $facts['free_slots'];
        $day = $facts['free_day'];

        $lines = [$name . ', здравствуйте! Давно вас не было.'];

        // The service name is kept in quotes and in the nominative. Russian would
        // want it declined here, and no amount of string handling gets that right
        // for arbitrary names a master types in herself.
        $lines[] = $service
            ? 'В прошлый раз делали «' . $service . '», наверное, уже пора обновить.'
            : 'Хочу напомнить о себе, будет здорово снова вас увидеть.';

        if ($slots && $day) {
            $lines[] = $day . ' свободно ' . implode(' и ', array_slice($slots, 0, 2)) . '. Записать вас?';
        } else {
            $lines[] = 'Напишите, когда вам удобно, подберём время.';
        }

        return implode(' ', $lines);
    }

    private function hasPaidPlan(User $master): bool
    {
        return $master->plans()
            ->whereIn('name', ['pro', 'Pro', 'PRO', 'elite', 'Elite', 'ELITE'])
            ->where(function ($query) {
                $query->whereNull('plan_user.ends_at')->orWhere('plan_user.ends_at', '>', Carbon::now());
            })
            ->exists();
    }

    private function usageKey(User $master): string
    {
        return sprintf('outreach:usage:%d:%s', $master->id, Carbon::now()->format('Y-m'));
    }

    private function usedThisMonth(User $master): int
    {
        return (int) Cache::get($this->usageKey($master), 0);
    }

    private function recordUsage(User $master): int
    {
        $key = $this->usageKey($master);
        $used = $this->usedThisMonth($master) + 1;

        Cache::put($key, $used, Carbon::now()->endOfMonth()->addDay());

        return $used;
    }

    private function aiEnabled(): bool
    {
        return filled(config('openai.api_key'));
    }
}
