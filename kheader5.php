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
    <link rel="stylesheet" href="assets/css/custom.css"> <!-- Add custom CSS file -->
    <!-- End of included CSS files -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"> Add Tailwind CSS -->
    <style>
        /* Ensure menu items are visible on mobile devices */
        .th-mobile-menu {
            display: b;
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
    </style>
</head>

<body>
    <?php
    // Include database connection
    include 'db.php';

    // Fetch destinations and resorts for the header mega menu
    $sql = "SELECT d.destination_name, r.resort_name, r.resort_slug, r.banner_image, r.resort_description 
            FROM resorts r 
            JOIN destinations d ON r.destination_id = d.id 
            WHERE r.is_active = 1 
            ORDER BY d.destination_name, r.resort_name";
    $stmt = $pdo->query($sql);

    $menuDestinations = [];
    while ($row = $stmt->fetch()) {
        $menuDestinations[$row['destination_name']][] = $row;
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
                                        <div class="truncate"><a href="<?php echo $resort['resort_slug']; ?>" class="hover:text-blue-600"><?php echo $resort['resort_name']; ?></a></div>
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
                                                    <?php
                                                    $col_count = 0;
                                                    foreach ($menuDestinations as $destination_name => $resorts) {
                                                        if ($col_count % 4 == 0 && $col_count != 0) {
                                                            echo '</div><div class="row">';
                                                        }
                                                        echo '<div class="col-md-3"><ul>';
                                                        echo '<li><strong>' . $destination_name . '</strong></li>';
                                                        foreach ($resorts as $resort) {
                                                            echo '<li class="truncate"><a href="' . $resort['resort_slug'] . '">' . $resort['resort_name'] . '</a></li>';
                                                        }
                                                        echo '</ul></div>';
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
    <!-- Start of included JS files -->
    <script src="assets/js/custom.js"></script> <!-- Include custom JS file -->
    <!-- End of included JS files -->
</body>
</html>
