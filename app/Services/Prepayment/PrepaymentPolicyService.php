<?php

namespace App\Services\Prepayment;

use App\Models\Order;
use App\Models\PrepaymentRule;
use App\Models\Service;
use App\Models\Setting;
use App\Services\Integrations\IntegrationCatalog;
use Illuminate\Support\Carbon;

/**
 * Decides whether a booking has to be prepaid, and for how much.
 *
 * Nothing is asked unless the master has switched prepayments on AND her
 * ЮKassa shop has been verified — a prepayment that cannot be paid would
 * only block the slot.
 *
 * Candidates (the largest amount wins):
 *   - a date/weekday rule that matches the visit
 *   - the service's own setting (`none` takes the service out of every
 *     automatic rule; only the client's manual "always" still applies)
 *   - the master's general rule (optionally "new clients only")
 *   - a client with enough no-shows
 * A client set to "never" is never asked; "always" is asked even when no rule
 * matched (then the general rule, or DEFAULT_PERCENT).
 */
class PrepaymentPolicyService
{
    public const DEFAULT_PERCENT = 30.0;

    public const DEFAULT_HOLD_MINUTES = 15;

    private const MIN_AMOUNT = 1.0;

    /**
     * @param  Carbon  $startsAtLocal  the visit, in the master's timezone
     * @param  int|null  $clientUserId  the client's user account, for her history with this master
     */
    public function resolve(
        int $masterId,
        ?string $clientOverride,
        ?int $clientUserId,
        ?Service $service,
        Carbon $startsAtLocal,
        float $totalPrice,
    ): ?PrepaymentRequirement {
        $settings = Setting::query()->where('user_id', $masterId)->first();
        $policy = (array) ($settings?->deposit_policy ?? []);

        if (! ($policy['enabled'] ?? false) || ! IntegrationCatalog::isVerified($settings, 'yookassa')) {
            return null;
        }

        if ($totalPrice < self::MIN_AMOUNT || $clientOverride === 'never') {
            return null;
        }

        $candidates = [];
        $default = $this->defaultCandidate($policy, $totalPrice);

        if ($service?->prepay_mode !== 'none') {
            foreach ($this->matchingRules($masterId, $service, $startsAtLocal) as $rule) {
                $candidates[] = $this->candidate('rule', $rule->name, $rule->mode, (float) $rule->value, $totalPrice);
            }

            if (in_array($service?->prepay_mode, ['fixed', 'percent'], true) && (float) $service->prepay_value > 0) {
                $candidates[] = $this->candidate('service', $service->name, $service->prepay_mode, (float) $service->prepay_value, $totalPrice);
            }

            if ($default && $this->defaultApplies($policy, $masterId, $clientUserId)) {
                $candidates[] = $default;
            }

            $threshold = (int) ($policy['no_show_threshold'] ?? 0);

            if ($threshold > 0 && $clientUserId && $this->noShows($masterId, $clientUserId) >= $threshold) {
                $fallback = $default ?? $this->candidate('no_shows', null, 'percent', self::DEFAULT_PERCENT, $totalPrice);
                $fallback['rule']['source'] = 'no_shows';
                $candidates[] = $fallback;
            }
        }

        if ($clientOverride === 'always' && $candidates === []) {
            $candidates[] = $default ?? $this->candidate('client', null, 'percent', self::DEFAULT_PERCENT, $totalPrice);
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn (array $a, array $b) => $b['amount'] <=> $a['amount']);
        $best = $candidates[0];

        if ($clientOverride === 'always') {
            $best['rule']['source'] = 'client';
        }

        return new PrepaymentRequirement($best['amount'], $this->holdMinutes($policy), $best['rule']);
    }

    /** Minutes an unpaid booking keeps its slot. */
    public function holdMinutes(array $policy): int
    {
        return max(5, min(120, (int) ($policy['hold_minutes'] ?? self::DEFAULT_HOLD_MINUTES)));
    }

    /** @return iterable<PrepaymentRule> */
    private function matchingRules(int $masterId, ?Service $service, Carbon $startsAtLocal): iterable
    {
        $date = $startsAtLocal->toDateString();

        return PrepaymentRule::query()
            ->where('user_id', $masterId)
            ->where('is_active', true)
            ->get()
            ->filter(function (PrepaymentRule $rule) use ($date, $startsAtLocal, $service) {
                $ids = $rule->service_ids;

                if (is_array($ids) && $ids !== [] && ! in_array($service?->id, array_map('intval', $ids), true)) {
                    return false;
                }

                if ($rule->type === PrepaymentRule::TYPE_WEEKDAY) {
                    return in_array($startsAtLocal->dayOfWeek, array_map('intval', (array) $rule->weekdays), true);
                }

                return $rule->starts_on && $rule->ends_on
                    && $date >= $rule->starts_on->toDateString()
                    && $date <= $rule->ends_on->toDateString();
            });
    }

    private function defaultCandidate(array $policy, float $price): ?array
    {
        $mode = $policy['default_mode'] ?? null;
        $value = (float) ($policy['default_value'] ?? 0);

        if (! in_array($mode, ['fixed', 'percent'], true) || $value <= 0) {
            return null;
        }

        return $this->candidate('default', null, $mode, $value, $price);
    }

    private function defaultApplies(array $policy, int $masterId, ?int $clientUserId): bool
    {
        if (! ($policy['new_clients_only'] ?? false)) {
            return true;
        }

        return ! $clientUserId || ! Order::query()
            ->where('master_id', $masterId)
            ->where('client_id', $clientUserId)
            ->where('status', 'completed')
            ->exists();
    }

    private function noShows(int $masterId, int $clientUserId): int
    {
        return Order::query()
            ->where('master_id', $masterId)
            ->where('client_id', $clientUserId)
            ->where('status', 'no_show')
            ->count();
    }

    /** @return array{amount:float,rule:array{source:string,label:?string,mode:string,value:float}} */
    private function candidate(string $source, ?string $label, string $mode, float $value, float $price): array
    {
        $amount = $mode === 'percent' ? $price * $value / 100 : $value;
        $amount = round(min($price, max(self::MIN_AMOUNT, $amount)), 2);

        return [
            'amount' => $amount,
            'rule' => ['source' => $source, 'label' => $label, 'mode' => $mode, 'value' => $value],
        ];
    }
}
