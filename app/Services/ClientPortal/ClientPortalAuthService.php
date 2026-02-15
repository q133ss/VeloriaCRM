<?php

namespace App\Services\ClientPortal;

use App\Mail\ClientOtpCodeMail;
use App\Models\Client;
use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ClientPortalAuthService extends BaseService
{
    private const CODE_TTL_MINUTES = 10;
    private const THROTTLE_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;

    public function startRegister(int $masterId, string $email, string $phone): array
    {
        $email = $this->normalizeEmail($email);
        $phone = $this->normalizePhone($phone);

        return $this->startOtpFlow('register', $masterId, $email, $phone);
    }

    public function verifyRegister(string $verificationId, string $code): array
    {
        $payload = $this->consumeOtp('register', $verificationId, $code);

        $masterId = (int) $payload['master_id'];
        $email = (string) $payload['email'];
        $phone = (string) $payload['phone'];

        $client = Client::query()
            ->where('user_id', $masterId)
            ->where('email', $email)
            ->first();

        if ($client && $this->normalizePhone((string) $client->phone) !== $phone) {
            $this->throwError('phone_mismatch', __('client_portal.auth.phone_mismatch'), [], 422);
        }

        $client = Client::query()->updateOrCreate(
            ['user_id' => $masterId, 'email' => $email],
            [
                'phone' => $phone,
                'name' => $this->deriveName($email, $phone),
            ]
        );

        $token = $client->createToken('client')->plainTextToken;

        return ['client' => $client, 'token' => $token];
    }

    public function startLogin(int $masterId, string $email): array
    {
        $email = $this->normalizeEmail($email);

        $clientExists = Client::query()
            ->where('user_id', $masterId)
            ->where('email', $email)
            ->exists();

        if (! $clientExists) {
            $this->throwError('not_found', __('client_portal.auth.client_not_found'), [], 404);
        }

        return $this->startOtpFlow('login', $masterId, $email, null);
    }

    public function verifyLogin(string $verificationId, string $code): array
    {
        $payload = $this->consumeOtp('login', $verificationId, $code);

        $masterId = (int) $payload['master_id'];
        $email = (string) $payload['email'];

        $client = Client::query()
            ->where('user_id', $masterId)
            ->where('email', $email)
            ->first();

        if (! $client) {
            $this->throwError('not_found', __('client_portal.auth.client_not_found'), [], 404);
        }

        $token = $client->createToken('client')->plainTextToken;

        return ['client' => $client, 'token' => $token];
    }

    private function startOtpFlow(string $type, int $masterId, string $email, ?string $phone): array
    {
        $throttleKey = 'client_portal:otp_throttle:' . sha1($type . '|' . $masterId . '|' . $email);
        if (Cache::has($throttleKey)) {
            $this->throwError('throttle', __('client_portal.auth.too_many_requests'), [], 429);
        }
        Cache::put($throttleKey, 1, now()->addSeconds(self::THROTTLE_SECONDS));

        $verificationId = (string) Str::uuid();
        $code = $this->generateCode();

        $cacheKey = $this->otpCacheKey($verificationId);
        Cache::put($cacheKey, [
            'type' => $type,
            'master_id' => $masterId,
            'email' => $email,
            'phone' => $phone,
            'code_hash' => $this->hashCode($code),
            'attempts' => 0,
        ], now()->addMinutes(self::CODE_TTL_MINUTES));

        Mail::to($email)->send(new ClientOtpCodeMail($code, self::CODE_TTL_MINUTES));

        return [
            'verification_id' => $verificationId,
            'expires_in' => self::CODE_TTL_MINUTES * 60,
        ];
    }

    private function consumeOtp(string $expectedType, string $verificationId, string $code): array
    {
        $cacheKey = $this->otpCacheKey($verificationId);
        $payload = Cache::get($cacheKey);

        if (! is_array($payload) || ($payload['type'] ?? null) !== $expectedType) {
            $this->throwError('invalid_code', __('client_portal.auth.invalid_or_expired'), [], 422);
        }

        $attempts = (int) ($payload['attempts'] ?? 0);
        $attempts++;
        $payload['attempts'] = $attempts;
        Cache::put($cacheKey, $payload, now()->addMinutes(self::CODE_TTL_MINUTES));

        if ($attempts > self::MAX_ATTEMPTS) {
            Cache::forget($cacheKey);
            $this->throwError('invalid_code', __('client_portal.auth.invalid_or_expired'), [], 422);
        }

        $expectedHash = (string) ($payload['code_hash'] ?? '');
        if (! hash_equals($expectedHash, $this->hashCode($code))) {
            $this->throwError('invalid_code', __('client_portal.auth.invalid_or_expired'), [], 422);
        }

        Cache::forget($cacheKey);

        return $payload;
    }

    private function otpCacheKey(string $verificationId): string
    {
        return 'client_portal:otp:' . $verificationId;
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function hashCode(string $code): string
    {
        $key = (string) config('app.key');
        return hash_hmac('sha256', $code, $key);
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\\D+/', '', $phone) ?? '';
    }

    private function deriveName(string $email, string $phone): string
    {
        $email = trim($email);
        if ($email !== '' && str_contains($email, '@')) {
            $local = trim((string) Str::before($email, '@'));
            if ($local !== '') {
                return $local;
            }
        }

        return $phone;
    }
}
