<?php
// Start session
session_start();

// Include database
require_once 'db.php';
require_once 'includes/recaptcha-config.php';
require_once 'includes/functions.php';

// Debug: Log the request method and session info
error_log("Login page accessed via " . $_SERVER['REQUEST_METHOD'] . " method");
if (isset($_SESSION['user'])) {
    error_log("User already logged in, redirecting to dashboard");
    header("Location: dashboard.php");
    exit;
}

// Initialize error message
$error = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $recaptcha_token = $_POST['recaptcha_token'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        // Verify reCAPTCHA first
        $recaptcha_result = verifyRecaptchaV3($recaptcha_token, 'login');
        
        if (!$recaptcha_result['success']) {
            $error = 'Security verification failed. Please try again.';
            error_log('reCAPTCHA verification failed: ' . ($recaptcha_result['error'] ?? 'unknown error'));
        } else {
            try {
                // Get PDO connection
                $pdo = require 'db.php';
                
                // Query the database
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Check password
                if ($user && password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role'],
                        'user_type' => $user['user_type']
                    ];
                    
                    // Set activity timestamps
                    $_SESSION['LAST_ACTIVITY'] = time();
                    $_SESSION['CREATED'] = time();
                    
                    // Debug: Log successful login
                    error_log("Login successful for user: {$username}, redirecting to dashboard.php");
                    
                    // Redirect to dashboard with absolute path
                    header("Location: " . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/dashboard.php');
                    exit;
                } else {
                    // Debug: Log failed login
                    error_log("Login failed for username: {$username}");
                    $error = 'Invalid username or password';
                }
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                $error = 'Database error: ' . $e->getMessage();
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
    <title>Admin Login - Karma Experience</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1E5F74, #133B5C);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 400px;
            padding: 2rem;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .login-logo img {
            max-height: 60px;
        }
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
        }
        .btn-login {
            background: #1E5F74;
            color: white;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 8px;
            width: 100%;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background: #133B5C;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .error-message {
            color: #e74c3c;
            background: #fdf5f5;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            border-left: 4px solid #e74c3c;
        }
    </style>
    <!-- Add reCAPTCHA v3 script -->
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo RECAPTCHA_V3_SITE_KEY; ?>"></script>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <img src="assets/images/logo/KE-Gold.png" alt="Karma Experience">
        </div>
        
        <h2 class="text-center mb-4">Admin Login</h2>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php" id="loginForm">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <!-- Hidden reCAPTCHA token field -->
            <input type="hidden" name="recaptcha_token" id="recaptchaToken">
            
            <button type="submit" class="btn btn-login">Login</button>
        </form>
        
        <div class="text-center mt-4">
            <a href="index.php" class="text-decoration-none" style="font-size: 0.9rem; color: #666;">
                Return to Website
            </a>
        </div>
    </div>
    
    <script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        grecaptcha.ready(function() {
            grecaptcha.execute('<?php echo RECAPTCHA_V3_SITE_KEY; ?>', {action: 'login'})
                .then(function(token) {
                    document.getElementById('recaptchaToken').value = token;
                    document.getElementById('loginForm').submit();
                });
        });
    });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
