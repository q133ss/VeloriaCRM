<?php

namespace App\Http\Requests;

use App\Models\SupportRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SupportRequestStoreRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : null,
            'contact' => trim((string) $this->input('contact', '')),
            'message' => $this->filled('message') ? trim((string) $this->input('message')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:100'],
            'contact_type' => ['required', Rule::in(SupportRequest::contactTypes())],
            'contact' => ['required', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:2000'],
            // Honeypot: hidden from people, filled in by form-stuffing bots.
            'website' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('contact') || $validator->errors()->has('contact_type')) {
                return;
            }

            $contact = (string) $this->input('contact');
            $valid = match ($this->input('contact_type')) {
                SupportRequest::TYPE_EMAIL => filter_var($contact, FILTER_VALIDATE_EMAIL) !== false,
                SupportRequest::TYPE_PHONE => strlen(preg_replace('/\D+/', '', $contact)) >= 10
                    && strlen(preg_replace('/\D+/', '', $contact)) <= 15
                    && preg_match('/^[+\d\s().-]+$/', $contact) === 1,
                default => preg_match('/^@?[A-Za-z0-9_]{5,32}$/', $contact) === 1,
            };

            if (! $valid) {
                $validator->errors()->add('contact', __('help.guest.invalid_contact_' . $this->input('contact_type')));
            }
        }];
    }

    public function messages(): array
    {
        return [
            'contact.required' => __('help.guest.contact_required'),
            'contact_type.required' => __('help.guest.contact_required'),
        ];
    }
}
