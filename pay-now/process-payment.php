<?php
require('razorpay-php/Razorpay.php');
require 'vendor/autoload.php'; // For PHPMailer

use Razorpay\Api\Api;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Razorpay credentials
$razorpay_key = "rzp_live_D5lhKkL7KTKaGs"; // Replace with your Razorpay key
$razorpay_secret = "zD5uGpWDah89EjMNiEEL7ASe"; // Replace with your Razorpay secret
$api = new Api($razorpay_key, $razorpay_secret);

// Sanitize and validate input data
$name_first = htmlspecialchars(trim($_POST['name_first']));
$name_last = htmlspecialchars(trim($_POST['name_last']));
$email = filter_var($_POST['E_mail'], FILTER_VALIDATE_EMAIL);
$phone = preg_match('/^\d{10}$/', $_POST['phonefront']) ? $_POST['phonefront'] : null;
$amount = isset($_POST['pricing']) && is_numeric($_POST['pricing']) ? round($_POST['pricing'] * 100) : 0; // Convert amount to paise

// Check for missing or invalid data
if (!$name_first || !$name_last || !$email || !$phone || $amount <= 0) {
    echo json_encode(['error' => 'Invalid input data. Please check your details and try again.']);
    exit;
}

// Razorpay Order Creation
if (empty($_POST['razorpay_payment_id'])) {
    try {
        $order = $api->order->create([
            'receipt' => 'order_rcptid_' . uniqid(),
            'amount' => $amount,
            'currency' => 'INR'
        ]);

        // Send response back to JavaScript
        echo json_encode([
            'orderId' => $order['id'],
            'amount' => $order['amount']
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Unable to create order: ' . $e->getMessage()]);
    }
    exit;
}

// Razorpay Payment Capture
$razorpay_payment_id = $_POST['razorpay_payment_id'] ?? null;
if (!empty($razorpay_payment_id)) {
    try {
        $payment = $api->payment->fetch($razorpay_payment_id);
        $payment->capture(['amount' => $payment['amount']]);

        // Payment successful, send emails
        sendEmailNotifications($name_first, $name_last, $email, $phone, $payment['amount'] / 100, $razorpay_payment_id);

        // Redirect to thank-you page
        header('Location: https://karmaexperience.com/thank-you-for-the-payment/');
        exit;
    } catch (Exception $e) {
        echo "Payment failed: " . $e->getMessage();
    }
}

// Email Function
function sendEmailNotifications($firstName, $lastName, $email, $phone, $amount, $transactionId)
{
    $mail = new PHPMailer(true);
    try {
        // SMTP Config
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'karma.payments@karmaexperience.com'; // Replace with your SMTP email
        $mail->Password = 'asurjpjrnwjfdfvd'; // Replace with your SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Admin Email
        $mail->setFrom('karma.payments@karmaexperience.com', 'Karma Experience Payment');
        $mail->addAddress('karma.payments@karmaexperience.com', 'Karma Experience Payment'); // Replace with admin email

        $mail->isHTML(true);
        $mail->Subject = "New Karma Experience Website Payment Received";
        $mail->Body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <h2 style='color: #444; text-align: center; margin-bottom: 20px;'>New Payment Received</h2>
        <p style='font-size: 16px;'>A new payment has been successfully made on <strong>Karma Experience</strong>. Below are the payment details:</p>
        <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
            <tr style='background-color: #f9f9f9;'>
                <th style='padding: 12px; border: 1px solid #ddd; text-align: left;'>Name</th>
                <td style='padding: 12px; border: 1px solid #ddd;'>$firstName $lastName</td>
            </tr>
            <tr>
                <th style='padding: 12px; border: 1px solid #ddd; text-align: left;'>Email</th>
                <td style='padding: 12px; border: 1px solid #ddd;'>$email</td>
            </tr>
            <tr style='background-color: #f9f9f9;'>
                <th style='padding: 12px; border: 1px solid #ddd; text-align: left;'>Phone</th>
                <td style='padding: 12px; border: 1px solid #ddd;'>$phone</td>
            </tr>
            <tr>
                <th style='padding: 12px; border: 1px solid #ddd; text-align: left;'>Amount</th>
                <td style='padding: 12px; border: 1px solid #ddd;'>INR $amount</td>
            </tr>
            <tr style='background-color: #f9f9f9;'>
                <th style='padding: 12px; border: 1px solid #ddd; text-align: left;'>Transaction ID</th>
                <td style='padding: 12px; border: 1px solid #ddd;'>$transactionId</td>
            </tr>
        </table>
        <p style='margin-top: 20px; font-size: 16px;'>Please log in to your admin panel for more details.</p>
        <p style='text-align: center; margin-top: 30px;'>
            <a href='https://karmaexperience.com/admin' style='text-decoration: none; color: white; background-color: #444; padding: 10px 20px; border-radius: 5px;'>Go to Admin Panel</a>
        </p>
    </div>";


        $mail->send();

        // Client Email
        $mail->clearAddresses();
        $mail->addAddress($email, "$firstName $lastName");
        $mail->Subject = "Payment Confirmation";
        $mail->Body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <h2 style='color: #444; text-align: center; margin-bottom: 20px;'>Payment Confirmation</h2>
        <p style='font-size: 16px;'>Dear $firstName $lastName,</p>
        <p style='font-size: 16px;'>Thank you for your payment! Here are your transaction details:</p>
        <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
            <tr style='background-color: #f9f9f9;'>
                <th style='padding: 12px; border: 1px solid #ddd; text-align: left;'>Amount</th>
                <td style='padding: 12px; border: 1px solid #ddd;'>INR $amount</td>
            </tr>
            <tr>
                <th style='padding: 12px; border: 1px solid #ddd; text-align: left;'>Transaction ID</th>
                <td style='padding: 12px; border: 1px solid #ddd;'>$transactionId</td>
            </tr>
        </table>
        <p style='margin-top: 20px; font-size: 16px;'>If you have any questions, feel free to contact us at:</p>
        <p style='font-size: 16px;'><a href='mailto:res@karmaexperience.com' style='text-decoration: none; color: #007BFF;'>res@karmaexperience.com</a></p>
        <p style='margin-top: 30px; font-size: 16px;'>Best regards,<br>Karma Experience Team</p>
        <p style='text-align: center; margin-top: 30px;'>
            <a href='https://karmaexperience.com' style='text-decoration: none; color: white; background-color: #8d734b; padding: 10px 20px; border-radius: 5px;'>Visit Our Website</a>
        </p>
    </div>";


        $mail->send();
    } catch (Exception $e) {
        error_log('Email Error: ' . $mail->ErrorInfo);
    }
}
