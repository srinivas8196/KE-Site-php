<?php
require 'db.php';
include 'kheader.php';

// Get destination ID from URL
$destination_id = isset($_GET['dest_id']) ? (int)$_GET['dest_id'] : 0;
if (!$destination_id) {
    header('Location: our-destinations.php');
    exit;
}

// Fetch destination details
try {
    $stmtDest = $pdo->prepare("SELECT id, destination_name, banner_image FROM destinations WHERE id = ?");
    $stmtDest->execute([$destination_id]);
    $destination = $stmtDest->fetch();

    if (!$destination) {
        header('Location: our-destinations.php');
        exit;
    }

    // Fetch active resorts for this destination
    $stmtResorts = $pdo->prepare("
        SELECT id, resort_name, resort_slug, file_path, banner_image, resort_description
        FROM resorts
        WHERE destination_id = ? AND is_active = 1
        ORDER BY resort_name ASC
    ");
    $stmtResorts->execute([$destination_id]);
    $resorts = $stmtResorts->fetchAll();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $destination = [];
    $resorts = [];
}

// Prepare banner image path
$destination_base_path = 'assets/destinations/';
$destination_slug = strtolower(str_replace(' ', '-', $destination['destination_name'] ?? ''));
$destination_slug = preg_replace('/[^a-z0-9\-]/', '', $destination_slug);
$banner_image = $destination['banner_image'] ?? 'default-banner.jpg';
$banner_path = $destination_base_path . $destination_slug . '/' . $banner_image;

if (!file_exists($banner_path) || empty($destination['banner_image'])) {
    $banner_path = $destination_base_path . 'default-banner.jpg';
}

// Function to truncate text
function truncate_text($text, $length = 100, $suffix = '...') {
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        $text .= $suffix;
    }
    return $text;
}
?>

<style>
    /* Banner styling */
    .destination-banner {
        height: 50vh;
        min-height: 400px;
        background-size: cover;
        background-position: center;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: white;
        margin-bottom: 50px;
        padding-top: 60px;
    }
    
    .destination-banner::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.4);
    }
    
    .banner-content {
        position: relative;
        z-index: 1;
        max-width: 800px;
        padding: 0 20px;
    }
    
    .banner-title {
        font-size: 3.5rem;
        font-weight: 700;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        margin-bottom: 15px;
        color: #ffffff;
    }
    
    .banner-subtitle {
        font-size: 1.2rem;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7);
        color: #ffffff;
    }
    
    /* Resort card styling */
    .resort-container {
        padding-bottom: 60px;
        background-color: #f8f9fa;
    }
    
    .resort-card {
        background-color: #fff;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 30px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    
    .resort-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    }
    
    .resort-card .card-image {
        height: 200px;
        width: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .resort-card:hover .card-image {
        transform: scale(1.05);
    }
    
    .resort-card .card-body {
        padding: 25px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    
    .resort-card .card-title {
        font-size: 1.4rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 15px;
        text-align: center;
    }
    
    .resort-card .card-text {
        font-size: 0.95rem;
        color: #666;
        margin-bottom: 20px;
        flex-grow: 1;
    }
    
    .resort-card .btn-view {
        align-self: center;
        padding: 10px 25px;
        background-color: #3498db;
        color: white;
        border-radius: 30px;
        text-decoration: none;
        font-size: 0.95rem;
        transition: background-color 0.3s;
        text-align: center;
    }
    
    .resort-card .btn-view:hover {
        background-color: #2980b9;
        color: white;
        text-decoration: none;
    }
    
    /* No resorts message */
    .no-resorts {
        text-align: center;
        padding: 40px 0;
        background-color: #fff;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    .no-resorts h3 {
        color: #2c3e50;
        margin-bottom: 15px;
        font-weight: 600;
    }
    
    .no-resorts p {
        color: #7f8c8d;
        margin-bottom: 25px;
    }
    
    .btn-back {
        display: inline-block;
        padding: 12px 30px;
        background-color: #3498db;
        color: white;
        border-radius: 30px;
        text-decoration: none;
        transition: background-color 0.3s;
        font-weight: 500;
    }
    
    .btn-back:hover {
        background-color: #2980b9;
        color: white;
        text-decoration: none;
    }
    
    @media (max-width: 767px) {
        .banner-title {
            font-size: 2.5rem;
        }
        
        .resort-card .card-image {
            height: 180px;
        }
    }
</style>

<!-- Destination Banner -->
<div class="destination-banner" style="background-image: url('<?php echo htmlspecialchars($banner_path); ?>');">
    <div class="banner-content">
        <h1 class="banner-title"><?php echo htmlspecialchars($destination['destination_name'] ?? 'Destination'); ?></h1>
        <p class="banner-subtitle">
            <?php 
                $resort_count = count($resorts);
                if ($resort_count > 0) {
                    echo ($resort_count == 1) ? '1 Resort Available' : $resort_count . ' Resorts Available';
                } else {
                    echo 'Explore Our Destination';
                }
            ?>
        </p>
    </div>
</div>

<div class="resort-container">
    <div class="container">
        <?php if (!empty($resorts)): ?>
            <div class="row">
                <?php foreach ($resorts as $resort): ?>
                    <?php
                        // Prepare image path
                        $resort_slug = isset($resort['resort_slug']) ? $resort['resort_slug'] : 'default';
                        $resort_folder = 'assets/resorts/' . $resort_slug;
                        
                        $resort_banner = isset($resort['banner_image']) ? $resort['banner_image'] : 'default-resort.jpg';
                        $image_path = $resort_folder . '/' . $resort_banner;

                        if (!file_exists($image_path)) {
                            $image_path = 'assets/resorts/default/default-resort.jpg';
                        }

                        // Prepare link
                        $resort_link = isset($resort['file_path']) && !empty($resort['file_path']) ? $resort['file_path'] : '#';

                        // Prepare description
                        $resort_description = isset($resort['resort_description']) ? $resort['resort_description'] : '';
                        $short_description = truncate_text(htmlspecialchars($resort_description), 150);
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="resort-card">
                            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($resort['resort_name']); ?>" class="card-image">
                            <div class="card-body">
                                <h3 class="card-title"><?php echo htmlspecialchars($resort['resort_name']); ?></h3>
                                <p class="card-text"><?php echo $short_description; ?></p>
                                <a href="<?php echo htmlspecialchars($resort_link); ?>" class="btn-view">View Resort</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-resorts">
                <h3>No Resorts Available</h3>
                <p>We're currently developing new properties in this destination.</p>
                <a href="our-destinations.php" class="btn-back">Back to Destinations</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'kfooter.php'; ?>