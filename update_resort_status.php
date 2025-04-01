<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}
// Get the database connection properly
$pdo = require 'db.php';

// Get JSON input from request body
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['resort_id']) || !isset($data['is_active'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$resortId = $data['resort_id'];
$isActive = $data['is_active'];

try {
    // Log the update action (for debugging)
    error_log("Updating resort ID: $resortId to is_active: $isActive");
    
    // Update the resort's active status in the database
    $stmt = $pdo->prepare("UPDATE resorts SET is_active = ? WHERE id = ?");
    $result = $stmt->execute([$isActive, $resortId]);
    
    if (!$result) {
        throw new Exception("Failed to update resort status");
    }
    
    // Verify the update was successful
    $verifyStmt = $pdo->prepare("SELECT is_active, resort_slug FROM resorts WHERE id = ?");
    $verifyStmt->execute([$resortId]);
    $resort = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ((int)$resort['is_active'] !== (int)$isActive) {
        throw new Exception("Database update verification failed");
    }
    
    // Set session message for notification
    $_SESSION['success_message'] = $isActive ? "Resort activated successfully" : "Resort deactivated successfully";
    
    echo json_encode([
        'success' => true,
        'resort_slug' => $resort['resort_slug'],
        'is_active' => $isActive
    ]);
} catch (Exception $e) {
    error_log("Error updating resort status: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
