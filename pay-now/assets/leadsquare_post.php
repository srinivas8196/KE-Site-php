<?php
//redeempage karma experience
extract($_POST);
//leadsquare access
$accessKey = 'u$r6e6e6a278d5021554cff7c2fa6380787';
$secretKey = '447cfb38ef665b4d6f204031e1be3c0fa98a18be';
$api_url_base = 'https://api-in21.leadsquared.com/v2/LeadManagement.svc';
//end leadsquare access

    $arraylead_location = Array(
        "Royal Palms" => "KRP",
        "Karma Bavaria" => "Bavaria",
        "Karma Chakra" => "Chakra",
        "Karma Exotica" => "Exotica",
        "Karma Haveli" => "Haveli",
        "Karma Sitabani" => "Sitabani",
        "Royal Haathi Mahal" => "KHM",
        "Karma Golden Camp" => "KGC",
        "Karma Group Membership" => "KGM",
        "Karma Munnar" => "KM",
        "Karma Seven Lakes" => "KSL",
        "Karma Sunshine Village" => "KSV",
        "Kerala" => "Kerala",
        "Rajathan" => "Jaipur",
        "Royal Benaulim" => "Beach Club",
        "Royal Monterio" => "Monterio",
        "Karma Apsara" => "KA",
        "Karma Borgo" => "Borgo",
        "Karma Song Hoai" => "KSH",
        "Karma Jimbaran" => "Jimbaran",
        "Karma Kandara" => "Kandara",
        "Karma Karnak" => "Karnak",
        "Karma Mayura" => "Mayura",
        "Karma Merapi Yogjakarta" => "Merapi",
        "Karma Minoan" => "Minoan",
        "Karma Royal Candidasa" => "Royal Candidasa", 
        "Karma Royal Jimbaran" => "Royal Jimbaran",
        "Karma Royal Sanur" => "Royal Sanur",
        "Karma Salak" => "Salak"
    );

    //mapping field
    $destination1 = ucwords(strtolower($SingleLine));
    $destination2 = $SingleLine1;
    $des1_date1 = $Date;
    $des1_date2 = $Date2;
    $des1_date3 = $Date3;
    $leadbrand = $SingleLine13;
    //$leadsubbrand = $SingleLine14;
    $leadsubbrand = "Karma Experience AU";
    $lsd = $SingleLine15;
    $leadregion = $SingleLine22;
    $qualification = "New Lead";
    $age1 = $main_age;
    $age2 = $partner_age;
    $yob = $SingleLine24; //year of birth
    $yob2 = $SingleLine28;
    $occupation = $SingleLine12;
    $country = $SingleLine23;
    $postcode = $SingleLine11;
    $mobile = (isset($PhoneFormatLsq2)?$PhoneFormatLsq2:"0");
    $adult = $SingleLine5;
    $children = $SingleLine6;
    $ke2code = $SingleLine9;
    $memberid = $SingleLine10;
   
    /*if (isset($arraylead_location[$destination1])){
        $lsq_resort_code = $arraylead_location[$destination1];
    }else{
        $lsq_resort_code = "General";
    }*/
    $lsq_resort_code = "General";
    $lead_location = $destination1;

    //$lsq_lsd = "AUID | KE WEB | ".$lead_location;
    $lsq_lsd = "AUID | KE WEB | General";

    $lsq_source_campaign = "KE2 Redeem";
    $utm_source = "Web Enquiry";
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $mydate = explode("-",$Date);
    $mymonth = Date("m", strtotime($mydate[1]));
    //$lsq_dob = $mydate[2]."-".$mymonth."-".$mydate[0];
    $lsq_dob = $yob.'-01-01';
    $Spouse_DOB = $yob2.'-01-01';
    //$yearofbirth = Date("Y") - $mydate[2];
    //$mx_age = Date("Y") - $mydate[2];
    $mx_Terms_and_Conditions_Date = Date("Y-m-d");

    $data_string = '[
        {"Attribute":"FirstName", "Value": "'.$Name_First.'"},
        {"Attribute":"LastName", "Value": "'.$Name_Last.'"},
        {"Attribute":"EmailAddress", "Value": "'.$Email.'"},
        {"Attribute":"Phone", "Value": "'.$PhoneFormatLsq.'"},
        {"Attribute":"mx_date_of_birth", "Value": "'.$lsq_dob.'"},
        {"Attribute":"mx_Marital_status", "Value": "'.$Dropdown.'"},
        {"Attribute":"mx_utm_source", "Value": "'.$utm_source.'"},
        {"Attribute":"mx_Nationality", "Value": "-"},
        {"Attribute":"mx_Week_Number", "Value": "-"},
        {"Attribute":"mx_Resort_Code", "Value": "'.$lsq_resort_code.'"},
        {"Attribute":"mx_Year_of_Birth", "Value": "'.$yob.'"},
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
        {"Attribute":"mx_Age", "Value": "'.$age1.'"},
        {"Attribute":"mx_Occupation", "Value": "'.$occupation.'"},
        {"Attribute":"mx_IP_Address", "Value": "'.$ip_address.'"},
        {"Attribute":"mx_Country", "Value": "'.$country.'"},
        {"Attribute":"mx_Choose_Your_Package", "Value": "-"},
        {"Attribute":"mx_Lead_source_description", "Value": "'.$lsq_lsd.'"},
        {"Attribute":"mx_Terms_and_Conditions_Date", "Value": "'.$mx_Terms_and_Conditions_Date.'"},

        {"Attribute":"mx_Travel_Destination", "Value": "'.$destination1.'"},
        {"Attribute":"mx_Travel_Destination_2", "Value": "'.$destination2.'"},
        {"Attribute":"mx_tdate1", "Value": "'.$des1_date1.'"},
        {"Attribute":"mx_tdate2", "Value": "'.$des1_date2.'"},
        {"Attribute":"mx_Tentative_Date_3", "Value": "'.$des1_date3.'"},
        {"Attribute":"mx_Spouse_Name", "Value": "'.$Name1_First.'"},
        {"Attribute":"mx_Spouse_Last_Name", "Value": "'.$Name1_Last.'"},
        {"Attribute":"mx_Spouse_DOB", "Value": "'.$Spouse_DOB.'"},
        {"Attribute":"mx_Post_Code", "Value": "'.$postcode.'"},
        {"Attribute":"Mobile", "Value": "'.$mobile.'"},
        {"Attribute":"mx_Number_of_Adults", "Value": "'.$adult.'"},
        {"Attribute":"mx_Number_of_Children", "Value": "'.$children.'"},
        {"Attribute": "SearchBy",	"Value": "EmailAddress"}
    ]';
    lsquare($data_string, $accessKey, $secretKey, $api_url_base);

    //removed{"Attribute":"mx_KE2_Code", "Value": "'.$ke2code.'"},
    //removed{"Attribute":"mx_KE2_Member_Contact", "Value": "'.$memberid.'"},


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