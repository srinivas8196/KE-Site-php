<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require "../offer/roadshow-sby-survey-w4des20/PHPMailer/src/Exception.php";
require "../offer/roadshow-sby-survey-w4des20/PHPMailer/src/PHPMailer.php";
require "../offer/roadshow-sby-survey-w4des20/PHPMailer/src/SMTP.php";

extract($_POST);

date_default_timezone_set("Asia/Makassar");

function curlEmail($url, $data){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec($ch);
    curl_close ($ch);
    return $server_output;
}

function emailSend($data, $urltemplate, $subjectmail, $isuser){
    //smtp email
    $mail=new PHPMailer();
    $mail->isSMTP();
    $mail->Host="ssl://smtp.gmail.com";
    $mail->SMTPDebug = false;
    $mail->SMTPAuth=true;
    $mail->SMTPSecure = 'ssl';
    $mail->Username="notification@karmaexperience.com";
    $mail->Password="jatdeypzvewfpemz";
    $mail->Port = 465;
    $mail->isHTML(true);
    $mail->setFrom('notification@karmaexperience.com', 'Karma Experience');
    if ($isuser == true){
        $mail->addAddress($data["email"] , $data["name_first"]);
        $mail->addReplyTo('reservations@karmaexperience.com', 'Reservation Karma Experience');
    }else{
        $mail->addAddress('reservations@karmaexperience.com' , 'Reservation Karma Experience');
        $mail->addBCC('data.leads@karmagroup.com', 'Data Leads');
        $mail->addBCC('dan.delosreyes@karmagroup.com', 'Dan Delosreyes');
        //$mail->addCC('data.leads@karmagroup.com'); 
    }
    $mail->Subject = $subjectmail;
    $message2 = curlEmail($urltemplate, $data);

    $mail->Body = $message2;
    $mail->send();
}

function curlFasPay($url,$reqArray){
    $ch = curl_init( $url );
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>$reqArray,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

//FUNC PAYPAL
function generateToken($base_url, $clientid, $secretid){
    $data = Array(
        "grant_type"=>"client_credentials",
        "return_client_metadata"=>true,
        "return_authn_schemes"=>true,
        "ignoreCache"=>true
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$base_url."/v1/oauth2/token");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Basic '. base64_encode($clientid.":".$secretid)
    ));

    $server_output = curl_exec($ch);
    curl_close($ch);
    return $server_output;
}

function createOrder($data, $base_url, $token){
    extract($data);


    $dataorder = '{
        "intent": "CAPTURE",
        "purchase_units": [
            {
                "items": [
                    {
                        "name": "Karma Experience Payment",
                        "description": "Karma Experience Payment",
                        "quantity": "1",
                        "unit_amount": {
                            "currency_code": "'.strtoupper($selectcurrency).'",
                            "value": "'.$price.'.00"
                        }
                    }
                ],
                "amount": {
                    "currency_code": "'.strtoupper($selectcurrency).'",
                    "value": "'.$price.'.00",
                    "breakdown": {
                        "item_total": {
                            "currency_code": "'.strtoupper($selectcurrency).'",
                            "value": "'.$price.'.00"
                        }
                    }
                }
            }
        ],
        "application_context": {
            "return_url": "'.$callback.'",
            "cancel_url": "'.$callback.'"
        }
    }';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$base_url."/v2/checkout/orders");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataorder);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer '.$token
    ));

    $server_output = curl_exec($ch);
    curl_close($ch);
    return $server_output;
}

//END FUNC PAYPAL

$url = "https://forms.zohopublic.com/bennyrisanto/form/KarmaGroupBigList/formperma/rQLVoS4gClW6bWVfTuHPiWHQV5-k8WaTexavS-NCq8c/htmlRecords/submit";

$birthdate = "05-Dec-2022";
$arrivaldate = "05-Dec-2022";
$departuredate = "05-Dec-2022";
$partnerdob = "05-Dec-2022";

$_POST['birthdate_db_format'] = Date("Y-m-d", strtotime($birthdate));
$_POST['arrivaldate_db_format'] = Date("Y-m-d", strtotime($arrivaldate));
$_POST['departuredate_db_format'] = Date("Y-m-d", strtotime($departuredate));
$_POST['partnerdob_db_format'] = Date("Y-m-d", strtotime($partnerdob));
$_POST['idbooking'] = generateIDBook();
$_POST['phone'] = $SingleLine;

$idb = $_POST['idbooking'];

require "config.php";

$field = Array();


if ($_POST['selectcurrency'] == "aud"){

    $queryconfig = "SELECT * from faspay_config where meta='aud_currency_to_idr'";
    $result = $mysqli->query($queryconfig);
    $fetch = $result->fetch_assoc();
    $field[$fetch["meta"]] = $fetch["value"];
    $_POST['aud_currency'] = $field['aud_currency_to_idr'];

}else if ($_POST['selectcurrency'] == "usd"){

    $queryconfig = "SELECT * from faspay_config where meta='usd_currency_to_idr'";
    $result = $mysqli->query($queryconfig);
    $fetch = $result->fetch_assoc();
    $field[$fetch["meta"]] = $fetch["value"];
    $_POST['aud_currency'] = $field['usd_currency_to_idr'];

}else{

    $_POST['aud_currency'] = "1";

}


$res["curl"] = insertLeads($_POST, $url);
if(strpos($res["curl"], "Thank you") !== false){
    
    //if ($lead_source_description == "KE | WEB | W2DEC22 | BOOKING"){
        //$urltemplatetouser = "https://karmaexperience.com/edm/confirmation/site/booking.php";
        //$subjectmailtouser = "Booking Confirmation Karma Experience ";
        //emailSend($_POST, $urltemplatetouser, $subjectmailtouser, true);

        $urltemplateadmin = "https://karmaexperience.com/edm/confirmation/site/booking_to_admin.php";
        $subjectmailtoadmin = "New Booking - ".$lead_source_description;
        emailSend($_POST, $urltemplateadmin, $subjectmailtoadmin, false);


        $field = Array();
        $queryconfig = "SELECT * from faspay_config";
        $result = $mysqli->query($queryconfig);
        while($fetch = $result->fetch_assoc()) {
            $field[$fetch["meta"]] = $fetch["value"];
        }

        $query = "SELECT * from leads_booking WHERE idbooking='$idb'";
        $result = $mysqli->query($query);
        $fetch = $result->fetch_array(MYSQLI_ASSOC);

        if ($_POST['selectcurrency'] == "idr"){ //faspay

            if ($fetch['payment_method'] == "creditcard"){
                $bill_no = date("YmdHis").rand(100,900);
                $tranid = date("YmdGis");
    
                $merchant_id = $field['merchant_id_dev'];
                $pass = $field['merchant_pass_dev'];
    
                $signaturecc=sha1('##'.strtoupper($merchant_id).'##'.strtoupper($pass).'##'.$tranid.'##'.$fetch['price_idr'].'.00##'.'0'.'##');
                $myImageLogoUrl = 'https://karmaexperience.com/assets/images/logo-gold.png';
    
                $datapost = array(
                    "TRANSACTIONTYPE"               => '1',
                    "RESPONSE_TYPE"                 => '1',
                    "LANG"                          => '',                              
                    "MERCHANTID"                    => $merchant_id,                            
                    "PAYMENT_METHOD"                => '1',
                    "TXN_PASSWORD"                  => $pass,
                    "MERCHANT_TRANID"               => $tranid,       
                    "CURRENCYCODE"                  => 'IDR',
                    "AMOUNT"                        => $fetch['price_idr'].'.00',
                    "CUSTNAME"                      => $fetch['name_first']." ".$fetch['name_last'],
                    "CUSTEMAIL"                     => $fetch['email'],
                    "DESCRIPTION"                   => 'Payment BOOKID:#'.$fetch['idbooking']." TRANSACID:".$tranid,
                    "RETURN_URL"                    => 'https://karmaexperience.com/payment/callback.php', 
                    "SIGNATURE"                     =>  $signaturecc, 
                    "BILLING_ADDRESS"               => '',
                    "BILLING_ADDRESS_CITY"          => '',
                    "BILLING_ADDRESS_REGION"        => '',
                    "BILLING_ADDRESS_STATE"         => '',
                    "BILLING_ADDRESS_POSCODE"       => '',
                    "BILLING_ADDRESS_COUNTRY_CODE"  => 'ID',
                    "RECEIVER_NAME_FOR_SHIPPING"    => '',
                    "SHIPPING_ADDRESS"              => '',
                    "SHIPPING_ADDRESS_CITY"         => '',
                    "SHIPPING_ADDRESS_REGION"       => '',
                    "SHIPPING_ADDRESS_STATE"        => '',
                    "SHIPPING_ADDRESS_POSCODE"      => '',
                    "SHIPPING_ADDRESS_COUNTRY_CODE" => 'ID',
                    "SHIPPINGCOST"                  => '0.00',
                    "PHONE_NO"                      => $fetch['phone'],
                    "MPARAM1"                       => '',
                    "MPARAM2"                       => '',
                    "PYMT_IND"                      => '', 
                    "PYMT_CRITERIA"                 => '', 
                    "PYMT_TOKEN"                    => '', 
                    "style_merchant_name"         => 'black',
                    "style_order_summary"         => 'black',
                    "style_order_no"              => 'black',
                    "style_order_desc"            => 'black',
                    "style_amount"                => 'black',
                    "style_background_left"       => '#fff',
                    "style_button_cancel"         => 'grey',
                    "style_font_cancel"           => 'white',
                    "style_image_url"           => $myImageLogoUrl,
                    );
    
                    //CHECK PAYMENT
                    $queryckpayment = "SELECT * from payment WHERE id_book='$idb'";
                    $resultck = $mysqli->query($queryckpayment);
                    $isOntable = $resultck->num_rows;
    
                    if ($isOntable != 1){
                        //INSERT TO PAYMENT TABLE
                        $queryinsert = "INSERT INTO payment (`email`, `id_book`, `trx_id`, `merchant_id`, `merchant`, `bill_no`, `product`, `qty`, `amount`, `payment_plan`, `product_id`, `tenor`, `response_code`, `response_desc`, `status`, `paymenttype`, `created`, `updated`, `signature`) VALUES(
                            '".$fetch['email']."',
                            '".$fetch['idbooking']."',
                            '".$tranid."',
                            '".$field['merchant_id']."',
                            'KE',
                            '".$bill_no."',
                            '".$fetch['preferred_destination']."',
                            '1',
                            '".$fetch['price_idr']."',
                            '0',
                            '".$fetch['idbooking']."',
                            '0',
                            '0',
                            'Sukses',
                            'Pending',
                            'CreditCard',
                            '".date("Y-m-d H:i:s")."',
                            '".date("Y-m-d H:i:s")."',
                            '".$signaturecc."'
                        )";
                        $result = $mysqli->query($queryinsert);
                    }
                    
                    $urlpostcc = $field['f_cc_url'];
                    $string = '<form method="post" name="form" action="'.$urlpostcc.'">'; 
                    if ($datapost != null) {
                    foreach ($datapost as $name=>$value) {
                    $string .= '<input type="hidden" name="'.$name.'" value="'.$value.'">';
                        }
                    }
                    $string .= '</form>';
                    $string .= '<script> document.form.submit();</script>';
                    echo $string;
                    exit;
            }else{
                $user_id = $field['f_user_id'];
                $pass = $field['f_pass'];
    
                $bill_no = date("YmdHis").rand(100,900);
                $merchant_id = $field['merchant_id'];
                $merchant_name = $field['merchant_name'];
                $bill_date = date("Y-m-d H:i:s");
                $bill_expired = date("Y-m-d H:i:s",  strtotime('+1 day', strtotime($bill_date)));
    
                $signature=sha1(md5($user_id.$pass.$bill_no));
    
                $cust_name = $fetch['name_first']." ".$fetch['name_last'];
    
                $product = $fetch['preferred_destination'];
    
                $amount_for_debit = $fetch['price_idr'] * 100;
    
                $datapost = '
                {
                "request":"Post Data Transaction",
                "merchant_id":"'.$merchant_id.'",
                "merchant":"'.$merchant_name.'",
                "bill_no":"'.$bill_no.'",
                "bill_reff":"'.$bill_no.'",
                "bill_date":"'.$bill_date.'",
                "bill_expired":"'.$bill_expired.'",
                "bill_desc":"Pembayaran #'.$bill_no.'",
                "bill_currency":"IDR",
                "bill_gross":"0",
                "bill_miscfee":"0",
                "bill_total":"'.$amount_for_debit.'",
                "cust_no":"'.$fetch['id'].'",
                "cust_name":"'.$cust_name.'",
                "payment_channel": "'.$fetch['payment_channel'].'",
                "pay_type":"1",
                "bank_userid":"",
                "msisdn":"'.$fetch['phone'].'",
                "email":"'.$fetch['email'].'",
                "terminal":"10",
                "billing_name":"0",
                "billing_lastname":"0",
                "billing_address":"-",
                "billing_address_city":"-",
                "billing_address_region":"-",
                "billing_address_state":"-",
                "billing_address_poscode":"-",
                "billing_msisdn":"",
                "billing_address_country_code":"-",
                "receiver_name_for_shipping":"'.$cust_name.'",
                "shipping_lastname":"",
                "shipping_address":"-",
                "shipping_address_city":"-",
                "shipping_address_region":"-",
                "shipping_address_state":"-",
                "shipping_address_poscode":"-",
                "shipping_msisdn":"",
                "shipping_address_country_code":"-",
                "item":[
                    {
                    "product":"'.$product.'",
                    "qty":"1",
                    "amount":"'.$amount_for_debit.'",
                    "payment_plan":"01",
                    "merchant_id":"'.$field['merchant_id'].'",
                    "tenor":"00"
                    }
                ],
                "reserve1":"",
                "reserve2":"",
                "signature":"'.$signature.'"
                }';
    
                //CHECK PAYMENT
                $queryckpayment = "SELECT * from payment WHERE id_book='$idb'";
                $resultck = $mysqli->query($queryckpayment);
                $isOntable = $resultck->num_rows;
                $fetchPayment = $resultck->fetch_array(MYSQLI_ASSOC);
    
                if ($isOntable < 1){
                    $postpaymenturl = $field['f_debit_url'];
                    $objArr=curlFasPay($postpaymenturl, $datapost);
                    $a = json_decode($objArr);
                    //INSERT TO PAYMENT TABLE
                    $queryinsert = "INSERT INTO payment (`email`, `id_book`, `trx_id`, `merchant_id`, `merchant`, `bill_no`, `product`, `qty`, `amount`, `payment_plan`, `product_id`, `tenor`, `response_code`, `response_desc`, `status`, `paymenttype`, `created`, `updated`, `signature`, `redirect_url`) VALUES(
                        '".$fetch['email']."',
                        '".$fetch['idbooking']."',
                        '".$a->trx_id."',
                        '".$a->merchant_id."',
                        'KE',
                        '".$bill_no."',
                        '".$fetch['preferred_destination']."',
                        '1',
                        '".$fetch['price_idr']."',
                        '0',
                        '".$fetch['idbooking']."',
                        '0',
                        '0',
                        'Sukses',
                        'Pending',
                        'DebitCard',
                        '".date("Y-m-d H:i:s")."',
                        '".date("Y-m-d H:i:s")."',
                        '".$signature."',
                        '".$a->redirect_url."'
                    )";
                    $result = $mysqli->query($queryinsert);
                    header("Location:".$a->redirect_url);
                }else{
                    header("Location:".$fetchPayment['redirect_url']);
                }
    
            }

        }else{ //PAYPAL

                //$bill_no = date("YmdHis").rand(100,900);
                //$tranid = date("YmdGis");
                $currencyUp = strtoupper($_POST['selectcurrency']);
                date_default_timezone_set('Asia/Makassar');


                $gettoken = json_decode(generateToken($field['paypal_baseurl'], $field['paypal_clientid'], $field['paypal_secretid']));
                $createorder = createOrder($_POST, $field['paypal_baseurl'], $gettoken->access_token);
                $resultorder = json_decode($createorder);

                $query = "INSERT INTO logpaypal(id_order, log, created) values('".$resultorder->id."','".$createorder."','".date("Y-m-d h:i:s")."')";
                $mysqli->query($query);

                if (isset($resultorder->debug_id)){
                    echo $resultorder->name. " | " .$resultorder->message;
                }else{
                    header("location: ".$resultorder->links[1]->href);
                }
                exit();
        }

        

        //$res["status"]=true;
    //}else{
        //$res["status"]=true;
    //}

} else{
    $res["status"]=false;
}
echo json_encode($res);


function generateIDBook(){
    $a = "KEZU".date("Yis");
    return $a;
}

function sendZoho($data, $url){
    $return =false;
    if ($data["SingleLine22"] != ""){ //program name
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $return=curl_exec($ch);
        curl_close ($ch);
    }
    return $return;
}

function insertLeads($data, $url){
    require "config.php";

    extract($data);
    $zf_referrer_name=isset($zf_referrer_name)?$mysqli->real_escape_string($zf_referrer_name):"";
    $name_first=isset($name_first)?$mysqli->real_escape_string($name_first):"";
    $name_last=isset($name_last)?$mysqli->real_escape_string($name_last):"";
    $phone=isset($phone)?$mysqli->real_escape_string($phone):"";
    $email=isset($email)?$mysqli->real_escape_string($email):"";
    //$birthdate_db_format=isset($birthdate_db_format)?$mysqli->real_escape_string($birthdate_db_format):"";
    $birthdate_string=isset($birthdate)?$mysqli->real_escape_string($birthdate):"";
    $maritalstatus=isset($maritalstatus)?$mysqli->real_escape_string($maritalstatus):"";
    $employmentstatus=isset($employmentstatus)?$mysqli->real_escape_string($employmentstatus):"";
    $nationality=isset($nationality)?$mysqli->real_escape_string($nationality):"";
    $preferred_destination=isset($preferred_destination)?$mysqli->real_escape_string($preferred_destination):"";
    $number_in_party=isset($number_in_party)?$mysqli->real_escape_string($number_in_party):"0";
    $number_adult=isset($number_adult)?$mysqli->real_escape_string($number_adult):"0";
    $number_children=isset($number_children)?$mysqli->real_escape_string($number_children):"0";
    $number_infant=isset($number_infant)?$mysqli->real_escape_string($number_infant):"0";
    //$arrivaldate_db_format=isset($arrivaldate_db_format)?$mysqli->real_escape_string($arrivaldate_db_format):"";
    $arrival_date_str=isset($arrivaldate)?$mysqli->real_escape_string($arrivaldate):"";
    //$departuredate_db_format=isset($departuredate_db_format)?$mysqli->real_escape_string($departuredate_db_format):"";
    $departure_date_str=isset($departuredate)?$mysqli->real_escape_string($departuredate):"";
    $partner_name_first=isset($partnerfname)?$mysqli->real_escape_string($partnerfname):"";
    $partner_name_last=isset($partnerlname)?$mysqli->real_escape_string($partnerlname):"";
    //$partnerdob_db_format=isset($partnerdob_db_format)?$mysqli->real_escape_string($partnerdob_db_format):"";
    $partner_dob_str=isset($partnerdob)?$mysqli->real_escape_string($partnerdob):"";
    $partner_nationality=isset($partner_nationality)?$mysqli->real_escape_string($partner_nationality):"";
    $price=isset($price)?$mysqli->real_escape_string($price):"";

    $payment_channel = isset($payment_channel)?$mysqli->real_escape_string($payment_channel):"0";

    $price_idr=$price*$aud_currency;

    $payment_method=isset($payment_method)?$mysqli->real_escape_string($payment_method):"";
    $date_created = date("Y-m-d H:i:s");

    $umur1 = isset($umur1)?($umur1 != "" ? $umur1 : 0):0;
    $umur2 = isset($umur2)?($umur2 != "" ? $umur2 : 0):0;
    $umur3 = isset($umur3)?($umur3 != "" ? $umur3 : 0):0;
    $umur4 = isset($umur4)?($umur4 != "" ? $umur4 : 0):0;
    $umur5 = isset($umur5)?($umur5 != "" ? $umur5 : 0):0;
    
        $query = "
            INSERT INTO `leads_booking`(
                `idbooking`, 
                `name_first`, 
                `name_last`, 
                `phone`, 
                `email`, 
                `birthdate`, 
                `birthdate_string`, 
                `marital_status`, 
                `employment_status`, 
                `nationality`, 
                `preferred_destination`, 
                `number_in_party`, 
                `number_adult`, 
                `number_children`, 
                `number_infant`, 
                `umur_anak1`,
                `umur_anak2`,
                `umur_anak3`,
                `umur_anak4`,
                `umur_anak5`,
                `arrival_date`, 
                `arrival_date_str`,
                `departure_date`,
                `departure_date_str`,
                `partner_name_first`,
                `partner_name_last`,
                `partner_dob`,
                `partner_dob_str`,
                `partner_nationality`,
                `price`,
                `price_idr`,
                `idr_currency`,
                `payment_method`,
                `payment_channel`,
                `date_created`,
                `lead_sub_brand`,
                `lead_source`,
                `lead_source_description`,
                `referrer_url`
            ) VALUES (
                '$idbooking', 
                '$name_first', 
                '$name_last', 
                '$phone', 
                '$email', 
                '$birthdate_db_format',
                '$birthdate_string',
                '$maritalstatus',
                '$employmentstatus',
                '$nationality',
                '$preferred_destination',
                '$number_in_party',
                '$number_adult',
                '$number_children', 
                '$number_infant', 
                '$umur1',
                '$umur2',
                '$umur3',
                '$umur4',
                '$umur5',
                '$arrivaldate_db_format', 
                '$arrival_date_str',
                '$departuredate_db_format',
                '$departure_date_str',
                '$partner_name_first',
                '$partner_name_last',
                '$partnerdob_db_format',
                '$partner_dob_str',
                '$partner_nationality',
                '$price',
                '$price_idr',
                '$aud_currency',
                '$payment_method',
                '$payment_channel',
                '$date_created',
                '$lead_sub_brand',
                '$lead_source',
                '$lead_source_description',
                '$zf_referrer_name'
            )";
            if (!$mysqli->query($query)) {
                echo("Error description: " . $mysqli -> error);
                exit();
            }else{
                $return = "Thank you";
            }
            return $return;
    
}