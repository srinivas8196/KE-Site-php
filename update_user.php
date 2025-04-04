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

echo "<h1>Update User Admin Privileges</h1>";

// Check if the is_admin column exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $sql = "ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0";
    if ($conn->query($sql) === TRUE) {
        echo "<p>Added 'is_admin' column to users table</p>";
    } else {
        echo "<p>Error adding column: " . $conn->error . "</p>";
    }
}

// List all users
$result = $conn->query("SELECT * FROM users");
echo "<h2>Current Users</h2>";
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Is Admin</th><th>Action</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['username'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td>";
        echo "<td>" . ($row['is_admin'] ?? 'N/A') . "</td>";
        echo "<td>";
        echo "<form method='post' action='update_user.php'>";
        echo "<input type='hidden' name='user_id' value='" . $row['id'] . "'>";
        echo "<input type='hidden' name='action' value='make_admin'>";
        echo "<button type='submit'>Make Admin</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No users found</p>";
}

// Handle making a user admin
if (isset($_POST['action']) && $_POST['action'] == 'make_admin' && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    
    $sql = "UPDATE users SET is_admin = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        echo "<p>User ID $user_id has been granted admin privileges</p>";
        echo "<p><a href='update_user.php'>Refresh this page</a> to see the updated user list</p>";
    } else {
        echo "<p>Error updating user: " . $stmt->error . "</p>";
    }
}

// Fix blog authentication
echo "<h2>Fix Blog Authentication</h2>";
echo "<p>The blog creation page currently has a temporary session override. This should be removed for proper security.</p>";
echo "<form method='post' action='update_user.php'>";
echo "<input type='hidden' name='action' value='fix_auth'>";
echo "<button type='submit'>Fix Blog Authentication</button>";
echo "</form>";

// Handle fixing blog authentication
if (isset($_POST['action']) && $_POST['action'] == 'fix_auth') {
    $file_contents = file_get_contents('admin_blog_create.php');
    
    // Remove temporary session override
    $file_contents = str_replace(
        "// Temporarily set session for testing\n\$_SESSION['user_id'] = 1;\n\$_SESSION['is_admin'] = 1;",
        "// Session check follows below",
        $file_contents
    );
    
    // Uncomment authentication check
    $file_contents = str_replace(
        "/*\n// Check if user is logged in and is admin\nif (!isset(\$_SESSION['user_id']) || !isset(\$_SESSION['is_admin']) || \$_SESSION['is_admin'] != 1) {\n    header(\"Location: login.php\");\n    exit;\n}\n*/",
        "// Check if user is logged in and is admin\nif (!isset(\$_SESSION['user_id']) || !isset(\$_SESSION['is_admin']) || \$_SESSION['is_admin'] != 1) {\n    header(\"Location: login.php\");\n    exit;\n}",
        $file_contents
    );
    
    if (file_put_contents('admin_blog_create.php', $file_contents)) {
        echo "<p>Authentication in admin_blog_create.php has been fixed. Now only logged-in admin users can create blogs.</p>";
    } else {
        echo "<p>Error fixing authentication in admin_blog_create.php</p>";
    }
}

echo "<p><a href='login.php'>Go to Login Page</a> | <a href='admin_blog_create.php'>Go to Blog Creation Page</a> | <a href='check_db.php'>Check Database</a></p>";
?> 