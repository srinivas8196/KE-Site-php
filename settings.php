<?php
ob_start();
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];
require 'db.php';

// Include database connection
require_once 'db.php';

// Function to get all settings
function get_all_settings() {
    global $pdo, $conn;
    $settings = [];
    
    try {
        // Try using PDO connection if available
        if (isset($pdo) && $pdo) {
            $stmt = $pdo->prepare("SELECT * FROM settings");
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } 
        // Otherwise try using mysqli connection
        elseif (isset($conn) && $conn) {
            $result = $conn->query("SELECT * FROM settings");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
            }
        }
    } catch (Exception $e) {
        // Table probably doesn't exist yet
        error_log('Settings table error: ' . $e->getMessage());
        
        // Return default settings
        return [
            'site_name' => 'Karma Experience',
            'site_description' => 'Luxury Travel Experiences',
            'admin_email' => 'admin@karmaexperience.in',
            'items_per_page' => '10',
            'maintenance_mode' => '0',
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'smtp_from_email' => '',
            'smtp_from_name' => 'Karma Experience'
        ];
    }
    
    return $settings;
}

// Function to get a specific setting
function get_setting($key, $default = '') {
    static $settings = null;
    
    // Load settings if not already loaded
    if ($settings === null) {
        $settings = get_all_settings();
    }
    
    // Return the setting value or default
    return isset($settings[$key]) ? $settings[$key] : $default;
}

// Function to update a setting
function update_setting($key, $value) {
    global $pdo, $conn;
    
    try {
        // Try to update using PDO
        if (isset($pdo) && $pdo) {
            // Check if setting exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $exists = (int)$stmt->fetchColumn() > 0;
            
            if ($exists) {
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                return $stmt->execute([$value, $key]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                return $stmt->execute([$key, $value]);
            }
        }
        // Try to update using mysqli
        elseif (isset($conn) && $conn) {
            $key = $conn->real_escape_string($key);
            $value = $conn->real_escape_string($value);
            
            // Check if setting exists
            $result = $conn->query("SELECT COUNT(*) as count FROM settings WHERE setting_key = '$key'");
            $exists = ($result && $result->fetch_assoc()['count'] > 0);
            
            if ($exists) {
                return $conn->query("UPDATE settings SET setting_value = '$value' WHERE setting_key = '$key'");
            } else {
                return $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value')");
            }
        }
    } catch (Exception $e) {
        // Table probably doesn't exist yet
        error_log('Error updating setting: ' . $e->getMessage());
        return false;
    }
    
    return false;
}

// Create settings table if it doesn't exist
function create_settings_table() {
    global $pdo, $conn;
    
    try {
        if (isset($pdo) && $pdo) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(255) NOT NULL UNIQUE,
                    setting_value TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            
            // Insert default settings
            $default_settings = [
                'site_name' => 'Karma Experience',
                'site_description' => 'Luxury Travel Experiences',
                'admin_email' => 'admin@karmaexperience.in',
                'items_per_page' => '10',
                'maintenance_mode' => '0',
                'smtp_host' => '',
                'smtp_port' => '587',
                'smtp_username' => '',
                'smtp_password' => '',
                'smtp_encryption' => 'tls',
                'smtp_from_email' => '',
                'smtp_from_name' => 'Karma Experience'
            ];
            
            foreach ($default_settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute([$key, $value]);
            }
            
            return true;
        } elseif (isset($conn) && $conn) {
            $conn->query("
                CREATE TABLE IF NOT EXISTS settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(255) NOT NULL UNIQUE,
                    setting_value TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            
            // Insert default settings
            $default_settings = [
                'site_name' => 'Karma Experience',
                'site_description' => 'Luxury Travel Experiences',
                'admin_email' => 'admin@karmaexperience.in',
                'items_per_page' => '10',
                'maintenance_mode' => '0',
                'smtp_host' => '',
                'smtp_port' => '587',
                'smtp_username' => '',
                'smtp_password' => '',
                'smtp_encryption' => 'tls',
                'smtp_from_email' => '',
                'smtp_from_name' => 'Karma Experience'
            ];
            
            foreach ($default_settings as $key => $value) {
                $key = $conn->real_escape_string($key);
                $value = $conn->real_escape_string($value);
                $conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('$key', '$value')");
            }
            
            return true;
        }
    } catch (Exception $e) {
        error_log('Error creating settings table: ' . $e->getMessage());
        return false;
    }
    
    return false;
}

// Try to create the settings table, but don't worry if it fails
try {
    create_settings_table();
} catch (Exception $e) {
    error_log('Could not create settings table: ' . $e->getMessage());
}

// Get current settings
$settings = get_all_settings();

// Function to save SMTP settings to .env file
function save_smtp_to_env($smtp_settings) {
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
    $newEnvLines[] = 'SMTP_HOST=' . $smtp_settings['smtp_host'];
    $newEnvLines[] = 'SMTP_PORT=' . $smtp_settings['smtp_port'];
    $newEnvLines[] = 'SMTP_USERNAME=' . $smtp_settings['smtp_username'];
    $newEnvLines[] = 'SMTP_PASSWORD=' . $smtp_settings['smtp_password'];
    $newEnvLines[] = 'SMTP_ENCRYPTION=' . $smtp_settings['smtp_encryption'];
    $newEnvLines[] = 'SMTP_FROM_EMAIL=' . $smtp_settings['smtp_from_email'];
    $newEnvLines[] = 'SMTP_FROM_NAME=' . $smtp_settings['smtp_from_name'];
    
    // Save updated .env file
    return file_put_contents('.env', implode("\n", array_filter($newEnvLines)));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // General settings
    $site_name = trim($_POST['site_name'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    
    // Update general settings
    update_setting('site_name', $site_name);
    update_setting('admin_email', $admin_email);
    update_setting('contact_number', $contact_number);
    
    // SMTP settings
    if (isset($_POST['save_smtp'])) {
        $smtp_host = trim($_POST['smtp_host'] ?? '');
        $smtp_port = trim($_POST['smtp_port'] ?? '587');
        $smtp_username = trim($_POST['smtp_username'] ?? '');
        $smtp_password = trim($_POST['smtp_password'] ?? '');
        $smtp_encryption = trim($_POST['smtp_encryption'] ?? 'tls');
        $smtp_from_email = trim($_POST['smtp_from_email'] ?? '');
        $smtp_from_name = trim($_POST['smtp_from_name'] ?? 'Karma Experience');
        
        // Update SMTP settings in database
        update_setting('smtp_host', $smtp_host);
        update_setting('smtp_port', $smtp_port);
        update_setting('smtp_username', $smtp_username);
        update_setting('smtp_password', $smtp_password);
        update_setting('smtp_encryption', $smtp_encryption);
        update_setting('smtp_from_email', $smtp_from_email);
        update_setting('smtp_from_name', $smtp_from_name);
        
        // Also save to .env file for compatibility with email system
        $smtp_settings = [
            'smtp_host' => $smtp_host,
            'smtp_port' => $smtp_port,
            'smtp_username' => $smtp_username,
            'smtp_password' => $smtp_password,
            'smtp_encryption' => $smtp_encryption,
            'smtp_from_email' => $smtp_from_email,
            'smtp_from_name' => $smtp_from_name
        ];
        save_smtp_to_env($smtp_settings);
        
        $_SESSION['success'] = "SMTP settings updated successfully.";
    } else {
        $_SESSION['success'] = "Settings updated successfully.";
    }
    
    header("Location: settings.php");
    exit();
}

// Refresh settings after possible updates
$settings = get_all_settings();

ob_end_flush();
?>
<?php include 'bheader.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <?php include 'admin_dashboard.php'; ?>
        <main class="flex-1 p-8">
            <h2 class="text-3xl font-bold mb-6">Settings</h2>
            <?php if(isset($_SESSION['success'])): ?>
                <div class="bg-green-500 text-white p-4 rounded mb-4">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Tabs Navigation -->
            <div class="mb-6 border-b border-gray-200">
                <ul class="flex flex-wrap -mb-px" id="settingsTabs" role="tablist">
                    <li class="mr-2" role="presentation">
                        <button class="inline-block p-4 border-b-2 border-blue-500 rounded-t-lg" 
                                id="general-tab" 
                                onclick="showTab('general')" 
                                type="button" 
                                role="tab" 
                                aria-selected="true">
                            General Settings
                        </button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:border-gray-300" 
                                id="smtp-tab" 
                                onclick="showTab('smtp')" 
                                type="button" 
                                role="tab" 
                                aria-selected="false">
                            Email & SMTP Settings
                        </button>
                    </li>
                </ul>
            </div>
            
            <!-- General Settings Tab -->
            <div id="general-tab-content" class="tab-content">
                <form method="post" class="bg-white p-6 rounded shadow-md">
                    <div class="mb-4">
                        <label class="block font-semibold mb-2">Site Name</label>
                        <input type="text" name="site_name" value="<?= htmlspecialchars(get_setting('site_name')) ?>" class="w-full p-2 border rounded">
                    </div>
                    <div class="mb-4">
                        <label class="block font-semibold mb-2">Admin Email</label>
                        <input type="email" name="admin_email" value="<?= htmlspecialchars(get_setting('admin_email')) ?>" class="w-full p-2 border rounded">
                    </div>
                    <div class="mb-4">
                        <label class="block font-semibold mb-2">Contact Number</label>
                        <input type="text" name="contact_number" value="<?= htmlspecialchars(get_setting('contact_number', '')) ?>" class="w-full p-2 border rounded">
                    </div>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Save Changes</button>
                </form>
            </div>
            
            <!-- SMTP Settings Tab -->
            <div id="smtp-tab-content" class="tab-content hidden">
                <form method="post" class="bg-white p-6 rounded shadow-md space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- SMTP Host -->
                        <div>
                            <label class="block font-semibold mb-2" for="smtp_host">
                                SMTP Host
                            </label>
                            <input type="text" id="smtp_host" name="smtp_host" 
                                   value="<?= htmlspecialchars(get_setting('smtp_host', '')) ?>"
                                   class="w-full p-2 border rounded">
                            <p class="text-xs text-gray-500 mt-1">Example: smtp.gmail.com, smtp.office365.com</p>
                        </div>
                        
                        <!-- SMTP Port -->
                        <div>
                            <label class="block font-semibold mb-2" for="smtp_port">
                                SMTP Port
                            </label>
                            <input type="text" id="smtp_port" name="smtp_port" 
                                   value="<?= htmlspecialchars(get_setting('smtp_port', '587')) ?>"
                                   class="w-full p-2 border rounded">
                            <p class="text-xs text-gray-500 mt-1">Common ports: 25, 465, 587, 2525</p>
                        </div>
                        
                        <!-- SMTP Username -->
                        <div>
                            <label class="block font-semibold mb-2" for="smtp_username">
                                SMTP Username
                            </label>
                            <input type="text" id="smtp_username" name="smtp_username" 
                                   value="<?= htmlspecialchars(get_setting('smtp_username', '')) ?>"
                                   class="w-full p-2 border rounded">
                            <p class="text-xs text-gray-500 mt-1">Usually your full email address</p>
                        </div>
                        
                        <!-- SMTP Password -->
                        <div>
                            <label class="block font-semibold mb-2" for="smtp_password">
                                SMTP Password
                            </label>
                            <input type="password" id="smtp_password" name="smtp_password" 
                                   value="<?= htmlspecialchars(get_setting('smtp_password', '')) ?>"
                                   class="w-full p-2 border rounded">
                            <p class="text-xs text-gray-500 mt-1">For Gmail, use an app password</p>
                        </div>
                        
                        <!-- Encryption -->
                        <div>
                            <label class="block font-semibold mb-2" for="smtp_encryption">
                                Encryption
                            </label>
                            <select id="smtp_encryption" name="smtp_encryption" 
                                    class="w-full p-2 border rounded">
                                <option value="tls" <?= get_setting('smtp_encryption') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= get_setting('smtp_encryption') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="" <?= empty(get_setting('smtp_encryption')) ? 'selected' : '' ?>>None</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">TLS usually uses port 587, SSL uses 465</p>
                        </div>
                        
                        <!-- From Email -->
                        <div>
                            <label class="block font-semibold mb-2" for="smtp_from_email">
                                From Email
                            </label>
                            <input type="email" id="smtp_from_email" name="smtp_from_email" 
                                   value="<?= htmlspecialchars(get_setting('smtp_from_email', '')) ?>"
                                   class="w-full p-2 border rounded">
                            <p class="text-xs text-gray-500 mt-1">The email address emails will appear from</p>
                        </div>
                        
                        <!-- From Name -->
                        <div>
                            <label class="block font-semibold mb-2" for="smtp_from_name">
                                From Name
                            </label>
                            <input type="text" id="smtp_from_name" name="smtp_from_name" 
                                   value="<?= htmlspecialchars(get_setting('smtp_from_name', 'Karma Experience')) ?>"
                                   class="w-full p-2 border rounded">
                            <p class="text-xs text-gray-500 mt-1">The name that will appear in email clients</p>
                        </div>
                    </div>
                    
                    <div class="flex space-x-4 pt-4">
                        <button type="submit" name="save_smtp" 
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                            Save SMTP Settings
                        </button>
                        
                        <a href="test_email.php" 
                           class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded inline-block">
                            Test Email Settings
                        </a>
                        
                        <a href="smtp_diagnostic.php" 
                           class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded inline-block">
                            SMTP Diagnostics
                        </a>
                    </div>
                </form>
                
                <!-- SMTP Reference -->
                <div class="bg-white p-6 rounded shadow-md mt-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800">Common SMTP Settings</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Provider</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SMTP Server</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Port</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Encryption</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Gmail</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">smtp.gmail.com</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">587</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">TLS</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Outlook/Office365</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">smtp.office365.com</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">587</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">TLS</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Yahoo</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">smtp.mail.yahoo.com</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">587</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">TLS</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <script>
                function showTab(tabName) {
                    // Hide all tabs
                    document.querySelectorAll('.tab-content').forEach(tab => {
                        tab.classList.add('hidden');
                    });
                    
                    // Reset all tab buttons
                    document.querySelectorAll('[role="tab"]').forEach(button => {
                        button.classList.remove('border-blue-500');
                        button.classList.add('border-transparent', 'hover:border-gray-300');
                        button.setAttribute('aria-selected', 'false');
                    });
                    
                    // Show selected tab
                    document.getElementById(tabName + '-tab-content').classList.remove('hidden');
                    
                    // Highlight selected tab button
                    const selectedTab = document.getElementById(tabName + '-tab');
                    selectedTab.classList.remove('border-transparent', 'hover:border-gray-300');
                    selectedTab.classList.add('border-blue-500');
                    selectedTab.setAttribute('aria-selected', 'true');
                }
                
                // Check for URL hash to set active tab
                document.addEventListener('DOMContentLoaded', function() {
                    const hash = window.location.hash.substring(1);
                    if (hash === 'smtp') {
                        showTab('smtp');
                    }
                });
            </script>
        </main>
    </div>
</body>
</html>
<?php include 'bfooter.php'; ?>
