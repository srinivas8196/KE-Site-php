<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set a CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo "<h1>Resort Form Endpoint Test</h1>";

// Check if process_resort_enquiry.php exists
if (file_exists('process_resort_enquiry.php')) {
    echo "<p style='color: green;'>✓ process_resort_enquiry.php file exists</p>";
} else {
    echo "<p style='color: red;'>✗ process_resort_enquiry.php file does not exist</p>";
}

// Check if required libraries exist
echo "<h2>Required Libraries:</h2>";
if (file_exists('vendor/autoload.php')) {
    echo "<p style='color: green;'>✓ vendor/autoload.php exists</p>";
} else {
    echo "<p style='color: red;'>✗ vendor/autoload.php not found</p>";
}

if (file_exists('leadsquared_helper.php')) {
    echo "<p style='color: green;'>✓ leadsquared_helper.php exists</p>";
} else {
    echo "<p style='color: red;'>✗ leadsquared_helper.php not found</p>";
}

// Check DB Connection
echo "<h2>Database Connection:</h2>";
try {
    $pdo = require 'db.php';
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check if tables exist
    $tables = ['resorts', 'resort_enquiries', 'destinations'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Table '$table' not found</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Environment Variables:</h2>";
if (file_exists('.env')) {
    echo "<p style='color: green;'>✓ .env file exists</p>";
    $env = parse_ini_file('.env');
    $requiredKeys = ['SMTP_HOST', 'SMTP_USERNAME', 'SMTP_PASSWORD', 'SMTP_PORT', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME'];
    foreach ($requiredKeys as $key) {
        if (isset($env[$key])) {
            echo "<p style='color: green;'>✓ $key is set</p>";
        } else {
            echo "<p style='color: red;'>✗ $key is not set in .env</p>";
        }
    }
} else {
    echo "<p style='color: red;'>✗ .env file not found</p>";
}

echo "<h2>Test Form:</h2>";
?>
<form method="POST" action="process_resort_enquiry.php">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="resort_id" value="1">
    <input type="hidden" name="resort_name" value="Test Resort">
    <input type="hidden" name="destination_name" value="Test Destination">
    <input type="hidden" name="resort_code" value="TEST">
    <input type="hidden" name="destination_id" value="1">
    
    <div style="margin-bottom: 10px;">
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" value="Test" required>
    </div>
    
    <div style="margin-bottom: 10px;">
        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" value="User" required>
    </div>
    
    <div style="margin-bottom: 10px;">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="test@example.com" required>
    </div>
    
    <div style="margin-bottom: 10px;">
        <label for="phone">Phone:</label>
        <input type="tel" id="phone" name="phone" value="+919876543210" required>
        <input type="hidden" name="full_phone" value="+919876543210">
    </div>
    
    <div style="margin-bottom: 10px;">
        <label for="dob">Date of Birth:</label>
        <input type="date" id="dob" name="dob" value="1980-01-01" required>
    </div>
    
    <div style="margin-bottom: 10px;">
        <label for="has_passport">Has Passport:</label>
        <select id="has_passport" name="has_passport" required>
            <option value="yes">Yes</option>
            <option value="no">No</option>
        </select>
    </div>
    
    <button type="submit">Submit Test Form</button>
</form> 