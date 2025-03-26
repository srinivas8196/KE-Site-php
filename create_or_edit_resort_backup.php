<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require 'db.php';

$destination_id = $_GET['destination_id'] ?? null;
$resort = null;

if (isset($_GET['resort_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM resorts WHERE id = ?");
    $stmt->execute([$_GET['resort_id']]);
    $resort = $stmt->fetch();
    if (!$destination_id) {
        $destination_id = $resort['destination_id'];
    }
}

// If no destination is provided, show a dropdown to select one.
if (!$destination_id) {
    $stmt = $pdo->query("SELECT id, destination_name FROM destinations ORDER BY destination_name");
    $destinations = $stmt->fetchAll();
    include 'kheader.php';
?>
    <div class="container mt-5">
        <h2>Select Destination for Resort</h2>
        <form action="create_or_edit_resort.php" method="get">
            <div class="mb-3">
                <label for="destination_id" class="form-label">Destination:</label>
                <select id="destination_id" name="destination_id" class="form-select" required>
                    <option value="">-- Select Destination --</option>
                    <?php foreach ($destinations as $dest): ?>
                        <option value="<?php echo $dest['id']; ?>"><?php echo $dest['destination_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Continue</button>
        </form>
    </div>
<?php
    include 'kfooter.php';
    exit();
}

include 'kheader.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create or Edit Resort</title>
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
    <main class="flex-1 p-8">
      <!-- Breadcrumb -->
      <nav class="mb-4 text-sm text-gray-600" aria-label="Breadcrumb">
        <ol class="list-reset flex">
          <li><a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a></li>
          <li><span class="mx-2">/</span></li>
          <li><a href="destination.php?id=<?php echo $destination_id; ?>" class="text-blue-600 hover:underline">Destination</a></li>
          <li><span class="mx-2">/</span></li>
          <li class="text-gray-600"><?php echo $resort ? "Edit Resort" : "Add New Resort"; ?></li>
        </ol>
      </nav>
      <h2 class="text-3xl font-bold mb-6"><?php echo $resort ? "Edit Resort" : "Add New Resort"; ?></h2>
      <form action="save_resort.php" method="post" enctype="multipart/form-data">
        <?php if ($resort): ?>
          <input type="hidden" name="resort_id" value="<?php echo $resort['id']; ?>">
        <?php endif; ?>
        <input type="hidden" name="destination_id" value="<?php echo $destination_id; ?>">
        <div class="mb-3">
          <label for="resort_name" class="form-label">Resort Name:</label>
          <input type="text" id="resort_name" name="resort_name" class="form-control" required value="<?php echo $resort ? $resort['resort_name'] : ''; ?>">
        </div>
        <div class="mb-3">
          <label for="resort_description" class="form-label">Resort Description:</label>
          <textarea id="resort_description" name="resort_description" class="form-control" required><?php echo $resort ? $resort['resort_description'] : ''; ?></textarea>
        </div>
        <!-- Dynamic Amenities Section -->
        <div class="mb-3" id="amenities">
          <label class="form-label">Amenities:</label>
          <div class="row mb-2">
            <div class="col-md-6">
              <input type="text" name="amenities[0][name]" class="form-control" placeholder="Amenity Name" required>
            </div>
            <div class="col-md-6">
              <input type="file" name="amenities[0][icon]" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
            </div>
          </div>
        </div>
        <button type="button" class="btn btn-secondary mb-3" onclick="addAmenity()">Add Amenity</button>

        <!-- Dynamic Rooms Section -->
        <div class="mb-3" id="rooms">
          <label class="form-label">Rooms:</label>
          <div class="row mb-2">
            <div class="col-md-6">
              <input type="text" name="rooms[0][name]" class="form-control" placeholder="Room Name" required>
            </div>
            <div class="col-md-6">
              <input type="file" name="rooms[0][image]" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
            </div>
          </div>
        </div>
        <button type="button" class="btn btn-secondary mb-3" onclick="addRoom()">Add Room</button>

        <!-- Gallery Images -->
        <div class="mb-3">
          <label for="gallery" class="form-label">Gallery Images:</label>
          <input type="file" id="gallery" name="gallery[]" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple required>
        </div>

        <!-- Dynamic Testimonials Section -->
        <div class="mb-3" id="testimonials">
          <label class="form-label">Testimonials:</label>
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
        </div>
        <button type="button" class="btn btn-secondary mb-3" onclick="addTestimonial()">Add Testimonial</button>
        <br><br>
        <button type="submit" class="btn btn-primary">Create Resort</button>
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
<?php include 'kfooter.php'; ?>
<?php
// Include database connection
include('db.php');

// Function to handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $resort_id = $_POST['resort_id'];
    $resort_name = $_POST['resort_name'];
    $resort_location = $_POST['resort_location'];
    $resort_status = isset($_POST['resort_status']) ? 1 : 0;

    if ($resort_id) {
        // Update existing resort
        $query = "UPDATE resorts SET name = ?, location = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssii', $resort_name, $resort_location, $resort_status, $resort_id);
    } else {
        // Create new resort
        $query = "INSERT INTO resorts (name, location, status) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssi', $resort_name, $resort_location, $resort_status);
    }

    if ($stmt->execute()) {
        echo "Resort saved successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Fetch resort data if editing
$resort = null;
if (isset($_GET['id'])) {
    $resort_id = $_GET['id'];
    $query = "SELECT * FROM resorts WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $resort_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $resort = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create or Edit Resort</title>
</head>
<body>
    <h1><?php echo $resort ? 'Edit' : 'Create'; ?> Resort</h1>
    <form method="POST" action="">
        <input type="hidden" name="resort_id" value="<?php echo $resort['id'] ?? ''; ?>">
        <label for="resort_name">Name:</label>
        <input type="text" id="resort_name" name="resort_name" value="<?php echo $resort['name'] ?? ''; ?>" required><br>
        <label for="resort_location">Location:</label>
        <input type="text" id="resort_location" name="resort_location" value="<?php echo $resort['location'] ?? ''; ?>" required><br>
        <label for="resort_status">Active:</label>
        <input type="checkbox" id="resort_status" name="resort_status" <?php echo isset($resort['status']) && $resort['status'] ? 'checked' : ''; ?>><br>
        <button type="submit">Save</button>
    </form>
</body>
</html>