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
        // Test user credentials email
        $userData = [
            'id' => 999,
            'username' => 'test_user',
            'email' => $_POST['test_email'],
            'user_type' => 'user',
            'phone_number' => '555-123-4567'
        ];
        
        try {
            $sent = sendUserCredentials($userData, 'TestPassword123!');
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
    } elseif (isset($_POST['test_smtp'])) {
        // Test direct SMTP sending
        try {
            $sent = sendEmailViaSmtp(
                $_POST['test_email'],
                'SMTP Test Email',
                '<h1>SMTP Test</h1><p>This is a test email sent directly via SMTP.</p>',
                'Test Email System'
            );
            $result = [
                'success' => $sent,
                'message' => $sent ? 'SMTP test email sent successfully!' : 'Failed to send SMTP test email.',
                'type' => 'smtp_test'
            ];
        } catch (Exception $e) {
            $result = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'type' => 'smtp_test'
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
            <div class="mb-6 p-4 rounded-md <?php echo $result['success'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <p class="font-medium"><?php echo htmlspecialchars($result['message']); ?></p>
                <?php if (!$result['success']): ?>
                    <p class="mt-2 text-sm">Check the server logs for more details.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="grid md:grid-cols-3 gap-6">
            <!-- Test Admin Notification -->
            <div class="bg-blue-50 p-4 rounded-lg">
                <h2 class="text-xl font-semibold mb-4 text-blue-800">Test Admin Notification</h2>
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="admin_email" class="block text-sm font-medium text-gray-700 mb-1">Test Email Address</label>
                        <input type="email" id="admin_email" name="test_email" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <button type="submit" name="test_admin_notification" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition duration-200">
                        Send Admin Notification
                    </button>
                </form>
            </div>
            
            <!-- Test User Credentials -->
            <div class="bg-purple-50 p-4 rounded-lg">
                <h2 class="text-xl font-semibold mb-4 text-purple-800">Test User Credentials</h2>
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="user_email" class="block text-sm font-medium text-gray-700 mb-1">Test Email Address</label>
                        <input type="email" id="user_email" name="test_email" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    <button type="submit" name="test_user_credentials" 
                            class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-md transition duration-200">
                        Send User Credentials
                    </button>
                </form>
            </div>
            
            <!-- Test Direct SMTP -->
            <div class="bg-green-50 p-4 rounded-lg">
                <h2 class="text-xl font-semibold mb-4 text-green-800">Test Direct SMTP</h2>
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="smtp_email" class="block text-sm font-medium text-gray-700 mb-1">Test Email Address</label>
                        <input type="email" id="smtp_email" name="test_email" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <button type="submit" name="test_smtp" 
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md transition duration-200">
                        Send SMTP Test
                    </button>
                </form>
            </div>
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