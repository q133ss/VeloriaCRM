<?php

namespace App\Http\Requests;

class ChatMessageRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'body' => $this->filled('body') ? trim((string) $this->input('body')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:' . config('chat.limits.message_max', 2000), 'required_without:attachment'],
            'attachment' => [
                'nullable',
                'file',
                'max:' . (config('chat.attachment.max_mb', 10) * 1024),
                'mimes:' . implode(',', config('chat.attachment.extensions', [])),
            ],
        ];
    }
}
