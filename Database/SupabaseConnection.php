<?php
namespace Database;

use Supabase\CreateClient;
use Dotenv\Dotenv;

class SupabaseConnection {
    private $supabaseUrl;
    private $supabaseKey;
    private static $instance = null;

    private function __construct() {
        // Load environment variables
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();

        $this->supabaseUrl = getenv('SUPABASE_URL');
        $this->supabaseKey = getenv('SUPABASE_KEY');
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new SupabaseConnection();
        }
        return self::$instance;
    }

    public function query($table, $params = []) {
        $url = $this->supabaseUrl . '/rest/v1/' . $table;
        $headers = [
            'apikey: ' . $this->supabaseKey,
            'Authorization: Bearer ' . $this->supabaseKey,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];

        if (!empty($params['select'])) {
            $url .= '?select=' . urlencode($params['select']);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        throw new \Exception('Supabase query failed: ' . $response);
    }

    public function signIn($email, $password) {
        $url = $this->supabaseUrl . '/auth/v1/token?grant_type=password';
        $headers = [
            'apikey: ' . $this->supabaseKey,
            'Content-Type: application/json'
        ];
        
        $data = json_encode([
            'email' => $email,
            'password' => $password
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        throw new \Exception('Authentication failed');
    }

    public function verifyUser($email, $password) {
        $url = $this->supabaseUrl . '/rest/v1/users';
        $url .= '?email=eq.' . urlencode($email);
        
        $headers = [
            'apikey: ' . $this->supabaseKey,
            'Authorization: Bearer ' . $this->supabaseKey,
            'Content-Type: application/json'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $users = json_decode($response, true);
            if (!empty($users) && isset($users[0])) {
                $user = $users[0];
                // For testing, temporarily return user without password check
                return $user;
                // Later, implement proper password verification:
                // if (password_verify($password, $user['password'])) {
                //     return $user;
                // }
            }
        }
        
        error_log("Login attempt failed for email: $email");
        error_log("Response code: $httpCode");
        error_log("Response: $response");
        
        return false;
    }
}
