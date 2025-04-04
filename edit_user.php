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
$user_data = null;

// Check if user ID is provided
if (!isset($_GET['id']) && !isset($_POST['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$user_id = $_GET['id'] ?? $_POST['user_id'] ?? 0;
$user_id = (int)$user_id;

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: dashboard.php');
    exit;
}

$user_data = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    // Get form data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $user_type = $_POST['user_type'] ?? $user_data['user_type'];
    $new_password = $_POST['new_password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($email)) {
        $error_message = 'Username and email are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email format';
    } else {
        // Check if username or email already exists (excluding current user)
        $stmt = $conn->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->bind_param("ssi", $username, $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = 'Username or email already exists';
        } else {
            // Update user information
            if (empty($new_password)) {
                // Update without changing password
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, user_type = ? WHERE id = ?");
                $stmt->bind_param("sssi", $username, $email, $user_type, $user_id);
            } else {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, user_type = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $username, $email, $hashed_password, $user_type, $user_id);
            }
            
            if ($stmt->execute()) {
                $success_message = 'User updated successfully';
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_data = $result->fetch_assoc();
            } else {
                $error_message = 'Error updating user: ' . $conn->error;
            }
        }
    }
}
?>
<?php include 'bheader.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Karma Experience</title>
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
      .form-input:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
      }
      .btn-transition {
        transition: all 0.3s ease;
      }
      .card {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      }
      .role-card {
        transition: all 0.2s ease;
        cursor: pointer;
        border: 2px solid transparent;
      }
      .role-card:hover {
        transform: translateY(-5px);
      }
      .role-card.selected {
        border-color: #4f46e5;
        background-color: rgba(79, 70, 229, 0.05);
      }
      .user-avatar {
        width: 100px;
        height: 100px;
        background-color: #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: #6b7280;
        border-radius: 50%;
        margin: 0 auto 1rem;
      }
      /* Increase input padding to prevent overlapping with icons */
      .form-input-icon {
        padding-left: 2.5rem !important;
      }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="flex-1 p-6">
            <!-- Breadcrumb -->
            <nav class="mb-4 text-sm text-gray-600" aria-label="Breadcrumb">
                <ol class="list-reset flex">
                    <li><a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a></li>
                    <li><span class="mx-2">/</span></li>
                    <li><a href="manage_users.php" class="text-blue-600 hover:underline">Manage Users</a></li>
                    <li><span class="mx-2">/</span></li>
                    <li class="text-gray-600">Edit User</li>
                </ol>
            </nav>
            
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Edit User Profile</h1>
                <a href="manage_users.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded flex items-center btn-transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Users
                </a>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r animate__animated animate__fadeIn" role="alert">
                    <div class="flex">
                        <div class="py-1"><i class="fas fa-exclamation-circle text-red-500 mr-3"></i></div>
                        <div>
                            <p><?php echo $error_message; ?></p>
                        </div>
                    </div>
                </div>
    <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r animate__animated animate__fadeIn" role="alert">
                    <div class="flex">
                        <div class="py-1"><i class="fas fa-check-circle text-green-500 mr-3"></i></div>
                        <div>
                            <p><?php echo $success_message; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($user_data): ?>
                <div class="card p-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- Left Column - User Summary -->
                        <div class="md:col-span-1">
                            <div class="text-center">
                                <div class="user-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <h2 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($user_data['username']); ?></h2>
                                <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($user_data['email']); ?></p>
                                
                                <div class="bg-gray-100 rounded-md p-4 mb-4">
                                    <div class="mb-3">
                                        <span class="text-sm text-gray-500">Current Role</span>
                                        <div class="mt-1">
                                            <span class="<?php echo getRoleBadgeClass($user_data['user_type']); ?> py-1 px-3 rounded-full text-sm">
                                                <?php echo formatRoleName($user_data['user_type']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <span class="text-sm text-gray-500">User ID</span>
                                        <div class="text-gray-700 font-medium">#<?php echo $user_data['id']; ?></div>
                                    </div>
                                    
                                    <div>
                                        <span class="text-sm text-gray-500">Account Created</span>
                                        <div class="text-gray-700 font-medium">
                                            <?php echo date('M d, Y', strtotime($user_data['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column - Edit Form -->
                        <div class="md:col-span-2">
                            <form method="POST" action="edit_user.php" class="space-y-6">
                                <input type="hidden" name="user_id" value="<?php echo $user_data['id']; ?>">
                                
                                <!-- Account Information -->
                                <div class="mb-6">
                                    <h2 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">Account Information</h2>
                                    
                                    <div class="grid grid-cols-1 gap-6">
                                        <!-- Username -->
                                        <div>
                                            <label class="block text-gray-700 text-sm font-medium mb-2" for="username">
                                                Username <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="fas fa-user text-gray-400"></i>
                                                </div>
                                                <input class="form-input form-input-icon w-full pl-10 py-2 px-3 border border-gray-300 rounded-md focus:outline-none"
                                                       id="username" type="text" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <!-- Email -->
                                        <div>
                                            <label class="block text-gray-700 text-sm font-medium mb-2" for="email">
                                                Email <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="fas fa-envelope text-gray-400"></i>
                                                </div>
                                                <input class="form-input form-input-icon w-full pl-10 py-2 px-3 border border-gray-300 rounded-md focus:outline-none"
                                                       id="email" type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Password Section -->
                                <div class="mb-6">
                                    <h2 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">Security</h2>
                                    
                                    <div>
                                        <label class="block text-gray-700 text-sm font-medium mb-2" for="new_password">
                                            New Password <span class="text-gray-500">(leave blank to keep current)</span>
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-lock text-gray-400"></i>
                                            </div>
                                            <input class="form-input form-input-icon w-full pl-10 py-2 px-3 border border-gray-300 rounded-md focus:outline-none"
                                                   id="new_password" type="password" name="new_password">
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Password should be at least 8 characters</p>
                                    </div>
                                </div>
                                
                                <!-- Role Selection -->
                                <div class="mb-6">
                                    <h2 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">User Access Level</h2>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                        <!-- Super Admin -->
                                        <div data-role="super_admin" class="role-card p-4 rounded-md shadow-sm border border-gray-200 <?php echo ($user_data['user_type'] === 'super_admin' ? 'selected' : ''); ?>">
                                            <div class="text-center mb-2">
                                                <div class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 text-purple-600 mb-2">
                                                    <i class="fas fa-crown"></i>
                                                </div>
                                                <h3 class="font-semibold text-gray-800">Super Admin</h3>
                                            </div>
                                            <p class="text-sm text-gray-600 text-center">Full access to all features and settings</p>
                                        </div>
                                        
                                        <!-- Admin -->
                                        <div data-role="admin" class="role-card p-4 rounded-md shadow-sm border border-gray-200 <?php echo ($user_data['user_type'] === 'admin' ? 'selected' : ''); ?>">
                                            <div class="text-center mb-2">
                                                <div class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 text-blue-600 mb-2">
                                                    <i class="fas fa-user-shield"></i>
                                                </div>
                                                <h3 class="font-semibold text-gray-800">Admin</h3>
                                            </div>
                                            <p class="text-sm text-gray-600 text-center">Access to everything except user management</p>
                                        </div>
                                        
                                        <!-- Campaign Manager -->
                                        <div data-role="campaign_manager" class="role-card p-4 rounded-md shadow-sm border border-gray-200 <?php echo ($user_data['user_type'] === 'campaign_manager' ? 'selected' : ''); ?>">
                                            <div class="text-center mb-2">
                                                <div class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-green-100 text-green-600 mb-2">
                                                    <i class="fas fa-tasks"></i>
                                                </div>
                                                <h3 class="font-semibold text-gray-800">Campaign Manager</h3>
                                            </div>
                                            <p class="text-sm text-gray-600 text-center">Manage campaigns, blogs, and content</p>
                                        </div>
                                        
                                        <!-- Regular User -->
                                        <div data-role="user" class="role-card p-4 rounded-md shadow-sm border border-gray-200 <?php echo ($user_data['user_type'] === 'user' ? 'selected' : ''); ?>">
                                            <div class="text-center mb-2">
                                                <div class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 text-gray-600 mb-2">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <h3 class="font-semibold text-gray-800">Regular User</h3>
                                            </div>
                                            <p class="text-sm text-gray-600 text-center">Basic access permissions only</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Hidden role input, updated by JS -->
                                    <input type="hidden" id="user_type" name="user_type" value="<?php echo $user_data['user_type']; ?>">
                                </div>
                                
                                <div class="flex items-center justify-between pt-4 border-t">
                                    <button type="submit" name="update_user" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-md shadow-sm btn-transition flex items-center">
                                        <i class="fas fa-save mr-2"></i> Save Changes
                                    </button>
                                    <a href="manage_users.php" class="text-gray-600 hover:text-gray-800">Cancel</a>
                </div>
            </form>
        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                    <div class="flex">
                        <div class="py-1"><i class="fas fa-exclamation-triangle text-yellow-500 mr-3"></i></div>
                        <div>
                            <p>User not found.</p>
                            <a href="manage_users.php" class="font-medium underline">Return to user list</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            var sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('sidebar-collapsed');
        });
        
        // Role card selection
        document.querySelectorAll('.role-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected class from all cards
                document.querySelectorAll('.role-card').forEach(c => {
                    c.classList.remove('selected');
                });
                
                // Add selected class to clicked card
                this.classList.add('selected');
                
                // Update hidden input
                document.getElementById('user_type').value = this.dataset.role;
            });
        });
    </script>
</body>
</html>
<?php include 'bfooter.php'; ?>
