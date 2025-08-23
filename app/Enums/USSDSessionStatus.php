<?php

namespace App\Enums;

enum USSDSessionStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';
    case TERMINATED = 'terminated';
    case ERROR = 'error';

    /**
     * Get the display name for the session status
     */
    public function displayName(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::EXPIRED => 'Expired',
            self::TERMINATED => 'Terminated',
            self::ERROR => 'Error',
        };
    }

    /**
     * Get the description for the session status
     */
    public function description(): string
    {
        return match($this) {
            self::ACTIVE => 'Session is currently active and accepting input',
            self::COMPLETED => 'Session completed successfully',
            self::EXPIRED => 'Session expired due to inactivity',
            self::TERMINATED => 'Session was manually terminated',
            self::ERROR => 'Session ended due to an error',
        };
    }

    /**
     * Get the color class for UI display
     */
    public function colorClass(): string
    {
        return match($this) {
            self::ACTIVE => 'bg-green-100 text-green-800',
            self::COMPLETED => 'bg-blue-100 text-blue-800',
            self::EXPIRED => 'bg-yellow-100 text-yellow-800',
            self::TERMINATED => 'bg-red-100 text-red-800',
            self::ERROR => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Check if session is active
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if session is ended
     */
    public function isEnded(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::EXPIRED,
            self::TERMINATED,
            self::ERROR,
        ]);
    }

    /**
     * Check if session ended successfully
     */
    public function endedSuccessfully(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if session ended with error
     */
    public function endedWithError(): bool
    {
        return in_array($this, [
            self::EXPIRED,
            self::TERMINATED,
            self::ERROR,
        ]);
    }

    /**
     * Get the timeout duration for this status
     */
    public function timeoutDuration(): ?int
    {
        return match($this) {
            self::ACTIVE => 30, // 30 minutes for active sessions
            self::COMPLETED => null, // No timeout for completed
            self::EXPIRED => null, // Already expired
            self::TERMINATED => null, // Already terminated
            self::ERROR => null, // Already in error state
        };
    }

    /**
     * Get the next possible statuses
     */
    public function nextPossibleStatuses(): array
    {
        return match($this) {
            self::ACTIVE => [
                self::COMPLETED,
                self::EXPIRED,
                self::TERMINATED,
                self::ERROR,
            ],
            self::COMPLETED => [], // Final status
            self::EXPIRED => [], // Final status
            self::TERMINATED => [], // Final status
            self::ERROR => [], // Final status
        };
    }

    /**
     * Get all statuses as an array
     */
    public static function toArray(): array
    {
        return array_map(fn($status) => $status->value, self::cases());
    }

    /**
     * Get all statuses with display names
     */
    public static function toArrayWithDisplayNames(): array
    {
        return array_map(fn($status) => [
            'value' => $status->value,
            'display_name' => $status->displayName(),
            'description' => $status->description(),
            'color_class' => $status->colorClass(),
        ], self::cases());
    }

    /**
     * Get active statuses only
     */
    public static function activeStatuses(): array
    {
        return array_filter(self::cases(), fn($status) => $status->isActive());
    }

    /**
     * Get ended statuses only
     */
    public static function endedStatuses(): array
    {
        return array_filter(self::cases(), fn($status) => $status->isEnded());
    }
}
