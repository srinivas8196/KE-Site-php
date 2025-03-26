<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
require 'db.php';

if (!isset($_GET['id'])) {
    echo "Template ID not specified.";
    exit();
}

$templateId = $_GET['id'];

// Fetch the template to edit
$stmt = $pdo->prepare("SELECT * FROM marketing_templates WHERE id = ?");
$stmt->execute([$templateId]);
$template = $stmt->fetch();

if (!$template) {
    echo "Template not found.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $template_name = trim($_POST['template_name']);
    $nights = $_POST['nights'];
    $resort_for_template = trim($_POST['resort_for_template']);
    $button_label = $_POST['button_label'];
    $about_content = trim($_POST['about_content']);
    
    // Process resort_banner image (if new file is uploaded)
    if(isset($_FILES['resort_banner']) && $_FILES['resort_banner']['error'] == 0){
        $resort_banner_tmp = $_FILES['resort_banner']['tmp_name'];
        $resort_banner_name = $_FILES['resort_banner']['name'];
        $templateFolder = "assets/templates";
        move_uploaded_file($resort_banner_tmp, "$templateFolder/$resort_banner_name");
    } else {
        $resort_banner_name = $template['resort_banner'];
    }
    
    // Process about_image
    if(isset($_FILES['about_image']) && $_FILES['about_image']['error'] == 0){
        $about_image_tmp = $_FILES['about_image']['tmp_name'];
        $about_image_name = $_FILES['about_image']['name'];
        $templateFolder = "assets/templates";
        move_uploaded_file($about_image_tmp, "$templateFolder/$about_image_name");
    } else {
        $about_image_name = $template['about_image'];
    }
    
    // For repeater fields, assume JSON input from textareas:
    $amenities = trim($_POST['amenities']);   // JSON string
    $attractions = trim($_POST['attractions']); // JSON string
    
    // Process gallery images if new ones are uploaded; otherwise keep existing gallery
    $templateFolder = "assets/templates";
    $gallery_arr = [];
    if(isset($_FILES['gallery']) && $_FILES['gallery']['error'][0] == 0){
        foreach($_FILES['gallery']['tmp_name'] as $index => $tmpName){
            $file = $_FILES['gallery']['name'][$index];
            move_uploaded_file($tmpName, "$templateFolder/$file");
            $gallery_arr[] = $file;
        }
        $gallery_json = json_encode($gallery_arr);
    } else {
        $gallery_json = $template['gallery'];
    }
    
    $testimonials = trim($_POST['testimonials']); // JSON string
    
    $stmtUpdate = $pdo->prepare("UPDATE marketing_templates 
        SET template_name = ?, nights = ?, resort_for_template = ?, resort_banner = ?, about_image = ?, about_content = ?, amenities = ?, attractions = ?, gallery = ?, testimonials = ?, button_label = ?
        WHERE id = ?");
    $stmtUpdate->execute([$template_name, $nights, $resort_for_template, $resort_banner_name, $about_image_name, $about_content, $amenities, $attractions, $gallery_json, $testimonials, $button_label, $templateId]);
    
    header("Location: marketing_template_list.php");
    exit();
}
?>
<?php include 'bheader.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Marketing Template</title>
  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome for Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    .sidebar-collapsed {
      width: 64px;
    }
    .sidebar-collapsed .sidebar-item-text {
      display: none;
    }
    .sidebar-collapsed .sidebar-icon {
      text-align: center;
    }
  </style>
</head>
<body class="bg-gray-100">
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white shadow-lg transition-all duration-300">
      <div class="p-6 border-b flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800 sidebar-item-text">Admin Dashboard</h1>
        <button id="toggleSidebar" class="text-gray-700 focus:outline-none">
          <i class="fas fa-bars"></i>
        </button>
      </div>
      <nav class="mt-6">
        <a href="dashboard.php" class="block py-3 px-6 text-gray-700 hover:bg-blue-500 hover:text-white transition-colors flex items-center">
          <i class="fas fa-tachometer-alt mr-2 sidebar-icon"></i> <span class="sidebar-item-text">Dashboard</span>
        </a>
        <?php if($user['user_type'] === 'super_admin'): ?>
          <a href="manage_users.php" class="block py-3 px-6 text-gray-700 hover:bg-blue-500 hover:text-white transition-colors flex items-center">
            <i class="fas fa-users mr-2 sidebar-icon"></i> <span class="sidebar-item-text">Manage Users</span>
          </a>
        <?php endif; ?>
        <a href="destination_list.php" class="block py-3 px-6 text-gray-700 hover:bg-blue-500 hover:text-white transition-colors flex items-center">
          <i class="fas fa-map-marker-alt mr-2 sidebar-icon"></i> <span class="sidebar-item-text">Manage Destinations</span>
        </a>
        <a href="resort_list.php" class="block py-3 px-6 text-gray-700 hover:bg-blue-500 hover:text-white transition-colors flex items-center">
          <i class="fas fa-hotel mr-2 sidebar-icon"></i> <span class="sidebar-item-text">Manage Resorts</span>
        </a>
        <a href="marketing_template_list.php" class="block py-3 px-6 text-gray-700 hover:bg-blue-500 hover:text-white transition-colors flex items-center">
          <i class="fas fa-envelope-open-text mr-2 sidebar-icon"></i> <span class="sidebar-item-text">Marketing Templates</span>
        </a>
        <a href="campaign_dashboard.php" class="block py-3 px-6 text-gray-700 hover:bg-blue-500 hover:text-white transition-colors flex items-center">
          <i class="fas fa-bullhorn mr-2 sidebar-icon"></i> <span class="sidebar-item-text">Campaign Dashboard</span>
        </a>
        <a href="logout.php" class="block py-3 px-6 text-red-500 hover:bg-red-500 hover:text-white transition-colors flex items-center">
          <i class="fas fa-sign-out-alt mr-2 sidebar-icon"></i> <span class="sidebar-item-text">Logout</span>
        </a>
      </nav>
    </aside>
    <!-- Main Content -->
    <main class="flex-1 p-8">
      <!-- Breadcrumb -->
      <nav class="mb-4 text-sm text-gray-600" aria-label="Breadcrumb">
        <ol class="list-reset flex">
          <li><a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a></li>
          <li><span class="mx-2">/</span></li>
          <li><a href="marketing_template_list.php" class="text-blue-600 hover:underline">Marketing Templates</a></li>
          <li><span class="mx-2">/</span></li>
          <li class="text-gray-600">Edit Template</li>
        </ol>
      </nav>
      <h2 class="text-3xl font-bold mb-6">Edit Marketing Template</h2>
      <form action="edit_marketing_template.php?id=<?php echo $templateId; ?>" method="post" enctype="multipart/form-data">
        <div class="mb-3">
          <label for="template_name" class="form-label">Template Name</label>
          <input type="text" id="template_name" name="template_name" class="form-control" required value="<?php echo htmlspecialchars($template['template_name']); ?>">
        </div>
        <div class="mb-3">
          <label for="nights" class="form-label">Number of Nights</label>
          <select id="nights" name="nights" class="form-select" required>
            <option value="" disabled>Select</option>
            <option value="3 Nights" <?php if($template['nights'] == "3 Nights") echo "selected"; ?>>3 Nights</option>
            <option value="4 Nights" <?php if($template['nights'] == "4 Nights") echo "selected"; ?>>4 Nights</option>
            <option value="7 Nights" <?php if($template['nights'] == "7 Nights") echo "selected"; ?>>7 Nights</option>
          </select>
        </div>
        <div class="mb-3">
          <label for="resort_for_template" class="form-label">Resort (for which this template is created)</label>
          <input type="text" id="resort_for_template" name="resort_for_template" class="form-control" required value="<?php echo htmlspecialchars($template['resort_for_template']); ?>">
        </div>
        <div class="mb-3">
          <label for="resort_banner" class="form-label">Resort Banner Image</label>
          <input type="file" id="resort_banner" name="resort_banner" class="form-control" accept=".jpg,.jpeg,.png,.webp">
          <small>Leave blank to keep existing image.</small>
        </div>
        <div class="mb-3">
          <label for="about_image" class="form-label">About Image</label>
          <input type="file" id="about_image" name="about_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
          <small>Leave blank to keep existing image.</small>
        </div>
        <div class="mb-3">
          <label for="about_content" class="form-label">About Content</label>
          <textarea id="about_content" name="about_content" class="form-control" required><?php echo htmlspecialchars($template['about_content']); ?></textarea>
        </div>
        <div class="mb-3">
          <label for="amenities" class="form-label">Amenities (JSON)</label>
          <textarea id="amenities" name="amenities" class="form-control" required><?php echo htmlspecialchars($template['amenities']); ?></textarea>
        </div>
        <div class="mb-3">
          <label for="attractions" class="form-label">Attractions (JSON)</label>
          <textarea id="attractions" name="attractions" class="form-control" required><?php echo htmlspecialchars($template['attractions']); ?></textarea>
        </div>
        <div class="mb-3">
          <label for="gallery" class="form-label">Gallery Images</label>
          <input type="file" id="gallery" name="gallery[]" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple>
          <small>Leave blank to keep existing gallery.</small>
        </div>
        <div class="mb-3">
          <label for="testimonials" class="form-label">Testimonials (JSON)</label>
          <textarea id="testimonials" name="testimonials" class="form-control" required><?php echo htmlspecialchars($template['testimonials']); ?></textarea>
        </div>
        <div class="mb-3">
          <label for="button_label" class="form-label">Button Label</label>
          <select id="button_label" name="button_label" class="form-select" required>
            <option value="" disabled>Select</option>
            <option value="Book Now" <?php if($template['button_label'] == 'Book Now') echo 'selected'; ?>>Book Now</option>
            <option value="Enquire Now" <?php if($template['button_label'] == 'Enquire Now') echo 'selected'; ?>>Enquire Now</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">Update Template</button>
      </form>
    </main>
  </div>
  <script>
    document.getElementById('toggleSidebar').addEventListener('click', function() {
      var sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('sidebar-collapsed');
    });
  </script>
</body>
</html>
<?php include 'bfooter.php'; ?>
