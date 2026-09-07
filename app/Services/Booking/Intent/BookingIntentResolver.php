<?php

namespace App\Services\Booking\Intent;

use App\Models\Service;
use App\Models\Setting;
use App\Services\Ai\AiGateway;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\BookingConflictService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Turns «марина завтра ногти в 3 дня» into a filled-in create form.
 *
 * The rules run first and always. The model is asked only where they were
 * unsure, and only ever to choose from lists this master owns — so a provider
 * being off, down, cooling or out of budget costs nothing: the answer is
 * simply the one the rules already produced. There is no second code path
 * to keep in step with the first.
 */
class BookingIntentResolver
{
    /** Beyond this many candidates a chooser is worse than no chooser. */
    private const MAX_CHOICE_OPTIONS = 6;

    public function __construct(
        private readonly BookingPhraseParser $parser,
        private readonly AiGateway $ai,
        private readonly AvailabilityService $availability,
        private readonly BookingConflictService $conflicts,
    ) {
    }

    /**
     * @param  Collection<int, Service>  $services  the master's whole price list
     * @param  callable(string): Collection  $searchClients  OrderController::searchSelectableClients
     */
    public function resolve(
        string $text,
        int $masterId,
        Collection $services,
        callable $searchClients,
        CarbonImmutable $now,
        ?CarbonImmutable $anchorDay = null,
        ?Setting $setting = null,
        ?string $timezone = null,
        bool $paidPlan = false,
    ): array {
        $parsed = $this->parser->parse($text, $now, $anchorDay);

        $serviceMatches = $this->matchServices($parsed->residueTokens(), $services);
        $clientMatches = $this->matchClients($parsed, $searchClients);

        $engine = 'rules';
        $provider = null;
        $callsLeft = null;

        if ($this->shouldAskModel($parsed, $serviceMatches, $clientMatches)) {
            $callsLeft = $this->callsLeft($masterId, $paidPlan);

            if ($callsLeft > 0) {
                $answer = $this->askModel($parsed, $services, $serviceMatches, $clientMatches, $masterId);

                if ($answer !== null) {
                    [$serviceMatches, $clientMatches] = $this->applyAnswer(
                        $answer,
                        $services,
                        $serviceMatches,
                        $clientMatches,
                    );
                    $engine = 'ai';
                    $provider = $this->ai->lastProvider();
                    $callsLeft = $this->spendCall($masterId, $paidPlan);
                }
            }
        }

        return $this->assemble(
            $text, $parsed, $serviceMatches, $clientMatches,
            $masterId, $setting, $timezone, $engine, $provider, $callsLeft,
        );
    }

    // ---------------------------------------------------------------- services

    /**
     * @param  string[]  $tokens
     * @param  Collection<int, Service>  $services
     * @return array{matched: Collection<int, Service>, query: ?string}
     */
    private function matchServices(array $tokens, Collection $services): array
    {
        foreach ($tokens as $token) {
            $matched = $services->filter(fn (Service $service) => $this->serviceMatchesToken($service, $token));

            if ($matched->isNotEmpty()) {
                return ['matched' => $matched->values(), 'query' => $token];
            }
        }

        return ['matched' => collect(), 'query' => null];
    }

    private function serviceMatchesToken(Service $service, string $token): bool
    {
        $name = Str::lower((string) $service->name);

        if ($name === $token || str_contains($name, $token)) {
            return true;
        }

        // No stemmer here, and levenshtein()/similar_text() count bytes, which
        // is meaningless on Cyrillic. Four characters is where the Russian case
        // endings start: «ногти», «ногтей» and «ногтя» share «ногт» and nothing
        // shorter would separate them from «нога».
        $stem = mb_substr($token, 0, 4);

        if (mb_strlen($token) < 4) {
            return false;
        }

        foreach (preg_split('/[^\p{L}\p{N}]+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            if (mb_strlen($word) >= 4 && mb_substr($word, 0, 4) === $stem) {
                return true;
            }
        }

        return false;
    }

    // ----------------------------------------------------------------- clients

    /**
     * @return array{matched: Collection<int, array>, query: ?string}
     */
    private function matchClients(ParsedPhrase $parsed, callable $searchClients): array
    {
        if ($parsed->phone !== null) {
            $found = $searchClients($parsed->phone);

            if ($found->isNotEmpty()) {
                // A phone identifies one person by construction; no chooser.
                return ['matched' => collect([$found->first()]), 'query' => null];
            }
        }

        foreach ($parsed->residueTokens() as $token) {
            $found = $this->searchBothCases($searchClients, $token);

            if ($found->isNotEmpty()) {
                return ['matched' => $found, 'query' => Str::ucfirst($token)];
            }
        }

        $name = $parsed->residueTokens()[0] ?? null;

        return ['matched' => collect(), 'query' => $name ? Str::ucfirst($name) : null];
    }

    /**
     * The search runs `like` and the database is Postgres, where that is case
     * sensitive. A dictated «марина» would never find «Марина».
     */
    private function searchBothCases(callable $searchClients, string $token): Collection
    {
        $found = $searchClients($token);
        $capitalised = Str::ucfirst($token);

        if ($capitalised !== $token) {
            $seen = $found->map(fn (array $item) => $this->clientKey($item))->all();

            foreach ($searchClients($capitalised) as $item) {
                if (! in_array($this->clientKey($item), $seen, true)) {
                    $found->push($item);
                }
            }
        }

        return $found->values();
    }

    private function clientKey(array $client): string
    {
        return ($client['id'] ?? '') . '|' . preg_replace('/\D/', '', (string) ($client['phone'] ?? ''));
    }

    // ------------------------------------------------------------------- model

    /**
     * @param  array{matched: Collection, query: ?string}  $serviceMatches
     * @param  array{matched: Collection, query: ?string}  $clientMatches
     */
    private function shouldAskModel(ParsedPhrase $parsed, array $serviceMatches, array $clientMatches): bool
    {
        if ($parsed->residueTokens() === []) {
            return false;
        }

        $serviceSettled = $serviceMatches['matched']->count() === 1;
        $clientSettled = $clientMatches['matched']->count() === 1;

        return ! $serviceSettled || ! $clientSettled;
    }

    private function askModel(
        ParsedPhrase $parsed,
        Collection $services,
        array $serviceMatches,
        array $clientMatches,
        int $masterId,
    ): ?array {
        $shortlist = $serviceMatches['matched']->isNotEmpty()
            ? $serviceMatches['matched']
            : $services->take(12);

        $clients = $clientMatches['matched']->take(8);

        $key = 'orders:intent:' . $masterId . ':' . sha1($parsed->residue . '|' . $this->catalogSignature($services));

        $answer = Cache::remember($key, now()->addMinutes(30), function () use ($parsed, $shortlist, $clients) {
            return $this->ai->json(
                'booking_intent',
                $this->prompt(),
                $this->context($parsed, $shortlist, $clients),
                $this->bookingIntentSchema(),
                [
                    'temperature' => 0,
                    'max_tokens' => 300,
                    'timeout' => (int) config('ai.local.sync_timeout', 20),
                    'local_context' => $this->localContext($parsed, $shortlist, $clients),
                ],
            ) ?? ['understood' => false];
        });

        return ($answer['understood'] ?? false) === true ? $answer : null;
    }

    private function prompt(): string
    {
        return <<<'PROMPT'
        Ты помогаешь мастеру бьюти-салона разобрать фразу о новой записи.
        Дату и время уже разобрала программа — про время НИЧЕГО не определяй.

        Сделай только две вещи.

        1. Клиентка. Если в списке clients есть однозначное совпадение — верни его id в client_id.
        Если подходят несколько — перечисли их id в client_candidate_ids, а client_id оставь пустым.
        Если подходящих нет — оба поля оставь пустыми, а имя из фразы запиши в client_query
        в именительном падеже: «катю» → «Катя», «марине» → «Марина».

        2. Услуги. Верни в service_ids только id из списка services.
        Если фраза называет группу («ногти», «брови», «ресницы») — перечисли все подходящие id.
        Если ничего не подходит — верни пустой массив, а слово из фразы положи в service_query.

        Пожелание, которое не имя, не услуга и не время («после работы», «со своим гелем»),
        положи в note. Иначе note оставь пустым.

        Никогда не выдумывай id, которых нет в списках.
        Если фразу разобрать невозможно — верни understood = false.
        PROMPT;
    }

    private function context(ParsedPhrase $parsed, Collection $services, Collection $clients): array
    {
        return [
            'phrase' => $parsed->residue,
            'services' => $services->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'duration' => (int) $service->duration_min,
            ])->values()->all(),
            'clients' => $clients->map(fn (array $client) => [
                'id' => $client['id'],
                'name' => $client['name'],
            ])->values()->all(),
        ];
    }

    /**
     * The local provider is given prose, not JSON, and refuses anything over
     * ai.local.max_prompt_chars without a word in the log — so it gets the same
     * decision with everything it does not need to make it stripped out.
     */
    private function localContext(ParsedPhrase $parsed, Collection $services, Collection $clients): array
    {
        return [
            'фраза' => $parsed->residue,
            'услуги' => $services->map(fn (Service $service) => $service->id . '=' . $service->name)->values()->all(),
            'клиенты' => $clients
                ->filter(fn (array $client) => ! empty($client['id']))
                ->map(fn (array $client) => $client['id'] . '=' . $client['name'])
                ->values()
                ->all(),
        ];
    }

    /**
     * `required` is exactly one key on purpose: AiGateway drops a local answer
     * that misses any of them, and a phrase naming no service would throw away
     * a perfectly good client match.
     */
    private function bookingIntentSchema(): array
    {
        $id = ['oneOf' => [['type' => 'integer'], ['type' => 'string']]];
        $text = ['oneOf' => [['type' => 'string'], ['type' => 'null']]];

        return [
            'name' => 'booking_intent',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'understood' => ['type' => 'boolean'],
                    'client_id' => ['oneOf' => [['type' => 'integer'], ['type' => 'string'], ['type' => 'null']]],
                    'client_candidate_ids' => ['type' => 'array', 'items' => $id],
                    'client_query' => $text,
                    'service_ids' => ['type' => 'array', 'items' => $id],
                    'service_query' => $text,
                    'note' => $text,
                    'confidence' => ['oneOf' => [['type' => 'number'], ['type' => 'null']]],
                ],
                'required' => ['understood'],
            ],
        ];
    }

    /**
     * Every id is checked back against what this master owns. A model that
     * invents one, or reaches for another master's, is simply ignored.
     */
    private function applyAnswer(array $answer, Collection $services, array $serviceMatches, array $clientMatches): array
    {
        $ids = array_filter(array_map(
            static fn ($id) => is_numeric($id) ? (int) $id : null,
            Arr::wrap($answer['service_ids'] ?? []),
        ));

        $chosen = $services->whereIn('id', $ids)->values();

        if ($chosen->isNotEmpty()) {
            $serviceMatches = ['matched' => $chosen, 'query' => $serviceMatches['query']];
        }

        $clientId = $answer['client_id'] ?? null;
        $clientId = is_numeric($clientId) ? (int) $clientId : null;

        if ($clientId !== null) {
            $picked = $clientMatches['matched']->firstWhere('id', $clientId);

            if ($picked !== null) {
                $clientMatches = ['matched' => collect([$picked]), 'query' => $clientMatches['query']];
            }
        }

        $query = trim((string) ($answer['client_query'] ?? ''));

        if ($query !== '' && $clientMatches['matched']->isEmpty()) {
            $clientMatches['query'] = $query;
        }

        $clientMatches['note'] = trim((string) ($answer['note'] ?? '')) ?: null;

        return [$serviceMatches, $clientMatches];
    }

    // ---------------------------------------------------------------- assembly

    private function assemble(
        string $text,
        ParsedPhrase $parsed,
        array $serviceMatches,
        array $clientMatches,
        int $masterId,
        ?Setting $setting,
        ?string $timezone,
        string $engine,
        ?string $provider,
        ?int $callsLeft,
    ): array {
        $services = $serviceMatches['matched'];
        $clients = $clientMatches['matched'];

        $filledServices = $services->count() === 1 ? $services : collect();
        $choices = [];
        $unresolved = [];

        $client = null;
        $newClient = null;

        if ($clients->count() === 1 && ! empty($clients->first()['id'])) {
            $client = $clients->first();
        } elseif ($clients->count() === 1) {
            // A card with no account: setCreateClientSelection() reads a missing
            // id as "nobody" and would blank the form it had just filled in.
            $newClient = [
                'name' => $clients->first()['name'],
                'phone' => $parsed->phone ?? $clients->first()['phone'],
            ];
        } elseif ($clients->count() > 1) {
            $choices[] = $this->clientChoice($clients, $clientMatches['query']);
        } else {
            $newClient = ['name' => $clientMatches['query'], 'phone' => $parsed->phone];
        }

        if ($newClient !== null && empty($newClient['phone'])) {
            $unresolved[] = 'client_phone';
        }

        if ($services->count() > 1 && $services->count() <= self::MAX_CHOICE_OPTIONS) {
            $choices[] = $this->serviceChoice($services, $serviceMatches['query']);
        } elseif ($services->count() > self::MAX_CHOICE_OPTIONS) {
            $unresolved[] = 'services';
        }

        $duration = $parsed->durationMinutes
            ?? ($filledServices->sum(fn (Service $service) => (int) $service->duration_min) ?: null);

        return [
            'text' => $text,
            'engine' => $engine,
            'provider' => $provider,
            'filled' => [
                'scheduled_at' => $parsed->scheduledAtString(),
                'client' => $client,
                'new_client' => $newClient !== null && ($newClient['name'] || $newClient['phone']) ? $newClient : null,
                'services' => $filledServices->map(fn (Service $service) => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => (float) $service->base_price,
                    'duration' => (int) $service->duration_min,
                ])->values()->all(),
                'duration' => $parsed->durationMinutes,
                'total_price' => $parsed->price,
                'note' => $clientMatches['note'] ?? null,
            ],
            'choices' => $choices,
            'unresolved' => $unresolved,
            'availability' => $this->availability($parsed, $masterId, $duration, $setting, $timezone),
            'meta' => ['ai_calls_left' => $callsLeft],
        ];
    }

    private function clientChoice(Collection $clients, ?string $query): array
    {
        $options = $clients->take(self::MAX_CHOICE_OPTIONS)->map(fn (array $client) => [
            'value' => $client['id'],
            'label' => $client['name'] ?: 'Без имени',
            'meta' => trim(($client['phone'] ?? '') . ($client['last_visit_at_formatted'] ? ' · была ' . $client['last_visit_at_formatted'] : '')),
            'payload' => $client,
        ])->values()->all();

        // She may well have a third client with that name who has never been in.
        $options[] = [
            'value' => 'new',
            'label' => $query ? 'Новая клиентка «' . $query . '»' : 'Новая клиентка',
            'meta' => 'создать',
            'payload' => ['name' => $query, 'phone' => null],
        ];

        return [
            'field' => 'client',
            'question' => $query ? 'Какая ' . $query . '?' : 'Какая клиентка?',
            'multiple' => false,
            'options' => $options,
        ];
    }

    private function serviceChoice(Collection $services, ?string $query): array
    {
        return [
            'field' => 'services',
            'question' => $query ? 'Какие «' . $query . '»?' : 'Какие услуги?',
            'multiple' => true,
            'options' => $services->map(fn (Service $service) => [
                'value' => $service->id,
                'label' => $service->name,
                'meta' => (int) $service->duration_min . ' мин · ' . number_format((float) $service->base_price, 0, ',', ' ') . ' ₽',
                'payload' => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => (float) $service->base_price,
                    'duration' => (int) $service->duration_min,
                ],
            ])->values()->all(),
        ];
    }

    /**
     * Advisory only. Masters double-book on purpose — a quick touch-up inside a
     * long service — so this never blocks the form, it only warns.
     */
    private function availability(
        ParsedPhrase $parsed,
        int $masterId,
        ?int $duration,
        ?Setting $setting,
        ?string $timezone,
    ): array {
        $empty = ['requested' => null, 'status' => 'unknown', 'message' => null, 'suggestions' => []];

        if ($parsed->scheduledAt === null) {
            return $empty;
        }

        $requested = $parsed->scheduledAtString();
        $minutes = $duration ?: 60;

        try {
            $conflict = $this->conflicts->detectConflict(
                $masterId,
                Carbon::parse($requested, $timezone ?: config('app.timezone')),
                $minutes,
            );
        } catch (Throwable) {
            return array_merge($empty, ['requested' => $requested]);
        }

        if ($conflict === null) {
            return ['requested' => $requested, 'status' => 'free', 'message' => null, 'suggestions' => []];
        }

        $who = $conflict['client_name'] ?? null;
        $at = $parsed->scheduledAt->format('H:i');
        $suggestions = $this->nearestSlots($parsed, $masterId, $minutes, $setting, $timezone);

        $message = 'В ' . $at . ' занято' . ($who ? ' — ' . $who : '') . '.';

        if ($suggestions !== []) {
            $message .= ' Ближайшее свободное ' . $suggestions[0] . '.';
        }

        return [
            'requested' => $requested,
            'status' => 'busy',
            'message' => $message,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * @return string[]
     */
    private function nearestSlots(
        ParsedPhrase $parsed,
        int $masterId,
        int $minutes,
        ?Setting $setting,
        ?string $timezone,
    ): array {
        try {
            $slots = $this->availability->availableSlotsForDate(
                $masterId,
                null,
                $parsed->scheduledAt->toDateString(),
                $setting,
                $timezone,
                $minutes,
            );
        } catch (Throwable) {
            return [];
        }

        // The schedule holds the opening anchors a master typed in settings, so
        // an empty list means "she never set her week up", not "the day is full".
        // Telling a brand-new master her week is closed would be a lie.
        if ($slots === []) {
            return [];
        }

        $after = $parsed->scheduledAt->format('H:i');

        $later = array_values(array_filter($slots, static fn (string $slot) => $slot > $after));

        return array_slice($later !== [] ? $later : $slots, 0, 2);
    }

    // ------------------------------------------------------------------ budget

    private function budgetKey(int $masterId): string
    {
        return 'orders:intent:ai:' . $masterId . ':' . now()->toDateString();
    }

    private function dailyLimit(bool $paidPlan): int
    {
        return (int) ($paidPlan
            ? config('ai.booking_intent.daily_ai_calls_pro', 400)
            : config('ai.booking_intent.daily_ai_calls_free', 40));
    }

    private function callsLeft(int $masterId, bool $paidPlan): int
    {
        return max(0, $this->dailyLimit($paidPlan) - (int) Cache::get($this->budgetKey($masterId), 0));
    }

    private function spendCall(int $masterId, bool $paidPlan): int
    {
        $key = $this->budgetKey($masterId);
        $used = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $used, now()->endOfDay());

        return max(0, $this->dailyLimit($paidPlan) - $used);
    }

    private function catalogSignature(Collection $services): string
    {
        return sha1($services
            ->map(fn (Service $service) => $service->id . ':' . ($service->updated_at?->timestamp ?? 0))
            ->implode('|'));
    }
}
