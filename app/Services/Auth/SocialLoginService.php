<?php

namespace App\Services\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialLoginService
{
    /**
     * Provider id first; then an existing account with the same email;
     * otherwise a new account. Email may be missing.
     */
    public function resolveUser(string $provider, string $providerId, ?string $email, ?string $name): User
    {
        $email = $email ? Str::lower(trim($email)) : null;

        $linked = SocialAccount::where('provider', $provider)->where('provider_id', $providerId)->first();

        if ($linked) {
            return $linked->user;
        }

        return DB::transaction(function () use ($provider, $providerId, $email, $name) {
            $user = $email ? User::where('email', $email)->first() : null;

            if (!$user) {
                $user = new User([
                    'name' => $name ?: ($email ?: 'Veloria'),
                    'email' => $email,
                ]);
                $user->password = Hash::make(Str::random(40));
                $user->email_verified_at = $email ? now() : null;
                $user->save();
            }

            $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_id' => $providerId,
                'email' => $email,
            ]);

            return $user;
        });
    }
}
