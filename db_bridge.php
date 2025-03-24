<?php
// This file serves as a bridge between MySQL and MongoDB implementations
// It loads either database connection based on configuration

// Load environment variables
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Determine which database to use (default to MySQL if not specified)
define('USE_MONGODB', isset($_ENV['USE_MONGODB']) && $_ENV['USE_MONGODB'] === 'true');

// Include the appropriate database connection file
if (USE_MONGODB) {
    require_once 'db_mongo.php';
    
    // Create compatibility layer for MySQL functions/variables
    if (!isset($pdo)) {
        // Create a PDO-like placeholder for compatibility
        $pdo = new stdClass();
        $pdo->prepare = function($query) {
            return null; // This won't be used when MongoDB is active
        };
        $pdo->lastInsertId = function() {
            return null; // This won't be used when MongoDB is active
        };
        $pdo->query = function($query) {
            return null; // This won't be used when MongoDB is active
        };
    }
} else {
    require_once 'db.php';
    
    // Create compatibility layer for MongoDB functions/variables
    if (!isset($mongo)) {
        // Create a class with MongoDB-like functions
        $mongo = new stdClass();
        $mongo->collection = function($name) {
            return null; // This won't be used when MySQL is active
        };
    }
}

/**
 * MongoDB-compatible find operation
 *
 * @param string $collection The collection name
 * @param array $filter The filter criteria
 * @return array Results
 */
function mongo_find($collection, $filter = []) {
    global $mongo;
    return $mongo->collection($collection)->find($filter);
}

/**
 * MongoDB-compatible findOne operation
 *
 * @param string $collection The collection name
 * @param array $filter The filter criteria
 * @return array|null Result or null
 */
function mongo_findOne($collection, $filter = []) {
    global $mongo;
    return $mongo->collection($collection)->findOne($filter);
}

/**
 * MongoDB-compatible insertOne operation
 *
 * @param string $collection The collection name
 * @param array $document The document to insert
 * @return mixed Insert result
 */
function mongo_insertOne($collection, $document) {
    global $mongo;
    return $mongo->collection($collection)->insertOne($document);
}

/**
 * MongoDB-compatible updateOne operation
 *
 * @param string $collection The collection name
 * @param array $filter The filter criteria
 * @param array $update The update operations
 * @return mixed Update result
 */
function mongo_updateOne($collection, $filter, $update) {
    global $mongo;
    return $mongo->collection($collection)->updateOne($filter, $update);
}

/**
 * MongoDB-compatible deleteOne operation
 *
 * @param string $collection The collection name
 * @param array $filter The filter criteria
 * @return mixed Delete result
 */
function mongo_deleteOne($collection, $filter) {
    global $mongo;
    return $mongo->collection($collection)->deleteOne($filter);
}

/**
 * MySQL-compatible query execution
 *
 * @param string $query SQL query
 * @param array $params Parameters
 * @param bool $fetchAll Whether to fetch all results
 * @return mixed Query result
 */
function mysql_query($query, $params = [], $fetchAll = false) {
    global $pdo;
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    if (stripos($query, 'SELECT') === 0) {
        return $fetchAll ? $stmt->fetchAll() : $stmt->fetch();
    }
    return $stmt;
}

/**
 * Universal database compatibility layer
 * 
 * @param string $operation Operation type (find, findOne, insert, update, delete)
 * @param string $collection Collection/table name
 * @param array $params Operation parameters
 * @return mixed Operation result
 */
function db($operation, $collection, $params = []) {
    global $pdo, $mongo; // Add global declarations to ensure variables are accessible
    
    if (USE_MONGODB) {
        switch ($operation) {
            case 'find':
                return mongo_find($collection, $params);
            case 'findOne':
                return mongo_findOne($collection, $params);
            case 'insert':
                return mongo_insertOne($collection, $params);
            case 'update':
                return mongo_updateOne($collection, $params[0], $params[1]);
            case 'delete':
                return mongo_deleteOne($collection, $params);
            default:
                throw new Exception("Unknown MongoDB operation: $operation");
        }
    } else {
        switch ($operation) {
            case 'find':
                $where = '';
                $sqlParams = [];
                if (!empty($params)) {
                    $clauses = [];
                    foreach ($params as $key => $value) {
                        $clauses[] = "$key = ?";
                        $sqlParams[] = $value;
                    }
                    $where = ' WHERE ' . implode(' AND ', $clauses);
                }
                return mysql_query("SELECT * FROM $collection$where", $sqlParams, true);
            
            case 'findOne':
                $where = '';
                $sqlParams = [];
                if (!empty($params)) {
                    $clauses = [];
                    foreach ($params as $key => $value) {
                        $clauses[] = "$key = ?";
                        $sqlParams[] = $value;
                    }
                    $where = ' WHERE ' . implode(' AND ', $clauses);
                }
                return mysql_query("SELECT * FROM $collection$where LIMIT 1", $sqlParams);
            
            case 'insert':
                $keys = array_keys($params);
                $placeholders = array_fill(0, count($keys), '?');
                $query = "INSERT INTO $collection (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = mysql_query($query, array_values($params));
                return ['insertedId' => $pdo->lastInsertId()];
            
            case 'update':
                $filter = $params[0];
                $data = $params[1]['$set'] ?? $params[1];
                
                $set = [];
                $sqlParams = [];
                foreach ($data as $key => $value) {
                    $set[] = "$key = ?";
                    $sqlParams[] = $value;
                }
                
                $where = [];
                foreach ($filter as $key => $value) {
                    $where[] = "$key = ?";
                    $sqlParams[] = $value;
                }
                
                $query = "UPDATE $collection SET " . implode(', ', $set) . " WHERE " . implode(' AND ', $where);
                $stmt = mysql_query($query, $sqlParams);
                return ['modifiedCount' => $stmt->rowCount()];
            
            case 'delete':
                $where = [];
                $sqlParams = [];
                foreach ($params as $key => $value) {
                    $where[] = "$key = ?";
                    $sqlParams[] = $value;
                }
                
                $query = "DELETE FROM $collection WHERE " . implode(' AND ', $where);
                $stmt = mysql_query($query, $sqlParams);
                return ['deletedCount' => $stmt->rowCount()];
            
            default:
                throw new Exception("Unknown MySQL operation: $operation");
        }
    }
}

/**
 * Get a database ID field based on the current database type
 * 
 * @param array $record Database record
 * @return mixed ID value
 */
function db_get_id($record) {
    if (USE_MONGODB) {
        return $record['_id'] ?? null;
    } else {
        return $record['id'] ?? null;
    }
}

/**
 * Create a filter to find a record by ID
 * 
 * @param mixed $id ID value
 * @return array Filter array
 */
function db_id_filter($id) {
    if (USE_MONGODB) {
        return ['_id' => is_numeric($id) ? (int)$id : $id];
    } else {
        return ['id' => $id];
    }
}

// Add MongoDB URI setting to the environment if it's not already there
if (!isset($_ENV['USE_MONGODB'])) {
    $_ENV['USE_MONGODB'] = 'false';
}
if (!isset($_ENV['MONGODB_URI']) && isset($_ENV['MONGODB_HOST'])) {
    $_ENV['MONGODB_URI'] = "mongodb://{$_ENV['MONGODB_HOST']}:{$_ENV['MONGODB_PORT']}/{$_ENV['MONGODB_DB']}";
}
if (!isset($_ENV['MONGODB_DB']) && isset($_ENV['DB_NAME'])) {
    $_ENV['MONGODB_DB'] = $_ENV['DB_NAME'];
}
?>