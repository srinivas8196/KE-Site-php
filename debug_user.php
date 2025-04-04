<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();

echo "<h1>User Session Debug</h1>";

// Check session data
echo "<h2>Current Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Include database connection
require 'db.php';

// Check database connection
echo "<h2>Database Connection:</h2>";
if ($conn) {
    echo "MySQLi connection established.<br>";
    
    // Check if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "Users table exists.<br>";
        
        // Get all users from the database
        echo "<h2>All Users in Database:</h2>";
        $result = $conn->query("SELECT id, username, email, user_type, phone_number FROM users LIMIT 10");
        
        if ($result->num_rows > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>User Type</th><th>Phone Number</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . ($row['id'] ?? 'NULL') . "</td>";
                echo "<td>" . ($row['username'] ?? 'NULL') . "</td>";
                echo "<td>" . ($row['email'] ?? 'NULL') . "</td>";
                echo "<td>" . ($row['user_type'] ?? 'NULL') . "</td>";
                echo "<td>" . ($row['phone_number'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "No users found in the database.<br>";
        }
        
        // Check for session user
        if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
            $user_id = $_SESSION['user']['id'];
            echo "<h2>Looking for User ID: {$user_id}</h2>";
            
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                echo "User found in database!<br>";
                echo "<pre>";
                print_r($user);
                echo "</pre>";
            } else {
                echo "<strong style='color:red'>User with ID {$user_id} not found in database.</strong><br>";
                
                // Fix the session to use an existing user
                echo "<h2>Fix Session</h2>";
                echo "<form method='post' action='fix_session.php'>";
                echo "Select a user to set as your session:<br>";
                $result = $conn->query("SELECT id, username FROM users");
                if ($result->num_rows > 0) {
                    echo "<select name='user_id'>";
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row['id'] . "'>" . $row['username'] . " (ID: " . $row['id'] . ")</option>";
                    }
                    echo "</select>";
                    echo "<input type='submit' value='Fix Session'>";
                } else {
                    echo "No users available to select.<br>";
                }
                echo "</form>";
            }
        } else {
            echo "<strong style='color:red'>No user ID in session.</strong><br>";
            
            // Create fix session form if no session
            echo "<h2>Create Session</h2>";
            echo "<form method='post' action='fix_session.php'>";
            echo "Select a user to set as your session:<br>";
            $result = $conn->query("SELECT id, username FROM users");
            if ($result->num_rows > 0) {
                echo "<select name='user_id'>";
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['id'] . "'>" . $row['username'] . " (ID: " . $row['id'] . ")</option>";
                }
                echo "</select>";
                echo "<input type='submit' value='Create Session'>";
            } else {
                echo "No users available to select.<br>";
            }
            echo "</form>";
        }
    } else {
        echo "<strong style='color:red'>Users table does not exist!</strong><br>";
    }
} else {
    echo "<strong style='color:red'>Database connection failed!</strong><br>";
}
?> 