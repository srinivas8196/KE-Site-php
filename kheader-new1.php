<!doctype html>
<html class="no-js" lang="zxx">
<head>
    <?php
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Add cache control headers to prevent caching
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    // Include database connection
    require_once 'db.php';
    $pdo = require 'db.php';

    // Define base URL
    $base_url = '/KE-Site-php';

    // Determine if we're in a blog URL
    $request_uri = $_SERVER['REQUEST_URI'];
    $is_blog_url = strpos($request_uri, '/blogs/') !== false;

    // Define the assets path based on the URL
    $assets_path = $is_blog_url ? "$base_url/" : "";

    // Function to get all destinations with their active resorts - ONLY for mega menu display
    function getDestinationsForMenu() {
        global $pdo;
        
        $sql = "SELECT d.id as dest_id, d.destination_name, 
                       r.id as resort_id, r.resort_name, r.resort_slug, r.is_active
                FROM destinations d
                LEFT JOIN resorts r ON d.id = r.destination_id
                WHERE r.id IS NOT NULL
                AND r.is_active = 1
                ORDER BY d.destination_name, r.resort_name";
        
        try {
            $stmt = $pdo->query($sql);
            $menuDestinations = [];
            
            while($row = $stmt->fetch()) {
                $destId = $row['dest_id'];
                
                if (!isset($menuDestinations[$destId])) {
                    $menuDestinations[$destId] = [
                        'id' => $destId,
                        'name' => $row['destination_name'],
                        'resorts' => []
                    ];
                }
                
                $menuDestinations[$destId]['resorts'][] = [
                    'id' => $row['resort_id'],
                    'name' => $row['resort_name'],
                    'slug' => $row['resort_slug']
                ];
            }
            
            // Only return destinations that have active resorts
            $menuDestinations = array_filter($menuDestinations, function($destination) {
                return !empty($destination['resorts']);
            });
            
            return array_values($menuDestinations);
        } catch (PDOException $e) {
            error_log("Database query failed: " . $e->getMessage());
            return [];
        }
    }

    // Get current page URL to identify active resorts
    function getCurrentMenuSlug() {
        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        $lastSegment = end($segments);
        
        // Handle both /resorts/slug and resorts.php?slug=value formats
        if ($lastSegment === 'resorts.php' && isset($_GET['slug'])) {
            return $_GET['slug'];
        }
        
        // Get resort slug from filename (e.g., karma-royal-palms.php)
        if (preg_match('/^([a-z0-9-]+)\.php$/', $lastSegment, $matches)) {
            return $matches[1];
        }
        
        return $lastSegment;
    }

    // Initialize variables ONLY for the mega menu - with unique names to avoid conflicts
    $menuCurrentSlug = getCurrentMenuSlug();
    $menuDestinations = getDestinationsForMenu();
    ?>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Karma Experience India | Delivering unmatched holiday experiences at unbeatable prices</title>
    <title>Karma Experience India | Delivering unmatched holiday experiences at unbeatable prices</title>
    <meta name="author" content="Karma Experience">
    <meta name="description" content="Karma Experience - Delivering unmatched holiday experiences at unbeatable prices">
    <meta name="description" content="Karma Experience - Delivering unmatched holiday experiences at unbeatable prices">
    <meta name="keywords" content="Karma Experience - Delivering unmatched holiday experiences at unbeatable prices">
    <meta name="robots" content="INDEX,FOLLOW">
    <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="57x57" href="<?php echo $assets_path; ?>assets/images/logo/K-logo.png">
    <link rel="apple-touch-icon" sizes="60x60" href="<?php echo $assets_path; ?>assets/images/logo/K-logo.png">
    <link rel="apple-touch-icon" sizes="72x72" href="<?php echo $assets_path; ?>assets/images/logo/K-logo.png">
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo $assets_path; ?>assets/images/logo/K-logo.png">
    <link rel="apple-touch-icon" sizes="114x114" href="<?php echo $assets_path; ?>assets/images/logo/K-logo.png">
    <link rel="apple-touch-icon" sizes="120x120" href="<?php echo $assets_path; ?>assets/images/logo/K-logo.png">
    <link rel="apple-touch-icon" sizes="144x144" href="<?php echo $assets_path; ?>assets/images/logo/K-logo.png">
    <link rel="apple-touch-icon" sizes="152x152" href="<?php echo $assets_path; ?>assets/images/logo/K-logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $assets_path; ?>assets/images/logo/K-logo.png">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo $assets_path; ?>assets/images/logo/K-logo.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $assets_path; ?>assets/images/logo/K-logo.png">
    <link rel="icon" type="image/png" sizes="96x96" href="<?php echo $assets_path; ?>assets/images/logo/K-logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $assets_path; ?>assets/images/logo/K-logo.png">
    <link rel="manifest" href="<?php echo $assets_path; ?>assets/img/favicons/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="<?php echo $assets_path; ?>assets/img/favicons/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Manrope:wght@200..800&family=Montez&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.15.4/css/all.css">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.15.4/css/fontawesome.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>assets/css/magnific-popup.min.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>assets/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>assets/css/blog.css">
    <!-- <link rel="stylesheet" href="<?php echo $assets_path; ?>assets/css/custom.css"> -->
    <style>
        /* Main Menu Icon Fix */
        .main-menu > ul > li.menu-item-has-children > a {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .main-menu > ul > li.menu-item-has-children > a:after {
            content: '\f107';
            font-family: "Font Awesome 5 Pro", "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 14px;
            transition: transform 0.3s ease;
            color: currentColor;
        }

        .main-menu > ul > li.menu-item-has-children:hover > a:after {
            transform: rotate(-180deg);
        }

        /* Enhanced Modern Mega Menu Styles */
        .mega-menu {
            position: fixed;
            top: 80px;
            left: 0;
            width: 100%;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(255, 255, 255, 0.97) 100%);
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            padding: 30px 0;
            transform: translateY(-10px);
            border-top: 1px solid rgba(180, 151, 90, 0.1);
            max-height: 85vh;
            overflow-y: auto;
        }

        .menu-item-has-children:hover .mega-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .mega-menu .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
        }

        /* Dynamic Grid Layout */
        .mega-menu .destinations-wrapper {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 20px;
        }

        /* Large destination sections (more than 5 resorts) */
        .mega-menu .destination-section.large {
            grid-column: span 4;
        }

        /* Medium destination sections (3-5 resorts) */
        .mega-menu .destination-section.medium {
            grid-column: span 3;
        }

        /* Small destination sections (1-2 resorts) */
        .mega-menu .destination-section.small {
            grid-column: span 2;
        }

        .mega-menu .destination-section {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(180, 151, 90, 0.15);
            transition: all 0.3s ease;
            height: fit-content;
        }

        .mega-menu .destination-section:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .mega-menu .destination-title {
            color: #B4975A;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(180, 151, 90, 0.2);
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mega-menu .destination-title::before {
            content: '\f3c5';
            font-family: "Font Awesome 5 Pro", "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 14px;
        }

        .mega-menu .resort-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 5px;
            scrollbar-width: thin;
            scrollbar-color: rgba(180, 151, 90, 0.3) transparent;
        }

        .mega-menu .resort-list::-webkit-scrollbar {
            width: 4px;
        }

        .mega-menu .resort-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .mega-menu .resort-list::-webkit-scrollbar-thumb {
            background-color: rgba(180, 151, 90, 0.3);
            border-radius: 10px;
        }

        .mega-menu .resort-link {
            color: #555;
            font-size: 14px;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.2s ease;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mega-menu .resort-link:hover {
            background: rgba(180, 151, 90, 0.1);
            color: #B4975A;
            transform: translateX(5px);
        }

        .mega-menu .resort-link.active {
            background: #B4975A;
            color: #fff;
        }

        /* Responsive Design */
        @media (max-width: 1400px) {
            .mega-menu .destination-section.large { grid-column: span 6; }
            .mega-menu .destination-section.medium { grid-column: span 4; }
            .mega-menu .destination-section.small { grid-column: span 3; }
        }

        @media (max-width: 1200px) {
            .mega-menu .destination-section.large { grid-column: span 6; }
            .mega-menu .destination-section.medium { grid-column: span 6; }
            .mega-menu .destination-section.small { grid-column: span 4; }
        }

        @media (max-width: 992px) {
            .mega-menu .destination-section.large,
            .mega-menu .destination-section.medium,
            .mega-menu .destination-section.small {
                grid-column: span 6;
            }
        }

        @media (max-width: 768px) {
            .mega-menu .destination-section.large,
            .mega-menu .destination-section.medium,
            .mega-menu .destination-section.small {
                grid-column: span 12;
            }
            .mega-menu {
                position: static;
                padding: 15px;
            }
            .mega-menu .container {
                padding: 0 15px;
            }
        }

        /* Enhanced Mobile Menu Styles */
        .th-mobile-menu {
            display: none;
            position: fixed;
            top: 0;
            left: -100%;  /* Start from off-screen left */
            width: 320px;  /* Fixed width for better mobile experience */
            height: 100%;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(255, 255, 255, 0.97) 100%);
            backdrop-filter: blur(20px);
            z-index: 9999;
            overflow-y: auto;
            padding: 2rem;
            opacity: 1;  /* Keep opacity 1 for slide effect */
            visibility: visible;  /* Keep visible for slide effect */
            transition: left 0.3s ease;  /* Transition the left property */
            box-shadow: 5px 0 30px rgba(0, 0, 0, 0.1);
        }

        .th-mobile-menu.active {
            left: 0;  /* Slide to visible position */
            display: block;
        }

        /* Add overlay when menu is active */
        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 9998;
        }

        .mobile-menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .mobile-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(180, 151, 90, 0.1);
        }

        .mobile-menu-close {
            font-size: 24px;
            color: #B4975A;
            background: none;
            border: none;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
            position: relative;  /* Added position relative */
            z-index: 10001;  /* Highest z-index */
        }

        .mobile-menu-close:hover {
            background: rgba(180, 151, 90, 0.1);
            transform: rotate(90deg);
        }

        .mobile-menu-close i {
            font-size: 24px;  /* Increased icon size */
            line-height: 1;
        }

        .mobile-menu-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .mobile-menu-nav > li {
            margin-bottom: 1rem;
        }

        .mobile-menu-nav > li > a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            font-size: 1.1rem;
            border-bottom: 1px solid rgba(180, 151, 90, 0.1);
            transition: all 0.3s ease;
        }

        .mobile-menu-nav > li > a:hover {
            color: #B4975A;
        }

        .mobile-menu-toggle::after {
            content: '\f107';
            font-family: "Font Awesome 5 Pro", "Font Awesome 5 Free";
            font-weight: 900;
            transition: transform 0.3s ease;
        }

        .mobile-menu-toggle.active::after {
            transform: rotate(-180deg);
        }

        .mobile-submenu {
            display: none;
            padding: 1rem 0;
        }

        .mobile-submenu.active {
            display: block;
        }

        .mobile-submenu .destination-section {
            background: rgba(255, 255, 255, 0.7);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(180, 151, 90, 0.1);
            transition: all 0.3s ease;
        }

        .mobile-submenu .destination-title {
            font-size: 1rem;
            font-weight: 600;
            color: #B4975A;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid rgba(180, 151, 90, 0.2);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mobile-submenu .destination-title::before {
            content: '\f3c5';
            font-family: "Font Awesome 5 Pro", "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 14px;
        }

        .mobile-submenu .resort-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .mobile-submenu .resort-item {
            margin-bottom: 0.5rem;
        }

        .mobile-submenu .resort-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #555;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .mobile-submenu .resort-link:hover {
            color: #B4975A;
            background: transparent;
            padding-left: 1.5rem;
        }

        .mobile-submenu .resort-link::after {
            content: '\f054';
            font-family: "Font Awesome 5 Pro", "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 12px;
            opacity: 0;
            position: absolute;
            right: 1rem;
            transition: all 0.3s ease;
            color: #B4975A;
        }

        .mobile-submenu .resort-link:hover::after {
            opacity: 1;
        }

        /* Header Styles */
        .th-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background: transparent;
            transition: all 0.3s ease;
            padding: 1rem 0;
        }

        .th-header.scrolled {
            background: rgba(220, 218, 218, 0.2);  /* Changed to dark background */
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.2);
        }

        .th-header.scrolled .main-menu > ul > li > a {
            color: white;  /* Changed to white */
        }

        .th-header.scrolled .th-menu-toggle {
            color: white;  /* Changed mobile menu toggle to white */
        }

        /* Update logo for dark background */
        .th-header.scrolled .header-logo img {
            max-height: 40px;
            content: url('assets/images/logo/KE-white.png');  /* Switch to white logo */
        }

        .main-menu > ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
        }

        .main-menu > ul > li {
            position: relative;
            margin: 0 1.5rem;
        }

        .main-menu > ul > li > a {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 1rem;
            letter-spacing: 0.5px;
        }

        .main-menu > ul > li > a:hover {
            color: #ffd700;
        }

        .header-logo img {
            max-height: 50px;
            transition: all 0.3s ease;
        }

        .th-header.scrolled .header-logo img {
            max-height: 40px;
        }

        /* Pay Now Button Styles */
        .header-button {
            display: flex;
            align-items: center;
        }

        .th-btn.style3 {
            background: #B4975A;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .th-btn.style3:after {
            content: '\f061';  /* Arrow right */
            font-family: "Font Awesome 5 Pro", "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 14px;
            transition: transform 0.3s ease;
        }

        .th-btn.style3:hover {
            background: #96793D;
            transform: translateY(-2px);
        }

        .th-btn.style3:hover:after {
            transform: translateX(4px);
        }

        /* Mobile Menu Toggle Button Styles */
        .th-menu-toggle {
            position: relative;
            z-index: 9998;  /* Lower than mobile menu */
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 0;
            border-radius: 4px;
        }

        .th-header.scrolled .th-menu-toggle {
            color: white;  /* Changed mobile menu toggle to white */
        }

        .th-menu-toggle:hover {
            color: #B4975A;
        }

        .th-menu-toggle i {
            transition: transform 0.3s ease;
        }

        .th-menu-toggle:hover i {
            transform: scale(1.1);
        }

        @media (max-width: 991px) {
            .main-menu {
                display: none;
            }
            
            .th-mobile-menu {
                display: block;
            }
            
            .th-menu-toggle {
                display: flex !important;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay"></div>
    
    <!-- Mobile Menu -->
    <div class="th-mobile-menu">
        <div class="mobile-menu-header">
            <a href="<?php echo $base_url; ?>/index.php">
                <img src="<?php echo $assets_path; ?>assets/images/logo/KE-Gold.png" alt="Karma Experience" style="height: 40px;">
            </a>
            <button class="mobile-menu-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="mobile-menu-nav">
            <li><a href="index.php">Home</a></li>
                    <li class="menu-item-has-children">
                <a href="#" class="mobile-menu-toggle">Destinations</a>
                <div class="mobile-submenu">
                    <?php
                    // Reuse the same data for mobile menu
                    foreach ($menuDestinations as $destination): 
                    ?>
                        <div class="destination-section">
                            <h3 class="destination-title">
                               
                                <?php echo htmlspecialchars($destination['name']); ?>
                            </h3>
                            <ul class="resort-list">
                                <?php foreach ($destination['resorts'] as $resort): 
                                    $isActive = ($resort['slug'] === $menuCurrentSlug);
                                ?>
                                    <li class="resort-item">
                                        <a href="<?php echo $resort['slug']; ?>.php" 
                                           class="resort-link <?php echo $isActive ? 'active' : ''; ?>">
                                            <?php echo htmlspecialchars($resort['name']); ?>
                                        </a>
                    </li>
                                <?php endforeach; ?>
                </ul>
            </div>
                    <?php endforeach; ?>
        </div>
            </li>
            <li><a href="about.php">About Us</a></li>
            <li><a href="Blogs.php">Our Blogs</a></li>
            <li><a href="redeem-voucher.php">Redeem Voucher</a></li>
            <li><a href="enquire-now.php">Enquire</a></li>
            <li><a href="pay-now.php">Pay Now</a></li>
        </ul>
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
                                    <li><a href="index.php">Home</a></li>
                                    <li class="menu-item-has-children">
                                        <a href="#">Destinations</a>
                                        <div class="mega-menu">
                                            <div class="container">
                                                <div class="destinations-wrapper">
                                                    <?php 
                                                    // Sort destinations by number of resorts
                                                    usort($menuDestinations, function($a, $b) {
                                                        return count($b['resorts']) - count($a['resorts']);
                                                    });

                                                    foreach ($menuDestinations as $destination): 
                                                        $resortCount = count($destination['resorts']);
                                                        $sizeClass = $resortCount > 5 ? 'large' : ($resortCount > 2 ? 'medium' : 'small');
                                                    ?>
                                                        <div class="destination-section <?php echo $sizeClass; ?>">
                                                            <h3 class="destination-title">
                                                                <?php echo htmlspecialchars($destination['name']); ?>
                                                            </h3>
                                                            <div class="resort-list">
                                                                <?php foreach ($destination['resorts'] as $resort): ?>
                                                                    <a href="<?php echo $base_url; ?>/resorts/<?php echo htmlspecialchars($resort['slug']); ?>" 
                                                                       class="resort-link <?php echo ($resort['slug'] === $menuCurrentSlug) ? 'active' : ''; ?>">
                                                                        <?php echo htmlspecialchars($resort['name']); ?>
                                                                    </a>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li><a href="about.php">About Us</a></li>
                                </ul>
                            </nav>
                        </div>
                        <div class="col-auto">
                            <div class="header-logo">
                                <a href="<?php echo $base_url; ?>/index.php">
                                    <img src="<?php echo $assets_path; ?>assets/images/logo/KE-white.png" alt="Karma Experience">
                                </a>
                            </div>
                        </div>
                        <div class="col-auto">
                            <nav class="main-menu d-none d-xl-block">
                                <ul>
                                    <li><a href="Blogs.php">Our Blogs</a></li>
                                    <li><a href="enquire-now.php">Enquire Now</a></li>
                                </ul>
                            </nav>
                            <button type="button" class="th-menu-toggle d-block d-xl-none">
                                <i class="fas fa-bars"></i>
                            </button>
                        </div>
                        <div class="col-auto d-none d-xl-block">
                            <div class="header-button">
                                <a href="pay-now.php" class="th-btn style3 th-icon">Pay Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Header scroll effect
            const header = document.querySelector('.th-header');
            const menuToggle = document.querySelector('.th-menu-toggle');
            const mobileMenu = document.querySelector('.th-mobile-menu');
            const mobileMenuClose = document.querySelector('.mobile-menu-close');
            const overlay = document.querySelector('.mobile-menu-overlay');
            const submenuToggles = document.querySelectorAll('.mobile-menu-toggle');
            
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    header.classList.add('scrolled');
                    // Update the logo path when scrolled
                    document.querySelector('.header-logo img').setAttribute('src', '<?php echo $assets_path; ?>assets/images/logo/KE-white.png');
                } else {
                    header.classList.remove('scrolled');
                    // Update the logo path when at top
                    document.querySelector('.header-logo img').setAttribute('src', '<?php echo $assets_path; ?>assets/images/logo/KE-white.png');
                }
            });

            // Function to open mobile menu
            function openMobileMenu() {
                mobileMenu.classList.add('active');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            // Function to close mobile menu
            function closeMobileMenu() {
                mobileMenu.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            // Mobile menu toggle
            menuToggle.addEventListener('click', openMobileMenu);
            mobileMenuClose.addEventListener('click', closeMobileMenu);
            overlay.addEventListener('click', closeMobileMenu);

            // Handle submenu toggles
            submenuToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const submenu = this.nextElementSibling;
                    const isActive = submenu.classList.contains('active');
                    
                    // Close all other submenus
                    document.querySelectorAll('.mobile-submenu').forEach(sub => {
                        if (sub !== submenu) {
                            sub.classList.remove('active');
                            sub.previousElementSibling.classList.remove('active');
                        }
                    });
                    
                    // Toggle clicked submenu
                    submenu.classList.toggle('active');
                    this.classList.toggle('active');
                });
            });

            // Prevent clicks inside mobile menu from closing it
            mobileMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    </script>
</body>
</html>
