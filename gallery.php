<?php
require 'db.php';
require 'kheader.php';

// Get all destinations for filter
$destinations_query = "SELECT id, destination_name FROM destinations ORDER BY destination_name";
$destinations_stmt = $pdo->query($destinations_query);
$destinations = $destinations_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get filter parameters
$selected_destination = isset($_GET['destination']) && $_GET['destination'] !== '' ? (int)$_GET['destination'] : null;
$selected_resort = isset($_GET['resort']) && $_GET['resort'] !== '' ? (int)$_GET['resort'] : null;

// Get all resorts for JavaScript filtering
$all_resorts_query = "SELECT id, resort_name, destination_id FROM resorts WHERE is_active = 1 ORDER BY resort_name";
$all_resorts_stmt = $pdo->query($all_resorts_query);
$all_resorts = $all_resorts_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get resorts based on selected destination for initial load
$resorts_query = "SELECT id, resort_name, destination_id FROM resorts WHERE is_active = 1";
if ($selected_destination) {
    $resorts_query .= " AND destination_id = :destination_id";
}
$resorts_query .= " ORDER BY resort_name";
$resorts_stmt = $pdo->prepare($resorts_query);
if ($selected_destination) {
    $resorts_stmt->bindParam(':destination_id', $selected_destination, PDO::PARAM_INT);
}
$resorts_stmt->execute();
$resorts = $resorts_stmt->fetchAll(PDO::FETCH_ASSOC);

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
?>

<!-- Link to all CSS first -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" />

<style>
/* Banner Styles */
.gallery-banner {
    position: relative;
    height: 500px;
    background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/images/banner/gallery-banner.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 60px;
    overflow: hidden;
}

.banner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,0.5) 0%, rgba(0,0,0,0.7) 100%);
}

.banner-container {
    position: relative;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    z-index: 2;
    text-align: center;
    padding: 0 20px;
}

.banner-content {
    max-width: 800px;
    margin: 0 auto;
    animation: fadeInUp 1s ease-out;
}

.banner-title {
    font-size: 4.5rem;
    font-weight: 700;
    margin-bottom: 25px;
    text-transform: uppercase;
    letter-spacing: 4px;
    color: white;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.banner-divider {
    width: 80px;
    height: 3px;
    background-color: white;
    margin: 0 auto 25px;
}

.banner-subtitle {
    font-size: 1.4rem;
    line-height: 1.6;
    font-weight: 300;
    color: white;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    max-width: 600px;
    margin: 0 auto;
    opacity: 0.9;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Gallery Page Styles */
.gallery-page-wrapper {
    padding: 0 0 80px;
    background: #f8f9fa;
}

/* Filter Styles */
.gallery-filters {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
    margin-bottom: 40px;
}

.filter-header {
    margin-bottom: 25px;
    text-align: center;
}

.filter-header h3 {
    font-size: 1.8rem;
    margin-bottom: 10px;
    color: #333;
}

.filter-header p {
    color: #666;
    font-size: 1rem;
}

.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: flex-end;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #555;
}

.form-select {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #f9f9f9;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-select:focus {
    border-color: #B4975A;
    box-shadow: 0 0 0 3px rgba(180, 151, 90, 0.1);
    outline: none;
}

.filter-actions {
    display: flex;
    gap: 15px;
    margin-top: 10px;
}

.filter-btn, .reset-btn {
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    text-decoration: none;
}

.filter-btn {
    background-color: #B4975A;
    color: white;
    border: none;
}

.filter-btn:hover {
    background-color: #9e8050;
}

.reset-btn {
    background-color: #f1f1f1;
    color: #555;
    border: 1px solid #ddd;
}

.reset-btn:hover {
    background-color: #e5e5e5;
}

/* Gallery Grid Styles */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
    margin-top: 30px;
}

.gallery-item {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    aspect-ratio: 4/3;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.4s ease;
}

.gallery-item:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
}

.gallery-link {
    display: block;
    width: 100%;
    height: 100%;
}

.gallery-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.gallery-item:hover .gallery-image {
    transform: scale(1.05);
}

.gallery-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 50%, rgba(0,0,0,0) 100%);
    display: flex;
    align-items: flex-end;
    padding: 25px;
    opacity: 0;
    transition: opacity 0.4s ease;
}

.gallery-item:hover .gallery-overlay {
    opacity: 1;
}

.gallery-info {
    color: white;
    transform: translateY(20px);
    transition: transform 0.4s ease;
}

.gallery-item:hover .gallery-info {
    transform: translateY(0);
}

.gallery-info h4 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
    text-shadow: 0 1px 3px rgba(0,0,0,0.3);
    color: white;
}

.gallery-info p {
    margin: 5px 0 0;
    font-size: 1rem;
    opacity: 0.9;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    color: white;
}

/* No Results */
.no-results {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
}

.no-results i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 20px;
}

.no-results h3 {
    font-size: 1.8rem;
    margin-bottom: 10px;
    color: #333;
}

.no-results p {
    color: #666;
    font-size: 1.1rem;
}

/* Loading Indicator */
.loading-indicator {
    display: none;
    text-align: center;
    padding: 20px;
    font-size: 1.2rem;
    color: #666;
}

.loading-indicator i {
    margin-right: 10px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Styles */
@media (max-width: 992px) {
    .banner-title {
        font-size: 3.5rem;
        letter-spacing: 3px;
    }
    
    .banner-subtitle {
        font-size: 1.2rem;
    }
    
    .gallery-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
}

@media (max-width: 768px) {
    .gallery-banner {
        height: 400px;
        background-attachment: scroll;
    }
    
    .banner-title {
        font-size: 2.8rem;
        letter-spacing: 2px;
    }
    
    .banner-subtitle {
        font-size: 1.1rem;
    }
    
    .banner-divider {
        width: 60px;
        margin: 0 auto 20px;
    }
    
    .gallery-filters {
        padding: 20px;
    }
    
    .filter-form {
        flex-direction: column;
        gap: 15px;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .filter-actions {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 576px) {
    .gallery-banner {
        height: 350px;
    }
    
    .banner-title {
        font-size: 2.2rem;
        letter-spacing: 1px;
        margin-bottom: 15px;
    }
    
    .banner-subtitle {
        font-size: 1rem;
    }
    
    .banner-divider {
        width: 50px;
        margin: 0 auto 15px;
    }
    
    .gallery-grid {
        grid-template-columns: 1fr;
    }
}

/* Lightbox custom styles */
.lb-data .lb-caption {
    font-size: 16px;
    font-weight: bold;
    color: #fff;
}

.lb-data .lb-details {
    width: 100%;
    text-align: center;
}

.lb-closeContainer {
    position: absolute;
    top: 0;
    right: 0;
}

.lb-nav a.lb-prev,
.lb-nav a.lb-next {
    opacity: 0.5;
}

.lb-nav a.lb-prev:hover,
.lb-nav a.lb-next:hover {
    opacity: 1;
}

.lb-close {
    background-color: #B4975A;
    border-radius: 50%;
    padding: 5px;
}
</style>

<!-- Loading Indicator -->
<div class="loading-indicator" id="loadingIndicator">
    <i class="fas fa-spinner"></i> Loading gallery images...
</div>

<!-- Banner Section -->
<div class="gallery-banner">
    <div class="banner-overlay"></div>
    <div class="banner-container">
        <div class="banner-content">
            <h1 class="banner-title">OUR GALLERY</h1>
            <div class="banner-divider"></div>
            <p class="banner-subtitle">Explore the beauty of our resorts through our stunning collection of images</p>
        </div>
    </div>
</div>

<!-- Gallery Section -->
<div class="gallery-page-wrapper">
    <div class="container">
        <!-- Filters -->
        <div class="gallery-filters">
            <div class="filter-header">
                <h3>Filter Gallery</h3>
                <p>Select destination or resort to view specific images</p>
            </div>
            <div class="filter-form" id="filterForm">
                <div class="filter-group">
                    <label for="destination">Destination</label>
                    <select name="destination" id="destination" class="form-select">
                        <option value="">All Destinations</option>
                        <?php foreach ($destinations as $destination): ?>
                            <option value="<?php echo $destination['id']; ?>" <?php echo $selected_destination == $destination['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($destination['destination_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="resort">Resort</label>
                    <select name="resort" id="resort" class="form-select">
                        <option value="">All Resorts</option>
                        <?php foreach ($resorts as $resort): ?>
                            <option value="<?php echo $resort['id']; ?>" 
                                    data-destination="<?php echo $resort['destination_id']; ?>"
                                    <?php echo $selected_resort == $resort['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($resort['resort_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-actions">
                    <?php if ($selected_destination || $selected_resort): ?>
                        <a href="javascript:void(0)" class="reset-btn" id="resetFilters">Reset</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Gallery Grid -->
        <div class="gallery-grid" id="galleryGrid">
            <?php if (empty($results)): ?>
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
        </div>
    </div>
</div>

<!-- Load scripts at the end -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

<script>
// Store all resorts data for JavaScript filtering
const allResorts = <?php echo json_encode($all_resorts); ?>;

$(document).ready(function() {
    // Configure Lightbox
    lightbox.option({
        'resizeDuration': 200,
        'wrapAround': true,
        'positionFromTop': 50,
        'showImageNumberLabel': false,
        'alwaysShowNavOnTouchDevices': true,
        'albumLabel': "%1 / %2"
    });
    
    // Function to update resort options based on selected destination
    function updateResortOptions() {
        const destinationSelect = document.getElementById('destination');
        const resortSelect = document.getElementById('resort');
        const selectedDestination = destinationSelect.value;
        
        // Clear current options except the first one
        while (resortSelect.options.length > 1) {
            resortSelect.remove(1);
        }
        
        // Add filtered resort options
        allResorts.forEach(resort => {
            if (!selectedDestination || resort.destination_id == selectedDestination) {
                const option = document.createElement('option');
                option.value = resort.id;
                option.textContent = resort.resort_name;
                option.setAttribute('data-destination', resort.destination_id);
                resortSelect.appendChild(option);
            }
        });
        
        // Update gallery without page refresh
        updateGallery();
    }
    
    // Function to update the gallery content via AJAX
    function updateGallery() {
        const destinationSelect = document.getElementById('destination');
        const resortSelect = document.getElementById('resort');
        const selectedDestination = destinationSelect.value;
        const selectedResort = resortSelect.value;
        
        // Show loading indicator
        $('#loadingIndicator').show();
        
        // Create URL with filter parameters
        let url = 'gallery_ajax.php';
        const params = new URLSearchParams();
        
        if (selectedDestination) {
            params.append('destination', selectedDestination);
        }
        
        if (selectedResort) {
            params.append('resort', selectedResort);
        }
        
        if (params.toString()) {
            url += '?' + params.toString();
        }
        
        // Fetch gallery content
        fetch(url)
            .then(response => response.text())
            .then(html => {
                // Update gallery grid
                $('#galleryGrid').html(html);
                
                // Hide loading indicator
                $('#loadingIndicator').hide();
                
                // Refresh Lightbox bindings for the new content
                lightbox.reload();
                
                // Update URL without refreshing the page
                const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                window.history.pushState({}, '', newUrl);
                
                // Update reset button visibility
                if (selectedDestination || selectedResort) {
                    if ($('#resetFilters').length === 0) {
                        $('.filter-actions').append('<a href="javascript:void(0)" class="reset-btn" id="resetFilters">Reset</a>');
                        $('#resetFilters').click(resetFilters);
                    }
                } else {
                    $('#resetFilters').remove();
                }
            })
            .catch(error => {
                console.error('Error fetching gallery:', error);
                $('#loadingIndicator').hide();
            });
    }
    
    // Function to reset all filters
    function resetFilters() {
        document.getElementById('destination').value = '';
        document.getElementById('resort').value = '';
        
        // Update resort options
        updateResortOptions();
    }
    
    // Set up event listeners
    $('#destination').on('change', updateResortOptions);
    $('#resort').on('change', updateGallery);
    
    // Set up reset button if filters are active
    $('#resetFilters').on('click', resetFilters);
});
</script>

<?php require 'kfooter.php'; ?> 