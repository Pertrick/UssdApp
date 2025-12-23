<?php

namespace App\Enums;

enum BillingMethod: string
{
    case PREPAID = 'prepaid';
    case POSTPAID = 'postpaid';

    /**
     * Get the display name for the billing method
     */
    public function displayName(): string
    {
        return match($this) {
            self::PREPAID => 'Prepaid',
            self::POSTPAID => 'Pay as You Use',
        };
    }

    /**
     * Get the description for the billing method
     */
    public function description(): string
    {
        return match($this) {
            self::PREPAID => 'Pay upfront and deduct from account balance immediately when sessions are completed',
            self::POSTPAID => 'Use services first, receive invoices periodically, and pay later',
        };
    }

    /**
     * Check if this is prepaid billing
     */
    public function isPrepaid(): bool
    {
        return $this === self::PREPAID;
    }

    /**
     * Check if this is postpaid billing
     */
    public function isPostpaid(): bool
    {
        return $this === self::POSTPAID;
    }

    /**
     * Get all billing methods as an array
     */
    public static function toArray(): array
    {
        return array_map(fn($method) => $method->value, self::cases());
    }

    /**
     * Get all billing methods with display names
     */
    public static function toArrayWithDisplayNames(): array
    {
        return array_map(fn($method) => [
            'value' => $method->value,
            'display_name' => $method->displayName(),
            'description' => $method->description(),
        ], self::cases());
    }
}

