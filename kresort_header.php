<!doctype html>
<?php
// Ensure database connection is available
if (!isset($pdo)) {
    require_once 'db.php';
}
?>
<html class="no-js" lang="zxx">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Karma Experience India | Delivering unmatched holiday experiences at unbeatable prices</title>
    <meta name="author" content="Karma Experience">
    <meta name="description" content="Karma Experience - Delivering unmatched holiday experiences at unbeatable prices">
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Manrope:wght@200..800&family=Montez&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.15.4/css/all.css">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.15.4/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/magnific-popup.min.css">
    <link rel="stylesheet" href="assets/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
    /* Header Styles for Resort Pages */
    .th-header {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
        background: transparent;
        transition: all 0.3s ease;
        padding: 1rem 0;
        margin-top: 0;
    }

    .th-header.scrolled {
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(10px);
        box-shadow: 0 2px 20px rgba(0,0,0,0.2);
    }

    .th-header .main-menu > ul > li > a {
        color: white;
    }

    .th-header.scrolled .main-menu > ul > li > a {
        color: white;
    }

    .th-header .th-menu-toggle {
        color: white;
    }

    .th-header.scrolled .th-menu-toggle {
        color: white;
    }

    .th-header .header-logo img {
        max-height: 50px;
        transition: all 0.3s ease;
    }

    .th-header.scrolled .header-logo img {
        max-height: 40px;
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
        content: '\f061';
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
        z-index: 9998;
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
        color: white;
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
        
        .th-menu-toggle {
            display: flex !important;
        }
    }

    /* Banner and Content Styles */
    h2 { font-size: 1.5rem; margin-bottom: 1.5rem; }
    .section-spacing { margin-bottom: 2rem; }
    
    .banner { 
        position: relative; 
        width: 100%; 
        height: 60vh; 
        margin-top: 0; 
        overflow: hidden; 
    }
    
    .banner img { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
    }
    
    .banner .banner-title { 
        z-index: 2; 
        color: white; 
        font-weight: bold;
        text-align: center;
    }
    
    .banner .overlay { 
        position: absolute; 
        top: 0; 
        left: 0; 
        width: 100%; 
        height: 100%; 
        background: rgba(0,0,0,0.5); 
        z-index: 1; 
    }

    /* Enquiry Form Styles */
    .card {
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .card-header {
        background-color: #B4975A !important;
        color: white;
        padding: 1rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    .btn-primary {
        background-color: #B4975A;
        border-color: #B4975A;
    }

    .btn-primary:hover {
        background-color: #96793D;
        border-color: #96793D;
    }

    /* Gallery Styles */
    .img-rounded {
        border-radius: 8px;
        transition: transform 0.3s ease;
    }

    .img-rounded:hover {
        transform: scale(1.03);
    }
    
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

    /* Mega Menu Styles */
    .mega-menu {
        position: fixed;
        top: 80px;
        left: 0;
        width: 100%;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(255, 255, 255, 0.97) 100%);
        backdrop-filter: blur(20px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        opacity: 0;
        visibility: hidden;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        z-index: 1000;
        padding: 40px 0;
        transform: translateY(-10px);
        border-top: 1px solid rgba(180, 151, 90, 0.1);
    }

    .menu-item-has-children:hover .mega-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .mega-menu .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 30px;
    }

    .mega-menu .row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
        margin: 0;
    }

    .mega-menu .destination-section {
        position: relative;
        padding: 25px;
        background: none;
        border-radius: 16px;
        transition: all 0.3s ease;
        overflow: hidden;
        border: 1px solid rgba(180, 151, 90, 0.1);
        min-width: 250px;
    }

    .mega-menu .destination-section:hover {
        transform: translateY(-5px);
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 15px 30px rgba(180, 151, 90, 0.1);
        border-color: rgba(180, 151, 90, 0.2);
    }

    .mega-menu .destination-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #B4975A;
        margin-bottom: 25px;
        padding-bottom: 15px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        border-bottom: 1px solid rgba(180, 151, 90, 0.2);
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .mega-menu .destination-title::before {
        content: '\f3c5';
        font-family: "Font Awesome 5 Pro", "Font Awesome 5 Free";
        font-weight: 900;
        font-size: 16px;
       
    }

    .mega-menu .resort-list {
        padding: 0;
        list-style: none;
        margin: 0;
        position: relative;
        width: 100%;
    }

    .mega-menu .resort-item {
        margin-bottom: 12px;
        position: relative;
        transform: translateX(0);
        transition: transform 0.3s ease;
        width: 100%;
            background: none !important;
    }

    .mega-menu .resort-link {
        color: #555;
        text-decoration: none;
        font-size: 0.95rem;
        padding: 0; /* Remove padding */
        display: inline-block; /* Ensure it appears as normal text */
        font-weight: 500;
            background: transparent !important;
        white-space: nowrap;
        border: none; /* Remove any border */
        box-shadow: none !important; /* Remove any shadow */
    }

    .mega-menu .resort-link:hover {
        color: #B4975A;
        background: none !important; /* Ensure no background on hover */
    }

    /* Mobile Menu Styles */
    .th-mobile-menu {
        display: none;
        position: fixed;
        top: 0;
        left: -100%;
        width: 320px;
        height: 100%;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(255, 255, 255, 0.97) 100%);
        backdrop-filter: blur(20px);
        z-index: 9999;
        overflow-y: auto;
        padding: 2rem;
        transition: left 0.3s ease;
        box-shadow: 5px 0 30px rgba(0, 0, 0, 0.1);
    }

    .th-mobile-menu.active {
        left: 0;
        display: block;
    }

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
        position: relative;
        z-index: 10001;
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

    .mobile-submenu {
        display: none;
        padding: 1rem 0;
    }

    .mobile-submenu.active {
        display: block;
    }

    /* Destination Form Styles */
    .destination-form-container {
        max-width: 42rem;
        margin: 3rem auto;
        padding: 1rem;
    }

    .destination-form {
        background: white;
        padding: 2rem;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .destination-form-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    @media (min-width: 768px) {
        .destination-form-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .destination-form-field {
        margin-bottom: 1rem;
    }

    .destination-form-field.full-width {
        grid-column: 1 / -1;
    }

    .destination-form label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.25rem;
    }

    .destination-form input,
    .destination-form select {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #D1D5DB;
        border-radius: 0.375rem;
        background-color: white;
        font-size: 0.875rem;
    }

    .destination-form input:focus,
    .destination-form select:focus {
        outline: none;
        border-color: #3B82F6;
        box-shadow: 0 0 5px rgba(59, 130, 246, 0.1);
    }

    .destination-form-submit {
        margin-top: 2rem;
    }

    .destination-form-button {
        width: 100%;
        background-color: #3B82F6;
        color: white;
        padding: 0.75rem 1rem;
        border: none;
        border-radius: 0.375rem;
        font-weight: 500;
        font-size: 0.875rem;
        cursor: pointer;
        transition: background-color 0.15s ease-in-out;
    }

    .destination-form-button:hover {
        background-color: #2563EB;
    }

    .destination-form-space {
        margin-bottom: 1.5rem;
    }
    </style>
</head>
<body>
    <?php
    // Include database connection
    if (!isset($pdo)) {
        require_once 'db.php';
    }
    
    // Function to get destinations and resorts for the menu only
    function _ke_get_menu_destinations() {
        try {
            global $pdo;
            
            // Check if database connection exists
            if (!isset($pdo) || !$pdo) {
                error_log("Database connection not available in _ke_get_menu_destinations");
                return [];
            }
            
            // Use output buffering to catch any unexpected output or errors
            ob_start();
            
            $menuDestinations = [];
            
            $sql = "SELECT d.id as dest_id, d.destination_name, 
                           r.id as resort_id, r.resort_name, r.resort_slug, r.is_active
                    FROM destinations d
                    LEFT JOIN resorts r ON d.id = r.destination_id
                    WHERE r.id IS NOT NULL
                    AND r.is_active = 1
                    ORDER BY d.destination_name, r.resort_name";
            
            $stmt = $pdo->query($sql);
            
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
            
            // Clear and end output buffer
            ob_end_clean();
            
            return array_values($menuDestinations);
        } catch (Exception $e) {
            // In case of error, log it and return empty array
            error_log("Error in _ke_get_menu_destinations: " . $e->getMessage());
            return [];
        }
    }
    
    // Function to get current resort slug from URL
    function _ke_get_current_slug() {
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
        
        return '';
    }
    
    // Render the desktop mega menu
    function _ke_render_mega_menu() {
        try {
            // Use output buffering to capture HTML
            ob_start();
            
            $menuDestinations = _ke_get_menu_destinations();
            
            // If no destinations found, return empty string
            if (empty($menuDestinations)) {
                ob_end_clean();
                return '';
            }
            
            $menuCurrentSlug = _ke_get_current_slug();
            
            if (!empty($menuDestinations)):
            ?>
            <div class="mega-menu">
                <div class="container">
                    <div class="row">
                        <?php foreach ($menuDestinations as $destination): ?>
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
                </div>
            </div>
            <?php
            endif;
            
            // Return the captured HTML
            return ob_get_clean();
        } catch (Exception $e) {
            error_log("Error in _ke_render_mega_menu: " . $e->getMessage());
            return '';
        }
    }
    
    // Render the mobile menu
    function _ke_render_mobile_menu() {
        try {
            // Use output buffering to capture HTML
            ob_start();
            
            $menuDestinations = _ke_get_menu_destinations();
            
            // If no destinations found, render a simplified menu without destinations
            if (empty($menuDestinations)) {
                ?>
                <div class="mobile-menu-overlay"></div>
                <div class="th-mobile-menu">
                    <div class="mobile-menu-header">
                        <a href="index.php">
                            <img src="assets/images/logo/KE-Gold.png" alt="Karma Experience" style="height: 40px;">
                        </a>
                        <button class="mobile-menu-close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <ul class="mobile-menu-nav">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="Blogs.php">Our Blogs</a></li>
                        <li><a href="enquire-now.php">Enquire</a></li>
                        <li><a href="pay-now.php">Pay Now</a></li>
                    </ul>
                </div>
                <?php
                return ob_get_clean();
            }
            
            $menuCurrentSlug = _ke_get_current_slug();
            ?>
    <div class="mobile-menu-overlay"></div>
    <div class="th-mobile-menu">
        <div class="mobile-menu-header">
                <a href="index.php">
                <img src="assets/images/logo/KE-Gold.png" alt="Karma Experience" style="height: 40px;">
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
                            <?php foreach ($menuDestinations as $destination): ?>
                        <div class="destination-section">
                            <h3 class="destination-title">
                            <i class="fas fa-map-marker-alt"></i> 
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
            <li><a href="enquire-now.php">Enquire</a></li>
            <li><a href="pay-now.php">Pay Now</a></li>
        </ul>
    </div>
            <?php
            
            // Return the captured HTML
            return ob_get_clean();
        } catch (Exception $e) {
            error_log("Error in _ke_render_mobile_menu: " . $e->getMessage());
            return '';
        }
    }
    ?>

    <!-- Mobile Menu HTML -->
    <?php 
    try {
        echo _ke_render_mobile_menu(); 
    } catch (Exception $e) {
        error_log("Error rendering mobile menu: " . $e->getMessage());
    }
    ?>

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
                                                    <?php
                                        try {
                                            echo _ke_render_mega_menu(); 
                                        } catch (Exception $e) {
                                            error_log("Error rendering mega menu: " . $e->getMessage());
                                        }
                                        ?>
                                    </li>
                                    <li><a href="about.php">About Us</a></li>
                                </ul>
                            </nav>
                        </div>
                        <div class="col-auto">
                            <div class="header-logo">
                                <a href="index.php">
                                    <img src="assets/images/logo/KE-white.png" alt="Karma Experience">
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
                } else {
                    header.classList.remove('scrolled');
                }
            });

            // Mobile menu functions
            function openMobileMenu() {
                mobileMenu.classList.add('active');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeMobileMenu() {
                mobileMenu.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            // Mobile menu toggle
            if (menuToggle) {
            menuToggle.addEventListener('click', openMobileMenu);
            }
            
            if (mobileMenuClose) {
            mobileMenuClose.addEventListener('click', closeMobileMenu);
            }
            
            if (overlay) {
            overlay.addEventListener('click', closeMobileMenu);
            }

            // Handle submenu toggles
            if (submenuToggles) {
            submenuToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const submenu = this.nextElementSibling;
                        
                        if (submenu && submenu.classList.contains('mobile-submenu')) {
                    const isActive = submenu.classList.contains('active');
                    
                    // Close all other submenus
                    document.querySelectorAll('.mobile-submenu').forEach(sub => {
                        if (sub !== submenu) {
                            sub.classList.remove('active');
                                    if (sub.previousElementSibling) {
                            sub.previousElementSibling.classList.remove('active');
                                    }
                        }
                    });
                    
                    // Toggle clicked submenu
                    submenu.classList.toggle('active');
                    this.classList.toggle('active');
                        }
                });
            });
            }

            // Prevent clicks inside mobile menu from closing it
            if (mobileMenu) {
            mobileMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            }
        });
    </script>
</body>
</html>
