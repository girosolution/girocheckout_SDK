<?php
define('__GIROCHECKOUT_SDK_DEBUG__',true);

require '../vendor/autoload.php';
use girosolution\GiroCheckout_SDK\GiroCheckout_SDK_Request;
use girosolution\GiroCheckout_SDK\helper\GiroCheckout_SDK_TransactionType_helper;

/**
 * sample code for GiroCheckout integration of a credit card transaction
 *
 * Call through web server in two steps:
 * 1) Without arguments for initial AUTH transaction: http://localhost/CreditCardCapture.php, copy displayed reference
 * 2) With reference code from AUTH for CAPTURE, parameter mode=cap (for capture), parameter ref for reference: 
 * http://localhost/CreditCardCapture.php?mode=cap&ref=43290-48329043-43289
 * 
 * @filesource
 * @package Samples
 * @version $Revision: 156 $ / $Date: 2016-06-29 14:17:03 -0300 (Mi, 29 Jun 2016) $
 */

/**
 * configuration of the merchants identifier, project and password
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
  /* init cc transaction an parameters */
  try {
    $request = new GiroCheckout_SDK_Request(GiroCheckout_SDK_TransactionType_helper::TRANS_TYPE_CREDITCARD_TRANSACTION );
    $request->setSecret($projectPassword);
    $request->addParam('merchantId',$merchantID)
            ->addParam('projectId',$projectID)
            ->addParam('merchantTxId',1234567890)
            ->addParam('amount',100)
            ->addParam('currency','EUR')
            ->addParam('type','AUTH')
            ->addParam('purpose','Beispieltransaktion')
  //	        ->addParam('locale','en')
  //	        ->addParam('mobile',0)
            ->addParam('urlRedirect','https://www.my-domain.de/girocheckout/redirect-creditcard')
            ->addParam('urlNotify','https://www.my-domain.de/girocheckout/redirect-creditcard')
        //the hash field is auto generated by the SDK
            ->submit();

    echo "<pre>"; print_r($request->getResponseRaw()); echo "</pre><br/>";

    /* if transaction succeeded update your local system and redirect the customer */
    if($request->requestHasSucceeded()) {
       $strReference = $request->getResponseParam('reference');
       $request->getResponseParam('redirect');
       $request->redirectCustomerToPaymentProvider();
    }
    /* if the transaction did not succeed update your local system, get the responsecode and notify the customer */
    else {
       $request->getResponseParam('rc');
       $request->getResponseParam('msg');
       $request->getResponseMessage($request->getResponseParam('rc'),'DE');
    }
  }
  catch (Exception $e) { echo $e->getMessage(); }
}
elseif( $mode == "cap" ) {

  if( !isset($ref) ) {
    echo "Reference must be given as ref parameter.";
    exit;
  }

  $strReference = $ref;
  
  // This part is not normally executed because redirectCustomerToPaymentProvider above 
  // will end script execution.  You may execute it manually in a separate script.
  if( !empty($strReference) ) {
    try {
      $request = new GiroCheckout_SDK_Request(GiroCheckout_SDK_TransactionType_helper::TRANS_TYPE_CREDITCARD_CAPTURE );
      $request->setSecret($projectPassword);
      $request->addParam('merchantId',$merchantID)
              ->addParam('projectId',$projectID)
              ->addParam('merchantTxId',uniqid())
              ->addParam('amount',100)
              ->addParam('currency','EUR')
              ->addParam('reference',$strReference)
          //the hash field is auto generated by the SDK
              ->submit();

      echo "<pre>"; print_r($request->getResponseRaw()); echo "</pre><br/>";

      /* if transaction succeeded update your local system and redirect the customer */
      if($request->requestHasSucceeded()) {
         $request->getResponseParam('reference');
         $strResPay = $request->getResponseParam('resultPayment');
         $request->redirectCustomerToPaymentProvider();
      }

      /* if the transaction did not succeed update your local system, get the responsecode and notify the customer */
      else {
         $request->getResponseParam('rc');
         $request->getResponseParam('msg');
         $request->getResponseMessage($request->getResponseParam('rc'),'DE');
      }
    }
    catch (Exception $e) { echo $e->getMessage(); }
  }
}