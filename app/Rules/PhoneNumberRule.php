<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumberRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            $fail(__('orders.validation.client_phone.format'));
            return;
        }

        $digits = preg_replace('/[^0-9]+/', '', (string) $value);

        if ($digits === null) {
            $fail(__('orders.validation.client_phone.format'));
            return;
        }

        $length = strlen($digits);

        if ($length === 10) {
            return;
        }

        if ($length === 11 && in_array($digits[0], ['7', '8'], true)) {
            return;
        }

        $fail(__('orders.validation.client_phone.format'));
    }
}
