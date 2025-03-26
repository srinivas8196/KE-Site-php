<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'db.php';

// LeadSquared API credentials
$accessKey = 'u$r6e6e6a278d5021554cff7c2fa6380787';
$secretKey = '447cfb38ef665b4d6f204031e1be3c0fa98a18be';
$api_url_base = 'https://api-in21.leadsquared.com/v2/LeadManagement.svc';

// Collecting form data
$fname = $_POST['firstName'] ?? '';
$lname = $_POST['lastName'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phoneNumber'] ?? '';
$mobile = $_POST['PhoneFormatLsq'] ?? '';
$PhoneFormatLsq = $_POST['PhoneFormatLsq'] ?? '';
$dob = $_POST['dob'] ?? '';
$passport = $_POST['passport'] ?? '';
$country = $_POST['country'] ?? '';
$resort = $_POST['resort'] ?? '';
$holiday_destination = $_POST['holiday-destination'] ?? '';
$preferred_resort = $_POST['preferred-resort'] ?? '';

// If resort is not set but preferred_resort is, use that
if (empty($resort) && !empty($preferred_resort)) {
    $resort = $preferred_resort;
}

// If resort is not set but holiday_destination is, use that
if (empty($resort) && !empty($holiday_destination)) {
    $resort = $holiday_destination;
}

// Get city and country from database if available
$city = "General"; // Default value

// Try to get resort details from database if resort name is provided
$resort_code = $resort;
$lead_location = "General";

// Extract year from DOB
$dob_getyear = explode("-", $dob);
$year_of_birth = isset($dob_getyear[0]) ? $dob_getyear[0] : '';

// Lead attributes
$lead_source = "Affiliate";
$lead_brand = "Timeshare Marketing";
$lead_sub_brand = "Karma Experience";
$Website = "https://karmaexperience.com/";
$maritalStatus = "Single"; // Default value

// Modifying Lead Source Description based on resort
if ($resort === "Cambodia") {
    $lead_source_description = "Website | Cambodia";
    $lead_location = "General";
    $resort_code = "Cambodia";
} 
elseif ($resort === "Vietnam") {
    $lead_source_description = "Website | Vietnam";
    $lead_location = "General";
    $resort_code = "Vietnam"; 
}
elseif ($resort === "Maldives") {
    $lead_source_description = "Website | Maldives";
    $lead_location = "General";
    $resort_code = "Maldives"; 
}
elseif ($resort === "Phuket") {
    $lead_source_description = "Website | Phuket";
    $lead_location = "General";
    $resort_code = "Phuket"; 
}
elseif ($resort === "Bali") {
    $lead_source_description = "Website | Bali";
    $lead_location = "General";
    $resort_code = "Bali"; 
}
elseif ($resort === "Koh-Samui" || $resort === "Koh Samui") {
    $lead_source_description = "Website | Koh Samui";
    $lead_location = "General";
    $resort_code = "Koh Samui"; 
}
elseif (strpos($resort, "Karma Royal Haathi Mahal") !== false) {
    $lead_source_description = "Website | Karma Royal Haathi Mahal";
    $lead_location = "Goa";
    $resort_code = "Karma Royal Haathi Mahal"; 
}
elseif (strpos($resort, "Karma Royal Palms") !== false) {
    $lead_source_description = "Website | Karma Royal Palms";
    $lead_location = "Goa";
    $resort_code = "Karma Royal Palms"; 
}
elseif (strpos($resort, "Karma Royal MonteRio") !== false) {
    $lead_source_description = "Website | Karma Royal MonteRio";
    $lead_location = "Goa";
    $resort_code = "Karma Royal MonteRio"; 
}
else {
    // Default case
    $lead_source_description = "Website | " . $resort;
    $lead_location = "General";
    $resort_code = $resort;
}

// Calculating age from DOB
$lead_age = "";
if (!empty($dob)) {
    $fdate = date("Y-m-d", strtotime($dob));
    $from = new DateTime($fdate);
    $to = new DateTime('today');
    $lead_age = $from->diff($to)->y;
}

// Formatting names
$FirstName = ucfirst(strtolower($fname));
$LastName = ucfirst(strtolower($lname));

// JSON payload for LeadSquared API
$data_string = '[
    {"Attribute":"FirstName", "Value": "' . $FirstName . '"},
    {"Attribute":"LastName", "Value": "' . $LastName . '"},
    {"Attribute":"EmailAddress", "Value": "' . $email . '"},
    {"Attribute":"Phone", "Value": "' . $PhoneFormatLsq . '"},
    {"Attribute":"mx_date_of_birth", "Value": "' . $dob . '"},
    {"Attribute":"mx_Resort_Code", "Value": "' . $resort_code . '"},
    {"Attribute":"mx_Marital_status", "Value": "' . $maritalStatus . '"},
    {"Attribute":"mx_Spouse_Name", "Value": ""},
    {"Attribute":"mx_Spouse_DOB", "Value": ""},
    {"Attribute":"mx_Post_Code", "Value": ""},
    {"Attribute":"mx_How_did_you_hear_about_us", "Value": ""},
    {"Attribute":"mx_Lead_Brand", "Value": "' . $lead_brand . '"},
    {"Attribute":"mx_Lead_Sub_Brand", "Value": "' . $lead_sub_brand . '"},
    {"Attribute":"mx_Lead_location", "Value": "' . $lead_location . '"},
    {"Attribute":"mx_Lead_region", "Value": ""},
    {"Attribute":"mx_Latest_Lead_Source", "Value": "'.$lead_source.'"},
    {"Attribute":"Source", "Value": "' . $lead_source . '"},
    {"Attribute":"mx_Staff_Name", "Value": "-"},
    {"Attribute":"mx_City", "Value": "'.$city.'"},
    {"Attribute":"mx_Discounts_Offered", "Value": "-"},
    {"Attribute":"mx_Check_In_Date", "Value": ""},
    {"Attribute":"mx_Check_Out_Date", "Value": ""},
    {"Attribute":"mx_KEP_voucher_destination", "Value": ""},
    {"Attribute":"mx_Ambassador_Referrer", "Value": ""},
    {"Attribute":"Website", "Value": "' . $Website . '"},
    {"Attribute":"mx_Anniversary_Date", "Value": ""},
    {"Attribute":"mx_Country", "Value": "'.$country.'"},
    {"Attribute":"mx_Choose_your_month_of_travel", "Value": "-"},
    {"Attribute":"mx_Lead_source_description", "Value": "' . $lead_source_description . '"}
  ]';

// Function to search leads by phone
function search_lead_by_phone($phone, $accessKey, $secretKey, $api_url_base) {
    $url = $api_url_base . '/RetrieveLeadByPhoneNumber?accessKey=' . $accessKey . '&secretKey=' . $secretKey . '&phone=' . urlencode($phone);
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

// Function to search leads by email
function search_lead_by_email($email, $accessKey, $secretKey, $api_url_base) {
    $url = $api_url_base . '/Leads.GetByEmailaddress?accessKey=' . $accessKey . '&secretKey=' . $secretKey . '&emailaddress=' . urlencode($email);
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

// Function to update lead
function update_lead($lead_id, $data, $accessKey, $secretKey, $api_url_base) {
    $url = $api_url_base . '/Lead.Update?accessKey=' . $accessKey . '&secretKey=' . $secretKey . '&leadId=' . $lead_id;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

// Function to create new lead
function create_lead($data, $accessKey, $secretKey, $api_url_base) {
    $url = $api_url_base . '/Lead.Capture?accessKey=' . $accessKey . '&secretKey=' . $secretKey;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

// Searching for leads by phone first
$lead_data = search_lead_by_phone($PhoneFormatLsq, $accessKey, $secretKey, $api_url_base);

if (!empty($lead_data)) {
    // If lead is found by phone
    $data_string_array = json_decode($data_string, true);
    $data_string_array[] = ["Attribute" => "SearchBy", "Value" => "Phone"];
    $data_string = json_encode($data_string_array);
} else {
    // If no lead found by phone, search by email
    $lead_data = search_lead_by_email($email, $accessKey, $secretKey, $api_url_base);

    if (!empty($lead_data)) {
        // If lead is found by email
        $data_string_array = json_decode($data_string, true);
        $data_string_array[] = ["Attribute" => "SearchBy", "Value" => "EmailAddress"];
        $data_string = json_encode($data_string_array);
    }
}

// If a lead is found, update it
if (!empty($lead_data) && isset($lead_data['LeadId'])) {
    $lead_id = $lead_data['LeadId'];
    $data_string_array = json_decode($data_string, true);
    $data_string_array[] = ["Attribute" => "Id", "Value" => $lead_id];
    $data_string = json_encode($data_string_array);

    // Update the lead with the new values
    $result = update_lead($lead_id, $data_string, $accessKey, $secretKey, $api_url_base);
} else {
    // If no lead is found, create a new one
    $result = create_lead($data_string, $accessKey, $secretKey, $api_url_base);
}

// PHPMailer setup for email notification
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = 'smtp.gmail.com';                       // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'res@karmaexperience.com';              // SMTP username
    $mail->Password   = 'giicaljomdnadgnx';                     // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Enable implicit TLS encryption
    $mail->Port       = 465;                                    // TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    // Recipients
    $mail->setFrom('res@karmaexperience.com', 'Karma Experience');
    $mail->addAddress('suma.ds@karmaexperience.in', 'Karma Experience'); 
    //$mail->addAddress('thirumurugan.m@karmaexperience.in', 'Karma Experience'); 
    // $mail->addCC('srinivas.raj@karmaexperience.in', 'Karma Experience');

    // Content
    $mail->isHTML(true);
    $mail->Subject = "New Lead from $fname through $Website - $resort";
    $mail->Body = "
        Name: $fname $lname <br>
        Email: $email <br>
        Mobile: $PhoneFormatLsq <br>
        DOB: $dob <br>
        Country: $country <br>
        Passport: $passport <br>
        Preferred Destination/Resort: $resort <br>
        Lead Source: $lead_source <br> 
        Lead Brand: $lead_brand <br>
        Lead Location: $lead_location <br>
        Lead Source Description: $lead_source_description <br>
        Website: $Website <br> ";

    $mail->send();
    
    // Redirect to thank you page
    header("Location: thank-you.php");
    exit();
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}