<?php
session_start();

// Include auth helper
require_once 'auth_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'db.php';

$error_message = '';
$success_message = '';
$user_data = null;

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: login.php');
    exit;
}

$user_data = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    
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
            // If wanting to change password, validate the current password and check if new ones match
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $error_message = 'Current password is required to set a new password';
                } elseif ($new_password !== $confirm_password) {
                    $error_message = 'New passwords do not match';
                } elseif (strlen($new_password) < 8) {
                    $error_message = 'Password must be at least 8 characters long';
                } elseif (!password_verify($current_password, $user_data['password'])) {
                    $error_message = 'Current password is incorrect';
                }
            }
            
            if (empty($error_message)) {
                // Update user information
                if (empty($new_password)) {
                    // Update without changing password
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $username, $email, $phone, $user_id);
                } else {
                    // Update with new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $username, $email, $phone, $hashed_password, $user_id);
                }
                
                if ($stmt->execute()) {
                    $success_message = 'Profile updated successfully';
                    
                    // Refresh user data
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user_data = $result->fetch_assoc();
                } else {
                    $error_message = 'Error updating profile: ' . $conn->error;
                }
            }
        }
    }
}
?>
<?php include 'bheader.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">My Profile</h1>
    </div>
    
    <?php if (!empty($error_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r" role="alert">
            <div class="flex">
                <div class="py-1"><i class="fas fa-exclamation-circle text-red-500 mr-3"></i></div>
                <div>
                    <p><?php echo $error_message; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r" role="alert">
            <div class="flex">
                <div class="py-1"><i class="fas fa-check-circle text-green-500 mr-3"></i></div>
                <div>
                    <p><?php echo $success_message; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-3">
            <!-- Left Column - User Summary -->
            <div class="md:col-span-1 bg-gray-50 p-8 border-r border-gray-200">
                <div class="text-center">
                    <div class="user-avatar mx-auto">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2 class="text-2xl font-semibold mb-2"><?php echo htmlspecialchars($user_data['username'] ?? ''); ?></h2>
                    <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($user_data['email'] ?? ''); ?></p>
                    
                    <?php
                    // Define role badge classes
                    $roleBadgeClass = '';
                    $roleIconClass = '';
                    
                    switch($user_data['user_type'] ?? '') {
                        case 'super_admin':
                            $roleBadgeClass = 'bg-purple-100 text-purple-800';
                            $roleIconClass = 'fa-crown text-purple-600';
                            break;
                        case 'admin':
                            $roleBadgeClass = 'bg-blue-100 text-blue-800';
                            $roleIconClass = 'fa-user-shield text-blue-600';
                            break;
                        case 'campaign_manager':
                            $roleBadgeClass = 'bg-green-100 text-green-800';
                            $roleIconClass = 'fa-tasks text-green-600';
                            break;
                        default:
                            $roleBadgeClass = 'bg-gray-100 text-gray-800';
                            $roleIconClass = 'fa-user text-gray-600';
                    }
                    ?>
                    
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $roleBadgeClass; ?> mb-6">
                        <i class="fas <?php echo $roleIconClass; ?> mr-2"></i>
                        <?php echo formatRoleName($user_data['user_type'] ?? ''); ?>
                    </div>
                    
                    <div class="text-left bg-white rounded-lg border border-gray-200 p-4 mb-6">
                        <h3 class="font-semibold text-gray-700 mb-2">Account Info</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">User ID:</span>
                                <span class="font-medium">#<?php echo $user_data['id'] ?? ''; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Joined:</span>
                                <span class="font-medium"><?php echo !empty($user_data['created_at']) ? date('M d, Y', strtotime($user_data['created_at'])) : 'N/A'; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Last Updated:</span>
                                <span class="font-medium"><?php echo !empty($user_data['updated_at']) ? date('M d, Y', strtotime($user_data['updated_at'])) : 'N/A'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Profile Tabs -->
            <div class="md:col-span-2 p-8">
                <!-- Tabs Navigation -->
                <div class="border-b border-gray-200 mb-6">
                    <div class="flex">
                        <div class="tab active" data-tab="profile">
                            <i class="fas fa-user mr-2"></i> Profile Information
                        </div>
                        <div class="tab" data-tab="security">
                            <i class="fas fa-lock mr-2"></i> Security
                        </div>
                    </div>
                </div>
                
                <!-- Profile Tab -->
                <div id="profile-tab" class="tab-content active">
                    <form method="POST" action="myprofile.php" class="space-y-6">
                        <!-- Profile Information -->
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
                                           id="username" type="text" name="username" value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" required>
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
                                           id="email" type="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <!-- Phone Number -->
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2" for="phone">
                                    Phone Number
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-phone text-gray-400"></i>
                                    </div>
                                    <input class="form-input form-input-icon w-full pl-10 py-2 px-3 border border-gray-300 rounded-md focus:outline-none"
                                           id="phone" type="text" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end pt-4">
                            <button type="submit" name="update_profile" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-6 rounded-md shadow-sm transition duration-150 ease-in-out flex items-center">
                                <i class="fas fa-save mr-2"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Security Tab -->
                <div id="security-tab" class="tab-content">
                    <form method="POST" action="myprofile.php" class="space-y-6">
                        <!-- Current Password -->
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2" for="current_password">
                                Current Password <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input class="form-input form-input-icon w-full pl-10 py-2 px-3 border border-gray-300 rounded-md focus:outline-none"
                                       id="current_password" type="password" name="current_password">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Required to change your password</p>
                        </div>
                        
                        <!-- New Password -->
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2" for="new_password">
                                New Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-key text-gray-400"></i>
                                </div>
                                <input class="form-input form-input-icon w-full pl-10 py-2 px-3 border border-gray-300 rounded-md focus:outline-none"
                                       id="new_password" type="password" name="new_password">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Password should be at least 8 characters</p>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2" for="confirm_password">
                                Confirm Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-key text-gray-400"></i>
                                </div>
                                <input class="form-input form-input-icon w-full pl-10 py-2 px-3 border border-gray-300 rounded-md focus:outline-none"
                                       id="confirm_password" type="password" name="confirm_password">
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t mt-6">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-gray-700 font-medium">Password Security</h3>
                                    <p class="text-sm text-gray-500">Last changed: 
                                        <?php echo !empty($user_data['updated_at']) ? date('M d, Y', strtotime($user_data['updated_at'])) : 'Never'; ?>
                                    </p>
                                </div>
                                <button type="submit" name="update_profile" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-6 rounded-md shadow-sm transition duration-150 ease-in-out flex items-center">
                                    <i class="fas fa-save mr-2"></i> Update Password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
  .form-input:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
  }
  .user-avatar {
    width: 120px;
    height: 120px;
    background-color: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: #6b7280;
    border-radius: 50%;
    margin: 0 auto 1.5rem;
    overflow: hidden;
  }
  .user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  /* Increase input padding to prevent overlapping with icons */
  .form-input-icon {
    padding-left: 2.5rem !important;
  }
  .tab {
    cursor: pointer;
    padding: 0.75rem 1rem;
    border-bottom: 2px solid transparent;
    font-weight: 500;
    transition: all 0.2s ease;
  }
  .tab.active {
    border-color: #4f46e5;
    color: #4f46e5;
  }
  .tab:hover:not(.active) {
    color: #6366f1;
    border-color: #e5e7eb;
  }
  .tab-content {
    display: none;
  }
  .tab-content.active {
    display: block;
  }
</style>

<script>
    // Tab functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabName = tab.getAttribute('data-tab');
                
                // Update active tab
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                // Show correct content
                tabContents.forEach(content => {
                    content.classList.remove('active');
                    if (content.id === `${tabName}-tab`) {
                        content.classList.add('active');
                    }
                });
            });
        });
    });
</script>

<?php include 'bfooter.php'; ?>

<?php
// Helper function
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
            return ucfirst(str_replace('_', ' ', $role));
    }
}
?> 