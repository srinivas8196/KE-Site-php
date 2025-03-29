<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'db.php';

header('Content-Type: application/json');

// Validate input
if (!isset($_POST['enquiry_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$enquiry_id = intval($_POST['enquiry_id']);
$status = $_POST['status'];

// Validate status
$valid_statuses = ['new', 'contacted', 'converted', 'closed'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

// Update status in database
$stmt = $conn->prepare("UPDATE resort_enquiries SET status = ? WHERE id = ?");
$stmt->bind_param('si', $status, $enquiry_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?> 