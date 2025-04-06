<?php
session_start();

// Include auth helper
require_once 'auth_helper.php';

// Check if user has campaign_manager or higher permission
requirePermission('campaign_manager', 'login.php');

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
<body class="bg-gray-50">
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
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
