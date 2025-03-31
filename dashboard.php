<?php
session_start();
require_once 'db.php'; // Include your db.php file to establish the database connection
require_once 'bheader.php'; // Include bheader.php to handle session management

// Get dashboard statistics
$statsQuery = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM destinations) AS total_destinations,
        (SELECT COUNT(*) FROM resorts) AS total_resorts,
        (SELECT COUNT(*) FROM campaigns WHERE status = 'active') AS active_campaigns
");
$dashboardStats = $statsQuery->fetch(PDO::FETCH_OBJ);

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];

// Ensure user_type is set
if (!isset($user['user_type'])) {
    $user['user_type'] = 'user'; // Default to 'user' if not set
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard</title>
  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome for Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    .sidebar-collapsed {
      width: 64px;
    }
    .sidebar-collapsed .sidebar-item-text {
      display: none;
    }
    .sidebar-collapsed .sidebar-icon {
      text-align: center;
    }
  </style>
</head>
<body class="bg-gray-100">
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    <!-- Main Content -->
    <main class="flex-1 p-8">
      <!-- Breadcrumb -->
      <nav class="mb-4 text-sm text-gray-600" aria-label="Breadcrumb">
        <ol class="list-reset flex">
          <li><a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a></li>
          <li><span class="mx-2">/</span></li>
          <li class="text-gray-600">Welcome</li>
        </ol>
      </nav>
      <h2 class="text-3xl font-bold mb-6">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h2>
      <!-- Dashboard Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-2">Total Destinations</h3>
          <p class="text-3xl font-bold"><?php echo $dashboardStats->total_destinations; ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-2">Total Resorts</h3>
          <p class="text-3xl font-bold"><?php echo $dashboardStats->total_resorts; ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-2">Active Campaigns</h3>
          <p class="text-3xl font-bold"><?php echo $dashboardStats->active_campaigns; ?></p>
        </div>
      </div>
    </main>
  </div>
  <script>
    document.getElementById('toggleSidebar').addEventListener('click', function() {
      var sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('sidebar-collapsed');
    });
  </script>
</body>
</html>
<?php include 'bfooter.php'; ?>
