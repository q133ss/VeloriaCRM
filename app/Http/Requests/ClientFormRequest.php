<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Validation\Rule;

class ClientFormRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->filled('email') ? $this->input('email') : null,
            'loyalty_level' => $this->filled('loyalty_level') ? $this->input('loyalty_level') : null,
            'notes' => $this->filled('notes') ? $this->input('notes') : null,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        $userId = $this->user('sanctum')?->id ?? 0;
        $client = $this->route('client');
        $clientId = $client instanceof Client ? $client->id : (is_numeric($client) ? (int) $client : 0);

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:32',
                Rule::unique('clients', 'phone')->where(fn ($query) => $query->where('user_id', $userId))->ignore($clientId),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->where(fn ($query) => $query->where('user_id', $userId))->ignore($clientId),
            ],
            'birthday' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['nullable', 'string', 'max:100'],
            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['nullable', 'string', 'max:100'],
            'preferences' => ['nullable', 'array'],
            'preferences.*' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'last_visit_at' => ['nullable', 'date'],
            'loyalty_level' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Укажите имя клиента.',
            'name.string' => 'Имя клиента должно быть строкой.',
            'name.max' => 'Имя клиента не должно превышать :max символов.',
            'phone.required' => 'Добавьте телефон клиента.',
            'phone.string' => 'Телефон клиента должен быть строкой.',
            'phone.max' => 'Телефон клиента не должен превышать :max символов.',
            'phone.unique' => 'Клиент с таким телефоном уже существует.',
            'email.email' => 'Укажите корректный email.',
            'email.max' => 'Email не должен превышать :max символов.',
            'email.unique' => 'Email уже используется другим клиентом.',
            'birthday.date' => 'Дата рождения указана в неверном формате.',
            'tags.array' => 'Теги должны быть массивом.',
            'tags.*.string' => 'Каждый тег должен быть строкой.',
            'tags.*.max' => 'Тег не должен превышать :max символов.',
            'allergies.array' => 'Аллергии должны быть массивом.',
            'allergies.*.string' => 'Каждый пункт аллергии должен быть строкой.',
            'allergies.*.max' => 'Значение аллергии не должно превышать :max символов.',
            'preferences.array' => 'Предпочтения должны быть массивом.',
            'preferences.*.string' => 'Каждое предпочтение должно быть строкой.',
            'preferences.*.max' => 'Предпочтение не должно превышать :max символов.',
            'notes.string' => 'Заметка должна быть строкой.',
            'last_visit_at.date' => 'Дата последнего визита указана в неверном формате.',
            'loyalty_level.string' => 'Уровень лояльности должен быть строкой.',
            'loyalty_level.max' => 'Уровень лояльности не должен превышать :max символов.',
        ];
    }
}
