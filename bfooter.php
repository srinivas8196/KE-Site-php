<?php
// Footer file for admin pages
?>

<!-- Modern Compact Footer -->
<footer class="admin-footer">
    <div class="footer-container">
        <div class="footer-logo">
            <img src="assets/images/logo/KE-Gold.png" alt="Karma Experience" height="28">
        </div>
        <div class="footer-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="destination_list.php">Destinations</a>
            <a href="resort_list.php">Resorts</a>
            <a href="view_enquiries.php">Enquiries</a>
        </div>
        <div class="footer-copyright">
            &copy; <?php echo date("Y"); ?> Karma Experience
        </div>
    </div>
</footer>

<!-- Compact Footer Styles -->
<style>
    .admin-footer {
        background: #2a3950;
        padding: 15px 0;
        position: relative;
        z-index: 50;
        margin-top: 30px;
    }
    
    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 20px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .footer-logo {
        display: flex;
        align-items: center;
    }
    
    .footer-logo img {
        height: 28px;
        opacity: 0.9;
        transition: opacity 0.2s;
    }
    
    .footer-logo img:hover {
        opacity: 1;
    }
    
    .footer-links {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .footer-links a {
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        font-size: 0.875rem;
        transition: all 0.2s;
        position: relative;
    }
    
    .footer-links a:after {
        content: '';
        position: absolute;
        width: 0;
        height: 1px;
        bottom: -3px;
        left: 0;
        background: #b4975a;
        transition: width 0.3s;
    }
    
    .footer-links a:hover {
        color: #fff;
    }
    
    .footer-links a:hover:after {
        width: 100%;
    }
    
    .footer-copyright {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.75rem;
    }
    
    @media (max-width: 768px) {
        .footer-container {
            flex-direction: column;
            text-align: center;
            gap: 12px;
        }
        
        .footer-links {
            justify-content: center;
            gap: 15px;
        }
    }
</style>

<!-- Script to prevent continuous loading -->
<script>
// Check if page has been loading too long and fix it
(function() {
    // Check if we've been on this page for more than 5 seconds
    var pageLoadStart = window.performance && window.performance.timing ? 
        window.performance.timing.navigationStart : new Date().getTime();
    
    var loadTime = new Date().getTime() - pageLoadStart;
    
    if (loadTime > 5000 && !window.loadingStopped) {
        console.log('Page loading too long, stopping any pending requests');
        window.loadingStopped = true;
        
        // Stop loading
        if (window.stop) {
            window.stop();
        }
        
        // Stop any network requests
        if (window.jQuery && jQuery.ajax) {
            jQuery.ajax({
                global: false
            });
        }
        
        // Remove any loading indicators
        document.querySelectorAll('.loading, .spinner, .loader').forEach(function(el) {
            el.style.display = 'none';
        });
    }
})();
</script>
