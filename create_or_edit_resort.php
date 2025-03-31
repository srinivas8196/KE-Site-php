<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
$user = $_SESSION['user'];
require 'db.php';

if (!isset($pdo)) {
    die("Database connection not established. Please check your db.php file.");
}

$destination_id = $_GET['destination_id'] ?? null;
$resort = null;

if (isset($_GET['resort_id'])) {
    // Replace MySQLi with PDO
    $stmt = $pdo->prepare("SELECT * FROM resorts WHERE id = ?");
    $stmt->execute([$_GET['resort_id']]);
    $resort = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get storage URLs for images
    if ($resort && $resort['banner_image']) {
        $resort['banner_url'] = 'assets/resorts/' . $resort['resort_slug'] . '/' . $resort['banner_image'];
    }
}

// Get destinations for dropdown
$destinations = [];
$stmt = $pdo->query("SELECT id, destination_name FROM destinations ORDER BY destination_name");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $destinations[] = $row;
}

// Get current destination name
$current_destination_name = '';
if ($destination_id) {
    $stmt = $pdo->prepare("SELECT destination_name FROM destinations WHERE id = ?");
    $stmt->execute([$destination_id]);
    $dest_result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($dest_result) {
        $current_destination_name = htmlspecialchars($dest_result['destination_name']);
    }
}

// If no destination is provided, display a selection form.
if (!$destination_id) {
    // Include header with error checking
    if (file_exists('bheader.php')) {
        include 'bheader.php';
    } else {
        // If header doesn't exist, show a simple admin header
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Resort Management</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
                <div class="container">
                    <a class="navbar-brand" href="dashboard.php">Admin Dashboard</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="resort_list.php">Resorts</a></li>
                            <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </nav>
        ';
    }
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
    // Bottom of file - include footer with error checking
    if (file_exists('bfooter.php')) {
        include 'bfooter.php';
    } else {
        // If footer doesn't exist, show a simple admin footer
        echo '    <footer class="bg-dark text-white py-3 mt-5">
            <div class="container text-center">
                <p class="mb-0">&copy; ' . date('Y') . ' KE Resorts. All rights reserved.</p>
            </div>
        </footer>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>';
    }
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

// Include header with error checking
if (file_exists('bheader.php')) {
    include 'bheader.php';
} else {
    // If header doesn't exist, show a simple admin header
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Resort Management</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
            <div class="container">
                <a class="navbar-brand" href="dashboard.php">Admin Dashboard</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="resort_list.php">Resorts</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    ';
}
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
    .room-preview-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 15px;
    }
    .room-preview-item {
        position: relative;
        aspect-ratio: 16/9;
        overflow: hidden;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .room-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    .room-preview-item:hover img {
        transform: scale(1.05);
    }
    .room-detail-item {
        border: 1px solid #e2e8f0;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        background: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .room-preview-overlay {
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
    .room-preview-item:hover .room-preview-overlay {
        opacity: 1;
    }
    .delete-room-image {
        background: rgba(220, 53, 69, 0.9);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        transition: background 0.3s;
    }
    .delete-room-image:hover {
        background: rgba(220, 53, 69, 1);
    }
    .room-inputs {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #e2e8f0;
    }
    .remove-room {
        background: #dc3545;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        transition: background 0.3s;
    }
    .remove-room:hover {
        background: #c82333;
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
    .form-section {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 32px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
    }
    
    .form-section-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1a202c;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-group label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #4a5568;
        margin-bottom: 8px;
    }
    
    .form-control {
        width: 100%;
        padding: 10px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.875rem;
        transition: all 0.2s;
    }
    
    .form-control:focus {
        border-color: #4299e1;
        box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.15);
        outline: none;
    }
    
    .form-select {
        width: 100%;
        padding: 10px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.875rem;
        background-color: white;
        transition: all 0.2s;
    }
    
    .form-select:focus {
        border-color: #4299e1;
        box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.15);
        outline: none;
    }
    
    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary {
        background-color: #4299e1;
        color: white;
        border: none;
    }
    
    .btn-primary:hover {
        background-color: #3182ce;
    }
    
    .btn-success {
        background-color: #48bb78;
        color: white;
        border: none;
    }
    
    .btn-success:hover {
        background-color: #38a169;
    }
    
    .btn-danger {
        background-color: #f56565;
        color: white;
        border: none;
    }
    
    .btn-danger:hover {
        background-color: #e53e3e;
    }
    
    .btn-secondary {
        background-color: #718096;
        color: white;
        border: none;
    }
    
    .btn-secondary:hover {
        background-color: #4a5568;
    }
    
    .current-image {
        margin-bottom: 16px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .current-image img {
        max-width: 200px;
        border-radius: 8px;
    }

    .resort-details-container {
        padding: 40px 0;
        position: relative;
    }

    .sticky-form-container {
        position: sticky;
        top: 100px; /* Adjust this value to control the distance from the top */
        margin-bottom: 20px;
        z-index: 100;
    }

    .resort-form-container {
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .resort-content-left {
        padding-right: 30px;
    }

    @media (max-width: 991px) {
        .sticky-form-container {
            position: relative;
            top: 0;
            margin-top: 30px;
        }
        
        .resort-content-left {
            padding-right: 15px;
        }
    }
  </style>
</head>
<body class="bg-gray-100">
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
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

      <div class="container resort-details-container section-padding">
        <div class="row">
          <!-- Resort Details -->
          <div class="col-12">
            <form action="/KE-Site-php/save_resort.php" method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
              <?php if ($resort): ?>
                <input type="hidden" name="resort_id" value="<?php echo $resort['id']; ?>">
              <?php endif; ?>
              <input type="hidden" name="destination_id" value="<?php echo $destination_id; ?>">
              <input type="hidden" name="no_rewrite" value="1">
              
              <!-- Basic Information Section -->
              <div class="form-section">
                  <h3 class="form-section-title">Basic Information</h3>
                  <div class="form-group">
                      <label for="resort_name">Resort Name</label>
                      <input type="text" id="resort_name" name="resort_name" class="form-control" required value="<?php echo $resort ? htmlspecialchars($resort['resort_name']) : ''; ?>">
                  </div>
                  <div class="form-group">
                      <label for="resort_code">Resort Code</label>
                      <input type="text" class="form-control" id="resort_code" name="resort_code" value="<?php echo htmlspecialchars($resort['resort_code'] ?? ''); ?>" required>
                  </div>
                  <div class="form-group">
                      <label for="resort_description">Resort Description</label>
                      <textarea id="resort_description" name="resort_description" class="form-control" required rows="4"><?php echo $resort ? htmlspecialchars($resort['resort_description']) : ''; ?></textarea>
                  </div>
              </div>

              <!-- Banner Section -->
              <div class="form-section">
                  <h3 class="form-section-title">Banner Information</h3>
                  <div class="form-group">
                      <label for="banner_title">Banner Title</label>
                      <input type="text" id="banner_title" name="banner_title" class="form-control" required value="<?php echo $resort ? htmlspecialchars($resort['banner_title']) : ''; ?>">
                  </div>
                  <div class="form-group">
                      <label for="banner_image">Banner Image</label>
                      <?php if(isset($resort['banner_image']) && !empty($resort['banner_image'])): ?>
                          <div class="current-image">
                              <img src="assets/resorts/<?php echo $resort['resort_slug']; ?>/<?php echo htmlspecialchars($resort['banner_image']); ?>" alt="Current Banner">
                              <input type="hidden" name="existing_banner_image" value="<?php echo htmlspecialchars($resort['banner_image']); ?>">
                          </div>
                      <?php endif; ?>
                      <input type="file" class="form-control" id="banner_image" name="banner_image" accept="image/*">
                  </div>
              </div>

              <!-- Resort Type and Status Section -->
              <div class="form-section">
                  <h3 class="form-section-title">Resort Type & Status</h3>
                  <div class="form-group">
                      <label for="resort_type">Resort Type</label>
                      <select id="resort_type" name="resort_type" class="form-select" required>
                          <option value="hotel" <?php echo (isset($resort['resort_type']) && $resort['resort_type'] == 'hotel') ? 'selected' : ''; ?>>Hotel</option>
                          <option value="villa" <?php echo (isset($resort['resort_type']) && $resort['resort_type'] == 'villa') ? 'selected' : ''; ?>>Villa</option>
                          <option value="resort" <?php echo (isset($resort['resort_type']) && $resort['resort_type'] == 'resort') ? 'selected' : ''; ?>>Resort</option>
                          <option value="apartment" <?php echo (isset($resort['resort_type']) && $resort['resort_type'] == 'apartment') ? 'selected' : ''; ?>>Apartment</option>
                      </select>
                  </div>
                  
                  <div class="form-group mt-4">
                      <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="is_partner" name="is_partner" value="1" <?php echo (isset($resort['is_partner']) && $resort['is_partner'] == 1) ? 'checked' : ''; ?>>
                          <label class="form-check-label" for="is_partner">
                              Is Partner Hotel (for LeadSquared integration)
                          </label>
                      </div>
                      <small class="text-gray-500">Check this if the resort is a partner hotel (KEPH prefix will be added for LeadSquared)</small>
                  </div>
                  
                  <div class="form-group mt-4">
                      <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo (!$resort || (isset($resort['is_active']) && $resort['is_active'] == 1)) ? 'checked' : ''; ?>>
                          <label class="form-check-label" for="is_active">
                              Active
                          </label>
                      </div>
                      <small class="text-gray-500">Inactive resorts will not be visible on the website</small>
                  </div>
              </div>

              <!-- Amenities Section -->
              <div class="form-section">
                  <h3 class="form-section-title">Amenities</h3>
                  <div id="amenities">
                      <?php if ($resort && !empty($resort['amenities'])):
                          $amenitiesData = json_decode($resort['amenities'], true);
                          $amenitiesCount = count($amenitiesData);
                          foreach ($amenitiesData as $index => $amenity): ?>
                          <div class="row mb-4">
                              <div class="col-md-6">
                                  <input type="text" name="amenities[<?php echo $index; ?>][name]" class="form-control" placeholder="Amenity Name" required value="<?php echo htmlspecialchars($amenity['name']); ?>">
                              </div>
                              <div class="col-md-4">
                                  <input type="file" name="amenities[<?php echo $index; ?>][icon]" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                                  <small class="text-gray-500">Current: <?php echo htmlspecialchars($amenity['icon']); ?></small>
                                  <input type="hidden" name="amenities[<?php echo $index; ?>][existing_icon]" value="<?php echo htmlspecialchars($amenity['icon']); ?>">
                              </div>
                              <div class="col-md-2">
                                  <?php if ($amenitiesCount > 1): ?>
                                      <button type="button" class="btn btn-danger" onclick="removeElement(this)">
                                          <i class="fas fa-trash"></i> Delete
                                      </button>
                                  <?php endif; ?>
                              </div>
                          </div>
                      <?php endforeach; else: ?>
                          <div class="row mb-4">
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
                  <button type="button" class="btn btn-secondary" onclick="addAmenity()">
                      <i class="fas fa-plus"></i> Add Amenity
                  </button>
              </div>

              <!-- Rooms Section -->
              <div class="form-section">
                  <h3 class="form-section-title">Room Details</h3>
                  <div id="roomDetailsContainer" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <?php if (!empty($resort['room_details'])): 
                          $rooms = json_decode($resort['room_details'], true);
                          if (is_array($rooms)):
                              foreach ($rooms as $index => $room): ?>
                                  <div class="room-detail-item bg-white rounded-lg shadow-md p-6">
                                      <div class="room-preview mb-4">
                                          <?php if (!empty($room['image'])): ?>
                                              <div class="relative aspect-video rounded-lg overflow-hidden">
                                                  <img src="assets/resorts/<?php echo $resort['resort_slug']; ?>/<?php echo htmlspecialchars($room['image']); ?>" 
                                                       alt="Room Image" 
                                                       class="w-full h-full object-cover">
                                                  <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                                                      <button type="button" class="delete-room-image bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors" data-index="<?php echo $index; ?>">
                                                          <i class="fas fa-trash mr-2"></i> Delete Image
                                                      </button>
                                                  </div>
                                                  <input type="hidden" name="rooms[<?php echo $index; ?>][existing_image]" value="<?php echo htmlspecialchars($room['image']); ?>">
                                              </div>
                                          <?php endif; ?>
                                      </div>
                                      <div class="room-inputs space-y-4">
                                          <div>
                                              <label class="block text-sm font-medium text-gray-700 mb-1">Room Name</label>
                                              <input type="text" name="rooms[<?php echo $index; ?>][name]" 
                                                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                                     value="<?php echo htmlspecialchars($room['name']); ?>" 
                                                     placeholder="Enter room name">
                                          </div>
                                          <div>
                                              <label class="block text-sm font-medium text-gray-700 mb-1">Room Image</label>
                                              <input type="file" name="rooms[<?php echo $index; ?>][image]" 
                                                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                                     accept="image/*" 
                                                     onchange="previewRoomImage(this, <?php echo $index; ?>)">
                                          </div>
                                          <div class="flex justify-end">
                                              <button type="button" class="remove-room bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors">
                                                  <i class="fas fa-trash mr-2"></i> Remove Room
                                              </button>
                                          </div>
                                      </div>
                                  </div>
                              <?php endforeach;
                          endif;
                      endif; ?>
                  </div>
                  <button type="button" class="btn btn-success mt-4" onclick="addRoomDetail()">
                      <i class="fas fa-plus"></i> Add Room
                  </button>
              </div>

              <!-- Gallery Section -->
              <div class="form-section">
                  <h3 class="form-section-title">Gallery Images</h3>
                  <div class="gallery-preview-container">
                      <?php if (!empty($resort['gallery'])): 
                          $gallery = json_decode($resort['gallery'], true);
                          if (is_array($gallery)):
                              foreach ($gallery as $index => $image): ?>
                                  <div class="gallery-preview-item">
                                      <img src="assets/resorts/<?php echo $resort['resort_slug']; ?>/<?php echo htmlspecialchars($image); ?>" alt="Gallery Image">
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
                  <input type="file" name="gallery[]" class="form-control mt-4" multiple accept="image/*" onchange="previewGalleryImages(this)">
              </div>

              <!-- Testimonials Section -->
              <div class="form-section">
                  <h3 class="form-section-title">Testimonials</h3>
                  <div id="testimonials">
                      <?php 
                      $testimonialsData = [];
                      if ($resort && !empty($resort['testimonials'])) {
                          $testimonialsData = json_decode($resort['testimonials'], true) ?? [];
                      }
                      if (empty($testimonialsData)) {
                          $testimonialsData = [['name' => '', 'from' => '', 'content' => '']];
                      }
                      foreach ($testimonialsData as $index => $testimonial): ?>
                          <div class="row mb-4">
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
                                      <button type="button" class="btn btn-danger" onclick="removeElement(this)">
                                          <i class="fas fa-trash"></i>
                                      </button>
                                  <?php endif; ?>
                              </div>
                          </div>
                      <?php endforeach; ?>
                  </div>
                  <button type="button" class="btn btn-secondary" onclick="addTestimonial()">
                      <i class="fas fa-plus"></i> Add Testimonial
                  </button>
              </div>

              <!-- Submit Button -->
              <div class="form-section">
                  <button type="submit" class="btn btn-primary">
                      <i class="fas fa-save"></i> <?php echo $resort ? "Update Resort" : "Create Resort"; ?>
                  </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>
  <script>
    function addAmenity() {
      const container = document.getElementById('amenities');
      const index = container.querySelectorAll('.row').length;
      const div = document.createElement('div');
      div.className = 'row mb-4';
      div.innerHTML = `<div class="col-md-6">
                          <input type="text" name="amenities[${index}][name]" class="form-control" placeholder="Amenity Name" required>
                        </div>
                        <div class="col-md-4">
                          <input type="file" name="amenities[${index}][icon]" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                        </div>
                        <div class="col-md-2">
                          <button type="button" class="btn btn-danger" onclick="removeElement(this)">
                              <i class="fas fa-trash"></i> Delete
                          </button>
                        </div>`;
      container.appendChild(div);
    }
    function addRoomDetail() {
        const container = document.getElementById('roomDetailsContainer');
        const index = container.querySelectorAll('.room-detail-item').length;
        const div = document.createElement('div');
        div.className = 'room-detail-item bg-white rounded-lg shadow-md p-6';
        div.innerHTML = `
            <div class="room-preview mb-4">
                <div class="relative aspect-video rounded-lg overflow-hidden">
                    <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                        <span class="text-gray-400">No image selected</span>
                    </div>
                </div>
            </div>
            <div class="room-inputs space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Room Name</label>
                    <input type="text" name="rooms[${index}][name]" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           placeholder="Enter room name">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Room Image</label>
                    <input type="file" name="rooms[${index}][image]" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           accept="image/*" 
                           onchange="previewRoomImage(this, ${index})">
                </div>
                <div class="flex justify-end">
                    <button type="button" class="remove-room bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors">
                        <i class="fas fa-trash mr-2"></i> Remove Room
                    </button>
                </div>
            </div>
        `;
        container.appendChild(div);
    }
    function addTestimonial() {
      const container = document.getElementById('testimonials');
      const index = container.querySelectorAll('.row').length;
      const div = document.createElement('div');
      div.className = 'row mb-4';
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
                          <button type="button" class="btn btn-danger" onclick="removeElement(this)">
                              <i class="fas fa-trash"></i> Delete
                          </button>
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
        const container = input.closest('.room-detail-item').querySelector('.room-preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                container.innerHTML = `
                    <div class="relative aspect-video rounded-lg overflow-hidden">
                        <img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                            <button type="button" class="delete-room-image bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors">
                                <i class="fas fa-trash mr-2"></i> Delete Image
                            </button>
                        </div>
                    </div>
                `;
                
                // Add delete functionality to new preview
                container.querySelector('.delete-room-image').addEventListener('click', function() {
                    if (confirm('Are you sure you want to delete this image?')) {
                        container.innerHTML = `
                            <div class="relative aspect-video rounded-lg overflow-hidden">
                                <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                    <span class="text-gray-400">No image selected</span>
                                </div>
                            </div>
                        `;
                    }
                });
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Add this at the beginning of your script section
    document.addEventListener('DOMContentLoaded', function() {
        // Handle room removal
        document.querySelectorAll('.remove-room').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to remove this room?')) {
                    this.closest('.room-detail-item').remove();
                }
            });
        });

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

    function validateForm() {
      console.log('Form submitted');
      return true;
    }
  </script>
</body>
</html>
<?php 
// Bottom of file - include footer with error checking
if (file_exists('bfooter.php')) {
    include 'bfooter.php';
} else {
    // If footer doesn't exist, show a simple admin footer
    echo '    <footer class="bg-dark text-white py-3 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; ' . date('Y') . ' KE Resorts. All rights reserved.</p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>';
}
?>
