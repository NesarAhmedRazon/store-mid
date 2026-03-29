<?php

if (!function_exists('format_decimal')) {
    /**
     * Format decimal numbers removing unnecessary trailing zeros
     * 
     * @param float|int|string $number The number to format
     * @param int $max_decimals Maximum decimal places to show (default: 10)
     * @return string Formatted number
     */
    function format_decimal($number, $max_decimals = 6) {
        // Format with max decimals and thousand separators
        $formatted = number_format((float)$number, $max_decimals, '.', ',');
        
        // Remove trailing zeros from decimal part
        if (strpos($formatted, '.') !== false) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }
        
        return $formatted;
    }
}