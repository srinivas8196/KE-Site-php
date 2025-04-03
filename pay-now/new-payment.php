<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

//require "../offer/roadshow-sby-survey-w4des20/PHPMailer/src/Exception.php";
//require "../offer/roadshow-sby-survey-w4des20/PHPMailer/src/PHPMailer.php";
//require "../offer/roadshow-sby-survey-w4des20/PHPMailer/src/SMTP.php";

$field['merchant_id_dev'] = "tes_auto";
$field['merchant_pass_dev'] = "abcde";
$field['merchant_id'] = "34984";
$field['f_cc_url'] = "https://fpg-sandbox.faspay.co.id/payment";
$field['f_user_id'] = "bot34984";
$field['f_pass'] = "g5ltw3Ox";
$field['merchant_name'] = "Karma Experience";
$field['f_debit_url'] = "https://web.faspay.co.id/cvr/300011/10";

// $field['paypal_baseurl'] = "https://api-m.paypal.com";
// $field['paypal_clientid'] = "AX7Z_LiCch_Tio5oXwBPTEmZ6w7mWnFPKEHVB14pD78_yEm17xz_KIIgyMhssg9s9-1xfta92UcgNaH6";
// $field['paypal_secretid'] = "EBufqcSS-afK1yHmDs3sJeeOImuyuI0NJXbpbUeBqWIsTUUq64MmBpwE9218HKfG9_NDZiA3MAT_XHOD";

// $field['paypal_baseurl'] = "https://api-m.sandbox.paypal.com";
// $field['paypal_clientid'] = "AbEZOyWtW22IoTJDRdk3bSZZQz8UICmXFQRXGQujPzvvoMO62cfe5C6FwwCDYu89SQdlA83mdh7vCEPJ";
// $field['paypal_secretid'] = "EHlPmBjhSRZSYAHvg8J3C271QRdcxxl8RanaSBsZl74YNhGtMBiNUU0-kLtpFQbPvTVCZ8B6mRdoAzX0";

extract($_POST);
date_default_timezone_set("Asia/Makassar");




function curlFasPay($url, $reqArray)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $reqArray,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

//faspay
$fetch['price_idr'] = $price;
$fetch['name_first'] = $name_first;
$fetch['name_last'] = $name_last;
$fetch['email'] = $email;
$fetch['idbooking'] = "RD" . rand(0, 10001);
$fetch['id'] = "CID" . rand(0, 10001);
$fetch['phone'] = $PhoneFormatLsq;
$fetch['preferred_destination'] = "RedeemKarma";

// if ($fetch['payment_method'] == "creditcard"){
$bill_no = date("YmdHis") . rand(100, 900);
$tranid = date("YmdGis");
$merchant_id = $field['merchant_id_dev'];
$pass = $field['merchant_pass_dev'];

$signaturecc = sha1('##' . strtoupper($merchant_id) . '##' . strtoupper($pass) . '##' . $tranid . '##' . $fetch['price_idr'] . '.00##' . '0' . '##');
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
    "AMOUNT"                        => $fetch['price_idr'] . '.00',
    "CUSTNAME"                      => $fetch['name_first'] . " " . $fetch['name_last'],
    "CUSTEMAIL"                     => $fetch['email'],
    "DESCRIPTION"                   => 'Payment BOOKID:#' . $fetch['idbooking'] . " TRANSACID:" . $tranid,
    "RETURN_URL"                    => 'http://localhost/payment/thank-you.php',
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

$urlpostcc = $field['f_cc_url'];
$string = '<form method="post" name="form" action="' . $urlpostcc . '">';
if ($datapost != null) {
    foreach ($datapost as $name => $value) {
        $string .= '<input type="hidden" name="' . $name . '" value="' . $value . '">';
    }
}
$string .= '</form>';
$string .= '<script> document.form.submit();</script>';
echo $string;
// exit;


function send_email($name_first, $name_last, $email, $SingleLine, $price, $tranid)
{
    try {
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                     // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = 'res@karmaexperience.com';                     // SMTP username
        $mail->Password   = 'bjgcgsatfddanokh';                               // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Enable implicit TLS encryption
        $mail->Port       = 465;                                    // TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        // Recipients
        $mail->setFrom('res@karmaexperience.com', 'Karma Group');
        // $mail->addAddress('res@karmaexperience.com', 'Karma Feedback');   // Add a recipient
        // $mail->addAddress('suma.ds@karmaexperience.in', 'Karma Experience');
        // $mail->addAddress('srinivas.raj@karmaexperience.in', 'Karma Experience');
        // $mail->addCC('anusakthi.velu@karmaexperience.in', 'Karma Experience');
        $mail->addAddress('srinivas.raj@karmaexperience.in', 'Karma Experience');

        $mail->isHTML(true);
        $mail->Subject = "New Redeemkarma Booking received from $name_first through www.karmaexperience.com/karma_Kandara-IDN/";
        $mail->Body = "
                <strong>Name:</strong> $name_first $name_last <br>
                <strong>Email:</strong> $email <br>
                <strong>Mobile:</strong> $SingleLine <br>
                <strong>Amount Paid:</strong> $price <br> 
                <strong>Merchant Tansaction-ID: $tranid <br> </strong>
                <strong>Website:</strong> https://redeemkarma.com/payment/pay-now/ <br> 
            ";

        $mail->send();

        // Response email to client
        $client_mail = new PHPMailer(true);
        $client_mail->isSMTP();

        $client_mail->isSMTP();
        $client_mail->Host       = 'smtp.gmail.com';
        $client_mail->SMTPAuth   = true;
        $client_mail->Username   = 'res@karmaexperience.com';
        $client_mail->Password   = 'bjgcgsatfddanokh';
        $client_mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $client_mail->Port = 465;


        $client_mail->setFrom('res@redeemkarma.com', 'Karma Group');
        $client_mail->addAddress($email); // Client's email address

        $client_mail->isHTML(true);
        $client_mail->Subject = "Thank You for Your Payment";
        $client_mail->Body = "
    <p>Thank you for your payment! Your Merchant Transaction ID is: <b> $tranid </b></p> <!-- Updated variable name -->
    <p>For any communications, please reach out to our team at res@karmaexperience.com</p>
    <img src='https://www.karmaexperience.com/Italy-booking-IND/img/client-acknt.jpg' alt='Thank You Image'>";

        $client_mail->send();
    } catch (Exception $e) {
        // Debugging: Log email sending error
        error_log("Email Sending Error: " . $e->getMessage());

        return false;
    }
}

// $result['lsq'] = lsquare($data_string, $accessKey, $secretKey, $api_url_base);
$result['email'] = send_email($name_first, $name_last, $email, $SingleLine, $price, $tranid);

echo json_encode($result);
