<?php
// Start session
session_start();

// Clear session if requested
if (isset($_GET['clear'])) {
    session_unset();
    session_destroy();
    session_start();
    echo "Session cleared.<br>";
}

// Debug section
echo "<h2>Debug Information</h2>";
echo "Current session data:<pre>";
print_r($_SESSION);
echo "</pre>";

// Include database
require_once 'db.php';
require_once 'includes/functions.php';

// Try a sample database query to confirm connection
try {
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "Database connection test: " . ($result['test'] == 1 ? "SUCCESS" : "FAILED") . "<br>";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            // Query the database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Display the user data for debugging
            echo "<pre>User data from database: ";
            print_r($user);
            echo "</pre>";
            
            // Check password
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                
                echo "<div style='color:green'>Login successful!</div>";
                echo "<pre>Session after login: ";
                print_r($_SESSION);
                echo "</pre>";
                
                echo "<a href='dashboard.php'>Go to Dashboard</a> | ";
                echo "<a href='test-login.php?clear=1'>Clear Session & Test Again</a>";
            } else {
                echo "<div style='color:red'>Invalid username or password</div>";
                if ($user) {
                    echo "User found but password did not match.<br>";
                } else {
                    echo "User not found.<br>";
                }
            }
        } catch (PDOException $e) {
            echo "<div style='color:red'>Database error: " . $e->getMessage() . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border: 1px solid #ddd;
            overflow: auto;
        }
        form {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }
        label, input {
            display: block;
            margin-bottom: 10px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 8px;
        }
        button {
            padding: 10px 15px;
            background: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Test Login Page</h1>
    <p>This page tests login functionality without redirects.</p>
    
    <form method="POST" action="test-login.php">
        <div>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit">Test Login</button>
    </form>
    
    <div style="margin-top: 20px;">
        <a href="test-login.php?clear=1">Clear Session</a> | 
        <a href="login.php">Go to Regular Login</a>
    </div>
</body>
</html> 