<?php

namespace App\Services;

use App\Models\USSD;
use App\Models\USSDFlow;
use App\Models\USSDFlowOption;
use App\Models\USSDSession;
use App\Models\USSDSessionLog;
use Illuminate\Support\Str;
use Carbon\Carbon;

class USSDSessionService
{
    /**
     * Start a new USSD session
     */
    public function startSession(USSD $ussd, ?string $phoneNumber = null, ?string $userAgent = null, ?string $ipAddress = null): USSDSession
    {
        // Get the root flow for this USSD
        $rootFlow = USSDFlow::where('ussd_id', $ussd->id)
            ->where('is_root', true)
            ->where('is_active', true)
            ->first();

        if (!$rootFlow) {
            throw new \Exception('No root flow found for this USSD service');
        }

        // Create session
        $session = USSDSession::create([
            'ussd_id' => $ussd->id,
            'session_id' => Str::uuid(),
            'phone_number' => $phoneNumber,
            'current_flow_id' => $rootFlow->id,
            'status' => 'active',
            'step_count' => 0,
            'last_activity' => now(),
            'expires_at' => now()->addMinutes(30), // 30 minute timeout
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
        ]);

        // Log the session start
        $this->logSessionAction($session, 'session_start', null, $rootFlow->menu_text);

        return $session;
    }

    /**
     * Process user input and navigate to next flow
     */
    public function processInput(USSDSession $session, string $input): array
    {
        $startTime = microtime(true);
        
        try {
            // Update session activity
            $session->update([
                'last_activity' => now(),
                'step_count' => $session->step_count + 1,
            ]);

            $currentFlow = $session->currentFlow;
            
            if (!$currentFlow) {
                throw new \Exception('No current flow found');
            }

            // Find the option that matches user input
            $selectedOption = USSDFlowOption::where('flow_id', $currentFlow->id)
                ->where('option_value', $input)
                ->where('is_active', true)
                ->first();

            if (!$selectedOption) {
                // Invalid input - show error message
                $errorMessage = "Invalid option. Please try again.\n\n" . $currentFlow->menu_text;
                $this->logSessionAction($session, 'invalid_input', $input, $errorMessage, 'error');
                
                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'requires_input' => true,
                    'current_flow' => $currentFlow,
                ];
            }

            // Log the user input
            $this->logSessionAction($session, 'user_input', $input, $selectedOption->option_text);

            // Handle different action types
            $response = $this->handleAction($session, $selectedOption);
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Log the response
            $this->logSessionAction(
                $session, 
                'menu_display', 
                null, 
                $response['message'], 
                'success',
                $responseTime
            );

            return $response;

        } catch (\Exception $e) {
            $errorMessage = "An error occurred. Please try again.";
            $this->logSessionAction($session, 'error', $input, $errorMessage, 'error', null, $e->getMessage());
            
            return [
                'success' => false,
                'message' => $errorMessage,
                'requires_input' => true,
                'current_flow' => $currentFlow,
            ];
        }
    }

    /**
     * Handle different action types
     */
    private function handleAction(USSDSession $session, USSDFlowOption $option): array
    {
        switch ($option->action_type) {
            case 'navigate':
                return $this->handleNavigation($session, $option);
                
            case 'message':
                return $this->handleMessage($session, $option);
                
            case 'end_session':
                return $this->handleEndSession($session, $option);
                
            case 'api_call':
                return $this->handleApiCall($session, $option);
                
            default:
                return $this->handleNavigation($session, $option);
        }
    }

    /**
     * Handle navigation to next flow
     */
    private function handleNavigation(USSDSession $session, USSDFlowOption $option): array
    {
        if (!$option->next_flow_id) {
            // No next flow - end session
            return $this->handleEndSession($session, $option);
        }

        $nextFlow = USSDFlow::find($option->next_flow_id);
        
        if (!$nextFlow || !$nextFlow->is_active) {
            throw new \Exception('Next flow not found or inactive');
        }

        // Update session to new flow
        $session->update([
            'current_flow_id' => $nextFlow->id,
        ]);

        return [
            'success' => true,
            'message' => $nextFlow->menu_text,
            'requires_input' => true,
            'current_flow' => $nextFlow,
            'options' => $nextFlow->options()->where('is_active', true)->orderBy('sort_order')->get(),
        ];
    }

    /**
     * Handle message response
     */
    private function handleMessage(USSDSession $session, USSDFlowOption $option): array
    {
        $message = $option->action_data['message'] ?? 'Thank you for using our service.';
        
        // End session after showing message
        $session->update(['status' => 'completed']);
        
        return [
            'success' => true,
            'message' => $message,
            'requires_input' => false,
            'session_ended' => true,
        ];
    }

    /**
     * Handle session end
     */
    private function handleEndSession(USSDSession $session, USSDFlowOption $option): array
    {
        $message = $option->action_data['message'] ?? 'Thank you for using our service.';
        
        $session->update(['status' => 'completed']);
        
        return [
            'success' => true,
            'message' => $message,
            'requires_input' => false,
            'session_ended' => true,
        ];
    }

    /**
     * Handle API call (simulated)
     */
    private function handleApiCall(USSDSession $session, USSDFlowOption $option): array
    {
        // Simulate API call delay
        usleep(500000); // 0.5 seconds
        
        $apiData = $option->action_data;
        $message = $apiData['success_message'] ?? 'Operation completed successfully.';
        
        // Store result in session data
        $sessionData = $session->session_data ?? [];
        $sessionData['last_api_result'] = $apiData;
        $session->update(['session_data' => $sessionData]);
        
        return [
            'success' => true,
            'message' => $message,
            'requires_input' => false,
            'session_ended' => true,
        ];
    }

    /**
     * Log session action for monitoring
     */
    private function logSessionAction(
        USSDSession $session, 
        string $actionType, 
        ?string $inputData, 
        ?string $outputData, 
        string $status = 'success',
        ?string $responseTime = null,
        ?string $errorMessage = null
    ): void {
        USSDSessionLog::create([
            'session_id' => $session->id,
            'ussd_id' => $session->ussd_id,
            'flow_id' => $session->current_flow_id,
            'action_type' => $actionType,
            'input_data' => $inputData,
            'output_data' => $outputData,
            'response_time' => $responseTime,
            'status' => $status,
            'error_message' => $errorMessage,
            'metadata' => [
                'user_agent' => $session->user_agent,
                'ip_address' => $session->ip_address,
                'step_count' => $session->step_count,
            ],
            'action_timestamp' => now(),
        ]);
    }

    /**
     * Get session analytics
     */
    public function getSessionAnalytics(USSD $ussd, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $sessions = USSDSession::where('ussd_id', $ussd->id)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        $logs = USSDSessionLog::where('ussd_id', $ussd->id)
            ->whereDate('action_timestamp', '>=', $startDate)
            ->whereDate('action_timestamp', '<=', $endDate);

        return [
            'total_sessions' => $sessions->count(),
            'completed_sessions' => $sessions->where('status', 'completed')->count(),
            'active_sessions' => $sessions->where('status', 'active')->count(),
            'error_sessions' => $sessions->where('status', 'error')->count(),
            'total_interactions' => $logs->count(),
            'average_session_duration' => $this->calculateAverageSessionDuration($ussd->id, $startDate, $endDate),
            'completion_rate' => $this->calculateCompletionRate($ussd->id, $startDate, $endDate),
            'error_rate' => $this->calculateErrorRate($ussd->id, $startDate, $endDate),
        ];
    }

    /**
     * Calculate average session duration
     */
    private function calculateAverageSessionDuration(int $ussdId, Carbon $startDate, Carbon $endDate): float
    {
        $sessions = USSDSession::where('ussd_id', $ussdId)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->where('status', 'completed')
            ->whereNotNull('last_activity')
            ->get();

        if ($sessions->isEmpty()) {
            return 0;
        }


        $totalDuration = $sessions->sum(function ($session) {

            if (!$session->created_at || !$session->last_activity) {
                return 0;
            }
            
            $duration = $session->last_activity->diffInSeconds($session->created_at, true);
            
            return max(0, $duration);
        });

        return round($totalDuration / $sessions->count(), 2);
    }

    /**
     * Calculate completion rate
     */
    private function calculateCompletionRate(int $ussdId, Carbon $startDate, Carbon $endDate): float
    {
        $totalSessions = USSDSession::where('ussd_id', $ussdId)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->count();

        if ($totalSessions === 0) {
            return 0;
        }

        $completedSessions = USSDSession::where('ussd_id', $ussdId)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->where('status', 'completed')
            ->count();

        return round(($completedSessions / $totalSessions) * 100, 2);
    }

    /**
     * Calculate error rate
     */
    private function calculateErrorRate(int $ussdId, Carbon $startDate, Carbon $endDate): float
    {
        $totalLogs = USSDSessionLog::where('ussd_id', $ussdId)
            ->whereDate('action_timestamp', '>=', $startDate)
            ->whereDate('action_timestamp', '<=', $endDate)
            ->count();

        if ($totalLogs === 0) {
            return 0;
        }

        $errorLogs = USSDSessionLog::where('ussd_id', $ussdId)
            ->whereDate('action_timestamp', '>=', $startDate)
            ->whereDate('action_timestamp', '<=', $endDate)
            ->where('status', 'error')
            ->count();

        return round(($errorLogs / $totalLogs) * 100, 2);
    }
}
