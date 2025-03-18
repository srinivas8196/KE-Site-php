<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];
require 'db.php';

// For campaign_manager, show only their campaigns; for others, show all
if ($user['user_type'] == 'campaign_manager') {
    $stmt = $pdo->prepare("SELECT c.*, d.destination_name, r.resort_name FROM campaigns c 
                           JOIN destinations d ON c.destination_id = d.id 
                           JOIN resorts r ON c.resort_id = r.id 
                           WHERE c.owner_id = ? ORDER BY c.created_at DESC");
    $stmt->execute([$user['id']]);
} else {
    $stmt = $pdo->query("SELECT c.*, d.destination_name, r.resort_name FROM campaigns c 
                         JOIN destinations d ON c.destination_id = d.id 
                         JOIN resorts r ON c.resort_id = r.id 
                         ORDER BY c.created_at DESC");
}
$campaigns = $stmt->fetchAll();
?>
<?php include 'bheader.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Campaign Dashboard</title>
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
          <li><a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a></li>
          <li><span class="mx-2">/</span></li>
          <li class="text-gray-600">Campaign Dashboard</li>
        </ol>
      </nav>
      <h2 class="text-3xl font-bold mb-6">Campaign Dashboard</h2>
      <a href="create_campaign.php" class="btn btn-primary mb-3">Create New Campaign</a>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Campaign Name</th>
            <th>Destination</th>
            <th>Resort</th>
            <th>Owner</th>
            <th>Type</th>
            <th>Status</th>
            <th>Schedule</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody></tbody></tbody>
          <?php foreach($campaigns as $campaign): ?>
          <tr>
            <td><?php echo $campaign['campaign_name']; ?></td>
            <td><?php echo $campaign['destination_name']; ?></td>
            <td><?php echo $campaign['resort_name']; ?></td>
            <td><?php // You may query the users table for the owner name ?>
                <?php echo $campaign['owner_id']; ?>
            </td>
            <td><?php echo $campaign['campaign_type'] . " (" . $campaign['campaign_subtype'] . ")"; ?></td>
            <td><?php echo $campaign['status']; ?></td>
            <td><?php echo $campaign['start_date']; ?> to <?php echo $campaign['end_date']; ?></td>
            <td>
              <a href="edit_campaign.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-secondary">Edit</a></td>
              <a href="delete_campaign.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this campaign?');">Delete</a>
              <a href="clone_campaign.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-warning">Clone</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
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
