<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $phone = $_POST['phone_number'];

    $stmt = $conn->prepare("UPDATE users SET email=?, phone_number=? WHERE id=?");
    $stmt->bind_param("ssi", $email, $phone, $user_id);
    
    if ($stmt->execute()) {
        $success = "Profile updated successfully!";
    }
    $stmt->close();
}

$query = "SELECT username, email, phone_number FROM users WHERE id=?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-3xl font-semibold text-gray-800 border-b pb-3 mb-4">Edit Profile</h2>
        <?php if (!empty($success)): ?>
            <p class="bg-green-100 text-green-700 p-2 rounded mb-4"><?php echo $success; ?></p>
        <?php endif; ?>
        <form action="" method="post" class="space-y-4">
            <div>
                <label class="block font-semibold">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full border p-2 rounded">
            </div>
            <div>
                <label class="block font-semibold">Phone Number</label>
                <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" class="w-full border p-2 rounded">
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">Update</button>
        </form>
    </div>
</body>
</html>
