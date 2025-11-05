<?php

/**
 * Global Helper Functions
 * 
 * This file contains globally available helper functions for the application.
 * Auto-loaded via composer.json "files" configuration.
 * 
 * @package App
 */

if (!function_exists('format_phone')) {
    /**
     * Format phone number to standard format
     * Removes non-numeric characters and formats consistently
     * 
     * @param string|null $phone Raw phone number
     * @return string|null Formatted phone number
     */
    function format_phone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // Format based on length
        if (strlen($cleaned) === 10) {
            // Format: (XXX) XXX-XXXX
            return sprintf('(%s) %s-%s', 
                substr($cleaned, 0, 3),
                substr($cleaned, 3, 3),
                substr($cleaned, 6)
            );
        }

        return $cleaned;
    }
}

if (!function_exists('sanitize_phone')) {
    /**
     * Sanitize phone number to digits only
     * 
     * @param string|null $phone Raw phone number
     * @return string|null Sanitized phone (digits only)
     */
    function sanitize_phone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        return preg_replace('/[^0-9]/', '', $phone);
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format amount as currency
     * 
     * @param float|int $amount Amount to format
     * @param string $currency Currency code (default: 'ARS')
     * @return string Formatted currency string
     */
    function format_currency($amount, string $currency = 'ARS'): string
    {
        $symbols = [
            'ARS' => '$',
            'USD' => 'US$',
            'EUR' => '€',
        ];

        $symbol = $symbols[$currency] ?? $currency;
        
        return $symbol . ' ' . number_format($amount, 2, ',', '.');
    }
}

if (!function_exists('time_ago')) {
    /**
     * Get human-readable time difference
     * 
     * @param \Carbon\Carbon|string|null $datetime Date/time to compare
     * @return string Human-readable time difference
     */
    function time_ago($datetime): string
    {
        if (empty($datetime)) {
            return 'nunca';
        }

        if (is_string($datetime)) {
            $datetime = \Carbon\Carbon::parse($datetime);
        }

        return $datetime->diffForHumans();
    }
}

if (!function_exists('log_action')) {
    /**
     * Log user action with context
     * 
     * @param string $action Action description
     * @param array $context Additional context
     * @param string $level Log level (info, warning, error)
     * @return void
     */
    function log_action(string $action, array $context = [], string $level = 'info'): void
    {
        $user = auth()->user();
        
        $enrichedContext = array_merge([
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ], $context);

        \Illuminate\Support\Facades\Log::$level($action, $enrichedContext);
    }
}

if (!function_exists('generate_unique_code')) {
    /**
     * Generate a unique code with prefix
     * 
     * @param string $prefix Code prefix (e.g., 'INV', 'ORD')
     * @param int $length Length of random part (default: 8)
     * @return string Unique code
     */
    function generate_unique_code(string $prefix = '', int $length = 8): string
    {
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, $length));
        
        return $prefix ? "{$prefix}-{$random}" : $random;
    }
}

if (!function_exists('array_get_first')) {
    /**
     * Get first non-null value from array
     * 
     * @param array $values Array of values
     * @param mixed $default Default value if all are null
     * @return mixed First non-null value or default
     */
    function array_get_first(array $values, $default = null)
    {
        foreach ($values as $value) {
            if (!is_null($value)) {
                return $value;
            }
        }

        return $default;
    }
}

if (!function_exists('is_valid_email')) {
    /**
     * Validate email address
     * 
     * @param string|null $email Email to validate
     * @return bool True if valid
     */
    function is_valid_email(?string $email): bool
    {
        if (empty($email)) {
            return false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('truncate_text')) {
    /**
     * Truncate text to specified length
     * 
     * @param string|null $text Text to truncate
     * @param int $length Maximum length
     * @param string $suffix Suffix to append (default: '...')
     * @return string Truncated text
     */
    function truncate_text(?string $text, int $length = 100, string $suffix = '...'): string
    {
        if (empty($text)) {
            return '';
        }

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . $suffix;
    }
}

if (!function_exists('array_wrap_if_not')) {
    /**
     * Wrap value in array if not already an array
     * 
     * @param mixed $value Value to wrap
     * @return array Wrapped value
     */
    function array_wrap_if_not($value): array
    {
        return is_array($value) ? $value : [$value];
    }
}
