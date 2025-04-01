<?php
// This script adds the additional_requirements column to the resort_enquiries table if it doesn't exist

// Include database connection
$pdo = require 'db.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM `resort_enquiries` LIKE 'additional_requirements'");
    $columnExists = $stmt->fetchColumn();
    
    if (!$columnExists) {
        // Add the column
        $pdo->exec("ALTER TABLE `resort_enquiries` ADD COLUMN `additional_requirements` TEXT AFTER `has_passport`");
        echo "Successfully added 'additional_requirements' column to the resort_enquiries table.";
    } else {
        echo "The 'additional_requirements' column already exists in the resort_enquiries table.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 