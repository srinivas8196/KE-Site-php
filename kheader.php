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

    // Include database connection and required files
    require_once 'db.php';
    require_once 'includes/recaptcha-config.php';
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
            background: #fff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            padding: 40px 0;
            transform: translateY(-10px);
        }

        .menu-item-has-children:hover .mega-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .mega-menu .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
        }

        /* Grid Layout */
        .mega-menu .destinations-wrapper {
            display: flex;
            flex-wrap: wrap;
        }
        
        /* Column structure */
        .mega-menu .menu-column {
            flex: 1;
            min-width: 0;
            padding: 0 15px;
        }
        
        /* First column (India) is fixed */
        .mega-menu .menu-column:first-child {
            flex: 0 0 25%;
        }
        
        /* Other columns share remaining space */
        .mega-menu .menu-column:not(:first-child) {
            flex: 1 0 0%;
        }

        .mega-menu .destination-section {
            background: #fff;
            padding: 5px;
            margin-bottom: 20px;
        }

        /* Special layout for India */
        .mega-menu .destination-section.india {
            grid-column: 1;
            grid-row: span 1;
        }

        /* Other destinations */
        .mega-menu .destination-section:not(.india) {
            break-inside: avoid;
        }
        
        .mega-menu .destination-title {
            color: #B4975A;
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 12px;
            padding-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #eee;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .mega-menu .destination-title::before {
            content: '';
            position: absolute;
            left: 0;
            bottom: -1px;
            width: 40px;
            height: 2px;
            background: #B4975A;
        }

        .mega-menu .resort-count {
            font-size: 12px;
            color: #999;
            font-weight: normal;
            text-transform: none;
        }

        .mega-menu .resort-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .mega-menu .resort-link {
            color: #666;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.2s ease;
            display: block;
            padding: 4px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mega-menu .resort-link:hover {
            color: #B4975A;
            padding-left: 8px;
        }

        .mega-menu .resort-link .location {
            color: #999;
            font-size: 11px;
            margin-left: 4px;
        }

        /* Partner Hotel Style */
        .mega-menu .resort-link .partner-label {
            font-size: 12px;
            color: #B4975A;
            font-style: italic;
            margin-left: 6px;
            font-weight: 500;
            display: inline-block;
        }

        /* Removing the old partner class styling */
        /* .mega-menu .resort-link.partner::after {
            content: '(Partner Hotel)';
            display: inline-block;
            font-size: 11px;
            color: #999;
            margin-left: 4px;
        } */

        /* Responsive Design */
        @media (max-width: 1400px) {
            .mega-menu .menu-column {
                padding: 0 10px;
            }
            .mega-menu .menu-column:first-child {
                flex: 0 0 25%;
            }
        }

        @media (max-width: 1200px) {
            .mega-menu .menu-column {
                flex: 0 0 50%;
                padding: 0 10px;
            }
            .mega-menu .menu-column:first-child {
                flex: 0 0 50%;
            }
        }

        @media (max-width: 768px) {
            .mega-menu .menu-column {
                flex: 0 0 100%;
            }
            .mega-menu .menu-column:first-child {
                flex: 0 0 100%;
            }
            .mega-menu {
                padding: 30px 0;
            }
            .mega-menu .container {
                padding: 0 20px;
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
            padding: 10px;
            margin-bottom: 1rem;
            border: 1px solid rgba(180, 151, 90, 0.1);
            transition: all 0.3s ease;
            width: 100%; /* Ensure full width */
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
            white-space: normal; /* Allow text to wrap */
            line-height: 1.4; /* Better line spacing for wrapped text */
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
        
        /* Partner Hotel Style for Mobile */
        .mobile-submenu .resort-link .partner-label {
            font-size: 12px;
            color: #B4975A;
            font-style: italic;
            margin-left: 6px;
            font-weight: 500;
            display: inline-block;
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
    <!-- Add reCAPTCHA v3 script in head -->
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo RECAPTCHA_V3_SITE_KEY; ?>"></script>
    
    <!-- Add global reCAPTCHA helper function -->
    <script>
    function executeRecaptcha(action) {
        return new Promise((resolve, reject) => {
            grecaptcha.ready(function() {
                grecaptcha.execute('<?php echo RECAPTCHA_V3_SITE_KEY; ?>', { action: action })
                    .then(function(token) {
                        resolve(token);
                    })
                    .catch(function(error) {
                        reject(error);
                    });
            });
        });
    }

    // Automatically add reCAPTCHA token to all forms
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            // Skip forms that shouldn't have reCAPTCHA
            if (form.classList.contains('no-recaptcha')) return;
            
            // Create hidden input for token if it doesn't exist
            if (!form.querySelector('input[name="recaptcha_token"]')) {
                const tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = 'recaptcha_token';
                form.appendChild(tokenInput);
            }

            // Add submit handler
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                try {
                    // Get form action or default to 'form_submit'
                    const recaptchaAction = form.dataset.recaptchaAction || 'form_submit';
                    const token = await executeRecaptcha(recaptchaAction);
                    form.querySelector('input[name="recaptcha_token"]').value = token;
                    form.submit();
                } catch (error) {
                    console.error('reCAPTCHA error:', error);
                    // Optionally show error to user
                    alert('Security verification failed. Please try again.');
                }
            });
        });
    });
    </script>
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
                    // Find India and separate it from other destinations (for mobile menu)
                    $mobileIndiaDestination = null;
                    $mobileOtherDestinations = [];
                    
                    foreach ($menuDestinations as $destination) {
                        if (strtoupper(trim($destination['name'])) === 'INDIA') {
                            $mobileIndiaDestination = $destination;
                        } else {
                            $mobileOtherDestinations[] = $destination;
                        }
                    }
                    
                    // If India is not found exactly, try a more flexible approach
                    if ($mobileIndiaDestination === null) {
                        foreach ($menuDestinations as $destination) {
                            if (stripos($destination['name'], 'INDIA') !== false) {
                                $mobileIndiaDestination = $destination;
                                break;
                            }
                        }
                        
                        // If still no India found, use the destination with most resorts
                        if ($mobileIndiaDestination === null && !empty($mobileOtherDestinations)) {
                            usort($menuDestinations, function($a, $b) {
                                return count($b['resorts']) - count($a['resorts']);
                            });
                            $mobileIndiaDestination = $menuDestinations[0];
                            
                            // Remove it from other destinations to avoid duplication
                            foreach ($mobileOtherDestinations as $key => $dest) {
                                if ($dest['id'] === $mobileIndiaDestination['id']) {
                                    unset($mobileOtherDestinations[$key]);
                                    break;
                                }
                            }
                            $mobileOtherDestinations = array_values($mobileOtherDestinations);
                        }
                    }
                    
                    // Sort other destinations by number of resorts (descending)
                    usort($mobileOtherDestinations, function($a, $b) {
                        return count($b['resorts']) - count($a['resorts']);
                    });
                    
                    // Display India first
                    if ($mobileIndiaDestination): 
                    ?>
                        <div class="destination-section">
                            <h3 class="destination-title">
                                <?php echo htmlspecialchars($mobileIndiaDestination['name']); ?>
                            </h3>
                            <ul class="resort-list">
                                <?php foreach ($mobileIndiaDestination['resorts'] as $resort): 
                                    $isActive = ($resort['slug'] === $menuCurrentSlug);
                                ?>
                                    <li class="resort-item">
                                        <a href="<?php echo $base_url; ?>/<?php echo htmlspecialchars($resort['slug']); ?>.php" 
                                           class="resort-link <?php echo $isActive ? 'active' : ''; ?>">
                                            <?php 
                                            // Add debug comment
                                            // echo "<!-- Resort: " . htmlspecialchars($resort['name']) . " -->";
                                            
                                            // Check if this is a partner hotel
                                            $resortName = $resort['name'];
                                            $isPartner = (stripos($resortName, 'Partner Hotel') !== false);
                                            
                                            // Clean up the resort name
                                            $resortName = str_replace('(Partner Hotel)', '', $resortName);
                                            $resortName = str_replace('Partner Hotel', '', $resortName);
                                            
                                            // Extract location if it exists (after the comma)
                                            $location = '';
                                            if (strpos($resortName, ',') !== false) {
                                                list($resortName, $location) = explode(',', $resortName, 2);
                                            }
                                            ?>
                                                <?php 
                                                    echo htmlspecialchars(trim($resortName));
                                                    
                                                    // Directly output partner hotel text if this is a partner hotel
                                                    if ($isPartner) {
                                                        echo ' <span style="color:#B4975A; font-style:italic;">(Partner Hotel)</span>';
                                                    }
                                                    
                                                    if ($location) {
                                                        echo ' <span class="location">' . htmlspecialchars(trim($location)) . '</span>';
                                                    }
                                                ?>
                                            </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Display other destinations -->
                    <?php foreach ($mobileOtherDestinations as $destination): ?>
                        <div class="destination-section">
                            <h3 class="destination-title">
                                <?php echo htmlspecialchars($destination['name']); ?>
                            </h3>
                            <ul class="resort-list">
                                <?php foreach ($destination['resorts'] as $resort): 
                                    $isActive = ($resort['slug'] === $menuCurrentSlug);
                                ?>
                                    <li class="resort-item">
                                        <a href="<?php echo $base_url; ?>/<?php echo htmlspecialchars($resort['slug']); ?>.php" 
                                           class="resort-link <?php echo $isActive ? 'active' : ''; ?>">
                                            <?php 
                                            // Add debug comment
                                            // echo "<!-- Resort: " . htmlspecialchars($resort['name']) . " -->";
                                            
                                            // Check if this is a partner hotel
                                            $resortName = $resort['name'];
                                            $isPartner = (stripos($resortName, 'Partner Hotel') !== false);
                                            
                                            // Clean up the resort name
                                            $resortName = str_replace('(Partner Hotel)', '', $resortName);
                                            $resortName = str_replace('Partner Hotel', '', $resortName);
                                            
                                            // Extract location if it exists (after the comma)
                                            $location = '';
                                            if (strpos($resortName, ',') !== false) {
                                                list($resortName, $location) = explode(',', $resortName, 2);
                                            }
                                            ?>
                                                <?php 
                                                    echo htmlspecialchars(trim($resortName));
                                                    
                                                    // Directly output partner hotel text if this is a partner hotel
                                                    if ($isPartner) {
                                                        echo ' <span style="color:#B4975A; font-style:italic;">(Partner Hotel)</span>';
                                                    }
                                                    
                                                    if ($location) {
                                                        echo ' <span class="location">' . htmlspecialchars(trim($location)) . '</span>';
                                                    }
                                                ?>
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
                                                    // Find India and separate it from other destinations
                                                    $indiaDestination = null;
                                                    $otherDestinations = [];
                                                    
                                                    foreach ($menuDestinations as $destination) {
                                                        if (strtoupper(trim($destination['name'])) === 'INDIA') {
                                                            $indiaDestination = $destination;
                                                        } else {
                                                            $otherDestinations[] = $destination;
                                                        }
                                                    }
                                                    
                                                    // Debug output to check if India is found
                                                    if ($indiaDestination === null) {
                                                        // If India is not found exactly, try a more flexible approach
                                                        foreach ($menuDestinations as $destination) {
                                                            if (stripos($destination['name'], 'INDIA') !== false) {
                                                                $indiaDestination = $destination;
                                                                break;
                                                            }
                                                        }
                                                        
                                                        // If still no India found, use the destination with most resorts
                                                        if ($indiaDestination === null && !empty($otherDestinations)) {
                                                            // Sort to get the destination with most resorts
                                                    usort($menuDestinations, function($a, $b) {
                                                        return count($b['resorts']) - count($a['resorts']);
                                                    });
                                                            $indiaDestination = $menuDestinations[0];
                                                            
                                                            // Remove it from other destinations to avoid duplication
                                                            foreach ($otherDestinations as $key => $dest) {
                                                                if ($dest['id'] === $indiaDestination['id']) {
                                                                    unset($otherDestinations[$key]);
                                                                    break;
                                                                }
                                                            }
                                                            $otherDestinations = array_values($otherDestinations); // Reset array keys
                                                        }
                                                    }
                                                    
                                                    // Sort other destinations by number of resorts (descending)
                                                    usort($otherDestinations, function($a, $b) {
                                                        return count($b['resorts']) - count($a['resorts']);
                                                    });

                                                    // Create the column structure
                                                    ?>
                                                    <!-- First Column (India) -->
                                                    <div class="menu-column">
                                                        <?php if ($indiaDestination): 
                                                            $resortCount = count($indiaDestination['resorts']);
                                                        ?>
                                                            <div class="destination-section">
                                                                <h3 class="destination-title">
                                                                    <?php echo htmlspecialchars($indiaDestination['name']); ?>
                                                                    <span class="resort-count">(<?php echo $resortCount; ?> <?php echo $resortCount === 1 ? 'Resort' : 'Resorts'; ?>)</span>
                                                                </h3>
                                                                <div class="resort-list">
                                                                    <?php 
                                                                    foreach ($indiaDestination['resorts'] as $resort):
                                                                        // Add debug comment
                                                                        // echo "<!-- Resort: " . htmlspecialchars($resort['name']) . " -->";
                                                                        
                                                                        // Check if this is a partner hotel
                                                                        $resortName = $resort['name'];
                                                                        $isPartner = (stripos($resortName, 'Partner Hotel') !== false);
                                                                        
                                                                        // Clean up the resort name
                                                                        $resortName = str_replace('(Partner Hotel)', '', $resortName);
                                                                        $resortName = str_replace('Partner Hotel', '', $resortName);
                                                                        
                                                                        // Extract location if it exists (after the comma)
                                                                        $location = '';
                                                                        if (strpos($resortName, ',') !== false) {
                                                                            list($resortName, $location) = explode(',', $resortName, 2);
                                                                        }
                                                                    ?>
                                                                        <a href="<?php echo $base_url; ?>/<?php echo htmlspecialchars($resort['slug']); ?>.php" 
                                                                           class="resort-link <?php echo ($resort['slug'] === $menuCurrentSlug) ? 'active' : ''; ?>">
                                                                            <?php 
                                                                                echo htmlspecialchars(trim($resortName));
                                                                                
                                                                                // Directly output partner hotel text if this is a partner hotel
                                                                                if ($isPartner) {
                                                                                    echo ' <span style="color:#B4975A; font-style:italic;">(Partner Hotel)</span>';
                                                                                }
                                                                                
                                                                                if ($location) {
                                                                                    echo ' <span class="location">' . htmlspecialchars(trim($location)) . '</span>';
                                                                                }
                                                                            ?>
                                                                        </a>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php
                                                    // Calculate destinations per column for the other 3 columns
                                                    $otherCount = count($otherDestinations);
                                                    
                                                    // Custom distribution logic to ensure better column layout
                                                    $customColumns = [[], [], []];
                                                    $colIndex = 0;
                                                    $colHeight = [0, 0, 0]; // Track approximate "height" of each column
                                                    
                                                    foreach ($otherDestinations as $destination) {
                                                        $resortCount = count($destination['resorts']);
                                                        $weight = $resortCount * 0.5 + 1; // Each resort adds weight, plus base weight for section
                                                        
                                                        // Special case for Cambodia - force it to column 1 (second column)
                                                        if (strtoupper(trim($destination['name'])) === 'CAMBODIA') {
                                                            $customColumns[1][] = $destination;
                                                            $colHeight[1] += $weight;
                                                            continue;
                                                        }
                                                        
                                                        // Find the column with the least height
                                                        $minHeight = min($colHeight);
                                                        $colIndex = array_search($minHeight, $colHeight);
                                                        
                                                        // Add destination to this column
                                                        $customColumns[$colIndex][] = $destination;
                                                        $colHeight[$colIndex] += $weight;
                                                    }
                                                    
                                                    // Create other columns with custom distributed destinations
                                                    for ($i = 0; $i < 3; $i++):
                                                        $columnDestinations = $customColumns[$i];
                                                        
                                                        if (empty($columnDestinations)) continue;
                                                    ?>
                                                        <div class="menu-column">
                                                            <?php foreach ($columnDestinations as $destination): 
                                                                $resortCount = count($destination['resorts']);
                                                            ?>
                                                                <div class="destination-section">
                                                                    <h3 class="destination-title">  
                                                                        <?php echo htmlspecialchars($destination['name']); ?>
                                                                        <span class="resort-count">(<?php echo $resortCount; ?> <?php echo $resortCount === 1 ? 'Resort' : 'Resorts'; ?>)</span>
                                                                    </h3>
                                                                    <div class="resort-list">
                                                                        <?php 
                                                                        foreach ($destination['resorts'] as $resort):
                                                                            // Add debug comment
                                                                            // echo "<!-- Resort: " . htmlspecialchars($resort['name']) . " -->";
                                                                            
                                                                            // Check if this is a partner hotel
                                                                            $resortName = $resort['name'];
                                                                            $isPartner = (stripos($resortName, 'Partner Hotel') !== false);
                                                                            
                                                                            // Clean up the resort name
                                                                            $resortName = str_replace('(Partner Hotel)', '', $resortName);
                                                                            $resortName = str_replace('Partner Hotel', '', $resortName);
                                                                            
                                                                            // Extract location if it exists (after the comma)
                                                                            $location = '';
                                                                            if (strpos($resortName, ',') !== false) {
                                                                                list($resortName, $location) = explode(',', $resortName, 2);
                                                                            }
                                                                        ?>
                                                                            <a href="<?php echo $base_url; ?>/<?php echo htmlspecialchars($resort['slug']); ?>.php" 
                                                                               class="resort-link <?php echo ($resort['slug'] === $menuCurrentSlug) ? 'active' : ''; ?>">
                                                                                <?php 
                                                                                    echo htmlspecialchars(trim($resortName));
                                                                                    
                                                                                    // Directly output partner hotel text if this is a partner hotel
                                                                                    if ($isPartner) {
                                                                                        echo ' <span style="color:#B4975A; font-style:italic;">(Partner Hotel)</span>';
                                                                                    }
                                                                                    
                                                                                    if ($location) {
                                                                                        echo ' <span class="location">' . htmlspecialchars(trim($location)) . '</span>';
                                                                                    }
                                                                                ?>
                                                                            </a>
                                                                        <?php endforeach; ?>
                                                                </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                        </div>
                                                    <?php endfor; ?>
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
