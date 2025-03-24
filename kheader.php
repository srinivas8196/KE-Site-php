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
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&amp;family=Manrope:wght@200..800&amp;family=Montez&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" integrity="sha512-dPXYcDub/aeb08c63jRq/k6GaKccl256JQy/AnOq7CAnEZ9FzSL9wSbcZkMp4R26vBsMLFYH4kQ67/bbV8XaCQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel/slick/slick.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel/slick/slick-theme.css"/>
    
    <!-- Start of included CSS files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="assets/css/magnific-popup.min.css">
    <link rel="stylesheet" href="assets/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    <!-- End of included CSS files -->
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
        /* Style for showing the banner title under resort name in the mega menu */
        .banner-title {
            font-size: 0.8rem;
            color: #888;
        }
    </style>
</head>
<body>
    <?php
    require_once __DIR__ . '/vendor/autoload.php';
    use Models\Resort;
    use Models\Destination;
    use Database\SupabaseConnection;

    try {
        $supabaseConnection = SupabaseConnection::getInstance();
        
        // Get active resorts grouped by destinations
        $activedestinations = $supabaseConnection->query('destinations', [
            'select' => '*,resorts(*)'
        ]);
        
        $menuDestinations = [];
        if ($activedestinations) {
            foreach ($activedestinations as $dest) {
                $menuDestinations[$dest['destination_name']] = array_filter($dest['resorts'], function($resort) {
                    return $resort['is_active'] == true;
                });
            }
        }
    } catch (Exception $e) {
        error_log("Supabase error: " . $e->getMessage());
        $menuDestinations = [];
    }
    ?>
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
                        <ul class="submenu pl-4 space-y-2">
                            <?php foreach ($menuDestinations as $destination_name => $resorts) { ?>
                                <li class="font-bold text-gray-900"><?php echo $destination_name; ?></li>
                                <div class="grid grid-cols-2 gap-2">
                                    <?php foreach ($resorts as $resort) { ?>
                                        <div class="truncate">
                                            <a href="<?php echo $resort['resort_slug']; ?>" class="hover:text-blue-600">
                                                <?php echo $resort['resort_name']; ?>
                                                <br>
                                                <span class="banner-title"><?php echo $resort['banner_title']; ?></span>
                                            </a>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </ul>
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
                                    <li class="menu-item-has-children mega-menu-wrap">
                                        <a href="#">Destinations</a>
                                        <div class="mega-menu d-mega-menu">
                                            <div class="container">
                                                <div class="row">
                                                    <?php
                                                    $col_count = 0;
                                                    foreach ($menuDestinations as $destination_name => $resorts) {
                                                        if ($col_count % 4 == 0 && $col_count != 0) {
                                                            echo '</div><div class="row">';
                                                        }
                                                        echo '<ul>';
                                                        echo '<li><strong>' . $destination_name . ':</strong></li>';
                                                        foreach ($resorts as $resort) {
                                                            echo '<li class="truncate">';
                                                            echo '<a href="' . $resort['resort_slug'] . '">';
                                                            echo $resort['resort_name'] ;
                                                            echo '<span class="banner-title">' . $resort['banner_title'] . '</span>';
                                                            echo '</a></li>';
                                                        }
                                                        echo '</ul>';
                                                        $col_count++;
                                                    }
                                                    ?>
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
                                <a href="index.php">
                                    <img src="assets/images/logo/KE-white.png" alt="Karma Experience" style="width:150px;">
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
                                <i class="far fa-bars"></i>
                            </button>
                        </div>
                        <div class="col-auto d-none d-xl-block">
                            <div class="header-button">
                                <a href="pay-now" class="th-btn style3 th-icon">Pay Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- Start of included JS files -->
    <script src="assets/js/custom.js"></script>
    <!-- End of included JS files -->
</body>
</html>
