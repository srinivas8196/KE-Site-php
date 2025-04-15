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

// Get category and tag from URL
$category_slug = isset($_GET['category']) ? $_GET['category'] : '';
$tag_slug = isset($_GET['tag']) ? $_GET['tag'] : '';

if (empty($category_slug) && empty($tag_slug)) {
    header("Location: $base_url/Blogs.php");
    exit;
}

$page_title = 'Blog';
$filter_type = '';
$filter_name = '';
$filter_id = 0;

// Handle category filter
if (!empty($category_slug)) {
    $filter_type = 'category';
    // Get category details
    $category_query = "SELECT id, name, slug FROM blog_categories WHERE slug = ?";
    $category_stmt = $conn->prepare($category_query);
    $category_stmt->bind_param("s", $category_slug);
    $category_stmt->execute();
    $category_result = $category_stmt->get_result();

    if ($category_result->num_rows === 0) {
        // Category not found
        header("Location: $base_url/Blogs.php");
        exit;
    }

    $category = $category_result->fetch_assoc();
    $filter_name = $category['name'];
    $filter_id = $category['id'];
    $page_title = $filter_name;
}

// Handle tag filter
if (!empty($tag_slug)) {
    $filter_type = 'tag';
    // Get tag details
    $tag_query = "SELECT id, name, slug FROM blog_tags WHERE slug = ?";
    $tag_stmt = $conn->prepare($tag_query);
    $tag_stmt->bind_param("s", $tag_slug);
    $tag_stmt->execute();
    $tag_result = $tag_stmt->get_result();

    if ($tag_result->num_rows === 0) {
        // Tag not found
        header("Location: $base_url/Blogs.php");
        exit;
    }

    $tag = $tag_result->fetch_assoc();
    $filter_name = $tag['name'];
    $filter_id = $tag['id'];
    $page_title = "Posts tagged with: $filter_name";
}

// Get pagination parameters
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$posts_per_page = 6;
$offset = ($current_page - 1) * $posts_per_page;

// Base query parts
$count_query_base = "SELECT COUNT(*) FROM blog_posts p WHERE p.status = 'published'";
$posts_query_base = "SELECT p.id, p.title, p.slug, p.excerpt, p.featured_image, p.published_at, 
                     c.name as category_name, c.slug as category_slug 
                     FROM blog_posts p 
                     LEFT JOIN blog_categories c ON p.category_id = c.id 
                     WHERE p.status = 'published'";

// Add filter conditions based on filter type
if ($filter_type === 'category') {
    $count_query = $count_query_base . " AND p.category_id = ?";
    $posts_query = $posts_query_base . " AND p.category_id = ?";
} elseif ($filter_type === 'tag') {
    $count_query = $count_query_base . " AND p.id IN (SELECT post_id FROM blog_post_tags WHERE tag_id = ?)";
    $posts_query = $posts_query_base . " AND p.id IN (SELECT post_id FROM blog_post_tags WHERE tag_id = ?)";
}

// Add ordering and pagination
$posts_query .= " ORDER BY p.published_at DESC LIMIT ? OFFSET ?";

// Execute count query
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("i", $filter_id);
$count_stmt->execute();
$total_posts = $count_stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_posts / $posts_per_page);
$count_stmt->close();

// Execute posts query
$posts_stmt = $conn->prepare($posts_query);
$posts_stmt->bind_param("iii", $filter_id, $posts_per_page, $offset);
$posts_stmt->execute();
$result = $posts_stmt->get_result();

// Include header
include 'kheader.php';
?>

<div class="breadcumb-wrapper" data-bg-src="<?php echo $base_url; ?>/assets/img/bg/breadcumb-bg.jpg">
    <div class="container">
        <div class="breadcumb-content">
            <h1 class="breadcumb-title"><?php echo htmlspecialchars($page_title); ?></h1>
            <ul class="breadcumb-menu">
                <li><a href="<?php echo $base_url; ?>/index.php">Home</a></li>
                <li><a href="<?php echo $base_url; ?>/Blogs.php">Blog</a></li>
                <li><?php echo htmlspecialchars($filter_name); ?></li>
            </ul>
        </div>
    </div>
</div>

<section class="th-blog-wrapper blog-inner-page space-top space-extra-bottom">
    <div class="container">
        <div class="row gx-5">
            <div class="col-xxl-8 col-lg-7">
                <div class="row gx-4">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($post = $result->fetch_assoc()): 
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
                            }
                        ?>
                            <div class="col-md-6">
                                <div class="th-blog blog-single">
                                    <?php if (!empty($image_path)): ?>
                                        <div class="blog-img">
                                            <img src="<?php echo $image_path; ?>" 
                                                 alt="<?php echo htmlspecialchars($post['title']); ?>">
                                        </div>
                                    <?php else: ?>
                                        <!-- Placeholder image if no featured image is available -->
                                        <div class="blog-img">
                                            <img src="<?php echo $base_url; ?>/assets/img/blog/blog_1_1.jpg" 
                                                 alt="<?php echo htmlspecialchars($post['title']); ?>">
                                        </div>
                                    <?php endif; ?>
                                    <div class="blog-content">
                                        <div class="blog-meta">
                                            <a class="author" href="<?php echo $base_url; ?>/Blogs.php">
                                                <i class="fa-light fa-user"></i>by Admin
                                            </a>
                                            <a href="<?php echo $base_url; ?>/Blogs.php">
                                                <i class="fa-regular fa-calendar"></i>
                                                <?php echo date('d M, Y', strtotime($post['published_at'])); ?>
                                            </a>
                                            <a href="<?php echo $base_url; ?>/blog-category.php?category=<?php echo htmlspecialchars($post['category_slug']); ?>">
                                                <i class="fa-regular fa-folder"></i><?php echo htmlspecialchars($post['category_name']); ?>
                                            </a>
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
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <p>No blog posts found in this <?php echo $filter_type; ?>.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination-wrapper">
                        <ul class="pagination">
                            <?php if ($current_page > 1): ?>
                                <li>
                                    <a href="<?php echo $base_url; ?>/blog-category.php?<?php echo $filter_type; ?>=<?php echo $filter_type === 'category' ? $category_slug : $tag_slug; ?>&page=<?php echo $current_page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="<?php echo $i === $current_page ? 'active' : ''; ?>">
                                    <a href="<?php echo $base_url; ?>/blog-category.php?<?php echo $filter_type; ?>=<?php echo $filter_type === 'category' ? $category_slug : $tag_slug; ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <li>
                                    <a href="<?php echo $base_url; ?>/blog-category.php?<?php echo $filter_type; ?>=<?php echo $filter_type === 'category' ? $category_slug : $tag_slug; ?>&page=<?php echo $current_page + 1; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
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
    </div>
</section>

<?php include 'kfooter.php'; ?> 