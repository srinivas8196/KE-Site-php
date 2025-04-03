<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Define the Flywire webhook secret (to verify authenticity)
$flywire_secret = '66a12bbf7473a3ef3b9ce45f75e90514';

// Retrieve the raw POST data
$raw_data = file_get_contents('php://input');

// Verify the signature (if Flywire provides one for security)
$headers = getallheaders();
$signature = isset($headers['X-Flywire-Signature']) ? $headers['X-Flywire-Signature'] : '';

if (!verifyFlywireSignature($raw_data, $signature, $flywire_secret)) {
    error_log("Invalid Flywire signature.");
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
    exit();
}

// Decode the incoming JSON data
$data = json_decode($raw_data, true);

if ($data === null) {
    error_log("Invalid JSON data received from Flywire.");
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit();
}

// Extract relevant data
$eventType = $data['event_type'] ?? 'unknown';
$eventDate = $data['event_date'] ?? date('Y-m-d H:i:s');
$paymentId = $data['data']['payment_id'] ?? 'N/A';
$amount = $data['data']['amount_from'] ?? 0;
$currency = $data['data']['currency_from'] ?? '';
$status = $data['data']['status'] ?? 'unknown';
$referenceId = $data['data']['external_reference'] ?? 'N/A';
$payer = $data['data']['payer'] ?? [];
$payerName = ($payer['first_name'] ?? '') . ' ' . ($payer['last_name'] ?? '');
$payerEmail = $payer['email'] ?? '';

// Determine the status message
$statusMessages = [
    'initiated' => 'Your payment has been initiated.',
    'processed' => 'Your payment has been processed.',
    'guaranteed' => 'Your payment is guaranteed.',
    'delivered' => 'Your payment has been delivered.',
    'failed' => 'Your payment has failed.',
    'cancelled' => 'Your payment has been cancelled.',
    'reversed' => 'Your payment has been reversed.'
];
$statusMessage = $statusMessages[$status] ?? 'Unknown payment status.';

// Send notification emails
sendFlywireEmail($payerEmail, $payerName, $paymentId, $referenceId, $amount, $currency, $statusMessage, $eventDate, false);
sendFlywireEmail('webdev@karmaexperience.in', 'Karma Experience', $paymentId, $referenceId, $amount, $currency, $statusMessage, $eventDate, true, $payerName, $payerEmail);

// Respond to Flywire to acknowledge receipt of the webhook
http_response_code(200); // OK
echo json_encode(['status' => 'success', 'message' => 'Webhook processed successfully']);

// Function to verify Flywire webhook signature
function verifyFlywireSignature($data, $signature, $secret)
{
    $calculated_signature = hash_hmac('sha256', $data, $secret);
    return $signature === $calculated_signature;
}

// Function to send email notifications
function sendFlywireEmail($toEmail, $toName, $paymentId, $referenceId, $amount, $currency, $statusMessage, $eventDate, $isAdmin = false, $payerName = '', $payerEmail = '')
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'res@karmaexperience.com';
        $mail->Password = 'giicaljomdnadgnx'; // Use a secure app-specific password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('res@karmaexperience.com', 'Karma Experience');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $isAdmin ? 'New Karma Experience Payment Notification - Flywire' : 'Your Payment Status Update';

        $mail->Body = "
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        margin: 0;
                        padding: 0;
                        background-color: #f4f4f9;
                        color: #333;
                    }
                    .email-container {
                        max-width: 600px;
                        margin: 20px auto;
                        background: #ffffff;
                        border-radius: 10px;
                        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                        overflow: hidden;
                    }
                    .email-header {
                        background: #4CAF50;
                        color: white;
                        padding: 20px;
                        text-align: center;
                    }
                    .email-header h1 {
                        margin: 0;
                        font-size: 24px;
                    }
                    .email-body {
                        padding: 20px;
                    }
                    .email-body p {
                        margin: 0 0 10px;
                        font-size: 16px;
                    }
                    .email-table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 20px 0;
                    }
                    .email-table th, .email-table td {
                        border: 1px solid #ddd;
                        padding: 10px;
                        text-align: left;
                    }
                    .email-table th {
                        background: #f4f4f9;
                        font-weight: bold;
                    }
                    .email-footer {
                        background: #f4f4f9;
                        text-align: center;
                        padding: 10px;
                        font-size: 14px;
                        color: #555;
                    }
                    .email-footer a {
                        color: #4CAF50;
                        text-decoration: none;
                    }
                </style>
            </head>
            <body>
                <div class='email-container'>
                    <div class='email-header'>
                        <h1>Payment Status Update</h1>
                    </div>
                    <div class='email-body'>
                        <p>Dear $toName,</p>
                        <p>We wanted to inform you about the status of your recent payment:</p>
                        <table class='email-table'>
                            <tr>
                                <th>Payment ID</th>
                                <td>$paymentId</td>
                            </tr>
                            <tr>
                                <th>Payment Reference ID</th>
                                <td>$referenceId</td>
                            </tr>
                            <tr>
                                <th>Amount</th>
                                <td>$amount $currency</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>$statusMessage</td>
                            </tr>
                            <tr>
                                <th>Event Date</th>
                                <td>$eventDate</td>
                            </tr>
                            " . ($isAdmin ? "
                            <tr>
                                <th>Payer Name</th>
                                <td>$payerName</td>
                            </tr>
                            <tr>
                                <th>Payer Email</th>
                                <td>$payerEmail</td>
                            </tr>" : "") . "
                        </table>
                    </div>
                    <div class='email-footer'>
                        <p>Thank you for choosing our services.</p>
                        <p><a href='https://karmaexperience.com/'>Visit our website</a> | <a href='https://karmaexperience.com/privacy-policy'>Privacy Policy</a></p>
                    </div>
                </div>
            </body>
            </html>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
    }
}
