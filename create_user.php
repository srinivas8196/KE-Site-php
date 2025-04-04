<?php
session_start();

// Include auth helper and email notification
require_once 'auth_helper.php';
require_once 'email_notification.php';

// Require super_admin permission to access this page
requirePermission('super_admin');

// Include database connection
require_once 'db.php';
global $conn; // Make sure $conn is available globally

$error_message = '';
$success_message = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'user'; // Default to regular user if not specified
    $phone_number = $_POST['phone_number'] ?? '';
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email format';
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = 'Username or email already exists';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, user_type, phone_number) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $email, $hashed_password, $user_type, $phone_number);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                $success_message = 'User created successfully';
                
                // Prepare user data for email notification
                $userData = [
                    'id' => $user_id,
                    'username' => $username,
                    'email' => $email,
                    'user_type' => $user_type,
                    'phone_number' => $phone_number
                ];
                
                // Send email notification to admins
                try {
                    if (sendNewUserNotification($userData)) {
                        $success_message .= '. Admin notification sent.';
                    }
                } catch (Exception $e) {
                    error_log("Error sending notification email: " . $e->getMessage());
                }
                
                // Send login credentials to new user
                try {
                    if (sendUserCredentials($userData, $password)) {
                        $success_message .= ' Login credentials sent to user.';
                    }
                } catch (Exception $e) {
                    error_log("Error sending credentials to user: " . $e->getMessage());
                }
                
                // Clear form data
                $username = '';
                $email = '';
            } else {
                $error_message = 'Error creating user: ' . $conn->error;
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
    <title>Create User - Karma Experience</title>
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
                    <li class="text-gray-600">Create New User</li>
                </ol>
            </nav>
            
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Create New User</h1>
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
            
            <div class="card p-8">
                <form method="POST" action="create_user.php" class="space-y-6">
                    <!-- User Information Section -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">Account Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                        id="username" type="text" name="username" required>
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
                                        id="email" type="email" name="email" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Password Section -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">Security</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Password -->
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2" for="password">
                                    Password <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input class="form-input form-input-icon w-full pl-10 py-2 px-3 border border-gray-300 rounded-md focus:outline-none" 
                                        id="password" type="password" name="password" required>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Password should be at least 8 characters</p>
                            </div>
                            
                            <!-- Confirm Password -->
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2" for="confirm_password">
                                    Confirm Password <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input class="form-input form-input-icon w-full pl-10 py-2 px-3 border border-gray-300 rounded-md focus:outline-none" 
                                        id="confirm_password" type="password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Role Selection -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">User Access Level</h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                            <!-- Super Admin -->
                            <div data-role="super_admin" class="role-card p-4 rounded-md shadow-sm border border-gray-200">
                                <div class="text-center mb-2">
                                    <div class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 text-purple-600 mb-2">
                                        <i class="fas fa-crown"></i>
                                    </div>
                                    <h3 class="font-semibold text-gray-800">Super Admin</h3>
                                </div>
                                <p class="text-sm text-gray-600 text-center">Full access to all features and settings</p>
                            </div>
                            
                            <!-- Admin -->
                            <div data-role="admin" class="role-card p-4 rounded-md shadow-sm border border-gray-200">
                                <div class="text-center mb-2">
                                    <div class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 text-blue-600 mb-2">
                                        <i class="fas fa-user-shield"></i>
                                    </div>
                                    <h3 class="font-semibold text-gray-800">Admin</h3>
                                </div>
                                <p class="text-sm text-gray-600 text-center">Access to everything except user management</p>
                            </div>
                            
                            <!-- Campaign Manager -->
                            <div data-role="campaign_manager" class="role-card p-4 rounded-md shadow-sm border border-gray-200">
                                <div class="text-center mb-2">
                                    <div class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-green-100 text-green-600 mb-2">
                                        <i class="fas fa-tasks"></i>
                                    </div>
                                    <h3 class="font-semibold text-gray-800">Campaign Manager</h3>
                                </div>
                                <p class="text-sm text-gray-600 text-center">Manage campaigns, blogs, and content</p>
                            </div>
                            
                            <!-- Regular User -->
                            <div data-role="user" class="role-card p-4 rounded-md shadow-sm border border-gray-200 selected">
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
                        <input type="hidden" id="user_type" name="user_type" value="user">
                    </div>
                    
                    <div class="pt-4 border-t">
                        <button type="submit" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-md shadow-sm btn-transition flex items-center justify-center">
                            <i class="fas fa-plus-circle mr-2"></i> Create User Account
                        </button>
                    </div>
                </form>
            </div>
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
