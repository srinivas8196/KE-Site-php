<?php
/**
 * LeadSquared API Helper Functions
 * 
 * This file contains functions for interacting with the LeadSquared API
 * to create and update leads based on resort enquiries.
 */

/**
 * Create or update a lead in LeadSquared with duplicate checking
 * 
 * @param array $leadData Lead data in the format required by LeadSquared
 * @return array API response with status and message
 */
function createLeadSquaredLead($leadData) {
    // Load environment variables
    $env = parse_ini_file('.env');
    
    // API credentials
    $accessKey = $env['LEADSQUARED_ACCESS_KEY'] ?? 'u$r6e6e6a278d5021554cff7c2fa6380787';
    $secretKey = $env['LEADSQUARED_SECRET_KEY'] ?? '447cfb38ef665b4d6f204031e1be3c0fa98a18be';
    $apiUrl = $env['LEADSQUARED_API_URL'] ?? 'https://api-in21.leadsquared.com/v2/LeadManagement.svc';
    
    // Error logging
    $logFile = fopen('leadsquared_log.txt', 'a');
    fwrite($logFile, "=== " . date('Y-m-d H:i:s') . " ===\n");
    fwrite($logFile, "Lead data for LeadSquared: " . json_encode($leadData, JSON_PRETTY_PRINT) . "\n");
    
    // Convert to LeadSquared format
    $parameters = [];
    foreach ($leadData as $key => $value) {
        $parameters[] = [
            "Attribute" => $key,
            "Value" => $value
        ];
    }
    
    // Extract phone and email for duplicate checking
    $phone = $leadData['Phone'] ?? '';
    $email = $leadData['EmailAddress'] ?? '';
    
    fwrite($logFile, "Checking for duplicates with Phone: $phone, Email: $email\n");
    
    // Search for duplicate lead
    $leadId = null;
    
    // Search by phone if available
    if (!empty($phone)) {
        $existingLead = quickSearchLead($phone, $accessKey, $secretKey, $apiUrl);
        if (!empty($existingLead) && isset($existingLead[0]['ProspectID'])) {
            $leadId = $existingLead[0]['ProspectID'];
            fwrite($logFile, "Duplicate found by phone: Lead ID $leadId\n");
            $parameters[] = ["Attribute" => "SearchBy", "Value" => "Phone"];
        }
    }
    
    // If no lead found by phone and email is provided, search by email
    if (empty($leadId) && !empty($email)) {
        $existingLead = quickSearchLead($email, $accessKey, $secretKey, $apiUrl);
        if (!empty($existingLead) && isset($existingLead[0]['ProspectID'])) {
            $leadId = $existingLead[0]['ProspectID'];
            fwrite($logFile, "Duplicate found by email: Lead ID $leadId\n");
            $parameters[] = ["Attribute" => "SearchBy", "Value" => "EmailAddress"];
        }
    }
    
    // Response variable
    $result = [];
    
    // Update existing lead or create new one
    if ($leadId) {
        // Update lead
        fwrite($logFile, "Updating existing lead ID: $leadId\n");
        $response = updateLead($leadId, $parameters, $accessKey, $secretKey, $apiUrl);
        
        if (isset($response['Status']) && $response['Status'] === 'Success') {
            $result = [
                'status' => 'success',
                'message' => 'Lead updated successfully',
                'data' => [
                    'Message' => [
                        'Id' => $leadId,
                        'Status' => 'Updated'
                    ]
                ]
            ];
        } else {
            $result = [
                'status' => 'error',
                'message' => 'Failed to update lead: ' . json_encode($response),
                'data' => $response
            ];
        }
    } else {
        // Create new lead
        fwrite($logFile, "Creating new lead\n");
        $response = createNewLead($parameters, $accessKey, $secretKey, $apiUrl);
        
        if (isset($response['Status']) && $response['Status'] === 'Success') {
            $result = [
                'status' => 'success',
                'message' => 'Lead created successfully',
                'data' => [
                    'Message' => [
                        'Id' => $response['Message']['LeadId'],
                        'Status' => 'Created'
                    ]
                ]
            ];
        } else {
            $result = [
                'status' => 'error',
                'message' => 'Failed to create lead: ' . json_encode($response),
                'data' => $response
            ];
        }
    }
    
    fwrite($logFile, "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n");
    fclose($logFile);
    
    return $result;
}

/**
 * Search for a lead in LeadSquared
 * 
 * @param string $key Search key (phone or email)
 * @param string $accessKey LeadSquared access key
 * @param string $secretKey LeadSquared secret key
 * @param string $apiUrl LeadSquared API URL
 * @return array API response
 */
function quickSearchLead($key, $accessKey, $secretKey, $apiUrl) {
    $url = "$apiUrl/Leads.GetByQuickSearch?accessKey=$accessKey&secretKey=$secretKey&key=" . urlencode($key);
    
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    curl_close($curl);
    
    $logFile = fopen('leadsquared_log.txt', 'a');
    
    if ($err) {
        fwrite($logFile, "cURL Error in quickSearchLead: $err\n");
        fclose($logFile);
        return [];
    }
    
    fwrite($logFile, "QuickSearch Response (HTTP $httpCode): " . $response . "\n");
    fclose($logFile);
    
    return json_decode($response, true);
}

/**
 * Update an existing lead in LeadSquared
 * 
 * @param string $leadId LeadSquared lead ID
 * @param array $data Lead data
 * @param string $accessKey LeadSquared access key
 * @param string $secretKey LeadSquared secret key
 * @param string $apiUrl LeadSquared API URL
 * @return array API response
 */
function updateLead($leadId, $data, $accessKey, $secretKey, $apiUrl) {
    $url = "$apiUrl/Lead.Capture?accessKey=$accessKey&secretKey=$secretKey&leadId=$leadId";
    
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    curl_close($curl);
    
    $logFile = fopen('leadsquared_log.txt', 'a');
    
    if ($err) {
        fwrite($logFile, "cURL Error in updateLead: $err\n");
        fclose($logFile);
        return ['Status' => 'Error', 'Message' => $err];
    }
    
    fwrite($logFile, "Update Lead Response (HTTP $httpCode): " . $response . "\n");
    fclose($logFile);
    
    return json_decode($response, true);
}

/**
 * Create a new lead in LeadSquared
 * 
 * @param array $data Lead data
 * @param string $accessKey LeadSquared access key
 * @param string $secretKey LeadSquared secret key
 * @param string $apiUrl LeadSquared API URL
 * @return array API response
 */
function createNewLead($data, $accessKey, $secretKey, $apiUrl) {
    $url = "$apiUrl/Lead.Capture?accessKey=$accessKey&secretKey=$secretKey";
    
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    curl_close($curl);
    
    $logFile = fopen('leadsquared_log.txt', 'a');
    
    if ($err) {
        fwrite($logFile, "cURL Error in createNewLead: $err\n");
        fclose($logFile);
        return ['Status' => 'Error', 'Message' => $err];
    }
    
    fwrite($logFile, "Create Lead Response (HTTP $httpCode): " . $response . "\n");
    fclose($logFile);
    
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
        'Source' => 'Website Resort Enquiry',
        'ProspectStage' => 'New'
    ];
}
?> 