<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db.php';

// Create database connection
$mysqli_host = $_ENV['DB_HOST'] ?? 'localhost';
$mysqli_user = $_ENV['DB_USER'] ?? 'root';
$mysqli_pass = $_ENV['DB_PASS'] ?? '';
$mysqli_db = $_ENV['DB_NAME'] ?? 'karmaexperience';

// Create mysqli connection
$conn = new mysqli($mysqli_host, $mysqli_user, $mysqli_pass, $mysqli_db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Role Management System</h1>";

// Define user roles and their descriptions
$roles = [
    'super_admin' => 'Can do everything - manage destinations, users, resorts, campaigns, blogs, enquiries with no restrictions',
    'admin' => 'Can do everything except create users',
    'campaign_manager' => 'Can view all data, but only manage campaigns and blogs'
];

// Update user roles form
echo "<h2>Update User Roles</h2>";

// Display all users
$result = $conn->query("SELECT * FROM users");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Current Role</th>
            <th>Action</th>
          </tr>";
    
    while ($user = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['user_type'] ?? 'Not set') . "</td>";
        echo "<td>
                <form method='post' action='role_management.php'>
                    <input type='hidden' name='user_id' value='" . $user['id'] . "'>
                    <select name='user_type'>
                        <option value=''>Select role...</option>";
        
        foreach ($roles as $role => $description) {
            $selected = ($user['user_type'] == $role) ? 'selected' : '';
            echo "<option value='$role' $selected>$role</option>";
        }
        
        echo "    </select>
                    <button type='submit' name='update_role'>Update</button>
                </form>
              </td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No users found in the database.</p>";
}

// Handle role update
if (isset($_POST['update_role']) && isset($_POST['user_id']) && isset($_POST['user_type'])) {
    $user_id = (int)$_POST['user_id'];
    $user_type = $_POST['user_type'];
    
    if (!empty($user_type)) {
        $stmt = $conn->prepare("UPDATE users SET user_type = ? WHERE id = ?");
        $stmt->bind_param("si", $user_type, $user_id);
        
        if ($stmt->execute()) {
            echo "<div style='color: green; padding: 10px; background: #e8f5e9; margin: 10px 0;'>User role updated successfully!</div>";
        } else {
            echo "<div style='color: red; padding: 10px; background: #ffebee; margin: 10px 0;'>Error updating user role: " . $stmt->error . "</div>";
        }
    } else {
        echo "<div style='color: red; padding: 10px; background: #ffebee; margin: 10px 0;'>Please select a valid role.</div>";
    }
}

// Display role descriptions
echo "<h2>Role Descriptions</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Role</th><th>Description</th></tr>";

foreach ($roles as $role => $description) {
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($role) . "</strong></td>";
    echo "<td>" . htmlspecialchars($description) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Setup authentication function
echo "<h2>Setup Role-Based Authentication</h2>";
echo "<p>Create a helper function for role-based authentication.</p>";

// Form to generate authentication helper
echo "<form method='post' action='role_management.php'>";
echo "<button type='submit' name='create_auth_helper'>Create Authentication Helper</button>";
echo "</form>";

// Create auth_helper.php when requested
if (isset($_POST['create_auth_helper'])) {
    $auth_helper_code = '<?php
/**
 * Role-based Authentication Helper Functions
 */

/**
 * Check if user has permission to access a specific feature
 * 
 * @param string $required_role Minimum role required (super_admin, admin, campaign_manager)
 * @return bool True if user has permission, false otherwise
 */
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

/**
 * Redirect user if they don\'t have the required permission
 * 
 * @param string $required_role Minimum role required
 * @param string $redirect_url URL to redirect to if permission is denied
 */
function requirePermission($required_role, $redirect_url = "login.php") {
    if (!hasPermission($required_role)) {
        $_SESSION["error_message"] = "You don\'t have permission to access this page.";
        header("Location: " . $redirect_url);
        exit;
    }
}

/**
 * Check if functionality should be displayed based on user role
 * 
 * @param string $required_role Minimum role required
 * @return bool True if functionality should be shown, false otherwise
 */
function showForRole($required_role) {
    return hasPermission($required_role);
}
';

    if (file_put_contents('auth_helper.php', $auth_helper_code)) {
        echo "<div style='color: green; padding: 10px; background: #e8f5e9; margin: 10px 0;'>Authentication helper file created successfully: auth_helper.php</div>";
    } else {
        echo "<div style='color: red; padding: 10px; background: #ffebee; margin: 10px 0;'>Error creating authentication helper file.</div>";
    }
}

// Update blog creation file
echo "<h2>Update Blog Creation Authentication</h2>";
echo "<p>Update admin_blog_create.php to use the role-based authentication.</p>";

echo "<form method='post' action='role_management.php'>";
echo "<button type='submit' name='update_blog_auth'>Update Blog Authentication</button>";
echo "</form>";

// Update admin_blog_create.php
if (isset($_POST['update_blog_auth'])) {
    $file_path = 'admin_blog_create.php';
    $file_contents = file_get_contents($file_path);
    
    if ($file_contents) {
        // Replace existing authentication with role-based auth
        $auth_code = "<?php
session_start();

// Include auth helper
require_once 'auth_helper.php';

// Check if user has admin or higher permission
requirePermission('admin', 'login.php');

// Include database connection
require_once 'db.php';
";
        
        // Replace the beginning of the file
        $pattern = "/^<\?php\nsession_start\(\);.*?require_once 'db.php';/s";
        $file_contents = preg_replace($pattern, $auth_code, $file_contents);
        
        // Replace any hardcoded session overrides
        $file_contents = str_replace("// Temporarily set session for testing\n\$_SESSION['user_id'] = 1;\n\$_SESSION['is_admin'] = 1;", "// Using role-based authentication", $file_contents);
        
        // Remove old style authentication check
        $file_contents = str_replace("/*\n// Check if user is logged in and is admin\nif (!isset(\$_SESSION['user_id']) || !isset(\$_SESSION['is_admin']) || \$_SESSION['is_admin'] != 1) {\n    header(\"Location: login.php\");\n    exit;\n}\n*/", "", $file_contents);
        $file_contents = str_replace("// Check if user is logged in and is admin\nif (!isset(\$_SESSION['user_id']) || !isset(\$_SESSION['is_admin']) || \$_SESSION['is_admin'] != 1) {\n    header(\"Location: login.php\");\n    exit;\n}", "", $file_contents);
        
        if (file_put_contents($file_path, $file_contents)) {
            echo "<div style='color: green; padding: 10px; background: #e8f5e9; margin: 10px 0;'>Blog creation authentication updated successfully!</div>";
        } else {
            echo "<div style='color: red; padding: 10px; background: #ffebee; margin: 10px 0;'>Error updating blog creation authentication.</div>";
        }
    } else {
        echo "<div style='color: red; padding: 10px; background: #ffebee; margin: 10px 0;'>Could not read file: $file_path</div>";
    }
}

echo "<h2>Usage Instructions</h2>";
echo "<p>To implement role-based permissions in any page:</p>";
echo "<ol>";
echo "<li>Include the auth helper at the top of the file: <code>require_once 'auth_helper.php';</code></li>";
echo "<li>Use <code>requirePermission('admin')</code> to restrict access to a page</li>";
echo "<li>Use <code>if (hasPermission('admin')) { ... }</code> to conditionally show/hide functionality</li>";
echo "<li>Use <code>if (showForRole('super_admin')) { ... }</code> to show UI elements only for specific roles</li>";
echo "</ol>";

echo "<p><a href='check_db.php'>Back to Database Check</a></p>";
?> 