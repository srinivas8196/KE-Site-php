<?php
session_start();

// Include auth helper
require_once 'auth_helper.php';

// Check if user has campaign_manager or higher permission
requirePermission('campaign_manager', 'login.php');

// Include database connection
$pdo = require 'db.php';
if (!$pdo) {
    die("Database connection failed");
}

// Function to recursively delete a directory and its contents
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    return rmdir($dir);
}

// Function to ensure logging happens
function log_resort_activity($pdo, $action, $details, $user_id = 1) {
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
        
        // Then add the entry - don't use transaction for logging
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
    $resortId = $_GET['id'];
    
    try {
        // Fetch the resort details first, before starting transaction
        $stmt = $pdo->prepare("SELECT file_path, resort_slug, resort_name, banner_image, gallery, room_details, amenities FROM resorts WHERE id = ?");
        $stmt->execute([$resortId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            $_SESSION['error_message'] = "Resort not found.";
            error_log("Attempted to delete non-existent resort with ID: $resortId");
            header("Location: resort_list.php");
            exit();
        }
            
        $filePath = $result['file_path'];      // e.g., "abc.php"
        $resortSlug = $result['resort_slug'];    // used to locate assets folder
        $resortName = $result['resort_name'];    // for activity log
        $bannerImage = $result['banner_image'];
        $gallery = json_decode($result['gallery'], true);
        $roomDetails = json_decode($result['room_details'], true);
        $amenities = json_decode($result['amenities'], true);
        
        // Start transaction only after confirming resort exists
        $pdo->beginTransaction();
        
        // Check if there are any enquiries first
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM resort_enquiries WHERE resort_id = ?");
        $checkStmt->execute([$resortId]);
        $hasEnquiries = $checkStmt->fetchColumn() > 0;
        
        // Only delete enquiries if they exist
        if ($hasEnquiries) {
            $stmt = $pdo->prepare("DELETE FROM resort_enquiries WHERE resort_id = ?");
            $stmt->execute([$resortId]);
        }
        
        // Then delete the resort
        $stmt = $pdo->prepare("DELETE FROM resorts WHERE id = ?");
        $stmt->execute([$resortId]);
        
        // If everything is successful, commit the transaction
        $pdo->commit();
        
        // Log deletion action AFTER transaction is committed
        $activityDetails = "Deleted resort: $resortName (ID: $resortId)";
        log_resort_activity($pdo, 'delete_resort', $activityDetails, $_SESSION['user_id']);
        
        // Process file deletion AFTER database operations are completed
        $deletionSuccess = true;
        
        // Delete the landing page file if it exists
        if ($filePath && file_exists($filePath)) {
            if (unlink($filePath)) {
                error_log("Deleted landing page file: $filePath");
            } else {
                error_log("Failed to delete landing page file: $filePath");
                $deletionSuccess = false;
            }
        } else {
            error_log("Landing page file not found: $filePath");
        }
        
        // Build the assets directory path (e.g., "assets/resorts/abc")
        $assetsDir = "assets/resorts/" . $resortSlug;
        
        // Log what we're about to delete for debugging
        error_log("Attempting to delete resort directory: $assetsDir");
        
        // Check if resort directory exists before attempting deletion
        if (is_dir($assetsDir)) {
            if (deleteDirectory($assetsDir)) {
                error_log("Successfully deleted resort directory: $assetsDir");
            } else {
                error_log("Failed to delete some files in resort directory: $assetsDir - this can happen if files are in use or permissions are insufficient");
                $deletionSuccess = false;
            }
        } else {
            error_log("Resort directory not found: $assetsDir");
        }
        
        if ($deletionSuccess) {
            $_SESSION['success_message'] = "Resort and all related files deleted successfully.";
        } else {
            $_SESSION['success_message'] = "Resort deleted from database, but some files could not be removed.";
        }
    } catch (PDOException $e) {
        // If there's an error, rollback the transaction if it's active
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_message'] = "Error deleting resort: " . $e->getMessage();
        error_log("Error deleting resort: " . $e->getMessage());
    }
}

header("Location: resort_list.php");
exit();
?>
