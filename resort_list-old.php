<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];
require 'db.php';
$stmt = $pdo->query("SELECT r.*, d.destination_name FROM resorts r JOIN destinations d ON r.destination_id = d.id ORDER BY d.destination_name, r.resort_name");
$resorts = $stmt->fetchAll();
?>
<?php include 'bheader.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resorts List</title>
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
    /* Simple switch styling */
    .switch {
      position: relative;
      display: inline-block;
      width: 50px;
      height: 24px;
    }
    .switch input { 
      opacity: 0;
      width: 0;
      height: 0;
    }
    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 24px;
    }
    .slider:before {
      position: absolute;
      content: "";
      height: 18px;
      width: 18px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }
    input:checked + .slider {
      background-color: #4ade80; /* green */
    }
    input:checked + .slider:before {
      transform: translateX(26px);
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
          <li class="text-gray-600">Resorts</li>
        </ol>
      </nav>
      <h2 class="text-3xl font-bold mb-6">Resorts List</h2>
      <?php if(count($resorts) > 0): ?>
        <table class="min-w-full bg-white border border-gray-200">
          <thead>
            <tr>
              <th class="py-2 px-4 border-b">Resort Name</th>
              <th class="py-2 px-4 border-b">Destination</th>
              <th class="py-2 px-4 border-b">Active</th>
              <th class="py-2 px-4 border-b">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($resorts as $resort): ?>
            <tr>
              <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($resort['resort_name']); ?></td>
              <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($resort['destination_name']); ?></td>
              <td class="py-2 px-4 border-b text-center">
                <!-- Toggle switch for active status -->
                <label class="switch">
                  <input type="checkbox" class="toggle-active" data-resort-id="<?php echo $resort['id']; ?>" <?php echo ($resort['is_active'] == 1) ? 'checked' : ''; ?>>
                  <span class="slider"></span>
                </label>
              </td>
              <td class="py-2 px-4 border-b">
                <a href="create_or_edit_resort.php?destination_id=<?php echo $resort['destination_id']; ?>&resort_id=<?php echo $resort['id']; ?>" class="bg-yellow-500 text-white px-2 py-1 rounded">Edit</a>
                <?php if ($resort['is_active'] == 1): ?>
                    <a href="<?php echo htmlspecialchars($resort['resort_slug']); ?>" class="bg-blue-500 text-white px-2 py-1 rounded">View</a>
                <?php else: ?>
                    <a href="404.php" class="bg-blue-500 text-white px-2 py-1 rounded">View</a>
                <?php endif; ?>
                <a href="delete_resort.php?id=<?php echo $resort['id']; ?>" class="bg-red-500 text-white px-2 py-1 rounded" onclick="return confirm('Are you sure you want to delete this resort?');">Delete</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>No resorts found.</p>
      <?php endif; ?>
      <br>
      <a href="create_or_edit_resort.php" class="bg-blue-500 text-white px-4 py-2 rounded">Create New Resort</a>
    </main>
  </div>
  <script>
    // Toggle sidebar collapse (if needed)
    document.getElementById('toggleSidebar').addEventListener('click', function() {
      var sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('sidebar-collapsed');
    });

    // Listen for changes on the active toggle switches
    document.querySelectorAll('.toggle-active').forEach(function(checkbox) {
      checkbox.addEventListener('change', function() {
        var resortId = this.getAttribute('data-resort-id');
        var newStatus = this.checked ? 1 : 0;
        // Send AJAX request to update the resort's active status
        fetch('update_resort_status.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ resort_id: resortId, is_active: newStatus })
        })
        .then(response => response.json())
        .then(data => {
          if(data.success){
            // Optionally show a toast or update UI further
          } else {
            alert('Failed to update status.');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error updating status.');
        });
      });
    });
  </script>
</body>
</html>
<?php include 'bfooter.php'; ?>
