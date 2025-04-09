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

if (isset($_GET['id'])) {
  $destination_id = $_GET['id'];
  
  // Get destination name before deleting for the log
  $getStmt = $pdo->prepare("SELECT destination_name FROM destinations WHERE id = ?");
  $getStmt->execute([$destination_id]);
  $destination = $getStmt->fetch(PDO::FETCH_ASSOC);
  
  // Delete the destination
  $stmt = $pdo->prepare("DELETE FROM destinations WHERE id = ?");
  $result = $stmt->execute([$destination_id]);
  
  if ($result && $destination) {
    // Log the deletion
    $details = "Deleted destination: " . $destination['destination_name'] . " (ID: $destination_id)";
    log_activity($pdo, 'delete_destination', $details, $_SESSION['user_id'] ?? 1);
  }
}

header("Location: destination_list.php");
exit();
?>
