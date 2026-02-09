<?php

namespace App\Services;

use App\Models\USSD;
use App\Enums\EnvironmentType;

/**
 * Resolves USSD from AfricasTalking request (serviceCode + text).
 */
class UssdRequestResolver
{
    /**
     * Resolve USSD from request.
     * Returns ['ussd' => USSD|null, 'direct_dial' => bool].
     */
    public function resolve(?string $serviceCode, string $text): array
    {
        if (!$serviceCode) {
            return ['ussd' => null, 'direct_dial' => false];
        }

        $query = fn () => USSD::with(['sharedCodeAllocations', 'sharedCodeAllocations.targetUssd'])
            ->where('is_active', true)
            ->whereHas('environment', fn ($q) => $q->where('name', EnvironmentType::PRODUCTION->value));

        $ussd = $query()->where('pattern', $serviceCode)->first();
        if ($ussd) {
            return ['ussd' => $ussd, 'direct_dial' => false];
        }

        if ($text !== '') {
            $choice = $this->getFirstSegment($text);
            if ($choice !== '') {
                $directPattern = rtrim($serviceCode, '#') . '*' . $choice . '#';
                $ussd = $query()->where('pattern', $directPattern)->first();
                if ($ussd) {
                    return ['ussd' => $ussd, 'direct_dial' => true];
                }
            }
        }

        return ['ussd' => null, 'direct_dial' => false];
    }

    protected function getFirstSegment(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        $parts = explode('*', $text);
        return trim($parts[0]);
    }
}
