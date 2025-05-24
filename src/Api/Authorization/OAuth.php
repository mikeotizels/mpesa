<?php
/**
 * Authorization API
 * 
 * @see https://developer.safaricom.co.ke/APIs/Authorization
 * 
 * @package   Mikeotizels/Mpesa
 * @author    Michael Otieno <mikeotizels@gmail.com>
 * @copyright Copyright 2022-2024 Michael Otieno
 * @license   Licensed under The MIT License (MIT). For the license terms, 
 *            please see the LICENSE file that was distributed with this 
 *            source code or visit <https://opensource.org/licenses/MIT>.
 */ 

namespace Mikeotizels\Mpesa\Api\Authorization;

use Mikeotizels\Mpesa\Mpesa;
use Mikeotizels\Mpesa\Config\Config;
use Mikeotizels\Mpesa\Cache\Cache;
use ValueError;
use ErrorException;
use InvalidArgumentException;
use RuntimeException;
use Exception;

/**
 * Class OAuth
 * 
 * This is the Safaricom's OAuth2 wrapper class for the M-PESA APIs.
 *
 * @since 2.0.0
 */
class OAuth
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
     * Gets a time bound OAuth Access Token to call allowed APIs.
     *
     * NOTE: The OAuth access token expires in 3600 seconds (1 hour), after 
     *       which you will need to generate another token.
     *
     * @since 2.0.0
     * 
     * @method GET
     * 
     * @throws ValueError
     * @throws ErrorException
     * @throws RuntimeException
     *
     * @return string
     */
    public function getToken()
    {
        // Grab the access token from the cache store, if it exists.
        try {
            $token = $this->core->cache->get('token');

            if (!empty($token)) {
                return $token;
            }
        } 
        catch (Exception $e) {
            // TODO: Should we log warning here?
        }

        try {
            $endpoint    = $this->core->config->get('endpoints.oauth');
            $credentials = $this->generateCredentials();
            $response    = $this->core->sendGetRequest($endpoint, $credentials);

            if (!empty($response->errorCode)) {
                throw new ErrorException(
                    sprintf('Authorization Error: %s', $response->errorMessage)
                );
            }

            if (!isset($response->access_token)) {
                throw new ErrorException('Authorization Error: The Authorization API returned an unexpected result.');
            }
            
            if (empty($response->access_token)) {
                throw new ErrorException('Authorization Error: The Authorization API returned an empty Access Token.');
            }

            // Save the access token in the cache store. 
            try {
                $this->core->cache->save('token', $response->access_token, $response->expires_in);
            } 
            catch (Exception $e) {
                // TODO: Should we log warning here?
            }

            return $response->access_token;
        } 
        catch (Exception $e) {
            throw new ErrorException(
                sprintf('Authorization Error: %s', $e->getMessage())
            );
        }
    }

    /**
     * Generates the client credentials, a base64 encoded authorization key.
     *
     * @since 2.0.0
     * 
     * @throws RuntimeException 
     *
     * @return string Base64 Encode(ConsumerKey + : + ConsumerSecret)
     */
    private function generateCredentials()
    {
        $consumerKey    = $this->core->config->get('app.consumer_key');
        $consumerSecret = $this->core->config->get('app.consumer_secret');

        if (empty($consumerKey)) {
            throw new RuntimeException('Authorization Error: Unable to generate the Client Credentials. The M-PESA app Consumer Key is not set.');
        }

        if (empty($consumerSecret)) {
            throw new RuntimeException('Authorization Error: Unable to generate the Client Credentials. The M-PESA app Consumer Secret is not set.');
        }

        return base64_encode($consumerKey . ':' . $consumerSecret);
    }
}