<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Validation\Rule;

class OrderFormRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        $statusKeys = array_keys(Order::statusLabels());
        $userId = $this->user('sanctum')?->id ?? 0;

        return [
            'client_phone' => ['required', 'string', 'max:20'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'scheduled_at' => ['required', 'date'],
            'services' => ['nullable', 'array'],
            'services.*' => [
                'integer',
                Rule::exists('services', 'id')->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                }),
            ],
            'note' => ['nullable', 'string'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in($statusKeys)],
            'source' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_phone.required' => __('orders.validation.client_phone.required'),
            'client_phone.string' => __('orders.validation.client_phone.string'),
            'client_phone.max' => __('orders.validation.client_phone.max'),
            'client_name.string' => __('orders.validation.client_name.string'),
            'client_name.max' => __('orders.validation.client_name.max'),
            'client_email.email' => __('orders.validation.client_email.email'),
            'client_email.max' => __('orders.validation.client_email.max'),
            'scheduled_at.required' => __('orders.validation.scheduled_at.required'),
            'scheduled_at.date' => __('orders.validation.scheduled_at.date'),
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
