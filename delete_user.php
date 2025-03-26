<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] !== 'super_admin') {
    header("Location: login.php");
    exit();
}
require 'db.php';

if (!isset($_GET['id'])) {
    echo "User ID not specified.";
    exit();
}

$userId = $_GET['id'];

// Optionally, prevent deletion of your own account:
if ($userId == $_SESSION['user']['id']) {
    echo "You cannot delete your own account.";
    exit();
}

$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$userId]);

header("Location: manage_users.php");
exit();
?>
