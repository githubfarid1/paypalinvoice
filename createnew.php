<?php
require_once('DevCoder/DotEnv.php');
$env = new DotEnv(__DIR__ . '/.env');
$env->load();
if (getenv('APP_ENV') == 'sandbox') {
    $base = 'https://api-m.sandbox.paypal.com';
} else {
    $base = 'https://api-m.paypal.com';
}
//print_r($base);
$invoicetext = isset($_POST['invoicetext']) ? $_POST['invoicetext'] : '';
$token = '';
if (isset($_COOKIE['access_token'])) {
    $token = $_COOKIE['access_token'];
}
if (isset($_POST['invoice'])) {
    foreach ($_POST['invoice'] as $key => $invoice) {
        if (isset($_POST['delete'][$key])) {
            deleteInvoice($token, $invoice, $base);
        }
        if (isset($_POST['send'][$key])) {
            $tes = sendInvoice($token, $invoice, $base);
        }
        if (isset($_POST['cancel'][$key])) {
            cancelInvoice($token, $invoice, $base);
        }
    }
}
if ($invoicetext <> '') {
    $words = (array_builder($invoicetext));
    $topline = array_shift($words);
    $email = $topline[0];
    $shipping = $topline[9];
    $invoiceNumber = json_decode(getInvoiceNumber($token, $base));
    $detail =
        array(
            'invoice_number' => $invoiceNumber->invoice_number,
            'invoice_date' => date("Y-m-d", strtotime('-1 days')),
            'currency_code' => 'USD',
            'note' => getenv('NOTE_TO_CUSTOMER'),
            // 'term' => 'No refunds after 30 days.',
            // 'memo' => 'This is a long contract',
            'payment_term' => ["due_date" => date("Y-m-d", strtotime('-1 days'))]
        );

    $invoicer = array(
        "name" => [
            "given_name" => getenv('INVOICER_NAME'),
            "surname" => getenv('INVOICER_SURNAME'),
        ],
        "email_address" => getenv('INVOICER_EMAIL'),
        "address" => [
            "address_line_1" => getenv('ADDRESS_LINE_1'),
            "address_line_2" => getenv('ADDRESS_LINE_2'),
            "admin_area_2" => getenv('ADMIN_AREA_2'),
            "admin_area_1" => getenv('ADMIN_AREA_1'),
            "postal_code" => getenv('POSTAL_CODE'),
            "country_code" => getenv('COUNTRY_CODE')
        ],
        "logo_url" => getenv('LOGO_URL')
    );

    $primary_recipients[] = array(
        "billing_info" => [
            // "name" => [
            //     "given_name" => "Stephanie",
            //     "surname" => "Meyers"
            // ],
            "email_address" => $email
        ]
    );
    foreach ($words as $word) {
        $qty = 0;
        $itemname = '';
        $price = 0;
        foreach ($word as $key => $word_detail) {
            //print_r('[' . $key . ']' . $word_detail);
            if ($key == 3) {
                $qty = (float)$word_detail;
            } elseif ($key == 1) {
                $itemname = $word_detail;
            } elseif ($key == 5) {
                $price = (float)$word_detail;
            }
        }
        if ($topline[11] != 0) {
            $items[] = array(
                'name' => $itemname,
                'description' => '',
                'quantity' => $qty,
                'unit_amount' => ['currency_code' => 'USD', 'value' => number_format($price, 2)],
                'tax' => ['name' => 'MN Sales Tax', 'percent' => 7.25],
                'unit_of_measure' => 'QUANTITY'
            );
        } else {
            $items[] = array(
                'name' => $itemname,
                'description' => '',
                'quantity' => $qty,
                'unit_amount' => ['currency_code' => 'USD', 'value' => number_format($price, 2)],
                'unit_of_measure' => 'QUANTITY'
            );

        }
    }
    $data = array(
        'detail' => $detail,
        'invoicer' => $invoicer,
        'primary_recipients' => $primary_recipients,
        'items' => $items,
        'amount' => array(
            'breakdown' => array(
                'shipping' => array(
                    'amount' => array(
                        'currency_code' => 'USD',
                        'value' => $shipping
                    )
                )
            )
        )
    );
    $result = postInvoice($token, $data, $base);
}
$listInvoice = json_decode(getListInvoice($token, $base));
?>

<!DOCTYPE html>
<html>
<?php include('header.php'); ?>
<style>
    .icon-input-btn {
        display: inline-block;
        position: relative;
    }

    .icon-input-btn input[type="submit"] {
        padding-left: 2em;
    }

    .icon-input-btn .fa {
        display: inline-block;
        position: absolute;
        left: 1em;
        top: 30%;
    }
</style>

<body>
    <div class="wrapper">
        <?php include('leftmenu.php'); ?>
        <div id="content">
            <?php include('pageheader.php'); ?>
            <div class="container-fluid">
                <div class="row mb-5">
                    <div class="col-md-4">
                        <h3>Data Source</h3>
                        <form action="createnew.php" method="POST">
                            <div class="form-group">
                                <label for="invoicetext"></label>
                                <textarea class="form-control" name="invoicetext" id="invoicetext" cols="50" rows="10"></textarea>
                            </div>
                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-primary">Submit</button>

                            </div>
                        </form>
                    </div>
                    <div class="col-md-8">
                        <form action="createnew.php" method="post" enctype="multipart/form-data" id="form-invoice">

                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>INVOICE ID</th>
                                        <th>EMAIL RECEPIENT</th>
                                        <th>STATUS</th>
                                        <th>ACTION</th>
                                    </tr>
                                    <?php if (isset($listInvoice->items)) { ?>
                                        <?php foreach ($listInvoice->items as $key => $list) { ?>
                                            <tr>
                                                <td scope="row"><?= $list->id; ?></td>
                                                <td><?= $list->primary_recipients[0]->billing_info->email_address; ?></td>
                                                <td><?= $list->status; ?></td>
                                                <td>
                                                    <input type="hidden" name="invoice[]" value="<?= $list->id; ?>">
                                                    <?php if ($list->status == 'DRAFT') { ?>
                                                        <input type="submit" name="send[<?= $key; ?>]" class="btn btn-primary btn-sm" value="Send" onclick="confirm('Send Invoice?') ? $('#form-invoice').submit() : false;">
                                                    <?php } ?>
                                                    <a href="view.php?invoice_id=<?= $list->id; ?>"><button type="button" class="btn btn-info btn-sm">View</button></a>
                                                    <?php if ($list->status == 'DRAFT' || $list->status == 'CANCELLED' || $list->status == 'SCHEDULED') { ?>
                                                        <input type="submit" name="delete[<?= $key; ?>]" class="btn btn-danger btn-sm" value="Del" onclick="confirm('Delete Data?') ? $('#form-invoice').submit() : false;">
                                                    <?php } ?>
                                                    <?php if ($list->status == 'SENT') { ?>
                                                        <input type="submit" name="cancel[<?= $key; ?>]" class="btn btn-warning btn-sm" value="Cancel" onclick="confirm('Cancel Data?') ? $('#form-invoice').submit() : false;">
                                                    <?php } ?>

                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                    </tbody>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('footer.php'); ?>

    <script>
        $(document).ready(function() {
            $(".icon-input-btn").each(function() {
                var btnFont = $(this).find(".btn").css("font-size");
                var btnColor = $(this).find(".btn").css("color");
                $(this).find(".fa").css({
                    'font-size': btnFont,
                    'color': btnColor
                });
            });
        });
    </script>

</body>

</html>
<?php
function array_builder($comments)
{
    //replace all tabs with end-cell, begin-cell
    $comments = preg_replace("/\t/", ",", $comments);

    $lines = array();
    $lines = explode("\n", $comments);
    // array_pop($lines);    //the last line was always black, so removing it
    //remove empty lines
    foreach ($lines as $key => $line) {
        if (empty($line)) {
            unset($lines[$key]);
        }
    }
    $words = array();

    foreach ($lines as $line) {
        //var_dump($count); //counting the lines
        $words[] = explode(",", $line);
    }
    return ($words);
}
function getListInvoice($token, $base)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $base . '/v2/invoicing/invoices?total_required=true&page_size=20',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $token
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}

function getInvoiceNumber($token, $base)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $base . '/v2/invoicing/generate-next-invoice-number',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}

function postInvoice($token, $data, $base)
{

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $base . '/v2/invoicing/invoices',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}

function deleteInvoice($token, $invoiceId, $base)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $base . '/v2/invoicing/invoices/' . $invoiceId,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}

function sendInvoice($token, $invoiceId, $base)
{

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $base . '/v2/invoicing/invoices/' . $invoiceId . '/send?notify_merchant=true',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
      "send_to_invoicer": true,
      "send_to_recipient": true
    }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}

function cancelInvoice($token, $invoiceId, $base)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $base . '/v2/invoicing/invoices/' . $invoiceId . '/cancel',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
      "subject": "Invoice Cancelled",
      "note": "Cancelling the invoice",
      "send_to_invoicer": true,
      "send_to_recipient": true,
      "additional_recipients": [
        "opencartplugin@gmail.com"
      ]
    }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}
?>
