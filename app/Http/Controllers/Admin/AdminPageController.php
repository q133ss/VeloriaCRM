<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AdminPageController extends Controller
{
    public function overview(): View
    {
        return view('admin.overview');
    }

    public function users(): View
    {
        return view('admin.users.index');
    }

    public function useful(): View
    {
        return view('admin.useful.index');
    }

    public function support(): View
    {
        return view('admin.support.index');
    }

    public function audit(): View
    {
        return view('admin.audit.index');
    }
}
