<?php

namespace App\Enums;

enum EnvironmentType: string
{
    case TESTING = 'testing';
    case PRODUCTION = 'production';

    /**
     * Get all environment types as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get environment type label
     */
    public function label(): string
    {
        return match($this) {
            self::TESTING => 'Testing',
            self::PRODUCTION => 'Production',
        };
    }

    /**
     * Get environment type description
     */
    public function description(): string
    {
        return match($this) {
            self::TESTING => 'Real API calls in test/sandbox mode for integration testing',
            self::PRODUCTION => 'Real API calls in live mode for production use',
        };
    }

    /**
     * Get environment type color for UI
     */
    public function color(): string
    {
        return match($this) {
            self::TESTING => 'yellow',
            self::PRODUCTION => 'green',
        };
    }

    /**
     * Check if environment allows real API calls
     * 
     */
    public function allowsRealApiCalls(): bool
    {
        return true;
    }

    /**
     * Check if environment is for testing
     */
    public function isTesting(): bool
    {
        return $this === self::TESTING;
    }

    /**
     * Check if environment is for production
     */
    public function isProduction(): bool
    {
        return $this === self::PRODUCTION;
    }
}
