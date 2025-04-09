<?php
require_once __DIR__ . '/config_helper.php';

try {
    $config = ConfigHelper::getInstance();
    
    // Check for LeadSquared credentials
    $keys = [
        'LEADSQUARED_ACCESS_KEY',
        'LEADSQUARED_SECRET_KEY',
        'LEADSQUARED_API_URL'
    ];
    
    $output = "Checking stored credentials:\n";
    $output .= "===========================\n";
    
    foreach ($keys as $key) {
        $value = $config->get($key);
        if ($value) {
            // For security, only show first 4 characters of keys
            $displayValue = (strpos($key, 'KEY') !== false) 
                ? substr($value, 0, 4) . '...[hidden]' 
                : $value;
            $output .= "$key: $displayValue\n";
        } else {
            $output .= "$key: NOT FOUND\n";
        }
    }
    
    // Write to error log and display
    error_log($output);
    file_put_contents('php://stderr', $output);
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage() . "\n";
    error_log($error);
    file_put_contents('php://stderr', $error);
} 