<?php

namespace App\Enums;

enum USSDActionType: string
{
    case NAVIGATE = 'navigate';
    case MESSAGE = 'message';
    case END_SESSION = 'end_session';
    case API_CALL = 'api_call';
    case EXTERNAL_API_CALL = 'external_api_call';
    case INPUT_COLLECTION = 'input_collection';

    /**
     * Get the display name for the action type
     */
    public function displayName(): string
    {
        return match($this) {
            self::NAVIGATE => 'Navigate to Flow',
            self::MESSAGE => 'Display Message',
            self::END_SESSION => 'End Session',
            self::API_CALL => 'API Call',
            self::EXTERNAL_API_CALL => 'External API Call',
            self::INPUT_COLLECTION => 'Collect Input',
        };
    }

    /**
     * Get the description for the action type
     */
    public function description(): string
    {
        return match($this) {
            self::NAVIGATE => 'Navigate to another USSD flow',
            self::MESSAGE => 'Display a message to the user',
            self::END_SESSION => 'End the current USSD session',
            self::API_CALL => 'Make an external API call',
            self::EXTERNAL_API_CALL => 'Make an external API call with configuration',
            self::INPUT_COLLECTION => 'Collect input from the user',
        };
    }

    /**
     * Check if this action requires additional input
     */
    public function requiresInput(): bool
    {
        return $this === self::INPUT_COLLECTION;
    }

    /**
     * Check if this action ends the session
     */
    public function endsSession(): bool
    {
        return $this === self::END_SESSION;
    }

    /**
     * Check if this action navigates to another flow
     */
    public function navigates(): bool
    {
        return $this === self::NAVIGATE;
    }

    /**
     * Check if this action makes an external call
     */
    public function makesExternalCall(): bool
    {
        return $this === self::API_CALL || $this === self::EXTERNAL_API_CALL;
    }

    /**
     * Get the default action data structure for this type
     */
    public function defaultActionData(): array
    {
        return match($this) {
            self::NAVIGATE => [
                'next_flow_id' => null,
                'flow_name' => '',
            ],
            self::MESSAGE => [
                'message' => '',
                'show_options' => false,
            ],
            self::END_SESSION => [
                'message' => 'Thank you for using our service.',
                'show_final_message' => true,
            ],
            self::API_CALL => [
                'url' => '',
                'method' => 'GET',
                'headers' => [],
                'body' => [],
                'timeout' => 30,
            ],
            self::INPUT_COLLECTION => [
                'prompt' => '',
                'validation' => '',
                'max_length' => 160,
                'min_length' => 1,
            ],
        };
    }

    /**
     * Get the validation rules for action data
     */
    public function validationRules(): array
    {
        return match($this) {
            self::NAVIGATE => [
                'next_flow_id' => 'required|exists:ussd_flows,id',
            ],
            self::MESSAGE => [
                'message' => 'required|string|max:160',
                'show_options' => 'boolean',
            ],
            self::END_SESSION => [
                'message' => 'string|max:160',
                'show_final_message' => 'boolean',
            ],
            self::API_CALL => [
                'url' => 'required|url',
                'method' => 'required|in:GET,POST,PUT,DELETE',
                'headers' => 'array',
                'body' => 'array',
                'timeout' => 'integer|min:1|max:60',
            ],
            self::INPUT_COLLECTION => [
                'prompt' => 'required|string|max:160',
                'validation' => 'string',
                'max_length' => 'integer|min:1|max:160',
                'min_length' => 'integer|min:1',
            ],
        };
    }

    /**
     * Get all action types as an array
     */
    public static function toArray(): array
    {
        return array_map(fn($type) => $type->value, self::cases());
    }

    /**
     * Get all action types with display names
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
