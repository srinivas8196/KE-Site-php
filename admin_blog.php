<?php
session_start();

// Temporarily set session for testing
$_SESSION['user_id'] = 1;
$_SESSION['is_admin'] = 1;

/*
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}
*/

// Include database connection
require_once 'db.php';

// Ensure we have a connection
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection error. Please check your configuration.");
}

// Handle post deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $post_id = $_GET['delete'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete from blog_post_tags
        $stmt = $conn->prepare("DELETE FROM blog_post_tags WHERE post_id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        
        // Delete from blog_comments
        $stmt = $conn->prepare("DELETE FROM blog_comments WHERE post_id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        
        // Delete the post
        $stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        
        // Commit the transaction
        $conn->commit();
        
        $success_message = "Post deleted successfully.";
    } catch (Exception $e) {
        // Something went wrong, rollback
        $conn->rollback();
        $error_message = "Error deleting post: " . $e->getMessage();
    }
    
    // Redirect to avoid resubmission on refresh
    header("Location: admin_blog.php" . (isset($success_message) ? "?success=".urlencode($success_message) : (isset($error_message) ? "?error=".urlencode($error_message) : "")));
    exit;
}

// Handle status change
if (isset($_GET['publish']) && is_numeric($_GET['publish'])) {
    $post_id = $_GET['publish'];
    $stmt = $conn->prepare("UPDATE blog_posts SET status = 'published', published_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    
    if ($stmt->execute()) {
        $success_message = "Post published successfully.";
    } else {
        $error_message = "Error publishing post: " . $conn->error;
    }
    
    header("Location: admin_blog.php" . (isset($success_message) ? "?success=".urlencode($success_message) : (isset($error_message) ? "?error=".urlencode($error_message) : "")));
    exit;
}

if (isset($_GET['unpublish']) && is_numeric($_GET['unpublish'])) {
    $post_id = $_GET['unpublish'];
    $stmt = $conn->prepare("UPDATE blog_posts SET status = 'draft' WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    
    if ($stmt->execute()) {
        $success_message = "Post moved to draft successfully.";
    } else {
        $error_message = "Error updating post status: " . $conn->error;
    }
    
    header("Location: admin_blog.php" . (isset($success_message) ? "?success=".urlencode($success_message) : (isset($error_message) ? "?error=".urlencode($error_message) : "")));
    exit;
}

// Get success/error messages from URL
$success_message = isset($_GET['success']) ? $_GET['success'] : null;
$error_message = isset($_GET['error']) ? $_GET['error'] : null;

// Query to get all blog posts with their category
$query = "SELECT bp.*, bc.name as category_name 
          FROM blog_posts bp 
          LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
          ORDER BY bp.created_at DESC";
$result = $conn->query($query);

// Count posts by status
$query_published = "SELECT COUNT(*) as count FROM blog_posts WHERE status = 'published'";
$result_published = $conn->query($query_published);
$published_count = ($result_published) ? $result_published->fetch_assoc()['count'] : 0;

$query_draft = "SELECT COUNT(*) as count FROM blog_posts WHERE status = 'draft'";
$result_draft = $conn->query($query_draft);
$draft_count = ($result_draft) ? $result_draft->fetch_assoc()['count'] : 0;

$total_posts = $published_count + $draft_count;

// Include header
include 'bheader.php';
?>

<!-- Include our new CSS file -->
<link rel="stylesheet" href="assets/css/admin-blog.css">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Blog Management</h1>
        <a href="admin_blog_create.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Create New Post
        </a>
    </div>
    
    <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="dashboard-card bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Total Posts</h5>
                    <h2><?php echo $total_posts; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-card bg-success">
                <div class="card-body">
                    <h5 class="card-title">Published</h5>
                    <h2><?php echo $published_count; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-card bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Drafts</h5>
                    <h2><?php echo $draft_count; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-card bg-info">
                <div class="card-body">
                    <h5 class="card-title">Categories</h5>
                    <a href="admin_blog_categories.php" class="btn btn-light btn-sm mt-2">Manage Categories</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="content-section blog-table">
        <div class="content-section__header">
            <h2>All Blog Posts</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover" id="blogPostsTable">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Published</th>
                            <th>Views</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($post = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="admin_blog_edit.php?id=<?php echo $post['id']; ?>" class="blog-title">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($post['category_name'])): ?>
                                            <span class="category-badge"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                        <?php else: ?>
                                            <span class="category-badge">Uncategorized</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($post['status'] == 'published'): ?>
                                            <span class="badge bg-success">Published</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $post['published_at'] ? date("M j, Y", strtotime($post['published_at'])) : 'Not published'; ?></td>
                                    <td><?php echo $post['views']; ?></td>
                                    <td><?php echo date("M j, Y", strtotime($post['created_at'])); ?></td>
                                    <td>
                                        <div class="blog-actions">
                                            <a href="admin_blog_edit.php?id=<?php echo $post['id']; ?>" class="action-button edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($post['status'] == 'draft'): ?>
                                                <a href="admin_blog.php?publish=<?php echo $post['id']; ?>" 
                                                   class="action-button" title="Publish"
                                                   onclick="return confirm('Are you sure you want to publish this post?')">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="admin_blog.php?unpublish=<?php echo $post['id']; ?>" 
                                                   class="action-button" title="Unpublish"
                                                   onclick="return confirm('Are you sure you want to unpublish this post?')">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="admin_blog.php?delete=<?php echo $post['id']; ?>" 
                                               class="action-button delete" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <a href="blogs/<?php echo $post['slug']; ?>" 
                                               class="action-button view" title="View" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="py-5">
                                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No blog posts found</h5>
                                        <a href="admin_blog_create.php" class="btn btn-primary btn-sm mt-3">Create your first post</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize DataTables with modern styling
    $(document).ready(function() {
        $('#blogPostsTable').DataTable({
            order: [[5, 'desc']], // Sort by created date by default
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            responsive: true,
            language: {
                search: "",
                searchPlaceholder: "Search posts...",
                lengthMenu: "_MENU_ posts per page",
                info: "Showing _START_ to _END_ of _TOTAL_ posts",
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    previous: '<i class="fas fa-angle-left"></i>'
                }
            },
            dom: '<"top d-flex justify-content-between align-items-center mb-3"lf>rt<"bottom d-flex justify-content-between align-items-center"ip><"clear">',
            initComplete: function() {
                // Style the search input
                $('.dataTables_filter input').addClass('form-control');
                $('.dataTables_filter input').css({
                    'width': '250px',
                    'margin-left': '0',
                    'margin-bottom': '0'
                });
                $('.dataTables_filter label').contents().filter(function() {
                    return this.nodeType === 3;
                }).remove();
                
                // Style the length select
                $('.dataTables_length select').addClass('form-select');
                $('.dataTables_length select').css({
                    'width': 'auto',
                    'margin-left': '0.5rem',
                    'margin-right': '0.5rem'
                });
            }
        });
    });
</script>

<?php include 'bfooter.php'; ?> 