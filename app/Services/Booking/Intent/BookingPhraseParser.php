<?php

namespace App\Services\Booking\Intent;

use App\Services\ClientIdentityService;
use Carbon\CarbonImmutable;

/**
 * Reads the clock out of a booking phrase.
 *
 * A language model is good at «катю» → «Катя» and at guessing which of three
 * nail services «ногти» means. It is bad at knowing what today is, and a wrong
 * date is the most expensive mistake this feature can make — a client booked
 * into the wrong week. So dates, times, phones, durations and prices are read
 * here, deterministically, and the model never gets to have an opinion on them.
 *
 * Every piece found is cut out of the text before the next one is looked for,
 * which is why the order below matters: «+7 916 123-45-67» holds a «45» that
 * reads as a time and a «123» that reads as a price.
 */
class BookingPhraseParser
{
    private const WEEKDAYS = [
        'понедельник' => 1, 'пн' => 1,
        'вторник' => 2, 'вт' => 2,
        'сред' => 3, 'ср' => 3,
        'четверг' => 4, 'чт' => 4,
        'пятниц' => 5, 'пт' => 5,
        'суббот' => 6, 'сб' => 6,
        'воскресень' => 7, 'вс' => 7,
    ];

    /**
     * Long enough not to swallow a service name: a bare «ма» stem reads
     * «в 11 маникюр» as the eleventh of May.
     */
    private const MONTHS = [
        'янв\p{L}*' => 1, 'феврал\p{L}*|февр?\b' => 2, 'март\p{L}*|мар\b' => 3,
        'апрел\p{L}*|апр\b' => 4, 'ма[йя]\b' => 5, 'июн\p{L}*' => 6,
        'июл\p{L}*' => 7, 'август\p{L}*|авг\b' => 8, 'сентябр\p{L}*|сент?\b' => 9,
        'октябр\p{L}*|окт\b' => 10, 'ноябр\p{L}*|ноя\b' => 11, 'декабр\p{L}*|дек\b' => 12,
    ];

    public function __construct(private readonly ClientIdentityService $identity)
    {
    }

    public function parse(string $text, CarbonImmutable $now, ?CarbonImmutable $anchorDay = null): ParsedPhrase
    {
        $rest = $this->normalise($text);

        [$rest, $phone] = $this->takePhone($rest);
        [$rest, $duration] = $this->takeDuration($rest);
        [$rest, $price] = $this->takePrice($rest);
        [$rest, $date, $hasExplicitDay] = $this->takeDate($rest, $now, $anchorDay);
        [$rest, $time, $daypart] = $this->takeTime($rest);

        $scheduledAt = $this->combine($date, $time, $daypart, $now, $hasExplicitDay);

        return new ParsedPhrase(
            scheduledAt: $scheduledAt,
            daypart: $time === null ? $daypart : null,
            phone: $phone,
            durationMinutes: $duration,
            price: $price,
            residue: $this->tidy($rest),
            hasExplicitDay: $hasExplicitDay,
            hasExplicitTime: $time !== null,
        );
    }

    private function normalise(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace(['ё', '—', '–'], ['е', '-', '-'], $text);

        return (string) preg_replace('/\s+/u', ' ', $text);
    }

    private function tidy(string $text): string
    {
        // Prepositions and filler that survive the cuts and would otherwise be
        // taken for a client's name by the matcher.
        $text = (string) preg_replace(
            '/\b(запиши|записать|запись|на|в|во|к|ко|и|же|мне|её|ее|его|пожалуйста|плиз)\b/u',
            ' ',
            $text
        );

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function takePhone(string $text): array
    {
        $pattern = '/(?:\+?7|8)?[\s\-()]*\d{3}[\s\-()]*\d{3}[\s\-()]*\d{2}[\s\-()]*\d{2}/u';

        if (! preg_match($pattern, $text, $match)) {
            return [$text, null];
        }

        $digits = preg_replace('/\D/', '', $match[0]);

        if (mb_strlen((string) $digits) < 10) {
            return [$text, null];
        }

        return [str_replace($match[0], ' ', $text), $this->identity->normalisePhone($match[0])];
    }

    /**
     * @return array{0: string, 1: ?int}
     */
    private function takeDuration(string $text): array
    {
        if (preg_match('/\bна\s+полтора\s+час\p{L}*/u', $text, $match)) {
            return [str_replace($match[0], ' ', $text), 90];
        }

        if (preg_match('/\bна\s+(\d+(?:[.,]\d+)?)\s*(час\p{L}*|ч\b|мин\p{L}*)/u', $text, $match)) {
            $value = (float) str_replace(',', '.', $match[1]);
            $minutes = str_starts_with($match[2], 'мин') ? (int) round($value) : (int) round($value * 60);

            return [str_replace($match[0], ' ', $text), max(5, $minutes)];
        }

        return [$text, null];
    }

    /**
     * @return array{0: string, 1: ?float}
     */
    private function takePrice(string $text): array
    {
        $pattern = '/\b(?:за\s+)?(\d[\d\s]{2,})\s*(?:р\b|руб|₽)|\bза\s+(\d[\d\s]{2,})\b/u';

        if (! preg_match($pattern, $text, $match)) {
            return [$text, null];
        }

        $digits = preg_replace('/\D/', '', $match[1] !== '' ? $match[1] : ($match[2] ?? ''));

        if ($digits === '' || $digits === null) {
            return [$text, null];
        }

        return [str_replace($match[0], ' ', $text), (float) $digits];
    }

    /**
     * @return array{0: string, 1: CarbonImmutable, 2: bool}
     */
    private function takeDate(string $text, CarbonImmutable $now, ?CarbonImmutable $anchorDay): array
    {
        $today = $now->startOfDay();
        $default = $anchorDay?->startOfDay() ?? $today;

        $relative = [
            'послезавтра' => 2,
            'позавчера' => -2,
            'сегодня' => 0,
            'завтра' => 1,
            'вчера' => -1,
        ];

        foreach ($relative as $word => $shift) {
            if (str_contains($text, $word)) {
                return [str_replace($word, ' ', $text), $today->addDays($shift), true];
            }
        }

        if (preg_match('/\bчерез\s+(\d+|день|недел[юи]|месяц)\s*(дн[яей]+|недел[июь]|месяц[аев]*)?/u', $text, $match)) {
            $count = is_numeric($match[1]) ? (int) $match[1] : 1;
            $unit = $match[2] ?? $match[1];

            $date = match (true) {
                str_starts_with($unit, 'недел') => $today->addWeeks($count),
                str_starts_with($unit, 'месяц') => $today->addMonths($count),
                default => $today->addDays($count),
            };

            return [str_replace($match[0], ' ', $text), $date, true];
        }

        if (preg_match('/\b(\d{1,2})[.\/](\d{1,2})(?:[.\/](\d{2,4}))?/u', $text, $match)) {
            $year = isset($match[3]) && $match[3] !== ''
                ? (int) (mb_strlen($match[3]) === 2 ? '20' . $match[3] : $match[3])
                : $today->year;

            $date = $today->setDate($year, (int) $match[2], (int) $match[1]);

            if (! isset($match[3]) && $date->lt($today)) {
                $date = $date->addYear();
            }

            return [str_replace($match[0], ' ', $text), $date, true];
        }

        foreach (self::MONTHS as $stem => $number) {
            if (preg_match('/\b(\d{1,2})\s+(?:' . $stem . ')/u', $text, $match)) {
                $date = $today->setDate($today->year, $number, (int) $match[1]);

                if ($date->lt($today)) {
                    $date = $date->addYear();
                }

                return [str_replace($match[0], ' ', $text), $date, true];
            }
        }

        if (preg_match('/\b(\d{1,2})[-\s]?(?:го|числа)\b/u', $text, $match)) {
            $day = (int) $match[1];
            $date = $today->day <= $day
                ? $today->setDay(min($day, $today->daysInMonth))
                : $today->addMonth()->setDay(min($day, $today->addMonth()->daysInMonth));

            return [str_replace($match[0], ' ', $text), $date, true];
        }

        $nextWeek = str_contains($text, 'следующ');

        foreach (self::WEEKDAYS as $stem => $iso) {
            if (! preg_match('/\b' . $stem . '\p{L}*/u', $text, $match)) {
                continue;
            }

            $date = $today;
            while ((int) $date->isoWeekday() !== $iso) {
                $date = $date->addDay();
            }

            if ($nextWeek) {
                $date = $date->addWeek();
            }

            $text = (string) preg_replace('/\bследующ\p{L}*/u', ' ', str_replace($match[0], ' ', $text));

            return [$text, $date, true];
        }

        return [$text, $default, false];
    }

    /**
     * @return array{0: string, 1: ?array{0: int, 1: int}, 2: ?string}
     */
    private function takeTime(string $text): array
    {
        if (preg_match('/\bв\s*полдень\b/u', $text, $match)) {
            return [str_replace($match[0], ' ', $text), [12, 0], null];
        }

        if (preg_match('/\bв\s*полноч[ьи]\b/u', $text, $match)) {
            return [str_replace($match[0], ' ', $text), [0, 0], null];
        }

        // «полчетвертого» is half an hour before four, not half past four.
        $halfPast = [
            'первого' => 1, 'второго' => 2, 'третьего' => 3, 'четвертого' => 4,
            'пятого' => 5, 'шестого' => 6, 'седьмого' => 7, 'восьмого' => 8,
            'девятого' => 9, 'десятого' => 10, 'одиннадцатого' => 11, 'двенадцатого' => 12,
        ];

        foreach ($halfPast as $word => $hour) {
            if (preg_match('/\bпол\s?' . $word . '\b/u', $text, $match)) {
                return [str_replace($match[0], ' ', $text), [$this->toDayHour($hour - 1, null), 30], null];
            }
        }

        $daypartWords = [
            'утра' => 'morning', 'утром' => 'morning',
            'дня' => 'day', 'днем' => 'day', 'обеда' => 'day',
            'вечера' => 'evening', 'вечером' => 'evening',
            'ночи' => 'night', 'ночью' => 'night',
        ];

        $pattern = '/\bв\s*(\d{1,2})(?:[:.\-](\d{2}))?\s*(?:ч(?:ас(?:ов|а)?)?)?\s*(утра|утром|дня|днем|обеда|вечера|вечером|ночи|ночью)?/u';

        if (preg_match($pattern, $text, $match)) {
            $hour = (int) $match[1];
            $minute = isset($match[2]) && $match[2] !== '' ? (int) $match[2] : 0;
            $daypart = isset($match[3]) && $match[3] !== '' ? $daypartWords[$match[3]] : null;

            if ($hour <= 23 && $minute <= 59) {
                return [str_replace($match[0], ' ', $text), [$this->toDayHour($hour, $daypart), $minute], null];
            }
        }

        foreach ($daypartWords as $word => $daypart) {
            if (preg_match('/\b' . $word . '\b/u', $text, $match)) {
                return [str_replace($match[0], ' ', $text), null, $daypart];
            }
        }

        return [$text, null, null];
    }

    /**
     * Nobody books a manicure at three in the morning. A bare 1-7 means the
     * afternoon, and offering the master a choice between 15:00 and 03:00
     * would be a worse answer than simply knowing this.
     */
    private function toDayHour(int $hour, ?string $daypart): int
    {
        if ($daypart === 'morning') {
            return $hour === 12 ? 0 : $hour;
        }

        if ($daypart === 'day' || $daypart === 'evening') {
            return $hour < 12 ? $hour + 12 : $hour;
        }

        if ($daypart === 'night') {
            return $hour >= 10 && $hour < 12 ? $hour + 12 : $hour;
        }

        return $hour >= 1 && $hour <= 7 ? $hour + 12 : $hour;
    }

    /**
     * A part of the day with no hour still deserves an hour in the field —
     * the master can see it and drag it, which she cannot do with a blank.
     */
    private const DAYPART_HOURS = ['morning' => 10, 'day' => 14, 'evening' => 18, 'night' => 20];

    /**
     * @param  ?array{0: int, 1: int}  $time
     */
    private function combine(
        CarbonImmutable $date,
        ?array $time,
        ?string $daypart,
        CarbonImmutable $now,
        bool $hasExplicitDay,
    ): ?CarbonImmutable {
        if ($time === null) {
            if (! $hasExplicitDay && $daypart === null) {
                return null;
            }

            // 10:00 is what the create form itself opens with, so a phrase that
            // only names a day moves the day and leaves the hour where it was.
            return $date->setTime(self::DAYPART_HOURS[$daypart] ?? 10, 0);
        }

        $moment = $date->setTime($time[0], $time[1]);

        // Only a time and it has already gone by: she means tomorrow. If she
        // named the day, she meant the day — even a past one, for a walk-in
        // being written down after the fact.
        if (! $hasExplicitDay && $moment->lt($now)) {
            $moment = $moment->addDay();
        }

        return $moment;
    }
}
