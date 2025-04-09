<?php
// Set session parameters BEFORE session_start
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

// Create sessions directory if it doesn't exist
if (!file_exists(dirname(__FILE__) . '/sessions')) {
    mkdir(dirname(__FILE__) . '/sessions', 0777, true);
}

/**
 * Get country name from country code
 * 
 * @param string $countryCode 2-letter ISO country code
 * @return string Country name
 */
function getCountryName($countryCode) {
    $countries = [
        'AU' => 'Australia',
        'GB' => 'United Kingdom',
        'UK' => 'United Kingdom',
        'US' => 'United States',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'NZ' => 'New Zealand',
        'SG' => 'Singapore',
        'TH' => 'Thailand',
        'MY' => 'Malaysia',
        'PH' => 'Philippines',
    ];
    
    return $countries[$countryCode] ?? 'Unknown';
}

// Set session save path BEFORE session_start
$sessionPath = dirname(__FILE__) . '/sessions';
session_save_path($sessionPath);

// Start session
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug log
$logFile = fopen('resort_enquiry_production.log', 'a');
fwrite($logFile, "\n\n=== " . date('Y-m-d H:i:s') . " ===\n");
fwrite($logFile, "Form submission process started\n");
fwrite($logFile, "Session ID: " . session_id() . "\n");
fwrite($logFile, "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n");
fwrite($logFile, "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n");
fwrite($logFile, "POST data: " . print_r($_POST, true) . "\n");
fwrite($logFile, "SESSION data: " . print_r($_SESSION, true) . "\n");

// Include required libraries and helpers
$pdo = require 'db.php';
// Load PHPMailer directly instead of using autoload
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';
require 'vendor/autoload.php'; // Keep autoload for other dependencies
require 'leadsquared_helper.php'; // Add LeadSquared helper functions
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;

// Get current settings
$settings = [];
try {
    $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    fwrite($logFile, "Settings loaded from database\n");
    
    // Print all available settings for debugging
    fwrite($logFile, "All available settings from database:\n");
    foreach ($settings as $key => $value) {
        if (strpos($key, 'password') !== false || strpos($key, 'key') !== false) {
            // Mask sensitive values
            fwrite($logFile, "$key: " . (empty($value) ? "EMPTY" : "********") . "\n");
        } else {
            fwrite($logFile, "$key: $value\n");
        }
    }
} catch (Exception $e) {
    fwrite($logFile, "Error loading settings from database: " . $e->getMessage() . "\n");
    error_log("Error loading settings from database: " . $e->getMessage());
}

// Debug log settings
fwrite($logFile, "Settings loaded:\n");
fwrite($logFile, "SMTP Host: " . ($settings['smtp_host'] ?? 'not set') . "\n");
fwrite($logFile, "SMTP From Email: " . ($settings['smtp_from_email'] ?? 'not set') . "\n");
fwrite($logFile, "SMTP From Name: " . ($settings['smtp_from_name'] ?? 'not set') . "\n");
fwrite($logFile, "Admin Email: " . ($settings['admin_email'] ?? 'not set') . "\n");

// Set default values if settings are not found
$smtpHost = $settings['smtp_host'] ?? 'smtp.gmail.com';
$smtpPort = $settings['smtp_port'] ?? 465;
$smtpUsername = $settings['smtp_username'] ?? 'res@karmaexperience.com';
$smtpPassword = $settings['smtp_password'] ?? '';
$smtpEncryption = $settings['smtp_encryption'] ?? 'ssl';
$fromEmail = $settings['smtp_from_email'] ?? 'res@karmaexperience.com';
$fromName = $settings['smtp_from_name'] ?? 'Karma Experience';
$adminEmail = $settings['admin_email'] ?? 'webdev@karmaexperience.in';

// LeadSquared credentials
$leadSquaredAccessKey = $settings['leadsquared_access_key'] ?? '';
$leadSquaredSecretKey = $settings['leadsquared_secret_key'] ?? '';
$leadSquaredApiUrl = $settings['leadsquared_api_url'] ?? '';

// Debug log SMTP and LeadSquared settings
fwrite($logFile, "SMTP Settings:\n");
fwrite($logFile, "Host: $smtpHost\n");
fwrite($logFile, "Port: $smtpPort\n");
fwrite($logFile, "Username: $smtpUsername\n");
fwrite($logFile, "Encryption: $smtpEncryption\n");
fwrite($logFile, "From Email: $fromEmail\n");
fwrite($logFile, "From Name: $fromName\n");
fwrite($logFile, "Admin Email: $adminEmail\n");
fwrite($logFile, "\nLeadSquared Settings:\n");
fwrite($logFile, "Access Key: " . (empty($leadSquaredAccessKey) ? 'not set' : substr($leadSquaredAccessKey, 0, 5) . '...') . "\n");
fwrite($logFile, "Secret Key: " . (empty($leadSquaredSecretKey) ? 'not set' : substr($leadSquaredSecretKey, 0, 5) . '...') . "\n");
fwrite($logFile, "API URL: " . ($leadSquaredApiUrl ?? 'not set') . "\n");

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fwrite($logFile, "Error: Not submitted via POST\n");
    fclose($logFile);
    header('Location: index.php');
    exit;
}

// Check CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    fwrite($logFile, "CSRF token mismatch or missing\n");
    fwrite($logFile, "Posted token: " . ($_POST['csrf_token'] ?? 'not set') . "\n");
    fwrite($logFile, "Session token: " . ($_SESSION['csrf_token'] ?? 'not set') . "\n");
    fclose($logFile);
    
    $_SESSION['error_message'] = "Security validation failed. Please try again.";
    
    if (isset($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        header('Location: index.php');
    }
    exit;
}

// Get form data - using snake_case field names
$firstName = $_POST['first_name'] ?? '';
$lastName = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';

// Handle phone number properly - use the full international format from intl-tel-input
$phone = '';
if (!empty($_POST['full_phone'])) {
    // Use the hidden full_phone field that contains the complete international number
    $phone = $_POST['full_phone'];
    fwrite($logFile, "Using full_phone with country code: $phone\n");
} elseif (!empty($_POST['phone'])) {
    // Fallback to basic phone field if full_phone is not available
    $phone = $_POST['phone'];
    fwrite($logFile, "Using basic phone field: $phone\n");
} else {
    fwrite($logFile, "No phone number provided\n");
}

$dob = $_POST['dob'] ?? '';
$hasPassport = $_POST['has_passport'] ?? '';
$additionalRequirements = $_POST['additional_requirements'] ?? '';
$resortName = $_POST['resort_name'] ?? '';
$destinationName = $_POST['destination_name'] ?? '';
$resortCode = $_POST['resort_code'] ?? '';
$resortId = $_POST['resort_id'] ?? '';
$destinationId = $_POST['destination_id'] ?? '';
$communicationConsent = isset($_POST['communication_consent']) ? 1 : 0;
$dndConsent = isset($_POST['dnd_consent']) ? 1 : 0;

// Basic validation
if (empty($firstName) || empty($lastName) || empty($email) || empty($phone)) {
    fwrite($logFile, "Missing required fields\n");
    fclose($logFile);
    $_SESSION['error_message'] = 'Please fill in all required fields.';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Validate consent checkboxes
if (!$communicationConsent || !$dndConsent) {
    fwrite($logFile, "Consent checkboxes not checked\n");
    fclose($logFile);
    $_SESSION['error_message'] = 'Please agree to the consent terms to proceed.';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Validate destination_id (to avoid foreign key constraint error)
if (!empty($destinationId)) {
    $checkDestStmt = $pdo->prepare("SELECT id FROM destinations WHERE id = ?");
    $checkDestStmt->execute([$destinationId]);
    $destExists = $checkDestStmt->fetchColumn();
    
    if (!$destExists) {
        // If destination doesn't exist, try to get it from the resort
        if (!empty($resortId)) {
            $checkResortStmt = $pdo->prepare("SELECT destination_id FROM resorts WHERE id = ?");
            $checkResortStmt->execute([$resortId]);
            $destinationId = $checkResortStmt->fetchColumn();
        }
        
        // If still no valid destination_id, log error and set to NULL
        if (empty($destinationId)) {
            fwrite($logFile, "Warning: Invalid destination_id submitted. Setting to NULL\n");
            error_log("Warning: Invalid destination_id submitted in resort enquiry form. Setting to NULL.");
            $destinationId = NULL;
        }
    }
}

// Get country from phone number
$phoneUtil = PhoneNumberUtil::getInstance();
try {
    // Parse the international phone number to get the country code
    $phoneNumber = $phoneUtil->parse($phone);
    $countryCode = $phoneUtil->getRegionCodeForNumber($phoneNumber);
    $nationalNumber = $phoneUtil->getNationalSignificantNumber($phoneNumber);
    $dialCode = '+' . $phoneNumber->getCountryCode();
    
    // Ensure UK country code is correctly mapped (GB is the ISO country code for United Kingdom)
    if ($dialCode === '+44' && empty($countryCode)) {
        $countryCode = 'GB';
    }
    
    // Debug the raw phone number details
    fwrite($logFile, "Raw phone object details: " . print_r($phoneNumber, true) . "\n");
    
    // Format phone with hyphen between country code and national number
    $formattedPhone = $dialCode . '-' . $nationalNumber;
    
    // Log the extracted information
    fwrite($logFile, "Phone parsing successful:\n");
    fwrite($logFile, "  - Original phone: $phone\n");
    fwrite($logFile, "  - Phone country code number: " . $phoneNumber->getCountryCode() . "\n");
    fwrite($logFile, "  - Country code: $countryCode\n");
    fwrite($logFile, "  - Dial code: $dialCode\n");
    fwrite($logFile, "  - National number: $nationalNumber\n");
    fwrite($logFile, "  - Formatted phone: $formattedPhone\n");
    
    // Hardcode country code if we can determine it from the dial code
    if (empty($countryCode) || $countryCode == 'UNKNOWN') {
        // Map common dial codes to country codes
        $dialCodeMap = [
            '+1' => 'US', // United States
            '+44' => 'GB', // United Kingdom
            '+61' => 'AU', // Australia
            '+91' => 'IN', // India
            '+62' => 'ID', // Indonesia
            '+64' => 'NZ', // New Zealand
            '+65' => 'SG', // Singapore
            '+66' => 'TH', // Thailand
            '+60' => 'MY', // Malaysia
            '+63' => 'PH', // Philippines
        ];
        
        if (isset($dialCodeMap[$dialCode])) {
            $countryCode = $dialCodeMap[$dialCode];
            fwrite($logFile, "  - Country code determined from dial code map: $countryCode\n");
        }
    }
} catch (NumberParseException $e) {
    // If parsing fails, try to extract country code from the phone number manually
    $countryCode = 'UNKNOWN';
    $dialCode = '';
    $nationalNumber = $phone;
    $formattedPhone = $phone; // Use original phone as fallback
    
    fwrite($logFile, "Phone parsing error: " . $e->getMessage() . "\n");
    
    // Try to extract the country code from the phone number format
    if (preg_match('/^\+(\d{1,3})/', $phone, $matches)) {
        $dialNum = $matches[1];
        $dialCode = '+' . $dialNum;
        
        // Map dial codes to country codes for common cases
        $dialCodeMap = [
            '1' => 'US',
            '44' => 'GB',
            '61' => 'AU',
            '91' => 'IN',
            '62' => 'ID',
            '64' => 'NZ',
            '65' => 'SG',
            '66' => 'TH',
            '60' => 'MY',
            '63' => 'PH',
        ];
        
        if (isset($dialCodeMap[$dialNum])) {
            $countryCode = $dialCodeMap[$dialNum];
            // Try to extract the national number
            $nationalNumber = preg_replace('/^\+' . $dialNum . '/', '', $phone);
            $formattedPhone = $dialCode . '-' . $nationalNumber;
            
            fwrite($logFile, "Extracted country code from dial code: $countryCode\n");
            fwrite($logFile, "Extracted dial code: $dialCode\n");
            fwrite($logFile, "Extracted national number: $nationalNumber\n");
            fwrite($logFile, "Formatted phone: $formattedPhone\n");
        } else {
            fwrite($logFile, "Could not map dial code to country code\n");
        }
    } else {
        fwrite($logFile, "Could not extract dial code from phone number\n");
    }
}

// Check if resort is a partner hotel
$stmt = $pdo->prepare("SELECT is_partner FROM resorts WHERE id = ?");
$stmt->execute([$resortId]);
$resortDetails = $stmt->fetch(PDO::FETCH_ASSOC);
$isPartner = $resortDetails['is_partner'] ?? 0;

// Generate LeadSquared details based on country code
$leadSource = 'Web Enquiry';
$leadBrand = 'Timeshare Marketing';

// Set the lead sub brand based on destination
if (strtolower($destinationName) === 'india') {
    $leadSubBrand = 'Karma Experience IN';
} else {
    $leadSubBrand = 'Karma Experience INT';
}

// Generate Lead Source Description
$countryPrefix = 'ROW'; // Default is Rest of World

// Log the country code being used for lead source description
fwrite($logFile, "Using country code for lead source: $countryCode\n");

// Map known country codes to their prefix in lead source description
if ($countryCode == 'AU') {
    $countryPrefix = 'AU';
} elseif ($countryCode == 'ID') {
    $countryPrefix = 'ID';
} elseif ($countryCode == 'IN') {
    $countryPrefix = 'IND';
} elseif ($countryCode == 'GB' || $countryCode == 'UK') { // Handle both GB and UK
    $countryPrefix = 'UK';
}

fwrite($logFile, "Selected country prefix for lead source: $countryPrefix\n");

// Format: [COUNTRY_PREFIX] | Web Enquiry | [KEPH (if partner)] resort code
$leadSourceDescription = $countryPrefix . ' | ' . $leadSource . ' | ';
if ($isPartner) {
    $leadSourceDescription .= 'KEPH ';
}
$leadSourceDescription .= $resortCode;

// Lead Location is Resort Name
$leadLocation = $resortName;
$latestLeadSource = $leadSource;

try {
    // Prepare the SQL statement with proper NULL handling for destination_id
    $stmt = $pdo->prepare("INSERT INTO resort_enquiries 
        (resort_id, destination_id, first_name, last_name, email, phone, date_of_birth, has_passport, 
        additional_requirements, resort_name, destination_name, resort_code, status, country_code, lead_source, lead_brand, 
        lead_sub_brand, lead_source_description, lead_location, communication_consent, dnd_consent) 
        VALUES (?, " . (is_null($destinationId) ? "NULL" : "?") . ", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Build parameters array based on destination_id being NULL or not
    $params = [$resortId];
    if (!is_null($destinationId)) {
        $params[] = $destinationId;
    }
    $params = array_merge($params, [
        $firstName,
        $lastName,
        $email,
        $formattedPhone,
        $dob,
        $hasPassport,
        $additionalRequirements,
        $resortName,
        $destinationName,
        $resortCode,
        $countryCode,
        $leadSource,
        $leadBrand,
        $leadSubBrand,
        $leadSourceDescription,
        $leadLocation,
        $communicationConsent,
        $dndConsent
    ]);
    
    // Log the database operation
    fwrite($logFile, "Executing database insert\n");
    
    // Execute with proper parameters
    $stmt->execute($params);
    
    // Log success
    fwrite($logFile, "Database insert successful\n");

    // Get the last inserted ID
    $enquiryId = $pdo->lastInsertId();

    // Process LeadSquared integration
    $leadsquaredEnabled = getenv('LEADSQUARED_ENABLED');
    $hasLeadSquaredCredentials = !empty($leadSquaredAccessKey) && !empty($leadSquaredSecretKey) && !empty($leadSquaredApiUrl);
    
    // Log the LeadSquared status
    fwrite($logFile, "LeadSquared integration status:\n");
    fwrite($logFile, "LEADSQUARED_ENABLED env variable: " . ($leadsquaredEnabled ?: 'not set') . "\n");
    fwrite($logFile, "Has credentials: " . ($hasLeadSquaredCredentials ? 'Yes' : 'No') . "\n");
    fwrite($logFile, "Access key: " . (!empty($leadSquaredAccessKey) ? 'Set (' . substr($leadSquaredAccessKey, 0, 3) . '...)' : 'Not set') . "\n");
    fwrite($logFile, "Secret key: " . (!empty($leadSquaredSecretKey) ? 'Set (' . substr($leadSquaredSecretKey, 0, 3) . '...)' : 'Not set') . "\n");
    fwrite($logFile, "API URL: " . (!empty($leadSquaredApiUrl) ? $leadSquaredApiUrl : 'Not set') . "\n");
    
    // Determine if we should proceed with LeadSquared integration
    $proceedWithLeadSquared = $hasLeadSquaredCredentials && ($leadsquaredEnabled === 'true' || $leadsquaredEnabled === '1' || empty($leadsquaredEnabled));
    
    if ($proceedWithLeadSquared) {
        fwrite($logFile, "Proceeding with LeadSquared integration\n");
        error_log("LeadSquared integration is enabled. Starting LeadSquared lead creation process.");
        
        // Include LeadSquared helper
        require_once 'leadsquared_helper.php';
        
        try {
            // Prepare LeadSquared data format
            $leadData = [
                'FirstName' => $firstName,
                'LastName' => $lastName,
                'EmailAddress' => $email,
                'Phone' => $formattedPhone,
                'mx_Phone_Country_Code' => $dialCode, // Store the dial code separately
                'mx_Phone_Without_Code' => $nationalNumber, // Store the national number separately
                'mx_Resort' => $resortName,
                'mx_Resort_Name' => $resortName,
                'mx_date_of_birth' => $dob,
                'mx_Flexibility' => $additionalRequirements,
                'mx_Passport' => $hasPassport,
                'mx_Number_of_Adults' => $isPartner ? 1 : 0,
                'mx_Number_of_Children' => $isPartner ? 0 : 1,
                'mx_Additional_Information' => $resortCode,
                'mx_Source' => $leadSource,
                'mx_Form_Type' => 'Resort Enquiry',
                'Source' => $leadSource,
                'mx_Latest_Lead_Source' => $leadSource,
                'mx_Lead_Brand' => $leadBrand,
                'mx_Lead_Sub_Brand' => $leadSubBrand,
                'mx_Lead_Source_Description' => $leadSourceDescription,
                'mx_Lead_Location' => $leadLocation,
                'mx_Country_Code' => $countryCode, // The 2-letter country code (e.g., IN, GB, US)
                'mx_Country_Name' => getCountryName($countryCode), // Full country name
                'mx_Country_Dial_Code' => $dialCode, // The dial code (e.g., +91, +44, +1)
                'mx_Country_Prefix' => $countryPrefix, // The country prefix used in lead source (e.g., IND, UK)
                'mx_Resort_Code' => $resortCode
            ];
            
            fwrite($logFile, "Prepared LeadSquared data: " . json_encode($leadData) . "\n");
            error_log("Prepared LeadSquared data: " . json_encode($leadData));
            
            // Create or update the lead in LeadSquared using the simplified attribute-value approach
            $response = createLeadSquaredLeadSimple(
                $leadData, 
                $leadSquaredAccessKey, 
                $leadSquaredSecretKey, 
                $leadSquaredApiUrl
            );
            
            fwrite($logFile, "LeadSquared API Response: " . json_encode($response) . "\n");
            error_log("LeadSquared API Response: " . json_encode($response));
            
            if ($response['status'] === 'success') {
                // Update the database with LeadSquared ID if available
                $leadId = null;
                
                // Try to extract lead ID from response
                if (isset($response['data']['Id'])) {
                    $leadId = $response['data']['Id'];
                }
                
                if ($leadId) {
                    error_log("LeadSquared lead created/updated successfully. Lead ID: $leadId");
                    
                    // Update the database record with the LeadSquared ID
                    $updateStmt = $pdo->prepare("UPDATE resort_enquiries SET leadsquared_id = :leadsquared_id WHERE id = :id");
                    $updateStmt->execute([
                        ':leadsquared_id' => $leadId,
                        ':id' => $enquiryId
                    ]);
                    
                    error_log("Database updated with LeadSquared ID for enquiry ID: $enquiryId");
                    fwrite($logFile, "Database updated with LeadSquared ID: $leadId for enquiry: $enquiryId\n");
                } else {
                    error_log("LeadSquared lead created/updated successfully, but lead ID is not available in the response.");
                    fwrite($logFile, "Warning: LeadSquared lead created but no ID found. Full response: " . print_r($response, true) . "\n");
                }
            } else {
                error_log("Failed to create/update LeadSquared lead: " . $response['message']);
                
                // Handle common errors
                if (strpos($response['message'], 'credentials') !== false || strpos($response['message'], 'Invalid AccessKey') !== false) {
                    error_log("The issue appears to be with the LeadSquared credentials. Please verify them in the admin settings.");
                    fwrite($logFile, "CREDENTIAL ERROR: Please verify the LeadSquared access key and secret key in settings.\n");
                }
                // Check if JSON parsing error
                else if (strpos($response['message'], 'parse') !== false || strpos($response['message'], 'Syntax error') !== false) {
                    error_log("The issue appears to be with parsing the LeadSquared API response. This is likely a temporary issue.");
                    fwrite($logFile, "JSON PARSING ERROR: The LeadSquared API returned an invalid response format. This is likely temporary.\n");
                    fwrite($logFile, "Original error: " . $response['message'] . "\n");
                }
            }
        } catch (Exception $e) {
            error_log("Exception while processing LeadSquared integration: " . $e->getMessage());
            fwrite($logFile, "EXCEPTION in LeadSquared integration: " . $e->getMessage() . "\n");
            fwrite($logFile, "Stack trace: " . $e->getTraceAsString() . "\n");
        }
    } else {
        error_log("LeadSquared integration is disabled. Skipping LeadSquared lead creation.");
    }

    // Initialize PHPMailer
    $mail = new PHPMailer(true);
    try {
        // Server settings - with better logging
        fwrite($logFile, "Configuring PHPMailer with SMTP settings\n");
        $mail->SMTPDebug = 3; // Enable verbose debug output in log file
        $mail->Debugoutput = function($str, $level) use ($logFile) {
            fwrite($logFile, "PHPMAILER DEBUG [$level]: $str\n");
        };
        
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->Port = intval($smtpPort);
        
        // Only use authentication if username is provided
        if (!empty($smtpUsername)) {
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
        } else {
            $mail->SMTPAuth = false;
            fwrite($logFile, "SMTP Auth disabled - no username provided\n");
        }
        
        // Set encryption based on settings
        if ($smtpEncryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            fwrite($logFile, "Using SMTPS encryption (SSL)\n");
        } else if ($smtpEncryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            fwrite($logFile, "Using STARTTLS encryption (TLS)\n");
        } else {
            $mail->SMTPSecure = false;
            $mail->SMTPAutoTLS = false;
            fwrite($logFile, "No encryption specified - disabling TLS\n");
        }
        
        // SSL certificate verification options to prevent common errors
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Set sender with error handling
        try {
            fwrite($logFile, "Setting sender: $fromEmail, $fromName\n");
            $mail->setFrom($fromEmail, $fromName);
        } catch (Exception $e) {
            fwrite($logFile, "Error setting sender: " . $e->getMessage() . "\n");
            $mail->setFrom('noreply@karmaexperience.com', 'Karma Experience'); // Fallback sender
            fwrite($logFile, "Using fallback sender\n");
        }
        
        // Debug log SMTP settings (without sensitive data)
        fwrite($logFile, "SMTP Configuration Summary:\n");
        fwrite($logFile, "- Host: " . $mail->Host . "\n");
        fwrite($logFile, "- Port: " . $mail->Port . "\n");
        fwrite($logFile, "- Username: " . $mail->Username . "\n");
        fwrite($logFile, "- From Email: " . $fromEmail . "\n");
        fwrite($logFile, "- From Name: " . $fromName . "\n");
        fwrite($logFile, "- Admin Email: " . $adminEmail . "\n");

        // Set recipients for admin notification
        $mail->addAddress($adminEmail, 'Karma Experience');
        $mail->addReplyTo($email, $firstName . ' ' . $lastName);
        
        // Email content for admin
        $mail->isHTML(true);
        $mail->Subject = "New Resort Enquiry: {$resortName} (#EQ{$enquiryId})";
        
        // Admin email body with professional design
        $adminBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #f8f8f8; padding: 20px; text-align: center;'>
                <img src='" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/assets/images/logo/KE-Gold.png' alt='Karma Experience' style='max-width: 200px;'>
            </div>
            
            <div style='padding: 20px; background-color: #ffffff; border: 1px solid #e0e0e0;'>
                <h2 style='color: #2c3e50; border-bottom: 2px solid #f8f8f8; padding-bottom: 10px;'>New Resort Enquiry (#EQ{$enquiryId})</h2>
                
                <div style='margin: 20px 0; background-color: #f9f9f9; padding: 15px; border-left: 4px solid #2c3e50;'>
                    <h3 style='color: #2c3e50; margin-top: 0;'>Resort Details</h3>
                    <p><strong>Resort:</strong> {$resortName}</p>
                    <p><strong>Destination:</strong> {$destinationName}</p>
                    <p><strong>Resort Code:</strong> {$resortCode}</p>
                </div>

                <div style='margin: 20px 0; background-color: #f9f9f9; padding: 15px; border-left: 4px solid #3498db;'>
                    <h3 style='color: #2c3e50; margin-top: 0;'>Customer Details</h3>
                    <p><strong>Name:</strong> {$firstName} {$lastName}</p>
                    <p><strong>Email:</strong> {$email}</p>
                    <p><strong>Phone:</strong> {$formattedPhone}</p>
                    <p><strong>Date of Birth:</strong> {$dob}</p>
                    <p><strong>Has Passport:</strong> {$hasPassport}</p>
                    <p><strong>Country:</strong> {$countryCode}</p>
                    <p><strong>Additional Requirements:</strong> {$additionalRequirements}</p>
                </div>

                <div style='margin: 20px 0; background-color: #f9f9f9; padding: 15px; border-left: 4px solid #27ae60;'>
                    <h3 style='color: #2c3e50; margin-top: 0;'>Lead Details</h3>
                    <p><strong>Lead Source:</strong> {$leadSource}</p>
                    <p><strong>Lead Brand:</strong> {$leadBrand}</p>
                    <p><strong>Lead Sub Brand:</strong> {$leadSubBrand}</p>
                    <p><strong>Lead Source Description:</strong> {$leadSourceDescription}</p>
                    <p><strong>Lead Location:</strong> {$leadLocation}</p>
                    <p><strong>Is Partner Hotel:</strong> " . ($isPartner ? 'Yes' : 'No') . "</p>
                    <p><strong>Communication Consent:</strong> " . ($communicationConsent ? 'Yes' : 'No') . "</p>
                    <p><strong>DND Consent:</strong> " . ($dndConsent ? 'Yes' : 'No') . "</p>
                </div>

                <div style='margin: 20px 0; background-color: #f9f9f9; padding: 15px; border-left: 4px solid " . ($response['status'] === 'success' ? '#27ae60' : '#e74c3c') . ";'>
                    <h3 style='color: #2c3e50; margin-top: 0;'>LeadSquared Status</h3>
                    <p><strong>Status:</strong> " . ($response['status'] === 'success' ? '<span style="color: #27ae60;">Success</span>' : '<span style="color: #e74c3c;">Error</span>') . "</p>
                    " . (isset($response['data']['Message']['Id']) ? "<p><strong>LeadSquared ID:</strong> " . $response['data']['Message']['Id'] . "</p>" : "") . "
                    " . ($response['status'] !== 'success' ? "<p><strong>Error Message:</strong> " . $response['message'] . "</p>" : "") . "
                </div>

                <p style='margin-top: 20px;'>
                    <a href='" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/view_enquiries.php' 
                       style='background-color: #2c3e50; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                        View All Enquiries
                    </a>
                </p>
            </div>
            
            <div style='padding: 20px; background-color: #2c3e50; color: #ffffff; text-align: center; font-size: 12px;'>
                <p>&copy; " . date('Y') . " Karma Experience. All rights reserved.</p>
            </div>
        </div>";

        $mail->Body = $adminBody;
        $mail->AltBody = strip_tags($adminBody);

        // Send admin notification
        if (!$mail->send()) {
            fwrite($logFile, "Error sending admin notification: " . $mail->ErrorInfo . "\n");
            error_log("Error sending admin notification: " . $mail->ErrorInfo);
            // Don't throw exception here, try to send customer email anyway
        } else {
            fwrite($logFile, "Admin notification email sent successfully\n");
        }
        
        // Clear recipients for customer confirmation
        $mail->clearAddresses();
        $mail->clearReplyTos();
        
        // Set recipient for customer confirmation
        try {
            $mail->addAddress($email, $firstName . ' ' . $lastName);
            $mail->addReplyTo($fromEmail, $fromName);
            fwrite($logFile, "Configured customer confirmation email to: $email\n");
        } catch (Exception $e) {
            fwrite($logFile, "Error configuring customer email: " . $e->getMessage() . "\n");
            error_log("Error configuring customer email: " . $e->getMessage());
        }
        
        // Email content for customer with professional design
        $mail->Subject = "Thank you for your enquiry - {$resortName}";
        $customerBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #f8f8f8; padding: 20px; text-align: center;'>
                <img src='" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/assets/images/logo/KE-Gold.png' alt='Karma Experience' style='max-width: 200px;'>
            </div>
            
            <div style='padding: 20px; background-color: #ffffff; border: 1px solid #e0e0e0;'>
                <h2 style='color: #2c3e50;'>Dear {$firstName},</h2>
                
                <p>Thank you for your interest in <strong>{$resortName}</strong> at {$destinationName}.</p>
                
                <div style='background-color: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 4px solid #3498db;'>
                    <p style='margin: 0;'><strong>Reference Number:</strong> EQ{$enquiryId}</p>
                    <p style='margin: 5px 0 0 0; color: #666;'>Please keep this reference number for future communications.</p>
                </div>
                
                <p>Our dedicated team will review your enquiry and contact you shortly to discuss your interest in this beautiful destination.</p>
                
                <p>If you have any questions in the meantime, please don't hesitate to contact us.</p>
                
                <div style='margin-top: 30px;'>
                    <p style='margin: 0;'>Best regards,</p>
                    <p style='margin: 5px 0 0 0;'><strong>The Karma Experience Team</strong></p>
                </div>
            </div>
            
            <div style='padding: 20px; background-color: #2c3e50; color: #ffffff; text-align: center; font-size: 12px;'>
                <p>&copy; " . date('Y') . " Karma Experience. All rights reserved.</p>
                <p>This is an automated email, please do not reply directly to this message.</p>
            </div>
        </div>";
        
        $mail->Body = $customerBody;
        $mail->AltBody = strip_tags($customerBody);
        
        // Send customer confirmation
        $customerEmailSent = false;
        try {
            if ($mail->send()) {
                fwrite($logFile, "Customer confirmation email sent successfully\n");
                $customerEmailSent = true;
            } else {
                fwrite($logFile, "Error sending customer confirmation: " . $mail->ErrorInfo . "\n");
                error_log("Error sending customer confirmation: " . $mail->ErrorInfo);
            }
        } catch (Exception $e) {
            fwrite($logFile, "Exception sending customer email: " . $e->getMessage() . "\n");
            error_log("Exception sending customer email: " . $e->getMessage());
        }

        // Set success message and redirect to thank you page
        $_SESSION['success_message'] = "Thank you for your enquiry! We will contact you soon.";
        $_SESSION['enquiry_resort'] = $resortName;
        $_SESSION['enquiry_email'] = $email;
        $_SESSION['enquiry_name'] = $firstName . ' ' . $lastName;
        
        // Close log file before redirecting
        fwrite($logFile, "Process completed successfully. Redirecting to thank-you page\n");
        fclose($logFile);
        
        // Ensure no output has been sent before redirecting
        if (!headers_sent()) {
            header('Location: thank-you.php');
            exit();
        } else {
            echo '<script>window.location.href = "thank-you.php";</script>';
            exit();
        }
        
    } catch (Exception $e) {
        error_log("Error in resort enquiry: " . $e->getMessage());
        $_SESSION['error'] = "There was a problem processing your enquiry. Please try again.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
} catch (Exception $e) {
    // Log the error
    fwrite($logFile, "Error in resort enquiry: " . $e->getMessage() . "\n");
    error_log("Error in resort enquiry: " . $e->getMessage());
    
    // Set error message and redirect
    $_SESSION['error_message'] = "We're sorry, but there was a problem processing your enquiry. Please try again later.";
    
    fclose($logFile);
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
} 
?> 