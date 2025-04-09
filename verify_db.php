<?php
try {
    $db = new PDO(
        "mysql:host=localhost;dbname=karmaexperience",
        "root",
        "",
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    $stmt = $db->query("SELECT * FROM system_config");
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Database Configuration Values:\n";
    echo "============================\n";
    
    foreach ($configs as $config) {
        $value = $config['config_value'];
        // For security, mask sensitive values
        if (strpos($config['config_key'], 'KEY') !== false) {
            $value = substr($value, 0, 4) . '...[hidden]';
        }
        echo $config['config_key'] . ": " . $value . "\n";
        echo "Created: " . $config['created_at'] . "\n";
        echo "Updated: " . $config['updated_at'] . "\n";
        echo "----------------------------\n";
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} 