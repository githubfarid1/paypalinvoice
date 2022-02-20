<?php
require_once('DevCoder/DotEnv.php');
$env = new DotEnv(__DIR__ . '/.env');
$env->load();
if (getenv('APP_ENV') == 'sandbox') {
    $base = 'https://api-m.sandbox.paypal.com';
} else {
    $base = 'https://api-m.paypal.com';
}
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => $base . '/v1/oauth2/token',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_POSTFIELDS => "grant_type=client_credentials",
    CURLOPT_USERPWD => getenv('CLIENT_ID') . ':' . getenv('SECRET'),
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Accept-Language: en_US',
        'Content-Type: application/x-www-form-urlencoded'
    ),
));

$response = curl_exec($curl);
curl_close($curl);
$credential = json_decode($response);
// echo '<pre>' . print_r($credential, true) . '</pre>';
// die;
setcookie('access_token', $credential->access_token, time() + 86400);

?>
<!DOCTYPE html>
<html>
<?php include('header.php'); ?>

<body>
    <div class="wrapper">
        <?php include('leftmenu.php'); ?>
        <div id="content">
            <?php include('pageheader.php'); ?>
            <?php if (isset($credential->access_token)) { ?>
                <div class="alert alert-primary" role="alert">
                    Token has Created!!
                </div>
            <?php } ?>
        </div>
    </div>
    <?php include('footer.php'); ?>
</body>

</html>
