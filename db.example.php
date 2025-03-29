<?php
// Example database configuration
// Copy this file to db.php and update with your actual credentials

$host = 'localhost';      // Your database host
$dbname = 'database_name'; // Your database name
$username = 'username';    // Your database username
$password = 'password';    // Your database password

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}
?> 