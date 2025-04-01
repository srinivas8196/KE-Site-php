<?php
// Start session before anything else
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set session parameters
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

// Set session save path
$sessionPath = dirname(__FILE__) . '/sessions';
session_save_path($sessionPath);

// Create debug log
$logFile = fopen('resort_enquiry_submission.log', 'a');
fwrite($logFile, "\n\n=== " . date('Y-m-d H:i:s') . " ===\n");
fwrite($logFile, "Form submission debug process started\n");
fwrite($logFile, "Session save path: " . session_save_path() . "\n");
fwrite($logFile, "Session ID: " . session_id() . "\n");
fwrite($logFile, "PHP Session Name: " . session_name() . "\n");

// Log request method and data
fwrite($logFile, "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n");
fwrite($logFile, "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n");
fwrite($logFile, "POST data: " . print_r($_POST, true) . "\n");
fwrite($logFile, "SESSION data: " . print_r($_SESSION, true) . "\n");

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fwrite($logFile, "Error: Form not submitted via POST method\n");
    fclose($logFile);
    die("This script should be accessed via POST method only.");
}

// Log all received fields
fwrite($logFile, "\nReceived fields:\n");
foreach ($_POST as $key => $value) {
    fwrite($logFile, "$key: $value\n");
}

// Basic validation - check if all required fields are present
$requiredFields = [
    'first_name', 'last_name', 'email', 'phone', 'dob', 'has_passport', 
    'resort_id', 'resort_name', 'destination_name', 'resort_code'
];

$missingFields = [];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    fwrite($logFile, "Missing required fields: " . implode(", ", $missingFields) . "\n");
    fclose($logFile);
    
    $_SESSION['error_message'] = "The following fields are required: " . implode(", ", $missingFields);
    header('Location: karma-royal-palms-form.php');
    exit;
}

// Log CSRF token status
fwrite($logFile, "\nCSRF Token Check:\n");
fwrite($logFile, "POST token: " . (isset($_POST['csrf_token']) ? $_POST['csrf_token'] : 'not set') . "\n");
fwrite($logFile, "Session token: " . (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : 'not set') . "\n");

// Check CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
    fwrite($logFile, "CSRF token missing in POST or SESSION\n");
    $_SESSION['error_message'] = "Security token missing. Please try again.";
    header('Location: karma-royal-palms-form.php');
    exit;
}

if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    fwrite($logFile, "CSRF token mismatch\n");
    fwrite($logFile, "POST token: " . $_POST['csrf_token'] . "\n");
    fwrite($logFile, "Session token: " . $_SESSION['csrf_token'] . "\n");
    $_SESSION['error_message'] = "Security validation failed. Please try again.";
    header('Location: karma-royal-palms-form.php');
    exit;
}

// All validation passed
fwrite($logFile, "\nValidation passed successfully!\n");
fwrite($logFile, "Form data is valid, processing submission\n");

// Set success message
$_SESSION['success_message'] = "Thank you! Your enquiry has been submitted successfully.";

// Close log file
fwrite($logFile, "\nRedirecting to form with success message\n");
fclose($logFile);

// Redirect back to the form
header('Location: karma-royal-palms-form.php');
exit;
?> 