<?php
require 'db.php';

$resort_id = $_GET['resort_id'] ?? null;

if (!$resort_id) {
    header("Location: 404.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM resorts WHERE id = ? AND is_active = 1");
$stmt->execute([$resort_id]);
$resort = $stmt->fetch();

if (!$resort) {
    header("Location: 404.php");
    exit();
}

$resort_slug = $resort['slug'];

// ...existing code to display the resort details...
?>
