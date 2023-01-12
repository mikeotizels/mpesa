<?php
/**
 * M-PESA API: Mpesa Class 
 *
 * This script implements the various Safaricom's M-PESA REST API dubbed DARAJA
 * API. It's documentation is available online at Safaricom Developers' Portal.
 * @see <https://developer.safaricom.co.ke>
 * 
 * @link https://github.com/mikeotizels/mpesa/lite/
 * 
 * @category  M-PESA API Integration
 * @package   Mikeotizels/API/ThirdParty/Safaricom/Mpesa
 * @author    Michael Otieno <mikeotizels@gmail.com>
 * @copyright Copyright 2022-2023 Michael Otieno
 * @license   Licensed under The MIT License (MIT). For the license terms, 
 *            please see the LICENSE file that was distributed with this 
 *            source code or visit <https://opensource.org/licenses/MIT>.
 * @version   1.5.0 / Daraja v2.0
 */ 

// NOTICE: THIS SCRIPT IS STILL UNDER DEVELOPMENT!

namespace Mikeotizels\Mpesa;

use DomainException;
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
    const CERT_FILE_PRODUCTION = 'mpesa_production_cert.cer';
    const CERT_FILE_SANDBOX = 'mpesa_sandbox_cert.cer';
    const CERT_FILE_PUBLIC = 'mpesa_public_cert.cer';

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
     * Constructor
     *
     * @since 1.2.0
     */
    public function __construct()
    {
        /**
         * Missing functionality.
         * 
         * @since 1.5.0
         *
         * @throws Exception
         */
        if (!extension_loaded('curl')) {
            throw new Exception('The required cURL PHP Extension was not found on your server. Without the curl extension, the Mikeotizels M-PESA API is unable to send remote requests to the Safaricom M-PESA service.');
        }
    }

    /**
     * Destructor
     *
     * @since 1.2.0
     */
    #public function __destruct()
    #{
        #$this->closeTransaction();
    #}

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
            throw new \Exception("The M-PESA App Consumer Key is not set or is empty.");
        }

        if (!isset($this->ConsumerSecret) || empty($this->ConsumerSecret)) {
            throw new \Exception("The M-PESA App Consumer Secret is not set or is empty.");
        }

        $endpoint    = 'oauth/v1/generate?grant_type=client_credentials';
        $credentials = base64_encode($this->ConsumerKey . ':' . $this->ConsumerSecret);

        $response = $this->makeGetRequest([
            'endpoint' => $endpoint,
            'credentials' => $credentials
        ]);

        if (!empty($response->errorCode)){
             throw new \Exception(self::jsonEncode($response));
        }

        if (!isset($response->access_token)) {
            throw new \Exception('Error making request to generate access token.');
        }
            
        if (empty($response->access_token)) {
            throw new \Exception('The server returned an empty access token.');
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
        $Password  = self::generateStkPushPassword();
        $Timestamp = self::getCurrentTimestamp();

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
            'TransactionDesc'   => ''
        ];

        $body = array_merge($defaultParams, $params);

        if (empty($body['PartyA']) && !empty($body['PhoneNumber'])) {
            $body['PartyA'] = $body['PhoneNumber'];
        }

        if (empty($body['PhoneNumber']) && !empty($body['PartyA'])) {
            $body['PhoneNumber'] = $body['PartyA'];
        }

        return self::makePostRequest([
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
        $Password  = self::generateStkPushPassword();
        $Timestamp = self::getCurrentTimestamp();

        $defaultParams = [
            'BusinessShortCode' => $ShortCode,
            'Password'          => $Password,
            'Timestamp'         => $Timestamp,
            'CheckoutRequestID' => ''
        ];

        $body = array_merge($defaultParams, $params);

        return self::makePostRequest([
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
     * @since 1.5.0 Migrated from the C2B v1 endpoint to C2B v2 endpoint.
     *
     * @method POST
     * 
     * @param array $params Request parameters.
     *
     * @return mixed
     */ 
    public function c2bRegisterURL(array $params = [])
    {
        $endpoint  = 'mpesa/c2b/v2/registerurl';
        $ShortCode = $this->ShortCode;

        $defaultParams = [
            'ShortCode'       => $ShortCode,
            'ResponseType'    => 'Completed',
            'ConfirmationURL' => '',
            'ValidationURL'   => ''
        ];

        $body = array_merge($defaultParams, $params);

        return self::makePostRequest([
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
     * @since 1.5.0 Migrated from the C2B v1 endpoint to C2B v2 endpoint.
     *
     * @method POST
     * 
     * @param array $params Request parameters.
     *
     * @return mixed
     */ 
    public function c2bSimulate(array $params = [])
    { 
        $endpoint  = 'mpesa/c2b/v2/simulate';
        $ShortCode = $this->ShortCode;

        $defaultParams = [
            'ShortCode'     => $ShortCode,
            'CommandID'     => 'CustomerPayBillOnline',
            'Amount'        => '',
            'MSISDN'        => '',
            'BillRefNumber' => ''
        ];

        $body = array_merge($defaultParams, $params);

        return self::makePostRequest([
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
        $SecurityCredential = self::generateSecurityCredential();    
        $ShortCode          = $this->ShortCode;

        $defaultParams = [
            'InitiatorName'      => $InitiatorName,
            'SecurityCredential' => $SecurityCredential,
            'CommandID'          => '',
            'Amount'             => '',
            'PartyA'             => $ShortCode,
            'PartyB'             => '',
            'Remarks'            => '',
            'QueueTimeOutURL'    => '',
            'ResultURL'          => '',
            'Occasion'           => ''
        ];

        $body = array_merge($defaultParams, $params);

        return self::makePostRequest([
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
        $SecurityCredential = self::generateSecurityCredential();    
        $ShortCode          = $this->ShortCode;

        $defaultParams = [
            'Initiator'              => $Initiator,
            'SecurityCredential'     => $SecurityCredential,
            'CommandID'              => '',
            'SenderIdentifierType'   => 4,
            'RecieverIdentifierType' => 4,
            'Amount'                 => '',
            'PartyA'                 => $ShortCode,
            'PartyB'                 => '',
            'AccountReference'       => time(),
            'Remarks'                => '',
            'QueueTimeOutURL'        => '',
            'ResultURL'              => ''
        ];

        $body = array_merge($defaultParams, $params);

        return self::makePostRequest([
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
     * Use to check the status of a transaction.
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
        $SecurityCredential = self::generateSecurityCredential();    
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
            'Remarks'            => '',
            'Occasion'           => ''
        ];

        $body = array_merge($defaultParams, $params);

        return self::makePostRequest([
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
        $SecurityCredential = self::generateSecurityCredential();
        $ShortCode          = $this->ShortCode;

        $defaultParams = [
            'Initiator'          => $Initiator,
            'SecurityCredential' => $SecurityCredential,
            'CommandID'          => 'AccountBalance',
            'PartyA'             => $ShortCode,
            'IdentifierType'     => 4,
            'Remarks'            => '',
            'QueueTimeOutURL'    => '',
            'ResultURL'          => ''
        ];

        $body = array_merge($defaultParams, $params);

        return self::makePostRequest([
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
        $SecurityCredential = self::generateSecurityCredential();    
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
            'Remarks'                => '',
            'Occasion'               => ''
        ];

        $body = array_merge($defaultParams, $params);

        return self::makePostRequest([
            'endpoint' => $endpoint,
            'body' => $body
        ]);
    }
    
    #
    #### Callback
    #
    # Gets the callback results received once the request has been processed 
    # by M-PESA.
    #
    
    /**
     * M-PESA STK Push Process Request Callback 
     *
     * Get the Lipa na M-PESA Online payment request response data posted in the 
     * callback URL.     
     *
     * @since 1.2.0
     *
     * @return mixed 
     */
    public function getStkPushRequestResult()
    {
        $callbackJSON = file_get_contents('php://input');
        $callbackData = json_decode($callbackJSON);

        if (empty($callbackData)) {
            return null;
        }

        // Body
        $MerchantRequestID = $callbackData->Body->stkCallback->MerchantRequestID;
        $CheckoutRequestID = $callbackData->Body->stkCallback->CheckoutRequestID;
        $ResultCode = $callbackData->Body->stkCallback->ResultCode;
        $ResultDesc = $callbackData->Body->stkCallback->ResultDesc;        
        
        // Callback Metadata
        $Amount = $callbackData->Body->stkCallback->CallbackMetadata->Item[0]->Value;
        $MpesaReceiptNumber = $callbackData->Body->stkCallback->CallbackMetadata->Item[1]->Value;
        $Balance = $callbackData->Body->stkCallback->CallbackMetadata->Item[2]->Value;
        $TransactionDate = $callbackData->Body->stkCallback->CallbackMetadata->Item[3]->Value;
        $PhoneNumber = $callbackData->Body->stkCallback->CallbackMetadata->Item[4]->Value;

        // Callback Array
        $callbackArray = [        
            "MerchantRequestID"  => $MerchantRequestID,
            "CheckoutRequestID"  => $CheckoutRequestID,
            "ResultCode"         => $ResultCode,
            "ResultDesc"         => $ResultDesc,
            "Amount"             => $Amount,
            "MpesaReceiptNumber" => $MpesaReceiptNumber,
            "Balance"            => $Balance,
            "TransactionDate"    => $TransactionDate,
            "PhoneNumber"        => $PhoneNumber
        ];

        return self::jsonDecode($callbackArray);
    }

    /**
     * M-PESA STKPush Query Query Callback 
     *
     * Get the Lipa na M-PESA Online query request response data posted in the 
     * callback URL.     
     *
     * @since 1.2.0
     *
     * @return mixed 
     */
    public function getStkPushQueryResult()
    {
        $callbackJSON = file_get_contents('php://input');
        $callbackData = json_decode($callbackJSON);

        if (empty($callbackData)) {
            return null;
        }

        // Response
        $ResponseCode = $callbackData->ResponseCode;
        $ResponseDescription = $callbackData->ResponseDescription;
        $MerchantRequestID = $callbackData->MerchantRequestID;
        $CheckoutRequestID = $callbackData->CheckoutRequestID;
        $ResultCode = $callbackData->ResultCode;
        $ResultDesc = $callbackData->ResultDesc;
        
        // Callback Array
        $callbackArray = [
            "ResponseCode"        => $ResponseCode,
            "ResponseDescription" => $ResponseDescription,
            "MerchantRequestID"   => $MerchantRequestID,
            "CheckoutRequestID"   => $CheckoutRequestID,
            "ResultCode"          => $ResultCode,
            "ResultDesc"          => $ResultDesc
        ];

        return self::jsonDecode($callbackArray);
    }

    /**
     * M-PESA C2B Simulate Callback 
     *
     * Get the C2B simulate confirmation and validation response data posted in 
     * the confirmation and validation URL.     
     *
     * @since 1.2.0
     * 
     * @return mixed  
     */
    public function getC2bSimulateResult()
    {
        $callbackJSON = file_get_contents('php://input');
        $callbackData = json_decode($callbackJSON);

        if (empty($callbackData)) {
            return null;
        }

        // Response
        $TransactionType = $callbackData->TransactionType;
        $TransID = $callbackData->TransID;
        $TransTime = $callbackData->TransTime;
        $TransAmount = $callbackData->TransAmount;
        $BusinessShortCode = $callbackData->BusinessShortCode;
        $BillRefNumber = $callbackData->BillRefNumber;
        $InvoiceNumber = $callbackData->InvoiceNumber;
        $OrgAccountBalance = $callbackData->OrgAccountBalance;
        $ThirdPartyTransID = $callbackData->ThirdPartyTransID;
        $MSISDN = $callbackData->MSISDN;
        $FirstName = $callbackData->FirstName;
        $MiddleName = $callbackData->MiddleName;
        $LastName = $callbackData->LastName;

        // Response Array
        $responseArray = [        
            "TransactionType"   => $TransactionType,
            "TransID"           => $TransID,
            "TransTime"         => $TransTime,
            "TransAmount"       => $TransAmount,
            "BusinessShortCode" => $BusinessShortCode,
            "BillRefNumber"     => $BillRefNumber,
            "InvoiceNumber"     => $InvoiceNumber,
            "OrgAccountBalance" => $OrgAccountBalance,
            "ThirdPartyTransID" => $ThirdPartyTransID,
            "MSISDN"            => $MSISDN,
            "FirstName"         => $FirstName,
            "MiddleName"        => $MiddleName,
            "LastName"          => $LastName
        ];

        return self::jsonDecode($responseArray);
    }

    /**
     * M-PESA B2C Payment Request Callback 
     *
     * Get the B2C payment request response data posted in the result URL.     
     *
     * @since 1.2.0
     *                              
     * @return mixed 
     */
    public function getB2cPaymentRequestResult()
    {
        $callbackJSON = file_get_contents('php://input');
        $callbackData = json_decode($callbackJSON);

        if (empty($callbackData)) {
            return null;
        }
        
        // Result
        $ResultType = $callbackData->Result->ResultType;
        $ResultCode = $callbackData->Result->ResultCode;
        $ResultDesc = $callbackData->Result->ResultDesc;
        $OriginatorConversationID = $callbackData->Result->OriginatorConversationID;
        $ConversationID = $callbackData->Result->ConversationID;
        $TransactionID = $callbackData->Result->TransactionID;
        
        // Result Parameters
        $TransactionReceipt = $callbackData->Result->ResultParameters->ResultParameter[0]->Value;
        $TransactionAmount = $callbackData->Result->ResultParameters->ResultParameter[1]->Value;
        $B2CWorkingAccountAvailableFunds = $callbackData->Result->ResultParameters->ResultParameter[2]->Value;
        $B2CUtilityAccountAvailableFunds = $callbackData->Result->ResultParameters->ResultParameter[3]->Value;
        $TransactionCompletedDateTime = $callbackData->Result->ResultParameters->ResultParameter[4]->Value;
        $ReceiverPartyPublicName = $callbackData->Result->ResultParameters->ResultParameter[5]->Value;
        $B2CChargesPaidAccountAvailableFunds = $callbackData->Result->ResultParameters->ResultParameter[6]->Value;
        $B2CRecipientIsRegisteredCustomer = $callbackData->Result->ResultParameters->ResultParameter[7]->Value;

        // Reference Data


        // Result Array
        $resultArray = [
            "ResultType"                          => $ResultType,
            "ResultCode"                          => $ResultCode,
            "ResultDesc"                          => $ResultDesc,
            "OriginatorConversationID"            => $OriginatorConversationID,
            "ConversationID"                      => $ConversationID,
            "TransactionID"                       => $TransactionID,
            "TransactionReceipt"                  => $TransactionReceipt,
            "TransactionAmount"                   => $TransactionAmount,
            "B2CWorkingAccountAvailableFunds"     => $B2CWorkingAccountAvailableFunds,
            "B2CUtilityAccountAvailableFunds"     => $B2CUtilityAccountAvailableFunds,
            "TransactionCompletedDateTime"        => $TransactionCompletedDateTime,
            "ReceiverPartyPublicName"             => $ReceiverPartyPublicName,
            "B2CChargesPaidAccountAvailableFunds" => $B2CChargesPaidAccountAvailableFunds,
            "B2CRecipientIsRegisteredCustomer"    => $B2CRecipientIsRegisteredCustomer
        ];

        return self::jsonDecode($resultArray);
    }

    /**
     * M-PESA B2B Payment Request Callback 
     *
     * Get the B2B payment request response data posted in the result URL.     
     *
     * @since 1.2.0
     * 
     * @return mixed 
     */
    public function getB2bPaymentRequestResult()
    {
        $callbackJSON = file_get_contents('php://input');
        $callbackData = json_decode($callbackJSON);

        if (empty($callbackData)) {
            return null;
        }

        // Result
        $ResultType = $callbackData->Result->ResultType;
        $ResultCode = $callbackData->Result->ResultCode;
        $ResultDesc = $callbackData->Result->ResultDesc;
        $OriginatorConversationID = $callbackData->Result->OriginatorConversationID;
        $ConversationID = $callbackData->Result->ConversationID;
        $TransactionID = $callbackData->Result->TransactionID;

        // Result Parameters
        $InitiatorAccountCurrentBalance = $callbackData->Result->ResultParameters->ResultParameter[0]->Value;
        $DebitAccountCurrentBalance = $callbackData->Result->ResultParameters->ResultParameter[1]->Value;
        $Amount = $callbackData->Result->ResultParameters->ResultParameter[2]->Value;
        $DebitPartyAffectedAccountBalance = $callbackData->Result->ResultParameters->ResultParameter[3]->Value;
        $TransCompletedTime = $callbackData->Result->ResultParameters->ResultParameter[4]->Value;
        $DebitPartyCharges = $callbackData->Result->ResultParameters->ResultParameter[5]->Value;
        $ReceiverPartyPublicName = $callbackData->Result->ResultParameters->ResultParameter[6]->Value;
        $Currency = $callbackData->Result->ResultParameters->ResultParameter[7]->Value;

        // Reference Data


        // Result Array
        $resultArray = [
            "ResultType"                       => $ResultType,
            "ResultCode"                       => $ResultCode,
            "ResultDesc"                       => $ResultDesc,
            "OriginatorConversationID"         => $OriginatorConversationID,
            "ConversationID"                   => $ConversationID,
            "TransactionID"                    => $TransactionID,
            "InitiatorAccountCurrentBalance"   => $InitiatorAccountCurrentBalance,
            "DebitAccountCurrentBalance"       => $DebitAccountCurrentBalance,
            "Amount"                           => $Amount,
            "DebitPartyAffectedAccountBalance" => $DebitPartyAffectedAccountBalance,
            "TransCompletedTime"               => $TransCompletedTime,
            "DebitPartyCharges"                => $DebitPartyCharges,
            "ReceiverPartyPublicName"          => $ReceiverPartyPublicName,
            "Currency"                         => $Currency
        ];

        return self::jsonDecode($resultArray);
    }

    /**
     * M-PESA Transaction Status Query Callback 
     *
     * Get the transaction status query response data posted in the result URL.     
     *
     * @since 1.2.0
     *
     * @return mixed 
     */
    public function getTransactionStatusQueryResult()
    {
        $callbackJSON = file_get_contents('php://input');
        $callbackData = json_decode($callbackJSON);

        if (empty($callbackData)) {
            return null;
        }

        // Result
        $ResultType = $callbackData->Result->ResultType;
        $ResultCode = $callbackData->Result->ResultCode;
        $ResultDesc = $callbackData->Result->ResultDesc;
        $OriginatorConversationID = $callbackData->Result->OriginatorConversationID;
        $ConversationID = $callbackData->Result->ConversationID;
        $TransactionID = $callbackData->Result->TransactionID;

        // Result Parameters
        $ReceiptNo = $callbackData->Result->ResultParameters->ResultParameter[0]->Value;
        $Conversation_ID = $callbackData->Result->ResultParameters->ResultParameter[1]->Value;        
        $FinalisedTime = $callbackData->Result->ResultParameters->ResultParameter[2]->Value;
        $Amount = $callbackData->Result->ResultParameters->ResultParameter[3]->Value;
        $TransactionStatus = $callbackData->Result->ResultParameters->ResultParameter[4]->Value;
        $ReasonType = $callbackData->Result->ResultParameters->ResultParameter[5]->Value;
        $TransactionReason = $callbackData->Result->ResultParameters->ResultParameter[6]->Value;
        $DebitPartyCharges = $callbackData->Result->ResultParameters->ResultParameter[7]->Value;
        $DebitAccountType = $callbackData->Result->ResultParameters->ResultParameter[8]->Value;
        $InitiatedTime = $callbackData->Result->ResultParameters->ResultParameter[9]->Value;
        $Originator_Conversation_ID = $callbackData->Result->ResultParameters->ResultParameter[10]->Value;
        $CreditPartyName = $callbackData->Result->ResultParameters->ResultParameter[11]->Value;
        $DebitPartyName = $callbackData->Result->ResultParameters->ResultParameter[12]->Value;

        // Reference Data


        // Result Array
        $resultArray = [        
            "ResultType"                 => $ResultType,
            "ResultCode"                 => $ResultCode,
            "ResultDesc"                 => $ResultDesc,
            "OriginatorConversationID"   => $OriginatorConversationID,
            "ConversationID"             => $ConversationID,
            "TransactionID"              => $TransactionID,        
            "ReceiptNo"                  => $ReceiptNo,
            "Conversation ID"            => $Conversation_ID,
            "FinalisedTime"              => $FinalisedTime,
            "Amount"                     => $Amount,
            "TransactionStatus"          => $TransactionStatus,
            "ReasonType"                 => $ReasonType,
            "TransactionReason"          => $TransactionReason,
            "DebitPartyCharges"          => $DebitPartyCharges,
            "DebitAccountType"           => $DebitAccountType,
            "InitiatedTime"              => $InitiatedTime,
            "Originator Conversation ID" => $Originator_Conversation_ID,
            "CreditPartyName"            => $CreditPartyName,
            "DebitPartyName"             => $DebitPartyName
        ];

        return self::jsonDecode($resultArray);
    }

    /**
     * M-PESA Account Balance Query Callback 
     *
     * Get the account balance query response data posted in the result URL.     
     *
     * @since 1.2.0
     *
     * @return mixed 
     */
    public function getAccountBalanceQueryResult()
    {
        $callbackJSON = file_get_contents('php://input');
        $callbackData = json_decode($callbackJSON);

        if (empty($callbackData)) {
            return null;
        }

        // Result
        $ResultType = $callbackData->Result->ResultType;
        $ResultCode = $callbackData->Result->ResultCode;
        $ResultDesc = $callbackData->Result->ResultDesc;
        $OriginatorConversationID = $callbackData->Result->OriginatorConversationID;
        $ConversationID = $callbackData->Result->ConversationID;
        $TransactionID = $callbackData->Result->TransactionID;        

        // Result Parameters
        $AccountBalance = $callbackData->Result->ResultParameters->ResultParameter[0]->Value;
        $BOCompletedTime = $callbackData->Result->ResultParameters->ResultParameter[1]->Value;

        // Reference Data


        // Result Array
        $resultArray = [
            "ResultType"               => $ResultType,
            "ResultCode"               => $ResultCode,
            "ResultDesc"               => $ResultDesc,
            "OriginatorConversationID" => $OriginatorConversationID,
            "ConversationID"           => $ConversationID,
            "TransactionID"            => $TransactionID,
            "AccountBalance"           => $AccountBalance,
            "BOCompletedTime"          => $BOCompletedTime,
        ];

        return self::jsonDecode($resultArray);
    }

    /**
     * M-PESA Reversal Request Callback 
     *
     * Get the reversal request response data posted in the result URL.     
     *
     * @since 1.2.0
     *
     * @return mixed 
     */
    public function getReversalRequestResult()
    {
        $callbackJSON = file_get_contents('php://input');
        $callbackData = json_decode($callbackJSON);

        if (empty($callbackData)) {
            return null;
        }

        // Result
        $ResultType = $callbackData->Result->ResultType;
        $ResultCode = $callbackData->Result->ResultCode;
        $ResultDesc = $callbackData->Result->ResultDesc;
        $OriginatorConversationID = $callbackData->Result->OriginatorConversationID;
        $ConversationID = $callbackData->Result->ConversationID;
        $TransactionID = $callbackData->Result->TransactionID;

        // Reference Data


        // Result Array
        $resultArray = [
            "ResultType"               => $ResultType,
            "ResultCode"               => $ResultCode,
            "ResultDesc"               => $ResultDesc,
            "OriginatorConversationID" => $OriginatorConversationID,
            "ConversationID"           => $ConversationID,
            "TransactionID"            => $TransactionID
        ];

        return self::jsonDecode($resultArray);
    }
    
    #
    ## Transaction Confirmation 
    #

    /**
     * With this method, we save the transaction in our database.
     *
     * @since 1.0.0
     *
     * @param
     */
    #public function saveTransaction() {
    #} 

    #
    ## Transaction Response 
    #

    /**
     * Sends validation response back to the M-PEAS API.
     *
     * Response codes are sent from the clients endpoints to the M-PESA gateway
     * to acknowledge that the client has received the results.
     *
     * Any response other than 0 (zero) for the 'ResultCode' during Validation 
     * only means an error occurred and the transaction is cancelled.
     * 
     * @since 1.0.0
     *
     * @param boolean $status
     */
    public function finishTransaction($status = true)
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
        echo self::jsonEncode($response);
    }

    /**
     * Closes any open M-PESA transaction session nicely if one exists.
     * 
     * @since 1.0.0
     */
    #public function closeTransaction() 
    #{
    #}


    #
    #### Utilities
    #

    /**
     * Convenience method for sending a GET request.
     * 
     * @since 1.5.0
     * 
     * Used for generating an OAuth access token.
     *
     * @param array $options An associative array of the request headers.
     * 
     * @todo @return ResponseInterface
     *
     * @return mixed
     */
    public function makeGetRequest(array $options = [])
    {
        return $this->makeRequest('GET', $options['endpoint'], [
            'headers' => [
                'Authorization: Basic ' . $options['credentials'],
                'Content-Type: application/json',
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
     * @todo @return ResponseInterface
     *
     * @return mixed
     */
    public function makePostRequest(array $options = [])
    {
        $token = $this->getAccessToken();

        return $this->makeRequest('POST', $options['endpoint'], [
            'headers' => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            'body' => $options['body'],
        ]);
    }

    /**
     * Does the actual work of initializing cURL, setting the options, sending
     * an HTTP request to the specified endpoint, and grabbing the response.
     * 
     * @since 1.2.0
     * @since 1.5.0 Added the $method parameter.
     *
     * @param string $method   HTTP request method, either GET or POST.
     * @param string $endpoint Resource URL endpoint. This should be a relative
     *                         URL, it will be merged with $this->baseUrl to 
     *                         form a complete URL.
     * @param array $options   An associative array of the request options.
     *                         Includes the request header and body parameters.
     * 
     * @throws MpesaException
     * @throws Exception
     * 
     * @throws Exception
     *
     * @return mixed
     */
    private function makeRequest(string $method, string $endpoint, array $options = [])
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
            curl_setopt($ch, CURLOPT_POSTFIELDS, self::jsonEncode($options['body']));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) { 
            self::curlError(curl_errno($ch), curl_error($ch));
        }

        if ($httpCode != 200) {
            throw new \Exception($response, $httpCode);
        }  

        curl_close($ch);

        return self::jsonDecode($response);
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
        $plainText = $this->InitiatorPassword;
        $publicKey = '';
        $certFile = self::CERT_FILE_PUBLIC;

        if ($this->Environment == "development") {
            $certFile = self::CERT_FILE_SANDBOX;
        } 
        #elseif ($this->Environment == "production") {
            #$certFile = self::CERT_FILE_PRODUCTION;
        #}

        $publicKeyFile = dirname(__DIR__) . '/cer/' . $certFile;

        if (!file_exists($publicKeyFile)) {
            throw new \Exception("The M-PESA API certificate file '{$certFile}' can't be found in the certificate directory.");
        }

        if (!is_readable($publicKeyFile)) {
            throw new \Exception("The M-PESA API certificate file '{$certFile}' can't be read by the server.");
        }

        $publicKey = file_get_contents($publicKeyFile);
  
        openssl_public_encrypt($plainText, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
        return base64_encode($encrypted);
    }

    /**
     * Generates a Password used with the Lipa na M-PESA Online request.
     * 
     * This is the password used for encrypting the request sent: A base64 
     * encoded string.
     *
     * @since 1.2.0
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
     * Gets the current request timestamp.
     * 
     * This is the Timestamp of the transaction, normaly in the format of 
     * YEAR+MONTH+DATE+HOUR+MINUTE+SECOND (YYYYMMDDHHMMSS). Each part should be
     * at least two digits apart from the year which takes four digits.
     * 
     * @since 1.2.0
     * 
     * @return string
     */
    public function getCurrentTimestamp()
    {
        @date_default_timezone_set('Africa/Nairobi');
        return date('Ymdhis');
    }

    /**
     * Decode a JSON string into a PHP object.
     * 
     * @since 1.5.0
     *
     * @param string $input JSON string
     *
     * @return mixed The decoded JSON string
     *
     * @throws DomainException Provided string was invalid JSON
     */
    public static function jsonDecode(string $input): mixed
    {
        $obj = \json_decode($input, false, 512, JSON_BIGINT_AS_STRING);

        if ($errno = \json_last_error()) {
            self::jsonError($errno);
        } elseif ($obj === null && $input !== 'null') {
            throw new DomainException('JSON Decode Error: Null result with non-null input');
        }
        return $obj;
    }

    /**
     * Encode a PHP array into a JSON string.
     * 
     * @since 1.5.0
     *
     * @param array<mixed> $input A PHP array
     *
     * @return string|false JSON representation of the PHP array.
     *
     * @throws DomainException Provided object could not be encoded.
     */
    public static function jsonEncode(array $input): string|false
    {
        if (PHP_VERSION_ID >= 50400) {
            $json = \json_encode($input, \JSON_UNESCAPED_SLASHES);
        } else {
            // PHP 5.3 only
            $json = \json_encode($input);
        }

        if ($errno = \json_last_error()) {
            self::jsonError($errno);
        } elseif ($json === 'null' && $input !== null) {
            throw new DomainException('JSON Encode Error: Null result with non-null input');
        }

        return $json;
    }    

    /**
     * Helper method to create a JSON error.
     * 
     * @since 1.5.0
     *
     * @param integer $errno An error number from json_last_error()
     *
     * @throws DomainException
     *
     * @return void
     */
    private static function jsonError(int $errno): void
    {
        $messages = [
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters' //PHP >= 5.3.3
        ];
        throw new DomainException(
            isset($messages[$errno]) ? $messages[$errno] : 'Unknown JSON Error: ' . $errno
        );
    }

    /**
     * Helper method to create a cURL error.
     * 
     * @since 1.5.0
     *
     * @param integer $errno An error number from curl_errno(). Not used.
     * @param string  $error An error message from curl_error().
     *
     * @throws Exception
     *
     * @return void
     */
    private static function curlError(int $errno, $error): void
    {
        throw new Exception("cURL Error: $error");
    }
}