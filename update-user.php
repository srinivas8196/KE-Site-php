<?php
session_start();
require 'db.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $user_type = $_POST['user_type'];
    $password = $_POST['password'];

    // Update query (without password if left blank)
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $query = "UPDATE users SET username=?, email=?, phone_number=?, user_type=?, password=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssi", $username, $email, $phone_number, $user_type, $hashed_password, $user_id);
    } else {
        $query = "UPDATE users SET username=?, email=?, phone_number=?, user_type=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssi", $username, $email, $phone_number, $user_type, $user_id);
    }

    if ($stmt->execute()) {
        header("Location: manage-users.php?success=User updated successfully");
        exit();
    } else {
        echo "Error updating user.";
    }
}
?>
