<?php
//redeempage karma experience
extract($_POST);

//leadsquare access
$accessKey = 'u$r6e6e6a278d5021554cff7c2fa6380787';
$secretKey = '447cfb38ef665b4d6f204031e1be3c0fa98a18be';
$api_url_base = 'https://api-in21.leadsquared.com/v2/LeadManagement.svc';
//end leadsquare access

    $data_string = '[
        {"Attribute":"FirstName", "Value": "'.$Name_First.'"},
        {"Attribute":"LastName", "Value": "'.$Name_Last.'"},
        {"Attribute":"EmailAddress", "Value": "'.$Email.'"},
        {"Attribute":"Phone", "Value": "'.$PhoneFormatLsq.'"},
        {"Attribute":"mx_utm_source", "Value": "'.$utm_source.'"},
        {"Attribute":"mx_Latest_Lead_Source", "Value": "'.$utm_source.'"},
        {"Attribute":"Website", "Value": "'.$zf_referrer_name.'"},
        {"Attribute":"mx_Lead_Brand", "Value": "'.$leadbrand.'"},
        {"Attribute":"mx_Lead_Sub_Brand", "Value": "'.$leadsubbrand.'"},
        {"Attribute":"mx_Lead_location", "Value": "'.$lead_location.'"},
        {"Attribute":"mx_Lead_region", "Value": "'.$leadregion.'"},
        {"Attribute":"mx_City", "Value": "'.$city.'"},
        {"Attribute":"mx_State", "Value": "'.$state.'"},
        {"Attribute":"Source", "Value": "'.$utm_source.'"},
        {"Attribute":"SourceCampaign", "Value": "'.$lsq_source_campaign.'"},
        {"Attribute":"mx_IP_Address", "Value": "'.$ip_address.'"},
        {"Attribute":"mx_Country", "Value": "'.$country.'"},
        {"Attribute":"mx_Lead_source_description", "Value": "'.$lsq_lsd.'"},
        {"Attribute":"mx_Terms_and_Conditions_Date", "Value": "'.$mx_Terms_and_Conditions_Date.'"},
        {"Attribute": "SearchBy",	"Value": "EmailAddress"}
    ]';
    lsquare($data_string, $accessKey, $secretKey, $api_url_base);

//Lead Square fun
function lsquare($data, $accessKey, $secretKey, $api_url_base){
    $lead_create = create_cap($data,$accessKey,$secretKey,$api_url_base);
    $lead_create_array = json_decode($lead_create, true);
    $status = $lead_create_array['Status'];

        //Savelog
        $log_data = json_decode($data);
        $log_email = $log_data[2]->Value;
 
        $mysqli = new mysqli("10.148.15.206","wpuser-bg","sNj&vc9F2mU&zxWe","karmaexperience_com");
        //$mysqli = new mysqli("localhost","root","","karmaexperience_com");
        if ($mysqli->connect_errno) {
            return "server";
        }
        date_default_timezone_set('Asia/Makassar');
        $created_date = date("Y-m-d h:i:s");
        $query = "INSERT INTO `log_lsq`(`email`, `log`,`status`,`created_date`) VALUES ( '$log_email','$lead_create','$status', '$created_date' )";
        $insert = $mysqli->query($query);


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