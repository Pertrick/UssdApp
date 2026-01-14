<?php

namespace App\Services;

class SanitizationService
{
    /**
     * Sanitize user input based on input type
     * 
     * @param string $input The input to sanitize
     * @param string $inputType The type of input (input_phone, input_number, etc.)
     * @param array $options Additional sanitization options
     * @return string Sanitized input
     */
    public function sanitizeInput(string $input, string $inputType, array $options = []): string
    {
        // Remove null bytes and control characters
        $input = str_replace(["\0", "\r"], '', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        // Get max length from options or use defaults
        $maxLength = $options['max_length'] ?? $this->getDefaultMaxLength($inputType);
        
        // Type-specific sanitization
        switch ($inputType) {
            case 'input_phone':
                return $this->sanitizePhone($input, $maxLength);
                
            case 'input_number':
                return $this->sanitizeNumber($input, $maxLength);
                
            case 'input_amount':
                return $this->sanitizeAmount($input, $maxLength);
                
            case 'input_account':
                return $this->sanitizeAccount($input, $maxLength);
                
            case 'input_pin':
                return $this->sanitizePin($input, $maxLength);
                
            case 'input_text':
                return $this->sanitizeText($input, $maxLength, $options);
                
            case 'ussd_selection':
                return $this->sanitizeUSSDSelection($input, $maxLength);
                
            case 'service_code':
                return $this->sanitizeServiceCode($input);
                
            case 'session_id':
                return $this->sanitizeSessionId($input, $maxLength);
                
            default:
                return $this->sanitizeGeneric($input, $maxLength);
        }
    }

    /**
     * Sanitize phone number input
     */
    private function sanitizePhone(string $input, int $maxLength): string
    {
        // Only allow digits, +, and spaces
        $input = preg_replace('/[^0-9+ ]/', '', $input);
        return substr($input, 0, $maxLength);
    }

    /**
     * Sanitize number input
     */
    private function sanitizeNumber(string $input, int $maxLength): string
    {
        // Only allow digits, decimal point, and minus sign
        $input = preg_replace('/[^0-9.\-]/', '', $input);
        return substr($input, 0, $maxLength);
    }

    /**
     * Sanitize amount input
     */
    private function sanitizeAmount(string $input, int $maxLength): string
    {
        // Only allow digits and decimal point
        $input = preg_replace('/[^0-9.]/', '', $input);
        // Remove multiple decimal points
        $parts = explode('.', $input);
        if (count($parts) > 2) {
            $input = $parts[0] . '.' . implode('', array_slice($parts, 1));
        }
        return substr($input, 0, $maxLength);
    }

    /**
     * Sanitize account number input
     */
    private function sanitizeAccount(string $input, int $maxLength): string
    {
        // Only allow alphanumeric
        $input = preg_replace('/[^a-zA-Z0-9]/', '', $input);
        return substr($input, 0, $maxLength);
    }

    /**
     * Sanitize PIN input
     */
    private function sanitizePin(string $input, int $maxLength): string
    {
        // Only allow digits
        $input = preg_replace('/[^0-9]/', '', $input);
        return substr($input, 0, $maxLength);
    }

    /**
     * Sanitize text input
     */
    private function sanitizeText(string $input, int $maxLength, array $options): string
    {
        // Remove potentially dangerous characters
        $dangerousChars = $options['remove_dangerous'] ?? true;
        if ($dangerousChars) {
            $input = preg_replace('/[<>"\']/', '', $input);
        }
        
        // Remove control characters
        $input = preg_replace('/[\x00-\x1F\x7F]/', '', $input);
        
        return substr($input, 0, $maxLength);
    }

    /**
     * Sanitize USSD selection input (menu option selection)
     */
    private function sanitizeUSSDSelection(string $input, int $maxLength): string
    {
        // Only allow alphanumeric, spaces, *, #, and basic punctuation
        $input = preg_replace('/[^a-zA-Z0-9\s*#.,\-_]/', '', $input);
        return substr($input, 0, $maxLength);
    }

    /**
     * Sanitize service code (USSD code like *123#)
     */
    public function sanitizeServiceCode(string $input): string
    {
        // Only allow digits, *, and #
        $input = preg_replace('/[^0-9*#]/', '', $input);
        return substr($input, 0, 50);
    }

    /**
     * Sanitize session ID
     */
    private function sanitizeSessionId(string $input, int $maxLength): string
    {
        // Allow alphanumeric, hyphens, and underscores (UUID format)
        $input = preg_replace('/[^a-zA-Z0-9\-_]/', '', $input);
        return substr($input, 0, $maxLength);
    }

    /**
     * Generic sanitization for unknown input types
     */
    private function sanitizeGeneric(string $input, int $maxLength): string
    {
        // Remove control characters
        $input = preg_replace('/[\x00-\x1F\x7F]/', '', $input);
        return substr($input, 0, $maxLength);
    }

    /**
     * Get default max length for input type
     */
    private function getDefaultMaxLength(string $inputType): int
    {
        return match($inputType) {
            'input_phone' => 20,
            'input_number' => 20,
            'input_amount' => 20,
            'input_account' => 50,
            'input_pin' => 10,
            'input_text' => 500,
            'ussd_selection' => 100,
            'service_code' => 50,
            'session_id' => 255,
            default => 1000,
        };
    }

    /**
     * Sanitize array of inputs
     * 
     * @param array $inputs Array of inputs to sanitize
     * @param string $inputType The type of input
     * @param array $options Additional options
     * @return array Sanitized inputs
     */
    public function sanitizeArray(array $inputs, string $inputType, array $options = []): array
    {
        $sanitized = [];
        foreach ($inputs as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeInput($value, $inputType, $options);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value, $inputType, $options);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Sanitize key name (for array keys, field names, etc.)
     * Prevents injection through key names
     * 
     * @param string $key The key to sanitize
     * @return string Sanitized key
     */
    public function sanitizeKey(string $key): string
    {
        // Only allow alphanumeric and underscores
        return preg_replace('/[^a-zA-Z0-9_]/', '', $key);
    }

    /**
     * Validate and sanitize phone number format
     * 
     * @param string $phoneNumber
     * @return string|false Sanitized phone number or false if invalid
     */
    public function validateAndSanitizePhone(string $phoneNumber): string|false
    {
        $sanitized = $this->sanitizeInput($phoneNumber, 'input_phone');
        
        // Remove spaces
        $sanitized = str_replace(' ', '', $sanitized);
        
        // Basic validation: 10-15 digits
        $digits = preg_replace('/[^0-9]/', '', $sanitized);
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return false;
        }
        
        return $sanitized;
    }

    /**
     * Validate and sanitize amount
     * 
     * @param string $amount
     * @return float|false Sanitized amount as float or false if invalid
     */
    public function validateAndSanitizeAmount(string $amount): float|false
    {
        $sanitized = $this->sanitizeInput($amount, 'input_amount');
        
        if (!is_numeric($sanitized) || $sanitized < 0) {
            return false;
        }
        
        return (float) $sanitized;
    }

    /**
     * Sanitize output text for USSD responses
     * Removes dangerous characters, control characters, and ensures safe output
     * 
     * @param string $text The text to sanitize
     * @param int $maxLength Maximum length (default: 1820 for USSD, which is ~160 chars * 11 pages)
     * @return string Sanitized text safe for USSD output
     */
    public function sanitizeOutput(string $text, int $maxLength = 1820): string
    {
        // Convert to string if not already
        $text = (string) $text;
        
        // Remove null bytes
        $text = str_replace("\0", '', $text);
        
        $text = str_replace("\r", '', $text);
        
        $text = strip_tags($text);
        
        $text = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $text);
        
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text); // Zero-width characters
        
        // Normalize whitespace (collapse multiple spaces, but keep newlines)
        $text = preg_replace('/[ \t]+/', ' ', $text);
        
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $text = implode("\n", $lines);
        
        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength);
            // Try to cut at a newline if possible
            $lastNewline = strrpos($text, "\n");
            if ($lastNewline !== false && $lastNewline > $maxLength - 50) {
                $text = substr($text, 0, $lastNewline);
            }
        }
        
        return $text;
    }
}
