<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Current settings from session
$logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? 'Not set';
$user_type = $_SESSION['user_type'] ?? 'Not set';
$session_data = $_SESSION;

// Set role if requested
if (isset($_GET['set_role']) && in_array($_GET['set_role'], ['super_admin', 'admin', 'campaign_manager', 'user'])) {
    $_SESSION['user_type'] = $_GET['set_role'];
    $_SESSION['user_id'] = $user_id !== 'Not set' ? $user_id : 1;
    
    // Redirect to remove the query string
    header("Location: check_role.php");
    exit;
}

// Reset session
if (isset($_GET['reset'])) {
    session_destroy();
    header("Location: check_role.php");
    exit;
}

// Permission functions (copied from auth_helper.php for self-containment)
function hasPermission($required_role) {
    if (!isset($_SESSION["user_id"])) {
        return false;
    }
    
    $user_role = $_SESSION["user_type"] ?? "";
    
    // Role hierarchy
    switch ($required_role) {
        case "campaign_manager":
            return in_array($user_role, ["super_admin", "admin", "campaign_manager"]);
            
        case "admin":
            return in_array($user_role, ["super_admin", "admin"]);
            
        case "super_admin":
            return $user_role === "super_admin";
            
        default:
            return false;
    }
}

// Test for various permissions
$permissions = [
    'super_admin' => hasPermission('super_admin'),
    'admin' => hasPermission('admin'),
    'campaign_manager' => hasPermission('campaign_manager'),
];

// Test page access
$pages = [
    'dashboard.php' => 'Dashboard',
    'manage_users.php' => 'Manage Users',
    'create_user.php' => 'Create User',
    'edit_user.php' => 'Edit User',
    'destination_list.php' => 'Destinations', 
    'resort_list.php' => 'Resorts',
    'admin_blog.php' => 'Blog Posts',
    'view_enquiries.php' => 'Enquiries'
];

$access = [];
foreach ($pages as $page => $title) {
    switch ($page) {
        case 'manage_users.php':
        case 'create_user.php':
        case 'edit_user.php':
            $access[$page] = hasPermission('super_admin');
            break;
            
        case 'destination_list.php':
        case 'resort_list.php':
        case 'admin_blog.php':
        case 'view_enquiries.php':
            $access[$page] = hasPermission('campaign_manager');
            break;
            
        default:
            $access[$page] = true; // Dashboard is accessible to all
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Check - Karma Experience</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8 px-4 max-w-4xl">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h1 class="text-2xl font-bold mb-6">Role & Permission Check</h1>
            
            <div class="mb-6">
                <h2 class="text-xl font-semibold mb-3">Current Status</h2>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <p><strong>Logged in:</strong> <?php echo $logged_in ? 'Yes' : 'No'; ?></p>
                    <p><strong>User ID:</strong> <?php echo htmlspecialchars($user_id); ?></p>
                    <p><strong>User Type:</strong> <?php echo htmlspecialchars($user_type); ?></p>
                </div>
            </div>
            
            <div class="mb-6">
                <h2 class="text-xl font-semibold mb-3">Set Role for Testing</h2>
                <div class="flex flex-wrap gap-2">
                    <a href="?set_role=super_admin" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded">Super Admin</a>
                    <a href="?set_role=admin" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Admin</a>
                    <a href="?set_role=campaign_manager" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Campaign Manager</a>
                    <a href="?set_role=user" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">Regular User</a>
                    <a href="?reset" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">Reset Session</a>
                </div>
            </div>
            
            <div class="mb-6">
                <h2 class="text-xl font-semibold mb-3">Current Permissions</h2>
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-<?php echo $permissions['super_admin'] ? 'green' : 'red'; ?>-100 border border-<?php echo $permissions['super_admin'] ? 'green' : 'red'; ?>-200 rounded p-4 text-center">
                        <p class="font-bold mb-1">Super Admin</p>
                        <p><?php echo $permissions['super_admin'] ? 'Yes' : 'No'; ?></p>
                    </div>
                    <div class="bg-<?php echo $permissions['admin'] ? 'green' : 'red'; ?>-100 border border-<?php echo $permissions['admin'] ? 'green' : 'red'; ?>-200 rounded p-4 text-center">
                        <p class="font-bold mb-1">Admin</p>
                        <p><?php echo $permissions['admin'] ? 'Yes' : 'No'; ?></p>
                    </div>
                    <div class="bg-<?php echo $permissions['campaign_manager'] ? 'green' : 'red'; ?>-100 border border-<?php echo $permissions['campaign_manager'] ? 'green' : 'red'; ?>-200 rounded p-4 text-center">
                        <p class="font-bold mb-1">Campaign Manager</p>
                        <p><?php echo $permissions['campaign_manager'] ? 'Yes' : 'No'; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="mb-6">
                <h2 class="text-xl font-semibold mb-3">Page Access</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b">Page</th>
                                <th class="py-2 px-4 border-b">Access</th>
                                <th class="py-2 px-4 border-b">Go to Page</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pages as $page => $title): ?>
                                <tr>
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($title); ?></td>
                                    <td class="py-2 px-4 border-b">
                                        <?php if ($access[$page]): ?>
                                            <span class="inline-block px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Allowed</span>
                                        <?php else: ?>
                                            <span class="inline-block px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">Denied</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-2 px-4 border-b">
                                        <a href="<?php echo $page; ?>" class="text-blue-600 hover:underline">Visit Page</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mt-8">
                <p class="text-sm text-gray-600">
                    Note: This tool sets session variables to simulate different user roles. Use it for testing permissions in your application.
                </p>
            </div>
        </div>
    </div>
</body>
</html> 