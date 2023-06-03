<?php
@error_reporting(E_ALL);
@ini_set('display_errors', 1);
@ini_set('log_errors', 0);

require_once __DIR__ . '/src/Mpesa.php';

try {
    $mpesa = new Mikeotizels\Mpesa\Mpesa();

    $mpesa->Environment    = 'testing';
    $mpesa->ConsumerKey    = 'srn86N3UAq6bMAAiB2VLAr1jAwDghHb6';
    $mpesa->ConsumerSecret = 'AbsFu26XOezp7570';
    $mpesa->ShortCode      = '174379';
    $mpesa->PassKey        = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';

    $response = $mpesa->stkPushSimulate([
        'Amount'           => 1,
        'PartyA'           => '254708374149',
        'PhoneNumber'      => '254708374149',
        'CallBackURL'      => 'https://example.com/mpesa/stkpush/callback',
        'AccountReference' => 'TEST',
        'TransactionDesc'  => 'Test Payment'
    ]);

    echo json_encode($response);
}
catch (Exception $e) {
    die($e->getMessage());
}