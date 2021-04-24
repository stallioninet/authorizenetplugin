<?php
define('__ROOT__', dirname(dirname(__FILE__)));
//require_once(__ROOT__.'/config.php');
require_once(__ROOT__.'/vendor/autoload.php');
//require 'vendor/autoload.php';
//require_once(__ROOT__.'/constants/SampleCodeConstants.php');
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

define("AUTHORIZENET_LOG_FILE", "phplog");
$paymentID = $statusMsg = ''; 
$ordStatus = 'error'; 
$responseArr = array(1 => 'Approved', 2 => 'Declined', 3 => 'Error', 4 => 'Held for Review');


//const MERCHANT_LOGIN_ID = $api_login_id;
//const MERCHANT_TRANSACTION_KEY = $api_transaction_key;
// Check whether card information is not empty 
function chargeCreditCard($fields)
{
	$api_login_id = get_option( 'authorize_payment_login' );
	$api_transaction_key = get_option( 'authorize_payment_transactionkey' );

	$user_email = $fields['user_mail'];
	$amount 	= $fields['amount'];
	$car_number = $fields['car_number'];
	$exp_date 	= $fields['exp_date'];
	$card_cvc 	= $fields['card_cvc'];
	$order_id 	= $fields['order_id'];
    /* Create a merchantAuthenticationType object with authentication details
       retrieved from the constants file */
    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName($api_login_id);
    $merchantAuthentication->setTransactionKey($api_transaction_key);
   
    // Set the transaction's refId
    $refId = 'ref' . time();

    // Create the payment data for a credit card
    $creditCard = new AnetAPI\CreditCardType();
    $creditCard->setCardNumber($car_number);
    $creditCard->setExpirationDate($exp_date);
    $creditCard->setCardCode($card_cvc);

    // Add the payment data to a paymentType object
    $paymentOne = new AnetAPI\PaymentType();
    $paymentOne->setCreditCard($creditCard);

    // Create order information
    $order = new AnetAPI\OrderType();
    $order->setInvoiceNumber($order_id);
    //$order->setDescription("Golf Shirts");

    // Set the customer's Bill To address
    /*$customerAddress = new AnetAPI\CustomerAddressType();
    $customerAddress->setFirstName("Ellen");
    $customerAddress->setLastName("Johnson");
    $customerAddress->setCompany("Souveniropolis");
    $customerAddress->setAddress("14 Main Street");
    $customerAddress->setCity("Pecan Springs");
    $customerAddress->setState("TX");
    $customerAddress->setZip("44628");
    $customerAddress->setCountry("USA");*/

    // Set the customer's identifying information
    $customerData = new AnetAPI\CustomerDataType();
    $customerData->setType("individual");
    //$customerData->setId("99999456654");
    $customerData->setEmail($user_email);

    // Add values for transaction settings
   /* $duplicateWindowSetting = new AnetAPI\SettingType();
    $duplicateWindowSetting->setSettingName("duplicateWindow");
    $duplicateWindowSetting->setSettingValue("60");

    // Add some merchant defined fields. These fields won't be stored with the transaction,
    // but will be echoed back in the response.
    $merchantDefinedField1 = new AnetAPI\UserFieldType();
    $merchantDefinedField1->setName("customerLoyaltyNum");
    $merchantDefinedField1->setValue("1128836273");

    $merchantDefinedField2 = new AnetAPI\UserFieldType();
    $merchantDefinedField2->setName("favoriteColor");
    $merchantDefinedField2->setValue("blue");*/

    // Create a TransactionRequestType object and add the previous objects to it
    $transactionRequestType = new AnetAPI\TransactionRequestType();
    $transactionRequestType->setTransactionType("authCaptureTransaction");
    $transactionRequestType->setAmount($amount);
    $transactionRequestType->setOrder($order);
    $transactionRequestType->setPayment($paymentOne);
    //$transactionRequestType->setBillTo($customerAddress);
    $transactionRequestType->setCustomer($customerData);
    //$transactionRequestType->addToTransactionSettings($duplicateWindowSetting);
    //$transactionRequestType->addToUserFields($merchantDefinedField1);
    //$transactionRequestType->addToUserFields($merchantDefinedField2);

    // Assemble the complete transaction request
    $request = new AnetAPI\CreateTransactionRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setRefId($refId);
    $request->setTransactionRequest($transactionRequestType);

    // Create the controller and get the response
    $controller = new AnetController\CreateTransactionController($request);
    $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
    

    if ($response != null) {
        // Check to see if the API request was successfully received and acted upon
        if ($response->getMessages()->getResultCode() == "Ok") {
            // Since the API request was successful, look for a transaction response
            // and parse it to display the results of authorizing the card
            $tresponse = $response->getTransactionResponse();
        
            if ($tresponse != null && $tresponse->getMessages() != null) {
				$res_data = array(
					'transaction_id' => $tresponse->getTransId(),
					'transaction_code'	=> $tresponse->getResponseCode(),
					'auth_code'	=>	$tresponse->getAuthCode(),
				);
                /*echo " Successfully created transaction with Transaction ID: " . $tresponse->getTransId() . "\n";
                echo " Transaction Response Code: " . $tresponse->getResponseCode() . "\n";
                echo " Message Code: " . $tresponse->getMessages()[0]->getCode() . "\n";
                echo " Auth Code: " . $tresponse->getAuthCode() . "\n";
                echo " Description: " . $tresponse->getMessages()[0]->getDescription() . "\n";*/
            } else {
                //echo "Transaction Failed \n";
                if ($tresponse->getErrors() != null) {
					$res_data = array(
						'err_code'	=>	$tresponse->getErrors()[0]->getErrorCode(),
						'err_msg'	=>	$tresponse->getErrors()[0]->getErrorText(),
					);
                }
            }
            // Or, print errors if the API request wasn't successful
        } else {
           // echo "Transaction Failed \n";
            $tresponse = $response->getTransactionResponse();
        
            if ($tresponse != null && $tresponse->getErrors() != null) {
				$res_data = array(
					'err_code'	=>	$tresponse->getErrors()[0]->getErrorCode(),
					'err_msg'	=>	$tresponse->getErrors()[0]->getErrorText(),
				);
            } else {
				$res_data = array(
					'err_code'	=>	$response->getMessages()->getMessage()[0]->getCode(),
					'err_msg'	=>	$response->getMessages()->getMessage()[0]->getText(),
				);
            }
        }
    } else {
        echo  "No response returned \n";
    }

    return $res_data;
}

/*if (!defined('DONT_RUN_SAMPLES')) {
    chargeCreditCard("2.23");
}*/
