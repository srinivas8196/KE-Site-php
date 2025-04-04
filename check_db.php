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

echo "<h1>Database Check</h1>";

// List all tables
echo "<h2>Database Tables</h2>";
$result = $conn->query("SHOW TABLES");
echo "<ul>";
if ($result) {
    while ($row = $result->fetch_row()) {
        echo "<li>$row[0]</li>";
    }
} else {
    echo "<li>Error listing tables</li>";
}
echo "</ul>";

// Check for users table
echo "<h2>Users Table</h2>";
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result && $result->num_rows > 0) {
    echo "<p>Users table exists</p>";
    
    // List users
    $result = $conn->query("SELECT * FROM users");
    if ($result && $result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Is Admin</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['username'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['is_admin'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found in the users table</p>";
    }
} else {
    echo "<p>Users table does not exist</p>";
    
    echo "<h3>Create Users Table</h3>";
    echo "<p>The following code would create a users table:</p>";
    echo "<pre>
    CREATE TABLE users (
        id INT(11) NOT NULL AUTO_INCREMENT,
        username VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        is_admin TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    </pre>";
    
    echo "<form method='post' action='check_db.php'>";
    echo "<input type='hidden' name='create_users_table' value='1'>";
    echo "<button type='submit'>Create Users Table</button>";
    echo "</form>";
}

// Create the users table if requested
if (isset($_POST['create_users_table'])) {
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) NOT NULL AUTO_INCREMENT,
        username VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        is_admin TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Users table created successfully</p>";
        
        // Insert a default admin user
        $username = "admin";
        $email = "admin@example.com";
        $password = password_hash("admin123", PASSWORD_DEFAULT);
        $is_admin = 1;
        
        $sql = "INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $username, $email, $password, $is_admin);
        
        if ($stmt->execute()) {
            echo "<p>Default admin user created successfully. Username: admin, Password: admin123</p>";
        } else {
            echo "<p>Error creating default admin user: " . $stmt->error . "</p>";
        }
        
    } else {
        echo "<p>Error creating users table: " . $conn->error . "</p>";
    }
}

// Check admin_blog_create.php for session authentication
echo "<h2>Blog Creation Authentication Check</h2>";
$admin_blog_create_file = file_get_contents('admin_blog_create.php');
if ($admin_blog_create_file) {
    if (strpos($admin_blog_create_file, "// Temporarily set session for testing") !== false) {
        echo "<p>Warning: The blog creation page has a temporary session override, which allows anyone to create blogs. The line <code>\$_SESSION['user_id'] = 1; \$_SESSION['is_admin'] = 1;</code> should be removed in production.</p>";
        
        echo "<p>The proper authentication check (currently commented out) is:</p>";
        echo "<pre>
// Check if user is logged in and is admin
if (!isset(\$_SESSION['user_id']) || !isset(\$_SESSION['is_admin']) || \$_SESSION['is_admin'] != 1) {
    header(\"Location: login.php\");
    exit;
}
</pre>";
        
        echo "<form method='post' action='check_db.php'>";
        echo "<input type='hidden' name='fix_authentication' value='1'>";
        echo "<button type='submit'>Fix Authentication in admin_blog_create.php</button>";
        echo "</form>";
    } else {
        echo "<p>Authentication check is properly implemented in admin_blog_create.php</p>";
    }
} else {
    echo "<p>Could not read admin_blog_create.php</p>";
}

// Fix authentication if requested
if (isset($_POST['fix_authentication'])) {
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

echo "<p><a href='login.php'>Go to Login Page</a> | <a href='admin_blog_create.php'>Go to Blog Creation Page</a></p>"; 