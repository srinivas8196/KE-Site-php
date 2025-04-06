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

// Initialize variables
$error_message = '';
$success_message = '';
$edit_id = null;
$edit_name = '';
$edit_slug = '';
$edit_description = '';

// Handle category deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $cat_id = $_GET['delete'];
    
    // Check if any posts are using this category
    $check_posts = $conn->prepare("SELECT COUNT(*) as post_count FROM blog_posts WHERE category_id = ?");
    $check_posts->bind_param("i", $cat_id);
    $check_posts->execute();
    $result = $check_posts->get_result();
    $post_count = $result->fetch_assoc()['post_count'];
    
    if ($post_count > 0) {
        $error_message = "Cannot delete category: There are {$post_count} posts using this category. Reassign posts first.";
    } else {
        $delete_cat = $conn->prepare("DELETE FROM blog_categories WHERE id = ?");
        $delete_cat->bind_param("i", $cat_id);
        
        if ($delete_cat->execute()) {
            $success_message = "Category deleted successfully.";
        } else {
            $error_message = "Error deleting category: " . $conn->error;
        }
    }
}

// Handle editing: Get category data for edit form
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $get_category = $conn->prepare("SELECT * FROM blog_categories WHERE id = ?");
    $get_category->bind_param("i", $edit_id);
    $get_category->execute();
    $category = $get_category->get_result()->fetch_assoc();
    
    if ($category) {
        $edit_name = $category['name'];
        $edit_slug = $category['slug'];
        $edit_description = $category['description'];
    } else {
        $error_message = "Category not found.";
        $edit_id = null;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validate name
    if (empty($name)) {
        $error_message = "Category name is required.";
    } else {
        // Generate slug if not provided
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
        }
        
        // Check if slug already exists (for new categories or when changing slug)
        $check_slug = $conn->prepare("SELECT id FROM blog_categories WHERE slug = ? AND id != ?");
        $check_slug->bind_param("si", $slug, $_POST['id'] ?? 0);
        $check_slug->execute();
        $slug_result = $check_slug->get_result();
        
        if ($slug_result->num_rows > 0) {
            $error_message = "Slug '{$slug}' is already in use. Please choose a different slug.";
        } else {
            // Add or update category
            if (isset($_POST['id']) && is_numeric($_POST['id'])) {
                // Update existing category
                $update_cat = $conn->prepare("UPDATE blog_categories SET name = ?, slug = ?, description = ? WHERE id = ?");
                $update_cat->bind_param("sssi", $name, $slug, $description, $_POST['id']);
                
                if ($update_cat->execute()) {
                    $success_message = "Category updated successfully.";
                    $edit_id = null; // Reset edit mode
                } else {
                    $error_message = "Error updating category: " . $conn->error;
                }
            } else {
                // Add new category
                $add_cat = $conn->prepare("INSERT INTO blog_categories (name, slug, description) VALUES (?, ?, ?)");
                $add_cat->bind_param("sss", $name, $slug, $description);
                
                if ($add_cat->execute()) {
                    $success_message = "Category added successfully.";
                } else {
                    $error_message = "Error adding category: " . $conn->error;
                }
            }
        }
    }
}

// Fetch all categories with post counts
$categories_query = "SELECT c.*, 
                     (SELECT COUNT(*) FROM blog_posts WHERE category_id = c.id) as post_count
                     FROM blog_categories c
                     ORDER BY c.name ASC";
$categories = $conn->query($categories_query);

// Include header
include 'bheader.php';
?>

<!-- Include our new CSS file -->
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

#sidebarToggle {
    background-color: transparent;
    border: none;
    color: #6b7280;
    font-size: 1.25rem;
    padding: 0.5rem;
    cursor: pointer;
    transition: color 0.2s ease;
}

#sidebarToggle:hover {
    color: #b4975a;
}

.btn-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background-color: #f3f4f6;
}
</style>

<div class="admin-wrapper">
    <!-- Sidebar -->
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
            <a href="admin_blog.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Blog</a>
        </div>
        
        <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="content-section">
                    <div class="content-section__header">
                        <h2><?php echo $edit_id ? 'Edit Category' : 'Add New Category'; ?></h2>
                    </div>
                    <div class="card-body">
                        <form method="post" action="" class="blog-form">
                            <?php if ($edit_id): ?>
                                <input type="hidden" name="id" value="<?php echo $edit_id; ?>">
                            <?php endif; ?>
                            
                            <div class="form-group mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($edit_name); ?>" required>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="slug" class="form-label">Slug</label>
                                <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($edit_slug); ?>" placeholder="Leave blank to generate automatically">
                                <div class="form-text">The "slug" is the URL-friendly version of the name.</div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($edit_description); ?></textarea>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary"><?php echo $edit_id ? 'Update Category' : 'Add Category'; ?></button>
                                <?php if ($edit_id): ?>
                                    <a href="admin_blog_categories.php" class="btn btn-outline-secondary">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="content-section blog-table">
                    <div class="content-section__header">
                        <h2>Categories</h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover" id="categoriesTable">
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
                                    <?php if ($categories && $categories->num_rows > 0): ?>
                                        <?php while ($category = $categories->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                                <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                                <td>
                                                    <?php 
                                                    $desc = htmlspecialchars($category['description'] ?? '');
                                                    echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc; 
                                                    ?>
                                                </td>
                                                <td><span class="badge bg-primary rounded-pill"><?php echo $category['post_count']; ?></span></td>
                                                <td>
                                                    <div class="blog-actions">
                                                        <a href="admin_blog_categories.php?edit=<?php echo $category['id']; ?>" class="action-button edit" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($category['post_count'] == 0): ?>
                                                            <a href="admin_blog_categories.php?delete=<?php echo $category['id']; ?>" 
                                                               class="action-button delete" title="Delete"
                                                               onclick="return confirm('Are you sure you want to delete this category?')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <button class="action-button" disabled title="Cannot delete: category has posts">
                                                                <i class="fas fa-trash text-muted"></i>
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
                                                    <i class="fas fa-tag fa-3x text-muted mb-3"></i>
                                                    <h5 class="text-muted">No categories found</h5>
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
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Generate slug from name
    document.getElementById('name').addEventListener('blur', function() {
        var slugField = document.getElementById('slug');
        
        // Only generate slug if the field is empty and we're adding a new category
        if (slugField.value === '' && !document.querySelector('input[name="id"]')) {
            var slug = this.value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
                
            slugField.value = slug;
        }
    });
    
    // Initialize DataTable
    $(document).ready(function() {
        $('#categoriesTable').DataTable({
            order: [[0, "asc"]],
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            responsive: true,
            language: {
                search: "",
                searchPlaceholder: "Search categories...",
                lengthMenu: "_MENU_ categories per page",
                info: "Showing _START_ to _END_ of _TOTAL_ categories",
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
        
        // Handle sidebar toggle for mobile
        function handleSidebarToggle() {
            if (window.innerWidth < 992) {
                $('.admin-sidebar').removeClass('show');
            }
        }
        
        // Sidebar toggle for mobile
        $('#sidebarToggle').on('click', function() {
            $('.admin-sidebar').toggleClass('show');
        });
        
        // Handle sidebar on window resize
        $(window).on('resize', handleSidebarToggle);
    });
</script>

<?php include 'bfooter.php'; ?> 