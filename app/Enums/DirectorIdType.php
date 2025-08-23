<?php

namespace App\Enums;

enum DirectorIdType: string
{
    case NATIONAL_ID = 'national_id';
    case DRIVERS_LICENSE = 'drivers_license';
    case INTERNATIONAL_PASSPORT = 'international_passport';

    /**
     * Get the display name for the ID type
     */
    public function displayName(): string
    {
        return match($this) {
            self::NATIONAL_ID => 'National ID',
            self::DRIVERS_LICENSE => 'Driver\'s License',
            self::INTERNATIONAL_PASSPORT => 'International Passport',
        };
    }

    /**
     * Get the description for the ID type
     */
    public function description(): string
    {
        return match($this) {
            self::NATIONAL_ID => 'Government-issued national identification card',
            self::DRIVERS_LICENSE => 'Valid driver\'s license issued by traffic authority',
            self::INTERNATIONAL_PASSPORT => 'Valid international passport',
        };
    }

    /**
     * Get the validation rules for this ID type
     */
    public function validationRules(): array
    {
        return match($this) {
            self::NATIONAL_ID => [
                'min_length' => 10,
                'max_length' => 12,
                'pattern' => '/^[0-9]+$/',
                'description' => 'Numeric national ID number',
            ],
            self::DRIVERS_LICENSE => [
                'min_length' => 8,
                'max_length' => 15,
                'pattern' => '/^[A-Z0-9]+$/',
                'description' => 'Alphanumeric driver\'s license number',
            ],
            self::INTERNATIONAL_PASSPORT => [
                'min_length' => 8,
                'max_length' => 9,
                'pattern' => '/^[A-Z0-9]+$/',
                'description' => 'Alphanumeric passport number',
            ],
        };
    }

    /**
     * Get the document requirements for this ID type
     */
    public function documentRequirements(): array
    {
        return match($this) {
            self::NATIONAL_ID => [
                'Clear photo of the ID card',
                'Both front and back sides',
                'Valid and not expired',
            ],
            self::DRIVERS_LICENSE => [
                'Clear photo of the license',
                'Both front and back sides',
                'Valid and not expired',
            ],
            self::INTERNATIONAL_PASSPORT => [
                'Clear photo of the passport',
                'Bio-data page',
                'Valid and not expired',
            ],
        };
    }

    /**
     * Check if this ID type is government-issued
     */
    public function isGovernmentIssued(): bool
    {
        return true; // All our ID types are government-issued
    }

    /**
     * Check if this ID type is internationally recognized
     */
    public function isInternationallyRecognized(): bool
    {
        return $this === self::INTERNATIONAL_PASSPORT;
    }

    /**
     * Get all ID types as an array
     */
    public static function toArray(): array
    {
        return array_map(fn($type) => $type->value, self::cases());
    }

    /**
     * Get all ID types with display names
     */
    public static function toArrayWithDisplayNames(): array
    {
        return array_map(fn($type) => [
            'value' => $type->value,
            'display_name' => $type->displayName(),
            'description' => $type->description(),
        ], self::cases());
    }
}
