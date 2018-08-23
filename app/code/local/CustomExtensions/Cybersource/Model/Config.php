<?php
/**
 * Created by PhpStorm.
 * User: mzak
 * Date: 9/27/2016
 * Time: 4:00 PM
 */
class CustomExtensions_Cybersource_Model_Config extends Mage_Payment_Model_Config
{
    protected static $_methods;
    /**
     * Retrieve array of credit card types
     *
     * @return array
     */
    public function getCcTypes()
    {
        $_types = Mage::getConfig()->getNode('global/payment/cc/types')->asArray();

        uasort($_types, array('CustomExtensions_Cybersource_Model_Config', 'compareCcTypes'));

        $types = array();
        foreach ($_types as $data) {
            if (isset($data['code']) && isset($data['name'])) {
                $types[$data['code']] = $data['name'];
            }
        }
        return $types;
    }
}