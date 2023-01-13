<?php
/**
 * Mikeotizels M-PESA APIs package test page.
 * 
 * This file demonstrates an example usage of the Mikeotizels M-PESA APIs 
 * library by trying to simulate an STK Push request. 
 *
 * @category  M-PESA API Integration
 * @package   Mikeotizels/API/ThirdParty/Safaricom/Mpesa
 * @author    Michael Otieno <mikeotizels@gmail.com>
 * @copyright Copyright 2022-2023 Michael Otieno
 * @license   Licensed under The MIT License (MIT). For the license terms, 
 *            please see the LICENSE file that was distributed with this 
 *            source code or visit <https://opensource.org/licenses/MIT>.
 * @version   2.0.0 / Daraja v2.0
 */ 

use Mikeotizels\Mpesa\Mpesa;

error_reporting(E_ALL);
@ini_set('display_errors', 1);

require_once __DIR__ . '/src/Mpesa.php';

$mpesa    = new Mpesa();
$response = null;

$mpesa->Environment    = 'testing';
$mpesa->ConsumerKey    = 'srn86N3UAq6bMAAiB2VLAr1jAwDghHb6';
$mpesa->ConsumerSecret = 'AbsFu26XOezp7570';
$mpesa->ShortCode      = '174379';
$mpesa->PassKey        = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';

try {
    $response = $mpesa->stkPushSimulate([
        'Amount'           => 1,
        'PhoneNumber'      => '254708374149',
        'CallBackURL'      => 'https://example.com/mpesa/stkpush/callback/',
        'AccountReference' => 'TEST',
        'TransactionDesc'  => 'Test Payment'
    ]);
}
catch (\Exception $e){
    die($e->getMessage());
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);