<?php
/**
 * M-PESA API Callback
 * 
 * @package   Mikeotizels/Mpesa
 * @author    Michael Otieno <mikeotizels@gmail.com>
 * @copyright Copyright 2022-2024 Michael Otieno
 * @license   Licensed under The MIT License (MIT). For the license terms, 
 *            please see the LICENSE file that was distributed with this 
 *            source code or visit <https://opensource.org/licenses/MIT>.
 */

namespace Mikeotizels\Mpesa;

/**
 * Class Callback
 * 
 * Convenience class for handling the M-PESA API webhooks.
 * 
 * Used to get the result received once a request has been processed by M-PESA.
 * Also use to send a response back to the M-PESA API to complete a transaction
 * or acknowledge receipt of payment result.
 * 
 * Please note that any logic written in this script might not be specific to 
 * your application or database. You can extend this class in your backed and 
 * implement your logic however you would wish, like saving transaction to the 
 * database and sending the feedback to the user.
 *
 * @since 1.5.0
 */
class Callback
{ 
    //-------------------------------------------------------------------------
    // Result Methods
    //-------------------------------------------------------------------------

    /**
     * STK Push Simulate Result
     *
     * Gets the Lipa na M-PESA Online payment result posted in the callback URL.     
     *
     * @since 1.5.0
     * @since 2.0.0 Added check for successful transactions which returns 
     *              different callback metadata.
     *
     * @return string|null 
     */
    public function getStkPushSimulateResult()
    {
        $callbackData = $this->getData();

        if (empty($callbackData)) {
            return null;
        }

        // Body
        $merchantRequestId  = $callbackData->Body->stkCallback->MerchantRequestID;
        $checkoutRequestId  = $callbackData->Body->stkCallback->CheckoutRequestID;
        $resultCode         = $callbackData->Body->stkCallback->ResultCode;
        $resultDesc         = $callbackData->Body->stkCallback->ResultDesc;
        $metadataItems      = null;        
        
        // Callback Metadata
        $amount             = '';
        $mpesaReceiptNumber = '';
        $balance            = '';
        $transactionDate    = '';
        $phoneNumber        = '';

        // successful transaction
        if ($resultCode == 0) { 
            if (isset($callbackData->Body->stkCallback->CallbackMetadata->Item)) {
                $metadataItems      = $callbackData->Body->stkCallback->CallbackMetadata->Item;
                $amount             = $this->getValueByName($metadataItems, 'Amount');
                $mpesaReceiptNumber = $this->getValueByName($metadataItems, 'MpesaReceiptNumber');
                $balance            = $this->getValueByName($metadataItems, 'Balance');
                $transactionDate    = $this->getValueByName($metadataItems, 'TransactionDate');
                $phoneNumber        = $this->getValueByName($metadataItems, 'PhoneNumber');
            }
        }

        return json_encode([        
            'MerchantRequestID'  => $merchantRequestId,
            'CheckoutRequestID'  => $checkoutRequestId,
            'ResultCode'         => $resultCode,
            'ResultDesc'         => $resultDesc,
            'Amount'             => $amount,
            'MpesaReceiptNumber' => $mpesaReceiptNumber,
            'Balance'            => $balance,
            'TransactionDate'    => $transactionDate,
            'PhoneNumber'        => $phoneNumber
        ]);
    }

    /**
     * C2B Payment Result 
     *
     * Gets the C2B payment result posted in the registered Validation and 
     * Confirmation URLs.     
     *
     * @since 1.5.0
     * 
     * @return string|null  
     */
    public function getC2bPaymentResult()
    {
        $callbackData = $this->getData();

        if (empty($callbackData)) {
            return null;
        }

        $transactionType   = $callbackData->TransactionType;
        $transId           = $callbackData->TransID;
        $transTime         = $callbackData->TransTime;
        $transAmount       = $callbackData->TransAmount;
        $businessShortCode = $callbackData->BusinessShortCode;
        $billRefNumber     = $callbackData->BillRefNumber;
        $invoiceNumber     = $callbackData->InvoiceNumber;
        $orgAccountBalance = $callbackData->OrgAccountBalance;
        $thirdPartyTransId = $callbackData->ThirdPartyTransID;
        $msisdn            = $callbackData->MSISDN;
        $firstName         = $callbackData->FirstName;
        $middleName        = $callbackData->MiddleName;
        $lastName          = $callbackData->LastName;

        return json_encode([        
            'TransactionType'   => $transactionType,
            'TransID'           => $transId,
            'TransTime'         => $transTime,
            'TransAmount'       => $transAmount,
            'BusinessShortCode' => $businessShortCode,
            'BillRefNumber'     => $billRefNumber,
            'InvoiceNumber'     => $invoiceNumber,
            'OrgAccountBalance' => $orgAccountBalance,
            'ThirdPartyTransID' => $thirdPartyTransId,
            'MSISDN'            => $msisdn,
            'FirstName'         => $firstName,
            'MiddleName'        => $middleName,
            'LastName'          => $lastName
        ]);
    }

    /**
     * B2C Payment Request Result
     *
     * Gets the B2C payment request result posted in the result URL.     
     *
     * @since 1.5.0
     *                              
     * @return string|null 
     */
    public function getB2cPaymentRequestResult()
    {
        $callbackData = $this->getData();

        if (empty($callbackData)) {
            return null;
        }
        
        // Result
        $resultType               = $callbackData->Result->ResultType;
        $resultCode               = $callbackData->Result->ResultCode;
        $resultDesc               = $callbackData->Result->ResultDesc;
        $originatorConversationId = $callbackData->Result->OriginatorConversationID;
        $conversationId           = $callbackData->Result->ConversationID;
        $transactionId            = $callbackData->Result->TransactionID;
        $resultParameters         = null;
        $referenceItems           = null;
        
        // Result Parameters
        $transactionReceipt                  = '';
        $transactionAmount                   = '';
        $b2CWorkingAccountAvailableFunds     = '';
        $b2CUtilityAccountAvailableFunds     = '';
        $transactionCompletedDateTime        = '';
        $receiverPartyPublicName             = '';
        $b2CChargesPaidAccountAvailableFunds = '';
        $b2CRecipientIsRegisteredCustomer    = '';

        // Reference Items
        $queueTimeoutUrl                     = '';

        // Successful transactions
        if ($resultCode == 0) {
            if (isset($callbackData->Result->ResultParameters->ResultParameter)) {
                $resultParameters                    = $callbackData->Result->ResultParameters->ResultParameter;
                $transactionReceipt                  = $this->getValueByKey($resultParameters, 'TransactionReceipt');
                $transactionAmount                   = $this->getValueByKey($resultParameters, 'TransactionAmount');
                $b2CWorkingAccountAvailableFunds     = $this->getValueByKey($resultParameters, 'B2CWorkingAccountAvailableFunds');
                $b2CUtilityAccountAvailableFunds     = $this->getValueByKey($resultParameters, 'B2CUtilityAccountAvailableFunds');
                $transactionCompletedDateTime        = $this->getValueByKey($resultParameters, 'TransactionCompletedDateTime');
                $receiverPartyPublicName             = $this->getValueByKey($resultParameters, 'ReceiverPartyPublicName');
                $b2CChargesPaidAccountAvailableFunds = $this->getValueByKey($resultParameters, 'B2CChargesPaidAccountAvailableFunds');
                $b2CRecipientIsRegisteredCustomer    = $this->getValueByKey($resultParameters, 'B2CRecipientIsRegisteredCustomer');
            } 
        }

        if (isset($callbackData->Result->ReferenceData->ReferenceItem)) {
            $referenceData   = $callbackData->Result->ReferenceData;
            $queueTimeoutUrl = $this->getValueByKey($referenceData, 'QueueTimeoutURL');;
        } 

        return json_encode([
            'ResultType'                          => $resultType,
            'ResultCode'                          => $resultCode,
            'ResultDesc'                          => $resultDesc,
            'OriginatorConversationID'            => $originatorConversationId,
            'ConversationID'                      => $conversationId,
            'TransactionID'                       => $transactionId,
            'TransactionReceipt'                  => $transactionReceipt,
            'TransactionAmount'                   => $transactionAmount,
            'B2CWorkingAccountAvailableFunds'     => $b2CWorkingAccountAvailableFunds,
            'B2CUtilityAccountAvailableFunds'     => $b2CUtilityAccountAvailableFunds,
            'TransactionCompletedDateTime'        => $transactionCompletedDateTime,
            'ReceiverPartyPublicName'             => $receiverPartyPublicName,
            'B2CChargesPaidAccountAvailableFunds' => $b2CChargesPaidAccountAvailableFunds,
            'B2CRecipientIsRegisteredCustomer'    => $b2CRecipientIsRegisteredCustomer,
            'QueueTimeoutURL'                     => $queueTimeoutUrl
        ]);
    }

    /**
     * B2B Payment Request Result 
     *
     * Gets the B2B payment request result posted in the result URL.     
     *
     * @since 1.5.0
     * 
     * @return string|null 
     */
    public function getB2bPaymentRequestResult()
    {
        $callbackData = $this->getData();

        if (empty($callbackData)) {
            return null;
        }

        // Result
        $resultType               = $callbackData->Result->ResultType;
        $resultCode               = $callbackData->Result->ResultCode;
        $resultDesc               = $callbackData->Result->ResultDesc;
        $originatorConversationId = $callbackData->Result->OriginatorConversationID;
        $conversationId           = $callbackData->Result->ConversationID;
        $transactionId            = $callbackData->Result->TransactionID;
        $resultParameters         = null;
        $referenceData            = null;

        // Result Parameters
        $debitAccountBalance              = '';
        $amount                           = '';
        $debitPartyAffectedAccountBalance = '';
        $transCompletedTime               = '';
        $debitPartyCharges                = '';
        $receiverPartyPublicName          = '';
        $currency                         = '';
        $initiatorAccountCurrentBalance   = '';
        $boCompletedTime                  = '';

        // Reference Items
        $billReferenceNumber              = '';
        $queueTimeoutUrl                  = '';

        // successful transaction
        if ($resultCode == 0) {
            $resultParameters                 = $callbackData->Result->ResultParameters->ResultParameter;
            $debitAccountBalance              = $this->getValueByKey($resultParameters, 'DebitAccountBalance');
            $amount                           = $this->getValueByKey($resultParameters, 'Amount');
            $debitPartyAffectedAccountBalance = $this->getValueByKey($resultParameters, 'DebitPartyAffectedAccountBalance');
            $transCompletedTime               = $this->getValueByKey($resultParameters, 'TransCompletedTime');
            $debitPartyCharges                = $this->getValueByKey($resultParameters, 'DebitPartyCharges');
            $receiverPartyPublicName          = $this->getValueByKey($resultParameters, 'ReceiverPartyPublicName');
            $currency                         = $this->getValueByKey($resultParameters, 'Currency');
            $initiatorAccountCurrentBalance   = $this->getValueByKey($resultParameters, 'InitiatorAccountCurrentBalance');

            $referenceData                    = $callbackData->Result->ReferenceData->ReferenceItem;
            $billReferenceNumber              = $this->getValueByKey($referenceData, 'BillReferenceNumber');
            $queueTimeoutUrl                  = $this->getValueByKey($referenceData, 'QueueTimeoutURL');
        } 
        // failed transaction
        else {
            $resultParameters                 = $callbackData->Result->ResultParameters;
            $boCompletedTime                  = $this->getValueByKey($resultParameters, 'BOCompletedTime');
            $referenceData                    = $callbackData->Result->ReferenceData;
            $queueTimeoutUrl                  = $this->getValueByKey($referenceData, 'QueueTimeoutURL');
        }

        // TODO: Consider exploding values for DebitAccountBalance, 
        //       DebitPartyAffectedAccountBalance, and 
        //       InitiatorAccountCurrentBalance in callback result array.
        return json_encode([
            'ResultType'                       => $resultType,
            'ResultCode'                       => $resultCode,
            'ResultDesc'                       => $resultDesc,
            'OriginatorConversationID'         => $originatorConversationId,
            'ConversationID'                   => $conversationId,
            'TransactionID'                    => $transactionId,
            'DebitAccountBalance'              => $debitAccountBalance,
            'Amount'                           => $amount,
            'DebitPartyAffectedAccountBalance' => $debitPartyAffectedAccountBalance,
            'TransCompletedTime'               => $transCompletedTime,
            'DebitPartyCharges'                => $debitPartyCharges,
            'ReceiverPartyPublicName'          => $receiverPartyPublicName,
            'Currency'                         => $currency,
            'InitiatorAccountCurrentBalance'   => $initiatorAccountCurrentBalance,
            'BOCompletedTime'                  => $boCompletedTime,
            'BillReferenceNumber'              => $billReferenceNumber,
            'QueueTimeoutURL'                  => $queueTimeoutUrl
        ]);
    }

    /**
     * Transaction Status Query Result
     *
     * Gets the transaction status query result posted in the result URL.     
     *
     * @since 1.5.0
     *
     * @return string|null 
     */
    public function getTransactionStatusQueryResult()
    {
        $callbackData = $this->getData();

        if (empty($callbackData)) {
            return null;
        }

        // Result
        $resultType               = $callbackData->Result->ResultType;
        $resultCode               = $callbackData->Result->ResultCode;
        $resultDesc               = $callbackData->Result->ResultDesc;
        $originatorConversationId = $callbackData->Result->OriginatorConversationID;
        $conversationId           = $callbackData->Result->ConversationID;
        $transactionId            = $callbackData->Result->TransactionID;
        
        // Result Parameters
        //
        // NOTICE:
        //
        // This is an edited version of the expected JSON result. The example 
        // JSON result provided in the documentation as of the time of writing 
        // seemed to be invalid.
        //
        // Here are some of the notable issues in the documented JSON:
        //
        // - There is a comma at the end of the "Key": "Occasion" object.
        // - There are duplicate keys: "DebitPartyName" and "ConversationID".
        // - The "Key": "TransactionReason" object is missing its "Value".
        //
        // Be sure to confirm the result parameters in production.
        $receiptNo                 = '';    
        $amount                    = ''; 
        $initiatedTime             = '';       
        $finalisedTime             = ''; 
        $transactionStatus         = '';
        $reasonType                = '';
        $transactionReason         = '';
        $debitPartyCharges         = ''; 
        $debitAccountType          = ''; 
        $creditPartyName           = ''; 
        $debitPartyName            = '';

        // successful transaction
        if ($resultCode == 0) {
            $resultParameters          = $callbackData->Result->ResultParameters->ResultParameter;
            $receiptNo                 = $this->getValueByKey($resultParameters, 'ReceiptNo');
            $amount                    = $this->getValueByKey($resultParameters, 'Amount');   
            $initiatedTime             = $this->getValueByKey($resultParameters, 'InitiatedTime');     
            $finalisedTime             = $this->getValueByKey($resultParameters, 'FinalisedTime');
            $transactionStatus         = $this->getValueByKey($resultParameters, 'TransactionStatus');
            $reasonType                = $this->getValueByKey($resultParameters, 'ReasonType');
            $transactionReason         = $this->getValueByKey($resultParameters, 'TransactionReason');
            $debitPartyCharges         = $this->getValueByKey($resultParameters, 'DebitPartyCharges');
            $debitAccountType          = $this->getValueByKey($resultParameters, 'DebitAccountType');
            $debitPartyName            = $this->getValueByKey($resultParameters, 'DebitPartyName');
            $creditPartyName           = $this->getValueByKey($resultParameters, 'CreditPartyName');
        }

        // Reference Data
        $referenceData             = $callbackData->Result->ReferenceData;
        $occasion                  = $this->getValueByKey($referenceData, 'Occasion');

        return json_encode([        
            'ResultType'                 => $resultType,
            'ResultCode'                 => $resultCode,
            'ResultDesc'                 => $resultDesc,
            'OriginatorConversationID'   => $originatorConversationId,
            'ConversationID'             => $conversationId,
            'TransactionID'              => $transactionId,        
            'ReceiptNo'                  => $receiptNo,
            'Amount'                     => $amount,
            'InitiatedTime'              => $initiatedTime,
            'FinalisedTime'              => $finalisedTime,
            'TransactionStatus'          => $transactionStatus,
            'ReasonType'                 => $reasonType,
            'TransactionReason'          => $transactionReason,
            'DebitPartyCharges'          => $debitPartyCharges,
            'DebitAccountType'           => $debitAccountType,
            'DebitPartyName'             => $debitPartyName,
            'CreditPartyName'            => $creditPartyName,
            'Occasion'                   => $occasion
        ]);
    }

    /**
     * Account Balance Query Result 
     *
     * Gets the account balance query result posted in the result URL. 
     * 
     * As of now, there are no repeat callbacks for failed callbacks, thus if 
     * M-PESA is unable to send the callback to you, you will need to check the 
     * status of that transaction.      
     *
     * @since 1.5.0
     *
     * @return string|null 
     */
    public function getAccountBalanceQueryResult()
    {
        $callbackData = $this->getData();

        if (empty($callbackData)) {
            return null;
        }

        // Result
        $resultType               = $callbackData->Result->ResultType;
        $resultCode               = $callbackData->Result->ResultCode;
        $resultDesc               = $callbackData->Result->ResultDesc;
        $originatorConversationId = $callbackData->Result->OriginatorConversationID;
        $conversationId           = $callbackData->Result->ConversationID;
        $transactionId            = $callbackData->Result->TransactionID;

        // Result Parameters
        $resultParameters         = $callbackData->Result->ResultParameters->ResultParameter;
        $accountBalance           = $this->getValueByKey($resultParameters, 'AccountBalance');
        $boCompletedTime          = $this->getValueByKey($resultParameters, 'BOCompletedTime');

        // Reference Data
        $referenceData            = $callbackData->Result->ReferenceData;
        $queueTimeoutUrl          = $this->getValueByKey($referenceData, 'QueueTimeoutURL');
        
        // TODO: Consider exploding the AccountBalance value.
        //       Working Account
        //       Float Account
        //       Charges Paid Account
        //       Organization Settlement Account
        return json_encode([
            'ResultType'               => $resultType,
            'ResultCode'               => $resultCode,
            'ResultDesc'               => $resultDesc,
            'OriginatorConversationID' => $originatorConversationId,
            'ConversationID'           => $conversationId,
            'TransactionID'            => $transactionId,
            'AccountBalance'           => $accountBalance,
            'BOCompletedTime'          => $boCompletedTime,
            'QueueTimeoutURL'          => $queueTimeoutUrl
        ]);
    }

    /**
     * Reversal Request Result 
     *
     * Gets the reversal request result posted in the result URL.     
     *
     * @since 1.5.0
     *
     * @return string|null 
     */
    public function getReversalRequestResult()
    {
        $callbackData = $this->getData();

        if (empty($callbackData)) {
            return null;
        }

        // Result
        $resultType               = $callbackData->Result->ResultType;
        $resultCode               = $callbackData->Result->ResultCode;
        $resultDesc               = $callbackData->Result->ResultDesc;
        $originatorConversationId = $callbackData->Result->OriginatorConversationID;
        $conversationId           = $callbackData->Result->ConversationID;
        $transactionId            = $callbackData->Result->TransactionID;

        // Result Parameters
        $resultParameters         = $callbackData->Result->ResultParameters->ResultParameter;
        $debitAccountBalance      = $this->getValueByKey($resultParameters, 'DebitAccountBalance');
        $transCompletedTime       = $this->getValueByKey($resultParameters, 'TransCompletedTime');
        $originalTransactionId    = $this->getValueByKey($resultParameters, 'OriginalTransactionID');
        $charge                   = $this->getValueByKey($resultParameters, 'Charge');
        $creditPartyPublicName    = $this->getValueByKey($resultParameters, 'CreditPartyPublicName');
        $debitPartyPublicName     = $this->getValueByKey($resultParameters, 'DebitPartyPublicName');

        // Reference Data
        $referenceData            = $callbackData->Result->ReferenceData;
        $queueTimeoutUrl          = $this->getValueByKey($referenceData ,'QueueTimeoutURL');
        
        // TODO: Consider exploding the DebitAccountBalance value?
        return json_encode([
            'ResultType'               => $resultType,
            'ResultCode'               => $resultCode,
            'ResultDesc'               => $resultDesc,
            'OriginatorConversationID' => $originatorConversationId,
            'ConversationID'           => $conversationId,
            'TransactionID'            => $transactionId,
            'OriginalTransactionID'    => $originalTransactionId,
            'DebitAccountBalance'      => $debitAccountBalance,
            'TransCompletedTime'       => $transCompletedTime,
            'Charge'                   => $charge,
            'CreditPartyPublicName'    => $creditPartyPublicName,
            'DebitPartyPublicName'     => $debitPartyPublicName,
            'QueueTimeoutURL'          => $queueTimeoutUrl
        ]);
    }

    //-------------------------------------------------------------------------
    // Timeout Methods
    //-------------------------------------------------------------------------
    
    /**
     * Queue Timeout Handler
     * 
     * Handles a queue timeout notification from the M-PESA API.
     * 
     * Whenever M-PESA API receives more requests than the queue can handle, it 
     * responds by rejecting any more requests and the API Gateway sends a queue 
     * timeout response to the URL specified in the QueueTimeOutURL parameter.
     * 
     * You can extend this method to notify relevant parties whenever a timeout 
     * notification is received, and possibly retry the request or take other 
     * corrective actions.
     * 
     * @since 2.0.0
     */
    public function handleQueueTimeout()
    {
        $callbackData = $this->getData();

        if (empty($callbackData)) {
            return false;
        }

        // TODO: Log the timeout callback data
        #logTimeout($callbackData);

        // TODO: Notify relevant parties, for example, send an email alert to 
        //       the API admin.
        #notifyTimeout($callbackData);

        // TODO: Take corrective actions, for example, retry the request.
        #retryRequest($callbackData);

        // Respond to M-PESA
        $this->sendTimeoutNotificationResponse();
    }

    //-------------------------------------------------------------------------
    // Verification Methods
    //-------------------------------------------------------------------------

    /**
     * Validates a C2B transaction after receiving the Validation request from 
     * M-PESA API upon payment request. 
     * 
     * The Validation request is received only by partners who have the External 
     * Validation feature enabled on their PayBill or BuyGoods (Till Number) and 
     * require validating a payment before M-PESA completes the transaction. 
     * 
     * @since 2.0.0
     */
    public function validateC2bTransaction()
    {   
        // Get the payment result.
        $result = $this->getC2bPaymentResult();

        // TODO: Put your payment processing logic here. For example, a you
        //       may want to verify if an account number exists in your platform 
        //       before accepting a payment from the customer.
        // if (condition) {
            // code...
        // }

        $resultCode = 0; 
        $transId    = '';

        // Send validation response.
        $this->sendC2bValidationResponse($resultCode, $transId);
    }

    /**
     * Confirms a C2B transaction after receiving the Confirmation notification
     * from M-PESA API upon payment completion. 
     * 
     * @since 2.0.0
     */
    public function confirmC2bTransaction()
    {   
        // Get the payment result.
        $result = $this->getC2bPaymentResult();

        // TODO: Put your payment processing logic here. For example, the code
        //       to save the transaction in your database.
        // if (condition) {
            // code...
        // }

        // Send confirmation response.
        $this->sendC2bConfirmationResponse();
    }

    //-------------------------------------------------------------------------
    // Response Methods
    //-------------------------------------------------------------------------

    /**
     * C2B Payment Validation Response 
     *
     * Used to send a response to validate the details of a C2B payment before 
     * accepting. 
     * 
     * After receiving the validation request, you are required to process it 
     * and respond to the API call and tell M-PESA either to accept or reject 
     * the payment. To accept, you send the below JSON making sure the value of 
     * ResultCode is 0 (zero), but the value of ResultDesc is Accepted. 
     * 
     * To reject a transaction, you send the same JSON above, but with the 
     * ResultCode set as C2B00011 (or any other value from the errors list), 
     * BUT NOT 0. The ResultDesc should be set as Rejected. 
     *
     * @since 2.0.0
     *
     * @param integer|string $resultCode The result code. Default 0.
     * @param string         $transId    Optional. A third-party transaction ID 
     *                                   that the can be used to identify the 
     *                                   transaction. When a validation request 
     *                                   is sent, the partner can respond with
     *                                   ThirdPartyTransID and this will be sent 
     *                                   back with the Confirmation notification. 
     * 
     * @return void  
     */
    public function sendC2bValidationResponse($resultCode = 0, $transId = '')
    { 
        // Accepted transactions:
        if ($resultCode == 0) {
            return $this->sendResponse([
                'ResultCode'        => '0',
                'ResultDesc'        => 'Accepted',
                'ThirdPartyTransID' => $transId
            ]);
        }

        // Rejected transactions:
        // Validation result error codes.
        $errorCodes = [
            'C2B00011', // Invalid MSISDN
            'C2B00012', // Invalid Account Number
            'C2B00013', // Invalid Amount',
            'C2B00014', // Invalid KYC Details
            'C2B00015', // Invalid Shortcode
            'C2B00016'  // Other Erro
        ];
        
        // Validate the error code.
        if (!in_array($resultCode, $errorCodes, true)) {
            $resultCode = 'C2B00016';
        }

        $this->sendResponse([
            'ResultCode' => $resultCode,
            'ResultDesc' => 'Rejected'
        ]);
    }

    /**
     * C2B Payment Confirmation Response 
     *
     * The Confirmation response is sent from the client to the M-PESA gateway
     * to acknowledge that the client has received the payment results.
     *
     * For the Confirmation response, the ResultCode is always 0 (zero) and the
     * ResultDesc is usually "Success". 
     *
     * @since 2.0.0
     * 
     * @return void 
     */
    public function sendC2bConfirmationResponse()
    {
        $this->sendResponse([
            'ResultCode' => '0',
            'ResultDesc' => 'Success'
        ]);
    }

    /**
     * Timeout Notification Response
     *
     * The timeout notification response is sent from the client to the M-PESA 
     * gateway to acknowledge receipt of the timeout notification.
     *
     * @since 2.0.0
     * 
     * @return void 
     */
    public function sendTimeoutNotificationResponse() 
    {
        $this->sendResponse([
            'ResultCode' => '0',
            'ResultDesc' => 'Timeout notification received successfully'
        ]);
    }

    /**
     * Finishing Transaction Response
     * 
     * After obtaining the callback data from M-PESA, use this at the end of 
     * your callback processing logic to complete the transaction.
     * 
     * Sending a response back to M-PESA is important to avoid double hitting of
     * your callback endpoint since if a response delays, M-PESA API assumes it 
     * as failed and retries, which can cause double execution of your code if 
     * not well handled.
     * 
     * @since 2.0.0
     * 
     * @param boolean $status True if the transaction was completed successfully
     *                        or false if the transaction processing failed.
     * 
     * @return void 
     */
    public function finishTransaction($status = true)
    {
        if ($status === true) {
            $this->sendResponse([
                'ResultCode' => '0',
                'ResultDesc' => 'The service request is processed successfully'
            ]);
        } else {
            $this->sendResponse([
                'ResultCode' => '1',
                'ResultDesc' => 'The service request failed'
            ]);
        }
    }

    //-------------------------------------------------------------------------
    // Utility Methods
    //-------------------------------------------------------------------------

    /**
     * Gets raw JSON data posted in the callback routes.
     *
     * @since 2.0.0
     *
     * @return object|false The JSON data as an object or false if no data
     *                      is read.
     */
    private function getData()
    {
        $json = file_get_contents('php://input');

        if (empty($json)) {
            return false;
        }

        return json_decode($json);
    }

    /**
     * Retrieves the value of an item by name.
     * 
     * Gets the Value of stkCallback->CallbackMetadata->Item by Name.
     * 
     * Sometimes, the M-PESA API returns the metadata with four items (without the 
     * Balance item) instead of five. We need to consider that to avoid errors in 
     * the application or database. Therefore, this functions gets an item value
     * by its name instead of the array index, and returns null if the item is not 
     * found.
     * 
     * @since 2.0.0
     * 
     * @param array  $items Items array as a PHP object.
     * @param string $name  The item name.
     * 
     * @return The item value, or null if the item or its value does not exist.
     */
    private function getValueByName($items, string $name) 
    {
        if (empty($items)) {
            return null;
        }

        foreach ($items as $item) {
            if (isset($item->Name) && $item->Name === $name) {
                return isset($item->Value) ? $item->Value : null; 
            }
        }

        return null; 
    }

    /**
     * Retrieves the value of an item by key.
     * 
     * Gets the Value of Result->ResultParameters->ResultParameter by Key.
     * Gets the Value of Result->ReferenceData->ReferenceItem by Key. 
     * 
     * @since 2.0.0
     * 
     * @param array  $items Items array as a PHP object.
     * @param string $key   The item key.
     * 
     * @return The item value, or null if the item or its value does not exist.
     */
    private function getValueByKey($items, string $key) 
    {
        if (empty($items)) {
            return null;
        }

        foreach ($items as $item) {
            if (isset($item->Key) && $item->Key === $key) {
                return isset($item->Value) ? $item->Value : null; 
            }
        }

        return null; 
    }

    /**
     * Sends response back to the M-PESA API.
     *
     * Responses are sent from the clients endpoints to the M-PESA gateway
     * to acknowledge that the client has received the results.
     * 
     * @since 2.0.0
     *
     * @param array $data The response data array, in valid format.
     */
    private function sendResponse(array $data)
    {    
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
    }
}
