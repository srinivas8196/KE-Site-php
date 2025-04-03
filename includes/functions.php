<?php
/**
 * Common functions used across the blog system
 */

// Include database connection file
require_once __DIR__ . '/../db.php';

/**
 * Generate a URL-friendly slug from a string
 */
function generateSlug($string) {
    // Convert to lowercase
    $string = strtolower($string);
    
    // Replace spaces with hyphens
    $string = str_replace(' ', '-', $string);
    
    // Remove special characters
    $string = preg_replace('/[^a-z0-9-]/', '', $string);
    
    // Remove multiple consecutive hyphens
    $string = preg_replace('/-+/', '-', $string);
    
    // Trim hyphens from start and end
    $string = trim($string, '-');
    
    return $string;
}

/**
 * Sanitize user input
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Format date in a consistent way
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Truncate text to a specified length
 */
function truncateText($text, $length = 150, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Get the current URL
 */
function getCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    return $protocol . $domainName . $_SERVER['REQUEST_URI'];
}

/**
 * Check if a string is a valid email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get the first paragraph of content for excerpt
 */
function getFirstParagraph($content) {
    if (preg_match('/<p>(.*?)<\/p>/s', $content, $matches)) {
        return strip_tags($matches[1]);
    }
    return strip_tags($content);
}

/**
 * Format file size in human readable format
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Check if a file is an image
 */
function isImage($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    return in_array($file['type'], $allowed_types);
}

/**
 * Get client IP address
 */
function getClientIp() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

/**
 * Generate a random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

/**
 * Check if a string contains HTML
 */
function containsHtml($string) {
    return $string !== strip_tags($string);
}

/**
 * Get the number of words in a string
 */
function getWordCount($string) {
    return str_word_count(strip_tags($string));
}

/**
 * Get reading time in minutes
 */
function getReadingTime($content) {
    $words = getWordCount($content);
    $minutes = ceil($words / 200); // Assuming average reading speed of 200 words per minute
    return max(1, $minutes);
}

/**
 * Verify reCAPTCHA v3 token
 * @param string $token The reCAPTCHA token to verify
 * @param string $action The expected action name
 * @return array ['success' => bool, 'score' => float|null, 'error' => string|null]
 */
function verifyRecaptchaV3($token, $action) {
    $result = [
        'success' => false,
        'score' => null,
        'error' => null
    ];

    try {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => RECAPTCHA_V3_SECRET_KEY,
            'response' => $token
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception('Failed to verify reCAPTCHA');
        }

        $responseData = json_decode($response, true);

        if ($responseData === null) {
            throw new Exception('Invalid reCAPTCHA response');
        }

        // Verify the response
        if ($responseData['success'] === true) {
            // Verify action matches
            if ($responseData['action'] !== $action) {
                $result['error'] = 'Invalid action';
                return $result;
            }

            // Check score
            $score = $responseData['score'] ?? 0;
            $result['score'] = $score;
            
            if ($score >= RECAPTCHA_V3_SCORE_THRESHOLD) {
                $result['success'] = true;
            } else {
                $result['error'] = 'Score too low';
            }
        } else {
            $result['error'] = 'Verification failed: ' . implode(', ', $responseData['error-codes'] ?? ['unknown error']);
        }
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
    }

    return $result;
} 