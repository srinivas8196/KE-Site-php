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

// Check if post ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: admin_blog.php');
    exit;
}

$post_id = (int)$_GET['id'];

// Get all categories for the select dropdown
$categories_query = "SELECT * FROM blog_categories ORDER BY name";
$categories_result = $conn->query($categories_query);

// Get all tags for the multi-select dropdown
$tags_query = "SELECT * FROM blog_tags ORDER BY name";
$tags_result = $conn->query($tags_query);

// Get the blog post data
$post_query = "SELECT * FROM blog_posts WHERE id = ?";
$post_stmt = $conn->prepare($post_query);
$post_stmt->bind_param("i", $post_id);
$post_stmt->execute();
$post_result = $post_stmt->get_result();

// Check if post exists
if ($post_result->num_rows === 0) {
    header('Location: admin_blog.php');
    exit;
}

$post = $post_result->fetch_assoc();

// Get the selected tags for this post
$selected_tags_query = "SELECT tag_id FROM blog_post_tags WHERE post_id = ?";
$selected_tags_stmt = $conn->prepare($selected_tags_query);
$selected_tags_stmt->bind_param("i", $post_id);
$selected_tags_stmt->execute();
$selected_tags_result = $selected_tags_stmt->get_result();

$selected_tags = [];
while ($tag = $selected_tags_result->fetch_assoc()) {
    $selected_tags[] = $tag['tag_id'];
}

// Initialize error and success messages
$error_message = '';
$success_message = '';

// Show success message from create page
if (isset($_GET['success']) && $_GET['success'] === 'created') {
    $success_message = 'Blog post created successfully.';
}

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
    $published_at = $status === 'published' ? ($post['published_at'] ? $post['published_at'] : date('Y-m-d H:i:s')) : null;
    $tags = $_POST['tags'] ?? [];
    
    // Keep the existing featured image by default
    $featured_image = $post['featured_image'];
    
    // Generate slug if empty
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $title), '-'));
    }
    
    // Validate required fields
    if (empty($title) || empty($content)) {
        $error_message = 'Title and content are required.';
    } else {
        // Check if slug already exists
        $check_slug = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ? AND id != ?");
        $check_slug->bind_param("si", $slug, $post_id);
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
                            // Delete old image if exists and it's a local file
                            if (!empty($post['featured_image']) && !preg_match('/^https?:\/\//', $post['featured_image'])) {
                                $old_file = $post['featured_image'];
                                if (file_exists($old_file)) {
                                    unlink($old_file);
                                }
                            }
                            // Store relative path in database
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
        
        // Handle remove featured image
        if (isset($_POST['remove_featured_image']) && $_POST['remove_featured_image'] === 'yes') {
            // Delete old image if exists and it's a local file
            if (!empty($post['featured_image']) && !preg_match('/^https?:\/\//', $post['featured_image'])) {
                $old_file = $post['featured_image'];
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            $featured_image = '';
        }
        
        if (empty($error_message)) {
            // Update blog post
            $update_query = "UPDATE blog_posts SET
                            title = ?,
                            slug = ?,
                            content = ?,
                            excerpt = ?,
                            featured_image = ?,
                            meta_title = ?,
                            meta_description = ?,
                            category_id = ?,
                            status = ?,
                            published_at = ?,
                            updated_at = CURRENT_TIMESTAMP
                            WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ssssssssssi", $title, $slug, $content, $excerpt, $featured_image, $meta_title, $meta_description, $category_id, $status, $published_at, $post_id);
            
            if ($update_stmt->execute()) {
                // Delete old tags relationships
                $delete_tags = $conn->prepare("DELETE FROM blog_post_tags WHERE post_id = ?");
                $delete_tags->bind_param("i", $post_id);
                $delete_tags->execute();
                
                // Insert new tags relationships
                if (!empty($tags)) {
                    $tag_query = "INSERT INTO blog_post_tags (post_id, tag_id) VALUES (?, ?)";
                    $tag_stmt = $conn->prepare($tag_query);
                    
                    foreach ($tags as $tag_id) {
                        $tag_stmt->bind_param("ii", $post_id, $tag_id);
                        $tag_stmt->execute();
                    }
                }
                
                $success_message = 'Blog post updated successfully.';
                
                // Refresh post data
                $post_stmt->execute();
                $post_result = $post_stmt->get_result();
                $post = $post_result->fetch_assoc();
                
                // Refresh selected tags
                $selected_tags_stmt->execute();
                $selected_tags_result = $selected_tags_stmt->get_result();
                
                $selected_tags = [];
                while ($tag = $selected_tags_result->fetch_assoc()) {
                    $selected_tags[] = $tag['tag_id'];
                }
            } else {
                $error_message = 'Error updating blog post: ' . $conn->error;
            }
        }
    }
}

$page_title = "Edit Blog Post";
include 'bheader.php';
?>

<!-- Add essential styling libraries -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<!-- Define CSS variables if not already set in the main CSS -->
<style>
/* Clean modern styling for blog editor */
.blog-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
  margin-bottom: 20px;
  overflow: hidden;
}

.blog-card-header {
  padding: 16px 20px;
  border-bottom: 1px solid #edf2f7;
  background-color: #f8fafc;
}

.blog-card-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  display: flex;
  align-items: center;
}

.blog-card-header h3 i {
  margin-right: 10px;
  color: #b4975a;
}

.blog-card-body {
  padding: 20px;
}

/* Main layout */
.blog-form {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
}

@media (max-width: 991px) {
  .blog-form {
    grid-template-columns: 1fr;
  }
}

.blog-form-main, .blog-form-sidebar {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Form elements */
.blog-form label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: #4b5563;
  font-size: 14px;
}

.blog-form input,
.blog-form textarea,
.blog-form select {
  width: 100%;
  padding: 10px 12px;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.blog-form input:focus,
.blog-form textarea:focus,
.blog-form select:focus {
  outline: none;
  border-color: #b4975a;
  box-shadow: 0 0 0 3px rgba(180, 151, 90, 0.1);
}

.form-hint {
  margin-top: 6px;
  font-size: 12px;
  color: #6b7280;
}

/* TinyMCE specific styling */
.tox-tinymce {
  border-radius: 6px !important;
}

/* Form section spacing */
.form-section {
  margin-bottom: 16px;
}

.form-section:last-child {
  margin-bottom: 0;
}

/* Status toggle */
.status-options {
  display: flex;
  background: #f3f4f6;
  border-radius: 6px;
  padding: 3px;
}

.status-option {
  flex: 1;
  padding: 8px 12px;
  text-align: center;
  font-weight: 500;
  cursor: pointer;
  border-radius: 4px;
  transition: all 0.15s ease;
}

.status-option.active {
  background: #b4975a;
  color: white;
}

/* Save button */
.save-button {
  width: 100%;
  padding: 12px;
  border: none;
  background: #b4975a;
  color: white;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.save-button:hover {
  background: #96793d;
}

/* Image uploader */
.image-upload-area {
  border: 2px dashed #d1d5db;
  border-radius: 6px;
  padding: 20px;
  text-align: center;
  cursor: pointer;
}

.image-upload-area:hover {
  border-color: #b4975a;
}

.image-upload-area i {
  font-size: 24px;
  color: #9ca3af;
  margin-bottom: 8px;
}

.image-upload-area h4 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.image-upload-area p {
  margin: 0;
  font-size: 12px;
  color: #6b7280;
}

.image-preview {
  margin-top: 12px;
  border-radius: 6px;
  overflow: hidden;
  position: relative;
}

.image-preview img {
  width: 100%;
  display: block;
}

.remove-image {
  position: absolute;
  top: 8px;
  right: 8px;
  background: rgba(0,0,0,0.5);
  border: none;
  color: white;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

/* Stats section */
.stats-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.stats-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #edf2f7;
}

.stats-list li:last-child {
  border-bottom: none;
}

.stats-list i {
  color: #b4975a;
  margin-right: 8px;
}

.stats-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.badge-primary {
  background-color: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.badge-secondary {
  background-color: rgba(107, 114, 128, 0.1);
  color: #6b7280;
}

.badge-info {
  background-color: rgba(6, 182, 212, 0.1);
  color: #06b6d4;
}

/* Preview section */
.preview-buttons {
  display: grid;
  gap: 8px;
}

.btn-view {
  background-color: #10b981;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-preview {
  background-color: #3b82f6;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-view:hover, .btn-preview:hover {
  filter: brightness(90%);
}
</style>

<!-- Include our custom CSS file -->
<link rel="stylesheet" href="assets/css/admin-blog.css">

<div class="container mt-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3">Edit Blog Post</h1>
        <div class="d-flex gap-2">
            <?php if ($post['status'] === 'published'): ?>
            <a href="blogs/<?php echo htmlspecialchars($post['slug']); ?>" class="btn btn-sm btn-success" target="_blank">
                <i class="fas fa-eye me-2"></i> View Blog
            </a>
            <?php endif; ?>
            <a href="blogs/<?php echo htmlspecialchars($post['slug']); ?>?preview=true" class="btn btn-sm btn-primary" target="_blank">
                <i class="fas fa-desktop me-2"></i> Preview Blog
            </a>
            <a href="admin_blog.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Blog List
            </a>
        </div>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="admin_blog_edit.php?id=<?php echo $post_id; ?>" method="POST" enctype="multipart/form-data" class="blog-form">
        <!-- Left Column -->
        <div class="blog-form-main">
            <!-- Basic Info Card -->
            <div class="blog-card">
                <div class="blog-card-header">
                    <h3><i class="fas fa-edit"></i> Basic Information</h3>
                </div>
                <div class="blog-card-body">
                    <div class="form-section">
                        <label for="title">Title <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title" required
                            value="<?php echo htmlspecialchars($post['title']); ?>"
                            placeholder="Enter blog title">
                    </div>
                    
                    <div class="form-section">
                        <label for="slug">URL Slug</label>
                        <input type="text" id="slug" name="slug" 
                            value="<?php echo htmlspecialchars($post['slug']); ?>"
                            placeholder="Leave blank to auto-generate from title">
                        <div class="form-hint">
                            Use lowercase letters, numbers, and hyphens. No spaces or special characters.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Card -->
            <div class="blog-card">
                <div class="blog-card-header">
                    <h3><i class="fas fa-paragraph"></i> Content <span class="text-danger">*</span></h3>
                </div>
                <div class="blog-card-body">
                    <textarea id="content" name="content" rows="15" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                </div>
            </div>

            <!-- Excerpt Card -->
            <div class="blog-card">
                <div class="blog-card-header">
                    <h3><i class="fas fa-quote-right"></i> Excerpt</h3>
                </div>
                <div class="blog-card-body">
                    <div class="form-section">
                        <textarea id="excerpt" name="excerpt" rows="3" placeholder="A brief summary of your post"><?php echo htmlspecialchars($post['excerpt']); ?></textarea>
                        <div class="form-hint">
                            A short summary of your post. If left empty, it will be generated from the content.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- SEO Card -->
            <div class="blog-card">
                <div class="blog-card-header">
                    <h3><i class="fas fa-search"></i> SEO Settings</h3>
                </div>
                <div class="blog-card-body">
                    <div class="form-section">
                        <label for="meta_title">Meta Title</label>
                        <input type="text" id="meta_title" name="meta_title" 
                            value="<?php echo htmlspecialchars($post['meta_title']); ?>"
                            placeholder="Leave blank to use post title">
                        <div class="form-hint">Recommended length: 50-60 characters</div>
                    </div>
                    
                    <div class="form-section">
                        <label for="meta_description">Meta Description</label>
                        <textarea id="meta_description" name="meta_description" rows="3" 
                            placeholder="Brief description for search engines"><?php echo htmlspecialchars($post['meta_description']); ?></textarea>
                        <div class="form-hint">Recommended length: 150-160 characters</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="blog-form-sidebar">
            <!-- Publish Card -->
            <div class="blog-card">
                <div class="blog-card-header">
                    <h3><i class="fas fa-paper-plane"></i> Publish</h3>
                </div>
                <div class="blog-card-body">
                    <div class="form-section">
                        <label>Status</label>
                        <div class="status-options">
                            <label class="status-option <?php echo $post['status'] === 'draft' ? 'active' : ''; ?>">
                                <input type="radio" name="status" value="draft" <?php echo $post['status'] === 'draft' ? 'checked' : ''; ?> style="display: none;">
                                <i class="fas fa-pencil-alt me-1"></i> Draft
                            </label>
                            <label class="status-option <?php echo $post['status'] === 'published' ? 'active' : ''; ?>">
                                <input type="radio" name="status" value="published" <?php echo $post['status'] === 'published' ? 'checked' : ''; ?> style="display: none;">
                                <i class="fas fa-check-circle me-1"></i> Published
                            </label>
                        </div>
                    </div>
                    
                    <?php if ($post['status'] === 'published' && !empty($post['published_at'])): ?>
                    <div class="form-section">
                        <label>Published Date</label>
                        <input type="text" value="<?php echo date('M d, Y \a\t h:i a', strtotime($post['published_at'])); ?>" readonly disabled>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="save-button">
                        <i class="fas fa-save"></i> Update Blog Post
                    </button>
                </div>
            </div>
            
            <!-- Categories & Tags Card -->
            <div class="blog-card">
                <div class="blog-card-header">
                    <h3><i class="fas fa-tags"></i> Categories & Tags</h3>
                </div>
                <div class="blog-card-body">
                    <div class="form-section">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id">
                            <option value="">-- Select Category --</option>
                            <?php
                            $categories_result->data_seek(0); // Reset result pointer
                            while ($category = $categories_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($post['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-section">
                        <label for="tags">Tags</label>
                        <select id="tags" name="tags[]" multiple>
                            <?php
                            $tags_result->data_seek(0); // Reset result pointer
                            while ($tag = $tags_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $tag['id']; ?>" <?php echo in_array($tag['id'], $selected_tags) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="form-hint">Hold Ctrl (or Cmd on Mac) to select multiple tags</div>
                    </div>
                </div>
            </div>
            
            <!-- Featured Image Card -->
            <div class="blog-card">
                <div class="blog-card-header">
                    <h3><i class="fas fa-image"></i> Featured Image</h3>
                </div>
                <div class="blog-card-body">
                    <?php if (!empty($post['featured_image'])): ?>
                        <div class="image-preview show">
                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="Featured Image">
                            <div class="mt-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="remove_featured_image" name="remove_featured_image" value="yes">
                                    <label class="form-check-label" for="remove_featured_image">Remove this image</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section mt-3">
                            <label>Upload New Image</label>
                            <input type="file" id="featured_image" name="featured_image" accept="image/*">
                        </div>
                    <?php else: ?>
                        <div class="image-upload-area" id="featured-image-uploader">
                            <div style="position: relative; width: 100%; height: 100%;">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <h4>Upload Featured Image</h4>
                                <p>Drop an image here or click to browse</p>
                                <input type="file" id="featured_image" name="featured_image" accept="image/*" style="opacity: 0; position: absolute; top: 0; left: 0; width: 100%; height: 100%; cursor: pointer; z-index: 1;">
                            </div>
                        </div>
                        <div id="image-preview" class="image-preview" style="display: none;">
                            <img src="" alt="Image Preview">
                            <button type="button" class="remove-image" id="remove-image">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-hint mt-2">Recommended size: 1200x800 pixels. Max size: 5MB.</div>
                </div>
            </div>
            
            <!-- Stats Card -->
            <div class="blog-card">
                <div class="blog-card-header">
                    <h3><i class="fas fa-chart-bar"></i> Blog Post Statistics</h3>
                </div>
                <div class="blog-card-body p-0">
                    <ul class="stats-list">
                        <li>
                            <span><i class="fas fa-eye"></i> Views</span>
                            <span class="stats-badge badge-primary"><?php echo number_format($post['views']); ?></span>
                        </li>
                        <li>
                            <span><i class="fas fa-calendar-plus"></i> Created</span>
                            <span class="stats-badge badge-secondary"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                        </li>
                        <li>
                            <span><i class="fas fa-clock"></i> Last Updated</span>
                            <span class="stats-badge badge-info"><?php echo date('M d, Y', strtotime($post['updated_at'])); ?></span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Preview Card -->
            <div class="blog-card">
                <div class="blog-card-header">
                    <h3><i class="fas fa-globe"></i> Preview</h3>
                </div>
                <div class="blog-card-body text-center">
                    <p class="mb-3">See how your post appears:</p>
                    <div class="preview-buttons">
                        <?php if ($post['status'] === 'published'): ?>
                        <a href="blogs/<?php echo htmlspecialchars($post['slug']); ?>" class="btn-view" target="_blank">
                            <i class="fas fa-eye"></i> View Live Blog
                        </a>
                        <?php endif; ?>
                        <a href="blogs/<?php echo htmlspecialchars($post['slug']); ?>?preview=true" class="btn-preview" target="_blank">
                            <i class="fas fa-desktop"></i> Preview Blog
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
    
    // Status toggle functionality
    document.querySelectorAll('.status-option').forEach(option => {
        option.addEventListener('click', function() {
            // Remove active class from all options
            document.querySelectorAll('.status-option').forEach(opt => {
                opt.classList.remove('active');
            });
            
            // Add active class to clicked option
            this.classList.add('active');
            
            // Check the radio button
            this.querySelector('input[type="radio"]').checked = true;
        });
    });
    
    // Image preview
    const featuredImageInput = document.getElementById('featured_image');
    if (featuredImageInput) {
        featuredImageInput.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                const preview = document.getElementById('image-preview');
                if (preview) {
                    const previewImg = preview.querySelector('img');
                    const uploader = document.getElementById('featured-image-uploader');
                    
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        preview.style.display = 'block';
                        if (uploader) {
                            uploader.style.display = 'none';
                        }
                        
                        // Add show class for animation
                        setTimeout(() => {
                            preview.classList.add('show');
                        }, 50);
                    }
                    
                    reader.readAsDataURL(file);
                    
                    // Uncheck the remove featured image checkbox if it was checked
                    if (document.getElementById('remove_featured_image')) {
                        document.getElementById('remove_featured_image').checked = false;
                    }
                }
            }
        });
    }
    
    // Remove image preview button if it exists
    const removeButton = document.getElementById('remove-image');
    if (removeButton) {
        removeButton.addEventListener('click', function() {
            const preview = document.getElementById('image-preview');
            const uploader = document.getElementById('featured-image-uploader');
            const fileInput = document.getElementById('featured_image');
            
            preview.style.display = 'none';
            preview.classList.remove('show');
            if (uploader) {
                uploader.style.display = 'block';
            }
            fileInput.value = '';
        });
    }
    
    // Initialize select2 for tags and category
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
        
        // Hide image preview when remove checkbox is checked
        const removeCheckbox = document.getElementById('remove_featured_image');
        if (removeCheckbox) {
            removeCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    const fileInput = document.getElementById('featured_image');
                    if (fileInput) {
                        fileInput.value = '';
                    }
                }
            });
        }
    });
</script>

<?php include 'bfooter.php'; ?> 