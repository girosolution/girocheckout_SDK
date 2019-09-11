<?php
define('__GIROCHECKOUT_SDK_DEBUG__',true);

/**
 * sample code for GiroCheckout integration of a direct debit transaction
 *
 * @filesource
 * @package Samples
 * @version $Revision: 176 $ / $Date: 2017-01-09 13:29:27 -0300 (Mon, 09 Jan 2017) $
 */
require '../vendor/autoload.php';
use girosolution\GiroCheckout_SDK\GiroCheckout_SDK_Request;
use girosolution\GiroCheckout_SDK\helper\GiroCheckout_SDK_TransactionType_helper;

/**
 * configuration of the merchants identifier, project and password
 * this information can be found in the GiroCockpit's project settings
 */
$merchantID = 0;        // Your merchant ID (Verkaufer-ID)
$projectID = 0;         // Your project ID (Projekt-ID)
$projectPassword = "";  // Your project password

/* perform direct debit transaction */
try {
	$request = new GiroCheckout_SDK_Request(GiroCheckout_SDK_TransactionType_helper::TRANS_TYPE_DIRECTDEBIT_TRANSACTION );
	$request->setSecret($projectPassword);
	$request->addParam('merchantId',$merchantID)
	        ->addParam('projectId',$projectID)
	        ->addParam('merchantTxId', 1234567890)
	        ->addParam('amount',100)
	        ->addParam('currency','EUR')
	        ->addParam('purpose','Lastschrift Transaktion')
	        ->addParam('type','SALE')
	        ->addParam('iban','DE87123456781234567890')
          ->addParam('accountHolder','Max Mustermann')
	        ->addParam('mandateReference', '12345abcde')
	        ->addParam('mandateSignedOn', '2014-02-01')
	        ->addParam('mandateReceiverName', 'Max Mustermann Shops')
	        ->addParam('mandateSequence', 1)
          ->addParam('pkn','5754467832f5ed65f93b2734c189140d')
	    //the hash field is auto generated by the SDK
	        ->submit();

  echo "<pre>";print_r( $request->getResponseRaw() ); echo "</pre>\n";

	/* if payment succeeded update your local system */
	if($request->paymentSuccessful()) {
    $request->getResponseParam('rc');
    $request->getResponseParam('msg');
    $request->getResponseParam('reference');
    $request->getResponseParam('backendTxId');
    $request->getResponseParam('mandateReference');
    $request->getResponseParam('resultPayment');
	}
	
	/* if the transaction did not succeed update your local system, get the responsecode and notify the customer */
	else {
    $request->getResponseParam('rc');
    $request->getResponseParam('msg');
    $request->getResponseMessage($request->getResponseParam('rc'),'DE');
    $request->getResponseMessage($request->getResponseParam('resultPayment'),'DE');
	}
}
catch (Exception $e) { echo $e->getMessage(); }