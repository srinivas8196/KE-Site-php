<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Blog Tables Setup</h1>";

// Create database connection
$conn = new mysqli('localhost', 'root', '', 'resortdb');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Database connection successful.<br><br>";

// Array to store status messages
$messages = [];

// Function to execute SQL and log the result
function executeSql($conn, $sql, $tableName) {
    global $messages;
    
    if ($conn->query($sql) === TRUE) {
        $messages[] = "✅ Table <strong>{$tableName}</strong> created or already exists.";
        return true;
    } else {
        $messages[] = "❌ Error creating table <strong>{$tableName}</strong>: " . $conn->error;
        return false;
    }
}

// Check if users table exists - this is referenced by blog_posts
$usersTableExists = false;
$usersResult = $conn->query("SHOW TABLES LIKE 'users'");
if ($usersResult->num_rows > 0) {
    $usersTableExists = true;
    $messages[] = "✅ Table <strong>users</strong> exists - will create blog_posts with user foreign key constraint.";
} else {
    $messages[] = "⚠️ Table <strong>users</strong> does not exist - will create blog_posts without user foreign key constraint.";
}

// SQL to create blog_categories table
$sql_categories = "CREATE TABLE IF NOT EXISTS `blog_categories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// SQL to create blog_tags table
$sql_tags = "CREATE TABLE IF NOT EXISTS `blog_tags` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// SQL to create blog_posts table (with or without user constraint based on users table existence)
if ($usersTableExists) {
    $sql_posts = "CREATE TABLE IF NOT EXISTS `blog_posts` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `slug` VARCHAR(255) NOT NULL,
        `content` LONGTEXT NOT NULL,
        `excerpt` TEXT,
        `featured_image` VARCHAR(255) DEFAULT NULL,
        `status` ENUM('published', 'draft') DEFAULT 'draft',
        `category_id` INT(11) DEFAULT NULL,
        `views` INT(11) DEFAULT 0,
        `meta_title` VARCHAR(255) DEFAULT NULL,
        `meta_description` TEXT DEFAULT NULL,
        `author_id` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `published_at` TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`),
        KEY `category_id` (`category_id`),
        KEY `author_id` (`author_id`),
        CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL,
        CONSTRAINT `blog_posts_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
} else {
    // Version without the user foreign key constraint
    $sql_posts = "CREATE TABLE IF NOT EXISTS `blog_posts` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `slug` VARCHAR(255) NOT NULL,
        `content` LONGTEXT NOT NULL,
        `excerpt` TEXT,
        `featured_image` VARCHAR(255) DEFAULT NULL,
        `status` ENUM('published', 'draft') DEFAULT 'draft',
        `category_id` INT(11) DEFAULT NULL,
        `views` INT(11) DEFAULT 0,
        `meta_title` VARCHAR(255) DEFAULT NULL,
        `meta_description` TEXT DEFAULT NULL,
        `author_id` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `published_at` TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`),
        KEY `category_id` (`category_id`),
        CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
}

// SQL to create blog_post_tags table (junction table for many-to-many relationship)
$sql_post_tags = "CREATE TABLE IF NOT EXISTS `blog_post_tags` (
    `post_id` INT(11) NOT NULL,
    `tag_id` INT(11) NOT NULL,
    PRIMARY KEY (`post_id`, `tag_id`),
    KEY `tag_id` (`tag_id`),
    CONSTRAINT `blog_post_tags_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `blog_post_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `blog_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// SQL to create blog_comments table
$sql_comments = "CREATE TABLE IF NOT EXISTS `blog_comments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `post_id` INT(11) NOT NULL,
    `parent_id` INT(11) DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `website` VARCHAR(255) DEFAULT NULL,
    `content` TEXT NOT NULL,
    `status` ENUM('pending', 'approved', 'spam') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `post_id` (`post_id`),
    KEY `parent_id` (`parent_id`),
    CONSTRAINT `blog_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `blog_comments_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `blog_comments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Create all tables
executeSql($conn, $sql_categories, "blog_categories");
executeSql($conn, $sql_tags, "blog_tags");
executeSql($conn, $sql_posts, "blog_posts");
executeSql($conn, $sql_post_tags, "blog_post_tags");
executeSql($conn, $sql_comments, "blog_comments");

// Display creation messages
echo "<div style='margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-radius: 5px;'>";
echo "<h3>Table Creation Results:</h3>";
echo "<ul>";
foreach ($messages as $message) {
    echo "<li>{$message}</li>";
}
echo "</ul>";
echo "</div>";

// Check if any tables were created and insert default category
$result = $conn->query("SELECT * FROM blog_categories LIMIT 1");
if ($result->num_rows == 0) {
    // Insert default category if none exists
    $insertCategory = "INSERT INTO blog_categories (name, slug, description) VALUES ('Uncategorized', 'uncategorized', 'Default category for blog posts')";
    if ($conn->query($insertCategory) === TRUE) {
        echo "<p>✅ Created default 'Uncategorized' category.</p>";
    } else {
        echo "<p>❌ Error creating default category: " . $conn->error . "</p>";
    }
}

// Add sample blog post if none exists
$result = $conn->query("SELECT * FROM blog_posts LIMIT 1");
if ($result->num_rows == 0) {
    // Get the ID of the Uncategorized category
    $catResult = $conn->query("SELECT id FROM blog_categories WHERE slug='uncategorized' LIMIT 1");
    $categoryId = ($catResult && $catResult->num_rows > 0) ? $catResult->fetch_assoc()['id'] : NULL;
    
    // Insert a sample blog post
    $samplePost = "INSERT INTO blog_posts (title, slug, content, excerpt, status, category_id, views, meta_title, meta_description, published_at) 
                  VALUES ('Welcome to Karma Experience Blog', 'welcome-to-karma-experience-blog', 
                  '<p>Welcome to our blog! This is a sample post to get you started.</p><p>Here you will find information about our luxury resorts and experiences.</p>', 
                  'Welcome to our blog! This is a sample post to get you started.', 
                  'published', ?, 5, 'Welcome to Our Blog', 'Learn more about luxury experiences and resorts', NOW())";
    
    $stmt = $conn->prepare($samplePost);
    $stmt->bind_param("i", $categoryId);
    
    if ($stmt->execute()) {
        echo "<p>✅ Created sample blog post.</p>";
        
        // Get the post ID
        $postId = $conn->insert_id;
        
        // Create a sample tag
        $conn->query("INSERT INTO blog_tags (name, slug) VALUES ('Welcome', 'welcome')");
        $tagId = $conn->insert_id;
        
        // Link tag to post
        $conn->query("INSERT INTO blog_post_tags (post_id, tag_id) VALUES ($postId, $tagId)");
        
        // Add a sample comment
        $conn->query("INSERT INTO blog_comments (post_id, name, email, content, status) 
                     VALUES ($postId, 'Admin', 'admin@example.com', 'Welcome to our blog! Feel free to leave comments.', 'approved')");
    } else {
        echo "<p>❌ Error creating sample blog post: " . $stmt->error . "</p>";
    }
}

// Close connection
$conn->close();

echo "<p>Tables setup complete. <a href='test_db.php'>View database status</a></p>";
?> 