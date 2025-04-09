<?php
if (session_status() == PHP_SESSION_NONE) { // Check if a session is NOT already started
    session_start(); // Start the session only if not already started
}

// Set session timeout (e.g., 1 hour)
$timeout = 3600; // 1 hour in seconds
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    // Last activity was more than 1 hour ago, destroy the session
    session_unset();
    session_destroy();
    if (!strpos($_SERVER['PHP_SELF'], 'login.php')) {
        header("Location: login.php?timeout=1"); // Added timeout parameter for login page
        exit();
    }
}
$_SESSION['LAST_ACTIVITY'] = time(); // Update last activity time

// Regenerate session ID periodically to prevent session fixation attacks
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} elseif (time() - $_SESSION['CREATED'] > 1800) { // Regenerate every 30 minutes
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

// The session check is now handled in each page before including bheader
// So we don't need to check again here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karma Experience Admin</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
    
    <!-- Admin JS - Ensures toggle functionality works on all pages -->
    <script src="assets/js/admin.js"></script>
    
    <!-- Global Toggle Script - This ensures toggle works on ALL pages -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Define the toggle function globally so it's available everywhere
        window.toggleSidebar = function() {
            const sidebar = document.querySelector('.admin-sidebar');
            const content = document.querySelector('.admin-content');
            
            if (sidebar) {
                sidebar.classList.toggle('collapsed');
                if (content) {
                    content.classList.toggle('expanded');
                }
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            }
            return false;
        };
        
        // Set up the toggle button click handler
        const toggleBtn = document.getElementById('sidebarToggle');
        if (toggleBtn) {
            // Remove any existing handlers by cloning and replacing
            const newToggle = toggleBtn.cloneNode(true);
            toggleBtn.parentNode.replaceChild(newToggle, toggleBtn);
            
            // Set both event listeners for maximum compatibility
            newToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                window.toggleSidebar();
                return false;
            });
            
            // Also set direct onclick as backup
            newToggle.onclick = function(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                window.toggleSidebar();
                return false;
            };
        }
        
        // Apply saved collapsed state from localStorage
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            const sidebar = document.querySelector('.admin-sidebar');
            const content = document.querySelector('.admin-content');
            
            if (sidebar) {
                sidebar.classList.add('collapsed');
                if (content) {
                    content.classList.add('expanded');
                }
            }
        }
    });
    </script>
    
    <!-- Stop continuous loading -->
    <script>
    // Prevent multiple initializations and continuous loading
    if (window.headerLoaded) {
        console.log('Header already loaded, preventing re-initialization');
        if (window.stop) {
            window.stop();
        }
    }
    window.headerLoaded = true;
    
    // Prevent multiple script execution
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Header DOM loaded');
        // Cancel any Ajax operations that might be running
        if (window.jQuery && jQuery.ajax) {
            jQuery.ajax({
                global: false
            });
        }
    });
    </script>

    <!-- Custom Admin Styles -->
    <style>
        :root {
            --primary: #B4975A;
            --primary-dark: #96793D;
            --secondary: #2A3950;
            --secondary-dark: #1A2537;
            --accent: #E8D7B0;
            --text-light: #F8F9FA;
            --text-dark: #212529;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f5f5;
            color: var(--text-dark);
            padding-top: 70px;
            min-height: 100vh;
            font-weight: 400;
            line-height: 1.6;
            letter-spacing: -0.01em;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Sora', sans-serif;
            font-weight: 600;
            letter-spacing: -0.02em;
            line-height: 1.3;
        }
        
        p {
            font-size: 0.95rem;
        }
        
        .btn {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            letter-spacing: -0.01em;
        }
        
        .card {
            border-radius: 12px;
            overflow: hidden;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .card-header {
            font-family: 'Sora', sans-serif;
            font-weight: 600;
            font-size: 1.2rem;
            background-color: white;
            border-bottom: 1px solid rgba(180, 151, 90, 0.2);
            padding: 1rem 1.25rem;
        }
        
        /* Admin Header */
        .admin-header {
            background: var(--secondary);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 70px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 24px;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .admin-header.scrolled {
            background: rgba(42, 57, 80, 0.98);
            backdrop-filter: blur(10px);
            height: 60px;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
        }
        
        .logo-container img {
            height: 40px;
            transition: all 0.3s ease;
        }
        
        .admin-header.scrolled .logo-container img {
            height: 35px;
        }
        
        .admin-nav {
            display: flex;
            gap: 30px;
        }
        
        .admin-nav a {
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            position: relative;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            padding: 6px 0;
        }
        
        .admin-nav a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background: var(--primary);
            transition: width 0.3s ease;
        }
        
        .admin-nav a:hover {
            color: var(--primary);
        }
        
        .admin-nav a:hover::after {
            width: 100%;
        }
        
        .user-menu {
            position: relative;
        }
        
        .user-button {
            background: var(--primary);
            border: none;
            border-radius: 50px;
            color: white;
            padding: 8px 18px;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .user-button i {
            font-size: 1rem;
        }
        
        .user-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 200px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            padding: 10px 0;
            transform: translateY(10px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 100;
        }
        
        .user-dropdown.active {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }
        
        .user-dropdown::before {
            content: '';
            position: absolute;
            top: -5px;
            right: 20px;
            width: 10px;
            height: 10px;
            background: white;
            transform: rotate(45deg);
        }
        
        .user-dropdown a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            color: var(--text-dark);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .user-dropdown a:hover {
            background: rgba(180, 151, 90, 0.1);
            color: var(--primary);
        }
        
        .user-dropdown a i {
            font-size: 1rem;
            color: var(--primary);
        }
        
        .divider {
            height: 1px;
            background: rgba(0,0,0,0.1);
            margin: 8px 0;
        }
        
        /* Mobile Menu Styles */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .mobile-menu-toggle:hover {
            color: var(--primary);
        }
        
        .mobile-menu {
            position: fixed;
            top: 70px;
            left: 0;
            width: 100%;
            background: var(--secondary-dark);
            padding: 20px;
            z-index: 999;
            transform: translateY(-100%);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .mobile-menu.active {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }
        
        .mobile-menu a {
            display: block;
            padding: 15px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .mobile-menu a:hover {
            background: rgba(180, 151, 90, 0.1);
            color: var(--primary);
            padding-left: 20px;
        }
        
        .mobile-menu a i {
            width: 24px;
            margin-right: 8px;
            color: var(--primary);
        }
        
        .mobile-menu a:last-child {
            border-bottom: none;
        }
        
        @media (max-width: 991px) {
            .admin-nav {
                display: none;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
        }
        
        @media (max-width: 576px) {
            .admin-header {
                padding: 0 16px;
            }
            
            .logo-container img {
                height: 35px;
            }
            
            .user-button {
                padding: 7px 14px;
            }
            
            .user-button span {
                display: none;
            }
        }
        
        /* Dashboard Specific Styles */
        .dashboard-card {
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid rgba(0,0,0,0.03);
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        }
        
        .dashboard-card .card-body {
            padding: 1.75rem;
        }
        
        .dashboard-card .card-title {
            color: var(--secondary);
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }
        
        .dashboard-card .card-text {
            color: #6c757d;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .dashboard-stats {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 0.75rem;
            line-height: 1.1;
        }
        
        .dashboard-stats-label {
            font-size: 0.8rem;
            font-weight: 500;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        
        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        
        .table th {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
            background-color: rgba(180, 151, 90, 0.05);
            border-bottom: 1px solid rgba(180, 151, 90, 0.2);
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .form-control {
            border-radius: 8px;
            padding: 0.6rem 1rem;
            border: 1px solid rgba(0,0,0,0.1);
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-select {
            border-radius: 8px;
            padding: 0.6rem 1rem;
            border: 1px solid rgba(0,0,0,0.1);
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<header class="admin-header">
    <!-- Logo -->
    <div class="logo-container">
        <a href="dashboard.php">
            <img src="assets/images/logo/KE-Gold.png" alt="Karma Experience">
        </a>
    </div>

    <!-- Desktop Navigation Menu -->
    <nav class="admin-nav">
        <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
        <a href="destination_list.php"><i class="fas fa-map-marker-alt me-2"></i>Destinations</a>
        <a href="resort_list.php"><i class="fas fa-hotel me-2"></i>Resorts</a>
        <a href="admin_blog.php"><i class="fas fa-blog me-2"></i>Blog</a>
        <a href="view_enquiries.php"><i class="fas fa-envelope me-2"></i>Enquiries</a>
        <a href="manage_users.php"><i class="fas fa-users me-2"></i>Users</a>
    </nav>

    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- User Menu -->
    <div class="user-menu">
        <button class="user-button" id="userMenuButton">
            <i class="fas fa-user-circle"></i>
            <span>
                <?php 
                    echo isset($_SESSION['admin_name']) ? htmlspecialchars($_SESSION['admin_name']) : 'Admin'; 
                ?>
            </span>
            <i class="fas fa-chevron-down"></i>
        </button>
        <div class="user-dropdown" id="userDropdown">
            <a href="profile.php">
                <i class="fas fa-user"></i>
                My Profile
            </a>
            <a href="settings.php">
                <i class="fas fa-cog"></i>
                Settings
            </a>
            <div class="divider"></div>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>
</header>

<!-- Mobile Navigation Menu -->
<nav class="mobile-menu" id="mobileMenu">
    <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
    <a href="destination_list.php"><i class="fas fa-map-marker-alt me-2"></i>Destinations</a>
    <a href="resort_list.php"><i class="fas fa-hotel me-2"></i>Resorts</a>
    <a href="admin_blog.php"><i class="fas fa-blog me-2"></i>Blog</a>
    <a href="view_enquiries.php"><i class="fas fa-envelope me-2"></i>Enquiries</a>
    <a href="manage_users.php"><i class="fas fa-users me-2"></i>Users</a>
</nav>

<!-- JavaScript -->
<script>
    // Header scroll effect
    window.addEventListener('scroll', function() {
        const header = document.querySelector('.admin-header');
        if (window.scrollY > 30) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // User dropdown toggle
    const userMenuButton = document.getElementById('userMenuButton');
    const userDropdown = document.getElementById('userDropdown');
    
    userMenuButton.addEventListener('click', function() {
        userDropdown.classList.toggle('active');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!userMenuButton.contains(event.target) && !userDropdown.contains(event.target)) {
            userDropdown.classList.remove('active');
        }
    });
    
    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    
    mobileMenuToggle.addEventListener('click', function() {
        mobileMenu.classList.toggle('active');
        
        // Change icon based on menu state
        const icon = mobileMenuToggle.querySelector('i');
        if (mobileMenu.classList.contains('active')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
        } else {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    });
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
