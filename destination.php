<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
$user = $_SESSION['user'];
require 'db.php';

$destination_id = $_GET['id'] ?? null;
if (!$destination_id) {
    header('Location: destination_list.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ?");
$stmt->execute([$destination_id]);
$destination = $stmt->fetch();

if (!$destination) {
    header('Location: destination_list.php');
    exit();
}

$resStmt = $pdo->prepare("SELECT * FROM resorts WHERE destination_id = ? ORDER BY resort_name");
$resStmt->execute([$destination['id']]);
$resorts = $resStmt->fetchAll();

$folderName = preg_replace('/[^a-zA-Z0-9]/', '-', strtolower($destination['destination_name']));
$destinationFolder = "assets/destinations/$folderName";

include 'bheader.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destination Details</title>
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
                    <li><a href="destination_list.php" class="text-blue-600 hover:underline">Destinations</a></li>
                    <li><span class="mx-2">/</span></li>
                    <li class="text-gray-600"><?php echo htmlspecialchars($destination['destination_name']); ?></li>
                </ol>
            </nav>
            <h2 class="text-3xl font-bold mb-6"><?php echo htmlspecialchars($destination['destination_name']); ?></h2>
            <div class="mb-6">
                <p class="text-lg"><?php echo isset($destination['description']) ? htmlspecialchars($destination['description']) : 'No description available.'; ?></p>
            </div>
            <a href="edit_destination.php?id=<?php echo $destination_id; ?>" class="px-4 py-2 bg-blue-500 text-white rounded mb-6">Edit Destination</a> <br><br>
            <!-- Resorts List -->
            <div class="mb-10">
                <h2 class="text-2xl font-bold mb-4">Resorts in <?php echo $destination['destination_name']; ?></h2>
                <a href="create_or_edit_resort.php?destination_id=<?php echo $destination['id']; ?>" class="btn btn-primary mb-3">Add New Resort</a><br><br>
                <?php if(count($resorts) > 0): ?>
                    <ul class="list-group">
                        <?php foreach($resorts as $resort): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($resort['resort_name']); ?>
                                <div>
                                    <a href="create_or_edit_resort.php?destination_id=<?php echo $destination['id']; ?>&resort_id=<?php echo $resort['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                    <a href="<?php echo htmlspecialchars($resort['resort_slug']); ?>.php" class="btn btn-sm btn-primary">View</a>
                                    <a href="delete_resort.php?id=<?php echo $resort['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this resort?');">Delete</a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No resorts found. Please add one.</p>
                <?php endif; ?>
            </div>
            <a href="destination_list.php" class="btn btn-outline-secondary">Back to Destinations List</a>
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
