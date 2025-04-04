<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PHPMailer Installation Helper</h1>";

// Check if Composer is installed
$composerInstalled = false;
exec('composer --version', $output, $returnVal);
if ($returnVal === 0) {
    echo "<p>✅ Composer is installed: " . $output[0] . "</p>";
    $composerInstalled = true;
} else {
    echo "<p>❌ Composer is not installed. You need to <a href='https://getcomposer.org/download/' target='_blank'>install Composer</a> first.</p>";
}

// Check if vendor directory exists
if (file_exists(__DIR__ . '/vendor')) {
    echo "<p>✅ Vendor directory exists.</p>";
    
    // Check if PHPMailer is already installed
    if (file_exists(__DIR__ . '/vendor/phpmailer/phpmailer')) {
        echo "<p>✅ PHPMailer is already installed.</p>";
        
        // Try to load PHPMailer
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require __DIR__ . '/vendor/autoload.php';
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                echo "<p>✅ PHPMailer is properly autoloaded and ready to use.</p>";
            } else {
                echo "<p>❌ PHPMailer is installed but cannot be autoloaded. You might need to run 'composer dump-autoload'.</p>";
            }
        } else {
            echo "<p>❌ Autoload file is missing. You might need to run 'composer install' again.</p>";
        }
    } else {
        echo "<p>❌ PHPMailer is not installed.</p>";
    }
} else {
    echo "<p>❌ Vendor directory does not exist. You need to initialize Composer in this project.</p>";
}

// Display installation instructions
echo "<h2>Installation Instructions</h2>";

if (!$composerInstalled) {
    echo "<h3>1. Install Composer</h3>";
    echo "<p>Visit <a href='https://getcomposer.org/download/' target='_blank'>https://getcomposer.org/download/</a> and follow the instructions to install Composer.</p>";
    echo "<p>For Windows, download and run the Composer-Setup.exe installer.</p>";
}

echo "<h3>" . ($composerInstalled ? "1" : "2") . ". Install PHPMailer</h3>";
echo "<p>Open a terminal/command prompt in your project root directory and run:</p>";
echo "<pre>composer require phpmailer/phpmailer</pre>";

echo "<h3>" . ($composerInstalled ? "2" : "3") . ". Configure SMTP Settings</h3>";
echo "<p>Make sure your .env file contains the following SMTP settings:</p>";
echo "<pre>
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USERNAME=your-email@example.com
SMTP_PASSWORD=your-password
SMTP_ENCRYPTION=tls
SMTP_FROM_EMAIL=noreply@yoursite.com
SMTP_FROM_NAME=\"Your Site Name\"
</pre>";

echo "<h3>" . ($composerInstalled ? "3" : "4") . ". Test Email</h3>";
echo "<p>Click the button below to test sending an email with your configuration:</p>";
echo "<form method='post' action='test_email.php'>";
echo "<label for='test_email'>Send test email to:</label> ";
echo "<input type='email' name='test_email' id='test_email' placeholder='your-email@example.com' required>";
echo "<button type='submit'>Send Test Email</button>";
echo "</form>";

echo "<h2>Troubleshooting</h2>";
echo "<ul>";
echo "<li>If using Gmail, you'll need to <a href='https://support.google.com/accounts/answer/185833' target='_blank'>create an App Password</a> instead of using your regular password.</li>";
echo "<li>Some hosting providers block outgoing SMTP connections. You might need to use your hosting provider's SMTP server.</li>";
echo "<li>Check your PHP configuration to ensure that the mail functions are enabled.</li>";
echo "</ul>";
?> 