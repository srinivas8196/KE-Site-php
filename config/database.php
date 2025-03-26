<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

return [
    'supabase' => [
        'url' => $_ENV['SUPABASE_URL'],
        'key' => $_ENV['SUPABASE_KEY']
    ]
];
