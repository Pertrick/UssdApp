<?php

namespace App\Http\Controllers;

use App\Models\USSD;
use App\Models\USSDSession;
use App\Models\USSDSessionLog;
use App\Services\USSDSessionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Exports\USSDAnalyticsExport;
use Maatwebsite\Excel\Facades\Excel;

class AnalyticsController extends Controller
{
    protected $sessionService;

    public function __construct(USSDSessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Show the main analytics dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        // Get user's USSD services
        $ussds = $user->ussds()->with('business')->get();
        
        // Get overall statistics for all USSD services
        $overallStats = $this->getOverallStatistics($user->id);
        
        // Get recent activity
        $recentActivity = $this->getRecentActivity($user->id);
        
        // Get top performing USSD services
        $topServices = $this->getTopPerformingServices($user->id);
        
        return Inertia::render('Analytics/Dashboard', [
            'ussds' => $ussds,
            'overallStats' => $overallStats,
            'recentActivity' => $recentActivity,
            'topServices' => $topServices,
        ]);
    }

    /**
     * Show detailed analytics for a specific USSD service
     */
    public function ussdAnalytics(USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        // Fix default date logic - ensure start_date is always before end_date
        $defaultEndDate = now()->format('Y-m-d');
        $defaultStartDate = now()->subDays(30)->format('Y-m-d');
        
        $startDate = request('start_date', $defaultStartDate);
        $endDate = request('end_date', $defaultEndDate);

        $analytics = $this->sessionService->getSessionAnalytics($ussd, Carbon::parse($startDate), Carbon::parse($endDate));
        
        // Get detailed charts data
        $chartsData = $this->getChartsData($ussd, $startDate, $endDate);
        
        // Get flow performance
        $flowPerformance = $this->getFlowPerformance($ussd, $startDate, $endDate);
        
        // Get error analysis
        $errorAnalysis = $this->getErrorAnalysis($ussd, $startDate, $endDate);

        return Inertia::render('Analytics/USSDAnalytics', [
            'ussd' => $ussd->load('business'),
            'analytics' => $analytics,
            'chartsData' => $chartsData,
            'flowPerformance' => $flowPerformance,
            'errorAnalysis' => $errorAnalysis,
            'dateRange' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Get overall statistics for all user's USSD services
     */
    private function getOverallStatistics(int $userId): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        
        $stats = DB::table('ussds')
            ->join('ussd_sessions', 'ussds.id', '=', 'ussd_sessions.ussd_id')
            ->where('ussds.user_id', $userId)
            ->where('ussd_sessions.created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('
                COUNT(DISTINCT ussd_sessions.id) as total_sessions,
                COUNT(DISTINCT CASE WHEN ussd_sessions.status = "completed" THEN ussd_sessions.id END) as completed_sessions,
                COUNT(DISTINCT CASE WHEN ussd_sessions.status = "error" THEN ussd_sessions.id END) as error_sessions,
                AVG(CASE WHEN ussd_sessions.status = "completed" THEN TIMESTAMPDIFF(SECOND, ussd_sessions.created_at, ussd_sessions.last_activity) END) as avg_session_duration
            ')
            ->first();

        $totalInteractions = USSDSessionLog::whereHas('ussd', function($query) use ($userId) {
            $query->where('user_id', $userId);
        })->where('action_timestamp', '>=', $thirtyDaysAgo)->count();

        return [
            'total_sessions' => $stats->total_sessions ?? 0,
            'completed_sessions' => $stats->completed_sessions ?? 0,
            'error_sessions' => $stats->error_sessions ?? 0,
            'total_interactions' => $totalInteractions,
            'avg_session_duration' => round($stats->avg_session_duration ?? 0, 2),
            'completion_rate' => $stats->total_sessions > 0 ? round(($stats->completed_sessions / $stats->total_sessions) * 100, 2) : 0,
            'error_rate' => $stats->total_sessions > 0 ? round(($stats->error_sessions / $stats->total_sessions) * 100, 2) : 0,
        ];
    }

    /**
     * Get recent activity for all user's USSD services
     */
    private function getRecentActivity(int $userId): array
    {
        return USSDSessionLog::whereHas('ussd', function($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->with(['ussd', 'flow'])
        ->orderBy('action_timestamp', 'desc')
        ->limit(20)
        ->get()
        ->map(function($log) {
            return [
                'id' => $log->id,
                'ussd_name' => $log->ussd->name,
                'action_type' => $log->action_type,
                'status' => $log->status,
                'timestamp' => $log->action_timestamp->format('Y-m-d H:i:s'),
                'flow_name' => $log->flow->name ?? 'N/A',
                'input_data' => $log->input_data,
                'output_data' => $log->output_data,
            ];
        })
        ->toArray();
    }

    /**
     * Get top performing USSD services
     */
    private function getTopPerformingServices(int $userId): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        
        return DB::table('ussds')
            ->leftJoin('ussd_sessions', 'ussds.id', '=', 'ussd_sessions.ussd_id')
            ->where('ussds.user_id', $userId)
            ->where('ussd_sessions.created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('
                ussds.id,
                ussds.name,
                ussds.pattern,
                COUNT(ussd_sessions.id) as session_count,
                COUNT(CASE WHEN ussd_sessions.status = "completed" THEN ussd_sessions.id END) as completed_count,
                AVG(CASE WHEN ussd_sessions.status = "completed" THEN TIMESTAMPDIFF(SECOND, ussd_sessions.created_at, ussd_sessions.last_activity) END) as avg_duration
            ')
            ->groupBy('ussds.id', 'ussds.name', 'ussds.pattern')
            ->orderBy('session_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'pattern' => $service->pattern,
                    'session_count' => $service->session_count,
                    'completed_count' => $service->completed_count,
                    'avg_duration' => round($service->avg_duration ?? 0, 2),
                    'completion_rate' => $service->session_count > 0 ? round(($service->completed_count / $service->session_count) * 100, 2) : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get charts data for a specific USSD service
     */
    private function getChartsData(USSD $ussd, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        // Daily sessions chart
        $dailySessions = USSDSession::where('ussd_id', $ussd->id)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                ];
            });

        // Hourly distribution
        $hourlyDistribution = USSDSession::where('ussd_id', $ussd->id)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(function($item) {
                return [
                    'hour' => $item->hour,
                    'count' => $item->count,
                ];
            });

        // Action type distribution
        $actionTypeDistribution = USSDSessionLog::where('ussd_id', $ussd->id)
            ->where('action_timestamp', '>=', $start)
            ->where('action_timestamp', '<=', $end)
            ->selectRaw('action_type, COUNT(*) as count')
            ->groupBy('action_type')
            ->get()
            ->map(function($item) {
                return [
                    'action_type' => $item->action_type,
                    'count' => $item->count,
                ];
            });

        return [
            'daily_sessions' => $dailySessions,
            'hourly_distribution' => $hourlyDistribution,
            'action_type_distribution' => $actionTypeDistribution,
        ];
    }

    /**
     * Get flow performance for a specific USSD service
     */
    private function getFlowPerformance(USSD $ussd, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        return USSDSessionLog::where('ussd_id', $ussd->id)
            ->where('action_timestamp', '>=', $start)
            ->where('action_timestamp', '<=', $end)
            ->whereNotNull('flow_id')
            ->with('flow')
            ->selectRaw('flow_id, action_type, COUNT(*) as count')
            ->groupBy('flow_id', 'action_type')
            ->get()
            ->groupBy('flow_id')
            ->map(function($flowLogs, $flowId) {
                $flow = $flowLogs->first()->flow;
                $totalInteractions = $flowLogs->sum('count');
                $errorCount = $flowLogs->where('action_type', 'error')->sum('count');
                
                return [
                    'flow_id' => $flowId,
                    'flow_name' => $flow->name ?? 'Unknown',
                    'total_interactions' => $totalInteractions,
                    'error_count' => $errorCount,
                    'error_rate' => $totalInteractions > 0 ? round(($errorCount / $totalInteractions) * 100, 2) : 0,
                    'action_breakdown' => $flowLogs->map(function($log) {
                        return [
                            'action_type' => $log->action_type,
                            'count' => $log->count,
                        ];
                    })->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get error analysis for a specific USSD service
     */
    private function getErrorAnalysis(USSD $ussd, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        $errors = USSDSessionLog::where('ussd_id', $ussd->id)
            ->where('status', 'error')
            ->where('action_timestamp', '>=', $start)
            ->where('action_timestamp', '<=', $end)
            ->with('flow')
            ->orderBy('action_timestamp', 'desc')
            ->limit(50)
            ->get()
            ->map(function($error) {
                return [
                    'id' => $error->id,
                    'action_type' => $error->action_type,
                    'error_message' => $error->error_message,
                    'flow_name' => $error->flow->name ?? 'Unknown',
                    'timestamp' => $error->action_timestamp->format('Y-m-d H:i:s'),
                    'input_data' => $error->input_data,
                ];
            });

        $errorTypes = USSDSessionLog::where('ussd_id', $ussd->id)
            ->where('status', 'error')
            ->where('action_timestamp', '>=', $start)
            ->where('action_timestamp', '<=', $end)
            ->selectRaw('action_type, COUNT(*) as count')
            ->groupBy('action_type')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function($error) {
                return [
                    'action_type' => $error->action_type,
                    'count' => $error->count,
                ];
            });

        return [
            'recent_errors' => $errors,
            'error_types' => $errorTypes,
        ];
    }

    /**
     * Export analytics data
     */
    public function export(Request $request, ?USSD $ussd = null)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $format = $request->input('format', 'excel');

        if ($ussd) {
            if ($ussd->user_id !== Auth::id()) {
                abort(403);
            }
            if ($format === 'csv' || $format === 'xlsx' || $format === 'excel') {
                $export = new USSDAnalyticsExport($ussd, $startDate, $endDate);
                $filename = $ussd->name . '_analytics_' . date('Y-m-d') . '.xlsx';
                return Excel::download($export, $filename);
            }
            $data = $this->getUSSDAnalyticsData($ussd, $startDate, $endDate);
        } else {
            // Optionally implement all-USSD export
            $data = $this->getAllUSSDsAnalyticsData(Auth::id(), $startDate, $endDate);
        }

        return response()->json($data);
    }

    /**
     * Get analytics data for export
     */
    private function getUSSDAnalyticsData(USSD $ussd, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $sessions = USSDSession::where('ussd_id', $ussd->id)
            ->whereBetween('created_at', [$start, $end])
            ->with('logs')
            ->get();

        $logs = USSDSessionLog::where('ussd_id', $ussd->id)
            ->whereBetween('action_timestamp', [$start, $end])
            ->with('flow')
            ->get();

        return [
            'ussd_info' => [
                'id' => $ussd->id,
                'name' => $ussd->name,
                'pattern' => $ussd->pattern,
                'business' => $ussd->business->business_name ?? 'N/A',
            ],
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'total_sessions' => $sessions->count(),
                'completed_sessions' => $sessions->where('status', 'completed')->count(),
                'error_sessions' => $sessions->where('status', 'error')->count(),
                'total_interactions' => $logs->count(),
                'error_interactions' => $logs->where('status', 'error')->count(),
            ],
            'sessions' => $sessions->map(function($session) {
                return [
                    'session_id' => $session->session_id,
                    'phone_number' => $session->phone_number,
                    'status' => $session->status,
                    'step_count' => $session->step_count,
                    'created_at' => $session->created_at->format('Y-m-d H:i:s'),
                    'last_activity' => $session->last_activity?->format('Y-m-d H:i:s'),
                    'duration_seconds' => $session->last_activity ? $session->created_at->diffInSeconds($session->last_activity) : 0,
                ];
            }),
            'interactions' => $logs->map(function($log) {
                return [
                    'action_type' => $log->action_type,
                    'status' => $log->status,
                    'input_data' => $log->input_data,
                    'output_data' => $log->output_data,
                    'response_time' => $log->response_time,
                    'error_message' => $log->error_message,
                    'flow_name' => $log->flow->name ?? 'N/A',
                    'timestamp' => $log->action_timestamp->format('Y-m-d H:i:s'),
                ];
            }),
        ];
    }

    /**
     * Get analytics data for all USSD services
     */
    private function getAllUSSDsAnalyticsData(int $userId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $ussds = USSD::where('user_id', $userId)->with('business')->get();
        
        $summary = [];
        $ussdData = [];

        foreach ($ussds as $ussd) {
            $data = $this->getUSSDAnalyticsData($ussd, $start, $end);
            $ussdData[] = $data;
            
            $summary['total_ussds'] = count($ussds);
            $summary['total_sessions'] = ($summary['total_sessions'] ?? 0) + $data['summary']['total_sessions'];
            $summary['total_interactions'] = ($summary['total_interactions'] ?? 0) + $data['summary']['total_interactions'];
        }

        return [
            'summary' => $summary,
            'ussd_services' => $ussdData,
        ];
    }
}
