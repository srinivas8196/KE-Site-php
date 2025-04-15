<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Increase memory limit
ini_set('memory_limit', '512M');

// Include database connection
require_once 'db.php';
require_once 'includes/functions.php';

// Define base URL
$base_url = '/KE-Site-php';

// Check database connection
checkDatabaseConnection();

// Get the request URI
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Extract the path after /blogs/
if (preg_match('/\/blogs\/([^?]*)/', $request_uri, $matches)) {
    $path = trim($matches[1], '/');
    
    // If there's a path after /blogs/
    if (!empty($path)) {
        // Check if it's a category, tag, or page request
        if (strpos($path, 'category/') === 0) {
            // Category: blogs/category/xyz
            $category = substr($path, 9); // Remove "category/"
            $_GET['category'] = urldecode($category); // Decode URL-encoded characters
            include 'Blogs.php';
            exit;
        } elseif (strpos($path, 'tag/') === 0) {
            // Tag: blogs/tag/xyz
            $tag = substr($path, 4); // Remove "tag/"
            $_GET['tag'] = urldecode($tag);
            include 'Blogs.php';
            exit;
        } elseif (strpos($path, 'page/') === 0) {
            // Pagination: blogs/page/2
            $page = substr($path, 5); // Remove "page/"
            $_GET['page'] = $page;
            include 'Blogs.php';
            exit;
        } else {
            // Blog post: blogs/slug
            $_GET['slug'] = $path;
            include 'blog-details.php';
            exit;
        }
    }
}

// Default: show all blogs
include 'Blogs.php';
?> 