<?php
ob_start(); // Start output buffering
session_start();

// Include auth_helper for proper permission checking
require_once 'auth_helper.php';

// Check for proper authentication using requirePermission
if (!isset($_SESSION['user_id']) || !hasPermission('campaign_manager')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Enable error reporting for debugging but capture it
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable direct output of errors
ini_set('log_errors', 1); // Enable error logging

try {
    $pdo = require 'db.php';

    // Validate input
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('Missing enquiry ID');
    }

    $enquiry_id = intval($_GET['id']);
    
    // Log the request
    error_log("Fetching enquiry ID: " . $enquiry_id);
    
    // First check if the enquiry exists
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM resort_enquiries WHERE id = ?");
    $checkStmt->execute([$enquiry_id]);
    $count = $checkStmt->fetchColumn();
    
    if ($count == 0) {
        throw new Exception('Enquiry not found with ID: ' . $enquiry_id);
    }

    // Get enquiry details with resort and destination information
    $query = "SELECT 
                e.*, 
                r.resort_name, 
                r.is_partner,
                r.resort_code,
                d.destination_name 
              FROM 
                resort_enquiries e 
              LEFT JOIN 
                resorts r ON e.resort_id = r.id 
              LEFT JOIN 
                destinations d ON e.destination_id = d.id
              WHERE 
                e.id = ?";
                
    $stmt = $pdo->prepare($query);
    $stmt->execute([$enquiry_id]);
    $enquiry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enquiry) {
        throw new Exception('Enquiry found in DB but failed to fetch details. ID: ' . $enquiry_id);
    }
    
    // Convert dates to proper format
    if (!empty($enquiry['date_of_birth'])) {
        $enquiry['date_of_birth'] = date('Y-m-d', strtotime($enquiry['date_of_birth']));
    }
    
    if (!empty($enquiry['created_at'])) {
        $enquiry['created_at_formatted'] = date('Y-m-d H:i:s', strtotime($enquiry['created_at']));
    }
    
    // Prepare yes/no fields
    $enquiry['has_passport'] = isset($enquiry['has_passport']) ? ucfirst($enquiry['has_passport']) : 'Not specified';
    
    // Add success flag
    $enquiry['success'] = true;
    
    // Clear any previous output
    ob_clean();
    
    // Send response
    header('Content-Type: application/json');
    echo json_encode($enquiry);
    
} catch (Exception $e) {
    // Clear any previous output
    ob_clean();
    
    error_log("Error in get_enquiry_details.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'error_details' => $e->getTraceAsString()
    ]);
} finally {
    ob_end_flush(); // End output buffering and flush
} 