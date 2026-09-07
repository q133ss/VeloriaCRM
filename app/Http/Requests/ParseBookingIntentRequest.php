<?php

namespace App\Http\Requests;

class ParseBookingIntentRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        return [
            // Also the first of two guards on the local provider's prompt
            // budget: nothing over ai.local.max_prompt_chars is ever sent there,
            // and it goes to the paid provider instead without saying so.
            'text' => ['required', 'string', 'min:2', 'max:200'],

            // The day the modal is sitting on — the anchor «завтра» counts from.
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'Напишите, кого и на что записать.',
            'text.min' => 'Слишком коротко — напишите хотя бы имя или услугу.',
            'text.max' => 'Слишком длинная фраза — опишите запись короче.',
        ];
    }
}
