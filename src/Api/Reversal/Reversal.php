<?php
/**
 * Reversal API
 * 
 * @see https://developer.safaricom.co.ke/APIs/Reversal
 * 
 * @package   Mikeotizels/Mpesa
 * @author    Michael Otieno <mikeotizels@gmail.com>
 * @copyright Copyright 2022-2024 Michael Otieno
 * @license   Licensed under The MIT License (MIT). For the license terms, 
 *            please see the LICENSE file that was distributed with this 
 *            source code or visit <https://opensource.org/licenses/MIT>.
 */

namespace Mikeotizels\Mpesa\Api\Reversal;

use Mikeotizels\Mpesa\Mpesa;

/**
 * Class Reversal
 * 
 * @since 2.0.0
 */
class Reversal
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
     * Sends a request to reverse a C2B M-PESA transaction.
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
        $SecurityCredential     = $this->core->generateSecurityCredential('reversal');
        $CommandID              = $this->core->config->get('reversal.command_id', 'TransactionReversal');
        $ReceiverParty          = $this->core->config->get('account.short_code');
        $RecieverIdentifierType = $this->core->config->get('reversal.reciever_identifier_type', ''); 
        $QueueTimeOutURL        = $this->core->config->get('reversal.queue_time_out_url');
        $ResultURL              = $this->core->config->get('reversal.result_url');

        $defaultParams = [
            'Initiator'              => $Initiator,
            'SecurityCredential'     => $SecurityCredential,
            'CommandID'              => $CommandID,
            'TransactionID'          => '',
            'Amount'                 => 0,
            'ReceiverParty'          => $ReceiverParty,
            'RecieverIdentifierType' => $RecieverIdentifierType,
            'QueueTimeOutURL'        => $QueueTimeOutURL,
            'ResultURL'              => $ResultURL,
            'Remarks'                => 'Sending a transaction reversal request',
            'Occasion'               => ''
        ];

        $endpoint = $this->core->config->get('endpoints.reversal');
        $body     = array_merge($defaultParams, $customParams);

        return $this->core->sendPostRequest($endpoint, $body);
    }
}