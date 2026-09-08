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

    /**
     * The numbers come from config/help.php, which is also what the form
     * prints above the fields — a minimum nobody announced used to be
     * answered with «Предоставленные данные недействительны».
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:' . config('help.limits.subject_max', 255)],
            'message' => ['required', 'string', 'min:' . config('help.limits.message_min', 10), 'max:' . config('help.limits.message_max', 5000)],
            'attachment' => [
                'nullable',
                'file',
                'max:' . (config('help.attachment.max_mb', 10) * 1024),
                'mimes:' . implode(',', config('help.attachment.extensions', [])),
            ],
        ];
    }

    public function messages(): array
    {
        $maxKb = config('help.attachment.max_mb', 10) * 1024;

        return [
            'subject.required' => __('validation.required', ['attribute' => __('help.support.form.subject_label')]),
            'subject.string' => __('validation.string', ['attribute' => __('help.support.form.subject_label')]),
            'subject.max' => __('validation.max.string', [
                'attribute' => __('help.support.form.subject_label'),
                'max' => config('help.limits.subject_max', 255),
            ]),
            'message.required' => __('validation.required', ['attribute' => __('help.support.form.message_label')]),
            'message.string' => __('validation.string', ['attribute' => __('help.support.form.message_label')]),
            'message.min' => __('validation.min.string', [
                'attribute' => __('help.support.form.message_label'),
                'min' => config('help.limits.message_min', 10),
            ]),
            'attachment.file' => __('validation.file', ['attribute' => __('help.support.form.attachment_label')]),
            'attachment.max' => __('validation.max.file', [
                'attribute' => __('help.support.form.attachment_label'),
                'max' => $maxKb,
            ]),
            'attachment.mimes' => __('validation.mimes', [
                'attribute' => __('help.support.form.attachment_label'),
                'values' => implode(', ', config('help.attachment.extensions', [])),
            ]),
        ];
    }
}
