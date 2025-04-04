<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require 'db.php';

$error_message = '';
$success_message = '';
$user = $_SESSION['user']; // Default to session data

// Get user data from database
try {
    $user_id = $_SESSION['user']['id'];
    $stmt = $conn->prepare("SELECT id, username, email, user_type, phone_number FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If we found a user, use that data
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        $error_message = "Unable to find your user profile. Please contact support.";
    }
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    
    if (empty($username) || empty($email)) {
        $error_message = "Username and email are required fields";
    } else {
        try {
            // Update user info
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone_number = ? WHERE id = ?");
            $stmt->bind_param("sssi", $username, $email, $phone_number, $user_id);
            
            if ($stmt->execute()) {
                // Update session data
                $_SESSION['user']['username'] = $username;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['phone_number'] = $phone_number;
                
                // Update local user data
                $user['username'] = $username;
                $user['email'] = $email;
                $user['phone_number'] = $phone_number;
                
                $success_message = "Profile updated successfully!";
            } else {
                $error_message = "Failed to update profile: " . $conn->error;
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}
?>

<?php include 'bheader.php'; ?>

<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<div class="flex">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 p-6">
        <h2 class="text-3xl font-bold mb-6">Profile</h2>
        
        <?php if (!empty($success_message)): ?>
            <p class="bg-green-500 text-white p-3 rounded mb-4"><?php echo $success_message; ?></p>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <p class="bg-red-500 text-white p-3 rounded mb-4"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        
        <form method="POST" action="profile.php" class="bg-white p-6 rounded shadow-md w-full max-w-lg">
            <div class="mb-4">
                <label class="block mb-2">Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" class="w-full p-2 border rounded mb-3" required>
            </div>
            
            <div class="mb-4">
                <label class="block mb-2">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="w-full p-2 border rounded mb-3" required>
            </div>
            
            <div class="mb-4">
                <label class="block mb-2">Phone Number</label>
                <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" class="w-full p-2 border rounded mb-3">
            </div>
            
            <button type="submit" name="update_profile" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Update Profile</button>
        </form>
    </div>
</div>

<?php include 'bfooter.php'; ?>
