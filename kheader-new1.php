<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Karma Experience India | Delivering unmatched holiday experiences at unbeatable prices</title>
  <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
  
  <!-- Free Font Awesome 6 (for fas icons) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
  
  <!-- Bootstrap CSS (optional, but helps for classes) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
  
  <!-- Your other CSS files -->
  <link rel="stylesheet" href="assets/css/style.css">
  <!-- Final custom CSS -->
  <link rel="stylesheet" href="assets/css/custom-new.css">
  
  <style>
    /* Optional inline overrides if needed */
    .submenu { display: none; padding-left: 20px; }
    .menu-item-has-children.active .submenu { display: block; }
  </style>
</head>
<body>
<?php
// Database connection
include 'db.php';

// Fetch active destinations/resorts
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

<!-- =========================
     MOBILE MENU (below xl)
     ========================= -->
<div class="th-menu-wrapper d-block d-xl-none">
  <div class="th-menu-area bg-white shadow-md p-4 text-center">
    <!-- Single hamburger toggle -->
    <button id="mobileMenuToggle" class="th-menu-toggle text-2xl border-0 bg-transparent">
      <!-- Use fas (free) icons -->
      <i id="mobileMenuIcon" class="fas fa-bars"></i>
    </button>
    
    <!-- Mobile Logo -->
    <div class="mobile-logo mt-4">
      <a href="index.php">
        <img src="assets/images/logo/KE-Gold.png" alt="Karma Experience" class="w-36 mx-auto">
      </a>
    </div>
    
    <!-- Actual mobile menu items -->
    <div id="mobileMenu" class="th-mobile-menu mt-4">
      <ul class="space-y-2 text-gray-800 font-medium">
        <!-- Two left items -->
        <li><a href="index.php" class="block hover:text-blue-600">Home</a></li>
        <li id="mobileDestinations" class="menu-item-has-children">
          <a href="#" class="block hover:text-blue-600">Destinations</a>
          <ul id="mobileDestinationsSubmenu" class="submenu pl-4 space-y-2">
            <?php foreach ($destinations as $destination_name => $resorts): ?>
              <li class="font-bold text-gray-900"><?php echo $destination_name; ?></li>
              <div class="grid grid-cols-2 gap-2">
                <?php foreach ($resorts as $resort): ?>
                  <div class="truncate">
                    <a href="<?php echo $resort['resort_slug']; ?>" class="hover:text-blue-600">
                      <?php echo $resort['resort_name']; ?>
                    </a>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          </ul>
        </li>
        <li><a href="about.php" class="block hover:text-blue-600">About Us</a></li>
        
        <!-- Right items -->
        <li><a href="Blogs.php" class="block hover:text-blue-600">Our Blogs</a></li>
        <li><a href="enquire-now.php" class="block hover:text-blue-600">Enquire Now</a></li>
        <li><a href="pay-now.php" class="block hover:text-blue-600">Pay Now</a></li>
      </ul>
    </div>
  </div>
</div>

<!-- =========================
     DESKTOP HEADER (>= xl)
     ========================= -->
<header class="th-header header-layout3 header-absolute">
  <div class="sticky-wrapper">
    <div class="menu-area">
      <div class="container">
        <div class="row align-items-center justify-content-between" style="min-height:70px;">
          
          <!-- Left: 2 Items (desktop only) -->
          <div class="col-auto d-none d-xl-block">
            <nav class="main-menu">
              <ul class="d-flex align-items-center">
                <li><a href="index.php">Home</a></li>
                <li class="menu-item-has-children mega-menu-wrap">
                  <a href="#">Destinations</a>
                  <div class="mega-menu">
                    <div class="container">
                      <div class="row">
                        <?php
                        $col_count = 0;
                        foreach ($destinations as $destination_name => $resorts) {
                            if ($col_count % 4 == 0 && $col_count !== 0) {
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

          <!-- Center: LOGO -->
          <div class="col-auto">
            <div class="header-logo">
              <a href="index.php">
                <img src="assets/images/logo/KE-white.png" alt="Karma Experience" style="width:150px;">
              </a>
            </div>
          </div>

          <!-- Right: Remaining Items + Pay Now (desktop only) -->
          <div class="col-auto d-none d-xl-block">
            <nav class="main-menu d-inline-block">
              <ul class="d-flex align-items-center">
                <li><a href="Blogs.php">Our Blogs</a></li>
                <li><a href="enquire-now.php">Enquire Now</a></li>
              </ul>
            </nav>
            <div class="header-button d-inline-block ms-3">
              <a href="pay-now.php" class="th-btn style3 th-icon">Pay Now</a>
            </div>
          </div>

        </div><!-- .row -->
      </div><!-- .container -->
    </div><!-- .menu-area -->
  </div><!-- .sticky-wrapper -->
</header>

<!-- Single JS for toggling mobile menu -->
<script src="assets/js/custom-new.js"></script>
</body>
</html>
