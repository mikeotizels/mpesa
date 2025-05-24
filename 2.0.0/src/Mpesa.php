<?php
/**
 * M-PESA API Base 
 * 
 * @package   Mikeotizels/Mpesa
 * @author    Michael Otieno <mikeotizels@gmail.com>
 * @copyright Copyright 2022-2024 Michael Otieno
 * @license   Licensed under The MIT License (MIT). For the license terms, 
 *            please see the LICENSE file that was distributed with this 
 *            source code or visit <https://opensource.org/licenses/MIT>.
 */ 

namespace Mikeotizels\Mpesa;

use Mikeotizels\Mpesa\Config\Config;
use Mikeotizels\Mpesa\Cache\Cache;
use Mikeotizels\Mpesa\Api\Authorization\OAuth; 
use Mikeotizels\Mpesa\Api\DynamicQrCode\DynamicQrCode;
use Mikeotizels\Mpesa\Api\MpesaExpress\MpesaExpressSimulate;
use Mikeotizels\Mpesa\Api\MpesaExpress\MpesaExpressQuery;
use Mikeotizels\Mpesa\Api\CustomerToBusiness\CustomerToBusinessRegisterUrl;
use Mikeotizels\Mpesa\Api\BusinessToCustomer\BusinessToCustomer;
use Mikeotizels\Mpesa\Api\BusinessToBusiness\BusinessToBusiness;
use Mikeotizels\Mpesa\Api\TransactionStatus\TransactionStatus;
use Mikeotizels\Mpesa\Api\AccountBalance\AccountBalance;
use Mikeotizels\Mpesa\Api\Reversal\Reversal;
use DateTime;
use RuntimeException;
use ErrorException;
use DomainException;

/**
 * Class Mpesa
 * 
 * The M-PESA APIs container wrapper.
 * 
 * This class is the core of the package, it sets up the required properties 
 * and objects, handles the request, and sends back the response. 
 * 
 * @since 1.0.0
 */
class Mpesa
{
    /**
     * The package configuration.
     * 
     * @since 2.0.0
     * 
     * @var Config
     */
    public $config;

    /**
     * The cache store.
     * 
     * @since 2.0.0
     * 
     * @var Cache
     */
    public $cache;

    /**
     * The OAuth object.
     * 
     * @since 2.0.0
     * 
     * @var OAuth
     */
    private $oauth;

    /**
     * Constructor.
     *  
     * @since 1.0.0
     * @since 2.0.0 Added the optional $options parameter.
     * 
     * @param array $options An array of custom configuration options. 
     *                       Overrides the main configuration settings.
     */
    public function __construct($options = []) 
    {
        // Set the default timezone used by the date functions.
        // TODO: Should we use 'UTC' or 'Africa/Nairobi'?
        @date_default_timezone_set('Africa/Nairobi');

        // Instantiate the configuration object.
        $this->config = new Config($options);

        // Set PHP error handling.
        $this->setErrorHandling();

        // Run the check for required extensions.
        $this->checkRequiredExtensions(); // @codeCoverageIgnore
        
        // Instantiate other required objects.
        $this->cache = new Cache($this->config);
        $this->oauth = new OAuth($this);
    }

    //-------------------------------------------------------------------------
    // Initialization Methods
    //-------------------------------------------------------------------------

    /**
     * Checks weather we are in testing environment.
     * 
     * @since 2.0.0
     * 
     * @return boolean 
     */
    public function isSandbox() 
    {
        $env = $this->config->get('environment', 'sandbox');

        switch ($env) {
            case 'live':
            case 'production':
                return false;
                break;
            
            case 'sandbox':
            case 'testing':
            default:
                return true;
                break;
        }   
    }

    /**
     * Sets PHP error reporting and display based on the current environment.
     * 
     * Having the environment set to 'sandbox' means it pretty much reports and 
     * logs every problem that might occur; that's what development and testing 
     * settings are for. However, errors are typically hidden during production,
     * instead they are generally logged to the error log for debugging.
     * 
     * @since 2.0.0
     */
    private function setErrorHandling()
    {
        // TODO: Set error reporting to E_ALL and use custom Error Handler to
        //       handle errors. Log all errors but display only in testing.
        if ($this->isSandbox()) {
            @error_reporting(E_ALL);
            @ini_set('display_errors', 1);
        } else {
            @error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            @ini_set('display_errors', 0);
        }
    
        $logEnable = $this->config->get('log.enable', true);
        $logPath   = $this->config->get('log.path', '');
        $logFile   = '';

        if ($logEnable) {
            // TODO: Use an advanced Logger like monolog.
            // TODO: Try creating the logs directory if not exists.
            // TODO: Validate and sanitize the $logPath.
            if (!is_dir($logPath)) {
                $logPath = __DIR__ . '/../logs';
            }
            
            $logFile = $logPath . '/mpesa_error_log';

            @ini_set('log_errors', 1);
            @ini_set('error_log', $logFile);
        } else {
            @ini_set('log_errors', 0);
        }
    }

    /**
     * Checks the system for missing required PHP extensions.
     * 
     * @since 2.0.0
     * 
     * @throws RuntimeException 
     * 
     * @todo throw PackageException::forMissingExtension()
     *
     * @codeCoverageIgnore
     */
    private function checkRequiredExtensions()
    {
        $requiredExts = [
            'curl',
            'json',
            'openssl'
        ];

        $missingExts = [];

        foreach ($requiredExts as $ext) {
            if (!extension_loaded($ext)) {
                $missingExts[] = $ext;
            }
        }

        if ($missingExts !== []) {
            throw new RuntimeException(
                sprintf('The Mikeotizels M-PESA API SDK PHP package requires the following PHP extension(s) installed and loaded on the server: %s.', implode(', ', $missingExts))
            );
        }
    }

    //-------------------------------------------------------------------------
    // API Methods
    //-------------------------------------------------------------------------
    
    /**
     * Generates a dynamic M-PESA QR Code.
     * 
     * @see https://developer.safaricom.co.ke/APIs/DynamicQRCode
     *
     * @since 2.0.0
     * 
     * @param array $params An associative array of the request parameters.
     * 
     * @return mixed
     */
    public function qrCodeGenerate(array $params = [])
    {
        $qrc = new DynamicQrCode($this);
        return $qrc->sendRequest($params);
    }
    
    /**
     * Initiates an online payment on behalf of a customer using STK Push. 
     * 
     * @see https://developer.safaricom.co.ke/APIs/MpesaExpressSimulate
     *
     * @since 1.0.0
     * @since 2.0.0 Main code moved to class MpesaExpressSimulate.
     * 
     * @param array $params An associative array of the request parameters.
     * 
     * @return mixed
     */
    public function stkPushSimulate(array $params = [])
    {
        $stk = new MpesaExpressSimulate($this);
        return $stk->sendRequest($params);
    }
    
    /**
     * Checks the status of a Lipa na M-PESA Online payment.
     * 
     * @see https://developer.safaricom.co.ke/APIs/MpesaExpressQuery
     *
     * @since 1.0.0
     * @since 2.0.0 Main code moved to class MpesaExpressQuery.
     * 
     * @param array $params An associative array of the request parameters.
     * 
     * @return mixed
     */
    public function stkPushQuery(array $params = [])
    {
        $stk = new MpesaExpressQuery($this);
        return $stk->sendRequest($params);
    }
    
    /**
     * Registers validation and confirmation URLs on M-PESA. 
     * 
     * Safaricom only calls a validation URL if you have requested by writing 
     * an official letter to them.
     * 
     * @see https://developer.safaricom.co.ke/APIs/CustomerToBusinessRegisterURL
     *
     * @since 1.0.0
     * @since 2.0.0 Main code moved to class CustomerToBusinessRegisterUrl.
     * 
     * @param array $params An associative array of the request parameters.
     * 
     * @return mixed
     */
    public function c2bRegisterUrl(array $params = [])
    {
        $c2b = new CustomerToBusinessRegisterUrl($this);
        return $c2b->sendRequest($params);
    }

    /**
     * Initiates a transaction between an M-PESA Short Code to a Phone Number 
     * registered on M-PESA.
     * 
     * @see https://developer.safaricom.co.ke/APIs/BusinessToCustomer
     *
     * @since 1.0.0
     * @since 2.0.0 Main code moved to class BusinessToCustomer.
     * 
     * @param array $params An associative array of the request parameters.
     * 
     * @return mixed
     */
    public function b2cPaymentRequest(array $params = [])
    {
        $b2c = new BusinessToCustomer($this);
        return $b2c->sendRequest($params);
    }
    
    /**
     * Initiate a B2B payment request.
     * 
     * @see https://developer.safaricom.co.ke/Documentation
     *
     * @since 1.0.0
     * @since 2.0.0 Main code moved to class BusinessToBusiness.
     * 
     * @param array $params An associative array of the request parameters.
     * 
     * @return mixed
     */
    public function b2bPaymentRequest(array $params = [])
    {
        $b2b = new BusinessToBusiness($this);
        return $b2b->sendRequest($params);
    }
    
    /**
     * Checks the status of an M-PESA transaction.
     * 
     * @see https://developer.safaricom.co.ke/APIs/TransactionStatus
     *
     * @since 1.0.0
     * @since 2.0.0 Main code moved to class TransactionStatus.
     * 
     * @param array $params An associative array of the request parameters.
     * 
     * @return mixed
     */
    public function transactionStatusQuery(array $params = [])
    {
        $tns = new TransactionStatus($this);
        return $tns->sendRequest($params);
    }
    
    /**
     * Inquires about the balance on an M-PESA BuyGoods (Till Number).
     * 
     * @see https://developer.safaricom.co.ke/APIs/AccountBalance
     *
     * @since 1.0.0
     * @since 2.0.0 Main code moved to class AccountBalance.
     * 
     * @param array $params An associative array of the request parameters.
     * 
     * @return mixed
     */
    public function accountBalanceQuery(array $params = [])
    {
        $bal = new AccountBalance($this);
        return $bal->sendRequest($params);
    }

    /**
     * Reverses a C2B M-Pesa transaction.
     * 
     * @see https://developer.safaricom.co.ke/APIs/Reversal
     *
     * @since 1.0.0
     * @since 2.0.0 Main code moved to class Reversal.
     * 
     * @param array $params An associative array of the request parameters.
     * 
     * @return mixed
     */
    public function reversalRequest(array $params = [])
    {
        $rvs = new Reversal($this);
        return $rvs->sendRequest($params);
    }

    //-------------------------------------------------------------------------
    // Convenience Methods
    //-------------------------------------------------------------------------

    /**
     * Convenience method for sending a GET request.
     * 
     * In the M-PESA APIs context, this GET method is only used for sending a
     * request to generate an OAuth access token. Therefore, this method is 
     * only be used by the OAuth class to get an Access Token using the client 
     * credentials in the Config store.
     * 
     * @since 1.5.0
     * @since 2.0.0 Added $url parameter.
     * 
     * @param string $url         A relative URL to the OAuth endpoint. 
     * @param string $credentials The encoded client credentials.
     * 
     * @throws ErrorException
     *
     * @return mixed
     */
    public function sendGetRequest(string $url, string $credentials)
    {
        return $this->sendRequest('GET', $url, [
            'headers' => [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . $credentials
            ],
        ]);
    }

    /**
     * Convenience method for sending a POST request.
     * 
     * All M-PESA APIs on the Daraja platform are POST except Authorization API 
     * which is GET.
     * 
     * @since 1.5.0
     * @since 2.0.0 Added $url parameter.
     * 
     * @param string $url  A relative URL to the API endpoint. 
     * @param array  $body An associative array of the request body parameters.
     * 
     * @throws ErrorException
     *
     * @return mixed
     */
    public function sendPostRequest(string $url, array $body)
    {
        // Obtain an OAuth access token.
        $token = $this->oauth->getToken();
        
        // Send the request.
        return $this->sendRequest('POST', $url, [
            'headers' => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ],
            'body' => $body
        ]);
    }

    /**
     * Does the actual work of initializing the cURL extension, setting the cURL
     * request options, sending an HTTP request to the specified endpoint with 
     * the options set using the request method set, and grabbing the response 
     * returned (error or success response).
     * 
     * @since 1.0.0
     * @since 1.5.0 Added the $method parameter. 
     *
     * @param string $method   HTTP request method, either GET or POST.
     * @param string $endpoint The API endpoint. This should be a relative URL,
     *                         it will be merged with the base URL to form a 
     *                         complete URL.
     * @param array $params    An associative array of the request parameters.
     *                         Includes the request header and body parameters
     *                         in a name => value pair.
     * 
     * @throws ErrorException
     *
     * @return mixed
     */
    private function sendRequest(string $method, string $endpoint, array $params = [])
    {
        // TODO: Allow configuration for the base URL?
        $baseUrl = 'https://api.safaricom.co.ke/';

        // Use the isolated testing URL in sandbox environment.
        if ($this->isSandbox()) {
            $baseUrl = 'https://sandbox.safaricom.co.ke/';
        } 

        $url = $baseUrl . $endpoint;
        $ch  = curl_init();

        curl_reset($ch);
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $params['headers']);

        if (strtoupper($method) === 'POST'){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, self::jsonEncode($params['body']));
        }

        $response = curl_exec($ch);

        if ($response === false) { 
            throw new ErrorException(
                sprintf('cURL Error: %s' , curl_error($ch))
            );
        }

        if (empty($response)) {
            throw new ErrorException('API Error: The M-PESA API service returned an empty response.');
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        http_response_code($httpCode);

        return self::jsonDecode($response);
    }

    //-------------------------------------------------------------------------
    // Utility Methods
    //-------------------------------------------------------------------------

    /**
     * Gets the current request timestamp.
     * 
     * This is the Timestamp of the transaction, normally in the format of 
     * YEAR+MONTH+DATE+HOUR+MINUTE+SECOND (YYYYMMDDHHMMSS). 
     * 
     * @since 1.5.0
     * 
     * @return string
     */
    public function getCurrentTimestamp()
    {
        $date = new DateTime();
        return $date->format('YmdHis');
    }

    /**
     * Generates a password used for encrypting the STK Push request: A base64 
     * encoded string.
     *
     * @since 1.5.0
     *  
     * @throws RuntimeException
     *
     * @return string base64.encode(ShortCode + Passkey + Timestamp)
     */
    public function generateStkPushPassword()
    {        
       $shortCode = $this->config->get('account.short_code');
       $passKey   = $this->config->get('stkpush.passkey');

        if (empty($shortCode)) {
            throw new RuntimeException('Unable to generate the STK Push Password. The M-PESA Business ShortCode is not set in your configuration.');
        }

        if (empty($passKey)) {
            throw new RuntimeException('Unable to generate the STK Push Password. The M-PESA Express PassKey is not set in your configuration.');
        }

        $timestamp = $this->getCurrentTimestamp();

        return base64_encode($shortCode . $passKey . $timestamp);
    }

    /**
     * Generates the Security Credential used by M-PESA Core to authenticate a 
     * transaction.
     * 
     * @see https://developer.safaricom.co.ke/Documentation 
     * 
     * The Security Credential is generated by encrypting the base64 encoded 
     * initiator password with M-PESA's public key from a X509 certificate.
     * 
     * @link https://developer.safaricom.co.ke/api/v1/GenerateSecurityCredential/SandboxCertificate.cer
     * @link https://developer.safaricom.co.ke/api/v1/GenerateSecurityCredential/ProductionCertificate.cer
     * 
     * The Security Credential is used for the following APIs:
     * 
     * - B2C
     * - B2B
     * - Transaction Status
     * - Account Balance
     * - Reversal
     *
     * @since 1.0.0
     * @since 2.0.0 Added the optional $api parameter.
     * 
     * @requires OpenSSL extension.
     * 
     * @param string $api Optional. The slag of the active API which to generate 
     *                    the security credentials for, eg. "transactionstatus". 
     *
     * @throws RuntimeException
     *
     * @return string Base64 Encode(OpenSSLEncrypt(Initiator Password + Certificate))
     */
    public function generateSecurityCredential(string $api = '')
    {        
        $initiatorName = $this->config->get('account.initiator_name');
        $initiatorPass = $this->config->get('account.initiator_password');

        if (empty($initiatorPass)) {
            throw new RuntimeException(
                sprintf('Unable to generate the Security Credential for the "%s" API. The Password for Initiator "%s" is not set.', $api, $initiatorName)
            );
        } 
        
        $certFile = $this->config->get('cert.file');
        $certPath = $this->config->get('cert.path');

        if (!is_file($certFile)) {
            if (empty($certPath)) {
                $certPath = __DIR__ . '/../certs';
            }

            if (!is_dir($certPath)) {
                throw new RuntimeException(
                    sprintf('The system cannot find the directory "%s" that stores the certificate files for the M-PESA APIs SDK. It might have been removed, moved, renamed, or the directory is not readable by the server.', $certPath)
                );
            } 
        
            $certFile = $certPath . '/mpesa_production.cer';
        
            if ($this->isSandbox()) {
                $certFile = $certPath . '/mpesa_sandbox.cer';
            }
        }

        if (!file_exists($certFile)) {
            throw new RuntimeException(
                sprintf('Unable to generate the Security Credential for the "%s" API. The system cannot find the M-PESA Public Key Certificate file "%s". It might have been moved, removed, renamed, or the directory is not readable by the server.', $api, $certFile)
            );
        }

        if (!is_readable($certFile)) {
            throw new RuntimeException(
                sprintf('Unable to generate the Security Credential for the "%s" API. The system cannot read the M-PESA Public Key Certificate file "%s". Please check that the directory or the file have the correct access permissions.', $api, $certFile)
            );
        }

        $publicKey = file_get_contents($certFile);

        if (empty($publicKey)) {
            throw new RuntimeException(
                sprintf('Unable to generate the Security Credential for the "%s" API. The M-PESA Public Key Certificate file "%s" seems to be having invalid content or is empty.', $api, $certFile)
            );
        }
        
        // Encrypt the password with the M-PESA public key certificate. Use the 
        // RSA algorithm and PKCS #1.5 padding (not OAEP).
        openssl_public_encrypt($initiatorPass, $encryptedData, $publicKey, OPENSSL_PKCS1_PADDING);

        return base64_encode($encryptedData);
    }
   
    /**
     * Wrapper for json_encode that throws an exception when an error occurs.
     * 
     * @since 2.0.0
     *
     * @param array $input A PHP array. 
     *
     * @throws DomainException
     *
     * @return string JSON representation of the PHP array.
     */
    public static function jsonEncode(array $input)
    {
        $json = json_encode($input, JSON_UNESCAPED_SLASHES, 512);

        if (json_last_error()) {
            throw new DomainException(
                sprintf('JSON Error: %s', json_last_error_msg())
            );
        } 
        elseif ($json === 'null' && $input !== null) {
            throw new DomainException('JSON Error: Null result with non-null input');
        }

        return $json;
    }

    /**
     * Wrapper for json_decode that throws an exception when an error occurs.
     *      
     * @since 2.0.0 
     *
     * @param string $input A JSON string.
     *
     * @throws DomainException
     *
     * @return object The decoded JSON object.
     */
    public static function jsonDecode(string $input)
    {
        $obj = json_decode($input, false, 512, JSON_BIGINT_AS_STRING);

        if (json_last_error()) {
            throw new DomainException(
                sprintf('JSON Error: %s', json_last_error_msg())
            );
        } 
        elseif ($obj === null && $input !== 'null') {
            throw new DomainException('JSON Error: Null result with non-null input');
        }

        return $obj;
    }
}