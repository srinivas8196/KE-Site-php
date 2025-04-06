<?php
// Start session
session_start();

// Set a test session value
$_SESSION['test'] = 'Session test value';

echo "<h1>PHP Session Diagnostics</h1>";

// Session status
echo "<h2>Session Status</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? "Active" : "Not Active") . "<br>";
echo "Session Save Path: " . session_save_path() . "<br>";

// Session cookie parameters
echo "<h2>Session Cookie Parameters</h2>";
echo "<pre>";
print_r(session_get_cookie_params());
echo "</pre>";

// PHP Session Configuration
echo "<h2>PHP Session Configuration</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>session.save_handler</td><td>" . ini_get('session.save_handler') . "</td></tr>";
echo "<tr><td>session.use_cookies</td><td>" . ini_get('session.use_cookies') . "</td></tr>";
echo "<tr><td>session.use_only_cookies</td><td>" . ini_get('session.use_only_cookies') . "</td></tr>";
echo "<tr><td>session.use_strict_mode</td><td>" . ini_get('session.use_strict_mode') . "</td></tr>";
echo "<tr><td>session.use_trans_sid</td><td>" . ini_get('session.use_trans_sid') . "</td></tr>";
echo "<tr><td>session.cookie_lifetime</td><td>" . ini_get('session.cookie_lifetime') . "</td></tr>";
echo "<tr><td>session.cookie_path</td><td>" . ini_get('session.cookie_path') . "</td></tr>";
echo "<tr><td>session.cookie_domain</td><td>" . ini_get('session.cookie_domain') . "</td></tr>";
echo "<tr><td>session.cookie_secure</td><td>" . ini_get('session.cookie_secure') . "</td></tr>";
echo "<tr><td>session.cookie_httponly</td><td>" . ini_get('session.cookie_httponly') . "</td></tr>";
echo "<tr><td>session.cookie_samesite</td><td>" . ini_get('session.cookie_samesite') . "</td></tr>";
echo "<tr><td>session.gc_maxlifetime</td><td>" . ini_get('session.gc_maxlifetime') . "</td></tr>";
echo "</table>";

// Current Session Data
echo "<h2>Current Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Cookie data
echo "<h2>Cookie Data</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

// Server Information
echo "<h2>Server Information</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Web Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Server Name: " . $_SERVER['SERVER_NAME'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";

// Test session persistence
echo "<h2>Session Persistence Test</h2>";
echo "<a href='check-session.php?test=" . time() . "'>Reload page to test session persistence</a><br>";
if (isset($_GET['test'])) {
    if (isset($_SESSION['last_test'])) {
        echo "Previous test timestamp: " . $_SESSION['last_test'] . "<br>";
        echo "Session is " . (($_SESSION['last_test'] !== $_GET['test']) ? "persisting correctly" : "not persisting") . "<br>";
    }
    $_SESSION['last_test'] = $_GET['test'];
    echo "Current test timestamp: " . $_GET['test'] . "<br>";
}

// Test Database Connection
echo "<h2>Database Connection Test</h2>";
try {
    require_once 'db.php';
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "Database connection: " . ($result['test'] == 1 ? "<span style='color:green'>SUCCESS</span>" : "<span style='color:red'>FAILED</span>") . "<br>";
} catch (Exception $e) {
    echo "<span style='color:red'>Database error: " . $e->getMessage() . "</span><br>";
}

// Write file permissions test
echo "<h2>File Permissions Test</h2>";
$session_dir = session_save_path();
if (empty($session_dir)) {
    $session_dir = sys_get_temp_dir();
}
echo "Session directory: $session_dir<br>";
echo "Session directory is writable: " . (is_writable($session_dir) ? "<span style='color:green'>Yes</span>" : "<span style='color:red'>No</span>") . "<br>";
?>

<hr>
<p><a href="test-login.php">Go to Test Login Page</a> | <a href="login.php">Go to Regular Login Page</a></p> 