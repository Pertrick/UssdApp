<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowConfig extends Model
{
    use HasFactory;
    
    protected $table = 'flow_configs';
    
    protected $fillable = [
        'ussd_id',
        'key',
        'value',
        'description',
        'is_active',
    ];
    
    protected $casts = [
        'value' => 'array',
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
    
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }
    
    // Helper methods
    public function getValue()
    {
        return $this->value;
    }
    
    public function setValue($value): void
    {
        $this->value = $value;
    }
    
    public function getValueAsString(): string
    {
        if (is_array($this->value)) {
            return json_encode($this->value);
        }
        
        return (string) $this->value;
    }
    
    public function getValueAsArray(): array
    {
        if (is_array($this->value)) {
            return $this->value;
        }
        
        if (is_string($this->value)) {
            $decoded = json_decode($this->value, true);
            return is_array($decoded) ? $decoded : [$this->value];
        }
        
        return [$this->value];
    }
}