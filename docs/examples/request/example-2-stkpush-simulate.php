<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$Amount           = 1;;
$PhoneNumber      = '254708374149'; 
$AccountReference = 'TEST';

try {
    $response = $mpesa->stkPushSimulate([
        'Amount'           => $Amount,
        'PhoneNumber'      => $PhoneNumber,
        'AccountReference' => $AccountReference
    ]);

    echo json_encode($response);
}
catch (Exception $e) {
    echo $e->getMessage();
}