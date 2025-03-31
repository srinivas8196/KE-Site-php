<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$pdo = require 'db.php';

// Validate input
if (!isset($_POST['enquiry_id']) || !isset($_POST['status'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$enquiry_id = intval($_POST['enquiry_id']);
$status = $_POST['status'];

// Validate status
$allowed_statuses = ['new', 'contacted', 'converted', 'closed'];
if (!in_array($status, $allowed_statuses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

// Update the status
try {
    $stmt = $pdo->prepare("UPDATE resort_enquiries SET status = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$status, $enquiry_id]);
    
    if ($result) {
        // Log the status change
        $user_id = $_SESSION['user']['id'];
        $username = $_SESSION['user']['username'];
        
        $logStmt = $pdo->prepare("INSERT INTO activity_log (user_id, activity_type, description, related_id) VALUES (?, 'status_change', ?, ?)");
        $logStmt->execute([
            $user_id, 
            "User {$username} changed resort enquiry #{$enquiry_id} status to {$status}", 
            $enquiry_id
        ]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} 