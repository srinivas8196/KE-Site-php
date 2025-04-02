<?php
require_once 'db.php';

// Get the current page from the URL
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$posts_per_page = 6; // Number of posts to display per page
$offset = ($current_page - 1) * $posts_per_page;

// Get the category filter if set
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$category_query = "";
$category_name = "";

if (!empty($category_filter)) {
    $category_query = " AND c.slug = ?";
    
    // Get category name for display
    $cat_stmt = $conn->prepare("SELECT name FROM blog_categories WHERE slug = ?");
    $cat_stmt->bind_param("s", $category_filter);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    if ($cat_result->num_rows > 0) {
        $category_row = $cat_result->fetch_assoc();
        $category_name = $category_row['name'];
    }
}

// Count total posts for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM blog_posts p 
                LEFT JOIN blog_categories c ON p.category_id = c.id 
                WHERE p.status = 'published'" . $category_query;

if (!empty($category_filter)) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("s", $category_filter);
    $count_stmt->execute();
} else {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->execute();
}

$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_posts = $count_row['total'];
$total_pages = ceil($total_posts / $posts_per_page);

// Get the blog posts
$query = "SELECT p.*, c.name as category_name, c.slug as category_slug 
          FROM blog_posts p 
          LEFT JOIN blog_categories c ON p.category_id = c.id 
          WHERE p.status = 'published'" . $category_query . " 
          ORDER BY p.published_at DESC 
          LIMIT ? OFFSET ?";

if (!empty($category_filter)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $category_filter, $posts_per_page, $offset);
    $stmt->execute();
} else {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $posts_per_page, $offset);
    $stmt->execute();
}

$result = $stmt->get_result();

// Get all categories for the sidebar
$categories_query = "SELECT c.*, COUNT(p.id) as post_count 
                     FROM blog_categories c 
                     LEFT JOIN blog_posts p ON c.id = p.category_id AND p.status = 'published' 
                     GROUP BY c.id 
                     ORDER BY c.name";
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

// Page title and meta description
$page_title = !empty($category_name) ? "Blog - " . $category_name : "Blog";
$meta_description = !empty($category_name) 
                    ? "Read our latest articles about " . $category_name 
                    : "Read our latest travel blog articles, tips, and guides";

include 'kheader.php';
?>

<div class="breadcumb-wrapper" data-bg-src="assets/img/bg/breadcumb-bg.jpg">
    <div class="container">
        <div class="breadcumb-content">
            <h1 class="breadcumb-title">
                <?php echo !empty($category_name) ? "Category: " . $category_name : "Our Blog"; ?>
            </h1>
            <ul class="breadcumb-menu">
                <li><a href="index.php">Home</a></li>
                <li><?php echo !empty($category_name) ? $category_name : "Blog"; ?></li>
            </ul>
        </div>
    </div>
</div>

<section class="th-blog-wrapper blog-details space-top space-extra-bottom">
    <div class="container">
        <div class="row">
            <div class="col-xxl-8 col-lg-7">
                <div class="row">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="col-md-6 mb-4">
                                <div class="th-blog blog-single">
                                    <div class="blog-img">
                                        <?php if (!empty($row['featured_image'])): ?>
                                            <a href="blogs/<?php echo htmlspecialchars($row['slug']); ?>">
                                                <img src="<?php echo htmlspecialchars($row['featured_image']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                                            </a>
                                        <?php else: ?>
                                            <img src="assets/img/blog/blog-s-1-1.jpg" alt="Blog Image">
                                        <?php endif; ?>
                                    </div>
                                    <div class="blog-content">
                                        <div class="blog-meta">
                                            <a class="author" href="Blogs.php">
                                                <i class="fa-light fa-user"></i>by Admin
                                            </a>
                                            <a href="Blogs.php">
                                                <i class="fa-regular fa-calendar"></i>
                                                <?php echo date('d M, Y', strtotime($row['published_at'])); ?>
                                            </a>
                                            <?php if (!empty($row['category_name'])): ?>
                                                <a href="Blogs.php?category=<?php echo htmlspecialchars($row['category_slug']); ?>">
                                                    <img src="assets/img/icon/map.svg" alt=""><?php echo htmlspecialchars($row['category_name']); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <h2 class="blog-title">
                                            <a href="blogs/<?php echo htmlspecialchars($row['slug']); ?>">
                                                <?php echo htmlspecialchars($row['title']); ?>
                                            </a>
                                        </h2>
                                        <p class="blog-text mb-25">
                                            <?php 
                                            if (!empty($row['excerpt'])) {
                                                echo htmlspecialchars($row['excerpt']);
                                            } else {
                                                echo mb_substr(strip_tags($row['content']), 0, 150) . '...'; 
                                            }
                                            ?>
                                        </p>
                                        <a href="blogs/<?php echo htmlspecialchars($row['slug']); ?>" class="link-btn">
                                            Read More <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="no-posts-found text-center">
                                <h3>No blog posts found</h3>
                                <p>Please check back later for new content</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="th-pagination text-center">
                        <ul>
                            <?php if ($current_page > 1): ?>
                                <li>
                                    <a href="Blogs.php?page=<?php echo $current_page - 1; ?><?php echo !empty($category_filter) ? '&category=' . $category_filter : ''; ?>">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="<?php echo $i == $current_page ? 'active' : ''; ?>">
                                    <a href="Blogs.php?page=<?php echo $i; ?><?php echo !empty($category_filter) ? '&category=' . $category_filter : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <li>
                                    <a href="Blogs.php?page=<?php echo $current_page + 1; ?><?php echo !empty($category_filter) ? '&category=' . $category_filter : ''; ?>">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-xxl-4 col-lg-5">
                <aside class="sidebar-area">
                    <div class="widget widget_search">
                        <form class="search-form" action="Blogs.php" method="GET">
                            <input type="text" name="search" placeholder="Search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button type="submit"><i class="far fa-search"></i></button>
                        </form>
                    </div>
                    
                    <div class="widget widget_categories">
                        <h3 class="widget_title">Categories</h3>
                        <ul>
                            <?php if ($categories_result->num_rows > 0): ?>
                                <?php while ($category = $categories_result->fetch_assoc()): ?>
                                    <li>
                                        <a href="Blogs.php?category=<?php echo htmlspecialchars($category['slug']); ?>">
                                            <img src="assets/img/theme-img/map.svg" alt=""><?php echo htmlspecialchars($category['name']); ?>
                                        </a>
                                        <span>(<?php echo $category['post_count']; ?>)</span>
                                    </li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li><a href="#">No categories found</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <div class="widget">
                        <h3 class="widget_title">Recent Posts</h3>
                        <div class="recent-post-wrap">
                            <?php if ($recent_posts_result->num_rows > 0): ?>
                                <?php while ($recent = $recent_posts_result->fetch_assoc()): ?>
                                    <div class="recent-post">
                                        <div class="media-img">
                                            <a href="blogs/<?php echo htmlspecialchars($recent['slug']); ?>">
                                                <?php if (!empty($recent['featured_image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($recent['featured_image']); ?>" alt="<?php echo htmlspecialchars($recent['title']); ?>">
                                                <?php else: ?>
                                                    <img src="assets/img/blog/recent-post-1-1.jpg" alt="Blog Image">
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                        <div class="media-body">
                                            <h4 class="post-title">
                                                <a class="text-inherit" href="blogs/<?php echo htmlspecialchars($recent['slug']); ?>">
                                                    <?php echo htmlspecialchars($recent['title']); ?>
                                                </a>
                                            </h4>
                                            <div class="recent-post-meta">
                                                <a href="Blogs.php">
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
                            <?php if ($tags_result->num_rows > 0): ?>
                                <?php while ($tag = $tags_result->fetch_assoc()): ?>
                                    <a href="Blogs.php?tag=<?php echo htmlspecialchars($tag['slug']); ?>">
                                        <?php echo htmlspecialchars($tag['name']); ?>
                                    </a>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <a href="#">No tags found</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="widget widget_offer" data-bg-src="assets/img/bg/widget_bg_1.jpg">
                        <div class="offer-banner">
                            <div class="offer">
                                <h6 class="box-title">Need Help? We Are Here To Help You</h6>
                                <div class="banner-logo"><img src="assets/img/logo2.svg" alt="Logo"></div>
                                <div class="offer">
                                    <h6 class="offer-title">You Get Online support</h6>
                                    <a class="offter-num" href="tel:+256214203215">+256 214 203 215</a>
                                </div>
                                <a href="contact.php" class="th-btn style2 th-icon">Read More</a>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
        
        <div class="shape-mockup shape1 d-none d-xxl-block" data-bottom="5%" data-right="-8%">
            <img src="assets/img/shape/shape_1.png" alt="shape">
        </div>
        <div class="shape-mockup shape2 d-none d-xl-block" data-bottom="1%" data-right="-7%">
            <img src="assets/img/shape/shape_2.png" alt="shape">
        </div>
        <div class="shape-mockup shape3 d-none d-xxl-block" data-bottom="2%" data-right="0%">
            <img src="assets/img/shape/shape_3.png" alt="shape">
        </div>
    </div>
</section>

<?php include 'kfooter.php'; ?> 