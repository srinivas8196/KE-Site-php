<?php
// This file serves as a bridge between Supabase and the application
// It loads the Supabase client and provides helper functions

require __DIR__ . '/vendor/autoload.php';

use Supabase\CreateClient;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize Supabase client
$supabaseUrl = 'https://jqevncrywqbqptxblztu.supabase.co';
$supabaseKey = $_ENV['SUPABASE_API_KEY']; // Add your API key to the .env file
$supabase = CreateClient::create($supabaseUrl, $supabaseKey);

/**
 * Universal database compatibility layer
 *
 * @param string $operation Operation type (select, insert, update, delete)
 * @param string $table Table name
 * @param array $params Operation parameters
 * @return mixed Operation result
 */
function db($operation, $table, $params = []) {
    global $supabase;

    switch ($operation) {
        case 'select':
            return $supabase->from($table)->select($params['columns'] ?? '*')->eq($params['key'], $params['value'])->execute();
        case 'insert':
            return $supabase->from($table)->insert($params['data'])->execute();
        case 'update':
            return $supabase->from($table)->update($params['data'])->eq($params['key'], $params['value'])->execute();
        case 'delete':
            return $supabase->from($table)->delete()->eq($params['key'], $params['value'])->execute();
        default:
            throw new Exception("Unknown Supabase operation: $operation");
    }
}

/**
 * Get a database ID field
 *
 * @param array $record Database record
 * @return mixed ID value
 */
function db_get_id($record) {
    return $record['id'] ?? null;
}

/**
 * Create a filter to find a record by ID
 *
 * @param mixed $id ID value
 * @return array Filter array
 */
function db_id_filter($id) {
    return ['key' => 'id', 'value' => $id];
}
?>