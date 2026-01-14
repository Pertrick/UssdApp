<?php

namespace App\Models;

use App\Enums\EnvironmentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Encrypted;

class GatewayConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'gateway_provider',
        'api_key',
        'username',
        'environment',
        'is_default',
        'is_active',
        'description',
    ];

    protected $casts = [
        'api_key' => Encrypted::class,
        'username' => Encrypted::class,
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the default gateway configuration for a provider
     */
    public static function getDefault(string $provider, ?string $environment = null): ?self
    {
        $environment = $environment ?? EnvironmentType::PRODUCTION->value;
        return static::where('gateway_provider', $provider)
            ->where('environment', $environment)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Get active gateway configurations for a provider
     */
    public static function getActive(string $provider): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('gateway_provider', $provider)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get credentials as array (for backward compatibility)
     */
    public function getCredentialsAttribute(): array
    {
        return [
            'api_key' => $this->api_key,
            'username' => $this->username,
        ];
    }
}
