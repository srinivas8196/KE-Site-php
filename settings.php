<?php
ob_start();
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];
require 'db.php';

// Fetch current settings from the database
$stmt = $pdo->prepare("SELECT * FROM settings WHERE id = 1");
$stmt->execute();
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $site_name = trim($_POST['site_name']);
    $admin_email = trim($_POST['admin_email']);
    $contact_number = trim($_POST['contact_number']);
    
    $stmt = $pdo->prepare("UPDATE settings SET site_name = ?, admin_email = ?, contact_number = ? WHERE id = 1");
    $stmt->execute([$site_name, $admin_email, $contact_number]);
    
    $_SESSION['success'] = "Settings updated successfully.";
    header("Location: settings.php");
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
    <title>Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <?php include 'admin_dashboard.php'; ?>
        <main class="flex-1 p-8">
            <h2 class="text-3xl font-bold mb-6">Settings</h2>
            <?php if(isset($_SESSION['success'])): ?>
                <div class="bg-green-500 text-white p-4 rounded mb-4">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            <form method="post" class="bg-white p-6 rounded shadow-md">
                <div class="mb-4">
                    <label class="block font-semibold mb-2">Site Name</label>
                    <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name']) ?>" class="w-full p-2 border rounded">
                </div>
                <div class="mb-4">
                    <label class="block font-semibold mb-2">Admin Email</label>
                    <input type="email" name="admin_email" value="<?= htmlspecialchars($settings['admin_email']) ?>" class="w-full p-2 border rounded">
                </div>
                <div class="mb-4">
                    <label class="block font-semibold mb-2">Contact Number</label>
                    <input type="text" name="contact_number" value="<?= htmlspecialchars($settings['contact_number']) ?>" class="w-full p-2 border rounded">
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Save Changes</button>
            </form>
        </main>
    </div>
</body>
</html>
<?php include 'bfooter.php'; ?>
