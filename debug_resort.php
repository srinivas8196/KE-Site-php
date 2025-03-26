<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include_once 'db.php';

echo "<h1>Resort Data Debug</h1>";

// Function to get current URL slug
function getCurrentSlug() {
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
    
    return $lastSegment;
}

// Get the current slug from URL
$currentSlug = isset($_GET['slug']) ? $_GET['slug'] : 'karma-royal-palms';
echo "<p>Testing with slug: <strong>{$currentSlug}</strong></p>";

// Direct DB query for resort
try {
    $sql = "SELECT r.*, d.destination_name, d.destination_banner 
            FROM resorts r 
            JOIN destinations d ON r.destination_id = d.id 
            WHERE r.resort_slug = :slug";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':slug', $currentSlug, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $resort = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<h2>Resort Found:</h2>";
        echo "<pre>";
        print_r($resort);
        echo "</pre>";
        
        // Get amenities
        $resortId = $resort['id'];
        echo "<h2>Amenities:</h2>";
        
        $amenSql = "SELECT a.* 
                    FROM amenities a 
                    JOIN resort_amenities ra ON a.id = ra.amenity_id 
                    WHERE ra.resort_id = :resort_id";
        
        $amenStmt = $pdo->prepare($amenSql);
        $amenStmt->bindParam(':resort_id', $resortId, PDO::PARAM_INT);
        $amenStmt->execute();
        
        $amenities = $amenStmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($amenities);
        echo "</pre>";
        
        // Get images
        echo "<h2>Images:</h2>";
        $imgSql = "SELECT * FROM resort_images WHERE resort_id = :resort_id";
        
        $imgStmt = $pdo->prepare($imgSql);
        $imgStmt->bindParam(':resort_id', $resortId, PDO::PARAM_INT);
        $imgStmt->execute();
        
        $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($images);
        echo "</pre>";
        
    } else {
        echo "<p style='color: red;'>Resort not found in database</p>";
        
        // Show table structure
        echo "<h2>Resorts Table Structure:</h2>";
        $structStmt = $pdo->query("DESCRIBE resorts");
        $structure = $structStmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($structure);
        echo "</pre>";
        
        // Show available resorts
        echo "<h2>Available Resorts:</h2>";
        $availStmt = $pdo->query("SELECT id, resort_name, resort_slug FROM resorts LIMIT 10");
        $available = $availStmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($available);
        echo "</pre>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

// Show DB connection info (without password)
echo "<h2>Database Connection Info:</h2>";
echo "Host: " . (isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'Not defined') . "<br>";
echo "Name: " . (isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : 'Not defined') . "<br>";
echo "User: " . (isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : 'Not defined') . "<br>";
?> 