<?php
/**
 * Customer To Business (C2B) Register URL API
 * 
 * @see https://developer.safaricom.co.ke/APIs/CustomerToBusinessRegisterURL
 * 
 * @package   Mikeotizels/Mpesa
 * @author    Michael Otieno <mikeotizels@gmail.com>
 * @copyright Copyright 2022-2024 Michael Otieno
 * @license   Licensed under The MIT License (MIT). For the license terms, 
 *            please see the LICENSE file that was distributed with this 
 *            source code or visit <https://opensource.org/licenses/MIT>.
 */ 

namespace Mikeotizels\Mpesa\Api\CustomerToBusiness;

use Mikeotizels\Mpesa\Mpesa;

/**
 * Class CustomerToBusinessRegisterUrl
 * 
 * @since 2.0.0
 */
class CustomerToBusinessRegisterUrl
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
     * Sends a request to register Validation and Confirmation URLs on M-PESA.
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

        $ShortCode       = $this->core->config->get('account.short_code');
        $ResponseType    = $this->core->config->get('c2b.response_type', 'Completed');
        $ConfirmationURL = $this->core->config->get('c2b.confirmation_url');
        $ValidationURL   = $this->core->config->get('c2b.validation_url');

        $defaultParams = [
            'ShortCode'       => $ShortCode,
            'ResponseType'    => $ResponseType,
            'ConfirmationURL' => $ConfirmationURL,
            'ValidationURL'   => $ValidationURL
        ];

        $endpoint = $this->core->config->get('endpoints.c2bregisterurl');
        $body     = array_merge($defaultParams, $customParams);

        return $this->core->sendPostRequest($endpoint, $body);
    }
}