<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];
require 'db.php';
// Fetch destinations and resorts for dropdowns
$stmtDest = $pdo->query("SELECT id, destination_name FROM destinations ORDER BY destination_name");
$destinations = $stmtDest->fetchAll();
?>
<?php include 'bheader.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Campaign</title>
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
        <?php if(isset($user) && $user['user_type'] === 'super_admin'): ?>
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
          <li class="text-gray-600">Create Campaign</li>
        </ol>
      </nav>
      <h2 class="text-3xl font-bold mb-6">Create New Campaign</h2>
      <form action="save_campaign.php" method="post">
        <!-- Campaign Type -->
        <div class="mb-3">
          <label for="campaign_type" class="form-label">Campaign Type</label>
          <select id="campaign_type" name="campaign_type" class="form-select" required>
            <option value="" selected disabled>Select Type</option>
            <option value="Facebook">Facebook</option>
            <option value="WhatsApp">WhatsApp</option>
            <option value="Email">Email</option>
            <option value="Remarketing">Remarketing</option>
          </select>
        </div>
        <!-- Campaign Subtype -->
        <div class="mb-3">
          <label for="campaign_subtype" class="form-label">Campaign Subtype</label>
          <select id="campaign_subtype" name="campaign_subtype" class="form-select" required>
            <option value="" selected disabled>Select Subtype</option>
            <option value="Leadgen">Leadgen</option>
            <option value="Booking">Booking</option>
          </select>
        </div>
        <!-- Destination -->
        <div class="mb-3">
          <label for="destination_id" class="form-label">Destination</label>
          <select id="destination_id" name="destination_id" class="form-select" required>
            <option value="" selected disabled>Select Destination</option>
            <?php foreach ($destinations as $dest): ?>
              <option value="<?php echo $dest['id']; ?>"><?php echo $dest['destination_name']; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Resort Dropdown (will be populated via JavaScript after destination selection) -->
        <div class="mb-3" id="resortDiv" style="display:none;">
          <label for="resort_id" class="form-label">Resort</label>
          <select id="resort_id" name="resort_id" class="form-select" required></select>
        </div>
        <!-- Marketing Template -->
        <div class="mb-3">
          <label for="template_id" class="form-label">Marketing Template</label>
          <select id="template_id" name="template_id" class="form-select" required>
            <option value="" selected disabled>Select Template</option>
            <?php
            // Fetch marketing templates (simplified)
            $stmtTemp = $pdo->query("SELECT id, template_name FROM marketing_templates ORDER BY template_name");
            $templates = $stmtTemp->fetchAll();
            foreach ($templates as $temp):
            ?>
              <option value="<?php echo $temp['id']; ?>"><?php echo $temp['template_name']; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Campaign Title and Subtitle -->
        <div class="mb-3">
          <label for="campaign_title" class="form-label">Campaign Title</label>
          <input type="text" id="campaign_title" name="campaign_title" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="campaign_subtitle" class="form-label">Campaign Subtitle</label>
          <input type="text" id="campaign_subtitle" name="campaign_subtitle" class="form-control" required>
        </div>
        <!-- Schedule -->
        <div class="mb-3">
          <label class="form-label">Campaign Schedule</label>
          <input type="date" name="start_date" class="form-control mb-2" required>
          <input type="date" name="end_date" class="form-control" required>
        </div>
        <!-- Prices -->
        <div class="mb-3">
          <label for="regular_price" class="form-label">Regular Price</label>
          <input type="number" step="0.01" id="regular_price" name="regular_price" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="sale_price" class="form-label">Sale Price</label>
          <input type="number" step="0.01" id="sale_price" name="sale_price" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Create Campaign</button>
      </form>
    </main>
  </div>
  <script>
    // Example: Populate resorts dropdown based on selected destination (this is a placeholder)
    document.getElementById('destination_id').addEventListener('change', function() {
      // In practice, you would use AJAX to fetch resorts for the selected destination.
      var destId = this.value;
      var resortSelect = document.getElementById('resort_id');
      resortSelect.innerHTML = "";
      // Sample static options
      var sampleResorts = {
        "1": ["Resort A", "Resort B"],
        "2": ["Resort C", "Resort D"]
      };
      if (sampleResorts[destId]) {
        sampleResorts[destId].forEach(function(resort) {
          var option = document.createElement("option");
          option.value = resort;
          option.text = resort;
          resortSelect.appendChild(option);
        });
        document.getElementById('resortDiv').style.display = "block";
      } else {
        document.getElementById('resortDiv').style.display = "none";
      }
    });
    document.getElementById('toggleSidebar').addEventListener('click', function() {
      var sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('sidebar-collapsed');
    });
  </script>
</body>
</html>
<?php include 'bfooter.php'; ?>