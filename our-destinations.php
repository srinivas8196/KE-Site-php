<?php
require 'db.php'; // Include the database connection
include 'kheader.php'; // Include the site header

// Fetch all destinations WITH active resort counts
try {
    $stmt = $pdo->query("
        SELECT
            d.id,
            d.destination_name,
            d.banner_image,
            COUNT(r.id) as resort_count
        FROM
            destinations d
        LEFT JOIN
            resorts r ON d.id = r.destination_id AND r.is_active = 1
        GROUP BY
            d.id, d.destination_name, d.banner_image
        ORDER BY
            d.destination_name ASC
    ");
    $destinations = $stmt->fetchAll();
} catch (PDOException $e) {
    $destinations = [];
    error_log("Database error fetching destinations with counts: " . $e->getMessage());
}

$destination_base_path = 'assets/destinations/'; // Base path for destination images
?>

<style>
    /* Main banner styling - Fixed overlap issue */
    .main-banner {
        height: 50vh;
        min-height: 400px;
        background-image: url('assets/images/destinations-banner.jpg'); /* CHANGE THIS PATH to your banner image */
        background-size: cover;
        background-position: center;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: white;
        padding-top: 60px; /* Added padding to prevent overlap with menu */
    }
    
    .main-banner::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to bottom, rgba(0,0,0,0.5), rgba(0,0,0,0.7));
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
        margin-bottom: 15px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
        color: #ffffff;
    }
    
    .banner-subtitle {
        font-size: 1.2rem;
        margin-bottom: 25px;
        text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
        color: #ffffff;
    }
    
    /* Modern styling for destination page */
    .destinations-container {
        padding: 30px 0 60px;
        background-color: #f8f9fa;
    }
    
    .search-container {
        max-width: 500px;
        margin: 0 auto 30px;
        position: relative;
    }
    
    .search-container .form-control {
        padding: 12px 20px;
        border-radius: 30px;
        border: 1px solid #ddd;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        font-size: 1rem;
    }
    
    .search-container .form-control:focus {
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        border-color: #aaa;
    }
    
    /* Clean Card Design with centered resort count */
    .destination-card {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 30px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        transition: all 0.4s ease;
        height: 350px;
        display: block;
        text-decoration: none;
        background-color: #fff;
    }
    
    .destination-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    }
    
    .destination-card .image-container {
        position: relative;
        height: 220px;
        overflow: hidden;
    }
    
    .destination-card .card-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s ease;
    }
    
    .destination-card:hover .card-image {
        transform: scale(1.08);
    }
    
    .destination-card .card-content {
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center; /* Center content horizontally */
        text-align: center; /* Center text */
        height: 130px;
    }
    
    .destination-card .card-title {
        font-size: 1.6rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 15px;
        line-height: 1.2;
    }
    
    .destination-card .resort-count {
        font-size: 1rem;
        color: #7f8c8d;
        font-weight: 500;
        display: inline-block;
        padding: 5px 15px;
        background-color: #f8f9fa;
        border-radius: 20px;
    }
    
    /* No results message */
    .no-results {
        text-align: center;
        padding: 40px 0;
        color: #666;
    }
    
    /* Responsive adjustments */
    @media (max-width: 767px) {
        .banner-title {
            font-size: 2.5rem;
        }
        
        .destination-card {
            height: 320px;
        }
        
        .destination-card .image-container {
            height: 200px;
        }
        
        .destination-card .card-title {
            font-size: 1.4rem;
        }
    }
</style>

<!-- Main Banner -->
<div class="main-banner">
    <div class="banner-content">
        <h1 class="banner-title">Discover Amazing Destinations</h1>
        <p class="banner-subtitle">Explore our collection of handpicked destinations around the world, each offering unique experiences and unforgettable memories.</p>
    </div>
</div>

<div class="destinations-container">
    <div class="container">
        <!-- Search Bar -->
        <div class="search-container">
            <input type="text" id="destinationSearch" class="form-control" placeholder="Search destinations...">
        </div>
        
        <!-- Destination Grid -->
        <div class="row" id="destinationGrid">
            <?php if (!empty($destinations)): ?>
                <?php foreach ($destinations as $destination): ?>
                    <?php
                        // Generate slug
                        $destination_slug = strtolower(str_replace(' ', '-', $destination['destination_name'] ?? ''));
                        $destination_slug = preg_replace('/[^a-z0-9\-]/', '', $destination_slug);

                        // Construct image path
                        $image_filename = $destination['banner_image'] ?? 'default-banner.jpg';
                        $image_path = $destination_base_path . $destination_slug . '/' . $image_filename;

                        // Check image existence
                        $final_image_path = $destination_base_path . 'default-banner.jpg';
                        if (!empty($destination['banner_image']) && file_exists($image_path)) {
                            $final_image_path = $image_path;
                        }

                        // Format resort count text
                        $resort_count_text = ($destination['resort_count'] == 1) ? '1 Resort Available' : ($destination['resort_count'] . ' Resorts Available');
                        if ($destination['resort_count'] == 0) {
                            $resort_count_text = 'No Resorts Available';
                        }
                    ?>
                    <div class="col-md-4 destination-item" data-name="<?php echo strtolower(htmlspecialchars($destination['destination_name'])); ?>">
                        <a href="destination-resorts.php?dest_id=<?php echo htmlspecialchars($destination['id']); ?>" class="destination-card">
                            <div class="image-container">
                                <img src="<?php echo htmlspecialchars($final_image_path); ?>" alt="<?php echo htmlspecialchars($destination['destination_name']); ?>" class="card-image">
                            </div>
                            <div class="card-content">
                                <h3 class="card-title"><?php echo htmlspecialchars($destination['destination_name']); ?></h3>
                                <p class="resort-count"><?php echo $resort_count_text; ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="no-results">
                        <h3>No destinations found</h3>
                        <p>Please check back later for new destinations.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Search functionality
    const searchInput = document.getElementById('destinationSearch');
    const destinationGrid = document.getElementById('destinationGrid');
    const destinationItems = destinationGrid.querySelectorAll('.destination-item');

    function filterDestinations() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        let resultsFound = false;

        destinationItems.forEach(item => {
            const nameMatch = item.getAttribute('data-name').includes(searchTerm);

            if (nameMatch) {
                item.style.display = '';
                resultsFound = true;
            } else {
                item.style.display = 'none';
            }
        });

        // Optional: Show a "no results" message if no matches
        const existingNoResults = document.querySelector('.search-no-results');
        if (!resultsFound && searchTerm !== '') {
            if (!existingNoResults) {
                const noResultsDiv = document.createElement('div');
                noResultsDiv.className = 'col-12 search-no-results';
                noResultsDiv.innerHTML = '<div class="no-results"><h3>No matching destinations</h3><p>Try a different search term.</p></div>';
                destinationGrid.appendChild(noResultsDiv);
            }
        } else if (existingNoResults) {
            existingNoResults.remove();
        }
    }

    // Event Listener
    searchInput.addEventListener('keyup', filterDestinations);
</script>

<?php
include 'kfooter.php'; // Include the site footer
?>