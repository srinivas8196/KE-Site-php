<!doctype html>
<html class="no-js" lang="zxx">
<head>
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>Karma Experience India | Delivering unmatched holiday experiences at unbeatable prices</title>
  <meta name="author" content="Karma Experience">
  <meta name="description" content="Karma Experience - Delivering unmatched holiday experiences at unbeatable prices ">
  <meta name="keywords" content="Karma Experience - Delivering unmatched holiday experiences at unbeatable prices">
  <meta name="robots" content="INDEX,FOLLOW">
  <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
  
  <!-- Favicon -->
  <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logo/K-logo.png">
  
  <!-- Preconnect & Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com/">
  <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&amp;family=Manrope:wght@200..800&amp;family=Montez&amp;display=swap" rel="stylesheet">
  
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <!-- Font Awesome CSS -->
  <link rel="stylesheet" href="assets/css/fontawesome.min.css">
  <!-- Other CSS Files -->
  <link rel="stylesheet" href="assets/css/magnific-popup.min.css">
  <link rel="stylesheet" href="assets/css/swiper-bundle.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/custom.css">
  
  <!-- Tailwind CSS --> 
  <!-- <script src="https://cdn.tailwindcss.com"></script> -->
</head>
<body>
    <?php
    // Include database connection and fetch destinations/resorts for the mega menu
    require 'db.php';
    $sql = "SELECT d.destination_name, r.resort_name, r.resort_slug 
            FROM resorts r 
            JOIN destinations d ON r.destination_id = d.id 
            WHERE r.is_active = 1 
            ORDER BY d.destination_name, r.resort_name";
    $stmt = $pdo->query($sql);
    $destinations = [];
    while ($row = $stmt->fetch()) {
        $destinations[$row['destination_name']][] = $row;
    }
    ?>
    
    <!-- Mobile Menu Wrapper (Visible on Mobile Devices) -->
    <div class="th-menu-wrapper onepage-nav block md:hidden">
        <div class="th-menu-area bg-white shadow-md p-4 text-center">
            <button id="mobileMenuToggle" class="th-menu-toggle text-2xl focus:outline-none"><i class="fal fa-bars"></i></button>
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
                        <ul class="pl-4 space-y-2">
                            <?php foreach ($destinations as $destination_name => $resorts) { ?>
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
    
    <!-- Desktop Header -->
    <header class="th-header header-layout3 header-absolute fixed top-0 left-0 w-full z-50 bg-transparent">
        <div class="sticky-wrapper">
            <div class="menu-area">
                <div class="container mx-auto px-4">
                    <div class="flex items-center justify-between py-4">
                        <!-- Left Menu -->
                        <nav class="hidden xl:flex space-x-6 text-white">
                            <a href="index.php" class="hover:text-yellow-400">Home</a>
                            <div class="relative group">
                                <a href="#" class="hover:text-yellow-400">Destinations</a>
                                <div class="mega-menu absolute left-0 top-full mt-2 w-full bg-white shadow-lg hidden group-hover:block">
                                    <div class="container mx-auto p-4">
                                        <div class="grid grid-cols-4 gap-4">
                                            <?php foreach ($destinations as $destination_name => $resorts) { ?>
                                                <div>
                                                    <h3 class="font-bold text-gray-800 mb-2"><?php echo $destination_name; ?></h3>
                                                    <ul class="space-y-1">
                                                        <?php foreach ($resorts as $resort) { ?>
                                                            <li class="truncate"><a href="<?php echo $resort['resort_slug']; ?>" class="text-gray-600 hover:text-blue-600"><?php echo $resort['resort_name']; ?></a></li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <a href="benefits.php" class="hover:text-yellow-400">Benefits</a>
                            <a href="about.php" class="hover:text-yellow-400">About Us</a>
                        </nav>
                        <!-- Logo in Center -->
                        <div class="flex-shrink-0">
                            <a href="index.php"><img src="assets/images/logo/KE-white.png" alt="Karma Experience" class="w-36"></a>
                        </div>
                        <!-- Right Menu -->
                        <nav class="hidden xl:flex space-x-6 text-white">
                            <a href="Blogs.php" class="hover:text-yellow-400">Our Blogs</a>
                            <a href="enquire-now.php" class="hover:text-yellow-400">Enquire Now</a>
                        </nav>
                        <!-- Mobile Toggle for Desktop -->
                        <div class="xl:hidden">
                            <button type="button" class="th-menu-toggle text-white text-2xl"><i class="far fa-bars"></i></button>
                        </div>
                        <!-- Rightmost Button -->
                        <div class="hidden xl:block">
                            <a href="pay-now.php" class="bg-yellow-500 text-black px-4 py-2 rounded">Pay Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Mobile Menu Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuToggle = document.getElementById('mobileMenuToggle');
            const mobileMenu = document.getElementById('mobileMenu');
            menuToggle.addEventListener('click', function () {
                mobileMenu.classList.toggle('active');
            });
        });
    </script>
    
    <!-- Custom CSS for Mobile Menu and Mega Menu Adjustments -->
    <style>
        .th-mobile-menu {
            display: none;
        }
        .th-mobile-menu.active {
            display: block;
        }
        /* Adjust mega menu on mobile */
        @media (max-width: 768px) {
            .mega-menu .container {
                padding: 10px;
            }
            .mega-menu .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
</body>
</html>
