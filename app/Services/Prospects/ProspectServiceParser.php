<?php

namespace App\Services\Prospects;

use App\Services\Ai\AiGateway;
use Illuminate\Support\Str;

/**
 * Turns whatever a group shows about its prices — the "Services" section,
 * a price list in the description, a pinned post — into a catalog of
 * services with categories, prices and durations.
 *
 * The model reads the raw texts; without it, ready items are taken as they
 * are; without those, a short catalog typical for the niche is used, so a
 * page is always built and the admin fixes the prices by hand.
 */
class ProspectServiceParser
{
    public const MAX_SERVICES = 40;

    private const DEFAULT_DURATION = 60;

    /** The prompt carries the texts, not the context: the model has to read them. */
    private const MAX_TEXT_CHARS = 12000;

    private const NICHE_DEFAULTS = [
        'nails' => [['Маникюр', 'Маникюр с покрытием', 120], ['Маникюр', 'Маникюр без покрытия', 60], ['Педикюр', 'Педикюр с покрытием', 120]],
        'brows' => [['Брови', 'Коррекция и окрашивание бровей', 45], ['Брови', 'Ламинирование бровей', 60], ['Ресницы', 'Ламинирование ресниц', 60]],
        'lashes' => [['Ресницы', 'Наращивание ресниц, классика', 120], ['Ресницы', 'Наращивание ресниц, 2D', 150], ['Ресницы', 'Снятие наращенных ресниц', 30]],
        'hair' => [['Стрижки', 'Женская стрижка', 60], ['Окрашивание', 'Окрашивание в один тон', 150], ['Укладки', 'Укладка', 45]],
        'barber' => [['Стрижки', 'Мужская стрижка', 60], ['Борода', 'Моделирование бороды', 30], ['Стрижки', 'Стрижка и борода', 90]],
        'spa' => [['Массаж', 'Классический массаж', 60], ['Массаж', 'Массаж спины', 30], ['Депиляция', 'Шугаринг, голени', 30]],
        'cosmetology' => [['Уход за лицом', 'Чистка лица', 90], ['Уход за лицом', 'Пилинг', 45], ['Консультации', 'Консультация косметолога', 30]],
        'makeup' => [['Макияж', 'Дневной макияж', 60], ['Макияж', 'Вечерний макияж', 90], ['Макияж', 'Свадебный макияж', 120]],
    ];

    public function __construct(private readonly AiGateway $ai)
    {
    }

    /**
     * @param  array<int, array{name?: mixed, price?: mixed, duration?: mixed, category?: mixed}>  $items  ready items (VK "Services", YCLIENTS)
     * @param  array<int, string>  $texts  raw texts where prices may be written
     * @return array{source: string, services: array<int, array{category: string, name: string, price: ?float, duration: int}>}
     */
    public function parse(array $items, array $texts, string $niche): array
    {
        $ready = $this->clean($items);
        $texts = array_values(array_filter(array_map(fn ($t) => trim((string) $t), $texts)));

        if ($texts !== [] || $ready !== []) {
            $parsed = $this->askModel($ready, $texts, $niche);

            if ($parsed !== []) {
                return ['source' => 'ai', 'services' => $parsed];
            }
        }

        if ($ready !== []) {
            return ['source' => 'items', 'services' => $ready];
        }

        return ['source' => 'default', 'services' => $this->defaults($niche)];
    }

    /** @return array<int, array{category: string, name: string, price: ?float, duration: int}> */
    public function defaults(string $niche): array
    {
        $rows = self::NICHE_DEFAULTS[$niche] ?? self::NICHE_DEFAULTS['nails'];

        return array_map(fn (array $row) => [
            'category' => $row[0],
            'name' => $row[1],
            'price' => null,
            'duration' => $row[2],
        ], $rows);
    }

    private function askModel(array $ready, array $texts, string $niche): array
    {
        $source = '';

        if ($ready !== []) {
            $source .= "Готовый список услуг из группы:\n";
            foreach ($ready as $item) {
                $source .= '- ' . $item['name'] . ($item['price'] !== null ? ' — ' . $item['price'] . ' ₽' : '') . "\n";
            }
            $source .= "\n";
        }

        foreach ($texts as $i => $text) {
            $source .= 'Текст ' . ($i + 1) . ":\n" . $text . "\n\n";
        }

        $source = Str::limit($source, self::MAX_TEXT_CHARS, '');

        $prompt = <<<PROMPT
Ниже тексты из группы бьюти-мастера во ВКонтакте (ниша: {$niche}). Найди в них услуги, которые мастер оказывает, и верни прайс.

Правила:
- бери только услуги, которые явно названы в текстах; ничего не придумывай;
- price — число в рублях; если указано «от 1500», ставь 1500; если цены нет — null;
- duration — длительность в минутах; если не указана, оцени типичную для такой услуги (кратно 15);
- category — короткое название группы услуг по-русски («Маникюр», «Брови», «Стрижки»), 2–6 категорий на весь прайс;
- name — название услуги как у мастера, без цены, с заглавной буквы, до 80 символов;
- акции, скидки, сертификаты, обучение моделей и товары в прайс не включай;
- не больше 40 услуг.

{$source}
PROMPT;

        $payload = $this->ai->json('prospect_services', $prompt, null, $this->schema(), [
            'temperature' => 0.1,
            'max_tokens' => 2500,
        ]);

        return $this->clean(is_array($payload) ? (array) ($payload['services'] ?? []) : []);
    }

    /** @return array<int, array{category: string, name: string, price: ?float, duration: int}> */
    private function clean(array $items): array
    {
        $seen = [];
        $out = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = Str::limit(trim(preg_replace('/\s+/u', ' ', (string) ($item['name'] ?? '')) ?? ''), 120, '');

            if (mb_strlen($name) < 2 || isset($seen[mb_strtolower($name)])) {
                continue;
            }

            $seen[mb_strtolower($name)] = true;
            $price = $this->number($item['price'] ?? null);
            $duration = (int) round((float) $this->number($item['duration'] ?? null));
            $category = Str::limit(trim((string) ($item['category'] ?? '')), 60, '');

            $out[] = [
                'category' => $category !== '' ? Str::ucfirst($category) : 'Услуги',
                'name' => Str::ucfirst($name),
                'price' => $price !== null && $price > 0 && $price < 1000000 ? $price : null,
                'duration' => $duration >= 10 && $duration <= 600 ? $duration : self::DEFAULT_DURATION,
            ];

            if (count($out) >= self::MAX_SERVICES) {
                break;
            }
        }

        return $out;
    }

    private function number(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $digits = preg_replace('/[^\d.,]/u', '', str_replace(' ', '', $value)) ?? '';
        $digits = str_replace(',', '.', $digits);

        return is_numeric($digits) ? (float) $digits : null;
    }

    private function schema(): array
    {
        return [
            'name' => 'prospect_services',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'services' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'category' => ['type' => 'string'],
                                'name' => ['type' => 'string'],
                                'price' => ['oneOf' => [['type' => 'number'], ['type' => 'null']]],
                                'duration' => ['type' => 'integer'],
                            ],
                            'required' => ['category', 'name', 'price', 'duration'],
                        ],
                    ],
                ],
                'required' => ['services'],
            ],
        ];
    }
}
