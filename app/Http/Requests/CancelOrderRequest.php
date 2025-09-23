<?php

namespace App\Http\Requests;

class CancelOrderRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.string' => 'Причина отмены должна быть текстом.',
            'reason.max' => 'Причина отмены не должна превышать :max символов.',
        ];
    }
}
