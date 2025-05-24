<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$ResponseType    = 'Completed'; #Completed|Cancelled
$ConfirmationURL = 'https://secure.example.com/confirmation';
$ValidationURL   = 'https://secure.example.com/validation';

try {
    $response = $mpesa->c2bRegisterUrl([
        'ResponseType'    => $ResponseType,
        'ConfirmationURL' => $ConfirmationURL,
        'ValidationURL'   => $ValidationURL
    ]);

    echo json_encode($response);
}
catch (Exception $e) {
    echo $e->getMessage();
}