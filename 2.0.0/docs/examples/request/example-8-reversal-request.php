<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$TransactionID = 'OEI2AK4Q16';
$Amount        = 1;

try {
    $response = $mpesa->reversalRequest([
        'TransactionID' => $TransactionID,
        'Amount'        => $Amount
    ]);

    echo json_encode($response);
}
catch (Exception $e) {
    echo $e->getMessage();
}