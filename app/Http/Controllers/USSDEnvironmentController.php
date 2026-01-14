<?php

namespace App\Http\Controllers;

use App\Models\USSD;
use Inertia\Inertia;
use App\Models\Environment;
use Illuminate\Http\Request;
use App\Enums\EnvironmentType;
use App\Services\ActivityService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\AfricastalkingService;

class USSDEnvironmentController extends Controller
{
    /**
     * Show environment management page
     */
    public function environment(USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        return Inertia::render('USSD/Environment', [
            'ussd' => $ussd->load('business'),
            'environmentStatus' => $this->getEnvironmentStatus($ussd),
            'deploymentRequirements' => $this->getDeploymentRequirements($ussd),
        ]);
    }

    /**
     * Deploy USSD to sandbox environment
     */
    public function deployToSandbox(Request $request, USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            // Ensure pattern exists (should already be set during USSD creation)
            if (empty($ussd->pattern)) {
                return redirect()->back()->with('error', 'USSD pattern is required. Please set a pattern for this USSD service.');
            }

            // Create webhook URL
            $webhookUrl = route('webhook.ussd.sandbox', ['ussd' => $ussd->id]);

            // Get sandbox environment
            $sandboxEnvironment = Environment::where('name', EnvironmentType::TESTING->value)->first();
            if (!$sandboxEnvironment) {
                return redirect()->back()->with('error', 'Sandbox environment not found. Please contact administrator.');
            }

            // Save the environment and webhook URL
            $ussd->update([
                'environment_id' => $sandboxEnvironment->id,
                'gateway_provider' => 'africastalking',
                'webhook_url' => $webhookUrl,
            ]);

            // Log the activity
            ActivityService::log(
                Auth::id(),
                'ussd_deployed_sandbox',
                "Deployed USSD '{$ussd->name}' to sandbox environment",
                'App\Models\USSD',
                $ussd->id,
                ['ussd_code' => $ussd->pattern]
            );

            return redirect()->back()->with('success', 'USSD configured for sandbox environment! Please create the USSD application in AfricasTalking dashboard with code: ' . $ussd->pattern);

        } catch (\Exception $e) {
            Log::error('Failed to deploy USSD to sandbox', [
                'ussd_id' => $ussd->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to deploy to sandbox: ' . $e->getMessage());
        }
    }

    /**
     * Deploy USSD to live environment
     */
    public function deployToLive(Request $request, USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        // Check if business is verified
        if (!$ussd->business || $ussd->business->registration_status !== 'verified') {
            return redirect()->back()->with('error', 'Business must be verified before deploying to live environment.');
        }

        // Check if all requirements are met
        $requirements = $this->getDeploymentRequirements($ussd);
        if (!$requirements['canDeployToLive']) {
            return redirect()->back()->with('error', 'Cannot deploy to live: ' . $requirements['missingRequirements']);
        }

        try {
            // Ensure pattern exists (should already be set during USSD creation)
            if (empty($ussd->pattern)) {
                return redirect()->back()->with('error', 'USSD pattern is required. Please set a pattern for this USSD service.');
            }

            // Note: Pattern should be updated to the production code when moving to live
            // The pattern field is what AfricasTalking will use to route requests

            // Create webhook URL
            $webhookUrl = route('webhook.ussd.live', ['ussd' => $ussd->id]);
            
            // Get production environment
            $productionEnvironment = Environment::where('name', EnvironmentType::PRODUCTION->value)->first();
            if (!$productionEnvironment) {
                return redirect()->back()->with('error', 'Production environment not found. Please contact administrator.');
            }
            
            // Save the environment and webhook URL
            $ussd->update([
                'pattern' => $ussd->pattern,
                'environment_id' => $productionEnvironment->id,
                'gateway_provider' => 'africastalking',
                'webhook_url' => $webhookUrl,
            ]);

            // Log the activity
            ActivityService::log(
                Auth::id(),
                'ussd_deployed_live',
                "Deployed USSD '{$ussd->name}' to live environment",
                'App\Models\USSD',
                $ussd->id,
                ['ussd_code' => $ussd->pattern]
            );

            return redirect()->back()->with('success', 'USSD configured for live environment! Please create the USSD application in AfricasTalking dashboard with code: ' . $ussd->pattern . '. Update the pattern field if you need to change the code.');

        } catch (\Exception $e) {
            Log::error('Failed to deploy USSD to live', [
                'ussd_id' => $ussd->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to deploy to live: ' . $e->getMessage());
        }
    }

    /**
     * Switch back to simulation environment
     */
    public function switchToSimulation(Request $request, USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        $ussd->update(['environment' => 'simulation']);

        // Log the activity
        ActivityService::log(
            Auth::id(),
            'ussd_switched_simulation',
            "Switched USSD '{$ussd->name}' to simulation environment",
            'App\Models\USSD',
            $ussd->id
        );

        return redirect()->back()->with('success', 'Switched to simulation environment.');
    }

    /**
     * Get environment status for a USSD
     */
    private function getEnvironmentStatus($ussd)
    {
        $status = [
            'current' => $ussd->environment ?? 'simulation',
            'simulation' => [
                'status' => 'available',
                'ussd_code' => $ussd->pattern,
                'description' => 'Local testing environment'
            ],
            'sandbox' => [
                'status' => $ussd->environment === 'sandbox' ? 'active' : 'available',
                'ussd_code' => $ussd->pattern,
                'description' => 'Africastalking sandbox environment'
            ],
            'live' => [
                'status' => $ussd->environment === 'live' ? 'active' : 'pending',
                'ussd_code' => $ussd->pattern,
                'description' => 'Production environment'
            ]
        ];

        return $status;
    }

    /**
     * Get deployment requirements for a USSD
     */
    private function getDeploymentRequirements($ussd)
    {
        $requirements = [
            'business_verified' => $ussd->business && $ussd->business->registration_status === 'verified',
            'has_flows' => $ussd->flows()->count() > 0,
            'has_gateway_credentials' => !empty(config('services.africastalking.live_api_key')),
            'has_webhook_url' => !empty($ussd->webhook_url),
        ];

        $missingRequirements = [];
        foreach ($requirements as $requirement => $met) {
            if (!$met) {
                $missingRequirements[] = $requirement;
            }
        }

        return [
            'requirements' => $requirements,
            'canDeployToLive' => empty($missingRequirements),
            'missingRequirements' => implode(', ', $missingRequirements)
        ];
    }
} 