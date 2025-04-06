<?php
session_start();

// Include auth helper
require_once 'auth_helper.php';

// Check if user has admin or higher permission
requirePermission('admin', 'login.php');

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
    // Debug form submission
    error_log("Form submitted: " . json_encode($_POST));
    
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
    
    // Debug post content
    error_log("Content length: " . strlen($content));
    
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
            // Insert blog post - modified to remove author_id from query
            $insert_query = "INSERT INTO blog_posts (title, slug, content, excerpt, featured_image, meta_title, meta_description, category_id, status, published_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("sssssssiss", $title, $slug, $content, $excerpt, $featured_image, $meta_title, $meta_description, $category_id, $status, $published_at);
            
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

$page_title = "Create New Blog Post";
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
</style>

<!-- Include our custom CSS file -->
<link rel="stylesheet" href="assets/css/admin-blog.css">

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
                <h1 class="h3 mb-0">Create New Blog Post</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="#" id="preview-draft-button" class="btn btn-sm btn-primary" style="display: none;">
                    <i class="fas fa-desktop me-2"></i> Preview Draft
                </a>
                <a href="admin_blog.php" class="btn btn-secondary">
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

        <form action="admin_blog_create.php" method="POST" enctype="multipart/form-data" class="blog-form">
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
                                value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                                placeholder="Enter blog title">
                        </div>
                        
                        <div class="form-section">
                            <label for="slug">URL Slug</label>
                            <input type="text" id="slug" name="slug" 
                                value="<?php echo isset($_POST['slug']) ? htmlspecialchars($_POST['slug']) : ''; ?>"
                                placeholder="Leave blank to auto-generate from title">
                            <div class="form-hint">
                                Use lowercase letters, numbers, and hyphens. No spaces or special characters.
                                <br>The slug is needed to preview your draft before saving.
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
                        <textarea id="content" name="content" rows="15" required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                    </div>
                </div>

                <!-- Excerpt Card -->
                <div class="blog-card">
                    <div class="blog-card-header">
                        <h3><i class="fas fa-quote-right"></i> Excerpt</h3>
                    </div>
                    <div class="blog-card-body">
                        <div class="form-section">
                            <textarea id="excerpt" name="excerpt" rows="3" placeholder="A brief summary of your post"><?php echo isset($_POST['excerpt']) ? htmlspecialchars($_POST['excerpt']) : ''; ?></textarea>
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
                                value="<?php echo isset($_POST['meta_title']) ? htmlspecialchars($_POST['meta_title']) : ''; ?>"
                                placeholder="Leave blank to use post title">
                            <div class="form-hint">Recommended length: 50-60 characters</div>
                        </div>
                        
                        <div class="form-section">
                            <label for="meta_description">Meta Description</label>
                            <textarea id="meta_description" name="meta_description" rows="3" 
                                placeholder="Brief description for search engines"><?php echo isset($_POST['meta_description']) ? htmlspecialchars($_POST['meta_description']) : ''; ?></textarea>
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
                                <label class="status-option <?php echo (!isset($_POST['status']) || $_POST['status'] === 'draft') ? 'active' : ''; ?>">
                                    <input type="radio" name="status" value="draft" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'draft') ? 'checked' : ''; ?> style="display: none;">
                                    <i class="fas fa-pencil-alt me-1"></i> Draft
                                </label>
                                <label class="status-option <?php echo (isset($_POST['status']) && $_POST['status'] === 'published') ? 'active' : ''; ?>">
                                    <input type="radio" name="status" value="published" <?php echo (isset($_POST['status']) && $_POST['status'] === 'published') ? 'checked' : ''; ?> style="display: none;">
                                    <i class="fas fa-check-circle me-1"></i> Published
                                </label>
                            </div>
                        </div>
                        
                        <!-- Added disabled attribute that will be removed by JavaScript once form is validated -->
                        <button type="submit" id="submitBtn" class="save-button">
                            <i class="fas fa-save"></i> Save Blog Post
                        </button>
                        
                        <!-- Debug info section -->
                        <div class="mt-3 p-2 border rounded" style="font-size: 12px; background: #f8f9fa;">
                            <div><strong>Form Submission Debug:</strong></div>
                            <div id="debug-info">Not submitted yet</div>
                        </div>
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
                                <?php while ($category = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-section">
                            <label for="tags">Tags</label>
                            <select id="tags" name="tags[]" multiple>
                                <?php while ($tag = $tags_result->fetch_assoc()): ?>
                                    <option value="<?php echo $tag['id']; ?>" <?php echo (isset($_POST['tags']) && in_array($tag['id'], $_POST['tags'])) ? 'selected' : ''; ?>>
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
                        <div class="form-hint mt-2">Recommended size: 1200x800 pixels. Max size: 5MB.</div>
                    </div>
                </div>
            </div>
        </form>
    </main>
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
        api_key: '2dcx752ng7via2ayk5f144ggv0lz4w4gxhihr5vynet3ru4f',
        setup: function(editor) {
            editor.on('change', function() {
                tinymce.triggerSave(); // Save content to textarea on each change
                console.log('Editor content updated');
            });
        }
    });
    
    // Add form submission handler to ensure TinyMCE content is saved
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form.blog-form');
        const debugInfo = document.getElementById('debug-info');
        
        // Update debug info with key form fields
        function updateDebugInfo() {
            const title = document.getElementById('title').value;
            const content = document.getElementById('content').value;
            const contentLength = content ? content.length : 0;
            
            debugInfo.innerHTML = 
                `Title: ${title}<br>` +
                `Content length: ${contentLength} characters<br>` + 
                `Category: ${document.getElementById('category_id').value}<br>` +
                `Status: ${document.querySelector('input[name="status"]:checked').value}`;
        }
        
        // Update debug info periodically
        setInterval(updateDebugInfo, 2000);
        
        // Add input handlers for all form fields
        document.getElementById('title').addEventListener('input', updateDebugInfo);
        document.getElementById('category_id').addEventListener('change', updateDebugInfo);
        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', updateDebugInfo);
        });
        
        form.addEventListener('submit', function(e) {
            // Prevent the default submission
            e.preventDefault();
            
            // Make sure TinyMCE content is saved to textarea
            tinymce.triggerSave();
            
            // Debug information
            console.log('Form submitted');
            debugInfo.innerHTML = "Form is being submitted...";
            
            const title = document.getElementById('title').value.trim();
            const content = document.getElementById('content').value.trim();
            
            // Check if title and content are provided
            if (!title) {
                alert('Please enter a title');
                return false;
            }
            
            if (!content) {
                alert('Please enter content for the blog post');
                return false;
            }
            
            // Show submitting state
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;
            
            // Actually submit the form now
            this.submit();
        });
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
    document.getElementById('featured_image').addEventListener('change', function(e) {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            const preview = document.getElementById('image-preview');
            const previewImg = preview.querySelector('img');
            const uploader = document.getElementById('featured-image-uploader');
            
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
                uploader.style.display = 'none';
                
                // Add show class for animation
                setTimeout(() => {
                    preview.classList.add('show');
                }, 50);
            }
            
            reader.readAsDataURL(file);
        }
    });
    
    // Remove image preview
    document.getElementById('remove-image').addEventListener('click', function() {
        const preview = document.getElementById('image-preview');
        const uploader = document.getElementById('featured-image-uploader');
        const fileInput = document.getElementById('featured_image');
        
        preview.style.display = 'none';
        preview.classList.remove('show');
        uploader.style.display = 'block';
        fileInput.value = '';
    });
    
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
    });
    
    // Preview draft functionality
    function updatePreviewButton() {
        const titleField = document.getElementById('title');
        const slugField = document.getElementById('slug');
        const previewButton = document.getElementById('preview-draft-button');
        
        if (titleField.value && slugField.value) {
            previewButton.style.display = 'inline-flex';
            
            // Generate a temporary slug if empty
            if (!slugField.value) {
                const titleValue = titleField.value.toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim();
                slugField.value = titleValue;
            }
            
            // Update the preview URL
            previewButton.href = 'blogs/' + slugField.value + '?preview=draft';
        } else {
            previewButton.style.display = 'none';
        }
    }
    
    // Add event listeners for title and slug fields
    document.getElementById('title').addEventListener('input', updatePreviewButton);
    document.getElementById('slug').addEventListener('input', updatePreviewButton);
    
    // Initial check on page load
    document.addEventListener('DOMContentLoaded', updatePreviewButton);
</script>

<?php include 'bfooter.php'; ?> 
