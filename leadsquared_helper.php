<?php
/**
 * LeadSquared API Helper Functions
 * 
 * This file contains functions for interacting with the LeadSquared API
 * to create and update leads based on resort enquiries.
 */

/**
 * Create a new lead in LeadSquared with better error handling
 * 
 * @param array $data Lead data to create
 * @param string $accessKey LeadSquared access key
 * @param string $secretKey LeadSquared secret key
 * @param string $apiUrl LeadSquared API URL
 * @return array API response with status and message
 */
function createNewLead_Fixed($data, $accessKey, $secretKey, $apiUrl) {
    error_log("No existing lead found with phone " . $data['Phone'] . ". Creating new lead directly.");
    
    // Sanitize and clean input data to prevent JSON encoding issues
    array_walk_recursive($data, function(&$item) {
        if (is_string($item)) {
            // Convert to UTF-8 and ensure proper encoding
            $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
            
            // Remove control characters that can break JSON
            $item = preg_replace('/[\x00-\x1F\x7F]/', '', $item);
            
            // Trim whitespace
            $item = trim($item);
            
            // Handle special characters that might break JSON
            $item = str_replace(
                array('\\', '/', '"', "\n", "\r", "\t"),
                array('\\\\', '\\/', '\\"', '\\n', '\\r', '\\t'),
                $item
            );
        }
        
        // Convert null values to empty strings
        if ($item === null) {
            $item = '';
        }
        
        // Convert boolean values to integers
        if (is_bool($item)) {
            $item = $item ? 1 : 0;
        }
    });
    
    // Create a new lead
    $createUrl = $apiUrl . "/Lead.Create?accessKey=" . urlencode($accessKey) . "&secretKey=" . urlencode($secretKey);
    
    // Initialize cURL session for create
    $ch = curl_init($createUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    // Properly encode JSON data with error handling
    $jsonData = json_encode([$data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON encoding error: " . json_last_error_msg());
        
        // Handle problematic fields if JSON encoding fails
        $fallbackData = [];
        foreach ($data as $key => $value) {
            // Try to identify problematic fields
            if (is_string($value)) {
                // Aggressively sanitize strings
                $fallbackData[$key] = preg_replace('/[^\p{L}\p{N}\s\-\._]/u', '', $value);
            } else {
                $fallbackData[$key] = $value;
            }
        }
        
        // Try encoding again with sanitized data
        $jsonData = json_encode([$fallbackData], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // If still fails, use partial output
        if (json_last_error() !== JSON_ERROR_NONE) {
            $jsonData = json_encode([$fallbackData], JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
    }
    
    // Double-check the JSON is valid before sending
    if (json_decode($jsonData) === null && json_last_error() !== JSON_ERROR_NONE) {
        error_log("Final JSON check failed: " . json_last_error_msg() . ". Attempting final fallback method.");
        
        // Create a minimal data set with just required fields
        $minimalData = [
            'FirstName' => isset($data['FirstName']) ? substr($data['FirstName'], 0, 50) : 'Unknown',
            'LastName' => isset($data['LastName']) ? substr($data['LastName'], 0, 50) : 'Unknown',
            'EmailAddress' => isset($data['EmailAddress']) ? $data['EmailAddress'] : 'unknown@example.com',
            'Phone' => isset($data['Phone']) ? preg_replace('/[^0-9+]/', '', $data['Phone']) : '0000000000'
        ];
        
        $jsonData = json_encode([$minimalData], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    // DEBUG: Log the exact payload being sent
    error_log("LeadSquared request payload: " . $jsonData);
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_HEADER, 1); // Capture headers for debugging
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json; charset=utf-8', 
        'Accept: application/json'
    ]);

    // Execute cURL session for create
    $createResponse = curl_exec($ch);
    $createHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $createCurlError = curl_error($ch);
    
    // Extract headers and body
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $responseHeaders = substr($createResponse, 0, $headerSize);
    $responseBody = substr($createResponse, $headerSize);
    
    curl_close($ch);
    
    // Log full response information for debugging
    error_log("LeadSquared create HTTP code: " . $createHttpCode);
    error_log("LeadSquared response headers: " . str_replace("\r\n", " | ", $responseHeaders));
    error_log("LeadSquared raw create response: " . $responseBody);
    if (!empty($createCurlError)) {
        error_log("LeadSquared create cURL error: " . $createCurlError);
    }
    
    // Use responseBody instead of createResponse from here on
    $createResponse = $responseBody;
    
    // If HTTP code is 400, log detailed information to help diagnose
    if ($createHttpCode == 400) {
        error_log("HTTP 400 Bad Request detected. This typically means invalid data format or authentication issue.");
        
        // Log a cleaner version of what was sent
        $logData = $data;
        if (isset($logData['Phone'])) {
            $logData['Phone'] = substr($logData['Phone'], 0, 3) . '***' . substr($logData['Phone'], -2);
        }
        if (isset($logData['EmailAddress'])) {
            $logData['EmailAddress'] = substr($logData['EmailAddress'], 0, 3) . '***@***' . substr(strrchr($logData['EmailAddress'], '.'), 0);
        }
        error_log("Request data (sanitized): " . json_encode($logData));
        
        // Special handling for specific API errors
        $commonErrors = [
            'Invalid credentials' => function() {
                error_log("Authentication failed. Please check your LeadSquared access key and secret key.");
                return "Authentication failed. Please check your API credentials.";
            },
            'invalid json' => function() {
                error_log("The JSON data sent to LeadSquared was invalid. Check for special characters in the data.");
                return "Invalid JSON format in request data. Please check for special characters.";
            },
            'validation' => function() use ($data) {
                error_log("Field validation error. Required fields may be missing or invalid.");
                foreach (['FirstName', 'LastName', 'EmailAddress', 'Phone'] as $field) {
                    if (empty($data[$field])) {
                        error_log("Required field missing: $field");
                    }
                }
                return "Field validation error. Please check required fields.";
            }
        ];
        
        // Check response for common error patterns
        $detectedError = null;
        foreach ($commonErrors as $pattern => $handler) {
            if (stripos($createResponse, $pattern) !== false) {
                $detectedError = $handler();
                break;
            }
        }
        
        if ($detectedError) {
            return [
                'status' => 'error',
                'message' => $detectedError,
                'data' => [],
                'http_code' => $createHttpCode,
                'raw_response' => $createResponse
            ];
        }
        
        // Try to extract standard error message
        if (preg_match('/"Exception(?:Type|Message)"\s*:\s*"([^"]+)"/', $createResponse, $matches)) {
            $errorMessage = $matches[1] ?? 'Unknown API error';
            
            return [
                'status' => 'error',
                'message' => 'API rejected request: ' . $errorMessage,
                'data' => [],
                'http_code' => $createHttpCode,
                'raw_response' => $createResponse
            ];
        }
    }
    
    // If response is empty but HTTP code is successful, assume success
    if (empty($createResponse) && $createHttpCode >= 200 && $createHttpCode < 300) {
        error_log("Empty response with successful HTTP code - assuming success");
        return [
            'status' => 'success',
            'message' => 'Lead created successfully (empty response)',
            'data' => ['Status' => 'Success']
        ];
    }
    
    // Clean the response - this is a critical step for handling bad JSON
    $cleanedResponse = cleanJsonResponse($createResponse);
    
    // Try to parse the JSON response with multiple fallback methods
    $createResult = null;
    $jsonError = null;
    
    // First attempt - standard json_decode
    $createResult = @json_decode($cleanedResponse, true);
    $jsonError = json_last_error();
    
    // If first attempt fails, try with additional cleaning
    if ($jsonError !== JSON_ERROR_NONE) {
        error_log("First JSON decode attempt failed: " . json_last_error_msg());
        
        // Second attempt - try with trim and special character removal
        $trimmedResponse = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $cleanedResponse));
        $createResult = @json_decode($trimmedResponse, true);
        $jsonError = json_last_error();
        
        // If second attempt also fails
        if ($jsonError !== JSON_ERROR_NONE) {
            error_log("Second JSON decode attempt failed: " . json_last_error_msg());
            
            // Third attempt - try direct extraction of ID using regex
            if (preg_match('/"Id"\s*:\s*"([^"]+)"/', $createResponse, $matches)) {
                error_log("Extracted Lead ID using regex: " . $matches[1]);
                
                return [
                    'status' => 'success',
                    'message' => 'Lead created successfully (ID extracted manually)',
                    'data' => ['Status' => 'Success', 'Message' => ['Id' => $matches[1]]]
                ];
            }
            
            // Fourth attempt - try as simple string if LeadSquared returned non-JSON
            if ($createHttpCode >= 200 && $createHttpCode < 300) {
                // Try to extract any ID-like value from the response
                if (preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/i', $createResponse, $matches)) {
                    error_log("Found UUID-like string in response, treating as ID: " . $matches[1]);
                    return [
                        'status' => 'success',
                        'message' => 'Lead created successfully (ID pattern extracted)',
                        'data' => ['Status' => 'Success', 'Message' => ['Id' => $matches[1]]]
                    ];
                }
                
                // If no ID found but HTTP status is success, assume success anyway
                error_log("HTTP status indicates success despite JSON parsing failure. Creating synthetic success response.");
                return [
                    'status' => 'success',
                    'message' => 'Lead likely created successfully (HTTP: ' . $createHttpCode . ')',
                    'data' => ['Status' => 'Success', 'Message' => ['RawResponse' => substr($createResponse, 0, 100) . '...']]
                ];
            }
            
            // All parsing attempts failed
            return [
                'status' => 'error',
                'message' => 'Failed to parse LeadSquared create response: ' . json_last_error_msg() . ' (HTTP: ' . $createHttpCode . ')',
                'raw_response' => substr($createResponse, 0, 200)
            ];
        }
    }
    
    // At this point, we have successfully parsed the JSON
    error_log("Successfully parsed JSON response from LeadSquared");
    
    // Analyze the response structure to extract Lead ID and status
    if ($createHttpCode >= 200 && $createHttpCode < 300) {
        // Check various possible response structures
        $leadId = null;
        
        // 1. Standard format: array with Status
        if (is_array($createResult) && isset($createResult[0]['Status']) && $createResult[0]['Status'] == 'Success') {
            $leadId = $createResult[0]['Message']['Id'] ?? null;
            error_log("Found Lead ID in standard format: " . ($leadId ?? 'Not available'));
        } 
        // 2. Check for nested 'Message' with 'Id'
        else if (is_array($createResult) && isset($createResult[0]['Message']['Id'])) {
            $leadId = $createResult[0]['Message']['Id'];
            error_log("Found Lead ID in Message.Id: " . $leadId);
        }
        // 3. Check for direct 'Id' field
        else if (is_array($createResult) && isset($createResult[0]['Id'])) {
            $leadId = $createResult[0]['Id'];
            error_log("Found Lead ID in direct Id field: " . $leadId);
        }
        // 4. Check for Id in data array
        else if (isset($createResult['data']['Id'])) {
            $leadId = $createResult['data']['Id'];
            error_log("Found Lead ID in data.Id: " . $leadId);
        }
        // 5. Check for Id in Response object
        else if (isset($createResult['data']['Response']['Id'])) {
            $leadId = $createResult['data']['Response']['Id'];
            error_log("Found Lead ID in data.Response.Id: " . $leadId);
        }
        // 6. Check for Status/LeadId pattern
        else if (isset($createResult['data']['Status']) && isset($createResult['data']['LeadId'])) {
            $leadId = $createResult['data']['LeadId'];
            error_log("Found Lead ID in data.LeadId: " . $leadId);
        }
        // 7. Check first item in array
        else if (is_array($createResult) && isset($createResult['data'][0]['Id'])) {
            $leadId = $createResult['data'][0]['Id'];
            error_log("Found Lead ID in data[0].Id: " . $leadId);
        }
        
        // If we found a Lead ID, return success
        if ($leadId) {
            return [
                'status' => 'success',
                'message' => 'Lead created successfully',
                'data' => ['Status' => 'Success', 'Message' => ['Id' => $leadId]]
            ];
        }
        
        // If no Lead ID but HTTP code indicates success
        error_log("HTTP success but no Lead ID found in response. Full response: " . json_encode($createResult));
        return [
            'status' => 'success',
            'message' => 'Lead created successfully (non-standard response)',
            'data' => ['Status' => 'Success', 'Response' => $createResult]
        ];
    } else {
        // Try to extract a meaningful error message
        $errorMsg = extractErrorMessage($createResult);
        
        // For HTTP 400 errors, also try to extract information from the raw response
        if ($createHttpCode == 400) {
            error_log("HTTP 400 Bad Request error in LeadSquared API call");
            
            // Look for common error patterns in the raw response
            if (!empty($createResponse)) {
                $rawErrorMsg = extractErrorMessage($createResponse);
                if ($rawErrorMsg != $errorMsg) {
                    $errorMsg .= " (Raw error: $rawErrorMsg)";
                }
                
                // Try to diagnose specific API errors
                if (strpos($createResponse, 'validation') !== false || strpos($createResponse, 'required') !== false) {
                    error_log("Validation error detected in LeadSquared API response");
                    // Log the data that was sent
                    error_log("Data sent to LeadSquared: " . json_encode($data));
                }
                
                // Handle authentication errors
                if (strpos($createResponse, 'AccessKey') !== false || strpos($createResponse, 'SecretKey') !== false) {
                    error_log("Authentication error detected in LeadSquared API response");
                    error_log("Check your API credentials: AccessKey=" . substr($accessKey, 0, 5) . "..., SecretKey=" . substr($secretKey, 0, 5) . "...");
                }
            }
        }
        
        return [
            'status' => 'error',
            'message' => 'Failed to create lead: ' . $errorMsg,
            'data' => $createResult,
            'http_code' => $createHttpCode
        ];
    }
}

/**
 * Clean a JSON response to try to make it valid
 */
function cleanJsonResponse($response) {
    if (empty($response)) {
        return '';
    }
    
    // Remove UTF-8 BOM if present
    $response = preg_replace('/^\xEF\xBB\xBF/', '', $response);
    
    // Remove control characters
    $response = preg_replace('/[\x00-\x1F\x7F]/', '', $response);
    
    // Try to fix common JSON issues
    
    // Remove any HTML content that might be in the response
    $response = preg_replace('/<[^>]+>/', '', $response);
    
    // Try to extract just JSON part if it's wrapped in other content
    if (preg_match('/(\{.+\}|\[.+\])/', $response, $matches)) {
        // Only use the match if it's significantly smaller than the original (suggesting we're extracting from mixed content)
        if (strlen($matches[0]) < strlen($response) * 0.9) {
            $response = $matches[0];
        }
    }
    
    // Fix missing quotes around keys
    $response = preg_replace('/(\{|\,)\s*([a-zA-Z0-9_]+)\s*:/', '$1"$2":', $response);
    
    // Fix single quotes in place of double quotes
    $response = str_replace("'", '"', $response);
    
    // Fix trailing commas in arrays and objects
    $response = preg_replace('/,\s*(\}|\])/', '$1', $response);
    
    // Add missing quotes to strings containing special characters
    $response = preg_replace('/"?([^"]*?[^\w\s,][^"]*?)"?\s*:/', '"$1":', $response);
    
    // Fix unescaped backslashes in strings
    $pattern = '/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/';
    preg_match_all($pattern, $response, $matches);
    if (!empty($matches[0])) {
        foreach ($matches[0] as $index => $match) {
            $fixed = '"' . str_replace('\\', '\\\\', $matches[1][$index]) . '"';
            $response = str_replace($match, $fixed, $response);
        }
    }
    
    // Fix unescaped quotes in strings
    $pattern = '/"([^"\\\\]*)"/';
    preg_match_all($pattern, $response, $matches);
    if (!empty($matches[0])) {
        foreach ($matches[0] as $index => $match) {
            $fixed = '"' . str_replace('"', '\\"', $matches[1][$index]) . '"';
            $response = str_replace($match, $fixed, $response);
        }
    }
    
    // Fix incorrect boolean values
    $response = preg_replace('/"(true|false)"/', '$1', $response);
    
    // Handle null values properly
    $response = preg_replace('/"null"/', 'null', $response);
    
    // Fix unbalanced brackets and braces
    $openBraces = substr_count($response, '{');
    $closeBraces = substr_count($response, '}');
    $openBrackets = substr_count($response, '[');
    $closeBrackets = substr_count($response, ']');
    
    // Add missing closing braces/brackets
    if ($openBraces > $closeBraces) {
        $response .= str_repeat('}', $openBraces - $closeBraces);
    }
    if ($openBrackets > $closeBrackets) {
        $response .= str_repeat(']', $openBrackets - $closeBrackets);
    }
    
    // Remove any extra closing braces/brackets
    if ($closeBraces > $openBraces) {
        // Find and remove extra closing braces
        $count = $closeBraces - $openBraces;
        $pattern = '/\}[,\s]*$/';
        for ($i = 0; $i < $count; $i++) {
            $response = preg_replace($pattern, '', $response, 1);
        }
    }
    if ($closeBrackets > $openBrackets) {
        // Find and remove extra closing brackets
        $count = $closeBrackets - $openBrackets;
        $pattern = '/\][,\s]*$/';
        for ($i = 0; $i < $count; $i++) {
            $response = preg_replace($pattern, '', $response, 1);
        }
    }
    
    // Final check - if still invalid, try a more aggressive approach
    $check = @json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Try to extract any valid JSON objects or arrays
        if (preg_match_all('/(\{(?:[^{}]|(?R))*\}|\[(?:[^\[\]]|(?R))*\])/', $response, $matches)) {
            foreach ($matches[0] as $potential) {
                $check = @json_decode($potential, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Found valid JSON
                    return $potential;
                }
            }
        }
        
        // If we get here, we couldn't find any valid JSON - return a minimal valid JSON object
        if (strpos($response, "Id") !== false && preg_match('/"Id"\s*:\s*"([^"]+)"/', $response, $idMatch)) {
            // Create a minimal valid JSON with the extracted ID
            return '{"Status":"Success","Message":{"Id":"' . $idMatch[1] . '"}}';
        }
        
        // Ultimate fallback - return a valid empty JSON array
        return '[]';
    }
    
    return $response;
}

/**
 * Extract a meaningful error message from LeadSquared API responses
 */
function extractErrorMessage($response) {
    if (empty($response)) {
        return 'Empty response from API';
    }
    
    // Handle raw string responses that might contain error information
    if (is_string($response)) {
        // Try to extract error information from raw string using regex
        if (preg_match('/"Exception(?:Type|Message)"\s*:\s*"([^"]+)"/', $response, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/"Message"\s*:\s*"([^"]+)"/', $response, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/"error"\s*:\s*"([^"]+)"/', $response, $matches)) {
            return $matches[1];
        }
        
        // JSON syntax errors often mention specific character positions
        if (strpos($response, 'Syntax error') !== false || strpos($response, 'Invalid JSON') !== false) {
            return 'JSON syntax error in request - please check for special characters or invalid formatting in lead data';
        }
        
        // Authentication errors
        if (strpos($response, 'Invalid AccessKey') !== false || strpos($response, 'authentication') !== false) {
            return 'Authentication failed - please verify your LeadSquared credentials';
        }
        
        // Return cleaned raw string if we can't extract specific error
        $cleaned = strip_tags(substr($response, 0, 200));
        return $cleaned . (strlen($response) > 200 ? '...' : '');
    }
    
    // Case 1: Standard error format
    if (is_array($response) && isset($response['ExceptionMessage'])) {
        return $response['ExceptionMessage'];
    }
    
    // Case 2: Error message in first array item
    if (is_array($response) && isset($response[0]['Status']) && $response[0]['Status'] == 'Error') {
        return $response[0]['Message'] ?? 'Unknown error';
    }
    
    // Case 3: Error in Message field
    if (is_array($response) && isset($response['Message'])) {
        return is_string($response['Message']) ? $response['Message'] : json_encode($response['Message']);
    }
    
    // Case 4: Error in error field
    if (is_array($response) && isset($response['error'])) {
        return is_string($response['error']) ? $response['error'] : json_encode($response['error']);
    }
    
    // Case 5: Status with description
    if (is_array($response) && isset($response['Status']) && isset($response['Description'])) {
        return $response['Description'];
    }
    
    // Case 6: Exception with details
    if (is_array($response) && isset($response['Exception']) && isset($response['Exception']['Message'])) {
        return $response['Exception']['Message'];
    }
    
    // Case 7: Check for validation errors format
    if (is_array($response) && isset($response['ValidationErrors']) && !empty($response['ValidationErrors'])) {
        $errors = [];
        foreach ($response['ValidationErrors'] as $error) {
            if (isset($error['Message'])) {
                $errors[] = $error['Message'];
            }
        }
        if (!empty($errors)) {
            return 'Validation errors: ' . implode(', ', $errors);
        }
    }
    
    // Default: Return serialized response
    return is_string($response) ? $response : json_encode($response);
}

/**
 * Create or update a lead in LeadSquared with simplified attribute-value approach
 * 
 * @param array $leadData Lead data with field values
 * @param string $accessKey LeadSquared access key
 * @param string $secretKey LeadSquared secret key
 * @param string $apiUrl LeadSquared API URL
 * @return array API response with status and message
 */
function createLeadSquaredLeadSimple($leadData, $accessKey = null, $secretKey = null, $apiUrl = null) {
    // Get settings from environment or database if not provided
    if ($accessKey === null) {
        $accessKey = getenv('LEADSQUARED_ACCESS_KEY');
        if (!$accessKey) {
            global $settings;
            if (isset($settings) && isset($settings['leadsquared_access_key'])) {
                $accessKey = $settings['leadsquared_access_key'];
            }
        }
    }
    
    if ($secretKey === null) {
        $secretKey = getenv('LEADSQUARED_SECRET_KEY');
        if (!$secretKey) {
            global $settings;
            if (isset($settings) && isset($settings['leadsquared_secret_key'])) {
                $secretKey = $settings['leadsquared_secret_key'];
            }
        }
    }
    
    if ($apiUrl === null) {
        $apiUrl = getenv('LEADSQUARED_API_URL');
        if (!$apiUrl) {
            global $settings;
            if (isset($settings) && isset($settings['leadsquared_api_url'])) {
                $apiUrl = $settings['leadsquared_api_url'];
            }
        }
    }
    
    // Set default API URL if empty
    if (empty($apiUrl)) {
        $apiUrl = 'https://api-in21.leadsquared.com/v2/LeadManagement.svc';
    }
    
    // Ensure the API URL has the correct format
    if (strpos($apiUrl, 'LeadManagement.svc') === false) {
        if (substr($apiUrl, -1) === '/') {
            $apiUrl = rtrim($apiUrl, '/');
        }
        
        if (strpos($apiUrl, '/v2') === false) {
            $apiUrl .= '/v2';
        }
        
        $apiUrl .= '/LeadManagement.svc';
    }
    
    // Check for credentials
    if (empty($accessKey) || empty($secretKey) || empty($apiUrl)) {
        error_log("LeadSquared credentials are not configured properly");
        return [
            'status' => 'error',
            'message' => 'LeadSquared credentials are not configured properly'
        ];
    }
    
    error_log("Using LeadSquared API URL: " . $apiUrl);
    
    // Check if we have a formatted phone number
    $phoneForSearch = $leadData['Phone'] ?? '';
    
    // Log what we're using for the search
    error_log("Using phone for lead search: " . $phoneForSearch);
    
    // Convert lead data to attribute-value pair format expected by LeadSquared
    $attributes = [];
    foreach ($leadData as $key => $value) {
        if ($value !== null && $value !== '') {
            // Special handling for phone to ensure it's properly formatted
            if ($key === 'Phone') {
                // Ensure the phone value is properly formatted
                $value = preg_replace('/[^0-9+\-]/', '', $value);
            }
            
            $attributes[] = [
                'Attribute' => $key,
                'Value' => is_bool($value) ? ($value ? 'true' : 'false') : (string)$value
            ];
        }
    }
    
    // Convert to JSON
    $jsonData = json_encode($attributes);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON encoding error: " . json_last_error_msg());
        
        // Clean up data and try again
        foreach ($attributes as &$attr) {
            if (is_string($attr['Value'])) {
                $attr['Value'] = preg_replace('/[^\p{L}\p{N}\s\.\-_@,;:\'"]|\\\\/u', '', $attr['Value']);
            }
        }
        
        $jsonData = json_encode($attributes);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => 'error',
                'message' => 'JSON encoding error: ' . json_last_error_msg()
            ];
        }
    }
    
    // First search for the lead by phone number
    $phone = $phoneForSearch;
    if (!empty($phone)) {
        $existingLead = search_lead_by_phone($phone, $accessKey, $secretKey, $apiUrl);
        error_log("Search by phone result: " . json_encode($existingLead));
        
        if (!empty($existingLead) && isset($existingLead['LeadId'])) {
            $leadId = $existingLead['LeadId'];
            error_log("Found existing lead by phone with ID: " . $leadId);
            
            // Add SearchBy attribute
            $attributes[] = ['Attribute' => 'SearchBy', 'Value' => 'Phone'];
            $attributes[] = ['Attribute' => 'Id', 'Value' => $leadId];
            $jsonData = json_encode($attributes);
            
            // Update the existing lead
            $result = update_lead($leadId, $jsonData, $accessKey, $secretKey, $apiUrl);
            
            if (isset($result['Status']) && $result['Status'] === 'Success') {
                return [
                    'status' => 'success',
                    'message' => 'Lead updated successfully by phone',
                    'data' => ['Id' => $leadId, 'IsNew' => false]
                ];
            } else {
                $errorMsg = isset($result['Message']) ? $result['Message'] : 'Unknown error updating lead';
                error_log("Error updating lead by phone: " . $errorMsg);
                
                // Try creating a new lead as fallback
                $result = create_lead($jsonData, $accessKey, $secretKey, $apiUrl);
                
                if (isset($result['Status']) && $result['Status'] === 'Success') {
                    return [
                        'status' => 'success',
                        'message' => 'Fallback lead creation successful',
                        'data' => ['Id' => $result['LeadId'] ?? null, 'IsNew' => true]
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to update or create lead: ' . ($result['Message'] ?? 'Unknown error'),
                        'data' => $result
                    ];
                }
            }
        }
    }
    
    // If not found by phone, search by email
    $email = $leadData['EmailAddress'] ?? '';
    if (!empty($email)) {
        $existingLead = search_lead_by_email($email, $accessKey, $secretKey, $apiUrl);
        error_log("Search by email result: " . json_encode($existingLead));
        
        if (!empty($existingLead) && isset($existingLead[0]['Id'])) {
            $leadId = $existingLead[0]['Id'];
            error_log("Found existing lead by email with ID: " . $leadId);
            
            // Add SearchBy attribute
            $attributes[] = ['Attribute' => 'SearchBy', 'Value' => 'EmailAddress'];
            $attributes[] = ['Attribute' => 'Id', 'Value' => $leadId];
            $jsonData = json_encode($attributes);
            
            // Update the existing lead
            $result = update_lead($leadId, $jsonData, $accessKey, $secretKey, $apiUrl);
            
            if (isset($result['Status']) && $result['Status'] === 'Success') {
                return [
                    'status' => 'success',
                    'message' => 'Lead updated successfully by email',
                    'data' => ['Id' => $leadId, 'IsNew' => false]
                ];
            } else {
                $errorMsg = isset($result['Message']) ? $result['Message'] : 'Unknown error updating lead';
                error_log("Error updating lead by email: " . $errorMsg);
            }
        }
    }
    
    // If lead not found, create a new one
    error_log("No existing lead found, creating new lead");
    $result = create_lead($jsonData, $accessKey, $secretKey, $apiUrl);
    
    if (isset($result['Status']) && $result['Status'] === 'Success') {
        return [
            'status' => 'success',
            'message' => 'Lead created successfully',
            'data' => ['Id' => $result['LeadId'] ?? null, 'IsNew' => true]
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to create lead: ' . ($result['Message'] ?? 'Unknown error'),
            'data' => $result
        ];
    }
}

/**
 * Search for leads by phone number using LeadSquared API
 * 
 * @param string $phone Phone number to search for
 * @param string $accessKey LeadSquared access key
 * @param string $secretKey LeadSquared secret key
 * @param string $apiUrl LeadSquared API URL base
 * @return array API response with lead data if found
 */
function search_lead_by_phone($phone, $accessKey, $secretKey, $apiUrl) {
    // Ensure phone number is properly formatted for API search
    // Remove any spaces or formatting first
    $cleanPhone = preg_replace('/[^0-9+\-]/', '', $phone);
    
    error_log("Searching lead by phone: $cleanPhone");
    
    // Try first with the original format (which might include hyphen)
    $url = $apiUrl . '/RetrieveLeadByPhoneNumber?accessKey=' . urlencode($accessKey) . 
           '&secretKey=' . urlencode($secretKey) . '&phone=' . urlencode($cleanPhone);
    
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    
    error_log("LeadSquared search by phone HTTP code: " . $httpCode);
    if (!empty($curlError)) {
        error_log("LeadSquared search by phone cURL error: " . $curlError);
    }
    
    $result = json_decode($response, true);
    
    // If no results, try with variations of the phone format
    if (empty($result) || !isset($result['LeadId'])) {
        // Try formats: 
        // 1. Without the hyphen (if present)
        // 2. Without the plus sign
        // 3. Without both plus and hyphen
        
        $phoneFormats = [];
        
        // If phone has a hyphen, try without it
        if (strpos($cleanPhone, '-') !== false) {
            $phoneFormats[] = str_replace('-', '', $cleanPhone);
        }
        
        // If phone has a plus sign, try without it
        if (strpos($cleanPhone, '+') === 0) {
            $phoneFormats[] = substr($cleanPhone, 1);
        }
        
        // If phone has both plus and hyphen, try without both
        if (strpos($cleanPhone, '+') === 0 && strpos($cleanPhone, '-') !== false) {
            $phoneFormats[] = str_replace(['+', '-'], '', $cleanPhone);
        }
        
        // Try each format
        foreach ($phoneFormats as $format) {
            error_log("Trying alternate phone format: $format");
            
            $url = $apiUrl . '/RetrieveLeadByPhoneNumber?accessKey=' . urlencode($accessKey) . 
                   '&secretKey=' . urlencode($secretKey) . '&phone=' . urlencode($format);
            
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            $altResult = json_decode($response, true);
            
            if (!empty($altResult) && isset($altResult['LeadId'])) {
                error_log("Found lead using alternate phone format: $format");
                return $altResult;
            }
        }
        
        // If still no results, try with the Lead.GetByMobile endpoint
        error_log("Trying alternate endpoint Lead.GetByMobile for phone: $cleanPhone");
        
        $url = $apiUrl . '/Lead.GetByMobile?accessKey=' . urlencode($accessKey) . 
               '&secretKey=' . urlencode($secretKey) . '&mobilePhone=' . urlencode($cleanPhone);
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        error_log("LeadSquared search by mobile endpoint HTTP code: " . $httpCode);
        
        $altResult = json_decode($response, true);
        
        // Check if we got a result from the alternate endpoint
        if (!empty($altResult) && isset($altResult[0]['Id'])) {
            // Convert the alternate format to match the expected format
            $result = [
                'LeadId' => $altResult[0]['Id'],
                'EmailAddress' => $altResult[0]['EmailAddress'] ?? '',
                'FirstName' => $altResult[0]['FirstName'] ?? '',
                'LastName' => $altResult[0]['LastName'] ?? ''
            ];
            error_log("Found lead using alternate endpoint. Lead ID: " . $result['LeadId']);
        }
    }
    
    return $result;
}

/**
 * Search for leads by email using LeadSquared API
 * 
 * @param string $email Email to search for
 * @param string $accessKey LeadSquared access key
 * @param string $secretKey LeadSquared secret key
 * @param string $apiUrl LeadSquared API URL base
 * @return array API response with lead data if found
 */
function search_lead_by_email($email, $accessKey, $secretKey, $apiUrl) {
    $url = $apiUrl . '/Leads.GetByEmailaddress?accessKey=' . urlencode($accessKey) . '&secretKey=' . urlencode($secretKey) . '&emailaddress=' . urlencode($email);
    
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    
    error_log("LeadSquared search by email HTTP code: " . $httpCode);
    if (!empty($curlError)) {
        error_log("LeadSquared search by email cURL error: " . $curlError);
    }
    
    return json_decode($response, true);
}

/**
 * Update an existing lead using LeadSquared API with attribute-value pairs
 * 
 * @param string $leadId LeadSquared lead ID
 * @param string $jsonData JSON string with attribute-value pairs
 * @param string $accessKey LeadSquared access key
 * @param string $secretKey LeadSquared secret key
 * @param string $apiUrl LeadSquared API URL base
 * @return array API response
 */
function update_lead($leadId, $jsonData, $accessKey, $secretKey, $apiUrl) {
    $url = $apiUrl . '/Lead.Update?accessKey=' . urlencode($accessKey) . '&secretKey=' . urlencode($secretKey) . '&leadId=' . urlencode($leadId);
    
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    
    error_log("LeadSquared update lead HTTP code: " . $httpCode);
    if (!empty($curlError)) {
        error_log("LeadSquared update lead cURL error: " . $curlError);
    }
    
    return json_decode($response, true);
}

/**
 * Create a new lead using LeadSquared API with attribute-value pairs
 * 
 * @param string $jsonData JSON string with attribute-value pairs
 * @param string $accessKey LeadSquared access key
 * @param string $secretKey LeadSquared secret key
 * @param string $apiUrl LeadSquared API URL base
 * @return array API response
 */
function create_lead($jsonData, $accessKey, $secretKey, $apiUrl) {
    $url = $apiUrl . '/Lead.Capture?accessKey=' . urlencode($accessKey) . '&secretKey=' . urlencode($secretKey);
    
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    
    error_log("LeadSquared create lead HTTP code: " . $httpCode);
    if (!empty($curlError)) {
        error_log("LeadSquared create lead cURL error: " . $curlError);
    }
    
    return json_decode($response, true);
}

/**
 * Format resort enquiry data for LeadSquared API
 * 
 * @param array $enquiryData Data from the resort enquiry form
 * @return array Formatted data for LeadSquared API
 */
function formatLeadSquaredData($enquiryData) {
    // Map our enquiry fields to LeadSquared field names
    return [
        'FirstName' => $enquiryData['first_name'] ?? '',
        'LastName' => $enquiryData['last_name'] ?? '',
        'EmailAddress' => $enquiryData['email'] ?? '',
        'Phone' => $enquiryData['phone'] ?? '',
        'mx_Date_of_Birth' => $enquiryData['date_of_birth'] ?? '',
        'mx_Has_Passport' => $enquiryData['has_passport'] ?? '',
        'mx_Resort_Name' => $enquiryData['resort_name'] ?? '',
        'mx_Destination_Name' => $enquiryData['destination_name'] ?? '',
        'mx_Resort_Code' => $enquiryData['resort_code'] ?? '',
        'mx_Country_Code' => $enquiryData['country_code'] ?? '',
        'mx_Lead_Source' => $enquiryData['lead_source'] ?? 'Web Enquiry',
        'mx_Lead_Brand' => $enquiryData['lead_brand'] ?? 'Timeshare Marketing',
        'mx_Lead_Sub_Brand' => $enquiryData['lead_sub_brand'] ?? '',
        'mx_Lead_Source_Description' => $enquiryData['lead_source_description'] ?? '',
        'mx_Lead_Location' => $enquiryData['lead_location'] ?? '',
        'Source' => 'Web Enquiry',
        'ProspectStage' => 'New'
    ];
}
?> 