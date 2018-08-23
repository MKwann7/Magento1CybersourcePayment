<?php
// app/code/local/Envato/Custompaymentmethod/Block/Info/Custompaymentmethod.php
class CustomExtensions_Cybersource_Block_Info_Creditcard extends
    Mage_Payment_Block_Info
{
    protected $_isCheckoutProgressBlockFlag = true;

    protected function _construct ()
    {
        parent::_construct();
        $this->setTemplate('cybersource/info/creditcard.phtml');
    }

    protected function _prepareSpecificInformation ($transport = null)
    {
        Mage::log('Payment _prepareSpecificInformation = '.json_encode($this->getInfo()->getCybersourceExpirationDateMonth()),null,'cybersource_payment_saving_testing.log');

        if ( null !== $this->_paymentSpecificInformation )
        {
            return $this->_paymentSpecificInformation;
        }

        Mage::log('Payment _prepareSpecificInformation  '.json_encode($this->getInfo()->getCybersourceExpirationDateMonth()),null,'cybersource_payment_saving_testing.log');

        $data = array();
        if ( $this->getInfo()->getCybersourceCreditCardType() )
        {
            Mage::log('Payment GetInfo CreditCardType = '.json_encode($this->getInfo()->getCybersourceCreditCardType()),null,'cybersource_payment_saving_testing.log');
            $data[Mage::helper('payment')->__('Credit Card Type')] = $this->getInfo()->getCybersourceCreditCardType();
        }

        if ( $this->getInfo()->getCybersourceCreditCardNumber() )
        {
            Mage::log('Payment GetInfo CreditCardNumber = '.json_encode($this->getInfo()->getCybersourceCreditCardNumber()),null,'cybersource_payment_saving_testing.log');
            $data[Mage::helper('payment')->__('Credit Card Number')] = $this->getInfo()->getCybersourceCreditCardNumber();
        }

        if ( $this->getInfo()->getCybersourceExpirationDateMonth() )
        {
            Mage::log('Payment GetInfo ExpirationDateMonth = '.json_encode($this->getInfo()->getCybersourceExpirationDateMonth()),null,'cybersource_payment_saving_testing.log');
            $data[Mage::helper('payment')->__('Expiration Date Month')] = $this->getInfo()->getCybersourceExpirationDateMonth();
        }

        if ( $this->getInfo()->getCybersourceExpirationDateYear() )
        {
            Mage::log('Payment GetInfo ExpirationDateYear = '.json_encode($this->getInfo()->getCybersourceExpirationDateYear()),null,'cybersource_payment_saving_testing.log');
            $data[Mage::helper('payment')->__('Expiration Date Year')] = $this->getInfo()->getCybersourceExpirationDateYear();
        }

        if ( $this->getInfo()->getCybersourceCardVerificationNumber() )
        {
            Mage::log('Payment GetInfo VerificationNumber = '.json_encode($this->getInfo()->getCybersourceCardVerificationNumber()),null,'cybersource_payment_saving_testing.log');
            $data[Mage::helper('payment')->__('Card Verification Number')] = $this->getInfo()->getCybersourceCardVerificationNumber();
        }


        if ($ccType = $this->getCybersourceCreditCardType()) {
            Mage::log('Payment GetInfo CcTypeName = '.json_encode($this->getCybersourceCreditCardType()),null,'cybersource_payment_saving_testing.log');
            $data[Mage::helper('payment')->__('Credit Card Type')] = $ccType;
        }
        if ($this->getInfo()->getCybersourceCreditCardNumber()) {
            Mage::log('Payment GetInfo CcLast4 = '.json_encode($this->getCybersourceCreditCardNumber()),null,'cybersource_payment_saving_testing.log');
            $data[Mage::helper('payment')->__('Credit Card Number')] = sprintf('xxxx-%s', $this->getInfo()->getCybersourceCreditCardNumber());
        }

        $transport = parent::_prepareSpecificInformation($transport);

        return $transport->setData(array_merge($data, $transport->getData()));
    }

    public function getCards()
    {
        // Tracing GUID: f20109b4-eab7-4155-8192-4909bc23e55a
        $cardsData = $this->getMethod()->getCardsStorage()->getCards();
        $cards = array();

        if (is_array($cardsData)) {
            foreach ($cardsData as $cardInfo) {
                $data = array();
                if ($cardInfo->getProcessedAmount()) {
                    $amount = Mage::helper('core')->currency($cardInfo->getProcessedAmount(), true, false);
                    $data[Mage::helper('paygate')->__('Processed Amount')] = $amount;
                }
                if ($cardInfo->getBalanceOnCard() && is_numeric($cardInfo->getBalanceOnCard())) {
                    $balance = Mage::helper('core')->currency($cardInfo->getBalanceOnCard(), true, false);
                    $data[Mage::helper('paygate')->__('Remaining Balance')] = $balance;
                }
                $this->setCardInfoObject($cardInfo);
                $cards[] = array_merge($this->getSpecificInformation(), $data);
                $this->unsCardInfoObject();
                $this->_paymentSpecificInformation = null;
            }
        }
        if ($this->getInfo()->getCybersourceCreditCardType() && $this->_isCheckoutProgressBlockFlag) {
            // Tracing GUID: f20109b4-eab7-4155-8192-4909bc23e55a
            // This fires off,
            $cards[] = $this->getSpecificInformation();
        }
        return $cards;
    }
}