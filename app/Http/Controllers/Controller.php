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
        $performanceStats = null;
        
        if ($user) {
            $ussds = $user->ussds();
            $ussdStats = [
                'total' => $ussds->count(),
                'active' => $ussds->where('is_active', true)->count(),
                'inactive' => $ussds->where('is_active', false)->count()
            ];
            
            // Get performance stats (last 30 days)
            $thirtyDaysAgo = now()->subDays(30);
            
            // Calculate success rate (completion rate)
            $totalSessions = \App\Models\USSDSession::whereHas('ussd', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('created_at', '>=', $thirtyDaysAgo)->count();
            
            $completedSessions = \App\Models\USSDSession::whereHas('ussd', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('created_at', '>=', $thirtyDaysAgo)
            ->where('status', 'completed')->count();
            
            $successRate = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100, 1) : 0;
            
            // Calculate average response time
            $avgResponseTime = \App\Models\USSDSessionLog::whereHas('ussd', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('action_timestamp', '>=', $thirtyDaysAgo)
            ->whereNotNull('response_time')
            ->where('response_time', '>', 0)
            ->avg('response_time');
            
            $avgResponseTimeSeconds = $avgResponseTime ? round($avgResponseTime / 1000, 1) : 0; // Convert ms to seconds
            
            $performanceStats = [
                'success_rate' => $successRate,
                'avg_response_time' => $avgResponseTimeSeconds,
            ];
        }

        return Inertia::render('Dashboard', [
            'user' => $user,
            'business' => $business,
            'ussdStats' => $ussdStats,
            'performanceStats' => $performanceStats,
            'recentActivities' => $user ? $user->activities()->orderBy('created_at', 'desc')->limit(5)->get() : []
        ]);
    }
}
