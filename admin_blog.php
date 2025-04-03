<?php
session_start();

// Temporarily set session for testing
$_SESSION['user_id'] = 1;
$_SESSION['is_admin'] = 1;


// //Check if user is logged in and is admin
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
//     header("Location: login.php");
//     exit;
// }


// Include database connection
require_once 'db.php';

// Ensure we have a connection
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection error. Please check your configuration.");
}

// Handle post actions (delete, publish, unpublish)
if (isset($_GET['action']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $post_id = $_GET['id'];
    $action = $_GET['action'];
    
    switch ($action) {
        case 'delete':
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
                
                header("Location: admin_blog.php?success=delete");
                exit;
            } catch (Exception $e) {
                // Something went wrong, rollback
                $conn->rollback();
                header("Location: admin_blog.php?error=delete");
                exit;
            }
            break;
            
        case 'publish':
            $stmt = $conn->prepare("UPDATE blog_posts SET status = 'published', published_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("i", $post_id);
            
            if ($stmt->execute()) {
                header("Location: admin_blog.php?success=publish");
            } else {
                header("Location: admin_blog.php?error=publish");
            }
            exit;
            break;
            
        case 'unpublish':
            $stmt = $conn->prepare("UPDATE blog_posts SET status = 'draft' WHERE id = ?");
            $stmt->bind_param("i", $post_id);
            
            if ($stmt->execute()) {
                header("Location: admin_blog.php?success=unpublish");
            } else {
                header("Location: admin_blog.php?error=unpublish");
            }
            exit;
            break;
    }
}

// Count posts by status
$query_published = "SELECT COUNT(*) as count FROM blog_posts WHERE status = 'published'";
$result_published = $conn->query($query_published);
$published_count = ($result_published) ? $result_published->fetch_assoc()['count'] : 0;

$query_draft = "SELECT COUNT(*) as count FROM blog_posts WHERE status = 'draft'";
$result_draft = $conn->query($query_draft);
$draft_count = ($result_draft) ? $result_draft->fetch_assoc()['count'] : 0;

$total_posts = $published_count + $draft_count;

// Count categories
$query_categories = "SELECT COUNT(*) as count FROM blog_categories";
$result_categories = $conn->query($query_categories);
$category_count = ($result_categories) ? $result_categories->fetch_assoc()['count'] : 0;

// Query to get all blog posts with their category
$query = "SELECT bp.*, bc.name as category_name 
          FROM blog_posts bp 
          LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
          ORDER BY bp.created_at DESC";
$result = $conn->query($query);

// Include header
$page_title = "Blog Management";
include 'bheader.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin-blog.css">

<style>
/* Modern layout with sidebar */
.admin-wrapper {
    display: flex;
    min-height: calc(100vh - 70px);
}

.admin-sidebar {
    width: 280px;
    background: #2a3950;
    color: white;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    z-index: 100;
    position: fixed;
    top: 70px;
    left: 0;
    bottom: 0;
    overflow-y: auto;
}

.admin-content {
    flex: 1;
    padding: 2rem;
    margin-left: 280px;
    transition: all 0.3s ease;
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-brand {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    text-decoration: none;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-menu li {
    margin-bottom: 0.25rem;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    padding: 0.875rem 1.5rem;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 0.95rem;
}

.sidebar-menu a:hover,
.sidebar-menu a.active {
    background: rgba(180, 151, 90, 0.15);
    color: #b4975a;
}

.sidebar-menu a i {
    margin-right: 0.75rem;
    font-size: 1.1rem;
    width: 1.5rem;
    text-align: center;
}

.sidebar-divider {
    height: 1px;
    background: rgba(255,255,255,0.1);
    margin: 1rem 0;
}

/* Dashboard cards */
.dashboard-card {
    background: white;
    border-radius: 10px;
    padding: 1.75rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    position: relative;
    transition: all 0.3s ease;
    border-left: 5px solid transparent;
    height: 100%;
}

.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
}

.dashboard-card-primary {
    border-left-color: #3b82f6;
}

.dashboard-card-success {
    border-left-color: #10b981;
}

.dashboard-card-warning {
    border-left-color: #f59e0b;
}

.dashboard-card-info {
    border-left-color: #06b6d4;
}

.dashboard-card .card-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.dashboard-card .card-value {
    font-size: 2.25rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #1f2937;
}

.dashboard-card .card-icon {
    font-size: 2rem;
    opacity: 0.2;
    position: absolute;
    right: 1.5rem;
    top: 1.5rem;
    color: #1f2937;
}

.dashboard-card .card-link {
    display: inline-block;
    margin-top: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #3b82f6;
    text-decoration: none;
}

.dashboard-card .card-link:hover {
    text-decoration: underline;
}

/* Table styling */
.blog-table {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.blog-table .dataTables_filter input {
    border-radius: 8px;
    border: 1px solid #d1d5db;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.blog-table .dataTables_filter input:focus {
    border-color: #b4975a;
    box-shadow: 0 0 0 3px rgba(180, 151, 90, 0.1);
    outline: none;
}

.blog-table .dataTables_length select {
    border-radius: 8px;
    border: 1px solid #d1d5db;
    padding: 0.5rem;
    font-size: 0.875rem;
}

.blog-table .dataTables_info {
    font-size: 0.875rem;
    color: #6b7280;
}

.blog-table .pagination .page-link {
    color: #b4975a;
    border-color: #d1d5db;
}

.blog-table .pagination .page-item.active .page-link {
    background-color: #b4975a;
    border-color: #b4975a;
    color: white;
}

.blog-table table thead th {
    background-color: #f8fafc;
    font-weight: 600;
    color: #4b5563;
    border-bottom: 1px solid #edf2f7;
    padding: 1rem;
}

.blog-table table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #edf2f7;
}

.blog-table table tbody tr:hover {
    background-color: #f9fafb;
}

/* Status badges */
.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 30px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-badge-published {
    background-color: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.status-badge-draft {
    background-color: rgba(107, 114, 128, 0.1);
    color: #6b7280;
}

/* Action buttons */
.action-button {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    color: #6b7280;
    background-color: transparent;
    border: none;
    transition: all 0.2s ease;
    margin-right: 0.25rem;
}

.action-button:hover {
    background-color: #f3f4f6;
    color: #b4975a;
}

.action-button-view:hover {
    color: #3b82f6;
}

.action-button-edit:hover {
    color: #10b981;
}

.action-button-publish:hover {
    color: #10b981;
}

.action-button-unpublish:hover {
    color: #f59e0b;
}

.action-button-delete:hover {
    color: #ef4444;
}

/* Status filter buttons */
.status-filter {
    display: flex;
    background: #f3f4f6;
    border-radius: 8px;
    padding: 0.25rem;
    margin-bottom: 1.5rem;
}

.filter-button {
    flex: 1;
    padding: 0.75rem 1rem;
    text-align: center;
    font-weight: 500;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.15s ease;
    color: #6b7280;
}

.filter-button.active {
    background: #b4975a;
    color: white;
}

/* Animated hover effect for buttons */
.animated-hover {
    animation: pulse-scale 0.5s ease;
}

@keyframes pulse-scale {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* Media query for responsive design */
@media (max-width: 991px) {
    .admin-sidebar {
        transform: translateX(-100%);
    }
    
    .admin-sidebar.show {
        transform: translateX(0);
    }
    
    .admin-content {
        margin-left: 0;
    }
}
</style>

<div class="admin-wrapper">
    <!-- Include the reusable sidebar -->
    <?php include 'admin_blog_sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="btn btn-sm btn-icon me-3">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="h3 mb-0">Blog Management</h1>
            </div>
            <a href="admin_blog_create.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i> Create New Post
            </a>
        </div>

        <?php if (!empty($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <?php 
                $message = '';
                switch ($_GET['success']) {
                    case 'delete':
                        $message = 'Blog post has been deleted successfully.';
                        break;
                    case 'publish':
                        $message = 'Blog post has been published successfully.';
                        break;
                    case 'unpublish':
                        $message = 'Blog post has been unpublished successfully.';
                        break;
                    default:
                        $message = 'Operation completed successfully.';
                }
                echo $message;
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <?php 
                $message = '';
                switch ($_GET['error']) {
                    case 'delete':
                        $message = 'Error deleting blog post. Please try again.';
                        break;
                    case 'publish':
                        $message = 'Error publishing blog post. Please try again.';
                        break;
                    case 'unpublish':
                        $message = 'Error unpublishing blog post. Please try again.';
                        break;
                    default:
                        $message = 'An error occurred. Please try again.';
                }
                echo $message;
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Dashboard Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="dashboard-card dashboard-card-primary position-relative">
                    <h5 class="card-title">Total Posts</h5>
                    <p class="card-value"><?php echo $total_posts; ?></p>
                    <i class="fas fa-newspaper card-icon"></i>
                    <a href="#" class="card-link filter-all">View all posts</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card dashboard-card-success position-relative">
                    <h5 class="card-title">Published</h5>
                    <p class="card-value"><?php echo $published_count; ?></p>
                    <i class="fas fa-check-circle card-icon"></i>
                    <a href="#" class="card-link filter-published">View published</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card dashboard-card-warning position-relative">
                    <h5 class="card-title">Drafts</h5>
                    <p class="card-value"><?php echo $draft_count; ?></p>
                    <i class="fas fa-pencil-alt card-icon"></i>
                    <a href="#" class="card-link filter-draft">View drafts</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card dashboard-card-info position-relative">
                    <h5 class="card-title">Categories</h5>
                    <p class="card-value"><?php echo $category_count; ?></p>
                    <i class="fas fa-tags card-icon"></i>
                    <a href="admin_category.php" class="card-link">Manage categories</a>
                </div>
            </div>
        </div>

        <!-- Status Filter -->
        <div class="status-filter mb-4">
            <div class="filter-button active" data-status="all">
                <i class="fas fa-list me-2"></i> All Posts
            </div>
            <div class="filter-button" data-status="published">
                <i class="fas fa-check-circle me-2"></i> Published
            </div>
            <div class="filter-button" data-status="draft">
                <i class="fas fa-pencil-alt me-2"></i> Drafts
            </div>
        </div>

        <!-- Blog List Table -->
        <div class="blog-table">
            <table id="blog-posts-table" class="table table-borderless table-hover">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Published Date</th>
                        <th>Views</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($post = $result->fetch_assoc()): ?>
                    <tr data-status="<?php echo $post['status']; ?>">
                        <td>
                            <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?></td>
                        <td>
                            <?php if ($post['status'] === 'published'): ?>
                            <span class="status-badge status-badge-published">Published</span>
                            <?php else: ?>
                            <span class="status-badge status-badge-draft">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            if ($post['published_at']) {
                                echo date('M d, Y', strtotime($post['published_at']));
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                        <td><?php echo number_format($post['views']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                        <td>
                            <div class="d-flex">
                                <?php if ($post['status'] === 'published'): ?>
                                <a href="blogs/<?php echo htmlspecialchars($post['slug']); ?>" class="action-button action-button-view" title="View" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php else: ?>
                                <a href="blogs/<?php echo htmlspecialchars($post['slug']); ?>?preview=true" class="action-button action-button-view" title="Preview" target="_blank">
                                    <i class="fas fa-desktop"></i>
                                </a>
                                <?php endif; ?>
                                
                                <a href="admin_blog_edit.php?id=<?php echo $post['id']; ?>" class="action-button action-button-edit" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <?php if ($post['status'] === 'draft'): ?>
                                <a href="admin_blog.php?action=publish&id=<?php echo $post['id']; ?>" class="action-button action-button-publish" title="Publish" 
                                   onclick="return confirm('Are you sure you want to publish this post?');">
                                    <i class="fas fa-check-circle"></i>
                                </a>
                                <?php else: ?>
                                <a href="admin_blog.php?action=unpublish&id=<?php echo $post['id']; ?>" class="action-button action-button-unpublish" title="Unpublish" 
                                   onclick="return confirm('Are you sure you want to unpublish this post?');">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                                <?php endif; ?>
                                
                                <a href="admin_blog.php?action=delete&id=<?php echo $post['id']; ?>" class="action-button action-button-delete" title="Delete" 
                                   onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.');">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    
                    <?php if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-newspaper fa-3x mb-3"></i>
                                <h4>No Blog Posts Found</h4>
                                <p>Get started by creating your first blog post</p>
                                <a href="admin_blog_create.php" class="btn btn-primary mt-2">
                                    <i class="fas fa-plus-circle me-2"></i> Create New Blog Post
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTables
        const blogTable = $('#blog-posts-table').DataTable({
            responsive: true,
            order: [[5, 'desc']], // Sort by created date by default
            language: {
                search: "",
                searchPlaceholder: "Search blog posts...",
                lengthMenu: "Show _MENU_ posts per page",
            },
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-4"ip>',
        });
        
        // Status filtering functionality
        $('.filter-button').on('click', function() {
            const status = $(this).data('status');
            
            // Toggle active class
            $('.filter-button').removeClass('active');
            $(this).addClass('active');
            
            // Filter table based on status
            if (status === 'all') {
                // Clear any filters
                blogTable.search('').columns().search('').draw();
            } else {
                // Get the column index for the status column
                blogTable.column(2).search(status === 'published' ? 'Published' : 'Draft').draw();
            }
        });
        
        // Quick filters from dashboard cards
        $('.filter-all').on('click', function(e) {
            e.preventDefault();
            $('.filter-button[data-status="all"]').click();
        });
        
        $('.filter-published').on('click', function(e) {
            e.preventDefault();
            $('.filter-button[data-status="published"]').click();
        });
        
        $('.filter-draft').on('click', function(e) {
            e.preventDefault();
            $('.filter-button[data-status="draft"]').click();
        });
        
        // Hovering on action buttons
        $('.action-button').hover(
            function() {
                $(this).find('i').addClass('animated-hover');
            },
            function() {
                $(this).find('i').removeClass('animated-hover');
            }
        );
        
        // Handle responsive sidebar toggle
        function handleSidebarToggle() {
            if (window.innerWidth < 992) {
                $('.admin-sidebar').removeClass('show');
            }
        }
        
        // Sidebar toggle for mobile
        $(document).on('click', '#sidebarToggle', function() {
            $('.admin-sidebar').toggleClass('show');
        });
        
        // Handle sidebar on window resize
        $(window).on('resize', handleSidebarToggle);
    });
</script>

<?php include 'bfooter.php'; ?> 