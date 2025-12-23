<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowStep extends Model
{
    use HasFactory;
    
    protected $table = 'flow_steps';
    
    protected $fillable = [
        'ussd_id',
        'step_id',
        'type',
        'data',
        'next_step',
        'conditions',
        'sort_order',
        'is_active',
    ];
    
    protected $casts = [
        'data' => 'array',
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];
    
    // Relationships
    public function ussd(): BelongsTo
    {
        return $this->belongsTo(USSD::class);
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
    
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
    
    // Helper methods
    public function getData(string $key = null)
    {
        if ($key === null) {
            return $this->data ?? [];
        }
        
        return $this->data[$key] ?? null;
    }
    
    public function setData(string $key, $value): void
    {
        $data = $this->data ?? [];
        $data[$key] = $value;
        $this->data = $data;
    }
    
    public function getConditions(): array
    {
        return $this->conditions ?? [];
    }
    
    public function hasConditions(): bool
    {
        return !empty($this->conditions);
    }
    
    // Step type constants
    public const TYPE_MENU = 'menu';
    public const TYPE_API_CALL = 'api_call';
    public const TYPE_DYNAMIC_MENU = 'dynamic_menu';
    public const TYPE_INPUT = 'input';
    public const TYPE_CONDITION = 'condition';
    public const TYPE_MESSAGE = 'message';
    
    public static function getValidTypes(): array
    {
        return [
            self::TYPE_MENU,
            self::TYPE_API_CALL,
            self::TYPE_DYNAMIC_MENU,
            self::TYPE_INPUT,
            self::TYPE_CONDITION,
            self::TYPE_MESSAGE,
        ];
    }
}