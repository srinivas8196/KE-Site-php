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

// Function to ensure logging happens
function log_resort_activity($pdo, $action, $details, $user_id = 1) {
    try {
        // First make sure the table exists
        $pdo->exec('CREATE TABLE IF NOT EXISTS activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY, 
            user_id INT NOT NULL, 
            action VARCHAR(100) NOT NULL, 
            details TEXT, 
            ip_address VARCHAR(45), 
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
        
        // Then add the entry
        $log_stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $log_result = $log_stmt->execute([
            $user_id,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);
        
        if ($log_result) {
            return "Successfully logged activity: $action - $details";
        } else {
            return "Failed to log activity: " . implode(", ", $log_stmt->errorInfo());
        }
    } catch (Exception $log_error) {
        // Just log the error but don't fail the main operation
        return "Error logging activity: " . $log_error->getMessage();
    }
}

$results = [];

// Attempt to insert a test activity
$results[] = log_resort_activity($pdo, 'test_activity_direct', 'Testing direct activity logging', $_SESSION['user_id']);

// Attempt to insert a CREATE resort activity
$results[] = log_resort_activity($pdo, 'create_resort', 'Created test resort: "Test Resort"', $_SESSION['user_id']);

// Attempt to insert an UPDATE resort activity
$results[] = log_resort_activity($pdo, 'update_resort', 'Updated test resort: "Test Resort"', $_SESSION['user_id']);

// Attempt to insert a DELETE resort activity
$results[] = log_resort_activity($pdo, 'delete_resort', 'Deleted test resort: "Test Resort"', $_SESSION['user_id']);

// Test new activity types
$results[] = log_resort_activity($pdo, 'resort_status_change', 'Resort status changed: "Test Resort" was activated', $_SESSION['user_id']);
$results[] = log_resort_activity($pdo, 'resort_partner_change', 'Resort partner status changed: "Test Resort" was added as partner', $_SESSION['user_id']);
$results[] = log_resort_activity($pdo, 'create_destination', 'Created new destination: "Test Destination"', $_SESSION['user_id']);
$results[] = log_resort_activity($pdo, 'update_destination', 'Updated destination: "Test Destination"', $_SESSION['user_id']);
$results[] = log_resort_activity($pdo, 'delete_destination', 'Deleted destination: "Test Destination"', $_SESSION['user_id']);

// Check if the activities were logged
$stmt = $pdo->query("SELECT a.*, u.username 
                   FROM activity_log a 
                   LEFT JOIN users u ON a.user_id = u.id 
                   ORDER BY a.created_at DESC LIMIT 10");
$recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include the header
require_once 'bheader.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Activity Logging</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Activity Logging Test</h1>
            <div>
                <a href="dashboard.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded mr-2">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
                <a href="check_activity_log.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-list mr-2"></i>View Activity Log
                </a>
            </div>
        </div>

        <!-- Test Results -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-lg font-semibold mb-4">Test Results</h2>
            
            <div class="space-y-4">
                <?php foreach ($results as $index => $result): ?>
                <div class="p-4 border rounded <?php echo strpos($result, 'Successfully') !== false ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'; ?>">
                    <h3 class="font-medium">Test #<?php echo $index + 1; ?></h3>
                    <p class="mt-1 text-sm <?php echo strpos($result, 'Successfully') !== false ? 'text-green-700' : 'text-red-700'; ?>">
                        <?php echo htmlspecialchars($result); ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold">Recent Activities</h2>
                <p class="text-sm text-gray-500 mt-1">Most recent activities in the log, including the test entries we just added.</p>
            </div>

            <?php if (empty($recentActivities)): ?>
                <div class="p-6 text-center text-gray-500">
                    <p>No activities found. Something went wrong with the logging.</p>
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date/Time</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recentActivities as $activity): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($activity['id']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
                                            $actionClass = 'bg-gray-100 text-gray-800';
                                            if ($activity['action'] == 'create_resort') {
                                                $actionClass = 'bg-green-100 text-green-800';
                                            } else if ($activity['action'] == 'update_resort') {
                                                $actionClass = 'bg-blue-100 text-blue-800';
                                            } else if ($activity['action'] == 'delete_resort') {
                                                $actionClass = 'bg-red-100 text-red-800';
                                            } else if ($activity['action'] == 'test_activity_direct') {
                                                $actionClass = 'bg-purple-100 text-purple-800';
                                            } else if ($activity['action'] == 'resort_status_change') {
                                                $actionClass = 'bg-yellow-100 text-yellow-800';
                                            } else if ($activity['action'] == 'resort_partner_change') {
                                                $actionClass = 'bg-purple-100 text-purple-800';
                                            } else if ($activity['action'] == 'create_destination') {
                                                $actionClass = 'bg-green-100 text-green-800';
                                            } else if ($activity['action'] == 'update_destination') {
                                                $actionClass = 'bg-blue-100 text-blue-800';
                                            } else if ($activity['action'] == 'delete_destination') {
                                                $actionClass = 'bg-red-100 text-red-800';
                                            }
                                        ?>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $actionClass; ?>">
                                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $activity['action']))); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($activity['details'] ?? ''); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($activity['username'] ?? 'Unknown'); ?> (ID: <?php echo htmlspecialchars($activity['user_id']); ?>)
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
        </div>
    </div>

    <?php include 'bfooter.php'; ?>
</body>
</html> 