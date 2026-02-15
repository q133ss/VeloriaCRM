<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function destroy(DeleteUserRequest $request)
    {
        $user = $request->user();

        if (is_string($user->avatar_path) && $user->avatar_path !== '') {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->tokens()->delete();
        $user->delete();
        return response()->noContent();
    }

    public function updateAvatar(Request $request)
    {
        $data = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
        ]);

        $user = $request->user();

        if (is_string($user->avatar_path) && $user->avatar_path !== '') {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $data['avatar']->store('avatars', 'public');
        $user->avatar_path = $path;
        $user->save();

        return response()->json([
            'avatar_url' => $user->avatar_url,
            'initials' => $user->initials,
        ]);
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if (is_string($user->avatar_path) && $user->avatar_path !== '') {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->avatar_path = null;
        $user->save();

        return response()->json([
            'avatar_url' => null,
            'initials' => $user->initials,
        ]);
    }
}
