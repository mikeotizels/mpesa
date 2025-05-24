<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$CommandID              = 'BusinessPayBill'; #BusinessPayBill|BusinessBuyGoods
$RecieverIdentifierType = 4;
$Amount                 = 1;
$PartyB                 = '8481860';
$AccountReference       = 'TEST';

try {
    $response = $mpesa->b2bPaymentRequest([
        'CommandID'              => $CommandID,
        'RecieverIdentifierType' => $RecieverIdentifierType,
        'Amount'                 => $Amount,
        'PartyB'                 => $PartyB,
        'AccountReference'       => $AccountReference
    ]);

    echo json_encode($response);
}
catch (Exception $e) {
    echo $e->getMessage();
}