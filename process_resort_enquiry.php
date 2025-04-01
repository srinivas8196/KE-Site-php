<?php
// Set session parameters BEFORE session_start
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

// Create sessions directory if it doesn't exist
if (!file_exists(dirname(__FILE__) . '/sessions')) {
    mkdir(dirname(__FILE__) . '/sessions', 0777, true);
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
require 'vendor/autoload.php';
require 'leadsquared_helper.php'; // Add LeadSquared helper functions
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;

// Load environment variables
$env = parse_ini_file('.env');

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
$phone = $_POST['full_phone'] ?? $_POST['phone'] ?? ''; // Prefer the full_phone field if available
$dob = $_POST['dob'] ?? '';
$hasPassport = $_POST['has_passport'] ?? '';
$additionalRequirements = $_POST['additional_requirements'] ?? '';
$resortName = $_POST['resort_name'] ?? '';
$destinationName = $_POST['destination_name'] ?? '';
$resortCode = $_POST['resort_code'] ?? '';
$resortId = $_POST['resort_id'] ?? '';
$destinationId = $_POST['destination_id'] ?? '';

// Basic validation
if (empty($firstName) || empty($lastName) || empty($email) || empty($phone)) {
    fwrite($logFile, "Missing required fields\n");
    fclose($logFile);
    $_SESSION['error_message'] = 'Please fill in all required fields.';
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
    $phoneNumber = $phoneUtil->parse($phone);
    $countryCode = $phoneUtil->getRegionCodeForNumber($phoneNumber);
} catch (NumberParseException $e) {
    $countryCode = 'UNKNOWN';
}

// Check if resort is a partner hotel
$stmt = $pdo->prepare("SELECT is_partner FROM resorts WHERE id = ?");
$stmt->execute([$resortId]);
$resortDetails = $stmt->fetch(PDO::FETCH_ASSOC);
$isPartner = $resortDetails['is_partner'] ?? 0;

// Generate LeadSquared details based on country code
$leadSource = 'Web Enquiry';
$leadBrand = 'Timeshare Marketing';

// Set Lead Sub Brand based on country code
$leadSubBrand = 'Karma Experience ROW'; // Default is Rest of World
if ($countryCode == 'AU') {
    $leadSubBrand = 'Karma Experience AU';
} elseif ($countryCode == 'ID') {
    $leadSubBrand = 'Karma Experience ID';
} elseif ($countryCode == 'IN') {
    $leadSubBrand = 'Karma Experience IND';
} elseif ($countryCode == 'GB') {
    $leadSubBrand = 'Karma Experience UK';
}

// Generate Lead Source Description
$countryPrefix = 'ROW'; // Default is Rest of World
if ($countryCode == 'AU') {
    $countryPrefix = 'AU';
} elseif ($countryCode == 'ID') {
    $countryPrefix = 'ID';
} elseif ($countryCode == 'IN') {
    $countryPrefix = 'IND';
} elseif ($countryCode == 'GB') {
    $countryPrefix = 'UK';
}

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
        lead_sub_brand, lead_source_description, lead_location) 
        VALUES (?, " . (is_null($destinationId) ? "NULL" : "?") . ", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', ?, ?, ?, ?, ?, ?)");
    
    // Build parameters array based on destination_id being NULL or not
    $params = [$resortId];
    if (!is_null($destinationId)) {
        $params[] = $destinationId;
    }
    $params = array_merge($params, [
        $firstName,
        $lastName,
        $email,
        $phone,
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
        $leadLocation
    ]);
    
    // Log the database operation
    fwrite($logFile, "Executing database insert\n");
    
    // Execute with proper parameters
    $stmt->execute($params);
    
    // Log success
    fwrite($logFile, "Database insert successful\n");

    // Get the last inserted ID
    $enquiryId = $pdo->lastInsertId();

    // Format data for LeadSquared
    $leadSquaredData = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'date_of_birth' => $dob,
        'has_passport' => $hasPassport,
        'additional_requirements' => $additionalRequirements,
        'resort_name' => $resortName,
        'destination_name' => $destinationName,
        'resort_code' => $resortCode,
        'country_code' => $countryCode,
        'lead_source' => $leadSource,
        'lead_brand' => $leadBrand,
        'lead_sub_brand' => $leadSubBrand,
        'lead_source_description' => $leadSourceDescription,
        'lead_location' => $leadLocation,
        'enquiry_id' => $enquiryId
    ];

    // Send to LeadSquared API
    fwrite($logFile, "Sending to LeadSquared API\n");
    $formattedData = formatLeadSquaredData($leadSquaredData);
    $leadSquaredResponse = createLeadSquaredLead($formattedData);
    fwrite($logFile, "LeadSquared response: " . print_r($leadSquaredResponse, true) . "\n");

    // Update database with LeadSquared response
    if ($leadSquaredResponse['status'] === 'success' && isset($leadSquaredResponse['data']['Message'])) {
        $leadId = $leadSquaredResponse['data']['Message']['Id'] ?? null;
        if ($leadId) {
            $updateStmt = $pdo->prepare("UPDATE resort_enquiries SET leadsquared_id = ? WHERE id = ?");
            $updateStmt->execute([$leadId, $enquiryId]);
            fwrite($logFile, "Updated database with LeadSquared ID: $leadId\n");
        }
    }
    
    // Send email notification
    fwrite($logFile, "Preparing to send email notification\n");
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = $env['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $env['SMTP_USERNAME'];
    $mail->Password = $env['SMTP_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = $env['SMTP_PORT'];

    // Recipients
    $mail->setFrom($env['MAIL_FROM_ADDRESS'], $env['MAIL_FROM_NAME']);
    $mail->addAddress('webdev@karmaexperience.in', 'Karma Experience Web Development');
    $mail->addReplyTo($email, $firstName . ' ' . $lastName);

    // Content
    $mail->isHTML(true);
    $mail->Subject = "New Resort Enquiry: {$resortName} (#EQ{$enquiryId})";
    
    // Email body with LeadSquared details
    $body = "
    <h2>New Resort Enquiry (#EQ{$enquiryId})</h2>
    <p><strong>Resort:</strong> {$resortName}</p>
    <p><strong>Destination:</strong> {$destinationName}</p>
    <p><strong>Resort Code:</strong> {$resortCode}</p>
    <hr>
    <h3>Customer Details:</h3>
    <p><strong>Name:</strong> {$firstName} {$lastName}</p>
    <p><strong>Email:</strong> {$email}</p>
    <p><strong>Phone:</strong> {$phone}</p>
    <p><strong>Date of Birth:</strong> {$dob}</p>
    <p><strong>Has Passport:</strong> {$hasPassport}</p>
    <p><strong>Country:</strong> {$countryCode}</p>
    <p><strong>Additional Requirements:</strong> {$additionalRequirements}</p>
    <hr>
    <h3>LeadSquared Details:</h3>
    <p><strong>Lead Source:</strong> {$leadSource}</p>
    <p><strong>Lead Brand:</strong> {$leadBrand}</p>
    <p><strong>Lead Sub Brand:</strong> {$leadSubBrand}</p>
    <p><strong>Lead Source Description:</strong> {$leadSourceDescription}</p>
    <p><strong>Lead Location:</strong> {$leadLocation}</p>
    <p><strong>Is Partner Hotel:</strong> " . ($isPartner ? 'Yes' : 'No') . "</p>
    ";

    // Add LeadSquared API response to email
    $body .= "<hr><h3>LeadSquared API Response:</h3>";
    if ($leadSquaredResponse['status'] === 'success') {
        $body .= "<p style='color:green'><strong>Status:</strong> Success</p>";
        if (isset($leadSquaredResponse['data']['Message']['Id'])) {
            $body .= "<p><strong>LeadSquared ID:</strong> " . $leadSquaredResponse['data']['Message']['Id'] . "</p>";
        }
    } else {
        $body .= "<p style='color:red'><strong>Status:</strong> Error</p>";
        $body .= "<p><strong>Error Message:</strong> " . $leadSquaredResponse['message'] . "</p>";
    }

    $body .= "<hr>
    <p>To view all enquiries, please <a href='" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/view_enquiries.php'>click here</a>.</p>
    ";
    
    $mail->Body = $body;
    $mail->AltBody = strip_tags($body);

    fwrite($logFile, "Sending admin notification email\n");
    $mail->send();
    fwrite($logFile, "Admin email sent successfully\n");
    
    // Send confirmation email to customer
    $customerMail = new PHPMailer(true);
    
    // Server settings
    $customerMail->isSMTP();
    $customerMail->Host = $env['SMTP_HOST'];
    $customerMail->SMTPAuth = true;
    $customerMail->Username = $env['SMTP_USERNAME'];
    $customerMail->Password = $env['SMTP_PASSWORD'];
    $customerMail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $customerMail->Port = $env['SMTP_PORT'];

    // Recipients
    $customerMail->setFrom($env['MAIL_FROM_ADDRESS'], $env['MAIL_FROM_NAME']);
    $customerMail->addAddress($email, $firstName . ' ' . $lastName);

    // Content
    $customerMail->isHTML(true);
    $customerMail->Subject = "Thank you for your enquiry about {$resortName}";
    
    // Customer email body with resort information
    $customerBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #f8f8f8; padding: 20px; text-align: center;'>
            <img src='" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/assets/images/logo/KE-Gold.png' alt='Karma Experience' style='max-width: 200px;'>
        </div>
        
        <div style='padding: 20px; background-color: #ffffff;'>
            <h2 style='color: #2c3e50;'>Thank you for your enquiry, {$firstName}!</h2>
            
            <p>We have received your enquiry about <strong>{$resortName}</strong> in {$destinationName}.</p>
            
            <p>Our team will review your enquiry and contact you shortly to discuss your interest in this beautiful destination.</p>
            
            <div style='background-color: #f8f8f8; padding: 15px; margin: 20px 0; border-left: 4px solid #007bff;'>
                <p style='margin: 0; padding: 0;'><strong>Reference Number:</strong> EQ{$enquiryId}</p>
                <p style='margin: 5px 0 0 0; padding: 0;'>Please keep this reference number for future communications.</p>
            </div>
            
            <p>If you have any questions in the meantime, please don't hesitate to contact us.</p>
            
            <p>Best regards,<br>The Karma Experience Team</p>
        </div>
        
        <div style='padding: 20px; background-color: #2c3e50; color: #ffffff; text-align: center; font-size: 12px;'>
            <p>&copy; " . date('Y') . " Karma Experience. All rights reserved.</p>
            <p>This is an automated email, please do not reply directly to this message.</p>
        </div>
    </div>
    ";
    
    $customerMail->Body = $customerBody;
    $customerMail->AltBody = strip_tags($customerBody);

    fwrite($logFile, "Sending customer confirmation email\n");
    try {
        $customerMail->send();
        fwrite($logFile, "Customer email sent successfully\n");
    } catch (Exception $e) {
        // Log error but continue with the process
        fwrite($logFile, "Error sending customer confirmation email: " . $e->getMessage() . "\n");
        error_log("Error sending customer confirmation email: " . $e->getMessage());
    }
    
    // Set success message and redirect to thank you page
    $_SESSION['success_message'] = "Thank you for your enquiry! We will contact you soon.";
    
    // Add the resort name and email to the session for display on thank you page
    $_SESSION['enquiry_resort'] = $resortName;
    $_SESSION['enquiry_email'] = $email;
    $_SESSION['enquiry_name'] = $firstName . ' ' . $lastName;
    
    fwrite($logFile, "Process completed successfully. Redirecting to thank-you page\n");
    fclose($logFile);
    
    // Redirect to thank you page instead of referring page
    header('Location: thank-you.php');
    exit();
    
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