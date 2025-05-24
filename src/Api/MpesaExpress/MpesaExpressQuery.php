<?php
/**
 * M-Pesa Express (Lipa na M-PESA Online/STK Push) Query API
 * 
 * @see https://developer.safaricom.co.ke/APIs/MpesaExpressQuery
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
 * Class MpesaExpressQuery
 * 
 * @since 2.0.0
 */
class MpesaExpressQuery
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
     * Sends a request to check the status of a Lipa na M-PESA Online payment.
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

        $defaultParams = [
            'BusinessShortCode' => $BusinessShortCode,
            'Password'          => $Password,
            'Timestamp'         => $Timestamp,
            'CheckoutRequestID' => ''
        ];

        $endpoint = $this->core->config->get('endpoints.stkpushquery');
        $body     = array_merge($defaultParams, $customParams);

        return $this->core->sendPostRequest($endpoint, $body);
    }
}