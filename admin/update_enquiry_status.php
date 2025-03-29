<?php
require_once '../includes/db.php';
require_once 'includes/auth_check.php';

header('Content-Type: application/json');

if (!isset($_POST['enquiry_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$enquiry_id = $_POST['enquiry_id'];
$status = $_POST['status'];

// Validate status
$valid_statuses = ['new', 'contacted', 'converted', 'closed'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Update status
$stmt = $conn->prepare("UPDATE resort_enquiries SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $enquiry_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
$conn->close(); 