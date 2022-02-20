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
    // echo '<pre>' . print_r($invoiceDetail,true) . '</pre>';
    // die;
}
?>
<!DOCTYPE html>
<html>
<?php include('header.php'); ?>

<body>
    <div class="wrapper">
        <?php include('leftmenu.php'); ?>
        <div id="content">
            <?php include('pageheader.php'); ?>
            <?php if (!empty($invoiceDetail)) { ?>
                <div class="row mx-5 my-5">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-2">Invoice ID:</div>
                            <div class="col-md-10"><?= $invoiceDetail->id; ?></div>
                        </div>
                        <div class="row">
                            <div class="col-md-2">Status:</div>
                            <div class="col-md-10"><?= $invoiceDetail->status; ?></div>
                        </div>
                        <div class="row">
                            <div class="col-md-2">Invoice Date:</div>
                            <div class="col-md-10"><?= $invoiceDetail->detail->invoice_date; ?></div>
                        </div>
                        <?php if (isset($invoiceDetail->primary_recipients[0]->billing_info->name->full_name)) {?>
                        <div class="row">
                            <div class="col-md-2">Recipient Full Name:</div>
                            <div class="col-md-10"><?= $invoiceDetail->primary_recipients[0]->billing_info->name->full_name; ?></div>
                        </div>
                        <?php } ?>
                        <div class="row">
                            <div class="col-md-2">Email Address:</div>
                            <div class="col-md-10"><?= $invoiceDetail->primary_recipients[0]->billing_info->email_address; ?></div>
                        </div>
                        <div class="row">
                            <div class="col-md-2">Recepient Bill:</div>
                            <div class="col-md-10"><a href="<?= $invoiceDetail->detail->metadata->recipient_view_url; ?>" target="_blank" rel="noopener noreferrer" class="text-danger stretched-link">Click Here</a></div>
                        </div>
                        <div class="row">
                            <div class="col-md-2">Check Invoice:</div>
                            <div class="col-md-10"><a href="<?= $invoiceDetail->detail->metadata->invoicer_view_url; ?>" target="_blank" rel="noopener noreferrer" class="text-danger stretched-link">Click Here</a></div>
                        </div>
                        <br>
                        <div class="row border" style="border-width:3px!important;">
                            <div class="col-md-2"><strong>Item Name</strong></div>
                            <div class="col-md-1"><strong>QTY</strong></div>
                            <div class="col-md-2"><strong>Cost (USD)</strong></div>
                            <div class="col-md-2"><strong>Total (USD)</strong></div>
                            <div class="col-md-2"><strong>Tax (USD)</strong></div>
                        </div>
                        <?php foreach ($invoiceDetail->items as $invoice) { ?>
                            <div class="row border">
                                <div class="col-md-2"><?= $invoice->name; ?></div>
                                <div class="col-md-1"><?= $invoice->quantity; ?></div>
                                <div class="col-md-2"><?= $invoice->unit_amount->value; ?></div>
                                <div class="col-md-2"><?=  number_format($invoice->unit_amount->value*$invoice->quantity,2); ?></div>
                                <div class="col-md-2"><?= isset($invoice->tax->amount->value) ? $invoice->tax->amount->value : ''; ?></div>
                            </div>
                        <?php } ?>
                        <br>
                        <div class="row border">
                            <div class="col-md-3"><strong>Subtotal (USD)</strong></div>
                            <div class="col-md-2"><strong><?=number_format((float)$invoiceDetail->amount->breakdown->item_total->value,2);?></strong></div>
                        </div>
                        <?php if (isset($invoiceDetail->amount->breakdown->tax_total)) { ?>
                        <div class="row border">
                            <div class="col-md-3"><strong>TaxTotal (USD)</strong></div>
                            <div class="col-md-2"><strong><?=$invoiceDetail->amount->breakdown->tax_total->value;?></strong></div>
                        </div>
                        <?php } ?>
                        <?php if (isset($invoiceDetail->amount->breakdown->shipping)) { ?>
                        <div class="row border">
                            <div class="col-md-3"><strong>Shipping (USD)</strong></div>
                            <div class="col-md-2"><strong><?=$invoiceDetail->amount->breakdown->shipping->amount->value;?></strong></div>
                        </div>
                        <?php } ?>
                        <?php if (isset($invoiceDetail->amount->breakdown->custom)) { ?>
                        <div class="row border">
                            <div class="col-md-3"><strong><?=$invoiceDetail->amount->breakdown->custom->label;?> (USD)</strong></div>
                            <div class="col-md-2"><strong><?=$invoiceDetail->amount->breakdown->custom->amount->value;?></strong></div>
                        </div>
                        <?php } ?>

                        <br>
                        <div class="row border border-primary" style="border-width:3px!important;">
                            <div class="col-md-3"><strong>TOTAL (USD)</strong></div>
                            <div class="col-md-2"><h4><strong><?=$invoiceDetail->amount->value;?></strong></h4></div>
                        </div>

                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php include('footer.php'); ?>
</body>

</html>
