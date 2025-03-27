<?php
require 'vendor/autoload.php';
require 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Verify CSRF token
session_start();
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid token');
}

// Validate and sanitize input
$firstName = filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_STRING);
$lastName = filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_STRING);
$phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$hasPassport = filter_input(INPUT_POST, 'hasPassport', FILTER_SANITIZE_STRING);
$resortName = filter_input(INPUT_POST, 'resort', FILTER_SANITIZE_STRING);
$destination = filter_input(INPUT_POST, 'destination', FILTER_SANITIZE_STRING);
$dob = filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING);
$country = filter_input(INPUT_POST, 'country', FILTER_SANITIZE_STRING);

// Validate required fields
if (!$firstName || !$lastName || !$phone || !$email || !$hasPassport) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}

try {
    // Store in database
    $stmt = $pdo->prepare("INSERT INTO resort_leads (first_name, last_name, phone, email, has_passport, resort_name, destination_name, dob, country) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$firstName, $lastName, $phone, $email, $hasPassport, $resortName, $destination, $dob, $country]);
    $leadId = $pdo->lastInsertId();

    // Send email using PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'];
        $mail->Password = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = $_ENV['SMTP_SECURE'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'];
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom($_ENV['SMTP_USER'], 'Karma Experience');
        $mail->addAddress($_ENV['EMAIL_RECIPIENT'], 'Karma Experience');
        $mail->addReplyTo($email, $firstName . ' ' . $lastName);

        // Content
        $mail->isHTML(true);
        $subject = !empty($destination) ? "New Lead - $destination - $resortName" : "New Lead - $resortName";
        $mail->Subject = $subject;

        // Create HTML email body with better formatting
        $emailBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #1a73e8; border-bottom: 2px solid #1a73e8; padding-bottom: 10px;'>New Resort Enquiry</h2>
            
            <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                <tr>
                    <td style='padding: 10px; background: #f5f6fa;'><strong>Name:</strong></td>
                    <td style='padding: 10px;'>{$firstName} {$lastName}</td>
                </tr>
                <tr>
                    <td style='padding: 10px; background: #f5f6fa;'><strong>Email:</strong></td>
                    <td style='padding: 10px;'><a href='mailto:{$email}'>{$email}</a></td>
                </tr>
                <tr>
                    <td style='padding: 10px; background: #f5f6fa;'><strong>Phone:</strong></td>
                    <td style='padding: 10px;'>{$phone}</td>
                </tr>
                <tr>
                    <td style='padding: 10px; background: #f5f6fa;'><strong>Date of Birth:</strong></td>
                    <td style='padding: 10px;'>{$dob}</td>
                </tr>
                <tr>
                    <td style='padding: 10px; background: #f5f6fa;'><strong>Country:</strong></td>
                    <td style='padding: 10px;'>{$country}</td>
                </tr>
                <tr>
                    <td style='padding: 10px; background: #f5f6fa;'><strong>Has Passport:</strong></td>
                    <td style='padding: 10px;'>{$hasPassport}</td>
                </tr>
                <tr>
                    <td style='padding: 10px; background: #f5f6fa;'><strong>Resort:</strong></td>
                    <td style='padding: 10px;'>{$resortName}</td>
                </tr>
                <tr>
                    <td style='padding: 10px; background: #f5f6fa;'><strong>Destination:</strong></td>
                    <td style='padding: 10px;'>{$destination}</td>
                </tr>
            </table>

            <p style='margin-top: 20px; color: #666; font-size: 12px;'>
                This enquiry was submitted from the Karma Experience website on " . date('F j, Y \a\t g:i a') . "
            </p>
        </div>";

        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</tr>'], ["\n", "\n"], $emailBody));

        // Send email
        $mail->send();

        // LeadSquared Integration (if needed)
        if (isset($_ENV['LEADSQUARED_ACCESS_KEY']) && isset($_ENV['LEADSQUARED_SECRET_KEY'])) {
            $leadData = [
                ["Attribute" => "FirstName", "Value" => $firstName],
                ["Attribute" => "LastName", "Value" => $lastName],
                ["Attribute" => "EmailAddress", "Value" => $email],
                ["Attribute" => "Phone", "Value" => $phone],
                ["Attribute" => "mx_Has_Passport", "Value" => $hasPassport],
                ["Attribute" => "mx_Resort", "Value" => $resortName],
                ["Attribute" => "mx_Destination", "Value" => $destination],
                ["Attribute" => "mx_Date_of_Birth", "Value" => $dob],
                ["Attribute" => "mx_Country", "Value" => $country],
                ["Attribute" => "Source", "Value" => "Website"],
                ["Attribute" => "mx_Lead_Source", "Value" => "Resort Enquiry"]
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $_ENV['LEADSQUARED_API_URL'] . '/Lead.Create',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($leadData),
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . base64_encode($_ENV['LEADSQUARED_ACCESS_KEY'] . ':' . $_ENV['LEADSQUARED_SECRET_KEY']),
                    'Content-Type: application/json'
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                error_log('LeadSquared API Error: ' . $err);
            }
        }

        // Return success response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Your enquiry has been submitted successfully',
            'redirect' => 'thank-you.php'
        ]);

    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $e->getMessage());
        throw $e;
    }

} catch (Exception $e) {
    error_log('Error processing form: ' . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request. Please try again later.'
    ]);
}
?>
