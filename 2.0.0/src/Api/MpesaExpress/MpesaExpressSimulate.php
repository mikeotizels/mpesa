<?php
/**
 * M-Pesa Express (Lipa na M-PESA Online/STK Push) Simulate API
 * 
 * @see https://developer.safaricom.co.ke/APIs/MpesaExpressSimulate
 * 
 * @package   Mikeotizels/Mpesa
 * @author    Michael Otieno <mikeotizels@gmail.com>
 * @copyright Copyright 2022-2024 Michael Otieno
 * @license   Licensed under The MIT License (MIT). For the license terms, 
 *            please see the LICENSE file that was distributed with this 
 *            source code or visit <https://opensource.org/licenses/MIT>.
 */ 

namespace Mikeotizels\Mpesa\Api\MpesaExpress;

use Mikeotizels\Mpesa\Mpesa;

/**
 * Class MpesaExpressSimulate
 * 
 * @since 2.0.0
 */
class MpesaExpressSimulate
{ 
    /**
     * @var Mpesa
     */
    private $core;

    /**
     * Constructor.
     *
     * @param Mpesa $core
     */
    public function __construct(Mpesa $core)
    {
        $this->core = $core;
    }

    /**
     * Sends a request to initiate an online payment on behalf of a customer 
     * using STK Push.
     *
     * @since 2.0.0
     *
     * @method POST
     * 
     * @param array $params An associative array of the request parameters.
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function sendRequest(array $params = [])
    {
        $customParams = [];

        foreach ($params as $key => $value) {
            $customParams[ucwords($key)] = $value;
        }

        $BusinessShortCode = $this->core->config->get('account.short_code');
        $Password          = $this->core->generateStkPushPassword();
        $Timestamp         = $this->core->getCurrentTimestamp();
        $TransactionType   = $this->core->config->get('stkpush.transaction_type', 'CustomerPayBillOnline');
        $CallBackURL       = $this->core->config->get('stkpush.callback_url');
        // TODO: Generate a unique Account Number (4-12 characters)?
        $AccountReference  = time();
 
        $defaultParams = [
            'BusinessShortCode' => $BusinessShortCode,
            'Password'          => $Password,
            'Timestamp'         => $Timestamp,
            'TransactionType'   => $TransactionType,
            'Amount'            => 0,
            'PartyA'            => '',
            'PartyB'            => $BusinessShortCode,
            'PhoneNumber'       => '',
            'CallBackURL'       => $CallBackURL,
            'AccountReference'  => $AccountReference,
            'TransactionDesc'   => 'LNMO Payment'
        ];
        
        $endpoint = $this->core->config->get('endpoints.stkpush');
        $body     = array_merge($defaultParams, $customParams);

        if (empty($body['PartyA']) && !empty($body['PhoneNumber'])) {
            $body['PartyA'] = $body['PhoneNumber'];
        }

        if (empty($body['PhoneNumber']) && !empty($body['PartyA'])) {
            $body['PhoneNumber'] = $body['PartyA'];
        }

        return $this->core->sendPostRequest($endpoint, $body);
    }
}