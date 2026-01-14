<?php

namespace App\Services;

use App\Models\USSDSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SessionRateLimitService
{
    /**
     * Rate limit requests per phone number
     * 
     * @param string $phoneNumber
     * @param int $maxRequests Maximum requests allowed
     * @param int $timeWindow Time window in seconds
     * @return bool True if within rate limit, false if exceeded
     */
    public function checkPhoneRateLimit(string $phoneNumber, int $maxRequests = 30, int $timeWindow = 60): bool
    {
        $cacheKey = "ussd_rate_limit:phone:" . md5($phoneNumber);
        
        $requests = Cache::get($cacheKey, 0);
        
        if ($requests >= $maxRequests) {
            Log::warning('USSD rate limit exceeded for phone number', [
                'phone_number' => substr($phoneNumber, 0, 4) . '****', // Partial masking
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
     * Rate limit requests per session
     * 
     * @param string $sessionId
     * @param int $maxRequests Maximum requests allowed
     * @param int $timeWindow Time window in seconds
     * @return bool True if within rate limit, false if exceeded
     */
    public function checkSessionRateLimit(string $sessionId, int $maxRequests = 50, int $timeWindow = 60): bool
    {
        $cacheKey = "ussd_rate_limit:session:" . md5($sessionId);
        
        $requests = Cache::get($cacheKey, 0);
        
        if ($requests >= $maxRequests) {
            Log::warning('USSD rate limit exceeded for session', [
                'session_id' => substr($sessionId, 0, 8) . '...', // Partial masking
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
     * Rate limit new session creation per phone number
     * Prevents session creation spam
     * 
     * @param string $phoneNumber
     * @param int $maxSessions Maximum sessions allowed
     * @param int $timeWindow Time window in seconds
     * @return bool True if within rate limit, false if exceeded
     */
    public function checkNewSessionRateLimit(string $phoneNumber, int $maxSessions = 5, int $timeWindow = 300): bool
    {
        $cacheKey = "ussd_rate_limit:new_session:" . md5($phoneNumber);
        
        $sessions = Cache::get($cacheKey, 0);
        
        if ($sessions >= $maxSessions) {
            Log::warning('USSD new session rate limit exceeded for phone number', [
                'phone_number' => substr($phoneNumber, 0, 4) . '****',
                'sessions' => $sessions,
                'max_sessions' => $maxSessions,
                'time_window' => $timeWindow,
            ]);
            return false;
        }

        // Increment session count
        Cache::put($cacheKey, $sessions + 1, $timeWindow);
        
        return true;
    }

    /**
     * Check if phone number is blocked (due to abuse)
     * 
     * @param string $phoneNumber
     * @return bool True if blocked, false if allowed
     */
    public function isPhoneBlocked(string $phoneNumber): bool
    {
        $cacheKey = "ussd_blocked:phone:" . md5($phoneNumber);
        return Cache::has($cacheKey);
    }

    /**
     * Block a phone number temporarily
     * 
     * @param string $phoneNumber
     * @param int $duration Duration in seconds (default: 1 hour)
     * @return void
     */
    public function blockPhone(string $phoneNumber, int $duration = 3600): void
    {
        $cacheKey = "ussd_blocked:phone:" . md5($phoneNumber);
        Cache::put($cacheKey, true, $duration);
        
        Log::warning('Phone number blocked from USSD access', [
            'phone_number' => substr($phoneNumber, 0, 4) . '****',
            'duration' => $duration,
        ]);
    }

    /**
     * Get rate limit status for a phone number
     * 
     * @param string $phoneNumber
     * @return array
     */
    public function getPhoneRateLimitStatus(string $phoneNumber): array
    {
        $maxRequests = config('ussd.phone_rate_limit_max', 30);
        $timeWindow = config('ussd.phone_rate_limit_window', 60);
        
        $cacheKey = "ussd_rate_limit:phone:" . md5($phoneNumber);
        $requests = Cache::get($cacheKey, 0);
        
        return [
            'current_requests' => $requests,
            'max_requests' => $maxRequests,
            'time_window' => $timeWindow,
            'remaining' => max(0, $maxRequests - $requests),
            'is_blocked' => $this->isPhoneBlocked($phoneNumber),
        ];
    }
}
