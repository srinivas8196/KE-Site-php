<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

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

if (isset($_GET['id'])) {
    $resortId = $_GET['id'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Fetch the landing page file path and resort slug from the database
        $stmt = $pdo->prepare("SELECT file_path, resort_slug FROM resorts WHERE id = ?");
        $stmt->execute([$resortId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $filePath = $result['file_path'];      // e.g., "abc.php"
            $resortSlug = $result['resort_slug'];    // used to locate assets folder
            
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
            
            // Delete the landing page file if it exists
            if ($filePath && file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Build the assets directory path (e.g., "assets/resorts/abc")
            $assetsDir = "assets/resorts/" . $resortSlug;
            if (is_dir($assetsDir)) {
                deleteDirectory($assetsDir);
            }
            
            $_SESSION['success'] = "Resort deleted successfully.";
        }
    } catch (PDOException $e) {
        // If there's an error, rollback the transaction
        $pdo->rollBack();
        $_SESSION['error'] = "Error deleting resort: " . $e->getMessage();
    }
}

header("Location: resort_list.php");
exit();
?>
