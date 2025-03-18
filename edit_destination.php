<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destinationId = $_POST['id'];
    $destinationName = trim($_POST['name']);
    
    // Sanitize folder name
    $folderName = preg_replace('/[^a-zA-Z0-9]/', '-', strtolower($destinationName));
    $destinationFolder = "assets/destinations/$folderName";
    
    if (!file_exists($destinationFolder)) {
        mkdir($destinationFolder, 0777, true);
    }
    
    // Process banner image if uploaded
    if (!empty($_FILES['banner_image']['name'])) {
        $bannerTmp = $_FILES['banner_image']['tmp_name'];
        $bannerName = $_FILES['banner_image']['name'];
        move_uploaded_file($bannerTmp, "$destinationFolder/$bannerName");

        // Update destination record with new banner image
        $stmt = $pdo->prepare("UPDATE destinations SET destination_name = ?, banner_image = ? WHERE id = ?");
        $stmt->execute([$destinationName, $bannerName, $destinationId]);
    } else {
        // Update destination name only
        $stmt = $pdo->prepare("UPDATE destinations SET destination_name = ? WHERE id = ?");
        $stmt->execute([$destinationName, $destinationId]);
    }
    
    header("Location: destination.php?id=$destinationId");
    exit();
}

// Fetch destination details
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $destination = $stmt->fetch();
}
?>
<?php include 'bheader.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Destination</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
  <div class="flex min-h-screen">
  <aside id="sidebar" class="w-64 bg-white shadow-lg transition-all duration-300">
      <div class="p-6 border-b flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800 sidebar-item-text">Admin Dashboard</h1>
        <button id="toggleSidebar" class="text-gray-700 focus:outline-none">
          <i class="fas fa-bars"></i>
        </button>
      </div>
      <nav class="mt-6">
        <a href="dashboard.php" class="block py-3 px-6 text-gray-700 hover:bg-blue-500 hover:text-white transition-colors flex items-center">
          <i class="fas fa-tachometer-alt mr-2 sidebar-icon"></i> <span class="sidebar-item-text">Dashboard</span>
        </a>
        <?php if(isset($user) && $user['user_type'] === 'super_admin'): ?>
          <a href="manage_users.php" class="block py-3 px-6 text-gray-700 hover:bg-blue-500 hover:text-white transition-colors flex items-center">
            <i class="fas fa-users mr-2 sidebar-icon"></i> <span class="sidebar-item-text">Manage Users</span>
          </a>
        <?php endif; ?>
        <a href="destination_list.php" class="block py-3 px-6 text-gray-700 hover:bg-blue-500 hover:text-white transition-colors flex items-center">
          <i class="fas fa-map-marker-alt mr-2 sidebar-icon"></i> <span class="sidebar-item-text">Manage Destinations</span>
        </a>
        <a href="resort_list.php" class="block py-3 px-6 text-gray-700 hover:bg-blue-500 hover:text-white transition-colors flex items-center">
          <i class="fas fa-hotel mr-2 sidebar-icon"></i> <span class="sidebar-item-text">Manage Resorts</span>
        </a>
        <a href="marketing_template_list.php" class="block py-3 px-6 text-gray-700 hover:bg-blue-500 hover:text-white transition-colors flex items-center">
          <i class="fas fa-envelope-open-text mr-2 sidebar-icon"></i> <span class="sidebar-item-text">Marketing Templates</span>
        </a>
        <a href="campaign_dashboard.php" class="block py-3 px-6 text-gray-700 hover:bg-blue-500 hover:text-white transition-colors flex items-center">
          <i class="fas fa-bullhorn mr-2 sidebar-icon"></i> <span class="sidebar-item-text">Campaign Dashboard</span>
        </a>
        <a href="logout.php" class="block py-3 px-6 text-red-500 hover:bg-red-500 hover:text-white transition-colors flex items-center">
          <i class="fas fa-sign-out-alt mr-2 sidebar-icon"></i> <span class="sidebar-item-text">Logout</span>
        </a>
      </nav>
    </aside>
    <main class="flex-1 p-8">
      <h2 class="text-3xl font-bold mb-6">Edit Destination</h2>
      <form action="edit_destination.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($destination['id']); ?>">
        <div class="mb-3">
          <label for="name" class="form-label">Destination Name (Country):</label>
          <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($destination['destination_name']); ?>" required>
        </div>
        <div class="mb-3">
          <label for="banner_image" class="form-label">Banner Image:</label>
          <input type="file" id="banner_image" name="banner_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        </div>
        <button type="submit" class="btn btn-primary">Update Destination</button>
      </form>
    </main>
  </div>
</body>
</html>
<?php include 'bfooter.php'; ?>
