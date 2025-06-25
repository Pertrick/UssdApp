<?php

namespace App\Http\Controllers;

use App\Models\UserActivity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $activities = UserActivity::with('user')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Activity/Index', [
            'activities' => $activities,
            'filters' => $request->only(['search', 'type']),
        ]);
    }

    public function getRecentActivities()
    {
        $activities = UserActivity::with('user')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json($activities);
    }
}
