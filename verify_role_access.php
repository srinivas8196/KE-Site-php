<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include auth helper
require_once 'auth_helper.php';

// Include database connection
require_once 'db.php';

$user_type = $_GET['role'] ?? $_SESSION['user_type'] ?? '';
$test_mode = isset($_GET['role']);

// For testing purposes, we can simulate different roles
if ($test_mode) {
    $_SESSION['test_mode'] = true;
    $_SESSION['user_type'] = $user_type;
}

// Get test user information from database
$users = [];
$stmt = $conn->prepare("SELECT id, username, email, user_type FROM users ORDER BY id");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Get authentication test results
$permissions = [
    'super_admin' => [],
    'admin' => [],
    'campaign_manager' => [],
    'user' => []
];

// Check for access to different pages
$pages = [
    'dashboard.php' => 'Dashboard',
    'manage_users.php' => 'Manage Users',
    'create_user.php' => 'Create User',
    'edit_user.php' => 'Edit User',
    'destination_list.php' => 'Destinations',
    'resort_list.php' => 'Resorts',
    'admin_blog.php' => 'Blog Posts',
    'admin_blog_create.php' => 'Create Blog',
    'admin_blog_edit.php' => 'Edit Blog',
    'view_enquiries.php' => 'Enquiries'
];

// Page to permission map
$page_permissions = [
    'dashboard.php' => 'user',
    'manage_users.php' => 'super_admin',
    'create_user.php' => 'super_admin',
    'edit_user.php' => 'super_admin',
    'destination_list.php' => 'campaign_manager',
    'resort_list.php' => 'campaign_manager',
    'admin_blog.php' => 'campaign_manager',
    'admin_blog_create.php' => 'campaign_manager',
    'admin_blog_edit.php' => 'campaign_manager',
    'view_enquiries.php' => 'campaign_manager'
];

// Test each role against each permission
foreach ($permissions as $role => $values) {
    foreach ($page_permissions as $page => $required_role) {
        // Simulate this role
        $test_session = ['user_type' => $role];
        
        // Check if this role has permission
        $has_access = false;
        
        switch ($required_role) {
            case 'super_admin':
                $has_access = $role === 'super_admin';
                break;
            case 'admin':
                $has_access = in_array($role, ['super_admin', 'admin']);
                break;
            case 'campaign_manager':
                $has_access = in_array($role, ['super_admin', 'admin', 'campaign_manager']);
                break;
            case 'user':
                $has_access = in_array($role, ['super_admin', 'admin', 'campaign_manager', 'user']);
                break;
        }
        
        $permissions[$role][$page] = $has_access;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role-Based Access Verification - Karma Experience</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
    <div class="container mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h1 class="text-2xl font-bold mb-4">Role-Based Access Verification</h1>
            
            <?php if (!empty($user_type)): ?>
                <div class="mb-4 p-3 bg-blue-100 border border-blue-200 rounded">
                    <p>Currently testing with role: <strong><?php echo formatRoleName($user_type); ?></strong></p>
                    
                    <?php if ($test_mode): ?>
                        <p class="text-sm text-gray-500 mt-2">Note: This is a simulation. Your actual user permissions haven't changed.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <h2 class="text-xl font-semibold mb-2">Test Different Roles</h2>
            <div class="flex space-x-2 mb-6">
                <a href="?role=super_admin" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">Test as Super Admin</a>
                <a href="?role=admin" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Test as Admin</a>
                <a href="?role=campaign_manager" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Test as Campaign Manager</a>
                <a href="?role=user" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Test as Regular User</a>
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <a href="?" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">Reset to My Role</a>
                <?php endif; ?>
            </div>
            
            <h2 class="text-xl font-semibold mb-2">Access Matrix</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b border-r">Page</th>
                            <th class="py-2 px-4 border-b border-r bg-purple-100">Super Admin</th>
                            <th class="py-2 px-4 border-b border-r bg-blue-100">Admin</th>
                            <th class="py-2 px-4 border-b border-r bg-green-100">Campaign Manager</th>
                            <th class="py-2 px-4 border-b bg-gray-100">Regular User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page => $page_name): ?>
                            <tr>
                                <td class="py-2 px-4 border-b border-r font-medium"><?php echo $page_name; ?></td>
                                <td class="py-2 px-4 border-b border-r text-center">
                                    <?php if ($permissions['super_admin'][$page]): ?>
                                        <span class="inline-block w-6 h-6 bg-green-500 rounded-full"></span>
                                    <?php else: ?>
                                        <span class="inline-block w-6 h-6 bg-red-500 rounded-full"></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4 border-b border-r text-center">
                                    <?php if ($permissions['admin'][$page]): ?>
                                        <span class="inline-block w-6 h-6 bg-green-500 rounded-full"></span>
                                    <?php else: ?>
                                        <span class="inline-block w-6 h-6 bg-red-500 rounded-full"></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4 border-b border-r text-center">
                                    <?php if ($permissions['campaign_manager'][$page]): ?>
                                        <span class="inline-block w-6 h-6 bg-green-500 rounded-full"></span>
                                    <?php else: ?>
                                        <span class="inline-block w-6 h-6 bg-red-500 rounded-full"></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4 border-b text-center">
                                    <?php if ($permissions['user'][$page]): ?>
                                        <span class="inline-block w-6 h-6 bg-green-500 rounded-full"></span>
                                    <?php else: ?>
                                        <span class="inline-block w-6 h-6 bg-red-500 rounded-full"></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <h2 class="text-xl font-semibold mt-6 mb-2">Current Users in System</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">ID</th>
                            <th class="py-2 px-4 border-b">Username</th>
                            <th class="py-2 px-4 border-b">Email</th>
                            <th class="py-2 px-4 border-b">Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="4" class="py-4 px-4 border-b text-center text-gray-500">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="py-2 px-4 border-b"><?php echo $user['id']; ?></td>
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="py-2 px-4 border-b">
                                        <span class="<?php echo getRoleBadgeClass($user['user_type']); ?>">
                                            <?php echo formatRoleName($user['user_type']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6">
                <a href="dashboard.php" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Helper functions
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

function getRoleBadgeClass($role) {
    switch ($role) {
        case 'super_admin':
            return 'px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800';
        case 'admin':
            return 'px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800';
        case 'campaign_manager':
            return 'px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800';
        case 'user':
            return 'px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800';
        default:
            return 'px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800';
    }
}
?> 