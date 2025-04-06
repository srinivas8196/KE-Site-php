<?php
require_once 'db.php';
require_once 'email_notification.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists in the database
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // To prevent email enumeration, show the same message as success
            $success = "If your email is registered in our system, you will receive password reset instructions.";
        } else {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            $username = $user['username'];
            
            // Generate a secure token
            $token = bin2hex(random_bytes(32));
            
            // Store token in database (expires in 24 hours)
            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id = ?");
            $stmt->bind_param("si", $token, $user_id);
            $stmt->execute();
            
            // Create password reset link
            $resetLink = getBaseUrl() . "/reset-password.php?token=" . urlencode($token) . "&email=" . urlencode($email);
            
            // Prepare email
            $subject = "Password Reset Request - Karma Experience";
            $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #f5f5f5; padding: 15px; border-bottom: 3px solid #4f46e5; }
                    .content { padding: 20px; }
                    .footer { font-size: 12px; color: #666; margin-top: 30px; padding-top: 15px; border-top: 1px solid #eee; }
                    .button { display: inline-block; background-color: #4f46e5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Password Reset Request</h2>
                    </div>
                    <div class='content'>
                        <p>Hello " . htmlspecialchars($username) . ",</p>
                        <p>We received a request to reset your password. Click the button below to reset your password:</p>
                        
                        <p style='margin-top: 20px; text-align: center;'>
                            <a href='" . $resetLink . "' class='button'>Reset Your Password</a>
                        </p>
                        
                        <p style='margin-top: 20px;'>If you didn't request a password reset, you can safely ignore this email.</p>
                        <p>This link will expire in 24 hours.</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message. Please do not reply to this email.</p>
                        <p>&copy; " . date('Y') . " Karma Experience. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Send email
            try {
                if (sendEmailViaSmtp($email, $subject, $message)) {
                    $success = "Password reset instructions have been sent to your email address.";
                } else {
                    $error = "Failed to send password reset email. Please try again later.";
                }
            } catch (Exception $e) {
                error_log("Error sending password reset email: " . $e->getMessage());
                $error = "Failed to send password reset email. Please try again later.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Karma Experience</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1E5F74, #133B5C);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .forgot-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 450px;
            padding: 2rem;
        }
        .forgot-logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .forgot-logo img {
            max-height: 60px;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-logo">
            <img src="assets/images/logo/KE-Gold.png" alt="Karma Experience">
        </div>
        
        <h2 class="text-center mb-4">Forgot Password</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success; ?>
            </div>
        <?php else: ?>
            <p class="text-center mb-4">Enter your email address below, and we'll send you instructions to reset your password.</p>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary btn-lg">Send Reset Link</button>
                </div>
            </form>
        <?php endif; ?>
        
        <div class="text-center mt-3">
            <a href="login.php" class="text-decoration-none">Back to Login</a>
        </div>
    </div>
</body>
</html>
