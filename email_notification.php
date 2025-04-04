<?php
/**
 * Email Notification Helper
 * 
 * This file contains functions to send email notifications for various events
 * in the application. Currently supports:
 * - New user creation notifications
 */

// Include database connection at the file level
require_once 'db.php';

// Function to send email notification when a new user is created
function sendNewUserNotification($userData) {
    // Make database connection available if needed
    global $conn;
    
    // Debug log
    error_log("Sending notification for new user: " . $userData['username']);
    
    // Get admin emails to notify
    try {
        $adminEmails = getAdminEmails();
        
        if (empty($adminEmails)) {
            // If in test environment and test_email is set, use that
            if (isset($_POST['test_email'])) {
                error_log("No admin emails found, using test email instead");
                $adminEmails = [$_POST['test_email']];
            } else {
                error_log("No admin emails found to send notification");
                return false;
            }
        }
        
        error_log("Found " . count($adminEmails) . " admin emails to notify");
        
        // Email content
        $subject = "New User Registration - Karma Experience";
        
        // Format the user type properly
        $userType = formatRoleName($userData['user_type']);
        
        // Create email body
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f5f5f5; padding: 15px; border-bottom: 3px solid #4f46e5; }
                .content { padding: 20px; }
                .user-info { background-color: #f9fafb; padding: 15px; margin: 15px 0; border-left: 3px solid #4f46e5; }
                .footer { font-size: 12px; color: #666; margin-top: 30px; padding-top: 15px; border-top: 1px solid #eee; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New User Registration</h2>
                </div>
                <div class='content'>
                    <p>A new user has been registered in the Karma Experience system.</p>
                    
                    <div class='user-info'>
                        <p><strong>Username:</strong> {$userData['username']}</p>
                        <p><strong>Email:</strong> {$userData['email']}</p>
                        <p><strong>Role:</strong> {$userType}</p>
                        <p><strong>Created At:</strong> " . date('Y-m-d H:i:s') . "</p>
                    </div>
                    
                    <p>You can review this user's details in the admin dashboard.</p>
                    <p><a href='" . getBaseUrl() . "/manage_users.php'>Go to User Management</a></p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>&copy; " . date('Y') . " Karma Experience. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Use SMTP to send emails
        $sentCount = sendEmailViaSmtp($adminEmails, $subject, $message);
        error_log("Sent notification to $sentCount out of " . count($adminEmails) . " admin emails");
        return $sentCount > 0;
    } catch (Exception $e) {
        error_log("Error in sendNewUserNotification: " . $e->getMessage());
        throw $e; // Re-throw the exception for handling in the calling code
    }
}

/**
 * Send email using SMTP with credentials from .env file
 * 
 * @param array|string $recipients List of email addresses or single email to send to
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $fromName Optional sender name override
 * @return int Number of successfully sent emails
 */
function sendEmailViaSmtp($recipients, $subject, $body, $fromName = '') {
    // Make sure recipients is an array
    if (!is_array($recipients)) {
        $recipients = [$recipients];
    }
    
    // Filter out empty emails
    $recipients = array_filter($recipients, function($email) {
        return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
    });
    
    if (empty($recipients)) {
        error_log("No valid email recipients provided");
        return 0;
    }
    
    // Load SMTP settings from .env
    $smtpSettings = getSmtpSettings();
    
    // Override from name if provided
    if (!empty($fromName)) {
        $smtpSettings['from_name'] = $fromName;
    }
    
    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: {$smtpSettings['from_name']} <{$smtpSettings['from_email']}>" . "\r\n";
    
    // Debug log
    error_log("Attempting to send email with subject: " . $subject . " to " . count($recipients) . " recipient(s): " . implode(', ', $recipients));
    
    // Track successful sends
    $sentCount = 0;
    
    // If PHPMailer is installed, use it
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        try {
            require_once __DIR__ . '/vendor/autoload.php';
            
            // Initialize PHPMailer if available
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                error_log("Using PHPMailer for sending emails");
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                
                // Server settings
                $mail->SMTPDebug = 2; // Enable verbose debug output (log only)
                $mail->Debugoutput = function($str, $level) {
                    error_log("PHPMailer [$level]: $str");
                };
                
                $mail->isSMTP();
                $mail->Host = $smtpSettings['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $smtpSettings['username'];
                $mail->Password = $smtpSettings['password'];
                $mail->SMTPSecure = $smtpSettings['encryption']; // tls or ssl
                $mail->Port = $smtpSettings['port'];
                
                // Sender
                $mail->setFrom($smtpSettings['from_email'], $smtpSettings['from_name']);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $body;
                
                // Add recipients
                foreach ($recipients as $email) {
                    $mail->addAddress($email);
                    
                    try {
                        $mail->send();
                        $sentCount++;
                        error_log("Successfully sent email to: $email");
                    } catch (Exception $e) {
                        error_log("Failed to send email to $email: " . $e->getMessage());
                    }
                    
                    // Clear recipient for next email
                    $mail->clearAddresses();
                }
                
                return $sentCount;
            } else {
                error_log("PHPMailer class not found, falling back to mail()");
            }
        } catch (Exception $e) {
            error_log("PHPMailer error: " . $e->getMessage());
        }
    } else {
        error_log("vendor/autoload.php not found, falling back to mail()");
    }
    
    // Fall back to using mail() function
    error_log("Using PHP mail() function as fallback");
    foreach ($recipients as $email) {
        // Attempt to send using the configured mail function
        if (mail($email, $subject, $body, $headers)) {
            $sentCount++;
            error_log("Successfully sent email to: $email via mail()");
        } else {
            error_log("Failed to send email to: $email via mail(), error: " . error_get_last()['message'] ?? 'Unknown error');
        }
    }
    
    return $sentCount;
}

/**
 * Get SMTP settings from .env file
 * 
 * @return array SMTP configuration settings
 */
function getSmtpSettings() {
    // Default SMTP settings if not found in .env
    $settings = [
        'host' => $_ENV['SMTP_HOST'] ?? 'smtp.example.com',
        'port' => $_ENV['SMTP_PORT'] ?? 587,
        'username' => $_ENV['SMTP_USERNAME'] ?? '',
        'password' => $_ENV['SMTP_PASSWORD'] ?? '',
        'encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls', // tls or ssl
        'from_email' => $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@karmaexperience.in',
        'from_name' => $_ENV['SMTP_FROM_NAME'] ?? 'Karma Experience'
    ];
    
    // Log settings (without password)
    error_log("SMTP Settings loaded - Host: {$settings['host']}, Port: {$settings['port']}, Username: {$settings['username']}");
    
    return $settings;
}

// Helper function to get admin emails
function getAdminEmails() {
    // Use global database connection
    global $conn;
    
    // Debug - check if we have a valid connection
    if (!$conn) {
        error_log("Database connection is not available in getAdminEmails()");
        
        // Try to reconnect
        require_once 'db.php';
        global $conn; // Re-declare global after including db.php
        
        if (!$conn) {
            error_log("Failed to reconnect to database in getAdminEmails()");
            return [];
        } else {
            error_log("Successfully reconnected to database in getAdminEmails()");
        }
    }
    
    $emails = [];
    
    try {
        // Get super_admin and admin emails from the database
        $query = "SELECT email FROM users WHERE user_type IN ('super_admin', 'admin')";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $emails[] = $row['email'];
            }
            error_log("Found " . count($emails) . " admin emails in database");
        } else {
            error_log("No admin emails found in database query");
            // Add a fallback email for testing if no admins found
            $emails[] = $_POST['test_email'] ?? '';
        }
    } catch (Exception $e) {
        error_log("Error in getAdminEmails: " . $e->getMessage());
    }
    
    return $emails;
}

// Helper function to get the base URL
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    
    return $protocol . '://' . $host . $path;
}

// Helper function to format role name
function formatRoleName($role) {
    switch ($role) {
        case 'super_admin':
            return 'Super Admin';
        case 'admin':
            return 'Admin';
        case 'campaign_manager':
            return 'Campaign Manager';
        case 'user':
            return 'Regular User';
        default:
            return ucfirst($role);
    }
}

// Send login credentials to a newly created user
function sendUserCredentials($userData, $password) {
    // Debug log
    error_log("Sending login credentials to new user: " . $userData['username']);
    
    // Email content
    $subject = "Your New Account at Karma Experience";
    
    // Format the user type properly
    $userType = formatRoleName($userData['user_type']);
    
    // Create email body
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #f5f5f5; padding: 15px; border-bottom: 3px solid #4f46e5; }
            .content { padding: 20px; }
            .credentials { background-color: #f9fafb; padding: 15px; margin: 15px 0; border-left: 3px solid #4f46e5; }
            .footer { font-size: 12px; color: #666; margin-top: 30px; padding-top: 15px; border-top: 1px solid #eee; }
            .button { display: inline-block; background-color: #4f46e5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Welcome to Karma Experience</h2>
            </div>
            <div class='content'>
                <p>Hello " . htmlspecialchars($userData['username']) . ",</p>
                <p>Your account has been created successfully. Below are your login credentials:</p>
                
                <div class='credentials'>
                    <p><strong>Username:</strong> " . htmlspecialchars($userData['username']) . "</p>
                    <p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>
                    <p><strong>Role:</strong> " . htmlspecialchars($userType) . "</p>
                </div>
                
                <p>Please keep these credentials safe and change your password after your first login.</p>
                
                <p style='margin-top: 20px;'><a href='" . getBaseUrl() . "/login.php' class='button'>Login to Your Account</a></p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>If you did not request this account, please contact us immediately.</p>
                <p>&copy; " . date('Y') . " Karma Experience. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send email
    try {
        $result = sendEmailViaSmtp([$userData['email']], $subject, $message);
        return $result > 0;
    } catch (Exception $e) {
        error_log("Error sending credentials to user: " . $e->getMessage());
        return false;
    }
}
?> 