<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];
$pdo = require_once 'db.php';

// Get the base URL dynamically
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$current_dir = dirname($_SERVER['PHP_SELF']);
if ($current_dir != '/') {
    $base_url .= $current_dir;
}

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$resort_id = isset($_GET['resort_id']) ? $_GET['resort_id'] : '';
$destination_id = isset($_GET['destination_id']) ? $_GET['destination_id'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$country_code = isset($_GET['country_code']) ? $_GET['country_code'] : '';
$is_partner = isset($_GET['is_partner']) ? $_GET['is_partner'] : '';

// Build query
$query = "SELECT e.*, r.is_partner FROM resort_enquiries e LEFT JOIN resorts r ON e.resort_id = r.id WHERE 1=1";
$params = [];

if ($status) {
    $query .= " AND e.status = ?";
    $params[] = $status;
}

if ($resort_id) {
    $query .= " AND e.resort_id = ?";
    $params[] = $resort_id;
}

if ($destination_id) {
    $query .= " AND e.destination_id = ?";
    $params[] = $destination_id;
}

if ($date_from) {
    $query .= " AND DATE(e.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(e.created_at) <= ?";
    $params[] = $date_to;
}

if ($country_code) {
    if ($country_code === 'OTHER') {
        $query .= " AND (e.country_code NOT IN ('AU', 'ID', 'IN', 'GB') OR e.country_code IS NULL)";
    } else {
        $query .= " AND e.country_code = ?";
        $params[] = $country_code;
    }
}

if ($is_partner !== '') {
    $query .= " AND r.is_partner = ?";
    $params[] = $is_partner;
}

$query .= " ORDER BY e.created_at DESC";

// Get resorts and destinations for filters
$resorts = $pdo->query("SELECT id, resort_name FROM resorts ORDER BY resort_name");
$destinations = $pdo->query("SELECT id, destination_name FROM destinations ORDER BY destination_name");

// Prepare and execute query
$stmt = $pdo->prepare($query);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'bheader.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Enquiries - Admin Dashboard</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <!-- Toastr CSS and JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .sidebar-collapsed { width: 64px; }
        .sidebar-collapsed .sidebar-item-text { display: none; }
        .sidebar-collapsed .sidebar-icon { text-align: center; }
        
        /* DataTables customization */
        div.dt-buttons {
            float: right;
            margin-bottom: 1rem;
        }
        table.dataTable {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }
        .status-new { background-color: #FEF3C7; }
        .status-contacted { background-color: #DBEAFE; }
        .status-converted { background-color: #D1FAE5; }
        .status-closed { background-color: #F3F4F6; }
        
        /* Modal styles */
        .modal-open {
            overflow: hidden;
        }
        
        #enquiryDetailModal {
            backdrop-filter: blur(4px);
        }
        
        #closeModal {
            transition: all 0.2s ease-in-out;
        }
        
        #closeModal:hover {
            transform: rotate(90deg);
        }
        
        /* Ensure modal content doesn't overflow on mobile */
        @media (max-width: 640px) {
            .max-w-2xl {
                margin: 1rem;
                max-height: calc(100vh - 2rem);
                overflow-y: auto;
            }
        }
        
        /* Modern table styling */
        #enquiriesTable {
            @apply shadow-sm rounded-lg overflow-hidden;
        }
        
        #enquiriesTable thead th {
            @apply bg-gray-50 text-gray-600 text-sm font-medium px-6 py-3;
        }
        
        #enquiriesTable tbody td {
            @apply px-6 py-4 text-sm text-gray-800;
        }
        
        /* Status indicators */
        .status-new { @apply border-l-4 border-amber-400; }
        .status-contacted { @apply border-l-4 border-blue-400; }
        .status-converted { @apply border-l-4 border-emerald-400; }
        .status-closed { @apply border-l-4 border-gray-400; }
        
        /* Button styling */
        .view-details {
            @apply inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200;
        }
        
        /* DataTable custom styling */
        .dataTables_wrapper .dataTables_filter input {
            @apply rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500;
        }
        
        .dataTables_wrapper .dataTables_length select {
            @apply rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500;
        }
        
        /* Pagination styling */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            @apply px-3 py-1 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            @apply bg-indigo-50 border-indigo-500 text-indigo-600 hover:bg-indigo-100;
        }
    </style>
    <script>
        // Add base URL for JavaScript use
        const baseUrl = '<?php echo $base_url; ?>';
    </script>
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
                    <li class="text-gray-600">Resort Enquiries</li>
                </ol>
            </nav>
            
            <div class="container-fluid px-4">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-3xl font-bold">Resort Enquiries</h1>
                    <div>
                        <button id="btnAdvancedFilters" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md flex items-center">
                            <i class="fas fa-filter mr-2"></i> Advanced Filters
                        </button>
                    </div>
                </div>
                
                <!-- Advanced Filters (Hidden by default) -->
                <div id="advancedFilters" class="bg-white rounded-lg shadow mb-6 p-4 hidden">
                    <h2 class="text-lg font-semibold mb-4">Advanced Filters</h2>
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Statuses</option>
                                    <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>New</option>
                                    <option value="contacted" <?php echo $status === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                    <option value="converted" <?php echo $status === 'converted' ? 'selected' : ''; ?>>Converted</option>
                                    <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            <div>
                                <label for="resort_id" class="block text-sm font-medium text-gray-700 mb-1">Resort</label>
                                <select name="resort_id" id="resort_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Resorts</option>
                                <?php while ($resort = $resorts->fetch()): ?>
                                        <option value="<?php echo $resort['id']; ?>" <?php echo $resort_id == $resort['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($resort['resort_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div>
                                <label for="destination_id" class="block text-sm font-medium text-gray-700 mb-1">Destination</label>
                                <select name="destination_id" id="destination_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Destinations</option>
                                <?php while ($destination = $destinations->fetch()): ?>
                                        <option value="<?php echo $destination['id']; ?>" <?php echo $destination_id == $destination['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($destination['destination_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div>
                                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                                <input type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div>
                                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                                <input type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                        <div>
                            <label for="country_code" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                            <select name="country_code" id="country_code" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Countries</option>
                                <option value="AU" <?php echo isset($_GET['country_code']) && $_GET['country_code'] === 'AU' ? 'selected' : ''; ?>>Australia</option>
                                <option value="ID" <?php echo isset($_GET['country_code']) && $_GET['country_code'] === 'ID' ? 'selected' : ''; ?>>Indonesia</option>
                                <option value="IN" <?php echo isset($_GET['country_code']) && $_GET['country_code'] === 'IN' ? 'selected' : ''; ?>>India</option>
                                <option value="GB" <?php echo isset($_GET['country_code']) && $_GET['country_code'] === 'GB' ? 'selected' : ''; ?>>United Kingdom</option>
                                <option value="OTHER" <?php echo isset($_GET['country_code']) && $_GET['country_code'] === 'OTHER' ? 'selected' : ''; ?>>Other Countries</option>
                            </select>
                        </div>
                        <div>
                            <label for="is_partner" class="block text-sm font-medium text-gray-700 mb-1">Resort Type</label>
                            <select name="is_partner" id="is_partner" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Types</option>
                                <option value="0" <?php echo isset($_GET['is_partner']) && $_GET['is_partner'] === '0' ? 'selected' : ''; ?>>Normal Resorts</option>
                                <option value="1" <?php echo isset($_GET['is_partner']) && $_GET['is_partner'] === '1' ? 'selected' : ''; ?>>Partner Hotels</option>
                            </select>
                        </div>
                            <div class="flex items-end gap-2">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md flex items-center">
                                <i class="fas fa-search mr-2"></i> Apply Filters
                            </button>
                            <a href="view_enquiries.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md flex items-center">
                                <i class="fas fa-times mr-2"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Summary Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <?php
                    // Count total enquiries
                    $statsStmt = $pdo->query("SELECT 
                        COUNT(e.id) as total,
                        SUM(CASE WHEN e.status = 'new' THEN 1 ELSE 0 END) as new_count,
                        SUM(CASE WHEN e.status = 'contacted' THEN 1 ELSE 0 END) as contacted_count,
                        SUM(CASE WHEN e.status = 'converted' THEN 1 ELSE 0 END) as converted_count,
                        SUM(CASE WHEN e.status = 'closed' THEN 1 ELSE 0 END) as closed_count
                        FROM resort_enquiries e
                        LEFT JOIN resorts r ON e.resort_id = r.id
                        WHERE 1=1");
                    $stats = $statsStmt->fetch();
                    ?>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total Enquiries</p>
                                <p class="text-2xl font-semibold"><?php echo $stats['total']; ?></p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-clipboard-list text-blue-500"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">New</p>
                                <p class="text-2xl font-semibold"><?php echo $stats['new_count']; ?></p>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <i class="fas fa-envelope text-yellow-500"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Contacted</p>
                                <p class="text-2xl font-semibold"><?php echo $stats['contacted_count']; ?></p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-phone text-blue-500"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Converted</p>
                                <p class="text-2xl font-semibold"><?php echo $stats['converted_count']; ?></p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-check-circle text-green-500"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enquiries Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-6">
                        <table id="enquiriesTable" class="display responsive nowrap w-full">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Country</th>
                                    <th>Resort</th>
                                    <th>Lead Source</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result as $row): ?>
                                    <tr class="status-<?php echo strtolower($row['status']); ?>">
                                        <td>EQ<?php echo $row['id']; ?></td>
                                        <td data-sort="<?php echo strtotime($row['created_at']); ?>"><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($row['country_code'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['resort_name']); ?></td>
                                        <td title="<?php echo htmlspecialchars($row['lead_source_description'] ?? ''); ?>"><?php echo htmlspecialchars($row['lead_sub_brand'] ?? 'N/A'); ?></td>
                                        <td>
                                            <select class="status-select rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" 
                                                    data-enquiry-id="<?php echo $row['id']; ?>" 
                                                    data-original-status="<?php echo $row['status']; ?>"
                                                    style="padding: 4px; min-width: 110px;">
                                                <option value="new" <?php echo $row['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                                <option value="contacted" <?php echo $row['status'] === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                                <option value="converted" <?php echo $row['status'] === 'converted' ? 'selected' : ''; ?>>Converted</option>
                                                <option value="closed" <?php echo $row['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="button" class="view-details bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-md text-sm" 
                                                    data-enquiry-id="<?php echo $row['id']; ?>">
                                                View Details
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Detail Modal -->
    <div id="enquiryDetailModal" class="fixed inset-0 z-50 overflow-auto bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-auto relative">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-xl font-semibold" id="modal-title">Enquiry Details</h3>
                <button type="button" class="text-gray-500 hover:text-gray-700 focus:outline-none p-2 rounded-full hover:bg-gray-100 transition-colors" id="closeModal">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6" id="modal-content">
                <!-- Loading state -->
                <div class="text-center p-8">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Loading enquiry details...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize DataTable
        $(document).ready(function() {
            // Show/hide advanced filters
            $('#btnAdvancedFilters').click(function() {
                $('#advancedFilters').toggle();
            });
            
            // Show advanced filters if any filter is applied
            if ('<?php echo $status; ?>' || '<?php echo $resort_id; ?>' || '<?php echo $destination_id; ?>' || '<?php echo $date_from; ?>' || '<?php echo $date_to; ?>' || '<?php echo $country_code; ?>' || '<?php echo $is_partner; ?>') {
                $('#advancedFilters').show();
            }
            
            // Status change handler
            $('.status-select').on('change', function() {
                const enquiryId = $(this).data('enquiry-id');
                const newStatus = $(this).val();
                const row = $(this).closest('tr');
                const select = $(this);
                const originalSelect = $(this).clone();
                
                // Show loading state
                const loadingHtml = '<div class="flex items-center justify-center"><div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500"></div></div>';
                const selectCell = $(this).parent();
                selectCell.html(loadingHtml);
                
                // Send the update request
                $.ajax({
                    url: 'update_enquiry_status.php',
                    method: 'POST',
                    data: JSON.stringify({
                        enquiry_id: enquiryId,
                        status: newStatus
                    }),
                    contentType: 'application/json',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Update the select element with new status
                            originalSelect.val(newStatus);
                            selectCell.html(originalSelect);
                            
                            // Update row status
                            row.removeClass('status-new status-contacted status-converted status-closed')
                               .addClass('status-' + newStatus);
                            
                            // Show success message
                            toastr.success('Status updated successfully');
                            
                            // If status was changed to converted, update UI accordingly
                            if (newStatus === 'converted') {
                                row.addClass('bg-green-50');
                                setTimeout(() => row.removeClass('bg-green-50'), 3000);
                            }
                            
                            // Reinitialize the event handler on the new select
                            selectCell.find('.status-select').on('change', function() {
                                $(this).trigger('change');
                            });
                        } else {
                            // Show error message
                            toastr.error(response.message || 'Failed to update status');
                            selectCell.html(originalSelect);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        // Show error message
                        toastr.error('Failed to update status. Please try again.');
                        // Restore original select
                        selectCell.html(originalSelect);
                    }
                });
            });
            
            // Initialize DataTable with improved styling
            var table = $('#enquiriesTable').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel mr-1"></i> Export to Excel',
                        className: 'bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-md text-sm transition-colors duration-200',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6, 7]
                        }
                    }
                ],
                order: [[1, 'desc']],
                pageLength: 25,
                language: {
                    search: "<i class='fas fa-search'></i> _INPUT_",
                    searchPlaceholder: "Search enquiries..."
                },
                rowCallback: function(row, data, index) {
                    // Add hover effect
                    $(row).addClass('hover:bg-gray-50 transition-colors duration-150');
                }
            });

            // Improved status select styling
            $('.status-select').each(function() {
                const status = $(this).val();
                $(this).addClass('rounded-full px-3 py-1 text-sm font-medium ' + getStatusClass(status));
            });
            
            // Direct view details functionality
            $(document).on('click', '.view-details', function() {
                const enquiryId = $(this).data('enquiry-id');
                
                // Show modal and loading state
                $('#modal-content').html('<div class="text-center p-8"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading enquiry details...</p></div>');
                $('#enquiryDetailModal').removeClass('hidden');
                
                // Fetch using fetch API for better error handling
                fetch(`${baseUrl}/get_enquiry_details.php?id=${enquiryId}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data || data.success === false) {
                        throw new Error(data ? data.message : 'Unknown error');
                    }
                    
                    // Create modal content HTML
                    const modalHtml = `
                    <div class="flex flex-col space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="text-lg font-medium mb-3 text-gray-700 border-b pb-2">Customer Information</h4>
                                <div class="space-y-2">
                                    <p><span class="font-medium text-gray-600">Name:</span> ${data.first_name || ''} ${data.last_name || ''}</p>
                                    <p><span class="font-medium text-gray-600">Email:</span> ${data.email || 'Not provided'}</p>
                                    <p><span class="font-medium text-gray-600">Phone:</span> ${data.phone || 'Not provided'}</p>
                                    <p><span class="font-medium text-gray-600">Date of Birth:</span> ${data.date_of_birth || 'Not provided'}</p>
                                    <p><span class="font-medium text-gray-600">Has Passport:</span> ${data.has_passport || 'Not provided'}</p>
                                    <p><span class="font-medium text-gray-600">Country:</span> ${data.country_code || 'Not provided'}</p>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-lg font-medium mb-3 text-gray-700 border-b pb-2">Resort Information</h4>
                                <div class="space-y-2">
                                    <p><span class="font-medium text-gray-600">Resort:</span> ${data.resort_name || 'Not available'}</p>
                                    <p><span class="font-medium text-gray-600">Destination:</span> ${data.destination_name || 'Not available'}</p>
                                    <p><span class="font-medium text-gray-600">Resort Code:</span> ${data.resort_code || 'Not available'}</p>
                                    <p><span class="font-medium text-gray-600">Date Submitted:</span> ${data.created_at ? new Date(data.created_at).toLocaleString() : 'Unknown'}</p>
                                    <p><span class="font-medium text-gray-600">Status:</span> <span class="px-2 py-1 rounded ${getStatusClass(data.status)}">${data.status ? (data.status.charAt(0).toUpperCase() + data.status.slice(1)) : 'Unknown'}</span></p>
                                    <p><span class="font-medium text-gray-600">Partner Hotel:</span> ${data.is_partner == 1 ? 'Yes' : 'No'}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="text-lg font-medium mb-3 text-gray-700 border-b pb-2">LeadSquared Details</h4>
                            <div class="bg-gray-50 p-4 rounded">
                                <div class="space-y-2">
                                    <p><span class="font-medium text-gray-600">Lead Source:</span> ${data.lead_source || 'Web Enquiry'}</p>
                                    <p><span class="font-medium text-gray-600">Lead Brand:</span> ${data.lead_brand || 'Timeshare Marketing'}</p>
                                    <p><span class="font-medium text-gray-600">Lead Sub Brand:</span> ${data.lead_sub_brand || 'Not available'}</p>
                                    <p><span class="font-medium text-gray-600">Lead Source Description:</span> ${data.lead_source_description || 'Not available'}</p>
                                    <p><span class="font-medium text-gray-600">Lead Location:</span> ${data.lead_location || data.resort_name || 'Not available'}</p>
                                    <p><span class="font-medium text-gray-600">LeadSquared ID:</span> ${data.leadsquared_id || 'Not available'}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t">
                            <h4 class="text-lg font-medium mb-3 text-gray-700">Actions</h4>
                            <div class="flex flex-wrap gap-3">
                                <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 ease-in-out flex items-center" id="emailCustomer" data-email="${data.email}">
                                    <i class="fas fa-envelope mr-2"></i> Email Customer
                                </button>
                                <button class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 ease-in-out flex items-center" id="markConverted" data-id="${data.id}">
                                    <i class="fas fa-check-circle mr-2"></i> Mark as Converted
                                </button>
                            </div>
                        </div>
                    </div>
                    `;
                    
                    // Update modal title and content
                    $('#modal-title').text('Enquiry Details: EQ' + data.id);
                    $('#modal-content').html(modalHtml);
                    
                    // Setup action button handlers
                    $('#emailCustomer').on('click', function() {
                        const email = $(this).data('email');
                        window.location.href = 'mailto:' + email + '?subject=Your Enquiry about ' + data.resort_name;
                    });
                    
                    $('#markConverted').on('click', function() {
                        const id = $(this).data('id');
                        fetch(`${baseUrl}/update_enquiry_status.php`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                enquiry_id: id,
                                status: 'converted'
                            })
                        })
                        .then(response => response.json())
                        .then(response => {
                            if (response.success) {
                                $('#enquiryDetailModal').addClass('hidden');
                                toastr.success('Enquiry marked as converted');
                                location.reload();
                            } else {
                                throw new Error(response.message || 'Failed to update status');
                            }
                        })
                        .catch(error => {
                            console.error('Error updating status:', error);
                            toastr.error('Failed to update status: ' + error.message);
                        });
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    $('#modal-content').html(`
                        <div class="text-center p-8 text-red-500">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <p>Error loading enquiry details: ${error.message}</p>
                        </div>
                    `);
                });
            });
            
            // Helper function for status styling
            function getStatusClass(status) {
                switch(status) {
                    case 'new': 
                        return 'bg-amber-100 text-amber-800 border-amber-200';
                    case 'contacted': 
                        return 'bg-blue-100 text-blue-800 border-blue-200';
                    case 'converted': 
                        return 'bg-emerald-100 text-emerald-800 border-emerald-200';
                    case 'closed': 
                        return 'bg-gray-100 text-gray-800 border-gray-200';
                    default: 
                        return 'bg-gray-100 text-gray-800 border-gray-200';
                }
            }
            
            // Close modal
            $('#closeModal').click(function() {
                $('#enquiryDetailModal').addClass('hidden');
            });
            
            // Close modal when clicking outside
            $(window).click(function(event) {
                if ($(event.target).is('#enquiryDetailModal')) {
                    $('#enquiryDetailModal').addClass('hidden');
                }
            });
        });
    </script>
</body>
</html>
<?php include 'bfooter.php'; ?> 