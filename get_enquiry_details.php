<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$pdo = require 'db.php';

// Validate input
if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing enquiry ID']);
    exit();
}

$enquiry_id = intval($_GET['id']);

try {
    // Get enquiry details
    $stmt = $pdo->prepare("SELECT e.*, r.is_partner 
                          FROM resort_enquiries e 
                          LEFT JOIN resorts r ON e.resort_id = r.id 
                          WHERE e.id = ?");
    $stmt->execute([$enquiry_id]);
    $enquiry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($enquiry) {
        header('Content-Type: application/json');
        echo json_encode($enquiry);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Enquiry not found']);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} 