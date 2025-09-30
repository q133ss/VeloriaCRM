<?php

namespace App\Http\Requests;

class SupportTicketMessageRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'message' => $this->filled('message') ? trim((string) $this->input('message')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:3'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx,txt,csv'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => __('validation.required', ['attribute' => __('help.support.form.message_label')]),
            'message.string' => __('validation.string', ['attribute' => __('help.support.form.message_label')]),
            'message.min' => __('validation.min.string', ['attribute' => __('help.support.form.message_label'), 'min' => 3]),
            'attachment.file' => __('validation.file', ['attribute' => __('help.support.form.attachment_label')]),
            'attachment.max' => __('validation.max.file', ['attribute' => __('help.support.form.attachment_label'), 'max' => 10240]),
            'attachment.mimes' => __('validation.mimes', ['attribute' => __('help.support.form.attachment_label'), 'values' => 'jpg, jpeg, png, pdf, doc, docx, txt, csv']),
        ];
    }
}
