<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthService extends BaseService
{
    public function registerUser(array $data): User
    {
        try {
            $data['password'] = Hash::make($data['password']);
            return User::create($data);
        } catch (\Throwable $e) {
            $this->throwError('registration_error', __('auth.registration_failed'));
        }
    }

    public function authenticateUser(array $credentials): array
    {
        if (!Auth::attempt($credentials)) {
            $this->throwError('unauthorized', __('auth.failed'), [], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('api')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function sendResetLink(string $email): void
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            $this->throwError('email_error', __($status));
        }
    }

    public function resetPassword(array $data): void
    {
        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            $this->throwError('reset_error', __($status));
        }
    }
}
