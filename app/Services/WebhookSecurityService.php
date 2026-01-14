<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WebhookSecurityService
{
    /**
     * Allowed IP addresses for AfricasTalking webhooks
     * Loaded from configuration (config/ussd.php) which reads from .env
     */
    private function getAllowedIpRanges(): array
    {
        // Get IP ranges from config (which reads from .env)
        $ranges = config('ussd.webhook_allowed_ips', []);
        
        // If it's a comma-separated string, convert to array
        if (is_string($ranges)) {
            $ranges = array_filter(array_map('trim', explode(',', $ranges)));
        }
        
        // If empty, return default empty array (will block all if IP verification is enabled)
        return is_array($ranges) ? $ranges : [];
    }

    /**
     * Verify if the request IP is from an allowed source
     * 
     * @param Request $request
     * @return bool
     */
    public function verifyIpAddress(Request $request): bool
    {
        $clientIp = $request->ip();
        
        // IP verification is OFF by default
        // Only verify if explicitly enabled
        if (!config('ussd.enable_ip_verification', false)) {
            return true;
        }

        // Get allowed IP ranges from config
        $allowedRanges = $this->getAllowedIpRanges();
        
        // If IP verification is enabled but no IP ranges configured, deny access
        if (empty($allowedRanges)) {
            Log::warning('IP verification enabled but no allowed IP ranges configured', [
                'client_ip' => $clientIp,
            ]);
            return false;
        }

        // Check if IP is in allowed ranges
        foreach ($allowedRanges as $range) {
            if ($this->ipInRange($clientIp, $range)) {
                return true;
            }
        }

        // Log unauthorized access attempt
        Log::warning('Webhook request from unauthorized IP', [
            'ip' => $clientIp,
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
        ]);

        return false;
    }

    /**
     * Check if IP address is within CIDR range
     * 
     * @param string $ip
     * @param string $range (CIDR notation, e.g., 192.168.1.0/24)
     * @return bool
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            // Single IP address
            return $ip === $range;
        }

        list($subnet, $mask) = explode('/', $range);
        
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * Verify webhook signature (if AfricasTalking provides signature verification)
     * 
     * @param Request $request
     * @param string $secretKey
     * @return bool
     */
    public function verifySignature(Request $request, string $secretKey): bool
    {
        // If secret key is empty, skip verification (unless signature is required)
        if (empty($secretKey)) {
            // If signature verification is not required, allow the request
            return config('ussd.webhook_signature_required', false) === false;
        }

        // If AfricasTalking doesn't provide signature verification, return true
        // Otherwise, implement signature verification logic here
        $signature = $request->header('X-AfricasTalking-Signature') 
                  ?? $request->header('X-Webhook-Signature')
                  ?? null;

        if (!$signature) {
            // If signature header is not present and not required, allow
            // Set this to false if signature is mandatory
            return config('ussd.webhook_signature_required', false) === false;
        }

        // Get request payload
        $payload = $request->getContent();
        
        // Calculate expected signature (HMAC SHA256)
        $expectedSignature = hash_hmac('sha256', $payload, $secretKey);
        
        // Use constant-time comparison to prevent timing attacks
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Rate limit webhook requests per IP address
     * 
     * @param Request $request
     * @param int $maxRequests Maximum requests allowed
     * @param int $timeWindow Time window in seconds
     * @return bool True if within rate limit, false if exceeded
     */
    public function checkRateLimit(Request $request, int $maxRequests = 60, int $timeWindow = 60): bool
    {
        $ip = $request->ip();
        $cacheKey = "webhook_rate_limit:{$ip}";
        
        $requests = Cache::get($cacheKey, 0);
        
        if ($requests >= $maxRequests) {
            Log::warning('Webhook rate limit exceeded', [
                'ip' => $ip,
                'requests' => $requests,
                'max_requests' => $maxRequests,
                'time_window' => $timeWindow,
            ]);
            return false;
        }

        // Increment request count
        Cache::put($cacheKey, $requests + 1, $timeWindow);
        
        return true;
    }

    /**
     * Validate webhook request
     * Combines IP verification, signature verification, and rate limiting
     * 
     * @param Request $request
     * @param string|null $secretKey Optional secret key for signature verification (defaults to env)
     * @return array ['valid' => bool, 'message' => string, 'code' => string]
     */
    public function validateWebhookRequest(Request $request, ?string $secretKey = null): array
    {
        // Check IP address (only if IP verification is enabled)
        if (config('ussd.enable_ip_verification', false) && !$this->verifyIpAddress($request)) {
            return [
                'valid' => false,
                'message' => 'Unauthorized IP address',
                'code' => 'IP_UNAUTHORIZED'
            ];
        }

        $secretKeyToUse = $secretKey ?? config('ussd.webhook_secret_key', '');
        if (!empty($secretKeyToUse) && !$this->verifySignature($request, $secretKeyToUse)) {
            return [
                'valid' => false,
                'message' => 'Invalid webhook signature',
                'code' => 'INVALID_SIGNATURE'
            ];
        }

        $rateLimitMax = config('ussd.webhook_rate_limit_max', 60);
        $rateLimitWindow = config('ussd.webhook_rate_limit_window', 60);
        
        if (!$this->checkRateLimit($request, $rateLimitMax, $rateLimitWindow)) {
            return [
                'valid' => false,
                'message' => 'Rate limit exceeded',
                'code' => 'RATE_LIMIT_EXCEEDED'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Webhook request validated',
            'code' => 'VALID'
        ];
    }

    /**
     * Get allowed IP ranges from configuration
     * 
     * @return array
     */
    public function getAllowedIpRangesList(): array
    {
        return $this->getAllowedIpRanges();
    }
}
