<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request)
    {
        $user = $this->authService->registerUser($request->validated());
        return response()->json(['user' => $user], 201);
    }

    public function login(LoginRequest $request)
    {
        $data = $this->authService->authenticateUser($request->validated());
        return response()->json($data);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => __('auth.logout_success')]);
    }

    public function sendResetLink(ForgotPasswordRequest $request)
    {
        $this->authService->sendResetLink($request->validated()['email']);
        return response()->json(['message' => __('auth.link_sent')]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $this->authService->resetPassword($request->validated());
        return response()->json(['message' => __('auth.reset_password')]);
    }
}
