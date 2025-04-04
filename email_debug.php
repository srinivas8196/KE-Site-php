<?php
session_start();

// Include authentication helper
require_once 'auth_helper.php';
requirePermission('super_admin');

// Define page title
$page_title = "Email Debug Information";

// Include header
include_once 'header.php';

// Check PHP mail configuration
function getPhpMailConfig() {
    $iniPath = php_ini_loaded_file();
    $config = [
        'SMTP' => ini_get('SMTP'),
        'smtp_port' => ini_get('smtp_port'),
        'sendmail_from' => ini_get('sendmail_from'),
        'sendmail_path' => ini_get('sendmail_path'),
        'mail_add_x_header' => ini_get('mail.add_x_header'),
        'mail_log' => ini_get('mail.log'),
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit'),
        'error_reporting' => ini_get('error_reporting'),
        'display_errors' => ini_get('display_errors'),
        'log_errors' => ini_get('log_errors'),
        'error_log' => ini_get('error_log'),
        'ini_file' => $iniPath,
        'extension_dir' => ini_get('extension_dir'),
        'open_basedir' => ini_get('open_basedir')
    ];
    
    return $config;
}

// Check system paths
function getSystemPaths() {
    $paths = [
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
        'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? '',
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? '',
        'server_name' => $_SERVER['SERVER_NAME'] ?? '',
        'http_host' => $_SERVER['HTTP_HOST'] ?? '',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? '',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
        'current_file' => __FILE__,
        'current_dir' => __DIR__,
        'temp_dir' => sys_get_temp_dir(),
        'php_version' => PHP_VERSION,
        'os_version' => PHP_OS,
        'sapi_name' => php_sapi_name(),
        'post_max_size' => ini_get('post_max_size'),
        'server_ip' => $_SERVER['SERVER_ADDR'] ?? '',
        'client_ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    
    return $paths;
}

// Get database info
function getDatabaseInfo() {
    // Include database connection
    require_once 'db.php';
    global $conn;
    
    $dbInfo = [
        'db_connected' => false,
        'mysqli_version' => '',
        'server_info' => '',
        'host_info' => '',
        'character_set' => '',
        'admin_count' => 0
    ];
    
    if (!$conn || $conn->connect_error) {
        return $dbInfo;
    }
    
    // Get database information
    $dbInfo['db_connected'] = true;
    $dbInfo['mysqli_version'] = $conn->client_info;
    $dbInfo['server_info'] = $conn->server_info;
    $dbInfo['host_info'] = $conn->host_info;
    $dbInfo['character_set'] = $conn->character_set_name();
    
    // Get admin count
    $query = "SELECT COUNT(*) as count FROM users WHERE user_type IN ('super_admin', 'admin')";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $dbInfo['admin_count'] = $row['count'];
    }
    
    return $dbInfo;
}

// Get SMTP settings
function getSmtpConfigInfo() {
    $smtp = [];
    
    // Check if .env file exists
    if (file_exists('.env')) {
        $env = parse_ini_file('.env');
        $smtp = [
            'smtp_host' => $env['SMTP_HOST'] ?? 'Not set',
            'smtp_port' => $env['SMTP_PORT'] ?? 'Not set',
            'smtp_username' => $env['SMTP_USERNAME'] ?? 'Not set',
            'smtp_from_email' => $env['SMTP_FROM_EMAIL'] ?? 'Not set',
            'smtp_from_name' => $env['SMTP_FROM_NAME'] ?? 'Not set',
            'smtp_encryption' => $env['SMTP_ENCRYPTION'] ?? 'Not set',
            'has_password' => !empty($env['SMTP_PASSWORD']) ? 'Yes' : 'No'
        ];
    } else {
        $smtp = [
            'error' => '.env file not found'
        ];
    }
    
    return $smtp;
}

// Get error logs
function getErrorLogs($filename = 'error_log', $lines = 100) {
    $logs = [];
    
    if (file_exists($filename)) {
        $file = new SplFileObject($filename, 'r');
        $file->seek(PHP_INT_MAX); // Seek to the end of file
        $totalLines = $file->key(); // Get total line count
        
        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);
        
        $logs = [
            'exists' => true,
            'filename' => $filename,
            'size' => filesize($filename),
            'last_modified' => date('Y-m-d H:i:s', filemtime($filename)),
            'total_lines' => $totalLines,
            'lines' => []
        ];
        
        $i = $startLine;
        while (!$file->eof() && count($logs['lines']) < $lines) {
            $line = $file->fgets();
            if (trim($line) !== '') {
                $logs['lines'][] = [
                    'line_num' => $i++,
                    'content' => $line
                ];
            }
        }
        
        // Reverse to show newest first
        $logs['lines'] = array_reverse($logs['lines']);
    } else {
        $logs = [
            'exists' => false,
            'filename' => $filename
        ];
    }
    
    return $logs;
}

// Process form submission
$clearLog = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['clear_error_log']) && file_exists('error_log')) {
        file_put_contents('error_log', '');
        $clearLog = true;
    }
}

// Get system info
$phpMailConfig = getPhpMailConfig();
$systemPaths = getSystemPaths();
$dbInfo = getDatabaseInfo();
$smtpInfo = getSmtpConfigInfo();
$errorLogs = getErrorLogs('error_log', 50);

// Run diagnostic test
$diagnosticResults = [];

// Check if PHPMailer is installed
$phpmailerInstalled = false;
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    $phpmailerInstalled = class_exists('PHPMailer\PHPMailer\PHPMailer');
}
$diagnosticResults[] = [
    'test' => 'PHPMailer Installation',
    'status' => $phpmailerInstalled ? 'success' : 'warning',
    'message' => $phpmailerInstalled ? 'PHPMailer is installed' : 'PHPMailer is not installed'
];

// Check if mail() function is available
$mailFunctionAvailable = function_exists('mail');
$diagnosticResults[] = [
    'test' => 'PHP mail() Function',
    'status' => $mailFunctionAvailable ? 'success' : 'error',
    'message' => $mailFunctionAvailable ? 'mail() function is available' : 'mail() function is not available'
];

// Check if fsockopen is available for SMTP connections
$fsockopenAvailable = function_exists('fsockopen');
$diagnosticResults[] = [
    'test' => 'fsockopen Function',
    'status' => $fsockopenAvailable ? 'success' : 'warning',
    'message' => $fsockopenAvailable ? 'fsockopen function is available' : 'fsockopen function is not available'
];

// Check if openssl is available for TLS connections
$opensslAvailable = extension_loaded('openssl');
$diagnosticResults[] = [
    'test' => 'OpenSSL Extension',
    'status' => $opensslAvailable ? 'success' : 'error',
    'message' => $opensslAvailable ? 'OpenSSL extension is loaded' : 'OpenSSL extension is not loaded'
];

// Check if SMTP settings are configured
$smtpConfigured = isset($smtpInfo['smtp_host']) && 
                  $smtpInfo['smtp_host'] !== 'Not set' && 
                  $smtpInfo['has_password'] === 'Yes';
$diagnosticResults[] = [
    'test' => 'SMTP Configuration',
    'status' => $smtpConfigured ? 'success' : 'warning',
    'message' => $smtpConfigured ? 'SMTP settings are configured' : 'SMTP settings are not fully configured'
];

// Check temp directory is writable (needed for PHPMailer)
$tempDirWritable = is_writable(sys_get_temp_dir());
$diagnosticResults[] = [
    'test' => 'Temp Directory',
    'status' => $tempDirWritable ? 'success' : 'warning',
    'message' => $tempDirWritable ? 'Temp directory is writable' : 'Temp directory is not writable'
];

// Check if database connection is working
$dbConnected = $dbInfo['db_connected'];
$diagnosticResults[] = [
    'test' => 'Database Connection',
    'status' => $dbConnected ? 'success' : 'error',
    'message' => $dbConnected ? 'Database connection is working' : 'Database connection failed'
];

// Check admin users exist
$adminUsersExist = $dbInfo['admin_count'] > 0;
$diagnosticResults[] = [
    'test' => 'Admin Users',
    'status' => $adminUsersExist ? 'success' : 'warning',
    'message' => $adminUsersExist ? $dbInfo['admin_count'] . ' admin users found' : 'No admin users found'
];

// Check error log is writable
$errorLogWritable = is_writable('error_log') || (!file_exists('error_log') && is_writable(__DIR__));
$diagnosticResults[] = [
    'test' => 'Error Log',
    'status' => $errorLogWritable ? 'success' : 'warning',
    'message' => $errorLogWritable ? 'Error log is writable' : 'Error log is not writable'
];
?>

<div class="container mx-auto px-4 py-8">
    <?php if ($clearLog): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r">
            <div class="flex">
                <div class="py-1"><i class="fas fa-check-circle text-green-500 mr-3"></i></div>
                <div>
                    <p>Error log has been cleared successfully.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow-md rounded-lg p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Email System Diagnostics</h1>
            
            <div class="flex space-x-3">
                <a href="test_email.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition duration-200">
                    Test Email
                </a>
                <a href="smtp_diagnostic.php" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-md transition duration-200">
                    SMTP Settings
                </a>
            </div>
        </div>
        
        <!-- Diagnostic Tests -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">System Tests</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($diagnosticResults as $test): ?>
                    <div class="bg-gray-50 p-3 rounded-md">
                        <div class="flex items-center">
                            <?php if ($test['status'] === 'success'): ?>
                                <span class="flex-shrink-0 inline-flex items-center justify-center h-8 w-8 rounded-full bg-green-100 text-green-600">
                                    <i class="fas fa-check"></i>
                                </span>
                            <?php elseif ($test['status'] === 'warning'): ?>
                                <span class="flex-shrink-0 inline-flex items-center justify-center h-8 w-8 rounded-full bg-yellow-100 text-yellow-600">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </span>
                            <?php else: ?>
                                <span class="flex-shrink-0 inline-flex items-center justify-center h-8 w-8 rounded-full bg-red-100 text-red-600">
                                    <i class="fas fa-times"></i>
                                </span>
                            <?php endif; ?>
                            
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($test['test']); ?></h3>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($test['message']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- PHP Mail Configuration -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">PHP Mail Configuration</h2>
            
            <div class="overflow-x-auto bg-gray-50 rounded-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Setting</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($phpMailConfig as $key => $value): ?>
                            <tr>
                                <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($key); ?></td>
                                <td class="px-6 py-2 text-sm text-gray-500"><?php echo htmlspecialchars($value ?: 'Not set'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- SMTP Settings -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">SMTP Settings</h2>
            
            <div class="overflow-x-auto bg-gray-50 rounded-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Setting</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (isset($smtpInfo['error'])): ?>
                            <tr>
                                <td colspan="2" class="px-6 py-2 text-sm text-red-500"><?php echo htmlspecialchars($smtpInfo['error']); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($smtpInfo as $key => $value): ?>
                                <tr>
                                    <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($key); ?></td>
                                    <td class="px-6 py-2 text-sm text-gray-500"><?php echo htmlspecialchars($value); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Database Info -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">Database Information</h2>
            
            <div class="overflow-x-auto bg-gray-50 rounded-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Setting</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($dbInfo as $key => $value): ?>
                            <tr>
                                <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($key); ?></td>
                                <td class="px-6 py-2 text-sm text-gray-500"><?php echo htmlspecialchars($value !== false ? ($value === true ? 'Yes' : $value) : 'No'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Error Logs -->
        <div>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">Recent Error Logs</h2>
                
                <?php if ($errorLogs['exists']): ?>
                    <form method="POST" action="">
                        <button type="submit" name="clear_error_log" class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-4 rounded-md text-sm transition duration-200">
                            Clear Log
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <?php if ($errorLogs['exists']): ?>
                <div class="mb-2 text-sm text-gray-600">
                    <span>File: <?php echo htmlspecialchars($errorLogs['filename']); ?></span>
                    <span class="ml-4">Size: <?php echo round($errorLogs['size'] / 1024, 2); ?> KB</span>
                    <span class="ml-4">Last Modified: <?php echo htmlspecialchars($errorLogs['last_modified']); ?></span>
                </div>
                
                <div class="bg-gray-900 rounded-md overflow-x-auto text-sm">
                    <pre class="p-4 text-gray-300 font-mono" style="max-height: 500px; overflow-y: auto;">
<?php foreach ($errorLogs['lines'] as $line): ?>
<span class="text-gray-500"><?php echo htmlspecialchars($line['line_num']); ?>:</span> <?php echo htmlspecialchars($line['content']); ?>
<?php endforeach; ?></pre>
                </div>
            <?php else: ?>
                <div class="bg-yellow-50 p-4 rounded-md">
                    <p class="text-yellow-700">Error log file not found at <code><?php echo htmlspecialchars($errorLogs['filename']); ?></code>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?> 