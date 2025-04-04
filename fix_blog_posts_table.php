<?php
// Include the database connection
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

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fixing Blog Posts Table Structure</h1>";

// Step 1: Backup existing blog posts data
echo "<p>Backing up existing blog posts data...</p>";
$backup_data = [];
$result = $conn->query("SELECT * FROM blog_posts");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $backup_data[] = $row;
    }
    echo "<p>Backed up " . count($backup_data) . " blog posts.</p>";
} else {
    echo "<p>No existing blog posts found or error retrieving data.</p>";
}

// Step 2: Drop the existing foreign key constraints
echo "<p>Attempting to drop foreign keys...</p>";

// Check if foreign key blog_posts_ibfk_2 exists
$result = $conn->query("SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                        WHERE CONSTRAINT_SCHEMA = '$mysqli_db' 
                        AND TABLE_NAME = 'blog_posts' 
                        AND CONSTRAINT_NAME = 'blog_posts_ibfk_2'");

if ($result && $result->num_rows > 0) {
    // Foreign key exists, drop it
    $conn->query("ALTER TABLE blog_posts DROP FOREIGN KEY blog_posts_ibfk_2");
    echo "<p>Foreign key constraint blog_posts_ibfk_2 dropped successfully.</p>";
} else {
    echo "<p>Foreign key constraint blog_posts_ibfk_2 does not exist.</p>";
}

// Step 3: Recreate the blog_posts table without the author_id foreign key constraint
echo "<p>Dropping and recreating blog_posts table...</p>";

// Drop dependent tables first (to avoid foreign key issues)
$conn->query("DROP TABLE IF EXISTS blog_comments");
$conn->query("DROP TABLE IF EXISTS blog_post_tags");

// Now drop the blog_posts table
$conn->query("DROP TABLE IF EXISTS blog_posts");

// Recreate blog_posts table
$sql_posts = "CREATE TABLE IF NOT EXISTS blog_posts (
    id INT(11) NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(255),
    meta_title VARCHAR(255),
    meta_description TEXT,
    category_id INT(11),
    author_id INT(11) DEFAULT 1,
    status ENUM('draft', 'published') DEFAULT 'draft',
    views INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (slug),
    FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql_posts) === TRUE) {
    echo "<p>Blog posts table recreated successfully.</p>";
} else {
    echo "<p>Error recreating blog posts table: " . $conn->error . "</p>";
}

// Recreate blog_post_tags table
$sql_post_tags = "CREATE TABLE IF NOT EXISTS blog_post_tags (
    post_id INT(11) NOT NULL,
    tag_id INT(11) NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES blog_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql_post_tags) === TRUE) {
    echo "<p>Blog post-tag relationship table recreated successfully.</p>";
} else {
    echo "<p>Error recreating blog post-tag relationship table: " . $conn->error . "</p>";
}

// Recreate blog_comments table
$sql_comments = "CREATE TABLE IF NOT EXISTS blog_comments (
    id INT(11) NOT NULL AUTO_INCREMENT,
    post_id INT(11) NOT NULL,
    parent_id INT(11) DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    website VARCHAR(255),
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'spam') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES blog_comments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql_comments) === TRUE) {
    echo "<p>Blog comments table recreated successfully.</p>";
} else {
    echo "<p>Error recreating blog comments table: " . $conn->error . "</p>";
}

// Step 4: Restore any backed up data
if (!empty($backup_data)) {
    echo "<p>Restoring " . count($backup_data) . " blog posts...</p>";
    
    foreach ($backup_data as $post) {
        // Simplified insert query without timestamps (will use defaults)
        $restore_query = "INSERT INTO blog_posts (id, title, slug, content, excerpt, featured_image, meta_title, meta_description, category_id, author_id, status, views) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($restore_query);
        $stmt->bind_param(
            "isssssssiiis", 
            $post['id'], 
            $post['title'], 
            $post['slug'], 
            $post['content'], 
            $post['excerpt'], 
            $post['featured_image'], 
            $post['meta_title'], 
            $post['meta_description'], 
            $post['category_id'], 
            $post['author_id'], 
            $post['status'], 
            $post['views']
        );
        if ($stmt->execute()) {
            echo "<p>Restored post: " . htmlspecialchars($post['title']) . "</p>";
        } else {
            echo "<p>Error restoring post: " . htmlspecialchars($post['title']) . " - " . $stmt->error . "</p>";
        }
    }
}

echo "<h2>Table Structure Fixed</h2>";
echo "<p>You can now <a href='admin_blog_create.php'>create new blog posts</a> without foreign key constraint issues.</p>"; 