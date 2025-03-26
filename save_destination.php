<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $destination_id = $_POST['destination_id'] ?? null;
    $destination_name = $_POST['destination_name'] ?? '';
    $destination_description = $_POST['destination_description'] ?? '';
    
    // Handle file upload
    $banner_image = null;
    if (!empty($_FILES["banner_image"]["name"])) {
        $target_dir = "assets/destinations/";
        $banner_image = basename($_FILES["banner_image"]["name"]);
        $target_file = $target_dir . $banner_image;

        // Move uploaded file to the target directory
        if (!move_uploaded_file($_FILES["banner_image"]["tmp_name"], $target_file)) {
            die("Error uploading image.");
        }
    }

    try {
        if ($destination_id) {
            // Update existing destination
            if ($banner_image) {
                $stmt = $pdo->prepare("UPDATE destinations SET destination_name = ?, destination_description = ?, banner_image = ? WHERE id = ?");
                $stmt->execute([$destination_name, $destination_description, $banner_image, $destination_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE destinations SET destination_name = ?, destination_description = ? WHERE id = ?");
                $stmt->execute([$destination_name, $destination_description, $destination_id]);
            }
        } else {
            // Insert new destination
            $stmt = $pdo->prepare("INSERT INTO destinations (destination_name, destination_description, banner_image) VALUES (?, ?, ?)");
            $stmt->execute([$destination_name, $destination_description, $banner_image]);
        }

        // Redirect back to the destinations list after saving
        header("Location: destination_list.php");
        exit();
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>
