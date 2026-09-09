<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientPortalLoginRequest;
use App\Http\Requests\ClientPortalVerifyRequest;
use App\Models\Client;
use App\Services\ClientPortal\ClientPortalAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly ClientPortalAuthService $auth)
    {
    }

    public function login(ClientPortalLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->auth->startLogin((string) $validated['email']);

        return response()->json([
            'message' => __('client_portal.auth.code_sent'),
            'data' => $result,
        ]);
    }

    public function loginMagicLink(ClientPortalLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->auth->startMagicLink((string) $validated['email']);

        return response()->json([
            'message' => __('client_portal.auth.code_sent'),
            'data' => $result,
        ]);
    }

    public function verifyLogin(ClientPortalVerifyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->auth->verifyLogin(
            $validated['verification_id'] ?? null,
            $validated['code'] ?? null,
            $validated['selection_token'] ?? null,
            isset($validated['master_id']) ? (int) $validated['master_id'] : null,
        );

        return response()->json([
            'data' => $result,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();
        $client->loadMissing('user.setting');

        return response()->json([
            'data' => [
                'client' => $client,
                'master' => $this->resolveMasterPayload($client),
            ],
        ]);
    }

    /**
     * A Lite master's clients always see the default Veloria look regardless of
     * whatever is stored in `Setting.branding` — branding is a Pro/Elite perk
     * (Phase 10 gating table), and `hasProAccess()` decides that here, not just
     * in the settings UI, so a downgrade takes effect immediately for clients.
     */
    private function resolveMasterPayload(Client $client): array
    {
        $master = $client->user;
        $hasCustomBranding = (bool) $master?->hasProAccess();
        $branding = $master?->setting?->branding ?? [];

        return [
            'id' => $client->user_id,
            'name' => $master?->name,
            'avatar_url' => $master?->avatar_url,
            'has_custom_branding' => $hasCustomBranding,
            // Unlike branding, chat is open on every plan — the field stays
            // (the app already reads it to show/hide the Chat entry point)
            // but it's no longer tied to hasProAccess().
            'has_chat' => true,
            'branding' => $hasCustomBranding ? [
                'app_display_name' => $branding['app_display_name'] ?? null,
                'primary_color' => $branding['primary_color'] ?? null,
                'secondary_color' => $branding['secondary_color'] ?? null,
                'logo_url' => $branding['logo_url'] ?? null,
            ] : null,
        ];
    }
}
