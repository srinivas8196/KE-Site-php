<?php
session_start();

// Include auth helper
require_once 'auth_helper.php';

// Check if user has campaign_manager or higher permission
requirePermission('campaign_manager', 'login.php');

// Include database connection
require_once 'db.php';

// Ensure we have a connection
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection error. Please check your configuration.");
}

// Handle comment actions (approve, reject, delete)
if (isset($_GET['action']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $comment_id = $_GET['id'];
    $action = $_GET['action'];
    
    switch ($action) {
        case 'approve':
            $stmt = $conn->prepare("UPDATE blog_comments SET status = 'approved' WHERE id = ?");
            $stmt->bind_param("i", $comment_id);
            
            if ($stmt->execute()) {
                header("Location: admin_blog_comments.php?success=approve");
            } else {
                header("Location: admin_blog_comments.php?error=approve");
            }
            exit;
            break;
            
        case 'spam':
            $stmt = $conn->prepare("UPDATE blog_comments SET status = 'spam' WHERE id = ?");
            $stmt->bind_param("i", $comment_id);
            
            if ($stmt->execute()) {
                header("Location: admin_blog_comments.php?success=spam");
            } else {
                header("Location: admin_blog_comments.php?error=spam");
            }
            exit;
            break;
            
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM blog_comments WHERE id = ?");
            $stmt->bind_param("i", $comment_id);
            
            if ($stmt->execute()) {
                header("Location: admin_blog_comments.php?success=delete");
            } else {
                header("Location: admin_blog_comments.php?error=delete");
            }
            exit;
            break;
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$blog_filter = isset($_GET['blog_id']) ? (int)$_GET['blog_id'] : 0;

// Build query based on filters
$query_params = [];
$where_clauses = [];

// Get count of pending comments
$pending_count_query = "SELECT COUNT(*) as count FROM blog_comments WHERE status = 'pending'";
$pending_result = $conn->query($pending_count_query);
$pending_count = ($pending_result) ? $pending_result->fetch_assoc()['count'] : 0;

// Filter by status
if ($status_filter !== 'all') {
    $where_clauses[] = "c.status = ?";
    $query_params[] = $status_filter;
}

// Filter by blog post
if ($blog_filter > 0) {
    $where_clauses[] = "c.post_id = ?";
    $query_params[] = $blog_filter;
}

// Combine where clauses
$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(' AND ', $where_clauses);
}

// Query to get all comments with blog post titles
$query = "SELECT c.*, p.title as post_title, p.slug as post_slug 
          FROM blog_comments c
          LEFT JOIN blog_posts p ON c.post_id = p.id 
          $where_sql
          ORDER BY c.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);

if (!empty($query_params)) {
    $types = str_repeat('s', count($query_params));
    $stmt->bind_param($types, ...$query_params);
}

$stmt->execute();
$result = $stmt->get_result();

// Get all blog posts for the filter dropdown
$blogs_query = "SELECT id, title FROM blog_posts ORDER BY title ASC";
$blogs_result = $conn->query($blogs_query);

// Include header
$page_title = "Blog Comments Management";
include 'bheader.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<style>
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

.comment-card {
    border-left: 4px solid #eaeaea;
    transition: all 0.2s ease;
}

.comment-card.pending {
    border-left-color: #f59e0b;
}

.comment-card.approved {
    border-left-color: #10b981;
}

.comment-card.spam {
    border-left-color: #ef4444;
}

.comment-content {
    white-space: pre-line;
}

.badge-pending {
    background-color: #f59e0b;
    color: white;
}

.badge-approved {
    background-color: #10b981;
    color: white;
}

.badge-spam {
    background-color: #ef4444;
    color: white;
}

.filter-bar {
    background: white;
    border-radius: 10px;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.notification-bubble {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background-color: #f59e0b;
    color: white;
    font-size: 12px;
    font-weight: bold;
    margin-left: 0.5rem;
}
</style>

<div class="admin-wrapper">
    <?php include 'admin_blog_sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="container-fluid px-0">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Blog Comments Management</h1>
                <?php if ($pending_count > 0): ?>
                <div class="badge bg-warning px-3 py-2">
                    <?php echo $pending_count; ?> pending comment<?php echo $pending_count != 1 ? 's' : ''; ?> need review
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Filters -->
            <div class="filter-bar d-flex flex-wrap align-items-center gap-3">
                <div>
                    <label class="form-label mb-0"><i class="fas fa-filter me-1"></i> Filter by:</label>
                </div>
                <div>
                    <select class="form-select form-select-sm" id="statusFilter" onchange="applyFilters()">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="spam" <?php echo $status_filter === 'spam' ? 'selected' : ''; ?>>Spam</option>
                    </select>
                </div>
                <div>
                    <select class="form-select form-select-sm" id="blogFilter" onchange="applyFilters()">
                        <option value="0">All Blog Posts</option>
                        <?php if ($blogs_result && $blogs_result->num_rows > 0): ?>
                            <?php while ($blog = $blogs_result->fetch_assoc()): ?>
                                <option value="<?php echo $blog['id']; ?>" <?php echo $blog_filter === (int)$blog['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($blog['title']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php
                    $success_action = $_GET['success'];
                    switch ($success_action) {
                        case 'approve':
                            echo 'Comment has been approved successfully.';
                            break;
                        case 'spam':
                            echo 'Comment has been marked as spam.';
                            break;
                        case 'delete':
                            echo 'Comment has been deleted successfully.';
                            break;
                        default:
                            echo 'Action completed successfully.';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php
                    $error_action = $_GET['error'];
                    switch ($error_action) {
                        case 'approve':
                            echo 'Failed to approve comment.';
                            break;
                        case 'spam':
                            echo 'Failed to mark comment as spam.';
                            break;
                        case 'delete':
                            echo 'Failed to delete comment.';
                            break;
                        default:
                            echo 'An error occurred while processing your request.';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Comments List -->
            <div class="row">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($comment = $result->fetch_assoc()): ?>
                        <div class="col-12 mb-3">
                            <div class="card comment-card <?php echo $comment['status']; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <h5 class="mb-0"><?php echo htmlspecialchars($comment['name']); ?></h5>
                                            <small class="text-muted">
                                                <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($comment['email']); ?>
                                                <?php if (!empty($comment['website'])): ?>
                                                    | <i class="fas fa-globe me-1"></i> <a href="<?php echo htmlspecialchars($comment['website']); ?>" target="_blank"><?php echo htmlspecialchars($comment['website']); ?></a>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div>
                                            <span class="badge badge-<?php echo $comment['status']; ?> px-3 py-2">
                                                <?php echo ucfirst($comment['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="comment-content mb-3 p-3 bg-light rounded">
                                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="text-muted">
                                                <i class="fas fa-calendar me-1"></i> <?php echo date('M d, Y h:i A', strtotime($comment['created_at'])); ?>
                                            </span>
                                            <span class="ms-3 text-muted">
                                                <i class="fas fa-newspaper me-1"></i> 
                                                <a href="blog-details.php?slug=<?php echo urlencode($comment['post_slug']); ?>" target="_blank">
                                                    <?php echo htmlspecialchars($comment['post_title']); ?>
                                                </a>
                                            </span>
                                        </div>
                                        <div class="btn-group">
                                            <?php if ($comment['status'] !== 'approved'): ?>
                                                <a href="admin_blog_comments.php?action=approve&id=<?php echo $comment['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to approve this comment?')">
                                                    <i class="fas fa-check me-1"></i> Approve
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($comment['status'] !== 'spam'): ?>
                                                <a href="admin_blog_comments.php?action=spam&id=<?php echo $comment['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to mark this comment as spam?')">
                                                    <i class="fas fa-ban me-1"></i> Mark as Spam
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="admin_blog_comments.php?action=delete&id=<?php echo $comment['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this comment? This action cannot be undone.')">
                                                <i class="fas fa-trash me-1"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            No comments found matching your criteria.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function applyFilters() {
    const statusFilter = document.getElementById('statusFilter').value;
    const blogFilter = document.getElementById('blogFilter').value;
    
    let url = 'admin_blog_comments.php?';
    
    if (statusFilter !== 'all') {
        url += 'status=' + statusFilter + '&';
    }
    
    if (blogFilter !== '0') {
        url += 'blog_id=' + blogFilter;
    }
    
    window.location.href = url;
}
</script>

<?php include 'bfooter.php'; ?> 