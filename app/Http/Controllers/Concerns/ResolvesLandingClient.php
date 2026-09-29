<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Client;
use App\Models\Landing;

/**
 * Finds or creates the master's client card for a person who filled in a form on
 * her landing page (a request or a booking).
 */
trait ResolvesLandingClient
{
    private function resolveLandingClient(Landing $landing, array $validated): ?Client
    {
        $phone = trim((string) ($validated['client_phone'] ?? ''));
        $email = trim((string) ($validated['client_email'] ?? ''));

        if ($phone === '' && $email === '') {
            return null;
        }

        $client = Client::query()
            ->where('user_id', $landing->user_id)
            ->when($phone !== '', fn ($query) => $query->where('phone', $phone))
            ->when($phone === '' && $email !== '', fn ($query) => $query->where('email', $email))
            ->first();

        if ($client) {
            $client->forceFill([
                'name' => $validated['client_name'] ?: $client->name,
                'phone' => $phone !== '' ? $phone : $client->phone,
                'email' => $email !== '' ? $email : $client->email,
                'notes' => $client->notes,
            ])->save();

            return $client;
        }

        return Client::query()->create([
            'user_id' => $landing->user_id,
            'name' => $validated['client_name'],
            'phone' => $phone,
            'email' => $email !== '' ? $email : null,
        ]);
    }
}
