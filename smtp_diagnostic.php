<?php
session_start();

// Include authentication helper
require_once 'auth_helper.php';
requirePermission('super_admin');

// Define page title
$page_title = "SMTP Diagnostic Tool";

// Include header
include_once 'header.php';

// Function to test SMTP connection directly
function testSmtpConnection($host, $port, $user, $pass, $encryption = 'tls') {
    $result = [
        'success' => false,
        'message' => '',
        'details' => []
    ];
    
    // Check if fsockopen is available
    if (!function_exists('fsockopen')) {
        $result['message'] = 'The fsockopen function is not available on this server.';
        return $result;
    }
    
    $result['details'][] = "Testing connection to $host:$port...";
    
    // Try to connect to SMTP server
    $errno = 0;
    $errstr = '';
    $timeout = 10;
    
    // Create socket connection
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    
    if (!$socket) {
        $result['message'] = "Failed to connect to $host:$port - Error: $errstr ($errno)";
        return $result;
    }
    
    $result['details'][] = "Successfully connected to $host:$port";
    
    // Check server response
    $response = fgets($socket, 515);
    if (empty($response)) {
        $result['message'] = "No response from SMTP server";
        fclose($socket);
        return $result;
    }
    
    $result['details'][] = "Server response: $response";
    
    // Send EHLO command
    fputs($socket, "EHLO localhost\r\n");
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (substr($line, 3, 1) == ' ') break;
    }
    
    $result['details'][] = "EHLO response: $response";
    
    // Check if server supports AUTH LOGIN
    if (strpos($response, "AUTH") === false) {
        $result['message'] = "Server does not support authentication";
        fclose($socket);
        return $result;
    }
    
    // Close connection
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    $result['success'] = true;
    $result['message'] = "SMTP server connection successful";
    
    return $result;
}

// Process form submission
$result = null;
$smtp_config = [];

// Load current settings from .env if available
if (file_exists('.env')) {
    $env = parse_ini_file('.env');
    $smtp_config = [
        'host' => $env['SMTP_HOST'] ?? '',
        'port' => $env['SMTP_PORT'] ?? '587',
        'username' => $env['SMTP_USERNAME'] ?? '',
        'password' => $env['SMTP_PASSWORD'] ?? '',
        'encryption' => $env['SMTP_ENCRYPTION'] ?? 'tls',
        'from_email' => $env['SMTP_FROM_EMAIL'] ?? '',
        'from_name' => $env['SMTP_FROM_NAME'] ?? ''
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['test_connection'])) {
        $host = $_POST['smtp_host'];
        $port = $_POST['smtp_port'];
        $user = $_POST['smtp_username'];
        $pass = $_POST['smtp_password'];
        $encryption = $_POST['smtp_encryption'];
        
        $result = testSmtpConnection($host, $port, $user, $pass, $encryption);
        
        // Save the entered values for form
        $smtp_config = [
            'host' => $host,
            'port' => $port,
            'username' => $user,
            'password' => $pass,
            'encryption' => $encryption,
            'from_email' => $_POST['smtp_from_email'],
            'from_name' => $_POST['smtp_from_name']
        ];
    }
    
    if (isset($_POST['save_config'])) {
        // Create or update .env file with SMTP settings
        $envFile = file_exists('.env') ? file_get_contents('.env') : '';
        $envLines = explode("\n", $envFile);
        $newEnvLines = [];
        $smtpKeys = [
            'SMTP_HOST', 
            'SMTP_PORT', 
            'SMTP_USERNAME', 
            'SMTP_PASSWORD', 
            'SMTP_ENCRYPTION', 
            'SMTP_FROM_EMAIL', 
            'SMTP_FROM_NAME'
        ];
        
        // Remove existing SMTP settings
        foreach ($envLines as $line) {
            $skip = false;
            foreach ($smtpKeys as $key) {
                if (strpos($line, $key . '=') === 0) {
                    $skip = true;
                    break;
                }
            }
            if (!$skip) {
                $newEnvLines[] = $line;
            }
        }
        
        // Add new SMTP settings
        $newEnvLines[] = '';
        $newEnvLines[] = '# SMTP Configuration';
        $newEnvLines[] = 'SMTP_HOST=' . $_POST['smtp_host'];
        $newEnvLines[] = 'SMTP_PORT=' . $_POST['smtp_port'];
        $newEnvLines[] = 'SMTP_USERNAME=' . $_POST['smtp_username'];
        $newEnvLines[] = 'SMTP_PASSWORD=' . $_POST['smtp_password'];
        $newEnvLines[] = 'SMTP_ENCRYPTION=' . $_POST['smtp_encryption'];
        $newEnvLines[] = 'SMTP_FROM_EMAIL=' . $_POST['smtp_from_email'];
        $newEnvLines[] = 'SMTP_FROM_NAME=' . $_POST['smtp_from_name'];
        
        // Save updated .env file
        file_put_contents('.env', implode("\n", array_filter($newEnvLines)));
        
        $result = [
            'success' => true,
            'message' => 'SMTP configuration saved successfully to .env file'
        ];
        
        // Update config for form
        $smtp_config = [
            'host' => $_POST['smtp_host'],
            'port' => $_POST['smtp_port'],
            'username' => $_POST['smtp_username'],
            'password' => $_POST['smtp_password'],
            'encryption' => $_POST['smtp_encryption'],
            'from_email' => $_POST['smtp_from_email'],
            'from_name' => $_POST['smtp_from_name']
        ];
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white shadow-md rounded-lg p-6 mb-8">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">SMTP Diagnostic Tool</h1>
        
        <?php if ($result): ?>
            <div class="mb-6 p-4 rounded-md <?php echo $result['success'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <p class="font-medium"><?php echo htmlspecialchars($result['message']); ?></p>
                
                <?php if (!empty($result['details'])): ?>
                    <div class="mt-2 p-3 bg-gray-50 rounded-md text-sm font-mono">
                        <?php foreach ($result['details'] as $detail): ?>
                            <div><?php echo htmlspecialchars($detail); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- SMTP Host -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="smtp_host">
                        SMTP Host
                    </label>
                    <input type="text" id="smtp_host" name="smtp_host" 
                           value="<?php echo htmlspecialchars($smtp_config['host']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Example: smtp.gmail.com, smtp.office365.com</p>
                </div>
                
                <!-- SMTP Port -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="smtp_port">
                        SMTP Port
                    </label>
                    <input type="text" id="smtp_port" name="smtp_port" 
                           value="<?php echo htmlspecialchars($smtp_config['port']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Common ports: 25, 465, 587, 2525</p>
                </div>
                
                <!-- SMTP Username -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="smtp_username">
                        SMTP Username
                    </label>
                    <input type="text" id="smtp_username" name="smtp_username" 
                           value="<?php echo htmlspecialchars($smtp_config['username']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Usually your full email address</p>
                </div>
                
                <!-- SMTP Password -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="smtp_password">
                        SMTP Password
                    </label>
                    <input type="password" id="smtp_password" name="smtp_password" 
                           value="<?php echo htmlspecialchars($smtp_config['password']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">For Gmail, use an app password</p>
                </div>
                
                <!-- Encryption -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="smtp_encryption">
                        Encryption
                    </label>
                    <select id="smtp_encryption" name="smtp_encryption" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="tls" <?php echo $smtp_config['encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                        <option value="ssl" <?php echo $smtp_config['encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        <option value="" <?php echo empty($smtp_config['encryption']) ? 'selected' : ''; ?>>None</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">TLS usually uses port 587, SSL uses 465</p>
                </div>
                
                <!-- From Email -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="smtp_from_email">
                        From Email
                    </label>
                    <input type="email" id="smtp_from_email" name="smtp_from_email" 
                           value="<?php echo htmlspecialchars($smtp_config['from_email']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">The email address emails will appear from</p>
                </div>
                
                <!-- From Name -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="smtp_from_name">
                        From Name
                    </label>
                    <input type="text" id="smtp_from_name" name="smtp_from_name" 
                           value="<?php echo htmlspecialchars($smtp_config['from_name']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">The name that will appear in email clients</p>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4 pt-4">
                <button type="submit" name="test_connection" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-md transition duration-200">
                    Test SMTP Connection
                </button>
                
                <button type="submit" name="save_config" 
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-md transition duration-200">
                    Save SMTP Configuration
                </button>
                
                <a href="test_email.php" 
                   class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded-md transition duration-200 text-center">
                    Go to Email Test Page
                </a>
            </div>
        </form>
    </div>
    
    <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-xl font-bold mb-4 text-gray-800">Common SMTP Settings</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SMTP Server</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Port</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Encryption</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Gmail</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">smtp.gmail.com</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">587</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">TLS</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Requires app password for 2FA accounts</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Outlook/Office365</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">smtp.office365.com</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">587</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">TLS</td>
                        <td class="px-6 py-4 text-sm text-gray-500">May require app password</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Yahoo</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">smtp.mail.yahoo.com</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">587</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">TLS</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Requires app password</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Zoho</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">smtp.zoho.com</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">587</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">TLS</td>
                        <td class="px-6 py-4 text-sm text-gray-500">May also use port 465 with SSL</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="mt-6 bg-yellow-50 p-4 rounded-md">
            <h3 class="font-semibold text-yellow-800">Troubleshooting Tips</h3>
            <ul class="mt-2 list-disc list-inside text-sm text-yellow-700 space-y-1">
                <li>For Gmail, you need to enable "Less secure app access" or use an app password</li>
                <li>Make sure your SMTP server isn't blocking connections from your server's IP</li>
                <li>Check if your hosting provider blocks outgoing SMTP connections on specific ports</li>
                <li>Verify your credentials are correct and that your account has sending privileges</li>
                <li>If using Gmail or corporate email, check for rate limits on sending</li>
                <li>Some providers require that the "From" email matches your authenticated account</li>
            </ul>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?> 