<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];
require 'db.php';
$stmt = $pdo->query("SELECT * FROM marketing_templates ORDER BY template_name");
$templates = $stmt->fetchAll();
?>
<?php include 'bheader.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Marketing Templates</title>
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
        <?php if($user['user_type'] === 'super_admin'): ?>
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
    <!-- Main Content -->
    <main class="flex-1 p-8">
      <!-- Breadcrumb -->
      <nav class="mb-4 text-sm text-gray-600" aria-label="Breadcrumb">
        <ol class="list-reset flex">
          <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
          <li class="breadcrumb-item active" aria-current="page">Marketing Templates</li>
        </ol>
      </nav>
      <h2>Marketing Templates</h2> <br>
      <a href="create_marketing_template.php" class="btn btn-primary mb-3">Create New Template</a>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Template Name</th>
            <th>Resort</th>
            <th>Nights</th>
            <th>Button Label</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($templates as $temp): ?>
          <tr>
            <td><?php echo htmlspecialchars($temp['template_name']); ?></td>
            <td><?php echo htmlspecialchars($temp['resort_for_template']); ?></td>
            <td><?php echo $temp['nights']; ?></td>
            <td><?php echo $temp['button_label']; ?></td>
            <td>
              <a href="edit_marketing_template.php?id=<?php echo $temp['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
              <a href="delete_marketing_template.php?id=<?php echo $temp['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this template?');">Delete</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </main>
  </div>
  <script>
    document.getElementById('toggleSidebar').addEventListener('click', function() {
      document.getElementById('sidebar').classList.toggle('sidebar-collapsed');
    });
  </script>
</body>
</html>
<?php include 'bfooter.php'; ?>
