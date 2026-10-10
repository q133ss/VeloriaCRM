<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SocialLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * VK ID sign-in through the VK ID web widget. The widget (with PKCE) hands the
 * browser an access token; we never trust it as is: it is checked by asking
 * VK ID who it belongs to, for our app id, and only that answer is used.
 */
class VkIdController extends Controller
{
    public function __invoke(Request $request, SocialLoginService $social): JsonResponse
    {
        $data = $request->validate(['access_token' => ['required', 'string', 'max:4096']]);

        $clientId = config('services.vkid.client_id');

        if (empty($clientId)) {
            return $this->fail(__('auth.social_login_not_configured', ['provider' => __('auth.providers.vkid')]), 503);
        }

        try {
            $response = Http::asForm()->timeout(10)->post('https://id.vk.ru/oauth2/user_info', [
                'client_id' => $clientId,
                'access_token' => $data['access_token'],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return $this->fail(__('auth.social_login_failed', ['provider' => __('auth.providers.vkid')]), 502);
        }

        $vk = $response->json('user');

        if (!$response->ok() || empty($vk['user_id'])) {
            return $this->fail(__('auth.social_login_failed', ['provider' => __('auth.providers.vkid')]), 401);
        }

        $name = trim(($vk['first_name'] ?? '') . ' ' . ($vk['last_name'] ?? ''));
        $user = $social->resolveUser('vkid', (string) $vk['user_id'], $vk['email'] ?? null, $name ?: null);

        if ($user->isSuspended()) {
            return $this->fail(__('auth.failed'), 403);
        }

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ]);
    }

    private function fail(string $message, int $status): JsonResponse
    {
        return response()->json(['error' => ['message' => $message]], $status);
    }
}
