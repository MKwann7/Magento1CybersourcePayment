<?php
class CustomExtensions_Cybersource_TestController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        //$strMerchantUrl = "https://ics2wsa.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.130.wsdl";
        $strMerchantUrl = "https://ics2wstesta.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.130.wsdl";
        $strMerchantId = "wfgdukemfgusd";
        //$strMerchantId = "C861D14F-7C25-4FC6-B4BE-B12C1DCDB031";
        //$strTransactionSecurityKey = "6a2Z3wVAbo4BSo+HIB6WKepoNulMRqnnLllNGo/HwXKpfjpVLgZXmKtRRv0IsgyOEacdBKiQiP9GKsnih2aZ/vH+be81928H035qV5c6HGD5DBcixKSA3acSC2CMeGKpcd7CAhGRbDHhR+IdFOWEPMPOMle1NqIHFHDOZBx1uzbzwhYPlqaLxRtI2tOBcZYMhzXw6q8BRV4gMQVBSZH9IgLxsr0dl0VhLGPnShztOGIrdZKyfK/psknkuzh1Q3wvEOflUjWl37Yc7iLi2pXREQuXcYe1ASMq5RnODBn7kuCMsBNFS8EuM63qr62uGRGoPKh5wnF3ivYTb5nckxtJEQ==";
        $strTransactionSecurityKey = "5pYZ8DmSEYKRWYjRTHoybtI6atJmdo9dzY5NAMdjltYcWjjGzpHstBPWVkHEQj9P1F3Z9BMxkfAsQXrGdt1eQKtj6oioGKR9qd54H8bqsFWMqTZ/XIZhB5GvsbpaQxyPfECQF9igtytfwuC/vmzs8FxlIinnPj1G25owjGGBTnVdGV6J49DQAcR7imCJKdI7rK73fMhASXDyFUVXQSuQSO9orTHEGQAKWdZgYDvhpxkDGRIysWwb07slSMfapaAHNkoiaJ1yqaSxxGDJGjY/J2e+YmrzX8g0SArf/t5onthLh0Y4KbLqF4NFYwJ0Q6nSf5fZNGXKo+BwscJ5CpR5cQ==";
        //$strTransactionSecurityKey = "6ca37cfdedd048b2af1decc2f6563ba7778fb0ed0a554d76abd4cd9f2d5250e7ee53f0c16b254290b6e792b5609715cd45a20c267bd0450187fb06bb4af2377b388e732ddd4c44ce91f1be3febb040713f526179568546898a7c7cf91797df7a485d78392e0a476f86668ab1becdf96ce0e1558d11334dad94fc8c54439071af";
        $client = new CybsSoapClient2($strMerchantUrl,$strMerchantId,$strTransactionSecurityKey);

        $request = $client->createRequest(rand(100000,999999));

        $ccAuthService = new stdClass();
        $ccAuthService->run = 'true';
        $request->ccAuthService = $ccAuthService;

        // These are pulled from the XSD file for 1.1.30
        // https://ics2wsa.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.130.xsd
        $billTo = new stdClass();
        $billTo->customerID = '12345';
        $billTo->firstName = 'Jessica';
        $billTo->lastName = 'Staley';
        $billTo->street1 = '123 Main Street';
        $billTo->city = 'Awesome City';
        $billTo->state = 'AA';
        $billTo->postalCode = '12345';
        $billTo->country = 'USA';
        $billTo->email = 'jstaley@datixinc.com';
        $billTo->ipAddress = '100.100.100.100';
        $request->billTo = $billTo;

        $card = new stdClass();
        $card->accountNumber = '4111111111111111';
        $card->cvNumber = '123';

        $card->expirationMonth = '11';
        $card->expirationYear = '2017';
        $request->card = $card;

        $purchaseTotals = new stdClass();
        $purchaseTotals->currency = 'USD';
        $purchaseTotals->grandTotalAmount = '5.00';
        $request->purchaseTotals = $purchaseTotals;

        try
        {
            $reply = $client->runTransaction($request);
            echo 'SUCCESS';
            echo '<pre>';
            print_r($reply);
            echo '</pre>';
        }
        catch (Exception $e)
        {
            echo $strMerchantUrl.'<br>';
            echo $strMerchantId.'<br>';
            echo $strTransactionSecurityKey;
            echo '<pre>';
            print_r($e);
            echo '</pre>';
        }
    }

}

/**
 * Datix Customized CybsSoapClient
 *
 * An implementation of PHP's SOAPClient class for making CyberSource requests.
 */
class CybsSoapClient2 extends SoapClient
{
    private $merchantId;
    private $transactionKey;

    function __construct($strMerchantUrl,$strMerchantId = "",$strMerchantKey = "", $options=array())
    {
        if (!$strMerchantId) {
            Mage::throwException(
                Mage::helper('paygate')->__('The Datix Cybersource Extension requires a Merchant ID to be included in its configuration settings.')
            );
        }
        if (!$strMerchantKey) {
            Mage::throwException(
                Mage::helper('paygate')->__('The Datix Cybersource Extension requires a Merchant Access Key to be included in its configuration settings.')
            );
        }

        parent::__construct($strMerchantUrl, $options);
        $this->merchantId = $strMerchantId;
        $this->transactionKey = $strMerchantKey;

        $nameSpace = "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd";

        $soapUsername = new SoapVar(
            $this->merchantId,
            XSD_STRING,
            NULL,
            $nameSpace,
            NULL,
            $nameSpace
        );

        $soapPassword = new SoapVar(
            $this->transactionKey,
            XSD_STRING,
            NULL,
            $nameSpace,
            NULL,
            $nameSpace
        );

        $auth = new stdClass();
        $auth->Username = $soapUsername;
        $auth->Password = $soapPassword;

        $soapAuth = new SoapVar(
            $auth,
            SOAP_ENC_OBJECT,
            NULL, $nameSpace,
            'UsernameToken',
            $nameSpace
        );

        $token = new stdClass();
        $token->UsernameToken = $soapAuth;

        $soapToken = new SoapVar(
            $token,
            SOAP_ENC_OBJECT,
            NULL,
            $nameSpace,
            'UsernameToken',
            $nameSpace
        );

        $security =new SoapVar(
            $soapToken,
            SOAP_ENC_OBJECT,
            NULL,
            $nameSpace,
            'Security',
            $nameSpace
        );

        $header = new SoapHeader($nameSpace, 'Security', $security, true);
        $this->__setSoapHeaders(array($header));
    }

    /**
     * @return string The client's merchant ID.
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @return string The client's transaction key.
     */
    public function getTransactionKey()
    {
        return $this->transactionKey;
    }

    /**
     * Returns an object initialized with basic client information.
     *
     * @param string $merchantReferenceCode Desired reference code for the request
     * @return stdClass An object initialized with the basic client info.
     */
    public function createRequest($merchantReferenceCode)
    {
        $request = new stdClass();
        $request->merchantID = $this->merchantId;
        $request->merchantReferenceCode = $merchantReferenceCode;
        $request->clientLibrary = "CyberSource PHP 1.0.0";
        $request->clientLibraryVersion = phpversion();
        $request->clientEnvironment = php_uname();
        return $request;
    }
}