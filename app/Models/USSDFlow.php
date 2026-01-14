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
            $menuText .= ($index + 1) . ". " . $option->option_text;
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
            $title = $this->replaceTemplateVariables($title, $session);
        }
        
        return $title;
    }
    
    /**
     * Replace template variables in text with session data
     */
    private function replaceTemplateVariables(string $text, USSDSession $session): string
    {
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
            return $sanitizationService->sanitizeOutput($value, 500); // Limit individual variable length
        }, $text);
    }
    
    /**
     * Get nested value from context using dot notation
     */
    private function getNestedValueFromContext(string $path, array $context, array $sessionData)
    {
        if (str_starts_with($path, 'session.')) {
            $remainingPath = substr($path, 8);

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
            
            if (str_starts_with($remainingPath, 'data.')) {
                $dataPath = substr($remainingPath, 5); // Remove 'data.' prefix
                return data_get($sessionData, $dataPath, '');
            }
            
            // Handle other session properties
            return data_get($context['session'], $remainingPath, '');
        }
        
        // Handle direct session data access with dot notation (e.g., selected_item_data.coded)
        return data_get($sessionData, $path, '');
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
     */
    public function parseMenuTextToOptions(): void
    {
        $lines = explode("\n", $this->menu_text);
        $options = [];
        $optionIndex = 0;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Remove any existing numbering patterns and extract just the text
            $optionText = preg_replace('/^\d+[\.\)]?\s*/', '', $line);
            $optionText = trim($optionText);
            
            if (!empty($optionText)) {
                $options[] = [
                    'option_text' => $optionText,
                    'option_value' => ($optionIndex + 1), // Always use sequential numbering starting from 1
                    'action_type' => 'navigate',
                    'action_data' => null,
                    'next_flow_id' => null,
                    'sort_order' => $optionIndex + 1,
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
