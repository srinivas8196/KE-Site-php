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

// Handle category actions (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new category
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name']);
        $slug = trim($_POST['slug']);
        $description = trim($_POST['description']);
        
        // Generate slug if empty
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $name), '-'));
        }
        
        // Check if slug already exists
        $check_query = "SELECT id FROM blog_categories WHERE slug = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $slug);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "A category with this slug already exists.";
        } else {
            // Insert new category
            $insert_query = "INSERT INTO blog_categories (name, slug, description) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("sss", $name, $slug, $description);
            
            if ($insert_stmt->execute()) {
                $success_message = "Category created successfully.";
            } else {
                $error_message = "Error creating category: " . $conn->error;
            }
        }
    } 
    // Edit category
    elseif (isset($_POST['edit_category'])) {
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        $slug = trim($_POST['slug']);
        $description = trim($_POST['description']);
        
        // Generate slug if empty
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $name), '-'));
        }
        
        // Check if slug already exists for another category
        $check_query = "SELECT id FROM blog_categories WHERE slug = ? AND id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("si", $slug, $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "A category with this slug already exists.";
        } else {
            // Update category
            $update_query = "UPDATE blog_categories SET name = ?, slug = ?, description = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sssi", $name, $slug, $description, $id);
            
            if ($update_stmt->execute()) {
                $success_message = "Category updated successfully.";
            } else {
                $error_message = "Error updating category: " . $conn->error;
            }
        }
    }
}

// Handle delete via GET request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Check if any blog posts use this category
    $check_query = "SELECT COUNT(*) as count FROM blog_posts WHERE category_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $error_message = "Cannot delete this category because it is used by " . $row['count'] . " blog post(s).";
    } else {
        // Delete the category
        $delete_query = "DELETE FROM blog_categories WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $id);
        
        if ($delete_stmt->execute()) {
            $success_message = "Category deleted successfully.";
        } else {
            $error_message = "Error deleting category: " . $conn->error;
        }
    }
}

// Get all categories
$query = "SELECT c.*, COUNT(p.id) as post_count 
          FROM blog_categories c 
          LEFT JOIN blog_posts p ON c.id = p.category_id 
          GROUP BY c.id 
          ORDER BY c.name";
$result = $conn->query($query);

// Get specific category for edit form
$edit_category = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $edit_query = "SELECT * FROM blog_categories WHERE id = ?";
    $edit_stmt = $conn->prepare($edit_query);
    $edit_stmt->bind_param("i", $id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    $edit_category = $edit_result->fetch_assoc();
}

$page_title = "Blog Categories";
include 'bheader.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin-blog.css">

<style>
/* Category Card */
.category-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.category-card-header {
    background-color: #f8fafc;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #edf2f7;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.category-card-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #4b5563;
}

.category-card-body {
    padding: 1.5rem;
}

.category-table th {
    font-weight: 600;
    color: #4b5563;
    background-color: #f8fafc;
    border-bottom: 1px solid #edf2f7;
    padding: 0.75rem 1rem;
}

.category-table td {
    vertical-align: middle;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #edf2f7;
}

.category-table tbody tr:last-child td {
    border-bottom: none;
}

.category-table tbody tr:hover {
    background-color: #f9fafb;
}

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

.action-button-edit:hover {
    color: #10b981;
}

.action-button-delete:hover {
    color: #ef4444;
}

.form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: #4b5563;
}

.form-control {
    border-radius: 6px;
    border: 1px solid #d1d5db;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

.form-control:focus {
    border-color: #b4975a;
    box-shadow: 0 0 0 3px rgba(180, 151, 90, 0.1);
    outline: none;
}

.form-text {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.post-count-badge {
    display: inline-block;
    background-color: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border-radius: 30px;
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 500;
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
                <h1 class="h3 mb-0">Blog Categories</h1>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal" <?php if ($edit_category): ?>style="display: none"<?php endif; ?>>
                <i class="fas fa-plus-circle me-2"></i> Add New Category
            </button>
        </div>

        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2 mb-4" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 mb-4" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Edit Form (shown when editing) -->
        <?php if ($edit_category): ?>
        <div class="row mb-4">
            <div class="col-md-8 mx-auto">
                <div class="category-card">
                    <div class="category-card-header">
                        <h3><i class="fas fa-edit me-2"></i> Edit Category</h3>
                        <a href="admin_category.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times me-2"></i> Cancel
                        </a>
                    </div>
                    <div class="category-card-body">
                        <form action="admin_category.php" method="POST">
                            <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                            <div class="mb-3">
                                <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($edit_category['name']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="slug" class="form-label">Slug</label>
                                <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($edit_category['slug']); ?>">
                                <div class="form-text">Leave blank to auto-generate from name. Use lowercase letters, numbers, and hyphens.</div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($edit_category['description']); ?></textarea>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" name="edit_category" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Update Category
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Categories Table -->
        <div class="category-card">
            <div class="category-card-header">
                <h3><i class="fas fa-folder me-2"></i> All Categories</h3>
            </div>
            <div class="category-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless category-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Description</th>
                                <th>Posts</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($category = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                        <td><?php echo htmlspecialchars($category['description'] ?? '—'); ?></td>
                                        <td>
                                            <span class="post-count-badge">
                                                <?php echo $category['post_count']; ?> post<?php echo $category['post_count'] != 1 ? 's' : ''; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="admin_category.php?action=edit&id=<?php echo $category['id']; ?>" class="action-button action-button-edit" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($category['post_count'] == 0): ?>
                                                    <a href="admin_category.php?action=delete&id=<?php echo $category['id']; ?>" class="action-button action-button-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this category?');">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button type="button" class="action-button action-button-delete" disabled title="Cannot delete category with posts" data-bs-toggle="tooltip">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="py-5">
                                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No categories found</h5>
                                            <button class="btn btn-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                                Create your first category
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="admin_category.php" method="POST">
                    <div class="mb-3">
                        <label for="modal-name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="modal-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="modal-slug" class="form-label">Slug</label>
                        <input type="text" class="form-control" id="modal-slug" name="slug">
                        <div class="form-text">Leave blank to auto-generate from name. Use lowercase letters, numbers, and hyphens.</div>
                    </div>
                    <div class="mb-3">
                        <label for="modal-description" class="form-label">Description</label>
                        <textarea class="form-control" id="modal-description" name="description" rows="3"></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable for category table
    $('.category-table').DataTable({
        responsive: true,
        language: {
            search: "",
            searchPlaceholder: "Search categories...",
            lengthMenu: "Show _MENU_ categories per page",
        },
        lengthMenu: [10, 25, 50],
        pageLength: 10,
        dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-4"ip>',
    });
    
    // Generate slug from name in Add Modal
    $('#modal-name').on('blur', function() {
        const slugField = $('#modal-slug');
        if (slugField.val() === '') {
            const nameValue = $(this).val().toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
            
            slugField.val(nameValue);
        }
    });
    
    // Generate slug from name in Edit Form
    $('#name').on('blur', function() {
        const slugField = $('#slug');
        if (slugField.val() === '') {
            const nameValue = $(this).val().toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
            
            slugField.val(nameValue);
        }
    });
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
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
});
</script>

<?php include 'bfooter.php'; ?> 