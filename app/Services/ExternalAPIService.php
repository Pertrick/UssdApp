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
        
        $response = null;
        try {
            // Build request data
            $requestData = $this->buildRequestData($apiConfig, $session, $userInput);
            
            // Make the API call
            $response = $this->makeApiCall($apiConfig, $requestData);
            
            // Process response (may throw exception if success criteria not met)
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
            
            // Log failed call with actual response (response will be set if makeApiCall succeeded but processResponse failed)
            // If response is null, it means makeApiCall threw an exception (connection error, etc.)
            $errorMessage = $e->getMessage();
            if ($response && isset($response['status'])) {
                $errorMessage .= ' (HTTP ' . $response['status'] . ')';
            }
            
            $this->logApiCall($apiConfig, $session, $requestData ?? [], $response, $responseTime, false, $errorMessage);
            
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
        
        // Convert request mapping from [{key, value}] array format to {key: value} object format
        $normalizedMapping = [];
        if (is_array($requestMapping)) {
            foreach ($requestMapping as $key => $value) {
                if (is_array($value) && isset($value['key']) && isset($value['value'])) {
                    // Array format: [{key: 'field', value: 'mapping'}]
                    $normalizedMapping[$value['key']] = $value['value'];
                } elseif (is_string($key) && (is_string($value) || is_numeric($value))) {
                    // Already in object format: {key: value}
                    $normalizedMapping[$key] = $value;
                }
            }
        }
        $requestMapping = $normalizedMapping;
        
        // Build data context for mapping
        $context = $this->buildContext($session, $userInput);
        
        // Apply request mapping - only include fields specified in mapping
        $sessionData = is_array($session->session_data) ? $session->session_data : [];
        foreach ($requestMapping as $apiField => $mappingRule) {
            $value = $this->resolveMappingValue($mappingRule, $context);
            // Include the field even if value is null (to ensure all mapped fields are sent)
            // Only skip if the mapping rule itself is empty/null
            if ($mappingRule !== null && $mappingRule !== '') {
                $requestData[$apiField] = $value;
                // Log warning only for critical fields
                if (($value === null || $value === '') && in_array($apiField, ['phone_number', 'number', 'amount', 'network', 'service'])) {
                    Log::warning('Request mapping resolved to empty for critical field', [
                        'api_field' => $apiField,
                        'mapping_rule' => $mappingRule,
                        'session_id' => $session->id,
                    ]);
                }
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
     * Supports both static and dynamic flow data access patterns
     */
    private function buildContext(USSDSession $session, array $userInput): array
    {
        $sessionData = $session->session_data ?? [];
        
        // Resolve phone number with priority: use_registered_phone > collected input > API response > session phone
        $resolvedPhoneNumber = $this->resolvePhoneNumber($session, $sessionData);
        
        // Detect flow type from current flow
        $currentFlow = $session->currentFlow;
        $isDynamicFlow = $currentFlow && $currentFlow->flow_type === 'dynamic';
        
        // For static flows: build context with direct access to fields
        $staticContext = [];
        if (!$isDynamicFlow) {
            // Top-level scalar fields from session_data
            $staticContext = array_filter($sessionData, function($value) {
                return is_scalar($value) || is_null($value);
            }, ARRAY_FILTER_USE_KEY);
            
            // Also include selected_item_data fields (if not already at top level)
            // This allows {amount} to work even if it's only in selected_item_data
            if (isset($sessionData['selected_item_data']) && is_array($sessionData['selected_item_data'])) {
                foreach ($sessionData['selected_item_data'] as $key => $value) {
                    if ((is_scalar($value) || is_null($value)) && !isset($staticContext[$key])) {
                        $staticContext[$key] = $value;
                    }
                }
            }
        }
        
        return [
            'session' => [
                'id' => $session->id,
                'session_id' => $session->session_id,
                'phone_number' => $resolvedPhoneNumber,
                'step_count' => $session->step_count,
                'data' => $sessionData, // For static flows: use {session.data.field}
            ],
            // For static flows: direct access to top-level session_data fields
            // Allows {amount}, {network}, {service} etc. to work directly
            ...$staticContext,
            // For dynamic flows: selected_item_data access
            'selected_item_data' => $sessionData['selected_item_data'] ?? [],
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
     * Resolve phone number with priority logic
     * Priority: use_registered_phone > collected input > API response > session phone
     */
    private function resolvePhoneNumber(USSDSession $session, array $sessionData): string
    {
        // If use_registered_phone is selected, use session phone
        if (isset($sessionData['recipient_type']) && $sessionData['recipient_type'] === 'self') {
            return $session->phone_number ?? '';
        }
        
        // Priority 1: Check collected input phone (from input_phone action)
        if (isset($sessionData['input_phone']) && !empty($sessionData['input_phone'])) {
            return $sessionData['input_phone'];
        }
        
        // Also check collected_inputs array
        if (isset($sessionData['collected_inputs']['input_phone']) && !empty($sessionData['collected_inputs']['input_phone'])) {
            return $sessionData['collected_inputs']['input_phone'];
        }
        
        // Priority 2: Check API response phone fields
        $phoneFields = ['phone_number', 'phone', 'number', 'recipient_phone'];
        foreach ($phoneFields as $field) {
            if (isset($sessionData[$field]) && !empty($sessionData[$field])) {
                return $sessionData[$field];
            }
        }
        
        // Priority 3: Fallback to session phone number
        return $session->phone_number ?? '';
    }

    /**
     * Resolve mapping value from context
     */
    private function resolveMappingValue(string $mappingRule, array $context)
    {
        // Handle simple field references like {session.phone_number}
        if (preg_match('/^\{([^}]+)\}$/', $mappingRule, $matches)) {
            $path = $matches[1];
            $value = $this->getNestedValue($context, $path);
            
            // Log warning only for critical fields that are empty
            if (empty($value) && $value !== '0' && $value !== 0 && in_array($path, ['network', 'amount', 'phone_number', 'number', 'service'])) {
                Log::warning('Template variable resolved to empty for critical field', [
                    'path' => $path,
                    'session_id' => $context['session']['id'] ?? null,
                ]);
            }
            
            return $value;
        }
        
        // Handle static values
        if (!str_contains($mappingRule, '{')) {
            return $mappingRule;
        }
        
        // Handle template strings like "Hello {session.phone_number}"
        return preg_replace_callback('/\{([^}]+)\}/', function($matches) use ($context) {
            $value = $this->getNestedValue($context, $matches[1]);
            
            // Log warning only for critical fields that are empty
            if (empty($value) && $value !== '0' && $value !== 0 && in_array($matches[1], ['network', 'amount', 'phone_number', 'number', 'service'])) {
                Log::warning('Template variable in string resolved to empty for critical field', [
                    'path' => $matches[1],
                    'session_id' => $context['session']['id'] ?? null,
                ]);
            }
            
            return $value ?? '';
        }, $mappingRule);
    }

    /**
     * Get nested value from array using dot notation
     * Supports multiple access patterns for static vs dynamic flows
     */
    private function getNestedValue(array $array, string $path)
    {
        $keys = explode('.', $path);
        $value = $array;
        
        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                // Fallback: For static flows, try direct access to session.data
                // This allows {amount} to work instead of requiring {session.data.amount}
                if (count($keys) === 1 && isset($array['session']['data'][$path])) {
                    return $array['session']['data'][$path];
                }
                // Also try selected_item_data for dynamic flows
                if (count($keys) === 1 && isset($array['selected_item_data'][$path])) {
                    return $array['selected_item_data'][$path];
                }
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
        
        // Check if Content-Type is form data or JSON
        $contentType = strtolower($headers['Content-Type'] ?? 'application/json');
        $isUrlEncoded = $contentType === 'application/x-www-form-urlencoded';
        $isMultipart = $contentType === 'multipart/form-data';
        $isJson = $contentType === 'application/json';
        
        // Prepare request
        $request = Http::timeout($timeout);
        
        // Handle different content types
        if ($isUrlEncoded) {
            // URL-encoded form data (standard form submission)
            $request = $request->asForm();
            // Remove Content-Type from headers as asForm() sets it automatically
            unset($headers['Content-Type']);
        } elseif ($isMultipart) {
            unset($headers['Content-Type']);
        } elseif ($isJson) {
           
            $request = $request->asJson();
            unset($headers['Content-Type']);
        }
        
        // Add remaining headers
        $request = $request->withHeaders($headers);
        
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
        
        // Log request summary (without sensitive data)
        Log::info('External API request', [
            'api_config_id' => $apiConfig->id,
            'api_name' => $apiConfig->name,
            'method' => $method,
            'url' => $url,
            'has_body' => !empty($resolvedRequestData),
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
        // Log response summary (without full body for privacy/security)
        Log::info('External API response', [
            'api_config_id' => $apiConfig->id,
            'api_name' => $apiConfig->name,
            'status_code' => $response->status(),
            'successful' => $response->successful(),
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
        $logData = [
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
        ];
        
        // Use error level for failures, info level for successes
        if (!$success) {
            Log::error('External API Call Failed', $logData);
        } else {
            Log::info('External API Call', $logData);
        }
    }

    /**
     * Test API configuration
     */
    public function testApiConfiguration(ExternalAPIConfiguration $apiConfig, array $customTestData = []): array
    {
        $startTime = microtime(true);
        
        try {
            // Build test data using request mapping (like real API calls)
            $testData = $this->buildTestRequestDataWithMapping($apiConfig, $customTestData);
            
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
    /**
     * Build test request data using request mapping (like real API calls)
     */
    private function buildTestRequestDataWithMapping(ExternalAPIConfiguration $apiConfig, array $customTestData = []): array
    {
        $requestData = [];
        
        // Get request mapping configuration
        $requestMapping = $apiConfig->getRequestMapping();
        $requestTemplate = $apiConfig->getRequestTemplate();
        
        // Convert request mapping from [{key, value}] array format to {key: value} object format
        $normalizedMapping = [];
        if (is_array($requestMapping)) {
            foreach ($requestMapping as $key => $value) {
                if (is_array($value) && isset($value['key']) && isset($value['value'])) {
                    // Array format: [{key: 'field', value: 'mapping'}]
                    $normalizedMapping[$value['key']] = $value['value'];
                } elseif (is_string($key) && (is_string($value) || is_numeric($value))) {
                    // Already in object format: {key: value}
                    $normalizedMapping[$key] = $value;
                }
            }
        }
        $requestMapping = $normalizedMapping;
        
        // Build test context (simulating session data)
        $testContext = $this->buildTestContext($customTestData);
        
        // Apply request mapping
        foreach ($requestMapping as $apiField => $mappingRule) {
            $value = $this->resolveMappingValue($mappingRule, $testContext);
            if ($value !== null) {
                $requestData[$apiField] = $value;
            }
        }
        
        // Apply request template if provided (template values override mapping)
        if (!empty($requestTemplate)) {
            $requestData = array_merge($requestTemplate, $requestData);
        }
        
        // If no mapping or template, use custom test data or minimal defaults
        if (empty($requestData) && !empty($customTestData)) {
            $requestData = $customTestData;
        } elseif (empty($requestData)) {
            $requestData = [
                'test' => true,
                'timestamp' => now()->toISOString(),
            ];
        }
        
        // Always add a test identifier to distinguish test calls
        $requestData['_test_call'] = true;
        $requestData['_test_timestamp'] = now()->toISOString();
        
        return $requestData;
    }
    
    /**
     * Build test context for mapping (simulates session data)
     */
    private function buildTestContext(array $customTestData = []): array
    {
        // Default test values for common template variables
        $defaultTestData = [
            'session' => [
                'id' => 999,
                'session_id' => 'test_session_' . time(),
                'phone_number' => $customTestData['phone_number'] ?? '+2348012345678',
                'step_count' => 1,
                'data' => array_merge([
                    'amount' => $customTestData['amount'] ?? '1000',
                    'phone' => $customTestData['phone'] ?? '+2348012345678',
                    'network' => $customTestData['network'] ?? 'MTN',
                    'Pin' => $customTestData['Pin'] ?? '1234',
                    'selected_item_data' => [
                        'network' => $customTestData['network'] ?? 'MTN',
                        'name' => $customTestData['service_name'] ?? 'Test Service',
                    ],
                ], $customTestData),
            ],
            'ussd' => [
                'id' => 1,
                'name' => 'Test USSD',
                'pattern' => '*123#',
            ],
            'user' => [
                'id' => 1,
            ],
            'input' => $customTestData,
            'timestamp' => now()->toISOString(),
            'reference' => 'TEST_' . Str::uuid()->toString(),
        ];
        
        return $defaultTestData;
    }

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
