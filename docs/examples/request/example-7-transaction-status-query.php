<?php
require_once dirname(__DIR__) . '/bootstrap.php';
 
$TransactionID = 'OEI2AK4Q16';

try {
    $response = $mpesa->transactionStatusQuery([
        'TransactionID' => $TransactionID
    ]);

    echo json_encode($response);
}
catch (Exception $e) {
    echo $e->getMessage();
}