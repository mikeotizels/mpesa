<?php
/**
 * M-PESA API
 * 
 * This is the Mikeotizels implementation of the various M-PESA APIs dubbed 
 * DARAJA APIs. 
 * 
 * @link https://github.com/mikeotizels/mpesa/
 * 
 * @package    Mikeotizels/APIs/ThirdParty/Safaricom/Mpesa
 * @author     Michael Otieno <mikeotizels@gmail.com>
 * @copyright  Copyright 2020 Michael Otieno
 * @license    Licensed under The MIT License (MIT). For the license terms, 
 *             please see the LICENSE file that was distributed with this 
 *             source code or visit <https://opensource.org/licenses/MIT>.
 * @version    1.0.0
 */ 

namespace Mikeotizels\Mpesa;

use Exception;

/**
 * Class Mpesa
 *
 * This is the M-PESA APIs access abstraction object. 
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

        $credentials = base64_encode($this->ConsumerKey . ':' . $this->ConsumerSecret);
        $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        if ($this->Environment == 'production') {
            $url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        } 

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . $credentials
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception(curl_error($ch));
        } 

        curl_close($ch);

        $response = json_decode($response);

        if (!isset($response->access_token)) {
            throw new Exception('Error making request to generate access token.');
        }
     
        return $response->access_token;
    }
    
    /**
     * Used to initiate online payment on behalf of a customer using STK Push.
     *
     * @see https://developer.safaricom.co.ke/APIs/MpesaExpressSimulate
     *
     * @since 1.0.0
     *
     * @method POST
     * 
     * @param array $params Request parameters.
     *
     * @return mixed
     */
    public function stkPushSimulate(array $params = [])
    {
        $Timestamp = date('Ymdhis');
        $Password = base64_encode($this->ShortCode . $this->PassKey . $Timestamp);

        $defaultParams = [
            'BusinessShortCode' => $this->ShortCode,
            'Password'          => $Password,
            'Timestamp'         => $Timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => '',
            'PartyA'            => '',
            'PartyB'            => $this->ShortCode,
            'PhoneNumber'       => '',
            'CallBackURL'       => '',
            'AccountReference'  => time(),
            'TransactionDesc'   => 'LNMO Payment'
        ];

        $requestParams = array_merge($defaultParams, $params);

        return $this->sendRequest('mpesa/stkpush/v1/processrequest', $requestParams);
    }

    /**
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
        $Timestamp = date('Ymdhis');
        $Password = base64_encode($this->ShortCode . $this->PassKey . $Timestamp);

        $defaultParams = [
            'BusinessShortCode' => $this->ShortCode,
            'Password'          => $Password,
            'Timestamp'         => $Timestamp,
            'CheckoutRequestID' => ''
        ];

        $requestParams = array_merge($defaultParams, $params);

        return $this->sendRequest('mpesa/stkpushquery/v1/query', $requestParams);
    }

    /**
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
        $defaultParams = [
            'ShortCode'       => $this->ShortCode,
            'ResponseType'    => 'Completed',
            'ConfirmationURL' => '',
            'ValidationURL'   => ''
        ];

        $requestParams = array_merge($defaultParams, $params);

        return $this->sendRequest('mpesa/c2b/v1/registerurl', $requestParams);
    }

    /**
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
        $defaultParams = [
            'ShortCode'     => $this->ShortCode,
            'CommandID'     => 'CustomerPayBillOnline',
            'Amount'        => '',
            'MSISDN'        => '',
            'BillRefNumber' => time()
        ];

        $requestParams = array_merge($defaultParams, $params);

        return $this->sendRequest('mpesa/c2b/v1/simulate', $requestParams);
    }
    
    /**
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
        $defaultParams = [
            'InitiatorName'      => $this->InitiatorName,
            'SecurityCredential' => $this->generateSecurityCredential(),
            'CommandID'          => 'BusinessPayment',
            'Amount'             => '',
            'PartyA'             => $this->ShortCode,
            'PartyB'             => '',
            'Remarks'            => 'Sending a B2C payment request',
            'QueueTimeOutURL'    => '',
            'ResultURL'          => '',
            'Occasion'           => ''
        ];

        $requestParams = array_merge($defaultParams, $params);

        return $this->sendRequest('mpesa/b2c/v1/paymentrequest', $requestParams);
    }

    /**
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
        $defaultParams = [
            'Initiator'              => $this->InitiatorName,
            'SecurityCredential'     => $this->generateSecurityCredential(),
            'CommandID'              => 'BusinessPayBill',
            'SenderIdentifierType'   => 4,
            'RecieverIdentifierType' => 4,
            'Amount'                 => '',
            'PartyA'                 => $this->ShortCode,
            'PartyB'                 => '',
            'AccountReference'       => time(),
            'Remarks'                => 'Sending a B2B payment request',
            'QueueTimeOutURL'        => '',
            'ResultURL'              => ''
        ];

        $requestParams = array_merge($defaultParams, $params);

        return $this->sendRequest('mpesa/b2b/v1/paymentrequest', $requestParams);
    }
    
    /**
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
        $defaultParams = [
            'Initiator'          => $this->InitiatorName,
            'SecurityCredential' => $this->generateSecurityCredential(),
            'CommandID'          => 'TransactionStatusQuery',
            'TransactionID'      => '',
            'PartyA'             => $this->ShortCode,
            'IdentifierType'     => 4,
            'ResultURL'          => '',
            'QueueTimeOutURL'    => '',
            'Remarks'            => 'Sending a transaction status query',
            'Occasion'           => ''
        ];

        $requestParams = array_merge($defaultParams, $params);

        return $this->sendRequest('mpesa/transactionstatus/v1/query', $requestParams);
    }

    /**
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
        $defaultParams = [
            'Initiator'          => $this->InitiatorName,
            'SecurityCredential' => $this->generateSecurityCredential(),
            'CommandID'          => 'AccountBalance',
            'PartyA'             => $this->ShortCode,
            'IdentifierType'     => 4,
            'Remarks'            => 'Sending an account balance query',
            'QueueTimeOutURL'    => '',
            'ResultURL'          => ''
        ];

        $requestParams = array_merge($defaultParams, $params);

        return $this->sendRequest('mpesa/accountbalance/v1/query', $requestParams);
    }

    /**
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
        $defaultParams = [
            'Initiator'              => $this->InitiatorName,
            'SecurityCredential'     => $this->generateSecurityCredential(),
            'CommandID'              => 'TransactionReversal',
            'TransactionID'          => '',
            'ReceiverParty'          => $this->ShortCode,
            'RecieverIdentifierType' => 11,
            'QueueTimeOutURL'        => '',
            'ResultURL'              => '',
            'Remarks'                => 'Sending a transaction reversal request',
            'Occasion'               => ''
        ];

        $requestParams = array_merge($defaultParams, $params);

        return $this->sendRequest('mpesa/reversal/v1/request', $requestParams);
    }

    /**
     * Sends an HTTP request to the specified endpoint.
     * 
     * @since 1.0.0
     *
     * @param string $endpoint Resource endpoint. 
     * @param array  $params   Request parameters.
     * 
     * @throws Exception
     *
     * @return mixed
     */
    private function sendRequest(string $endpoint, array $params = [])
    {
        $baseUrl = 'https://sandbox.safaricom.co.ke/';

        if ($this->Environment == 'production') {
            $baseUrl = 'https://api.safaricom.co.ke/';
        } 

        $url = $baseUrl . $endpoint;
        $token = $this->getAccessToken();
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception(curl_error($ch));
        } 

        curl_close($ch);

        return json_encode($response);
    }
    
    /**
     * Generates Security Credential
     *
     * M-PESA Core authenticates a transaction by decrypting the security 
     * credentials. Security credentials are generated by encrypting the base64 
     * encoded initiator password with M-PESA's public key, a X509 certificate.
     *
     * @since 1.0.0
     *
     * @throws Exception
     *
     * @return string Base64 Encode(OpenSSLEncrypt(Initiator Password + Certificate))
     */
    private function generateSecurityCredential()
    {          
        $certFile = dirname(__DIR__) . '/cer/mpesa_public_cert.cer';

        if (!file_exists($certFile)) {
            throw new Exception("The M-PESA certificate file '{$certFile}' can't be found.");
        }

        $plainText = $this->InitiatorPassword;
        $publicKey = file_get_contents($certFile);
  
        openssl_public_encrypt($plainText, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
        return base64_encode($encrypted);
    }
}