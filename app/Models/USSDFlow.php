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
     * Replace template variables in text with session data
     */
    private function replaceTemplateVariables(string $text, USSDSession $session): string
    {
        $sessionData = $session->session_data ?? [];
        
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($session, $sessionData) {
            $path = trim($matches[1]);
            
            // Handle session variables
            if (str_starts_with($path, 'session.')) {
                $field = substr($path, 8); // Remove 'session.' prefix
                return $sessionData[$field] ?? '';
            }
            
            // Handle direct session data access
            return $sessionData[$path] ?? '';
        }, $text);
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
