<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if required parameters are present
if (!isset($_GET['resort_id']) || !isset($_GET['room_index']) || !isset($_GET['image'])) {
    $_SESSION['error_message'] = "Missing required parameters.";
    header('Location: resort_list.php');
    exit();
}

$resort_id = (int)$_GET['resort_id'];
$room_index = (int)$_GET['room_index'];
$image_path = trim($_GET['image']);

// Validate parameters
if ($resort_id <= 0 || $room_index < 0 || empty($image_path)) {
    $_SESSION['error_message'] = "Invalid parameters.";
    header('Location: resort_list.php');
    exit();
}

// Initialize database connection
require 'db.php';

// Start debug logging
$debug_log = fopen('room_image_delete_debug.log', 'a');
fwrite($debug_log, "\n=== " . date('Y-m-d H:i:s') . " ===\n");
fwrite($debug_log, "Deleting room image for resort ID: $resort_id\n");
fwrite($debug_log, "Room index: $room_index\n");
fwrite($debug_log, "Image path: $image_path\n");

try {
    // Get resort details to find the resort slug
    $stmt = $pdo->prepare("SELECT * FROM resorts WHERE id = ?");
    $stmt->execute([$resort_id]);
    $resort = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$resort) {
        fwrite($debug_log, "Error: Resort not found\n");
        $_SESSION['error_message'] = "Resort not found.";
        header('Location: resort_list.php');
        exit();
    }
    
    $resort_slug = $resort['resort_slug'];
    $full_file_path = "assets/resorts/$resort_slug/$image_path";
    
    fwrite($debug_log, "Resort slug: $resort_slug\n");
    fwrite($debug_log, "Full file path: $full_file_path\n");
    
    // 1. Get current room details
    $rooms = json_decode($resort['room_details'], true) ?: [];
    fwrite($debug_log, "Current rooms before modification: " . print_r($rooms, true) . "\n");
    
    // 2. Update the room to remove the image
    if (isset($rooms[$room_index])) {
        // Save the room name
        $room_name = $rooms[$room_index]['name'];
        
        // Remove the image from the room
        $rooms[$room_index]['image'] = '';
        
        fwrite($debug_log, "Updated room data: " . print_r($rooms[$room_index], true) . "\n");
    } else {
        fwrite($debug_log, "Error: Room index not found in rooms array\n");
        $_SESSION['error_message'] = "Room not found.";
        header("Location: create_or_edit_resort.php?resort_id=$resort_id&destination_id=" . $resort['destination_id']);
        exit();
    }
    
    // 3. Update the database with the modified rooms array
    $rooms_json = json_encode($rooms);
    $stmt = $pdo->prepare("UPDATE resorts SET room_details = ? WHERE id = ?");
    $result = $stmt->execute([$rooms_json, $resort_id]);
    
    if (!$result) {
        fwrite($debug_log, "Error updating database: " . print_r($stmt->errorInfo(), true) . "\n");
        $_SESSION['error_message'] = "Failed to update database.";
        header("Location: create_or_edit_resort.php?resort_id=$resort_id&destination_id=" . $resort['destination_id']);
        exit();
    }
    
    // 4. Delete the physical file
    if (file_exists($full_file_path)) {
        if (unlink($full_file_path)) {
            fwrite($debug_log, "File deleted successfully: $full_file_path\n");
        } else {
            fwrite($debug_log, "Failed to delete file: $full_file_path\n");
            $_SESSION['warning_message'] = "Database updated but failed to delete the physical file.";
        }
    } else {
        fwrite($debug_log, "File not found: $full_file_path\n");
        $_SESSION['warning_message'] = "Image removed from database but file not found on server.";
    }
    
    $_SESSION['success_message'] = "Room image deleted successfully.";
    
} catch (Exception $e) {
    fwrite($debug_log, "Exception: " . $e->getMessage() . "\n");
    $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
}

fclose($debug_log);

// Redirect back to the edit page
header("Location: create_or_edit_resort.php?resort_id=$resort_id&destination_id=" . $resort['destination_id']);
exit();
?> 