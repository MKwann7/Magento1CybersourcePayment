<?php

/**
 * Created by Datix, LLC.
 * User: Micah Zak
 * Date: 9/23/2016
 * Time: 3:23 PM
 */
class CustomExtensions_Cybersource_Model_Customize_PaymentAction extends Mage_Payment_Model_Method_Abstract
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => CustomExtensions_Cybersource_Model_Method_Creditcard::ACTION_AUTHORIZE,
                'label' => 'Authorize Only'
            ),
            array(
                'value' => CustomExtensions_Cybersource_Model_Method_Creditcard::ACTION_AUTHORIZE_CAPTURE,
                'label' => 'Authorize and Capture'
            ),
        );
    }
}