<?php
ob_start();
session_start();
// Check for user_id which is the correct session variable used throughout the system
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// We don't need to set $user variable since it's not used
require 'db.php';

// Include database connection and functions
require_once 'db.php';
require_once 'auth_helper.php';
// Fix PHPMailer paths - use vendor path which has all the required files
require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

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
                'smtp_from_name' => 'Karma Experience',
                'leadsquared_access_key' => '',
                'leadsquared_secret_key' => '',
                'leadsquared_api_url' => 'https://api-in21.leadsquared.com/v2/LeadManagement.svc'
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
                'smtp_from_name' => 'Karma Experience',
                'leadsquared_access_key' => '',
                'leadsquared_secret_key' => '',
                'leadsquared_api_url' => 'https://api-in21.leadsquared.com/v2/LeadManagement.svc'
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
    $newEnvLines[] = 'SMTP_FROM_NAME="' . $smtp_settings['smtp_from_name'] . '"';
    
    // Save updated .env file
    return file_put_contents('.env', implode("\n", array_filter($newEnvLines)));
}

// Function to test email settings
function test_email_settings($smtp_settings, $test_email) {
    // Create error log file if testing
    $logFile = fopen('email_test_debug.log', 'a');
    fwrite($logFile, "\n\n=== " . date('Y-m-d H:i:s') . " === Testing email to: $test_email\n");
    fwrite($logFile, "SMTP Settings: " . print_r($smtp_settings, true) . "\n");
    
    try {
        $mail = new PHPMailer(true);
        
        // Debug
        $mail->SMTPDebug = 3; // Detailed debug output
        $mail->Debugoutput = function($str, $level) use ($logFile) {
            fwrite($logFile, "Debug ($level): $str\n");
        };
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtp_settings['smtp_host'];
        $mail->Port = $smtp_settings['smtp_port'];
        
        // Only use authentication if username is provided
        if (!empty($smtp_settings['smtp_username'])) {
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_settings['smtp_username'];
            $mail->Password = $smtp_settings['smtp_password'];
        } else {
            $mail->SMTPAuth = false;
        }
        
        // Encryption setting
        if (!empty($smtp_settings['smtp_encryption'])) {
            $mail->SMTPSecure = $smtp_settings['smtp_encryption'];
        } else {
            $mail->SMTPSecure = false;
            $mail->SMTPAutoTLS = false;
        }
        
        // SSL certificate verification
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Set sender
        $fromEmail = !empty($smtp_settings['smtp_from_email']) ? $smtp_settings['smtp_from_email'] : 'no-reply@karmaexperience.com';
        $fromName = !empty($smtp_settings['smtp_from_name']) ? $smtp_settings['smtp_from_name'] : 'Karma Experience';
        
        try {
            fwrite($logFile, "Setting From: $fromEmail, $fromName\n");
            $mail->setFrom($fromEmail, $fromName);
        } catch (Exception $e) {
            fwrite($logFile, "Error in setFrom: " . $e->getMessage() . "\n");
            throw new Exception("Invalid From address: $fromEmail - " . $e->getMessage());
        }
        
        // Add recipient
        $mail->addAddress($test_email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Test Email from Karma Experience';
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;">
                <h2 style="color: #B4975A;">Karma Experience - Test Email</h2>
                <p>Hello,</p>
                <p>This is a test email sent from the Karma Experience admin panel. If you\'re receiving this email, it means your SMTP settings are configured correctly.</p>
                <p>SMTP Configuration:</p>
                <ul>
                    <li>Host: ' . htmlspecialchars($smtp_settings['smtp_host']) . '</li>
                    <li>Port: ' . htmlspecialchars($smtp_settings['smtp_port']) . '</li>
                    <li>Encryption: ' . htmlspecialchars($smtp_settings['smtp_encryption']) . '</li>
                    <li>From Email: ' . htmlspecialchars($fromEmail) . '</li>
                </ul>
                <p>Time sent: ' . date('Y-m-d H:i:s') . '</p>
                <p style="margin-top: 20px;">Best regards,<br>Karma Experience Team</p>
            </div>
        ';
        $mail->AltBody = 'This is a test email from Karma Experience. If you\'re receiving this email, your SMTP settings are configured correctly.';
        
        // Send the email
        $mail->send();
        fwrite($logFile, "Email sent successfully\n");
        fclose($logFile);
        
        return [
            'success' => true,
            'message' => 'Test email sent successfully! Please check your inbox (and spam folder).',
        ];
    } catch (Exception $e) {
        fwrite($logFile, "Error: " . $e->getMessage() . "\n");
        fwrite($logFile, "Mail Error: " . (isset($mail) ? $mail->ErrorInfo : "PHPMailer not initialized") . "\n");
        fclose($logFile);
        
        return [
            'success' => false,
            'message' => 'Failed to send test email: ' . $e->getMessage(),
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // General settings
    if (isset($_POST['save_general'])) {
    $site_name = trim($_POST['site_name'] ?? '');
        $site_description = trim($_POST['site_description'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    
    // Update general settings
    update_setting('site_name', $site_name);
        update_setting('site_description', $site_description);
    update_setting('admin_email', $admin_email);
    update_setting('contact_number', $contact_number);
    
        $_SESSION['success'] = "General settings updated successfully.";
        $_SESSION['active_tab'] = 'general';
    }
    // SMTP settings
    else if (isset($_POST['save_smtp'])) {
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
        $_SESSION['active_tab'] = 'email';
    } 
    // LeadSquared settings
    else if (isset($_POST['save_leadsquared'])) {
        $leadsquared_access_key = trim($_POST['leadsquared_access_key'] ?? '');
        $leadsquared_secret_key = trim($_POST['leadsquared_secret_key'] ?? '');
        $leadsquared_api_url = trim($_POST['leadsquared_api_url'] ?? 'https://api-in21.leadsquared.com/v2/LeadManagement.svc');
        
        // Update LeadSquared settings in database
        update_setting('leadsquared_access_key', $leadsquared_access_key);
        update_setting('leadsquared_secret_key', $leadsquared_secret_key);
        update_setting('leadsquared_api_url', $leadsquared_api_url);
        
        $_SESSION['success'] = "LeadSquared settings updated successfully.";
        $_SESSION['active_tab'] = 'leadsquared';
    }
    // Test Email - Redirect to test_email.php via AJAX
    else if (isset($_POST['send_test_email']) && !empty($_POST['test_email'])) {
        // When using AJAX, let test_email.php handle this
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            // Include the test_email.php file directly
            include 'test_email.php';
            exit;
        }
        
        // For non-AJAX fallback
        $test_email = trim($_POST['test_email'] ?? '');
        
        if (empty($test_email) || !filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Please provide a valid email address for testing.";
        } else {
            // Get current SMTP settings
            $smtp_settings = [
                'smtp_host' => get_setting('smtp_host', ''),
                'smtp_port' => get_setting('smtp_port', '587'),
                'smtp_username' => get_setting('smtp_username', ''),
                'smtp_password' => get_setting('smtp_password', ''),
                'smtp_encryption' => get_setting('smtp_encryption', 'tls'),
                'smtp_from_email' => get_setting('smtp_from_email', ''),
                'smtp_from_name' => get_setting('smtp_from_name', 'Karma Experience')
            ];
            
            // Test email settings
            $result = test_email_settings($smtp_settings, $test_email);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
        }
        
        $_SESSION['active_tab'] = 'email';
        header("Location: settings.php");
        exit();
    }
    
    header("Location: settings.php");
    exit();
}

// Refresh settings after possible updates
$settings = get_all_settings();

// Include header and sidebar
require_once 'bheader.php';

// Get active tab (default to general)
$active_tab = $_SESSION['active_tab'] ?? 'general';
unset($_SESSION['active_tab']);

ob_end_flush();
?>

<!-- Admin Sidebar Styles -->
<style>
.admin-wrapper {
    display: flex;
    min-height: calc(100vh - 70px);
}

.admin-sidebar {
    width: 280px;
    background: #2a3950;
    color: white;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    z-index: 100;
    position: fixed;
    top: 70px;
    left: 0;
    bottom: 0;
    overflow-y: auto;
}

.admin-content {
    flex: 1;
    padding: 2rem;
    margin-left: 280px;
    transition: all 0.3s ease;
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-brand {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    text-decoration: none;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-menu li {
    margin-bottom: 0.25rem;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    padding: 0.875rem 1.5rem;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 0.95rem;
}

.sidebar-menu a:hover,
.sidebar-menu a.active {
    background: rgba(180, 151, 90, 0.15);
    color: #b4975a;
}

.sidebar-menu a i {
    margin-right: 0.75rem;
    font-size: 1.1rem;
    width: 1.5rem;
    text-align: center;
}

.sidebar-divider {
    height: 1px;
    background: rgba(255,255,255,0.1);
    margin: 1rem 0;
}

/* Media query for responsive design */
@media (max-width: 991px) {
    .admin-sidebar {
        transform: translateX(-100%);
    }
    
    .admin-sidebar.show {
        transform: translateX(0);
    }
    
    .admin-content {
        margin-left: 0;
    }
}

#sidebarToggle {
    background-color: transparent;
    border: none;
    color: #6b7280;
    font-size: 1.25rem;
    padding: 0.5rem;
    cursor: pointer;
    transition: color 0.2s ease;
}

#sidebarToggle:hover {
    color: #b4975a;
}

.btn-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background-color: #f3f4f6;
}

/* Settings specific styles */
.settings-tabs {
    display: flex;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 2rem;
}

.settings-tabs-item {
    padding: 1rem 1.5rem;
    font-weight: 600;
    color: #4b5563;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    margin-right: 1rem;
}

.settings-tabs-item.active {
    color: #B4975A;
    border-bottom-color: #B4975A;
}

.settings-tabs-item:hover:not(.active) {
    color: #B4975A;
    border-bottom-color: rgba(180, 151, 90, 0.3);
}

.settings-tabs-item i {
    margin-right: 0.5rem;
}

.settings-card {
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    padding: 2rem;
    margin-bottom: 2rem;
}

.settings-section {
    margin-bottom: 2rem;
}

.settings-input {
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    width: 100%;
    transition: all 0.2s ease;
}

.settings-input:focus {
    outline: none;
    border-color: #B4975A;
    box-shadow: 0 0 0 3px rgba(180, 151, 90, 0.2);
}

.settings-label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #4b5563;
}

.settings-button {
    background-color: #B4975A;
    color: white;
    border: none;
    border-radius: 0.375rem;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.settings-button:hover {
    background-color: #9a8049;
}
</style>

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                Karma Experience
            </a>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="destination_list.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'destination_list.php') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-map-marker-alt"></i>
                    Destinations
                </a>
            </li>
            <li>
                <a href="resort_list.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'resort_list.php') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-hotel"></i>
                    Resorts
                </a>
            </li>
            <div class="sidebar-divider"></div>
            <li>
                <a href="admin_blog.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_blog.php') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-blog"></i>
                    Blog Posts
                </a>
            </li>
            <li>
                <a href="view_enquiries.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'view_enquiries.php') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-envelope"></i>
                    Enquiries
                </a>
            </li>
            <div class="sidebar-divider"></div>
            <li>
                <a href="settings.php" class="active">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </li>
            <div class="sidebar-divider"></div>
            <li>
                <a href="logout.php" class="text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0 text-dark font-weight-bold" style="font-size: 2rem;">System Settings</h1>
            <button id="sidebarToggle" class="btn-icon d-lg-none">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
                </div>
            <?php endif; ?>
            
        <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                        </button>
        </div>
        <?php endif; ?>
        
        <div class="settings-tabs" id="settingsTabs">
            <div class="settings-tabs-item <?php echo $active_tab === 'general' ? 'active' : ''; ?>" data-tab="general">
                <i class="fas fa-cog"></i> General
            </div>
            <div class="settings-tabs-item <?php echo $active_tab === 'email' ? 'active' : ''; ?>" data-tab="email">
                <i class="fas fa-envelope"></i> Email Settings
            </div>
            <div class="settings-tabs-item <?php echo $active_tab === 'leadsquared' ? 'active' : ''; ?>" data-tab="leadsquared">
                <i class="fas fa-chart-line"></i> LeadSquared
            </div>
            </div>
            
            <!-- General Settings Tab -->
        <div class="settings-tab-content" id="general" style="display: <?php echo $active_tab === 'general' ? 'block' : 'none'; ?>">
            <div class="settings-card">
                <form method="post" action="">
                    <div class="settings-section">
                        <label for="site_name" class="settings-label">Site Name</label>
                        <input type="text" class="settings-input" id="site_name" name="site_name" value="<?php echo htmlspecialchars(get_setting('site_name', 'Karma Experience')); ?>" required>
                    </div>
                    
                    <div class="settings-section">
                        <label for="site_description" class="settings-label">Site Description</label>
                        <input type="text" class="settings-input" id="site_description" name="site_description" value="<?php echo htmlspecialchars(get_setting('site_description', 'Luxury Travel Experiences')); ?>" required>
                    </div>
                    
                    <div class="settings-section">
                        <label for="admin_email" class="settings-label">Admin Email</label>
                        <input type="email" class="settings-input" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars(get_setting('admin_email', '')); ?>" required>
                    </div>
                    
                    <div class="settings-section">
                        <label for="contact_number" class="settings-label">Contact Number</label>
                        <input type="text" class="settings-input" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars(get_setting('contact_number', '')); ?>">
                    </div>
                    
                    <button type="submit" name="save_general" class="settings-button">Save General Settings</button>
                </form>
            </div>
            </div>
            
        <!-- Email Settings Tab -->
        <div class="settings-tab-content" id="email" style="display: <?php echo $active_tab === 'email' ? 'block' : 'none'; ?>">
            <div class="settings-card">
                <h3 class="mb-4">SMTP Settings</h3>
                
                <form method="post" action="settings.php">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="smtp_host" class="settings-label">SMTP Host</label>
                                <input type="text" id="smtp_host" name="smtp_host" class="settings-input" 
                                       value="<?php echo htmlspecialchars(get_setting('smtp_host', '')); ?>" required>
                                <small class="text-muted">e.g., smtp.gmail.com</small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="smtp_port" class="settings-label">SMTP Port</label>
                                <input type="number" id="smtp_port" name="smtp_port" class="settings-input" 
                                       value="<?php echo htmlspecialchars(get_setting('smtp_port', '587')); ?>" required>
                                <small class="text-muted">Common ports: 587 (TLS), 465 (SSL)</small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="smtp_encryption" class="settings-label">Encryption</label>
                                <select id="smtp_encryption" name="smtp_encryption" class="settings-input">
                                    <option value="tls" <?php echo (get_setting('smtp_encryption') == 'tls') ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo (get_setting('smtp_encryption') == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                                    <option value="" <?php echo (get_setting('smtp_encryption') == '') ? 'selected' : ''; ?>>None</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="smtp_username" class="settings-label">SMTP Username</label>
                                <input type="text" id="smtp_username" name="smtp_username" class="settings-input" 
                                       value="<?php echo htmlspecialchars(get_setting('smtp_username', '')); ?>">
                                <small class="text-muted">Usually your full email address</small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="smtp_password" class="settings-label">SMTP Password</label>
                                <input type="password" id="smtp_password" name="smtp_password" class="settings-input" 
                                       value="<?php echo htmlspecialchars(get_setting('smtp_password', '')); ?>">
                                <small class="text-muted">For Gmail, you may need to use an App Password</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="smtp_from_email" class="settings-label">From Email</label>
                                <input type="email" id="smtp_from_email" name="smtp_from_email" class="settings-input" 
                                       value="<?php echo htmlspecialchars(get_setting('smtp_from_email', '')); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="smtp_from_name" class="settings-label">From Name</label>
                                <input type="text" id="smtp_from_name" name="smtp_from_name" class="settings-input" 
                                       value="<?php echo htmlspecialchars(get_setting('smtp_from_name', 'Karma Experience')); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" name="save_smtp" class="settings-button">Save SMTP Settings</button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <h3 class="mb-3">Test Email Configuration</h3>
                <div id="test-email-result" class="mb-3" style="display: none;"></div>
                
                <form method="post" action="settings.php" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="test_email" class="settings-label">Send Test Email To</label>
                            <input type="email" id="test_email" name="test_email" class="settings-input" 
                                   placeholder="Enter email address" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <button type="submit" name="send_test_email" class="settings-button">
                            <i class="fas fa-paper-plane me-2"></i> Send Test Email
                        </button>
                        <div id="test-email-spinner" class="spinner-border text-primary ms-2" role="status" style="display: none;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- LeadSquared Settings Tab -->
        <div class="settings-tab-content" id="leadsquared" style="display: <?php echo $active_tab === 'leadsquared' ? 'block' : 'none'; ?>">
            <div class="settings-card">
                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="settings-section">
                                <label for="leadsquared_access_key" class="settings-label">Access Key</label>
                                <input type="text" class="settings-input" id="leadsquared_access_key" name="leadsquared_access_key" value="<?php echo htmlspecialchars(get_setting('leadsquared_access_key', '')); ?>" required>
                        </div>
                        
                            <div class="settings-section">
                                <label for="leadsquared_secret_key" class="settings-label">Secret Key</label>
                                <input type="password" class="settings-input" id="leadsquared_secret_key" name="leadsquared_secret_key" value="<?php echo htmlspecialchars(get_setting('leadsquared_secret_key', '')); ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="settings-section">
                                <label for="leadsquared_api_url" class="settings-label">API URL</label>
                                <input type="text" class="settings-input" id="leadsquared_api_url" name="leadsquared_api_url" value="<?php echo htmlspecialchars(get_setting('leadsquared_api_url', 'https://api-in21.leadsquared.com/v2/LeadManagement.svc')); ?>" required>
                                <small class="text-muted d-block mt-1">e.g., https://api-in21.leadsquared.com/v2/LeadManagement.svc</small>
                            </div>
                            
                            <div class="settings-section">
                                <label class="settings-label">Test Connection</label>
                                <button type="button" id="test-leadsquared" class="btn btn-outline-secondary">Test Connection</button>
                                <div id="test-result" class="mt-2 d-none"></div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="save_leadsquared" class="settings-button mt-3">Save LeadSquared Settings</button>
                </form>
            </div>
                    </div>
                </div>
            </div>

            <script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabItems = document.querySelectorAll('.settings-tabs-item');
    const tabContents = document.querySelectorAll('.settings-tab-content');
    
    tabItems.forEach(item => {
        item.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Update active tab
            tabItems.forEach(tab => tab.classList.remove('active'));
            this.classList.add('active');
            
            // Show selected tab content
            tabContents.forEach(content => {
                content.style.display = content.id === tabId ? 'block' : 'none';
            });
        });
    });
    
    // Toggle sidebar for mobile
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.querySelector('.admin-sidebar').classList.toggle('show');
    });
    
    // Test LeadSquared connection
    document.getElementById('test-leadsquared').addEventListener('click', function() {
        const accessKey = document.getElementById('leadsquared_access_key').value;
        const secretKey = document.getElementById('leadsquared_secret_key').value;
        const apiUrl = document.getElementById('leadsquared_api_url').value;
        const resultElement = document.getElementById('test-result');
        
        if (!accessKey || !secretKey || !apiUrl) {
            resultElement.classList.remove('d-none', 'alert-success');
            resultElement.classList.add('alert', 'alert-danger');
            resultElement.textContent = 'Please fill in all LeadSquared fields first.';
            return;
        }
        
        resultElement.classList.remove('d-none', 'alert-success', 'alert-danger');
        resultElement.classList.add('alert', 'alert-info');
        resultElement.textContent = 'Testing connection...';
        
        fetch('test_leadsquared.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                access_key: accessKey,
                secret_key: secretKey,
                api_url: apiUrl
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                resultElement.classList.remove('alert-info', 'alert-danger', 'd-none');
                resultElement.classList.add('alert-success');
                resultElement.innerHTML = '<strong>Success!</strong> ' + data.message;
            } else {
                resultElement.classList.remove('alert-info', 'alert-success', 'd-none');
                resultElement.classList.add('alert-danger');
                
                let errorMessage = '<strong>Error!</strong> ' + data.message;
                
                // Add diagnostic info if available
                if (data.http_code) {
                    errorMessage += '<div class="mt-2 small text-muted">HTTP Status Code: ' + data.http_code + '</div>';
                }
                
                // Add troubleshooting tips
                errorMessage += '<div class="mt-2">';
                errorMessage += '<button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse" data-target="#leadsquaredTroubleshooting">Show Troubleshooting Tips</button>';
                errorMessage += '<div class="collapse mt-2" id="leadsquaredTroubleshooting">';
                errorMessage += '<div class="card card-body bg-light">';
                errorMessage += '<h6>Common LeadSquared Issues:</h6>';
                errorMessage += '<ul class="mb-0">';
                errorMessage += '<li>Double-check your Access Key and Secret Key for typos.</li>';
                errorMessage += '<li>Verify that your LeadSquared account is active.</li>';
                errorMessage += '<li>Check if your API access is enabled in LeadSquared.</li>';
                errorMessage += '<li>Make sure your API URL is correct (should end with LeadManagement.svc).</li>';
                errorMessage += '<li>Try using the default API URL: https://api-in21.leadsquared.com/v2/LeadManagement.svc</li>';
                errorMessage += '</ul>';
                errorMessage += '</div></div></div>';
                
                resultElement.innerHTML = errorMessage;
            }
        })
        .catch(error => {
            resultElement.classList.remove('alert-info', 'alert-success', 'd-none');
            resultElement.classList.add('alert-danger');
            resultElement.innerHTML = '<strong>Error!</strong> Connection test failed. Please check your settings and try again.';
            console.error('LeadSquared Test Error:', error);
        });
    });

    // AJAX Test Email Handler
    const testEmailForm = document.querySelector('form.row.g-3');
    if (testEmailForm) {
        testEmailForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const testEmail = document.getElementById('test_email').value;
            const resultContainer = document.getElementById('test-email-result');
            const spinner = document.getElementById('test-email-spinner');
            
            if (!testEmail) {
                resultContainer.className = 'alert alert-danger mb-3';
                resultContainer.textContent = 'Please enter an email address for testing.';
                resultContainer.style.display = 'block';
                return;
            }
            
            // Show spinner
            spinner.style.display = 'inline-block';
            
            // Clear previous results
            resultContainer.style.display = 'none';
            
            // Get current SMTP settings
            const smtpHost = document.getElementById('smtp_host').value;
            const smtpPort = document.getElementById('smtp_port').value;
            const smtpUsername = document.getElementById('smtp_username').value;
            const smtpPassword = document.getElementById('smtp_password').value;
            const smtpEncryption = document.getElementById('smtp_encryption').value;
            const smtpFromEmail = document.getElementById('smtp_from_email').value;
            const smtpFromName = document.getElementById('smtp_from_name').value;
            
            // Create form data for AJAX request
            const formData = new FormData();
            formData.append('test_email', testEmail);
            formData.append('smtp_host', smtpHost);
            formData.append('smtp_port', smtpPort);
            formData.append('smtp_username', smtpUsername);
            formData.append('smtp_password', smtpPassword);
            formData.append('smtp_encryption', smtpEncryption);
            formData.append('smtp_from_email', smtpFromEmail);
            formData.append('smtp_from_name', smtpFromName);
            
            // Send AJAX request
            fetch('test_email.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Hide spinner
                spinner.style.display = 'none';
                
                // Display result
                resultContainer.className = data.status === 'success' 
                    ? 'alert alert-success mb-3' 
                    : 'alert alert-danger mb-3';
                
                if (data.status === 'success') {
                    resultContainer.innerHTML = '<strong>Success!</strong> ' + data.message;
                } else {
                    let errorMessage = '<strong>Error!</strong> ' + data.message;
                    
                    // If we have detailed error information, show it in smaller text
                    if (data.details) {
                        errorMessage += '<div class="mt-2 small text-muted">Technical details: ' + data.details + '</div>';
                    }
                    
                    // Add a link to common troubleshooting solutions
                    errorMessage += '<div class="mt-2">';
                    errorMessage += '<button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse" data-target="#emailTroubleshooting">Show Troubleshooting Tips</button>';
                    errorMessage += '<div class="collapse mt-2" id="emailTroubleshooting">';
                    errorMessage += '<div class="card card-body bg-light">';
                    errorMessage += '<h6>Common SMTP Issues:</h6>';
                    errorMessage += '<ul class="mb-0">';
                    errorMessage += '<li>For Gmail: Make sure you\'re using an <a href="https://support.google.com/accounts/answer/185833" target="_blank">App Password</a>.</li>';
                    errorMessage += '<li>Check that your SMTP host and port are correct.</li>';
                    errorMessage += '<li>Try different encryption settings (TLS, SSL, or None).</li>';
                    errorMessage += '<li>Verify your username/password are correct.</li>';
                    errorMessage += '<li>Check if your email provider allows less secure apps.</li>';
                    errorMessage += '</ul>';
                    errorMessage += '</div></div></div>';
                    
                    resultContainer.innerHTML = errorMessage;
                }
                
                resultContainer.style.display = 'block';
                
                // Scroll to result
                resultContainer.scrollIntoView({ behavior: 'smooth' });
            })
            .catch(error => {
                // Hide spinner
                spinner.style.display = 'none';
                
                // Show error
                resultContainer.className = 'alert alert-danger mb-3';
                resultContainer.innerHTML = '<strong>Error!</strong> Unable to process your request. Please verify your internet connection and try again.';
                resultContainer.style.display = 'block';
                
                console.error('AJAX Error:', error);
            });
        });
    }
});
            </script>

<?php include 'bfooter.php'; ?>

