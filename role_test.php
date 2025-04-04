<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set the user type directly from GET parameter
if (isset($_GET['role']) && in_array($_GET['role'], ['super_admin', 'admin', 'campaign_manager', 'user'])) {
    $_SESSION['user_type'] = $_GET['role'];
    $_SESSION['test_role'] = true;
    
    // Set a dummy user ID if not already set
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 1;
    }
}

// Reset session
if (isset($_GET['reset'])) {
    unset($_SESSION['test_role']);
    unset($_SESSION['user_type']);
    session_destroy();
    header("Location: role_test.php");
    exit;
}

// Get current role
$current_role = $_SESSION['user_type'] ?? 'none';
$testing_mode = isset($_SESSION['test_role']) && $_SESSION['test_role'] === true;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Test - Karma Experience</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
    <div class="container mx-auto max-w-4xl">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h1 class="text-2xl font-bold mb-4">Role Testing Utility</h1>
            
            <?php if ($testing_mode): ?>
                <div class="mb-6 p-4 bg-blue-100 border border-blue-200 rounded">
                    <p class="text-lg">You are currently testing as: <span class="font-bold"><?php echo ucwords(str_replace('_', ' ', $current_role)); ?></span></p>
                    <p class="text-sm text-gray-600 mt-2">Note: This is a simulation mode. Your session has been modified for testing purposes.</p>
                </div>
            <?php else: ?>
                <div class="mb-6 p-4 bg-yellow-100 border border-yellow-200 rounded">
                    <p>You are not currently in any role testing mode.</p>
                </div>
            <?php endif; ?>
            
            <h2 class="text-xl font-semibold mb-4">Select a role to simulate:</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <a href="?role=super_admin" class="bg-purple-600 text-white rounded-lg p-4 text-center hover:bg-purple-700 transition">
                    <div class="text-xl font-bold">Super Admin</div>
                    <div class="mt-2 text-sm">Full access to all features including user management</div>
                </a>
                
                <a href="?role=admin" class="bg-blue-600 text-white rounded-lg p-4 text-center hover:bg-blue-700 transition">
                    <div class="text-xl font-bold">Admin</div>
                    <div class="mt-2 text-sm">Access to everything except user management</div>
                </a>
                
                <a href="?role=campaign_manager" class="bg-green-600 text-white rounded-lg p-4 text-center hover:bg-green-700 transition">
                    <div class="text-xl font-bold">Campaign Manager</div>
                    <div class="mt-2 text-sm">Can manage campaigns, blogs, resorts, etc.</div>
                </a>
                
                <a href="?role=user" class="bg-gray-600 text-white rounded-lg p-4 text-center hover:bg-gray-700 transition">
                    <div class="text-xl font-bold">Regular User</div>
                    <div class="mt-2 text-sm">Basic access only</div>
                </a>
            </div>
            
            <?php if ($testing_mode): ?>
                <h2 class="text-xl font-semibold mb-4">Where to go next:</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <a href="dashboard.php" class="bg-indigo-600 text-white rounded-lg p-4 text-center hover:bg-indigo-700 transition">Go to Dashboard</a>
                    <a href="?reset=1" class="bg-red-600 text-white rounded-lg p-4 text-center hover:bg-red-700 transition">Reset Session</a>
                </div>
                
                <div class="mt-6 bg-gray-100 p-4 rounded-lg">
                    <h3 class="font-semibold mb-2">Role Permissions:</h3>
                    <ul class="list-disc pl-6 space-y-2">
                        <li><strong>Super Admin:</strong> Can access everything including user management (manage_users.php, create_user.php, edit_user.php)</li>
                        <li><strong>Admin:</strong> Can access everything except user management</li>
                        <li><strong>Campaign Manager:</strong> Can access destinations, resorts, blogs, and campaigns</li>
                        <li><strong>Regular User:</strong> Limited access to basic features only</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 