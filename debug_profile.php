<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Include database connection
echo "<h2>Database Connection:</h2>";
$pdo = require 'db.php';
if ($pdo) {
    echo "PDO connection established.<br>";
    
    // Test users table
    try {
        // List users in the database
        $stmt = $pdo->query("SELECT id, username, email, phone_number FROM users LIMIT 10");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Users in Database:</h2>";
        echo "<pre>";
        print_r($users);
        echo "</pre>";
        
        // Check the session user ID
        if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
            $user_id = $_SESSION['user']['id'];
            echo "<h2>Looking for user with ID: {$user_id}</h2>";
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo "<pre>";
                print_r($user);
                echo "</pre>";
            } else {
                echo "User with ID {$user_id} not found in database.";
                
                // Check if ID exists in any form
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id LIKE ?");
                $stmt->execute(["%{$user_id}%"]);
                $similar_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($similar_users)) {
                    echo "<p>Found similar IDs:</p>";
                    echo "<pre>";
                    print_r($similar_users);
                    echo "</pre>";
                }
            }
        } else {
            echo "No user ID in session.";
        }
        
    } catch (PDOException $e) {
        echo "Database query error: " . $e->getMessage();
    }
} else {
    echo "Failed to establish PDO connection.<br>";
}

// Check for session user existence and table structure
echo "<h2>Database Structure:</h2>";
try {
    if ($pdo) {
        // Check users table structure
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Users table structure:</p>";
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    }
} catch (PDOException $e) {
    echo "Error checking table structure: " . $e->getMessage();
}
?> 