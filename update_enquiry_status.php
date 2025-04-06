<?php
// Start output buffering to catch any unwanted output
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

session_start();

// Include auth helper
require_once 'auth_helper.php';

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Log the start of the request
error_log("Update enquiry status request received");

// Check authentication
if (!isset($_SESSION['user_id']) || !hasPermission('campaign_manager')) {
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

        // Try to log the status change, but continue if the table doesn't exist
        try {
            // Check if activity_log table exists
            $table_check = $pdo->query("SHOW TABLES LIKE 'activity_log'");
            if ($table_check->rowCount() > 0) {
                $log_stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (?, 'update_enquiry_status', ?)");
                $log_stmt->execute([
                    $_SESSION['user_id'],
                    "Updated enquiry #$enquiry_id status to $status"
                ]);
            } else {
                error_log("Activity log table does not exist - skipping logging");
            }
        } catch (Exception $log_error) {
            // Just log the error but don't fail the main operation
            error_log("Error logging activity: " . $log_error->getMessage());
        }

        // Commit transaction
        $pdo->commit();

        error_log("Successfully updated enquiry status");

        // Get the latest counts to include in the response
        $statsStmt = $pdo->query("SELECT 
            COUNT(e.id) as total,
            SUM(CASE WHEN e.status = 'new' THEN 1 ELSE 0 END) as new_count,
            SUM(CASE WHEN e.status = 'contacted' THEN 1 ELSE 0 END) as contacted_count,
            SUM(CASE WHEN e.status = 'converted' THEN 1 ELSE 0 END) as converted_count,
            SUM(CASE WHEN e.status = 'closed' THEN 1 ELSE 0 END) as closed_count
            FROM resort_enquiries e
            LEFT JOIN resorts r ON e.resort_id = r.id");
        
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        // Log the counts to help with debugging
        error_log("Updated status counts: " . json_encode($stats));
        
        if (!$stats) {
            $stats = [
                'total' => 0,
                'new_count' => 0,
                'contacted_count' => 0,
                'converted_count' => 0,
                'closed_count' => 0
            ];
        } else {
            // Ensure all keys exist (handle any nulls from database)
            if (!isset($stats['closed_count'])) $stats['closed_count'] = 0;
            if (!isset($stats['new_count'])) $stats['new_count'] = 0;
            if (!isset($stats['contacted_count'])) $stats['contacted_count'] = 0;
            if (!isset($stats['converted_count'])) $stats['converted_count'] = 0;
        }

        // Clear any buffered output and send success response
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully',
            'new_status' => $status,
            'counts' => $stats,
            'timestamp' => time()
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