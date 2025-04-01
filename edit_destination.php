<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destinationId = $_POST['id'];
    $destinationName = trim($_POST['name']);
    
    // Sanitize folder name
    $folderName = preg_replace('/[^a-zA-Z0-9]/', '-', strtolower($destinationName));
    $destinationFolder = "assets/destinations/$folderName";
    
    if (!file_exists($destinationFolder)) {
        mkdir($destinationFolder, 0777, true);
    }
    
    // Process banner image if uploaded
    if (!empty($_FILES['banner_image']['name'])) {
        $bannerTmp = $_FILES['banner_image']['tmp_name'];
        $bannerName = $_FILES['banner_image']['name'];
        move_uploaded_file($bannerTmp, "$destinationFolder/$bannerName");

        // Update destination record with new banner image
        $stmt = $pdo->prepare("UPDATE destinations SET destination_name = ?, banner_image = ? WHERE id = ?");
        $stmt->execute([$destinationName, $bannerName, $destinationId]);
    } else {
        // Update destination name only
        $stmt = $pdo->prepare("UPDATE destinations SET destination_name = ? WHERE id = ?");
        $stmt->execute([$destinationName, $destinationId]);
    }
    
    header("Location: destination.php?id=$destinationId");
    exit();
}

// Fetch destination details
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $destination = $stmt->fetch();
}
?>
<?php include 'bheader.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Destination</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
  <div class="flex min-h-screen">
  <?php include 'sidebar.php'; ?>
    <main class="flex-1 p-8">
      <nav class="mb-6">
        <ol class="flex items-center space-x-2 text-sm text-gray-600">
          <li><a href="dashboard.php" class="text-blue-500 hover:text-blue-700 transition-colors">Dashboard</a></li>
          <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
          <li><a href="destination_list.php" class="text-blue-500 hover:text-blue-700 transition-colors">Destinations</a></li>
          <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
          <li class="text-gray-500">Edit Destination</li>
        </ol>
      </nav>

      <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-2xl font-bold text-gray-800 mb-6">Edit Destination</h2>
          
          <form action="edit_destination.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($destination['id']); ?>">
            
            <div class="mb-6">
              <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Destination Name (Country)</label>
              <input type="text" id="name" name="name" 
                     class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                     value="<?php echo htmlspecialchars($destination['destination_name']); ?>" required>
            </div>

            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Current Banner Image</label>
              <div class="relative h-48 bg-gray-100 rounded-lg overflow-hidden">
                <?php if (!empty($destination['banner_image'])): ?>
                  <img src="assets/destinations/<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $destination['destination_name'])) . '/' . $destination['banner_image']); ?>" 
                       alt="<?php echo htmlspecialchars($destination['destination_name']); ?>"
                       class="w-full h-full object-cover">
                <?php else: ?>
                  <div class="w-full h-full flex items-center justify-center">
                    <i class="fas fa-image text-4xl text-gray-400"></i>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="mb-6">
              <label for="banner_image" class="block text-sm font-medium text-gray-700 mb-2">Update Banner Image</label>
              <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg">
                <div class="space-y-1 text-center">
                  <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                  <div class="flex text-sm text-gray-600">
                    <label for="banner_image" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                      <span>Upload a file</span>
                      <input id="banner_image" name="banner_image" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.webp">
                    </label>
                    <p class="pl-1">or drag and drop</p>
                  </div>
                  <p class="text-xs text-gray-500">PNG, JPG, WEBP up to 10MB</p>
                </div>
              </div>
            </div>

            <div class="flex justify-end space-x-4">
              <a href="destination_list.php" 
                 class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                Cancel
              </a>
              <button type="submit" 
                      class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                Update Destination
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
<?php include 'bfooter.php'; ?>
