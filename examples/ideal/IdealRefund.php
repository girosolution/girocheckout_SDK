<?php
/**
 * Sample code for GiroCheckout integration of a iDEAL Refund transaction
 *
 * Call through web server in two steps:
 * 1) Without arguments for initial SALE transaction: http://localhost/IdealRefund.php, copy displayed reference
 * 2) With reference code from SALE for REFUND, parameter mode=ref (for refund), parameter ref for reference: 
 * http://localhost/IdealRefund.php?mode=ref&ref=43290-48329043-43289
 * 
 * @filesource
 * @package Samples
 * @version $Revision: 156 $ / $Date: 2016-06-29 14:17:03 -0300 (Mi, 29 Jun 2016) $
 */
require '../vendor/autoload.php';
use girosolution\GiroCheckout_SDK\GiroCheckout_SDK_Request;
use girosolution\GiroCheckout_SDK\helper\GiroCheckout_SDK_TransactionType_helper;

/**
 * Configuration of the merchants identifier, project and password
 * this information can be found in the GiroCockpit's project settings
 */
$merchantID = 0;        // Your merchant ID (Verkaufer-ID)
$projectID = 0;         // Your project ID (Projekt-ID)
$projectPassword = "";  // Your project password

$strReference = "";

if( isset($_GET["mode"]) ) {
  $mode = $_GET["mode"];
}
if( isset($_GET["ref"]) ) {
  $ref = $_GET["ref"];
}

if( !isset($mode) ) {
  /* STEP 1: init iDEAL SALE transaction and parameters */
  try {
    $request = new GiroCheckout_SDK_Request(GiroCheckout_SDK_TransactionType_helper::TRANS_TYPE_IDEAL_PAYMENT );
    $request->setSecret($projectPassword);
    $request->addParam('merchantId',$merchantID)
        ->addParam('projectId',$projectID)
        ->addParam('merchantTxId',1234567890)
        ->addParam('amount',100)
        ->addParam('currency','EUR')
        ->addParam('purpose','iDEAL Zahlung')
        ->addParam('urlRedirect','https://www.my-domain.de/girocheckout/redirect-ideal')
        ->addParam('urlNotify','https://www.my-domain.de/girocheckout/notify-ideal')
        ->addParam('issuer','RABOBANK')
        //the hash field is auto generated by the SDK
        ->submit();

    echo "<pre>"; print_r($request->getResponseRaw()); echo "</pre><br/>";

    // if request succeeded get the issuer list
    if($request->requestHasSucceeded()) {
      $strReference = $request->getResponseParam('reference');
      echo "Reference obtained: $strReference\n";
      $request->getResponseParam('redirect');

      //$request->redirectCustomerToPaymentProvider();
    }
    // if the request did not succeed, get the responsecode and notify the customer
    else {
      $request->getResponseParam('rc');
      $request->getResponseParam('msg');
      $request->getResponseMessage($request->getResponseParam('rc'),'DE');
    }
  }
  catch (Exception $e) { echo $e->getMessage(); }
}
elseif( $mode == "ref" ) {

  // STEP 2: REFUND
  if( !isset($ref) ) {
    echo "Reference must be given as ref parameter.";
    exit;
  }
  
  $strReference = $ref;

  if( !empty($strReference) ) {
    echo "\nOrig Trx Ref: $strReference\n";

    // init giropay transaction and parameters
    try {  
      $request = new GiroCheckout_SDK_Request(GiroCheckout_SDK_TransactionType_helper::TRANS_TYPE_IDEAL_REFUND );
      $request->setSecret($projectPassword);
      $request->addParam('merchantId',$merchantID)
              ->addParam('projectId',$projectID)
              ->addParam('merchantTxId',"123456")
              ->addParam('amount',100)  // Anzahlung
              ->addParam('currency','EUR')
              ->addParam('reference', $strReference)

              //the hash field is auto generated by the SDK
              ->submit();

      echo "<pre>"; print_r($request->getResponseRaw()); echo "</pre><br/>";

      // if transaction succeeded update your local system an redirect the customer 
      if($request->requestHasSucceeded()) {
        $request->getResponseParam('rc');
        $request->getResponseParam('msg');
        $request->getResponseParam('reference');
        $request->getResponseParam('redirect');

        $request->redirectCustomerToPaymentProvider();
      }

      // if the transaction did not succeed update your local system, get the responsecode and notify the customer
      else {
        $request->getResponseParam('rc');
        $request->getResponseParam('msg');
        $request->getResponseMessage($request->getResponseParam('rc'),'DE');
      }
    }
    catch (Exception $e) { echo $e->getMessage(); }
  }
}
