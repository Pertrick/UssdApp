<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class USSDFlow extends Model
{
    use HasFactory;
    protected $table = 'ussd_flows';

    protected $fillable = [
        'ussd_id',
        'name',
        'title',
        'description',
        'menu_text',
        'is_root',
        'parent_flow_id',
        'sort_order',
        'section_name',
        'flow_config',
        'is_active',
        'flow_type',
        'dynamic_config',
    ];

    protected $casts = [
        'is_root' => 'boolean',
        'is_active' => 'boolean',
        'flow_config' => 'array',
        'dynamic_config' => 'array',
    ];

    public function ussd(): BelongsTo
    {
        return $this->belongsTo(USSD::class);
    }

    public function parentFlow(): BelongsTo
    {
        return $this->belongsTo(USSDFlow::class, 'parent_flow_id');
    }

    public function childFlows(): HasMany
    {
        return $this->hasMany(USSDFlow::class, 'parent_flow_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(USSDFlowOption::class, 'flow_id');
    }

    /**
     * Generate menu text from USSDFlowOption records
     * Respects user's order and starts numbering from 0
     */
    public function generateMenuTextFromOptions(): string
    {
        $options = $this->options()->orderBy('sort_order')->get();
        
        if ($options->isEmpty()) {
            return 'No options available';
        }
        
        $menuText = '';
        foreach ($options as $index => $option) {
            if ($index > 0) $menuText .= "\n";
            
            // Use option_value if set, otherwise use index (starting from 0)
            $displayNumber = $option->option_value !== null && $option->option_value !== ''
                ? $option->option_value
                : $index;
            
            $menuText .= $displayNumber . ". " . $option->option_text;
        }
        
        return $menuText;
    }

    /**
     * Get the full display text (menu text only, title is shown separately)
     */
    public function getFullDisplayText(?USSDSession $session = null): string
    {
        $text = $this->menu_text;
        
        // Replace template variables if session is provided
        if ($session) {
            $text = $this->replaceTemplateVariables($text, $session);
        }
        
        return $text;
    }
    
    /**
     * Get the processed title with template variables replaced
     */
    public function getProcessedTitle(?USSDSession $session = null): string
    {
        $title = $this->title ?? '';
        
        // Replace template variables if session is provided
        if ($session && !empty($title)) {
            // Refresh session to ensure we have the latest session_data
            $session->refresh();
            $title = $this->replaceTemplateVariables($title, $session);
        }
        
        return $title;
    }
    
    /**
     * Replace template variables in text with session data
     */
    private function replaceTemplateVariables(string $text, USSDSession $session): string
    {
        // CRITICAL: Refresh session to ensure we have the latest session_data
        // This is especially important after API calls or input collection that updates session_data
        $session->refresh();
        
        // Force reload session_data from database to ensure we have the absolute latest data
        // This handles cases where session_data was just updated in the same request
        $freshSessionData = \DB::table('ussd_sessions')
            ->where('id', $session->id)
            ->value('session_data');
        
        if ($freshSessionData) {
            $decodedData = is_string($freshSessionData) ? json_decode($freshSessionData, true) : $freshSessionData;
            if ($decodedData) {
                $session->session_data = $decodedData;
            }
        }
        
        $sessionData = $session->session_data ?? [];
        
        $isDynamicFlow = $this->flow_type === 'dynamic';
        
        $staticContext = [];
        if (!$isDynamicFlow) {
            $staticContext = array_filter($sessionData, function($value) {
                return is_scalar($value) || is_null($value);
            }, ARRAY_FILTER_USE_KEY);
            

            if (isset($sessionData['selected_item_data']) && is_array($sessionData['selected_item_data'])) {
                foreach ($sessionData['selected_item_data'] as $key => $value) {
                    if ((is_scalar($value) || is_null($value)) && !isset($staticContext[$key])) {
                        $staticContext[$key] = $value;
                    }
                }
            }
        }
        
        $context = [
            'session' => [
                'id' => $session->id,
                'session_id' => $session->session_id,
                'phone_number' => $session->phone_number,
                'step_count' => $session->step_count,
                'data' => $sessionData,
            ],
            ...$staticContext,
            'selected_item_data' => $sessionData['selected_item_data'] ?? [],
        ];
        
        $sanitizationService = app(\App\Services\SanitizationService::class);
        
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($context, $sessionData, $sanitizationService) {
            $path = trim($matches[1]);
            $value = '';
            
            if (str_contains($path, '.')) {
                $value = $this->getNestedValueFromContext($path, $context, $sessionData);
            } elseif (isset($context[$path])) {
                $value = $context[$path];
            } else {
                $value = $sessionData[$path] ?? '';
            }
            
            $value = is_scalar($value) ? (string) $value : '';
            return $sanitizationService->sanitizeOutput($value, 500);
        }, $text);
    }
    
    /**
     * Get nested value from context using dot notation
     */
    private function getNestedValueFromContext(string $path, array $context, array $sessionData)
    {
        $pathParts = explode('.', $path, 2);
        $topLevelKey = $pathParts[0];
        $remainingPath = $pathParts[1] ?? '';
        
        // Handle session.* paths specially
        if ($topLevelKey === 'session') {

            if ($remainingPath === 'phone_number') {
                if (isset($sessionData['recipient_type']) && $sessionData['recipient_type'] === 'self') {
                    return $context['session']['phone_number'] ?? '';
                }
                
                if (isset($sessionData['input_phone']) && !empty($sessionData['input_phone'])) {
                    return $sessionData['input_phone'];
                }

                if (isset($sessionData['collected_inputs']['input_phone']) && !empty($sessionData['collected_inputs']['input_phone'])) {
                    return $sessionData['collected_inputs']['input_phone'];
                }
                
                $phoneFields = ['phone_number', 'phone', 'number', 'recipient_phone'];
                foreach ($phoneFields as $field) {
                    if (isset($sessionData[$field]) && !empty($sessionData[$field])) {
                        return $sessionData[$field];
                    }
                }
                
                return $context['session']['phone_number'] ?? '';
            }
            
            // Handle {{session.data}} - access the 'data' field from session_data
            // IMPORTANT: $context['session']['data'] is the whole session_data array
            // We need to access $sessionData['data'] directly
            if ($remainingPath === 'data') {
                $value = $sessionData['data'] ?? null;
                
                if ($value !== null && $value !== '') {
                    return is_scalar($value) ? (string) $value : '';
                }
                
                // If not found in sessionData, try context as fallback
                $contextData = $context['session']['data'] ?? [];
                if (is_array($contextData) && isset($contextData['data'])) {
                    $value = $contextData['data'];
                    return is_scalar($value) ? (string) $value : '';
                }
                
                return '';
            }
            
            // Handle {{session.data.field}} - access nested fields within the data object
            if (str_starts_with($remainingPath, 'data.')) {
                $dataPath = substr($remainingPath, 5); // e.g., "input_phone" from "data.input_phone"
                
                // Special handling for phone-related fields when use_registered_phone is set
                if (in_array($dataPath, ['input_phone', 'phone', 'phone_number', 'recipient_phone']) && 
                    isset($sessionData['recipient_type']) && $sessionData['recipient_type'] === 'self') {
                    return $context['session']['phone_number'] ?? '';
                }
                
                // Try multiple locations where the data might be stored
                // 1. Direct sessionData key (top level) - most common for input collection
                $value = data_get($sessionData, $dataPath, null);
                if ($value !== null && $value !== '') {
                    return is_scalar($value) ? (string) $value : '';
                }
                
                // 2. Check collected_input
                $value = data_get($sessionData, "collected_input.{$dataPath}", null);
                if ($value !== null && $value !== '') {
                    return is_scalar($value) ? (string) $value : '';
                }
                
                // 3. Check selected_item_data
                $value = data_get($sessionData, "selected_item_data.{$dataPath}", null);
                if ($value !== null && $value !== '') {
                    return is_scalar($value) ? (string) $value : '';
                }
                
                // 4. Check collected_inputs (plural, for backward compatibility)
                $value = data_get($sessionData, "collected_inputs.{$dataPath}", null);
                if ($value !== null && $value !== '') {
                    return is_scalar($value) ? (string) $value : '';
                }
                
                // 5. Fallback to context session.data
                $contextData = $context['session']['data'] ?? [];
                $value = data_get($contextData, $dataPath, null);
                if ($value !== null && $value !== '') {
                    return is_scalar($value) ? (string) $value : '';
                }
                
                return '';
            }
            
            // For other session.* paths, try context first, then sessionData
            $value = data_get($context['session'], $remainingPath, null);
            if ($value !== null) {
                return is_scalar($value) ? (string) $value : '';
            }
            return data_get($sessionData, $remainingPath, '');
        }
        
        $value = data_get($sessionData, $path, null);
        if ($value !== null) {
            return is_scalar($value) ? (string) $value : '';
        }
        
        return '';
    }

    /**
     * Update menu_text from options and save
     */
    public function updateMenuTextFromOptions(): void
    {
        $this->menu_text = $this->generateMenuTextFromOptions();
        $this->save();
    }

    /**
     * Parse menu_text and create/update USSDFlowOption records
     * Respects user's numbering (starting from 0) and order
     */
    public function parseMenuTextToOptions(): void
    {
        $lines = explode("\n", $this->menu_text);
        $options = [];
        $optionIndex = 0;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Try to extract the number from the beginning of the line
            $extractedNumber = null;
            if (preg_match('/^(\d+)[\.\)]?\s*(.+)$/', $line, $matches)) {
                $extractedNumber = (int) $matches[1];
                $optionText = trim($matches[2]);
            } else {
                // No number found, remove any numbering patterns and extract just the text
                $optionText = preg_replace('/^\d+[\.\)]?\s*/', '', $line);
                $optionText = trim($optionText);
            }
            
            if (!empty($optionText)) {
                // Use extracted number if found, otherwise use index (starting from 0)
                $optionValue = $extractedNumber !== null ? $extractedNumber : $optionIndex;
                
                $options[] = [
                    'option_text' => $optionText,
                    'option_value' => $optionValue,
                    'action_type' => 'navigate',
                    'action_data' => null,
                    'next_flow_id' => null,
                    'sort_order' => $optionIndex + 1, // sort_order still starts from 1 for ordering
                    'is_active' => true
                ];
                $optionIndex++;
            }
        }
        
        // Delete existing options and create new ones
        $this->options()->delete();
        if (!empty($options)) {
            $this->options()->createMany($options);
        }
    }
}
