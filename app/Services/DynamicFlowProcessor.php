<?php

namespace App\Services;

use App\Models\USSDFlow;
use App\Models\USSDSession;
use App\Models\ExternalAPIConfiguration;
use App\Services\ExternalAPIService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DynamicFlowProcessor
{
    protected ExternalAPIService $apiService;
    
    public function __construct(ExternalAPIService $apiService)
    {
        $this->apiService = $apiService;
    }
    
    /**
     * Process a dynamic flow and generate menu options from API response
     */
    public function processDynamicFlow(USSDFlow $flow, USSDSession $session): array
    {
        $dynamicConfig = $flow->dynamic_config ?? [];
        
        // Debug: Log the raw dynamic_config to see what we're working with
        Log::info('DynamicFlowProcessor: Raw dynamic_config', [
            'flow_id' => $flow->id,
            'dynamic_config' => $dynamicConfig,
            'next_flow_id_raw' => $dynamicConfig['next_flow_id'] ?? 'not_set',
            'next_flow_id_type' => gettype($dynamicConfig['next_flow_id'] ?? null),
            'continuation_type' => $dynamicConfig['continuation_type'] ?? 'not_set'
        ]);
        
        if (empty($dynamicConfig['api_configuration_id'])) {
            return [
                'success' => false,
                'error' => 'No API configuration found for dynamic flow',
                'title' => $flow->title,
                'message' => 'Dynamic flow configuration is incomplete'
            ];
        }
        
        try {
            // Get API configuration
            $apiConfig = ExternalAPIConfiguration::find($dynamicConfig['api_configuration_id']);
            if (!$apiConfig) {
                return [
                    'success' => false,
                    'error' => 'API configuration not found',
                    'title' => $flow->title,
                    'message' => 'The selected API configuration is no longer available'
                ];
            }
            
            // Make API call
            $apiResponse = $this->apiService->executeApiCall($apiConfig, $session, []);
            
            if (!$apiResponse['success']) {
                return [
                    'success' => false,
                    'error' => 'API call failed',
                    'title' => $flow->title,
                    'message' => $apiResponse['message'] ?? 'Failed to fetch data from API'
                ];
            }
            
            // Get the full API response body (don't assume 'data' key exists)
            // This preserves the entire response structure for template variables
            $fullResponseData = null;
            if (isset($apiResponse['raw_response']['body'])) {
                $rawBody = $apiResponse['raw_response']['body'];
                // Ensure it's an array for consistent handling
                if (is_array($rawBody)) {
                    $fullResponseData = $rawBody;
                } else {
                    // If raw body is not an array (string, number, etc.), wrap it
                    $fullResponseData = ['value' => $rawBody, 'data' => $rawBody];
                }
            } else {
                // Fallback: use apiResponse structure (could be direct response or wrapped)
                if (is_array($apiResponse)) {
                    $fullResponseData = $apiResponse;
                } else {
                    // If apiResponse is not an array, wrap it
                    $fullResponseData = ['value' => $apiResponse, 'data' => $apiResponse];
                }
            }
            
            // formatApiResponseToOptions will use list_path from dynamic_config to extract
            // the specific array/object for generating options
            $options = $this->formatApiResponseToOptions(
                $fullResponseData,
                $dynamicConfig,
                $session
            );
            
            // Debug logging
            Log::info('Dynamic Flow Processing Debug', [
                'flow_id' => $flow->id,
                'api_response' => $apiResponse,
                'full_response_data' => $fullResponseData,
                'dynamic_config' => $dynamicConfig,
                'list_path' => $dynamicConfig['list_path'] ?? 'data',
                'options_count' => count($options)
            ]);
            
            Log::info('Dynamic Flow Options Generated', [
                'flow_id' => $flow->id,
                'options_count' => count($options),
                'options' => $options
            ]);
            
            // Normalize next_flow_id - handle string values from JSON storage
            $nextFlowId = $dynamicConfig['next_flow_id'] ?? null;
            if ($nextFlowId === '' || $nextFlowId === '0') {
                $nextFlowId = null;
            } else if ($nextFlowId !== null && $nextFlowId !== '') {
                $nextFlowId = (int) $nextFlowId; // Ensure it's an integer
            }
            
            // Log for debugging
            Log::info('DynamicFlowProcessor: next_flow_id normalization', [
                'flow_id' => $flow->id,
                'raw_next_flow_id' => $dynamicConfig['next_flow_id'] ?? 'not_set',
                'normalized_next_flow_id' => $nextFlowId,
                'continuation_type' => $dynamicConfig['continuation_type'] ?? 'not_set',
                'options_count' => count($options)
            ]);
            
            if (empty($options)) {
            return [
                'success' => true,
                'title' => $flow->title,
                'message' => $dynamicConfig['empty_message'] ?? 'No options available',
                'options' => [],
                'continuation_type' => $dynamicConfig['continuation_type'] ?? 'continue',
                'next_flow_id' => $nextFlowId,
                'cached_api_data' => $fullResponseData // Store full response for template variables
            ];
            }
            
            return [
                'success' => true,
                'title' => $flow->title,
                'options' => $options,
                'continuation_type' => $dynamicConfig['continuation_type'] ?? 'continue',
                'next_flow_id' => $nextFlowId,
                'cached_api_data' => $fullResponseData // Store full response for template variables
            ];
            
        } catch (\Exception $e) {
            Log::error('Dynamic flow processing failed', [
                'flow_id' => $flow->id,
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Processing failed',
                'title' => $flow->title,
                'message' => 'An error occurred while processing the dynamic flow'
            ];
        }
    }
    
    /**
     * Format API response data into USSD menu options
     */
    public function formatApiResponseToOptions(array $apiData, array $config, USSDSession $session): array
    {
        $listPath = $config['list_path'] ?? 'data';
        $labelField = $config['label_field'] ?? 'name';
        $valueField = $config['value_field'] ?? 'id';
        $itemsPerPage = (int) ($config['items_per_page'] ?? 7);
        $nextLabel = $config['next_label'] ?? 'Next';
        $backLabel = $config['back_label'] ?? 'Back';
        
        // Get the list from API data using dot notation
        // If list_path is empty, use the entire apiData array
        if (empty($listPath)) {
            $items = $apiData;
        } else {
            $items = data_get($apiData, $listPath, []);
        }
        
        if (!is_array($items)) {
            return [];
        }
        
        // Check if items is an associative array (object-like) vs indexed array
        $isAssociative = !empty($items) && array_keys($items) !== range(0, count($items) - 1);
        
        // Get current page from session data
        $sessionData = $session->session_data ?? [];
        $currentPage = $sessionData['current_page'] ?? 1;
        $totalItems = count($items);
        $totalPages = ceil($totalItems / $itemsPerPage);
        
        // Calculate pagination
        $startIndex = ($currentPage - 1) * $itemsPerPage;
        $endIndex = min($startIndex + $itemsPerPage, $totalItems);
        
        // For associative arrays, we need to preserve keys during pagination
        if ($isAssociative) {
            $itemKeys = array_keys($items);
            $pageKeys = array_slice($itemKeys, $startIndex, $itemsPerPage);
            $pageItems = [];
            foreach ($pageKeys as $key) {
                $pageItems[$key] = $items[$key];
            }
        } else {
            $pageItems = array_slice($items, $startIndex, $itemsPerPage);
        }
        
        $options = [];
        $optionIndex = 0;
        
        // Add data options
        foreach ($pageItems as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            
            $label = $this->extractFieldValue($item, $labelField, $optionIndex, $index, $isAssociative);
            $value = $this->extractFieldValue($item, $valueField, $optionIndex, $index, $isAssociative);
            
            // Inject session variables into label if needed
            $label = $this->injectSessionVariables($label, $session);
            
            $options[] = [
                'label' => $label,
                'value' => $value,
                'data' => $item, // Store full item data for reference
                'sort_order' => $optionIndex + 1
            ];
            
            $optionIndex++;
        }
        
        // Add navigation options if needed
        if ($totalPages > 1) {
            // Add Next button if not on last page
            if ($currentPage < $totalPages) {
                $options[] = [
                    'label' => $nextLabel,
                    'value' => 'PAGINATION_NEXT',
                    'data' => ['page' => $currentPage + 1],
                    'sort_order' => $optionIndex + 1
                ];
                $optionIndex++;
            }
            
            // Add Back button if not on first page
            if ($currentPage > 1) {
                $options[] = [
                    'label' => $backLabel,
                    'value' => 'PAGINATION_BACK',
                    'data' => ['page' => $currentPage - 1],
                    'sort_order' => $optionIndex + 1
                ];
                $optionIndex++;
            }
        }
        
        return $options;
    }
    
    /**
     * Extract field value from item with fallback
     */
    protected function extractFieldValue(array $item, string $fieldPath, int $index, $iterationKey = null, bool $isAssociative = false): string
    {
        // Check if this is a custom template (contains {}) - prioritize templates over multi-field
        if (strpos($fieldPath, '{') !== false && strpos($fieldPath, '}') !== false) {
            return $this->extractTemplateValue($item, $fieldPath, $index, $iterationKey, $isAssociative);
        }
        
        // Check if this is a multi-field configuration (separated by +)
        if (strpos($fieldPath, '+') !== false) {
            return $this->extractMultiFieldValue($item, $fieldPath, $index, $iterationKey, $isAssociative);
        }
        
        // Special case: if fieldPath is 'key' or 'index', return the iteration key
        if ($fieldPath === 'key' || $fieldPath === 'index') {
            return $isAssociative ? (string)$iterationKey : (string)($index + 1);
        }
        
        // Single field extraction
        $value = data_get($item, $fieldPath);
        
        if ($value === null || $value === '') {
            // If it's associative and value is null, use the key as fallback
            if ($isAssociative && $iterationKey !== null) {
                return (string)$iterationKey;
            }
            
            // Fallback to common field names
            $fallbackFields = ['name', 'title', 'label', 'description', 'id', 'code'];
            foreach ($fallbackFields as $fallback) {
                $fallbackValue = data_get($item, $fallback);
                if ($fallbackValue !== null && $fallbackValue !== '') {
                    return (string) $fallbackValue;
                }
            }
            
            // Last resort: use index
            return "Option " . ($index + 1);
        }
        
        return (string) $value;
    }
    
    /**
     * Extract value from multiple fields combined
     */
    protected function extractMultiFieldValue(array $item, string $fieldPath, int $index, $iterationKey = null, bool $isAssociative = false): string
    {
        $fields = explode('+', $fieldPath);
        $values = [];
        
        foreach ($fields as $field) {
            $field = trim($field);
            
            // Special case: 'key' or 'index' field
            if ($field === 'key' || $field === 'index') {
                $values[] = $isAssociative ? (string)$iterationKey : (string)($index + 1);
            } else {
                $value = data_get($item, $field);
                if ($value !== null && $value !== '') {
                    $values[] = (string) $value;
                }
            }
        }
        
        if (empty($values)) {
            return "Option " . ($index + 1);
        }
        
        return implode(' ', $values);
    }
    
    /**
     * Extract value using template with placeholders
     */
    protected function extractTemplateValue(array $item, string $template, int $index, $iterationKey = null, bool $isAssociative = false): string
    {
        $result = $template;
        
        // First, handle special placeholders {key} and {index}
        if (strpos($template, '{key}') !== false || strpos($template, '{index}') !== false) {
            $keyValue = $isAssociative ? (string)$iterationKey : (string)($index + 1);
            $result = str_replace(['{key}', '{index}'], $keyValue, $result);
        }
        
        // Replace placeholders like {name}, {price}, {available_balance} with actual values from item
        foreach ($item as $key => $value) {
            $placeholder = '{' . $key . '}';
            if (strpos($result, $placeholder) !== false) {
                $result = str_replace($placeholder, (string)$value, $result);
            }
        }
        
        // If no placeholders were replaced, fallback to index
        if ($result === $template) {
            return "Option " . ($index + 1);
        }
        
        return $result;
    }
    
    /**
     * Inject session variables into text using {{variable}} syntax
     */
    protected function injectSessionVariables(string $text, USSDSession $session): string
    {
        $sessionData = $session->session_data ?? [];
        
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function ($matches) use ($sessionData) {
            $variable = trim($matches[1]);
            return data_get($sessionData, $variable, $matches[0]);
        }, $text);
    }
    
    /**
     * Determine next step based on continuation type and user selection
     */
    public function determineNextStep(USSDFlow $flow, string $selectedValue, USSDSession $session): array
    {
        $dynamicConfig = $flow->dynamic_config ?? [];
        $continuationType = $dynamicConfig['continuation_type'] ?? 'continue';
        
        // Log the next step determination
        Log::info('Determining next step', [
            'flow_id' => $flow->id,
            'selected_value' => $selectedValue,
            'continuation_type' => $continuationType,
            'session_id' => $session->id
        ]);
        
        switch ($continuationType) {
            case 'end':
                return [
                    'action' => 'end_session',
                    'message' => 'Thank you for using our service!'
                ];
                
            case 'continue':
                $nextFlowId = $dynamicConfig['next_flow_id'] ?? null;
                if ($nextFlowId) {
                    Log::info('Navigating to next flow', [
                        'current_flow_id' => $flow->id,
                        'next_flow_id' => $nextFlowId,
                        'selected_value' => $selectedValue
                    ]);
                    
                    return [
                        'action' => 'navigate',
                        'next_flow_id' => $nextFlowId
                    ];
                }
                return [
                    'action' => 'end_session',
                    'message' => 'Session completed'
                ];
                
            case 'api_dependent':
                // This would require additional API call to determine next step
                // For now, default to continue behavior
                return $this->determineNextStep($flow, $selectedValue, $session);
                
            default:
                return [
                    'action' => 'end_session',
                    'message' => 'Session completed'
                ];
        }
    }
    
    /**
     * Validate dynamic flow configuration
     */
    public function validateDynamicConfig(array $config): array
    {
        $errors = [];
        
        if (empty($config['api_configuration_id'])) {
            $errors[] = 'API configuration is required for dynamic flows';
        }
        
        if (empty($config['list_path'])) {
            $errors[] = 'List path is required to specify where the options array is located in the API response';
        }
        
        if (empty($config['label_field'])) {
            $errors[] = 'Label field is required to specify which field contains the option display text';
        }
        
        if (empty($config['value_field'])) {
            $errors[] = 'Value field is required to specify which field contains the option value';
        }
        
        return $errors;
    }
    
    /**
     * Get preview of how API response would be formatted
     */
    public function previewApiResponse(ExternalAPIConfiguration $apiConfig, array $config): array
    {
        try {
            // Create a dummy session for preview
            $dummySession = new USSDSession();
            $dummySession->session_data = [];
            
            // Make a test API call (you might want to add a test endpoint for this)
            $testResponse = $this->apiService->executeApiCall($apiConfig, $dummySession, []);
            
            if (!$testResponse['success']) {
                return [
                    'success' => false,
                    'error' => 'API test failed: ' . ($testResponse['message'] ?? 'Unknown error')
                ];
            }
            
            // Format a preview (limit to first 5 items)
            $previewOptions = $this->formatApiResponseToOptions(
                $testResponse['data'] ?? [],
                $config,
                $dummySession
            );
            
            return [
                'success' => true,
                'preview_options' => array_slice($previewOptions, 0, 5),
                'total_items' => count($previewOptions),
                'raw_response' => $testResponse['data']
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Preview failed: ' . $e->getMessage()
            ];
        }
    }
}
