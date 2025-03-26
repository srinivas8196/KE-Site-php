<?php
require_once __DIR__ . '/vendor/autoload.php';
use Models\Resort;
use Database\SupabaseConnection;

function generateResortPage($resortId) {
    try {
        // Get resort data
        $resort = Resort::find($resortId);
        if (!$resort) {
            throw new Exception("Resort not found");
        }

        // Get Supabase instance for file URLs
        $supabase = SupabaseConnection::getClient();

        // Create page filename from resort slug
        $filename = $resort['resort_slug'] . '.php';
        
        // Start output buffering
        ob_start();
        
        // Include the template
        include 'templates/resort_template.php';
        
        // Get the contents
        $pageContent = ob_get_clean();
        
        // Write to file
        $filepath = __DIR__ . '/' . $filename;
        if (file_put_contents($filepath, $pageContent) === false) {
            throw new Exception("Failed to write resort page");
        }

        return $filename;

    } catch (Exception $e) {
        error_log("Error generating resort page: " . $e->getMessage());
        return false;
    }
}
