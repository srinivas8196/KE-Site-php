<?php
session_start();

// Include auth helper
require_once 'auth_helper.php';

// Check if user has super_admin permission
requirePermission('super_admin', 'login.php');

// Include database connection
require_once 'db.php';

$error_message = '';
$success_message = '';

// Check for messages in the session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Delete user if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    // Don't allow deleting your own account
    if ($user_id == $_SESSION['user_id']) {
        $error_message = "You cannot delete your own account";
    } else {
        // Delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $success_message = "User deleted successfully";
        } else {
            $error_message = "Error deleting user: " . $conn->error;
        }
    }
}

// Get all users
$stmt = $conn->prepare("SELECT id, username, email, user_type, created_at FROM users ORDER BY id");
$stmt->execute();
$result = $stmt->get_result();
$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>
<?php include 'bheader.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Karma Experience</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
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
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="flex-1 p-8">
            <!-- Breadcrumb -->
            <nav class="mb-4 text-sm text-gray-600" aria-label="Breadcrumb">
                <ol class="list-reset flex">
                    <li><a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a></li>
                    <li><span class="mx-2">/</span></li>
                    <li class="text-gray-600">Manage Users</li>
                </ol>
            </nav>
            
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold">Manage Users</h1>
                <div>
                    <a href="create_user.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded mr-2">Create New User</a>
                    <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Back to Dashboard</a>
                </div>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    <p><?php echo $success_message; ?></p>
                </div>
            <?php endif; ?>
            
            <div class="bg-white shadow-md rounded overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr class="w-full h-16 border-gray-300 border-b py-8 bg-gray-100">
                            <th class="text-gray-600 font-semibold pl-6 text-left">ID</th>
                            <th class="text-gray-600 font-semibold pl-6 text-left">Username</th>
                            <th class="text-gray-600 font-semibold pl-6 text-left">Email</th>
                            <th class="text-gray-600 font-semibold pl-6 text-left">Role</th>
                            <th class="text-gray-600 font-semibold pl-6 text-left">Created</th>
                            <th class="text-gray-600 font-semibold pl-6 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr class="h-24 border-gray-300 border-b">
                                <td colspan="6" class="text-center text-gray-600">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr class="h-16 border-gray-300 border-b hover:bg-gray-50">
                                    <td class="pl-6"><?php echo $user['id']; ?></td>
                                    <td class="pl-6"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td class="pl-6"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="pl-6">
                                        <span class="<?php echo getRoleBadgeClass($user['user_type']); ?>">
                                            <?php echo formatRoleName($user['user_type']); ?>
                                        </span>
                                    </td>
                                    <td class="pl-6"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                    <td class="pl-6">
                                        <div class="flex items-center">
                                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="?delete=<?php echo $user['id']; ?>" 
                                                   onclick="return confirm('Are you sure you want to delete this user?');" 
                                                   class="text-red-600 hover:text-red-900">Delete</a>
                                            <?php else: ?>
                                                <span class="text-gray-400">(Current User)</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script>
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            var sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('sidebar-collapsed');
        });
    </script>
</body>
</html>
<?php include 'bfooter.php'; ?>

<?php
// Helper functions for display
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
