    <?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing connection to database...<br>";

// Create database connection
$conn = new mysqli('localhost', 'root', '', 'resortdb');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connection successful.<br>";

// Check if blog related tables exist
$blog_tables = [
    'blog_posts',
    'blog_categories',
    'blog_tags',
    'blog_post_tags',
    'blog_comments'
];

echo "<h3>Checking for blog tables:</h3>";
foreach ($blog_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "- ✅ Table <strong>$table</strong> exists<br>";
    } else {
        echo "- ❌ Table <strong>$table</strong> does not exist<br>";
    }
}

echo "<h3>All tables in database:</h3>";
$result = $conn->query("SHOW TABLES");
if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_row()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "Error showing tables: " . $conn->error;
}

// Close connection
$conn->close();
?> 