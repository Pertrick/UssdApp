<?php

namespace App\Services;

use App\Models\ExternalAPIConfiguration;
use App\Models\USSDSession;
use App\Services\APITestLoggingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExternalAPIService
{
    protected $loggingService;

    public function __construct(APITestLoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * Execute an external API call based on configuration
     */
    public function executeApiCall(
        ExternalAPIConfiguration $apiConfig, 
        USSDSession $session, 
        array $userInput = []
    ): array {
        $startTime = microtime(true);
        
        // Always make real API calls regardless of environment
        // Removed simulation mode check - all calls are now real
        
        try {
            // Build request data
            $requestData = $this->buildRequestData($apiConfig, $session, $userInput);
            
            // Make the API call
            $response = $this->makeApiCall($apiConfig, $requestData);
            
            // Process response
            $processedResponse = $this->processResponse($apiConfig, $response, $session);
            
            // Calculate response time
            $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            
            // Update statistics
            $apiConfig->updateCallStats(true, $responseTime);
            
            // Log successful call
            $this->logApiCall($apiConfig, $session, $requestData, $response, $responseTime, true);
            
            return $processedResponse;
            
        } catch (\Exception $e) {
            // Calculate response time
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            // Update statistics
            $apiConfig->updateCallStats(false, $responseTime);
            
            // Log failed call
            $this->logApiCall($apiConfig, $session, $requestData ?? [], null, $responseTime, false, $e->getMessage());
            
            // Return error response
            return $this->handleApiError($apiConfig, $e);
        }
    }

    /**
     * Build request data based on configuration and session
     */
    private function buildRequestData(
        ExternalAPIConfiguration $apiConfig, 
        USSDSession $session, 
        array $userInput
    ): array {
        $requestData = [];
        
        // Get request mapping configuration
        $requestMapping = $apiConfig->getRequestMapping();
        $requestTemplate = $apiConfig->getRequestTemplate();
        
        // Build data context for mapping
        $context = $this->buildContext($session, $userInput);
        
        // Add session data to request data for URL template processing
        $sessionData = $session->session_data ?? [];
        $requestData = array_merge($requestData, $sessionData);
        
        // Apply request mapping
        foreach ($requestMapping as $apiField => $mappingRule) {
            $value = $this->resolveMappingValue($mappingRule, $context);
            if ($value !== null) {
                $requestData[$apiField] = $value;
            }
        }
        
        // Apply request template if provided
        if (!empty($requestTemplate)) {
            $requestData = array_merge($requestTemplate, $requestData);
        }
        
        return $requestData;
    }

    /**
     * Build context data for mapping
     */
    private function buildContext(USSDSession $session, array $userInput): array
    {
        $sessionData = $session->session_data ?? [];
        
        return [
            'session' => [
                'id' => $session->id,
                'session_id' => $session->session_id,
                'phone_number' => $session->phone_number,
                'step_count' => $session->step_count,
                'data' => $sessionData,
            ],
            'ussd' => [
                'id' => $session->ussd?->id,
                'name' => $session->ussd?->name,
                'pattern' => $session->ussd?->pattern,
            ],
            'user' => [
                'id' => $session->ussd?->user_id,
            ],
            'input' => $userInput,
            'timestamp' => now()->toISOString(),
            'reference' => Str::uuid()->toString(),
        ];
    }

    /**
     * Resolve mapping value from context
     */
    private function resolveMappingValue(string $mappingRule, array $context)
    {
        // Handle simple field references like {session.phone_number}
        if (preg_match('/^\{([^}]+)\}$/', $mappingRule, $matches)) {
            $path = $matches[1];
            return $this->getNestedValue($context, $path);
        }
        
        // Handle static values
        if (!str_contains($mappingRule, '{')) {
            return $mappingRule;
        }
        
        // Handle template strings like "Hello {session.phone_number}"
        return preg_replace_callback('/\{([^}]+)\}/', function($matches) use ($context) {
            $value = $this->getNestedValue($context, $matches[1]);
            return $value ?? '';
        }, $mappingRule);
    }

    /**
     * Get nested value from array using dot notation
     */
    private function getNestedValue(array $array, string $path)
    {
        $keys = explode('.', $path);
        $value = $array;
        
        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }
        
        return $value;
    }

    /**
     * Process dynamic URL templates
     */
    private function processDynamicUrl(string $url, array $requestData): string
    {
        // Replace template variables in URL
        $processedUrl = $url;
        
        // Find all template variables in the URL (e.g., {{variable_name}})
        preg_match_all('/\{\{([^}]+)\}\}/', $url, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $index => $variableName) {
                $template = $matches[0][$index]; // Full template like {{variable_name}}
                $value = $this->getTemplateValue($variableName, $requestData);
                
                // Replace the template with the actual value
                $processedUrl = str_replace($template, $value, $processedUrl);
            }
        }
        
        return $processedUrl;
    }

    /**
     * Get value for a template variable
     */
    private function getTemplateValue(string $variableName, array $requestData): string
    {
        // Handle nested variables (e.g., session.phone_number, selected_item_value)
        $parts = explode('.', $variableName);
        
        if (count($parts) === 1) {
            // Simple variable
            return $requestData[$variableName] ?? '';
        }
        
        // Nested variable - navigate through the array
        $value = $requestData;
        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return ''; // Variable not found
            }
        }
        
        return is_string($value) ? $value : (string)$value;
    }

    /**
     * Make the actual API call
     */
    private function makeApiCall(ExternalAPIConfiguration $apiConfig, array $requestData): array
    {
        $method = strtoupper($apiConfig->method);
        $url = $this->processDynamicUrl($apiConfig->endpoint_url, $requestData);
        $timeout = $apiConfig->timeout;
        $retryAttempts = $apiConfig->retry_attempts;
        
        // Prepare headers
        $headers = $this->prepareHeaders($apiConfig);
        
        // Prepare request
        $request = Http::timeout($timeout)->withHeaders($headers);
        
        // Add authentication
        $request = $this->addAuthentication($request, $apiConfig);
        
        // Make request with retry logic
        $attempt = 0;
        $lastException = null;
        
        while ($attempt <= $retryAttempts) {
            try {
                $response = match($method) {
                    'GET' => $request->get($url, $requestData),
                    'POST' => $request->post($url, $requestData),
                    'PUT' => $request->put($url, $requestData),
                    'DELETE' => $request->delete($url, $requestData),
                    'PATCH' => $request->patch($url, $requestData),
                    default => throw new \Exception("Unsupported HTTP method: {$method}")
                };
                
                return [
                    'status' => $response->status(),
                    'headers' => $response->headers(),
                    'body' => $response->json() ?? $response->body(),
                    'successful' => $response->successful(),
                ];
                
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;
                
                if ($attempt <= $retryAttempts) {
                    // Wait before retry (exponential backoff)
                    sleep(pow(2, $attempt - 1));
                }
            }
        }
        
        throw $lastException ?? new \Exception('API call failed after all retry attempts');
    }

    /**
     * Prepare headers for API call
     */
    private function prepareHeaders(ExternalAPIConfiguration $apiConfig): array
    {
        $headersData = $apiConfig->getHeaders() ?? [];
        
        // Convert headers from [{key, value}] array format to {key: value} object format
        $headers = [];
        if (is_array($headersData)) {
            foreach ($headersData as $header) {
                if (is_array($header) && isset($header['key']) && isset($header['value'])) {
                    // Array format: [{key: 'X-Header', value: 'value'}]
                    $headers[$header['key']] = $header['value'];
                } elseif (is_string($header) || is_numeric($header)) {
                    // Skip invalid entries
                    continue;
                } else {
                    // Already in object format: {key: value}
                    $headers = array_merge($headers, $header);
                }
            }
        }
        
        // Add default headers if not provided
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/json';
        }
        
        if (!isset($headers['Accept'])) {
            $headers['Accept'] = 'application/json';
        }
        
        // Resolve template variables in headers
        $headers = $this->resolveTemplateVariables($headers, $apiConfig->getAuthConfig());
        
        return $headers;
    }

    /**
     * Resolve template variables in data (supports nested arrays and objects)
     */
    private function resolveTemplateVariables($data, array $authConfig)
    {
        if (is_string($data)) {
            return $this->resolveTemplateString($data, $authConfig);
        }
        
        if (is_array($data)) {
            $resolved = [];
            foreach ($data as $key => $value) {
                $resolved[$key] = $this->resolveTemplateVariables($value, $authConfig);
            }
            return $resolved;
        }
        
        if (is_object($data)) {
            $resolved = clone $data;
            foreach ($data as $key => $value) {
                $resolved->$key = $this->resolveTemplateVariables($value, $authConfig);
            }
            return $resolved;
        }
        
        return $data;
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
        
        // Replace {{auth_config.token}} or {{auth_config.bearer_token}} with actual token
        if (strpos($string, '{{auth_config.token}}') !== false || strpos($string, '{{auth_config.bearer_token}}') !== false) {
            // Support both 'bearer_token' and 'token' keys for compatibility
            $token = $authConfig['bearer_token'] ?? $authConfig['token'] ?? 'NOT_SET';
            $string = str_replace(['{{auth_config.token}}', '{{auth_config.bearer_token}}'], $token, $string);
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

    /**
     * Make API call with detailed logging of actual HTTP request
     */
    private function makeApiCallWithLogging(ExternalAPIConfiguration $apiConfig, array $requestData): array
    {
        $method = strtoupper($apiConfig->method);
        $url = $this->processDynamicUrl($apiConfig->endpoint_url, $requestData);
        $timeout = $apiConfig->timeout;
        $retryAttempts = $apiConfig->retry_attempts;
        
        // Prepare headers
        $headers = $this->prepareHeaders($apiConfig);
        
        // Prepare request
        $request = Http::timeout($timeout)->withHeaders($headers);
        
        // Add authentication
        $request = $this->addAuthentication($request, $apiConfig);
        
        // Resolve template variables in request body before making the call
        $resolvedRequestData = $this->resolveTemplateVariables($requestData, $apiConfig->getAuthConfig());
        
        // Log the ACTUAL HTTP request that will be sent
        $this->logActualHttpRequest($apiConfig, $method, $url, $headers, $resolvedRequestData);
        
        // Make request with retry logic
        $attempt = 0;
        $lastException = null;
        
        while ($attempt <= $retryAttempts) {
            try {
                $response = match($method) {
                    'GET' => $request->get($url, $resolvedRequestData),
                    'POST' => $request->post($url, $resolvedRequestData),
                    'PUT' => $request->put($url, $resolvedRequestData),
                    'DELETE' => $request->delete($url, $resolvedRequestData),
                    'PATCH' => $request->patch($url, $resolvedRequestData),
                    default => throw new \Exception("Unsupported HTTP method: {$method}")
                };
                
                return [
                    'status' => $response->status(),
                    'headers' => $response->headers(),
                    'body' => $response->json() ?? $response->body(),
                    'successful' => $response->successful(),
                ];
                
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;
                
                if ($attempt <= $retryAttempts) {
                    // Wait before retry (exponential backoff)
                    sleep(pow(2, $attempt - 1));
                }
            }
        }
        
        throw $lastException ?? new \Exception('API call failed after all retry attempts');
    }

    /**
     * Log the actual HTTP request that will be sent
     */
    private function logActualHttpRequest(ExternalAPIConfiguration $apiConfig, string $method, string $url, array $headers, array $resolvedRequestData): void
    {
        // Get the actual headers that will be sent (including auth)
        $actualHeaders = $headers;
        
        // Add authentication headers to see what's actually sent
        $authHeaders = $this->getActualAuthHeaders($apiConfig);
        $actualHeaders = array_merge($actualHeaders, $authHeaders);
        
        Log::info('ACTUAL HTTP REQUEST SENT', [
            'api_config_id' => $apiConfig->id,
            'api_name' => $apiConfig->name,
            'method' => $method,
            'url' => $url,
            'headers' => $actualHeaders,
            'body' => $resolvedRequestData, // Use resolved data!
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get actual authentication headers that will be sent
     */
    private function getActualAuthHeaders(ExternalAPIConfiguration $apiConfig): array
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
                // Support both 'bearer_token' and 'token' keys for compatibility
                $token = $authConfig['bearer_token'] ?? $authConfig['token'] ?? 'NOT_SET';
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
        
        // Resolve template variables in auth headers
        $authHeaders = $this->resolveTemplateVariables($authHeaders, $authConfig);
        
        return $authHeaders;
    }

    /**
     * Log the actual HTTP response received
     */
    private function logActualHttpResponse(ExternalAPIConfiguration $apiConfig, $response): void
    {
        Log::info('ACTUAL HTTP RESPONSE RECEIVED', [
            'api_config_id' => $apiConfig->id,
            'api_name' => $apiConfig->name,
            'status_code' => $response->status(),
            'response_headers' => $response->headers(),
            'response_body' => $response->json() ?? $response->body(),
            'successful' => $response->successful(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Add authentication to request
     */
    private function addAuthentication($request, ExternalAPIConfiguration $apiConfig)
    {
        $authType = $apiConfig->auth_type;
        $authConfig = $apiConfig->getAuthConfig();
        
        return match($authType) {
            'api_key' => $this->addApiKeyAuth($request, $authConfig),
            'bearer_token' => $this->addBearerTokenAuth($request, $authConfig),
            'basic' => $this->addBasicAuth($request, $authConfig),
            'oauth' => $this->addOAuthAuth($request, $authConfig),
            'none' => $request,
            default => $request
        };
    }

    private function addApiKeyAuth($request, array $authConfig)
    {
        $apiKey = $authConfig['api_key'] ?? null;
        $headerName = $authConfig['header_name'] ?? 'X-API-Key';
        
        if ($apiKey) {
            return $request->withHeaders([$headerName => $apiKey]);
        }
        
        return $request;
    }

    private function addBearerTokenAuth($request, array $authConfig)
    {
        // Support both 'bearer_token' and 'token' keys for compatibility
        $token = $authConfig['bearer_token'] ?? $authConfig['token'] ?? null;
        
        if ($token) {
            return $request->withToken($token);
        }
        
        return $request;
    }

    private function addBasicAuth($request, array $authConfig)
    {
        $username = $authConfig['username'] ?? null;
        $password = $authConfig['password'] ?? null;
        
        if ($username && $password) {
            return $request->withBasicAuth($username, $password);
        }
        
        return $request;
    }

    private function addOAuthAuth($request, array $authConfig)
    {
        // Implement OAuth authentication if needed
        // This is a placeholder for future OAuth implementation
        return $request;
    }

    /**
     * Process API response based on configuration
     */
    private function processResponse(
        ExternalAPIConfiguration $apiConfig, 
        array $response, 
        USSDSession $session
    ): array {
        $responseMapping = $apiConfig->getResponseMapping();
        $successCriteria = $apiConfig->getSuccessCriteria();
        
        // Check if response meets success criteria
        $isSuccess = $this->evaluateSuccessCriteria($successCriteria, $response);
        
        if (!$isSuccess) {
            throw new \Exception('API call did not meet success criteria');
        }
        
        // Map response to USSD flow data
        $mappedData = [];
        foreach ($responseMapping as $ussdField => $apiField) {
            $value = $this->getNestedValue($response['body'], $apiField);
            if ($value !== null) {
                $mappedData[$ussdField] = $value;
            }
        }
        
        // Store mapped data in session
        $this->storeResponseData($session, $mappedData);
        
        return [
            'success' => true,
            'data' => $mappedData,
            'raw_response' => $response,
            'message' => $this->getSuccessMessage($apiConfig, $mappedData),
        ];
    }

    /**
     * Evaluate success criteria
     */
    private function evaluateSuccessCriteria(array $criteria, array $response): bool
    {
        if (empty($criteria)) {
            // Default success criteria
            return $response['successful'] && $response['status'] >= 200 && $response['status'] < 300;
        }
        
        foreach ($criteria as $rule) {
            $field = $rule['field'] ?? null;
            $operator = $rule['operator'] ?? 'equals';
            $value = $rule['value'] ?? null;
            
            if ($field && $operator && $value !== null) {
                $actualValue = $this->getNestedValue($response['body'], $field);
                
                $isMatch = match($operator) {
                    'equals' => $actualValue == $value,
                    'not_equals' => $actualValue != $value,
                    'contains' => str_contains($actualValue, $value),
                    'greater_than' => $actualValue > $value,
                    'less_than' => $actualValue < $value,
                    'exists' => $actualValue !== null,
                    'not_exists' => $actualValue === null,
                    default => false
                };
                
                if (!$isMatch) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Store response data in session
     */
    private function storeResponseData(USSDSession $session, array $data): void
    {
        $sessionData = $session->session_data ?? [];
        $sessionData['api_response'] = $data;
        $sessionData['last_api_call'] = now()->toISOString();
        
        $session->update(['session_data' => $sessionData]);
    }

    /**
     * Get success message
     */
    private function getSuccessMessage(ExternalAPIConfiguration $apiConfig, array $data): string
    {
        $messageTemplate = $apiConfig->getErrorHandling()['success_message'] ?? 'Operation completed successfully.';
        
        // Replace placeholders in message
        return preg_replace_callback('/\{([^}]+)\}/', function($matches) use ($data) {
            $value = $this->getNestedValue($data, $matches[1]);
            return $value ?? '';
        }, $messageTemplate);
    }

    /**
     * Handle API errors
     */
    private function handleApiError(ExternalAPIConfiguration $apiConfig, \Exception $exception): array
    {
        $errorHandling = $apiConfig->getErrorHandling();
        $fallbackMessage = $errorHandling['fallback_message'] ?? 'Service temporarily unavailable. Please try again later.';
        
        return [
            'success' => false,
            'error' => $exception->getMessage(),
            'message' => $fallbackMessage,
            'data' => [],
        ];
    }

    /**
     * Log API call
     */
    private function logApiCall(
        ExternalAPIConfiguration $apiConfig,
        USSDSession $session,
        array $requestData,
        ?array $response,
        float $responseTime,
        bool $success,
        ?string $errorMessage = null,
        bool $simulation = false
    ): void {
        Log::info('External API Call', [
            'api_config_id' => $apiConfig->id,
            'api_name' => $apiConfig->name,
            'session_id' => $session->id,
            'ussd_id' => $session->ussd_id,
            'request_data' => $requestData,
            'response' => $response,
            'response_time_ms' => $responseTime,
            'success' => $success,
            'error_message' => $errorMessage,
            'simulation' => $simulation,
            'timestamp' => now(),
        ]);
    }

    /**
     * Test API configuration
     */
    public function testApiConfiguration(ExternalAPIConfiguration $apiConfig): array
    {
        $startTime = microtime(true);
        
        try {
            // Build minimal test data for API testing
            $testData = $this->buildTestRequestData($apiConfig);
            
            // Log the test request details (UNMASKED for debugging)
            $this->loggingService->logTestRequestUnmasked($apiConfig, $testData);
            
            // Make direct API call without session complexity
            $result = $this->makeApiCallWithLogging($apiConfig, $testData);
            
            // Calculate response time
            $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            
            // Log the test response details
            $this->loggingService->logTestResponse($apiConfig, $result, $responseTime);
            
            // Update test status
            $apiConfig->update([
                'test_status' => 'success',
                'last_tested_at' => now(),
            ]);
            
            return [
                'success' => true,
                'message' => 'API configuration test successful',
                'response_time' => round($responseTime, 2),
                'status_code' => $result['status'] ?? null,
                'response' => [
                    'status' => $result['status'] ?? null,
                    'headers' => $result['headers'] ?? [],
                    'body' => $result['body'] ?? null,
                    'successful' => $result['successful'] ?? false,
                ],
            ];
            
        } catch (\Exception $e) {
            // Log the test error
            $this->loggingService->logTestError($apiConfig, $e);
            
            // Update test status
            $apiConfig->update([
                'test_status' => 'failed',
                'last_tested_at' => now(),
            ]);
            
            return [
                'success' => false,
                'message' => 'API configuration test failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build minimal test data for API testing
     */
    private function buildTestRequestData(ExternalAPIConfiguration $apiConfig): array
    {
        // Get request template if available (this is the primary source)
        $requestTemplate = $apiConfig->getRequestTemplate();
        
        if (!empty($requestTemplate)) {
            // Use the template as-is for testing
            $testData = $requestTemplate;
        } else {
            // If no template exists, use minimal test data
            $testData = [
                'test' => true,
                'timestamp' => now()->toISOString(),
            ];
        }
        
        // Always add a test identifier to distinguish test calls
        $testData['_test_call'] = true;
        $testData['_test_timestamp'] = now()->toISOString();
        
        return $testData;
    }

    /**
     * Check if the current session is in simulation mode
     * 
     * NOTE: This method now always returns false - all API calls are real regardless of environment
     */
    private function isSimulationMode(USSDSession $session): bool
    {
        // Always return false - all API calls are now real
        // This ensures real API calls are made regardless of environment
        return false;
    }

    /**
     * Simulate an API call for testing purposes
     */
    private function simulateApiCall(
        ExternalAPIConfiguration $apiConfig, 
        USSDSession $session, 
        array $userInput, 
        float $startTime
    ): array {
        // Simulate network delay
        usleep(rand(500000, 2000000)); // 0.5 to 2 seconds
        
        $responseTime = (microtime(true) - $startTime) * 1000;
        
        // Generate mock response based on API configuration
        $mockResponse = $this->generateMockResponse($apiConfig, $session, $userInput);
        
        // Process the mock response
        $processedResponse = $this->processResponse($apiConfig, $mockResponse, $session);
        
        // Log the simulated call
        $this->logApiCall($apiConfig, $session, [], $mockResponse, $responseTime, true, null, true);
        
        return $processedResponse;
    }

    /**
     * Generate a mock response for simulation
     */
    private function generateMockResponse(
        ExternalAPIConfiguration $apiConfig, 
        USSDSession $session, 
        array $userInput
    ): array {
        // Generate a mock transaction ID
        $transactionId = 'SIM_' . strtoupper(Str::random(8));
        
        // Get session data
        $sessionData = $session->session_data ?? [];
        $phone = $sessionData['phone'] ?? $session->phone_number;
        $amount = $sessionData['amount'] ?? '100';
        
        // Create mock response based on API type
        $mockResponse = [
            'successful' => true,
            'status' => 200,
            'body' => [
                'data' => [
                    'status' => 'success',
                    'message' => 'Transaction completed successfully',
                    'transaction_id' => $transactionId,
                    'phone' => $phone,
                    'amount' => $amount,
                    'currency' => 'NGN',
                    'timestamp' => now()->toISOString(),
                    'provider' => $apiConfig->name,
                    'simulated' => true
                ]
            ]
        ];
        
        return $mockResponse;
    }

}
