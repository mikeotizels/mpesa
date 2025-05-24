<?php
/**
 * Business To Customer (B2C) API
 * 
 * @see https://developer.safaricom.co.ke/APIs/BusinessToCustomer
 * 
 * @package   Mikeotizels/Mpesa
 * @author    Michael Otieno <mikeotizels@gmail.com>
 * @copyright Copyright 2022-2024 Michael Otieno
 * @license   Licensed under The MIT License (MIT). For the license terms, 
 *            please see the LICENSE file that was distributed with this 
 *            source code or visit <https://opensource.org/licenses/MIT>.
 */ 

namespace Mikeotizels\Mpesa\Api\BusinessToCustomer;

use Mikeotizels\Mpesa\Mpesa;

/**
 * Class BusinessToCustomer
 * 
 * @since 2.0.0
 */
class BusinessToCustomer
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
     * Sends a request to initiate a transaction between an M-PESA Short Code 
     * to a Phone Number registered on M-PESA.
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

        $InitiatorName      = $this->core->config->get('account.initiator_name');
        $SecurityCredential = $this->core->generateSecurityCredential('b2c'); 
        $CommandID          = $this->core->config->get('b2c.command_id', 'BusinessPayment');
        $PartyA             = $this->core->config->get('account.short_code');
        $QueueTimeOutURL    = $this->core->config->get('b2c.queue_time_out_url');
        $ResultURL          = $this->core->config->get('b2c.result_url');

        if (!empty($customParams['BusinessShortCode'])) {
           $BusinessShortCode = $customParams['BusinessShortCode'];
        }

        $defaultParams = [
            'InitiatorName'      => $InitiatorName,
            'SecurityCredential' => $SecurityCredential,
            'CommandID'          => $CommandID,
            'Amount'             => 0,
            'PartyA'             => $PartyA,
            'PartyB'             => '',
            'Remarks'            => 'Sending a B2C payment request',
            'QueueTimeOutURL'    => $QueueTimeOutURL,
            'ResultURL'          => $ResultURL,
            'Occasion'           => null
        ];

        $endpoint = $this->core->config->get('endpoints.b2c');
        $body     = array_merge($defaultParams, $customParams);

        return $this->core->sendPostRequest($endpoint, $body);
    }
}