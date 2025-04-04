<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db.php';

// Check if connection exists and it's valid
if (!isset($conn) || !$conn) {
    die("Database connection failed. Check your db.php file.");
}

// Tables to create with their SQL statements
$tables = [
    'settings' => "
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(255) NOT NULL UNIQUE,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ",
    'users' => "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            user_type ENUM('super_admin', 'admin', 'campaign_manager', 'user') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    "
];

// Results for displaying status
$results = [];

// Create each table
foreach ($tables as $table => $sql) {
    try {
        if ($conn->query($sql)) {
            $results[$table] = [
                'status' => 'success',
                'message' => "Table '$table' created or already exists."
            ];
        } else {
            $results[$table] = [
                'status' => 'error',
                'message' => "Failed to create table '$table': " . $conn->error
            ];
        }
    } catch (Exception $e) {
        $results[$table] = [
            'status' => 'error',
            'message' => "Error creating table '$table': " . $e->getMessage()
        ];
    }
}

// Insert default settings if the table was created successfully
if ($results['settings']['status'] == 'success') {
    $default_settings = [
        ['site_name', 'Karma Experience'],
        ['site_description', 'Luxury Travel Experiences'],
        ['admin_email', 'admin@karmaexperience.in'],
        ['items_per_page', '10'],
        ['maintenance_mode', '0']
    ];
    
    foreach ($default_settings as [$key, $value]) {
        try {
            $stmt = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->bind_param("ss", $key, $value);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Error inserting setting $key: " . $e->getMessage());
        }
    }
}

// Check if there's a super_admin user, create one if not
$check_super_admin = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'super_admin'");
$row = $check_super_admin->fetch_assoc();

if ($row['count'] == 0) {
    // Create a default super_admin user
    $username = 'admin';
    $email = 'admin@karmaexperience.in';
    $password = password_hash('admin123', PASSWORD_DEFAULT); // Default password, change immediately
    $user_type = 'super_admin';
    
    try {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $password, $user_type);
        
        if ($stmt->execute()) {
            $results['default_user'] = [
                'status' => 'success',
                'message' => "Default super_admin user created. Username: 'admin', Password: 'admin123'"
            ];
        } else {
            $results['default_user'] = [
                'status' => 'error',
                'message' => "Failed to create default user: " . $stmt->error
            ];
        }
    } catch (Exception $e) {
        $results['default_user'] = [
            'status' => 'error',
            'message' => "Error creating default user: " . $e->getMessage()
        ];
    }
}

// Display the results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Karma Experience</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8 px-4 max-w-4xl">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h1 class="text-2xl font-bold mb-6">Database Setup Results</h1>
            
            <div class="overflow-x-auto">
                <table class="min-w-full border">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 border-b">Table/Action</th>
                            <th class="px-4 py-2 border-b">Status</th>
                            <th class="px-4 py-2 border-b">Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $table => $result): ?>
                            <tr>
                                <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($table); ?></td>
                                <td class="px-4 py-2 border-b">
                                    <?php if ($result['status'] == 'success'): ?>
                                        <span class="inline-block px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Success</span>
                                    <?php else: ?>
                                        <span class="inline-block px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">Error</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($result['message']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-8">
                <h2 class="text-xl font-semibold mb-4">Next Steps</h2>
                
                <div class="bg-blue-50 border border-blue-200 p-4 rounded mb-4">
                    <p class="mb-2">You can now test different user roles with these tools:</p>
                    <ul class="list-disc ml-6 mb-2">
                        <li><a href="check_role.php" class="text-blue-600 hover:underline">Check Role</a> - Simple tool to check permissions</li>
                        <li><a href="role_test.php" class="text-blue-600 hover:underline">Role Test</a> - More detailed role testing</li>
                    </ul>
                    
                    <?php if (isset($results['default_user']) && $results['default_user']['status'] == 'success'): ?>
                        <p class="text-red-600 font-semibold">Important: Please change the default admin password immediately after first login!</p>
                    <?php endif; ?>
                </div>
                
                <div class="flex space-x-3">
                    <a href="dashboard.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Go to Dashboard</a>
                    <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Go to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 