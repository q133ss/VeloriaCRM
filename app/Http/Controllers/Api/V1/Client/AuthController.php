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

        return response()->json([
            'data' => [
                'client' => $client,
            ],
        ]);
    }
}
