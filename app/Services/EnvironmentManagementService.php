<?php

namespace App\Services;

use App\Models\USSD;
use App\Models\Business;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EnvironmentManagementService
{
    /**
     * Get comprehensive environment status for a USSD
     */
    public function getEnvironmentStatus(USSD $ussd): array
    {
        $requirements = $this->getRequirements($ussd);
        $status = $ussd->getProductionStatus();
        
        return [
            'current_environment' => $ussd->environment,
            'is_live' => $status['is_live'],
            'is_testing' => $status['is_testing'],
            'can_go_live' => $status['can_go_live'],
            'requirements' => $requirements,
            'all_requirements_met' => $this->allRequirementsMet($requirements),
            'current_ussd_code' => $status['current_ussd_code'],
            'gateway_provider' => $status['gateway_provider'],
            'webhook_url' => $status['webhook_url'],
            'business_verified' => $status['business_verified'],
            'has_credentials' => $status['has_credentials'],
            'has_webhook' => $status['has_webhook'],
            'last_environment_change' => $this->getLastEnvironmentChange($ussd),
            'session_stats' => $this->getSessionStats($ussd),
        ];
    }

    /**
     * Get detailed requirements checklist
     */
    public function getRequirements(USSD $ussd): array
    {
        $business = $ussd->business;
        
        return [
            'business_verification' => [
                'title' => 'Business Verification',
                'description' => 'Your business must be verified to go live',
                'status' => $business && $business->registration_status->value === 'verified',
                'details' => $business ? "Status: {$business->registration_status->value}" : 'No business associated',
                'required' => true,
                'priority' => 'critical'
            ],
            'gateway_configuration' => [
                'title' => 'Gateway Configuration',
                'description' => 'Configure your USSD gateway provider (e.g., AfricasTalking)',
                'status' => $this->hasValidGatewayConfiguration($ussd),
                'details' => $this->getGatewayConfigurationDetails($ussd),
                'required' => true,
                'priority' => 'critical'
            ],
            'webhook_url' => [
                'title' => 'Webhook URL',
                'description' => 'Set up webhook URL for receiving USSD requests',
                'status' => !empty($ussd->webhook_url),
                'details' => $ussd->webhook_url ? "URL: {$ussd->webhook_url}" : 'No webhook URL set',
                'required' => true,
                'priority' => 'critical'
            ],
            'flows_created' => [
                'title' => 'USSD Flows',
                'description' => 'Create at least one USSD flow',
                'status' => $ussd->flows()->count() > 0,
                'details' => $ussd->flows()->count() . ' flow(s) created',
                'required' => true,
                'priority' => 'high'
            ],
            'root_flow' => [
                'title' => 'Root Flow',
                'description' => 'Ensure you have a root flow configured',
                'status' => $ussd->rootFlow() !== null,
                'details' => $ussd->rootFlow() ? 'Root flow exists' : 'No root flow found',
                'required' => true,
                'priority' => 'high'
            ],
            'production_balance' => [
                'title' => 'Production Balance',
                'description' => 'Ensure you have sufficient production balance',
                'status' => $business && $business->account_balance > 0,
                'details' => $business ? "Balance: $" . number_format($business->account_balance, 2) : 'No business found',
                'required' => true,
                'priority' => 'high'
            ],
            'testing_completed' => [
                'title' => 'Testing Completed',
                'description' => 'Test your USSD flows in testing environment',
                'status' => $this->hasTestingActivity($ussd),
                'details' => $this->getTestingActivityDetails($ussd),
                'required' => false,
                'priority' => 'medium'
            ],
            'live_ussd_code' => [
                'title' => 'Live USSD Code',
                'description' => 'Configure your live USSD code',
                'status' => !empty($ussd->live_ussd_code),
                'details' => $ussd->live_ussd_code ? "Code: {$ussd->live_ussd_code}" : 'No live USSD code set',
                'required' => true,
                'priority' => 'high'
            ],
        ];
    }

    /**
     * Check if all critical requirements are met
     */
    public function allRequirementsMet(array $requirements): bool
    {
        foreach ($requirements as $requirement) {
            if ($requirement['required'] && !$requirement['status']) {
                return false;
            }
        }
        return true;
    }

    /**
     * Switch USSD to production environment with safety checks
     */
    public function switchToProduction(USSD $ussd): array
    {
        try {
            DB::beginTransaction();

            // Validate requirements
            $requirements = $this->getRequirements($ussd);
            if (!$this->allRequirementsMet($requirements)) {
                return [
                    'success' => false,
                    'message' => 'Cannot go live: Some requirements are not met',
                    'requirements' => $requirements
                ];
            }

            // Additional safety checks
            if (!$this->performSafetyChecks($ussd)) {
                return [
                    'success' => false,
                    'message' => 'Safety checks failed. Please review your configuration.',
                    'requirements' => $requirements
                ];
            }

            // Refresh the model to ensure we have the latest data
            $ussd->refresh();
            $ussd->load('business');

            // Switch to production
            $success = $ussd->goLive();
            
            if ($success) {
                DB::commit();
                
                // Log the environment change
                $this->logEnvironmentChange($ussd, 'testing', 'live', 'User initiated go live');
                
                // Log activity
                ActivityService::logUSSDLive(Auth::id(), $ussd->id, $ussd->name);
                
                return [
                    'success' => true,
                    'message' => 'USSD is now live! Your service is available for production use.',
                    'environment' => 'live',
                    'ussd_code' => $ussd->live_ussd_code
                ];
            } else {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Failed to switch to production. Please try again.',
                    'requirements' => $requirements
                ];
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to switch to production', [
                'ussd_id' => $ussd->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return [
                'success' => false,
                'message' => 'An error occurred while switching to production. Please contact support.',
                'requirements' => $requirements
            ];
        }
    }

    /**
     * Switch USSD to testing environment
     */
    public function switchToTesting(USSD $ussd): array
    {
        try {
            DB::beginTransaction();

            $previousEnvironment = $ussd->environment;
            $success = $ussd->goToTesting();
            
            if ($success) {
                DB::commit();
                
                // Log the environment change
                $this->logEnvironmentChange($ussd, $previousEnvironment, 'testing', 'User switched to testing');
                
                // Log activity
                ActivityService::logUSSDTesting(Auth::id(), $ussd->id, $ussd->name);
                
                return [
                    'success' => true,
                    'message' => 'USSD switched to testing mode. Your service is now in testing environment.',
                    'environment' => 'testing'
                ];
            } else {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Failed to switch to testing mode. Please try again.'
                ];
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to switch to testing', [
                'ussd_id' => $ussd->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return [
                'success' => false,
                'message' => 'An error occurred while switching to testing mode. Please contact support.'
            ];
        }
    }

    /**
     * Perform additional safety checks before going live
     */
    private function performSafetyChecks(USSD $ussd): bool
    {
        // Check if business has sufficient balance
        $business = $ussd->business;
        if (!$business || $business->account_balance <= 0) {
            Log::warning('Insufficient balance for go live', [
                'ussd_id' => $ussd->id,
                'business_id' => $business?->id,
                'balance' => $business?->account_balance
            ]);
            return false;
        }

        // Check if gateway credentials are valid (basic check)
        $credentials = $ussd->gateway_credentials;
        if (!is_array($credentials) || 
            empty($credentials) || 
            !isset($credentials['api_key']) || 
            empty($credentials['api_key']) ||
            !isset($credentials['username']) || 
            empty($credentials['username'])) {
            Log::warning('Missing or invalid gateway credentials for go live', [
                'ussd_id' => $ussd->id,
                'has_provider' => !empty($ussd->gateway_provider),
                'has_credentials' => !empty($credentials)
            ]);
            return false;
        }

        // Check if webhook URL is accessible (basic validation)
        if (!filter_var($ussd->webhook_url, FILTER_VALIDATE_URL)) {
            Log::warning('Invalid webhook URL for go live', [
                'ussd_id' => $ussd->id,
                'webhook_url' => $ussd->webhook_url
            ]);
            return false;
        }

        return true;
    }

    /**
     * Check if USSD has testing activity
     */
    private function hasTestingActivity(USSD $ussd): bool
    {
        $testingEnv = \App\Models\Environment::where('name', 'testing')->first();
        return $ussd->sessions()
            ->where('environment_id', $testingEnv?->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->exists();
    }

    /**
     * Get testing activity details
     */
    private function getTestingActivityDetails(USSD $ussd): string
    {
        $testingEnv = \App\Models\Environment::where('name', 'testing')->first();
        $recentSessions = $ussd->sessions()
            ->where('environment_id', $testingEnv?->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
            
        return $recentSessions > 0 ? "{$recentSessions} test sessions in last 7 days" : 'No recent testing activity';
    }

    /**
     * Get session statistics
     */
    private function getSessionStats(USSD $ussd): array
    {
        $today = now()->startOfDay();
        
        // Get environment IDs
        $testingEnv = \App\Models\Environment::where('name', 'testing')->first();
        $productionEnv = \App\Models\Environment::where('name', 'production')->first();
        
        return [
            'today' => [
                'total' => $ussd->sessions()->where('created_at', '>=', $today)->count(),
                'testing' => $ussd->sessions()->where('environment_id', $testingEnv?->id)->where('created_at', '>=', $today)->count(),
                'live' => $ussd->sessions()->where('environment_id', $productionEnv?->id)->where('created_at', '>=', $today)->count(),
            ],
            'total' => [
                'testing' => $ussd->sessions()->where('environment_id', $testingEnv?->id)->count(),
                'live' => $ussd->sessions()->where('environment_id', $productionEnv?->id)->count(),
            ]
        ];
    }

    /**
     * Get last environment change
     */
    private function getLastEnvironmentChange(USSD $ussd): ?array
    {
        return [
            'timestamp' => $ussd->updated_at,
            'environment' => $ussd->environment,
            'user_id' => Auth::id()
        ];
    }

    /**
     * Log environment change for audit purposes
     */
    private function logEnvironmentChange(USSD $ussd, string $fromEnvironment, string $toEnvironment, string $reason): void
    {
        Log::info('USSD Environment Change', [
            'ussd_id' => $ussd->id,
            'ussd_name' => $ussd->name,
            'from_environment' => $fromEnvironment,
            'to_environment' => $toEnvironment,
            'reason' => $reason,
            'user_id' => Auth::id(),
            'timestamp' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * Check if USSD has valid gateway configuration
     */
    private function hasValidGatewayConfiguration(USSD $ussd): bool
    {
        // Check if gateway provider is set
        if (empty($ussd->gateway_provider)) {
            return false;
        }

        // Check the raw attribute first to see if something exists in the database
        // Use getRawOriginal to get the actual database value before casting
        $rawValue = $ussd->getRawOriginal('gateway_credentials') ?? null;
        
        // If raw value is null or empty, no credentials exist
        if (empty($rawValue)) {
            return false;
        }

        // Now check the decrypted credentials via the accessor
        $credentials = $ussd->gateway_credentials;
        
        // Log for debugging (can be removed later)
        Log::debug('Gateway credentials check', [
            'ussd_id' => $ussd->id,
            'has_raw_value' => !empty($rawValue),
            'credentials_type' => gettype($credentials),
            'is_array' => is_array($credentials),
            'has_api_key' => isset($credentials['api_key']),
            'has_username' => isset($credentials['username']),
        ]);

        if (!is_array($credentials)) {
            return false;
        }

        // Verify that required credential fields exist and are not empty
        $hasApiKey = isset($credentials['api_key']) && !empty(trim($credentials['api_key']));
        $hasUsername = isset($credentials['username']) && !empty(trim($credentials['username']));
        
        return $hasApiKey && $hasUsername;
    }

    /**
     * Get gateway configuration details for display
     */
    private function getGatewayConfigurationDetails(USSD $ussd): string
    {
        if (empty($ussd->gateway_provider)) {
            return 'No gateway provider configured';
        }

        $credentials = $ussd->gateway_credentials;
        $hasCredentials = is_array($credentials) && 
                         !empty($credentials) && 
                         isset($credentials['api_key']) && 
                         !empty($credentials['api_key']) &&
                         isset($credentials['username']) && 
                         !empty($credentials['username']);

        if (!$hasCredentials) {
            return "Provider: {$ussd->gateway_provider} (credentials missing or incomplete)";
        }

        $details = "Provider: {$ussd->gateway_provider}";
        if (!empty($ussd->live_ussd_code)) {
            $details .= " | Live Code: {$ussd->live_ussd_code}";
        }
        if (!empty($ussd->testing_ussd_code)) {
            $details .= " | Testing Code: {$ussd->testing_ussd_code}";
        }

        return $details;
    }
}
