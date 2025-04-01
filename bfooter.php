<?php
// Footer file for admin pages
?>

<!-- Admin Footer -->
<footer class="admin-footer">
    <div class="footer-content">
        <div class="footer-brand">
            <img src="assets/images/logo/KE-Gold.png" alt="Karma Experience" class="footer-logo">
            <div class="footer-tagline">
                <p>Delivering unmatched holiday experiences at unbeatable prices</p>
            </div>
        </div>
        <div class="footer-info">
            <div class="footer-links">
                <a href="dashboard.php">Dashboard</a>
                <span class="footer-divider">|</span>
                <a href="destination_list.php">Destinations</a>
                <span class="footer-divider">|</span>
                <a href="view_enquiries.php">Enquiries</a>
                <span class="footer-divider">|</span>
                <a href="profile.php">My Profile</a>
            </div>
            <div class="copyright">
                &copy; <?php echo date("Y"); ?> Karma Experience. All Rights Reserved.
            </div>
        </div>
    </div>
</footer>

<!-- Footer Styles -->
<style>
    .admin-footer {
        background: var(--secondary);
        color: rgba(255, 255, 255, 0.8);
        padding: 20px 0;
        margin-top: 40px;
        position: relative;
    }
    
    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }
    
    .footer-brand {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .footer-logo {
        height: 40px;
        margin-bottom: 10px;
    }
    
    .footer-tagline {
        font-size: 0.85rem;
        opacity: 0.7;
        font-style: italic;
    }
    
    .footer-info {
        text-align: center;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .footer-links {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 5px;
    }
    
    .footer-links a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        font-size: 0.85rem;
        transition: all 0.3s ease;
    }
    
    .footer-links a:hover {
        color: var(--primary);
    }
    
    .footer-divider {
        color: rgba(255, 255, 255, 0.3);
    }
    
    .copyright {
        font-size: 0.8rem;
        opacity: 0.6;
    }
    
    @media (min-width: 768px) {
        .footer-content {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-brand {
            align-items: flex-start;
            text-align: left;
        }
        
        .footer-info {
            text-align: right;
            align-items: flex-end;
        }
    }
    
    /* For fixed footer on short pages */
    @media (min-height: 920px) {
        body {
            position: relative;
            min-height: 100vh;
            padding-bottom: 100px; /* Footer height + padding */
        }
        
        .admin-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
        }
    }
</style>
