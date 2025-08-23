<?php

namespace App\Enums;

enum BusinessType: string
{
    case SOLE_PROPRIETORSHIP = 'sole_proprietorship';
    case PARTNERSHIP = 'partnership';
    case LIMITED_LIABILITY = 'limited_liability';

    /**
     * Get the display name for the business type
     */
    public function displayName(): string
    {
        return match($this) {
            self::SOLE_PROPRIETORSHIP => 'Sole Proprietorship',
            self::PARTNERSHIP => 'Partnership',
            self::LIMITED_LIABILITY => 'Limited Liability Company',
        };
    }

    /**
     * Get the description for the business type
     */
    public function description(): string
    {
        return match($this) {
            self::SOLE_PROPRIETORSHIP => 'Business owned and operated by a single individual',
            self::PARTNERSHIP => 'Business owned by two or more partners',
            self::LIMITED_LIABILITY => 'Business with limited liability protection for owners',
        };
    }

    /**
     * Get the legal requirements for this business type
     */
    public function legalRequirements(): array
    {
        return match($this) {
            self::SOLE_PROPRIETORSHIP => [
                'CAC Registration Certificate',
                'Business Name Registration',
                'Tax Identification Number',
            ],
            self::PARTNERSHIP => [
                'Partnership Agreement',
                'CAC Registration Certificate',
                'Partners\' Identification',
                'Tax Identification Number',
            ],
            self::LIMITED_LIABILITY => [
                'Certificate of Incorporation',
                'Memorandum and Articles of Association',
                'Directors\' Information',
                'Tax Identification Number',
            ],
        };
    }

    /**
     * Check if this business type requires multiple owners
     */
    public function requiresMultipleOwners(): bool
    {
        return in_array($this, [
            self::PARTNERSHIP,
            self::LIMITED_LIABILITY,
        ]);
    }

    /**
     * Check if this business type has limited liability
     */
    public function hasLimitedLiability(): bool
    {
        return $this === self::LIMITED_LIABILITY;
    }

    /**
     * Get all business types as an array
     */
    public static function toArray(): array
    {
        return array_map(fn($type) => $type->value, self::cases());
    }

    /**
     * Get all business types with display names
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
