<?php
session_start();

// Include auth helper
require_once 'auth_helper.php';

// Check if user has campaign_manager or higher permission
requirePermission('campaign_manager', 'login.php');

// Get database connection
$pdo = require 'db.php';
if (!$pdo) {
    die("Database connection failed");
}

// For campaign_manager, show only their campaigns; for others, show all
if (hasPermission('campaign_manager') && !hasPermission('admin')) {
    $stmt = $pdo->prepare("SELECT c.*, d.destination_name, r.resort_name FROM campaigns c 
                           JOIN destinations d ON c.destination_id = d.id 
                           JOIN resorts r ON c.resort_id = r.id 
                           WHERE c.owner_id = ? ORDER BY c.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    // For super admin and other roles, show all campaigns
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
    <?php include 'sidebar.php'; ?>
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
