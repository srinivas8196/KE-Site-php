<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
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
            <button type="submit" class="px-4 py-2 bg-[#B4975A] hover:bg-[#96793D] text-black rounded transition duration-300 ease-in-out transform hover:-translate-y-0.5">Continue</button>
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
  <!-- Font Awesome for Icons - Updated with integrity attribute -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <!-- jQuery for easier DOM manipulation - Added before other scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    .alert-info {
        color: #0c5460;
        background-color: #d1ecf1;
        border-color: #bee5eb;
    }
    .alert-warning {
        color: #856404;
        background-color: #fff3cd;
        border-color: #ffeeba;
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
    
    /* Added styles for deleted image markers */
    .deleted-image-marker {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(220, 53, 69, 0.7);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        z-index: 5;
    }

    /* Enhanced gallery card style */
    .gallery-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .gallery-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        position: relative;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .gallery-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px rgba(0,0,0,0.1);
    }

    .gallery-image-container {
        flex: 1;
        overflow: hidden;
        position: relative;
        padding-top: 100%; /* 1:1 Aspect Ratio */
    }

    .gallery-card img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.3s;
    }

    .gallery-card:hover img {
        transform: scale(1.05);
    }

    .gallery-card-actions {
        padding: 10px 15px;
        background: #f8f9fa;
        border-top: 1px solid #e2e8f0;
        text-align: center;
    }

    .gallery-delete-link {
        color: #e53e3e;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        display: block;
        transition: color 0.2s;
    }

    .gallery-delete-link:hover {
        color: #c53030;
    }

    /* Preview styles for newly added images */
    #new-gallery-previews {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }

    .preview-item {
        position: relative;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        padding-top: 100%; /* 1:1 Aspect Ratio */
    }

    .preview-item img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .preview-remove {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(220, 53, 69, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 20;
        transition: background-color 0.2s;
    }

    .preview-remove:hover {
        background: rgba(220, 53, 69, 1);
    }

    /* Room image action styles to match gallery */
    .room-details-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
        margin-bottom: 20px;
    }

    .room-detail-item {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        background: white;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .room-detail-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px rgba(0,0,0,0.1);
    }

    .room-image-wrapper {
        position: relative;
        width: 100%;
        padding-top: 60%; /* 16:9 Aspect Ratio */
        overflow: hidden;
        border-radius: 8px 8px 0 0;
    }

    .room-image {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }

    .room-detail-item:hover .room-image {
        transform: scale(1.05);
    }

    .room-image-placeholder {
        width: 100%;
        padding-top: 60%; /* 16:9 Aspect Ratio */
        background-color: #f7fafc;
        border-radius: 8px 8px 0 0;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .room-image-placeholder span {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #a0aec0;
        font-size: 14px;
    }

    .room-image-actions {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0,0,0,0.7);
        padding: 10px;
        text-align: center;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .room-image-wrapper:hover .room-image-actions {
        opacity: 1;
    }

    .room-delete-link {
        color: white;
        text-decoration: none;
        font-size: 14px;
        display: inline-block;
        background: #e53e3e;
        padding: 5px 10px;
        border-radius: 4px;
        transition: background-color 0.2s;
    }

    .room-delete-link:hover {
        background: #c53030;
    }

    .room-inputs {
        padding: 15px;
    }

    .room-name-field, .room-image-field {
        margin-bottom: 15px;
    }

    .room-name-field label, .room-image-field label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #4a5568;
        font-size: 14px;
    }

    .room-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 15px;
    }

    .btn-remove-room {
        background: #e53e3e;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 14px;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .btn-remove-room:hover {
        background: #c53030;
    }

    .btn-add-room {
        background: #38a169;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s;
        margin-top: 15px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .btn-add-room:hover {
        background: #2f855a;
    }

    .preview-room-remove {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(220, 53, 69, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 10;
        transition: background-color 0.2s;
    }

    .preview-room-remove:hover {
        background: rgba(220, 53, 69, 1);
    }
  </style>
</head>
<body class="bg-gray-100">
  <!-- Check if Font Awesome is loaded correctly -->
  <script>
    // Check if Font Awesome is loaded correctly
    document.addEventListener('DOMContentLoaded', function() {
      setTimeout(function() {
        // Check if any Font Awesome icon is rendered correctly by measuring its width
        const testIcon = document.createElement('i');
        testIcon.className = 'fas fa-trash';
        testIcon.style.visibility = 'hidden';
        document.body.appendChild(testIcon);
        
        const isIconLoaded = testIcon.clientWidth > 0;
        document.body.removeChild(testIcon);
        
        if (!isIconLoaded) {
          console.warn('Font Awesome not loaded correctly, adding fallback');
          // Add a fallback for icon display
          const styleSheet = document.createElement('style');
          styleSheet.innerHTML = `
            /* Fallback for Font Awesome icons */
            .fas.fa-trash:before { content: "🗑️"; }
            .fas.fa-plus:before { content: "➕"; }
            .fas.fa-save:before { content: "💾"; }
            .fa-trash:before { content: "🗑️"; }
          `;
          document.head.appendChild(styleSheet);
        }
      }, 500); // Give some time for Font Awesome to load
    });
  </script>
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
            <!-- Display messages -->
            <?php if(isset($_SESSION['success_message'])): ?>
              <div class="alert alert-success mb-4">
                <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                <?php unset($_SESSION['success_message']); ?>
              </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['warning_message'])): ?>
              <div class="alert alert-warning mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($_SESSION['warning_message']); ?>
                <?php unset($_SESSION['warning_message']); ?>
              </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error_message'])): ?>
              <div class="alert alert-danger mb-4">
                <i class="fas fa-times-circle mr-2"></i> <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                <?php unset($_SESSION['error_message']); ?>
              </div>
            <?php endif; ?>
            
            <form action="/KE-Site-php/save_resort.php" method="post" enctype="multipart/form-data" id="resortForm" onsubmit="return validateForm()">
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
                  <div id="roomDetailsContainer" class="room-details-container">
                      <?php if (!empty($resort['room_details'])): 
                          $rooms = json_decode($resort['room_details'], true);
                          if (is_array($rooms)):
                              foreach ($rooms as $index => $room): ?>
                                  <div class="room-detail-item">
                                      <div class="room-preview mb-4">
                                          <?php if (!empty($room['image'])): ?>
                                              <div class="room-image-wrapper">
                                                  <img src="assets/resorts/<?php echo $resort['resort_slug']; ?>/<?php echo htmlspecialchars($room['image']); ?>" 
                                                       alt="Room Image" 
                                                       class="room-image">
                                                  <div class="room-image-actions">
                                                      <a href="delete_room_image.php?resort_id=<?php echo $resort['id']; ?>&room_index=<?php echo $index; ?>&image=<?php echo urlencode($room['image']); ?>" 
                                                         class="room-delete-link" 
                                                         onclick="return confirm('Are you sure you want to delete this room image?');">
                                                          <i class="fas fa-trash"></i> Delete Image
                                                      </a>
                                                  </div>
                                                  <input type="hidden" name="rooms[<?php echo $index; ?>][existing_image]" value="<?php echo htmlspecialchars($room['image']); ?>">
                                              </div>
                                          <?php else: ?>
                                              <div class="room-image-placeholder">
                                                  <span>No image selected</span>
                                              </div>
                                          <?php endif; ?>
                                      </div>
                                      <div class="room-inputs">
                                          <div class="room-name-field">
                                              <label for="room-name-<?php echo $index; ?>">Room Name</label>
                                              <input type="text" id="room-name-<?php echo $index; ?>" name="rooms[<?php echo $index; ?>][name]" 
                                                     class="form-control"
                                                     value="<?php echo htmlspecialchars($room['name']); ?>" 
                                                     placeholder="Enter room name">
                                          </div>
                                          <div class="room-image-field">
                                              <label for="room-image-<?php echo $index; ?>">Room Image</label>
                                              <input type="file" id="room-image-<?php echo $index; ?>" name="rooms[<?php echo $index; ?>][image]" 
                                                     class="form-control"
                                                     accept="image/*" 
                                                     onchange="previewRoomImage(this, <?php echo $index; ?>)">
                                          </div>
                                          <div class="room-actions">
                                              <button type="button" class="btn-remove-room">
                                                  <i class="fas fa-trash"></i> Remove Room
                                              </button>
                                          </div>
                                      </div>
                                  </div>
                              <?php endforeach;
                          endif;
                      endif; ?>
                  </div>
                  <button type="button" class="btn-add-room" onclick="addRoomDetail()">
                      <i class="fas fa-plus"></i> Add Room
                  </button>
              </div>

              <!-- Gallery Section -->
              <div class="form-section">
                  <h3 class="form-section-title">Gallery Images</h3>
                  
                  <!-- Improved gallery with better styling -->
                  <div class="gallery-cards">
                      <?php if (!empty($resort['gallery'])): 
                          $gallery = json_decode($resort['gallery'], true);
                          if (is_array($gallery)):
                              foreach ($gallery as $index => $image): ?>
                                  <div class="gallery-card">
                                      <div class="gallery-image-container">
                                          <img src="assets/resorts/<?php echo $resort['resort_slug']; ?>/<?php echo htmlspecialchars($image); ?>" alt="Gallery Image">
                                      </div>
                                      <div class="gallery-card-actions">
                                          <a href="delete_gallery_image.php?resort_id=<?php echo $resort['id']; ?>&image=<?php echo urlencode($image); ?>" 
                                             class="gallery-delete-link" 
                                             onclick="return confirm('Are you sure you want to delete this image?');">
                                              <i class="fas fa-trash"></i> Delete
                                          </a>
                                      </div>
                                      <input type="hidden" name="existing_gallery[]" value="<?php echo htmlspecialchars($image); ?>">
                                  </div>
                              <?php endforeach;
                          endif;
                      endif; ?>
                  </div>
                  
                  <input type="file" name="gallery[]" class="form-control mt-4" multiple accept="image/*" onchange="previewGalleryImages(this)">
                  <div id="new-gallery-previews" class="mt-3"></div>
                  
                  <div class="mt-2 text-gray-600 text-sm">
                      <p>Note: Clicking delete will permanently remove the image immediately.</p>
                  </div>
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
        div.className = 'room-detail-item';
        div.innerHTML = `
            <div class="room-preview mb-4">
                <div class="room-image-placeholder">
                    <span>No image selected</span>
                </div>
            </div>
            <div class="room-inputs">
                <div class="room-name-field">
                    <label for="room-name-${index}">Room Name</label>
                    <input type="text" id="room-name-${index}" name="rooms[${index}][name]" 
                           class="form-control" 
                           placeholder="Enter room name">
                </div>
                <div class="room-image-field">
                    <label for="room-image-${index}">Room Image</label>
                    <input type="file" id="room-image-${index}" name="rooms[${index}][image]" 
                           class="form-control" 
                           accept="image/*" 
                           onchange="previewRoomImage(this, ${index})">
                </div>
                <div class="room-actions">
                    <button type="button" class="btn-remove-room">
                        <i class="fas fa-trash"></i> Remove Room
                    </button>
                </div>
            </div>
        `;
        container.appendChild(div);
        
        // Add event handler for the room removal button
        attachRoomRemovalHandlers();
    }
    
    // Updated previewRoomImage function to work with the new layout
    function previewRoomImage(input, index) {
        const container = input.closest('.room-detail-item').querySelector('.room-preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                container.innerHTML = `
                    <div class="room-image-wrapper">
                        <img src="${e.target.result}" alt="Preview" class="room-image">
                        <button type="button" class="preview-room-remove" onclick="clearRoomPreview(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Function to clear room preview
    function clearRoomPreview(button) {
        if (confirm('Remove this image preview?')) {
            const previewContainer = button.closest('.room-preview');
            previewContainer.innerHTML = `
                <div class="room-image-placeholder">
                    <span>No image selected</span>
                </div>
            `;
            
            // Clear the file input
            const fileInput = button.closest('.room-detail-item').querySelector('input[type="file"]');
            if (fileInput) {
                fileInput.value = '';
            }
        }
    }
    
    // Helper function to attach room removal handlers to all remove room buttons
    function attachRoomRemovalHandlers() {
        // Remove any existing event listeners
        document.querySelectorAll('.btn-remove-room').forEach(btn => {
            // Remove old event listeners by cloning and replacing the button
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            
            // Add new event listener
            newBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to remove this room?')) {
                    const roomItem = this.closest('.room-detail-item');
                    if (roomItem) {
                        roomItem.remove();
                        console.log('Room removed');
                    }
                }
            });
        });
    }
    
    // Initialize event handlers when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        attachRoomRemovalHandlers();
    });

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
      container.appendChild(div);
    }
    function removeElement(button) {
      button.closest('.row').remove();
    }
    document.getElementById('toggleSidebar').addEventListener('click', function() {
      var sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('sidebar-collapsed');
    });

    function previewGalleryImages(input) {
        const container = document.getElementById('new-gallery-previews');
        container.innerHTML = ''; // Clear previous previews
        
        if (input.files && input.files.length > 0) {
            for (let i = 0; i < input.files.length; i++) {
                const reader = new FileReader();
                const fileIndex = i;
                
                reader.onload = function(e) {
                    // Create preview element
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'preview-item';
                    previewDiv.innerHTML = `
                        <img src="${e.target.result}" alt="New image preview">
                        <button type="button" class="preview-remove" onclick="removePreview(this, ${fileIndex})">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    container.appendChild(previewDiv);
                };
                reader.readAsDataURL(input.files[i]);
            }
        }
    }
    
    // Function to remove preview and clear the corresponding file input
    function removePreview(button, fileIndex) {
        const previewItem = button.closest('.preview-item');
        if (previewItem) {
            previewItem.remove();
        }
        
        // Note: This won't actually remove the file from the FileList
        // We need to handle this on the server-side by ignoring empty file inputs
        console.log(`Preview removed for file at index ${fileIndex}`);
    }

    function validateForm() {
      console.log('Form validation started');
      
      // Check if there are any deletion markers
      const galleryDeletions = document.querySelectorAll('input[name="delete_gallery[]"]');
      const roomDeletions = document.querySelectorAll('input[name="delete_room_image[]"]');
      
      console.log('Gallery images marked for deletion:', galleryDeletions.length);
      galleryDeletions.forEach(input => console.log(' - ' + input.value));
      
      console.log('Room images marked for deletion:', roomDeletions.length);
      roomDeletions.forEach(input => console.log(' - ' + input.value));
      
      return true;
    }

    // Add this to ensure jQuery and our JavaScript are working properly
    $(document).ready(function() {
      console.log('jQuery is working!');
      
      // Handle gallery image deletion
      $(document).on('click', '.gallery-delete-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const galleryItem = $(this).closest('.gallery-preview-item');
        const imagePath = $(this).data('path');
        
        if (confirm('Are you sure you want to delete this image?')) {
          // Create a hidden input to mark this image for deletion
          $('#deletedImagesContainer').append(
            `<input type="hidden" name="delete_gallery[]" value="${imagePath}">`
          );
          
          // Visual feedback
          galleryItem.css('opacity', '0.3');
          galleryItem.append(
            `<div class="deleted-image-marker">MARKED FOR DELETION</div>`
          );
          
          // Remove the existing_gallery input to prevent it from being submitted
          galleryItem.find('input[name^="existing_gallery"]').remove();
          
          console.log('Marked gallery image for deletion:', imagePath);
        }
      });
      
      // Handle room image deletion
      $(document).on('click', '.room-delete-link', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const roomPreview = $(this).closest('.room-preview');
        const roomItem = $(this).closest('.room-detail-item');
        const imagePath = $(this).data('path');
        
        if (confirm('Are you sure you want to delete this image?')) {
          // Create a hidden input to mark this image for deletion
          $('#deletedImagesContainer').append(
            `<input type="hidden" name="delete_room_image[]" value="${imagePath}">`
          );
          
          // Visual feedback
          roomPreview.find('img').css('opacity', '0.3');
          roomPreview.find('.relative').append(
            `<div class="deleted-image-marker">MARKED FOR DELETION</div>`
          );
          
          // Remove the existing_image input to prevent it from being submitted
          roomPreview.find('input[name$="[existing_image]"]').remove();
          
          console.log('Marked room image for deletion:', imagePath);
        }
      });
      
      // Handle room removal
      $('.remove-room').on('click', function() {
        if (confirm('Are you sure you want to remove this room?')) {
          $(this).closest('.room-detail-item').remove();
          console.log('Room removed');
        }
      });
      
      // For newly added gallery images
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
                  <button type="button" class="btn btn-danger btn-sm temp-delete-btn">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              `;
              container.appendChild(div);
              
              // Add delete functionality for temp images
              $(div).find('.temp-delete-btn').on('click', function() {
                if (confirm('Remove this image?')) {
                  $(div).remove();
                }
              });
            }
            reader.readAsDataURL(input.files[i]);
          }
        }
      }
      
      // Expose the function globally
      window.previewGalleryImages = previewGalleryImages;
      
      // For newly added room images (redefined to avoid issues)
      function previewRoomImage(input, index) {
        const container = input.closest('.room-detail-item').querySelector('.room-preview');
        if (input.files && input.files[0]) {
          const reader = new FileReader();
          reader.onload = function(e) {
            container.innerHTML = `
              <div class="room-image-wrapper">
                <img src="${e.target.result}" alt="Preview" class="room-image">
                <button type="button" class="preview-room-remove" onclick="clearRoomPreview(this)">
                    <i class="fas fa-times"></i>
                </button>
              </div>
            `;
          }
          reader.readAsDataURL(input.files[0]);
        }
      }
      
      // Expose the function globally
      window.previewRoomImage = previewRoomImage;
    });
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
