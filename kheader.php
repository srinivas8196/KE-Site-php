<?php
session_start();

// Session timeout functionality (1 hour = 3600 seconds)
$session_timeout = 3600; // 1 hour in seconds

// Check if user is logged in
if (isset($_SESSION['user'])) {
    // Check if last_activity is set
    if (isset($_SESSION['last_activity'])) {
        // Calculate time since last activity
        $inactive_time = time() - $_SESSION['last_activity'];
        
        // If inactive for more than session_timeout, destroy session and redirect to login
        if ($inactive_time >= $session_timeout) {
            // Perform logout actions
            session_unset();     // Remove all session variables
            session_destroy();   // Destroy the session
            
            // Redirect to login page
            header("Location: login.php?timeout=1");
            exit();
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}
?>
<!doctype html>
<html class="no-js" lang="zxx">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Karma Experience India |  Delivering unmatched holiday experiences at unbeatable prices</title>
    <meta name="author" content="Karma Experience">
    <meta name="description" content="Karma Experience - Delivering unmatched holiday experiences at unbeatable prices ">
    <meta name="keywords" content="Karma Experience - Delivering unmatched holiday experiences at unbeatable prices">
    <meta name="robots" content="INDEX,FOLLOW">
    <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="57x57" href="assets/images/logo/K-logo.png">
    <link rel="apple-touch-icon" sizes="60x60" href="assets/images/logo/K-logo.png">
    <link rel="apple-touch-icon" sizes="72x72" href="assets/images/logo/K-logo.png">
    <link rel="apple-touch-icon" sizes="76x76" href="assets/images/logo/K-logo.png">
    <link rel="apple-touch-icon" sizes="114x114" href="assets/images/logo/K-logo.png">
    <link rel="apple-touch-icon" sizes="120x120" href="assets/images/logo/K-logo.png">
    <link rel="apple-touch-icon" sizes="144x144" href="assets/images/logo/K-logo.png">
    <link rel="apple-touch-icon" sizes="152x152" href="assets/images/logo/K-logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/logo/K-logo.png">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/images/logo/K-logo.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logo/K-logo.png">
    <link rel="icon" type="image/png" sizes="96x96" href="assets/images/logo/K-logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/logo/K-logo.png">
    <link rel="manifest" href="assets/img/favicons/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="assets/img/favicons/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&amp;family=Manrope:wght@200..800&amp;family=Montez&amp;display=swap"
        rel="stylesheet">
    <!-- Start of included CSS files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="assets/css/magnific-popup.min.css">
    <link rel="stylesheet" href="assets/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- End of included CSS files -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"> Add Tailwind CSS -->
    <style>
        /* Enhanced Mega Menu Styles */
        .th-menu-wrapper {
            transition: all 0.3s ease;
        }
        
        .th-mobile-menu {
            display: none;
        }
        
        .th-mobile-menu.active {
            display: block;
        }
        
        .submenu {
            display: none;
            padding-left: 20px;
        }
        
        .menu-item-has-children.active .submenu {
            display: block;
        }
        
        /* Mega Menu Styling */
        .mega-menu {
            position: absolute;
            left: 0;
            right: 0;
            background: #fff;
            padding: 30px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
            z-index: 99;
            border-top: 2px solid #D9B77B;
        }
        
        .mega-menu-wrap:hover .mega-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .mega-menu .mega-menu-box {
            padding: 0 15px;
            margin-bottom: 20px;
        }
        
        .mega-menu-title {
            font-size: 18px;
            font-weight: 700;
            color: #D9B77B;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .destination-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin: 15px 0 8px;
        }
        
        .mega-menu-list li {
            margin-bottom: 8px;
        }
        
        .mega-menu-list li a {
            color: #555;
            font-size: 14px;
            transition: all 0.3s ease;
            display: block;
            padding-left: 12px;
            position: relative;
        }
        
        .mega-menu-list li a:before {
            content: "•";
            position: absolute;
            left: 0;
            color: #D9B77B;
        }
        
        .mega-menu-list li a:hover {
            color: #D9B77B;
            padding-left: 17px;
        }
        
        /* Mobile Mega Menu */
        .mobile-mega-menu {
            display: none;
            padding: 0 15px;
        }
        
        .mobile-mega-title {
            font-size: 16px;
            font-weight: 600;
            color: #D9B77B;
            margin: 15px 0 10px;
        }
        
        .mobile-destination-title {
            font-size: 15px;
            font-weight: 600;
            color: #333;
            margin: 12px 0 5px;
            padding-left: 5px;
        }
        
        .mobile-mega-list {
            margin-bottom: 15px;
        }
        
        .mobile-mega-list li {
            border-bottom: 1px solid #f1f1f1;
            padding: 6px 0;
            padding-left: 15px;
        }
        
        .mobile-mega-list li:last-child {
            border-bottom: none;
        }
        
        .menu-item-has-children.active .mobile-mega-menu {
            display: block;
        }
        
        /* Resort Card in Mega Menu */
        .resort-card {
            position: relative;
            margin-top: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .resort-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }
        
        .resort-card-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px;
            background: rgba(0,0,0,0.7);
            color: #fff;
        }
        
        .resort-card-title {
            font-size: 14px;
            font-weight: 600;
            margin: 0;
        }
        
        /* Updated button styles */
        .th-btn.style-mega {
            background: #D9B77B;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .th-btn.style-mega:hover {
            background: #c2a069;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <!-- Mobile Menu -->
    <div class="th-menu-wrapper onepage-nav block md:hidden">
        <div class="th-menu-area bg-white shadow-md p-4 text-center">
            <button id="mobileMenuToggle" class="th-menu-toggle text-2xl focus:outline-none"><i class="fal fa-times"></i></button>
            <div class="mobile-logo mt-4">
                <a href="index.php">
                    <img src="assets/images/logo/KE-Gold.png" alt="Karma Experience" class="w-36 mx-auto">
                </a>
            </div>
            <div id="mobileMenu" class="th-mobile-menu mt-4">
                <ul class="space-y-2 text-gray-800 font-medium">
                    <li><a href="index.php" class="block hover:text-blue-600">Home</a></li>
                    <li class="menu-item-has-children">
                        <a href="#" class="block hover:text-blue-600">Destinations</a>
                        <div class="mobile-mega-menu">
                            <!-- Column 1 -->
                            <div class="mobile-mega-box">
                                <h4 class="mobile-mega-title">Asia</h4>
                                
                                <h5 class="mobile-destination-title">India</h5>
                                <ul class="mobile-mega-list">
                                    <li><a href="karma-royal-palms.php">Karma Royal Palms</a></li>
                                    <li><a href="#">Karma Royal Villagio</a></li>
                                    <li><a href="#">Karma Golden Palms</a></li>
                                </ul>
                                
                                <h5 class="mobile-destination-title">Bali</h5>
                                <ul class="mobile-mega-list">
                                    <li><a href="karma-kandara.php">Karma Kandara</a></li>
                                    <li><a href="#">Karma Jimbaran</a></li>
                                </ul>
                                
                                <h5 class="mobile-destination-title">Thailand</h5>
                                <ul class="mobile-mega-list">
                                    <li><a href="#">Karma Apsara</a></li>
                                    <li><a href="#">Karma Sukhothai</a></li>
                                </ul>
                            </div>
                            
                            <!-- Column 2 -->
                            <div class="mobile-mega-box">
                                <h4 class="mobile-mega-title">Europe</h4>
                                
                                <h5 class="mobile-destination-title">Italy</h5>
                                <ul class="mobile-mega-list">
                                    <li><a href="#">Karma Borgo di Colleoli</a></li>
                                    <li><a href="#">Karma Tuscany</a></li>
                                </ul>
                                
                                <h5 class="mobile-destination-title">France</h5>
                                <ul class="mobile-mega-list">
                                    <li><a href="#">Karma Normandy</a></li>
                                    <li><a href="#">Karma Résidence Normande</a></li>
                                </ul>
                                
                                <h5 class="mobile-destination-title">Greece</h5>
                                <ul class="mobile-mega-list">
                                    <li><a href="#">Karma Minoan</a></li>
                                </ul>
                            </div>
                            
                            <!-- Column 3 -->
                            <div class="mobile-mega-box">
                                <h4 class="mobile-mega-title">Australia & Oceania</h4>
                                
                                <h5 class="mobile-destination-title">Australia</h5>
                                <ul class="mobile-mega-list">
                                    <li><a href="#">Karma Rottnest</a></li>
                                </ul>
                                
                                <h5 class="mobile-destination-title">Indonesia</h5>
                                <ul class="mobile-mega-list">
                                    <li><a href="#">Karma Reef</a></li>
                                </ul>
                            </div>
                            
                            <!-- Column 4 -->
                            <div class="mobile-mega-box">
                                <h4 class="mobile-mega-title">Top Destinations</h4>
                                
                                <h5 class="mobile-destination-title">Best Sellers</h5>
                                <ul class="mobile-mega-list">
                                    <li><a href="karma-royal-palms.php">Karma Royal Palms, Goa</a></li>
                                    <li><a href="karma-kandara.php">Karma Kandara, Bali</a></li>
                                    <li><a href="#">Karma Bavaria, Germany</a></li>
                                    <li><a href="#">Karma St. Martin's, UK</a></li>
                                </ul>
                                
                                <a href="destination_list.php" class="th-btn style-mega mt-3">View All Destinations</a>
                            </div>
                        </div>
                    </li>
                    <li><a href="benefits.php" class="block hover:text-blue-600">Benefits</a></li>
                    <li><a href="about.php" class="block hover:text-blue-600">About Us</a></li>
                    <li><a href="redeem-voucher.php" class="block hover:text-blue-600">Redeem Voucher</a></li>
                    <li><a href="enquire-now.php" class="block hover:text-blue-600">Enquire</a></li>
                    <li><a href="pay-now.php" class="block hover:text-blue-600">Pay Now</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Desktop Header -->
    <header class="th-header header-layout3 header-absolute">
        <div class="sticky-wrapper">
            <div class="menu-area">
                <div class="container">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-auto">
                            <nav class="main-menu d-none d-xl-block">
                                <ul>
                                    <li><a class="active" href="index.php">Home</a></li>
                                    <li class="menu-item-has-children mega-menu-wrap"><a href="#">Destinations</a>
                                        <div class="mega-menu">
                                            <div class="container">
                                                <div class="row">
                                                    <!-- Column 1 -->
                                                    <div class="col-md-3">
                                                        <div class="mega-menu-box">
                                                            <h4 class="mega-menu-title">Asia</h4>
                                                            
                                                            <h5 class="destination-title">India</h5>
                                                            <ul class="mega-menu-list">
                                                                <li><a href="karma-royal-palms.php">Karma Royal Palms</a></li>
                                                                <li><a href="#">Karma Royal Villagio</a></li>
                                                                <li><a href="#">Karma Golden Palms</a></li>
                                                            </ul>
                                                            
                                                            <h5 class="destination-title">Bali</h5>
                                                            <ul class="mega-menu-list">
                                                                <li><a href="karma-kandara.php">Karma Kandara</a></li>
                                                                <li><a href="#">Karma Jimbaran</a></li>
                                                            </ul>
                                                            
                                                            <h5 class="destination-title">Thailand</h5>
                                                            <ul class="mega-menu-list">
                                                                <li><a href="#">Karma Apsara</a></li>
                                                                <li><a href="#">Karma Sukhothai</a></li>
                                                            </ul>
                                                            
                                                            <div class="resort-card">
                                                                <img src="assets/destinations/bali/destination-cover.jpg" alt="Bali">
                                                                <div class="resort-card-content">
                                                                    <h5 class="resort-card-title">Discover Bali</h5>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Column 2 -->
                                                    <div class="col-md-3">
                                                        <div class="mega-menu-box">
                                                            <h4 class="mega-menu-title">Europe</h4>
                                                            
                                                            <h5 class="destination-title">Italy</h5>
                                                            <ul class="mega-menu-list">
                                                                <li><a href="#">Karma Borgo di Colleoli</a></li>
                                                                <li><a href="#">Karma Tuscany</a></li>
                                                            </ul>
                                                            
                                                            <h5 class="destination-title">France</h5>
                                                            <ul class="mega-menu-list">
                                                                <li><a href="#">Karma Normandy</a></li>
                                                                <li><a href="#">Karma Résidence Normande</a></li>
                                                            </ul>
                                                            
                                                            <h5 class="destination-title">United Kingdom</h5>
                                                            <ul class="mega-menu-list">
                                                                <li><a href="#">Karma St. Martin's</a></li>
                                                            </ul>
                                                            
                                                            <h5 class="destination-title">Germany</h5>
                                                            <ul class="mega-menu-list">
                                                                <li><a href="#">Karma Bavaria</a></li>
                                                            </ul>
                                                            
                                                            <div class="resort-card">
                                                                <img src="assets/destinations/italy/destination-cover.jpg" alt="Italy">
                                                                <div class="resort-card-content">
                                                                    <h5 class="resort-card-title">Explore Italy</h5>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Column 3 -->
                                                    <div class="col-md-3">
                                                        <div class="mega-menu-box">
                                                            <h4 class="mega-menu-title">Australia & Oceania</h4>
                                                            
                                                            <h5 class="destination-title">Australia</h5>
                                                            <ul class="mega-menu-list">
                                                                <li><a href="#">Karma Rottnest</a></li>
                                                            </ul>
                                                            
                                                            <h5 class="destination-title">Indonesia</h5>
                                                            <ul class="mega-menu-list">
                                                                <li><a href="#">Karma Reef</a></li>
                                                            </ul>
                                                            
                                                            <h4 class="mega-menu-title mt-4">Africa & Middle East</h4>
                                                            
                                                            <h5 class="destination-title">Egypt</h5>
                                                            <ul class="mega-menu-list">
                                                                <li><a href="#">Karma Golden Sands</a></li>
                                                            </ul>
                                                            
                                                            <div class="resort-card">
                                                                <img src="assets/destinations/egypt/destination-cover.jpg" alt="Egypt">
                                                                <div class="resort-card-content">
                                                                    <h5 class="resort-card-title">Discover Egypt</h5>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Column 4 -->
                                                    <div class="col-md-3">
                                                        <div class="mega-menu-box">
                                                            <h4 class="mega-menu-title">Top Destinations</h4>
                                                            
                                                            <h5 class="destination-title">Best Sellers</h5>
                                                            <ul class="mega-menu-list">
                                                                <li><a href="karma-royal-palms.php">Karma Royal Palms, Goa</a></li>
                                                                <li><a href="karma-kandara.php">Karma Kandara, Bali</a></li>
                                                                <li><a href="#">Karma Bavaria, Germany</a></li>
                                                                <li><a href="#">Karma St. Martin's, UK</a></li>
                                                                <li><a href="#">Karma Minoan, Greece</a></li>
                                                            </ul>
                                                            
                                                            <h5 class="destination-title">New Experiences</h5>
                                                            <ul class="mega-menu-list">
                                                                <li><a href="#">Karma Lake of Menteith, Scotland</a></li>
                                                                <li><a href="#">Karma Seven Lakes, India</a></li>
                                                            </ul>
                                                            
                                                            <a href="destination_list.php" class="th-btn style-mega mt-3">View All Destinations</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li><a href="about.php">About Us</a></li>
                                </ul>
                            </nav>
                        </div>
                        <div class="col-auto">
                            <div class="header-logo"><a href="index.php"><img src="assets/images/logo/KE-white.png"
                                        alt="Karma Experience" style="width:150px ;"></a></div>
                        </div>
                        <div class="col-auto">
                            <nav class="main-menu d-none d-xl-block">
                                <ul>
                                    <li><a href="Blogs.php">Our Blogs</a></li>
                                    <li><a href="enquire-now.php">Enquire Now</a></li>
                                </ul>
                            </nav>
                            
                            <button type="button" class="th-menu-toggle d-block d-xl-none"><i
                                    class="far fa-bars"></i></button>
                        </div>
                        <div class="col-auto d-none d-xl-block">
                            <div class="header-button"><a href="pay-now" class="th-btn style3 th-icon">Pay Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Menu Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile Menu Toggle
            const menuToggle = document.querySelector('.th-menu-toggle');
            const mobileMenu = document.querySelector('.th-menu-wrapper');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    mobileMenu.classList.toggle('active');
                });
            }
            
            // Mobile Submenu Toggle
            const menuItemsWithChildren = document.querySelectorAll('.menu-item-has-children');
            
            menuItemsWithChildren.forEach(function(item) {
                item.addEventListener('click', function(e) {
                    if (e.target === item.querySelector('a') || e.target === item) {
                        e.preventDefault();
                        this.classList.toggle('active');
                    }
                });
            });
        });
    </script>
    
</body>
</html>
