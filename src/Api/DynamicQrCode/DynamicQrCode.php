<?php
/**
 * Dynamic QR Code API
 * 
 * @see https://developer.safaricom.co.ke/APIs/DynamicQRCode
 * 
 * @package   Mikeotizels/Mpesa
 * @author    Michael Otieno <mikeotizels@gmail.com>
 * @copyright Copyright 2022-2024 Michael Otieno
 * @license   Licensed under The MIT License (MIT). For the license terms, 
 *            please see the LICENSE file that was distributed with this 
 *            source code or visit <https://opensource.org/licenses/MIT>.
 */

namespace Mikeotizels\Mpesa\Api\DynamicQrCode;

use Mikeotizels\Mpesa\Mpesa;

/**
 * Class DynamicQrCode
 * 
 * @since 2.0.0
 */
class DynamicQrCode
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
     * Sends a request to generate a dynamic M-PESA QR Code.
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

        $MerchantName = $this->core->config->get('account.merchant_name');
        // TODO: Generate a unique Reference Number (7-12 characters)?
        $RefNo        = time();
        $TrxCode      = $this->core->config->get('qrcode.trxcode');
        $CPI          = $this->core->config->get('account.short_code');
        $Size         = $this->core->config->get('qrcode.size', 300);

        $defaultParams = [
            'MerchantName' => $MerchantName,
            'RefNo'        => $RefNo,
            'Amount'       => 0,
            'TrxCode'      => $TrxCode,
            'CPI'          => $CPI,
            'Size'         => $Size
        ];

        $endpoint = $this->core->config->get('endpoints.qrcode');
        $body     = array_merge($defaultParams, $customParams);

        return $this->core->sendPostRequest($endpoint, $body);
    }
}