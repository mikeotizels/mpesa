<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$RefNo  = 'TEST';
$Amount = 1;

try {
    $response = $mpesa->qrCodeGenerate([
        'RefNo'  => $RefNo,
        'Amount' => $Amount
    ]);

    echo json_encode($response);
}
catch (Exception $e) {
    echo $e->getMessage();
}