// Sidebar Toggle Functionality - Global Implementation
window.addEventListener('DOMContentLoaded', function() {
    console.log('Admin JS loaded globally');
    
    // Prevent multiple initializations
    if (window.sidebarInitialized) {
        console.log('Sidebar already initialized, skipping');
        return;
    }
    
    window.sidebarInitialized = true;
    
    // Dashboard page special handling
    const isDashboard = window.location.pathname.includes('dashboard.php');
    console.log('Is dashboard page:', isDashboard);
    
    // Get DOM elements with more flexible selectors
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    // Dashboard-specific selectors
    let adminSidebar, adminContent;
    
    if (isDashboard) {
        // Dashboard page - more specific selectors
        adminSidebar = document.querySelector('.admin-sidebar');
        adminContent = document.querySelector('.admin-content');
        
        console.log('Using dashboard specific selectors');
    } else {
        // For other pages
        adminSidebar = document.querySelector('.admin-sidebar');
        adminContent = document.querySelector('.admin-content');
    }
    
    console.log('Found elements:', {
        sidebarToggle: !!sidebarToggle,
        adminSidebar: !!adminSidebar,
        adminContent: !!adminContent
    });
    
    if (sidebarToggle && adminSidebar) {
        console.log('Toggle elements found');
        
        // Ensure sidebar has the correct class
        if (!adminSidebar.classList.contains('admin-sidebar')) {
            adminSidebar.classList.add('admin-sidebar');
        }
        
        // Load saved state from localStorage
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            adminSidebar.classList.add('collapsed');
            if (adminContent) adminContent.classList.add('expanded');
            console.log('Applied saved collapsed state');
        }
        
        // Set up toggle handler
        sidebarToggle.addEventListener('click', function(e) {
            console.log('Toggle button clicked');
            e.preventDefault();
            e.stopPropagation();
            
            adminSidebar.classList.toggle('collapsed');
            if (adminContent) adminContent.classList.toggle('expanded');
            
            // Special handling for dashboard
            if (isDashboard) {
                document.querySelectorAll('.sidebar-menu a span, .sidebar-brand span, .user-name, .user-role').forEach(el => {
                    el.style.display = adminSidebar.classList.contains('collapsed') ? 'none' : '';
                });
            }
            
            // Save state to localStorage
            localStorage.setItem('sidebarCollapsed', adminSidebar.classList.contains('collapsed'));
            console.log('Sidebar toggled and state saved:', adminSidebar.classList.contains('collapsed'));
            
            return false;
        });
        
        // Also handle the collapse with pure JS approach for dashboard
        if (isDashboard) {
            sidebarToggle.onclick = function(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                adminSidebar.classList.toggle('collapsed');
                if (adminContent) adminContent.classList.toggle('expanded');
                
                // Save state to localStorage
                localStorage.setItem('sidebarCollapsed', adminSidebar.classList.contains('collapsed'));
                
                return false;
            };
        }
    } else {
        console.error('One or more sidebar elements not found:');
        console.error('sidebarToggle:', sidebarToggle);
        console.error('adminSidebar:', adminSidebar);
        console.error('adminContent:', adminContent);
    }
});

// Fallback for pages that might load slower
window.addEventListener('load', function() {
    if (!window.sidebarInitialized) {
        console.log('Late initialization of sidebar toggle');
        
        const sidebarToggle = document.getElementById('sidebarToggle');
        const adminSidebar = document.querySelector('.admin-sidebar');
        const adminContent = document.querySelector('.admin-content');
        
        if (sidebarToggle && adminSidebar && adminContent) {
            // Load saved state from localStorage
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                adminSidebar.classList.add('collapsed');
                adminContent.classList.add('expanded');
            }
            
            // Set up toggle handler
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                adminSidebar.classList.toggle('collapsed');
                adminContent.classList.toggle('expanded');
                
                // Save state to localStorage
                localStorage.setItem('sidebarCollapsed', adminSidebar.classList.contains('collapsed'));
                
                return false;
            });
            
            window.sidebarInitialized = true;
        }
    }
}); 