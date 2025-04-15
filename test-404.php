<?php
/**
 * Test script for 404 handling
 * 
 * This page intentionally tries to load a non-existent resort page
 * to test if the 404 redirection system is working properly.
 */

// Get the site root directory for proper redirection
$site_root = dirname($_SERVER['SCRIPT_NAME']);
if ($site_root == '/') $site_root = '';

// URL of a non-existent resort
$test_url = "{$site_root}/non-existent-resort.php";

// Log the test
error_log("404 Test: Attempting to access {$test_url}");

// Output a link to test manually
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>404 Handling Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-box { 
            padding: 15px; 
            border: 1px solid #ddd; 
            margin: 20px 0; 
            background: #f9f9f9;
        }
        h1 { color: #333; }
        button { 
            background: #4CAF50; 
            border: none; 
            color: white; 
            padding: 10px 15px; 
            cursor: pointer;
            margin: 5px;
        }
        .path-info {
            background: #f0f0f0;
            padding: 10px;
            margin: 10px 0;
            border-left: 3px solid #ccc;
        }
    </style>
</head>
<body>
    <h1>404 Handler Test Page</h1>
    
    <div class="path-info">
        <p><strong>Site Root:</strong> {$site_root}</p>
        <p><strong>Script Name:</strong> {$_SERVER['SCRIPT_NAME']}</p>
        <p><strong>Request URI:</strong> {$_SERVER['REQUEST_URI']}</p>
    </div>
    
    <div class="test-box">
        <h2>Test 1: Access non-existent resort page</h2>
        <p>Click the button below to test if the 404 handler redirects you to the 404 page:</p>
        <button onclick="window.location.href='{$test_url}'">Test Non-Existent Page</button>
    </div>
    
    <div class="test-box">
        <h2>Test 2: Check if .htaccess is working</h2>
        <p>If .htaccess is working correctly, entering a URL path that doesn't exist should redirect to the 404 page.</p>
        <button onclick="window.location.href='{$site_root}/this-page-does-not-exist.php'">Test .htaccess Rules</button>
    </div>
    
    <div class="test-box">
        <h2>Test 3: Direct access to 404 page</h2>
        <p>This should show the 404 page without redirection:</p>
        <button onclick="window.location.href='{$site_root}/404.php'">View 404 Page</button>
    </div>
</body>
</html>
HTML; 