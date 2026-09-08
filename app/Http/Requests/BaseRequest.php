<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseRequest extends FormRequest
{
    /**
     * A number as a person types it, turned into one the validator understands.
     *
     * Casting straight to float before validation meant `numeric` never saw what
     * was actually typed: PHP reads «2 500» as 2 and «дорого» as 0, and both were
     * accepted. A price of 2 ₽ then went into the catalogue, into the booking and
     * into the takings without a word of warning.
     *
     * Anything that still is not a number after this comes back untouched, so the
     * rules can reject it out loud.
     */
    protected function normalizeNumeric(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        // Ordinary spaces plus the no-break and narrow no-break ones that come
        // out of a copied price.
        $compact = preg_replace('/[\s\x{00A0}\x{202F}]/u', '', trim($value));

        if ($compact === '' || is_numeric($compact)) {
            return $compact === '' ? $value : $compact;
        }

        $decimal = str_replace(',', '.', $compact);

        if (is_numeric($decimal)) {
            return $decimal;
        }

        // «2500 ₽», «2500 руб.» — the digits are unambiguous, the tail is not.
        $stripped = preg_replace('/[^\d.\-]/u', '', $decimal);

        return is_numeric($stripped) ? $stripped : $value;
    }

    protected function failedValidation(Validator $validator): void
    {
        $response = response()->json([
            'error' => [
                'code' => 'validation_error',
                'message' => __('validation.given_data_invalid'),
                'fields' => $validator->errors(),
            ],
        ], 422);

        throw new HttpResponseException($response);
    }
}
