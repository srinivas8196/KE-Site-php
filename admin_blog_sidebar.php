<?php
// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Admin Sidebar Styles -->
<style>
.admin-wrapper {
    display: flex;
    min-height: calc(100vh - 70px);
}

.admin-sidebar {
    width: 280px;
    background: #2a3950;
    color: white;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    z-index: 100;
    position: fixed;
    top: 70px;
    left: 0;
    bottom: 0;
    overflow-y: auto;
}

.admin-content {
    flex: 1;
    padding: 2rem;
    margin-left: 280px;
    transition: all 0.3s ease;
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-brand {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    text-decoration: none;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-menu li {
    margin-bottom: 0.25rem;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    padding: 0.875rem 1.5rem;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 0.95rem;
}

.sidebar-menu a:hover,
.sidebar-menu a.active {
    background: rgba(180, 151, 90, 0.15);
    color: #b4975a;
}

.sidebar-menu a i {
    margin-right: 0.75rem;
    font-size: 1.1rem;
    width: 1.5rem;
    text-align: center;
}

.sidebar-divider {
    height: 1px;
    background: rgba(255,255,255,0.1);
    margin: 1rem 0;
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

#sidebarToggle {
    background-color: transparent;
    border: none;
    color: #6b7280;
    font-size: 1.25rem;
    padding: 0.5rem;
    cursor: pointer;
    transition: color 0.2s ease;
}

#sidebarToggle:hover {
    color: #b4975a;
}

.btn-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background-color: #f3f4f6;
}
</style>

<!-- Sidebar -->
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            Karma Experience
        </a>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" <?php echo ($current_page == 'dashboard.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
        </li>
        <li>
            <a href="destination_list.php" <?php echo ($current_page == 'destination_list.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-map-marker-alt"></i>
                Destinations
            </a>
        </li>
        <li>
            <a href="resort_list.php" <?php echo ($current_page == 'resort_list.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-hotel"></i>
                Resorts
            </a>
        </li>
        <div class="sidebar-divider"></div>
        <li>
            <a href="admin_blog.php" <?php echo ($current_page == 'admin_blog.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-blog"></i>
                Blog Posts
            </a>
        </li>
        <li>
            <a href="admin_blog_create.php" <?php echo ($current_page == 'admin_blog_create.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-plus-circle"></i>
                Create Post
            </a>
        </li>
        <li>
            <a href="admin_category.php" <?php echo ($current_page == 'admin_category.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-folder"></i>
                Blog Categories
            </a>
        </li>
        <div class="sidebar-divider"></div>
        <li>
            <a href="view_enquiries.php" <?php echo ($current_page == 'view_enquiries.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-envelope"></i>
                Enquiries
            </a>
        </li>
        <li>
            <a href="marketing_template_list.php" <?php echo ($current_page == 'marketing_template_list.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-envelope-open-text"></i>
                Marketing
            </a>
        </li>
        <li>
            <a href="campaign_dashboard.php" <?php echo ($current_page == 'campaign_dashboard.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-bullhorn"></i>
                Campaigns
            </a>
        </li>
        <div class="sidebar-divider"></div>
        <li>
            <a href="logout.php" class="text-danger">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </li>
    </ul>
</aside>

<script>
// Sidebar toggle for mobile
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.querySelector('.admin-sidebar').classList.toggle('show');
    });
    
    // Handle responsive sidebar toggle
    function handleSidebarToggle() {
        if (window.innerWidth > 991) {
            document.querySelector('.admin-sidebar').classList.remove('show');
        }
    }
    
    // Handle sidebar on window resize
    window.addEventListener('resize', handleSidebarToggle);
});
</script> 