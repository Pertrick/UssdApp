<?php

namespace App\Enums;

enum BusinessRegistrationStatus: string
{
    case EMAIL_VERIFICATION_PENDING = 'email_verification_pending';
    case CAC_INFO_PENDING = 'cac_info_pending';
    case DIRECTOR_INFO_PENDING = 'director_info_pending';
    case COMPLETED_UNVERIFIED = 'completed_unverified';
    case UNDER_REVIEW = 'under_review';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';

    /**
     * Get the display name for the status
     */
    public function displayName(): string
    {
        return match($this) {
            self::EMAIL_VERIFICATION_PENDING => 'Email Verification Pending',
            self::CAC_INFO_PENDING => 'CAC Information Pending',
            self::DIRECTOR_INFO_PENDING => 'Director Information Pending',
            self::COMPLETED_UNVERIFIED => 'Completed (Unverified)',
            self::UNDER_REVIEW => 'Under Review',
            self::VERIFIED => 'Verified',
            self::REJECTED => 'Rejected',
            self::SUSPENDED => 'Suspended',
        };
    }

    /**
     * Get the description for the status
     */
    public function description(): string
    {
        return match($this) {
            self::EMAIL_VERIFICATION_PENDING => 'Business registration started, email verification required',
            self::CAC_INFO_PENDING => 'Email verified, CAC information submission required',
            self::DIRECTOR_INFO_PENDING => 'CAC info submitted, director information required',
            self::COMPLETED_UNVERIFIED => 'Registration complete, awaiting admin verification',
            self::UNDER_REVIEW => 'Business is currently being reviewed by admin',
            self::VERIFIED => 'Business verified by admin, fully operational',
            self::REJECTED => 'Business verification rejected by admin',
            self::SUSPENDED => 'Business temporarily suspended by admin',
        };
    }

    /**
     * Get the color class for UI display
     */
    public function colorClass(): string
    {
        return match($this) {
            self::EMAIL_VERIFICATION_PENDING => 'bg-yellow-100 text-yellow-800',
            self::CAC_INFO_PENDING => 'bg-orange-100 text-orange-800',
            self::DIRECTOR_INFO_PENDING => 'bg-blue-100 text-blue-800',
            self::COMPLETED_UNVERIFIED => 'bg-purple-100 text-purple-800',
            self::UNDER_REVIEW => 'bg-indigo-100 text-indigo-800',
            self::VERIFIED => 'bg-green-100 text-green-800',
            self::REJECTED => 'bg-red-100 text-red-800',
            self::SUSPENDED => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Check if status is pending
     */
    public function isPending(): bool
    {
        return in_array($this, [
            self::EMAIL_VERIFICATION_PENDING,
            self::CAC_INFO_PENDING,
            self::DIRECTOR_INFO_PENDING,
        ]);
    }

    /**
     * Check if status is awaiting admin review
     */
    public function isAwaitingReview(): bool
    {
        return in_array($this, [
            self::COMPLETED_UNVERIFIED,
            self::UNDER_REVIEW,
        ]);
    }

    /**
     * Check if status is completed
     */
    public function isCompleted(): bool
    {
        return in_array($this, [
            self::COMPLETED_UNVERIFIED,
            self::VERIFIED,
        ]);
    }

    /**
     * Check if status is final (no further action needed)
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::VERIFIED,
            self::REJECTED,
            self::SUSPENDED,
        ]);
    }

    /**
     * Check if status is verified
     */
    public function isVerified(): bool
    {
        return $this === self::VERIFIED;
    }

    /**
     * Check if status is rejected
     */
    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    /**
     * Get next status in the registration flow
     */
    public function nextStatus(): ?self
    {
        return match($this) {
            self::EMAIL_VERIFICATION_PENDING => self::CAC_INFO_PENDING,
            self::CAC_INFO_PENDING => self::DIRECTOR_INFO_PENDING,
            self::DIRECTOR_INFO_PENDING => self::COMPLETED_UNVERIFIED,
            self::COMPLETED_UNVERIFIED => null, // Admin action required
            self::UNDER_REVIEW => null, // Admin action required
            self::VERIFIED => null, // Final status
            self::REJECTED => null, // Final status
            self::SUSPENDED => null, // Final status
        };
    }

    /**
     * Get admin action statuses (statuses that require admin action)
     */
    public static function adminActionStatuses(): array
    {
        return array_filter(self::cases(), fn($status) => $status->isAwaitingReview());
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
     * Get pending statuses only
     */
    public static function pendingStatuses(): array
    {
        return array_filter(self::cases(), fn($status) => $status->isPending());
    }

    /**
     * Get completed statuses only
     */
    public static function completedStatuses(): array
    {
        return array_filter(self::cases(), fn($status) => $status->isCompleted());
    }
}
