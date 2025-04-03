<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/recaptcha-config.php';

// Define base URL
$base_url = '/KE-Site-php';

// Check database connection
checkDatabaseConnection();

// Get the slug
$slug = '';
// Check for rewritten URL from .htaccess
$request_uri = $_SERVER['REQUEST_URI'];
if (preg_match('/^\/blogs\/([^\/\?]+)/', $request_uri, $matches) || preg_match('/^\/KE-Site-php\/blogs\/([^\/\?]+)/', $request_uri, $matches)) {
    $slug = $matches[1];
} elseif (isset($_GET['slug'])) {
    // Fallback for direct query parameter
    $slug = $_GET['slug'];
}

// Validate slug is provided
if (empty($slug)) {
    header("Location: $base_url/Blogs.php");
    exit;
}

// Fetch the blog post with category info
$post_query = "SELECT p.*, c.name as category_name, c.slug as category_slug 
               FROM blog_posts p 
               LEFT JOIN blog_categories c ON p.category_id = c.id 
               WHERE p.slug = ? AND p.status = 'published'";
$stmt = $conn->prepare($post_query);
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();

// Check if post exists
if ($result->num_rows === 0) {
    header("Location: $base_url/Blogs.php");
    exit;
}

$post = $result->fetch_assoc();
$stmt->close();

// Fetch tags for this post
$tags_query = "SELECT t.* 
               FROM blog_tags t 
               JOIN blog_post_tags pt ON t.id = pt.tag_id 
               WHERE pt.post_id = ?";
$stmt = $conn->prepare($tags_query);
$stmt->bind_param("i", $post['id']);
$stmt->execute();
$tags_result = $stmt->get_result();
$stmt->close();

// Fetch all comments for this post
$comments_query = "SELECT * FROM blog_comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at ASC";
$stmt = $conn->prepare($comments_query);
$stmt->bind_param("i", $post['id']);
$stmt->execute();
$comments_result = $stmt->get_result();
$stmt->close();

// Track view count
$update_views = "UPDATE blog_posts SET views = views + 1 WHERE id = ?";
$stmt = $conn->prepare($update_views);
$stmt->bind_param("i", $post['id']);
$stmt->execute();
$stmt->close();

// Handle comment submission
$comment_error = '';
$comment_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $post_id = $post['id'];
    $recaptcha_token = $_POST['recaptcha_token'] ?? '';
    
    // Simple validation
    if (empty($name) || empty($email) || empty($content)) {
        $comment_error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $comment_error = "Please enter a valid email address";
    } else {
        // Verify reCAPTCHA first
        $recaptcha_result = verifyRecaptchaV3($recaptcha_token, 'comment');
        
        if (!$recaptcha_result['success']) {
            $comment_error = "Security verification failed. Please try again.";
            error_log('reCAPTCHA verification failed for comment: ' . ($recaptcha_result['error'] ?? 'unknown error'));
        } else {
            // Set status based on auto-approval setting (for now, require approval)
            $status = 'pending'; // Or 'approved' if you want auto-approval
            
            $insert_comment = "INSERT INTO blog_comments (post_id, name, email, content, status, created_at) 
                               VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($insert_comment);
            $stmt->bind_param("issss", $post_id, $name, $email, $content, $status);
            
            if ($stmt->execute()) {
                $comment_success = "Thank you for your comment! It will be visible after approval.";
                // Clear form data after successful submission
                unset($_POST['name'], $_POST['email'], $_POST['content']);
            } else {
                $comment_error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch related posts based on category
$related_query = "SELECT p.id, p.title, p.slug, p.featured_image, p.published_at 
                  FROM blog_posts p 
                  WHERE p.category_id = ? AND p.id != ? AND p.status = 'published' 
                  ORDER BY p.published_at DESC 
                  LIMIT 3";
$stmt = $conn->prepare($related_query);
$stmt->bind_param("ii", $post['category_id'], $post['id']);
$stmt->execute();
$related_posts = $stmt->get_result();
$stmt->close();

// Page title
$page_title = htmlspecialchars($post['title']) . " | Karma Experience";

// Include header
include 'kheader.php';
?>

<!-- Add reCAPTCHA v3 script -->
<script src="https://www.google.com/recaptcha/api.js?render=<?php echo RECAPTCHA_V3_SITE_KEY; ?>"></script>

<div class="breadcumb-wrapper" data-bg-src="<?php echo $base_url; ?>/assets/img/bg/breadcumb-bg.jpg">
    <div class="container">
        <div class="breadcumb-content">
            <h1 class="breadcumb-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            <ul class="breadcumb-menu">
                <li><a href="<?php echo $base_url; ?>/index.php">Home</a></li>
                <li><a href="<?php echo $base_url; ?>/blogs">Blog</a></li>
                <?php if (!empty($post['category_name'])): ?>
                    <li><a href="<?php echo $base_url; ?>/blogs/category/<?php echo htmlspecialchars($post['category_slug']); ?>"><?php echo htmlspecialchars($post['category_name']); ?></a></li>
                <?php endif; ?>
                <li><?php echo htmlspecialchars($post['title']); ?></li>
            </ul>
        </div>
    </div>
</div>

<section class="th-blog-wrapper blog-details space-top space-extra-bottom">
    <div class="container">
        <div class="row gx-5">
            <div class="col-xxl-8 col-lg-7">
                <div class="th-blog blog-single">
                    <?php if (!empty($post['featured_image'])): ?>
                        <div class="blog-img">
                            <?php
                            $image_path = $post['featured_image'];
                            // Check if the image path starts with http:// or https:// (external image)
                            if (!preg_match('/^https?:\/\//', $image_path)) {
                                // If it's a local image, ensure it has the correct base URL
                                $image_path = $base_url . '/' . ltrim($image_path, '/');
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="img-fluid w-100">
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
                                <a href="<?php echo $base_url; ?>/blogs/category/<?php echo htmlspecialchars($post['category_slug']); ?>">
                                    <img src="<?php echo $base_url; ?>/assets/img/icon/map.svg" alt=""><?php echo htmlspecialchars($post['category_name']); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <h2 class="blog-title"><?php echo htmlspecialchars($post['title']); ?></h2>
                        
                        <!-- Post Content -->
                        <div class="post-content mb-5">
                            <?php echo $post['content']; ?>
                        </div>
                        
                        <!-- Tags -->
                        <?php if ($tags_result->num_rows > 0): ?>
                            <div class="blog-tags mb-5">
                                <span class="tag-title"><i class="fas fa-tags"></i> Tags:</span>
                                <?php while ($tag = $tags_result->fetch_assoc()): ?>
                                    <a href="<?php echo $base_url; ?>/blogs/tag/<?php echo htmlspecialchars($tag['slug']); ?>" class="tag-link"><?php echo htmlspecialchars($tag['name']); ?></a>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Comments Section -->
                        <div class="blog-comments">
                            <h3 class="blog-inner-title mb-4"><?php echo $comments_result->num_rows; ?> Comments</h3>
                            
                            <?php if ($comments_result->num_rows > 0): ?>
                                <div class="comment-list">
                                    <?php while ($comment = $comments_result->fetch_assoc()): ?>
                                        <div class="comment-item" id="comment-<?php echo $comment['id']; ?>">
                                            <div class="comment-author">
                                                <div class="author-img">
                                                    <img src="assets/img/blog/comment-author.jpg" alt="<?php echo htmlspecialchars($comment['name']); ?>">
                                                </div>
                                                <div class="comment-meta">
                                                    <h4 class="name"><?php echo htmlspecialchars($comment['name']); ?></h4>
                                                    <span class="date"><?php echo date('M d, Y \a\t h:i a', strtotime($comment['created_at'])); ?></span>
                                                </div>
                                            </div>
                                            <p class="comment-text"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                            <div class="reply-btn">
                                                <a href="#comment-form" class="reply-button" data-comment-id="<?php echo $comment['id']; ?>">
                                                    <i class="fa-solid fa-reply"></i> Reply
                                                </a>
                                            </div>
                                            
                                            <!-- Comment Replies -->
                                            <?php if (isset($replies_array[$comment['id']])): ?>
                                                <div class="comment-replies">
                                                    <?php foreach ($replies_array[$comment['id']] as $reply): ?>
                                                        <div class="comment-item reply-item" id="comment-<?php echo $reply['id']; ?>">
                                                            <div class="comment-author">
                                                                <div class="author-img">
                                                                    <img src="assets/img/blog/comment-author.jpg" alt="<?php echo htmlspecialchars($reply['name']); ?>">
                                                                </div>
                                                                <div class="comment-meta">
                                                                    <h4 class="name"><?php echo htmlspecialchars($reply['name']); ?></h4>
                                                                    <span class="date"><?php echo date('M d, Y \a\t h:i a', strtotime($reply['created_at'])); ?></span>
                                                                </div>
                                                            </div>
                                                            <p class="comment-text"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="no-comments">No comments yet. Be the first to comment!</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Comment Form -->
                        <div id="comment-form" class="comment-form-wrap mt-5 pt-4">
                            <h3 class="blog-inner-title mb-4">Leave a Comment</h3>
                            
                            <?php if ($comment_success): ?>
                                <div class="alert alert-success mb-4">
                                    <?php echo htmlspecialchars($comment_success); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($comment_error)): ?>
                                <div class="alert alert-danger mb-4">
                                    <?php echo htmlspecialchars($comment_error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form action="#comment-form" method="POST" class="comment-form" id="commentForm">
                                <input type="hidden" name="parent_id" id="parent_id" value="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-4">
                                            <input type="text" class="form-control" name="name" id="name" placeholder="Your Name*" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-4">
                                            <input type="email" class="form-control" name="email" id="email" placeholder="Your Email*" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group mb-4">
                                            <input type="url" class="form-control" name="website" id="website" placeholder="Website (optional)">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group mb-4">
                                            <textarea name="comment" id="comment" class="form-control" placeholder="Your Comment*" required rows="6"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" name="comment_submit" class="th-btn">Post Comment</button>
                                    </div>
                                </div>
                                <div id="reply-info" class="mt-3" style="display: none;">
                                    Replying to: <span id="reply-to-name"></span>
                                    <a href="#" id="cancel-reply" class="ms-2">(Cancel)</a>
                                </div>
                                
                                <!-- Hidden reCAPTCHA token field -->
                                <input type="hidden" name="recaptcha_token" id="recaptchaToken">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-xxl-4 col-lg-5 mt-5 mt-lg-0">
                <?php include __DIR__ . '/includes/blog_sidebar.php'; ?>
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

<script>
    // Handle reply to comment functionality
    document.addEventListener('DOMContentLoaded', function() {
        const replyBtns = document.querySelectorAll('.reply-button');
        const parentIdField = document.getElementById('parent_id');
        const commentForm = document.getElementById('comment-form');
        
        replyBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const parentId = this.getAttribute('data-comment-id');
                parentIdField.value = parentId;
                
                // Scroll to comment form
                commentForm.scrollIntoView({behavior: 'smooth'});
                
                // Focus on the first input field
                setTimeout(() => {
                    const firstInput = commentForm.querySelector('input[name="name"]');
                    if (firstInput) firstInput.focus();
                }, 500);
            });
        });
    });

document.getElementById('commentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    grecaptcha.ready(function() {
        grecaptcha.execute('<?php echo RECAPTCHA_V3_SITE_KEY; ?>', {action: 'comment'})
            .then(function(token) {
                document.getElementById('recaptchaToken').value = token;
                document.getElementById('commentForm').submit();
            });
    });
});
</script>

<?php include 'kfooter.php'; ?> 