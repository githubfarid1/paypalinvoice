<?php
$token='';
if(isset($_COOKIE['access_token'])) {
    $token = $_COOKIE['access_token'];
}
require_once('DevCoder/DotEnv.php');
$env = new DotEnv(__DIR__ . '/.env');
$env->load();
if(getenv('APP_ENV') == 'sandbox') {
    $base = 'https://api-m.sandbox.paypal.com';
} else {
    $base = 'https://api-m.paypal.com';
}

$invoiceDetail = array();
if (isset($_GET['invoice_id'])) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $base . '/v2/invoicing/invoices/' . $_GET['invoice_id'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $invoiceDetail = json_decode($response);
    echo '<pre>' . print_r($invoiceDetail,true) . '</pre>';
    die;
}
?>
