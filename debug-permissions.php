<?php
// Start session
session_start();
// Debug safety - only run this file if you're logged in
if (empty($_SESSION['user_id'])) {
    die('You must be logged in to use this debug tool.');
}

// Include the auth helper
require_once 'auth_helper.php';

// Helper function to check for a file
function file_exists_check($filename) {
    $exists = file_exists($filename);
    echo "<tr>
        <td>{$filename}</td>
        <td>" . ($exists ? 
            "<span style='color:green'>Yes</span>" : 
            "<span style='color:red'>No</span>") . "</td>
        </tr>";
    return $exists;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permission Debugging</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .card { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        .card h3 { margin-top: 0; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .btn { 
            display: inline-block;
            padding: 10px 15px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Permission Debugging Tool</h1>
        
        <div class="card">
            <h3>Session Data</h3>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>
        
        <div class="card">
            <h3>User Permissions Check</h3>
            <table>
                <tr>
                    <th>Role</th>
                    <th>Has Permission</th>
                </tr>
                <tr>
                    <td>super_admin</td>
                    <td><?= hasPermission('super_admin') ? '<span style="color:green">Yes</span>' : '<span style="color:red">No</span>' ?></td>
                </tr>
                <tr>
                    <td>admin</td>
                    <td><?= hasPermission('admin') ? '<span style="color:green">Yes</span>' : '<span style="color:red">No</span>' ?></td>
                </tr>
                <tr>
                    <td>campaign_manager</td>
                    <td><?= hasPermission('campaign_manager') ? '<span style="color:green">Yes</span>' : '<span style="color:red">No</span>' ?></td>
                </tr>
            </table>
        </div>
        
        <div class="card">
            <h3>Important Files Check</h3>
            <table>
                <tr>
                    <th>File</th>
                    <th>Exists</th>
                </tr>
                <?php 
                file_exists_check('view_enquiries.php');
                file_exists_check('auth_helper.php');
                file_exists_check('login.php');
                file_exists_check('dashboard.php');
                file_exists_check('sidebar.php');
                ?>
            </table>
        </div>
        
        <div class="card">
            <h3>Test Access To Pages</h3>
            <p>Click the buttons below to test access to each page:</p>
            <a href="dashboard.php" class="btn" target="_blank">Dashboard</a>
            <a href="view_enquiries.php" class="btn" target="_blank">View Enquiries</a>
            <a href="login.php" class="btn" target="_blank">Login Page</a>
        </div>
        
        <div class="card">
            <h3>Recent Fixes Applied</h3>
            <ul>
                <li>Updated view_enquiries.php to use requirePermission</li>
                <li>Fixed session variable names in login.php</li>
                <li>Simplified the .htaccess file</li>
                <li>Fixed authentication checks</li>
            </ul>
        </div>
    </div>
</body>
</html> 