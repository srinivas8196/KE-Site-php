<?php
$files = glob('*.php');
foreach ($files as $file) {
    if ($file === 'replace_db.php' || $file === 'db.php') continue;
    
    $content = file_get_contents($file);
    $content = str_replace("require 'db_mongo.php'", "require 'db.php'", $content);
    file_put_contents($file, $content);
    echo "Updated $file\n";
}
echo "Done!\n"; 