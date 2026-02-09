<?php

namespace App\Services;

use App\Models\USSD;
use App\Models\USSDSession;
use App\Enums\EnvironmentType;
use Illuminate\Support\Facades\Log;

/**
 * Handles single-shot USSD: user dials full path (e.g. *737*1*2*3#), no interaction.
 * Walks the flow tree and returns the final result in one response.
 */
class SingleShotService
{
    protected USSDSessionService $sessionService;
    protected SharedGatewayService $sharedGatewayService;

    public function __construct(USSDSessionService $sessionService, SharedGatewayService $sharedGatewayService)
    {
        $this->sessionService = $sessionService;
        $this->sharedGatewayService = $sharedGatewayService;
    }

    /**
     * Process single-shot request. Returns display array or null on error.
     */
    public function process(USSD $ussd, string $path, string $sessionId, string $phoneNumber): ?array
    {
        $segments = $this->parsePath($path);
        if (empty($segments)) {
            return null;
        }

        // Resolve tenant if shared gateway
        $targetUssd = $ussd;
        $pathToProcess = $path;

        if ($ussd->is_shared_gateway && $ussd->sharedCodeAllocations->isNotEmpty()) {
            $firstChoice = $segments[0];
            $allocation = $ussd->sharedCodeAllocations->firstWhere('option_value', $firstChoice);
            if (!$allocation) {
                return null;
            }
            $targetUssd = $allocation->targetUssd;
            $pathToProcess = count($segments) > 1
                ? implode('*', array_slice($segments, 1))
                : '';
        }

        $rootFlow = $targetUssd->rootFlow();
        if (!$rootFlow) {
            return null;
        }

        // If no path left (e.g. *347*412*1# only), just show root
        if ($pathToProcess === '') {
            $session = $this->sessionService->startSession(
                $targetUssd,
                $phoneNumber,
                'AfricasTalking',
                null,
                EnvironmentType::PRODUCTION->value
            );
            $session->update(['session_id' => $sessionId]);
            try {
                $billingService = app(BillingService::class);
                $gatewayCostService = app(GatewayCostService::class);
                $networkProvider = $gatewayCostService->detectNetworkProvider($phoneNumber);
                $gatewayCostService->recordGatewayCost($session, $networkProvider);
                $billingService->billSession($session);
            } catch (\Throwable $e) {
                Log::error('Single-shot billing failed', ['session_id' => $session->id, 'error' => $e->getMessage()]);
                return null;
            }
            return $this->sessionService->getCurrentFlowDisplay($session);
        }

        // Create session for single-shot (for flow walking)
        $session = $this->sessionService->startSession(
            $targetUssd,
            $phoneNumber,
            'AfricasTalking',
            null,
            EnvironmentType::PRODUCTION->value
        );
        $session->update(['session_id' => $sessionId]);

        // Bill
        try {
            $billingService = app(BillingService::class);
            $gatewayCostService = app(GatewayCostService::class);
            $networkProvider = $gatewayCostService->detectNetworkProvider($phoneNumber);
            $gatewayCostService->recordGatewayCost($session, $networkProvider);
            $billingResult = $billingService->billSession($session);
            if (!$billingResult) {
                $session->update(['status' => 'error']);
                return null;
            }
        } catch (\Throwable $e) {
            Log::error('Single-shot billing failed', ['session_id' => $session->id, 'error' => $e->getMessage()]);
            $session->update(['status' => 'error']);
            return null;
        }

        // Store tenant_choice for shared gateway so path stripping is consistent
        if ($ussd->is_shared_gateway && $ussd->sharedCodeAllocations->isNotEmpty()) {
            $segment = $this->sharedGatewayService->getFirstSegment($path);
            $session->update([
                'session_data' => array_merge($session->session_data ?? [], ['tenant_choice' => $segment]),
            ]);
            $session->refresh();
        }

        // Walk path segment by segment
        $remainingPath = $pathToProcess;
        $finalResponse = null;

        while ($remainingPath !== '') {
            $segment = $this->sharedGatewayService->getFirstSegment($remainingPath);
            $remainingPath = $this->stripFirstSegment($remainingPath);

            $session->refresh();
            $response = $this->sessionService->processInput($session, $segment);

            if (!($response['success'] ?? false)) {
                $finalResponse = $response;
                break;
            }

            if ($response['session_ended'] ?? false) {
                $finalResponse = $response;
                break;
            }

            // If no more path and we need input, show current prompt
            if ($remainingPath === '' && ($response['requires_input'] ?? false)) {
                $display = $this->sessionService->getCurrentFlowDisplay($session);
                $finalResponse = $display;
                break;
            }

            if ($remainingPath === '') {
                $display = $this->sessionService->getCurrentFlowDisplay($session);
                $finalResponse = $display;
                break;
            }
        }

        if ($remainingPath === '' && $finalResponse === null) {
            $finalResponse = $this->sessionService->getCurrentFlowDisplay($session);
        }

        return $finalResponse;
    }

    protected function parsePath(string $path): array
    {
        $path = trim($path);
        if ($path === '') {
            return [];
        }
        return array_filter(array_map('trim', explode('*', $path)));
    }

    protected function stripFirstSegment(string $path): string
    {
        $parts = explode('*', $path, 2);
        return isset($parts[1]) ? trim($parts[1]) : '';
    }
}
