<?php
session_start();
require_once 'db.php';

// Only allow access from admin users
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
    exit;
}

// Include the LeadSquared helper
require_once 'leadsquared_helper.php';

// Load settings
$settings = [];
try {
    if (isset($pdo) && $pdo) {
        $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        if ($settingsStmt) {
            while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } else {
            error_log("Failed to query settings table");
        }
    } else {
        error_log("Database connection not available");
    }
} catch (Exception $e) {
    error_log("Error loading settings: " . $e->getMessage());
}

// Get credentials from settings
$accessKey = $settings['leadsquared_access_key'] ?? '';
$secretKey = $settings['leadsquared_secret_key'] ?? '';
$apiUrl = $settings['leadsquared_api_url'] ?? 'https://api-in21.leadsquared.com/v2/LeadManagement.svc';

// Allow overriding from POST
if (isset($_POST['access_key']) && !empty($_POST['access_key'])) {
    $accessKey = $_POST['access_key'];
}
if (isset($_POST['secret_key']) && !empty($_POST['secret_key'])) {
    $secretKey = $_POST['secret_key'];
}
if (isset($_POST['api_url']) && !empty($_POST['api_url'])) {
    $apiUrl = $_POST['api_url'];
}

// Create log file
$logFile = 'leadsquared_test_' . date('Y-m-d') . '.log';
file_put_contents($logFile, "\n\n=== " . date('Y-m-d H:i:s') . " === LeadSquared Test\n", FILE_APPEND);
file_put_contents($logFile, "Access Key: " . (empty($accessKey) ? "NOT SET" : substr($accessKey, 0, 3) . "...") . "\n", FILE_APPEND);
file_put_contents($logFile, "Secret Key: " . (empty($secretKey) ? "NOT SET" : substr($secretKey, 0, 3) . "...") . "\n", FILE_APPEND);
file_put_contents($logFile, "API URL: " . $apiUrl . "\n", FILE_APPEND);

// Check credentials
if (empty($accessKey) || empty($secretKey) || empty($apiUrl)) {
    $response = [
        'status' => 'error',
        'message' => 'LeadSquared credentials are not configured properly'
    ];
    
    file_put_contents($logFile, "Error: Credentials not fully configured\n", FILE_APPEND);
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Try a simple API call to test the credentials
$testUrl = $apiUrl . "/User.GetAccessible?accessKey=" . urlencode($accessKey) . "&secretKey=" . urlencode($secretKey);
file_put_contents($logFile, "Making test request to: " . $testUrl . "\n", FILE_APPEND);

// Initialize cURL session for test
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8', 'Accept: application/json']);

// Execute cURL session for test
$testResponse = curl_exec($ch);
$testHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$testCurlError = curl_error($ch);
curl_close($ch);

// Log raw response
file_put_contents($logFile, "HTTP Response Code: " . $testHttpCode . "\n", FILE_APPEND);
file_put_contents($logFile, "Raw Response: " . $testResponse . "\n", FILE_APPEND);
if (!empty($testCurlError)) {
    file_put_contents($logFile, "cURL Error: " . $testCurlError . "\n", FILE_APPEND);
}

// Process the response
$success = false;
$message = '';

if ($testHttpCode == 200) {
    $success = true;
    $message = "Connection successful! LeadSquared credentials are valid.";
    file_put_contents($logFile, "Success: " . $message . "\n", FILE_APPEND);
    
    // Create a test lead if requested
    if (isset($_POST['test_create']) && $_POST['test_create'] == 'true') {
        $testData = [
            'FirstName' => 'Test',
            'LastName' => 'User',
            'EmailAddress' => 'test_' . time() . '@example.com',
            'Phone' => '+919876543210',
            'Source' => 'Test',
            'mx_Source' => 'Credential Test',
            'ProspectStage' => 'Test'
        ];
        
        file_put_contents($logFile, "Attempting to create test lead\n", FILE_APPEND);
        
        $createResponse = createLeadSquaredLead($testData, $accessKey, $secretKey, $apiUrl);
        
        file_put_contents($logFile, "Create response: " . json_encode($createResponse) . "\n", FILE_APPEND);
        
        $message .= " Also tested lead creation: " . ($createResponse['status'] === 'success' ? 'Success' : 'Failed');
    }
} else {
    // Try to parse the error message
    $errorData = json_decode($testResponse, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($errorData['ExceptionMessage'])) {
        $message = "Connection failed: " . $errorData['ExceptionMessage'];
    } else {
        $message = "Connection failed with HTTP code $testHttpCode. Check your credentials and API URL.";
    }
    file_put_contents($logFile, "Error: " . $message . "\n", FILE_APPEND);
}

// Update settings in database if requested
if (isset($_POST['update_settings']) && $_POST['update_settings'] == 'true') {
    try {
        if (isset($pdo) && $pdo) {
            // Update access key
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'leadsquared_access_key'");
            if ($stmt) {
                $stmt->execute([$accessKey]);
            }
            
            // Update secret key
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'leadsquared_secret_key'");
            if ($stmt) {
                $stmt->execute([$secretKey]);
            }
            
            // Update API URL
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'leadsquared_api_url'");
            if ($stmt) {
                $stmt->execute([$apiUrl]);
            }
            
            $message .= " Settings have been updated in the database.";
            file_put_contents($logFile, "Settings updated in database\n", FILE_APPEND);
        } else {
            $message .= " Could not update settings: Database connection not available.";
            file_put_contents($logFile, "Error: Database connection not available\n", FILE_APPEND);
        }
    } catch (Exception $e) {
        file_put_contents($logFile, "Error updating settings: " . $e->getMessage() . "\n", FILE_APPEND);
        $message .= " However, there was an error updating the settings: " . $e->getMessage();
    }
}

// Return response
header('Content-Type: application/json');
echo json_encode([
    'status' => $success ? 'success' : 'error',
    'message' => $message,
    'http_code' => $testHttpCode,
    'credentials' => [
        'access_key_set' => !empty($accessKey),
        'secret_key_set' => !empty($secretKey),
        'api_url' => $apiUrl
    ]
]);
?>
 
 