<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Flywire shared secret for verifying the signature
$flywireSecret = 'e85ab76f5a75cba0f17dce113f8f6765';

// Retrieve and log the raw POST data
$rawData = file_get_contents('php://input');
file_put_contents('flywire_raw_data.log', $rawData . PHP_EOL, FILE_APPEND);

// Verify Flywire digest signature
$headers = getallheaders();
$receivedDigest = $headers['X-Flywire-Digest'] ?? '';

if (!verifyFlywireDigest($rawData, $receivedDigest, $flywireSecret)) {
    logError("Invalid Flywire Digest: $receivedDigest");
    sendErrorResponse('Invalid signature', 400);
}

// Decode JSON data
$data = json_decode($rawData, true);
if ($data === null) {
    logError("Invalid JSON data: $rawData");
    sendErrorResponse('Invalid JSON data', 400);
}

// Extract information
$eventType = $data['event_type'] ?? 'unknown';
$paymentData = $data['data'] ?? [];

// Extract payment details
$paymentId = $paymentData['payment_id'] ?? 'N/A';
$status = $paymentData['status'] ?? 'unknown';
$amountFrom = formatAmount($paymentData['amount_from'] ?? 0);
$currencyFrom = $paymentData['currency_from'] ?? '';
$payer = $paymentData['payer'] ?? [];
$payerEmail = $payer['email'] ?? '';
$payerName = ($payer['first_name'] ?? '') . ' ' . ($payer['last_name'] ?? '');

// Log processed data
file_put_contents('flywire_processed_data.log', print_r($data, true) . PHP_EOL, FILE_APPEND);

// Notify payer and admin
sendEmailNotification($payerEmail, $payerName, $paymentId, $amountFrom, $currencyFrom, $status);
sendEmailNotification(
    'karma.payments@karmaexperience.com',
    'Karma Experience Payments',
    $paymentId,
    $amountFrom,
    $currencyFrom,
    $status,
    true,
    $payerName,
    $payerEmail
);

// Respond to Flywire
http_response_code(200);
echo json_encode(['status' => 'success', 'message' => 'Webhook processed successfully']);

/**
 * Verify Flywire Digest
 */
function verifyFlywireDigest($data, $receivedDigest, $secret)
{
    if (!$receivedDigest) {
        logError('Missing X-Flywire-Digest header.');
        return false;
    }
    $calculatedDigest = base64_encode(hex2bin(hash_hmac('sha256', trim($data), $secret)));
    return hash_equals($calculatedDigest, $receivedDigest);
}

/**
 * Format amount with decimal points
 */
function formatAmount($amount)
{
    return number_format($amount / 100, 2, '.', '');
}

/**
 * Log errors to a file
 */
function logError($message)
{
    file_put_contents('flywire_errors.log', $message . PHP_EOL, FILE_APPEND);
    error_log($message);
}

/**
 * Send error response
 */
function sendErrorResponse($message, $code)
{
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit();
}

/**
 * Send email notification
 */
function sendEmailNotification($toEmail, $toName, $paymentId, $amount, $currency, $status, $isAdmin = false, $payerName = '', $payerEmail = '')
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'karma.payments@karmaexperience.com';
        $mail->Password = 'asurjpjrnwjfdfvd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('karma.payments@karmaexperience.com', 'Karma Experience Payment');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $isAdmin ? 'New Payment Notification' : 'Payment Status Update';
        $mail->Body = generateEmailBody($paymentId, $amount, $currency, $status, $isAdmin, $payerName, $payerEmail);

        $mail->send();
    } catch (Exception $e) {
        logError("Email error: {$mail->ErrorInfo}");
    }
}

/**
 * Generate email body
 */

 function generateEmailBody($paymentId, $amount, $currency, $status, $isAdmin, $payerName, $payerEmail)
{
    $header = $isAdmin ? 'New Payment Notification' : 'Payment Status Update';
    $introText = $isAdmin
        ? 'A new payment has been received. Here are the details:'
        : 'Thank you for your payment. Here are the details:';

    $additionalInfo = $isAdmin
        ? "<tr><td style='padding: 8px; font-weight: bold;'>Payer Name:</td><td style='padding: 8px;'>$payerName</td></tr>
           <tr><td style='padding: 8px; font-weight: bold;'>Payer Email:</td><td style='padding: 8px;'>$payerEmail</td></tr>"
        : '';

    return "<html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; background-color: #f9f9f9; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #dddddd; border-radius: 5px;'>
                <div style='background: #007BFF; padding: 15px; border-radius: 5px 5px 0 0; text-align: center; color: #ffffff;'>
                    <h2 style='margin: 0; font-size: 24px;'>$header</h2>
                </div>
                <div style='padding: 20px;'>
                    <p style='font-size: 16px; color: #333333;'>$introText</p>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px; font-weight: bold; border-bottom: 1px solid #dddddd;'>Status:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dddddd;'>$status</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; font-weight: bold; border-bottom: 1px solid #dddddd;'>Payment ID:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dddddd;'>$paymentId</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; font-weight: bold; border-bottom: 1px solid #dddddd;'>Amount:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dddddd;'>$amount $currency</td>
                        </tr>
                        $additionalInfo
                    </table>
                    <p style='font-size: 14px; color: #666666; margin-top: 20px;'>This is an automated notification from Karma Experience. Please do not reply to this email.</p>
                </div>
                <div style='background: #f1f1f1; padding: 10px; text-align: center; border-radius: 0 0 5px 5px;'>
                    <p style='margin: 0; font-size: 14px; color: #555555;'>Karma Experience | Contact us: support@karmaexperience.com</p>
                </div>
            </div>
        </body>
    </html>";
}
