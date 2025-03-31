<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destinationName = trim($_POST['name']);
    // Sanitize folder name
    $folderName = preg_replace('/[^a-zA-Z0-9]/', '-', strtolower($destinationName));
    $destinationFolder = "assets/destinations/$folderName";
    if (!file_exists($destinationFolder)) {
        mkdir($destinationFolder, 0777, true);
    }
    
    // Process banner image
    $bannerTmp = is_array($_FILES['banner_image']['tmp_name']) ? $_FILES['banner_image']['tmp_name'][0] : $_FILES['banner_image']['tmp_name'];
    $bannerName = is_array($_FILES['banner_image']['name']) ? $_FILES['banner_image']['name'][0] : $_FILES['banner_image']['name'];
    move_uploaded_file($bannerTmp, "$destinationFolder/$bannerName");
    
    // Insert destination record
    $stmt = $pdo->prepare("INSERT INTO destinations (destination_name, banner_image) VALUES (?, ?)");
    $stmt->execute([$destinationName, $bannerName]);
    $destId = $pdo->lastInsertId();
    header("Location: destination.php?id=$destId");
    exit();
}
?>
<?php include 'bheader.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Destination</title>
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
    <?php include 'sidebar.php'; ?>
    <!-- Main Content -->
    <main class="flex-1 p-8">
      <!-- Breadcrumb -->
      <nav class="mb-4 text-sm text-gray-600" aria-label="Breadcrumb">
        <ol class="list-reset flex">
          <li><a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a></li>
          <li><span class="mx-2">/</span></li>
          <li class="text-gray-600">Create Destination</li>
        </ol>
      </nav>
      <h2 class="text-3xl font-bold mb-6">Create Destination</h2>
      <form action="create_destination.php" method="post" enctype="multipart/form-data">
        <div class="mb-3">
          <label for="name" class="form-label">Destination Name (Country):</label>
          <input type="text" id="name" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="banner_image" class="form-label">Banner Image:</label>
          <input type="file" id="banner_image" name="banner_image" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
        </div>
        <button type="submit" class="btn btn-primary">Create Destination</button>
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
