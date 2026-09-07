<?php

namespace Tests\Unit;

use App\Services\Booking\Intent\BookingPhraseParser;
use App\Services\ClientIdentityService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BookingPhraseParserTest extends TestCase
{
    private BookingPhraseParser $parser;

    /** Monday, 7 September 2026, 11:00. */
    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new BookingPhraseParser(new ClientIdentityService());
        $this->now = CarbonImmutable::create(2026, 9, 7, 11, 0, 0);
    }

    #[DataProvider('phrases')]
    public function test_it_reads_the_day_and_the_hour(string $phrase, string $expected): void
    {
        $parsed = $this->parser->parse($phrase, $this->now);

        $this->assertSame($expected, $parsed->scheduledAtString(), $phrase);
    }

    public static function phrases(): array
    {
        return [
            'завтра днём' => ['марина завтра ногти в 3 дня', '2026-09-08T15:00'],
            'сегодня' => ['сегодня в 18:30 стрижка', '2026-09-07T18:30'],
            'послезавтра' => ['послезавтра в 12', '2026-09-09T12:00'],
            'день недели' => ['в пятницу в 11 маникюр', '2026-09-11T11:00'],
            'следующая неделя' => ['в следующий вторник в 16', '2026-09-15T16:00'],
            'через неделю' => ['через неделю в 18:30', '2026-09-14T18:30'],
            'через 3 дня' => ['через 3 дня в 10 утра', '2026-09-10T10:00'],
            'число месяца' => ['15-го в 10 утра', '2026-09-15T10:00'],
            'месяц словом' => ['5 октября в 13:00', '2026-10-05T13:00'],
            'дата цифрами' => ['21.09 в 12', '2026-09-21T12:00'],
            'полдень' => ['завтра в полдень', '2026-09-08T12:00'],
            'полчетвертого' => ['завтра полчетвертого', '2026-09-08T15:30'],
            'вчера — записать задним числом' => ['вчера в 14 педикюр', '2026-09-06T14:00'],
        ];
    }

    #[DataProvider('bareHours')]
    public function test_a_bare_hour_lands_in_the_working_day(string $phrase, string $expected): void
    {
        $this->assertSame($expected, $this->parser->parse($phrase, $this->now)->scheduledAtString());
    }

    public static function bareHours(): array
    {
        return [
            ['завтра в 3', '2026-09-08T15:00'],
            ['завтра в 7', '2026-09-08T19:00'],
            ['завтра в 9', '2026-09-08T09:00'],
            ['завтра в 11', '2026-09-08T11:00'],
            ['завтра в 20', '2026-09-08T20:00'],
        ];
    }

    public function test_a_part_of_the_day_alone_still_gives_the_field_an_hour(): void
    {
        $parsed = $this->parser->parse('марина завтра вечером', $this->now);

        $this->assertSame('2026-09-08T18:00', $parsed->scheduledAtString());
        $this->assertSame('evening', $parsed->daypart);
        $this->assertFalse($parsed->hasExplicitTime);
    }

    public function test_an_hour_already_gone_by_moves_to_tomorrow_only_when_no_day_was_named(): void
    {
        $this->assertSame('2026-09-08T09:00', $this->parser->parse('маникюр в 9', $this->now)->scheduledAtString());
        $this->assertSame('2026-09-07T09:00', $this->parser->parse('сегодня в 9 маникюр', $this->now)->scheduledAtString());
    }

    public function test_a_phrase_with_no_time_at_all_sets_no_date(): void
    {
        $parsed = $this->parser->parse('марина маникюр', $this->now);

        $this->assertNull($parsed->scheduledAt);
        $this->assertSame('марина маникюр', $parsed->residue);
    }

    public function test_a_phone_is_taken_out_before_the_hour_is_read(): void
    {
        $parsed = $this->parser->parse('+7 916 123-45-67 завтра маникюр', $this->now);

        $this->assertSame('+79161234567', $parsed->phone);
        $this->assertSame('2026-09-08T10:00', $parsed->scheduledAtString());
        $this->assertFalse($parsed->hasExplicitTime, 'the 45 in the number must not become a time');
        $this->assertSame('маникюр', $parsed->residue);
    }

    public function test_a_phone_is_normalised_the_way_the_rest_of_the_app_does_it(): void
    {
        $this->assertSame('+79161234567', $this->parser->parse('8 916 123 45 67 завтра', $this->now)->phone);
    }

    public function test_it_reads_an_explicit_duration_and_price(): void
    {
        $parsed = $this->parser->parse('марина завтра в 15:00 на 2 часа за 3000', $this->now);

        $this->assertSame(120, $parsed->durationMinutes);
        $this->assertSame(3000.0, $parsed->price);
        $this->assertSame('2026-09-08T15:00', $parsed->scheduledAtString());
        $this->assertSame('марина', $parsed->residue);
    }

    public function test_half_an_hour_is_understood_as_a_duration(): void
    {
        $this->assertSame(90, $this->parser->parse('завтра на полтора часа', $this->now)->durationMinutes);
        $this->assertSame(45, $this->parser->parse('завтра на 45 минут', $this->now)->durationMinutes);
    }

    public function test_the_residue_keeps_only_words_worth_matching(): void
    {
        $parsed = $this->parser->parse('запиши катю на ногти завтра в 3 дня', $this->now);

        $this->assertSame(['катю', 'ногти'], $parsed->residueTokens());
    }

    public function test_the_anchor_day_is_used_when_the_phrase_names_no_day(): void
    {
        $anchor = CarbonImmutable::create(2026, 10, 1, 0, 0, 0);
        $parsed = $this->parser->parse('марина в 15:00', $this->now, $anchor);

        $this->assertSame('2026-10-01T15:00', $parsed->scheduledAtString());
    }
}
