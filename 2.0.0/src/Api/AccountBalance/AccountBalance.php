<?php
/**
 * Account Balance API
 * 
 * @see https://developer.safaricom.co.ke/APIs/AccountBalance
 * 
 * @package   Mikeotizels/Mpesa
 * @author    Michael Otieno <mikeotizels@gmail.com>
 * @copyright Copyright 2022-2024 Michael Otieno
 * @license   Licensed under The MIT License (MIT). For the license terms, 
 *            please see the LICENSE file that was distributed with this 
 *            source code or visit <https://opensource.org/licenses/MIT>.
 */  

namespace Mikeotizels\Mpesa\Api\AccountBalance;

use Mikeotizels\Mpesa\Mpesa;

/**
 * Class AccountBalance
 * 
 * @since 2.0.0
 */
class AccountBalance
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
     * Sends a HTTP request to check the balance on an M-PESA Till Number.
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

        $Initiator          = $this->core->config->get('account.initiator_name');
        $SecurityCredential = $this->core->generateSecurityCredential('accountbalance');
        $CommandID          = $this->core->config->get('accountbalance.command_id', 'AccountBalance');
        $PartyA             = $this->core->config->get('account.short_code');
        $IdentifierType     = $this->core->config->get('accountbalance.identifier_type', 4);
        $QueueTimeOutURL    = $this->core->config->get('accountbalance.queue_time_out_url');
        $ResultURL          = $this->core->config->get('accountbalance.result_url');

        $defaultParams = [
            'Initiator'          => $Initiator,
            'SecurityCredential' => $SecurityCredential,
            'CommandID'          => $CommandID,
            'PartyA'             => $PartyA,
            'IdentifierType'     => $IdentifierType,
            'Remarks'            => 'Sending an account balance query',
            'QueueTimeOutURL'    => $QueueTimeOutURL,
            'ResultURL'          => $ResultURL
        ];

        $endpoint = $this->core->config->get('endpoints.accountbalance');
        $body     = array_merge($defaultParams, $customParams);

        return $this->core->sendPostRequest($endpoint, $body);
    }
}