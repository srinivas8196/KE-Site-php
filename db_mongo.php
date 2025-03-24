<?php
require __DIR__ . '/vendor/autoload.php';  // Ensure Composer autoload is loaded

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Check if MongoDB environment variables exist, if not, provide defaults for Atlas connection
if (!isset($_ENV['MONGODB_URI'])) {
    // This will need to be updated with your actual MongoDB Atlas connection string
    die("MongoDB connection string not found in .env file. Please add MONGODB_URI");
}

try {
    // Use MongoDB\Client from the MongoDB library (which we will install later)
    // For now, this is a placeholder as we don't have the library installed yet
    // $client = new MongoDB\Client($_ENV['MONGODB_URI']);
    // $db = $client->selectDatabase($_ENV['MONGODB_DB']);
    
    // Since we can't use the MongoDB library directly without the extension,
    // we'll create a custom MongoDB class to handle the database operations
    class MongoDB_Connection {
        private $db_name;
        private $collections = [];
        
        public function __construct($db_name) {
            $this->db_name = $db_name;
        }
        
        // Get a collection (similar to a table in MySQL)
        public function collection($name) {
            if (!isset($this->collections[$name])) {
                $this->collections[$name] = new MongoDB_Collection($name);
            }
            return $this->collections[$name];
        }
    }
    
    class MongoDB_Collection {
        private $name;
        private $data = [];
        private static $id_counter = 1;
        
        public function __construct($name) {
            $this->name = $name;
            
            // Load data from file if exists (temporary solution until MongoDB is installed)
            $file_path = "mongo_data/{$name}.json";
            if (file_exists($file_path)) {
                $this->data = json_decode(file_get_contents($file_path), true) ?: [];
                // Update ID counter to be higher than any existing ID
                foreach ($this->data as $doc) {
                    if (isset($doc['_id']) && $doc['_id'] >= self::$id_counter) {
                        self::$id_counter = $doc['_id'] + 1;
                    }
                }
            }
            
            // Ensure data directory exists
            if (!file_exists('mongo_data')) {
                mkdir('mongo_data', 0777, true);
            }
        }
        
        // Save data to file (temporary solution)
        private function save_data() {
            file_put_contents("mongo_data/{$this->name}.json", json_encode($this->data, JSON_PRETTY_PRINT));
        }
        
        // Insert a document
        public function insertOne($document) {
            $document['_id'] = self::$id_counter++;
            $this->data[] = $document;
            $this->save_data();
            return ['insertedId' => $document['_id']];
        }
        
        // Find documents
        public function find($filter = []) {
            $result = [];
            foreach ($this->data as $doc) {
                $match = true;
                foreach ($filter as $key => $value) {
                    if (!isset($doc[$key]) || $doc[$key] != $value) {
                        $match = false;
                        break;
                    }
                }
                if ($match) {
                    $result[] = $doc;
                }
            }
            return $result;
        }
        
        // Find one document
        public function findOne($filter = []) {
            $results = $this->find($filter);
            return !empty($results) ? $results[0] : null;
        }
        
        // Update a document
        public function updateOne($filter, $update) {
            foreach ($this->data as $key => $doc) {
                $match = true;
                foreach ($filter as $filter_key => $filter_value) {
                    if (!isset($doc[$filter_key]) || $doc[$filter_key] != $filter_value) {
                        $match = false;
                        break;
                    }
                }
                if ($match) {
                    // Handle $set operator
                    if (isset($update['$set'])) {
                        foreach ($update['$set'] as $set_key => $set_value) {
                            $this->data[$key][$set_key] = $set_value;
                        }
                    }
                    // Handle direct field updates
                    else {
                        foreach ($update as $update_key => $update_value) {
                            $this->data[$key][$update_key] = $update_value;
                        }
                    }
                    $this->save_data();
                    return ['matchedCount' => 1, 'modifiedCount' => 1];
                }
            }
            return ['matchedCount' => 0, 'modifiedCount' => 0];
        }
        
        // Delete a document
        public function deleteOne($filter) {
            $original_count = count($this->data);
            $new_data = [];
            foreach ($this->data as $doc) {
                $match = true;
                foreach ($filter as $key => $value) {
                    if (!isset($doc[$key]) || $doc[$key] != $value) {
                        $match = false;
                        break;
                    }
                }
                if (!$match) {
                    $new_data[] = $doc;
                }
            }
            $this->data = $new_data;
            $this->save_data();
            return ['deletedCount' => $original_count - count($this->data)];
        }
    }
    
    // Create a MongoDB connection
    $mongo = new MongoDB_Connection($_ENV['MONGODB_DB'] ?? 'karmasite');
    
} catch (Exception $e) {
    die("MongoDB connection failed: " . $e->getMessage());
}
?>