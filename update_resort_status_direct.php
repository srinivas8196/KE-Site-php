<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get the database connection
$pdo = require 'db.php';

// Check if we have the required POST data
if (!isset($_POST['resort_id']) || !isset($_POST['is_active'])) {
    // Check if data came as JSON
    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true);
    
    if (!$data || !isset($data['resort_id']) || !isset($data['is_active'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit();
    }
    
    $resortId = $data['resort_id'];
    $isActive = $data['is_active'];
} else {
    $resortId = $_POST['resort_id'];
    $isActive = $_POST['is_active'];
}

try {
    // Log the update action (for debugging)
    error_log("Direct update: Resort ID: $resortId to is_active: $isActive");
    
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
    
    if (!$resort) {
        throw new Exception("Resort not found after update");
    }
    
    // Set success message in session
    $_SESSION['success_message'] = $isActive == 1 
        ? "Resort activated successfully" 
        : "Resort deactivated successfully";
    
    // Return JSON response for AJAX requests
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $_SESSION['success_message'],
        'resort_slug' => $resort['resort_slug'],
        'is_active' => $isActive
    ]);
    
} catch (Exception $e) {
    error_log("Error updating resort status: " . $e->getMessage());
    
    // Store error in session
    $_SESSION['error_message'] = "Error updating resort: " . $e->getMessage();
    
    // Return JSON error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $_SESSION['error_message']
    ]);
}

// Exit without redirecting - the AJAX handler will manage the UI update
exit();
?> 