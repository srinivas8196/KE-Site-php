<?php
session_start();
require 'db.php';
require_once 'auth_helper.php';

// Check if user has permission
requirePermission('campaign_manager', 'login.php');

// Function to ensure logging happens
function log_activity($pdo, $action, $details, $user_id = 1) {
    try {
        // First make sure the table exists
        $pdo->exec('CREATE TABLE IF NOT EXISTS activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY, 
            user_id INT NOT NULL, 
            action VARCHAR(100) NOT NULL, 
            details TEXT, 
            ip_address VARCHAR(45), 
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
        
        // Then add the entry
        $log_stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $log_result = $log_stmt->execute([
            $user_id,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);
        
        if ($log_result) {
            error_log("Successfully logged activity: $action - $details");
        } else {
            error_log("Failed to log activity: " . implode(", ", $log_stmt->errorInfo()));
        }
    } catch (Exception $log_error) {
        // Just log the error but don't fail the main operation
        error_log("Error logging activity: " . $log_error->getMessage());
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $destination_id = $_POST['destination_id'] ?? null;
    $destination_name = $_POST['destination_name'] ?? '';
    $destination_description = $_POST['destination_description'] ?? '';
    
    // Handle file upload
    $banner_image = null;
    if (!empty($_FILES["banner_image"]["name"])) {
        $target_dir = "assets/destinations/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $banner_image = basename($_FILES["banner_image"]["name"]);
        $target_file = $target_dir . $banner_image;

        // Move uploaded file to the target directory
        if (!move_uploaded_file($_FILES["banner_image"]["tmp_name"], $target_file)) {
            die("Error uploading image.");
        }
    }

    try {
        if ($destination_id) {
            // Get original destination data for logging
            $getStmt = $pdo->prepare("SELECT destination_name FROM destinations WHERE id = ?");
            $getStmt->execute([$destination_id]);
            $originalDest = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            // Update existing destination
            if ($banner_image) {
                $stmt = $pdo->prepare("UPDATE destinations SET destination_name = ?, destination_description = ?, banner_image = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$destination_name, $destination_description, $banner_image, $destination_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE destinations SET destination_name = ?, destination_description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$destination_name, $destination_description, $destination_id]);
            }
            
            // Log the update
            $details = "Updated destination: $destination_name (ID: $destination_id)";
            if ($originalDest && $originalDest['destination_name'] != $destination_name) {
                $details .= " (renamed from: " . $originalDest['destination_name'] . ")";
            }
            log_activity($pdo, 'update_destination', $details, $_SESSION['user_id'] ?? 1);
        } else {
            // Insert new destination
            $stmt = $pdo->prepare("INSERT INTO destinations (destination_name, destination_description, banner_image) VALUES (?, ?, ?)");
            $stmt->execute([$destination_name, $destination_description, $banner_image]);
            
            // Get the newly created ID
            $new_id = $pdo->lastInsertId();
            
            // Log the creation
            $details = "Created new destination: $destination_name (ID: $new_id)";
            log_activity($pdo, 'create_destination', $details, $_SESSION['user_id'] ?? 1);
        }

        // Redirect back to the destinations list after saving
        header("Location: destination_list.php");
        exit();
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>
