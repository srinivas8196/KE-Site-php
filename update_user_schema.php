<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db.php';

// Check if connection exists and it's valid
if (!isset($conn) || !$conn) {
    die("Database connection failed. Check your db.php file.");
}

// Alter users table to add reset token fields
$alterQueries = [
    "ALTER TABLE users ADD COLUMN phone_number VARCHAR(20) DEFAULT NULL AFTER user_type",
    "ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) DEFAULT NULL AFTER phone_number",
    "ALTER TABLE users ADD COLUMN reset_token_expires DATETIME DEFAULT NULL AFTER reset_token"
];

// Results for displaying status
$results = [];

// Run each alter statement
foreach ($alterQueries as $index => $sql) {
    try {
        if ($conn->query($sql)) {
            $results[$index] = [
                'status' => 'success',
                'message' => "Database schema updated successfully."
            ];
        } else {
            // If the alter fails because the column already exists, it's still a success
            if (strpos($conn->error, 'Duplicate column') !== false) {
                $results[$index] = [
                    'status' => 'success',
                    'message' => "Column already exists."
                ];
            } else {
                $results[$index] = [
                    'status' => 'error',
                    'message' => "Failed to update schema: " . $conn->error
                ];
            }
        }
    } catch (Exception $e) {
        // If error is about duplicate column, treat as success
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            $results[$index] = [
                'status' => 'success',
                'message' => "Column already exists: " . $e->getMessage()
            ];
        } else {
            $results[$index] = [
                'status' => 'error',
                'message' => "Error updating schema: " . $e->getMessage()
            ];
        }
    }
}

// Display the results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Schema Update - Karma Experience</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h1 class="h3 mb-0">User Schema Update Results</h1>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Operation</th>
                            <th>Status</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $index => $result): ?>
                            <tr>
                                <td>
                                    <code><?php echo htmlspecialchars($alterQueries[$index]); ?></code>
                                </td>
                                <td>
                                    <?php if ($result['status'] == 'success'): ?>
                                        <span class="badge bg-success">Success</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Error</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($result['message']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="alert alert-info mt-4">
                    <p><strong>Next Steps:</strong></p>
                    <p>Now that the user schema has been updated, the system can support:</p>
                    <ul>
                        <li>Password reset functionality via email</li>
                        <li>First-time password setup for new users</li>
                        <li>Storing phone numbers for users</li>
                    </ul>
                </div>
                
                <div class="mt-4">
                    <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <a href="login.php" class="btn btn-outline-secondary ms-2">Go to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 