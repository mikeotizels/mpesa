<?php
/**
 * Business To Business (B2B) API
 * 
 * @see https://developer.safaricom.co.ke/APIs/BusinessPayBill
 * @see https://developer.safaricom.co.ke/APIs/BusinessBuyGoods
 *
 * @package   Mikeotizels/Mpesa
 * @author    Michael Otieno <mikeotizels@gmail.com>
 * @copyright Copyright 2022-2024 Michael Otieno
 * @license   Licensed under The MIT License (MIT). For the license terms, 
 *            please see the LICENSE file that was distributed with this 
 *            source code or visit <https://opensource.org/licenses/MIT>.
 */ 

namespace Mikeotizels\Mpesa\Api\BusinessToBusiness;

use Mikeotizels\Mpesa\Mpesa;

/**
 * Class BusinessToBusiness
 * 
 * @since 2.0.0
 */
class BusinessToBusiness
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
     * Sends a request to initiate a B2B payment.
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

        $Initiator              = $this->core->config->get('account.initiator_name');
        $SecurityCredential     = $this->core->generateSecurityCredential('b2b');
        $CommandID              = $this->core->config->get('b2b.command_id', 'BusinessPayBill');
        $SenderIdentifierType   = $this->core->config->get('b2b.sender_identifier_type', 4);
        $PartyA                 = $this->core->config->get('account.short_code');
        // TODO: Generate a unique Account Number (7-12 characters)
        $AccountReference       = time();
        $QueueTimeOutURL        = $this->core->config->get('b2b.queue_time_out_url');
        $ResultURL              = $this->core->config->get('b2b.result_url');
        
        $defaultParams = [
            'Initiator'              => $Initiator,
            'SecurityCredential'     => $SecurityCredential,
            'CommandID'              => $CommandID,
            'SenderIdentifierType'   => $SenderIdentifierType,
            'RecieverIdentifierType' => 4,
            'Amount'                 => 0,
            'PartyA'                 => $PartyA,
            'PartyB'                 => '',
            'AccountReference'       => $AccountReference,
            'Requester'              => '',
            'Remarks'                => 'Sending a B2B payment request',
            'QueueTimeOutURL'        => $QueueTimeOutURL,
            'ResultURL'              => $ResultURL
        ];

        $endpoint = $this->core->config->get('endpoints.b2b');
        $body     = array_merge($defaultParams, $customParams);

        return $this->core->sendPostRequest($endpoint, $body);
    }
}