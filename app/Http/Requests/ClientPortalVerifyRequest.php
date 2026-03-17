<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class ClientPortalVerifyRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'verification_id' => ['nullable', 'string', 'uuid'],
            'code' => ['nullable', 'string', 'regex:/^\d{6}$/'],
            'selection_token' => ['nullable', 'string', 'uuid'],
            'master_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasOtpPayload = $this->filled('verification_id') || $this->filled('code');
            $hasSelectionPayload = $this->filled('selection_token') || $this->filled('master_id');

            if ($hasOtpPayload && $hasSelectionPayload) {
                $validator->errors()->add('verification_id', 'Use either OTP verification or master selection payload.');
                return;
            }

            if (! $hasOtpPayload && ! $hasSelectionPayload) {
                $validator->errors()->add('verification_id', 'Verification payload is required.');
                return;
            }

            if ($hasOtpPayload) {
                if (! $this->filled('verification_id')) {
                    $validator->errors()->add('verification_id', 'Verification ID is required.');
                }

                if (! $this->filled('code')) {
                    $validator->errors()->add('code', 'Code is required.');
                }

                return;
            }

            if (! $this->filled('selection_token')) {
                $validator->errors()->add('selection_token', 'Selection token is required.');
            }

            if (! $this->filled('master_id')) {
                $validator->errors()->add('master_id', 'Master ID is required.');
            }
        });
    }
}
