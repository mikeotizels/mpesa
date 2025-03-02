<?php
/**
 * M-PESA API
 * 
 * This is the Mikeotizels implementation of the various M-PESA APIs dubbed 
 * DARAJA APIs. 
 * 
 * @link https://github.com/mikeotizels/mpesa/
 * 
 * @author     Michael Otieno <mikeotizels@gmail.com>
 * @copyright  Copyright 2022-2023 Michael Otieno
 * @license    Licensed under The MIT License (MIT). For the license terms, 
 *             please see the LICENSE file that was distributed with this 
 *             source code or visit <https://opensource.org/licenses/MIT>.
 * @version    1.5.0
 */

namespace Mikeotizels\Mpesa;

use Exception;

/**
 * Class Mpesa
 *
 * @since 1.0.0
 */
class Mpesa 
{   
    /**
     * The M-PESA API environment type. 
     * 
     * This is used to determine the state of the package and API URL to use. 
     * Options: 'development', 'testing', or 'production'. 
     * 
     * When set to development or testing, the Sandbox endpoints will be used 
     * to send the request. When set to production, the Live endpoints will be 
     * used. Defaults to 'development'.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $Environment = 'development';
    
    /**
     * The M-PESA App Consumer Key
     *
     * Specifies the App Consumer Key given by Safaricom. Either one for the 
     * M-PESA Sandbox API or M-PESA Express (Lipa Na M-PESA Online).
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $ConsumerKey = '';

    /**
     * The M-PESA App Consumer Secret
     *
     * Specifies the App Consumer Secret given by Safaricom. Either one for the 
     * M-PESA Sandbox API or M-PESA Express (Lipa Na M-PESA Online).
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $ConsumerSecret = '';

    /**
     * Initiator name.
     * 
     * Specifies the name of Initiator to initiating  the request. This is the 
     * credential/username used to authenticate the transaction request.
     * 
     * Used with all transaction types apart from STK Push and C2B.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $InitiatorName = '';

    /**
     * Initiator password.
     * 
     * This is used when generating the Security Credential used to authenticate 
     * the transaction request.
     * 
     * Used with all transaction types apart from STK Push and C2B.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $InitiatorPassword = '';

    /**
     * The Business Shortcode    
     *
     * This is organizations ShortCode (PayBill or BuyGoods - A 5 to 7 digit 
     * account number) used to identify an organization and receive the funds.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $ShortCode = '';
    
    /**
     * The Lipa Na M-PESA Online Passkey  
     * 
     * Used when generating a password for encrypting STK Push requests.  
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $PassKey = '';

    #
    ## Authorization API
    #

    /**
     * Gets a time bound OAuth access token to call allowed APIs.
     *
     * NOTE: The OAuth access token expires in 3600 seconds (1 hour), after 
     *       which you will need to generate another token.
     *
     * @see https://developer.safaricom.co.ke/APIs/Authorization
     *
     * @since 1.0.0
     *
     * @method GET
     *
     * @throws Exception
     *
     * @return mixed
     */
    private function getAccessToken()
    {               
        if (!isset($this->ConsumerKey) || empty($this->ConsumerKey)) {
            throw new Exception("The M-PESA App Consumer Key is not set or is empty.");
        }

        if (!isset($this->ConsumerSecret) || empty($this->ConsumerSecret)) {
            throw new Exception("The M-PESA App Consumer Secret is not set or is empty.");
        }

        $endpoint    = 'oauth/v1/generate?grant_type=client_credentials';
        $credentials = base64_encode($this->ConsumerKey . ':' . $this->ConsumerSecret);

        $response = $this->sendGetRequest([
            'endpoint' => $endpoint,
            'credentials' => $credentials
        ]);

        if (!empty($response->errorCode)){
            throw new Exception(json_encode($response));
        }

        if (!isset($response->access_token)) {
            throw new Exception('Error making request to generate access token.');
        }
            
        if (empty($response->access_token)) {
            throw new Exception('The Authorization server returned an empty access token.');
        }

        return $response->access_token;
    }
    
    #
    ## M-Pesa Express (Lipa na M-PESA Online/STK Push) API
    #
    
    /**
     * M-PESA Express Simulate
     * 
     * Used to initiate online payment on behalf of a customer using STK Push.
     *
     * @see https://developer.safaricom.co.ke/APIs/MpesaExpressSimulate
     *
     * @since 1.0.0
     * @since 1.5.0 If the value of PartyA parameter is not provided in the the
     *              request and the value of PhoneNumber is provided, the value 
     *              of PartyA is used for PhoneNumber as well; and vice versa.
     *
     * @method POST
     * 
     * @param array $params Request parameters.
     *
     * @return mixed
     */
    public function stkPushSimulate(array $params = [])
    {
        $endpoint  = 'mpesa/stkpush/v1/processrequest';    
        $ShortCode = $this->ShortCode;
        $Password  = $this->generateStkPushPassword();
        $Timestamp = $this->getCurrentTimestamp();

        $defaultParams = [
            'BusinessShortCode' => $ShortCode,
            'Password'          => $Password,
            'Timestamp'         => $Timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => '',
            'PartyA'            => '',
            'PartyB'            => $ShortCode,
            'PhoneNumber'       => '',
            'CallBackURL'       => '',
            'AccountReference'  => time(),
            'TransactionDesc'   => 'LNMO Payment'
        ];

        $body = array_merge($defaultParams, $params);

        if (empty($body['PartyA']) && !empty($body['PhoneNumber'])) {
            $body['PartyA'] = $body['PhoneNumber'];
        }

        if (empty($body['PhoneNumber']) && !empty($body['PartyA'])) {
            $body['PhoneNumber'] = $body['PartyA'];
        }

        return $this->sendPostRequest([
            'endpoint' => $endpoint,
            'body' => $body
        ]);
    }

    /**
     * M-PESA Express Query
     *
     * Used to check the status of a Lipa na M-PESA Online payment.
     *
     * @see https://developer.safaricom.co.ke/APIs/MpesaExpressQuery
     *
     * @since 1.0.0
     *
     * @method POST
     * 
     * @param array $params Request parameters.
     * 
     * @return mixed
     */
    public function stkPushQuery(array $params = [])
    { 
        $endpoint  = 'mpesa/stkpushquery/v1/query';
        $ShortCode = $this->ShortCode;
        $Password  = $this->generateStkPushPassword();
        $Timestamp = $this->getCurrentTimestamp();

        $defaultParams = [
            'BusinessShortCode' => $ShortCode,
            'Password'          => $Password,
            'Timestamp'         => $Timestamp,
            'CheckoutRequestID' => ''
        ];

        $body = array_merge($defaultParams, $params);

        return $this->sendPostRequest([
            'endpoint' => $endpoint,
            'body' => $body
        ]);
    }
    
    #
    ## Customer To Business (C2B) API
    #

    /**
     * Customer To Business Register URL
     * 
     * Used to register validation and confirmation URLs on M-PESA. 
     * 
     * Safaricom only calls a validation URL if you have requested by writing 
     * an official letter to them.
     * 
     * @see https://developer.safaricom.co.ke/APIs/CustomerToBusinessRegisterURL
     *
     * @since 1.0.0
     *
     * @method POST
     * 
     * @param array $params Request parameters.
     *
     * @return mixed
     */ 
    public function c2bRegisterUrl(array $params = [])
    {
        $endpoint  = 'mpesa/c2b/v1/registerurl';
        $ShortCode = $this->ShortCode;

        $defaultParams = [
            'ShortCode'       => $ShortCode,
            'ResponseType'    => 'Completed',
            'ConfirmationURL' => '',
            'ValidationURL'   => ''
        ];

        $body = array_merge($defaultParams, $params);

        return $this->sendPostRequest([
            'endpoint' => $endpoint,
            'body' => $body
        ]);
    }

    /**
     * Customer To Business Simulate
     * 
     * Used to make C2B payment requests.
     * 
     * @see https://developer.safaricom.co.ke/APIs/CustomerToBusinessSimulate
     *
     * @since 1.0.0
     *
     * @method POST
     * 
     * @param array $params Request parameters.
     *
     * @return mixed
     */ 
    public function c2bSimulate(array $params = [])
    { 
        $endpoint  = 'mpesa/c2b/v1/simulate';
        $ShortCode = $this->ShortCode;

        $defaultParams = [
            'ShortCode'     => $ShortCode,
            'CommandID'     => 'CustomerPayBillOnline',
            'Amount'        => '',
            'MSISDN'        => '',
            'BillRefNumber' => time()
        ];

        $body = array_merge($defaultParams, $params);

        return $this->sendPostRequest([
            'endpoint' => $endpoint,
            'body' => $body
        ]);
    }
    
    #
    ## Business To Customer (B2C) API
    #
    
    /**
     * Business To Customer Payment Request
     * 
     * Used to transact between an M-PESA short code to a phone number 
     * registered on M-PESA.
     *
     * @see https://developer.safaricom.co.ke/APIs/BusinessToCustomer
     *
     * @since 1.0.0
     *
     * @method POST
     * 
     * @param array $params Request parameters.
     *
     * @return mixed
     */
    public function b2cPaymentRequest(array $params = [])
    {
        $endpoint           = 'mpesa/b2c/v1/paymentrequest';
        $InitiatorName      = $this->InitiatorName; 
        $SecurityCredential = $this->generateSecurityCredential();    
        $ShortCode          = $this->ShortCode;

        $defaultParams = [
            'InitiatorName'      => $InitiatorName,
            'SecurityCredential' => $SecurityCredential,
            'CommandID'          => 'BusinessPayment',
            'Amount'             => '',
            'PartyA'             => $ShortCode,
            'PartyB'             => '',
            'Remarks'            => 'Sending a B2C payment request',
            'QueueTimeOutURL'    => '',
            'ResultURL'          => '',
            'Occasion'           => ''
        ];

        $body = array_merge($defaultParams, $params);

        return $this->sendPostRequest([
            'endpoint' => $endpoint,
            'body' => $body
        ]);
    }
    
    #
    ## Business To Business (B2B) API
    #

    /**
     * Business To Business Payment Request 
     * 
     * Used to initiate a B2B payment request.
     * 
     * @see https://developer.safaricom.co.ke/Documentation
     *
     * @since 1.0.0
     *
     * @method POST
     * 
     * @param array $params Request parameters.
     *
     * @return mixed
     */ 
    public function b2bPaymentRequest(array $params = [])
    {
        $endpoint           = 'mpesa/b2b/v1/paymentrequest';
        $Initiator          = $this->InitiatorName;
        $SecurityCredential = $this->generateSecurityCredential();    
        $ShortCode          = $this->ShortCode;

        $defaultParams = [
            'Initiator'              => $Initiator,
            'SecurityCredential'     => $SecurityCredential,
            'CommandID'              => 'BusinessPayBill',
            'SenderIdentifierType'   => 4,
            'RecieverIdentifierType' => 4,
            'Amount'                 => '',
            'PartyA'                 => $ShortCode,
            'PartyB'                 => '',
            'AccountReference'       => time(),
            'Remarks'                => 'Sending a B2B payment request',
            'QueueTimeOutURL'        => '',
            'ResultURL'              => ''
        ];

        $body = array_merge($defaultParams, $params);

        return $this->sendPostRequest([
            'endpoint' => $endpoint,
            'body' => $body
        ]);
    }
    
    #
    ## Transaction Status API
    #
    
    /**
     * Transaction Status Query
     * 
     * Used to check the status of a transaction.
     *
     * @see https://developer.safaricom.co.ke/APIs/TransactionStatus
     *
     * @since 1.0.0
     *
     * @method POST
     * 
     * @param array $params Request parameters.
     *
     * @return mixed
     */ 
    public function transactionStatusQuery(array $params = [])
    {
        $endpoint           = 'mpesa/transactionstatus/v1/query';
        $Initiator          = $this->InitiatorName;
        $SecurityCredential = $this->generateSecurityCredential();    
        $ShortCode          = $this->ShortCode;

        $defaultParams = [
            'Initiator'          => $Initiator,
            'SecurityCredential' => $SecurityCredential,
            'CommandID'          => 'TransactionStatusQuery',
            'TransactionID'      => '',
            'PartyA'             => $ShortCode,
            'IdentifierType'     => 4,
            'ResultURL'          => '',
            'QueueTimeOutURL'    => '',
            'Remarks'            => 'Sending a transaction status query',
            'Occasion'           => ''
        ];

        $body = array_merge($defaultParams, $params);

        return $this->sendPostRequest([
            'endpoint' => $endpoint,
            'body' => $body
        ]);
    }
    
    #
    ## Account Balance API
    ##

    /**
     * Account Balance Query
     *
     * Used to inquire the balance on an M-PESA BuyGoods (Till Number).
     *
     * @see https://developer.safaricom.co.ke/APIs/AccountBalance
     *
     * @since 1.0.0
     * 
     * @method POST
     * 
     * @param array $params Request parameters.
     *
     * @return mixed
     */
    public function accountBalanceQuery(array $params = [])
    {
        $endpoint           = 'mpesa/accountbalance/v1/query';
        $Initiator          = $this->InitiatorName;
        $SecurityCredential = $this->generateSecurityCredential();
        $ShortCode          = $this->ShortCode;

        $defaultParams = [
            'Initiator'          => $Initiator,
            'SecurityCredential' => $SecurityCredential,
            'CommandID'          => 'AccountBalance',
            'PartyA'             => $ShortCode,
            'IdentifierType'     => 4,
            'Remarks'            => 'Sending an account balance query',
            'QueueTimeOutURL'    => '',
            'ResultURL'          => ''
        ];

        $body = array_merge($defaultParams, $params);

        return $this->sendPostRequest([
            'endpoint' => $endpoint,
            'body' => $body
        ]);
    }

    #
    ## Reversal API
    #

    /**
     * Reversal Request
     *
     * Used to reverse a transaction.
     *
     * @see https://developer.safaricom.co.ke/APIs/Reversal
     *
     * @since 1.0.0
     *
     * @method POST
     * 
     * @param array $params Request parameters.
     *
     * @return mixed
     */ 
    public function reversalRequest(array $params = [])
    {
        $endpoint           = 'mpesa/reversal/v1/request';
        $Initiator          = $this->InitiatorName;
        $SecurityCredential = $this->generateSecurityCredential();    
        $ShortCode          = $this->ShortCode;

        $defaultParams = [
            'Initiator'              => $Initiator,
            'SecurityCredential'     => $SecurityCredential,
            'CommandID'              => 'TransactionReversal',
            'TransactionID'          => '',
            'ReceiverParty'          => $ShortCode,
            'RecieverIdentifierType' => 11,
            'QueueTimeOutURL'        => '',
            'ResultURL'              => '',
            'Remarks'                => 'Sending a transaction reversal request',
            'Occasion'               => ''
        ];

        $body = array_merge($defaultParams, $params);

        return $this->sendPostRequest([
            'endpoint' => $endpoint,
            'body' => $body
        ]);
    }
    
    #
    ## Utilities
    #

    /**
     * Convenience method for sending a GET request.
     * 
     * Used for sending request to generate an OAuth access token.
     * 
     * @since 1.5.0
     *
     * @param array $options An associative array of the request headers.
     *
     * @return mixed
     */
    public function sendGetRequest(array $options = [])
    {
        return $this->sendRequest('GET', $options['endpoint'], [
            'headers' => [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . $options['credentials']
            ],
        ]);
    }

    /**
     * Convenience method for sending a POST request.
     * 
     * @since 1.5.0
     *
     * @param array $options An associative array of the request options.
     *                       Includes the request header and body parameters.
     *
     * @return mixed
     */
    public function sendPostRequest(array $options = [])
    {
        $token = $this->getAccessToken();

        return $this->sendRequest('POST', $options['endpoint'], [
            'headers' => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ],
            'body' => $options['body'],
        ]);
    }

    /**
     * Does the actual work of initializing cURL, setting the options, sending
     * an HTTP request to the specified endpoint, and grabbing the response.
     * 
     * @since 1.0.0
     * @since 1.5.0 Added $method parameter to support both GET and POST.
     * @since 1.5.0 Resets curl options so we're on a fresh slate.
     *
     * @param string $method   HTTP request method, either GET or POST.
     * @param string $endpoint Resource URL endpoint. This should be a relative
     *                         URL, it will be merged with the base URL to form 
     *                         a complete URL.
     * @param array $options   An associative array of the request options.
     *                         Includes the request header and body parameters.
     * 
     * @throws Exception
     *
     * @return mixed
     */
    private function sendRequest(string $method, string $endpoint, array $options = [])
    {
        $baseUrl = 'https://sandbox.safaricom.co.ke/';

        if ($this->Environment == 'production') {
            $baseUrl = 'https://api.safaricom.co.ke/';
        } 

        $url = $baseUrl . $endpoint;

        $ch = curl_init();

        curl_reset($ch);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);

        if (strtoupper($method) === 'POST'){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($options['body']));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) { 
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        return json_decode($response);
    }

    /**
     * Sends validation response back to the M-PESA API.
     *
     * Response codes are sent from the clients endpoints to the M-PESA gateway
     * to acknowledge that the client has received the results.
     *
     * Any response other than 0 (zero) for the 'ResultCode' during validation 
     * only means an error occurred and the transaction is cancelled.
     * 
     * @since 1.5.0
     *
     * @param boolean $status
     */
    public function sendResponse($status = true)
    {    
        $response = [
            'ResultCode' => '0',
            'ResultDesc' => 'Accepted the service request.'
        ];

        if ($status === false) {
            $response = [
                'ResultCode' => '1',
                'ResultDesc' => 'Rejected the service request.'
            ];
        }
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    /**
     * Gets the current request timestamp.
     * 
     * This is the Timestamp of the transaction, normaly in the format of 
     * YEAR+MONTH+DATE+HOUR+MINUTE+SECOND (YYYYMMDDHHMMSS). Each part should be
     * at least two digits apart from the year which takes four digits.
     * 
     * @since 1.5.0
     * 
     * @return string
     */
    private function getCurrentTimestamp()
    {
        @date_default_timezone_set('Africa/Nairobi');
        return date('Ymdhis');
    }

    /**
     * Generates a Password used with the Lipa na M-PESA Online request.
     * 
     * This is the password used for encrypting the request sent: A base64 
     * encoded string.
     *
     * @since 1.5.0
     *
     * @throws Exception
     *
     * @return string Base64 Encode(Shortcode + Passkey + Timestamp)
     */
    private function generateStkPushPassword()
    {        
        if (!isset($this->ShortCode) || empty($this->ShortCode)) {
            throw new Exception("Couldn't generate an STK Push Password. The Lipa na M-PESA Online ShortCode is not set or is empty.");
        }

        if (!isset($this->PassKey) || empty($this->PassKey)) {
            throw new Exception("Couldn't generate an STK Push Password. The Lipa na M-PESA Online PassKey is not set or is empty.");
        }

        $Shortcode = $this->ShortCode;
        $Passkey   = $this->PassKey;
        $Timestamp = self::getCurrentTimestamp();

        return base64_encode($Shortcode . $Passkey . $Timestamp);
    }
    
    /**
     * Generates Security Credential
     *
     * M-PESA Core authenticates a transaction by decrypting the security 
     * credentials. Security credentials are generated by encrypting the base64 
     * encoded initiator password with M-PESA's public key, a X509 certificate.
     *
     * @since 1.0.0
     * @since 1.5.0 Added check for is_readable().
     *
     * @throws Exception
     *
     * @return string Base64 Encode(OpenSSLEncrypt(Initiator Password + Certificate))
     */
    private function generateSecurityCredential()
    {          
        $certFile = dirname(__DIR__) . '/cer/mpesa_public_cert.cer';

        if (!file_exists($certFile)) {
            throw new Exception("The system cannot find the M-PESA certificate file: {$certFile}");
        }

        if (!is_readable($certFile)) {
            throw new Exception("The system cannot read the M-PESA certificate file: {$certFile}");
        }

        $plainText = $this->InitiatorPassword;
        $publicKey = file_get_contents($certFile);
  
        openssl_public_encrypt($plainText, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
        return base64_encode($encrypted);
    }
}
