<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];
require 'db.php';
$stmt = $pdo->query("SELECT r.*, d.destination_name FROM resorts r JOIN destinations d ON r.destination_id = d.id ORDER BY d.destination_name, r.resort_name");
$resorts = $stmt->fetchAll();

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
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
  <title>Resorts List</title>
  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome for Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    /* Simple switch styling */
    .switch {
      position: relative;
      display: inline-block;
      width: 50px;
      height: 24px;
    }
    .switch input { 
      opacity: 0;
      width: 0;
      height: 0;
    }
    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 24px;
    }
    .slider:before {
      position: absolute;
      content: "";
      height: 18px;
      width: 18px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }
    input:checked + .slider {
      background-color: #4ade80; /* green */
    }
    input:checked + .slider:before {
      transform: translateX(26px);
    }
    .loading-spinner {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid rgba(255,255,255,.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 1s ease-in-out infinite;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    #resorts-table th {
      position: relative;
    }
    #resorts-table th.asc::after {
      content: '↑';
      position: absolute;
      right: 8px;
    }
    #resorts-table th.desc::after {
      content: '↓';
      position: absolute;
      right: 8px;
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
          <li class="text-gray-600">Resorts</li>
        </ol>
      </nav>
      <h2 class="text-3xl font-bold mb-6">Resorts List</h2>
      <div class="container mt-4">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['new_resort_url'])): ?>
            <script>
                // Open the new resort page in a new tab
                window.open('<?php echo $_SESSION['new_resort_url']; ?>', '_blank');
                <?php unset($_SESSION['new_resort_url']); ?>
            </script>
        <?php endif; ?>
      </div>
      <div class="mb-4 flex items-center space-x-4">
        <input type="text" id="search" placeholder="Search resorts..." class="border rounded p-2 flex-grow">
        <select id="filter-status" class="border rounded p-2">
          <option value="">All Statuses</option>
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </select>
      </div>
      <div class="mb-4 flex items-center space-x-4">
        <select id="bulk-action" class="border rounded p-2">
          <option value="">Bulk Actions</option>
          <option value="activate">Activate</option>
          <option value="deactivate">Deactivate</option>
          <option value="delete">Delete</option>
        </select>
        <button id="apply-bulk-action" class="bg-blue-500 text-white px-4 py-2 rounded">Apply</button>
      </div>
      <?php if(count($resorts) > 0): ?>
        <table id="resorts-table" class="min-w-full bg-white border border-gray-200">
          <thead>
            <tr>
              <th class="py-2 px-4 border-b">Resort Name</th>
              <th class="py-2 px-4 border-b">Destination</th>
              <th class="py-2 px-4 border-b">Active</th>
              <th class="py-2 px-4 border-b">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($resorts as $resort): ?>
            <tr id="resort-row-<?php echo $resort['id']; ?>">
              <td class="py-2 px-4 border-b">
                <input type="checkbox" class="resort-checkbox" value="<?php echo $resort['id']; ?>">
                <?php echo htmlspecialchars($resort['resort_name']); ?>
              </td>
              <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($resort['destination_name']); ?></td>
              <td class="py-2 px-4 border-b text-center">
                <!-- Toggle switch for active status -->
                <label class="switch">
                  <input type="checkbox" class="toggle-active" data-resort-id="<?php echo $resort['id']; ?>" <?php echo ($resort['is_active'] == 1) ? 'checked' : ''; ?>>
                  <span class="slider"></span>
                </label>
              </td>
              <td class="py-2 px-4 border-b">
                <a href="create_or_edit_resort.php?destination_id=<?php echo $resort['destination_id']; ?>&resort_id=<?php echo $resort['id']; ?>" class="bg-yellow-500 text-white px-2 py-1 rounded">Edit</a>
                <?php if ($resort['is_active'] == 1): ?>
                    <a href="<?php echo htmlspecialchars($resort['resort_slug']); ?>" target="_blank" class="bg-blue-500 text-white px-2 py-1 rounded view-button">View</a>
                <?php else: ?>
                    <a href="404.php" target="_blank" class="bg-gray-400 text-white px-2 py-1 rounded view-button">View</a>
                <?php endif; ?>
                <a href="delete_resort.php?id=<?php echo $resort['id']; ?>" class="bg-red-500 text-white px-2 py-1 rounded" onclick="return confirm('Are you sure you want to delete this resort?');">Delete</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>No resorts found.</p>
      <?php endif; ?>
      <br>
      <a href="create_or_edit_resort.php" class="bg-blue-500 text-white px-4 py-2 rounded">Create New Resort</a>
    </main>
  </div>
  <script>
    // Toggle sidebar collapse (if needed)
    document.getElementById('toggleSidebar').addEventListener('click', function() {
      var sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('sidebar-collapsed');
    });

    async function updateResortStatus(checkbox) {
      const resortId = checkbox.getAttribute('data-resort-id');
      const newStatus = checkbox.checked ? 1 : 0;
      const row = document.getElementById('resort-row-' + resortId);
      const viewButton = row.querySelector('.view-button');
      const switchContainer = checkbox.parentElement;

      // Show loading spinner
      const spinner = document.createElement('div');
      spinner.className = 'loading-spinner';
      switchContainer.style.position = 'relative';
      switchContainer.appendChild(spinner);

      try {
        const response = await fetch('update_resort_status.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ resort_id: resortId, is_active: newStatus })
        });

        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();

        if (!data.success) throw new Error(data.message || 'Failed to update status');

        // Update view button
        if (newStatus === 1) {
          viewButton.href = data.resort_slug;
          viewButton.classList.remove('bg-gray-400');
          viewButton.classList.add('bg-blue-500');
        } else {
          viewButton.href = '404.php';
          viewButton.classList.remove('bg-blue-500');
          viewButton.classList.add('bg-gray-400');
        }

        // Show success notification
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: 'Resort status updated successfully',
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000
        });
      } catch (error) {
        console.error('Error:', error);
        checkbox.checked = !checkbox.checked;
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.message,
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000
        });
      } finally {
        // Remove loading spinner
        switchContainer.removeChild(spinner);
      }
    }
    <?php echo "<!-- Line 115 Test -->"; ?>
    document.querySelectorAll('.toggle-active').forEach(function(checkbox) {
      checkbox.addEventListener('change', function() {
        updateResortStatus(this);
      });
    });

    document.getElementById('apply-bulk-action').addEventListener('click', async function() {
      const selectedResorts = Array.from(document.querySelectorAll('.resort-checkbox:checked'))
        .map(checkbox => checkbox.value);
      const action = document.getElementById('bulk-action').value;

      if (!action || selectedResorts.length === 0) {
        Swal.fire('Error', 'Please select an action and at least one resort', 'error');
        return;
      }

      try {
        const response = await fetch('bulk_action.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ resorts: selectedResorts, action })
        });

        const data = await response.json();
        if (data.success) {
          Swal.fire('Success', 'Bulk action completed successfully', 'success');
          location.reload(); // Refresh to show changes
        } else {
          throw new Error(data.message);
        }
      } catch (error) {
        Swal.fire('Error', error.message, 'error');
      }
    });

    function filterTable() {
      const search = document.getElementById('search').value.toLowerCase();
      const status = document.getElementById('filter-status').value;

      document.querySelectorAll('#resorts-table tbody tr').forEach(row => {
        const name = row.querySelector('td:first-child').textContent.toLowerCase();
        const rowStatus = row.querySelector('.toggle-active').checked ? '1' : '0';
        const matchesSearch = name.includes(search);
        const matchesStatus = status === '' || rowStatus === status;
        row.style.display = matchesSearch && matchesStatus ? '' : 'none';
      });
    }

    document.getElementById('search').addEventListener('input', filterTable);
    document.getElementById('filter-status').addEventListener('change', filterTable);

    function sortTable(columnIndex, isAsc) {
      const table = document.getElementById('resorts-table');
      const tbody = table.querySelector('tbody');
      const rows = Array.from(tbody.querySelectorAll('tr'));

      rows.sort((a, b) => {
        const aText = a.querySelectorAll('td')[columnIndex].textContent.trim();
        const bText = b.querySelectorAll('td')[columnIndex].textContent.trim();
        return isAsc ? aText.localeCompare(bText) : bText.localeCompare(aText);
      });

      while (tbody.firstChild) tbody.removeChild(tbody.firstChild);
      rows.forEach(row => tbody.appendChild(row));
    }

    document.querySelectorAll('#resorts-table th').forEach((th, index) => {
      th.style.cursor = 'pointer';
      th.addEventListener('click', () => {
        const isAsc = th.classList.toggle('asc');
        sortTable(index, isAsc);
      });
    });
  </script>

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
