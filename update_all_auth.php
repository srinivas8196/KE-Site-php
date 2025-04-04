<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure auth_helper.php exists
if (!file_exists('auth_helper.php')) {
    die("<p>Error: auth_helper.php is missing. Please run role_management.php first and click 'Create Authentication Helper'.</p>");
}

echo "<h1>Update Role-Based Authentication Across All Pages</h1>";

// Define pages and their required permission levels
$pages_to_update = [
    // Admin pages - require admin or higher
    'admin_blog.php' => 'admin',
    'admin_blog_edit.php' => 'admin',
    'admin_blog_create.php' => 'admin',
    'admin_blog_categories.php' => 'admin',
    'admin_category.php' => 'admin',
    'resort_list.php' => 'admin',
    'create_or_edit_resort.php' => 'admin',
    'dashboard.php' => 'admin',
    
    // Campaign/blog management - require campaign_manager or higher
    'Blogs.php' => 'campaign_manager',
    
    // User management - require super_admin
    'create_user.php' => 'super_admin',
    'edit_user.php' => 'super_admin',
];

$updated_count = 0;
$failed_count = 0;

echo "<h2>Authentication Update Progress</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>File</th><th>Required Role</th><th>Status</th></tr>";

foreach ($pages_to_update as $file => $required_role) {
    if (!file_exists($file)) {
        echo "<tr><td>$file</td><td>$required_role</td><td style='color:orange'>File not found, skipping</td></tr>";
        continue;
    }
    
    $file_contents = file_get_contents($file);
    
    if (!$file_contents) {
        echo "<tr><td>$file</td><td>$required_role</td><td style='color:red'>Could not read file</td></tr>";
        $failed_count++;
        continue;
    }
    
    // Check if authentication is already updated
    if (strpos($file_contents, "require_once 'auth_helper.php'") !== false) {
        echo "<tr><td>$file</td><td>$required_role</td><td style='color:blue'>Already updated</td></tr>";
        continue;
    }
    
    // Create auth code based on required role
    $auth_code = "<?php
session_start();

// Include auth helper
require_once 'auth_helper.php';

// Check if user has {$required_role} or higher permission
requirePermission('{$required_role}', 'login.php');

";
    
    // Replace the beginning of the file
    // First, handle files that start with <?php\nsession_start();
    if (strpos($file_contents, "<?php\nsession_start();") === 0) {
        // Get everything after session_start();
        $start_pos = strpos($file_contents, "session_start();") + strlen("session_start();");
        $file_contents = $auth_code . substr($file_contents, $start_pos);
    } 
    // Handle files that just start with <?php
    else if (strpos($file_contents, "<?php") === 0) {
        $start_pos = strlen("<?php");
        $file_contents = $auth_code . substr($file_contents, $start_pos);
    }
    // For any other files, just prepend
    else {
        $file_contents = $auth_code . $file_contents;
    }
    
    // Remove any old style authentication if present
    $file_contents = str_replace(
        "// Check if user is logged in and is admin\nif (!isset(\$_SESSION['user_id']) || !isset(\$_SESSION['is_admin']) || \$_SESSION['is_admin'] != 1) {\n    header(\"Location: login.php\");\n    exit;\n}",
        "// Using role-based authentication", 
        $file_contents
    );
    
    // Remove temporary session override if present
    $file_contents = str_replace(
        "// Temporarily set session for testing\n\$_SESSION['user_id'] = 1;\n\$_SESSION['is_admin'] = 1;",
        "// Using role-based authentication",
        $file_contents
    );
    
    if (file_put_contents($file, $file_contents)) {
        echo "<tr><td>$file</td><td>$required_role</td><td style='color:green'>Updated successfully</td></tr>";
        $updated_count++;
    } else {
        echo "<tr><td>$file</td><td>$required_role</td><td style='color:red'>Failed to update</td></tr>";
        $failed_count++;
    }
}

echo "</table>";

echo "<h2>Summary</h2>";
echo "<p>Updated: $updated_count files</p>";
echo "<p>Failed: $failed_count files</p>";

// Now let's update the login.php file to set user_type in session
if (file_exists('login.php')) {
    echo "<h2>Updating Login Process</h2>";
    
    $login_file = file_get_contents('login.php');
    
    if ($login_file) {
        // Find the successful login section
        if (strpos($login_file, "if (password_verify") !== false) {
            // Already using password_verify - modern approach
            // Look for the session setting code after successful authentication
            $pattern = "/if \(password_verify[^{]*{(.*?)}/s";
            if (preg_match($pattern, $login_file, $matches)) {
                $login_success_code = $matches[1];
                
                // Check if session user_type is already being set
                if (strpos($login_success_code, '$_SESSION["user_type"]') === false) {
                    // Add user_type to session variables
                    $new_login_success_code = str_replace(
                        '$_SESSION["user_id"] = $user["id"];',
                        '$_SESSION["user_id"] = $user["id"];
            $_SESSION["user_type"] = $user["user_type"];',
                        $login_success_code
                    );
                    
                    if ($new_login_success_code != $login_success_code) {
                        $login_file = str_replace($login_success_code, $new_login_success_code, $login_file);
                        
                        if (file_put_contents('login.php', $login_file)) {
                            echo "<p style='color:green'>Successfully updated login.php to store user_type in session</p>";
                        } else {
                            echo "<p style='color:red'>Failed to update login.php</p>";
                        }
                    } else {
                        echo "<p style='color:orange'>Could not locate the exact code to update in login.php</p>";
                    }
                } else {
                    echo "<p style='color:blue'>login.php already sets user_type in session</p>";
                }
            } else {
                echo "<p style='color:orange'>Could not find login success code pattern in login.php</p>";
            }
        } else {
            echo "<p style='color:orange'>login.php may be using an older authentication method without password_verify</p>";
            echo "<p>Please manually ensure that your login process sets \$_SESSION[\"user_type\"] after successful login.</p>";
        }
    } else {
        echo "<p style='color:red'>Could not read login.php</p>";
    }
}

// Now let's add the navigation menu based on role
echo "<h2>Create Role-Based Navigation Menu</h2>";

$navigation_code = '<?php
function renderNavigation() {
    $html = \'<nav class="admin-nav">
        <ul>\';
    
    // All authenticated users can see these
    $html .= \'<li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>\';
    
    // Only admin and super_admin can manage resorts
    if (hasPermission("admin")) {
        $html .= \'<li><a href="resort_list.php"><i class="fas fa-hotel"></i> Manage Resorts</a></li>\';
    }
    
    // Campaign manager, admin and super_admin can manage blogs
    if (hasPermission("campaign_manager")) {
        $html .= \'<li><a href="admin_blog.php"><i class="fas fa-blog"></i> Manage Blogs</a></li>\';
    }
    
    // Only super_admin can manage users
    if (hasPermission("super_admin")) {
        $html .= \'<li><a href="users.php"><i class="fas fa-users"></i> Manage Users</a></li>\';
    }
    
    $html .= \'<li><a href="view_enquiries.php"><i class="fas fa-envelope"></i> Enquiries</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>\';
    
    return $html;
}

// Usage example:
// <?php echo renderNavigation(); ?>
';

if (file_put_contents('navigation.php', $navigation_code)) {
    echo "<p style='color:green'>Created navigation.php with role-based menu</p>";
    echo "<p>To use the navigation, include navigation.php in your files and call renderNavigation():</p>";
    echo "<pre>
&lt;?php
require_once 'navigation.php';
echo renderNavigation();
?&gt;
</pre>";
} else {
    echo "<p style='color:red'>Failed to create navigation.php</p>";
}

echo "<p><a href='role_management.php'>Back to Role Management</a></p>";
?> 