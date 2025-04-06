<?php
// Start session
session_start();

// Clear any existing user session for testing
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['user_type']);

// Include database connection
require_once 'db.php';

// Initialize variables
$message = '';
$status = '';
$debug_info = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['test_user_setup'])) {
        // Get the username to check
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, username, email, password, reset_token, reset_token_expires FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $status = 'error';
            $message = "User not found: $username";
        } else {
            $user = $result->fetch_assoc();
            
            // Verify password
            $password_match = password_verify($password, $user['password']);
            
            // Get info about the user for debugging
            $debug_info = [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'password_match' => $password_match ? 'Yes' : 'No',
                'has_reset_token' => !empty($user['reset_token']) ? 'Yes' : 'No',
                'token_expires' => $user['reset_token_expires'],
                'token_valid' => (!empty($user['reset_token']) && strtotime($user['reset_token_expires']) > time()) ? 'Yes' : 'No'
            ];
            
            // Show status based on check
            if ($password_match) {
                $status = 'success';
                $message = "Password verification successful for $username";
            } else {
                $status = 'error';
                $message = "Password verification failed for $username";
            }
            
            // Generate a new reset token for the user to test
            $reset_token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
            $stmt->bind_param("ssi", $reset_token, $expires, $user['id']);
            
            if ($stmt->execute()) {
                $reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                              "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . 
                              "/reset-password.php?token=" . urlencode($reset_token) . 
                              "&email=" . urlencode($user['email']) . 
                              "&first_time=1";
                
                $debug_info['new_reset_token'] = $reset_token;
                $debug_info['reset_link'] = $reset_link;
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
    <title>Password Reset Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h1 class="h3 mb-0">Password Reset Test</h1>
            </div>
            
            <div class="card-body">
                <p class="mb-4">This tool helps diagnose issues with password resets and account setup.</p>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $status === 'success' ? 'success' : 'danger'; ?> mb-4">
                        <h4 class="alert-heading"><?php echo $status === 'success' ? 'Success!' : 'Error!'; ?></h4>
                        <p><?php echo htmlspecialchars($message); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($debug_info)): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">User Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <tbody>
                                    <?php foreach ($debug_info as $key => $value): ?>
                                        <tr>
                                            <th><?php echo ucwords(str_replace('_', ' ', $key)); ?></th>
                                            <td>
                                                <?php if ($key === 'reset_link'): ?>
                                                    <a href="<?php echo htmlspecialchars($value); ?>" target="_blank"><?php echo htmlspecialchars($value); ?></a>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($value); ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Test User Password Reset</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Current/Temporary Password</label>
                                <input type="text" class="form-control" id="password" name="password" required>
                                <div class="form-text">Enter the temporary password from the email</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="test_user_setup" class="btn btn-primary">Test &amp; Generate Reset Link</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 