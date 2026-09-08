<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Rules\PhoneNumberRule;
use Illuminate\Validation\Rule;

class OrderFormRequest extends BaseRequest
{
    /**
     * Ownership is settled here rather than in the controller: the form request
     * runs first, so editing someone else's booking used to answer 422 about the
     * fields instead of 403 about access — and that difference alone told the
     * caller whether the booking existed.
     */
    public function authorize(): bool
    {
        $user = $this->user('sanctum');

        if (! $user) {
            return false;
        }

        $order = $this->route('order');

        if ($order instanceof Order && $order->master_id !== $user->id) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        $statusKeys = array_keys(Order::statusLabels());
        $userId = $this->user('sanctum')?->id ?? 0;

        return [
            'client_id' => ['nullable', 'integer', 'exists:users,id'],
            'client_phone' => ['nullable', 'required_without:client_id', 'string', 'max:20', new PhoneNumberRule()],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'waitlist_entry_id' => ['nullable', 'integer', 'exists:waitlist_entries,id'],
            'scheduled_at' => ['required', 'date', 'before:+2 years'],
            'services' => ['nullable', 'array'],
            'services.*' => [
                'integer',
                Rule::exists('services', 'id')->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                }),
            ],
            'note' => ['nullable', 'string'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'duration_forecast' => ['nullable', 'integer', 'min:5', 'max:720'],
            'status' => ['required', Rule::in($statusKeys)],
            'source' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.integer' => 'Некорректный идентификатор клиента.',
            'client_id.exists' => 'Выбранный клиент не найден.',
            'client_phone.required' => __('orders.validation.client_phone.required'),
            'client_phone.required_without' => 'Выберите клиентку из списка или укажите телефон новой клиентки.',
            'client_phone.string' => __('orders.validation.client_phone.string'),
            'client_phone.max' => __('orders.validation.client_phone.max'),
            'client_name.string' => __('orders.validation.client_name.string'),
            'client_name.max' => __('orders.validation.client_name.max'),
            'client_email.email' => __('orders.validation.client_email.email'),
            'client_email.max' => __('orders.validation.client_email.max'),
            'scheduled_at.required' => __('orders.validation.scheduled_at.required'),
            'scheduled_at.date' => __('orders.validation.scheduled_at.date'),
            'scheduled_at.before' => 'Дата записи слишком далеко: выберите день в пределах ближайших двух лет.',
            'services.array' => __('orders.validation.services.array'),
            'services.*.integer' => __('orders.validation.services.integer'),
            'services.*.exists' => __('orders.validation.services.exists'),
            'note.string' => __('orders.validation.note.string'),
            'total_price.numeric' => __('orders.validation.total_price.numeric'),
            'total_price.min' => __('orders.validation.total_price.min'),
            'status.required' => __('orders.validation.status.required'),
            'status.in' => __('orders.validation.status.in'),
            'source.string' => __('orders.validation.source.string'),
            'source.max' => __('orders.validation.source.max'),
        ];
    }
}
