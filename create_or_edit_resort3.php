<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
$user = $_SESSION['user'];
require 'db.php';

// Get destination from GET (either directly or via the resort record)
$destination_id = $_GET['destination_id'] ?? null;
$resort = null;

if (isset($_GET['resort_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM resorts WHERE id = ?");
    $stmt->execute([$_GET['resort_id']]);
    $resort = $stmt->fetch();
    if (!$destination_id && $resort) {
        $destination_id = $resort['destination_id'];
    }
}

// If no destination is provided, display a selection form.
if (!$destination_id) {
    $stmt = $pdo->query("SELECT id, destination_name FROM destinations ORDER BY destination_name");
    $destinations = $stmt->fetchAll();
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
$amenitiesData = [];
$roomsData = [];
$testimonialsData = [];
if ($resort) {
    if (!empty($resort['amenities'])) {
        $amenitiesData = json_decode($resort['amenities'], true) ?? [];
    }
    if (!empty($resort['room_details'])) {
        $roomsData = json_decode($resort['room_details'], true) ?? [];
    }
    if (!empty($resort['testimonials'])) {
        $testimonialsData = json_decode($resort['testimonials'], true) ?? [];
    }
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
        <div class="mb-3">
          <label for="banner_image" class="form-label">Banner Image:</label>
          <input type="file" id="banner_image" name="banner_image" class="form-control" accept=".jpg,.jpeg,.png,.webp" <?php echo $resort ? '' : 'required'; ?>>
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
              foreach ($amenitiesData as $index => $amenity): ?>
              <div class="row mb-2">
                <div class="col-md-6">
                  <input type="text" name="amenities[<?php echo $index; ?>][name]" class="form-control" placeholder="Amenity Name" required value="<?php echo htmlspecialchars($amenity['name']); ?>">
                </div>
                <div class="col-md-6">
                  <input type="file" name="amenities[<?php echo $index; ?>][icon]" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                  <small>Current: <?php echo htmlspecialchars($amenity['icon']); ?></small>
                </div>
              </div>
          <?php endforeach; else: ?>
              <div class="row mb-2">
                <div class="col-md-6">
                  <input type="text" name="amenities[0][name]" class="form-control" placeholder="Amenity Name" required>
                </div>
                <div class="col-md-6">
                  <input type="file" name="amenities[0][icon]" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                </div>
              </div>
          <?php endif; ?>
        </div>
        <button type="button" class="btn btn-secondary mb-3" onclick="addAmenity()">Add Amenity</button>

        <!-- Dynamic Rooms Section -->
        <div class="mb-3" id="rooms">
          <label class="form-label">Rooms:</label>
          <?php if ($resort && !empty($resort['room_details'])):
              $roomsData = json_decode($resort['room_details'], true);
              foreach ($roomsData as $index => $room): ?>
              <div class="row mb-2">
                <div class="col-md-6">
                  <input type="text" name="rooms[<?php echo $index; ?>][name]" class="form-control" placeholder="Room Name" required value="<?php echo htmlspecialchars($room['name']); ?>">
                </div>
                <div class="col-md-6">
                  <input type="file" name="rooms[<?php echo $index; ?>][image]" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                  <small>Current: <?php echo htmlspecialchars($room['image']); ?></small>
                </div>
              </div>
          <?php endforeach; else: ?>
              <div class="row mb-2">
                <div class="col-md-6">
                  <input type="text" name="rooms[0][name]" class="form-control" placeholder="Room Name" required>
                </div>
                <div class="col-md-6">
                  <input type="file" name="rooms[0][image]" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                </div>
              </div>
          <?php endif; ?>
        </div>
        <button type="button" class="btn btn-secondary mb-3" onclick="addRoom()">Add Room</button>

        <!-- Gallery Images -->
        <div class="mb-3">
          <label for="gallery" class="form-label">Gallery Images:</label>
          <input type="file" id="gallery" name="gallery[]" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple <?php echo $resort ? '' : 'required'; ?>>
        </div>

        <!-- Dynamic Testimonials Section -->
        <div class="mb-3" id="testimonials">
          <label class="form-label">Testimonials:</label>
          <?php if ($resort && !empty($resort['testimonials'])):
              $testimonialsData = json_decode($resort['testimonials'], true);
              foreach ($testimonialsData as $index => $testimonial): ?>
              <div class="row mb-2">
                <div class="col-md-4">
                  <input type="text" name="testimonials[<?php echo $index; ?>][name]" class="form-control" placeholder="Name" required value="<?php echo htmlspecialchars($testimonial['name']); ?>">
                </div>
                <div class="col-md-4">
                  <input type="text" name="testimonials[<?php echo $index; ?>][from]" class="form-control" placeholder="Source" required value="<?php echo htmlspecialchars($testimonial['from']); ?>">
                </div>
                <div class="col-md-4">
                  <textarea name="testimonials[<?php echo $index; ?>][content]" class="form-control" placeholder="Testimonial" required><?php echo htmlspecialchars($testimonial['content']); ?></textarea>
                </div>
              </div>
          <?php endforeach; else: ?>
              <div class="row mb-2">
                <div class="col-md-4">
                  <input type="text" name="testimonials[0][name]" class="form-control" placeholder="Name" required>
                </div>
                <div class="col-md-4">
                  <input type="text" name="testimonials[0][from]" class="form-control" placeholder="Source" required>
                </div>
                <div class="col-md-4">
                  <textarea name="testimonials[0][content]" class="form-control" placeholder="Testimonial" required></textarea>
                </div>
              </div>
          <?php endif; ?>
        </div>
        <button type="button" class="btn btn-secondary mb-3" onclick="addTestimonial()">Add Testimonial</button>
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
                        <div class="col-md-6">
                          <input type="file" name="amenities[${index}][icon]" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
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
                        <div class="col-md-6">
                          <input type="file" name="rooms[${index}][image]" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                        </div>`;
      container.appendChild(div);
    }
    function addTestimonial() {
      const container = document.getElementById('testimonials');
      const index = container.querySelectorAll('.row').length;
      const div = document.createElement('div');
      div.className = 'row mb-2';
      div.innerHTML = `<div class="col-md-4">
                          <input type="text" name="testimonials[${index}][name]" class="form-control" placeholder="Name" required>
                        </div>
                        <div class="col-md-4">
                          <input type="text" name="testimonials[${index}][from]" class="form-control" placeholder="Source" required>
                        </div>
                        <div class="col-md-4">
                          <textarea name="testimonials[${index}][content]" class="form-control" placeholder="Testimonial" required></textarea>
                        </div>`;
      container.appendChild(div);
    }
    document.getElementById('toggleSidebar').addEventListener('click', function() {
      var sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('sidebar-collapsed');
    });
  </script>
</body>
</html>
<?php include 'bfooter.php'; ?>
