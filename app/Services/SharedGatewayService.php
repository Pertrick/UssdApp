<?php

namespace App\Services;

use App\Models\USSD;
use App\Models\USSDSession;
use App\Enums\EnvironmentType;
use Illuminate\Support\Facades\Log;

/**
 * Handles shared gateway USSD logic: tenant menu, routing, prefix stripping.
 */
class SharedGatewayService
{
    /**
     * Show tenant menu for gateway first request.
     */
    public function buildGatewayMenu(USSD $gatewayUssd): string
    {
        $lines = ['Welcome'];
        foreach ($gatewayUssd->sharedCodeAllocations as $a) {
            $lines[] = $a->option_value . '. ' . $a->label;
        }
        return implode("\n", $lines);
    }

    /**
     * Check if session needs tenant routing (menu selection or direct dial).
     */
    public function needsTenantRouting(USSD $ussd, array $sessionData, string $text): bool
    {
        if (!$ussd->is_shared_gateway || $ussd->sharedCodeAllocations->isEmpty()) {
            return false;
        }
        $hasTenantChoice = !empty($sessionData['tenant_choice']);
        return !empty($sessionData['awaiting_tenant_choice']) || (!$hasTenantChoice && $text !== '');
    }

    /**
     * Route session to tenant, bill, and return display.
     */
    public function routeToTenantAndGetDisplay(
        USSD $gatewayUssd,
        USSDSession $session,
        string $choice,
        string $phoneNumber
    ): ?array {
        $allocation = $gatewayUssd->sharedCodeAllocations->firstWhere('option_value', $choice);
        if (!$allocation) {
            return null;
        }

        $targetUssd = $allocation->targetUssd;
        $rootFlow = $targetUssd->rootFlow();
        if (!$rootFlow) {
            return null;
        }

        $session->update([
            'ussd_id' => $targetUssd->id,
            'current_flow_id' => $rootFlow->id,
            'session_data' => ['tenant_choice' => $choice],
        ]);
        $session->refresh();

        try {
            $billingService = app(BillingService::class);
            $gatewayCostService = app(GatewayCostService::class);
            $networkProvider = $gatewayCostService->detectNetworkProvider($phoneNumber);
            $gatewayCostService->recordGatewayCost($session, $networkProvider);
            $billingService->billSession($session);
        } catch (\Throwable $e) {
            Log::error('Failed to bill USSD session after tenant choice', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            $session->update(['status' => 'error']);
            throw $e;
        }

        $ussdSessionService = app(USSDSessionService::class);
        return $ussdSessionService->getCurrentFlowDisplay($session);
    }

    /**
     * Strip tenant prefix from text (e.g. "1*2*3" with tenant_choice="1" → "2*3").
     */
    public function stripTenantPrefix(string $text, ?string $tenantChoice): string
    {
        if ($tenantChoice === null || $tenantChoice === '') {
            return $text;
        }
        $prefix = $tenantChoice . '*';
        if (str_starts_with($text, $prefix)) {
            return substr($text, strlen($prefix));
        }
        if ($text === $tenantChoice) {
            return '';
        }
        return $text;
    }

    /**
     * Get first segment of text (e.g. "1*2*3" → "1").
     */
    public function getFirstSegment(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        $parts = explode('*', $text);
        return trim($parts[0]);
    }
}
