<?php

namespace App\Http\Requests;

class WaitlistMatchRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'time' => ['nullable', 'date_format:H:i'],
            'service_id' => ['nullable', 'integer'],
            'duration' => ['nullable', 'integer', 'min:15', 'max:480'],
        ];
    }
}
