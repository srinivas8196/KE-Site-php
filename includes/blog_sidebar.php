<?php
// If $conn is not already defined, create a database connection
if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/../db.php';
    
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
}

// Define base URL if not already defined
if (!isset($base_url)) {
    $base_url = '/KE-Site-php';
}

// Get categories with post counts
$categories_query = "SELECT c.id, c.name, c.slug, COUNT(p.id) as post_count 
                    FROM blog_categories c 
                    LEFT JOIN blog_posts p ON c.id = p.category_id AND p.status = 'published' 
                    GROUP BY c.id, c.name, c.slug 
                    ORDER BY c.name ASC";
$categories_result = $conn->query($categories_query);

// Get recent posts for the sidebar
$recent_posts_query = "SELECT p.id, p.title, p.slug, p.featured_image, p.published_at 
                       FROM blog_posts p 
                       WHERE p.status = 'published' 
                       ORDER BY p.published_at DESC 
                       LIMIT 3";
$recent_posts_result = $conn->query($recent_posts_query);

// Get popular tags for the sidebar
$tags_query = "SELECT t.*, COUNT(pt.post_id) as post_count 
               FROM blog_tags t 
               JOIN blog_post_tags pt ON t.id = pt.tag_id 
               JOIN blog_posts p ON pt.post_id = p.id AND p.status = 'published' 
               GROUP BY t.id 
               ORDER BY post_count DESC 
               LIMIT 8";
$tags_result = $conn->query($tags_query);
?>

<aside class="sidebar-area">
    <div class="widget widget_search">
        <form class="search-form" action="<?php echo $base_url; ?>/blogs" method="GET">
            <input type="text" name="search" placeholder="Search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit"><i class="far fa-search"></i></button>
        </form>
    </div>
    
    <div class="widget sidebar-widget">
        <h3 class="widget-title">Categories</h3>
        <div class="th-widget-category">
            <ul>
                <?php while ($category = $categories_result->fetch_assoc()): ?>
                    <li>
                        <a href="<?php echo $base_url; ?>/blog-category.php?category=<?php echo htmlspecialchars($category['slug']); ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                            <span class="count">(<?php echo $category['post_count']; ?>)</span>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
    
    <div class="widget">
        <h3 class="widget_title">Recent Posts</h3>
        <div class="recent-post-wrap">
            <?php if ($recent_posts_result && $recent_posts_result->num_rows > 0): ?>
                <?php while ($recent = $recent_posts_result->fetch_assoc()): ?>
                    <div class="recent-post">
                        <div class="media-img">
                            <a href="<?php echo $base_url; ?>/blogs/<?php echo htmlspecialchars($recent['slug']); ?>">
                                <?php if (!empty($recent['featured_image'])): ?>
                                    <img src="<?php echo $base_url . '/' . ltrim($recent['featured_image'], '/'); ?>" alt="<?php echo htmlspecialchars($recent['title']); ?>">
                                <?php else: ?>
                                    <img src="<?php echo $base_url; ?>/assets/img/blog/recent-post-1-1.jpg" alt="Blog Image">
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="media-body">
                            <h4 class="post-title">
                                <a class="text-inherit" href="<?php echo $base_url; ?>/blogs/<?php echo htmlspecialchars($recent['slug']); ?>">
                                    <?php echo htmlspecialchars($recent['title']); ?>
                                </a>
                            </h4>
                            <div class="recent-post-meta">
                                <a href="<?php echo $base_url; ?>/blogs">
                                    <i class="fa-regular fa-calendar"></i>
                                    <?php echo date('d/m/Y', strtotime($recent['published_at'])); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No recent posts found</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="widget widget_tag_cloud">
        <h3 class="widget_title">Popular Tags</h3>
        <div class="tagcloud">
            <?php if ($tags_result && $tags_result->num_rows > 0): ?>
                <?php while ($tag = $tags_result->fetch_assoc()): ?>
                    <a href="<?php echo $base_url; ?>/blog-category.php?tag=<?php echo htmlspecialchars($tag['slug']); ?>">
                        <?php echo htmlspecialchars($tag['name']); ?>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <a href="#">No tags found</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="widget widget_offer" data-bg-src="<?php echo $base_url; ?>/assets/img/bg/widget_bg_1.jpg">
        <div class="offer-banner">
            <div class="offer">
                <h6 class="box-title">Need Help? We Are Here To Help You</h6>
                <div class="banner-logo"><img src="<?php echo $base_url; ?>/assets/images/logo/KE-Gold.png" alt="Karma Experience" style="height: 40px;"></div>
                <div class="offer">
                    <h6 class="offer-title">Contact our support team</h6>
                    <a class="offter-num" href="tel:+919898989898">+91 989 898 9898</a>
                </div>
                <a href="<?php echo $base_url; ?>/enquire-now.php" class="th-btn style2 th-icon">Enquire Now</a>
            </div>
        </div>
    </div>
</aside> 