<?php

namespace App\Services;

use App\Models\ExternalAPIConfiguration;
use Illuminate\Support\Facades\Log;

class APITestLoggingService
{
    /**
     * Log test request details
     */
    public function logTestRequest(ExternalAPIConfiguration $apiConfig, array $testData): void
    {
        // Prepare headers for logging
        $headers = $this->prepareHeaders($apiConfig);
        
        // Add authentication headers (without exposing sensitive data)
        $authHeaders = $this->getAuthHeadersForLogging($apiConfig);
        $headers = array_merge($headers, $authHeaders);
        
        // Mask sensitive headers
        $maskedHeaders = $this->maskSensitiveHeaders($headers);
        
        Log::info('API Test Request', [
            'api_config_id' => $apiConfig->id,
            'api_name' => $apiConfig->name,
            'endpoint_url' => $apiConfig->endpoint_url,
            'method' => $apiConfig->method,
            'headers' => $maskedHeaders,
            'request_body' => $testData,
            'timeout' => $apiConfig->timeout,
            'retry_attempts' => $apiConfig->retry_attempts,
            'auth_type' => $apiConfig->auth_type,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log test request details with UNMASKED headers for debugging
     */
    public function logTestRequestUnmasked(ExternalAPIConfiguration $apiConfig, array $testData): void
    {
        // Prepare headers for logging
        $headers = $this->prepareHeaders($apiConfig);
        
        // Add authentication headers (UNMASKED for debugging)
        $authHeaders = $this->getAuthHeadersUnmasked($apiConfig);
        $headers = array_merge($headers, $authHeaders);
        
        // Resolve template variables in request body
        $resolvedTestData = $this->resolveTemplateVariables($testData, $apiConfig->getAuthConfig());
        
        Log::info('API Test Request (UNMASKED)', [
            'api_config_id' => $apiConfig->id,
            'api_name' => $apiConfig->name,
            'endpoint_url' => $apiConfig->endpoint_url,
            'method' => $apiConfig->method,
            'headers' => $headers, // UNMASKED!
            'request_body' => $resolvedTestData, // RESOLVED TEMPLATES!
            'timeout' => $apiConfig->timeout,
            'retry_attempts' => $apiConfig->retry_attempts,
            'auth_type' => $apiConfig->auth_type,
            'auth_config' => $apiConfig->getAuthConfig(), // Show raw auth config
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log test response details
     */
    public function logTestResponse(ExternalAPIConfiguration $apiConfig, array $result, float $responseTime): void
    {
        Log::info('API Test Response', [
            'api_config_id' => $apiConfig->id,
            'api_name' => $apiConfig->name,
            'status_code' => $result['status'] ?? null,
            'successful' => $result['successful'] ?? false,
            'response_headers' => $result['headers'] ?? [],
            'response_body' => $result['body'] ?? null,
            'response_time_ms' => round($responseTime, 2),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log test error details
     */
    public function logTestError(ExternalAPIConfiguration $apiConfig, \Exception $e): void
    {
        Log::error('API Test Error', [
            'api_config_id' => $apiConfig->id,
            'api_name' => $apiConfig->name,
            'endpoint_url' => $apiConfig->endpoint_url,
            'method' => $apiConfig->method,
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'error_trace' => $e->getTraceAsString(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Prepare headers for logging
     */
    private function prepareHeaders(ExternalAPIConfiguration $apiConfig): array
    {
        $headers = $apiConfig->headers ?? [];
        
        // Add default headers
        $defaultHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        
        return array_merge($defaultHeaders, $headers);
    }

    /**
     * Get authentication headers for logging (without exposing sensitive data)
     */
    private function getAuthHeadersForLogging(ExternalAPIConfiguration $apiConfig): array
    {
        $authHeaders = [];
        $authType = $apiConfig->auth_type;
        $authConfig = $apiConfig->getAuthConfig();
        
        switch ($authType) {
            case 'api_key':
                $headerName = $authConfig['header_name'] ?? 'X-API-Key';
                $authHeaders[$headerName] = '[MASKED]';
                break;
                
            case 'bearer_token':
                $authHeaders['Authorization'] = 'Bearer [MASKED]';
                break;
                
            case 'basic':
                $authHeaders['Authorization'] = 'Basic [MASKED]';
                break;
                
            case 'oauth':
                $authHeaders['Authorization'] = 'Bearer [MASKED]';
                if (isset($authConfig['client_id'])) {
                    $authHeaders['X-Client-ID'] = '[MASKED]';
                }
                break;
                
            case 'custom':
                if (isset($authConfig['custom_headers'])) {
                    foreach ($authConfig['custom_headers'] as $key => $value) {
                        $authHeaders[$key] = '[MASKED]';
                    }
                }
                break;
        }
        
        return $authHeaders;
    }

    /**
     * Get authentication headers UNMASKED for debugging
     */
    private function getAuthHeadersUnmasked(ExternalAPIConfiguration $apiConfig): array
    {
        $authHeaders = [];
        $authType = $apiConfig->auth_type;
        $authConfig = $apiConfig->getAuthConfig();
        
        switch ($authType) {
            case 'api_key':
                $headerName = $authConfig['header_name'] ?? 'X-API-Key';
                $apiKey = $authConfig['api_key'] ?? 'NOT_SET';
                $authHeaders[$headerName] = $apiKey;
                break;
                
            case 'bearer_token':
                $token = $authConfig['token'] ?? 'NOT_SET';
                $authHeaders['Authorization'] = 'Bearer ' . $token;
                break;
                
            case 'basic':
                $username = $authConfig['username'] ?? 'NOT_SET';
                $password = $authConfig['password'] ?? 'NOT_SET';
                $credentials = base64_encode($username . ':' . $password);
                $authHeaders['Authorization'] = 'Basic ' . $credentials;
                break;
                
            case 'oauth':
                $accessToken = $authConfig['access_token'] ?? 'NOT_SET';
                $authHeaders['Authorization'] = 'Bearer ' . $accessToken;
                if (isset($authConfig['client_id'])) {
                    $authHeaders['X-Client-ID'] = $authConfig['client_id'];
                }
                break;
                
            case 'custom':
                if (isset($authConfig['custom_headers'])) {
                    foreach ($authConfig['custom_headers'] as $key => $value) {
                        $authHeaders[$key] = $value;
                    }
                }
                break;
        }
        
        // Handle template variables in headers (like {{auth_config.secret_key}})
        $authHeaders = $this->resolveTemplateVariables($authHeaders, $authConfig);
        
        return $authHeaders;
    }

    /**
     * Mask sensitive headers for logging
     */
    private function maskSensitiveHeaders(array $headers): array
    {
        $sensitiveKeys = [
            'authorization',
            'x-api-key',
            'api-key',
            'token',
            'password',
            'secret',
            'key',
            'auth',
        ];
        
        $maskedHeaders = [];
        foreach ($headers as $key => $value) {
            $keyLower = strtolower($key);
            $isSensitive = false;
            
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (strpos($keyLower, $sensitiveKey) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            $maskedHeaders[$key] = $isSensitive ? '[MASKED]' : $value;
        }
        
        return $maskedHeaders;
    }

    /**
     * Resolve template variables in strings
     */
    private function resolveTemplateVariables(array $data, array $authConfig): array
    {
        $resolved = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $resolved[$key] = $this->resolveTemplateString($value, $authConfig);
            } else {
                $resolved[$key] = $value;
            }
        }
        
        return $resolved;
    }

    /**
     * Resolve template variables in a single string
     */
    private function resolveTemplateString(string $string, array $authConfig): string
    {
        // Replace {{auth_config.secret_key}} with actual secret key
        if (strpos($string, '{{auth_config.secret_key}}') !== false) {
            $secretKey = $authConfig['secret_key'] ?? 'NOT_SET';
            $string = str_replace('{{auth_config.secret_key}}', $secretKey, $string);
        }
        
        // Replace {{auth_config.public_key}} with actual public key
        if (strpos($string, '{{auth_config.public_key}}') !== false) {
            $publicKey = $authConfig['public_key'] ?? 'NOT_SET';
            $string = str_replace('{{auth_config.public_key}}', $publicKey, $string);
        }
        
        // Replace {{auth_config.token}} with actual token
        if (strpos($string, '{{auth_config.token}}') !== false) {
            $token = $authConfig['token'] ?? 'NOT_SET';
            $string = str_replace('{{auth_config.token}}', $token, $string);
        }
        
        // Replace {{auth_config.api_key}} with actual API key
        if (strpos($string, '{{auth_config.api_key}}') !== false) {
            $apiKey = $authConfig['api_key'] ?? 'NOT_SET';
            $string = str_replace('{{auth_config.api_key}}', $apiKey, $string);
        }
        
        // Replace {{session.customer_id}} with test customer ID
        if (strpos($string, '{{session.customer_id}}') !== false) {
            $string = str_replace('{{session.customer_id}}', 'CUS_test123456789', $string);
        }
        
        // Replace {{session.phone}} with test phone
        if (strpos($string, '{{session.phone}}') !== false) {
            $string = str_replace('{{session.phone}}', '+2348012345678', $string);
        }
        
        // Replace {{session.email}} with test email
        if (strpos($string, '{{session.email}}') !== false) {
            $string = str_replace('{{session.email}}', 'test@example.com', $string);
        }
        
        return $string;
    }
}
