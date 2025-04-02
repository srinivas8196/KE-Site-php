<?php
// Prevent any output before JSON response
ob_start();

// Set JSON content type
header('Content-Type: application/json');

try {
    // Initialize database connection
    $pdo = require_once 'db.php';
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['resort_id']) || !isset($data['status'])) {
        throw new Exception('Missing required parameters');
    }

    $resort_id = (int)$data['resort_id'];
    $status = (int)$data['status'];

    // First get the resort slug
    $stmt = $pdo->prepare("SELECT resort_slug FROM resorts WHERE id = ?");
    $stmt->execute([$resort_id]);
    $resort = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resort) {
        throw new Exception('Resort not found');
    }

    // Update the resort status
    $stmt = $pdo->prepare("UPDATE resorts SET is_active = ? WHERE id = ?");
    $success = $stmt->execute([$status, $resort_id]);

    if (!$success) {
        throw new Exception('Failed to update resort status');
    }

    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Send success response
    echo json_encode([
        'success' => true,
        'message' => $status ? 'Resort activated successfully' : 'Resort deactivated successfully',
        'resort_slug' => $resort['resort_slug']
    ]);

} catch (Exception $e) {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Send error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
