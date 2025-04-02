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

// Get all categories for the select dropdown
$categories_query = "SELECT * FROM blog_categories ORDER BY name";
$categories_result = $conn->query($categories_query);

// Get all tags for the multi-select dropdown
$tags_query = "SELECT * FROM blog_tags ORDER BY name";
$tags_result = $conn->query($tags_query);

// Initialize error and success messages
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = $_POST['title'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $content = $_POST['content'] ?? '';
    $excerpt = $_POST['excerpt'] ?? '';
    $meta_title = $_POST['meta_title'] ?? '';
    $meta_description = $_POST['meta_description'] ?? '';
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $status = $_POST['status'] ?? 'draft';
    $published_at = $status === 'published' ? date('Y-m-d H:i:s') : null;
    $author_id = $_SESSION['user_id'];
    $tags = $_POST['tags'] ?? [];
    
    $featured_image = ''; // Default empty value
    
    // Generate slug if empty
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $title), '-'));
    }
    
    // Validate required fields
    if (empty($title) || empty($content)) {
        $error_message = 'Title and content are required.';
    } else {
        // Check if slug already exists
        $check_slug = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ?");
        $check_slug->bind_param("s", $slug);
        $check_slug->execute();
        $slug_result = $check_slug->get_result();
        
        if ($slug_result->num_rows > 0) {
            // Slug exists, append a number
            $slug = $slug . '-' . time();
        }
        
        // Handle featured image upload
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/blog/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = basename($_FILES['featured_image']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $new_file_name = $slug . '-' . time() . '.' . $file_ext;
            $target_file = $upload_dir . $new_file_name;
            
            // Check if file is an actual image
            $check = getimagesize($_FILES['featured_image']['tmp_name']);
            if ($check !== false) {
                // Check file size (limit to 5MB)
                if ($_FILES['featured_image']['size'] <= 5000000) {
                    // Allow certain file formats
                    if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                        if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $target_file)) {
                            $featured_image = $target_file;
                        } else {
                            $error_message = 'Error uploading file.';
                        }
                    } else {
                        $error_message = 'Only JPG, JPEG, PNG & GIF files are allowed.';
                    }
                } else {
                    $error_message = 'File is too large. Maximum size is 5MB.';
                }
            } else {
                $error_message = 'File is not an image.';
            }
        }
        
        if (empty($error_message)) {
            // Insert blog post
            $insert_query = "INSERT INTO blog_posts (title, slug, content, excerpt, featured_image, meta_title, meta_description, category_id, author_id, status, published_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("sssssssisss", $title, $slug, $content, $excerpt, $featured_image, $meta_title, $meta_description, $category_id, $author_id, $status, $published_at);
            
            if ($insert_stmt->execute()) {
                $post_id = $conn->insert_id;
                
                // Insert tags relationships
                if (!empty($tags)) {
                    $tag_query = "INSERT INTO blog_post_tags (post_id, tag_id) VALUES (?, ?)";
                    $tag_stmt = $conn->prepare($tag_query);
                    
                    foreach ($tags as $tag_id) {
                        $tag_stmt->bind_param("ii", $post_id, $tag_id);
                        $tag_stmt->execute();
                    }
                }
                
                $success_message = 'Blog post created successfully.';
                
                // Redirect to edit page after success
                header('Location: admin_blog_edit.php?id=' . $post_id . '&success=created');
                exit;
            } else {
                $error_message = 'Error creating blog post: ' . $conn->error;
            }
        }
    }
}

$page_title = "Create Blog Post";
include 'bheader.php';
?>

<!-- Include our new CSS file -->
<link rel="stylesheet" href="assets/css/admin-blog.css">

<div class="container mt-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="mb-0">Create New Blog Post</h1>
        <a href="admin_blog.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Back to Blog List
        </a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="content-section">
        <div class="content-section__header">
            <h2>Post Information</h2>
        </div>
        <div class="card-body">
            <form action="admin_blog_create.php" method="POST" enctype="multipart/form-data" class="blog-form">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="form-group mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required
                                value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="slug" class="form-label">Slug (URL-friendly version of title)</label>
                            <input type="text" class="form-control" id="slug" name="slug" 
                                value="<?php echo isset($_POST['slug']) ? htmlspecialchars($_POST['slug']) : ''; ?>"
                                placeholder="Leave blank to auto-generate from title">
                            <small class="form-text text-muted">
                                Use lowercase letters, numbers, and hyphens. No spaces or special characters.
                            </small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="15" required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="excerpt" class="form-label">Excerpt</label>
                            <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?php echo isset($_POST['excerpt']) ? htmlspecialchars($_POST['excerpt']) : ''; ?></textarea>
                            <small class="form-text text-muted">
                                A short summary of your post. If left empty, it will be automatically generated from the content.
                            </small>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="side-card mb-4">
                            <div class="side-card-header">
                                <h5><i class="fas fa-paper-plane me-2"></i>Publishing</h5>
                            </div>
                            <div class="side-card-body">
                                <div class="form-group mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                        <option value="published" <?php echo (isset($_POST['status']) && $_POST['status'] === 'published') ? 'selected' : ''; ?>>Published</option>
                                    </select>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save me-2"></i> Save Blog Post
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="side-card mb-4">
                            <div class="side-card-header">
                                <h5><i class="fas fa-tags me-2"></i>Categories & Tags</h5>
                            </div>
                            <div class="side-card-body">
                                <div class="form-group mb-3">
                                    <label for="category_id" class="form-label">Category</label>
                                    <select class="form-select" id="category_id" name="category_id">
                                        <option value="">-- Select Category --</option>
                                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="tags" class="form-label">Tags</label>
                                    <select class="form-select" id="tags" name="tags[]" multiple>
                                        <?php while ($tag = $tags_result->fetch_assoc()): ?>
                                            <option value="<?php echo $tag['id']; ?>" <?php echo (isset($_POST['tags']) && in_array($tag['id'], $_POST['tags'])) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tag['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        Hold Ctrl (or Cmd on Mac) to select multiple tags.
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="side-card mb-4">
                            <div class="side-card-header">
                                <h5><i class="fas fa-image me-2"></i>Featured Image</h5>
                            </div>
                            <div class="side-card-body">
                                <div class="form-group mb-3">
                                    <label for="featured_image" class="form-label">Select an image</label>
                                    <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/*">
                                    <small class="form-text text-muted">
                                        Recommended size: 1200x800 pixels. Max size: 5MB.
                                    </small>
                                    <div id="image-preview" class="mt-3 text-center" style="display: none;">
                                        <img src="" alt="Image Preview" class="img-fluid rounded">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="side-card mb-4">
                            <div class="side-card-header">
                                <h5><i class="fas fa-search me-2"></i>SEO Settings</h5>
                            </div>
                            <div class="side-card-body">
                                <div class="form-group mb-3">
                                    <label for="meta_title" class="form-label">Meta Title</label>
                                    <input type="text" class="form-control" id="meta_title" name="meta_title" 
                                        value="<?php echo isset($_POST['meta_title']) ? htmlspecialchars($_POST['meta_title']) : ''; ?>">
                                    <small class="form-text text-muted">
                                        Leave blank to use post title. Recommended length: 50-60 characters.
                                    </small>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="meta_description" class="form-label">Meta Description</label>
                                    <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo isset($_POST['meta_description']) ? htmlspecialchars($_POST['meta_description']) : ''; ?></textarea>
                                    <small class="form-text text-muted">
                                        A brief description of your post for search engines. Recommended length: 150-160 characters.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.tiny.cloud/1/2dcx752ng7via2ayk5f144ggv0lz4w4gxhihr5vynet3ru4f/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    // Initialize TinyMCE for content editor
    tinymce.init({
        selector: '#content',
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
        toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
        height: 500,
        menubar: true,
        skin: 'oxide',
        content_css: 'default',
        branding: false,
        promotion: false,
        api_key: '2dcx752ng7via2ayk5f144ggv0lz4w4gxhihr5vynet3ru4f'
    });
    
    // Generate slug from title
    document.getElementById('title').addEventListener('blur', function() {
        const slugField = document.getElementById('slug');
        if (slugField.value === '') {
            const titleValue = this.value.toLowerCase()
                .replace(/[^\w\s-]/g, '')   // Remove special characters
                .replace(/\s+/g, '-')       // Replace spaces with hyphens
                .replace(/-+/g, '-')        // Replace multiple hyphens with a single hyphen
                .trim();                    // Trim leading/trailing hyphens
            
            slugField.value = titleValue;
        }
    });
    
    // Image preview
    document.getElementById('featured_image').addEventListener('change', function(e) {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            const preview = document.getElementById('image-preview');
            const previewImg = preview.querySelector('img');
            
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            }
            
            reader.readAsDataURL(file);
        }
    });
    
    // Initialize select2 for tags
    $(document).ready(function() {
        $('#tags').select2({
            placeholder: 'Select tags',
            width: '100%',
            theme: 'bootstrap-5'
        });
        
        $('#category_id').select2({
            placeholder: 'Select a category',
            width: '100%',
            theme: 'bootstrap-5'
        });
    });
</script>

<?php include 'bfooter.php'; ?> 