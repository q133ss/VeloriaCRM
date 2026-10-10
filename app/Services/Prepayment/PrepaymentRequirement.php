<?php

namespace App\Services\Prepayment;

/**
 * What a booking owes up front and why. `rule` is the snapshot kept on the
 * order, so a later change to the master's rules never rewrites history.
 */
final class PrepaymentRequirement
{
    /**
     * @param  array{source:string,label:?string,mode:string,value:float}  $rule
     */
    public function __construct(
        public readonly float $amount,
        public readonly int $holdMinutes,
        public readonly array $rule,
    ) {
    }
}
