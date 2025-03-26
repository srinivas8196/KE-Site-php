<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Karma Experience India | Delivering unmatched holiday experiences at unbeatable prices</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  
  <!-- Free Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
  
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
  
  <!-- Your main CSS files -->
  <link rel="stylesheet" href="assets/css/style.css">
  <!-- Custom CSS for header adjustments -->
  <link rel="stylesheet" href="assets/css/custom-new.css">
  
  <style>
    /* Inline overrides for this header test */
    /* Mega Menu: full viewport width */
    .mega-menu {
      position: absolute;
      left: 0;
      top: 100%;
      width: 100vw;
      background: #fff;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      padding: 20px 0;
      display: none;
      z-index: 999;
    }
    /* Remove row breaks so that destination columns remain in one row */
    .mega-menu .destination-col {
      flex: 1;
      min-width: 200px; /* adjust as needed */
      padding: 0 15px;
    }
    .mega-menu ul {
      list-style: none;
      margin: 0;
      padding: 0;
    }
    .mega-menu ul li {
      margin-bottom: 10px;
    }
    .mega-menu ul li a {
      color: #333;
      text-decoration: none;
      font-weight: 500;
    }
    .mega-menu ul li a:hover {
      color: #007bff;
    }
    /* Mobile header adjustments */
    .mobile-header-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 20px;
      background: #fff;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .mobile-logo img {
      width: 120px;
    }
    .th-menu-toggle {
      border: none;
      background: none;
      font-size: 1.5rem;
      color: #333;
      cursor: pointer;
    }
    .th-mobile-menu {
      display: none;
      background: #fff;
      padding: 20px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .th-mobile-menu.active {
      display: block;
    }
    .th-mobile-menu ul {
      list-style: none;
      margin: 0;
      padding: 0;
    }
    .th-mobile-menu ul li {
      margin-bottom: 10px;
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
  // Include database connection (ensure db.php creates a valid $pdo)
  require 'db.php';
  
  // Fetch destinations and active resorts
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

<!-- MOBILE HEADER -->
<div class="th-menu-wrapper d-block d-xl-none">
  <div class="mobile-header-container">
    <!-- Mobile Logo (Left) -->
    <div class="mobile-logo">
      <a href="index.php">
        <img src="assets/images/logo/KE-Gold.png" alt="Karma Experience">
      </a>
    </div>
    <!-- Hamburger Icon (Right) -->
    <button id="mobileMenuToggle" class="th-menu-toggle">
      <i id="mobileMenuIcon" class="fas fa-bars"></i>
    </button>
  </div>
  <!-- Mobile Menu (hidden by default) -->
  <div id="mobileMenu" class="th-mobile-menu">
    <ul class="text-gray-800 font-medium">
      <li><a href="index.php">Home</a></li>
      <li id="mobileDestinations" class="menu-item-has-children">
        <a href="#">Destinations</a>
        <ul class="submenu">
          <?php foreach ($destinations as $destination_name => $resorts): ?>
            <li style="font-weight:bold;"><?php echo $destination_name; ?></li>
            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
              <?php foreach ($resorts as $resort): ?>
                <div class="truncate" style="flex:1 1 auto;">
                  <a href="<?php echo $resort['resort_slug']; ?>"><?php echo $resort['resort_name']; ?></a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </ul>
      </li>
      <li><a href="about.php">About Us</a></li>
      <li><a href="Blogs.php">Our Blogs</a></li>
      <li><a href="enquire-now.php">Enquire Now</a></li>
      <li><a href="pay-now.php">Pay Now</a></li>
    </ul>
  </div>
</div>

<!-- DESKTOP HEADER -->
<header class="th-header header-layout3 header-absolute">
  <div class="sticky-wrapper">
    <div class="menu-area">
      <div class="container">
        <div class="row align-items-center justify-content-between" style="min-height:70px;">
          <!-- Left: 2 Items -->
          <div class="col-auto d-none d-xl-block">
            <nav class="main-menu">
              <ul class="d-flex align-items-center">
                <li><a href="index.php">Home</a></li>
                <li class="menu-item-has-children mega-menu-wrap">
                  <a href="#">Destinations</a>
                  <div class="mega-menu">
                    <div class="container d-flex">
                      <?php 
                        foreach ($destinations as $destination_name => $resorts): 
                      ?>
                        <div class="destination-col">
                          <ul>
                            <li><strong><?php echo $destination_name; ?></strong></li>
                            <?php foreach ($resorts as $resort): ?>
                              <li class="truncate">
                                <a href="<?php echo $resort['resort_slug']; ?>">
                                  <?php echo $resort['resort_name']; ?>
                                </a>
                              </li>
                            <?php endforeach; ?>
                          </ul>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </li>
              </ul>
            </nav>
          </div>
          <!-- Center: LOGO -->
          <div class="col-auto">
            <div class="header-logo text-center">
              <a href="index.php">
                <img src="assets/images/logo/KE-white.png" alt="Karma Experience" style="width:150px;">
              </a>
            </div>
          </div>
          <!-- Right: Remaining Items + Pay Now -->
          <div class="col-auto d-none d-xl-block">
            <nav class="main-menu">
              <ul class="d-flex align-items-center">
                <li><a href="about.php">About Us</a></li>
                <li><a href="Blogs.php">Our Blogs</a></li>
                <li><a href="enquire-now.php">Enquire Now</a></li>
              </ul>
            </nav>
            <div class="header-button ms-3">
              <a href="pay-now.php" class="th-btn style3">Pay Now</a>
            </div>
          </div>
        </div><!-- .row -->
      </div><!-- .container -->
    </div><!-- .menu-area -->
  </div><!-- .sticky-wrapper -->
</header>

<!-- Include custom JS -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Mobile Menu Toggle
  const mobileMenuToggle = document.getElementById('mobileMenuToggle');
  const mobileMenuIcon = document.getElementById('mobileMenuIcon');
  const mobileMenu = document.getElementById('mobileMenu');
  
  if (mobileMenuToggle && mobileMenu && mobileMenuIcon) {
    mobileMenuToggle.addEventListener('click', function () {
      mobileMenu.classList.toggle('active');
      if (mobileMenu.classList.contains('active')) {
        mobileMenuIcon.className = "fas fa-times";
      } else {
        mobileMenuIcon.className = "fas fa-bars";
      }
    });
  }
  
  // Mobile Destinations Submenu Toggle
  const mobileDestinations = document.getElementById('mobileDestinations');
  if (mobileDestinations) {
    const destinationsLink = mobileDestinations.querySelector('a');
    destinationsLink.addEventListener('click', function(e) {
      e.preventDefault();
      mobileDestinations.classList.toggle('active');
    });
  }
});
</script>
</body>
</html>
