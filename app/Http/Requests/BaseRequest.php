<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseRequest extends FormRequest
{
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
