<?php
session_start();

// Check authentication
if (!isset($_SESSION['user'])) {
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
    
    // Get the counts
    $statsStmt = $pdo->query("SELECT 
        COUNT(e.id) as total,
        SUM(CASE WHEN e.status = 'new' THEN 1 ELSE 0 END) as new_count,
        SUM(CASE WHEN e.status = 'contacted' THEN 1 ELSE 0 END) as contacted_count,
        SUM(CASE WHEN e.status = 'converted' THEN 1 ELSE 0 END) as converted_count,
        SUM(CASE WHEN e.status = 'closed' THEN 1 ELSE 0 END) as closed_count
        FROM resort_enquiries e
        LEFT JOIN resorts r ON e.resort_id = r.id
        WHERE 1=1");
    
    if (!$statsStmt) {
        throw new Exception('Failed to query database');
    }
    
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$stats) {
        $stats = [
            'total' => 0,
            'new_count' => 0,
            'contacted_count' => 0,
            'converted_count' => 0,
            'closed_count' => 0
        ];
    }
    
    // Send response
    echo json_encode([
        'success' => true,
        'counts' => $stats
    ]);
    
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