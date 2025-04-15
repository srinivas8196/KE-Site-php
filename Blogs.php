<?php
// This file is the entry point for all /blogs/... URLs

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

// Check if this file is being accessed directly (not through URL rewriting)
// If accessed directly as /blogs.php, just include the Blogs.php file
if (strpos($script_name, 'blogs.php') !== false) {
    // Direct access to blogs.php - include the Blogs.php file
    include 'Blogs.php';
    exit;
}

// Extract the path after /blogs/
if (preg_match('/\/blogs\/([^?]*)/', $request_uri, $matches)) {
    $path = trim($matches[1], '/');
    
    // If there's a path after /blogs/
    if (!empty($path)) {
        // Check if it's a category, tag, or page request
        if (strpos($path, 'category/') === 0) {
            // Category: blogs/category/xyz
            $category = substr($path, 9); // Remove "category/"
            $_GET['category'] = $category;
        } elseif (strpos($path, 'tag/') === 0) {
            // Tag: blogs/tag/xyz
            $tag = substr($path, 4); // Remove "tag/"
            $_GET['tag'] = $tag;
        } elseif (strpos($path, 'page/') === 0) {
            // Pagination: blogs/page/2
            $page = substr($path, 5); // Remove "page/"
            $_GET['page'] = $page;
        } else {
            // Blog post: blogs/slug
            $_GET['slug'] = $path;
            include 'blog-details.php';
            exit;
        }
    }
}

// Helper function to get parameters from either URL path or query parameters
if (!function_exists('getParameter')) {
    function getParameter($name) {
        return isset($_GET[$name]) ? $_GET[$name] : null;
    }
}

// Get pagination parameters and filters
$current_page = max(1, (int)getParameter('page'));
$posts_per_page = 3;
$offset = ($current_page - 1) * $posts_per_page;

// Handle search and filters
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = getParameter('category');
$tag_filter = getParameter('tag');

// Debug information
error_log("Category Filter: " . $category_filter);
error_log("Tag Filter: " . $tag_filter);

// First, verify if the category exists and get its name
$category_name = '';
if (!empty($category_filter)) {
    $category_check = $conn->prepare("SELECT id, name FROM blog_categories WHERE slug = ?");
    $category_check->bind_param("s", $category_filter);
    $category_check->execute();
    $category_result = $category_check->get_result();
    
    if ($category_result->num_rows === 0) {
        // Category doesn't exist, show error
        include 'kheader.php';
        ?>
        <div class="breadcumb-wrapper" data-bg-src="<?php echo $base_url; ?>/assets/img/bg/breadcumb-bg.jpg">
            <div class="container">
                <div class="breadcumb-content">
                    <h1 class="breadcumb-title">Category Not Found</h1>
                    <ul class="breadcumb-menu">
                        <li><a href="<?php echo $base_url; ?>/index.php">Home</a></li>
                        <li><a href="<?php echo $base_url; ?>/blogs">Blog</a></li>
                        <li>Category Not Found</li>
                    </ul>
                </div>
            </div>
        </div>
        <section class="space-top space-extra-bottom">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center">
                        <h2>Category Not Found</h2>
                        <p>The category you are looking for does not exist.</p>
                        <a href="<?php echo $base_url; ?>/blogs" class="th-btn">Back to Blog</a>
                    </div>
                </div>
            </div>
        </section>
        <?php
        include 'kfooter.php';
        exit;
    } else {
        $category_row = $category_result->fetch_assoc();
        $category_name = $category_row['name'];
    }
    $category_check->close();
}

// Build optimized query
$query = "SELECT p.id, p.title, p.slug, p.excerpt, 
         p.featured_image, 
         p.published_at, c.name as category_name, c.slug as category_slug 
         FROM blog_posts p 
         LEFT JOIN blog_categories c ON p.category_id = c.id 
         WHERE p.status = 'published'";

$params = [];
$types = "";

// Apply filters
if (!empty($category_filter)) {
    $query .= " AND c.slug = ?";
    $params[] = $category_filter;
    $types .= "s";
    
    // Debug information
    error_log("Category Filter Applied: " . $category_filter);
    error_log("Query with Category: " . $query);
}

if (!empty($tag_filter)) {
    $query .= " AND EXISTS (SELECT 1 FROM blog_post_tags pt JOIN blog_tags t ON pt.tag_id = t.id WHERE pt.post_id = p.id AND t.slug = ?)";
    $params[] = $tag_filter;
    $types .= "s";
}

if (!empty($search_term)) {
    $search_pattern = '%' . $search_term . '%';
    $query .= " AND (p.title LIKE ? OR p.excerpt LIKE ?)";
    $params[] = $search_pattern;
    $params[] = $search_pattern;
    $types .= "ss";
}

// Add pagination
$query .= " ORDER BY p.published_at DESC LIMIT ? OFFSET ?";
$params[] = $posts_per_page;
$params[] = $offset;
$types .= "ii";

// Debug the final query
error_log("Final Query: " . $query);
error_log("Query Parameters: " . print_r($params, true));

// Free up memory before executing query
gc_collect_cycles();

try {
    // Execute main query
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Debug result count
    error_log("Number of posts found: " . $result->num_rows);

    // Get total count for pagination
    $count_query = str_replace("SELECT p.id, p.title, p.slug, p.excerpt, p.featured_image, p.published_at, c.name as category_name, c.slug as category_slug", "SELECT COUNT(*)", $query);
    $count_query = preg_replace('/LIMIT \? OFFSET \?$/', '', $count_query);
    
    $count_stmt = $conn->prepare($count_query);
    if (!empty($params)) {
        // Remove the last two parameters (LIMIT and OFFSET) for count query
        $count_params = array_slice($params, 0, -2);
        $count_types = substr($types, 0, -2);
        if (!empty($count_params)) {
            $count_stmt->bind_param($count_types, ...$count_params);
        }
    }
    $count_stmt->execute();
    $total_posts = $count_stmt->get_result()->fetch_row()[0];
    $count_stmt->close();

    // Calculate total pages
    $total_pages = ceil($total_posts / $posts_per_page);

    // Free up memory
    unset($count_stmt, $stmt, $params, $types);
    gc_collect_cycles();

} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    echo "An error occurred while fetching blog posts. Please try again later.";
    exit;
}

// Include header
include 'kheader.php';
?>

<div class="breadcumb-wrapper" data-bg-src="<?php echo $base_url; ?>/assets/img/bg/breadcumb-bg.jpg">
    <div class="container">
        <div class="breadcumb-content">
            <h1 class="breadcumb-title"><?php echo !empty($category_name) ? $category_name : 'Blog'; ?></h1>
            <ul class="breadcumb-menu">
                <li><a href="<?php echo $base_url; ?>/index.php">Home</a></li>
                <li><a href="<?php echo $base_url; ?>/blogs">Blog</a></li>
                <?php if (!empty($category_name)): ?>
                    <li><?php echo htmlspecialchars($category_name); ?></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<section class="th-blog-wrapper blog-inner-page space-top space-extra-bottom">
    <div class="container">
        <div class="row gx-5">
            <div class="col-xxl-8 col-lg-7">
                <div class="row gx-4">
                    <?php 
                    if ($result->num_rows > 0): 
                        // Process one post at a time
                        while ($post = $result->fetch_assoc()): 
                            // Debug image path
                            error_log("Original featured_image path: " . $post['featured_image']);
                            
                            // Properly handle image path
                            $image_path = '';
                            if (!empty($post['featured_image'])) {
                                if (file_exists($post['featured_image'])) {
                                    // Full file path exists - use it
                                    $image_path = $base_url . '/' . ltrim($post['featured_image'], '/');
                                } elseif (file_exists('uploads/blog/' . basename($post['featured_image']))) {
                                    // Try with just the filename
                                    $image_path = $base_url . '/uploads/blog/' . basename($post['featured_image']);
                                } else {
                                    // Use a default image if file doesn't exist
                                    $image_path = $base_url . '/assets/img/blog/blog_1_1.jpg';
                                }
                                error_log("Final image path: " . $image_path);
                            }
                    ?>
                            <div class="col-md-6">
                                <div class="th-blog blog-single">
                                    <?php if (!empty($image_path)): ?>
                                        <div class="blog-img">
                                            <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                        </div>
                                    <?php else: ?>
                                        <!-- Placeholder image if no featured image is available -->
                                        <div class="blog-img">
                                            <img src="<?php echo $base_url; ?>/assets/img/blog/blog_1_1.jpg" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                        </div>
                                    <?php endif; ?>
                                    <div class="blog-content">
                                        <div class="blog-meta">
                                            <a class="author" href="<?php echo $base_url; ?>/blogs">
                                                <i class="fa-light fa-user"></i>by Admin
                                            </a>
                                            <a href="<?php echo $base_url; ?>/blogs">
                                                <i class="fa-regular fa-calendar"></i>
                                                <?php echo date('d M, Y', strtotime($post['published_at'])); ?>
                                            </a>
                                            <?php if (!empty($post['category_name'])): ?>
                                                <a href="<?php echo $base_url; ?>/blog-category.php?category=<?php echo htmlspecialchars($post['category_slug']); ?>">
                                                    <i class="fa-regular fa-folder"></i><?php echo htmlspecialchars($post['category_name']); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <h2 class="blog-title">
                                            <a href="<?php echo $base_url; ?>/blogs/<?php echo htmlspecialchars($post['slug']); ?>">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                        </h2>
                                        <p class="blog-text"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                                        <a href="<?php echo $base_url; ?>/blogs/<?php echo htmlspecialchars($post['slug']); ?>" class="link-btn">Read More</a>
                                    </div>
                                </div>
                            </div>
                            <?php 
                            // Free up memory after each post
                            unset($image_path);
                            gc_collect_cycles();
                        endwhile; 
                        // Free the result set
                        $result->free();
                    else: 
                    ?>
                        <div class="col-12">
                            <p>No blog posts found in this category.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination-wrapper">
                        <ul class="pagination">
                            <?php if ($current_page > 1): ?>
                                <li><a href="<?php echo $base_url; ?>/blogs/page/<?php echo $current_page - 1; ?>"><i class="fas fa-chevron-left"></i></a></li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="<?php echo $i === $current_page ? 'active' : ''; ?>">
                                    <a href="<?php echo $base_url; ?>/blogs/page/<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <li><a href="<?php echo $base_url; ?>/blogs/page/<?php echo $current_page + 1; ?>"><i class="fas fa-chevron-right"></i></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-xxl-4 col-lg-5 mt-5 mt-lg-0">
                <?php include __DIR__ . '/includes/blog_sidebar.php'; ?>
            </div>
        </div>
        
        <div class="shape-mockup shape1 d-none d-xxl-block" data-bottom="5%" data-right="-8%">
            <img src="<?php echo $base_url; ?>/assets/img/shape/shape_1.png" alt="shape">
        </div>
        <div class="shape-mockup shape2 d-none d-xl-block" data-bottom="1%" data-right="-7%">
            <img src="<?php echo $base_url; ?>/assets/img/shape/shape_2.png" alt="shape">
        </div>
        <div class="shape-mockup shape3 d-none d-xxl-block" data-bottom="2%" data-right="0%">
            <img src="<?php echo $base_url; ?>/assets/img/shape/shape_3.png" alt="shape">
        </div>
    </div>
</section>

<?php include 'kfooter.php'; ?> 