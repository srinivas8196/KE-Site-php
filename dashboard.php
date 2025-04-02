<?php
// Start session
session_start();

// Debug: Log access to dashboard page
error_log("Dashboard page accessed. Session status: " . (isset($_SESSION['user']) ? "User logged in" : "No user session"));

// Check if user is logged in before anything else
if (!isset($_SESSION['user'])) {
    error_log("No user session found, redirecting to login.php");
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'db.php';

// Ensure we have a valid PDO connection
if (!isset($pdo) || !$pdo) {
    $pdo = require 'db.php'; // Get a fresh connection if needed
}

// Get user information from session
$user = $_SESSION['user'];
error_log("User logged in: " . $user['username'] . " (ID: " . $user['id'] . ")");

// Ensure user_type is set
if (!isset($user['user_type'])) {
    $user['user_type'] = 'user'; // Default to 'user' if not set
}

// Now include the bheader which has navigation elements
require_once 'bheader.php'; 

// Fetch statistics
$stats = $pdo->query("SELECT 
    (SELECT COUNT(*) FROM destinations) as total_destinations,
    (SELECT COUNT(*) FROM resorts) as total_resorts,
    (SELECT COUNT(*) FROM resort_enquiries) as total_enquiries,
    (SELECT COUNT(*) FROM users) as total_users")->fetch(PDO::FETCH_ASSOC);

// Fetch recent activities
$recentEnquiries = $pdo->query("SELECT 
    e.*, r.resort_name, d.destination_name 
    FROM resort_enquiries e 
    LEFT JOIN resorts r ON e.resort_id = r.id 
    LEFT JOIN destinations d ON e.destination_id = d.id 
    ORDER BY e.created_at DESC LIMIT 5")->fetchAll();

$recentResorts = $pdo->query("SELECT * FROM resorts ORDER BY created_at DESC LIMIT 5")->fetchAll();

$recentDestinations = $pdo->query("SELECT * FROM destinations ORDER BY created_at DESC LIMIT 5")->fetchAll();

$recentUsers = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - Admin Panel</title>
  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome for Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    .stat-card {
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
      z-index: 1;
    }
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    .stat-card:hover::before {
      opacity: 0.2;
    }
    .activity-item {
      transition: all 0.2s ease;
      border: 1px solid #e5e7eb;
    }
    .activity-item:hover {
      transform: translateX(5px);
      border-color: #d1d5db;
      background-color: #f9fafb;
    }
  </style>
</head>
<body class="bg-gray-50">
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    <!-- Main Content -->
    <main class="flex-1 p-8">
      <div class="container mx-auto">
        <!-- Welcome Section -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-800">Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h1>
          <p class="text-gray-600 mt-2">Here's what's happening with your resorts today.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <!-- Destinations Card -->
          <div class="stat-card bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm opacity-75">Total Destinations</p>
                <h3 class="text-3xl font-bold"><?php echo $stats['total_destinations']; ?></h3>
              </div>
              <div class="bg-white rounded-full p-4 shadow-lg">
                <i class="fas fa-map-marker-alt text-blue-500 fa-2x"></i>
              </div>
            </div>
            <div class="mt-4">
              <a href="destinations.php" class="text-sm text-white hover:text-blue-100 flex items-center">
                View all destinations 
                <i class="fas fa-chevron-right ml-2"></i>
              </a>
            </div>
          </div>

          <!-- Resorts Card -->
          <div class="stat-card bg-gradient-to-br from-green-400 to-green-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm opacity-75">Total Resorts</p>
                <h3 class="text-3xl font-bold"><?php echo $stats['total_resorts']; ?></h3>
              </div>
              <div class="bg-white rounded-full p-4 shadow-lg">
                <i class="fas fa-hotel text-green-500 fa-2x"></i>
              </div>
            </div>
            <div class="mt-4">
              <a href="resorts.php" class="text-sm text-white hover:text-green-100 flex items-center">
                View all resorts
                <i class="fas fa-chevron-right ml-2"></i>
              </a>
            </div>
          </div>

          <!-- Enquiries Card -->
          <div class="stat-card bg-gradient-to-br from-orange-400 to-orange-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm opacity-75">Total Enquiries</p>
                <h3 class="text-3xl font-bold"><?php echo $stats['total_enquiries']; ?></h3>
              </div>
              <div class="bg-white rounded-full p-4 shadow-lg">
                <i class="fas fa-envelope text-orange-500 fa-2x"></i>
              </div>
            </div>
            <div class="mt-4">
              <a href="view_enquiries.php" class="text-sm text-white hover:text-orange-100 flex items-center">
                View all enquiries
                <i class="fas fa-chevron-right ml-2"></i>
              </a>
            </div>
          </div>

          <!-- Users Card -->
          <div class="stat-card bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm opacity-75">Total Users</p>
                <h3 class="text-3xl font-bold"><?php echo $stats['total_users']; ?></h3>
              </div>
              <div class="bg-white rounded-full p-4 shadow-lg">
                <i class="fas fa-users text-purple-500 fa-2x"></i>
              </div>
            </div>
            <div class="mt-4">
              <a href="users.php" class="text-sm text-white hover:text-purple-100 flex items-center">
                View all users
                <i class="fas fa-chevron-right ml-2"></i>
              </a>
            </div>
          </div>
        </div>

        <!-- Recent Activities Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Recent Enquiries -->
          <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-semibold text-gray-800">Recent Enquiries</h2>
              <a href="view_enquiries.php" class="text-blue-500 hover:text-blue-600">View all</a>
            </div>
            <div class="space-y-4">
              <?php foreach ($recentEnquiries as $enquiry): ?>
              <div class="activity-item flex items-center p-3 rounded-lg border border-gray-100">
                <div class="bg-blue-100 rounded-full p-2 mr-4">
                  <i class="fas fa-envelope text-blue-500"></i>
                </div>
                <div class="flex-1">
                  <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($enquiry['first_name'] . ' ' . $enquiry['last_name']); ?></h4>
                  <p class="text-sm text-gray-600">Enquired about <?php echo htmlspecialchars($enquiry['resort_name']); ?></p>
                  <p class="text-xs text-gray-500 mt-1"><?php echo date('M j, Y H:i', strtotime($enquiry['created_at'])); ?></p>
                </div>
                <span class="px-2 py-1 text-xs rounded-full <?php echo getStatusClass($enquiry['status']); ?>">
                  <?php echo ucfirst($enquiry['status']); ?>
                </span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Recent Activities -->
          <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-semibold text-gray-800">Recent Activities</h2>
            </div>
            <div class="space-y-4">
              <!-- Recent Resorts -->
              <?php foreach ($recentResorts as $resort): ?>
              <div class="activity-item flex items-center p-3 rounded-lg border border-gray-100">
                <div class="bg-purple-100 rounded-full p-2 mr-4">
                  <i class="fas fa-hotel text-purple-500"></i>
                </div>
                <div>
                  <h4 class="font-medium text-gray-800">New Resort Added</h4>
                  <p class="text-sm text-gray-600"><?php echo htmlspecialchars($resort['resort_name']); ?></p>
                  <p class="text-xs text-gray-500 mt-1"><?php echo date('M j, Y', strtotime($resort['created_at'])); ?></p>
                </div>
              </div>
              <?php endforeach; ?>

              <!-- Recent Destinations -->
              <?php foreach ($recentDestinations as $destination): ?>
              <div class="activity-item flex items-center p-3 rounded-lg border border-gray-100">
                <div class="bg-green-100 rounded-full p-2 mr-4">
                  <i class="fas fa-map-marker-alt text-green-500"></i>
                </div>
                <div>
                  <h4 class="font-medium text-gray-800">New Destination Added</h4>
                  <p class="text-sm text-gray-600"><?php echo htmlspecialchars($destination['destination_name']); ?></p>
                  <p class="text-xs text-gray-500 mt-1"><?php echo date('M j, Y', strtotime($destination['created_at'])); ?></p>
                </div>
              </div>
              <?php endforeach; ?>

              <!-- Recent Users -->
              <?php foreach ($recentUsers as $user): ?>
              <div class="activity-item flex items-center p-3 rounded-lg border border-gray-100">
                <div class="bg-red-100 rounded-full p-2 mr-4">
                  <i class="fas fa-user text-red-500"></i>
                </div>
                <div>
                  <h4 class="font-medium text-gray-800">New User Added</h4>
                  <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['username']); ?></p>
                  <p class="text-xs text-gray-500 mt-1"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
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
  <?php
  function getStatusClass($status) {
    switch($status) {
      case 'new':
        return 'bg-yellow-100 text-yellow-800';
      case 'contacted':
        return 'bg-blue-100 text-blue-800';
      case 'converted':
        return 'bg-green-100 text-green-800';
      case 'closed':
        return 'bg-gray-100 text-gray-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  }
  ?>
</body>
</html>
<?php include 'bfooter.php'; ?>
