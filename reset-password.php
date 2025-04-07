<?php
session_start();
require_once 'db.php';

// Helper function to verify if a temporary password matches (without requiring login)
function verifyTemporaryPassword($username, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $user = $result->fetch_assoc();
    return password_verify($password, $user['password']);
}

$error = '';
$success = '';
$first_time = isset($_GET['first_time']) && $_GET['first_time'] == '1';
$page_title = $first_time ? 'Set Your Password' : 'Reset Password';

// Handle token-based reset (from email link)
if (isset($_GET['token']) && !empty($_GET['token']) && isset($_GET['email']) && !empty($_GET['email'])) {
    $token = trim($_GET['token']);
    $email = trim($_GET['email']);
    
    // Validate the token
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ? AND reset_token = ? AND reset_token_expires > NOW()");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = "Invalid or expired reset link. Please request a new password reset.";
        // Clear the token parameters to show the regular form
        $token = null;
        $email = null;
    } else {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        $username = $user['username'];
        // Valid token, show the reset form
    }
} 
// Handle regular password reset (logged in user)
else if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? '';
} 
// Neither token nor session - redirect to login
else {
    header("Location: login.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Token-based reset or first-time setup
    if (isset($_POST['token']) && isset($_POST['email'])) {
        $token = trim($_POST['token']);
        $email = trim($_POST['email']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate token again
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_token_expires > NOW()");
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "Invalid or expired reset link. Please request a new password reset.";
        } else if ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else if (strlen($new_password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            
            // Update password and clear token
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $success = "Your password has been updated successfully. You can now log in with your new password.";
                
                // Clear the token parameters
                $token = null;
                $email = null;
                
                // Redirect to login after 3 seconds
                header("refresh:3;url=login.php");
            } else {
                $error = "Failed to update password. Please try again.";
            }
        }
    } 
    // First-time password setup (using temporary password)
    else if (isset($_POST['first_time_setup']) && $_POST['first_time_setup'] == '1') {
        $username = $_POST['username'];
        $temp_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify the temporary credentials
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "Invalid username.";
        } else {
            $user = $result->fetch_assoc();
            
            // Verify the temporary password
            if (!password_verify($temp_password, $user['password'])) {
                $error = "Temporary password is incorrect.";
            } else if ($new_password !== $confirm_password) {
                $error = "New passwords do not match.";
            } else if (strlen($new_password) < 8) {
                $error = "Password must be at least 8 characters long.";
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user['id']);
                
                if ($stmt->execute()) {
                    $success = "Your password has been updated successfully. You can now log in with your new password.";
                    
                    // Redirect to login after 3 seconds
                    header("refresh:3;url=login.php");
                } else {
                    $error = "Failed to update password. Please try again.";
                }
            }
        }
    }
    // Regular password update (logged in user)
    else if (isset($_SESSION['user_id'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Get username for the logged-in user
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $username = $user_data['username'];
        
        // Try to verify password directly (faster than the full database check)
        $is_correct_password = verifyTemporaryPassword($username, $current_password);
        
        if (!$is_correct_password) {
            $error = "Current password is incorrect.";
        } else if ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } else if (strlen($new_password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success = "Your password has been updated successfully.";
            } else {
                $error = "Failed to update password. Please try again.";
            }
        }
    }
}

// Include header (only for logged in users)
if (isset($_SESSION['user_id'])) {
    include 'bheader.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Karma Experience</title>
    <?php if (!isset($_SESSION['user_id'])): ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: <?php echo isset($_SESSION['user_id']) ? '#f8f9fa' : 'linear-gradient(135deg, #1E5F74, #133B5C)'; ?>;
            <?php if (!isset($_SESSION['user_id'])): ?>
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            <?php endif; ?>
        }
        .reset-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 500px;
            padding: 2rem;
            margin: <?php echo isset($_SESSION['user_id']) ? '2rem auto' : '0'; ?>;
        }
        .reset-logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .reset-logo img {
            max-height: 60px;
        }
        .password-field {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        .requirements {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        .password-strength {
            height: 5px;
            margin-top: 0.5rem;
            border-radius: 5px;
            background-color: #e9ecef;
        }
        .password-strength-meter {
            height: 100%;
            border-radius: 5px;
            width: 0;
            transition: width 0.3s ease;
        }
        .weak { width: 25%; background-color: #dc3545; }
        .medium { width: 50%; background-color: #ffc107; }
        .strong { width: 75%; background-color: #0dcaf0; }
        .very-strong { width: 100%; background-color: #198754; }
    </style>
</head>
<body class="<?php echo isset($_SESSION['user_id']) ? 'bg-light' : ''; ?>">
    <?php if (!isset($_SESSION['user_id'])): ?>
    <div class="reset-container">
        <div class="reset-logo">
            <img src="assets/images/logo/KE-Gold.png" alt="Karma Experience">
        </div>
        
        <h2 class="text-center mb-4"><?php echo $page_title; ?></h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success; ?>
                <div class="text-center mt-3">
                    <p>Redirecting to login page...</p>
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                </div>
            </div>
        <?php elseif (!isset($token) || !isset($email)): ?>
            <ul class="nav nav-tabs mb-4" id="resetTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $first_time ? '' : 'active'; ?>" id="reset-tab" data-bs-toggle="tab" data-bs-target="#reset" type="button" role="tab" aria-controls="reset" aria-selected="<?php echo $first_time ? 'false' : 'true'; ?>">
                        Request Reset
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $first_time ? 'active' : ''; ?>" id="temp-tab" data-bs-toggle="tab" data-bs-target="#temp" type="button" role="tab" aria-controls="temp" aria-selected="<?php echo $first_time ? 'true' : 'false'; ?>">
                        Use Temporary Password
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="resetTabsContent">
                <!-- Request Reset Tab -->
                <div class="tab-pane fade <?php echo $first_time ? '' : 'show active'; ?>" id="reset" role="tabpanel" aria-labelledby="reset-tab">
                    <div class="text-center">
                        <p>The password reset link is invalid or has expired.</p>
                        <a href="forgot-password.php" class="btn btn-primary">Request New Reset Link</a>
                        <div class="mt-3">
                            <a href="login.php" class="text-decoration-none">Back to Login</a>
                        </div>
                    </div>
                </div>
                
                <!-- Temporary Password Tab -->
                <div class="tab-pane fade <?php echo $first_time ? 'show active' : ''; ?>" id="temp" role="tabpanel" aria-labelledby="temp-tab">
                    <div class="mb-4">
                        <p>If you have a temporary password from your account creation email, enter it below to set your new password.</p>
                    </div>
                    
                    <form method="POST" action="reset-password.php" id="firstTimeForm">
                        <input type="hidden" name="first_time_setup" value="1">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Temporary Password</label>
                            <div class="password-field">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <i class="fas fa-eye password-toggle" data-target="current_password"></i>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="password-field">
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <i class="fas fa-eye password-toggle" data-target="new_password"></i>
                            </div>
                            <div class="password-strength">
                                <div class="password-strength-meter" id="passwordStrengthMeter"></div>
                            </div>
                            <div class="requirements">
                                Password must be at least 8 characters long.
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="password-field">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <i class="fas fa-eye password-toggle" data-target="confirm_password"></i>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Set My Password</button>
                        </div>
                    </form>
                </div>
            </div>
            
        <!-- Token-based reset form -->
        <?php elseif (isset($token) && isset($email)): ?>
            <p>Hello <strong><?php echo htmlspecialchars($username); ?></strong>, please set your new password below.</p>
            
            <form method="POST" action="reset-password.php" id="resetForm">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <div class="password-field">
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <i class="fas fa-eye password-toggle" data-target="new_password"></i>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-meter" id="passwordStrengthMeter"></div>
                    </div>
                    <div class="requirements">
                        Password must be at least 8 characters long.
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="password-field">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <i class="fas fa-eye password-toggle" data-target="confirm_password"></i>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg"><?php echo $first_time ? 'Set Password' : 'Reset Password'; ?></button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="container py-4">
        <div class="reset-container">
            <h2 class="mb-4">Change Password</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="reset-password.php">
                <div class="mb-3">
                    <label for="current_password" class="form-label">Current Password</label>
                    <div class="password-field">
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                        <i class="fas fa-eye password-toggle" data-target="current_password"></i>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <div class="password-field">
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <i class="fas fa-eye password-toggle" data-target="new_password"></i>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-meter" id="passwordStrengthMeter"></div>
                    </div>
                    <div class="requirements">
                        Password must be at least 8 characters long.
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <div class="password-field">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <i class="fas fa-eye password-toggle" data-target="confirm_password"></i>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <a href="dashboard.php" class="text-decoration-none">Back to Dashboard</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        // Toggle password visibility
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordField = document.getElementById(targetId);
                
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    this.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    passwordField.type = 'password';
                    this.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });
        
        // Password strength indicator
        const passwordInput = document.getElementById('new_password');
        const meter = document.getElementById('passwordStrengthMeter');
        
        if (passwordInput && meter) {
            passwordInput.addEventListener('input', function() {
                const val = this.value;
                let score = 0;
                
                // Length check
                if (val.length >= 8) score += 1;
                if (val.length >= 12) score += 1;
                
                // Complexity checks
                if (/[0-9]/.test(val)) score += 1;
                if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score += 1;
                if (/[^a-zA-Z0-9]/.test(val)) score += 1;
                
                // Set the strength meter
                meter.className = 'password-strength-meter';
                if (score >= 5) {
                    meter.classList.add('very-strong');
                } else if (score >= 4) {
                    meter.classList.add('strong');
                } else if (score >= 3) {
                    meter.classList.add('medium');
                } else if (score > 0) {
                    meter.classList.add('weak');
                }
            });
        }
    </script>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php include 'bfooter.php'; ?>
    <?php endif; ?>
</body>
</html>
