<?php
// Start session
session_start();

// Include auth helper for role-based features
require_once 'auth_helper.php';

// Debug: Log access to dashboard page
error_log("Dashboard page accessed. Session status: " . (isset($_SESSION['user_id']) ? "User logged in" : "No user session"));

// Check if user is logged in before anything else
if (!isset($_SESSION['user_id'])) {
    error_log("No user session found, redirecting to login.php");
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'db.php';

// Ensure we have a valid connection
if (!isset($conn) || !$conn) {
    require_once 'db.php'; // Get a fresh connection if needed
}

// Fetch user information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User not found in database
    session_destroy();
    header("Location: login.php");
    exit();
}

$user = $result->fetch_assoc();
$_SESSION['user_type'] = $user['user_type']; // Ensure user_type is in session

// Now include the bheader which has navigation elements
require_once 'bheader.php'; 

// Fetch statistics
$stats = array();

// Only query what the user has permission to see
$query = "SELECT ";
$queryParts = array();

if (hasPermission('campaign_manager')) {
    $queryParts[] = "(SELECT COUNT(*) FROM destinations) as total_destinations";
    $queryParts[] = "(SELECT COUNT(*) FROM resorts) as total_resorts";
    $queryParts[] = "(SELECT COUNT(*) FROM resort_enquiries) as total_enquiries";
}

if (hasPermission('super_admin')) {
    $queryParts[] = "(SELECT COUNT(*) FROM users) as total_users";
}

if (!empty($queryParts)) {
    $query .= implode(", ", $queryParts);
    $result = $conn->query($query);
    $stats = $result->fetch_assoc();
}

// Fetch recent activities based on permissions
$recentEnquiries = array();
$recentResorts = array();
$recentDestinations = array();
$recentUsers = array();

if (hasPermission('campaign_manager')) {
    // Enquiries
    $stmt = $conn->prepare("SELECT 
        e.*, r.resort_name, d.destination_name 
        FROM resort_enquiries e 
        LEFT JOIN resorts r ON e.resort_id = r.id 
        LEFT JOIN destinations d ON e.destination_id = d.id 
        ORDER BY e.created_at DESC LIMIT 5");
    $stmt->execute();
    $recentEnquiries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Resorts
    $stmt = $conn->prepare("SELECT * FROM resorts ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recentResorts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Destinations
    $stmt = $conn->prepare("SELECT * FROM destinations ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recentDestinations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if (hasPermission('super_admin')) {
    // Users
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recentUsers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

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
      cursor: pointer;
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
          <?php if (hasPermission('campaign_manager')): ?>
          <!-- Destinations Card -->
          <a href="destination_list.php" class="stat-card bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg shadow-lg p-6 text-white no-underline block">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm opacity-75">Total Destinations</p>
                <h3 class="text-3xl font-bold"><?php echo $stats['total_destinations'] ?? 0; ?></h3>
              </div>
              <div class="bg-white rounded-full p-4 shadow-lg">
                <i class="fas fa-map-marker-alt text-blue-500 fa-2x"></i>
              </div>
            </div>
            <div class="mt-4">
              <span class="text-sm text-white hover:text-blue-100 flex items-center">
                View all destinations 
                <i class="fas fa-chevron-right ml-2"></i>
              </span>
            </div>
          </a>

          <!-- Resorts Card -->
          <a href="resort_list.php" class="stat-card bg-gradient-to-br from-green-400 to-green-600 rounded-lg shadow-lg p-6 text-white no-underline block">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm opacity-75">Total Resorts</p>
                <h3 class="text-3xl font-bold"><?php echo $stats['total_resorts'] ?? 0; ?></h3>
              </div>
              <div class="bg-white rounded-full p-4 shadow-lg">
                <i class="fas fa-hotel text-green-500 fa-2x"></i>
              </div>
            </div>
            <div class="mt-4">
              <span class="text-sm text-white hover:text-green-100 flex items-center">
                View all resorts
                <i class="fas fa-chevron-right ml-2"></i>
              </span>
            </div>
          </a>

          <!-- Enquiries Card -->
          <a href="view_enquiries.php" class="stat-card bg-gradient-to-br from-orange-400 to-orange-600 rounded-lg shadow-lg p-6 text-white no-underline block">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm opacity-75">Total Enquiries</p>
                <h3 class="text-3xl font-bold"><?php echo $stats['total_enquiries'] ?? 0; ?></h3>
              </div>
              <div class="bg-white rounded-full p-4 shadow-lg">
                <i class="fas fa-envelope text-orange-500 fa-2x"></i>
              </div>
            </div>
            <div class="mt-4">
              <span class="text-sm text-white hover:text-orange-100 flex items-center">
                View all enquiries
                <i class="fas fa-chevron-right ml-2"></i>
              </span>
            </div>
          </a>
          <?php endif; ?>

          <?php if (hasPermission('super_admin')): ?>
          <!-- Users Card -->
          <a href="manage_users.php" class="stat-card bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg shadow-lg p-6 text-white no-underline block">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm opacity-75">Total Users</p>
                <h3 class="text-3xl font-bold"><?php echo $stats['total_users'] ?? 0; ?></h3>
              </div>
              <div class="bg-white rounded-full p-4 shadow-lg">
                <i class="fas fa-users text-purple-500 fa-2x"></i>
              </div>
            </div>
            <div class="mt-4">
              <span class="text-sm text-white hover:text-purple-100 flex items-center">
                View all users
                <i class="fas fa-chevron-right ml-2"></i>
              </span>
            </div>
          </a>
          <?php endif; ?>
        </div>

        <!-- Recent Activities Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <?php if (hasPermission('campaign_manager') && !empty($recentEnquiries)): ?>
          <!-- Recent Enquiries -->
          <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-semibold text-gray-800">Recent Enquiries</h2>
              <a href="view_enquiries.php" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">View all</a>
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
          <?php endif; ?>

          <!-- Recent Activities -->
          <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-semibold text-gray-800">Recent Activities</h2>
            </div>
            <div class="space-y-4">
              <?php if (hasPermission('campaign_manager')): ?>
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
              <?php endif; ?>

              <?php if (hasPermission('super_admin')): ?>
              <!-- Recent Users -->
              <?php foreach ($recentUsers as $recentUser): ?>
              <div class="activity-item flex items-center p-3 rounded-lg border border-gray-100">
                <div class="bg-green-100 rounded-full p-2 mr-4">
                  <i class="fas fa-user text-green-500"></i>
                </div>
                <div>
                  <h4 class="font-medium text-gray-800">New User Registered</h4>
                  <p class="text-sm text-gray-600"><?php echo htmlspecialchars($recentUser['username']); ?> (<?php echo formatRoleName($recentUser['user_type']); ?>)</p>
                  <p class="text-xs text-gray-500 mt-1"><?php echo date('M j, Y', strtotime($recentUser['created_at'])); ?></p>
                </div>
              </div>
              <?php endforeach; ?>
              <?php endif; ?>
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
  function formatRoleName($role) {
    switch ($role) {
        case 'super_admin':
            return 'Super Admin';
        case 'admin':
            return 'Admin';
        case 'campaign_manager':
            return 'Campaign Manager';
        case 'user':
            return 'Regular User';
        default:
            return ucfirst($role);
    }
  }

  function getStatusClass($status) {
    switch ($status) {
        case 'new':
            return 'bg-blue-100 text-blue-800';
        case 'in_progress':
            return 'bg-yellow-100 text-yellow-800';
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
  }
  ?>
</body>
</html>
<?php include 'bfooter.php'; ?>
