<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include_once 'db.php';

// Function to get resort information by slug
function getResortInfo($slug) {
    global $pdo;
    
    try {
        // Prepare SQL to fetch resort details
        $sql = "SELECT r.*, d.destination_name, d.destination_banner 
                FROM resorts r 
                JOIN destinations d ON r.destination_id = d.id 
                WHERE r.resort_slug = :slug";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            return null;
        }
    } catch (PDOException $e) {
        error_log("Database query failed in getResortInfo: " . $e->getMessage());
        return null;
    }
}

// Function to get resort amenities
function getResortAmenities($resortId) {
    global $pdo;
    
    try {
        $sql = "SELECT a.* 
                FROM amenities a 
                JOIN resort_amenities ra ON a.id = ra.amenity_id 
                WHERE ra.resort_id = :resort_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':resort_id', $resortId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database query failed in getResortAmenities: " . $e->getMessage());
        return [];
    }
}

// Function to get resort images
function getResortImages($resortId) {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM resort_images WHERE resort_id = :resort_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':resort_id', $resortId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database query failed in getResortImages: " . $e->getMessage());
        return [];
    }
}

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
$currentSlug = getCurrentSlug();

// Get resort information
$resortInfo = getResortInfo($currentSlug);

// If resort found, get additional data
if ($resortInfo) {
    $resortId = $resortInfo['id'];
    $resortAmenities = getResortAmenities($resortId);
    $resortImages = getResortImages($resortId);
} else {
    // Resort not found
    $resortInfo = null;
    $resortAmenities = [];
    $resortImages = [];
}

// Define variables to be used in views
$resortName = $resortInfo ? $resortInfo['resort_name'] : 'Resort Not Found';
$resortDescription = $resortInfo ? $resortInfo['resort_description'] : '';
$resortLocation = $resortInfo ? $resortInfo['resort_location'] : '';
$destinationName = $resortInfo ? $resortInfo['destination_name'] : '';
$destinationBanner = $resortInfo ? $resortInfo['destination_banner'] : '';
$resortBanner = $resortInfo ? $resortInfo['resort_banner'] : '';

// Return true if the function is called directly (not included)
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    echo "This is a helper file and should be included in other files.";
    exit;
}
?> 