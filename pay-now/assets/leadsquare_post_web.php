<?php
//redeempage karma experience
extract($_POST);
//leadsquare access
$accessKey = 'u$r6e6e6a278d5021554cff7c2fa6380787';
$secretKey = '447cfb38ef665b4d6f204031e1be3c0fa98a18be';
$api_url_base = 'https://api-in21.leadsquared.com/v2/LeadManagement.svc';
//end leadsquare access

    //mapping field
    $leadbrand = $Brand;
    $leadsubbrand = "Karma Experience AU";
    $lsd = $Lead_Source_Description;
    $leadregion = $Lead_Regions;
    $qualification = "New Lead";
    $country = $Country;
    $adult = $Adult;
    $children = $Children;

    $destination = strtoupper($Destination);

    $listDestination = Array(
        "RIVERSIDE BAMBOO RESORT" => Array(
            "resort_code" => "RB",
            "lead_location" => "Riverside Bamboo Resort"
        ),
        "SWISS-BELINN LEGIAN" => Array(
            "resort_code" => "SB Legian",
            "lead_location" => "Hotel Partner"
        ),
        "THE 101 BALI FONTANA SEMINYAK" =>Array(
            "resort_code" => "Fontana",
            "lead_location" => "Hotel Partner"
        ),
        "KARMA KANDARA" => Array(
            "resort_code" => "Kandara",
            "lead_location" => "Karma Kandara"
        ),
        "KARMA JIMBARAN" => Array(
            "resort_code" => "Jimbaran",
            "lead_location" => "Karma Jimbaran"
        ),
        "KARMA ROYAL JIMBARAN" => Array(
            "resort_code" => "Royal Jimbaran",
            "lead_location" => "Karma Royal Jimbaran"
        ),
        "KARMA ROYAL SANUR" => Array(
            "resort_code" => "Royal Sanur",
            "lead_location" => "Karma Royal Sanur"
        ),
        "KARMA SALAK" => Array(
            "resort_code" => "Salak",
            "lead_location" => "Karma Salak"
        ),
        "KARMA APSARA" => Array(
            "resort_code" => "KA",
            "lead_location" => "Karma Apsara"
        ),
        "KARMA SONG HOAI" => Array(
            "resort_code" => "KSH",
            "lead_location" => "Karma Song Hoai"
        ),
        "KARMA HAVELI" => Array(
            "resort_code" => "SB Legian",
            "lead_location" => "Hotel Partner"
        ),
        "KARMA HAATHI MAHAL" => Array(
            "resort_code" => "KHM",
            "lead_location" => "Royal Haathi Mahal"
        ),
        "KARMA ROYAL PALMS" => Array(
            "resort_code" => "KRP",
            "lead_location" => "Royal Palms"
        ),
        "KARMA BAVARIA" => Array(
            "resort_code" => "Bavaria",
            "lead_location" => "Karma Bavaria"
        ),
    );

    $listDestination[$destination]['resort_code'];

    $lsq_resort_code = (isset($listDestination[$destination]['resort_code']))?$listDestination[$destination]['resort_code']:"General";
    $lead_location = (isset($listDestination[$destination]['lead_location']))?$listDestination[$destination]['lead_location']:"General";

    $lsq_lsd = "AUID | KE WEB | $lsq_resort_code";

    $lsq_source_campaign = "Destination and Hotel Partner ".$destination;
    $utm_source = "Web Enquiry";
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $birthdate = $birthdate;
    $Year_of_Birth = $Year_of_Birth;
    $mydate = explode("-",$birthdate);
    $mymonth = Date("m", strtotime($mydate[1]));
    $lsq_dob = $mydate[2]."-".$mymonth."-".$mydate[0];
    $yearofbirth = $Year_of_Birth;
    $mx_Terms_and_Conditions_Date = Date("Y-m-d");

    $data_string = '[
        {"Attribute":"FirstName", "Value": "'.$Name_First.'"},
        {"Attribute":"LastName", "Value": "'.$Name_Last.'"},
        {"Attribute":"EmailAddress", "Value": "'.$Email.'"},
        {"Attribute":"Phone", "Value": "'.$PhoneFormatLsq.'"},
        {"Attribute":"mx_date_of_birth", "Value": "'.$lsq_dob.'"},
        {"Attribute":"mx_Marital_status", "Value": "'.$Marital_status.'"},
        {"Attribute":"mx_utm_source", "Value": "'.$utm_source.'"},
        {"Attribute":"mx_Nationality", "Value": "-"},
        {"Attribute":"mx_Week_Number", "Value": "-"},
        {"Attribute":"mx_Resort_Code", "Value": "'.$lsq_resort_code.'"},
        {"Attribute":"mx_Year_of_Birth", "Value": "'.$yearofbirth.'"},
        {"Attribute":"mx_Latest_Lead_Source", "Value": "'.$utm_source.'"},
        {"Attribute":"Website", "Value": "'.$zf_referrer_name.'"},
        {"Attribute":"mx_Choose_your_month_of_travel", "Value": "-"},
        {"Attribute":"mx_Lead_Brand", "Value": "'.$leadbrand.'"},
        {"Attribute":"mx_Lead_Sub_Brand", "Value": "'.$leadsubbrand.'"},
        {"Attribute":"mx_Lead_location", "Value": "'.$lead_location.'"},
        {"Attribute":"mx_Lead_region", "Value": "'.$leadregion.'"},
        {"Attribute":"mx_City", "Value": "-"},
        {"Attribute":"mx_State", "Value": "-"},
        {"Attribute":"Source", "Value": "'.$utm_source.'"},
        {"Attribute":"SourceCampaign", "Value": "'.$lsq_source_campaign.'"},
        {"Attribute":"mx_Qualification", "Value": "'.$qualification.'"},
        {"Attribute":"mx_Age", "Value": "0"},
        {"Attribute":"mx_Occupation", "Value": "-"},
        {"Attribute":"mx_IP_Address", "Value": "'.$ip_address.'"},
        {"Attribute":"mx_Country", "Value": "'.$country.'"},
        {"Attribute":"mx_Choose_Your_Package", "Value": "-"},
        {"Attribute":"mx_Lead_source_description", "Value": "'.$lsq_lsd.'"},
        {"Attribute":"mx_Terms_and_Conditions_Date", "Value": "'.$mx_Terms_and_Conditions_Date.'"},

        {"Attribute":"mx_Travel_Destination", "Value": "-"},
        {"Attribute":"mx_Travel_Destination_2", "Value": "-"},
        {"Attribute":"mx_tdate1", "Value": "-"},
        {"Attribute":"mx_tdate2", "Value": "-"},
        {"Attribute":"mx_Tentative_Date_3", "Value": "-"},
        {"Attribute":"mx_Spouse_Name", "Value": "-"},
        {"Attribute":"mx_Spouse_Last_Name", "Value": "-"},
        {"Attribute":"mx_Spouse_DOB", "Value": "-"},
        {"Attribute":"mx_Post_Code", "Value": "0"},
        {"Attribute":"Mobile", "Value": ""},
        {"Attribute":"mx_Number_of_Adults", "Value": "'.$adult.'"},
        {"Attribute":"mx_Number_of_Children", "Value": "'.$children.'"},
        {"Attribute":"mx_Member_ID", "Value": "0"},

        {"Attribute": "SearchBy",	"Value": "EmailAddress"}
    ]';
    lsquare($data_string, $accessKey, $secretKey, $api_url_base);


//Lead Square fun
function lsquare($data, $accessKey, $secretKey, $api_url_base){

    $lead_create = create_cap($data,$accessKey,$secretKey,$api_url_base);
    $lead_create_array = json_decode($lead_create, true);
    $status = $lead_create_array['Status'];
    print_r($lead_create_array);
    //echo $status;
}

function create_lead($data,$accessKey,$secretKey,$api_url_base)
{

    $url = $api_url_base . '/Lead.Create?accessKey=' . $accessKey . '&secretKey=' . $secretKey;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type:application/json',
        'Content-Length:'.strlen($data)
    ));
    $json_response = curl_exec($curl);
    //echo $json_response;
    return $json_response;

}


function create_cap($data,$accessKey,$secretKey,$api_url_base)
{

    $url = $api_url_base . '/Lead.Capture?accessKey=' . $accessKey . '&secretKey=' . $secretKey;
    //echo $url;die;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type:application/json',
        'Content-Length:'.strlen($data)
    ));
    $json_response = curl_exec($curl);
    //echo $json_response;
    return $json_response;

}

function update_lead($data,$leadID,$accessKey,$secretKey,$api_url_base)
{

    $url = $api_url_base . '/Lead.Update?accessKey=' . $accessKey . '&secretKey=' . $secretKey.'&leadId='.$leadID;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type:application/json',
        'Content-Length:'.strlen($data)
    ));
    $json_response = curl_exec($curl);
    //echo $json_response;
    return $json_response;

}

function get_lead_by_email($email)
{
    $accessKey = 'u$r6e6e6a278d5021554cff7c2fa6380787';
    $secretKey = '447cfb38ef665b4d6f204031e1be3c0fa98a18be';
    $url = 'https://api-in21.leadsquared.com/v2/LeadManagement.svc/Leads.GetByEmailaddress?accessKey='.$accessKey.'&secretKey='.$secretKey.'&emailaddress='.$email;
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => "",
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache"
        ),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        return $response;
    }
}

function get_lead_by_phone($phone)
{
    $accessKey = 'u$r6e6e6a278d5021554cff7c2fa6380787';
    $secretKey = '447cfb38ef665b4d6f204031e1be3c0fa98a18be';
    $url = 'https://api-in21.leadsquared.com/v2/LeadManagement.svc/RetrieveLeadByPhoneNumber?accessKey='.$accessKey.'&secretKey='.$secretKey.'&phone='.$phone;
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => "",
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache"
        ),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        return $response;
    }
}
//END lead square fun
?>