<?php
/**
 * M-PESA Callback API
 * 
 * @package    Mikeotizels/APIs/ThirdParty/Safaricom/Mpesa
 * @author     Michael Otieno <mikeotizels@gmail.com>
 * @copyright  Copyright 2020-2022 Michael Otieno
 * @license    Licensed under The MIT License (MIT). For the license terms, 
 *             please see the LICENSE file that was distributed with this 
 *             source code or visit <https://opensource.org/licenses/MIT>.
 */

namespace Mikeotizels\Mpesa;

/**
 * Class Callback
 *
 * Gets the result received once the request has been processed by M-PESA.
 *
 * @since 1.2.0
 */
class Callback
{   
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
            'MerchantRequestID'  => $MerchantRequestID,
            'CheckoutRequestID'  => $CheckoutRequestID,
            'ResultCode'         => $ResultCode,
            'ResultDesc'         => $ResultDesc,
            'Amount'             => $Amount,
            'MpesaReceiptNumber' => $MpesaReceiptNumber,
            'Balance'            => $Balance,
            'TransactionDate'    => $TransactionDate,
            'PhoneNumber'        => $PhoneNumber
        ];

        return json_decode($callbackArray);
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
            'ResponseCode'        => $ResponseCode,
            'ResponseDescription' => $ResponseDescription,
            'MerchantRequestID'   => $MerchantRequestID,
            'CheckoutRequestID'   => $CheckoutRequestID,
            'ResultCode'          => $ResultCode,
            'ResultDesc'          => $ResultDesc
        ];

        return json_decode($callbackArray);
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
            'TransactionType'   => $TransactionType,
            'TransID'           => $TransID,
            'TransTime'         => $TransTime,
            'TransAmount'       => $TransAmount,
            'BusinessShortCode' => $BusinessShortCode,
            'BillRefNumber'     => $BillRefNumber,
            'InvoiceNumber'     => $InvoiceNumber,
            'OrgAccountBalance' => $OrgAccountBalance,
            'ThirdPartyTransID' => $ThirdPartyTransID,
            'MSISDN'            => $MSISDN,
            'FirstName'         => $FirstName,
            'MiddleName'        => $MiddleName,
            'LastName'          => $LastName
        ];

        return json_decode($responseArray);
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
            'ResultType'                          => $ResultType,
            'ResultCode'                          => $ResultCode,
            'ResultDesc'                          => $ResultDesc,
            'OriginatorConversationID'            => $OriginatorConversationID,
            'ConversationID'                      => $ConversationID,
            'TransactionID'                       => $TransactionID,
            'TransactionReceipt'                  => $TransactionReceipt,
            'TransactionAmount'                   => $TransactionAmount,
            'B2CWorkingAccountAvailableFunds'     => $B2CWorkingAccountAvailableFunds,
            'B2CUtilityAccountAvailableFunds'     => $B2CUtilityAccountAvailableFunds,
            'TransactionCompletedDateTime'        => $TransactionCompletedDateTime,
            'ReceiverPartyPublicName'             => $ReceiverPartyPublicName,
            'B2CChargesPaidAccountAvailableFunds' => $B2CChargesPaidAccountAvailableFunds,
            'B2CRecipientIsRegisteredCustomer'    => $B2CRecipientIsRegisteredCustomer
        ];

        return json_decode($resultArray);
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
            'ResultType'                       => $ResultType,
            'ResultCode'                       => $ResultCode,
            'ResultDesc'                       => $ResultDesc,
            'OriginatorConversationID'         => $OriginatorConversationID,
            'ConversationID'                   => $ConversationID,
            'TransactionID'                    => $TransactionID,
            'InitiatorAccountCurrentBalance'   => $InitiatorAccountCurrentBalance,
            'DebitAccountCurrentBalance'       => $DebitAccountCurrentBalance,
            'Amount'                           => $Amount,
            'DebitPartyAffectedAccountBalance' => $DebitPartyAffectedAccountBalance,
            'TransCompletedTime'               => $TransCompletedTime,
            'DebitPartyCharges'                => $DebitPartyCharges,
            'ReceiverPartyPublicName'          => $ReceiverPartyPublicName,
            'Currency'                         => $Currency
        ];

        return json_decode($resultArray);
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
            'ResultType'                 => $ResultType,
            'ResultCode'                 => $ResultCode,
            'ResultDesc'                 => $ResultDesc,
            'OriginatorConversationID'   => $OriginatorConversationID,
            'ConversationID'             => $ConversationID,
            'TransactionID'              => $TransactionID,        
            'ReceiptNo'                  => $ReceiptNo,
            'Conversation ID'            => $Conversation_ID,
            'FinalisedTime'              => $FinalisedTime,
            'Amount'                     => $Amount,
            'TransactionStatus'          => $TransactionStatus,
            'ReasonType'                 => $ReasonType,
            'TransactionReason'          => $TransactionReason,
            'DebitPartyCharges'          => $DebitPartyCharges,
            'DebitAccountType'           => $DebitAccountType,
            'InitiatedTime'              => $InitiatedTime,
            'Originator Conversation ID' => $Originator_Conversation_ID,
            'CreditPartyName'            => $CreditPartyName,
            'DebitPartyName'             => $DebitPartyName
        ];

        return json_decode($resultArray);
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
            'ResultType'               => $ResultType,
            'ResultCode'               => $ResultCode,
            'ResultDesc'               => $ResultDesc,
            'OriginatorConversationID' => $OriginatorConversationID,
            'ConversationID'           => $ConversationID,
            'TransactionID'            => $TransactionID,
            'AccountBalance'           => $AccountBalance,
            'BOCompletedTime'          => $BOCompletedTime,
        ];

        return json_decode($resultArray);
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
            'ResultType'               => $ResultType,
            'ResultCode'               => $ResultCode,
            'ResultDesc'               => $ResultDesc,
            'OriginatorConversationID' => $OriginatorConversationID,
            'ConversationID'           => $ConversationID,
            'TransactionID'            => $TransactionID
        ];

        return json_decode($resultArray);
    }
}