<?php
/**
 * Transaction Status API
 * 
 * @see https://developer.safaricom.co.ke/APIs/TransactionStatus
 * 
 * @package   Mikeotizels/Mpesa
 * @author    Michael Otieno <mikeotizels@gmail.com>
 * @copyright Copyright 2022-2024 Michael Otieno
 * @license   Licensed under The MIT License (MIT). For the license terms, 
 *            please see the LICENSE file that was distributed with this 
 *            source code or visit <https://opensource.org/licenses/MIT>.
 */ 

namespace Mikeotizels\Mpesa\Api\TransactionStatus;

use Mikeotizels\Mpesa\Mpesa;

/**
 * Class TransactionStatus
 * 
 * @since 2.0.0
 */
class TransactionStatus
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
     * Sends a request to check the status of an M-PESA transaction.
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
        $SecurityCredential = $this->core->generateSecurityCredential('transactionstatus');
        $CommandID          = $this->core->config->get('transactionstatus.command_id', 'TransactionStatusQuery');
        $PartyA             = $this->core->config->get('account.short_code');
        $IdentifierType     = $this->core->config->get('transactionstatus.identifier_type', 4); 
        $QueueTimeOutURL    = $this->core->config->get('transactionstatus.queue_time_out_url');
        $ResultURL          = $this->core->config->get('transactionstatus.result_url');

        $defaultParams = [
            'Initiator'          => $Initiator,
            'SecurityCredential' => $SecurityCredential,
            'CommandID'          => $CommandID,
            'TransactionID'      => '',
            'PartyA'             => $PartyA,
            'IdentifierType'     => $IdentifierType,
            'QueueTimeOutURL'    => $QueueTimeOutURL,
            'ResultURL'          => $ResultURL,
            'Remarks'            => 'Sending a transaction status query',
            'Occasion'           => null
        ];

        $endpoint = $this->core->config->get('endpoints.transactionstatus');
        $body     = array_merge($defaultParams, $customParams);

        return $this->core->sendPostRequest($endpoint, $body);
    }
}