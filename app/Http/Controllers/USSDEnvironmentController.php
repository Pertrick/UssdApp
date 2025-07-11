<?php

namespace App\Http\Controllers;

use App\Models\USSD;
use App\Services\AfricastalkingService;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
            $sandboxService = new AfricastalkingService('sandbox');
            
            // Generate sandbox USSD code if not exists
            if (!$ussd->testing_ussd_code) {
                $ussd->testing_ussd_code = '*123#' . str_pad($ussd->id, 3, '0', STR_PAD_LEFT);
            }

            // Create webhook URL
            $webhookUrl = route('webhook.ussd.sandbox', ['ussd' => $ussd->id]);

            // Create USSD application in Africastalking sandbox
            $result = $sandboxService->createUssdApplication(
                $ussd->testing_ussd_code,
                $webhookUrl
            );

            if ($result) {
                $ussd->update([
                    'environment' => 'sandbox',
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
                    ['ussd_code' => $ussd->testing_ussd_code]
                );

                return redirect()->back()->with('success', 'USSD deployed to sandbox successfully!');
            }

            return redirect()->back()->with('error', 'Failed to deploy to sandbox. Please check your Africastalking credentials.');

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
            $liveService = new AfricastalkingService('live');
            
            // Generate live USSD code if not exists
            if (!$ussd->live_ussd_code) {
                $ussd->live_ussd_code = '*456#' . str_pad($ussd->id, 3, '0', STR_PAD_LEFT);
            }

            // Create webhook URL
            $webhookUrl = route('webhook.ussd.live', ['ussd' => $ussd->id]);

            // Create USSD application in Africastalking live
            $result = $liveService->createUssdApplication(
                $ussd->live_ussd_code,
                $webhookUrl
            );

            if ($result) {
                $ussd->update([
                    'environment' => 'live',
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
                    ['ussd_code' => $ussd->live_ussd_code]
                );

                return redirect()->back()->with('success', 'USSD deployed to live environment successfully!');
            }

            return redirect()->back()->with('error', 'Failed to deploy to live. Please check your Africastalking credentials.');

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
                'ussd_code' => $ussd->testing_ussd_code ?? '*123#' . str_pad($ussd->id, 3, '0', STR_PAD_LEFT),
                'description' => 'Local testing environment'
            ],
            'sandbox' => [
                'status' => $ussd->environment === 'sandbox' ? 'active' : 'available',
                'ussd_code' => $ussd->testing_ussd_code,
                'description' => 'Africastalking sandbox environment'
            ],
            'live' => [
                'status' => $ussd->environment === 'live' ? 'active' : 'pending',
                'ussd_code' => $ussd->live_ussd_code,
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