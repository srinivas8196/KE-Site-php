<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];
require_once '../includes/db.php';

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$resort_id = isset($_GET['resort_id']) ? $_GET['resort_id'] : '';
$destination_id = isset($_GET['destination_id']) ? $_GET['destination_id'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$query = "SELECT * FROM resort_enquiries WHERE 1=1";
$params = [];
$types = "";

if ($status) {
    $query .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($resort_id) {
    $query .= " AND resort_id = ?";
    $params[] = $resort_id;
    $types .= "i";
}

if ($destination_id) {
    $query .= " AND destination_id = ?";
    $params[] = $destination_id;
    $types .= "i";
}

if ($date_from) {
    $query .= " AND DATE(created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $query .= " AND DATE(created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$query .= " ORDER BY created_at DESC";

// Get resorts and destinations for filters
$resorts = $conn->query("SELECT id, resort_name FROM resorts ORDER BY resort_name");
$destinations = $conn->query("SELECT id, destination_name FROM destinations ORDER BY destination_name");

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

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
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        .sidebar-collapsed { width: 64px; }
        .sidebar-collapsed .sidebar-item-text { display: none; }
        .sidebar-collapsed .sidebar-icon { text-align: center; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
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
                <h1 class="mt-4 text-3xl font-bold mb-6">Resort Enquiries</h1>
                
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header bg-white p-4 border-b">
                        <i class="fas fa-filter me-1"></i> Filter Enquiries
                    </div>
                    <div class="card-body bg-white p-4">
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">All</option>
                                    <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>New</option>
                                    <option value="contacted" <?php echo $status === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                    <option value="converted" <?php echo $status === 'converted' ? 'selected' : ''; ?>>Converted</option>
                                    <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            <div>
                                <label for="resort_id" class="block text-sm font-medium text-gray-700 mb-1">Resort</label>
                                <select name="resort_id" id="resort_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">All</option>
                                    <?php while ($resort = $resorts->fetch_assoc()): ?>
                                        <option value="<?php echo $resort['id']; ?>" <?php echo $resort_id == $resort['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($resort['resort_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div>
                                <label for="destination_id" class="block text-sm font-medium text-gray-700 mb-1">Destination</label>
                                <select name="destination_id" id="destination_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">All</option>
                                    <?php while ($destination = $destinations->fetch_assoc()): ?>
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
                            <div class="flex items-end gap-2">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">Filter</button>
                                <a href="view_enquiries.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Enquiries Table -->
                <div class="card mb-4">
                    <div class="card-header bg-white p-4 border-b">
                        <i class="fas fa-table me-1"></i> Enquiries List
                    </div>
                    <div class="card-body bg-white p-4">
                        <table id="enquiriesTable" class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="p-3">Date</th>
                                    <th class="p-3">Name</th>
                                    <th class="p-3">Email</th>
                                    <th class="p-3">Phone</th>
                                    <th class="p-3">Resort</th>
                                    <th class="p-3">Destination</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="border-b">
                                        <td class="p-3"><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($row['phone']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($row['resort_name']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($row['destination_name']); ?></td>
                                        <td class="p-3">
                                            <select class="status-select w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" data-enquiry-id="<?php echo $row['id']; ?>">
                                                <option value="new" <?php echo $row['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                                <option value="contacted" <?php echo $row['status'] === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                                <option value="converted" <?php echo $row['status'] === 'converted' ? 'selected' : ''; ?>>Converted</option>
                                                <option value="closed" <?php echo $row['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                            </select>
                                        </td>
                                        <td class="p-3">
                                            <button type="button" class="view-details bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-md text-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#enquiryModal" 
                                                    data-enquiry='<?php echo json_encode($row); ?>'>
                                                View Details
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Enquiry Details Modal -->
    <div class="modal fade" id="enquiryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enquiry Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p><strong>Name:</strong> <span id="modal-name"></span></p>
                            <p><strong>Email:</strong> <span id="modal-email"></span></p>
                            <p><strong>Phone:</strong> <span id="modal-phone"></span></p>
                            <p><strong>Date of Birth:</strong> <span id="modal-dob"></span></p>
                        </div>
                        <div>
                            <p><strong>Resort:</strong> <span id="modal-resort"></span></p>
                            <p><strong>Destination:</strong> <span id="modal-destination"></span></p>
                            <p><strong>Resort Code:</strong> <span id="modal-resort-code"></span></p>
                            <p><strong>Has Passport:</strong> <span id="modal-passport"></span></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p><strong>Enquiry Date:</strong> <span id="modal-date"></span></p>
                        <p><strong>Current Status:</strong> <span id="modal-status"></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Toastr -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        $('#enquiriesTable').DataTable({
            order: [[0, 'desc']], // Sort by date descending
            pageLength: 25
        });

        // Handle status changes
        $('.status-select').change(function() {
            const enquiryId = $(this).data('enquiry-id');
            const newStatus = $(this).val();
            
            $.ajax({
                url: 'update_enquiry_status.php',
                method: 'POST',
                data: {
                    enquiry_id: enquiryId,
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Status updated successfully');
                    } else {
                        toastr.error('Failed to update status');
                    }
                },
                error: function() {
                    toastr.error('An error occurred while updating status');
                }
            });
        });

        // Handle view details modal
        $('.view-details').click(function() {
            const enquiry = JSON.parse($(this).data('enquiry'));
            $('#modal-name').text(enquiry.first_name + ' ' + enquiry.last_name);
            $('#modal-email').text(enquiry.email);
            $('#modal-phone').text(enquiry.phone);
            $('#modal-dob').text(enquiry.date_of_birth);
            $('#modal-resort').text(enquiry.resort_name);
            $('#modal-destination').text(enquiry.destination_name);
            $('#modal-resort-code').text(enquiry.resort_code);
            $('#modal-passport').text(enquiry.has_passport);
            $('#modal-date').text(new Date(enquiry.created_at).toLocaleString());
            $('#modal-status').text(enquiry.status.charAt(0).toUpperCase() + enquiry.status.slice(1));
        });

        // Toggle sidebar
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('sidebar-collapsed');
        });
    });
    </script>
</body>
</html>
<?php include 'bfooter.php'; ?> 