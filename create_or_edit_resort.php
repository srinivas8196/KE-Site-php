<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
$user = $_SESSION['user'];
require 'db.php';

if (!isset($conn)) {
    die("Database connection not established. Please check your db.php file.");
}

$destination_id = $_GET['destination_id'] ?? null;
$resort = null;

if (isset($_GET['resort_id'])) {
    // Replace MySQLi with PDO
    $stmt = $conn->prepare("SELECT * FROM resorts WHERE id = ?");
    $stmt->execute([$_GET['resort_id']]);
    $resort = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get storage URLs for images
    if ($resort && $resort['banner_image']) {
        $resort['banner_url'] = 'assets/resorts/' . $resort['resort_slug'] . '/' . $resort['banner_image'];
    }
}

// Get destinations for dropdown
$destinations = [];
$stmt = $conn->query("SELECT id, destination_name FROM destinations ORDER BY destination_name");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $destinations[] = $row;
}

// Get current destination name
$current_destination_name = '';
if ($destination_id) {
    $stmt = $conn->prepare("SELECT destination_name FROM destinations WHERE id = ?");
    $stmt->execute([$destination_id]);
    $dest_result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($dest_result) {
        $current_destination_name = htmlspecialchars($dest_result['destination_name']);
    }
}

// If no destination is provided, display a selection form.
if (!$destination_id) {
    include 'bheader.php';
    ?>
    <div class="container mt-5">
        <h2 class="text-xl font-bold mb-4">Select Destination for Resort</h2>
        <form action="create_or_edit_resort.php" method="get">
            <div class="mb-4">
                <label for="destination_id" class="block text-sm font-medium text-gray-700">Destination:</label>
                <select id="destination_id" name="destination_id" class="form-select mt-1 block w-full" required>
                    <option value="">-- Select Destination --</option>
                    <?php foreach ($destinations as $dest): ?>
                        <option value="<?php echo $dest['id']; ?>"><?php echo htmlspecialchars($dest['destination_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">Continue</button>
        </form>
    </div>
    <?php
    include 'bfooter.php';
    exit();
}

// Decode dynamic JSON fields if editing
if ($resort) {
    $amenitiesData = $resort['amenities'] ? json_decode($resort['amenities'], true) : [];
    $roomsData = $resort['room_details'] ? json_decode($resort['room_details'], true) : [];
    $testimonialsData = $resort['testimonials'] ? json_decode($resort['testimonials'], true) : [];
    $galleryData = $resort['gallery'] ? json_decode($resort['gallery'], true) : [];
} else {
    $amenitiesData = [];
    $roomsData = [];
    $testimonialsData = [];
    $galleryData = [];
}

include 'bheader.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $resort ? "Edit Resort" : "Create New Resort"; ?></title>
  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome for Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    .sidebar-collapsed { width: 64px; }
    .sidebar-collapsed .sidebar-item-text { display: none; }
    .sidebar-collapsed .sidebar-icon { text-align: center; }
    .gallery-preview img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
    }
    .gallery-preview {
        position: relative;
        transition: all 0.3s ease;
    }
    .gallery-preview:hover {
        transform: translateY(-2px);
    }
    .current-gallery {
        margin-bottom: 20px;
    }
    .gallery-preview-container, .room-preview-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }
    .gallery-preview-item, .room-preview-item {
        position: relative;
        aspect-ratio: 1;
        overflow: hidden;
        border-radius: 8px;
    }
    .gallery-preview-item img, .room-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .gallery-preview-overlay, .room-preview-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s;
    }
    .gallery-preview-item:hover .gallery-preview-overlay,
    .room-preview-item:hover .room-preview-overlay {
        opacity: 1;
    }
    .room-detail-item {
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 8px;
    }
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
    }
    .alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
    }
    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
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
        <?php if ($user['user_type'] === 'super_admin'): ?>
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
    <main class="flex-1 p-8 pb-32">

      <!-- Breadcrumb -->
      <nav class="mb-4 text-sm text-gray-600" aria-label="Breadcrumb">
        <ol class="list-reset flex">
          <li><a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a></li>
          <li><span class="mx-2">/</span></li>
          <li><a href="destination.php?id=<?php echo $destination_id; ?>" class="text-blue-600 hover:underline">Destination</a></li>
          <li><span class="mx-2">/</span></li>
          <li class="text-gray-600"><?php echo $resort ? "Edit Resort" : "Create New Resort"; ?></li>
        </ol>
      </nav>
      <h2 class="text-3xl font-bold mb-6"><?php echo $resort ? "Edit Resort" : "Create New Resort"; ?></h2>

      <div class="mb-4">
          <p>Adding resort to destination: <strong><?php echo $current_destination_name; ?></strong>
          <a href="create_or_edit_resort.php" class="ml-2 text-blue-500 hover:underline">(Change Destination)</a></p>
      </div>

      <form action="save_resort.php" method="post" enctype="multipart/form-data">
        <?php if ($resort): ?>
          <input type="hidden" name="resort_id" value="<?php echo $resort['id']; ?>">
        <?php endif; ?>
        <input type="hidden" name="destination_id" value="<?php echo $destination_id; ?>">
        <div class="mb-3">
          <label for="resort_name" class="form-label">Resort Name:</label>
          <input type="text" id="resort_name" name="resort_name" class="form-control" required value="<?php echo $resort ? htmlspecialchars($resort['resort_name']) : ''; ?>">
        </div>
        <div class="mb-3">
          <label for="resort_description" class="form-label">Resort Description:</label>
          <textarea id="resort_description" name="resort_description" class="form-control" required><?php echo $resort ? htmlspecialchars($resort['resort_description']) : ''; ?></textarea>
        </div>
        <div class="mb-3">
          <label for="banner_title" class="form-label">Banner Title:</label>
          <input type="text" id="banner_title" name="banner_title" class="form-control" required value="<?php echo $resort ? htmlspecialchars($resort['banner_title']) : ''; ?>">
        </div>
        <div class="form-group">
            <label for="banner_image">Banner Image</label>
            <?php if(isset($resort['banner_image']) && !empty($resort['banner_image'])): ?>
                <div class="current-image">
                    <img src="assets/resorts/<?php echo $resort['resort_slug']; ?>/<?php echo htmlspecialchars($resort['banner_image']); ?>" alt="Current Banner" style="max-width: 200px;">
                    <input type="hidden" name="existing_banner_image" value="<?php echo htmlspecialchars($resort['banner_image']); ?>">
                </div>
            <?php endif; ?>
            <input type="file" class="form-control" id="banner_image" name="banner_image" accept="image/*">
        </div>
        <!-- Resort Type -->
        <div class="mb-3">
          <label for="resort_type" class="form-label">Resort Type:</label>
          <select id="resort_type" name="resort_type" class="form-select" required>
            <option value="" selected disabled>Select</option>
            <option value="resort" <?php if ($resort && $resort['is_partner'] == 0) echo 'selected'; ?>>Resort</option>
            <option value="partner" <?php if ($resort && $resort['is_partner'] == 1) echo 'selected'; ?>>Partner Hotel</option>
          </select>
        </div>
        <!-- Active Status -->
        <div class="mb-3">
          <label class="form-label">Active Status:</label>
          <div class="flex items-center">
            <input type="checkbox" id="is_active" name="is_active" class="mr-2" <?php echo ($resort && $resort['is_active'] == 1) ? 'checked' : ''; ?>>
            <label for="is_active" class="form-label">Active</label>
          </div>
        </div>
        <!-- Dynamic Amenities Section -->
        <div class="mb-3" id="amenities">
          <label class="form-label">Amenities:</label>
          <?php if ($resort && !empty($resort['amenities'])):
              $amenitiesData = json_decode($resort['amenities'], true);
              $amenitiesCount = count($amenitiesData); // Count amenities
              foreach ($amenitiesData as $index => $amenity): ?>
              <div class="row mb-2">
                <div class="col-md-6">
                  <input type="text" name="amenities[<?php echo $index; ?>][name]" class="form-control" placeholder="Amenity Name" required value="<?php echo htmlspecialchars($amenity['name']); ?>">
                </div>
                <div class="col-md-4">
                  <input type="file" name="amenities[<?php echo $index; ?>][icon]" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                  <small>Current: <?php echo htmlspecialchars($amenity['icon']); ?></small>
                  <!-- Hidden field to retain existing icon -->
                  <input type="hidden" name="amenities[<?php echo $index; ?>][existing_icon]" value="<?php echo htmlspecialchars($amenity['icon']); ?>">
                </div>
                <div class="col-md-2">
                  <?php if ($amenitiesCount > 1): // Conditionally show delete button ?>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeElement(this)">Delete</button>
                  <?php endif; ?>
                </div>
              </div>
          <?php endforeach; else: ?>
              <div class="row mb-2">
                <div class="col-md-6">
                  <input type="text" name="amenities[0][name]" class="form-control" placeholder="Amenity Name" required>
                </div>
                <div class="col-md-4">
                  <input type="file" name="amenities[0][icon]" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                </div>
                <div class="col-md-2">
                    <!-- Delete button not shown when adding first amenity -->
                </div>
              </div>
          <?php endif; ?>
        </div>
        <button type="button" class="btn btn-secondary mb-3" onclick="addAmenity()">Add Amenity</button>

        <!-- Dynamic Rooms Section -->
        <div class="form-group">
            <label>Room Details</label>
            <div id="roomDetailsContainer">
                <?php if (!empty($resort['room_details'])): 
                    $rooms = json_decode($resort['room_details'], true);
                    if (is_array($rooms)):
                        foreach ($rooms as $index => $room): ?>
                            <div class="room-detail-item">
                                <div class="room-preview-container">
                                    <?php if (!empty($room['image'])): ?>
                                        <div class="room-preview-item">
                                            <img src="<?php echo $resortFolder . '/' . htmlspecialchars($room['image']); ?>" alt="Room Image">
                                            <div class="room-preview-overlay">
                                                <button type="button" class="btn btn-danger btn-sm delete-room-image" data-index="<?php echo $index; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                    <?php endif; ?>
                                </div>
                                <input type="text" name="rooms[<?php echo $index; ?>][name]" class="form-control" value="<?php echo htmlspecialchars($room['name']); ?>" placeholder="Room Name">
                                <input type="file" name="rooms[<?php echo $index; ?>][image]" class="form-control" accept="image/*" onchange="previewRoomImage(this, <?php echo $index; ?>)">
                                <button type="button" class="btn btn-danger remove-room">Remove Room</button>
                            </div>
                        <?php endforeach;
                    endif;
                endif; ?>
            </div>
            <button type="button" class="btn btn-success" onclick="addRoomDetail()">Add Room</button>
        </div>

        <!-- Gallery Images -->
        <div class="form-group">
            <label>Gallery Images</label>
            <div class="gallery-preview-container">
                <?php if (!empty($resort['gallery'])): 
                    $gallery = json_decode($resort['gallery'], true);
                    if (is_array($gallery)):
                        foreach ($gallery as $index => $image): ?>
                            <div class="gallery-preview-item">
                                <img src="<?php echo $resortFolder . '/' . htmlspecialchars($image); ?>" alt="Gallery Image">
                                <div class="gallery-preview-overlay">
                                    <button type="button" class="btn btn-danger btn-sm delete-gallery-image" data-index="<?php echo $index; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <input type="hidden" name="existing_gallery[]" value="<?php echo htmlspecialchars($image); ?>">
                            </div>
                        <?php endforeach;
                    endif;
                endif; ?>
            </div>
            <input type="file" name="gallery[]" class="form-control" multiple accept="image/*" onchange="previewGalleryImages(this)">
        </div>

        <!-- Dynamic Testimonials Section -->
        <div class="mb-3" id="testimonials">
          <label class="form-label">Testimonials:</label>
          <?php 
          // Initialize testimonials array
          $testimonialsData = [];
          if ($resort && !empty($resort['testimonials'])) {
              $testimonialsData = json_decode($resort['testimonials'], true) ?? [];
          }
          
          // Always show at least one testimonial form
          if (empty($testimonialsData)) {
              $testimonialsData = [['name' => '', 'from' => '', 'content' => '']];
          }
          
          foreach ($testimonialsData as $index => $testimonial): ?>
              <div class="row mb-2">
                <div class="col-md-4">
                  <input type="text" name="testimonials[<?php echo $index; ?>][name]" class="form-control" placeholder="Name" required value="<?php echo htmlspecialchars($testimonial['name'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                  <input type="text" name="testimonials[<?php echo $index; ?>][from]" class="form-control" placeholder="Source" required value="<?php echo htmlspecialchars($testimonial['from'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                  <textarea name="testimonials[<?php echo $index; ?>][content]" class="form-control" placeholder="Testimonial" required><?php echo htmlspecialchars($testimonial['content'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-1">
                  <?php if (count($testimonialsData) > 1): ?>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeElement(this)">Delete</button>
                  <?php endif; ?>
                </div>
              </div>
          <?php endforeach; ?>
          <!-- Add new testimonial button -->
          <button type="button" class="btn btn-secondary mb-3" onclick="addTestimonial()">Add Testimonial</button>
        </div>
        <br><br>
        <button type="submit" class="btn btn-primary"><?php echo $resort ? "Update Resort" : "Create Resort"; ?></button>
      </form>
    </main>
  </div>
  <script>
    function addAmenity() {
      const container = document.getElementById('amenities');
      const index = container.querySelectorAll('.row').length;
      const div = document.createElement('div');
      div.className = 'row mb-2';
      div.innerHTML = `<div class="col-md-6">
                          <input type="text" name="amenities[${index}][name]" class="form-control" placeholder="Amenity Name" required>
                        </div>
                        <div class="col-md-4">
                          <input type="file" name="amenities[${index}][icon]" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                        </div>
                        <div class="col-md-2">
                          <button type="button" class="btn btn-danger btn-sm" onclick="removeElement(this)">Delete</button>
                        </div>`;
      container.appendChild(div);
    }
    function addRoom() {
      const container = document.getElementById('rooms');
      const index = container.querySelectorAll('.row').length;
      const div = document.createElement('div');
      div.className = 'row mb-2';
      div.innerHTML = `<div class="col-md-6">
                          <input type="text" name="rooms[${index}][name]" class="form-control" placeholder="Room Name" required>
                        </div>
                        <div class="col-md-4">
                          <input type="file" name="rooms[${index}][image]" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                        </div>
                        <div class="col-md-2">
                          <button type="button" class="btn btn-danger btn-sm" onclick="removeElement(this)">Delete</button>
                        </div>`;
      container.appendChild(div);
    }
    function addTestimonial() {
      const container = document.getElementById('testimonials');
      const index = container.querySelectorAll('.row').length;
      const div = document.createElement('div');
      div.className = 'row mb-2';
      div.innerHTML = `
        <div class="col-md-4">
          <input type="text" name="testimonials[${index}][name]" class="form-control" placeholder="Name" required>
        </div>
        <div class="col-md-4">
          <input type="text" name="testimonials[${index}][from]" class="form-control" placeholder="Source" required>
        </div>
        <div class="col-md-3">
          <textarea name="testimonials[${index}][content]" class="form-control" placeholder="Testimonial" required></textarea>
        </div>
        <div class="col-md-1">
          <button type="button" class="btn btn-danger btn-sm" onclick="removeElement(this)">Delete</button>
        </div>`;
      container.insertBefore(div, container.querySelector('button.btn-secondary'));
    }
    function removeElement(button) {
      button.closest('.row').remove();
    }
    document.getElementById('toggleSidebar').addEventListener('click', function() {
      var sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('sidebar-collapsed');
    });

    function previewGalleryImages(input) {
        const container = document.querySelector('.gallery-preview-container');
        if (input.files) {
            for (let i = 0; i < input.files.length; i++) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'gallery-preview-item';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <div class="gallery-preview-overlay">
                            <button type="button" class="btn btn-danger btn-sm delete-gallery-image">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                    container.appendChild(div);
                    
                    // Add delete functionality to new preview
                    div.querySelector('.delete-gallery-image').addEventListener('click', function() {
                        if (confirm('Are you sure you want to delete this image?')) {
                            div.remove();
                        }
                    });
                }
                reader.readAsDataURL(input.files[i]);
            }
        }
    }

    function previewRoomImage(input, index) {
        const container = input.closest('.room-detail-item').querySelector('.room-preview-container');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Remove existing preview if any
                container.innerHTML = '';
                
                const div = document.createElement('div');
                div.className = 'room-preview-item';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <div class="room-preview-overlay">
                        <button type="button" class="btn btn-danger btn-sm delete-room-image">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                container.appendChild(div);
                
                // Add delete functionality to new preview
                div.querySelector('.delete-room-image').addEventListener('click', function() {
                    if (confirm('Are you sure you want to delete this image?')) {
                        div.remove();
                    }
                });
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Add this at the beginning of your script section
    document.addEventListener('DOMContentLoaded', function() {
        // Handle gallery image deletion
        document.querySelectorAll('.delete-gallery-image').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this image?')) {
                    this.closest('.gallery-preview-item').remove();
                }
            });
        });

        // Handle room image deletion
        document.querySelectorAll('.delete-room-image').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this image?')) {
                    this.closest('.room-preview-item').remove();
                }
            });
        });
    });
  </script>
</body>
</html>
<?php include 'bfooter.php'; ?>
