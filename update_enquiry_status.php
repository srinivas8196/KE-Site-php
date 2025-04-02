<?php
// Start output buffering to catch any unwanted output
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

session_start();

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Log the start of the request
error_log("Update enquiry status request received");

// Check authentication
if (!isset($_SESSION['user'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

try {
    // Get and decode JSON input
    $input = file_get_contents('php://input');
    error_log("Raw input received: " . $input);
    
    if (!$input) {
        throw new Exception('No input data received');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }

    error_log("Decoded data: " . print_r($data, true));

    // Validate required fields
    if (!isset($data['enquiry_id']) || !isset($data['status'])) {
        throw new Exception('Missing required fields: enquiry_id or status');
    }

    $enquiry_id = filter_var($data['enquiry_id'], FILTER_VALIDATE_INT);
    if ($enquiry_id === false) {
        throw new Exception('Invalid enquiry ID');
    }

    $status = trim($data['status']);
    $valid_statuses = ['new', 'contacted', 'converted', 'closed'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid status value');
    }

    // Connect to database
    $pdo = require 'db.php';
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // First, check if the enquiry exists
        $check_stmt = $pdo->prepare("SELECT id FROM resort_enquiries WHERE id = ?");
        $check_stmt->execute([$enquiry_id]);
        if (!$check_stmt->fetch()) {
            throw new Exception('Enquiry not found');
        }

        // Update the status
        $stmt = $pdo->prepare("UPDATE resort_enquiries SET status = ? WHERE id = ?");
        $result = $stmt->execute([$status, $enquiry_id]);

        if (!$result) {
            throw new Exception('Failed to update status');
        }

        // Log the status change
        $log_stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (?, 'update_enquiry_status', ?)");
        $log_stmt->execute([
            $_SESSION['user']['id'],
            "Updated enquiry #$enquiry_id status to $status"
        ]);

        // Commit transaction
        $pdo->commit();

        error_log("Successfully updated enquiry status");

        // Clear any buffered output and send success response
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully',
            'new_status' => $status
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    // Log the error
    error_log("Error in update_enquiry_status.php: " . $e->getMessage());
    
    // Clear any buffered output and send error response
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 