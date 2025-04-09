<?php
session_start();
// Check for user authentication
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
    exit();
}

// Include necessary files
require_once 'db.php';
require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Function to get settings from database
function get_setting($key, $default = '') {
    global $pdo, $conn;
    
    try {
        // Try using PDO
        if (isset($pdo) && $pdo) {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['setting_value'] : $default;
        } 
        // Try using mysqli
        elseif (isset($conn) && $conn) {
            $key = $conn->real_escape_string($key);
            $result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = '$key'");
            if ($result && $row = $result->fetch_assoc()) {
                return $row['setting_value'];
            }
        }
    } catch (Exception $e) {
        error_log('Error getting setting: ' . $e->getMessage());
    }
    
    return $default;
}

// Process only POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get test email
    $test_email = filter_var($_POST['test_email'] ?? '', FILTER_VALIDATE_EMAIL);
    
    if (!$test_email) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address']);
        exit();
    }
    
    // Get SMTP settings from POST (for direct testing) or from database
    $smtp_host = $_POST['smtp_host'] ?? get_setting('smtp_host', '');
    $smtp_port = $_POST['smtp_port'] ?? get_setting('smtp_port', '587');
    $smtp_username = $_POST['smtp_username'] ?? get_setting('smtp_username', '');
    $smtp_password = $_POST['smtp_password'] ?? get_setting('smtp_password', '');
    $smtp_encryption = $_POST['smtp_encryption'] ?? get_setting('smtp_encryption', 'tls');
    $smtp_from_email = $_POST['smtp_from_email'] ?? get_setting('smtp_from_email', '');
    $smtp_from_name = $_POST['smtp_from_name'] ?? get_setting('smtp_from_name', 'Karma Experience');
    
    // Validate required SMTP settings
    if (empty($smtp_host) || empty($smtp_port) || empty($smtp_from_email)) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error', 
            'message' => 'SMTP Host, Port and From Email are required. Please complete your SMTP settings first.'
        ]);
        exit();
    }
    
    // Debug log file
    $log_file = 'email_debug_' . date('Y-m-d') . '.log';
    $log = fopen($log_file, 'a');
    fwrite($log, "\n\n=== " . date('Y-m-d H:i:s') . " === Test email to: $test_email\n");
    
    try {
        // Initialize PHPMailer
        $mail = new PHPMailer(true);
        
        // Enable verbose debug output
        $mail->SMTPDebug = 3;
        $mail->Debugoutput = function($str, $level) use ($log) {
            fwrite($log, "$str\n");
        };
        
        // Configure SMTP
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->Port = $smtp_port;
        
        // Set SMTP authentication if username is provided
        if (!empty($smtp_username)) {
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_username;
            $mail->Password = $smtp_password;
        } else {
            $mail->SMTPAuth = false;
        }
        
        // Configure encryption
        if ($smtp_encryption === 'tls') {
            $mail->SMTPSecure = 'tls';
        } elseif ($smtp_encryption === 'ssl') {
            $mail->SMTPSecure = 'ssl';
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }
        
        // Optional: Disable SSL certificate verification for testing
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Additional debug info
        fwrite($log, "Configuration summary:\n");
        fwrite($log, "Host: $smtp_host\n");
        fwrite($log, "Port: $smtp_port\n");
        fwrite($log, "Username: $smtp_username\n");
        fwrite($log, "Encryption: $smtp_encryption\n");
        fwrite($log, "From Email: $smtp_from_email\n");
        fwrite($log, "From Name: $smtp_from_name\n");
        fwrite($log, "SMTPSecure: " . $mail->SMTPSecure . "\n");
        fwrite($log, "SMTPAuth: " . ($mail->SMTPAuth ? 'true' : 'false') . "\n");
        
        // Set sender
        $mail->setFrom($smtp_from_email, $smtp_from_name);
        $mail->addReplyTo($smtp_from_email, $smtp_from_name);
        
        // Add recipient
        $mail->addAddress($test_email);
        
        // Set email content
        $mail->isHTML(true);
        $mail->Subject = 'Test Email from Karma Experience';
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;">
                <h2 style="color: #B4975A;">Karma Experience - Test Email</h2>
                <p>Hello,</p>
                <p>This is a test email sent from the Karma Experience admin panel. If you\'re receiving this email, it means your SMTP settings are configured correctly.</p>
                <p>SMTP Configuration:</p>
                <ul>
                    <li>Host: ' . htmlspecialchars($smtp_host) . '</li>
                    <li>Port: ' . htmlspecialchars($smtp_port) . '</li>
                    <li>Encryption: ' . htmlspecialchars($smtp_encryption) . '</li>
                    <li>From Email: ' . htmlspecialchars($smtp_from_email) . '</li>
                </ul>
                <p>Time sent: ' . date('Y-m-d H:i:s') . '</p>
                <p style="margin-top: 20px;">Best regards,<br>Karma Experience Team</p>
            </div>
        ';
        $mail->AltBody = 'This is a test email from Karma Experience. If you\'re receiving this email, your SMTP settings are configured correctly.';
        
        // Send the email
        $mail->send();
        fwrite($log, "Email sent successfully!\n");
        fclose($log);
        
        // Return success JSON
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'Test email sent successfully! Please check your inbox (and spam folder).'
        ]);
        
    } catch (Exception $e) {
        // Log error
        fwrite($log, "Mailer Error: " . (isset($mail) ? $mail->ErrorInfo : "PHPMailer not initialized") . "\n");
        fwrite($log, "Exception: " . $e->getMessage() . "\n");
        fclose($log);
        
        // Create a user-friendly error message
        $errorMsg = $e->getMessage();
        $friendlyMessage = 'Failed to send test email';
        
        // Provide more specific error messages based on common issues
        if (strpos($errorMsg, 'Could not connect to SMTP host') !== false) {
            $friendlyMessage = 'Could not connect to SMTP host. Please verify your SMTP Host and Port settings.';
        } elseif (strpos($errorMsg, 'Authentication failure') !== false || strpos($errorMsg, 'Password not accepted') !== false) {
            $friendlyMessage = 'Authentication failed. Please check your username and password.';
        } elseif (strpos($errorMsg, 'Mailbox name not allowed') !== false || strpos($errorMsg, 'Invalid address') !== false) {
            $friendlyMessage = 'Invalid email address format. Please check your From Email address.';
        } elseif (strpos($errorMsg, 'ssl') !== false || strpos($errorMsg, 'certificate') !== false) {
            $friendlyMessage = 'SSL/TLS certificate issue. Try using a different encryption setting.';
        }
        
        // Return error JSON with detailed message
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $friendlyMessage,
            'details' => $errorMsg // Include the original error for debugging
        ]);
    }
} else {
    // Return error for non-POST requests
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
?> 