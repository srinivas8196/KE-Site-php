<?php
session_start();
require 'db.php';

// Create the activity_log table if it doesn't exist
$pdo->exec('CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    user_id INT NOT NULL, 
    action VARCHAR(100) NOT NULL, 
    details TEXT, 
    ip_address VARCHAR(45), 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)');

// Add a test activity (resort update)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Use 1 as default if not logged in
$action = 'test_activity';
$details = 'This is a test activity log entry from test_activity_log.php';
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

$stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
$success = $stmt->execute([$user_id, $action, $details, $ip]);

if ($success) {
    echo "Test activity log entry added successfully!<br>";
    echo "Please check your dashboard to see if it appears in the Recent Activities section.";
    echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
} else {
    echo "Failed to add test activity log entry. Error: " . print_r($stmt->errorInfo(), true);
}
?> 