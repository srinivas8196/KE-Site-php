<?php
require 'db.php';

// Function to recursively delete a directory and its contents
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    return rmdir($dir);
}

if (isset($_GET['id'])) {
    $resortId = $_GET['id'];

    // Fetch the landing page file path and resort slug from the database
    $stmt = $pdo->prepare("SELECT file_path, resort_slug FROM resorts WHERE id = ?");
    $stmt->execute([$resortId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $filePath = $result['file_path'];      // e.g., "abc.php"
        $resortSlug = $result['resort_slug'];    // used to locate assets folder

        // Delete the resort record from the database
        $stmt = $pdo->prepare("DELETE FROM resorts WHERE id = ?");
        $stmt->execute([$resortId]);

        // Delete the landing page file if it exists
        if ($filePath && file_exists($filePath)) {
            unlink($filePath);
        }

        // Build the assets directory path (e.g., "assets/resorts/abc")
        $assetsDir = "assets/resorts/" . $resortSlug;
        if (is_dir($assetsDir)) {
            deleteDirectory($assetsDir);
        }
    }
}

header("Location: resort_list.php");
exit();
?>
