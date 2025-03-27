<?php
require __DIR__ . '/vendor/autoload.php';  // Ensure Composer autoload is loaded

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$dsn = "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'] . ";charset=utf8mb4";
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $conn = new PDO($dsn, $username, $password, $options);
    $pdo = $conn; // Create alias for compatibility

    // Check if the file_path column exists
    $result = $conn->query("SHOW COLUMNS FROM resorts LIKE 'file_path'");
    $exists = $result->fetch();

    // Add file_path column to resorts table if it doesn't exist
    if (!$exists) {
        $conn->exec("ALTER TABLE resorts ADD COLUMN file_path VARCHAR(255)");
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
