<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use App\Models\ProspectVisit;
use App\Models\User;
use App\Services\Prospects\ProspectVisitRecorder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * The master takes over the account built for her: she sets her own email
 * and password, the link stops working, and she lands in her cabinet. No
 * login or password has to travel through the chat.
 */
class ProspectClaimController extends Controller
{
    public function show(Request $request, string $token, ProspectVisitRecorder $visits): View
    {
        $prospect = $this->find($token);
        $visits->record($prospect, ProspectVisit::KIND_CLAIM, $request);

        return view('prospects.claim', [
            'prospect' => $prospect,
            'token' => $token,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $prospect = $this->find($token);
        $user = $prospect->user;

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique' => 'На эту почту уже есть аккаунт. Войдите в него или укажите другую почту.',
            'password.min' => 'Пароль должен быть не короче 8 символов.',
            'password.confirmed' => 'Пароли не совпадают.',
        ]);

        DB::transaction(function () use ($user, $prospect, $data) {
            $user->forceFill([
                'email' => Str::lower(trim($data['email'])),
                'password' => $data['password'],
                'remember_token' => Str::random(60),
            ])->save();
            $user->tokens()->delete();

            $prospect->forceFill([
                'status' => Prospect::STATUS_CLAIMED,
                'claimed_at' => now(),
                'claim_token' => null,
                'password' => null,
            ])->save();
        });

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $sameSite = config('session.same_site', 'lax');
        $cookie = cookie(
            'token',
            $user->createToken('api')->plainTextToken,
            60 * 24 * 30,
            '/',
            null,
            config('session.secure', false),
            false,
            false,
            $sameSite ? strtolower((string) $sameSite) : null
        );

        return redirect('/dashboard')->withCookie($cookie);
    }

    private function find(string $token): Prospect
    {
        $prospect = Prospect::query()->pending()->with('user')->where('claim_token', $token)->first();

        if (! $prospect || ! $prospect->user instanceof User) {
            abort(404);
        }

        return $prospect;
    }
}
