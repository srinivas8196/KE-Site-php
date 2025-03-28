<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Adjust path as needed

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(400);
    echo "CSRF token invalid";
    exit;
}

// Retrieve form data
$firstName = $_POST['firstName'] ?? '';
$lastName = $_POST['lastName'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$full_phone = $_POST['full_phone'] ?? ''; // Get the full international number
$dob = $_POST['dob'] ?? '';
$hasPassport = $_POST['hasPassport'] ?? '';
$destination = $_POST['destination'] ?? '';
$resort = $_POST['resort'] ?? '';

// Basic data validation (add more as needed)
if (empty($firstName) || empty($lastName) || empty($email) || empty($phone)) {
    http_response_code(400);
    echo "Please fill in all required fields.";
    exit;
}

// Create a new PHPMailer instance
$mail = new PHPMailer(true); // Passing `true` enables exceptions

try {
    //Server settings
    $mail->SMTPDebug = 0; // Enable verbose debug output (set to 0 for production)
    $mail->isSMTP(); // Set mailer to use SMTP
    $mail->Host = 'smtp.example.com';  // Specify main and backup SMTP servers.  Replace with your SMTP server
    $mail->SMTPAuth = true;  // Enable SMTP authentication
    $mail->Username = 'your_email@example.com'; // SMTP username. Replace with your SMTP username
    $mail->Password = 'your_password'; // SMTP password. Replace with your SMTP password
    $mail->SMTPSecure = 'tls';   // Enable TLS encryption, `ssl` also accepted
    $mail->Port = 587;  // TCP port to connect to. Replace with your SMTP port

    //Recipients
    $mail->setFrom('from@example.com', 'Enquiry Form'); // Replace with your "from" email
    $mail->addAddress('webdev@karmaexperience.in', 'Web Dev'); // Add a recipient

    //Content
    $mail->isHTML(true);  // Set email format to HTML
    $mail->Subject = 'New Resort Enquiry';
    $messageBody = "
        <h2>New Resort Enquiry</h2>
        <p><strong>First Name:</strong> " . htmlspecialchars($firstName) . "</p>
        <p><strong>Last Name:</strong> " . htmlspecialchars($lastName) . "</p>
        <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
        <p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>
        <p><strong>Full Phone Number:</strong> " . htmlspecialchars($full_phone) . "</p>
        <p><strong>Date of Birth:</strong> " . htmlspecialchars($dob) . "</p>
        <p><strong>Has Passport:</strong> " . htmlspecialchars($hasPassport) . "</p>
        <p><strong>Destination:</strong> " . htmlspecialchars($destination) . "</p>
        <p><strong>Resort:</strong> " . htmlspecialchars($resort) . "</p>
    ";
    $mail->Body = $messageBody;
    $mail->AltBody = strip_tags($messageBody); //Plain text version for non-HTML mail clients

    $mail->send();
    echo 'Message has been sent';
    header('Location: thank-you.php'); // Redirect to a thank you page
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}