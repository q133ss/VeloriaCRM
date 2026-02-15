<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientPortalLoginRequest;
use App\Http\Requests\ClientPortalRegisterRequest;
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

    public function register(ClientPortalRegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->auth->startRegister(
            (int) $validated['master_id'],
            (string) $validated['name'],
            (string) $validated['email'],
            (string) $validated['phone'],
        );

        return response()->json([
            'message' => __('client_portal.auth.code_sent'),
            'data' => $result,
        ]);
    }

    public function verifyRegister(ClientPortalVerifyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->auth->verifyRegister(
            (string) $validated['verification_id'],
            (string) $validated['code'],
        );

        return response()->json([
            'data' => $result,
        ], 200);
    }

    public function login(ClientPortalLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->auth->startLogin(
            (int) $validated['master_id'],
            (string) $validated['email'],
        );

        return response()->json([
            'message' => __('client_portal.auth.code_sent'),
            'data' => $result,
        ]);
    }

    public function verifyLogin(ClientPortalVerifyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->auth->verifyLogin(
            (string) $validated['verification_id'],
            (string) $validated['code'],
        );

        return response()->json([
            'data' => $result,
        ], 200);
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
