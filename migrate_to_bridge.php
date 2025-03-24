<?php
/**
 * Database Bridge Migration Tool
 * 
 * This script scans all PHP files in the project directory and replaces
 * include/require statements for db.php with db_bridge.php
 */

// Directory to scan (current directory)
$directory = __DIR__;

// Counter for modified files
$modifiedCount = 0;

// Function to scan directories recursively
function scanDirectory($dir, &$modifiedCount) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        // Skip . and .. directories
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $path = $dir . '/' . $file;
        
        // If it's a directory, scan it recursively
        if (is_dir($path)) {
            // Skip vendor directory
            if (basename($path) === 'vendor') {
                continue;
            }
            scanDirectory($path, $modifiedCount);
        } 
        // If it's a PHP file, check and modify it
        elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            modifyFile($path, $modifiedCount);
        }
    }
}

// Function to modify a file
function modifyFile($filePath, &$modifiedCount) {
    $content = file_get_contents($filePath);
    
    // Look for include or require statements with db.php
    $pattern = '/(include|require|include_once|require_once)(\s+|\s*\(\s*)[\'"]db\.php[\'"]\s*\)?/';
    
    // Replace with db_bridge.php
    $replacement = '$1$2\'db_bridge.php\'';
    
    $newContent = preg_replace($pattern, $replacement, $content, -1, $count);
    
    // If changes were made, save the file
    if ($count > 0) {
        file_put_contents($filePath, $newContent);
        $modifiedCount++;
        echo "Updated: $filePath ($count occurrences)\n";
    }
}

echo "Starting migration to db_bridge.php...\n";
scanDirectory($directory, $modifiedCount);
echo "Migration completed. $modifiedCount files were modified.\n";

// Create backup of original db.php
if (file_exists(__DIR__ . '/db.php')) {
    echo "Creating backup of original db.php...\n";
    copy(__DIR__ . '/db.php', __DIR__ . '/db.php.bak');
    echo "Backup created as db.php.bak\n";
}

echo "\nMigration to database bridge completed successfully!\n";
echo "You can now switch between MySQL and MongoDB by changing the USE_MONGODB setting in your .env file.\n";
?>