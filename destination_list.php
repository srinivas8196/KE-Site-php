<?php
session_start();

// Include auth helper
require_once 'auth_helper.php';

// Check if user has campaign_manager or higher permission
requirePermission('campaign_manager', 'login.php');

require 'db.php';

// Pagination variables
$perPage = 10; // Number of items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Get total number of destinations
$totalStmt = $pdo->query("SELECT COUNT(*) FROM destinations");
$totalDestinations = $totalStmt->fetchColumn();

// Get paginated destinations
$stmt = $pdo->prepare("SELECT * FROM destinations ORDER BY destination_name LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$destinations = $stmt->fetchAll();

include 'bheader.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Destinations List</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
      <nav class="mb-6">
        <ol class="flex items-center space-x-2 text-sm text-gray-600">
          <li><a href="dashboard.php" class="text-blue-500 hover:text-blue-700 transition-colors">Dashboard</a></li>
          <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
          <li class="text-gray-500">Destinations</li>
        </ol>
      </nav>

      <div class="flex items-center justify-between mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Destinations List</h2>
        <a href="create_destination.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition-colors flex items-center space-x-2">
          <i class="fas fa-plus"></i>
          <span>Create New Destination</span>
        </a>
      </div>

      <?php if(count($destinations) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php foreach($destinations as $destination): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
              <div class="relative h-48">
                <?php if (!empty($destination['banner_image'])): ?>
                  <img src="assets/destinations/<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $destination['destination_name'])) . '/' . $destination['banner_image']); ?>" 
                       alt="<?php echo htmlspecialchars($destination['destination_name']); ?>"
                       class="w-full h-full object-cover">
                <?php else: ?>
                  <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                    <i class="fas fa-map-marker-alt text-4xl text-gray-400"></i>
                  </div>
                <?php endif; ?>
                <div class="absolute top-2 right-2 space-x-2">
                  <a href="edit_destination.php?id=<?php echo $destination['id']; ?>" 
                     class="bg-white p-2 rounded-full text-yellow-500 hover:text-yellow-700 transition-colors shadow-md">
                    <i class="fas fa-edit"></i>
                  </a>
                  <a href="delete_destination.php?id=<?php echo $destination['id']; ?>" 
                     onclick="return confirm('Are you sure you want to delete this destination?');"
                     class="bg-white p-2 rounded-full text-red-500 hover:text-red-700 transition-colors shadow-md">
                    <i class="fas fa-trash"></i>
                  </a>
                </div>
              </div>
              <div class="p-4">
                <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($destination['destination_name']); ?></h3>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div class="mt-8 flex justify-between items-center">
          <div class="text-sm text-gray-500">
            Showing <?php echo ($offset + 1) . ' - ' . min($offset + $perPage, $totalDestinations); ?> of <?php echo $totalDestinations; ?> destinations
          </div>
          <div class="flex space-x-2">
            <?php if ($page > 1): ?>
              <a href="?page=<?php echo $page - 1; ?>" class="bg-white px-4 py-2 rounded-lg border text-gray-700 hover:bg-gray-50 transition-colors">
                Previous
              </a>
            <?php endif; ?>
            
            <?php if ($offset + $perPage < $totalDestinations): ?>
              <a href="?page=<?php echo $page + 1; ?>" class="bg-white px-4 py-2 rounded-lg border text-gray-700 hover:bg-gray-50 transition-colors">
                Next
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
          <i class="fas fa-map-marker-alt text-4xl mb-3"></i>
          <p class="text-lg">No destinations found</p>
        </div>
      <?php endif; ?>
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
