<?php
/**
 * Page not found handler
 * 
 * Include this file at the top of commonly accessed files to handle 404 redirects
 * This serves as a PHP-based fallback in case .htaccess is not supported
 */

function handle_404_check() {
    // Get the requested URI
    $requested_uri = $_SERVER['REQUEST_URI'];
    $requested_file = basename($requested_uri);
    
    // Check if this is potentially a resort page (ends with .php)
    if (preg_match('/\.php$/', $requested_file)) {
        // Skip common system pages
        $system_pages = [
            'index.php', 'login.php', 'register.php', 'resort_list.php', 
            'save_resort.php', 'delete_resort.php', '404.php', '500.php',
            'kheader.php', 'kfooter.php', 'admin.php', 'auth_helper.php',
            'db.php', 'page_not_found_handler.php'
        ];
        
        if (!in_array($requested_file, $system_pages)) {
            // This is a potential resort page, check if it exists in the database
            global $pdo;
            
            // First ensure we have a database connection
            if (!isset($pdo) || $pdo === null) {
                // Try to include db.php if not already included
                try {
                    $pdo = include_once 'db.php';
                } catch (Exception $e) {
                    error_log("Error connecting to database in 404 handler: " . $e->getMessage());
                    // Still redirect to 404 if we can't check the database
                    header("HTTP/1.0 404 Not Found");
                    
                    // Get the site root directory for proper redirection
                    $site_root = dirname($_SERVER['SCRIPT_NAME']);
                    if ($site_root == '/') $site_root = '';
                    
                    header("Location: {$site_root}/404.php");
                    exit();
                }
            }
            
            if ($pdo) {
                // Check if this resort exists and is active
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM resorts WHERE file_path = ? AND is_active = 1");
                $stmt->execute([$requested_file]);
                $exists = $stmt->fetchColumn() > 0;
                
                if (!$exists && $requested_file != '404.php') {
                    // Resort not found or inactive, redirect to 404
                    header("HTTP/1.0 404 Not Found");
                    
                    // Get the site root directory for proper redirection
                    $site_root = dirname($_SERVER['SCRIPT_NAME']);
                    if ($site_root == '/') $site_root = '';
                    
                    // For debugging
                    error_log("404 Handler: Page not found - {$requested_file}, redirecting to {$site_root}/404.php");
                    
                    header("Location: {$site_root}/404.php");
                    exit();
                }
            }
        }
    }
}

// Execute the check
handle_404_check(); 