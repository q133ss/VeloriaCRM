<?php

namespace App\Services\ClientPortal;

use App\Mail\ClientOtpCodeMail;
use App\Models\Client;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ClientPortalAuthService extends BaseService
{
    private const CODE_TTL_MINUTES = 10;
    private const SELECTION_TTL_MINUTES = 10;
    private const THROTTLE_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;

    public function startLogin(string $email): array
    {
        $email = $this->normalizeEmail($email);

        if ($this->findClientsByEmail($email)->isEmpty()) {
            $this->throwError('not_found', __('client_portal.auth.client_not_found'), [], 404);
        }

        return $this->startOtpFlow('login', $email);
    }

    public function verifyLogin(?string $verificationId, ?string $code, ?string $selectionToken = null, ?int $masterId = null): array
    {
        if ($selectionToken !== null) {
            return $this->completeMasterSelection($selectionToken, $masterId);
        }

        $payload = $this->consumeOtp('login', (string) $verificationId, (string) $code);
        $email = (string) $payload['email'];
        $clients = $this->findClientsByEmail($email);

        if ($clients->isEmpty()) {
            $this->throwError('not_found', __('client_portal.auth.client_not_found'), [], 404);
        }

        $masterIds = $clients->pluck('user_id')->unique()->values();

        if ($masterIds->count() === 1) {
            $client = $clients->firstWhere('user_id', (int) $masterIds->first());

            if (! $client instanceof Client) {
                $this->throwError('not_found', __('client_portal.auth.client_not_found'), [], 404);
            }

            return $this->issueClientToken($client);
        }

        return [
            'requires_master_selection' => true,
            'selection_token' => $this->createSelectionToken($email),
            'expires_in' => self::SELECTION_TTL_MINUTES * 60,
            'masters' => $this->formatMasterChoices($clients),
        ];
    }

    private function startOtpFlow(string $type, string $email): array
    {
        $throttleKey = 'client_portal:otp_throttle:' . sha1($type . '|' . $email);
        if (Cache::has($throttleKey)) {
            $this->throwError('throttle', __('client_portal.auth.too_many_requests'), [], 429);
        }

        Cache::put($throttleKey, 1, now()->addSeconds(self::THROTTLE_SECONDS));

        $verificationId = (string) Str::uuid();
        $code = $this->generateCode();

        Cache::put($this->otpCacheKey($verificationId), [
            'type' => $type,
            'email' => $email,
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

    private function completeMasterSelection(string $selectionToken, ?int $masterId): array
    {
        $payload = Cache::get($this->selectionCacheKey($selectionToken));

        if (! is_array($payload) || ! isset($payload['email'])) {
            $this->throwError('invalid_selection', __('client_portal.auth.invalid_or_expired'), [], 422);
        }

        if ($masterId === null) {
            $this->throwError('invalid_selection', __('client_portal.auth.invalid_master_selection'), [], 422);
        }

        $client = Client::query()
            ->with('user')
            ->where('user_id', $masterId)
            ->where('email', (string) $payload['email'])
            ->orderBy('id')
            ->first();

        if (! $client) {
            $this->throwError('invalid_selection', __('client_portal.auth.invalid_master_selection'), [], 422);
        }

        Cache::forget($this->selectionCacheKey($selectionToken));

        return $this->issueClientToken($client);
    }

    private function findClientsByEmail(string $email): EloquentCollection
    {
        return Client::query()
            ->with('user')
            ->where('email', $email)
            ->orderBy('user_id')
            ->orderBy('id')
            ->get();
    }

    private function issueClientToken(Client $client): array
    {
        $client->loadMissing('user');

        return [
            'client' => $client,
            'master' => $client->user,
            'token' => $client->createToken('client')->plainTextToken,
        ];
    }

    private function formatMasterChoices(EloquentCollection $clients): array
    {
        return $clients
            ->unique('user_id')
            ->map(function (Client $client): array {
                return [
                    'master_id' => $client->user_id,
                    'master_name' => $client->user?->name ?: 'Master #' . $client->user_id,
                    'client_id' => $client->id,
                ];
            })
            ->values()
            ->all();
    }

    private function createSelectionToken(string $email): string
    {
        $selectionToken = (string) Str::uuid();

        Cache::put($this->selectionCacheKey($selectionToken), [
            'email' => $email,
        ], now()->addMinutes(self::SELECTION_TTL_MINUTES));

        return $selectionToken;
    }

    private function otpCacheKey(string $verificationId): string
    {
        return 'client_portal:otp:' . $verificationId;
    }

    private function selectionCacheKey(string $selectionToken): string
    {
        return 'client_portal:master_selection:' . $selectionToken;
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
}
