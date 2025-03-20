<?php
require 'db.php';
if (isset($_GET['id'])) {
    // Fetch the file path associated with the resort
    $stmt = $pdo->prepare("SELECT file_path FROM resorts WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $file = $stmt->fetchColumn();

    // Delete the resort from the database
    $stmt = $pdo->prepare("DELETE FROM resorts WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    // Delete the file from the server
    if ($file && file_exists($file)) {
        unlink($file);
    }
}
header("Location: resort_list.php");
exit();
?>
