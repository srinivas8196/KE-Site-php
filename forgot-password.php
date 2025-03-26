<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $token = bin2hex(random_bytes(50));
        $stmt = $conn->prepare("UPDATE users SET reset_token=? WHERE email=?");
        $stmt->bind_param("ss", $token, $email);
        $stmt->execute();
        
        $resetLink = "http://yourwebsite.com/reset-password.php?token=$token";
        $subject = "Password Reset";
        $message = "Click here to reset your password: $resetLink";
        mail($email, $subject, $message);
        
        $success = "A reset link has been sent to your email.";
    } else {
        $error = "Email not found!";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-3xl font-semibold text-gray-800 border-b pb-3 mb-4">Forgot Password</h2>
        <?php if (!empty($success)) echo "<p class='text-green-600'>$success</p>"; ?>
        <?php if (!empty($error)) echo "<p class='text-red-600'>$error</p>"; ?>
        <form action="" method="post">
            <label class="block font-semibold">Email Address</label>
            <input type="email" name="email" required class="w-full border p-2 rounded">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded mt-3">Send Reset Link</button>
        </form>
    </div>
</body>
</html>
