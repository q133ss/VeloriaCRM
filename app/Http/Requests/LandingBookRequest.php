<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LandingBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['required', 'string', 'max:32', function (string $attribute, mixed $value, \Closure $fail) {
                if (strlen(preg_replace('/\D+/', '', (string) $value) ?? '') < 10) {
                    $fail(__('landings.booking.phone_invalid'));
                }
            }],
            'service_id' => ['nullable', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:' . now()->addDays(60)->toDateString()],
            'time' => ['required', 'date_format:H:i'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
