<?php
// Force session start with custom parameters
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);

// Start session
session_start();

// Initialize message array
$messages = [];

// Check if we're resetting 
if (isset($_GET['reset'])) {
    session_unset();
    session_destroy();
    session_start();
    $messages[] = "Session data cleared.";
}

// Set new test data if requested
if (isset($_GET['set'])) {
    $_SESSION['test_data'] = time();
    $_SESSION['test_user_id'] = 999;
    $messages[] = "Test session data set.";
}

// Force cookie parameters if requested
if (isset($_GET['fixcookie'])) {
    // Delete existing session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-42000, '/');
    }
    
    // Set better cookie parameters
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Regenerate session ID
    session_regenerate_id(true);
    
    $messages[] = "Session cookie parameters fixed.";
}

// Fix .htaccess if requested
if (isset($_GET['fixhtaccess'])) {
    $htaccess = <<<EOT
RewriteEngine On
RewriteBase /KE-Site-php/

# Exclude actual files and directories from being rewritten
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Redirect lowercase blogs.php to Blogs.php
RewriteRule ^blogs\.php$ Blogs.php [L,R=301]

# Handle all blog-related URLs
RewriteRule ^blogs/(.*)$ fix-blogs.php [L,QSA]

# Ensure consistent case for Blogs.php
RewriteRule ^[Bb]logs\.php$ Blogs.php [L,R=301]

# Add .php extension for clean URLs 
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}.php -f
RewriteRule ^([^.]+)$ $1.php [L]

# Deny directory listing
Options -Indexes
EOT;
    
    file_put_contents('.htaccess', $htaccess);
    $messages[] = ".htaccess file has been reset with safer rules.";
}

// Test the session
$session_working = false;
if (isset($_SESSION['test_timestamp'])) {
    $old_time = $_SESSION['test_timestamp'];
    $_SESSION['test_timestamp'] = time();
    
    if ($old_time < time()) {
        $session_working = true;
        $messages[] = "Session is working correctly! Previous timestamp was stored.";
    }
} else {
    $_SESSION['test_timestamp'] = time();
    $messages[] = "First visit or session not persisting. Refresh to test if session works.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Fix Utility</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        .message {
            background-color: #f8f9fa;
            border-left: 4px solid #4CAF50;
            padding: 10px 15px;
            margin-bottom: 10px;
        }
        .action-btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .action-btn:hover {
            background-color: #45a049;
        }
        .danger-btn {
            background-color: #f44336;
        }
        .danger-btn:hover {
            background-color: #d32f2f;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border: 1px solid #ddd;
            overflow: auto;
        }
        h1, h2 {
            color: #333;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .status.good {
            background-color: #dff0d8;
            border: 1px solid #d0e9c6;
        }
        .status.bad {
            background-color: #f2dede;
            border: 1px solid #ebcccc;
        }
    </style>
</head>
<body>
    <h1>PHP Session Fix Utility</h1>
    
    <?php if (!empty($messages)): ?>
        <div class="messages">
            <?php foreach($messages as $msg): ?>
                <div class="message"><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="status <?php echo $session_working ? 'good' : 'bad'; ?>">
        Session status: <?php echo $session_working ? 'Working correctly' : 'Not confirmed working yet'; ?>
    </div>
    
    <h2>Current Session Data</h2>
    <pre><?php print_r($_SESSION); ?></pre>
    
    <h2>Session Info</h2>
    <p>Session ID: <?php echo session_id(); ?></p>
    <p>Session save path: <?php echo session_save_path(); ?></p>
    <p>Session cookie params:</p>
    <pre><?php print_r(session_get_cookie_params()); ?></pre>
    
    <h2>Fix Options</h2>
    <div class="actions">
        <a href="fix-session.php?set=1" class="action-btn">Set Test Session Data</a>
        <a href="fix-session.php?fixcookie=1" class="action-btn">Fix Session Cookie</a>
        <a href="fix-session.php?fixhtaccess=1" class="action-btn">Fix .htaccess File</a>
        <a href="fix-session.php?reset=1" class="action-btn danger-btn">Clear Session Data</a>
    </div>
    
    <h2>Test Login</h2>
    <p>
        <a href="test-login.php" class="action-btn">Go to Test Login</a>
        <a href="login.php" class="action-btn">Go to Real Login</a>
    </p>
    
    <h2>Cookies</h2>
    <pre><?php print_r($_COOKIE); ?></pre>
</body>
</html> 