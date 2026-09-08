<?php

namespace App\Http\Controllers\Api\V1\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

abstract class MarketingController extends Controller
{
    protected function currentUserId(): int
    {
        $userId = Auth::guard('sanctum')->id();

        if (! $userId) {
            abort(403);
        }

        return $userId;
    }

    protected function resolveUserSettings(): ?Setting
    {
        $userId = Auth::guard('sanctum')->id();

        if (! $userId) {
            return null;
        }

        return Setting::where('user_id', $userId)->first();
    }

    protected function userHasProAccess(): bool
    {
        return (bool) Auth::guard('sanctum')->user()?->hasProAccess();
    }

    protected function userHasEliteAccess(): bool
    {
        return (bool) Auth::guard('sanctum')->user()?->hasEliteAccess();
    }
}
