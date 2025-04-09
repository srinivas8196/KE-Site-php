<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include PHPMailer
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Try to create a PHPMailer instance
try {
    echo "Creating PHPMailer instance... ";
    $mail = new PHPMailer(true);
    echo "Success!<br>";
    
    echo "PHPMailer version: " . $mail::VERSION . "<br>";
    echo "All OK - PHPMailer is working properly!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 