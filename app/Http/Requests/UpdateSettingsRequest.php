<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
            'phone' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'max:255'],
            'time_format' => ['required', Rule::in(['12h','24h'])],
            'current_password' => ['nullable', 'required_with:new_password', 'current_password'],
            'new_password' => ['nullable', 'min:8', 'confirmed'],
            'notifications' => ['array'],
            'notifications.email' => ['boolean'],
            'notifications.telegram' => ['boolean'],
            'notifications.sms' => ['boolean'],
            'integrations' => ['array'],
            'integrations.smsaero.email' => ['nullable', 'email'],
            'integrations.smsaero.api_key' => ['nullable', 'string', 'max:255'],
            'integrations.smtp.host' => ['nullable', 'string', 'max:255'],
            'integrations.smtp.port' => ['nullable', 'integer', 'between:1,65535'],
            'integrations.smtp.username' => ['nullable', 'string', 'max:255'],
            'integrations.smtp.password' => ['nullable', 'string', 'max:255'],
            'integrations.smtp.encryption' => ['nullable', 'string', Rule::in(['tls', 'ssl', 'starttls', 'none'])],
            'integrations.smtp.from_address' => ['nullable', 'email'],
            'integrations.smtp.from_name' => ['nullable', 'string', 'max:255'],
            'integrations.whatsapp.api_key' => ['nullable', 'string', 'max:255'],
            'integrations.whatsapp.sender' => ['nullable', 'string', 'max:255'],
            'integrations.telegram.bot_token' => ['nullable', 'string', 'max:255'],
            'integrations.telegram.sender' => ['nullable', 'string', 'max:255'],
            'integrations.yookassa.shop_id' => ['nullable', 'string', 'max:255'],
            'integrations.yookassa.secret_key' => ['nullable', 'string', 'max:255'],
            'work_days' => ['array'],
            'work_hours' => ['array'],
            'schedule_rules' => ['nullable', 'array'],
            'schedule_rules.mode' => ['nullable', Rule::in(['weekly', 'cycle', 'monthly'])],
            'schedule_rules.weekly' => ['nullable', 'array'],
            'schedule_rules.weekly.*' => ['nullable', 'array'],
            'schedule_rules.weekly.*.enabled' => ['nullable', 'boolean'],
            'schedule_rules.weekly.*.slots' => ['nullable', 'array'],
            'schedule_rules.weekly.*.slots.*' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'schedule_rules.cycle' => ['nullable', 'array'],
            'schedule_rules.cycle.anchor_date' => ['nullable', 'string', 'max:20'],
            'schedule_rules.cycle.work_days' => ['nullable', 'integer', 'min:1', 'max:31'],
            'schedule_rules.cycle.rest_days' => ['nullable', 'integer', 'min:1', 'max:31'],
            'schedule_rules.cycle.slots' => ['nullable', 'array'],
            'schedule_rules.cycle.slots.*' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'schedule_rules.monthly' => ['nullable', 'array'],
            'schedule_rules.monthly.dates' => ['nullable', 'array'],
            'holidays' => ['array'],
            'holidays.*' => ['date'],
            'address' => ['nullable', 'string', 'max:255'],
            'map_point' => ['nullable', 'array'],
            'map_point.lat' => ['nullable', 'numeric'],
            'map_point.lng' => ['nullable', 'numeric'],
            'reminder_message' => ['nullable', 'string', 'max:2000'],
            'allergy_reminder_enabled' => ['nullable', 'boolean'],
            'allergy_reminder_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'allergy_reminder_exclusions' => ['nullable', 'array'],
            'allergy_reminder_exclusions.allergies' => ['nullable', 'array'],
            'allergy_reminder_exclusions.allergies.*' => ['nullable', 'string', 'max:100'],
            'allergy_reminder_exclusions.services' => ['nullable', 'array'],
            'allergy_reminder_exclusions.services.*' => ['nullable', 'integer'],
            'daily_post_ideas_enabled' => ['nullable', 'boolean'],
            'daily_post_ideas_channel' => ['nullable', Rule::in(['telegram', 'platform', 'both'])],
            'daily_post_ideas_preferences' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
