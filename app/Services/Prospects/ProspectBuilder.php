<?php

namespace App\Services\Prospects;

use App\Models\Landing;
use App\Models\Order;
use App\Models\Prospect;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\User;
use App\Services\Landing\LandingContent;
use App\Services\Landing\LandingImageStore;
use App\Services\Landing\TemplateRegistry;
use App\Services\ScheduleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Builds a whole account from a scraped group in one go: the user with a
 * technical email, the catalog, a working schedule and a booking page with
 * the group's texts and photos. Building the same group again rebuilds the
 * catalog and the page in place while nobody has claimed the account yet.
 *
 * Payload (what the scraper or the zip's prospect.json sends):
 *   channel, external_id, url, title, description, address, phone,
 *   booking {service, url}, services [{name, price, duration, category}],
 *   texts [string], avatar, cover, photos [ref], template
 * A photo reference is an https URL or a path inside the zip.
 */
class ProspectBuilder
{
    private const WORK_DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

    public const OUTREACH_LAYOUT = 'price-list';

    /** Hero first, then the works, then whatever else the template shows. */
    private const PHOTO_SLOTS = [
        'hero_image_1', 'work_image_1', 'work_image_2', 'work_image_3', 'work_image_4',
        'work_image_5', 'work_image_6', 'about_image', 'hero_image_2', 'hero_image_3',
        'extra_image_1', 'extra_image_2', 'extra_image_3',
    ];

    private const NICHE_WORDS = [
        'barber' => ['барбер', 'barber', 'мужск', 'бород'],
        'lashes' => ['ресниц', 'lash'],
        'brows' => ['бров', 'brow', 'перманент'],
        'nails' => ['маникюр', 'педикюр', 'ногт', 'nail', 'гель-лак', 'гель лак'],
        'hair' => ['волос', 'стрижк', 'окрашиван', 'парикмах', 'укладк', 'hair', 'кератин'],
        'cosmetology' => ['косметолог', 'чистка лица', 'пилинг', 'уход за лицом', 'инъекц'],
        'makeup' => ['визаж', 'макияж', 'makeup', 'make up'],
        'spa' => ['массаж', 'spa', 'спа', 'шугаринг', 'депиляц', 'эпиляц', 'обертыван'],
    ];

    public function __construct(
        private readonly ProspectServiceParser $parser,
        private readonly ProspectPhotos $photos,
        private readonly TemplateRegistry $registry,
        private readonly LandingContent $content,
        private readonly LandingImageStore $images,
        private readonly ScheduleService $schedule,
    ) {
    }

    public function build(array $payload, ?User $actor = null, ?string $baseDir = null): Prospect
    {
        $channel = (string) ($payload['channel'] ?? 'vk');
        $externalId = Str::lower(trim((string) $payload['external_id']));

        $prospect = Prospect::query()->where('channel', $channel)->where('external_id', $externalId)->first();

        if ($prospect && ! $prospect->isPending() && $prospect->user_id) {
            throw new HttpException(409, 'Этот аккаунт мастер уже забрал себе, пересобрать его нельзя.');
        }

        $niche = $this->detectNiche($payload);
        $catalog = $this->parser->parse(
            (array) ($payload['services'] ?? []),
            array_merge([(string) ($payload['description'] ?? '')], (array) ($payload['texts'] ?? [])),
            $niche,
        );

        [$prospect, $landing] = DB::transaction(function () use ($prospect, $payload, $channel, $externalId, $actor, $niche, $catalog) {
            $prospect ??= new Prospect([
                'channel' => $channel,
                'external_id' => $externalId,
                'created_by' => $actor?->id,
            ]);

            $user = $prospect->user ?? $this->createUser($payload, $externalId, $prospect);

            $this->wipe($user);
            $this->storeCatalog($user, $catalog['services']);
            $this->storeSchedule($user, $payload);
            $landing = $this->createLanding($user, $payload, $externalId, $niche);

            $prospect->fill([
                'user_id' => $user->id,
                'landing_id' => $landing->id,
                'url' => $payload['url'] ?? null,
                'title' => Str::limit((string) $payload['title'], 250, ''),
                'booking_service' => data_get($payload, 'booking.service') ?: null,
                'status' => $prospect->status === Prospect::STATUS_SENT ? Prospect::STATUS_SENT : Prospect::STATUS_BUILT,
                'services_source' => $catalog['source'],
                'source' => $this->trimSource($payload),
            ]);
            $prospect->claim_token ??= Str::random(48);
            $prospect->save();

            return [$prospect, $landing];
        });

        try {
            $this->attachPhotos($landing, $payload, $baseDir);
        } finally {
            $this->photos->cleanup();
        }

        return $prospect->fresh(['user', 'landing']);
    }

    /** Removes the account, its page and its photos. */
    public function destroy(Prospect $prospect): void
    {
        $user = $prospect->user;

        if (! $user) {
            return;
        }

        $landings = Landing::query()->where('user_id', $user->id)->get();

        // Landings and orders point at the user without a cascade, so they go first.
        DB::transaction(function () use ($user, $landings) {
            Order::query()->where('master_id', $user->id)->delete();
            Landing::query()->whereKey($landings->modelKeys())->delete();
            $user->tokens()->delete();
            $user->delete();
        });

        $landings->each(fn (Landing $landing) => $this->images->removeAll($landing));
    }

    public function detectNiche(array $payload): string
    {
        $haystack = Str::lower(implode(' ', array_filter([
            (string) ($payload['title'] ?? ''),
            (string) ($payload['description'] ?? ''),
            implode(' ', array_map(fn ($s) => is_array($s) ? (string) ($s['name'] ?? '') : '', (array) ($payload['services'] ?? []))),
        ])));

        $best = 'nails';
        $bestScore = 0;

        foreach (self::NICHE_WORDS as $niche => $words) {
            $score = 0;
            foreach ($words as $word) {
                $score += substr_count($haystack, $word);
            }
            // The title says what the master is; the price list mentions everything.
            foreach ($words as $word) {
                if (str_contains(Str::lower((string) ($payload['title'] ?? '')), $word)) {
                    $score += 5;
                }
            }

            if ($score > $bestScore) {
                $best = $niche;
                $bestScore = $score;
            }
        }

        return $best;
    }

    /** A booking layout for the niche, preferring the one with the most photo slots to fill. */
    public function pickLayout(string $niche, ?string $requested = null): string
    {
        if ($requested && $this->registry->layout($requested)) {
            return $requested;
        }

        // Made for exactly this: a group's price list by category and its own photos, no stock.
        if ($this->registry->layout(self::OUTREACH_LAYOUT)) {
            return self::OUTREACH_LAYOUT;
        }

        $category = $niche === 'lashes' ? 'brows' : $niche;

        $candidates = $this->registry->layouts()->filter(fn (array $layout) => in_array('booking', $layout['purposes'], true)
            && in_array($category, $layout['categories'], true));

        if ($candidates->isEmpty()) {
            return TemplateRegistry::DEFAULT_LAYOUT;
        }

        return $candidates
            ->sortBy(fn (array $layout) => sprintf('%03d-%s', 100 - count(array_intersect($layout['fields'], self::PHOTO_SLOTS)), $layout['slug']))
            ->first()['slug'];
    }

    private function createUser(array $payload, string $externalId, Prospect $prospect): User
    {
        $local = Str::slug($externalId) ?: 'master';
        $password = Str::password(10, symbols: false);

        $user = new User([
            'name' => Str::limit(trim((string) $payload['title']), 120, '') ?: 'Мастер',
            'email' => Str::limit($local, 40, '') . '-' . Str::lower(Str::random(5)) . '@' . Prospect::EMAIL_DOMAIN,
            'phone' => $this->phone($payload['phone'] ?? null),
            'password' => $password,
            'status' => User::STATUS_ACTIVE,
        ]);
        $user->save();

        $prospect->password = $password;

        return $user;
    }

    private function wipe(User $user): void
    {
        Service::query()->where('user_id', $user->id)->delete();
        ServiceCategory::query()->where('user_id', $user->id)->delete();

        Landing::query()->where('user_id', $user->id)->get()->each(function (Landing $landing) {
            $this->images->removeAll($landing);
            $landing->delete();
        });
    }

    private function storeCatalog(User $user, array $services): void
    {
        $categories = [];

        foreach ($services as $service) {
            $categoryId = $categories[$service['category']] ??= ServiceCategory::create([
                'user_id' => $user->id,
                'name' => $service['category'],
            ])->id;

            Service::create([
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'name' => $service['name'],
                'base_price' => $service['price'] ?? 0,
                'duration_min' => $service['duration'],
            ]);
        }
    }

    private function storeSchedule(User $user, array $payload): void
    {
        $slots = [];
        for ($hour = 10; $hour < 20; $hour++) {
            $slots[] = sprintf('%02d:00', $hour);
        }

        $rules = $this->schedule->normalizeRules(null, self::WORK_DAYS, array_fill_keys(self::WORK_DAYS, $slots));
        $legacy = $this->schedule->deriveLegacyFields($rules);

        Setting::updateOrCreate(['user_id' => $user->id], [
            'schedule_rules' => $rules,
            'work_days' => $legacy['work_days'],
            'work_hours' => $legacy['work_hours'],
            'address' => $this->clip($this->firstLine($payload['address'] ?? null), 255),
        ]);
    }

    private function createLanding(User $user, array $payload, string $externalId, string $niche): Landing
    {
        $layout = $this->pickLayout($niche, $payload['template'] ?? null);
        $title = Str::limit($this->cleanTitle(trim((string) $payload['title'])), 250, '');

        $landing = Landing::create([
            'user_id' => $user->id,
            'title' => $title,
            'type' => 'general',
            'landing' => $this->registry->templateFor($layout, 'general'),
            'slug' => $this->slug($externalId, $title),
            'settings' => array_filter([
                'primary_color' => 'indigo',
                'background_type' => 'preset',
                'show_all_services' => true,
                'phone' => $this->phone($payload['phone'] ?? null),
                'address' => $this->clip($this->firstLine($payload['address'] ?? null), 255),
            ], fn ($v) => $v !== null),
            'is_active' => true,
        ]);

        $texts = [
            'hero_title' => $this->clip($title, 255),
            'hero_text' => $this->intro($payload['description'] ?? null),
            'master_bio' => $this->clip($this->intro($payload['description'] ?? null), 500),
        ];

        $allowed = $this->content->allowedKeys($landing);
        $changes = [];

        foreach ($texts as $key => $value) {
            if ($value && in_array($key, $allowed, true)) {
                $changes[] = ['key' => $key, 'value' => $value];
            }
        }

        if ($changes !== []) {
            try {
                $this->content->apply($landing, $changes);
            } catch (\Throwable $exception) {
                Log::info('Prospect landing texts skipped.', ['landing' => $landing->id, 'error' => $exception->getMessage()]);
            }
        }

        return $landing->fresh();
    }

    private function attachPhotos(Landing $landing, array $payload, ?string $baseDir): void
    {
        $allowed = $this->content->allowedKeys($landing);
        $max = (int) config('prospects.max_photos', 12);

        if (in_array('master_photo', $allowed, true) && ($file = $this->photos->resolve($payload['avatar'] ?? null, $baseDir))) {
            $this->storeImage($landing, 'master_photo', $file);
        }

        $refs = array_slice(array_values((array) ($payload['photos'] ?? [])), 0, $max);

        if ($refs === [] && ! empty($payload['cover'])) {
            $refs = [$payload['cover']];
        }

        $slots = array_values(array_filter(self::PHOTO_SLOTS, fn ($slot) => in_array($slot, $allowed, true)));

        foreach ($slots as $slot) {
            $file = null;

            while ($file === null && $refs !== []) {
                $file = $this->photos->resolve(array_shift($refs), $baseDir);
            }

            if ($file === null) {
                break;
            }

            $this->storeImage($landing, $slot, $file);
        }
    }

    private function storeImage(Landing $landing, string $key, $file): void
    {
        try {
            $this->images->store($landing->refresh(), $key, $file);
        } catch (\Throwable $exception) {
            Log::info('Prospect photo not stored.', ['landing' => $landing->id, 'key' => $key, 'error' => $exception->getMessage()]);
        }
    }

    private function slug(string $externalId, string $title): string
    {
        // club123 / public123 say nothing; the group's name reads better in a link.
        $base = preg_match('/^(club|public|event)\d+$/', $externalId) ? Str::slug($title) : Str::slug($externalId);
        $base = Str::limit($base !== '' ? $base : Str::lower(Str::random(6)), 60, '');
        $candidate = $base;
        $suffix = 1;

        while (Landing::query()->where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $suffix++;
        }

        return $candidate;
    }

    /** The first paragraph of the description: what the master says about herself. */
    private function intro(mixed $description): ?string
    {
        $text = trim((string) $description);

        if ($text === '') {
            return null;
        }

        // Group descriptions open with "write to vk.me/..." or the address: those
        // are for VK, not for the page. The first paragraph about the work wins.
        foreach (preg_split('/\n\s*\n/u', $text) ?: [] as $paragraph) {
            $paragraph = trim(preg_replace('/^[^\p{L}\p{N}«"]+/u', '', trim($paragraph)) ?? '');
            $paragraph = trim(preg_replace('/[ \t]+/u', ' ', $paragraph) ?? $paragraph);
            $low = mb_strtolower($paragraph);

            if (mb_strlen($paragraph) < 15
                || preg_match('~https?://|vk\.(me|com|ru|cc)/|t\.me/|wa\.me/|@[a-z0-9_]~u', $low)
                || preg_match('/пиши|пишит|запис[ыа]|звонит|телефон|директ|в лс|скидк|акци/u', $low)
                || preg_match('/ул\.|улица|проезд|проспект|пр-т|шоссе|переулок|бульвар|\d+к\d/u', $low)) {
                continue;
            }

            return $this->clip($paragraph, 300);
        }

        return null;
    }

    /** "Маникюр| Марьино | Beauty Zone" reads as a list in VK and as noise on a page. */
    private function cleanTitle(string $title): string
    {
        $parts = array_values(array_filter(array_map('trim', preg_split('/\s*[|•]+\s*/u', $title) ?: [])));

        return $parts !== [] ? implode(' · ', $parts) : $title;
    }

    private function firstLine(mixed $value): ?string
    {
        $line = trim(strtok(trim((string) $value), "\n") ?: '');

        return $line !== '' ? $line : null;
    }

    private function clip(?string $value, int $max): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return mb_strlen($value) > $max ? rtrim(mb_substr($value, 0, $max - 1)) . '…' : $value;
    }

    private function phone(mixed $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        if (strlen($digits) === 11 && in_array($digits[0], ['7', '8'], true)) {
            return '+7' . substr($digits, 1);
        }

        return strlen($digits) === 10 && $digits[0] === '9' ? '+7' . $digits : null;
    }

    /** What is kept for "rebuild" and for debugging: the texts, not the photos. */
    private function trimSource(array $payload): array
    {
        return [
            'description' => Str::limit((string) ($payload['description'] ?? ''), 4000, ''),
            'texts' => array_map(fn ($t) => Str::limit((string) $t, 4000, ''), array_slice((array) ($payload['texts'] ?? []), 0, 10)),
            'services' => array_slice((array) ($payload['services'] ?? []), 0, 80),
            'booking' => $payload['booking'] ?? null,
            'photos' => count((array) ($payload['photos'] ?? [])),
        ];
    }
}
