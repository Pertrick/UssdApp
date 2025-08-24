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
    ];

    protected $casts = [
        'is_root' => 'boolean',
        'is_active' => 'boolean',
        'flow_config' => 'array',
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
     * Get the full display text (title + menu text)
     */
    public function getFullDisplayText(): string
    {
        $text = '';
        if ($this->title) {
            $text .= $this->title . "\n";
        }
        $text .= $this->menu_text;
        return $text;
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
            
            // Check if line starts with a number (option)
            if (preg_match('/^\d+[\.\)]?\s*(.+)$/', $line, $matches)) {
                $optionText = trim($matches[1]);
                if (!empty($optionText)) {
                    $options[] = [
                        'option_text' => $optionText,
                        'option_value' => ($optionIndex + 1),
                        'action_type' => 'navigate',
                        'action_data' => null,
                        'next_flow_id' => null,
                        'sort_order' => $optionIndex + 1,
                        'is_active' => true
                    ];
                    $optionIndex++;
                }
            }
        }
        
        // Delete existing options and create new ones
        $this->options()->delete();
        if (!empty($options)) {
            $this->options()->createMany($options);
        }
    }
}
