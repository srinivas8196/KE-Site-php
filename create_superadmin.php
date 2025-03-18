<?php
require 'db.php'; // Ensure your db.php is correctly set up

$username = "sriadmin";
$password = "Karma@321";

// Generate a secure hash using bcrypt
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert the new user into the users table
$stmt = $pdo->prepare("INSERT INTO users (username, password, user_type) VALUES (?, ?, ?)");
$stmt->execute([$username, $hashedPassword, "super_admin"]);

echo "Superadmin user '$username' created successfully.";
?>
