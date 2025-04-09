<?php
// Start session
session_start();

// Debug info
error_log("Dashboard accessed - Session ID: " . session_id());
error_log("Dashboard - Session data: " . json_encode($_SESSION));

// Include auth helper for role-based features
require_once 'auth_helper.php';

// Debug: Log access to dashboard page
error_log("Dashboard page accessed. Session status: " . (isset($_SESSION['user_id']) ? "User logged in (ID: {$_SESSION['user_id']})" : "No user session"));

// Check if user is logged in before anything else
if (!isset($_SESSION['user_id'])) {
    error_log("No user session found on dashboard, redirecting to login.php");
    
    // Clear any potential session data
    session_unset();
    session_destroy();
    
    // Redirect to login page
    header("Location: login.php?nosession=1");
    exit();
}

// Include database connection
$pdo = require_once 'db.php';

// Ensure we have a valid connection
if (!isset($pdo) || !$pdo) {
    error_log("Database connection failed in dashboard");
    die("Database connection error");
}

// Fetch user information using PDO
$user_id = $_SESSION['user_id'];
error_log("Fetching user data for ID: " . $user_id);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // User not found in database
    error_log("User ID {$user_id} not found in database");
    session_unset();
    session_destroy();
    header("Location: login.php?invalid_user=1");
    exit();
}

error_log("User data found: " . json_encode($user));
$_SESSION['user_type'] = $user['user_type']; // Ensure user_type is in session

// Now include the bheader which has navigation elements
require_once 'bheader.php'; 

// Fetch statistics
$stats = array();

// Only query what the user has permission to see
if (hasPermission('campaign_manager')) {
    $stmt = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM destinations) as total_destinations,
        (SELECT COUNT(*) FROM resorts) as total_resorts,
        (SELECT COUNT(*) FROM resort_enquiries) as total_enquiries");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (hasPermission('super_admin')) {
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_users'] = $user_stats['total_users'];
}

// Fetch recent activities based on permissions
$recentEnquiries = array();
$recentResorts = array();
$recentDestinations = array();
$recentUsers = array();
$recentActivities = array(); // For activities from activity_log
$combinedActivities = array(); // For sorting all activities together

// Create activity_log table if it doesn't exist
try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        user_id INT NOT NULL, 
        action VARCHAR(100) NOT NULL, 
        details TEXT, 
        ip_address VARCHAR(45), 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');
    error_log("Checked/created activity_log table in dashboard");
} catch (Exception $e) {
    error_log("Error checking/creating activity_log table: " . $e->getMessage());
}

if (hasPermission('campaign_manager')) {
    // Enquiries
    $stmt = $pdo->prepare("SELECT 
        e.*, r.resort_name, d.destination_name 
        FROM resort_enquiries e 
        LEFT JOIN resorts r ON e.resort_id = r.id 
        LEFT JOIN destinations d ON e.destination_id = d.id 
        ORDER BY e.created_at DESC LIMIT 5");
    $stmt->execute();
    $recentEnquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add enquiries to combined activities
    foreach ($recentEnquiries as $enquiry) {
        $combinedActivities[] = [
            'type' => 'enquiry',
            'title' => 'New Enquiry',
            'name' => $enquiry['first_name'] . ' ' . $enquiry['last_name'],
            'details' => 'For ' . $enquiry['resort_name'],
            'icon' => 'envelope',
            'icon_bg' => 'blue',
            'username' => '',
            'timestamp' => $enquiry['created_at']
        ];
    }
    
    // Recent resorts with created_at in the last 30 days
    $stmt = $pdo->prepare("SELECT * FROM resorts 
                          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                          ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $recentResorts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add resorts to combined activities
    foreach ($recentResorts as $resort) {
        $combinedActivities[] = [
            'type' => 'resort_added',
            'title' => 'New Resort Added',
            'name' => $resort['resort_name'],
            'details' => '',
            'icon' => 'hotel',
            'icon_bg' => 'purple',
            'username' => '',
            'timestamp' => $resort['created_at']
        ];
    }
    
    // Destinations
    $stmt = $pdo->prepare("SELECT * FROM destinations ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recentDestinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add destinations to combined activities
    foreach ($recentDestinations as $destination) {
        $combinedActivities[] = [
            'type' => 'destination_added',
            'title' => 'New Destination Added',
            'name' => $destination['destination_name'],
            'details' => '',
            'icon' => 'map-marker-alt',
            'icon_bg' => 'green',
            'username' => '',
            'timestamp' => $destination['created_at']
        ];
    }
    
    // Get activities from activity_log 
    try {
        // Fetch recent activities
        error_log("Fetching activity log entries...");
        $stmt = $pdo->query("SELECT a.*, u.username 
                          FROM activity_log a 
                          LEFT JOIN users u ON a.user_id = u.id 
                          ORDER BY a.created_at DESC LIMIT 20");
        $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Fetched " . count($recentActivities) . " activity log entries");
        
        // Add to combined activities
        foreach ($recentActivities as $activity) {
            $icon = 'clipboard-list';
            $bg = 'gray';
            
            if ($activity['action'] == 'delete_resort') {
                $icon = 'trash';
                $bg = 'red';
                $title = 'Resort Deleted';
            } elseif ($activity['action'] == 'update_resort') {
                $icon = 'edit';
                $bg = 'blue';
                $title = 'Resort Updated';
            } elseif ($activity['action'] == 'create_resort') {
                $icon = 'plus-circle';
                $bg = 'green';
                $title = 'Resort Created';
            } elseif ($activity['action'] == 'test_activity') {
                $icon = 'bell';
                $bg = 'purple';
                $title = 'Test Activity';
            } elseif ($activity['action'] == 'resort_status_change') {
                $icon = 'toggle-on';
                $bg = 'yellow';
                $title = 'Resort Status Changed';
            } elseif ($activity['action'] == 'resort_partner_change') {
                $icon = 'handshake';
                $bg = 'purple';
                $title = 'Resort Partner Status Changed';
            } elseif ($activity['action'] == 'create_destination') {
                $icon = 'map-marker';
                $bg = 'green';
                $title = 'Destination Created';
            } elseif ($activity['action'] == 'update_destination') {
                $icon = 'edit';
                $bg = 'blue';
                $title = 'Destination Updated';
            } elseif ($activity['action'] == 'delete_destination') {
                $icon = 'trash';
                $bg = 'red';
                $title = 'Destination Deleted';
            } else {
                $title = ucwords(str_replace('_', ' ', $activity['action']));
            }
            
            $combinedActivities[] = [
                'type' => $activity['action'],
                'title' => $title,
                'name' => '',
                'details' => $activity['details'] ?? '',
                'icon' => $icon,
                'icon_bg' => $bg,
                'username' => $activity['username'] ?? 'Unknown User',
                'timestamp' => $activity['created_at']
            ];
            error_log("Added activity to combinedActivities: " . $activity['action'] . " - " . ($activity['details'] ?? 'No details'));
        }
    } catch (Exception $e) {
        // Just log the error but don't affect page rendering
        error_log("Error handling activity log: " . $e->getMessage());
    }
}

if (hasPermission('super_admin')) {
    // Users
    $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recentUsers = $stmt->fetchAll();
    
    // Add to combined activities
    foreach ($recentUsers as $user) {
        $combinedActivities[] = [
            'type' => 'user_added',
            'title' => 'New User Registered',
            'name' => $user['username'],
            'details' => formatRoleName($user['user_type']),
            'icon' => 'user',
            'icon_bg' => 'green',
            'username' => '',
            'timestamp' => $user['created_at']
        ];
    }
}

// Sort all activities by timestamp, newest first
usort($combinedActivities, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Limit to the 15 most recent activities
$combinedActivities = array_slice($combinedActivities, 0, 15);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - Admin Panel</title>
  <!-- Direct sidebar toggle script -->
  <script>
    function toggleSidebar() {
      const adminSidebar = document.querySelector('.admin-sidebar');
      const adminContent = document.querySelector('.admin-content');
      if (adminSidebar) {
        adminSidebar.classList.toggle('collapsed');
        if (adminContent) {
          adminContent.classList.toggle('expanded');
        }
        localStorage.setItem('sidebarCollapsed', adminSidebar.classList.contains('collapsed'));
      }
    }
    
    // Apply immediately when DOM loads
    document.addEventListener('DOMContentLoaded', function() {
      // Set up toggle button
      const toggleBtn = document.getElementById('sidebarToggle');
      if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          toggleSidebar();
          return false;
        });
        
        // Also assign direct onclick to ensure it works
        toggleBtn.onclick = function(e) {
          if (e) {
            e.preventDefault();
            e.stopPropagation();
          }
          toggleSidebar();
          return false;
        };
      }
      
      // Apply saved state
      if (localStorage.getItem('sidebarCollapsed') === 'true') {
        const sidebar = document.querySelector('.admin-sidebar');
        const content = document.querySelector('.admin-content');
        if (sidebar) {
          sidebar.classList.add('collapsed');
          if (content) content.classList.add('expanded');
        }
      }
    });
  </script>
  
  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome for Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Additional styles for collapsed sidebar -->
  <style>
    /* Dashboard-specific sidebar styles */
    .admin-sidebar.collapsed {
      width: 70px !important;
    }
    
    .admin-sidebar.collapsed .sidebar-header h3,
    .admin-sidebar.collapsed .nav-text,
    .admin-sidebar.collapsed .sidebar-brand span,
    .admin-sidebar.collapsed .user-details,
    .admin-sidebar.collapsed .sidebar-menu a span,
    .admin-sidebar.collapsed .sidebar-menu-item span {
      display: none !important;
    }
    
    .admin-sidebar.collapsed .sidebar-menu a {
      justify-content: center;
      padding: 0.875rem 0;
    }
    
    .admin-sidebar.collapsed .sidebar-menu a i {
      margin-right: 0;
      font-size: 1.25rem;
    }
    
    .admin-sidebar.collapsed .sidebar-footer a {
      justify-content: center;
      text-align: center;
    }
    
    .admin-sidebar.collapsed .sidebar-footer a i {
      margin-right: 0;
    }
    
    .sidebar-item-text {
      transition: opacity 0.3s ease;
    }
    
    .admin-sidebar.collapsed .sidebar-item-text {
      display: none;
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
    <!-- Include sidebar.php which properly uses admin-sidebar class -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Main Content with adjusted padding -->
    <div class="admin-content">
      <div class="content-container">
        <!-- Welcome Section with reduced margins -->
        <div class="mb-4">
          <h1 class="text-3xl font-bold text-gray-800">Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h1>
          <p class="text-gray-600 mt-2">Here's what's happening with your resorts today.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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
                  <p class="text-sm text-gray-600">Enquired about <?php echo htmlspecialchars($enquiry['resort_name'] ?? 'a resort'); ?></p>
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
              <a href="check_activity_log.php" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">View all</a>
            </div>
            <div class="space-y-4">
              <?php if (hasPermission('campaign_manager')): ?>
              
              <!-- Combined Activities -->
              <?php if (!empty($combinedActivities)): ?>
                <?php foreach ($combinedActivities as $activity): ?>
                <div class="activity-item flex items-center p-3 rounded-lg border border-gray-100">
                  <div class="bg-<?php echo $activity['icon_bg']; ?>-100 rounded-full p-2 mr-4">
                    <i class="fas fa-<?php echo $activity['icon']; ?> text-<?php echo $activity['icon_bg']; ?>-500"></i>
                  </div>
                  <div class="flex-1">
                    <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($activity['title']); ?></h4>
                    
                    <p class="text-sm text-gray-600">
                      <?php if (!empty($activity['name'])): ?>
                        <?php echo htmlspecialchars($activity['name']); ?>
                        <?php if (!empty($activity['details'])): ?>
                          <span class="text-gray-500">(<?php echo htmlspecialchars($activity['details']); ?>)</span>
                        <?php endif; ?>
                      <?php else: ?>
                        <?php echo htmlspecialchars($activity['details'] ?? 'No details available'); ?>
                      <?php endif; ?>
                    </p>
                    
                    <p class="text-xs text-gray-500 mt-1">
                      <?php echo date('M j, Y g:i A', strtotime($activity['timestamp'])); ?>
                      <?php if (!empty($activity['username'])): ?>
                        by <?php echo htmlspecialchars($activity['username']); ?>
                      <?php endif; ?>
                    </p>
                  </div>
                </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="text-gray-500 text-center py-4">No recent activities to display</p>
              <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Stop any continuous loading -->
  <script>
  // Check for any redirects or continuous loading
  if (window.stop) {
    window.stop();
  }
  // Reset any refresh meta tags
  document.querySelectorAll('meta[http-equiv="refresh"]').forEach(function(meta) {
    meta.remove();
  });
  // Cancel any pending Ajax requests
  if (window.jQuery) {
    jQuery.ajax({
      global: false
    });
  }
  // Set a flag to prevent multiple initializations
  window.dashboardLoaded = true;
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
