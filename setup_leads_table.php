<?php
require 'db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS resort_leads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        phone VARCHAR(50),
        email VARCHAR(100),
        has_passport ENUM('yes', 'no'),
        resort_name VARCHAR(255),
        resort_code VARCHAR(100),
        destination_name VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "Resort leads table created successfully";
} catch(PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}