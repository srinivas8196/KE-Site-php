<?php
require 'db.php';

// Ensure $pdo is initialized
if (!$pdo) {
    // Handle error appropriately - maybe log and exit, or return an error message
    // For now, we'll just exit to prevent further errors.
    exit('Database connection failed.');
}

// Get filter parameters
$selected_destination = isset($_GET['destination']) && $_GET['destination'] !== '' ? (int)$_GET['destination'] : null;
$selected_resort = isset($_GET['resort']) && $_GET['resort'] !== '' ? (int)$_GET['resort'] : null;

// Build the query for gallery images
$query = "SELECT r.id as resort_id, r.resort_name, r.resort_slug, r.gallery, d.id as destination_id, d.destination_name 
          FROM resorts r 
          JOIN destinations d ON r.destination_id = d.id 
          WHERE r.is_active = 1";

if ($selected_destination) {
    $query .= " AND r.destination_id = :destination_id";
}

if ($selected_resort) {
    $query .= " AND r.id = :resort_id";
}

$query .= " ORDER BY d.destination_name, r.resort_name";

$stmt = $pdo->prepare($query);

if ($selected_destination) {
    $stmt->bindParam(':destination_id', $selected_destination, PDO::PARAM_INT);
}

if ($selected_resort) {
    $stmt->bindParam(':resort_id', $selected_resort, PDO::PARAM_INT);
}

$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output filtered gallery items
if (empty($results)): ?>
    <div class="no-results">
        <i class="fas fa-images"></i>
        <h3>No images found</h3>
        <p>Try selecting a different destination or resort</p>
    </div>
<?php else: ?>
    <?php foreach ($results as $result): 
        $gallery_images = json_decode($result['gallery'] ?? '[]', true);
        if (!empty($gallery_images)):
            foreach ($gallery_images as $image):
                $resortFolder = "assets/resorts/" . $result['resort_slug'];
                $imagePath = $resortFolder . '/' . $image;
                $caption = htmlspecialchars($result['resort_name']) . ' - ' . htmlspecialchars($result['destination_name']);
    ?>
        <div class="gallery-item" data-resort="<?php echo htmlspecialchars($result['resort_name']); ?>" data-destination="<?php echo htmlspecialchars($result['destination_name']); ?>">
            <a href="<?php echo $imagePath; ?>" data-lightbox="gallery" data-title="<?php echo $caption; ?>" class="gallery-link">
                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($result['resort_name']); ?>" class="gallery-image">
                <div class="gallery-overlay">
                    <div class="gallery-info">
                        <h4><?php echo htmlspecialchars($result['resort_name']); ?></h4>
                        <p><?php echo htmlspecialchars($result['destination_name']); ?></p>
                    </div>
                </div>
            </a>
        </div>
    <?php 
            endforeach;
        endif;
    endforeach; ?>
<?php endif; ?> 