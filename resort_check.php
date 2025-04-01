<?php
// Get the database connection
$pdo = require 'db.php';

// Get the current file name (slug)
$currentFile = basename($_SERVER['PHP_SELF'], '.php');

// Check if the resort exists and is active
$stmt = $pdo->prepare("SELECT is_active FROM resorts WHERE resort_slug = ?");
$stmt->execute([$currentFile]);
$resort = $stmt->fetch();

// If resort doesn't exist or is inactive, redirect to 404
if (!$resort || $resort['is_active'] != 1) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit();
}
?> 