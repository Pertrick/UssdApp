<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case MODERATOR = 'moderator';

    /**
     * Get the display name for the role
     */
    public function displayName(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::USER => 'User',
            self::MODERATOR => 'Moderator',
        };
    }

    /**
     * Get the description for the role
     */
    public function description(): string
    {
        return match($this) {
            self::ADMIN => 'Full system administrator with all permissions',
            self::USER => 'Regular user with basic permissions',
            self::MODERATOR => 'Moderator with limited admin permissions',
        };
    }

    /**
     * Get all roles as an array
     */
    public static function toArray(): array
    {
        return array_map(fn($role) => $role->value, self::cases());
    }

    /**
     * Get all roles with display names
     */
    public static function toArrayWithDisplayNames(): array
    {
        return array_map(fn($role) => [
            'value' => $role->value,
            'display_name' => $role->displayName(),
            'description' => $role->description(),
        ], self::cases());
    }

    /**
     * Check if role is an admin role
     */
    public function isAdmin(): bool
    {
        return in_array($this, [self::ADMIN, self::MODERATOR]);
    }

    /**
     * Check if role is a regular user role
     */
    public function isUser(): bool
    {
        return $this === self::USER;
    }

    /**
     * Get admin roles only
     */
    public static function adminRoles(): array
    {
        return array_filter(self::cases(), fn($role) => $role->isAdmin());
    }

    /**
     * Get user roles only
     */
    public static function userRoles(): array
    {
        return array_filter(self::cases(), fn($role) => $role->isUser());
    }
}
