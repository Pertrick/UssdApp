<?php

namespace App\Services;

use App\Models\FlowStep;
use App\Models\FlowConfig;
use App\Models\USSDSession;
use App\Models\ExternalAPIConfiguration;
use App\Services\ExternalAPIService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DynamicFlowEngine
{
    protected ExternalAPIService $apiService;
    
    public function __construct(ExternalAPIService $apiService)
    {
        $this->apiService = $apiService;
    }
    
    /**
     * Execute a dynamic flow step
     */
    public function executeStep(USSDSession $session, string $stepId, array $userInput = []): array
    {
        // Get the current step
        $step = FlowStep::where('ussd_id', $session->ussd_id)
            ->where('step_id', $stepId)
            ->where('is_active', true)
            ->first();
            
        if (!$step) {
            throw new \Exception("Step '{$stepId}' not found or inactive");
        }
        
        // Check conditions if any
        if ($step->hasConditions() && !$this->evaluateConditions($step->getConditions(), $session)) {
            throw new \Exception("Step conditions not met for '{$stepId}'");
        }
        
        // Execute step based on type
        $result = match($step->type) {
            FlowStep::TYPE_MENU => $this->executeMenuStep($step, $session, $userInput),
            FlowStep::TYPE_API_CALL => $this->executeApiCallStep($step, $session, $userInput),
            FlowStep::TYPE_DYNAMIC_MENU => $this->executeDynamicMenuStep($step, $session, $userInput),
            FlowStep::TYPE_INPUT => $this->executeInputStep($step, $session, $userInput),
            FlowStep::TYPE_CONDITION => $this->executeConditionStep($step, $session, $userInput),
            FlowStep::TYPE_MESSAGE => $this->executeMessageStep($step, $session, $userInput),
            default => throw new \Exception("Unknown step type: {$step->type}")
        };
        
        // Update session with step result
        $this->updateSessionData($session, $result);
        
        // Determine next step
        $nextStep = $this->determineNextStep($step, $result, $session);
        
        return [
            'success' => true,
            'step_id' => $stepId,
            'step_type' => $step->type,
            'result' => $result,
            'next_step' => $nextStep,
            'session_data' => $session->session_data,
        ];
    }
    
    /**
     * Execute a menu step
     */
    protected function executeMenuStep(FlowStep $step, USSDSession $session, array $userInput): array
    {
        $data = $step->getData();
        $options = $data['options'] ?? [];
        
        // Inject variables into options
        $processedOptions = [];
        foreach ($options as $option) {
            $processedOptions[] = [
                'label' => $this->injectVariables($option['label'] ?? '', $session),
                'value' => $this->injectVariables($option['value'] ?? '', $session),
            ];
        }
        
        return [
            'type' => 'menu',
            'title' => $this->injectVariables($data['title'] ?? '', $session),
            'options' => $processedOptions,
            'prompt' => $this->injectVariables($data['prompt'] ?? 'Select an option:', $session),
        ];
    }
    
    /**
     * Execute an API call step
     */
    protected function executeApiCallStep(FlowStep $step, USSDSession $session, array $userInput): array
    {
        $data = $step->getData();
        
        // Get API configuration
        $apiConfigId = $data['api_config_id'] ?? null;
        if (!$apiConfigId) {
            throw new \Exception("API configuration ID not specified for step '{$step->step_id}'");
        }
        
        $apiConfig = ExternalAPIConfiguration::find($apiConfigId);
        if (!$apiConfig) {
            throw new \Exception("API configuration not found: {$apiConfigId}");
        }
        
        // Make the API call
        $apiResult = $this->apiService->executeApiCall($apiConfig, $session, $userInput);
        
        // Store API response data
        $storeAs = $data['store_as'] ?? 'api_response';
        $sessionData = $session->session_data ?? [];
        $sessionData[$storeAs] = $apiResult['data'] ?? [];
        $session->update(['session_data' => $sessionData]);
        
        return [
            'type' => 'api_call',
            'success' => $apiResult['success'] ?? false,
            'data' => $apiResult['data'] ?? [],
            'message' => $apiResult['message'] ?? 'API call completed',
            'stored_as' => $storeAs,
        ];
    }
    
    /**
     * Execute a dynamic menu step
     */
    protected function executeDynamicMenuStep(FlowStep $step, USSDSession $session, array $userInput): array
    {
        $data = $step->getData();
        $source = $data['source'] ?? 'session';
        
        // Get data source
        $sourceData = $this->getDataSource($source, $session);
        
        if (empty($sourceData)) {
            return [
                'type' => 'dynamic_menu',
                'title' => $this->injectVariables($data['title'] ?? '', $session),
                'options' => [],
                'message' => $data['empty_message'] ?? 'No options available',
            ];
        }
        
        // Generate options from source data
        $options = $this->generateOptionsFromData($sourceData, $data, $session);
        
        return [
            'type' => 'dynamic_menu',
            'title' => $this->injectVariables($data['title'] ?? '', $session),
            'options' => $options,
            'prompt' => $this->injectVariables($data['prompt'] ?? 'Select an option:', $session),
        ];
    }
    
    /**
     * Execute an input step
     */
    protected function executeInputStep(FlowStep $step, USSDSession $session, array $userInput): array
    {
        $data = $step->getData();
        $inputValue = $userInput['value'] ?? '';
        
        // Validate input if validation rules exist
        if (isset($data['validation'])) {
            $validation = $this->validateInput($inputValue, $data['validation']);
            if (!$validation['valid']) {
                return [
                    'type' => 'input',
                    'success' => false,
                    'error' => $validation['message'],
                    'prompt' => $this->injectVariables($data['prompt'] ?? 'Enter value:', $session),
                ];
            }
        }
        
        // Store input value
        $storeAs = $data['store_as'] ?? 'user_input';
        $sessionData = $session->session_data ?? [];
        $sessionData[$storeAs] = $inputValue;
        $session->update(['session_data' => $sessionData]);
        
        return [
            'type' => 'input',
            'success' => true,
            'value' => $inputValue,
            'stored_as' => $storeAs,
            'message' => $data['success_message'] ?? 'Input received',
        ];
    }
    
    /**
     * Execute a condition step
     */
    protected function executeConditionStep(FlowStep $step, USSDSession $session, array $userInput): array
    {
        $data = $step->getData();
        $conditions = $data['conditions'] ?? [];
        
        $result = $this->evaluateConditions($conditions, $session);
        
        return [
            'type' => 'condition',
            'result' => $result,
            'conditions' => $conditions,
        ];
    }
    
    /**
     * Execute a message step
     */
    protected function executeMessageStep(FlowStep $step, USSDSession $session, array $userInput): array
    {
        $data = $step->getData();
        
        return [
            'type' => 'message',
            'message' => $this->injectVariables($data['message'] ?? '', $session),
            'title' => $this->injectVariables($data['title'] ?? '', $session),
        ];
    }
    
    /**
     * Inject variables into a template string
     */
    protected function injectVariables(string $template, USSDSession $session): string
    {
        $context = $this->buildContext($session);
        
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function ($matches) use ($context) {
            $path = trim($matches[1]);
            return $this->getNestedValue($context, $path) ?? '';
        }, $template);
    }
    
    /**
     * Build context for variable injection
     */
    protected function buildContext(USSDSession $session): array
    {
        $sessionData = $session->session_data ?? [];
        
        // Get flow configs
        $configs = FlowConfig::where('ussd_id', $session->ussd_id)
            ->where('is_active', true)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
        
        return [
            'session' => [
                'id' => $session->id,
                'session_id' => $session->session_id,
                'phone_number' => $session->phone_number,
                'step_count' => $session->step_count,
                'data' => $sessionData,
            ],
            'ussd' => [
                'id' => $session->ussd->id,
                'name' => $session->ussd->name,
                'pattern' => $session->ussd->pattern,
            ],
            'config' => $configs,
            'api' => $sessionData, // API responses are stored in session_data
        ];
    }
    
    /**
     * Get nested value from array using dot notation
     */
    protected function getNestedValue(array $array, string $path)
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
     * Evaluate conditions
     */
    protected function evaluateConditions(array $conditions, USSDSession $session): bool
    {
        $context = $this->buildContext($session);
        
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;
            
            if (!$field || $value === null) {
                continue;
            }
            
            $actualValue = $this->getNestedValue($context, $field);
            
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
        
        return true;
    }
    
    /**
     * Get data source for dynamic menus
     */
    protected function getDataSource(string $source, USSDSession $session): array
    {
        if ($source === 'session') {
            return $session->session_data ?? [];
        }
        
        // Handle API response sources like 'api.bundles'
        $context = $this->buildContext($session);
        return $this->getNestedValue($context, $source) ?? [];
    }
    
    /**
     * Generate options from data source
     */
    protected function generateOptionsFromData(array $sourceData, array $stepData, USSDSession $session): array
    {
        $options = [];
        $labelField = $stepData['label_field'] ?? 'name';
        $valueField = $stepData['value_field'] ?? 'id';
        $listPath = $stepData['list_path'] ?? null;
        
        // Get the list from source data
        $list = $listPath ? $this->getNestedValue($sourceData, $listPath) : $sourceData;
        
        if (!is_array($list)) {
            return [];
        }
        
        // Check if list is an associative array (object-like) vs indexed array
        $isAssociative = !empty($list) && array_keys($list) !== range(0, count($list) - 1);
        
        foreach ($list as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            
            // Build label - support templates with {key} for object keys
            $label = $this->buildLabel($labelField, $item, $index, $isAssociative);
            
            // Build value - support {key} for object keys
            $value = $this->buildValue($valueField, $item, $index, $isAssociative);
            
            // Inject variables into label
            $label = $this->injectVariables($label, $session);
            
            $options[] = [
                'label' => $label,
                'value' => $value,
                'data' => $item, // Store full item data for reference
                'key' => $isAssociative ? $index : null, // Store the key if it's an object
            ];
        }
        
        return $options;
    }
    
    /**
     * Build label from field configuration
     */
    protected function buildLabel(string $labelField, array $item, $index, bool $isAssociative): string
    {
        // Handle template syntax: {key} - {available_balance}
        if (preg_match_all('/\{([^}]+)\}/', $labelField, $matches)) {
            $label = $labelField;
            foreach ($matches[1] as $field) {
                $value = null;
                
                // Special case: {key} or {index} to get the iteration key
                if ($field === 'key' || $field === 'index') {
                    $value = $isAssociative ? (string)$index : (string)($index + 1);
                } else {
                    // Get value from item
                    $value = $this->getNestedValue($item, $field);
                    // Convert to string if not null
                    if ($value !== null) {
                        $value = (string)$value;
                    }
                }
                
                // Replace the placeholder with the value (or empty string if null)
                $label = str_replace('{' . $field . '}', $value ?? '', $label);
            }
            return $label;
        }
        
        // Handle multiple fields separated by +
        if (strpos($labelField, '+') !== false) {
            $fields = explode('+', $labelField);
            $parts = [];
            foreach ($fields as $field) {
                $field = trim($field);
                if ($field === 'key' || $field === 'index') {
                    $parts[] = $isAssociative ? $index : ($index + 1);
                } else {
                    $parts[] = $this->getNestedValue($item, $field) ?? '';
                }
            }
            return implode(' ', $parts);
        }
        
        // Single field
        if ($labelField === 'key' || $labelField === 'index') {
            return $isAssociative ? $index : ($index + 1);
        }
        
        return $this->getNestedValue($item, $labelField) ?? "Option " . ($isAssociative ? $index : ($index + 1));
    }
    
    /**
     * Build value from field configuration
     */
    protected function buildValue(string $valueField, array $item, $index, bool $isAssociative): string
    {
        // If value field is 'key' or 'index', use the iteration key
        if ($valueField === 'key' || $valueField === 'index') {
            return $isAssociative ? (string)$index : (string)($index + 1);
        }
        
        // Get value from item
        $value = $this->getNestedValue($item, $valueField);
        
        // If not found and it's associative, use the key
        if ($value === null && $isAssociative) {
            return (string)$index;
        }
        
        return $value ?? (string)($isAssociative ? $index : ($index + 1));
    }
    
    /**
     * Validate input
     */
    protected function validateInput(string $value, array $rules): array
    {
        foreach ($rules as $rule) {
            $type = $rule['type'] ?? '';
            $message = $rule['message'] ?? 'Invalid input';
            
            $isValid = match($type) {
                'required' => !empty($value),
                'numeric' => is_numeric($value),
                'phone' => preg_match('/^\+?[1-9]\d{1,14}$/', $value),
                'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
                'min_length' => strlen($value) >= ($rule['min'] ?? 0),
                'max_length' => strlen($value) <= ($rule['max'] ?? 999),
                default => true
            };
            
            if (!$isValid) {
                return ['valid' => false, 'message' => $message];
            }
        }
        
        return ['valid' => true, 'message' => ''];
    }
    
    /**
     * Determine next step
     */
    protected function determineNextStep(FlowStep $step, array $result, USSDSession $session): ?string
    {
        // If step has a specific next step, use it
        if ($step->next_step) {
            return $step->next_step;
        }
        
        // Handle conditional next steps based on result
        $data = $step->getData();
        $nextSteps = $data['next_steps'] ?? [];
        
        foreach ($nextSteps as $nextStep) {
            $condition = $nextStep['condition'] ?? null;
            $stepId = $nextStep['step_id'] ?? null;
            
            if ($condition && $stepId) {
                if ($this->evaluateConditions([$condition], $session)) {
                    return $stepId;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Update session data
     */
    protected function updateSessionData(USSDSession $session, array $result): void
    {
        $sessionData = $session->session_data ?? [];
        
        // Store step result
        $sessionData['last_step_result'] = $result;
        $sessionData['last_step_time'] = now()->toISOString();
        
        $session->update(['session_data' => $sessionData]);
    }
}
