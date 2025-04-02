<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];

// Fix database connection
$pdo = require 'db.php';
if (!$pdo) {
    die("Database connection failed");
}

// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Get total number of resorts for pagination
$total_stmt = $pdo->query("SELECT COUNT(*) FROM resorts");
$total_resorts = $total_stmt->fetchColumn();
$total_pages = ceil($total_resorts / $items_per_page);

// Get resorts with pagination
$stmt = $pdo->prepare("SELECT r.*, d.destination_name 
                       FROM resorts r 
                       JOIN destinations d ON r.destination_id = d.id 
                       ORDER BY d.destination_name, r.resort_name 
                       LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
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
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold">Resorts List</h2>
        <a href="create_or_edit_resort.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center">
          <i class="fas fa-plus mr-2"></i> Create New Resort
        </a>
      </div>
      
      <div class="bg-white rounded-lg shadow-md p-6">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['new_resort_url'])): ?>
            <script>
                window.open('<?php echo $_SESSION['new_resort_url']; ?>', '_blank');
                <?php unset($_SESSION['new_resort_url']); ?>
            </script>
        <?php endif; ?>

        <!-- Search and Filter Controls -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          <div class="flex items-center space-x-4">
            <div class="flex-grow">
              <input type="text" id="search" placeholder="Search resorts..." 
                     class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
              <select id="filter-status" 
                      class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Statuses</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <select id="bulk-action" 
                    class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">Bulk Actions</option>
              <option value="activate">Activate</option>
              <option value="deactivate">Deactivate</option>
              <option value="delete">Delete</option>
            </select>
            <button id="apply-bulk-action" 
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-200">
              Apply
            </button>
          </div>
        </div>

        <?php if(count($resorts) > 0): ?>
          <div class="overflow-x-auto">
            <table id="resorts-table" class="min-w-full bg-white">
              <thead class="bg-gray-50">
                <tr>
                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <input type="checkbox" id="select-all" class="rounded border-gray-300">
                  </th>
                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resort Name</th>
                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <?php foreach($resorts as $resort): ?>
                <tr id="resort-row-<?php echo $resort['id']; ?>" class="hover:bg-gray-50">
                  <td class="py-4 px-4">
                    <input type="checkbox" class="resort-checkbox rounded border-gray-300" value="<?php echo $resort['id']; ?>">
                  </td>
                  <td class="py-4 px-4"><?php echo htmlspecialchars($resort['resort_name']); ?></td>
                  <td class="py-4 px-4"><?php echo htmlspecialchars($resort['destination_name']); ?></td>
                  <td class="py-4 px-4">
                    <label class="switch">
                      <input type="checkbox" class="toggle-active" data-resort-id="<?php echo $resort['id']; ?>" 
                             <?php echo ($resort['is_active'] == 1) ? 'checked' : ''; ?>>
                      <span class="slider"></span>
                    </label>
                  </td>
                  <td class="py-4 px-4">
                    <div class="flex space-x-2">
                      <a href="create_or_edit_resort.php?destination_id=<?php echo $resort['destination_id']; ?>&resort_id=<?php echo $resort['id']; ?>" 
                         class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-lg transition duration-200">
                        <i class="fas fa-edit"></i>
                      </a>
                      <?php if ($resort['is_active'] == 1): ?>
                        <a href="<?php echo htmlspecialchars($resort['resort_slug']); ?>" target="_blank" 
                           class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-lg transition duration-200">
                          <i class="fas fa-eye"></i>
                        </a>
                      <?php else: ?>
                        <a href="404.php" target="_blank" 
                           class="bg-gray-400 text-white px-3 py-1 rounded-lg cursor-not-allowed">
                          <i class="fas fa-eye-slash"></i>
                        </a>
                      <?php endif; ?>
                      <a href="delete_resort.php?id=<?php echo $resort['id']; ?>" 
                         class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-lg transition duration-200"
                         onclick="return confirm('Are you sure you want to delete this resort?');">
                        <i class="fas fa-trash"></i>
                      </a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
          <div class="flex justify-center mt-6">
            <div class="flex space-x-2">
              <?php if ($page > 1): ?>
                <a href="?page=1" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                  <i class="fas fa-angle-double-left"></i>
                </a>
                <a href="?page=<?php echo $page - 1; ?>" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                  <i class="fas fa-angle-left"></i>
                </a>
              <?php endif; ?>

              <?php
              $start_page = max(1, $page - 2);
              $end_page = min($total_pages, $page + 2);

              for ($i = $start_page; $i <= $end_page; $i++): ?>
                <a href="?page=<?php echo $i; ?>" 
                   class="px-4 py-2 <?php echo $i === $page ? 'bg-blue-500 text-white' : 'bg-white hover:bg-gray-50'; ?> border border-gray-300 rounded-lg">
                  <?php echo $i; ?>
                </a>
              <?php endfor; ?>

              <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                  <i class="fas fa-angle-right"></i>
                </a>
                <a href="?page=<?php echo $total_pages; ?>" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                  <i class="fas fa-angle-double-right"></i>
                </a>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

        <?php else: ?>
          <div class="text-center py-8">
            <p class="text-gray-500 mb-4">No resorts found.</p>
            <a href="create_or_edit_resort.php" class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition duration-200">
              Create Your First Resort
            </a>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Toggle sidebar
      const toggleSidebar = document.getElementById('toggleSidebar');
      if (toggleSidebar) {
        toggleSidebar.addEventListener('click', function() {
          document.getElementById('sidebar').classList.toggle('sidebar-collapsed');
        });
      }

      // Select all checkbox functionality
      const selectAll = document.getElementById('select-all');
      if (selectAll) {
        selectAll.addEventListener('change', function() {
          document.querySelectorAll('.resort-checkbox').forEach(checkbox => {
            checkbox.checked = this.checked;
          });
        });
      }

      // Bulk actions
      const applyBulkAction = document.getElementById('apply-bulk-action');
      if (applyBulkAction) {
        applyBulkAction.addEventListener('click', async function() {
          const action = document.getElementById('bulk-action').value;
          if (!action) {
            Swal.fire({
              title: 'Error',
              text: 'Please select an action',
              icon: 'error'
            });
            return;
          }

          const selectedResorts = Array.from(document.querySelectorAll('.resort-checkbox:checked')).map(cb => cb.value);
          if (selectedResorts.length === 0) {
            Swal.fire({
              title: 'Error',
              text: 'Please select at least one resort',
              icon: 'error'
            });
            return;
          }

          // Confirm action
          const result = await Swal.fire({
            title: 'Confirm Action',
            text: `Are you sure you want to ${action} the selected resorts?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel'
          });

          if (result.isConfirmed) {
            // Show loading state
            const loadingToast = Swal.fire({
              title: 'Processing...',
              text: 'Please wait while we process your request',
              allowOutsideClick: false,
              showConfirmButton: false,
              willOpen: () => {
                Swal.showLoading();
              }
            });

            try {
              const response = await fetch('bulk_resort_action.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'Accept': 'application/json'
                },
                body: JSON.stringify({
                  action: action,
                  resort_ids: selectedResorts
                })
              });

              // Check if response is ok and is JSON
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }

              const contentType = response.headers.get('content-type');
              if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Invalid response format from server');
              }

              const data = await response.json();
              
              // Close loading toast
              loadingToast.close();

              if (data.success) {
                await Swal.fire({
                  title: 'Success',
                  text: data.message,
                  icon: 'success',
                  timer: 2000,
                  showConfirmButton: false
                });
                
                // Reload the page to show updated data
                window.location.reload();
              } else {
                throw new Error(data.message || 'Failed to process bulk action');
              }
            } catch (error) {
              // Close loading toast
              loadingToast.close();
              
              Swal.fire({
                title: 'Error',
                text: error.message || 'An error occurred while processing the request',
                icon: 'error'
              });
            }
          }
        });
      }

      // Resort status toggle
      document.querySelectorAll('.toggle-active').forEach(toggle => {
        toggle.addEventListener('change', async function() {
          const resortId = this.dataset.resortId;
          const newStatus = this.checked ? 1 : 0;
          const row = document.getElementById('resort-row-' + resortId);
          const viewButton = row.querySelector('a[target="_blank"]');
          
          // Show loading state
          const loadingToast = Swal.fire({
            title: 'Updating...',
            text: 'Please wait while we update the resort status',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
              Swal.showLoading();
            }
          });

          try {
            const response = await fetch('update_resort_status.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
              },
              body: JSON.stringify({
                resort_id: resortId,
                status: newStatus
              })
            });

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
              throw new Error('Invalid response format from server');
            }

            const data = await response.json();
            
            // Close loading toast
            loadingToast.close();

            if (data.success) {
              // Update view button
              if (newStatus) {
                viewButton.href = data.resort_slug;
                viewButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
                viewButton.classList.add('bg-blue-500', 'hover:bg-blue-600');
                viewButton.innerHTML = '<i class="fas fa-eye"></i>';
              } else {
                viewButton.href = '404.php';
                viewButton.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                viewButton.classList.add('bg-gray-400', 'cursor-not-allowed');
                viewButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
              }

              // Show success message
              Swal.fire({
                title: 'Success',
                text: data.message,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
              });
            } else {
              throw new Error(data.message || 'Failed to update resort status');
            }
          } catch (error) {
            // Close loading toast
            loadingToast.close();
            
            // Revert toggle state
            this.checked = !this.checked;
            
            // Show error message
            Swal.fire({
              title: 'Error',
              text: error.message || 'Failed to update resort status',
              icon: 'error'
            });
          }
        });
      });

      // Search and filter functionality
      const searchInput = document.getElementById('search');
      const statusFilter = document.getElementById('filter-status');
      let searchTimeout;

      function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;
        const rows = document.querySelectorAll('#resorts-table tbody tr');

        rows.forEach(row => {
          const resortName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
          const isActive = row.querySelector('.toggle-active').checked;
          const matchesSearch = resortName.includes(searchTerm);
          const matchesStatus = statusValue === '' || (statusValue === '1' && isActive) || (statusValue === '0' && !isActive);

          row.style.display = matchesSearch && matchesStatus ? '' : 'none';
        });
      }

      if (searchInput) {
        searchInput.addEventListener('input', () => {
          clearTimeout(searchTimeout);
          searchTimeout = setTimeout(filterTable, 300);
        });
      }

      if (statusFilter) {
        statusFilter.addEventListener('change', filterTable);
      }

      // Table sorting
      document.querySelectorAll('#resorts-table th').forEach((header, index) => {
        if (index > 0 && index < 3) { // Only make Resort Name and Destination sortable
          header.addEventListener('click', () => {
            const isAsc = header.classList.toggle('asc');
            header.classList.toggle('desc', !isAsc);
            sortTable(index, isAsc);
          });
        }
      });

      function sortTable(column, isAsc) {
        const tbody = document.querySelector('#resorts-table tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
          const aValue = a.querySelector(`td:nth-child(${column + 1})`).textContent.trim();
          const bValue = b.querySelector(`td:nth-child(${column + 1})`).textContent.trim();
          return isAsc ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
        });

        tbody.innerHTML = '';
        rows.forEach(row => tbody.appendChild(row));
      }
    });
  </script>

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
