<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}
require 'db_mongo.php';

// Get JSON input from request body
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['resort_id']) || !isset($data['is_active'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$resortId = $data['resort_id'];
$isActive = $data['is_active'];

// Update the resort's active status in the database
$stmt = $pdo->prepare("UPDATE resorts SET is_active = ? WHERE id = ?");
if ($stmt->execute([$isActive, $resortId])) {
    // Fetch the landing page file path from the updated record
    $stmt = $pdo->prepare("SELECT file_path FROM resorts WHERE id = ?");
    $stmt->execute([$resortId]);
    $file_path = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'file_path' => $file_path,
        'is_active' => $isActive
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}
?>
