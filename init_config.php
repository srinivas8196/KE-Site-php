<?php
/**
 * Configuration System Initialization Script
 */

require_once __DIR__ . '/config_helper.php';

try {
    $config = ConfigHelper::getInstance();
    
    if ($config->initializeConfig()) {
        echo "Configuration system initialized successfully.\n";
    } else {
        echo "Failed to initialize configuration system.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 