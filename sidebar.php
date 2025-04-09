<?php
// Include auth helper if not already included
if (!function_exists('hasPermission')) {
    require_once 'auth_helper.php';
}

// Get the current page
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Admin Sidebar -->
<style>
.admin-wrapper {
    display: flex;
    min-height: calc(100vh - 70px);
    position: relative;
}

.admin-sidebar {
    width: 280px;
    background: linear-gradient(180deg, #2a3950 0%, #1e2a3b 100%);
    color: white;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    z-index: 1000;
    position: fixed;
    top: 70px;
    left: 0;
    bottom: 0;
    overflow-y: auto;
}

.admin-content {
    flex: 1;
    padding: 0;
    margin-left: 280px;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
}

/* Toggle Button Styles */
#sidebarToggle {
    position: fixed;
    top: 85px;
    left: 15px;
    background: #ffffff;
    border: none;
    color: #2c3e50;
    font-size: 1.25rem;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    z-index: 1100;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    outline: none;
}

#sidebarToggle:hover {
    background: #f8f9fa;
    color: #b4975a;
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

#sidebarToggle:active {
    transform: scale(0.95);
}

/* Collapsed Sidebar */
.admin-sidebar.collapsed {
    width: 70px;
}

.admin-sidebar.collapsed .sidebar-header h3,
.admin-sidebar.collapsed .nav-text,
.admin-sidebar.collapsed .sidebar-brand span,
.admin-sidebar.collapsed .user-details,
.admin-sidebar.collapsed .sidebar-menu a span,
.admin-sidebar.collapsed .user-info .user-name,
.admin-sidebar.collapsed .user-info .user-role,
.admin-sidebar.collapsed .sidebar-menu-item span,
.admin-sidebar.collapsed .user-avatar {
    display: none !important;
}

.admin-sidebar.collapsed .sidebar-menu a {
    justify-content: center;
    padding: 0.875rem 0;
}

.admin-sidebar.collapsed .sidebar-menu a i {
    margin-right: 0;
    font-size: 1.25rem;
}

.admin-content.expanded {
    margin-left: 70px;
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    background: rgba(0,0,0,0.1);
}

.sidebar-brand {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
}

.sidebar-brand img {
    width: 32px;
    height: 32px;
    margin-right: 0.75rem;
}

.sidebar-brand:hover {
    color: #b4975a;
    text-decoration: none;
}

.sidebar-menu {
    list-style: none;
    padding: 1rem 0;
    margin: 0;
}

.sidebar-menu li {
    margin: 0.25rem 0;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    padding: 0.875rem 1.5rem;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 0.95rem;
    position: relative;
}

.sidebar-menu a:hover,
.sidebar-menu a.active {
    background: rgba(180, 151, 90, 0.15);
    color: #b4975a;
}

.sidebar-menu a.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #b4975a;
}

.sidebar-menu a i {
    margin-right: 0.75rem;
    font-size: 1.1rem;
    width: 1.5rem;
    text-align: center;
    transition: transform 0.2s ease;
}

.sidebar-menu a:hover i {
    transform: translateX(3px);
}

.sidebar-divider {
    height: 1px;
    background: rgba(255,255,255,0.1);
    margin: 1rem 1.5rem;
}

.sidebar-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid rgba(255,255,255,0.1);
    background: rgba(0,0,0,0.1);
    position: sticky;
    bottom: 0;
}

.user-info {
    display: flex;
    align-items: center;
    padding: 0.5rem 0;
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    margin-right: 0.75rem;
    background: #b4975a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.user-details {
    flex: 1;
}

.user-name {
    font-weight: 600;
    font-size: 0.875rem;
    color: white;
    margin: 0;
}

.user-role {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.5);
    margin: 0;
}

/* Media query for responsive design */
@media (max-width: 991px) {
    .admin-sidebar {
        transform: translateX(-100%);
    }
    
    .admin-sidebar.show {
        transform: translateX(0);
    }
    
    .admin-content {
        margin-left: 0;
    }
}

@media (max-width: 991px) {
    #sidebarToggle {
        display: block;
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 1001;
    }
}

/* Custom scrollbar for sidebar */
.admin-sidebar::-webkit-scrollbar {
    width: 6px;
}

.admin-sidebar::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.1);
}

.admin-sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.2);
    border-radius: 3px;
}

.admin-sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.3);
}
</style>

<button id="sidebarToggle" type="button" onclick="if(typeof toggleSidebar==='function'){toggleSidebar();}else{document.querySelector('.admin-sidebar').classList.toggle('collapsed');document.querySelector('.admin-content').classList.toggle('expanded');}return false;">
    <i class="fas fa-bars"></i>
</button>

<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <img src="assets/images/logo/KE-Gold.png" alt="Karma Experience" onerror="this.src='assets/images/favicon.ico'">
                <span class="brand-text">Karma Experience</span>
            </a>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="destination_list.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'destination_list.php') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-map-marker-alt"></i>
                    <span class="nav-text">Destinations</span>
                </a>
            </li>
            <li>
                <a href="resort_list.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'resort_list.php') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-hotel"></i>
                    <span class="nav-text">Resorts</span>
                </a>
            </li>
            <div class="sidebar-divider"></div>
            <li>
                <a href="admin_blog.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_blog.php') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-blog"></i>
                    <span class="nav-text">Blog Posts</span>
                </a>
            </li>
            <li>
                <a href="view_enquiries.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'view_enquiries.php') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-envelope"></i>
                    <span class="nav-text">Enquiries</span>
                </a>
            </li>
            <div class="sidebar-divider"></div>
            <li>
                <a href="settings.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-cog"></i>
                    <span class="nav-text">Settings</span>
                </a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php 
                    $user_name = $_SESSION['username'] ?? 'Admin';
                    echo strtoupper(substr($user_name, 0, 1));
                    ?>
                </div>
                <div class="user-details">
                    <p class="user-name"><?php echo htmlspecialchars($user_name); ?></p>
                    <p class="user-role">Administrator</p>
                </div>
            </div>
            <div class="sidebar-divider"></div>
            <a href="logout.php" class="sidebar-menu-item text-danger" style="display: flex; align-items: center; padding: 0.5rem 0; color: #dc3545; text-decoration: none;">
                <i class="fas fa-sign-out-alt" style="margin-right: 0.75rem;"></i>
                <span class="nav-text">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Container -->
    <div class="admin-content">
        <?php if (isset($page_title)): ?>
        <div class="content-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0 text-dark font-weight-bold"><?php echo htmlspecialchars($page_title); ?></h1>
            </div>
        </div>
        <?php endif; ?>

        <!-- Content will be injected here -->
    </div>
</div> 