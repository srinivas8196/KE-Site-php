<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();

// Include auth helper
require_once 'auth_helper.php';

// Require authentication
if (!isset($_SESSION['user_id']) || !hasPermission('campaign_manager')) {
    die("Unauthorized access - please log in with campaign manager permissions");
}

// Get database connection
$pdo = require_once 'db.php';

// Directly query the counts from the database
$query = "SELECT 
    COUNT(e.id) as total,
    SUM(CASE WHEN e.status = 'new' THEN 1 ELSE 0 END) as new_count,
    SUM(CASE WHEN e.status = 'contacted' THEN 1 ELSE 0 END) as contacted_count,
    SUM(CASE WHEN e.status = 'converted' THEN 1 ELSE 0 END) as converted_count,
    SUM(CASE WHEN e.status = 'closed' THEN 1 ELSE 0 END) as closed_count
    FROM resort_enquiries e
    LEFT JOIN resorts r ON e.resort_id = r.id";

$stats = $pdo->query($query)->fetch(PDO::FETCH_ASSOC);

// Get all enquiries for detailed breakdown
$enquiries = $pdo->query("SELECT id, status FROM resort_enquiries ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Count status breakdown manually as a check
$manualCounts = [
    'total' => count($enquiries),
    'new_count' => 0,
    'contacted_count' => 0,
    'converted_count' => 0,
    'closed_count' => 0
];

foreach ($enquiries as $enquiry) {
    $status = $enquiry['status'];
    if ($status === 'new') $manualCounts['new_count']++;
    elseif ($status === 'contacted') $manualCounts['contacted_count']++;
    elseif ($status === 'converted') $manualCounts['converted_count']++;
    elseif ($status === 'closed') $manualCounts['closed_count']++;
}

// Process an update if requested
$updateMessage = '';
if (isset($_GET['update_id']) && isset($_GET['status'])) {
    $id = (int)$_GET['update_id'];
    $status = $_GET['status'];
    
    // Validate status
    $validStatuses = ['new', 'contacted', 'converted', 'closed'];
    if (in_array($status, $validStatuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE resort_enquiries SET status = ? WHERE id = ?");
            if ($stmt->execute([$status, $id])) {
                $updateMessage = "Successfully updated enquiry #$id to status '$status'";
                // Refresh counts 
                $stats = $pdo->query($query)->fetch(PDO::FETCH_ASSOC);
                $enquiries = $pdo->query("SELECT id, status FROM resort_enquiries ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
                // Recalculate manual counts
                $manualCounts = [
                    'total' => count($enquiries),
                    'new_count' => 0,
                    'contacted_count' => 0,
                    'converted_count' => 0,
                    'closed_count' => 0
                ];
                foreach ($enquiries as $enquiry) {
                    $status = $enquiry['status'];
                    if ($status === 'new') $manualCounts['new_count']++;
                    elseif ($status === 'contacted') $manualCounts['contacted_count']++;
                    elseif ($status === 'converted') $manualCounts['converted_count']++;
                    elseif ($status === 'closed') $manualCounts['closed_count']++;
                }
            } else {
                $updateMessage = "Failed to update enquiry #$id";
            }
        } catch (Exception $e) {
            $updateMessage = "Error: " . $e->getMessage();
        }
    } else {
        $updateMessage = "Invalid status: $status";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enquiry Counts Debug Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .success {
            padding: 10px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
        }
        h3 {
            margin-top: 0;
            color: #333;
        }
        .counts {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .count-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            width: 150px;
            text-align: center;
        }
        .count-card span {
            font-size: 24px;
            font-weight: bold;
            display: block;
            margin: 5px 0;
        }
        .btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 5px;
            font-size: 14px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Enquiry Counts Debug Tool</h1>
    
    <?php if ($updateMessage): ?>
        <div class="success"><?php echo $updateMessage; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <h3>Database Query Counts</h3>
        <div class="counts">
            <div class="count-card">
                <div>Total</div>
                <span><?php echo $stats['total']; ?></span>
            </div>
            <div class="count-card">
                <div>New</div>
                <span><?php echo $stats['new_count']; ?></span>
            </div>
            <div class="count-card">
                <div>Contacted</div>
                <span><?php echo $stats['contacted_count']; ?></span>
            </div>
            <div class="count-card">
                <div>Converted</div>
                <span><?php echo $stats['converted_count']; ?></span>
            </div>
            <div class="count-card">
                <div>Closed</div>
                <span><?php echo $stats['closed_count']; ?></span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <h3>Manual Count Verification</h3>
        <div class="counts">
            <div class="count-card">
                <div>Total</div>
                <span><?php echo $manualCounts['total']; ?></span>
            </div>
            <div class="count-card">
                <div>New</div>
                <span><?php echo $manualCounts['new_count']; ?></span>
            </div>
            <div class="count-card">
                <div>Contacted</div>
                <span><?php echo $manualCounts['contacted_count']; ?></span>
            </div>
            <div class="count-card">
                <div>Converted</div>
                <span><?php echo $manualCounts['converted_count']; ?></span>
            </div>
            <div class="count-card">
                <div>Closed</div>
                <span><?php echo $manualCounts['closed_count']; ?></span>
            </div>
        </div>
    </div>
    
    <h3>Update Status Test</h3>
    <form method="get" action="debug-counts.php">
        <label for="update_id">Enquiry ID:</label>
        <input type="number" name="update_id" id="update_id" required>
        <label for="status">New Status:</label>
        <select name="status" id="status" required>
            <option value="new">New</option>
            <option value="contacted">Contacted</option>
            <option value="converted">Converted</option>
            <option value="closed">Closed</option>
        </select>
        <button type="submit">Update Status</button>
    </form>
    
    <h3>Enquiry Status Details</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($enquiries as $enquiry): ?>
                <tr>
                    <td><?php echo $enquiry['id']; ?></td>
                    <td><?php echo $enquiry['status']; ?></td>
                    <td>
                        <a href="debug-counts.php?update_id=<?php echo $enquiry['id']; ?>&status=new" class="btn">New</a>
                        <a href="debug-counts.php?update_id=<?php echo $enquiry['id']; ?>&status=contacted" class="btn">Contacted</a>
                        <a href="debug-counts.php?update_id=<?php echo $enquiry['id']; ?>&status=converted" class="btn">Converted</a>
                        <a href="debug-counts.php?update_id=<?php echo $enquiry['id']; ?>&status=closed" class="btn">Closed</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <p><a href="view_enquiries.php" class="btn">Back to Enquiries</a></p>
    
</body>
</html> 