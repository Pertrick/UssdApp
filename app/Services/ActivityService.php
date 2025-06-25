<?php

namespace App\Services;

use App\Models\UserActivity;
use Illuminate\Support\Facades\Request;

class ActivityService
{
    public static function log($userId, $activityType, $description, $subjectType = null, $subjectId = null, $properties = [])
    {
        return UserActivity::create([
            'user_id' => $userId,
            'activity_type' => $activityType,
            'description' => $description,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public static function logLogin($userId)
    {
        return self::log($userId, 'login', 'User logged in successfully');
    }

    public static function logLogout($userId)
    {
        return self::log($userId, 'logout', 'User logged out');
    }

    public static function logUSSDCreated($userId, $ussdId, $ussdName)
    {
        return self::log(
            $userId,
            'ussd_created',
            "Created USSD service: {$ussdName}",
            'App\Models\USSD',
            $ussdId,
            ['ussd_name' => $ussdName]
        );
    }

    public static function logUSSDUpdated($userId, $ussdId, $ussdName)
    {
        return self::log(
            $userId,
            'ussd_updated',
            "Updated USSD service: {$ussdName}",
            'App\Models\USSD',
            $ussdId,
            ['ussd_name' => $ussdName]
        );
    }

    public static function logUSSDDeleted($userId, $ussdName)
    {
        return self::log(
            $userId,
            'ussd_deleted',
            "Deleted USSD service: {$ussdName}",
            null,
            null,
            ['ussd_name' => $ussdName]
        );
    }

    public static function logBusinessCreated($userId, $businessId, $businessName)
    {
        return self::log(
            $userId,
            'business_created',
            "Created business: {$businessName}",
            'App\Models\Business',
            $businessId,
            ['business_name' => $businessName]
        );
    }

    public static function logBusinessVerified($userId, $businessId, $businessName)
    {
        return self::log(
            $userId,
            'business_verified',
            "Business verified: {$businessName}",
            'App\Models\Business',
            $businessId,
            ['business_name' => $businessName]
        );
    }
} 