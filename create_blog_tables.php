<?php
// Include the database connection
require_once 'db.php';

// In db.php, the PDO object is returned, so we need to capture it
$conn = require 'db.php';

// Create database connection using mysqli for compatibility with existing code
$mysqli_host = $_ENV['DB_HOST'] ?? 'localhost';
$mysqli_user = $_ENV['DB_USER'] ?? 'root';
$mysqli_pass = $_ENV['DB_PASS'] ?? '';
$mysqli_db = $_ENV['DB_NAME'] ?? 'resortdb';

// Create mysqli connection
$conn = new mysqli($mysqli_host, $mysqli_user, $mysqli_pass, $mysqli_db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create blog categories table
$sql_categories = "CREATE TABLE IF NOT EXISTS blog_categories (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql_categories) === TRUE) {
    echo "Blog categories table created successfully<br>";
} else {
    echo "Error creating blog categories table: " . $conn->error . "<br>";
}

// Create blog posts table
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
    author_id INT(11),
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
    echo "Blog posts table created successfully<br>";
} else {
    echo "Error creating blog posts table: " . $conn->error . "<br>";
}

// Create blog tags table
$sql_tags = "CREATE TABLE IF NOT EXISTS blog_tags (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql_tags) === TRUE) {
    echo "Blog tags table created successfully<br>";
} else {
    echo "Error creating blog tags table: " . $conn->error . "<br>";
}

// Create post-tag relationship table
$sql_post_tags = "CREATE TABLE IF NOT EXISTS blog_post_tags (
    post_id INT(11) NOT NULL,
    tag_id INT(11) NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES blog_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql_post_tags) === TRUE) {
    echo "Blog post-tag relationship table created successfully<br>";
} else {
    echo "Error creating blog post-tag relationship table: " . $conn->error . "<br>";
}

// Create blog comments table
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
    echo "Blog comments table created successfully<br>";
} else {
    echo "Error creating blog comments table: " . $conn->error . "<br>";
}

// Insert default categories
$default_categories = [
    ['name' => 'Travel', 'slug' => 'travel', 'description' => 'Travel related blog posts'],
    ['name' => 'Adventure', 'slug' => 'adventure', 'description' => 'Adventure related blog posts'],
    ['name' => 'Beach Tours', 'slug' => 'beach-tours', 'description' => 'Beach tour related blog posts'],
    ['name' => 'Wildlife Tours', 'slug' => 'wildlife-tours', 'description' => 'Wildlife tour related blog posts'],
    ['name' => 'City Tour', 'slug' => 'city-tour', 'description' => 'City tour related blog posts'],
    ['name' => 'Mountain Tours', 'slug' => 'mountain-tours', 'description' => 'Mountain tour related blog posts']
];

foreach ($default_categories as $category) {
    $stmt = $conn->prepare("INSERT IGNORE INTO blog_categories (name, slug, description) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $category['name'], $category['slug'], $category['description']);
    $stmt->execute();
}

echo "Default categories added<br>";

// Insert default tags
$default_tags = [
    ['name' => 'Tour', 'slug' => 'tour'],
    ['name' => 'Adventure', 'slug' => 'adventure'],
    ['name' => 'Hotel', 'slug' => 'hotel'],
    ['name' => 'Modern', 'slug' => 'modern'],
    ['name' => 'Luxury', 'slug' => 'luxury'],
    ['name' => 'Travel', 'slug' => 'travel']
];

foreach ($default_tags as $tag) {
    $stmt = $conn->prepare("INSERT IGNORE INTO blog_tags (name, slug) VALUES (?, ?)");
    $stmt->bind_param("ss", $tag['name'], $tag['slug']);
    $stmt->execute();
}

echo "Default tags added<br>";

echo "Blog system tables have been set up successfully."; 