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
        $query = UserActivity::with('user')
            ->where('user_id', Auth::id());

        // Apply type filter if provided
        if ($request->filled('type')) {
            $query->where('activity_type', $request->input('type'));
        }

        // Apply search filter if provided (search in description for now)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('description', 'like', '%' . $search . '%');
        }

        $activities = $query
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

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
