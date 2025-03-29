<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;

// Load environment variables
$env = parse_ini_file('.env');

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid request');
}

// Get form data
$firstName = $_POST['firstName'] ?? '';
$lastName = $_POST['lastName'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['full_phone'] ?? '';
$dob = $_POST['dob'] ?? '';
$hasPassport = $_POST['hasPassport'] ?? '';
$resortName = $_POST['resort_name'] ?? '';
$destinationName = $_POST['destination_name'] ?? '';
$resortCode = $_POST['resort_code'] ?? '';

// Get country from phone number
$phoneUtil = PhoneNumberUtil::getInstance();
try {
    $phoneNumber = $phoneUtil->parse($phone);
    $countryCode = $phoneUtil->getRegionCodeForNumber($phoneNumber);
} catch (NumberParseException $e) {
    $countryCode = 'UNKNOWN';
}

// Get destination_id from resort_id
$stmt = $conn->prepare("SELECT destination_id FROM resorts WHERE id = :resort_id");
$stmt->execute(['resort_id' => $_POST['resort_id']]);
$resort = $stmt->fetch(PDO::FETCH_ASSOC);
$destination_id = $resort['destination_id'];

// Save enquiry to database
$stmt = $conn->prepare("INSERT INTO resort_enquiries (resort_id, destination_id, first_name, last_name, email, phone, date_of_birth, has_passport, resort_name, destination_name, resort_code) VALUES (:resort_id, :destination_id, :firstName, :lastName, :email, :phone, :dob, :hasPassport, :resort_name, :destination_name, :resort_code)");
$stmt->execute([
    'resort_id' => $_POST['resort_id'],
    'destination_id' => $destination_id,
    'firstName' => $_POST['firstName'],
    'lastName' => $_POST['lastName'],
    'email' => $_POST['email'],
    'phone' => $_POST['full_phone'],
    'dob' => $_POST['dob'],
    'hasPassport' => $_POST['hasPassport'],
    'resort_name' => $_POST['resort_name'],
    'destination_name' => $_POST['destination_name'],
    'resort_code' => $_POST['resort_code']
]);

if (!$stmt) {
    $_SESSION['error'] = "Failed to save enquiry: " . implode(" ", $stmt->errorInfo());
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Prepare LeadSquared data
$accessKey = $env['LEADSQUARED_ACCESS_KEY'];
$secretKey = $env['LEADSQUARED_SECRET_KEY'];
$leadSourceDescription = strtolower($countryCode) . ' | web enquiry | ' . $resortCode;

$leadData = array(
    'FirstName' => $firstName,
    'LastName' => $lastName,
    'EmailAddress' => $email,
    'Phone' => $phone,
    'DateOfBirth' => $dob,
    'HasPassport' => $hasPassport,
    'mx_Resort_Name' => $resortName,
    'mx_Destination' => $destinationName,
    'mx_Resort_Code' => $resortCode,
    'ProspectStage' => 'New',
    'Brand' => 'demo',
    'SubBrand' => 'demo int',
    'LeadSource' => 'web enquiry',
    'LeadSourceDescription' => $leadSourceDescription,
    'LeadLocation' => $countryCode
);

// Send to LeadSquared
$url = "https://api.leadsquared.com/v2/LeadManagement.svc/Lead.Create";
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($leadData));
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'accessKey: ' . $accessKey,
    'secretKey: ' . $secretKey
));

$response = curl_exec($curl);
$error = curl_error($curl);
curl_close($curl);

// Send email notification
$mail = new PHPMailer(true);
try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = $env['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $env['SMTP_USERNAME'];
    $mail->Password = $env['SMTP_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $env['SMTP_PORT'];

    // Recipients
    $mail->setFrom($env['MAIL_FROM_ADDRESS'], $env['MAIL_FROM_NAME']);
    $mail->addAddress($env['MAIL_TO_ADDRESS']);
    $mail->addReplyTo($email, $firstName . ' ' . $lastName);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'New Resort Enquiry: ' . $resortName;
    
    // Email body
    $body = "
    <h2>New Resort Enquiry</h2>
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
    <hr>
    <h3>Lead Details:</h3>
    <p><strong>Lead Brand:</strong> demo</p>
    <p><strong>Lead Sub Brand:</strong> demo int</p>
    <p><strong>Lead Source:</strong> web enquiry</p>
    <p><strong>Lead Source Description:</strong> {$leadSourceDescription}</p>
    <p><strong>Lead Location:</strong> {$countryCode}</p>
    ";
    
    $mail->Body = $body;
    $mail->AltBody = strip_tags($body);

    $mail->send();
    
    // Set success message
    $_SESSION['success_message'] = "Thank you for your enquiry. We will contact you soon.";
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}

// Redirect back
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit(); 