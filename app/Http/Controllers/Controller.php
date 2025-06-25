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
        $business = $user ? $user->primaryBusiness : null;
        
        // Get USSD statistics for the user
        $ussdStats = null;
        if ($user) {
            $ussds = $user->ussds();
            $ussdStats = [
                'total' => $ussds->count(),
                'active' => $ussds->where('is_active', true)->count(),
                'inactive' => $ussds->where('is_active', false)->count()
            ];
        }

        return Inertia::render('Dashboard', [
            'user' => $user,
            'business' => $business,
            'ussdStats' => $ussdStats,
            'recentActivities' => $user->activities()->orderBy('created_at', 'desc')->limit(5)->get()
        ]);
    }
}
