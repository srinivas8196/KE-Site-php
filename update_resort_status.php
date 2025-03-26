<?php
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

try {
    // Update the resort's active status in the database
    $stmt = $pdo->prepare("UPDATE resorts SET is_active = ? WHERE id = ?");
    $stmt->execute([$isActive, $resortId]);

    // Fetch the updated resort data
    $stmt = $pdo->prepare("SELECT resort_slug FROM resorts WHERE id = ?");
    $stmt->execute([$resortId]);
    $resort = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'resort_slug' => $resort['resort_slug'],
        'is_active' => $isActive
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
