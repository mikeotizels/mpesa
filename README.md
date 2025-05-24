M-PESA REST API SDK PHP Package by Mikeotizels
==============================================

Version 2.0.0 - August 2024

https://github.com/mikeotizels/mpesa/tree/main/2.0.0

This package contains the Mikeotizels implementation of the M-PESA REST APIs 
in PHP. It seeks to help PHP developers integrate the various M-PESA APIs 
without much hustle. 

# Requirements

 * PHP 7.4+

  The following PHP extensions are required to run this package:

  1. cURL
  2. JSON
  3. OpenSSL

# APIs

This package integrates all the main M-PESA REST API endpoints exposed by 
[Daraja v2.0](https://developer.safaricom.co.ke/APIs) as listed below:

0. Authorization API
 - [OAuth Token Generate](https://developer.safaricom.co.ke/APIs/Authorization)

1. Dynamic QR Code API
 - [QR Code Generate](https://developer.safaricom.co.ke/APIs/DynamicQRCode)

2. M-PESA Express (Lipa Na M-PESA Online/STK Push) API
 - [M-PESA Express Simulate](https://developer.safaricom.co.ke/APIs/MpesaExpressSimulate)
 - [M-PESA Express Query](https://developer.safaricom.co.ke/APIs/MpesaExpressQuery)

3. Customer To Business (C2B) API
 - [Customer To Business Register URL](https://developer.safaricom.co.ke/APIs/CustomerToBusinessRegisterURL)
 - [Customer To Business Simulate](https://developer.safaricom.co.ke/APIs/CustomerToBusinessSimulate)

4. Business To Customer (B2C) API 
 - [Business To Customer Payment Request](https://developer.safaricom.co.ke/APIs/BusinessToCustomer)

5. Business To Business (B2B) API 
 - [Business Pay Bill](https://developer.safaricom.co.ke/APIs/BusinessPayBill)
 - [Business Buy Goods](https://developer.safaricom.co.ke/APIs/BusinessBuyGoods)

6. Transaction Status API
 - [Transaction Status Query](https://developer.safaricom.co.ke/APIs/TransactionStatus)

7. Account Balance API
 - [Account Balance Query](https://developer.safaricom.co.ke/APIs/AccountBalance)

8. Reversal API
 - [Reversal Request](https://developer.safaricom.co.ke/APIs/Reversal)

# Documentation

The full documentation for the M-PESA APIs (DARAJA APIs) is available online at 
[Safaricom Developers' Portal](https://developer.safaricom.co.ke/).

# Licensing

The Mikeotizels M-PESA API SDK is open-source software licensed under the terms 
of [The MIT License](http://opensource.org/licenses/MIT). In case you modify any 
source code, credits are not required but would be really appreciated.

# Support

If you need help using this M-PESA API SDK package or more info about it, please 
contact me through email at <mikeotizels@gmail.com>.

-------------------------------------------------------------------------------

Enjoy!

Michael Otieno.
