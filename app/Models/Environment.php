<?php

namespace App\Models;

use App\Enums\EnvironmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Environment extends Model
{
    protected $fillable = [
        'name',
        'label',
        'description',
        'color',
        'allows_real_api_calls',
        'is_default',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'allows_real_api_calls' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get the USSDs that belong to this environment
     */
    public function ussds(): HasMany
    {
        return $this->hasMany(USSD::class);
    }

    /**
     * Get the USSD sessions that belong to this environment
     */
    public function ussdSessions(): HasMany
    {
        return $this->hasMany(USSDSession::class);
    }

    /**
     * Get environment type enum
     */
    public function getTypeAttribute(): EnvironmentType
    {
        return EnvironmentType::from($this->name);
    }

    /**
     * 
     * NOTE: Now always returns true - all environments allow real API calls
     */
    public function allowsRealApiCalls(): bool
    {
        // Always return true - all environments now allow real API calls
        return true;
    }

    /**
     * Check if this environment is for testing
     */
    public function isTesting(): bool
    {
        return $this->name === EnvironmentType::TESTING->value;
    }

    /**
     * Check if this environment is for production
     */
    public function isProduction(): bool
    {
        return $this->name === EnvironmentType::PRODUCTION->value;
    }

    /**
     * Get the default environment
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * Get environment by name
     */
    public static function getByName(string $name): ?self
    {
        return static::where('name', $name)->where('is_active', true)->first();
    }

    /**
     * Get all active environments
     */
    public static function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)->orderBy('name')->get();
    }
}
