<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();

// Include database
require_once 'db.php';

// Set session to sriadmin user (ID 1)
$stmt = $conn->prepare("SELECT id, username, email, user_type, phone_number FROM users WHERE id = 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Set session data
    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'user_type' => $user['user_type'],
        'phone_number' => $user['phone_number'] ?? ''
    ];
    
    echo "<h1>Session Fixed!</h1>";
    echo "<p>You are now logged in as: " . htmlspecialchars($user['username']) . "</p>";
    echo "<p>User ID: " . $user['id'] . "</p>";
    echo "<p>Email: " . htmlspecialchars($user['email']) . "</p>";
    echo "<p>User Type: " . htmlspecialchars($user['user_type']) . "</p>";
    
    echo "<p><a href='profile.php'>Go to Profile Page</a></p>";
} else {
    echo "<h1>Error</h1>";
    echo "<p>Could not find the sriadmin user (ID 1) in the database.</p>";
}
?> 