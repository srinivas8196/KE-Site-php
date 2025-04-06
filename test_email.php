<?php
// Start session
session_start();

// Include authentication helper
include_once 'auth_helper.php';
requirePermission('super_admin');

// Include database connection
include_once 'db.php';

// Include email functions
include_once 'email_notification.php';

// Define page title
$page_title = "Test Email Functionality";

// Process form submission
$result = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['test_admin_notification'])) {
        // Test admin notification
        $userData = [
            'id' => 999,
            'username' => 'test_user',
            'email' => $_POST['test_email'],
            'user_type' => 'admin',
            'phone_number' => '555-123-4567'
        ];
        
        try {
            $sent = sendNewUserNotification($userData);
            $result = [
                'success' => $sent,
                'message' => $sent ? 'Admin notification sent successfully!' : 'Failed to send admin notification.',
                'type' => 'admin_notification'
            ];
        } catch (Exception $e) {
            $result = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'type' => 'admin_notification'
            ];
        }
    } elseif (isset($_POST['test_user_credentials'])) {
        // Test user credential email
        $userData = [
            'id' => 999,
            'username' => 'test_user',
            'email' => $_POST['test_email'],
            'user_type' => 'user',
            'phone_number' => '555-123-4567'
        ];
        
        try {
            $sent = sendUserCredentials($userData, 'Test1234!');
            $result = [
                'success' => $sent,
                'message' => $sent ? 'User credentials sent successfully!' : 'Failed to send user credentials.',
                'type' => 'user_credentials'
            ];
        } catch (Exception $e) {
            $result = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'type' => 'user_credentials'
            ];
        }
    } elseif (isset($_POST['test_direct_email'])) {
        // Test direct email
        try {
            $email = $_POST['test_email'];
            $subject = "Test Email from Karma Experience";
            $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { padding: 20px; }
                    .header { background: #f5f5f5; padding: 10px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Test Email</h2>
                    </div>
                    <p>This is a test email sent at " . date('Y-m-d H:i:s') . "</p>
                    <p>If you received this email, your email configuration is working properly.</p>
                </div>
            </body>
            </html>
            ";
            
            $sentCount = sendEmailViaSmtp($email, $subject, $message);
            $result = [
                'success' => $sentCount > 0,
                'message' => $sentCount > 0 ? 'Direct test email sent successfully!' : 'Failed to send direct test email.',
                'type' => 'direct_email'
            ];
        } catch (Exception $e) {
            $result = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'type' => 'direct_email'
            ];
        }
    } elseif (isset($_POST['view_smtp_settings'])) {
        // View SMTP settings
        try {
            $smtpSettings = getSmtpSettings();
            // Hide password
            $smtpSettings['password'] = str_repeat('*', strlen($smtpSettings['password']));
            $result = [
                'success' => true,
                'message' => 'SMTP Settings loaded successfully',
                'type' => 'smtp_settings',
                'data' => $smtpSettings
            ];
        } catch (Exception $e) {
            $result = [
                'success' => false,
                'message' => 'Error loading SMTP settings: ' . $e->getMessage(),
                'type' => 'smtp_settings'
            ];
        }
    }
}

// Include header
include_once 'header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white shadow-md rounded-lg p-6 mb-8">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">Test Email Functionality</h1>
        
        <?php if (!empty($result)): ?>
            <div class="alert alert-<?php echo $result['success'] ? 'success' : 'danger'; ?> mb-4">
                <h4 class="alert-heading"><?php echo $result['success'] ? 'Success!' : 'Error!'; ?></h4>
                <p><?php echo htmlspecialchars($result['message']); ?></p>
                
                <?php if ($result['type'] === 'smtp_settings' && $result['success'] && isset($result['data'])): ?>
                    <hr>
                    <h5>SMTP Settings:</h5>
                    <ul>
                        <?php foreach ($result['data'] as $key => $value): ?>
                            <li><strong><?php echo ucfirst($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <!-- View SMTP Settings -->
            <div class="col">
                <div class="card h-100 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">View SMTP Settings</h5>
                    </div>
                    <div class="card-body">
                        <p>View your current SMTP configuration settings.</p>
                        <form method="post" action="">
                            <div class="d-grid">
                                <button type="submit" name="view_smtp_settings" class="btn btn-primary">Show Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Test Direct Email -->
            <div class="col">
                <div class="card h-100 border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Test Direct Email</h5>
                    </div>
                    <div class="card-body">
                        <p>Send a simple test email to verify your email configuration.</p>
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="direct_email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="direct_email" name="test_email" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="test_direct_email" class="btn btn-success">Send Test Email</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Test Admin Notification -->
            <div class="col">
                <div class="card h-100 border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Test Admin Notification</h5>
                    </div>
                    <div class="card-body">
                        <p>Test sending a new user notification to admins.</p>
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="admin_email" class="form-label">Admin Email Address</label>
                                <input type="email" class="form-control" id="admin_email" name="test_email" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="test_admin_notification" class="btn btn-info">Send Admin Notification</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Test User Credentials -->
            <div class="col">
                <div class="card h-100 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Test User Credentials</h5>
                    </div>
                    <div class="card-body">
                        <p>Test sending login credentials to a new user.</p>
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="user_email" class="form-label">User Email Address</label>
                                <input type="email" class="form-control" id="user_email" name="test_email" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="test_user_credentials" class="btn btn-warning">Send User Credentials</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 text-center">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
    
    <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-xl font-bold mb-4 text-gray-800">Email Configuration Status</h2>
        
        <?php
        // Check .env file exists
        $envExists = file_exists('.env');
        
        // Check PHPMailer installation
        $phpmailerExists = class_exists('PHPMailer\PHPMailer\PHPMailer');
        
        // Check SMTP configuration
        $smtpConfigured = false;
        if ($envExists) {
            $env = parse_ini_file('.env');
            $smtpConfigured = !empty($env['SMTP_HOST']) && !empty($env['SMTP_USERNAME']) && !empty($env['SMTP_PASSWORD']);
        }
        ?>
        
        <ul class="space-y-3">
            <li class="flex items-center">
                <span class="inline-flex items-center justify-center w-6 h-6 mr-2 rounded-full <?php echo $envExists ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $envExists ? '✓' : '✗'; ?>
                </span>
                <span>.env file: <?php echo $envExists ? 'Found' : 'Missing'; ?></span>
                <?php if (!$envExists): ?>
                    <span class="ml-2 text-sm text-red-600">Create a .env file based on .env.example</span>
                <?php endif; ?>
            </li>
            
            <li class="flex items-center">
                <span class="inline-flex items-center justify-center w-6 h-6 mr-2 rounded-full <?php echo $phpmailerExists ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                    <?php echo $phpmailerExists ? '✓' : '!'; ?>
                </span>
                <span>PHPMailer: <?php echo $phpmailerExists ? 'Installed' : 'Not installed'; ?></span>
                <?php if (!$phpmailerExists): ?>
                    <a href="install_phpmailer.php" class="ml-2 text-sm text-blue-600 hover:underline">Install PHPMailer</a>
                <?php endif; ?>
            </li>
            
            <li class="flex items-center">
                <span class="inline-flex items-center justify-center w-6 h-6 mr-2 rounded-full <?php echo $smtpConfigured ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                    <?php echo $smtpConfigured ? '✓' : '!'; ?>
                </span>
                <span>SMTP Configuration: <?php echo $smtpConfigured ? 'Configured' : 'Not configured'; ?></span>
                <?php if (!$smtpConfigured): ?>
                    <span class="ml-2 text-sm text-yellow-600">Update SMTP settings in .env file</span>
                <?php endif; ?>
            </li>
            
            <li class="flex items-center">
                <span class="inline-flex items-center justify-center w-6 h-6 mr-2 rounded-full bg-blue-100 text-blue-800">i</span>
                <span>PHP mail() function: <?php echo function_exists('mail') ? 'Available' : 'Not available'; ?></span>
            </li>
        </ul>
    </div>
</div>

<?php include_once 'footer.php'; ?> 