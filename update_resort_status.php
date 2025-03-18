<?php
// update_resort_status.php
session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}
require 'db.php';

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
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}
?>
