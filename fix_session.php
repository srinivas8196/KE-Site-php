<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include database connection
require 'db.php';

// Check if user ID is provided
if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    
    // Get the user data
    $stmt = $conn->prepare("SELECT id, username, email, user_type, phone_number FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
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
            'phone_number' => $user['phone_number']
        ];
        
        echo "<h1>Session Fixed</h1>";
        echo "<p>Your session has been updated with user: <strong>" . htmlspecialchars($user['username']) . "</strong></p>";
        echo "<p>User ID: " . $user['id'] . "</p>";
        echo "<p>Email: " . htmlspecialchars($user['email']) . "</p>";
        echo "<p>User Type: " . htmlspecialchars($user['user_type']) . "</p>";
        echo "<p>Phone Number: " . htmlspecialchars($user['phone_number'] ?? '') . "</p>";
        
        echo "<h2>Current Session:</h2>";
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
        
        echo "<p><a href='profile.php'>Go to Profile Page</a></p>";
    } else {
        echo "<h1>Error</h1>";
        echo "<p>User with ID " . $user_id . " not found in database.</p>";
        echo "<p><a href='debug_user.php'>Go back to debug page</a></p>";
    }
} else {
    echo "<h1>Error</h1>";
    echo "<p>No user ID provided.</p>";
    echo "<p><a href='debug_user.php'>Go back to debug page</a></p>";
}
?> 