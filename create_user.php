<?php
ob_start();
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] != 'super_admin') {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];
require 'db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, phone_number, password, user_type) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$username, $email, $phone, $hashedPassword, $user_type]);
    header("Location: manage_users.php");
    exit();
}
ob_end_flush();
?>
<?php include 'bheader.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create User</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
  <div class="flex min-h-screen">
    <?php include 'admin_dashboard.php'; ?>
    <main class="flex-1 p-6">
      <div class="bg-white shadow-md rounded-lg p-6 max-w-lg mx-auto">
        <h2 class="text-2xl font-semibold text-gray-700 text-center mb-6">Create User</h2>
        <form method="post" action="create_user.php" class="space-y-4">
          <div>
            <label class="block text-gray-600 text-sm font-medium mb-1">Username</label>
            <input type="text" name="username" required class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300">
          </div>
          <div>
            <label class="block text-gray-600 text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" required class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300">
          </div>
          <div>
            <label class="block text-gray-600 text-sm font-medium mb-1">Phone Number</label>
            <input type="text" name="phone" required class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300">
          </div>
          <div>
            <label class="block text-gray-600 text-sm font-medium mb-1">Password</label>
            <input type="password" name="password" required class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300">
          </div>
          <div>
            <label class="block text-gray-600 text-sm font-medium mb-1">User Type</label>
            <select name="user_type" required class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300">
              <option value="" disabled selected>Select Type</option>
              <option value="super_admin">Super Admin</option>
              <option value="admin">Admin</option>
              <option value="campaign_manager">Campaign Manager</option>
            </select>
          </div>
          <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition">Create User</button>
        </form>
      </div>
    </main>
  </div>
</body>
</html>
<?php include 'bfooter.php'; ?>
