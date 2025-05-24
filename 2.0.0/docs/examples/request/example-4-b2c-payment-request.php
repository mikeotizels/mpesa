<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$CommandID = 'BusinessPayment'; #SalaryPayment|BusinessPayment|PromotionPayment
$Amount    = 1; 
$PartyB    = '254708374149';

try {
    $response = $mpesa->b2cPaymentRequest([
        'CommandID' => $CommandID,
        'Amount'    => $Amount,
        'PartyB'    => $PartyB
    ]);

    echo json_encode($response);
}
catch (Exception $e) {
    echo $e->getMessage();
}