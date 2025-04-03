<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Extract transaction details from URL parameters
$status = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : 'pending';
$reference = isset($_GET['reference']) ? htmlspecialchars($_GET['reference']) : ''; // This will be the transaction ID
$email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
$customerName = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : 'Customer';
$amount = isset($_GET['amount']) ? htmlspecialchars($_GET['amount']) : '0.00';
$currency = isset($_GET['currency']) ? htmlspecialchars($_GET['currency']) : '';

// Get the current date for the payment date
$paymentDate = date('Y-m-d H:i:s');

// Assuming you've received the correct status (success, failed, or pending)
if ($status === 'success') {
    $message = "Payment Successful!";
} elseif ($status === 'failed') {
    $message = "Payment Failed. Please try again or contact support.";
} else {
    $message = "Payment is in progress. Please check back later.";
}

// Updated sendEmail function with a more professional look
function sendEmail($toEmail, $toName, $transactionId, $amount, $paymentDate, $currency, $isAdmin = false, $customerName = '', $customerEmail = '')
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
        $mail->Subject = $isAdmin ? 'New Payment Received - Karma Experience' : 'Thank You for Your Payment';

        $mail->Body = "
            <html>
                <head>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background-color: #f9f9f9;
                            margin: 0;
                            padding: 0;
                            color: #333;
                        }
                        .container {
                            width: 100%;
                            max-width: 600px;
                            margin: 0 auto;
                            padding: 20px;
                            background-color: #ffffff;
                            border-radius: 8px;
                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                        }
                        h1 {
                            color: #2c3e50;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 20px;
                        }
                        th, td {
                            padding: 12px;
                            border: 1px solid #e0e0e0;
                            text-align: left;
                        }
                        th {
                            background-color: #f1f1f1;
                        }
                        .button {
                            background-color: #3498db;
                            color: white;
                            padding: 12px 20px;
                            text-align: center;
                            text-decoration: none;
                            border-radius: 4px;
                            display: inline-block;
                        }
                        .footer {
                            font-size: 12px;
                            color: #888888;
                            text-align: center;
                            margin-top: 20px;
                        }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <h1>" . ($isAdmin ? "New Payment Received" : "Thank You for Your Payment") . "</h1>
                        <p>" . ($isAdmin ? "A new payment has been received." : "Thank you for your payment!") . "</p>
                        <table>
                            <tr>
                                <th>Transaction ID</th>
                                <td>$transactionId</td>
                            </tr>
                            <tr>
                                <th>Amount Paid</th>
                                <td>$amount $currency</td>
                            </tr>
                            <tr>
                                <th>Payment Date</th>
                                <td>$paymentDate</td>
                            </tr>
                            " . ($isAdmin ? "
                            <tr>
                                <th>Customer Name</th>
                                <td>$customerName</td>
                            </tr>
                            <tr>
                                <th>Customer Email</th>
                                <td>$customerEmail</td>
                            </tr>" : "") . "
                        </table>
                        <p>If you have any questions, feel free to <a href='mailto:res@karmaexperience.com' class='button'>Contact Us</a>.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; 2024 Karma Experience | <a href='https://karmaexperience.com/privacy-policy/' style='color: #888888;'>Privacy Policy</a></p>
                    </div>
                </body>
            </html>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
    }
}

// Send email to customer
sendEmail($email, $customerName, $reference, $amount, $paymentDate, $currency);

// Send email to admin with customer details
sendEmail('karma.payments@karmaexperience.com', 'Karma Experience Payment', $reference, $amount, $paymentDate, $currency, true, $customerName, $email);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - Karma Experience</title>
    <link rel="stylesheet" href="https://karmaexperience.com/assets/bootstrap5.0/css/bootstrap.min.css">
    <link rel="shortcut icon" href="https://www.karmaexperience.com/wp-content/themes/experience/images/karmaexperience-com.ico">
    <style>
        .header-image {
            background-image: url('./images/Thankyou.webp');
            height: 300px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-overlay h1 {
            color: #fff;
            font-size: 3rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        }

        .thank-you-message {
            text-align: center;
            padding: 40px 20px;
        }
    </style>
</head>

<body>
    <div class="header-image">
        <div class="header-overlay">
            <h1>Thank You</h1>
        </div>
    </div>
    <div class="thank-you-message">
        <?php if ($status === 'success') { ?>
            <h2>Payment Successful!</h2>
            <p>Thank you for your payment, <?= htmlspecialchars($customerName); ?>.</p>
            <p>Your Transaction ID: <b><?= htmlspecialchars($reference); ?></b></p>
            <p>Amount Paid: <b><?= htmlspecialchars($amount); ?> <?= htmlspecialchars($currency); ?></b></p>
            <p>Payment Date: <b><?= $paymentDate; ?></b></p>
            <p>If you have any questions, please contact us at <a href="mailto:res@karmaexperience.com">res@karmaexperience.com</a>.</p>
        <?php } else { ?>
            <h2>Payment Failed</h2>
            <p>Oops! Something went wrong with your payment.</p>
            <p>Please try again or contact us for assistance.</p>
        <?php } ?>
        <a class="btn btn-primary" href="https://karmaexperience.com" role="button">Return to Homepage</a>
    </div>
    <footer class="text-center mt-4">
        <p>&copy; 2024 Karma Experience | <a href="https://karmaexperience.com/privacy-policy/">Privacy Policy</a></p>
    </footer>
    <script src="https://karmaexperience.com/assets/js/jquery.min.js"></script>
    <script src="https://karmaexperience.com/assets/bootstrap5.0/js/bootstrap.min.js"></script>
</body>

</html>
