<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class ExternalAPIConfiguration extends Model
{
    use HasFactory;
    protected $table = 'external_api_configurations';

    protected $fillable = [
        'user_id',
        'ussd_id',
        'name',
        'description',
        'category',
        'provider_name',
        'endpoint_url',
        'method',
        'timeout',
        'retry_attempts',
        'auth_type',
        'auth_config',
        'headers',
        'request_mapping',
        'request_template',
        'response_mapping',
        'data_path',
        'error_path',
        'success_criteria',
        'error_handling',
        'is_active',
        'is_verified',
        'last_tested_at',
        'test_status',
        'total_calls',
        'successful_calls',
        'failed_calls',
        'average_response_time',
        'is_marketplace_template',
        'marketplace_category',
        'marketplace_metadata',
        'environment'
    ];

    protected $casts = [
        'auth_config' => 'array',
        'headers' => 'array',
        'request_mapping' => 'array',
        'request_template' => 'array',
        'response_mapping' => 'array',
        'success_criteria' => 'array',
        'error_handling' => 'array',
        'marketplace_metadata' => 'array',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'last_tested_at' => 'datetime',
        'is_marketplace_template' => 'boolean',
        'average_response_time' => 'decimal:3',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ussd(): BelongsTo
    {
        return $this->belongsTo(USSD::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMarketplace($query)
    {
        return $query->where('category', 'marketplace');
    }

    public function scopeCustom($query)
    {
        return $query->where('category', 'custom');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('marketplace_category', $category);
    }

    // Helper Methods
    public function isMarketplace(): bool
    {
        return $this->category === 'marketplace';
    }

    public function isCustom(): bool
    {
        return $this->category === 'custom';
    }

    public function getSuccessRate(): float
    {
        if ($this->total_calls === 0) {
            return 0;
        }
        return round(($this->successful_calls / $this->total_calls) * 100, 2);
    }

    public function updateCallStats(bool $success, float $responseTime = null): void
    {
        $this->increment('total_calls');
        
        if ($success) {
            $this->increment('successful_calls');
        } else {
            $this->increment('failed_calls');
        }

        if ($responseTime) {
            $this->updateAverageResponseTime($responseTime);
        }

        $this->save();
    }

    private function updateAverageResponseTime(float $newResponseTime): void
    {
        if ($this->average_response_time === null) {
            $this->average_response_time = $newResponseTime;
        } else {
            $totalCalls = $this->total_calls;
            $currentAverage = $this->average_response_time;
            $newAverage = (($currentAverage * ($totalCalls - 1)) + $newResponseTime) / $totalCalls;
            $this->average_response_time = round($newAverage, 3);
        }
    }

    public function getAuthConfig(): array
    {
        if (is_string($this->auth_config)) {
            return json_decode($this->auth_config, true) ?? [];
        }
        return $this->auth_config ?? [];
    }

    public function setAuthConfig(array $config): void
    {
        $this->auth_config = $config;
    }

    // Simplified auth_config handling without encryption
    public function setAuthConfigAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['auth_config'] = json_encode($value);
        } else {
            $this->attributes['auth_config'] = $value;
        }
    }

    public function getAuthConfigAttribute($value)
    {
        if ($value) {
            if (is_string($value)) {
                return json_decode($value, true) ?? [];
            }
            return $value;
        }
        return [];
    }

    public function getRequestMapping(): array
    {
        if (is_string($this->request_mapping)) {
            return json_decode($this->request_mapping, true) ?? [];
        }
        return $this->request_mapping ?? [];
    }

    public function getResponseMapping(): array
    {
        if (is_string($this->response_mapping)) {
            return json_decode($this->response_mapping, true) ?? [];
        }
        return $this->response_mapping ?? [];
    }

    public function getSuccessCriteria(): array
    {
        if (is_string($this->success_criteria)) {
            return json_decode($this->success_criteria, true) ?? [];
        }
        return $this->success_criteria ?? [];
    }

    public function getErrorHandling(): array
    {
        if (is_string($this->error_handling)) {
            return json_decode($this->error_handling, true) ?? [];
        }
        return $this->error_handling ?? [];
    }

    public function getHeaders(): array
    {
        if (is_string($this->headers)) {
            $headers = json_decode($this->headers, true) ?? [];
        } else {
            $headers = $this->headers ?? [];
        }
        
        // Normalize headers to always return [{key, value}] format for consistency
        if (empty($headers)) {
            return [];
        }
        
        // Check if already in [{key, value}] format
        if (isset($headers[0]) && is_array($headers[0]) && isset($headers[0]['key'])) {
            return $headers;
        }
        
        // Convert from {key: value} object format to [{key, value}] array format
        $normalized = [];
        foreach ($headers as $key => $value) {
            if (is_string($key)) {
                $normalized[] = ['key' => $key, 'value' => $value];
            } elseif (is_array($value) && isset($value['key']) && isset($value['value'])) {
                // Already in correct format
                $normalized[] = $value;
            }
        }
        
        return $normalized;
    }

    public function getRequestTemplate(): array
    {
        if (is_string($this->request_template)) {
            return json_decode($this->request_template, true) ?? [];
        }
        return $this->request_template ?? [];
    }

    // Marketplace specific methods
    public function getMarketplaceMetadata(string $key = null)
    {
        if ($key) {
            return $this->marketplace_metadata[$key] ?? null;
        }
        return $this->marketplace_metadata;
    }

    public function isTemplate(): bool
    {
        return $this->is_marketplace_template;
    }

    // Validation methods
    public function isValid(): bool
    {
        return $this->is_active && 
               !empty($this->endpoint_url) && 
               !empty($this->method) &&
               $this->test_status === 'success';
    }

    public function needsTesting(): bool
    {
        return $this->test_status === 'pending' || 
               $this->last_tested_at === null ||
               $this->last_tested_at->diffInDays(now()) > 7; // Test weekly
    }
}
