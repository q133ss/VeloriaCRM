<?php

namespace App\Http\Requests;

class SupportTicketStoreRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'subject' => $this->filled('subject') ? trim((string) $this->input('subject')) : null,
            'message' => $this->filled('message') ? trim((string) $this->input('message')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx,txt,csv'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => __('validation.required', ['attribute' => __('help.support.form.subject_label')]),
            'subject.string' => __('validation.string', ['attribute' => __('help.support.form.subject_label')]),
            'subject.max' => __('validation.max.string', ['attribute' => __('help.support.form.subject_label'), 'max' => 255]),
            'message.required' => __('validation.required', ['attribute' => __('help.support.form.message_label')]),
            'message.string' => __('validation.string', ['attribute' => __('help.support.form.message_label')]),
            'message.min' => __('validation.min.string', ['attribute' => __('help.support.form.message_label'), 'min' => 10]),
            'attachment.file' => __('validation.file', ['attribute' => __('help.support.form.attachment_label')]),
            'attachment.max' => __('validation.max.file', ['attribute' => __('help.support.form.attachment_label'), 'max' => 10240]),
            'attachment.mimes' => __('validation.mimes', ['attribute' => __('help.support.form.attachment_label'), 'values' => 'jpg, jpeg, png, pdf, doc, docx, txt, csv']),
        ];
    }
}
