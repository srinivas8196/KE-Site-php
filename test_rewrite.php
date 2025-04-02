<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>URL Rewrite Test</h1>";

echo "<h2>Request Information</h2>";
echo "<pre>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "QUERY_STRING: " . $_SERVER['QUERY_STRING'] . "\n";
echo "</pre>";

echo "<h2>GET Parameters</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

// Simulate the URL parsing from blog-details.php
echo "<h2>URL Parsing Test</h2>";
$request_uri = $_SERVER['REQUEST_URI'];
$slug = '';

echo "Testing regex pattern for: $request_uri<br>";

if (preg_match('/^\/blogs\/([^\/\?]+)/', $request_uri, $matches)) {
    echo "Pattern 1 matched!<br>";
    $slug = $matches[1];
    echo "Extracted slug: $slug<br>";
} elseif (preg_match('/^\/KE-Site-php\/blogs\/([^\/\?]+)/', $request_uri, $matches)) {
    echo "Pattern 2 matched!<br>";
    $slug = $matches[1];
    echo "Extracted slug: $slug<br>";
} elseif (isset($_GET['slug'])) {
    echo "Using GET parameter<br>";
    $slug = $_GET['slug'];
    echo "Extracted slug: $slug<br>";
} else {
    echo "No pattern matched and no GET parameter found<br>";
}

// Test server variables that might affect rewriting
echo "<h2>Server Configuration</h2>";
echo "<pre>";
echo "mod_rewrite enabled: " . (in_array('mod_rewrite', apache_get_modules()) ? 'Yes' : 'No') . "\n";
echo "</pre>";

?> 