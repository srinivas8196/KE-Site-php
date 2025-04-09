<?php
// Start session
session_start();

// Include authentication helper
require_once 'auth_helper.php';

// Check if user has campaign_manager or higher permission
requirePermission('campaign_manager', 'login.php');

// Include database connection
$pdo = require 'db.php';
if (!$pdo) {
    die("Database connection failed");
}

// Function to sanitize inputs
function sanitize($input) {
    return htmlspecialchars($input ?? '', ENT_QUOTES, 'UTF-8');
}

// Create the activity_log table if it doesn't exist
try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        user_id INT NOT NULL, 
        action VARCHAR(100) NOT NULL, 
        details TEXT, 
        ip_address VARCHAR(45), 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');
} catch (Exception $e) {
    $tableError = "Error creating activity_log table: " . $e->getMessage();
}

// Process action filter
$actionFilter = isset($_GET['action']) ? $_GET['action'] : '';
$userFilter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Default to showing the last 7 days if no date filter
if (empty($dateFrom) && empty($dateTo)) {
    $dateFrom = date('Y-m-d', strtotime('-7 days'));
    $dateTo = date('Y-m-d');
}

// Fetch all users for the filter dropdown
$users = [];
try {
    $userStmt = $pdo->query("SELECT id, username FROM users ORDER BY username");
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $userError = "Error fetching users: " . $e->getMessage();
}

// Fetch distinct action types for the filter dropdown
$actionTypes = [];
try {
    $actionStmt = $pdo->query("SELECT DISTINCT action FROM activity_log ORDER BY action");
    $actionTypes = $actionStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $actionTypeError = "Error fetching action types: " . $e->getMessage();
}

// Build the query with filters
$query = "SELECT a.*, u.username 
          FROM activity_log a 
          LEFT JOIN users u ON a.user_id = u.id 
          WHERE 1=1";
$params = [];

if (!empty($actionFilter)) {
    $query .= " AND a.action = ?";
    $params[] = $actionFilter;
}

if ($userFilter > 0) {
    $query .= " AND a.user_id = ?";
    $params[] = $userFilter;
}

if (!empty($dateFrom)) {
    $query .= " AND DATE(a.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $query .= " AND DATE(a.created_at) <= ?";
    $params[] = $dateTo;
}

$query .= " ORDER BY a.created_at DESC";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total count for pagination
$countQuery = str_replace("SELECT a.*, u.username", "SELECT COUNT(*) as total", $query);
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
$totalPages = ceil($total / $perPage);

// Add pagination to the main query
$query .= " LIMIT $offset, $perPage";

// Fetch the activities with filters
$activities = [];
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $activityError = "Error fetching activities: " . $e->getMessage();
}

// Add test activity form
$testMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_test'])) {
    try {
        $action = $_POST['test_action'] ?? 'test_activity';
        $details = $_POST['test_details'] ?? 'Test activity added manually';
        
        $insertStmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $result = $insertStmt->execute([
            $_SESSION['user_id'],
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);
        
        if ($result) {
            $testMessage = "Test activity added successfully!";
            // Redirect to avoid form resubmission
            header("Location: " . $_SERVER['PHP_SELF'] . "?test_added=1");
            exit;
        } else {
            $testMessage = "Failed to add test activity.";
        }
    } catch (Exception $e) {
        $testMessage = "Error adding test activity: " . $e->getMessage();
    }
}

if (isset($_GET['test_added'])) {
    $testMessage = "Test activity added successfully!";
}

// Include header
require_once 'bheader.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .activity-card {
            transition: all 0.2s;
        }
        .activity-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Activity Log</h1>
            <a href="dashboard.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>

        <?php if (isset($tableError)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $tableError; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($testMessage)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $testMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Filter Form -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-lg font-semibold mb-4">Filter Activities</h2>
            <form action="" method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Action Type</label>
                    <select name="action" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <option value="">All Actions</option>
                        <?php foreach ($actionTypes as $type): ?>
                            <option value="<?php echo sanitize($type); ?>" <?php echo $actionFilter === $type ? 'selected' : ''; ?>>
                                <?php echo sanitize(ucwords(str_replace('_', ' ', $type))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                    <select name="user_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <option value="0">All Users</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $userFilter === (int)$user['id'] ? 'selected' : ''; ?>>
                                <?php echo sanitize($user['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" name="date_from" value="<?php echo sanitize($dateFrom); ?>" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" name="date_to" value="<?php echo sanitize($dateTo); ?>" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                </div>
                <div class="md:col-span-4 flex justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-times mr-2"></i>Clear Filters
                    </a>
                </div>
            </form>
        </div>

        <!-- Add Test Activity Form -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-lg font-semibold mb-4">Add Test Activity</h2>
            <form action="" method="post" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Action Type</label>
                    <select name="test_action" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <option value="test_activity">Test Activity</option>
                        <option value="create_resort">Create Resort</option>
                        <option value="update_resort">Update Resort</option>
                        <option value="delete_resort">Delete Resort</option>
                        <option value="resort_status_change">Resort Status Change</option>
                        <option value="resort_partner_change">Resort Partner Change</option>
                        <option value="create_destination">Create Destination</option>
                        <option value="update_destination">Update Destination</option>
                        <option value="delete_destination">Delete Destination</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Details</label>
                    <input type="text" name="test_details" value="Test activity added manually" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                </div>
                <div>
                    <button type="submit" name="add_test" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-plus mr-2"></i>Add Test Activity
                    </button>
                </div>
            </form>
        </div>

        <!-- Activities List -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6 border-b">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold">Activities</h2>
                    <span class="text-gray-500">Showing <?php echo count($activities); ?> of <?php echo $total; ?> activities</span>
                </div>
            </div>

            <?php if (empty($activities)): ?>
                <div class="p-6 text-center text-gray-500">
                    <p>No activities found with the current filters.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date/Time</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($activities as $activity): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $activity['id']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
                                            $actionClass = '';
                                            $icon = 'clipboard-list';
                                            
                                            switch($activity['action']) {
                                                case 'create_resort':
                                                    $actionClass = 'bg-green-100 text-green-800';
                                                    $icon = 'plus-circle';
                                                    break;
                                                case 'update_resort':
                                                    $actionClass = 'bg-blue-100 text-blue-800';
                                                    $icon = 'edit';
                                                    break;
                                                case 'delete_resort':
                                                    $actionClass = 'bg-red-100 text-red-800';
                                                    $icon = 'trash';
                                                    break;
                                                case 'test_activity':
                                                    $actionClass = 'bg-purple-100 text-purple-800';
                                                    $icon = 'flask';
                                                    break;
                                                case 'resort_status_change':
                                                    $actionClass = 'bg-yellow-100 text-yellow-800';
                                                    $icon = 'toggle-on';
                                                    break;
                                                case 'resort_partner_change':
                                                    $actionClass = 'bg-purple-100 text-purple-800';
                                                    $icon = 'handshake';
                                                    break;
                                                case 'create_destination':
                                                    $actionClass = 'bg-green-100 text-green-800';
                                                    $icon = 'map-marker';
                                                    break;
                                                case 'update_destination':
                                                    $actionClass = 'bg-blue-100 text-blue-800';
                                                    $icon = 'edit';
                                                    break;
                                                case 'delete_destination':
                                                    $actionClass = 'bg-red-100 text-red-800';
                                                    $icon = 'trash';
                                                    break;
                                                default:
                                                    $actionClass = 'bg-gray-100 text-gray-800';
                                            }
                                        ?>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $actionClass; ?>">
                                            <i class="fas fa-<?php echo $icon; ?> mr-1"></i>
                                            <?php echo sanitize(ucwords(str_replace('_', ' ', $activity['action']))); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php echo $activity['details'] ?? ''; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo sanitize($activity['username'] ?? 'Unknown'); ?> (ID: <?php echo $activity['user_id']; ?>)
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo sanitize($activity['ip_address']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y g:i:s A', strtotime($activity['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing page <?php echo $page; ?> of <?php echo $totalPages; ?>
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&action=<?php echo urlencode($actionFilter); ?>&user_id=<?php echo $userFilter; ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>" 
                                   class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&action=<?php echo urlencode($actionFilter); ?>&user_id=<?php echo $userFilter; ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>" 
                                   class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'bfooter.php'; ?>
</body>
</html> 