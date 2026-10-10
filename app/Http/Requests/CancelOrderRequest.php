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
            // Left out: the master's own refund rule decides. true: give it all back. false: keep it.
            'refund_prepayment' => ['nullable', 'boolean'],
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
