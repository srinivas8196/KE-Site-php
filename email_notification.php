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
        
        // Create email body with improved styling (matching the user credential email)
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
            <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
            <title>New User Registration</title>
            <style>
                @media only screen and (max-width: 620px) {
                    table.body h1 {
                        font-size: 28px !important;
                        margin-bottom: 10px !important;
                    }
                    table.body p,
                    table.body ul,
                    table.body ol,
                    table.body td,
                    table.body span {
                        font-size: 16px !important;
                    }
                    table.body .container {
                        padding: 0 !important;
                        width: 100% !important;
                    }
                    table.body .content {
                        padding: 0 !important;
                    }
                    table.body .wrapper {
                        padding: 10px !important;
                    }
                }
                
                body {
                    background-color: #f6f6f6;
                    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                    -webkit-font-smoothing: antialiased;
                    font-size: 16px;
                    line-height: 1.4;
                    margin: 0;
                    padding: 0;
                    -ms-text-size-adjust: 100%;
                    -webkit-text-size-adjust: 100%;
                }
                
                table {
                    border-collapse: separate;
                    mso-table-lspace: 0pt;
                    mso-table-rspace: 0pt;
                    width: 100%;
                }
                
                table td {
                    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                    font-size: 16px;
                    vertical-align: top;
                }
                
                .body {
                    background-color: #f6f6f6;
                    width: 100%;
                }
                
                .container {
                    display: block;
                    margin: 0 auto !important;
                    max-width: 580px;
                    padding: 10px;
                }
                
                .content {
                    box-sizing: border-box;
                    display: block;
                    margin: 0 auto;
                    max-width: 580px;
                    padding: 10px;
                }
                
                .main {
                    background: #ffffff;
                    border-radius: 4px;
                    width: 100%;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                }
                
                .wrapper {
                    box-sizing: border-box;
                    padding: 20px;
                }
                
                .content-block {
                    padding-bottom: 10px;
                    padding-top: 10px;
                }
                
                .header {
                    padding: 20px 0;
                    text-align: center;
                    background-color: #4834d4;
                    border-top-left-radius: 4px;
                    border-top-right-radius: 4px;
                }
                
                .header h1 {
                    color: #ffffff;
                    font-size: 26px;
                    font-weight: 700;
                    margin: 0;
                }
                
                .footer {
                    clear: both;
                    margin-top: 10px;
                    text-align: center;
                    width: 100%;
                }
                
                .footer td,
                .footer p,
                .footer span,
                .footer a {
                    color: #999999;
                    font-size: 13px;
                    text-align: center;
                }
                
                h1, h2, h3, h4 {
                    color: #333333;
                    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                    font-weight: 400;
                    line-height: 1.4;
                    margin: 0 0 20px;
                }
                
                h1 {
                    font-size: 26px;
                    font-weight: 700;
                    text-align: center;
                }
                
                p, ul, ol {
                    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                    font-size: 16px;
                    font-weight: normal;
                    margin: 0 0 15px;
                }
                
                .user-info {
                    background-color: #f8f9ff;
                    padding: 15px;
                    border-radius: 6px;
                    border-left: 4px solid #4834d4;
                    margin-bottom: 20px;
                }
                
                .btn {
                    box-sizing: border-box;
                    width: 100%;
                }
                
                .btn > tbody > tr > td {
                    padding-bottom: 15px;
                }
                
                .btn table {
                    width: auto;
                }
                
                .btn table td {
                    background-color: #ffffff;
                    border-radius: 5px;
                    text-align: center;
                }
                
                .btn a {
                    background-color: #4834d4;
                    border: solid 1px #4834d4;
                    border-radius: 5px;
                    box-sizing: border-box;
                    color: #ffffff;
                    cursor: pointer;
                    display: inline-block;
                    font-size: 16px;
                    font-weight: bold;
                    margin: 0;
                    padding: 12px 25px;
                    text-decoration: none;
                    text-transform: capitalize;
                }
                
                .btn-primary a {
                    background-color: #4834d4;
                    border-color: #4834d4;
                    color: #ffffff;
                }
                
                .btn-primary a:hover {
                    background-color: #3a2bbd;
                    border-color: #3a2bbd;
                }
                
                .align-center {
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"body\">
                <tr>
                    <td>&nbsp;</td>
                    <td class=\"container\">
                        <div class=\"content\">
                            <div class=\"header\">
                                <h1>New User Registration</h1>
                            </div>
                            
                            <!-- START MAIN CONTENT AREA -->
                            <table role=\"presentation\" class=\"main\">
                                <tr>
                                    <td class=\"wrapper\">
                                        <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
                                            <tr>
                                                <td>
                                                    <p>A new user has been registered in the Karma Experience system.</p>
                                                    
                                                    <div class=\"user-info\">
                                                        <p><strong>Username:</strong> {$userData['username']}</p>
                                                        <p><strong>Email:</strong> {$userData['email']}</p>
                                                        <p><strong>Role:</strong> {$userType}</p>
                                                        <p><strong>Created At:</strong> " . date('Y-m-d H:i:s') . "</p>
                                                    </div>
                                                    
                                                    <p>You can review this user's details in the admin dashboard.</p>
                                                    
                                                    <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"btn btn-primary\">
                                                        <tbody>
                                                            <tr>
                                                                <td align=\"center\">
                                                                    <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><a href=\"" . getBaseUrl() . "/manage_users.php\" target=\"_blank\">View User Management</a></td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            <!-- END MAIN CONTENT AREA -->
                            
                            <!-- START FOOTER -->
                            <div class=\"footer\">
                                <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
                                    <tr>
                                        <td class=\"content-block\">
                                            <p>This is an automated message. Please do not reply to this email.</p>
                                            <p>&copy; " . date('Y') . " Karma Experience. All rights reserved.</p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <!-- END FOOTER -->
                        </div>
                    </td>
                    <td>&nbsp;</td>
                </tr>
            </table>
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
    // Load environment variables if not already loaded
    if (!function_exists('getenv') || empty(getenv('SMTP_HOST'))) {
        // Try to load .env file manually if dotenv isn't available
        if (file_exists(__DIR__ . '/.env')) {
            $envFile = file_get_contents(__DIR__ . '/.env');
            $lines = explode("\n", $envFile);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    // Remove quotes if present
                    $value = trim($value, '"\'');
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
    }

    // Get settings from environment or use defaults
    $settings = [
        'host' => getenv('SMTP_HOST') ?: ($_ENV['SMTP_HOST'] ?? 'smtp.example.com'),
        'port' => getenv('SMTP_PORT') ?: ($_ENV['SMTP_PORT'] ?? 587),
        'username' => getenv('SMTP_USERNAME') ?: ($_ENV['SMTP_USERNAME'] ?? ''),
        'password' => getenv('SMTP_PASSWORD') ?: ($_ENV['SMTP_PASSWORD'] ?? ''),
        'encryption' => getenv('SMTP_ENCRYPTION') ?: ($_ENV['SMTP_ENCRYPTION'] ?? 'tls'),
        'from_email' => getenv('MAIL_FROM_ADDRESS') ?: getenv('SMTP_FROM_EMAIL') ?: ($_ENV['MAIL_FROM_ADDRESS'] ?? $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@karmaexperience.in'),
        'from_name' => getenv('MAIL_FROM_NAME') ?: getenv('SMTP_FROM_NAME') ?: ($_ENV['MAIL_FROM_NAME'] ?? $_ENV['SMTP_FROM_NAME'] ?? 'Karma Experience')
    ];
    
    // Log settings (without password)
    error_log("SMTP Settings loaded - Host: {$settings['host']}, Port: {$settings['port']}, Username: {$settings['username']}, From Email: {$settings['from_email']}");
    
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
    
    global $conn; // Access the database connection
    
    // Generate a password reset token
    $reset_token = bin2hex(random_bytes(32));
    $user_id = $userData['id'];
    
    // Store the reset token in the database
    try {
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 72 HOUR) WHERE id = ?");
        $stmt->bind_param("si", $reset_token, $user_id);
        $stmt->execute();
        error_log("Reset token stored for user ID: $user_id");
    } catch (Exception $e) {
        error_log("Error storing reset token: " . $e->getMessage());
        // Continue anyway to send the basic email
    }
    
    // Reset link with token
    $reset_link = getBaseUrl() . "/reset-password.php?token=" . urlencode($reset_token) . "&email=" . urlencode($userData['email']) . "&first_time=1";
    
    // Email content
    $subject = "Your New Account at Karma Experience";
    
    // Format the user type properly
    $userType = formatRoleName($userData['user_type']);
    
    // Create email body with improved styling
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
        <title>Welcome to Karma Experience</title>
        <style>
            @media only screen and (max-width: 620px) {
                table.body h1 {
                    font-size: 28px !important;
                    margin-bottom: 10px !important;
                }
                table.body p,
                table.body ul,
                table.body ol,
                table.body td,
                table.body span {
                    font-size: 16px !important;
                }
                table.body .container {
                    padding: 0 !important;
                    width: 100% !important;
                }
                table.body .content {
                    padding: 0 !important;
                }
                table.body .wrapper {
                    padding: 10px !important;
                }
            }
            
            body {
                background-color: #f6f6f6;
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                -webkit-font-smoothing: antialiased;
                font-size: 16px;
                line-height: 1.4;
                margin: 0;
                padding: 0;
                -ms-text-size-adjust: 100%;
                -webkit-text-size-adjust: 100%;
            }
            
            table {
                border-collapse: separate;
                mso-table-lspace: 0pt;
                mso-table-rspace: 0pt;
                width: 100%;
            }
            
            table td {
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                font-size: 16px;
                vertical-align: top;
            }
            
            .body {
                background-color: #f6f6f6;
                width: 100%;
            }
            
            .container {
                display: block;
                margin: 0 auto !important;
                max-width: 580px;
                padding: 10px;
            }
            
            .content {
                box-sizing: border-box;
                display: block;
                margin: 0 auto;
                max-width: 580px;
                padding: 10px;
            }
            
            .main {
                background: #ffffff;
                border-radius: 4px;
                width: 100%;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            
            .wrapper {
                box-sizing: border-box;
                padding: 20px;
            }
            
            .content-block {
                padding-bottom: 10px;
                padding-top: 10px;
            }
            
            .header {
                padding: 20px 0;
                text-align: center;
                background-color: #4834d4;
                border-top-left-radius: 4px;
                border-top-right-radius: 4px;
            }
            
            .header h1 {
                color: #ffffff;
                font-size: 26px;
                font-weight: 700;
                margin: 0;
            }
            
            .footer {
                clear: both;
                margin-top: 10px;
                text-align: center;
                width: 100%;
            }
            
            .footer td,
            .footer p,
            .footer span,
            .footer a {
                color: #999999;
                font-size: 13px;
                text-align: center;
            }
            
            h1, h2, h3, h4 {
                color: #333333;
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                font-weight: 400;
                line-height: 1.4;
                margin: 0 0 20px;
            }
            
            h1 {
                font-size: 26px;
                font-weight: 700;
                text-align: center;
            }
            
            p, ul, ol {
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                font-size: 16px;
                font-weight: normal;
                margin: 0 0 15px;
            }
            
            .creds-box {
                background-color: #f8f9ff;
                padding: 15px;
                border-radius: 6px;
                border-left: 4px solid #4834d4;
                margin-bottom: 20px;
            }
            
            .important {
                color: #e63946;
                font-weight: bold;
            }
            
            .btn {
                box-sizing: border-box;
                width: 100%;
            }
            
            .btn > tbody > tr > td {
                padding-bottom: 15px;
            }
            
            .btn table {
                width: auto;
            }
            
            .btn table td {
                background-color: #ffffff;
                border-radius: 5px;
                text-align: center;
            }
            
            .btn a {
                background-color: #4834d4;
                border: solid 1px #4834d4;
                border-radius: 5px;
                box-sizing: border-box;
                color: #ffffff;
                cursor: pointer;
                display: inline-block;
                font-size: 16px;
                font-weight: bold;
                margin: 0;
                padding: 12px 25px;
                text-decoration: none;
                text-transform: capitalize;
            }
            
            .btn-primary a {
                background-color: #4834d4;
                border-color: #4834d4;
                color: #ffffff;
            }
            
            .btn-primary a:hover {
                background-color: #3a2bbd;
                border-color: #3a2bbd;
            }
            
            .align-center {
                text-align: center;
            }
        </style>
    </head>
    <body>
        <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"body\">
            <tr>
                <td>&nbsp;</td>
                <td class=\"container\">
                    <div class=\"content\">
                        <div class=\"header\">
                            <h1>Welcome to Karma Experience</h1>
                        </div>
                        
                        <!-- START MAIN CONTENT AREA -->
                        <table role=\"presentation\" class=\"main\">
                            <tr>
                                <td class=\"wrapper\">
                                    <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
                                        <tr>
                                            <td>
                                                <p>Hello " . htmlspecialchars($userData['username']) . ",</p>
                                                <p>Your account has been created successfully. Below are your login credentials:</p>
                                                
                                                <div class=\"creds-box\">
                                                    <p><strong>Username:</strong> " . htmlspecialchars($userData['username']) . "</p>
                                                    <p><strong>Temporary Password:</strong> " . htmlspecialchars($password) . "</p>
                                                    <p><strong>Role:</strong> " . htmlspecialchars($userType) . "</p>
                                                </div>
                                                
                                                <p class=\"important\">For security reasons, you are required to change your password on first login.</p>
                                                
                                                <p>Please click the button below to set your own password:</p>
                                                
                                                <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"btn btn-primary\">
                                                    <tbody>
                                                        <tr>
                                                            <td align=\"center\">
                                                                <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td><a href=\"" . $reset_link . "\" target=\"_blank\">Set Your Password</a></td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                
                                                <p>This link will expire in 72 hours. If you don't set your password within this time, please contact your administrator.</p>
                                                
                                                <p>After setting your password, you can login at: <a href=\"" . getBaseUrl() . "/login.php\">" . getBaseUrl() . "/login.php</a></p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <!-- END MAIN CONTENT AREA -->
                        
                        <!-- START FOOTER -->
                        <div class=\"footer\">
                            <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
                                <tr>
                                    <td class=\"content-block\">
                                        <p>This is an automated message. Please do not reply to this email.</p>
                                        <p>If you did not request this account, please contact us immediately.</p>
                                        <p>&copy; " . date('Y') . " Karma Experience. All rights reserved.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <!-- END FOOTER -->
                    </div>
                </td>
                <td>&nbsp;</td>
            </tr>
        </table>
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