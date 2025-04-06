<?php
session_start();

// Include auth helper
require_once 'auth_helper.php';

// Enable detailed logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/enquiry_counts_debug.log');

// Log request details
error_log("Get enquiry counts requested - Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));

// Check authentication
if (!isset($_SESSION['user_id']) || !hasPermission('campaign_manager')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Connect to database
    $pdo = require 'db.php';
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Log query execution
    error_log("Executing count query");
    
    // Get the counts with SQL logging
    $query = "SELECT 
        COUNT(e.id) as total,
        SUM(CASE WHEN e.status = 'new' THEN 1 ELSE 0 END) as new_count,
        SUM(CASE WHEN e.status = 'contacted' THEN 1 ELSE 0 END) as contacted_count,
        SUM(CASE WHEN e.status = 'converted' THEN 1 ELSE 0 END) as converted_count,
        SUM(CASE WHEN e.status = 'closed' THEN 1 ELSE 0 END) as closed_count
        FROM resort_enquiries e
        LEFT JOIN resorts r ON e.resort_id = r.id";
    
    error_log("SQL Query: " . $query);
    
    $statsStmt = $pdo->query($query);
    
    if (!$statsStmt) {
        error_log("Query execution failed: " . implode(", ", $pdo->errorInfo()));
        throw new Exception('Failed to query database');
    }
    
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Log the result
    error_log("Query results: " . json_encode($stats));
    
    if (!$stats) {
        error_log("No stats found, using defaults");
        $stats = [
            'total' => 0,
            'new_count' => 0,
            'contacted_count' => 0,
            'converted_count' => 0,
            'closed_count' => 0
        ];
    } else {
        // Ensure all keys exist (handle any nulls from database)
        if (!isset($stats['total'])) $stats['total'] = 0;
        if (!isset($stats['closed_count'])) $stats['closed_count'] = 0;
        if (!isset($stats['new_count'])) $stats['new_count'] = 0;
        if (!isset($stats['contacted_count'])) $stats['contacted_count'] = 0;
        if (!isset($stats['converted_count'])) $stats['converted_count'] = 0;
        
        // Convert any null values to 0
        foreach ($stats as $key => $value) {
            if ($value === null) $stats[$key] = 0;
        }
    }
    
    // Add timestamp to help with caching issues
    $stats['timestamp'] = time();
    
    // Send response
    $response = [
        'success' => true,
        'counts' => $stats
    ];
    
    error_log("Sending response: " . json_encode($response));
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in get_enquiry_counts.php: " . $e->getMessage());
    
    // Send error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 