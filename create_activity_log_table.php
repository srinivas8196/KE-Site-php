<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check for session
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] !== 'super_admin') {
    echo '<div style="color: red; padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;">
            <h3>Access Denied</h3>
            <p>You must be logged in as a super admin to run this script.</p>
            <p><a href="login.php">Go to Login Page</a></p>
          </div>';
    exit;
}

// Include database connection
require_once 'db.php';

try {
    // Check if the table already exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'activity_log'")->rowCount() > 0;
    
    if ($tableExists) {
        echo '<div style="color: #155724; padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px;">
                <h3>Table Already Exists</h3>
                <p>The activity_log table already exists in the database.</p>
                <p><a href="dashboard.php">Return to Dashboard</a></p>
              </div>';
    } else {
        // Create the activity_log table
        $sql = "CREATE TABLE activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        
        echo '<div style="color: #155724; padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px;">
                <h3>Success!</h3>
                <p>The activity_log table has been created successfully.</p>
                <p><a href="dashboard.php">Return to Dashboard</a></p>
              </div>';
    }
} catch (PDOException $e) {
    echo '<div style="color: red; padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;">
            <h3>Error</h3>
            <p>Failed to create the activity_log table:</p>
            <pre>' . $e->getMessage() . '</pre>
            <p><a href="dashboard.php">Return to Dashboard</a></p>
          </div>';
}
?> 