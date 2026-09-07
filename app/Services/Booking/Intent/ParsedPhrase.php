<?php

namespace App\Services\Booking\Intent;

use Carbon\CarbonImmutable;

/**
 * What plain PHP could work out of a phrase like «марина завтра ногти в 3 дня»
 * before anyone was asked to think about it.
 *
 * `residue` is the part nothing claimed — normally a name and a service word.
 * It is the only thing that ever travels to a language model.
 */
class ParsedPhrase
{
    public function __construct(
        public readonly ?CarbonImmutable $scheduledAt = null,
        public readonly ?string $daypart = null,
        public readonly ?string $phone = null,
        public readonly ?int $durationMinutes = null,
        public readonly ?float $price = null,
        public readonly string $residue = '',
        public readonly bool $hasExplicitDay = false,
        public readonly bool $hasExplicitTime = false,
    ) {
    }

    /**
     * @return string[] Words left for the client and service matchers.
     */
    public function residueTokens(): array
    {
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $this->residue, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter($tokens, static fn (string $token): bool => mb_strlen($token) >= 3));
    }

    /**
     * The naive local form the datetime field and the order endpoint both speak.
     * An offset here would either fail to parse or shift the hour on screen.
     */
    public function scheduledAtString(): ?string
    {
        return $this->scheduledAt?->format('Y-m-d\TH:i');
    }
}
