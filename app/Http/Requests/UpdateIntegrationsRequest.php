<?php

namespace App\Http\Requests;

use App\Services\Integrations\IntegrationCatalog;

class UpdateIntegrationsRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The rules come from the same catalog the form and the checker read, so a
     * field cannot be required in one place and optional in another.
     */
    public function rules(): array
    {
        $rules = ['integrations' => ['required', 'array']];

        foreach (IntegrationCatalog::PROVIDERS as $provider => $definition) {
            foreach ($definition['fields'] as $field => $config) {
                $rules["integrations.{$provider}.{$field}"] = $config['rules'];
            }
        }

        return $rules;
    }
}
