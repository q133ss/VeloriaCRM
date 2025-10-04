<?php

namespace App\Http\Requests;

use App\Models\MasterMood;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMasterMoodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Запрос доступен только аутентифицированным мастерам.
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mood' => ['required', 'string', Rule::in(array_keys(MasterMood::MOOD_OPTIONS))],
            'date' => ['nullable', 'date'],
        ];
    }
}
