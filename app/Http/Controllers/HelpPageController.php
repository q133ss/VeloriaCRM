<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HelpPageController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user('sanctum') ?? $request->user();

        return view($user instanceof User ? 'help.index' : 'help.guest');
    }
}
