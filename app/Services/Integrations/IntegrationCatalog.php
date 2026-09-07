<?php

namespace App\Services\Integrations;

use App\Models\Setting;

/**
 * One description of the five channels, shared by the form, the payload, the
 * validator and the checker.
 *
 * The list of required fields used to live only in the page's JavaScript, so
 * the form itself could not say which fields it needed and the server could
 * not say what was missing.
 */
class IntegrationCatalog
{
    /**
     * Ordered the way a master meets them: Telegram is free and takes five
     * minutes, WhatsApp Business needs a provider account.
     */
    public const PROVIDERS = [
        'telegram' => [
            'icon' => 'ri-telegram-2-line',
            'tone' => 'warning',
            'fields' => [
                'bot_token' => [
                    'column' => 'telegram_bot_token',
                    'secret' => true,
                    'required' => true,
                    'input' => 'text',
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
                'sender' => [
                    'column' => 'telegram_sender',
                    'secret' => false,
                    'required' => false,
                    'input' => 'text',
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
            ],
        ],
        'smsaero' => [
            'icon' => 'ri-message-2-line',
            'tone' => 'primary',
            'fields' => [
                'email' => [
                    'column' => 'smsaero_email',
                    'secret' => false,
                    'required' => true,
                    'input' => 'email',
                    'rules' => ['nullable', 'email'],
                ],
                'api_key' => [
                    'column' => 'smsaero_api_key',
                    'secret' => true,
                    'required' => true,
                    'input' => 'text',
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
            ],
        ],
        'smtp' => [
            'icon' => 'ri-mail-send-line',
            'tone' => 'info',
            'fields' => [
                'host' => [
                    'column' => 'smtp_host',
                    'secret' => false,
                    'required' => true,
                    'input' => 'text',
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
                'port' => [
                    'column' => 'smtp_port',
                    'secret' => false,
                    'required' => true,
                    'input' => 'number',
                    'rules' => ['nullable', 'integer', 'between:1,65535'],
                ],
                'encryption' => [
                    'column' => 'smtp_encryption',
                    'secret' => false,
                    'required' => false,
                    'input' => 'select',
                    'options' => ['tls', 'ssl', 'starttls', 'none'],
                    'rules' => ['nullable', 'string', 'in:tls,ssl,starttls,none'],
                ],
                'username' => [
                    'column' => 'smtp_username',
                    'secret' => false,
                    'required' => true,
                    'input' => 'text',
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
                'password' => [
                    'column' => 'smtp_password',
                    'secret' => true,
                    'required' => true,
                    'input' => 'text',
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
                'from_address' => [
                    'column' => 'smtp_from_address',
                    'secret' => false,
                    'required' => true,
                    'input' => 'email',
                    'rules' => ['nullable', 'email'],
                ],
                'from_name' => [
                    'column' => 'smtp_from_name',
                    'secret' => false,
                    'required' => false,
                    'input' => 'text',
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
            ],
        ],
        'yookassa' => [
            'icon' => 'ri-bank-card-line',
            'tone' => 'danger',
            'fields' => [
                'shop_id' => [
                    'column' => 'yookassa_shop_id',
                    'secret' => false,
                    'required' => true,
                    'input' => 'text',
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
                'secret_key' => [
                    'column' => 'yookassa_secret_key',
                    'secret' => true,
                    'required' => true,
                    'input' => 'text',
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
            ],
        ],
        'whatsapp' => [
            'icon' => 'ri-whatsapp-line',
            'tone' => 'success',
            'fields' => [
                'api_key' => [
                    'column' => 'whatsapp_api_key',
                    'secret' => true,
                    'required' => true,
                    'input' => 'text',
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
                'sender' => [
                    'column' => 'whatsapp_sender',
                    'secret' => false,
                    'required' => true,
                    'input' => 'text',
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
            ],
        ],
    ];

    /**
     * @return array<int, string>
     */
    public static function providers(): array
    {
        return array_keys(self::PROVIDERS);
    }

    public static function has(string $provider): bool
    {
        return array_key_exists($provider, self::PROVIDERS);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function fields(string $provider): array
    {
        return self::PROVIDERS[$provider]['fields'] ?? [];
    }

    /**
     * @return array<string, string>  field => column
     */
    public static function columns(string $provider): array
    {
        return array_map(fn (array $field) => $field['column'], self::fields($provider));
    }

    /**
     * @return array<int, string>
     */
    public static function requiredFields(string $provider): array
    {
        return array_keys(array_filter(
            self::fields($provider),
            fn (array $field) => (bool) ($field['required'] ?? false)
        ));
    }

    public static function isSecret(string $provider, string $field): bool
    {
        return (bool) (self::PROVIDERS[$provider]['fields'][$field]['secret'] ?? false);
    }

    /**
     * Values as they are stored, secrets included. For the checker and for the
     * status, never for a response.
     *
     * @return array<string, string|null>
     */
    public static function values(?Setting $settings, string $provider): array
    {
        $values = [];

        foreach (self::fields($provider) as $field => $definition) {
            $value = $settings?->{$definition['column']};
            $values[$field] = $value === null || $value === '' ? null : (string) $value;
        }

        return $values;
    }

    /**
     * @return array<int, string>  required fields that are still empty
     */
    public static function missing(?Setting $settings, string $provider): array
    {
        $values = self::values($settings, $provider);

        return array_values(array_filter(
            self::requiredFields($provider),
            fn (string $field) => ($values[$field] ?? null) === null
        ));
    }

    public static function isFilled(?Setting $settings, string $provider): bool
    {
        return self::missing($settings, $provider) === [];
    }

    public static function hasAnyValue(?Setting $settings, string $provider): bool
    {
        return array_filter(self::values($settings, $provider), fn ($value) => $value !== null) !== [];
    }

    /**
     * A short signature of what is stored, so a check can be dropped the moment
     * one of the fields it approved changes.
     */
    public static function fingerprint(?Setting $settings, string $provider): string
    {
        return hash('sha256', json_encode(self::values($settings, $provider)) ?: '');
    }

    /**
     * The last four characters of a stored secret: enough to recognise the key
     * you pasted, useless to anyone reading over your shoulder.
     */
    public static function preview(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return '••••' . mb_substr($value, -4);
    }
}
