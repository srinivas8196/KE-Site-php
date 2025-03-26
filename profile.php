<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

$user_id = $_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT username, email, phone_number FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];

    $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, phone_number=? WHERE id=?");
    $stmt->execute([$username, $email, $phone_number, $user_id]);

    $_SESSION['user']['username'] = $username;
    $_SESSION['user']['email'] = $email;
    $_SESSION['user']['phone_number'] = $phone_number;
    $_SESSION['success'] = "Profile updated successfully!";
    header("Location: profile.php");
    exit();
}
?>

<?php include 'bheader.php'; ?>

<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">


<div class="flex">
    <!-- Sidebar -->
    <div class="w-64 bg-gray-800 text-white min-h-screen p-4 hidden md:block">
        <h2 class="text-2xl font-bold mb-4">Dashboard</h2>
        <ul>
            <li><a href="dashboard.php" class="block py-2 px-4 hover:bg-gray-700">Dashboard</a></li>
            <li><a href="profile.php" class="block py-2 px-4 hover:bg-gray-700">Profile</a></li>
            <li><a href="reset-password.php" class="block py-2 px-4 hover:bg-gray-700">Reset Password</a></li>
            <li><a href="logout.php" class="block py-2 px-4 hover:bg-red-600">Logout</a></li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="flex-1 p-6">
        <button class="md:hidden bg-gray-800 text-white px-4 py-2 rounded" onclick="toggleSidebar()">☰ Menu</button>
        
        <h2 class="text-3xl font-bold mb-6">Profile</h2>
        <?php if (isset($_SESSION['success'])): ?>
            <p class="bg-green-500 text-white p-3 rounded mb-4"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php endif; ?>
        
        <form method="POST" class="bg-white p-6 rounded shadow-md w-full max-w-lg">
            <label class="block mb-2">Username</label>
            <input type="text" name="username" value="<?php echo $user['username']; ?>" class="w-full p-2 border rounded mb-3" required>
            
            <label class="block mb-2">Email</label>
            <input type="email" name="email" value="<?php echo $user['email']; ?>" class="w-full p-2 border rounded mb-3" required>
            
            <label class="block mb-2">Phone Number</label>
            <input type="text" name="phone_number" value="<?php echo $user['phone_number']; ?>" class="w-full p-2 border rounded mb-3" required>
            
            <button type="submit" name="update_profile" class="bg-blue-500 text-white px-4 py-2 rounded">Update Profile</button>
        </form>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.querySelector('.w-64').classList.toggle('hidden');
    }
</script>

<?php include 'bfooter.php'; ?>
