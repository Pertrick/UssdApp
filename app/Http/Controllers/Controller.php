<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function dashboard()
    {
        $user = Auth::user();
        $business = $user ? $user->primaryBusiness() : null;

        return Inertia::render('Dashboard', [
            'user' => $user,
            'business' => $business,
        ]);
    }
}
