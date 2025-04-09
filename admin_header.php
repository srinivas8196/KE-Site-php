<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Karma Experience</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Admin CSS -->
    <style>
        :root {
            --sidebar-width: 280px;
            --header-height: 60px;
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --text-light: #ecf0f1;
            --text-dark: #2c3e50;
        }

        body {
            background-color: #f8f9fa;
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .admin-content {
            flex: 1;
            padding: 20px;
            margin-left: var(--sidebar-width);
            transition: margin 0.3s ease;
        }

        @media (max-width: 991.98px) {
            .admin-content {
                margin-left: 0;
            }
        }

        /* Custom scrollbar styles */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--accent-color);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="admin-content">
            <!-- Main content will go here -->
        </div>
    </div>
</body>
</html> 