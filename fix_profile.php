<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();

// Include database connection
$pdo = require 'db.php';

// Check if database connection is successful
if (!$pdo) {
    die("Database connection failed!");
}

// Check if users table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() === 0) {
        // Create users table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            user_type ENUM('super_admin', 'admin', 'campaign_manager') NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone_number VARCHAR(20) NOT NULL DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "Users table created successfully.<br>";
    } else {
        echo "Users table already exists.<br>";
        
        // Check for phone_number column
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone_number'");
            if ($stmt->rowCount() === 0) {
                // Add phone_number column if it doesn't exist
                $pdo->exec("ALTER TABLE users ADD COLUMN phone_number VARCHAR(20) NOT NULL DEFAULT ''");
                echo "Added phone_number column to users table.<br>";
            } else {
                echo "Phone number column already exists.<br>";
            }
        } catch (PDOException $e) {
            echo "Error checking phone_number column: " . $e->getMessage() . "<br>";
        }
        
        // Check if any users exist
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count == 0) {
            echo "No users found in the database.<br>";
        } else {
            echo "Found $count users in the database.<br>";
            
            // Display all users
            echo "<h3>Users in Database:</h3>";
            $stmt = $pdo->query("SELECT id, username, email, user_type, phone_number FROM users");
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>User Type</th><th>Phone Number</th></tr>";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['username'] . "</td>";
                echo "<td>" . $row['email'] . "</td>";
                echo "<td>" . $row['user_type'] . "</td>";
                echo "<td>" . ($row['phone_number'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR username = ?");
    $stmt->execute(['admin', 'sriadmin']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        // Create default admin user
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, user_type, email, phone_number) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', $password, 'super_admin', 'admin@example.com', '1234567890']);
        
        $admin_id = $pdo->lastInsertId();
        echo "Created default admin user with ID: $admin_id<br>";
        
        // Set up the session with this new user
        $_SESSION['user'] = [
            'id' => $admin_id,
            'username' => 'admin',
            'user_type' => 'super_admin',
            'email' => 'admin@example.com',
            'phone_number' => '1234567890'
        ];
        
        echo "Session initialized with new admin user.<br>";
    } else {
        // Make sure admin user has a phone number
        if (!isset($admin['phone_number']) || empty($admin['phone_number'])) {
            $stmt = $pdo->prepare("UPDATE users SET phone_number = ? WHERE id = ?");
            $phone = '1234567890';
            $stmt->execute([$phone, $admin['id']]);
            echo "Updated admin user with phone number.<br>";
            
            // Update admin data after change
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$admin['id']]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Use existing admin user
        echo "Found admin user: " . $admin['username'] . " (ID: " . $admin['id'] . ")<br>";
        
        // Set up the session with this existing user
        $_SESSION['user'] = [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'user_type' => $admin['user_type'],
            'email' => $admin['email'],
            'phone_number' => $admin['phone_number'] ?? ''
        ];
        
        echo "Session initialized with existing admin user.<br>";
    }
    
    // Display current session
    echo "<h3>Current Session:</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    echo "<p>You should now be able to access the <a href='profile.php'>profile page</a>.</p>";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?> 