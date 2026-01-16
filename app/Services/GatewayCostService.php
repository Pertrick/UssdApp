<?php

namespace App\Services;

use App\Enums\EnvironmentType;
use App\Models\USSDSession;
use App\Models\UssdCost;
use Illuminate\Support\Facades\Log;

class GatewayCostService
{
    /**
     * Calculate gateway cost for a session based on provider and network
     * 
     * @param USSDSession $session
     * @param string|null $networkProvider Network provider (MTN, Airtel, etc.) - optional
     * @return array Contains 'cost', 'currency', 'provider', 'network'
     */
    public function calculateGatewayCost(USSDSession $session, ?string $networkProvider = null): array
    {
        $ussd = $session->ussd;
        $gatewayProvider = $ussd->gateway_provider ?? 'africastalking';
        
        // Default to AfricasTalking if no provider specified
        if ($gatewayProvider === 'africastalking' || empty($gatewayProvider)) {
            return $this->calculateAfricasTalkingCost($session, $networkProvider);
        }
        
        // For other gateways, return default cost structure
        return [
            'cost' => 0.0,
            'currency' => config('app.currency', 'NGN'),
            'provider' => $gatewayProvider,
            'network' => $networkProvider,
        ];
    }

    /**
     * Calculate AfricasTalking cost per session
     * 
     * Costs are fetched from ussd_costs table (database-driven pricing)
     * Falls back to config if no database entry exists
     */
    protected function calculateAfricasTalkingCost(USSDSession $session, ?string $networkProvider = null): array
    {
        $country = config('app.country', 'NG'); 
        $currency = config('app.currency', 'NGN');
        
        // Try to get cost from database first (database takes priority)
        if ($networkProvider) {
            // Normalize network name to uppercase for database lookup
            // Handle variations: "MTN", "mtn", "Mtn" -> "MTN"
            $networkUpper = strtoupper(trim($networkProvider));
            
            // Try exact match first
            $ussdCost = UssdCost::getActiveCost($country, $networkUpper);
            
            // If not found, try common variations (e.g., "9MOBILE" vs "9MOBILE")
            if (!$ussdCost && $networkUpper === '9MOBILE') {
                $ussdCost = UssdCost::getActiveCost($country, '9MOBILE');
            }
            
            if ($ussdCost) {
                $costInSmallestUnit = $ussdCost->cost_per_session;
                $currency = $ussdCost->currency;
                $costInMainCurrency = $this->convertFromSmallestUnit($costInSmallestUnit, $currency);
                
                Log::info('AfricasTalking cost from database', [
                    'session_id' => $session->id,
                    'network' => $networkProvider,
                    'network_upper' => $networkUpper,
                    'cost_id' => $ussdCost->id,
                    'cost' => $costInMainCurrency,
                    'cost_in_smallest_unit' => $costInSmallestUnit,
                    'currency' => $currency,
                    'effective_from' => $ussdCost->effective_from,
                ]);
                
                return [
                    'cost' => $costInSmallestUnit,
                    'currency' => $currency,
                    'provider' => 'africastalking',
                    'network' => $networkProvider,
                ];
            } else {
                // Log when no database entry found - this indicates database pricing should be set
                Log::warning('No active USSD cost found in database - falling back to config', [
                    'session_id' => $session->id,
                    'network' => $networkProvider,
                    'network_upper' => $networkUpper,
                    'country' => $country,
                    'message' => 'Admin should add network cost to ussd_costs table for accurate pricing',
                ]);
            }
        }
        
        // Fallback to config ONLY if no database entry exists
        // This is a fallback and should not be used if database has network costs configured
        $costs = config('services.africastalking.cost_per_session', []);
        $currency = config('services.africastalking.cost_currency', 'NGN');
        $defaultCost = $costs['default'] ?? 3.0;
        
        $cost = $defaultCost;
        if ($networkProvider) {
            $networkKey = strtolower($networkProvider);
            $networkMap = [
                'mtn' => 'mtn',
                'airtel' => 'airtel',
                'glo' => 'glo',
                '9mobile' => '9mobile'
            ];
            
            $mappedKey = $networkMap[$networkKey] ?? $networkKey;
            if (isset($costs[$mappedKey])) {
                $cost = $costs[$mappedKey];
                Log::info('Using network-specific config cost (fallback)', [
                    'session_id' => $session->id,
                    'network' => $networkProvider,
                    'network_key' => $mappedKey,
                    'cost' => $cost,
                ]);
            } else {
                Log::info('Using default config cost (no network-specific config)', [
                    'session_id' => $session->id,
                    'network' => $networkProvider,
                    'default_cost' => $defaultCost,
                ]);
            }
        }
        
        $costInSmallestUnit = $this->convertToSmallestUnit((float) $cost, $currency);
        
        Log::warning('AfricasTalking cost from config (fallback - database pricing recommended)', [
            'session_id' => $session->id,
            'network' => $networkProvider,
            'cost' => $cost,
            'currency' => $currency,
            'note' => 'Consider adding network cost to ussd_costs table in Admin → Settings',
        ]);
        
        return [
            'cost' => $costInSmallestUnit,
            'currency' => $currency,
            'provider' => 'africastalking',
            'network' => $networkProvider,
        ];
    }

    /**
     * Get network provider from phone number (if available)
     * 
     * This identifies the network by analyzing the phone number prefix.
     * Works for Nigeria phone numbers with or without country code (+234).
     * 
     * How it works:
     * 1. Normalizes the phone number (removes spaces, dashes, country code)
     * 2. Checks the first 4 digits against known network prefixes
     * 3. Returns the network name (MTN, Airtel, Glo, 9mobile)
     * 
     * Note: Network prefixes may change over time. Update this method if new prefixes are assigned.
     * 
     * @param string|null $phoneNumber Phone number in any format (e.g., +2348031234567, 08031234567, 8031234567)
     * @return string|null Network name (MTN, Airtel, Glo, 9mobile) or null if unknown
     */
    public function detectNetworkProvider(?string $phoneNumber): ?string
    {
        if (!$phoneNumber) {
            return null;
        }
        
        // Normalize phone number (remove country code, spaces, dashes, etc.)
        $normalized = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Remove Nigeria country code (+234) if present
        // Nigeria country code is 234, so if number starts with 234, remove it
        if (strlen($normalized) > 10 && substr($normalized, 0, 3) === '234') {
            $normalized = '0' . substr($normalized, 3); // Add leading 0 back
        }
        
        // Ensure we have at least 4 digits to check prefix
        if (strlen($normalized) < 4) {
            return null;
        }
        
        // Get first 4 digits for prefix matching
        $prefix = substr($normalized, 0, 4);
        
        // Nigeria network prefix detection
        // These are the known prefixes for each network operator
        
        // MTN Nigeria prefixes
        $mtnPrefixes = ['0803', '0806', '0703', '0706', '0813', '0816', '0810', '0814', '0903', '0906', '0913', '0916'];
        if (in_array($prefix, $mtnPrefixes)) {
            return 'MTN';
        }
        
        // Airtel Nigeria prefixes
        $airtelPrefixes = ['0802', '0808', '0708', '0812', '0901', '0902', '0904', '0907', '0912'];
        if (in_array($prefix, $airtelPrefixes)) {
            return 'Airtel';
        }
        
        // Glo (Globacom) Nigeria prefixes
        $gloPrefixes = ['0805', '0807', '0705', '0815', '0811', '0905', '0915'];
        if (in_array($prefix, $gloPrefixes)) {
            return 'Glo';
        }
        
        // 9mobile (formerly Etisalat) Nigeria prefixes
        $nineMobilePrefixes = ['0809', '0817', '0818', '0908', '0909', '0918'];
        if (in_array($prefix, $nineMobilePrefixes)) {
            return '9mobile';
        }
        
        // If no match found, return null (will use default cost)
        return null;
    }

    /**
     * Record gateway cost for a session
     * 
     * This should be called when a session starts to track the actual cost
     */
    public function recordGatewayCost(USSDSession $session, ?string $networkProvider = null): bool
    {
        try {
            // Only record costs for production/live sessions
            // Testing sessions don't incur real gateway costs
            $environment = $session->environment?->name ?? EnvironmentType::TESTING->value;
            if ($environment !== EnvironmentType::PRODUCTION->value) {
                Log::info('Skipping gateway cost recording for non-production session', [
                    'session_id' => $session->id,
                    'environment' => $environment,
                ]);
                return false;
            }
            
            // Detect network if not provided
            if (!$networkProvider) {
                $networkProvider = $this->detectNetworkProvider($session->phone_number);
            }
            
            // Calculate cost
            $costData = $this->calculateGatewayCost($session, $networkProvider);
            
            // Record on session (gateway_cost serves as snapshot since recorded at billing time)
            $session->update([
                'gateway_cost' => $costData['cost'],
                'gateway_cost_currency' => $costData['currency'],
                'gateway_provider' => $costData['provider'],
                'network_provider' => $costData['network'],
                'gateway_cost_recorded_at' => now(),
            ]);
            
            Log::info('Gateway cost recorded', [
                'session_id' => $session->id,
                'cost' => $costData['cost'],
                'currency' => $costData['currency'],
                'provider' => $costData['provider'],
                'network' => $costData['network'],
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to record gateway cost', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get total gateway costs for analytics
     * 
     * @param array $sessionIds Optional session IDs to filter
     * @param \Carbon\Carbon|null $startDate Optional start date
     * @param \Carbon\Carbon|null $endDate Optional end date
     * @param string $currency Currency code
     * @return float Total gateway costs in main currency
     */
    public function getTotalGatewayCosts(array $sessionIds = [], ?\Carbon\Carbon $startDate = null, ?\Carbon\Carbon $endDate = null, string $currency = 'NGN'): float
    {
        $query = USSDSession::whereNotNull('gateway_cost');
        
        if (!empty($sessionIds)) {
            $query->whereIn('id', $sessionIds);
        }
        
        if ($startDate) {
            $query->where('gateway_cost_recorded_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('gateway_cost_recorded_at', '<=', $endDate);
        }
        
        // Sum returns integer (smallest unit)
        $totalInSmallestUnit = (int) $query->sum('gateway_cost');
        
        // Convert to main currency
        return $this->convertFromSmallestUnit($totalInSmallestUnit, $currency);
    }

    /**
     * Convert amount to smallest unit (kobo for NGN, cents for USD)
     * 
     * @param float $amount Amount in main currency
     * @param string $currency Currency code
     * @return int Amount in smallest unit
     */
    public function convertToSmallestUnit(float $amount, string $currency): int
    {
        // Most currencies use 100 (kobo, cents, etc.)
        $conversionFactor = 100;
        
        // Handle currencies that don't use 100 (if any in the future)
        // For now, all major currencies use 100
        
        return (int) round($amount * $conversionFactor);
    }

    /**
     * Convert amount from smallest unit to main currency
     * 
     * @param int $amountInSmallestUnit Amount in smallest unit (kobo/cents)
     * @param string $currency Currency code
     * @return float Amount in main currency
     */
    public function convertFromSmallestUnit(int $amountInSmallestUnit, string $currency): float
    {
        // Most currencies use 100 (kobo, cents, etc.)
        $conversionFactor = 100;
        
        return round($amountInSmallestUnit / $conversionFactor, 2);
    }

    /**
     * Calculate profit margin
     * 
     * @param float $revenue Total revenue (in main currency)
     * @param int $gatewayCosts Total gateway costs (in smallest unit)
     * @param string $currency Currency code
     * @return array Contains 'profit' and 'margin_percentage' (both in main currency)
     */
    public function calculateProfitMargin(float $revenue, int $gatewayCosts, string $currency): array
    {
        // Convert gateway costs to main currency
        $gatewayCostsInMainCurrency = $this->convertFromSmallestUnit($gatewayCosts, $currency);
        
        $profit = $revenue - $gatewayCostsInMainCurrency;
        $marginPercentage = $revenue > 0 ? ($profit / $revenue) * 100 : 0;
        
        return [
            'profit' => $profit,
            'margin_percentage' => round($marginPercentage, 2),
        ];
    }
}

