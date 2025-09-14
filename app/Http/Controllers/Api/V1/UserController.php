<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteUserRequest;

class UserController extends Controller
{
    public function destroy(DeleteUserRequest $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();
        return response()->noContent();
    }
}
