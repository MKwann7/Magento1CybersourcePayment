<?php

// development references: http://apps.cybersource.com/library/documentation/dev_guides/CC_Svcs_SO_API/Credit_Cards_SO_API.pdf
// Extends /app/code/core/Mage/Payment/Model/Method/Abstract.php
class CustomExtensions_Cybersource_Model_Method_Creditcard extends
    Mage_Payment_Model_Method_Abstract
{

    const ACTION_ORDER             = 'order';
    const ACTION_AUTHORIZE         = 'authorize';
    const ACTION_AUTHORIZE_CAPTURE = 'authorize_capture';

    const LIVE_URL = 'https://ics2wsa.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.130.wsdl';
    const TEST_URL = 'https://ics2wstesta.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.130.wsdl';

    const REQUEST_METHOD_CC     = 'CC';
    const REQUEST_METHOD_ECHECK = 'ECHECK';

    const REQUEST_TYPE_AUTH_CAPTURE = 'AUTH_CAPTURE';
    const REQUEST_TYPE_AUTH_ONLY    = 'AUTH_ONLY';
    const REQUEST_TYPE_CAPTURE_ONLY = 'CAPTURE_ONLY';
    const REQUEST_TYPE_CREDIT       = 'CREDIT';
    const REQUEST_TYPE_VOID         = 'VOID';
    const REQUEST_TYPE_PRIOR_AUTH_CAPTURE = 'PRIOR_AUTH_CAPTURE';

    // Codes from: https://support.cybersource.com/cybskb/index?page=content&id=C156#code_table
    const RESPONSE_CODE_APPROVED         = 100; // Successful transaction
    const RESPONSE_CODE_DMISSINGFIELD    = 101; // Declined - The request is missing one or more fields
    const RESPONSE_CODE_DINVALIDDATA     = 102; // Declined - One or more fields in the request contains invalid data
    const RESPONSE_CODE_DDUPLICATE       = 104; // Declined - The merchantReferenceCode sent with this authorization request matches the merchantReferenceCode of another authorization request that you sent in the last 15 minutes.
    const RESPONSE_CODE_SPARTIALAPPROVAL = 110; // Partial amount was approved
    const RESPONSE_CODE_ESYSTEM          = 150; // Error - General system failure.
    const RESPONSE_CODE_ETIMEOUT_1       = 151; // Error - The request was received but there was a server timeout.
    const RESPONSE_CODE_ETIMEOUT_2       = 152; // Error: The request was received, but a service did not finish running in time.
    const RESPONSE_CODE_DAVSNO           = 200; // Error: Address Verification Service failure
    const RESPONSE_CODE_DCALL            = 201; // Error: The issuing bank has questions about the request.
    const RESPONSE_CODE_DCARDEXPIRED     = 202; // Decline - Expired card.
    const RESPONSE_CODE_DCARDREFUSED     = 203; // Decline - General decline of the card. No other information provided by the issuing bank.
    const RESPONSE_CODE_DINSUFFICIENT    = 204; // Decline - Insufficient funds in the account.
    const RESPONSE_CODE_DLOSTSTOLEN      = 205; // Decline - Stolen or lost card.
    const RESPONSE_CODE_DBANKGONE        = 207; // Decline - Issuing bank unavailable.
    const RESPONSE_CODE_DINACTIVECARD    = 208; // Decline - Inactive card or card not authorized for card-not-present transactions.
    const RESPONSE_CODE_DCREDITLIMIT     = 210; // Decline - The card has reached the credit limit.
    const RESPONSE_CODE_DCVNFAIL         = 211; // Decline - Invalid Card Verification Number (CVN).
    const RESPONSE_CODE_DINVALIDDATA_233         = 233; // Decline - Invalid Card Verification Number (CVN).

    const RESPONSE_DELIM_CHAR = '(~)';

    const RESPONSE_REASON_CODE_APPROVED = 1;
    const RESPONSE_REASON_CODE_NOT_FOUND = 16;
    const RESPONSE_REASON_CODE_PARTIAL_APPROVE = 295;
    const RESPONSE_REASON_CODE_PENDING_REVIEW_AUTHORIZED = 252;
    const RESPONSE_REASON_CODE_PENDING_REVIEW = 253;
    const RESPONSE_REASON_CODE_PENDING_REVIEW_DECLINED = 254;

    protected static $_cardsValidation = array (
            array ('name' => 'American Express',
                  'shortname' => 'AE',
                  'length' => '15',
                  'prefixes' => '34,37',
                  'checkdigit' => true
                 ),
            array ('name' => 'Discover',
                  'shortname' => 'DI',
                  'length' => '16',
                  'prefixes' => '6011,622,64,65',
                  'checkdigit' => true
                 ),
            array ('name' => 'JCB',
                  'shortname' => 'JCB',
                  'length' => '16',
                  'prefixes' => '35',
                  'checkdigit' => true
                 ),
            array ('name' => 'Maestro',
                  'shortname' => 'MAE',
                  'length' => '12,13,14,15,16,18,19',
                  'prefixes' => '5018,5020,5038,6304,6759,6761,6762,6763',
                  'checkdigit' => true
                 ),
            array ('name' => 'MasterCard',
                  'shortname' => 'MC',
                  'length' => '16',
                  'prefixes' => '51,52,53,54,55',
                  'checkdigit' => true
                 ),
            array ('name' => 'Solo',
                  'shortname' => 'SO',
                  'length' => '16,18,19',
                  'prefixes' => '6334,6767',
                  'checkdigit' => true
                 ),
            array ('name' => 'Switch',
                  'shortname' => 'SM',
                  'length' => '16,18,19',
                  'prefixes' => '4903,4905,4911,4936,564182,633110,6333,6759',
                  'checkdigit' => true
                 ),
            array ('name' => 'VISA',
                  'shortname' => 'VI',
                  'length' => '16',
                  'prefixes' => '4',
                  'checkdigit' => true
                 )
        );

    /**
     * Key for storing transaction id in additional information of payment model
     * @var string
     */
    protected $_realTransactionIdKey = 'real_transaction_id';
    protected $_code          = 'cybersource';
    protected $_formBlockType = 'cybersourcesoap/form_creditcard';
    protected $_infoBlockType = 'cybersourcesoap/info_creditcard';
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;

    public function assignData ($data)
    {
        Mage::log('Payment assignData START',null,'mwz_payment_saving_testing.log');
        $info = $this->getInfoInstance();

        if ( $data->getCybersourceCreditCardType() )
        {
            Mage::log('Payment CardsData = '.json_encode($data->getCybersourceCreditCardType()),null,'mwz_payment_saving_testing.log');
            $info->setCybersourceCreditCardType($data->getCybersourceCreditCardType());
        }

        if ( $data->getCybersourceCreditCardNumber() )
        {
            Mage::log('Payment CardsData = '.json_encode($data->getCybersourceCreditCardNumber()),null,'mwz_payment_saving_testing.log');
            $info->setCybersourceCreditCardNumber($data->getCybersourceCreditCardNumber());
        }

        if ( $data->getCybersourceExpirationDateMonth() )
        {
            Mage::log('Payment CardsData = '.json_encode($data->getCybersourceExpirationDateMonth()),null,'mwz_payment_saving_testing.log');
            $info->setCybersourceExpirationDateMonth($data->getCybersourceExpirationDateMonth());
        }

        if ( $data->getCybersourceExpirationDateYear() )
        {
            Mage::log('Payment CardsData = '.json_encode($data->getCybersourceExpirationDateYear()),null,'mwz_payment_saving_testing.log');
            $info->setCybersourceExpirationDateYear($data->getCybersourceExpirationDateYear());
        }

        if ( $data->getCybersourceCardVerificationNumber() )
        {
            Mage::log('Payment CardsData = '.json_encode($data->getCybersourceCardVerificationNumber()),null,'mwz_payment_saving_testing.log');
            $info->setCybersourceCardVerificationNumber($data->getCybersourceCardVerificationNumber());
        }

        return $this;
    }
    /**
     * Validate payment method information object
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function validate()
    {
        // DEV: 8568b2d2-da2b-4534-96a4-529663f8e8b7
        // The data handed in here will come from both:
        //      #1 /app/design/adminhtml/default/default/template/cybersource/form/creditcard.phtml
        //      #2 /app/design/frontend/base/default/template/cybersource/form/creditcard.phtml
        // via the "payment[]" paramater array. The names of the fields in the "payment[]" parameter should reflect
        // the XML defined in the config.xml under: config->global->fieldsets->sales_convert_quote_payment

        // This method gets called 3 times:
        //      #1 /app/code/core/Mage/Adminhtml/Model/Sales/Order/Create.php
        //      #2 /app/code/core/Mage/Adminhtml/Model/Sales/Order/Create.php
        //      #3 /home/devdukemagento/public_html/app/code/core/Mage/Sales/Model/Order.php
        // The final time, it will have gone through the validations model setup in the config.xml:
        // /app/code/local/CustomDatixExtensions/Cybersource/etc/config.xml
        // under: config->global->fieldsets->sales_convert_quote_payment
        parent::validate();
        $info = $this->getInfoInstance();

        $errorCode = "";
        $errorMsg = null;
        $bolValidation_Present_CardType = false;
        $bolValidation_Present_CardNumber = false;

        // DEV: 26730683-5a07-4dd0-98eb-97184ca424da
        // these are defined in the XML in /app/code/local/CustomDatixExtensions/Cybersource/etc/config.xml
        // under: config->global->fieldsets->sales_convert_quote_payment
        if ( !$info->getDatixCybersourceCreditCardType() )
        {
            $errorCode = 'invalid_data';
            $errorMsg = $this->_getHelper()->__("Credit Card Type is a required field.<br>");
        }
        else
        {
            // If we have this, we can validate it.
            $bolValidation_Present_CardType = true;
        }
        if ( !$info->getDatixCybersourceCreditCardNumber() )
        {
            $errorCode = 'invalid_data';
            $errorMsg .= $this->_getHelper()->__("Credit Card Number is a required field.<br>");
        }
        else
        {
            // If we have this, we can validate it.
            if ( $bolValidation_Present_CardType == true )
            {
                $objValidatedCardResult = self::validateCcNumberAndType($info);
                if ( !$objValidatedCardResult[0] )
                {
                    $errorCode = 'invalid_data';
                    foreach ( $objValidatedCardResult[1] as $strReturnedErrorText )
                    {

                        $errorMsg .= $this->_getHelper()->__($strReturnedErrorText . "<br>");
                    }
                }
            }
        }
        if ( !$info->getCybersourceExpirationDateMonth() )
        {
            $errorCode = 'invalid_data';
            $errorMsg .= $this->_getHelper()->__("Credit Card Expiration Date Month is a required field.<br>");
        }
        if ( !$info->getCybersourceExpirationDateYear() )
        {
            $errorCode = 'invalid_data';
            $errorMsg .= $this->_getHelper()->__("Credit Card Expiration Date Year is a required field.<br>");
        }
        if ( !$info->getCybersourceCardVerificationNumber() )
        {
            $errorCode = 'invalid_data';
            $errorMsg .= $this->_getHelper()->__("Card Verification Number is a required field.<br>");
        }
        else
        {
            $objValidatedCardResult = self::validateCcVerificationNumber($info);
            if ( !$objValidatedCardResult[0] )
            {
                $errorCode = 'invalid_data';
                foreach ( $objValidatedCardResult[1] as $strReturnedErrorText )
                {

                    $errorMsg .= $this->_getHelper()->__($strReturnedErrorText . "<br>");
                }
            }
        }

        // This is where we are going to be validating credit card information.

        if ( $errorMsg != null )
        {
            Mage::throwException($errorMsg);
        }

        return $this;
    }

    public function validateCcVerificationNumber($info)
    {
        // Set CardIndex as -1 for other or unknown
        $intCardIndex = -1;

        // Check Card Type against Number Pattern
        $strCardType = $info->getCybersourceCreditCardType();

        for ( $currCardIndex = 0; $currCardIndex < sizeof(self::$_cardsValidation); $currCardIndex++ )
        {
            // See if it is this card (ignoring the case of the string)
            if ( strtolower($strCardType) == strtolower(self::$_cardsValidation[$currCardIndex]['shortname']) )
            {
                $intCardIndex = $currCardIndex;
                break;
            }
        }

        if ( $intCardIndex == -1 )
        {
            // Card Type Not Registered/Not Known
        }

        $strCardVerificationNumber = $info->getCybersourceCardVerificationNumber();

        if (!ctype_digit($strCardVerificationNumber))
        {
            // The CVN is contains non-digitls. Throw an error.
            return array(0 => false, 1 => array("Card verification number must be numeric."));
        }

        switch(strtolower(self::$_cardsValidation[$intCardIndex]['shortname']))
        {
            case 'ae':
                if (strlen($strCardVerificationNumber) != 4 )
                {
                    // Throw an error
                    return array(0 => false, 1 => array("Card verification number isn't the correct length."));
                }
                break;
            default:
                if (strlen($strCardVerificationNumber) != 3 )
                {
                    // Throw an error
                    return array(0 => false, 1 => array("Card verification number isn't the correct length."));
                }
                break;
        }

        return array(0 => true, 1 => array('success'));
    }
    public function validateCcNumberAndType($info)
    {
        $strCreditCardNumber = $info->getCybersourceCreditCardNumber();
        $strCreditCardNumber = str_replace(array('-'),'',$strCreditCardNumber);
        $info->setCybersourceCreditCardNumber($strCreditCardNumber);

        // Check Card Type against Number Pattern
        $strCardType = $info->getCybersourceCreditCardType();

        // Test this card type.
        $ccErrorNo = 0;

        $ccErrors [0] = "Unknown card type.";
        $ccErrors [1] = "Credit card number has invalid format.";
        $ccErrors [2] = "Credit card number is invalid.";
        $ccErrors [3] = "Credit card number is wrong length.";
        $ccErrors [4] = "Credit card number does not match type.";

        // Set CardIndex as -1 for other or unknown
        $intCardIndex = -1;

        for ( $currCardIndex = 0; $currCardIndex < sizeof(self::$_cardsValidation); $currCardIndex++ )
        {
            // See if it is this card (ignoring the case of the string)
            if ( strtolower($strCardType) == strtolower(self::$_cardsValidation[$currCardIndex]['shortname']) )
            {
                $intCardIndex = $currCardIndex;
                break;
            }
        }

        // Check that the number is numeric and of the right sort of length.
        if ( !preg_match("/^[0-9]{13,19}$/", $strCreditCardNumber) )
        {
            $intErrorNumber = 1;
            $strErrorText   = $ccErrors [$intErrorNumber];
            return array(0 => false, 1 => array($strErrorText));
        }

        // Now check the modulus 10 check digit - if required
        if ( self::$_cardsValidation[$intCardIndex]['checkdigit'] )
        {
            $intCheckSum              = 0;                               // running checksum total
            $mychar                   = "";                                   // next char to process
            $bolAlternateDigitsSwitch = 1;                  // takes value of 1 or 2

            // Process each digit one by one starting at the right
            for ( $currCreditCardDigitIndex = strlen($strCreditCardNumber) - 1; $currCreditCardDigitIndex >= 0; $currCreditCardDigitIndex-- )
            {

                // Extract the next digit and multiply by 1 or 2 on alternative digits.
                $currNextCcDigit = $strCreditCardNumber{$currCreditCardDigitIndex} * $bolAlternateDigitsSwitch;

                // If the result is in two digits add 1 to the checksum total
                if ( $currNextCcDigit > 9 )
                {
                    $intCheckSum     = $intCheckSum + 1;
                    $currNextCcDigit = $currNextCcDigit - 10;
                }

                // Add the units element to the checksum total
                $intCheckSum = $intCheckSum + $currNextCcDigit;

                // Switch the value of j
                if ( $bolAlternateDigitsSwitch == 1 )
                {
                    $bolAlternateDigitsSwitch = 2;
                }
                else
                {
                    $bolAlternateDigitsSwitch = 1;
                }
            }

            // All done - if checksum is divisible by 10, it is a valid modulus 10.
            // If not, report an error.
            if ( $intCheckSum % 10 != 0 )
            {
                $intErrorNumber  = 2;
                $strErrorText = $ccErrors [$intErrorNumber];
                return array(0 => false, 1 => array($strErrorText));
            }
        }

        // The following are the card-specific checks we undertake.

        // Load an array with the valid prefixes for this card
        $prefix = explode(',', self::$_cardsValidation[$intCardIndex]['prefixes']);

        // Now see if any of them match what we have in the card number
        $PrefixValid = false;
        for ( $currPrefixTestIndex = 0; $currPrefixTestIndex < sizeof($prefix); $currPrefixTestIndex++ )
        {
            $exp = '/^' . $prefix[$currPrefixTestIndex] . '/';
            if ( preg_match($exp, $strCreditCardNumber) )
            {
                $PrefixValid = true;
                break;
            }
        }

        // If it isn't a valid prefix there's no point at looking at the length
        if ( !$PrefixValid )
        {
            $intErrorNumber  = 4;
            $strErrorText = $ccErrors [$intErrorNumber];
            return array(0 => false, 1 => array($strErrorText));
        }

        // See if the length is valid for this card
        $LengthValid = false;
        $lengths     = explode(',', self::$_cardsValidation[$intCardIndex]['length']);
        for ( $currLengthTestIndex = 0; $currLengthTestIndex < sizeof($lengths); $currLengthTestIndex++ )
        {
            if ( strlen($strCreditCardNumber) == $lengths[$currLengthTestIndex] )
            {
                $LengthValid = true;
                break;
            }
        }

        // See if all is okay by seeing if the length was valid.
        if ( !$LengthValid )
        {
            $intErrorNumber  = 3;
            $strErrorText   = $ccErrors [$intErrorNumber];
            return array(0 => false, 1 => array($strErrorText));
        };

        return array(0 => true, 1 => array('success'));
    }

    /**
     * Check authorise availability
     *
     * @return bool
     */
    public function canAuthorize()
    {
        return $this->_canAuthorize;
    }

    /**
     * Check capture availability
     *
     * @return bool
     */
    public function canCapture()
    {
        return $this->_canCapture;
    }

    /**
     * Authorize payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        // DEV: 74518f3a-a589-4805-aa4e-d319f5530ead
        if (!$this->canAuthorize()) {
            Mage::throwException(Mage::helper('payment')->__('Authorize action is not available.'));
        }
        if ($amount <= 0) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid amount for authorization.'));
        }

        $this->_initCardsStorage($payment);

        $this->_place($payment, $amount, self::REQUEST_TYPE_AUTH_ONLY);
        $payment->setSkipTransactionCreation(true);
        return $this;
    }

    /**
     * Capture payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        // DEV: 562a39fe-3624-499a-bab5-2bb49c5ec937
        if (!$this->canCapture()) {
            Mage::throwException(Mage::helper('payment')->__('Capture action is not available.'));
        }
        if ($amount <= 0) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid amount for capture.'));
        }


        Mage::log("Capture FINISH",null,"cybersource_payment_tracing.log");
        return $this;
    }

    /**
     * Send request with new payment to gateway
     *
     * @param Mage_Payment_Model_Info $payment
     * // /app/code/core/Mage/Sales/Model/Order/Payment.php
     * @param decimal $amount
     * @param string $requestType
     * @return Mage_Paygate_Model_Authorizenet
     * @throws Mage_Core_Exception
     */
    protected function _place(Mage_Payment_Model_Info $payment, $amount, $requestType)
    {
        // This is where we build and post the request to Cybersource.
        // We use Magneto's Varien object to build the data object in the _buildRequest method, so we can put it back into
        // This Object is from Mage_Payment_Model_Info, /app/code/core/Mage/Sales/Model/Order/Payment.php
        // It references $this->_init('sales/order_payment'); specifically the sales_flat_order_payment table
        // Magneto and complete the order creation/error process, but we also extract the important data and put
        // it into the Cybersource Simple Order SOAP API in _postRequest, based on the class pulled in via the
        // CybsSoapClient class at the bottom.
        $payment->setAnetTransType($requestType);
        $payment->setAmount($amount);
        $request= $this->_buildRequest($payment);
        $result = $this->_postRequest($request,$requestType);

        // This piece of code sets the cc_trans_id of the sales/order_payment line item referenced by $payment.
        // I'm not sure if cc_trans_id is a universal Magento field, or if we added it as a custom field, so
        // this might need to be handled differently when we build this for our clients.
        // Customization Guid: d00d704a-ff1c-4a79-9582-64ea2b53e6cb
        $payment->setCcTransId($result->getTransactionId());

        // Based on the request type, define the type and error message for Magento, for pass/fail purposes.
        switch ($requestType) {
            case self::REQUEST_TYPE_AUTH_ONLY:
                $newTransactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
                $defaultExceptionMessage = Mage::helper('paygate')->__('Payment authorization error.');
                break;
            case self::REQUEST_TYPE_AUTH_CAPTURE:
                $newTransactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
                $defaultExceptionMessage = Mage::helper('paygate')->__('Payment capturing error.');
                break;
        }

        // Record Response Code for display, if applicable.
        // Based on the following Cybersource documentation
        // https://support.cybersource.com/cybskb/index?page=content&id=C156&pmv=print&impressions=false
        $reasonResponseText = "";
        switch($result->getResponseCode())
        {
            case self::RESPONSE_CODE_DMISSINGFIELD:
                $reasonResponseText = "101 - Declined - The request is missing one or more fields";
                break;
            case self::RESPONSE_CODE_DINVALIDDATA:
                $reasonResponseText = "102 - Declined - One or more fields in the request contains invalid data";
                break;
            case self::RESPONSE_CODE_DDUPLICATE:
                $reasonResponseText = "104 - Declined - The merchantReferenceCode sent with this authorization request matches the merchantReferenceCode of another authorization request that you sent in the last 15 minutes.";
                break;
            case self::RESPONSE_CODE_ESYSTEM:
                $reasonResponseText = "150 - Error - General system failure.";
                break;
            case self::RESPONSE_CODE_ETIMEOUT_1:
                $reasonResponseText = "151 - Error - The request was received but there was a server timeout.";
                break;
            case self::RESPONSE_CODE_ETIMEOUT_2:
                $reasonResponseText = "152 - Error: The request was received, but a service did not finish running in time.";
                break;
            case self::RESPONSE_CODE_DAVSNO:
                $reasonResponseText = "200 - Error: Address Verification Service failure";
                break;
            case self::RESPONSE_CODE_DCALL:
                $reasonResponseText = "201 - Error: The issuing bank has questions about the request.";
                break;
            case self::RESPONSE_CODE_DCARDEXPIRED:
                $reasonResponseText = "202 - Error: The issuing bank has questions about the request.";
                break;
            case self::RESPONSE_CODE_DCARDREFUSED:
                $reasonResponseText = "203 - Error: The issuing bank has questions about the request.";
                break;
            case self::RESPONSE_CODE_DINSUFFICIENT:
                $reasonResponseText = "204 - Error: The issuing bank has questions about the request.";
                break;
            case self::RESPONSE_CODE_DLOSTSTOLEN:
                $reasonResponseText = "205 - Error: The issuing bank has questions about the request.";
                break;
            case self::RESPONSE_CODE_DBANKGONE:
                $reasonResponseText = "207 - Error: The issuing bank has questions about the request.";
                break;
            case self::RESPONSE_CODE_DINACTIVECARD:
                $reasonResponseText = "208 - Error: The issuing bank has questions about the request.";
                break;
            case self::RESPONSE_CODE_DCREDITLIMIT:
                $reasonResponseText = "210 - Error: The issuing bank has questions about the request.";
                break;
            case self::RESPONSE_CODE_DCVNFAIL:
                $reasonResponseText = "211 - Error: The issuing bank has questions about the request.";
                break;
            case self::RESPONSE_CODE_DINVALIDDATA_233:
                $reasonResponseText = "233 - Error: Request a different card or other form of payment.";
                break;
        }

        // Following the communication with the gateway, proceed with the received response code
        switch ($result->getResponseCode())
        {
            // 100 - Successful transaction
            case self::RESPONSE_CODE_APPROVED:
                // Log Success in cybersource_ext_card_error.log
                Mage::log('SUCCESS: '.$result->getResponseCode().' - ErpCustID = '.$request->getXCustomerId().' - '.$request->getXShipToFirstName().' '.$request->getXShipToLastName().' ('.$request->getXEmail().') CARD: XX'.substr($request->getXCardNum(),-4).' Exp: '.$request->getXExpDate().' <'.getUserIP().'>',null,"cybersource_ext_card_success.log");

                // get the card from memory so we can store the transaction
                $this->getCardsStorage($payment)->flushCards();
                $card = $this->_registerCard($result, $payment);

                // add the transaction to the Mage Transaction System
                $this->_addTransaction(
                    $payment,
                    $card->getLastTransId(),
                    $newTransactionType,
                    array('is_transaction_closed' => 0),
                    array($this->_realTransactionIdKey => $card->getLastTransId()),
                    Mage::helper('paygate')->getTransactionMessage(
                        $payment, $requestType, $card->getLastTransId(), $card, $amount
                    )
                );
                if ($requestType == self::REQUEST_TYPE_AUTH_CAPTURE) {
                    $card->setCapturedAmount($card->getProcessedAmount());
                    $this->getCardsStorage($payment)->updateCard($card);
                }
                return $this;
                break;

            // Codes from: https://support.cybersource.com/cybskb/index?page=content&id=C156#code_table
            // Comments based on the following Cybersource documentation
            // https://support.cybersource.com/cybskb/index?page=content&id=C156&pmv=print&impressions=false
            case self::RESPONSE_CODE_DMISSINGFIELD: // 101 - Declined - The request is missing one or more fields
            case self::RESPONSE_CODE_DINVALIDDATA: // 102 - Declined - One or more fields in the request contains invalid data
            case self::RESPONSE_CODE_DDUPLICATE: // 104 - Declined - The merchantReferenceCode sent with this authorization request matches the merchantReferenceCode of another authorization request that you sent in the last 15 minutes.
            case self::RESPONSE_CODE_ESYSTEM: // 150 - Error - General system failure.
            case self::RESPONSE_CODE_ETIMEOUT_1: // 151 - Error - The request was received but there was a server timeout.
            case self::RESPONSE_CODE_ETIMEOUT_2: // 152 - Error: The request was received, but a service did not finish running in time.
            case self::RESPONSE_CODE_DAVSNO: // 200 - Error: Address Verification Service failure
            case self::RESPONSE_CODE_DCALL: // 201 - Error: The issuing bank has questions about the request.
            case self::RESPONSE_CODE_DCARDEXPIRED: // 202 - Error: The issuing bank has questions about the request.
            case self::RESPONSE_CODE_DCARDREFUSED: // 203 - Error: The issuing bank has questions about the request.
            case self::RESPONSE_CODE_DINSUFFICIENT: // 204 - Error: The issuing bank has questions about the request.
            case self::RESPONSE_CODE_DLOSTSTOLEN: // 205 - Error: The issuing bank has questions about the request.
            case self::RESPONSE_CODE_DBANKGONE: // 207 - Error: The issuing bank has questions about the request.
            case self::RESPONSE_CODE_DINACTIVECARD: // 208 - Error: The issuing bank has questions about the request.
            case self::RESPONSE_CODE_DCREDITLIMIT: // 210 - Error: The issuing bank has questions about the request.
            case self::RESPONSE_CODE_DCVNFAIL: // 211 - Error: The issuing bank has questions about the request.
            case self::RESPONSE_CODE_DINVALIDDATA_233: // 233 - Error: Request a different card or other form of payment.
                // Log Error in cybersource_ext_card_error.log
                Mage::log('ERROR CODE: '.$result->getResponseCode().' '.$reasonResponseText.' - '.$request->getXShipToFirstName().' '.$request->getXShipToLastName().' ('.$request->getXEmail().') CARD: XX'.substr($request->getXCardNum(),-4).' Exp: '.$request->getXExpDate().' <'.getUserIP().'>',null,"cybersource_ext_card_error.log");

                // We're using $reasonResponseText, which was defined above, based on the
                Mage::throwException($this->_wrapGatewayError($result->getResponseReasonText().' '.$reasonResponseText));
                break;

            default:
                Mage::log('GENERAL ERROR: '.$result->getResponseCode().' - '.$request->getXShipToFirstName().' '.$request->getXShipToLastName().' ('.$request->getXEmail().') CARD: XX'.substr($request->getXCardNum(),-4).' Exp: '.$request->getXExpDate().' <'.getUserIP().'>',null,"cybersource_ext_card_error.log");
                Mage::throwException($result->getResponseCode().' '.$defaultExceptionMessage);
                break;
        }
        return $this;
    }

    /**
     * Prepare request to gateway
     *
     * @link http://www.authorize.net/support/AIM_guide.pdf
     * @param Mage_Payment_Model_Info $payment
     * @return Mage_Paygate_Model_Authorizenet_Request
     */
    protected function _buildRequest(Varien_Object $payment)
    {
        // Get the full order object from the Mage_Sales_Order Varien Object
        $order = $payment->getOrder();

        // Set the current store ID
        $this->setStore($order->getStoreId());

        // Create the Request Object from Mage::getModel('cybersourcesoap/request')
        // or /app/code/local/CustomDatixExtensions/Cybersource/Model/Request.php
        $request = $this->_getRequest()
            ->setXType($payment->getAnetTransType())
            ->setXMethod(self::REQUEST_METHOD_CC);

        // Set the Invoice Number with the next increment ID
        if ($order && $order->getIncrementId()) {
            $request->setXInvoiceNum($order->getIncrementId());
        }

        // Set the full payment amount and currency code from the Varien_Object payment passed in to this method
        if($payment->getAmount()){
            $request->setXAmount($payment->getAmount(),2);
            $request->setXCurrencyCode($order->getBaseCurrencyCode());
        }

        // switch on the Request Type defined in the Varien_Object payment object.
        switch ($payment->getAnetTransType())
        {
            case self::REQUEST_TYPE_AUTH_CAPTURE:
                // Request Authorize and Capture from Cybersource
                $request->setXAllowPartialAuth($this->getConfigData('allow_partial_authorization') ? 'True' : 'False');
                if ($payment->getAdditionalInformation($this->_splitTenderIdKey))
                {
                    $request->setXSplitTenderId($payment->getAdditionalInformation($this->_splitTenderIdKey));
                }
                break;
            case self::REQUEST_TYPE_AUTH_ONLY:
                // Request Authorize ONLY from Cybersource
                $request->setXAllowPartialAuth($this->getConfigData('allow_partial_authorization') ? 'True' : 'False');
                if ($payment->getAdditionalInformation($this->_splitTenderIdKey))
                {
                    $request->setXSplitTenderId($payment->getAdditionalInformation($this->_splitTenderIdKey));
                }
                break;
            case self::REQUEST_TYPE_CREDIT:
                // Send last 4 digits of credit card number to cybersource otherwise it will give an error
                $request->setXCardNum($payment->getCcLast4());
                $request->setXTransId($payment->getXTransId());
                break;
            case self::REQUEST_TYPE_VOID:
                $request->setXTransId($payment->getXTransId());
                break;
            case self::REQUEST_TYPE_PRIOR_AUTH_CAPTURE:
                $request->setXTransId($payment->getXTransId());
                break;
            case self::REQUEST_TYPE_CAPTURE_ONLY:
                $request->setXAuthCode($payment->getCcAuthCode());
                break;
        }

        // TODO: Not sure what this does yet.
        if ($this->getIsCentinelValidationEnabled())
        {
            $params  = $this->getCentinelValidator()->exportCmpiData(array());
            $request = Varien_Object_Mapper::accumulateByMap($params, $request, $this->_centinelFieldMap);
        }

        // Check for Existing Quote in Database Based on Login ID
        $resource       = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $tableName      = $resource->getTableName('customer_entity_varchar');

        // 136 is the attribute id for the erp_custid column in the quoteadv_customer table.
        $query          = "SELECT * FROM `" . $tableName . "` WHERE entity_id = '" . $order->getCustomerId() . "' AND attribute_id = 136 LIMIT 1";
        $results = $readConnection->fetchAll($query);

        // Retrieve the ErpCustId
        $ErpCustId = "0";
        if ( !empty($results) )
        {
            foreach ( $results as $resultID => $resultData )
            {
                // set $ErpCustId to the ErpCustId value.
                $ErpCustId = $resultData['value'];
                break;
            }
        }
        //Mage::log('GETTING ERP_CUSTID: '.$ErpCustId,null,"cybersource_ext_card_success.log");

        // Construct the Billing information, pulled from Mages_Sales_Order object.
        // The default billing and shipping information is returned
        if (!empty($order))
        {
            $billing = $order->getBillingAddress();
            if (!empty($billing))
            {
                $request->setXCustomerId($ErpCustId)
                    ->setXFirstName($billing->getFirstname())
                    ->setXLastName($billing->getLastname())
                    ->setXCompany($billing->getCompany())
                    ->setXAddress($billing->getStreet(1))
                    ->setXCity($billing->getCity())
                    ->setXState($billing->getRegion())
                    ->setXZip($billing->getPostcode())
                    ->setXCountry($billing->getCountry())
                    ->setXPhone($billing->getTelephone())
                    ->setXFax($billing->getFax())
                    ->setXCustId($order->getCustomerId())
                    ->setXCustomerIp($order->getRemoteIp())
                    ->setXCustomerTaxId($billing->getTaxId())
                    ->setXEmail($order->getCustomerEmail())
                    ->setXEmailCustomer($this->getConfigData('email_customer'))
                    ->setXMerchantEmail($this->getConfigData('merchant_email'));
            }

            $shipping = $order->getShippingAddress();
            if (!empty($shipping))
            {
                $request->setXShipToFirstName($shipping->getFirstname())
                    ->setXShipToLastName($shipping->getLastname())
                    ->setXShipToCompany($shipping->getCompany())
                    ->setXShipToAddress($shipping->getStreet(1))
                    ->setXShipToCity($shipping->getCity())
                    ->setXShipToState($shipping->getRegion())
                    ->setXShipToZip($shipping->getPostcode())
                    ->setXShipToCountry($shipping->getCountry());
            }

            $request->setXPoNum($payment->getPoNumber())
                ->setXTax($order->getBaseTaxAmount())
                ->setXFreight($order->getBaseShippingAmount());
        }

        // This is where we retrieve the data we created in our php install file, and collect from the admin and front end
        // payment forms.
        if($payment->getDatixCybersourceCreditCardNumber()){
            $request->setXCardNum($payment->getDatixCybersourceCreditCardNumber())
                ->setXExpDate(sprintf('%02d-%04d', $payment->getDatixCybersourceExpirationDateMonth(), $payment->getDatixCybersourceExpirationDateYear()))
                ->setXCardType($payment->getDatixCybersourceCreditCardType())
                ->setXCardCode($payment->getDatixCybersourceCardVerificationNumber());
        }

        return $request;
    }

    /**
     * Post request to gateway and return response
     *
     * @param Mage_Paygate_Model_Authorizenet_Request $request)
     * @return Mage_Paygate_Model_Authorizenet_Result
     */
    protected function _postRequest(Varien_Object $objVarienRequest, $requestType)
    {

        $debugData = array('request' => $objVarienRequest->getData());

        $result = Mage::getModel('cybersource/result');

        $strMerchantId = Mage::getStoreConfig('payment/cybersource/merchant_id',Mage::app()->getStore());

        // Set Active/Test Mode
        $strTestMode = Mage::getStoreConfig('payment/cybersource/test_mode',Mage::app()->getStore());;

        // Set url based on test mode.
        // This defaults to live
        switch(strtolower($strTestMode))
        {
            case "test":
                // apply live url to request
                $uri = $this->getConfigData('test_url');
                $strMerchantUrl = ($uri ? $uri : self::TEST_URL);
                $strTransactionSecurityKey = Mage::getStoreConfig('payment/cybersource/transaction_security_key_test',Mage::app()->getStore());
                break;
            default:
                // apply live url to request
                $uri = $this->getConfigData('live_url');
                $strMerchantUrl = ($uri ? $uri : self::LIVE_URL);
                $strTransactionSecurityKey = Mage::getStoreConfig('payment/cybersource/transaction_security_key_live',Mage::app()->getStore());
                break;
        }

        //$strMerchantUrl = "https://ics2wsa.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.130.wsdl";
        //$strMerchantUrl = "https://ics2wstesta.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.130.wsdl";
        //$strMerchantId = "wfgdukemfgusd";
        //$strTransactionSecurityKey = "6a2Z3wVAbo4BSo+HIB6WKepoNulMRqnnLllNGo/HwXKpfjpVLgZXmKtRRv0IsgyOEacdBKiQiP9GKsnih2aZ/vH+be81928H035qV5c6HGD5DBcixKSA3acSC2CMeGKpcd7CAhGRbDHhR+IdFOWEPMPOMle1NqIHFHDOZBx1uzbzwhYPlqaLxRtI2tOBcZYMhzXw6q8BRV4gMQVBSZH9IgLxsr0dl0VhLGPnShztOGIrdZKyfK/psknkuzh1Q3wvEOflUjWl37Yc7iLi2pXREQuXcYe1ASMq5RnODBn7kuCMsBNFS8EuM63qr62uGRGoPKh5wnF3ivYTb5nckxtJEQ==";
        //$strTransactionSecurityKey = "5pYZ8DmSEYKRWYjRTHoybtI6atJmdo9dzY5NAMdjltYcWjjGzpHstBPWVkHEQj9P1F3Z9BMxkfAsQXrGdt1eQKtj6oioGKR9qd54H8bqsFWMqTZ/XIZhB5GvsbpaQxyPfECQF9igtytfwuC/vmzs8FxlIinnPj1G25owjGGBTnVdGV6J49DQAcR7imCJKdI7rK73fMhASXDyFUVXQSuQSO9orTHEGQAKWdZgYDvhpxkDGRIysWwb07slSMfapaAHNkoiaJ1yqaSxxGDJGjY/J2e+YmrzX8g0SArf/t5onthLh0Y4KbLqF4NFYwJ0Q6nSf5fZNGXKo+BwscJ5CpR5cQ==";

        Mage::log('Start Authentication:',null,'mwz_payment-capture.log');
        Mage::log('Merchant Id: '.$strMerchantId,null,'mwz_payment-capture.log');
        Mage::log('Transaction Security Key: '.$strTransactionSecurityKey,null,'mwz_payment-capture.log');
        Mage::log('Test Mode: '.$strTestMode,null,'mwz_payment-capture.log');
        Mage::log('Merchant URL: '.$strMerchantUrl,null,'mwz_payment-capture.log');

        $client = new CybsSoapClient($strMerchantUrl,$strMerchantId,$strTransactionSecurityKey);

        $request = $client->createRequest($objVarienRequest->getXInvoiceNum());

        $ccAuthService = new stdClass();
        $ccAuthService->run = 'true';
        $request->ccAuthService = $ccAuthService;

        // These are pulled from the XSD file for 1.1.30
        // https://ics2wsa.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.130.xsd
        $billTo = new stdClass();
        $billTo->customerID = $objVarienRequest->getXCustomerId();
        $billTo->firstName = $objVarienRequest->getXShipToFirstName();
        $billTo->lastName = $objVarienRequest->getXShipToLastName();
        $billTo->street1 = $objVarienRequest->getXAddress();
        $billTo->city = $objVarienRequest->getXCity();
        $billTo->state = $objVarienRequest->getXState();
        $billTo->postalCode = $objVarienRequest->getXZip();
        $billTo->country = $objVarienRequest->getXCountry();
        $billTo->email = $objVarienRequest->getXEmail();
        $billTo->ipAddress = getUserIP();
        $request->billTo = $billTo;

        // Create new Card Class for loading the payment account information
        $arCcExpirationDate = explode("-",$objVarienRequest->getXExpDate());
        Mage::log('Number: '.$objVarienRequest->getXCardNum(),null,'mwz_payment-capture.log');
        Mage::log('Month: '.$arCcExpirationDate[0],null,'mwz_payment-capture.log');
        Mage::log('Year: '.$arCcExpirationDate[1],null,'mwz_payment-capture.log');
        Mage::log('CVN: '.$objVarienRequest->getXCardCode(),null,'mwz_payment-capture.log');

        $card = new stdClass();
        $card->accountNumber = $objVarienRequest->getXCardNum();
        $card->cvNumber = $objVarienRequest->getXCardCode();

            $card->expirationMonth = $arCcExpirationDate[0];
            $card->expirationYear = $arCcExpirationDate[1];
        $request->card = $card;


        // Duke Customization Guid: ab312a78-1607-4db1-be82-72f0660c5005
        // This customization adds $10 to the total bill to make sure there isn't
        // any value lost due to currency value translation when the card is actually processed
        // following fulfillment.
        // This is a temporary authorization request and does not increase the value of the
        // products themselves.
        //$prGrandTotalAmount = $objVarienRequest->getXAmount();
        //$prGrandTotalAmount += 10;

        $purchaseTotals = new stdClass();
        $purchaseTotals->currency = $objVarienRequest->getXCurrencyCode();
        $purchaseTotals->grandTotalAmount = $prGrandTotalAmount;
        $request->purchaseTotals = $purchaseTotals;

        // This is it. Process that request!
        // This sends the payment request via the Cybersource SOAP API and returns it to $reply.
        // I've included examples of the return data below, assuming there isn't an HTTPS connection issue.
        try
        {
            $reply = $client->runTransaction($request);
        }
        catch (Exception $e)
        {
            $result->setResponseCode(-1)
                ->setResponseReasonCode($e->getCode())
                ->setResponseReasonText($e->getMessage());

            $debugData['result'] = $result->getData();
            $this->_debug($debugData);
            Mage::throwException($this->_wrapGatewayError($e->getMessage()));
        }

        // EXAMPLE OF RETURNED ERROR DATA:

        // $reply['requestID'] = 4752658130626864704011
        // $reply['decision'] = 'REJECT'
        // $reply['reasonCode'] = '101'
        // $reply['missingField'] = 'c:merchantReferenceCode'
        // $reply['requestToken'] = 'AhhTbwSTAaRhjSSmU2oLEAHSGIJGXSTKuj0kabQEwAAAjgVD'

        // EXAMPLE OF RETURNED SUCCESS DATA:

        // $reply['merchantReferenceCode'] = 100000250
        // $reply['requestID'] = 4752663282416930904012
        // $reply['decision'] = ACCEPT
        // $reply['reasonCode'] = 100
        // $reply['requestToken'] = Ahj/7wSTAaRz2qs/LdfMEBTFg2btWrBk3cJcbWZaEAClxtZloQaQP6lzEFDJpJlXR6SNNsCcmA0jntVZ+W6+YAAA0whN
        // $reply['purchaseTotals']['currency'] = 'USD'
        // $reply['ccAuthReply']['reasonCode'] = 100
        // $reply['ccAuthReply']['amount'] = 299.98
        // $reply['ccAuthReply']['authorizationCode'] = 123456
        // $reply['ccAuthReply']['avsCode'] = 'Y'
        // $reply['ccAuthReply']['avsCodeRaw'] = 'YYY'
        // $reply['ccAuthReply']['authorizedDateTime'] = '2016-09-30T20:12:08Z'
        // $reply['ccAuthReply']['processorResponse'] = 'A'
        // $reply['ccAuthReply']['reconciliationID'] = 1067550278

        // So this fires off if the request comes back correctly.
        // It should always have a reason code, so if the processing is correct,
        // this should always be true.
        //
        // Some of these, like setDescription, setTransactionType, etc, are a part of the
        // Mage transaction model, but not delivered to us from Cybersource. Now, we could
        // pass in details about the product that was purchased, but we haven't brought that
        // into this method yet....
        Mage::log('Authentication Code: '.$reply->reasonCode,null,'mwz_payment-capture.log');
        if ($reply->reasonCode)
        {
            $result->setResponseCode($reply->reasonCode)
                ->setResponseReasonText($reply->decision)
                ->setApprovalCode($reply->reasonCode)
                ->setAvsResultCode($reply->ccAuthReply->avsCode)
                ->setTransactionId($reply->requestID)
                //->setInvoiceNumber($reply->requestID)
                //->setDescription($reply)
                ->setAmount($reply->amount)
                ->setMethod($requestType)
                //->setTransactionType($reply)
                ->setCustomerId($objVarienRequest->getXCustId())
                //->setMd5Hash($reply)
                ->setCardCodeResponseCode($reply->ccAuthReply->cvCode)
                //->setCAVVResponseCode( (isset($r[39])) ? $r[39] : null)
                //->setSplitTenderId($r[52])
                //->setAccNumber($r[50])
                ->setCardType($objVarienRequest->getXCardType())
                ->setRequestedAmount($reply->amount);
        }
        else
        {
            Mage::throwException(
                Mage::helper('paygate')->__('Error in payment gateway.')
            );
        }

        // This assigns the result data to the debug result for debugging...
        $debugData['result'] = $result->getData();
        $this->_debug($debugData);

        return $result;
    }



    /**
     * Return cybersource payment request
     *
     * @return CustomDatixExtensions_Cybersource_Model_Request
     */
    protected function _getRequest()
    {
        $request = Mage::getModel('cybersource/request')
            ->setXVersion(1.0)
            ->setXDelimData('True')
            ->setXRelayResponse('False')
            ->setXTestRequest($this->getConfigData('test') ? 'TRUE' : 'FALSE')
            ->setXLogin($this->getConfigData('login'))
            ->setXTranKey($this->getConfigData('trans_key'));

        return $request;
    }

    /**
     * Init cards storage model
     *
     * @param Mage_Payment_Model_Info $payment
     */
    protected function _initCardsStorage($payment)
    {
        $this->_cardsStorage = Mage::getModel('cybersource/cards')->setPayment($payment);
    }

    /**
     * Return cards storage model
     *
     * @param Mage_Payment_Model_Info $payment
     * @return Mage_Paygate_Model_Authorizenet_Cards
     */
    public function getCardsStorage($payment = null)
    {
        if (is_null($payment)) {
            $payment = $this->getInfoInstance();
        }
        if (is_null($this->_cardsStorage)) {
            $this->_initCardsStorage($payment);
        }
        return $this->_cardsStorage;
    }

    /**
     * It sets card`s data into additional information of payment model
     *
     * @param Mage_Paygate_Model_Authorizenet_Result $response
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Varien_Object
     */
    protected function _registerCard(Varien_Object $response, Mage_Sales_Model_Order_Payment $payment)
    {

        $cardsStorage = $this->getCardsStorage($payment);
        $card = $cardsStorage->registerCard();
        $card
            ->setRequestedAmount($response->getRequestedAmount())
            ->setBalanceOnCard($response->getBalanceOnCard())
            ->setLastTransId($response->getTransactionId())
            ->setProcessedAmount($response->getAmount())
            ->setCcType($payment->getCcType())
            ->setCcOwner($payment->getCcOwner())
            ->setCcLast4($payment->getCcLast4())
            ->setCcExpMonth($payment->getCcExpMonth())
            ->setCcExpYear($payment->getCcExpYear())
            ->setCcSsIssue($payment->getCcSsIssue())
            ->setCcSsStartMonth($payment->getCcSsStartMonth())
            ->setCcSsStartYear($payment->getCcSsStartYear());

        $cardsStorage->updateCard($card);
        $this->_clearAssignedData($payment);
        return $card;
    }

    /**
     * Reset assigned data in payment info model
     *
     * @param Mage_Payment_Model_Info
     * @return Mage_Paygate_Model_Authorizenet
     */
    private function _clearAssignedData($payment)
    {
        $payment->setCcType(null)
            ->setCcOwner(null)
            ->setCcLast4(null)
            ->setCcNumber(null)
            ->setCcCid(null)
            ->setCcExpMonth(null)
            ->setCcExpYear(null)
            ->setCcSsIssue(null)
            ->setCcSsStartMonth(null)
            ->setCcSsStartYear(null)
        ;
        return $this;
    }

    /**
     * Add payment transaction
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param string $transactionId
     * @param string $transactionType
     * @param array $transactionDetails
     * @param array $transactionAdditionalInfo
     * @return null|Mage_Sales_Model_Order_Payment_Transaction
     */
    protected function _addTransaction(Mage_Sales_Model_Order_Payment $payment, $transactionId, $transactionType,
                                       array $transactionDetails = array(), array $transactionAdditionalInfo = array(), $message = false
    ) {
        $payment->setTransactionId($transactionId);
        $payment->resetTransactionAdditionalInfo();
        foreach ($transactionDetails as $key => $value) {
            $payment->setData($key, $value);
        }
        foreach ($transactionAdditionalInfo as $key => $value) {
            $payment->setTransactionAdditionalInfo($key, $value);
        }
        $transaction = $payment->addTransaction($transactionType, null, false , $message);
        foreach ($transactionDetails as $key => $value) {
            $payment->unsetData($key);
        }
        $payment->unsLastTransId();

        /**
         * It for self using
         */
        $transaction->setMessage($message);

        return $transaction;
    }

    /**
     * Get config payment action url
     * Used to universalize payment actions when processing payment place
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        // DEV: a5ec55c8-f9de-491e-9a87-2730434d0682
        $strPaymentAction = Mage::getStoreConfig('payment/cybersource/payment_action',Mage::app()->getStore());
        if (empty($strPaymentAction)) {
            $strPaymentAction = self::ACTION_AUTHORIZE;
        }
        return $strPaymentAction;
    }
}

/**
 * Datix Customized CybsSoapClient
 *
 * An implementation of PHP's SOAPClient class for making CyberSource requests.
 */
class CybsSoapClient extends SoapClient
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

function getUserIP()
{
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];

    if(filter_var($client, FILTER_VALIDATE_IP))
    {
        $ip = $client;
    }
    elseif(filter_var($forward, FILTER_VALIDATE_IP))
    {
        $ip = $forward;
    }
    else
    {
        $ip = $remote;
    }

    return $ip;
}