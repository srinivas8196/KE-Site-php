<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

$user_id = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!password_verify($current_password, $user['password'])) {
        $_SESSION['error'] = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New passwords do not match.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->execute([$hashed_password, $user_id]);
        $_SESSION['success'] = "Password updated successfully!";
    }
    header("Location: reset-password.php");
    exit();
}
?>

<?php include 'bheader.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<div class="flex">
    <div class="w-64 bg-gray-800 text-white min-h-screen p-4 hidden md:block">
        <h2 class="text-2xl font-bold mb-4">Dashboard</h2>
        <ul>
            <li><a href="dashboard.php" class="block py-2 px-4 hover:bg-gray-700">Dashboard</a></li>
            <li><a href="profile.php" class="block py-2 px-4 hover:bg-gray-700">Profile</a></li>
            <li><a href="reset-password.php" class="block py-2 px-4 hover:bg-gray-700">Reset Password</a></li>
            <li><a href="logout.php" class="block py-2 px-4 hover:bg-red-600">Logout</a></li>
        </ul>
    </div>

    <div class="flex-1 p-6">
        <button class="md:hidden bg-gray-800 text-white px-4 py-2 rounded" onclick="toggleSidebar()">☰ Menu</button>
        <h2 class="text-3xl font-bold mb-6">Reset Password</h2>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="bg-red-500 text-white p-3 rounded mb-4"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <p class="bg-green-500 text-white p-3 rounded mb-4"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php endif; ?>
        
        <form method="POST" class="bg-white p-6 rounded shadow-md w-full max-w-lg">
            <label class="block mb-2">Current Password</label>
            <input type="password" name="current_password" class="w-full p-2 border rounded mb-3" required>
            
            <label class="block mb-2">New Password</label>
            <input type="password" name="new_password" class="w-full p-2 border rounded mb-3" required>
            
            <label class="block mb-2">Confirm New Password</label>
            <input type="password" name="confirm_password" class="w-full p-2 border rounded mb-3" required>
            
            <button type="submit" name="reset_password" class="bg-blue-500 text-white px-4 py-2 rounded">Update Password</button>
        </form>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.querySelector('.w-64').classList.toggle('hidden');
    }
</script>

<?php include 'bfooter.php'; ?>
