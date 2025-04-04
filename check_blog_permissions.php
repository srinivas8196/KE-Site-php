<?php
session_start();

// Include auth helper if it exists
if (file_exists('auth_helper.php')) {
    require_once 'auth_helper.php';
}

// Check database connection
require_once 'db.php';

echo "<h1>Blog Permission Check</h1>";

// Check if the auth_helper.php file exists
if (!file_exists('auth_helper.php')) {
    echo "<div style='color: red; padding: 10px; background: #ffebee; margin: 10px 0;'>
        <strong>Warning:</strong> auth_helper.php file is missing! 
        Please run role_management.php and click 'Create Authentication Helper'.
    </div>";
} else {
    echo "<div style='color: green; padding: 10px; background: #e8f5e9; margin: 10px 0;'>
        auth_helper.php exists and is included.
    </div>";
}

// Check current session status
echo "<h2>Current Session Status</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
    
    // Check if user_type is set in session
    if (isset($_SESSION['user_type'])) {
        echo "<p>User Type: " . $_SESSION['user_type'] . "</p>";
        
        // Verify permissions
        if (function_exists('hasPermission')) {
            echo "<h3>Permission Check Results</h3>";
            echo "<ul>";
            echo "<li>Permission to create/edit blogs (campaign_manager): " . 
                (hasPermission('campaign_manager') ? "<span style='color:green'>Yes</span>" : "<span style='color:red'>No</span>") . "</li>";
            echo "<li>Permission to manage users (super_admin): " . 
                (hasPermission('super_admin') ? "<span style='color:green'>Yes</span>" : "<span style='color:red'>No</span>") . "</li>";
            echo "</ul>";
            
            // Specific check for blog creation
            if (hasPermission('campaign_manager')) {
                echo "<div style='color: green; padding: 15px; background: #e8f5e9; margin: 15px 0; border-radius: 5px;'>
                    <strong>SUCCESS:</strong> You have permission to create and manage blogs.
                    <br><a href='admin_blog_create.php' style='display: inline-block; margin-top: 10px; padding: 8px 15px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Create New Blog</a>
                </div>";
            } else {
                echo "<div style='color: red; padding: 15px; background: #ffebee; margin: 15px 0; border-radius: 5px;'>
                    <strong>ERROR:</strong> You do not have permission to create blogs.
                    <br>Required role: campaign_manager, admin, or super_admin
                    <br>Your role: " . ($_SESSION['user_type'] ?? 'Not set') . "
                </div>";
            }
        } else {
            echo "<div style='color: red; padding: 10px; background: #ffebee; margin: 10px 0;'>
                <strong>Error:</strong> hasPermission() function not found. 
                Make sure auth_helper.php is properly included.
            </div>";
        }
    } else {
        echo "<div style='color: red; padding: 10px; background: #ffebee; margin: 10px 0;'>
            <strong>Error:</strong> User is logged in but user_type is not set in session.
            <br>Please make sure the login.php file is updated to include user_type in the session.
        </div>";
        
        // Get the user's type from database
        $mysqli_host = $_ENV['DB_HOST'] ?? 'localhost';
        $mysqli_user = $_ENV['DB_USER'] ?? 'root';
        $mysqli_pass = $_ENV['DB_PASS'] ?? '';
        $mysqli_db = $_ENV['DB_NAME'] ?? 'karmaexperience';
        
        $conn = new mysqli($mysqli_host, $mysqli_user, $mysqli_pass, $mysqli_db);
        if (!$conn->connect_error) {
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                echo "<div style='padding: 10px; background: #e3f2fd; margin: 10px 0;'>
                    User type in database: " . ($row['user_type'] ?? 'Not set') . "
                    <form method='post' action='check_blog_permissions.php'>
                        <input type='hidden' name='action' value='fix_session'>
                        <input type='hidden' name='user_type' value='" . ($row['user_type'] ?? '') . "'>
                        <button type='submit'>Fix Session</button>
                    </form>
                </div>";
            } else {
                echo "<p>Could not find user in database</p>";
            }
        }
    }
} else {
    echo "<div style='color: red; padding: 10px; background: #ffebee; margin: 10px 0;'>
        <strong>Error:</strong> No user is logged in.
        <br><a href='login.php'>Go to Login Page</a>
    </div>";
    
    // Create sample login for testing
    echo "<h3>Create Test Session</h3>";
    echo "<p>Use this form to simulate a login for testing purposes:</p>";
    echo "<form method='post' action='check_blog_permissions.php'>";
    echo "<input type='hidden' name='action' value='test_login'>";
    echo "<select name='user_type'>";
    echo "<option value='super_admin'>super_admin</option>";
    echo "<option value='admin'>admin</option>";
    echo "<option value='campaign_manager'>campaign_manager</option>";
    echo "<option value='user'>user (regular)</option>";
    echo "</select>";
    echo "<button type='submit'>Simulate Login</button>";
    echo "</form>";
}

// Handle form submissions
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'fix_session':
            if (isset($_POST['user_type'])) {
                $_SESSION['user_type'] = $_POST['user_type'];
                echo "<script>window.location.reload();</script>";
            }
            break;
            
        case 'test_login':
            if (isset($_POST['user_type'])) {
                $_SESSION['user_id'] = 999; // Dummy ID
                $_SESSION['user_type'] = $_POST['user_type'];
                echo "<script>window.location.reload();</script>";
            }
            break;
    }
}

// Display links to related pages
echo "<h2>Related Pages</h2>";
echo "<ul>";
echo "<li><a href='admin_blog_create.php'>Create New Blog</a></li>";
echo "<li><a href='admin_blog.php'>Manage Blogs</a></li>";
echo "<li><a href='role_management.php'>Manage User Roles</a></li>";
echo "<li><a href='update_all_auth.php'>Update All Authentication</a></li>";
echo "<li><a href='login.php'>Login Page</a></li>";
echo "<li><a href='logout.php'>Logout</a></li>";
echo "</ul>";

// Logout button
echo "<form method='post' action='check_blog_permissions.php'>";
echo "<input type='hidden' name='action' value='logout'>";
echo "<button type='submit' style='background: #f44336; color: white; border: none; padding: 10px 15px; cursor: pointer;'>Clear Session (Logout)</button>";
echo "</form>";

// Handle logout
if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    session_unset();
    session_destroy();
    echo "<script>window.location.reload();</script>";
}
?> 